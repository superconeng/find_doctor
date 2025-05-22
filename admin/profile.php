<?php
session_start();
ob_start();
require_once "includes/database.php"; // Ensure this file is present and correctly configured

// Check if user has rights
if (isset($_SESSION['rights']) && $_SESSION['rights'] != '') {
    // Fetch all users for display
    $usersSql = "SELECT user_name, email, login, user_image FROM `users`";
    $usersResult = mysqli_query($conn, $usersSql);
    if (!$usersResult) {
        die("Query Failed: " . mysqli_error($conn));
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
<div class="page-header">
<div class="page-title">
<h4>Profile</h4>
<h6>User Profile</h6>
</div>
</div>

<div class="card">
<div class="card-body">
<div class="profile-set">
<div class="profile-head">
</div>
<?php while ($row = mysqli_fetch_assoc($usersResult)): ?>
<div class="profile-top">
<div class="profile-content">
<div class="profile-contentimg">
 <?php if (!empty($row['user_image'])): ?>
                    <img src="assets/img/profiles/<?php echo htmlspecialchars($row['user_image']); ?>">
                <?php else: ?>
                    No Image
                <?php endif; ?>
<div class="profileupload">
<input type="file" id="imgInp">
<a href="javascript:void(0);"><img src="assets/img/icons/edit-set.svg" alt="img"></a>
</div>
</div>
<div class="profile-contentname">
<h2><?php echo htmlspecialchars($row['user_name']); ?></h2>
<h4>Updates Your Photo and Personal Details.</h4>
</div>
</div>
<div class="ms-auto">
<a href="javascript:void(0);" class="btn btn-submit me-2">Save</a>
<a href="javascript:void(0);" class="btn btn-cancel">Cancel</a>
</div>
</div>
</div>
<div class="row">
<div class="col-lg-6 col-sm-12">
<div class="form-group">
<label>Name</label>
<input type="text" name="user_name" placeholder="Username" required value="<?php echo $userData['user_name'] ?? ''; ?>" class="form-control">
</div>
</div>
<div class="col-lg-6 col-sm-12">
<div class="form-group">
<label>Email</label>
<input type="email" name="email" placeholder="Email" required value="<?php echo $userData['email'] ?? ''; ?>" class="form-control">
</div>
</div>

<div class="col-lg-6 col-sm-12">
<div class="form-group">
<label>User Name</label>
<input type="text" name="login" placeholder="Login" required value="<?php echo $userData['login'] ?? ''; ?>" class="form-control pass-input">
</div>
</div>
<div class="col-lg-6 col-sm-12">
<div class="form-group">
<label>Password</label>
<div class="pass-group">
<input type="password" name="password" placeholder="Password" class="form-control pass-inputs pass-input">
<span class="fas toggle-password fa-eye-slash"></span>
</div>
</div>
</div>
<div class="col-12">
<a href="javascript:void(0);" class="btn btn-submit me-2">Submit</a>
<a href="javascript:void(0);" class="btn btn-cancel">Cancel</a>
</div>
</div>
</div>
</div>

</div>
</div>
</div>

<?php endwhile; ?>
</body>
</html>

<?php 
} else {
    // Redirect to login page if no rights are found
    header('Location: login.php');
    exit;
}
ob_end_flush();
?>