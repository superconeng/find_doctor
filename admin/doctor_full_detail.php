<?php
require_once "includes/database.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (!isset($_GET['id'])) {
    die("Doctor ID not provided.");
    
}


$doctorId = (int) $_GET['id'];

// Main query to get doctor details
$query = "
    SELECT 
        d.id,
        d.name,
        d.image,
        d.phone,
        d.experience,
        d.c_treated,
        d.service,
        d.about,
        d.specialization_id,
        d.degree_id,
        d.membership_id,
        GROUP_CONCAT(DISTINCT s.name ORDER BY s.name ASC) AS specialization,  
        GROUP_CONCAT(DISTINCT deg.name ORDER BY deg.name ASC) AS degrees
    FROM doctor_detail d
    LEFT JOIN speciality s ON FIND_IN_SET(s.id, d.specialization_id)
    LEFT JOIN mt_degree deg ON FIND_IN_SET(deg.id, d.degree_id)
    WHERE d.id = $doctorId
    GROUP BY d.id
";

$result = mysqli_query($conn, $query);
$doctor = mysqli_fetch_assoc($result);

if (!$doctor) {
    die("Doctor not found.");
}

// Function to extract list items from HTML content
function extractListItems($html) {
    if (empty($html)) return [];
    
    // Remove HTML tags except li
    $clean = strip_tags($html, '<li>');
    
    // Extract li content
    preg_match_all('/<li>(.*?)<\/li>/', $clean, $matches);
    
    if (!empty($matches[1])) {
        return array_map('trim', $matches[1]);
    }
    
    // Fallback: if no li tags, split by newlines
    $items = preg_split('/\r\n|\r|\n/', strip_tags($html));
    return array_filter(array_map('trim', $items));
}

// Process services and conditions
$services = !empty($doctor['service']) ? extractListItems($doctor['service']) : [];
$conditions = !empty($doctor['c_treated']) ? extractListItems($doctor['c_treated']) : [];

// Process degrees
$degrees = [];
if (!empty($doctor['degree_id'])) {
    $degree_ids = explode(',', $doctor['degree_id']);
    $degree_ids = array_map('intval', $degree_ids); // Sanitize

    $in = implode(',', $degree_ids);
    $degree_query = "SELECT * FROM mt_degree WHERE id IN ($in)";
    $degree_result = mysqli_query($conn, $degree_query);

    while ($row = mysqli_fetch_assoc($degree_result)) {
        $degrees[] = $row['name'];
    }
}
$memberships = [];
if (!empty($doctor['membership_id'])) {
    $membership_ids = explode(',', $doctor['membership_id']);
    $membership_ids = array_map('intval', $membership_ids);

    $in = implode(',', $membership_ids);
    $membership_query = "SELECT * FROM mt_membership WHERE id IN ($in)";
    $membership_result = mysqli_query($conn, $membership_query);

    while ($row = mysqli_fetch_assoc($membership_result)) {
        $memberships[] = $row['name'];
    }
}
$doctorId = $_GET['id'];

$timings = [];

$query = "
    SELECT 
        h.name AS hospital_name,
    h.id AS hospital_id,
        h.map_location AS hospital_address,
        dt.fee,
        dt.from_time,
        dt.to_time,
        GROUP_CONCAT(d.name ORDER BY d.id) AS days
    FROM da_timing dt
    LEFT JOIN hospital h ON dt.hospital_id = h.id
    LEFT JOIN mt_day d ON FIND_IN_SET(d.id, dt.day_id)
    WHERE dt.doctor_id = $doctorId
    GROUP BY dt.hospital_id, dt.fee, dt.from_time, dt.to_time
";

$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $timings[] = $row;
}
function formatDayInfo($dayString) {
    $today = date('N'); // 1 (Mon) to 7 (Sun)
    $tomorrow = date('N', strtotime('+1 day'));
    
    $daysArray = explode(',', $dayString);
    $labels = [];

    foreach ($daysArray as $dayName) {
        $dayNum = date('N', strtotime($dayName));
        if ($dayNum == $today) {
            $labels[] = 'Available today';
        } elseif ($dayNum == $tomorrow) {
            $labels[] = 'Available tomorrow';
        } else {
            $labels[] = $dayName . ', ' . date('d M', strtotime("next $dayName"));
        }
    }

    return implode(', ', $labels);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="robots" content="noindex, nofollow">
    <?php include "styles.php"; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff;
            color: #212529;
        }
        
        .profile-img {
            width: 130px;
            height: 130px;
            object-fit: cover;
            border: 2px solid #20c997;
        }
        
        .initials-avatar {
            width: 130px;
            height: 130px;
            border: 2px solid #20c997;
            background-color: #e9ecef;
            font-size: 2.5rem;
            font-weight: bold;
            color: #6c757d;
        }
        
        .hospital-card {
            border-radius: 12px;
            border: 1px solid #dee2e6;
        }
        
        .service-list, .condition-list, .education-list, 
        .specialization-list, .membership-list {
            padding-left: 1.5rem;
        }
        
        .service-list li, .condition-list li, .education-list li,
        .specialization-list li, .membership-list li {
            margin-bottom: 0.5rem;
        }
        
        .availability-badge {
            font-size: 0.875rem;
            color: #198754;
        }
        
        .availability-badge i {
            font-size: 0.5rem;
            vertical-align: center;
        }
        
        @media (max-width: 767px) {
            .profile-section {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .sidebar-content {
                margin-left: auto;
                max-width: 300px;
            }
            
            .hospital-card {
                text-align:center;
                margin-left: auto;
                max-width: 300px;
            }
            
            .support-info {
                text-align: right;
                margin-left: auto;
                max-width: 300px;
            }
            
            .support-info .d-flex {
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
  

    <div class="container py-4">
        <div class="row">
            <!-- Left main content -->
            <div class="col-lg-8">
                <!-- Profile header -->
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-4 mb-4 profile-section">
                    <div class="flex-shrink-0 text-center">
                        <?php if (!empty($doctor['image'])): ?>
                            <img 
                                alt="Portrait of <?= htmlspecialchars($doctor['name']) ?>" 
                                class="profile-img rounded-circle" 
                                src="uploads/doctors/<?= htmlspecialchars($doctor['image']) ?>"
                            />
                        <?php else: ?>
                            <!-- Display initials when no image exists -->
                            <div class="initials-avatar rounded-circle d-flex align-items-center justify-content-center mx-auto">
                                <?php
                                $nameParts = explode(' ', $doctor['name']);
                                $initials = '';
                                foreach ($nameParts as $part) {
                                    if (count($nameParts) > 2) break;
                                    $initials .= strtoupper(substr($part, 0, 1));
                                }
                                ?>
                                <span><?= $initials ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <h1 class="h4 fw-semibold text-truncate" title="<?= htmlspecialchars($doctor['name']) ?>">
                            <?= htmlspecialchars($doctor['name']) ?>
                        </h1>
                        <p class="text-muted text-truncate">
                            <?= !empty($doctor['specialization']) ? htmlspecialchars($doctor['specialization']) : '&nbsp;' ?>
                        </p>
                        <p class="text-muted text-truncate">
                            <?= !empty($doctor['degrees']) 
                                ? htmlspecialchars(implode(', ', explode(',', $doctor['degrees']))) 
                                : '&nbsp;' ?>
                        </p>

                        <div class="d-flex gap-4 text-muted mt-3">
                            <div>
                                <div class="d-flex flex-column">
                                    <?php if (!empty($doctor['experience'])): ?>
                                        <?php
                                        preg_match('/\d+\s+years?/', $doctor['experience'], $matches);
                                        $yearsOnly = $matches[0] ?? $doctor['experience'];
                                        ?>
                                        <span><?= htmlspecialchars($yearsOnly) ?></span>
                                        <span class="text-muted small">Experience</span>
                                    <?php else: ?>
                                        <span>&nbsp;</span>
                                        <span class="text-muted small"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">

                <!-- Services Section -->
                <section class="mb-4">
                    <h3 class="h6 fw-semibold mb-3">Services</h3>
                    <div class="text-muted small ms-3">
                        <ul class="service-list list-disc">
                            <?php
                            $services = htmlspecialchars_decode($doctor['service']);
                            $services = strip_tags($services);
                            $service_items = preg_split('/\r\n|\r|\n/', $services);
                            foreach ($service_items as $item) {
                                if (trim($item) != '') {
                                    echo '<li>' . trim($item) . '</li>';
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </section>

                <hr class="my-4">

                <!-- Conditions Treated Section -->
                <section class="mb-4">
                    <h3 class="h6 fw-semibold mb-3">Condition Treated</h3>
                    <div class="text-muted small ms-3">
                        <ul class="condition-list list-disc">
                            <?php
                            $conditions = htmlspecialchars_decode($doctor['c_treated']);
                            $conditions = strip_tags($conditions);
                            
                            $condition_items = preg_split('/\r\n|\r|\n/', $conditions);
                            
                            if (count($condition_items) <= 1) {
                                $condition_items = preg_split('/\.\s+/', $conditions);
                            }
                            
                            foreach ($condition_items as $item) {
                                $item = trim($item);
                                if (!empty($item)) {
                                    if (!preg_match('/[.!?]$/', $item)) {
                                        $item .= '.';
                                    }
                                    echo '<li>' . $item . '</li>';
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </section>

                <hr class="my-4">

                <!-- Education Section -->
                <section class="mb-4">
                    <h3 class="h6 fw-semibold mb-3">Education</h3>
                    <div class="text-muted small ms-3">
                        <ul class="education-list list-disc">
                            <?php foreach ($degrees as $degree): ?>
                                <li><?= htmlspecialchars(trim($degree)) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>

                <hr class="my-4">

                <!-- Specialization Section -->
                <section class="mb-4">
                    <h3 class="h6 fw-semibold mb-3">Specialization</h3>
                    <div class="text-muted small ms-3">
                        <ul class="specialization-list list-disc">
                            <?php foreach (explode(',', $doctor['specialization']) as $spec): ?>
                                <li><?= htmlspecialchars(trim($spec)) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>

                <hr class="my-4">

                <!-- Professional Memberships Section -->
                <section class="mb-4">
                    <h3 class="h6 fw-semibold mb-3">Professional Memberships</h3>
                    <div class="text-muted small ms-3">
                        <ul class="membership-list list-disc">
                            <?php foreach ($memberships as $membership): ?>
                                <li><?= htmlspecialchars(trim($membership)) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>

                <hr class="my-4">

                <!-- About Section -->
                <section class="mb-4">
                    <h3 class="h6 fw-semibold mb-3">About</h3>
                    <div class="text-muted small">
                        <?= htmlspecialchars_decode($doctor['about']) ?>
                    </div>
                </section>
            </div>
            
            <!-- Right sidebar -->
            <div class="col-lg-4">
                <div class="sidebar-content">
                    <?php foreach ($timings as $row): ?>
                        <div class="hospital-card shadow-sm p-3 mb-4 bg-white">
                            <!-- Hospital Name -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-hospital text-primary fs-5"></i>
                                    <h3 class="h6 fw-semibold mb-0">
                                        <?= htmlspecialchars($row['hospital_name']) ?>
                                    </h3>
                                </div>
                            </div>

                            <!-- Fee -->
                            <div class="d-flex justify-content-between small text-muted border-bottom pb-2 mb-2">
                                <span>Fee:</span>
                                <span class="fw-semibold">Rs. <?= htmlspecialchars($row['fee']) ?></span>
                            </div>

                            <!-- Address -->
                            <div class="d-flex align-items-start gap-2 small text-muted mb-2">
                                <i class="fas fa-map-marker-alt mt-1"></i>
                                <div class="text-break">
                                    <?php if (!empty($row['hospital_address'])): ?>
                                        <a href="https://www.google.com/maps?q=<?= urlencode($row['hospital_name'] . ' ' . $row['hospital_address']) ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer"
                                           class="text-primary fw-semibold text-decoration-none">
                                            <?= htmlspecialchars($row['hospital_name']) ?>
                                            <i class="fas fa-external-link-alt ms-1 small"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="https://www.google.com/maps?q=<?= urlencode($row['hospital_name']) ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer"
                                           class="text-primary fw-semibold text-decoration-none">
                                            <?= htmlspecialchars($row['hospital_name']) ?>
                                            <i class="fas fa-external-link-alt ms-1 small"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Availability -->
                            <div class="availability-badge d-flex align-items-center gap-2 fw-semibold mb-1">
                                <i class="fas fa-circle"></i>
                                <span><?= formatDayInfo($row['days']) ?></span>
                            </div>

                            <!-- Timing -->
                            <div class="d-flex justify-content-between small fw-semibold text-muted mb-3">
                                <span><?= htmlspecialchars($row['from_time']) ?> - <?= htmlspecialchars($row['to_time']) ?></span>
                            </div>

                            <!-- Button -->
                            <a href="book_appointment.php?id=<?= $doctor['id'] ?>&hospital_id=<?= $row['hospital_id'] ?>" class="btn btn-primary w-100 btn-sm fw-semibold">
                                Book Appointment
                            </a>
                        </div>
                    <?php endforeach; ?>

                    <!-- Support info -->
                    <div class="text-muted small support-info">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-headphones-alt"></i>
                            <span>Priority customer support</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-lock"></i>
                            <span>100% secure</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-clock"></i>
                            <span>Book Appointment in 30 sec</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add this function to your PHP code
        function getGoogleMapsLink($address) {
            if (empty($address)) {
                return false;
                
            }
            
            // Basic cleaning
            $cleanAddress = trim(strip_tags($address));
            $cleanAddress = preg_replace('/\s+/', ' ', $cleanAddress);
            
            // If address contains a Google Maps short URL, return that directly
            if (strpos($cleanAddress, 'goo.gl/maps') !== false || 
                strpos($cleanAddress, 'maps.app.goo.gl') !== false) {
                return $cleanAddress;
            }
            
            // Otherwise create a search query
            return 'https://www.google.com/maps?q=' . urlencode($cleanAddress);
        }
    </script>
</body>
</html>