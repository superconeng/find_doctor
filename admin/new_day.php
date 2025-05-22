<?php
session_start();
ob_start();
require_once "includes/database.php";

if ($_SESSION['rights'] != '') {
    $dayData = null;
    $error = '';

    // Check for edit
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM `mt_day` WHERE `id` = $id";
        $result = mysqli_query($conn, $sql);
        $dayData = mysqli_fetch_assoc($result);
    }

    // Form submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = mysqli_real_escape_string($conn, $_POST['name']);

        if ($dayData) {
            // Update existing
            $sql = "UPDATE `mt_day` SET `name` = '$name' WHERE `id` = {$dayData['id']}";
        } else {
            // Insert new
            $sql = "INSERT INTO `mt_day` (`name`) VALUES ('$name')";
        }

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = $dayData ? "Day updated successfully." : "Day added successfully.";
            header("Location: day_list.php");
            exit;
        } else {
            $_SESSION['error'] = "Error saving day: " . mysqli_error($conn);
            header("Location: day_list.php");
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

            <!-- Day Form -->
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <!-- Day Name -->
                            <div class="col-lg-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label>Day Name</label>
                                    <input type="text" name="name" required placeholder="Enter Day Name"
                                           value="<?php echo $dayData['name'] ?? ''; ?>" class="form-control">
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-submit me-2">
                                    <?php echo $dayData ? 'Update Day' : 'Add Day'; ?>
                                </button>
                                <a href="day_list.php" class="btn btn-cancel">Cancel</a>
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
