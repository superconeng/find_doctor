<?php
session_start();
ob_start();
require_once "includes/database.php"; // Ensure this file is present and correctly configured

if ($_SESSION['rights'] != '') {
    $userData = null; // Initialize user data
    $message = ''; // For feedback messages

    // Check if an ID is passed for editing
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']); // Sanitize input
        $sql = "SELECT * FROM `users` WHERE `id` = $id"; // Use the correct ID column
        $result = mysqli_query($conn, $sql);
        $userData = mysqli_fetch_assoc($result); // Fetch user data as associative array
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $user_name = $_POST['user_name'];
        $login = $_POST['login'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $rights = $_POST['rights'];

        // Check for existing username/email
        $checkSql = "SELECT * FROM `users` WHERE (`user_name` = '$user_name' OR `email` = '$email')";
        if ($userData) {
            // Exclude current user when editing
            $checkSql .= " AND `id` != {$userData['id']}";
        }

        $checkResult = mysqli_query($conn, $checkSql);

        if (mysqli_num_rows($checkResult) > 0) {
            $message = "Username or Email already registered. Please use a different one.";
        } else {
            // Hash the password if provided (only if a new password is set)
            $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null; // Use password_hash for security

            // Handle profile picture upload
            $image_name = null;
            if (!empty($_FILES['profile_picture']['name'])) {
                $image_name = basename($_FILES['profile_picture']['name']);
                $target_dir = "assets/img/profiles/"; // Target directory
                $target_file = $target_dir . $image_name;

                if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                    $message = "Error uploading image.";
                }
            }

            // Prepare the SQL statement for insertion or update
            if ($userData) { // Update existing user
                $user_id = $userData['id'];
                $sql = "UPDATE `users` SET user_name='$user_name', login='$login', email='$email', rights='$rights'" . 
                       ($hashed_password ? ", password='$hashed_password'" : "") . 
                       ($image_name ? ", user_image='$image_name'" : "") . 
                       " WHERE id=$user_id";
            } else { // Insert new user
                $user_creation_date = date('Y-m-d H:i:s');
                $last_login_date = null; // This will be set when the user logs in
                $user_status = 'active'; // Default status can be set as 'active'

                $sql = "INSERT INTO `users` (user_name, login, password, email, user_image, rights, user_creation_date, last_login_date, user_status) 
                        VALUES ('$user_name', '$login', '$hashed_password', '$email', '$image_name', '$rights', '$user_creation_date', '$last_login_date', 'Y')";
            }

            // Execute the SQL statement
            if (mysqli_query($conn, $sql)) {
                header("Location: userlists.php"); // Redirect to user list page
                exit;
            } else {
                $message = "Error adding/updating user: " . mysqli_error($conn);
            }
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


            <!-- Form for User Data -->
            <div class="card">
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row">

                         <!-- User Name -->
                         <div class="col-lg-6 col-sm-6 col-12">
                             <div class="form-group">
                                 <label>User Name</label>
                                 <input type="text" name="user_name" placeholder="Username" required value="<?php echo $userData['user_name'] ?? ''; ?>" class="form-control">
                             </div>
                         </div>

                         <!-- Email -->
                         <div class="col-lg-6 col-sm-6 col-12">
                             <div class="form-group">
                                 <label>Email</label>
                                 <input type="email" name="email" placeholder="Email" required value="<?php echo $userData['email'] ?? ''; ?>" class="form-control">
                             </div>
                         </div>

                         <!-- Login -->
                         <div class="col-lg-6 col-sm-6 col-12">
                             <div class="form-group">
                                 <label>Login</label>
                                 <input type="text" name="login" placeholder="Login" required value="<?php echo $userData['login'] ?? ''; ?>" class="form-control pass-input">
                             </div>
                         </div>

                         <!-- Password -->
                         <div class="col-lg-6 col-sm-6 col-12">
                            <div class="form-group">
                                <label>Password</label>
                                <div class="pass-group">
                                     <input type="password" name="password" placeholder="Password" class="form-control pass-inputs pass-input">
                                    <span class="fas toggle-password fa-eye-slash"></span>
                                </div>
                            </div>
                         </div>
                         
                         <div class="col-lg-6 col-sm-6 col-12">
                             <div class="form-group">
                                 <label>Profile Picture</label>
                                 <input type="file" name="profile_picture" class="form-control">
                             </div>
                         </div>

                         <!-- Submit and Cancel Buttons -->
                         <div class="col-lg-12">
                             <button type="submit" class="btn btn-submit me-2"><?php echo $userData ? 'Update User' : 'Add User'; ?></button>
                             <a href="javascript:void(0);" class="btn btn-cancel" onclick="window.location='userlists.php';">Cancel</a>
                         </div>

                        </div>
                    </form>
                    <?php if ($message): ?>
                        <div class="alert alert-danger"><?php echo $message; ?></div>
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
    header('Location: login.php');
}
ob_end_flush();
?>
