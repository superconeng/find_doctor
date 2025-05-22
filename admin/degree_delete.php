<?php
session_start();
require_once "includes/database.php";

if ($_SESSION['rights'] != '') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $sql = "DELETE FROM `mt_degree` WHERE `id` = $id";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Degree deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting Degree: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "No Degree ID provided.";
    }

    header("Location: degree_list.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
