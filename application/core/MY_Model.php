<?php
class MY_Model extends CI_Model{

    /**
     * @param int $db_type  gold:gold库  sns:sns_db库
     */
    public function __construct($db_type = null) {

        if ($db_type == 'sns') {
            $this->db = $this->sns_db;
        }
    }

	/**
	 * 表名
	 *
	 * @return void
	 * @author
	 **/
	public function table_name()
	{
		// 需要实现
	}

	public function _filter($filter = array())
    {
        if ($filter) {
            foreach ($filter as $key => $value) {
                if (!is_numeric($key)) {
                    if (is_array($value)) {
                        $this->db->where_in($key, $value);
                    } else {
                        $this->db->where($key, $value);
                    }
                } else {
                    $this->db->where($value, null, false);
                }
            }
        }
    }

    /**
	* 获取
	*
	* @return void
	* @author
	**/
	public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby='', $like=''){

		$this->db->reconnect();

		$this->db->select($cols);
		$this->_filter($filter);
        if ($like) {
            $this->db->like($like);
        }
		$this->db->from($this->table_name());
		if ($orderby) $this->db->order_by($orderby);
		if ($limit < 0) $limit = '4294967295';
		$this->db->limit($limit,$offset);
		$list = $this->db->get()->result_array();
		return $list ? $list : array();
	}

	/**
	* 更新
	*
	* @return void
	* @author
	**/
	public function update($set,$filter){
		$this->db->reconnect();
		$this->_filter($filter);
		$res = $this->db->update($this->table_name(),$set);
		return $res;
//		return ($res == true) ? $this->db->affected_rows() : false;
	}

	/**
	* undocumented function
	*
	* @return void
	* @author
	**/
	public function dump($filter,$cols='*'){

		$this->db->reconnect();
		$this->db->select($cols);
		$this->_filter($filter);
		$this->db->from($this->table_name());
		$this->db->limit(1,0);
		$list = $this->db->get()->row_array();
		return $list;
	}

	/**
	* 插入
	*
	* @return void
	* @author
	**/
	public function insert($data){
		$this->db->reconnect();
		$rs = $this->db->insert($this->table_name(),$data);
		return $rs ? $this->db->insert_id() : 0;
	}

    /**
     * 插入多条
     *
     * @return void
     * @author
     **/
    public function insert_batch($data){
        $this->db->reconnect();
        $this->db->insert_batch($this->table_name(),$data);
        return $this->db->affected_rows();
    }

	/**
	* 计算数量
	*
	* @return void
	* @author
	**/
	public function count($filter = array(), $like = ''){
        $this->db->reconnect();
        $this->db->select('1');
		$this->_filter($filter);
        if ($like) {
            $this->db->like($like);
        }
		$count = $this->db->count_all_results($this->table_name());
		return $count;
	}

	/**
	* 删除
	*
	* @return void
	* @author
	**/
	public function delete($filter){
		$this->db->reconnect();
		$this->_filter($filter);
		$res = $this->db->delete($this->table_name());
		return ($res == true) ? $this->db->affected_rows() : false;
	}

    public function count_data() {
        $this->db->from($this->table);
        $this->db->where(array('is_del' => 0));
        $total = $this->db->count_all_results();
        return $total;
    }

    public function get_extra_sechma()
    {
    	return array();
    }

    public function get_sechma()
    {
    	return array();
    }
}