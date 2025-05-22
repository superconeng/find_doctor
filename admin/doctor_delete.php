<?php
session_start();
require_once "includes/database.php";

if (isset($_GET['id'])) {
    $doctor_id = (int)$_GET['id'];
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First delete from da_timing table
        $query1 = "DELETE FROM da_timing WHERE doctor_id = $doctor_id";
        if (!mysqli_query($conn, $query1)) {
            throw new Exception("Error deleting from da_timing: " . mysqli_error($conn));
        }
        
        // Then delete from doctor_detail table
        $query2 = "DELETE FROM doctor_detail WHERE id = $doctor_id";
        if (!mysqli_query($conn, $query2)) {
            throw new Exception("Error deleting from doctor_detail: " . mysqli_error($conn));
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        $_SESSION['success'] = "Doctor deleted successfully!";
    } catch (Exception $e) {
        // Rollback the transaction if any query fails
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: doctor_list.php");
    exit();
} else {
    $_SESSION['error'] = "No doctor ID specified for deletion";
    header("Location: doctor_list.php");
    exit();
}
?>