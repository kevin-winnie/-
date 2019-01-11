<?php

class Refer_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function all()
    {
        $rows = $this->db->from('refer')->get()->result_array();
        return array_column($rows, 'short_name', 'refer');
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