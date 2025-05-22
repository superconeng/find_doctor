<?php
session_start();
ob_start();
require_once "includes/database.php"; // Ensure this file is present and correctly configured

if ($_SESSION['rights'] != '') {
    // Fetch all users for display
    $usersSql = "SELECT * FROM `users`";
    $usersResult = mysqli_query($conn, $usersSql);
    $serial = 1; // Initialize serial number
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
                 <div class="d-flex justify-content-between my-3">
                <div class="add-lc-text">
                    <span>Add User</span>
                </div>
                <div>
                <a href="newuser.php" class="btn btn-added btn-primary">Add User
                    <i class="fa fa-plus"></i> <!-- Font Awesome Plus Icon -->
                </a>
                </div>
            </div>

<!-------Data Display------->
<div class="card">
<div class="card-body">
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
  <th class="seri-no">Sr.No</th>
  <th>Company Name</th>
  <th>Email</th>
  <th>Login</th>
  <th>Status</th>
  <th class="seri-no">Edit</th>
  <th class="seri-no">Delete</th>
         </tr>
     </thead>
     <tbody>
         <?php while ($row = mysqli_fetch_assoc($usersResult)): ?>
  <tr>
      <td class="seri-no"><?php echo $serial++; ?></td>
      <td><?php echo htmlspecialchars($row['user_name']); ?></td>
      <td><?php echo htmlspecialchars($row['email']); ?></td>
      <td><?php echo htmlspecialchars($row['login']); ?></td>
      
      <td><span class="bg-lightgreen badges"><?php echo htmlspecialchars($row['user_status']); ?></span></td>
      <td class="seri-no">
          <a href="newuser.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">
        <img src="assets/img/icons/edit.svg" class="action-icon">
          </a>
		  </td>
		  <td class="seri-no">
          <a href="javascript:void(0);" class="btn btn-delete" onclick="confirmDelete(<?php echo $row['id']; ?>)">
        <img src="assets/img/icons/delete.svg" class="action-icon">
          </a>
      </td>
  </tr>
         <?php endwhile; ?>
     </tbody>
 </table>
</div>
</div>
</div>
<!-------Data Display End------->

        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm("Are you sure you want to delete this user?")) {
        window.location = 'user_delete.php?id=' + id; // Assuming you have a delete handler
    }
}
</script>

</body>
</html>

<?php 
} else {
    header('Location: login.php');
}
ob_end_flush();
?>
