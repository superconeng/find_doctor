<?php
session_start();
require_once "includes/database.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="robots" content="noindex, nofollow">
    <?php include "styles.php"; ?>
</head>
<body>

<div class="main-wrapper">
    <div class="left-menu">
        <?php include "left-menu.php"; ?>
    </div>

    <div class="page-wrapper">
        <div class="content">
            <div class="d-flex justify-content-between my-3">
                <div class="add-lc-text">
                    <span>Add Hospital</span>
                </div>
                <div>
                    <a href="new_hospital.php" class="btn btn-added btn-primary">Add Hospital
                        <i class="fa fa-plus"></i>
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
                                    <th>Hospital Name</th>
                                    <th>Map Location</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM hospital ORDER BY id DESC";
                                $result = mysqli_query($conn, $sql);
                                $sr = 1;
                                while ($row = mysqli_fetch_assoc($result)) {
                                ?>
                                    <tr>
                                        <td><?php echo $sr++; ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['map_location']); ?></td>
                                        <td>
                                            <a href="new_hospital.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">
                                                <img src="assets/img/icons/edit.svg" class="action-icon">
                                            </a>
                                        </td>
                                        <td>
                                            <a href="hospital_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-delete">
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
