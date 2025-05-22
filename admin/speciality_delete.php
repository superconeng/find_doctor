<?php
session_start();
require_once "includes/database.php";

if ($_SESSION['rights'] != '') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $sql = "DELETE FROM `speciality` WHERE `id` = $id";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Speciality deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting Speciality: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "No Speciality ID provided.";
    }

    header("Location: Speciality_list.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
