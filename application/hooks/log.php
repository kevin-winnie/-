<?php
class Log{
	protected $curr_time;

	public function __construct(){
		$this->ci = &get_instance();
		$this->curr_time = time();
	}

	public function operate(){
		$this->ci->load->model("Rbac_operation_model");

		$userdata = $this->ci->session->userdata('sess_admin_data');
		$curr_uri = $this->ci->uri->uri_string();
		$remark = empty($_REQUEST) ? '' : json_encode($_REQUEST);
		if($curr_uri!="admin/login" && $curr_uri!="admin/logout" && !empty($curr_uri) && !empty($userdata['adminid']))
		{
			$data =  array(
				'operation'=>$curr_uri,
				'admin_id'=>$userdata['adminid'],
				'admin_name'=>$userdata['adminname'],
				'admin_ip'=>$this->ci->input->ip_address(),
				'ctime'=>$this->curr_time,
				'remark'=>$remark,
				);
			$this->ci->Rbac_operation_model->insOperation($data);
		}
	}
}