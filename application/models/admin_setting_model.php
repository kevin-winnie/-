<?php

class Admin_setting_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
        $this->c_db = $this->load->database('citybox_master',true);
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
            if (is_null($v)) {
                $this->db->where($k, null, true);
                continue;
            }
            $this->db->where($k, $v);
        }
    }

    public function data($params = [], $total = false)
    {
        $this->db->from('commercial c');

        if (!empty($params['where'])) {
            $this->db_where($params['where']);
        }
        if ($total) {
            $this->db->select('count(*) as cou');
            return $this->db->get()->row()->cou;
        }

        $this->db->select("c.*");
        $this->db->limit($params['limit'], $params['offset']);

        if (!empty($params['order_by'])) {
            $this->db->order_by($params['order_by']);
        }
        return $this->db->get()->result_array();
    }
    public function is_exits($platform_id)
    {
        $sql = " select * from s_group WHERE platform_id = '{$platform_id}'";
        return $this->c_db->query($sql)->row_array();
    }

    public function get_admin_id($name)
    {
        $sql = " select * from s_admin WHERE name = '{$name}' ";
        $rs = $this->c_db->query($sql)->row_array();
        return $rs['id'];
    }

    public function is_admin_exits($admin_id)
    {
        $sql = " select * from cb_s_admin_group WHERE admin_id = '{$admin_id}'";
        return $this->c_db->query($sql)->row_array();
    }
}