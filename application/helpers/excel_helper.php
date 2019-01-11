<?php

if (!function_exists('excel')) {

	function excel($content, $filename, $column = array(), $timelimit = 0) {
		@set_time_limit($timelimit);
		$date = date('Y-m-d');
		$tx = $filename . ':' . $date;
		if (empty($filename)) {
			$filename = $date;
			$tx = $filename;
		}
		header("Content-type:application/octet-stream");
		header("Accept-Ranges:bytes");
		header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:attachment;filename=" . $filename . ".csv");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Transfer-Encoding: binary");

		$tx.= "\n";
		if (is_array($column) && !empty($column)) {
			foreach ($column as $v) {
				$tx.=$v . ",";
			}
			$tx.= "\n";
		}
		echo mb_convert_encoding($tx . $content, "GBK", "UTF-8");
	}

}