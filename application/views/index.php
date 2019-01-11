<?php $this->load->view('include/header'); ?>
<?php $this->load->view('include/menu'); ?>
<div class="panel panel-default">
  <div class="panel-heading"><?php echo $this->session->userdata('sess_admin_data')["adminname"] . "，您好"?></div>

	<table class="table">
		<tr>
			<th>最近10次登录时间</th>
			<th>最近登录IP</th>
		</tr>
		<?php foreach($list as $val){ ?>
		<tr>
			<td><?php echo date("Y-m-d H:i:s",($val['ctime'])); ?></td>
			<td><?php echo $val['loginIP']; ?></td>
		</tr>
		<?php } ?>
	</table>
</div>
<?php $this->load->view('include/footer'); ?>