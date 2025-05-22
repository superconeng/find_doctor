<?php
// Database connection
require_once "admin/includes/database.php";
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch ALL cities for dropdown (not filtered by search)
$cityQueryAll = "SELECT id, name FROM mt_city ORDER BY name";
$cityResultAll = mysqli_query($conn, $cityQueryAll);

// Fetch ALL specialties for dropdown (not filtered by search)
$specialtyQueryAll = "SELECT id, name FROM speciality ORDER BY name";
$specialtyResultAll = mysqli_query($conn, $specialtyQueryAll);

// Get filters from URL with proper sanitization
$cityId = isset($_GET['city']) ? (int)$_GET['city'] : null;
$specialtyId = isset($_GET['specialty']) ? (int)$_GET['specialty'] : null;
$fromSearchForm = isset($_GET['from_search_form']) ? true : false;

// Get specialty and city names for display
$specialtyName = '';
$cityName = '';

if ($specialtyId) {
    $specialtyQuery = "SELECT name FROM speciality WHERE id = $specialtyId";
    $specialtyResult = mysqli_query($conn, $specialtyQuery);
    if ($specialtyResult && $specialtyRow = mysqli_fetch_assoc($specialtyResult)) {
        $specialtyName = $specialtyRow['name'];
    }
}

if ($cityId) {
    $cityQuery = "SELECT name FROM mt_city WHERE id = $cityId";
    $cityResult = mysqli_query($conn, $cityQuery);
    if ($cityResult && $cityRow = mysqli_fetch_assoc($cityResult)) {
        $cityName = $cityRow['name'];
    }
}

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

// Count total doctors for pagination
$countQuery = "SELECT COUNT(DISTINCT d.id) as total 
               FROM doctor_detail d
               WHERE 1";
               
if ($cityId) {
    $countQuery .= " AND d.city_id = $cityId";
}
if ($specialtyId) {
    $countQuery .= " AND FIND_IN_SET($specialtyId, d.specialization_id)";
}

$countResult = mysqli_query($conn, $countQuery);
$totalDoctors = $countResult ? mysqli_fetch_assoc($countResult)['total'] : 0;
$totalPages = ceil($totalDoctors / $limit);

// Main Query
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
    $mainQuery .= " AND d.city_id = $cityId";
}
if ($specialtyId) {
    $mainQuery .= " AND FIND_IN_SET($specialtyId, d.specialization_id)";
}

$mainQuery .= " GROUP BY d.id LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $mainQuery);
$doctors = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

// Function to get doctor availability with shift information
function getDoctorAvailability($doctorId, $conn) {
    $doctorId = (int)$doctorId;
    $query = "
        SELECT 
            h.id AS hospital_id,
            h.name AS hospital_name,
            dt.from_time,
            dt.to_time,
            dt.shift,
            GROUP_CONCAT(DISTINCT md.name ORDER BY md.id SEPARATOR ',') AS days
        FROM da_timing dt
        JOIN hospital h ON dt.hospital_id = h.id
        LEFT JOIN mt_day md ON FIND_IN_SET(md.id, dt.day_id)
        WHERE dt.doctor_id = $doctorId
        GROUP BY dt.hospital_id, dt.from_time, dt.to_time, dt.shift
    ";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return [];
    }
    
    $rawAvailability = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rawAvailability[] = $row;
    }
    
    $hospitalGroups = [];
    foreach ($rawAvailability as $avail) {
        $hospitalId = $avail['hospital_id'];
        
        if (!isset($hospitalGroups[$hospitalId])) {
            $hospitalGroups[$hospitalId] = [
                'hospital_id' => $avail['hospital_id'],
                'hospital_name' => $avail['hospital_name'],
                'days_groups' => []
            ];
        }
        
        $daysKey = $avail['days'];
        if (!isset($hospitalGroups[$hospitalId]['days_groups'][$daysKey])) {
            $hospitalGroups[$hospitalId]['days_groups'][$daysKey] = [
                'days' => $avail['days'],
                'timings' => []
            ];
        }
        
        $hospitalGroups[$hospitalId]['days_groups'][$daysKey]['timings'][] = [
            'from' => $avail['from_time'],
            'to' => $avail['to_time'],
            'shift' => $avail['shift']
        ];
    }
    
    // Map full day names to short forms
    $dayShortNames = [
        'Monday' => 'Mon',
        'Tuesday' => 'Tue',
        'Wednesday' => 'Wed',
        'Thursday' => 'Thu',
        'Friday' => 'Fri',
        'Saturday' => 'Sat',
        'Sunday' => 'Sun'
    ];
    
    $availability = [];
    foreach ($hospitalGroups as $hospital) {
        $hospitalEntry = [
            'hospital' => $hospital['hospital_name'],
            'days_groups' => []
        ];
        
        foreach ($hospital['days_groups'] as $daysGroup) {
            // Shorten the day names
            $daysArray = explode(',', $daysGroup['days']);
            $shortenedDays = array_map(function($day) use ($dayShortNames) {
                return $dayShortNames[$day] ?? $day; // Use short name if exists
            }, $daysArray);
            $shortenedDaysString = implode(', ', $shortenedDays);
            
            $formattedTimings = [];
            foreach ($daysGroup['timings'] as $timing) {
                $formattedTimings[] = [
                    'time' => date('h:i A', strtotime($timing['from'])) . ' - ' . 
                              date('h:i A', strtotime($timing['to'])),
                    'shift' => $timing['shift']
                ];
            }
            
            $hospitalEntry['days_groups'][] = [
                'days' => $shortenedDaysString,
                'timings' => $formattedTimings
            ];
        }
        
        $availability[] = $hospitalEntry;
    }
    
    return $availability;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sehat Pro - Doctor Search</title>
    <meta name="title" content="Find Specialist Doctors">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/favicon.jpg">
    <link rel="stylesheet" href="style/styles.css">
       <style>
        .current-filters {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .current-filters .container {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .current-filters h3 {
            margin: 0;
            font-size: 18px;
        }
        .current-filters ul {
            display: flex;
            gap: 15px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .current-filters li {
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
        }
        .no-doctors {
            text-align: center;
            padding: 50px 0;
        }
        .no-doctors h3 {
            color: #dc3545;
            margin-bottom: 10px;
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
        .availability-title {
            text-align: left;
            margin-top: 10px;
            font-weight: bold;
        }
        .availability-item {
            text-align: left;
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .timing-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .hospital-availability {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .hospital-availability:last-child {
            border-bottom: none;
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
            }
            .current-filters {
                background-color: #f8f9fa;
            }
            .current-filters h3 {
                font-size: 16px;
                font-weight: bold;
                margin-top: 4px;
            }
            .current-filters ul li {
                font-size: 12px;
                line-height: 1.4;
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
                        <p class="hero-subtitle has-before" data-reveal="left">Find Specialist Doctors</p>
                        <h1 class="headline-lg hero-title" data-reveal="left">
                           Qualified Healthcare,</br>
                            Professionals.
                        </h1>
                    </div>

                    <div class="search-card" data-reveal="left">
                        <form action="dc-search.php" method="GET">
                            <input type="hidden" name="from_search_form" value="1">
                            <div class="doc-form-row">
                                <!-- City Dropdown -->
                                <div class="doc-input-group">
                                    <i class="fa fa-map-marker-alt"></i>
                                    <select name="city" required>
                                        <option value="" disabled selected>City</option>
                                        <?php 
                                        if ($cityResultAll) {
                                            mysqli_data_seek($cityResultAll, 0);
                                            while ($city = mysqli_fetch_assoc($cityResultAll)): ?>
                                                <option value="<?= htmlspecialchars($city['id']) ?>" 
                                                    <?= $cityId == $city['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($city['name']) ?>
                                                </option>
                                            <?php endwhile;
                                        } ?>
                                    </select>
                                </div>

                                <!-- Specialty Dropdown -->
                                <div class="doc-input-group">
                                    <i class="fa fa-stethoscope"></i>
                                    <select name="specialty" required>
                                        <option value="" disabled selected>Specialty</option>
                                        <?php 
                                        if ($specialtyResultAll) {
                                            mysqli_data_seek($specialtyResultAll, 0);
                                            while ($specialty = mysqli_fetch_assoc($specialtyResultAll)): ?>
                                                <option value="<?= htmlspecialchars($specialty['id']) ?>" 
                                                    <?= $specialtyId == $specialty['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($specialty['name']) ?>
                                                </option>
                                            <?php endwhile;
                                        } ?>
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

            <!-- Current Filters Section - Only show if NOT from search form -->
            <?php if (!$fromSearchForm && ($specialtyName || $cityName)): ?>
            <section class="current-filters" aria-label="Current search filters">
                <div class="container">
                    <h3>Showing results for:</h3>
                    <ul>
                        <?php if ($specialtyName): ?>
                            <li>Specialty: <?= htmlspecialchars($specialtyName) ?></li>
                        <?php endif; ?>
                        <?php if ($cityName): ?>
                            <li>City: <?= htmlspecialchars($cityName) ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </section>
            <?php endif; ?>
    <!-- Search Results Section -->
    <section class="section listing searchboxes" aria-labelledby="listing-label">
        <div class="container doc-container">
            <?php if (!empty($doctors)) : ?>
                <?php foreach ($doctors as $doctor) : ?>
                    <?php $availability = getDoctorAvailability($doctor['id'], $conn); ?>
                    
                    <div class="docotor-card" role="region" aria-label="Doctor profile card">
                        <a href="doctor_full_detail.php?id=<?= htmlspecialchars($doctor['id']) ?>" class="doctor-link">
                            <div class="left-section">
                               <img class="profile-pic" 
                                    src="admin/uploads/doctors/<?= 
                                        !empty($doctor['image']) ? htmlspecialchars($doctor['image']) : 
                                        (isset($doctor['gender']) && $doctor['gender'] == 'female' ? 'images/girl.jpg' : 'images/male.jpg') 
                                    ?>" 
                                    alt="Dr. <?= htmlspecialchars($doctor['name']) ?>">
                                
                                <div class="info">
                                    <h2 class="name"><?= htmlspecialchars($doctor['name']) ?></h2>
                                    <p><strong>Specialization:</strong> <?= htmlspecialchars($doctor['specialization'] ?? 'N/A') ?></p>
                                    <p><strong>Qualifications:</strong> <?= htmlspecialchars($doctor['degrees'] ?? 'N/A') ?></p>

                                    <?php if (!empty($doctor['phone'])): ?>
                                        <a class="phone" href="tel:<?= htmlspecialchars($doctor['phone']) ?>" aria-label="Call <?= htmlspecialchars($doctor['phone']) ?>">
                                            <i class="fas fa-phone" aria-hidden="true"></i><?= htmlspecialchars($doctor['phone']) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($availability)): ?>
                                <div class="right-section" aria-label="Availability information">
                                    <?php foreach ($availability as $avail): ?>
                                        <div class="hospital-availability">
                                            <?php if (!empty($avail['hospital'])): ?>
                                                <div class="hospital">
                                                    <a href="dc-search.php?city=<?= $cityId ?>" class="hospital-link">
                                                        <i class="fas fa-hospital" aria-hidden="true"></i><?= htmlspecialchars($avail['hospital']) ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php foreach ($avail['days_groups'] as $daysGroup): ?>
                                                <?php if (!empty($daysGroup['days'])): ?>
                                                    <div class="availability-item calendar">
                                                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                                        <span><?= htmlspecialchars($daysGroup['days']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($daysGroup['timings'])): ?>
                                                    <div class="availability-item clock">
                                                        <i class="fas fa-clock" aria-hidden="true"></i>
                                                        <div class="timing-group">
                                                            <?php foreach ($daysGroup['timings'] as $timing): ?>
                                                                <div>
                                                                    <?php if (!empty($timing['shift'])): ?>
                                                                        <span class="shift-label"><?= htmlspecialchars(ucfirst($timing['shift'])) ?></span>
                                                                    <?php endif; ?>
                                                                    <?= htmlspecialchars($timing['time']) ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
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
        </article>
    </main>

    <?php include "footer.php"; ?>
    <script src="js/script.js"></script>
</body>
</html>