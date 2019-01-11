<?php

class Store_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function store_data($params = [], $total = false)
    {
        $this->db->from('store s')
            ->join('commercial c', 'c.id=s.platform_id');

        if (!empty($params['where'])) {
            $this->db_where($params['where']);
        }
        if ($total) {
            $this->db->select('count(*) as cou');
            return $this->db->get()->row()->cou;
        }

        $this->db->select("s.*,c.name platform_name");
        $this->db->limit($params['limit'], $params['offset']);

        if (!empty($params['order_by'])) {
            $this->db->order_by($params['order_by']);
        }


        return $this->db->get()->result_array();
    }

    public function do_editable($table, $params)
    {
        return $this->db->update($table, [$params['field'] => $params['value']], ['id' => (int)$params['id']]);
    }

    public function do_add($params)
    {
        $this->db->insert('store', $params);
        return $this->db->insert_id();
    }

    public function get_code()
    {
        $id = (int)$this->db->select('max(id) as id')->get('store')->row()->id;
        return 'P' . str_pad($id + 1, 3, '0', STR_PAD_LEFT);
    }

    public function check_code($code, $platform_id){
        return !!$this->db->select('id')->where(['code'=> $code, 'platform_id' => $platform_id])->get('store')->row();
    }

    public function check_name($name, $platform_id){
        return !!$this->db->select('id')->where(['name'=> $name, 'platform_id' => $platform_id])->get('store')->row();
    }

    private function db_where($where = [])
    {
        if (!is_array($where)) {
            return;
        }
        foreach ($where as $k => $v) {
            if (is_array($v)) {
                $this->db->where_in($k, $v);
                continue;
            }
            $this->db->where($k, $v);
        }
    }
}