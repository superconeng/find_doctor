<?php

function Add_Log_Detail($page_name, $page_description, $type=0)

{

    require_once('includes/database.php');

	$ip_address=$_SERVER['REMOTE_ADDR'];

    $log_datetime=date('Y-m-d H:i:s');

	$sql="INSERT INTO `user_log_detail` ( `uld_id` , `user_log_id` , `page_name` , `page_description` , `visit_time` , `user_ip`, `ulog_type` ) VALUES ( NULL , '$_SESSION[login_id]', '$page_name', '$page_description', '$log_datetime', '$ip_address', '$type')";

	if(mysqli_query($conn,$sql))

	{

		return 1;

	}

	else

	{

		return 2;

	}

} 







?>