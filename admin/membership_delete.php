<?php
session_start();
require_once "includes/database.php";

if ($_SESSION['rights'] != '') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $sql = "DELETE FROM `mt_membership` WHERE `id` = $id";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Membership deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting membership: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "No membership ID provided.";
    }

    header("Location: membership_list.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
