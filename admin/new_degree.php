<?php
session_start();
ob_start();
require_once "includes/database.php";

if ($_SESSION['rights'] != '') {
    $degreeData = null;
    $error = '';

    // Edit check
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM `mt_degree` WHERE `id` = $id";
        $result = mysqli_query($conn, $sql);
        $degreeData = mysqli_fetch_assoc($result);
    }

    // Form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = mysqli_real_escape_string($conn, $_POST['name']);

        if ($degreeData) {
            $sql = "UPDATE `mt_degree` SET `name` = '$name' WHERE `id` = {$degreeData['id']}";
        } 
        else {
            $sql = "INSERT INTO `mt_degree` (`name`) VALUES ('$name')";
        }

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = $degreeData ? "Degree updated successfully." : "Degree added successfully.";
            header("Location: degree_list.php");
            exit;
        } else {
            $_SESSION['error'] = "Error saving degree: " . mysqli_error($conn);
            header("Location: degree_list.php");
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
            <!-- Degree Form -->
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <!-- Degree Name -->
                            <div class="col-lg-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label>Degree Name</label>
                                    <input type="text" name="name" required placeholder="Enter Degree Name"
                                           value="<?php echo $degreeData['name'] ?? ''; ?>" class="form-control">
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-submit me-2">
                                    <?php echo $degreeData ? 'Update Degree' : 'Add Degree'; ?>
                                </button>
                                <a href="degree_list.php" class="btn btn-cancel">Cancel</a>
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
