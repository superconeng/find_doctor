<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "includes/database.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "styles.php"; ?>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
<style>
    .doctor-card {
        max-width: 400px;
        font-size: 12px;
        display: flex;
        flex-direction: column;
        justify-content: space-between; /* Ensures content stays spread evenly */
        height: 100%; /* Forces all cards to take up the same height */
    }
    
    .doctor-image {
        width: 120px;
        height: 120px;
        object-fit: cover;
        margin: 0 auto; /* Centers the image horizontally */
    }
    
    .doctor-card .btn {
        font-size: 0.8rem; /* Makes the phone number text smaller */
        padding: 3px 1rem; /* Adjusts button padding */
        margin-top: auto; /* Pushes the phone button to the bottom of the card */
    }

    .card-title {
        font-weight: bold; /* Makes the doctor's name bold */
        margin-bottom: 0.5rem; /* Adds spacing between name and other content */
    }

    .doctor-card p {
        margin-bottom: 0.5rem; /* Adds spacing between text elements */
    }
</style>

</head>
<body>

<div class="main-wrapper">
    <?php include "left-menu.php"; ?>

    <div class="page-wrapper">
        <div class="content">
            <div class="container mt-4">
                <div class="row">
                    <?php
                    // Fetch doctor data
                    $query = "SELECT d.*, s.name AS specialization_name, deg.name AS degree_title 
                              FROM doctor_detail d
                              LEFT JOIN speciality s ON d.specialization_id = s.id
                              LEFT JOIN mt_degree deg ON d.degree_id = deg.id";
                    
                    $result = mysqli_query($conn, $query);

                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            ?>
                            <div class="col-md-3 mb-3 d-flex justify-content-start mb-5">
                                <div class="card doctor-card shadow-sm p-4 rounded text-center w-100">
                                    <!-- Doctor Image -->
                                     <img class="profile-pic" 
                                            src="admin/uploads/doctors/<?= 
                                                !empty($doctor['image']) ? htmlspecialchars($doctor['image']) : 
                                                ($doctor['gender'] == 'female' ? 'images/girl.jpg' : 'images/male.jpg') 
                                            ?>" 
                                            alt="Dr. <?= htmlspecialchars($doctor['name']) ?>">

                                    <!-- Doctor Info -->
                                    <h5 class="card-title"><?php echo $row['name']; ?></h5>
                                    <p class="mb-1"><strong>Specialization:</strong> <?php echo $row['specialization_name']; ?></p>
                                    <p class="mb-3"><strong>Qualifications:</strong> <?php echo $row['degree_title']; ?></p>

                                    <!-- Phone Button -->
                                    <a href="tel:<?php echo $row['phone']; ?>" class="btn btn-primary px-4 rounded-pill">
                                        <i class="fas fa-phone-alt me-2"></i><?php echo $row['phone']; ?>
                                    </a>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<p>No doctors found.</p>";
                    }
                    ?>
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
