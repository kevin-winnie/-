<?php
class Acl{
	public function __construct(){
		$this->ci = &get_instance();
	}

	public function ckFunc(){
		$this->ci->load->model("Admin_model");

		$res = $this->ci->Admin_model->getFuncList();	
		$curr_uri = $this->ci->uri->uri_string();
		$adminid = $this->ci->session->userdata('sess_admin_data')['adminid'];
		$admin_funcs = $this->ci->Admin_model->getFunc($adminid);
		//验证
		foreach($res as $val){
			$id = $val->id;
			$uri = $val->name;
			if(in_array($id, $admin_funcs)){
				continue;
			}
			if(strpos($curr_uri, $uri)!==false){
				$this->splash("没有相关权限，请联系管理员开通");
			}
		}
	}

	/**
	 * undocumente
	 *
	 * @return void
	 * @author 
	 **/
	private function splash($msg)
	{
		header('Content-Type: text/html; charset=utf-8');
		$msg_str = "<h3><strong>".$msg."</strong></h3>";
		echo $msg_str;exit;
	} 
}