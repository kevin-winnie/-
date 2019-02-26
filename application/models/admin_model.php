<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Admin_model extends CI_Model
{
	private $flag = "11,12";//设置账号一定有特定的权限
	function __construct()
	{
		parent::__construct ();
    }

	function getAdmin($name)
	{
		$sql = "select * from s_admin where name='".$name."'";
		$res = $this->db->query($sql)->row_array();

		if ($res) {
            $str_id = '';
            $strflag_id = '';

//			if($res['is_s_admin']==1){
//                $res['flag'] = $this->flag;
//			}else{
                $id = $res['id'];
                $sql2 = "select * from s_admin_group where admin_id=$id";
                $res2 = $this->db->query($sql2)->result_array();
                foreach ($res2 as $key => $value) {
                    $str_id .= ','.$value['group_id'];
                }
                $str_id = ltrim($str_id,',');
                if(!empty($res2)){
                    $sql3 = "select flag from s_group where id in(".$str_id.")";
                    $res3 = $this->db->query($sql3)->result_array();
                    foreach ($res3 as $key => $value) {
                        $strflag_id .= ','.$value['flag'];
                    }
                    $strflag_id = ltrim($strflag_id,',');
                    $res['flag'] = $strflag_id;
                }else{
                    $res['flag'] = '';
                }

//			}
			return $res;
		} else {
            return array();
        }
	}

	function getAdminName($name)
	{
		$sql = "select s_admin.*,s_group.flag,s_group.name gname from s_admin
			left join s_group on s_group.id=s_admin.groupid
			where s_admin.name = '".$name."'";
		$query = $this->db->query($sql);
		return $query->row();
	}

	// function getUserList($groupid=0, $is_lock='-1', $lock_limit='-1')
	// {
	// 	$where = " where 1=1";
	// 	if(!empty($groupid)){
	// 		$where .= "  and s_admin.groupid=$groupid ";
	// 	}
	// 	if($is_lock!=-1){
	// 		$where .= "  and s_admin.is_lock=$is_lock ";
	// 	}
	// 	if($lock_limit!=-1){
	// 		$where .= "  and s_admin.lock_limit=$lock_limit ";
	// 	}
	// 	$sql = "select s_admin.*,s_group.flag,s_group.name gname from s_admin
	// 		left join s_group on s_group.id=s_admin.groupid
	// 		$where
	// 		order by s_admin.groupid desc,s_admin.id desc";
	// 	$query = $this->db->query ( $sql );
	// 	return $query->result ();
	// }

	function getUserList($groupid=0, $is_lock='-1', $lock_limit='-1',$name='',$alias='',$mobile='',$platform_id='')
	{
		$where = " where 1=1 and platform_id = '{$platform_id}'";
		if($groupid>0){
			$where .= "  and s_admin_group.group_id=$groupid ";
		}
		if($is_lock!=-1){
			$where .= "  and s_admin.is_lock=$is_lock ";
		}
		if($lock_limit!=-1){
			$where .= "  and s_admin.lock_limit=$lock_limit ";
		}

        if($name){
            $where .= "  and s_admin.name='".$name."' ";
        }
        
        if ($alias){
            $where .= "  and s_admin.alias='".$alias."' ";
        }
        
        if ($mobile){
            $where .= "  and s_admin.mobile='".$mobile."' ";
        }

		// $sql = "select s_admin.*,s_admin_group.group_id,s_group.flag
		// 	    from s_admin left join
		// 		s_admin_group on s_admin_group.admin_id=s_admin.id
		// 		left join s_group on s_admin_group.group_id=s_group.id $where";
        $sql = "select s_admin.*
			    from s_admin left join
				s_admin_group on s_admin_group.admin_id=s_admin.id $where group by s_admin.id";
		$res = $this->db->query($sql)->result_array();
		foreach ($res as $key => $value) {
			$group_name = '';
			$sql2 = "select s_admin_group.admin_id,s_admin_group.group_id,s_group.name from s_admin_group left join s_group on s_admin_group.group_id=s_group.id where s_admin_group.admin_id=".$value['id'];
			$res2 = $this->db->query($sql2)->result_array();
			foreach ($res2 as $k => $v) {
				$group_name .= ','.$v['name'];
			}
			$group_name = ltrim($group_name,',');
			$res[$key]['group_name'] = $group_name;
		}

		return $res;

	}

	function getUser($id){
		$sql = "select id,name,groupid,alias,is_lock,is_first,mobile,id_card,email from s_admin where id=".$id;
		$query = $this->db->query($sql);
		return $query->row();
	}

	function getUserb($id){
		$sql = "select s.id,s.name,s.alias,s.is_lock,s.is_first,sg.group_id from s_admin as s left join s_admin_group as sg on s.id=sg.admin_id where s.id=$id";
		$res = $this->db->query($sql)->result_array();
		return $res;
	}

	function getUserListb($search_group_id){

		if (empty($search_group_id) || !is_array($search_group_id)) {
			return array();
		}

		$group_id_str = "(".implode(",", $search_group_id).")";

		$sql = "select s_admin.*,s_admin_group.group_id,s_group.flag
			    from s_admin left join
				s_admin_group on s_admin_group.admin_id=s_admin.id
				left join s_group on s_admin_group.group_id=s_group.id where s_admin_group.group_id in ".$group_id_str;
		$res = $this->db->query($sql)->result_array();
		foreach ($res as $key => $value) {
			$group_name = '';
			$sql2 = "select s_admin_group.admin_id,s_admin_group.group_id,s_group.name from s_admin_group left join s_group on s_admin_group.group_id=s_group.id where s_admin_group.admin_id=".$value['id'];
			$res2 = $this->db->query($sql2)->result_array();
			foreach ($res2 as $k => $v) {
				$group_name .= ','.$v['name'];
			}
			$group_name = ltrim($group_name,',');
			$res[$key]['group_name'] = $group_name;
		}

		return $res;
	}

	function getStore($adminid){
		$adminUser = $this->getUser($adminid);
		if($adminUser->groupid==6){
			//$adminid = 22;
			return array();
		}
		$this->db->select('store_id');
		$this->db->from("s_admin_store");
		$this->db->where('admin_id',$adminid);
		$stores = $this->db->get()->result_array();
		if(empty($stores)){
			return array();
		}
		$result = array();
		foreach ($stores as $key => $value) {
			$result[] = $value['store_id'];
		}
		return $result;
	}
	function getGroups($adminid){
        $this->db->dbprefix = '';
		$this->db->select('group_id');
		$this->db->from("s_admin_group");
		$this->db->where('admin_id',$adminid);
		$groups = $this->db->get()->result_array();
		if(empty($groups)){
			return array();
		}
		$result = array();
		foreach ($groups as $key => $value) {
			$result[] = $value['group_id'];
		}
		return $result;
	}

	function getFunc($adminid){
		$this->db->select('func_id');
		$this->db->from("s_admin_func");
		$this->db->where('admin_id',$adminid);
		$stores = $this->db->get()->result_array();
		if(empty($stores)){
			return array();
		}
		$result = array();
		foreach ($stores as $key => $value) {
			$result[] = $value['func_id'];
		}
		return $result;
	}

	function getGroupList($platform_id)
	{
		$sql = "select * from s_group WHERE platform_id = '{$platform_id}' order by id desc";
		$query = $this->db->query ($sql);
		return $query->result();
	}
	function getGroupList_arr($platform_id)
	{
		$sql = "select * from s_group WHERE platform_id = '{$platform_id}' order by id desc";
		$query = $this->db->query ($sql);
		return $query->result_array();
	}
	function getFuncList()
	{
		$sql = "select * from s_func order by id desc";
		$query = $this->db->query ( $sql );
		return $query->result ();
	}

	function insServerUser($names,$pwd){
		if(empty($names)){
			return false;
		}
		$curr_time = time();
		$sql = "insert into s_admin(`name`,`pwd`,`groupid`,`ctime`,`alias`,`lock_limit`,`is_lock`,`utime`) values";
		foreach($names as $val){
			if(empty($val))continue;
			$sql_ins[] = "('".$val."','".md5($pwd)."','6','".$curr_time."','".$val."','0','1','0')";
		}
		$sql .= join(",",$sql_ins);
		return $this->db->query ( $sql );
	}
	function insGuanUser($names,$pwd){
		if(empty($names)){
			return false;
		}
		$curr_date = date("Y-m-d H:i:s");
		$sql = "insert into `cb_operation`(`user_name`,`user_password`,`permissions`,`operate`,`reg_time`,`last_time`,`is_lock`)  values";
		foreach($names as $val){
			if(empty($val))continue;
			$sql_ins[] = '(\''.$val.'\',\''.md5($pwd).'\',\'a:9:{i:0;s:16:"apealquality.php";i:1;s:8:"user.php";i:2;s:11:"usersms.php";i:3;s:9:"order.php";i:4;s:18:"pro_card_order.php";i:5;s:9:"trade.php";i:6;s:11:"comment.php";i:7;s:17:"card_exchange.php";i:8;s:20:"card_info_export.php";}\',\'a:10:{s:8:"orderdel";i:0;s:8:"tradedel";i:0;s:8:"tradeadd";i:0;s:6:"vipadd";i:0;s:6:"vipdel";i:0;s:8:"storedel";i:0;s:8:"storeadd";i:0;s:7:"userdel";i:0;s:10:"tradereset";i:0;s:11:"user_jf_add";i:0;}\',\''.$curr_date.'\',\''.$curr_date.'\',\'1\')';
		}
		$sql .= join(",",$sql_ins);
		return $this->db->query ( $sql );
	}

	function insertLogin($adminId, $loginIP)
	{
		$currTime = time();
		$sql = "insert into s_adminlogin(adminId,loginIP,ctime) values($adminId,'$loginIP','$currTime')";
		$this->db->query ( $sql );
	}

	function getLoginById($adminId)
	{
		$sql = "select a.*,b.name from s_adminlogin a left join s_admin b on b.id = a.adminId
		        where a.adminId = $adminId order by a.id desc limit 10 ";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	function getFlag($groupid)
	{
		$sql = "select flag from s_group where id = $groupid";
		$query = $this->db->query ( $sql );
		return $query->row ();
	}

	function updateLock($id,$lock_limit){
		$sql = "update s_admin set lock_limit = ".$lock_limit." where id = $id ";
		$this->db->query ( $sql );
	}

	function updateFlag($flag, $groupid)
	{
		$sql = "update s_group set flag = '$flag' where id = $groupid ";
		$this->db->query ( $sql );
	}

	function updateLoginTime($id){
		$curr_time = time();
		$sql = "update s_admin set utime = ".$curr_time." where id = $id ";
		$this->db->query ( $sql );
	}

	function delAdmin($adminid) {
		$sql = "delete from s_admin where id = $adminid";
		$this->db->query ( $sql );

		$sql3 = "delete from s_admin_group where admin_id=$adminid";
		$this->db->query ( $sql3 );

	}

	function delGroup($id) {
		$sql = "delete from s_group where id = $id";
		$this->db->query ( $sql );
	}

	function delFunc($id) {
		$sql = "delete from s_func where id = $id";
		$this->db->query ( $sql );
	}

	function delAdminGroup($id){
		$sql = "delete from s_admin_group where admin_id=$id";
		$this->db->query($sql);
	}
	function inseerAdminGroup($id,$groupid){
		$result = array();
		foreach ($groupid as $key => $value) {
			$result[] = array(
				'admin_id'=>$id,
				'group_id'=>$value
			);
		}
        $this->db->dbprefix = '';
        $this->db->insert_batch('s_admin_group',$result);
	}

	function insertAdmin($name, $pwd, $alias, $mobile, $id_card, $email ,$grade,$platfrom_id )
	{

		$sql = "select count(0) a from s_admin where name = '$name'";
		if ($this->db->query($sql)->row()->a == 0)
		{
			$ctime = time();
			$sql = "insert into s_admin(name,pwd,ctime,alias,mobile,id_card,email,grade,platform_id) values('$name','".md5($pwd)."',$ctime,'$alias','$mobile','$id_card','$email','$grade','$platfrom_id')";
			$this->db->query($sql);
			return $this->db->insert_id();
		}
		else
		{
			return - 1;
		}
	}

	function insertGroup($name,$platform_id)
	{
		$sql = "select count(0) a from s_group where name = '$name' and platform_id ='$platform_id'";
		if ($this->db->query($sql)->row()->a == 0)
		{
			$ctime = time();
			$sql = "insert into s_group(name,ctime,platform_id) values('$name',$ctime,'$platform_id')";
			$this->db->query($sql);
			return 0;
		}
		else
		{
			return - 1;
		}
	}

	function insertFunc($name)
	{
		$sql = "select count(0) a from s_group where name = '$name'";
		if ($this->db->query($sql)->row()->a == 0)
		{
			$ctime = time();
			$sql = "insert into s_func(name,ctime) values('$name',$ctime)";
			$this->db->query($sql);
			return 0;
		}
		else
		{
			return - 1;
		}
	}

	function insertAdminStore($adminid,$store_ids){
		foreach ($store_ids as $key => $value) {
			$insert_data[] = array(
				'admin_id'=>$adminid,
				'store_id'=>$value
			);
		}
		$this->db->insert_batch('s_admin_store', $insert_data);
	}

	function insertAdminFunc($adminid,$func_ids){
		foreach ($func_ids as $key => $value) {
			$insert_data[] = array(
				'admin_id'=>$adminid,
				'func_id'=>$value
			);
		}
		$this->db->insert_batch('s_admin_func', $insert_data);
	}

	function insertAdminGroup($adminid, $group_ids){
        $this->db->dbprefix = '';
        foreach ($group_ids as $key => $value) {
			$insert_data[] = array(
				'admin_id'=>$adminid,
				'group_id'=>$value
			);
		}
		$this->db->insert_batch('s_admin_group', $insert_data);
	}

	function updateAdminStore($adminid,$store_ids){
		$this->db->delete('s_admin_store',array('admin_id'=>$adminid));
		$this->insertAdminStore($adminid,$store_ids);
	}
	function updateAdminPwd($id,$pwd){
		$sql = "update s_admin set pwd = '".md5($pwd)."',is_lock=1 where id = $id";
		$res = $this->db->query ( $sql );
		return $res;
	}

	function updateAdminFunc($adminid,$func_ids){
		$this->delAdminFunc($adminid);
		$this->insertAdminFunc($adminid,$func_ids);
	}
	function delAdminFunc($adminid){
		//$this->db->delete('s_admin_func',array('admin_id'=>$adminid));
	}

	function changePwd($adminid, $oldpwd, $newpwd)
	{
		$sql = "select count(0) c from s_admin where id = $adminid and pwd = '".md5($oldpwd). "'";
		$row = $this->db->query($sql)->row();
		if($row->c > 0)
		{
			$sql = "update s_admin set pwd = '".md5($newpwd)."',is_lock=0 where id = $adminid";
			$this->db->query($sql);
			return 0;
		}
		else
		{
			return - 1;
		}
	}

	function updateAdminGroup($ids,$groupid){
		if(empty($ids)){
			return false;
		}
		$sql = "update s_admin set groupid = '$groupid' where id in (".join(',',$ids).") ";
		$res = $this->db->query ( $sql );
		return $res;
	}

	function upUser($id,$alias,$is_first=0,$mobile,$id_card,$email){
		if(empty($id)){
			return false;
		}
		$sql = "update s_admin set alias='$alias',is_first='$is_first',mobile = '$mobile',id_card='$id_card',email='$email' where id=$id";
		$res = $this->db->query ( $sql );
		return $res;
	}

    function update($id,$data){
        $sql = "update s_admin set {$data['key']} = {$data['val']} where id in ( $id )";
        return $this->db->query ($sql);
    }

	/**
	 * 下级代理商
	 */
	public function getAgentList($platform_id)
	{
		$sql = " select * from p_agent WHERE  id = '{$platform_id}'";
		return $this->db->query ($sql)->result_array();
	}

	public function get_adminuser($name,$platform_id)
	{
		$sql = "select * from s_admin where name='".$name."' and platform_id = '{$platform_id}' ";
		return $this->db->query($sql)->row_array();
	}

	function update_pwd($id,$pwd){
		$pwd = md5($pwd);
		$sql = "update s_admin set pwd = '$pwd' where id = '{$id}' ";
		return $this->db->query ($sql);
	}

	function get_master_admin($id)
	{
		$sql = " SELECT b.* FROM p_commercial  as a
 				LEFT JOIN s_admin as b ON a.admin_name = b.`name`
 				WHERE  a.id = '{$id}'";
		return $this->db->query ($sql)->row_array();
	}

	function master_group_flag($id)
	{
		$sql = " select * from s_agent_group WHERE admin_id = '{$id}'";
		return $this->db->query ($sql)->row_array();
	}

	function update_agent_Flag($flag, $admin_id)
	{
		$sql = "update s_agent_group set flag = '$flag' where admin_id = $admin_id ";
		$this->db->query ( $sql );
	}

	function insert_agent_Flag($flagdata)
	{
		$this->db->set_dbprefix('s_');
		return $this->db->insert('agent_group', $flagdata);
	}

	function insertSgroup($data)
	{
		$this->db->set_dbprefix('s_');
		$this->db->insert('group', $data);
		return $this->db->insert_id();
	}

	function insertS_admin_group($data)
	{
		$this->db->set_dbprefix('s_');
		$this->db->insert('admin_group', $data);
		return $this->db->insert_id();
	}

}

?>
