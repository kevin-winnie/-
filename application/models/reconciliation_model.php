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
    {
        $this->db->select('*');
        $this->db->from('reconciliation');
        $this->db->where('type', $type);
        $this->db->where_in('agent_commer_id', $array);
        $rs = $this->db->get()->result_array();
        return $rs;
    }

}

?>
