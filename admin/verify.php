<?php 
ob_start();
session_start(); 
include("includes/database.php"); 

$login = $_POST["txtlogin"];
$password = base64_encode($_POST["txtpassword"]);
$_SESSION['login'] = $login;

// Query to get user data with matching login and password
$sql = "SELECT * FROM users WHERE login='$login' AND password='$password' AND user_status='Y'";
$result = $conn->query($sql);

if (!$result) {
    die('Could not get data: ' . $conn->error);
}

if ($row = $result->fetch_assoc()) {
    $_SESSION['rights'] = $row['rights'];
    $_SESSION['id'] = $row['id'];
    $_SESSION['log_user_name'] = $row['user_name'];

    // Check user rights and redirect accordingly (only one role now)
    if ($_SESSION['rights'] == 1) { // CEO
        header('Location: index.php');
    } else {
        header('Location: login.php?msg_error=Invalid username or password');
    }
} else {
    header('Location: login.php?msg_error=Invalid username or password');
}

ob_end_flush();
?>
