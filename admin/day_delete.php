<?php
session_start();
require_once "includes/database.php";

if ($_SESSION['rights'] != '') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $sql = "DELETE FROM `mt_day` WHERE `id` = $id";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Day deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting Day: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "No day ID provided.";
    }

    header("Location: day_list.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
