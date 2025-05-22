<?php
session_start();
ob_start();
require_once "includes/database.php";

if ($_SESSION['rights'] != '') {
    $hospitalData = null;
    $error = '';

    // Fetch hospital data if editing
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        // Modify the query to match your actual table structure
        $sql = "SELECT * FROM `hospital` WHERE `id` = $id"; // Change `hospital` to your table name
        $result = mysqli_query($conn, $sql);
        $hospitalData = mysqli_fetch_assoc($result);
    }

    // Generate or reuse CSRF token
    if (empty($_SESSION['form_token'])) {
        $_SESSION['form_token'] = bin2hex(random_bytes(32));
    }
    $form_token = $_SESSION['form_token'];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_hospital'])) {
        // Validate CSRF token
        if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
            $_SESSION['error'] = "Invalid or expired form submission.";
            header("Location: hospital_list.php");
            exit;
        }

        // Prevent token reuse
        unset($_SESSION['form_token']);

        // Sanitize input
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $map_location = mysqli_real_escape_string($conn, $_POST['map_location']);

        if ($hospitalData) {
            // Update existing
            $sql = "UPDATE `hospital` SET `name` = '$name', `map_location` = '$map_location' WHERE `id` = {$hospitalData['id']}"; // Change `hospital` to your table name
        } else {
            // Prevent duplicate entry
            $checkSql = "SELECT id FROM `hospital` WHERE `name` = '$name' AND `map_location` = '$map_location'"; // Change `hospital` to your table name
            $checkResult = mysqli_query($conn, $checkSql);
            if (mysqli_num_rows($checkResult) > 0) {
                $_SESSION['error'] = "This hospital already exists.";
                header("Location: hospital_list.php");
                exit;
            }

            // Insert new
            $sql = "INSERT INTO `hospital` (`name`, `map_location`) VALUES ('$name', '$map_location')"; // Change `hospital` to your table name
        }

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = $hospitalData ? "Hospital updated successfully." : "Hospital added successfully.";
            header("Location: hospital_list.php");
            exit;
        } else {
            $_SESSION['error'] = "Error saving hospital: " . mysqli_error($conn);
            header("Location: hospital_list.php");
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
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="form_token" value="<?php echo $form_token; ?>">

                        <div class="row">
                            <!-- Hospital Name -->
                            <div class="col-lg-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label>Hospital Name</label>
                                    <input type="text" name="name" required placeholder="Enter Hospital Name"
                                           value="<?php echo htmlspecialchars($hospitalData['name'] ?? '', ENT_QUOTES); ?>" class="form-control">
                                </div>
                            </div>

                            <!-- Map Location -->
                            <div class="col-lg-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label>Map Location</label>
                                    <input type="text" name="map_location" required placeholder="Enter Map Location"
                                           value="<?php echo htmlspecialchars($hospitalData['map_location'] ?? '', ENT_QUOTES); ?>" class="form-control">
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="col-lg-12">
                                <button type="submit" name="submit_hospital" class="btn btn-submit me-2">
                                    <?php echo $hospitalData ? 'Update Hospital' : 'Add Hospital'; ?>
                                </button>
                                <a href="hospital_list.php" class="btn btn-cancel">Cancel</a>
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
