<?php
session_start();
ob_start();
require_once "includes/database.php";

if ($_SESSION['rights'] != '') {
    $membershipData = null;
    $error = '';

    // Edit check
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM `mt_membership` WHERE `id` = $id";
        $result = mysqli_query($conn, $sql);
        $membershipData = mysqli_fetch_assoc($result);
    }

    // Form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = mysqli_real_escape_string($conn, $_POST['name']);

        if ($membershipData) {
            $sql = "UPDATE `mt_membership` SET `name` = '$name' WHERE `id` = {$membershipData['id']}";
        } else {
            $sql = "INSERT INTO `mt_membership` (`name`) VALUES ('$name')";
        }

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = $membershipData ? "Membership updated successfully." : "Membership added successfully.";
            header("Location: membership_list.php");
            exit;
        } else {
            $_SESSION['error'] = "Error saving membership: " . mysqli_error($conn);
            header("Location: membership_list.php");
            exit;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "styles.php"; ?>
    <meta name="robots" content="noindex, nofollow">
</head>
<body>

<div class="main-wrapper">
    <?php include "left-menu.php"; ?>

    <div class="page-wrapper">
        <div class="content">
            <!-- Membership Form -->
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <!-- Membership Name -->
                            <div class="col-lg-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label>Membership Name</label>
                                    <input type="text" name="name" required placeholder="Enter Membership Name"
                                           value="<?php echo $membershipData['name'] ?? ''; ?>" class="form-control">
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-submit me-2">
                                    <?php echo $membershipData ? 'Update Membership' : 'Add Membership'; ?>
                                </button>
                                <a href="membership_list.php" class="btn btn-cancel">Cancel</a>
                            </div>
                        </div>
                    </form>

                    <?php if ($error): ?>
                        <div class="alert alert-danger mt-2"><?php echo $error; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php
} else {
    header("Location: login.php");
}
ob_end_flush();
?>
