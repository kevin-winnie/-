<?php if (!defined('BASEPATH')) exit ('No direct script access allowed');

class Reconciliation_model extends MY_Model
{
    public function __construct(){
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }
    public function table_name()
    {
        return 'reconciliation';
    }

    /**
     * 获取下级地区
     *
     * @return void
     * @author
     **/
    public function getSonRegions($pid)
    {
        $rows = $this->c_db->select('AREAIDS,PARENTAREAIDS,AREANAME')
            ->from('sys_regional')
            ->where('PARENTAREAIDS', $pid)
            ->get()
            ->result_array();

        return $rows;
    }

    public function existSonRegions($pid)
    {
        $count = $this->c_db->select('AREAIDS')
            ->from('sys_regional')
            ->where('PARENTAREAIDS', $pid)
            ->count_all_results();

        return $count > 0 ? true : false;
    }

    /**
     * 获取地区类型
     *
     * @return void
     * @author
     **/
    public function getRegionType($id)
    {
        static $depth;

        $depth++;

        $region = $this->getRegion($id);

        if ($region['PARENTAREAIDS'] == 0) {

            switch ($depth) {
                case 1:
                    $type = 'province';
                    break;
                case 2:
                    $type = 'city';
                    break;
                case 3:
                    $type = 'area';
                    break;
                default:
                    $type = 'default';
                    break;
            }

            $depth = 0;

            return $type;
        }

        return $this->getRegionType($region['AREAIDS']);
    }

    /**
     * 获取行政区
     *
     * @return void
     * @author
     **/
    public function getRegion($id)
    {
        static $regions;

        if ($regions[$id]) return $regions[$id];

        $regions[$id] = $this->c_db->select('*')
            ->from('sys_regional')
            ->where('AREAIDS', $id)
            ->get()
            ->row_array();
        return $regions[$id];
    }

    /**
     * 获取上级行政区
     *
     * @return void
     * @author
     **/
    public function getParents($id)
    {
        static $regions;

        $r = $this->getRegion($id);
        $regions[] = $r;
        if ($r['PARENTAREAIDS'] == 0) return $regions;

        return $this->getParents($r['PARENTAREAIDS']);
    }

    public function getRegionInfo($id, $field='')
    {

        $regions = $this->c_db->from('sys_regional')
            ->where('AREAIDS', $id)
            ->get()
            ->row_array();
        return $regions[$field];
    }

    public function get_list($array,$type)
    {;

        $this->db->select('*');
        $this->db->from('reconciliation');
        if($type == 1)
        {
            $this->db->where_in('shou_platform_id', $array);
        }else
        {
            $this->db->where_in('shou_agent_id', $array);
        }
        $rs = $this->db->get()->result_array();
        return $rs;
    }

    /**
     * @param $where 搜索条件
     */
    public function get_search_reconLists($where,$platform_array,$agent_array)
    {
        $sql = " select * from p_reconciliation WHERE 1=1";
        if($where['start_time'])
        {
            $sql .= " and start_time >= '{$where['start_time']}'";
        }
        if($where['end_time'])
        {
            $sql .= " and end_time <= '{$where['end_time']}'";
        }
        if(isset($where['type']))
        {
            $sql .= " and type = '{$where['type']}'";
        }
        //收账代理商
        if($where['agent_id'] && $where['type'] == 1 )
        {
            $sql .= " and shou_agent_id = '{$where['agent_id']}'";
        }
        //收账商户
        if($where['platform_id'] && $where['type'] == 1 )
        {
            $sql = str_replace("and shou_agent_id = '{$where['agent_id']}'"," ",$sql);
            $sql .= " and shou_platform_id = '{$where['platform_id']}'";
        }
        if($where['platform_id'] && $where['type'] == 0)
        {
            return array();
        }
        //出账代理商
        if($where['agent_id'] && $where['type'] == 0)
        {
            $sql .= " and to_where_id = '{$where['agent_id']}'";
        }
        if($where['agent_id'] && $where['type'] == 0 && $where['platform_id'])
        {
            return array();
        }
        $rs = $this->db->query($sql)->result_array();
        return $rs;
    }

}

?>
