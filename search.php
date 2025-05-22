
<?php
// Database connection
require_once "admin/includes/database.php";
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetching City Data
$cityQuery = "SELECT id, name FROM mt_city ORDER BY name";
$cityResult = mysqli_query($conn, $cityQuery);
if (!$cityResult) {
    die("City query failed: " . mysqli_error($conn));
}

// Fetching Specialization Data and create mapping
$specialtyQuery = "SELECT id, name FROM speciality ORDER BY name";
$specialtyResult = mysqli_query($conn, $specialtyQuery);
if (!$specialtyResult) {
    die("Specialty query failed: " . mysqli_error($conn));
}

$specialistMapping = [];
while ($row = mysqli_fetch_assoc($specialtyResult)) {
    $specialistMapping[$row['name']] = $row['id'];
}

// Selected filters with proper sanitization
$cityId = isset($_GET['city']) ? (int)$_GET['city'] : null;
$specialtyId = isset($_GET['specialty']) ? (int)$_GET['specialty'] : null;

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

// Main Query to get doctors with phone number and gender
$mainQuery = "
    SELECT 
        d.id,
        d.name,
        d.image,
        d.phone,
        d.gender,
        GROUP_CONCAT(DISTINCT s.name ORDER BY s.name ASC SEPARATOR ', ') AS specialization,
        GROUP_CONCAT(DISTINCT deg.name ORDER BY deg.name ASC SEPARATOR ', ') AS degrees
    FROM doctor_detail d
    LEFT JOIN speciality s ON FIND_IN_SET(s.id, d.specialization_id)
    LEFT JOIN mt_degree deg ON FIND_IN_SET(deg.id, d.degree_id)
    WHERE 1
";

if ($cityId) {
    $mainQuery .= " AND d.city_id = " . (int)$cityId;
}
if ($specialtyId) {
    $mainQuery .= " AND FIND_IN_SET(" . (int)$specialtyId . ", d.specialization_id)";
}

$mainQuery .= " GROUP BY d.id LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $mainQuery);
if (!$result) {
    die("Main query failed: " . mysqli_error($conn));
}

$doctors = [];
while ($row = mysqli_fetch_assoc($result)) {
    $doctors[] = $row;
}
// Modified function to show short day names
function getDoctorAvailability($doctorId, $conn, $currentCityId) {
    $doctorId = (int)$doctorId;

    // Fetch all availability rows with Morning shifts first
    $query = "
        SELECT 
            h.id AS hospital_id,
            h.name AS hospital_name,
            dt.shift,
            dt.from_time,
            dt.to_time,
            GROUP_CONCAT(DISTINCT md.name ORDER BY md.id SEPARATOR ',') AS days
        FROM da_timing dt
        JOIN hospital h ON dt.hospital_id = h.id
        LEFT JOIN mt_day md ON FIND_IN_SET(md.id, dt.day_id)
        WHERE dt.doctor_id = $doctorId
        GROUP BY dt.id
        ORDER BY h.name, 
                 CASE WHEN dt.shift = 'Morning' THEN 0 ELSE 1 END,  -- Morning first
                 dt.from_time
    ";

    $result = mysqli_query($conn, $query);
    if (!$result) {
        return [];
    }

    // Group by hospital
    $hospitalAvailability = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $hospitalId = $row['hospital_id'];

        $hospitalLink = '<a href="dc-search.php?city=' . $currentCityId . '" class="hospital-link">' .
                        '<i class="fas fa-hospital"></i> ' . htmlspecialchars($row['hospital_name']) . '</a>';

        if (!isset($hospitalAvailability[$hospitalId])) {
            $hospitalAvailability[$hospitalId] = [
                'hospital' => $hospitalLink,
                'schedules' => []
            ];
        }

        // Convert full day names to short names
        $shortDays = '';
        if (!empty($row['days'])) {
            $dayNames = explode(',', $row['days']);
            $shortDayNames = array_map(function($day) {
                $day = trim($day);
                return substr($day, 0, 3); // 3-letter day names
            }, $dayNames);
            $shortDays = implode(', ', $shortDayNames);
        }

        $hospitalAvailability[$hospitalId]['schedules'][] = [
            'shift' => $row['shift'],
            'days' => $shortDays,
            'time' => date('h:i A', strtotime($row['from_time'])) . ' - ' . date('h:i A', strtotime($row['to_time']))
        ];
    }

    return $hospitalAvailability;
}

// Count total for pagination
$countQuery = "
    SELECT COUNT(DISTINCT d.id) AS total
    FROM doctor_detail d
    WHERE 1
";
if ($cityId) {
    $countQuery .= " AND d.city_id = " . (int)$cityId;
}
if ($specialtyId) {
    $countQuery .= " AND FIND_IN_SET(" . (int)$specialtyId . ", d.specialization_id)";
}

$countResult = mysqli_query($conn, $countQuery);
if (!$countResult) {
    $totalDoctors = 0;
} else {
    $totalDoctors = mysqli_fetch_assoc($countResult)['total'];
}
$totalPages = ceil($totalDoctors / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sehat Pro</title>
    <meta name="title" content="Smart Software for Smarter Clinics">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/favicon.jpg">
    <link rel="stylesheet" href="style/styles.css">
    <style>
        /* Add to your styles.css */
        .listing-card-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .listing-card-link:hover .listing-card {
            background-color: #f5f5f5;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .card-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        
        .hospital-link {
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
        
        .hospital-link:hover {
            text-decoration: underline;
        }
        .hospital-link i {
            margin-right: 6px;
        }
        .availability-title{
            text-align: left;
        }
        .availability-item{
            text-align:left;
        }
        .hospital-group{
            text-align:left;
        }
        @media (max-width: 767px) {
            .docotor-card {
                display: flex;
                flex-direction: column;
            }
            
            .doctor-link {
                display: flex;
                flex-direction: column;
            }
            
            .left-section {
                order: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            .left-section .info {
                order: 2;
                padding: 15px;
                width: 100%;
                text-align: center;
            }
            
            .right-section {
                order: 3;
                width: 100%;
                padding: 15px;
                margin-right:20px;
            }
        }
    </style>
</head>
<body id="top">
    <div class="preloader" data-preloader>
        <div class="circle"></div>
    </div>

    <main>
        <article>
            <section class="section hero" aria-label="home">
                <div class="container hero">
                    <div class="hero-content">
                        <p class="hero-subtitle has-before" data-reveal="left">Welcome To Sehat Pro</p>
                        <h1 class="headline-lg hero-title" data-reveal="left">
                            Near by Clinics,</br>
                            Hospitals.
                        </h1>
                    </div>
                    <div class="search-card" data-reveal="left">
                        <form action="search.php" method="GET">
                            <div class="doc-form-row">
                                <!-- City Dropdown -->
                                <div class="doc-input-group">
                                    <i class="fa fa-map-marker-alt"></i>
                                    <select name="city" required>
                                        <option value="" disabled selected>City</option>
                                        <?php 
                                        mysqli_data_seek($cityResult, 0);
                                        while ($city = mysqli_fetch_assoc($cityResult)): ?>
                                            <option value="<?= htmlspecialchars($city['id']) ?>" <?= isset($_GET['city']) && $_GET['city'] == $city['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($city['name']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <!-- Specialty Dropdown -->
                                <div class="doc-input-group">
                                    <i class="fa fa-stethoscope"></i>
                                    <select name="specialty" required>
                                        <option value="" disabled selected>Doctors</option>
                                        <?php 
                                        mysqli_data_seek($specialtyResult, 0);
                                        while ($specialty = mysqli_fetch_assoc($specialtyResult)): ?>
                                            <option value="<?= htmlspecialchars($specialty['id']) ?>" <?= isset($_GET['specialty']) && $_GET['specialty'] == $specialty['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($specialty['name']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="doc-btn-submit">
                                    <i class="fa fa-search"></i> Find Now
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Search Results Section -->
            <section class="section listing searchboxes" aria-labelledby="listing-label">
                <div class="container doc-container">
                    <?php if ($doctors) : ?>
                        <?php foreach ($doctors as $doctor) : ?>
                            <?php $availability = getDoctorAvailability($doctor['id'], $conn, $cityId); ?>
                            
                            <div class="docotor-card" role="region" aria-label="Doctor profile card">
                               <a href="doctor_full_detail.php?id=<?= htmlspecialchars($doctor['id']) ?>&name=<?= urlencode($doctor['name']) ?>" class="doctor-link">


                                    <!-- Left section now contains ONLY image and info -->
                                    <div class="left-section">
                                        <!-- Image (now at top for mobile) -->
                                        
                                        
                                        <img class="profile-pic" 
                                            src="admin/uploads/doctors/<?= 
                                                !empty($doctor['image']) ? htmlspecialchars($doctor['image']) : 
                                                ($doctor['gender'] == 'female' ? 'images/girl.jpg' : 'images/male.jpg') 
                                            ?>" 
                                            alt="Dr. <?= htmlspecialchars($doctor['name']) ?>">
                                        
                                        <!-- Info (now below image for mobile) -->
                                        <div class="info">
                                            <h2 class="name"><?= htmlspecialchars($doctor['name']) ?></h2>
                                            <p><strong>Specialization:</strong> <?= htmlspecialchars($doctor['specialization']) ?></p>
                                            <p><strong>Qualifications:</strong> <?= htmlspecialchars($doctor['degrees']) ?></p>

                                            <?php if (!empty($doctor['phone'])): ?>
                                                <a class="phone" href="tel:<?= htmlspecialchars($doctor['phone']) ?>" aria-label="Call <?= htmlspecialchars($doctor['phone']) ?>">
                                                    <i class="fas fa-phone" aria-hidden="true"></i><?= htmlspecialchars($doctor['phone']) ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Right section (now at bottom for mobile) -->
                                <?php if (!empty($availability)): ?>
                                <div class="right-section" aria-label="Availability information">
                                    <?php foreach ($availability as $hospital): ?>
                                        <!-- 1. Hospital Name -->
                                        <div class="hospital-group"><?= $hospital['hospital'] ?></div>
                            
                                        <!-- 2. Group days together first -->
                                        <?php 
                                        // Group days across shifts
                                        $allDays = [];
                                        foreach ($hospital['schedules'] as $s) {
                                            if (!empty($s['days'])) {
                                                $allDays[] = $s['days'];
                                            }
                                        }
                                        $uniqueDays = implode(', ', array_unique(explode(', ', implode(', ', $allDays))));
                                        ?>
                                        <?php if (!empty($uniqueDays)): ?>
                                            <div class="availability-item calendar">
                                                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                                <?= htmlspecialchars($uniqueDays) ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- 3. Now show all shift: time pairs -->
                                        <?php foreach ($hospital['schedules'] as $schedule): ?>
                                            <div class="availability-item clock">
                                                <i class="fas fa-clock" aria-hidden="true"></i>
                                                <strong><?= htmlspecialchars($schedule['shift']) ?>:</strong>
                                                <?= htmlspecialchars($schedule['time']) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?<?php 
                                        echo http_build_query(array_merge(
                                            $_GET,
                                            ['page' => $i]
                                        ));
                                    ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="no-doctors">
                            <h3>No doctors found matching your criteria</h3>
                            <p>Please try different search filters</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!------Specialist Listing Start--------->
            <section class="section listing" aria-labelledby="listing-label">
    <div class="container">
        <ul class="grid-list">
            <li>
                <p class="section-subtitle title-lg" id="listing-label">Specialist Listing</p>
                <h2 class="headline-md">Browse by Doctors</h2>
            </li>
            
            <!-- Dermatologist -->
            <li>
                <a href="get_speciality_id.php?term=Dermatologist&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/Dermatologist.png" width="71" height="71" loading="lazy" alt="Dermatologist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Dermatologist</h3>
                            <p class="card-text">Skin Specialist</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- Gynecologist -->
            <li>
                <a href="get_speciality_id.php?term=Gynecologist&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/Gynecologist.png" width="71" height="71" loading="lazy" alt="Gynecologist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Gynecologist</h3>
                            <p class="card-text">Women Specialist</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- Urologist -->
            <li>
                <a href="get_speciality_id.php?term=Urologists&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/urology.png" width="71" height="71" loading="lazy" alt="Urologist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Urologist</h3>
                            <p class="card-text">Urinary Specialist</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- Gastroenterologist -->
            <li>
                <a href="get_speciality_id.php?term=Gastroenterologist&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/Gastroenterologist.png" width="71" height="71" loading="lazy" alt="Gastroenterologist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Gastroenterologist</h3>
                            <p class="card-text">Digestive</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- Dentist -->
            <li>
                <a href="get_speciality_id.php?term=Dentist&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/Dentist.png" width="71" height="71" loading="lazy" alt="Dentist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Dentist</h3>
                            <p class="card-text">Teeth Specialist</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- Psychiatrist -->
            <li>
                <a href="get_speciality_id.php?term=Psychiatrist&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/Psychiatrist.png" width="71" height="71" loading="lazy" alt="Psychiatrist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Psychiatrist</h3>
                            <p class="card-text">Mental Specialist</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- ENT Specialist -->
            <li>
                <a href="get_speciality_id.php?term=E.N.T&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/ENT-Specialist.png" width="71" height="71" loading="lazy" alt="ENT Specialist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">ENT Specialist</h3>
                            <p class="card-text">ENT Doctor</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- Orthopedic Surgeon -->
            <li>
                 <a href="get_speciality_id.php?term=Orthopedic&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/Orthopedic-Surgeon.png" width="71" height="71" loading="lazy" alt="Orthopedic Surgeon">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Orthopedic Surgeon</h3>
                            <p class="card-text">Bone & Joint Specialist</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- Sexologist -->
            <li>
               <a href="get_speciality_id.php?term=Sexologist&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/Sexologist.png" width="71" height="71" loading="lazy" alt="Sexologist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Sexologist</h3>
                            <p class="card-text">Sexual Health Specialist</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- Neurologist -->
            <li>
                <a href="get_speciality_id.php?term=Neurologist&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/Neurologist.png" width="71" height="71" loading="lazy" alt="Neurologist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Neurologist</h3>
                            <p class="card-text">Nervous System Specialist</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- Child Specialist -->
            <li>
                <a href="get_speciality_id.php?term=Child Specialist&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/Child-Specialist.png" width="71" height="71" loading="lazy" alt="Child Specialist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Child Specialist</h3>
                            <p class="card-text">Kids' Doctor</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- Pulmonologist -->
            <li>
                <a href="get_speciality_id.php?term=Pulmonologist&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/Pulmonologist.png" width="71" height="71" loading="lazy" alt="Pulmonologist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Pulmonologist</h3>
                            <p class="card-text">Lung Specialist</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- Eye Specialist -->
            <li>
                <a href="get_speciality_id.php?term=Eye Specialist&city=<?= $cityId ?>" class="listing-card-link">
                    <div class="listing-card" data-reveal="bottom">
                        <div class="card-icon">
                            <img src="images/icons/Eye-Specialist.png" width="71" height="71" loading="lazy" alt="Eye Specialist">
                        </div>
                        <div>
                            <h3 class="headline-sm card-title">Eye Specialist</h3>
                            <p class="card-text">Ophthalmologist</p>
                        </div>
                    </div>
                </a>
            </li>
            
            <!-- General Physician -->
           <li>
            <a href="get_speciality_id.php?term=Physician&city=<?= $cityId ?>" class="listing-card-link">
                <div class="listing-card" data-reveal="bottom">
                    <div class="card-icon">
                        <img src="images/icons/General-Physician.png" width="71" height="71" loading="lazy" alt="General Physician">
                    </div>
                    <div>
                        <h3 class="headline-sm card-title">General Physician</h3>
                        <p class="card-text">Primary Doctor</p>
                    </div>
                </div>
            </a>
        </li>

        </ul>
    </div>
</section>
    </main>

    <?php include "footer.php";?>
    <script src="js/script.js"></script>
</body>
</html>