
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

// Fetching Specialization Data
$specialtyQuery = "SELECT id, name FROM speciality ORDER BY name";
$specialtyResult = mysqli_query($conn, $specialtyQuery);
if (!$specialtyResult) {
    die("Specialty query failed: " . mysqli_error($conn));
}

// Selected filters with proper sanitization
$cityId = isset($_GET['city']) ? (int)$_GET['city'] : null;
$specialtyId = isset($_GET['specialty']) ? (int)$_GET['specialty'] : null;

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

// Main Query to get doctors with phone number
$mainQuery = "
    SELECT 
        d.id,
        d.name,
        d.image,
        d.phone,
        d.experience,
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

// Function to get availability for each doctor
function getDoctorAvailability($doctorId, $conn) {
    $doctorId = (int)$doctorId;
    $query = "
        SELECT 
            h.name AS hospital_name,
            dt.fee,
            dt.from_time,
            dt.to_time,
            GROUP_CONCAT(DISTINCT md.name ORDER BY md.id SEPARATOR ',') AS days
        FROM da_timing dt
        JOIN hospital h ON dt.hospital_id = h.id
        LEFT JOIN mt_day md ON FIND_IN_SET(md.id, dt.day_id)
        WHERE dt.doctor_id = $doctorId
        GROUP BY dt.hospital_id, dt.fee, dt.from_time, dt.to_time
    ";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return []; // Return empty array if query fails
    }
    
    $availability = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $today = date('N'); // 1 (Mon) to 7 (Sun)
        $tomorrow = date('N', strtotime('+1 day'));
        
        $daysArray = explode(',', $row['days']);
        $labels = [];
        
        foreach ($daysArray as $dayName) {
            $dayNum = date('N', strtotime($dayName));
            if ($dayNum == $today) {
                $labels[] = 'Today';
            } elseif ($dayNum == $tomorrow) {
                $labels[] = 'Tomorrow';
            } else {
                $labels[] = $dayName . ' ' . date('d M', strtotime("next $dayName"));
            }
        }
        
        $availability[] = [
            'hospital' => $row['hospital_name'],
            'days' => implode(', ', $labels),
            'time' => date('h:i A', strtotime($row['from_time'])) . ' - ' . date('h:i A', strtotime($row['to_time'])),
            'fee' => number_format($row['fee'], 2)
        ];
    }
    
    return $availability;
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

function getUserCity() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $response = @file_get_contents("http://ip-api.com/json/{$ip}");
    if ($response !== false) {
        $data = json_decode($response, true);
        return $data['city'] ?? '';
    }
    return '';
}

$autoCity = getUserCity();
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
while ($city = mysqli_fetch_assoc($cityResult)): 
    $selected = '';
    if (isset($_GET['city']) && $_GET['city'] == $city['id']) {
        $selected = 'selected';
    } elseif (strtolower($city['name']) == strtolower($autoCity)) {
        $selected = 'selected';
    }
?>
    <option value="<?= htmlspecialchars($city['id']) ?>" <?= $selected ?>>
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
                                        // Reset pointer and loop again
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

    </article>
  </main>
  <script src="js/script.js"></script>
</body>
</html>
