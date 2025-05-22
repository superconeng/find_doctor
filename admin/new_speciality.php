<?php
session_start();
ob_start();
require_once "includes/database.php";

if ($_SESSION['rights'] != '') {
    $specialityData = null;
    $error = '';

    // Fetch speciality data if editing
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM `speciality` WHERE `id` = $id";
        $result = mysqli_query($conn, $sql);
        $specialityData = mysqli_fetch_assoc($result);
    }

    // Generate or reuse CSRF token
    if (empty($_SESSION['form_token'])) {
        $_SESSION['form_token'] = bin2hex(random_bytes(32));
    }
    $form_token = $_SESSION['form_token'];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_speciality'])) {
        // Validate CSRF token
        if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
            $_SESSION['error'] = "Invalid or expired form submission.";
            header("Location: speciality_list.php");
            exit;
        }

        // Prevent token reuse
        unset($_SESSION['form_token']);

        // Sanitize input
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        if ($specialityData) {
            // Update existing
            $sql = "UPDATE `speciality` SET `name` = '$name', `status` = '$status' WHERE `id` = {$specialityData['id']}";
        } else {
            // Prevent duplicate entry
            $checkSql = "SELECT id FROM `speciality` WHERE `name` = '$name'";
            $checkResult = mysqli_query($conn, $checkSql);
            if (mysqli_num_rows($checkResult) > 0) {
                $_SESSION['error'] = "This speciality already exists.";
                header("Location: speciality_list.php");
                exit;
            }

            // Insert new
            $sql = "INSERT INTO `speciality` (`name`, `status`) VALUES ('$name', '$status')";
        }

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = $specialityData ? "Speciality updated successfully." : "Speciality added successfully.";
            header("Location: speciality_list.php");
            exit;
        } else {
            $_SESSION['error'] = "Error saving speciality: " . mysqli_error($conn);
            header("Location: speciality_list.php");
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
                            <!-- Speciality Name -->
                            <div class="col-lg-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label>Speciality Name</label>
                                    <input type="text" name="name" required placeholder="Enter Speciality Name"
                                           value="<?php echo htmlspecialchars($specialityData['name'] ?? '', ENT_QUOTES); ?>" class="form-control">
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-lg-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" required class="form-control">
                                        <option value="1" <?php echo isset($specialityData['status']) && $specialityData['status'] == '1' ? 'selected' : ''; ?>>Active</option>
                                            <option value="0" <?php echo isset($specialityData['status']) && $specialityData['status'] == '0' ? 'selected' : ''; ?>>Inactive</option>

                                    </select>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="col-lg-12">
                                <button type="submit" name="submit_speciality" class="btn btn-submit me-2">
                                    <?php echo $specialityData ? 'Update Speciality' : 'Add Speciality'; ?>
                                </button>
                                <a href="speciality_list.php" class="btn btn-cancel">Cancel</a>
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
