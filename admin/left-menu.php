<?php
require_once "includes/database.php";
session_start();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// fetch user_name
$user_name = "";
$id = $_SESSION['id']; // User ID stored in the session
$query = "SELECT user_name, user_image FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $user_name = $row['user_name'];
	$user_image = $row['user_image'];
} else {
    $user_name = "User";
}
?>


<!DOCTYPE html>
<html lang="en">
<body>
<meta name="robots" content="noindex, nofollow">

<div class="main-wrapper">
<div class="header">

<!--------Company Logo--------->

<div class="header-left active">
<a href="index.php" class="logo">
<img src="assets/img/profiles/sehatpro.png">
</a>
<a href="index.php" class="logo-small">
<img src="assets/img/logo-small.png" alt="">
</a>
<a id="toggle_btn" href="javascript:void(0);">
</a>
</div>

<!--------Company Logo End--------->

<a id="mobile_btn" class="mobile_btn" href="#sidebar">
<span class="bar-icon">
<span></span>
<span></span>
<span></span>
</span>
</a>
<ul class="nav user-menu">
    <!-- Added Welcome Message -->
    <li class="nav-item welcome-item">
        <span class="welcome-message">Welcome, <?= htmlspecialchars($user_name); ?></span>
    </li>
    
    <!--------Profile Tab--------->
    <li class="nav-item dropdown has-arrow main-drop">
        <a href="javascript:void(0);" class="dropdown-toggle nav-link userset" data-bs-toggle="dropdown">
            <span class="user-img">
                <?php if (!empty($user_image)): ?>
                    <img src="assets/img/profiles/<?= htmlspecialchars($user_image); ?>">
                <?php else: ?>
                    <img src="assets/img/profiles/user-icon.png">
                <?php endif; ?>
                <span class="status online"></span>
            </span>
        </a>
        <div class="dropdown-menu menu-drop-user">
            <div class="profilename">
                <div class="profileset">
                    <span class="user-img">
                        <?php if (!empty($user_image)): ?>
                            <img src="assets/img/profiles/<?= htmlspecialchars($user_image); ?>" alt="">
                        <?php else: ?>
                            <img src="assets/img/profiles/avator1.jpg" alt="">
                        <?php endif; ?>
                        <span class="status online"></span>
                    </span>
                    <div class="profilesets">
                        <h6><a href="index.php"><?= htmlspecialchars($user_name); ?></a></h6>
                        <!--<h5>Admin</h5>---->
                    </div>
                </div>
                <hr class="m-0">
                <a class="dropdown-item" href="profile.php"> <i class="me-2" data-feather="user"></i> My Profile</a>
                <hr class="m-0">
                <a class="dropdown-item logout pb-0" href="logout.php"><img src="assets/img/icons/log-out.svg" class="me-2" alt="img">Logout</a>
            </div>
        </div>
    </li>
</ul>



<div class="dropdown mobile-user-menu">
<a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
<div class="dropdown-menu dropdown-menu-right">
<a class="dropdown-item" href="profile.html">My Profile</a>
<a class="dropdown-item" href="generalsettings.html">Settings</a>
<a class="dropdown-item" href="signin.html">Logout</a>
</div>
</div>
</div>

<!--------Profile Tab End--------->

<!--------Left Menu--------->

<div class="sidebar" id="sidebar">
<div class="sidebar-inner slimscroll">
<div id="sidebar-menu" class="sidebar-menu">
<ul>
    <li class="active">
        <a href="index.php"><img src="assets/img/icons/dashboard.svg" alt="img"><span> Dashboard</span> </a>
    </li>
    <li>
        <a href="userlists.php"><i data-feather="users"></i><span>Users</span></a>
    </li>
     <li>
        <a href="city_list.php"><i data-feather="users"></i><span>City</span></a>
    </li>
     <li>
        <a href="speciality_list.php"><i data-feather="users"></i><span>Speciality</span></a>
    </li>
    <li>
        <a href="hospital_list.php"><i data-feather="users"></i><span>Hospital</span></a>
    </li>
    
    <li>
        <a href="degree_list.php"><i data-feather="users"></i><span>Degree</span></a>
    </li>
   
    <li>
        <a href="membership_list.php"><i data-feather="users"></i><span>Membership</span></a>
    </li>
 
  
    <li>
        <a href="day_list.php"><i data-feather="users"></i><span>Day</span></a>
    </li>
    <li>
        <a href="doctor_list.php"><i data-feather="users"></i><span>Add Doctor</span></a>
    </li>
    <li>
    <a href="logout.php"><i data-feather="log-out"></i>
        <span>Log Out</span> </a>
    </li>

</ul>




</div>
</div>
</div>
</div>


<?php include "footer.php"; ?>
</div>

<!--------Left Menu End--------->
</body>
</html>