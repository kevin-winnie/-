<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Icon_model extends MY_Model
{
    public $redis;
    private $com_redis_pre = 'comercial_';

    function __construct()
    {
        parent::__construct();
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
    }

    function table_name()
    {
    	return 'icon';
    }

    /**
     * @param $platform_id 商户id
     * @param $icon_id  icon id
     */
    public function delete_icon($platform_id,$icon_id){
            if($icon_id == 1){
                $data = array(
                    'icon1_path'=>'',
                    'icon1_name'=>'',
                    'icon1_url'=>''
                );
            }else{
                $data = array(
                    'icon2_path'=>'',
                    'icon2_name'=>'',
                    'icon2_url'=>''
                );
            }
        $this->db->where('platform_id', $platform_id);
        $this->db->update($this->table_name(), $data);
        $this->db->set();
        return $this->db->affected_rows();
    }

    public function select_icon($platform_id){
        $sql = "select * from p_icon WHERE  platform_id = {$platform_id}";
        $icon_info = $this->db->query($sql)->row_array();
        return $icon_info;
    }
}
