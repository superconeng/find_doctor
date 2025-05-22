<?php
include("database.php"); 
function logsave($id, $p_name, $activity)
{
	global $conn;
	$visit_date=date('Y-m-d H:i:s');
	$ip=$_SERVER['REMOTE_ADDR'];
	$sql_logsave="INSERT INTO `user_log_detail` ( `uld_id` , `user_log_id` , `page_name` , `activity` , `visit_time` , `user_ip` ) VALUES ( NULL , '$id', '$p_name', '$activity', '$visit_date', '$ip' );"; 
	mysqli_query($conn,$sql_logsave)or die('Error 123: '.mysqli_error($conn));
}
?>