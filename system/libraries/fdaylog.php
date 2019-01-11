<?
class CI_Fdaylog{
 


	function add($db_log,$tag,$data){
		
	    $data = array(
	      'time'=>date("Y-m-d H:i:s"),
	      'tag'=>$tag,
	      'log_data'=>serialize($data)
	    );
	    $db_log->insert('error_log',$data);
	}
}
