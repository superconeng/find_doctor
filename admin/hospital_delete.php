<?php
session_start();
require_once "includes/database.php";

if ($_SESSION['rights'] != '') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $sql = "DELETE FROM `hospital` WHERE `id` = $id";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Hospital deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting Hospital: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "No Hospital ID provided.";
    }

    header("Location: Hospital_list.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
