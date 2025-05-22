<?php
session_start();
require_once "includes/database.php";

if ($_SESSION['rights'] != '') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $sql = "DELETE FROM `mt_city` WHERE `id` = $id";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "City deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting city: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "No City ID provided.";
    }

    header("Location: city_list.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
