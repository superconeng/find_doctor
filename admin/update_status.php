<?php
session_start();
require_once "includes/database.php"; // Ensure this file is present and correctly configured

if (isset($_POST['id']) && isset($_POST['status'])) {
    $userId = intval($_POST['id']);
    $status = $_POST['status'] == 'Y' ? 'Y' : 'N';

    // Update the user status in the database
    $updateSql = "UPDATE `users` SET `user_status` = '$status' WHERE `user_id` = $userId";
    if (mysqli_query($conn, $updateSql)) {
        echo "Status updated successfully.";
    } else {
        echo "Error updating status: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request.";
}
?>
