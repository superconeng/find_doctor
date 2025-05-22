<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "includes/database.php";

// 1. Total Doctors
$query1 = "SELECT COUNT(*) AS total_doctors FROM doctor_detail";
$result1 = mysqli_query($conn, $query1);
$row1 = mysqli_fetch_assoc($result1);
$total_doctors = $row1['total_doctors'];

// 2. Get today's name (e.g., Monday)
$todayName = date('l');

// 3. Get day ID from mt_day table
$dayQuery = mysqli_query($conn, "SELECT id FROM mt_day WHERE name = '$todayName'");
$dayRow = mysqli_fetch_assoc($dayQuery);
$todayDayId = $dayRow['id'] ?? 0;

// 4. Count doctors who are available today (use FIND_IN_SET for CSV day_id)
$query2 = "SELECT COUNT(DISTINCT doctor_id) AS available_today FROM da_timing WHERE FIND_IN_SET('$todayDayId', day_id)";
$result2 = mysqli_query($conn, $query2);
$row2 = mysqli_fetch_assoc($result2);
$available_today = $row2['available_today'];

// 5. Total Consultants (if you want to count based on 'consultant' in speciality)
$query3 = "SELECT COUNT(*) AS total_consultants FROM speciality WHERE name LIKE '%consultant%'";
$result3 = mysqli_query($conn, $query3);
$row3 = mysqli_fetch_assoc($result3);
$total_consultants = $row3['total_consultants'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "styles.php"; ?>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body>

<div class="main-wrapper">
    <?php include "left-menu.php"; ?>

    <div class="page-wrapper">
        <div class="content">
            <div class="row">
                <!-- Total Doctors -->
                <div class="col-lg-3 col-sm-6 col-12 d-flex">
                    <a href="all_doctors.php" class="w-full">
                        <div class="dash-count das1 transition ease-out duration-300">
                            <div class="dash-counts">
                                <h4><?php echo $total_doctors; ?></h4>
                                <h5>Total Doctors</h5>
                            </div>
                            <div class="dash-imgs">
                                <i data-feather="user"></i>
                            </div>
                        </div>
                    </a>
                </div>


                <!-- Available Today -->
                <div class="col-lg-3 col-sm-6 col-12 d-flex">
                    <div class="dash-count das2">
                        <div class="dash-counts">
                            <h4><?php echo $available_today; ?></h4>
                            <h5>Available Doctors</h5>
                        </div>
                        <div class="dash-imgs">
                            <i data-feather="calendar"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Consultants -->
                <div class="col-lg-3 col-sm-6 col-12 d-flex">
                    <div class="dash-count das3">
                        <div class="dash-counts">
                            <h4><?php echo $total_consultants; ?></h4>
                            <h5>Total Consultants</h5>
                        </div>
                        <div class="dash-imgs">
                            <i data-feather="briefcase"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php 
ob_end_flush();
?>

