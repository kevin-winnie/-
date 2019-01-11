<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Products extends MY_Controller {

    public $workgroup = 'products';
    private $secret_v2 = 'd50b6a5ff6ff4a3j814y6f6b97ec62ab';

    function __construct() {
        parent::__construct();
        $this->load->model("product_model");
        $this->load->model("product_class_model");
        $this->load->model("equipment_model");
        $this->load->model("commercial_model");
        $this->load->library('curl',null,'http_curl');
    }
    
    function labels_list() {
        $this->title = '商品标签列表';
        $this->page('products/labels_list.html');
    }
    
    function labels() {
        $this->title = '商品标签录入';
        $this->page('products/labels.html');
        
    }
    
    function labels_table(){
        $limit = $this->input->get('limit') ? : 10;
        $offset = $this->input->get('offset') ? : 0;
        $search_label = $this->input->get('search_label') ? : '';
        $product_name = $this->input->get('search_product_name') ? : '';
        
        $where = array();
        if ($search_label){
            $where['label'] = $search_label;
        }
        if($product_name){
            $where1['product_name like'] = '%'.$product_name.'%';
            $products = $this->product_model->getList($where1);
            $ids = "";
            foreach ($products as $p){
                $ids .= $p['id'].",";
            }
            if($ids != ""){
                $where['product_id'] = rtrim($ids,',');
            }
        }

        $array = $this->label_product_model->getLabels("", $where, $offset, $limit);
        
        $total = (int)$this->label_product_model->getLabels("count(*) as c",$where)[0]['c'];
        
        $result = array(
            'total' => $total,
            'rows' => $array,
        );
        echo json_encode($result);
    }

    function label_product_search(){

        $product_id = $this->input->get('product_id');
        $labels = $this->label_product_model->getLabelByProductId($product_id);
        $result = array(
            'total' => count($labels),
            'rows' => $labels
        );
        echo json_encode($result);
    }
    
    function labels_save(){
        $labels     = $this->input->post('labels');
        $product_id = $this->input->post('product_id');
        //查看product_id是否存在
        $where['id'] = $product_id;
        $product = $this->product_model->getProduct('*',$where);
        if (empty($product)){
            $this->showJson(array('status'=>'error', 'msg'=>'该商品id不存在'));
        }
        $arr=explode("\n",$labels);
        $i=0;
        foreach ($arr as $val){
            if (trim($val) != ''){
                $where_label['label'] = $val;
                $is_exist = $this->label_product_model->getLabelProduct($where_label);
                if ($is_exist){
                    $param['product_id']  = $product_id;
                    if(!$this->db->update('label_product', $param, array('id'=>$is_exist['id']))){
                        $this->showJson(array('status'=>'error', 'msg'=>'标签绑定失败，请重新绑定！'));
                    }else{
                        $i++;
                    }

                } else {
                    $param['label'] = trim($val);
                    $param['product_id']  = $product_id;
                    $param['created_time'] = time();
                    $this->db->insert('label_product',$param);
                    if($this->db->insert_id()>0){
                        $i++;
                    }else{
                        $this->showJson(array('status'=>'error', 'msg'=>'标签绑定失败，请重新绑定！'));
                    }
                }
            }
        }
        $this->showJson(array('status'=>'success', 'msg'=>$i.'个标签已成功绑定到商品'.$product_id.'上！'));
    }

    function index() {
        $this->title = '商品信息列表';
        $limit = $this->input->get('limit') ? : 10;
        $limit_class = 99;
        $offset = $this->input->get('offset') ? : 0;
        $where = array();
        $where_class = array('parent_id'=>0);
        $this->_pagedata ["list"] = $this->product_model->getProducts("", $where, $offset, $limit);
        $classList = $this->product_class_model->getProductClasses("", $where_class, 0, $limit_class);
        $show_class = '<ul>';
        foreach($classList as $eachClass){
            $show_class = $show_class.'<li class="parent_li" classid="'.$eachClass['id'].'">'.$eachClass['name'];
            $where_child_class = array('parent_id'=>$eachClass['id']);
            $child_classList = $this->product_class_model->getProductClasses("", $where_child_class, 0, $limit_class);
            $show_class .= "<ul>";
            foreach($child_classList as $eachChild){
                $show_class = $show_class.'<li classid="'.$eachChild['id'].'">'.$eachChild['name'].'</li>';
            }
            $show_class .= "</ul>";
            $show_class .= '</li>';
        }
        $show_class .= '</ul>';
        $this->_pagedata ['show_class'] = $show_class;
        $this->_pagedata ["treecss"] = base_url('assets/css/easyTree.css');
        $this->_pagedata ["treejs"] = base_url("assets/js/easyTree.js");
        $classList = $this->product_class_model->getProductClasses("", $where_class, 0, $limit_class);
        $show_class = '<select id = "search_class_id" name = "search_class_id" class = "form-control">';
        $show_class = $show_class.'<option value="-1">全部类目</option>';
        foreach($classList as $eachClass){
            $show_class = $show_class.'<optgroup label="'.$eachClass['name'].'">'.$eachClass['name'];
            $where_child_class = array('parent_id'=>$eachClass['id']);
            $child_classList = $this->product_class_model->getProductClasses("", $where_child_class, 0, $limit_class);
            foreach($child_classList as $eachChild){
                $show_class = $show_class.'<option value="'.$eachChild['id'].'">'.$eachChild['name'].'</option>';
            }
            $show_class .= '</optgroup>';
        }
        $show_class .= '</select>';
        
        $this->_pagedata['class_info'] = $show_class;
        
        $this->_pagedata['platforms'] = $this->commercial_model->get_all_platforms();
        
//      $this->_pagedata['list'] = $this->Admin_model->getLoginById ( $this->session->userdata('sess_admin_data') ["adminid"] );//最近十次登录查询
//      $this->_pagedata['list'] = $array;
        $this->page('products/index.html');
    }
    
    public function ajax_search_product(){
        if($this->input->is_ajax_request()){
            $limit = 10;
            $offset = 0;
            $search_name = $this->input->post('search_name') ? : '';
            $equipment_id = $this->input->post('equipment_id') ? : '';
            if (!$equipment_id){
                $this->showJson(array('status'=>'error'));
            }
            $where = array();
            if ($search_name){
                $where['id'] = $search_name;
            }
            $array = $this->product_model->getProducts("", $where, $offset, $limit);
            if ($array){
                $product_id = $array[0]['id'];
                //查看库存量
                $sql = 'select equipment_id from p_equipment where id = '.$equipment_id;
                $info = $this->db->query($sql)->row_array();
                $stock_data = $this->equipment_label_model->getStock($info['equipment_id']);
                $array[0]['stock_num'] = 0;
                foreach ($stock_data as $stock){
                    if ($stock['product_id'] == $product_id){
                        $array[0]['stock_num'] = $stock['count_num'] ? $stock['count_num'] : 0;
                    }
                }
                //查看预存量
                $shipping_config_list = $this->deliver_model->get_deliver_shipping_list($equipment_id);
                $array[0]['pre_num'] = 0;
                foreach($shipping_config_list as $config){
                    if ($config['product_id'] == $product_id){
                        $array[0]['pre_num'] = $config['pre_qty'] ? $config['pre_qty'] : 0;
                    }
                }
                //计算初始补货量
                $add_num = $array[0]['pre_num'] - $array[0]['stock_num'];
                $add_num = $add_num < 0 ? 0 : $add_num;
                $array[0]['add_num'] = $add_num;
            }
            $this->showJson(array('status'=>'success','product_info'=>$array));
        }
        $this->showJson(array('status'=>'error'));
    }

    
    public function table()
    {
        $limit = $this->input->get('limit') ? : 10;
        $offset = $this->input->get('offset') ? : 0;
        $search_name = $this->input->get('search_name') ? : '';
        $search_class_id = $this->input->get('search_class_id') ? : '';
        /* if ($this->input->get('search_is_paper_order') === '0'){
            $search_is_paper_order = 0;
        } else {
            $search_is_paper_order = $this->input->get('search_is_paper_order') ? : '';
        } */
        
        $search_tag = $this->input->get('search_tag') ? : '';
        $search_platform_id = $this->input->get('search_platform_id') ? : '';
        $where = array();
        if ($search_name){
            $where['product_name'] = $search_name;
        }
        if ($search_class_id){
            $where['class_id'] = $search_class_id;
        }
        if ($search_tag){
            $where['tag'] = $search_tag;
        }
        if ($search_platform_id){
            $where['platform_id'] = $search_platform_id;
        }
        /* if ($search_is_paper_order || $search_is_paper_order === 0){
            $where['is_paper_order'] = $search_is_paper_order;
        } */
        
        $platforms = $this->commercial_model->get_all_platforms();
        $arr_platform = array();
        foreach($platforms as $val){
            $arr_platform[$val['id']] = $val['name'];
        }
        $params = array(
            'timestamp'=>time() . '000',
            'source'    => 'platform',
            'where' => http_build_query($where),
            'platforms' => json_encode($arr_platform),
            'offset'=>$offset,
            'limit'=>$limit
        );
        $url = RBAC_URL."apiProducts/getProducts";
        
        $params['sign'] = $this->create_platform_sign($params);
        
        $options['timeout'] = 100;
        $result = $this->http_curl->request($url, $params, 'POST', $options);
        $result = json_decode($result['response'],1);
        $results = array(
            'total' => $result['total'],
            'rows' => $result['results'],
        );
        echo json_encode($results);
    }
    
    public function add(){
        $where_class = array('parent_id'=>0);
        $limit_class = 99;
        $classList = $this->product_class_model->getProductClasses("", $where_class, 0, $limit_class);
        $show_class = '<select style="width:230px;" id = "class_id" name = "class_id" class = "form-control">';
        foreach($classList as $eachClass){
            $show_class = $show_class.'<optgroup label="'.$eachClass['name'].'">'.$eachClass['name'];
            $where_child_class = array('parent_id'=>$eachClass['id']);
            $child_classList = $this->product_class_model->getProductClasses("", $where_child_class, 0, $limit_class);
            foreach($child_classList as $eachChild){
                $show_class = $show_class.'<option value="'.$eachChild['id'].'">'.$eachChild['name'].'</option>';
            }
            $show_class .= '</optgroup>';
        }
        $show_class .= '</select>';

        $this->_pagedata['class_info'] = $show_class;
        $this->page('products/add.html');
    }
    
    public function add_save(){
        $where_class = array('parent_id'=>0);
        $limit_class = 99;
        $classList = $this->product_class_model->getProductClasses("", $where_class, 0, $limit_class);
        $show_class = '<select style="width:230px;" id = "class_id" name = "class_id" class = "form-control">';
        foreach($classList as $eachClass){
            $show_class = $show_class.'<optgroup label="'.$eachClass['name'].'">'.$eachClass['name'];
            $where_child_class = array('parent_id'=>$eachClass['id']);
            $child_classList = $this->product_class_model->getProductClasses("", $where_child_class, 0, $limit_class);
            foreach($child_classList as $eachChild){
                $show_class = $show_class.'<option value="'.$eachChild['id'].'">'.$eachChild['name'].'</option>';
            }
            $show_class .= '</optgroup>';
        }
        $show_class .= '</select>';
        
        $this->_pagedata['class_info'] = $show_class;
        $param['product_id'] = 0;
        $is_oms = $this->input->post('is_oms');
        $param['class_id'] = intval($this->input->post('class_id'));
        $param['inner_code'] = $this->input->post('inner_code');
        $param['product_no'] = $this->input->post('product_no');
        $param['product_name']= $this->input->post('product_name');
        $param['price'] = $this->input->post('price');
        $param['old_price'] = $this->input->post('old_price');
        $param['volume'] = $this->input->post('volume');
        $param['unit'] = $this->input->post('unit');
        $param['tags'] = $this->input->post('tags');
        $param['serial_num'] = $this->input->post('serial_num');
        $param['preservation_time'] = $this->input->post('preservation_time');
        $param['created_time'] = time();
        $param['status'] = 1;
        $is_error = 0;
        if ($is_oms == 1) {
            if (!$param['product_no']){
                $is_error = 1;
                $this->_pagedata["tips"] = "请商品没有产品编码，请重新选择";
                $this->page('products/add.html');
                exit;
            }
            if (!$param['inner_code']){
                $is_error = 1;
                $this->_pagedata["tips"] = "该商品没有内部编码，请重新选择";
                $this->page('products/add.html');
                exit;
            }
            $time = time();
            $service = 'open.ifCityshopProduct';
            $params = array(
                'timestamp' => $time,
                'service' => $service,
                "product_no"=>$param['product_no'],
            );
            $params['sign'] = $this->create_sign_v2($params);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, 'http://nirvana.fruitday.com/openApi');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $result = json_decode($result);
            curl_close($ch);
            if ($result->result == '300'){
                $is_error = 1;
                $this->_pagedata["tips"] = "该商品不是城超商品";
                $this->page('products/add.html');
                exit;
            }
            //验证是否已有相同编码
            $sql = "SELECT * FROM p_product WHERE product_no='".$param['product_no']."' and status = 1";
            $info = $this->db->query($sql)->row_array();
            if ($info){
                $is_error = 1;
                $this->_pagedata["tips"] = "该编码商品已存在";
                $this->page('products/add.html');
                exit;
            }
        }
        
        if ($is_error == 0){
            $this->db->insert('product', $param);
            $product_id = $this->db->insert_id();
            if ($product_id > 0) {
                //添加图片
                if ($_FILES['img_url']['tmp_name']) {
                    $tag = rand_pass_new();
                    $photo_array = upload_pic2($product_id, $_FILES['img_url']['tmp_name'], $_FILES['img_url']['name'], 1, '01', $this->config->item('WEB_BASE_PATH') . 'images/box_products_img/', $tag);
                    $field = array(
                        'img_url' => $photo_array[1]
                    );
                }
                $this->db->update('product', $field, array('id'=>$product_id));
                redirect('/products/index');
            }
        }
        
        
    }
    
    
    public function edit(){
        $id = $this->uri->segment(3);
        $act = $this->uri->segment(2);
        $sql = "SELECT * FROM p_product WHERE id=$id";
        $info = $this->db->query($sql)->row_array();
        $where_class = array('parent_id'=>0);
        $limit_class = 99;
        $classList = $this->product_class_model->getProductClasses("", $where_class, 0, $limit_class);
        $show_class = '<select style="width:230px;" id = "class_id" name = "class_id" class = "form-control">';
        foreach($classList as $eachClass){
            $show_class = $show_class.'<optgroup label="'.$eachClass['name'].'">'.$eachClass['name'];
            $where_child_class = array('parent_id'=>$eachClass['id']);
            $child_classList = $this->product_class_model->getProductClasses("", $where_child_class, 0, $limit_class);
            foreach($child_classList as $eachChild){
                if ($info['class_id'] == $eachChild['id']){
                    $show_class = $show_class.'<option value="'.$eachChild['id'].'" selected=selected>'.$eachChild['name'].'</option>';
                } else {
                    $show_class = $show_class.'<option value="'.$eachChild['id'].'">'.$eachChild['name'].'</option>';
                }
                
            }
            $show_class .= '</optgroup>';
        }
        $show_class .= '</select>';
        
        $this->_pagedata['class_info'] = $show_class;
        
        $this->_pagedata['id'] = $id;
        $this->_pagedata['info'] = $info;
        $this->page('products/edit.html');
    }
    
    //编辑保存
    public function edit_save(){
        $id = $_POST['id'];
        $param['class_id'] = intval($this->input->post('class_id'));
        $param['product_name']= $this->input->post('product_name');
        $param['price'] = $this->input->post('price');
        $param['old_price'] = $this->input->post('old_price');
        $param['volume'] = $this->input->post('volume');
        //20170614新增可维护product_no
        $param['product_no'] = $this->input->post('product_no');
        $param['inner_code'] = $this->input->post('inner_code');
        if (!empty($param['product_no'])){
            if (empty($param['inner_code'])){
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("OMS编码和SAP编码必须同时为空或同时不为空！");location.href = "/products/edit/'.$id.'";</script></head>';
                exit;
            }
        }  
        if (!empty($param['inner_code'])){
            if (empty($param['product_no'])){
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("OMS编码和SAP编码必须同时为空或同时不为空！");location.href = "/products/edit/'.$id.'";</script></head>';
                exit;
            }
        }
        if ($param['inner_code'] && $param['product_no']){
            $time = time();
            $service = 'open.ifEditCityshopProduct';
            $params = array(
                'timestamp' => $time,
                'service' => $service,
                "product_no"=>$param['product_no'],
                'inner_code'=>$param['inner_code']
            );
            $params['sign'] = $this->create_sign_v2($params);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, 'http://nirvana.fruitday.com/openApi');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $result = json_decode($result);
            curl_close($ch);
            if ($result->result == '300'){
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该OMS编码与SAP编码对应的不是城超商品！");location.href = "/products/edit/'.$id.'";</script></head>';
                exit;
            }
        }
        
        $param['unit'] = $this->input->post('unit');
        $param['tags'] = $this->input->post('tags');
        $param['preservation_time'] = $this->input->post('preservation_time');

        $res = $this->db->update('product', $param, array('id'=>$id));
        $params = array();
        if ($_FILES['img_url']['tmp_name']) {
            $tag = rand_pass_new();
            $photo_array = upload_pic2($id, $_FILES['img_url']['tmp_name'], $_FILES['img_url']['name'], 1, '01', $this->config->item('WEB_BASE_PATH') . 'images/box_products_img/', $tag);
            $img_url = $photo_array[1];
        } else {
            $img_url = $_POST['old_img_url'];
        }
        $params['img_url'] = $img_url;
        $this->db->update('product', $params, array('id'=>$id));
        if ($res) {
            redirect('/products/index');
        } else {
            redirect('/products/edit/'.$id);
        }
    
    }
    
    public function delete($ids){
        $ids = urldecode($ids);
        $ids_arr = explode('|',$ids);
        $ids_arr = array_filter($ids_arr);
        //检查是不是有绑定标签
        /* foreach ($ids_arr as $id){
            $where['product_id'] = $id;
            $result = $this->label_product_model->getLabelProduct($where);
            if ($result){
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该商品还有对应绑定的标签！不能删除！");history.back();</script></head>';
                exit;
            }
        } */
        $rs = $this->product_model->product_update_where_in(array('status'=>0),array('platform_id'=>$this->platform_id),$ids_arr);
        
        redirect('/products/index');
    }
    
    //添加分类
    public function add_class(){
        if($this->input->is_ajax_request()){
            $param['parent_id'] = $this->input->post('parent_class_id') ? $this->input->post('parent_class_id') : 0;
            $param['name']  = $this->input->post('class_name');
            $param['ctime'] = time();
            if ($this->db->insert('product_class',$param)){
                $result = $this->db->insert_id();
                //done 打cityboxadmin接口 插入商品分类
                $params = array(
                    'timestamp'=>time() . '000',
                    'source'    => 'platform',
                    'id'=> $result,
                    'name'=>$param['name'],
                    'parent_id'=>$param['parent_id']
                );
                $url = RBAC_URL."apiProducts/addProductClass";
                
                $params['sign'] = $this->create_platform_sign($params);
                
                $options['timeout'] = 100;
                $result = $this->http_curl->request($url, $params, 'POST', $options);
                if(json_decode($result['response'],1)['code']==200){
                    $this->showJson(array('status'=>'success','class_id'=>$result, 'class_name'=>$param['name'], 'parent_class_id'=>$param['parent_class_id']));
                } else {
                    $this->showJson(array('status'=>'error'));
                }
            }
        }
        $this->showJson(array('status'=>'error'));
    }
    
    //编辑分类
    public function edit_class(){
        if($this->input->is_ajax_request()){
            $id = $this->input->post('classid');
            $param['name']  = $this->input->post('class_name');
            if($this->db->update('product_class', $param, array('id'=>$id) )){
                //done 打cityboxadmin接口 修改商品分类
                $params = array(
                    'timestamp'=>time() . '000',
                    'source'    => 'platform',
                    'id'=> $id,
                    'name'=>$param['name']
                );
                $url = RBAC_URL."apiProducts/editProductClass";
                
                $params['sign'] = $this->create_platform_sign($params);
                
                $options['timeout'] = 100;
                $result = $this->http_curl->request($url, $params, 'POST', $options);
                if(json_decode($result['response'],1)['code']==200){
                    $this->showJson(array('status'=>'success'));
                } else {
                    $this->showJson(array('status'=>'error'));
                }
            }
        }
        $this->showJson(array('status'=>'error'));
        
    }
    
    //删除分类
    public function delete_class(){
        if($this->input->is_ajax_request()){
            $id = $this->input->post('classid');
            //查看是否能删除
            $params = array(
                'timestamp'=>time() . '000',
                'source'    => 'platform',
                'id'=> $id
            );
            $url = RBAC_URL."apiProducts/ifDeleteProductClass";
            
            $params['sign'] = $this->create_platform_sign($params);
            
            $options['timeout'] = 100;
            $result = $this->http_curl->request($url, $params, 'POST', $options);
            
            if(json_decode($result['response'],1)['code']==200){
                if($this->db->delete('product_class', array('id'=>$id) )){
                //done 打cityboxadmin接口 删除商品分类
                    $params = array(
                        'timestamp'=>time() . '000',
                        'source'    => 'platform',
                        'id'=> $id
                    );
                    $url = RBAC_URL."apiProducts/deleteProductClass";
                    
                    $params['sign'] = $this->create_platform_sign($params);
                    
                    $options['timeout'] = 100;
                    $result = $this->http_curl->request($url, $params, 'POST', $options);
                    if(json_decode($result['response'],1)['code']==200){
                        $this->showJson(array('status'=>'success'));
                    } else {
                        $this->showJson(array('status'=>'error'));
                    }
                }
            } else {
                $show_msg = '';
                if (isset(json_decode($result['response'],1)['ids'])){
                    $ids = json_decode(json_decode($result['response'],1)['ids']);
                    $where = array();
                    $where['id'] = $ids;
                    $platforms = $this->commercial_model->getList("*", $where);
                    $platform_names = array();
                    foreach($platforms as $val){
                        $platform_names[] = $val['name'];
                    }
                    $show_platforms = implode(',',$platform_names);
                    $show_msg = $show_platforms.'还有该分类下的商品，无法删除！'; 
                }
                
                $this->showJson(array('status'=>'error','msg'=>$show_msg));
            }
            
        }
        $this->showJson(array('status'=>'error'));
    
    }
    
    public function search_product(){
        if ($this->input->is_ajax_request()){
            $product_no = $this->input->post('product_no');
            $time = time();
            $service = 'open.importCityshopProduct';
            $params = array(
                'timestamp' => $time,
                'service' => $service,
                "product_no"=>$product_no,
            );
            $params['sign'] = $this->create_sign_v2($params);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, 'http://nirvana.fruitday.com/openApi');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $json_result = json_decode($result);
            $product_data = $json_result->product_data;
            $groupFlag = $product_data->groupFlag ? unserialize($product_data->groupFlag) : '';
            $warehouse = $product_data->warehouse ? unserialize($product_data->warehouse) : '';
            if ($groupFlag){
                if (!in_array(2,$groupFlag) || !in_array(3,$groupFlag)){
                    $result = json_encode(array('result'=>500,'msg'=>'必须是城超和express商品！'));
                }
            } else {
                $result = json_encode(array('result'=>500,'msg'=>'必须是城超和express商品！'));
            }
            if ($warehouse){
                if (!in_array(5,$warehouse) || !in_array(10,$warehouse)){
                    $result = json_encode(array('result'=>500,'msg'=>'必须是上海和北京大仓！'));
                }
            } else {
                $result = json_encode(array('result'=>500,'msg'=>'必须是上海和北京大仓！'));
            }
            echo $result;
        }
    }
    
    public function create_sign_v2($params) {
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . $this->secret_v2), 0, -1) . 'w');
        return $sign;
    }

    public function ajax_search_proudct_by_id(){
        $id = $this->input->post('id');
        $sql = "SELECT * FROM p_product WHERE id=$id";
        $info = $this->db->query($sql)->row_array();
        if($info){
            echo $info['product_name'];
        }
        echo "";

    }

    public function ajax_search_proudct_by_name(){
        $name = $this->input->post('name');
        $sql = "SELECT * FROM p_product WHERE product_name like '%$name%' and status = 1";
        $info = $this->db->query($sql)->result_array();
        if($info){
           echo json_encode($info);
        }
        echo "";

    }
    
    public function export(){
        $search_name = $this->input->get('search_name') ? : '';
        $search_class_id = $this->input->get('search_class_id') ? : '';
        if ($this->input->get('search_is_paper_order') === '0'){
            $search_is_paper_order = 0;
        } else {
            $search_is_paper_order = $this->input->get('search_is_paper_order') ? : '';
        }
        $search_tag = $this->input->get('search_tag') ? : '';
        $where = array();
        if ($search_name){
            $where['name'] = $search_name;
        }
        if ($search_class_id){
            $where['class_id'] = $search_class_id;
        }
        if ($search_tag){
            $where['tag'] = $search_tag;
        }
        if ($search_is_paper_order || $search_is_paper_order === 0){
            $where['is_paper_order'] = $search_is_paper_order;
        }
        
        $limit = $this->input->get('limit') ? : 999;
        $offset = $this->input->get('offset') ? : 0;
        $array = $this->product_model->getProducts("", $where, $offset, $limit);
        $data[] = array('商品类目','商品名称','OMS编码','SAP编码','商品价格','商品原价','规格','计量单位','保鲜期（天）');
            
        foreach($array as $m=>$eachProduct){
            $data[] = array($eachProduct['class_name'],$eachProduct['product_name'],$eachProduct['product_no'],$eachProduct['inner_code'],$eachProduct['price'],$eachProduct['old_price'],$eachProduct['volume'],$eachProduct['unit'],$eachProduct['preservation_time']);
        }
        
        @set_time_limit(0);
        $this->load->library("Excel_Export");
        $exceler = new Excel_Export();
        $exceler->setFileName('商品导出.csv');
        $exceler->setContent($data);
        $exceler->toCode('GBK');
        $exceler->charset('utf-8');
        $exceler->export();
        exit;
    }
}
