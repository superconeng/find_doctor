 
<?php
require_once "admin/includes/database.php";
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
    d.gender,
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
    $degree_ids = array_map('intval', $degree_ids);

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

// Modified query to get all distinct hospital data
$query = "
    SELECT 
        h.name AS hospital_name,
        h.id AS hospital_id,
        h.map_location AS hospital_address
    FROM da_timing dt
    LEFT JOIN hospital h ON dt.hospital_id = h.id
    WHERE dt.doctor_id = $doctorId
    GROUP BY h.id
";

$hospitals_result = mysqli_query($conn, $query);
$hospitals = [];
while ($row = mysqli_fetch_assoc($hospitals_result)) {
    $hospitals[$row['hospital_id']] = $row;
}
$dayMapping = [
    'Monday' => 'Mon',
    'Tuesday' => 'Tue',
    'Wednesday' => 'Wed',
    'Thursday' => 'Thu',
    'Friday' => 'Fri',
    'Saturday' => 'Sat',
    'Sunday' => 'Sun'
];
// Now get all timings for each hospital
foreach ($hospitals as $hospital_id => $hospital) {
    $timing_query = "
        SELECT 
            dt.from_time,
            dt.to_time,
            dt.shift,
            GROUP_CONCAT(DISTINCT d.name ORDER BY d.id) AS days
        FROM da_timing dt
        LEFT JOIN mt_day d ON FIND_IN_SET(d.id, dt.day_id)
        WHERE dt.doctor_id = $doctorId AND dt.hospital_id = $hospital_id
        GROUP BY dt.from_time, dt.to_time, dt.shift
    ";
    
    $timing_result = mysqli_query($conn, $timing_query);
    $hospitals[$hospital_id]['timings'] = [];
    while ($row = mysqli_fetch_assoc($timing_result)) {
        $hospitals[$hospital_id]['timings'][] = $row;
    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<style>
 * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

.sesoft-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 20px;
    padding: 20px;
}

.sesoft-left {
    background-color: #fff;
    border-radius: 12px;
    padding: 24px;
    flex: 1 1 360px;
}

.sesoft-right {
    background-color: #fff;
    padding: 24px;
    border-radius: 12px;
    border: 1px solid #dee2e6;
    height: 360px;
}

.sesoft-profile {
    display: flex;
    align-items: center;
    gap: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
    margin-bottom: 20px;
}

.sesoft-profile-img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #d9d9d9;
}

.sesoft-profile h2 {
    font-size: 20px;
    margin-bottom: 4px;
}

.sesoft-info-title {
    font-weight: 600;
    color: #222;
    margin-top: 18px;
}

.sesoft-info-value {
    color: #555;
    margin-top: 5px;
    font-size: 15px;
}

.sesoft-appointment-box h4 {
    font-size: 18px;
    margin-bottom: 10px;
}

.sesoft-btn-book {
    background-color: #0066f5;
    color: #fff;
    font-weight: 300;
    border: none;
    padding: 9px;
    width: 100%;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 15px;
    font-size: 16px;
}

.sesoft-btn-book:hover {
    background-color: #0051c2;
}

.sesoft-appointment-details {
    margin-top: 10px;
    line-height: 1.6;
}

.sesoft-support-info {
    margin-top: 20px;
    font-size: 14px;
    color: #444;
    line-height: 1.8;
}
.sesoft-info-value ul {
    list-style-type: disc;
    padding-left: 20px;
    margin: 10px 0;
}
.sesoft-info-value ol {
    list-style-type: decimal;
    padding-left: 20px;
    margin: 10px 0;
}
.sesoft-info-value li {
    margin-bottom: 5px;
}
.sesoft-btn-book {
    display: block;
    text-align: center;
    /* your other styles like background, padding, etc. */
}

@media (max-width: 768px) {
    .sesoft-container {
        flex-direction: column;
    }
}
@media (max-width: 768px) {
    .sesoft-container {
        flex-direction: column;
    }

    .sesoft-profile {
        flex-direction: column; /* Stack image and text */
        align-items: center; /* Center on mobile */
        text-align: center; /* Optional: center text */
    }

    .sesoft-profile-img {
        margin-bottom: 10px; /* Space between image and name */
    }
}

</style>
</head>
<body>
  
<?php include "2.php";?>

<div class="sesoft-container">
    <div class="sesoft-left">
        <div class="sesoft-profile">        
            <img class="sesoft-profile-img" 
                 src="admin/uploads/doctors/<?= !empty($doctor['image']) ? htmlspecialchars($doctor['image']) : ($doctor['gender'] == 'female' ? 'images/girl.jpg' : 'images/male.jpg') 
                        ?>" alt="Dr. <?= htmlspecialchars($doctor['name']) ?>">

            <div>
                <h2><?= htmlspecialchars(strip_tags($doctor['name'])) ?></h2>
                <div class="sesoft-info-value"><?= !empty($doctor['specialization']) ? htmlspecialchars(strip_tags($doctor['specialization'])) : ' ' ?>
                </div>
                <div class="sesoft-info-value"><?= !empty($degrees) ? htmlspecialchars(strip_tags(implode(', ', $degrees))) : ' ' ?>
                </div>
                <div class="sesoft-info-value"><?= !empty($doctor['experience']) ? strip_tags($doctor['experience']) . 'Experience' : ' ' ?>
                </div>
            </div>
        </div>

        <div>
            <div class="sesoft-info-title">Specialization</div>
            <div class="sesoft-info-value"><?= !empty($doctor['specialization']) ? htmlspecialchars(strip_tags($doctor['specialization'])) : '-' ?></div>

          <div class="sesoft-info-title">Education</div>
<div class="sesoft-info-value">
    <?php if (!empty($degrees)): ?>
        <ul style="list-style-type: none; padding-left: 0;">
            <?php foreach ($degrees as $degree): ?>
                <?php 
                $cleaned_degree = htmlspecialchars_decode(strip_tags($degree, '<strong><b><em><i>'));
                if (!empty(trim($cleaned_degree))): ?>
                    <li style="margin-bottom: 5px;">• <?= $cleaned_degree ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        -
    <?php endif; ?>
</div>

            <div class="sesoft-info-title">Services</div>
            <div class="sesoft-info-value">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    • <?= htmlspecialchars(str_replace('&nbsp;', ' ', strip_tags($service))) ?><br>
                <?php endforeach; ?>
            <?php else: ?>
                -
            <?php endif; ?>
            </div>
            
            <div class="sesoft-info-title">Condition Treated</div>
            <div class="sesoft-info-value">
            <?php if (!empty($conditions)): ?>
                <?php foreach ($conditions as $condition): ?>
                    • <?= htmlspecialchars(str_replace('&nbsp;', ' ', strip_tags($condition))) ?><br>
                <?php endforeach; ?>
            <?php else: ?>
                -
            <?php endif; ?>
            </div>

            <div class="sesoft-info-title">Professional Memberships</div>
            <div class="sesoft-info-value"><?= !empty($memberships) ? htmlspecialchars(strip_tags(implode(', ', $memberships))) : '-' ?></div>

            <div class="sesoft-info-title">About</div>
               <div class="sesoft-info-value">
                    <?php if (!empty($doctor['about'])): ?>
                        <?= str_replace('&nbsp;', ' ', htmlspecialchars_decode(strip_tags($doctor['about'], '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><span>'))) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
        </div>
    </div>

 <div class="sesoft-right">
    <?php foreach ($hospitals as $hospital): ?>
        <?php
            $hospitalName = htmlspecialchars(strip_tags($hospital['hospital_name']));
            $mapsLink = "https://www.google.com/maps?q=" . urlencode($hospitalName);
        ?>
        <div class="sesoft-appointment-box">
            <h4><?= $hospitalName ?></h4>

            <div class="sesoft-info-value">
                <?php if (!empty($hospital['hospital_address'])): ?>
                <div style="display: flex; gap: 6px;">
                    <strong>Location:</strong>
                    <a href="<?= $mapsLink ?>" target="_blank"><?= $hospitalName ?></a>
                </div>
                <?php endif; ?>

                <?php foreach ($hospital['timings'] as $timing): ?>
    <div style="margin-top: 10px;">
        <?php if (!empty($timing['days'])): ?>
        <div style="display: flex; gap: 6px;">
            <strong>Days:</strong>
            <span>
                <?php 
                // Split days string into array
                $daysArray = explode(',', $timing['days']);
                // Trim whitespace from each day
                $daysArray = array_map('trim', $daysArray);
                // Shorten day names
                $shortenedDays = array_map(function($day) use ($dayMapping) {
                    return $dayMapping[$day] ?? $day;
                }, $daysArray);
                // Display shortened days
                echo htmlspecialchars(implode(', ', $shortenedDays));
                ?>
            </span>
        </div>
        <?php endif; ?>
        
        <div style="display: flex; gap: 6px; align-items: center; margin-top: 5px;">
            <?php if (!empty($timing['shift'])): ?>
            <span style="font-weight: 500;"><?= htmlspecialchars(ucfirst(strip_tags($timing['shift']))) ?></span>
            <?php endif; ?>
            
            <?php if (!empty($timing['from_time']) && !empty($timing['to_time'])): ?>
            <span>
                <?= date('h:i A', strtotime($timing['from_time'])) ?> - 
                <?= date('h:i A', strtotime($timing['to_time'])) ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
            </div>

            <a href="?id=<?= $doctor['id'] ?>&hospital_id=<?= $hospital['hospital_id'] ?>" class="sesoft-btn-book" style="display: block; text-align: center;">
                Book Appointment
            </a>

            <div class="sesoft-support-info">
                <div>✔ Priority customer support</div>
                <div>✔ 100% secure</div>
                <div>✔ Book Appointment in 30 sec</div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</div>
</div>
       <?php include "footer.php";?>
 

 <script>
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