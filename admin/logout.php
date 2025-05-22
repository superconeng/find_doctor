<?php
ob_start();
session_start();
require_once('includes/database.php');
require_once('includes/log_save.php');
if($_SESSION['rights']!=1)
{
	$activity=$_SESSION['log_user_name']." logout Successfully";
	logsave($_SESSION['id'], 'Logout', $activity);
}
session_destroy();
header("location:login.php?msglogout=logout");
ob_end_flush();
?>
