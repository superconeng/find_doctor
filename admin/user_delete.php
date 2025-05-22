<?php
session_start();
require_once "includes/database.php"; // Ensure this file is present and correctly configured

if ($_SESSION['rights'] != '') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']); // Sanitize input

        // SQL query to delete the user
        $sql = "DELETE FROM `users` WHERE `id` = $id";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['message'] = "User deleted successfully.";
        } else {
            $_SESSION['message'] = "Error deleting user: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['message'] = "No user ID provided.";
    }
    
    // Redirect back to the user management page
    header("Location: userlists.php");
    exit;
} else {
    header('Location: login.php');
}
?>
