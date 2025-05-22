<?php
session_start();
require_once "includes/database.php"; 
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="robots" content="noindex, nofollow">
    <?php include "styles.php"; ?>
</head>
<body>
<style>
    .page-wrapper {
        padding: 20px 0 0 !important;
    } 
</style>
<div class="main-wrapper">
    <div class="left-menu">
       <?php include "left-menu.php"; ?>
    </div>

    <div class="page-wrapper">
        <div class="content">
            <div class="d-flex justify-content-between my-3">
                <div class="add-lc-text">
                    <span>Doctor Timings</span>
                </div>
                <div>
                    <a href="doctor_detail.php" class="btn btn-added btn-primary">
                        <i class="fa fa-plus"></i>Add Doctor
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-top">
                        <div class="search-set">
                            <div class="search-path">
                                <a class="btn btn-filter" id="filter_search">
                                    <img src="assets/img/icons/filter.svg" alt="Filter">
                                    <span><img src="assets/img/icons/closes.svg" alt="Close"></span>
                                </a>
                            </div>
                            <div class="search-input">
                                <a class="btn btn-searchset">
                                    <img src="assets/img/icons/search-white.svg" alt="Search">
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table datanew">
                            <thead>
                                <tr>
                                    <th>Sr.No</th>
                                    <th>Doctor Name</th>
                                    <th>Hospital</th>
                                    <th>Day</th>
                                    <th>Timing</th>
                                    <th class="seri-no">Edit</th>
                                    <th class="seri-no">Delete</th>
                                </tr>
                            </thead>
                            <tbody>
    <?php
    $sql = "SELECT 
                dd.id AS doctor_id,
                dd.name AS doctor_name,
                h.name AS hospital_name,
                GROUP_CONCAT(mt.name ORDER BY mt.id SEPARATOR ', ') AS day_names,
                CONCAT(dt.from_time, ' - ', dt.to_time) AS timing
            FROM doctor_detail dd 
            LEFT JOIN da_timing dt ON dd.id = dt.doctor_id 
            LEFT JOIN hospital h ON dt.hospital_id = h.id 
            LEFT JOIN mt_day mt ON FIND_IN_SET(mt.id, dt.day_id) > 0 
            GROUP BY dt.id
            ORDER BY dd.id DESC";
    $result = mysqli_query($conn, $sql);
    $sr = 1;
    while ($row = mysqli_fetch_assoc($result)) {
    ?>
        <tr>
            <td><?php echo $sr++; ?></td>
            <td><?php echo !empty($row['doctor_name']) ? htmlspecialchars($row['doctor_name']) : 'N/A'; ?></td>
            <td><?php echo !empty($row['hospital_name']) ? htmlspecialchars($row['hospital_name']) : 'N/A'; ?></td>
            <td><?php echo !empty($row['day_names']) ? htmlspecialchars($row['day_names']) : 'N/A'; ?></td>
            <td><?php echo !empty($row['timing']) ? htmlspecialchars($row['timing']) : 'N/A'; ?></td>
            
            <td class="seri-no">
                <a href="doctor_edit.php?id=<?php echo $row['doctor_id']; ?>" class="btn btn-edit">
                    <img src="assets/img/icons/edit.svg" class="action-icon">
                </a>
            </td>
            <td class="seri-no">
                <a href="doctor_delete.php?id=<?php echo $row['doctor_id']; ?>" class="btn btn-delete">
                    <img src="assets/img/icons/delete.svg" class="action-icon">
                </a>
            </td>
        </tr>
    <?php } ?>
</tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
