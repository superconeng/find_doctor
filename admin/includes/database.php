<?php
error_reporting(0);
$conn = mysqli_connect("localhost","wwwsehatpro_doctors","wwwsehatpro_doctors","wwwsehatpro_doctors");
// Check connection
if (mysqli_connect_errno())
{
echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
date_default_timezone_set('Asia/Karachi');
?>

