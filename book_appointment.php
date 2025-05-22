<?php
require_once "includes/database.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);
$doctorId = (int) $_GET['id'];
$hospitalId = (int) $_GET['hospital_id'];

if (!isset($_GET['id']) || !isset($_GET['hospital_id'])) {
    die("Doctor ID or Hospital ID not provided.");
}

// Get doctor details
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

// Get hospital name
$hospital_query = mysqli_query($conn, "SELECT name FROM hospital WHERE id = $hospitalId");
$selected_hospital = mysqli_fetch_assoc($hospital_query);
$hospital_name = $selected_hospital['name'] ?? 'Not Available';

// Get timings for this doctor & hospital
$timings = [];
$timing_query = "
    SELECT 
        dt.from_time,
        dt.to_time,
        dt.day_id,
        dt.fee
    FROM da_timing dt
    WHERE dt.doctor_id = $doctorId AND dt.hospital_id = $hospitalId
";
$timing_result = mysqli_query($conn, $timing_query);
while ($row = mysqli_fetch_assoc($timing_result)) {
    $timings[] = $row;
}

// Organize timings by day_id
$organized_timings = [];
foreach ($timings as $row) {
    $organized_timings[$row['day_id']][] = $row;
}

// Get first fee if available
$first_fee = isset($timings[0]['fee']) ? number_format($timings[0]['fee']) : 'N/A';
// ... (previous code remains the same)

// Get timings for this doctor & hospital with day names
$timings = [];
$timing_query = "
    SELECT 
        dt.from_time,
        dt.to_time,
        dt.day_id,
        dt.fee,
        md.name as day_name
    FROM da_timing dt
    JOIN mt_day md ON dt.day_id = md.id
    WHERE dt.doctor_id = $doctorId AND dt.hospital_id = $hospitalId
    ORDER BY dt.day_id, dt.from_time
";
$timing_result = mysqli_query($conn, $timing_query);
while ($row = mysqli_fetch_assoc($timing_result)) {
    $timings[] = $row;
}

// Organize timings by day_name
$organized_timings = [];
foreach ($timings as $row) {
    $organized_timings[$row['day_name']][] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="robots" content="noindex, nofollow"> 
       <?php include "styles.php"; ?>

  <script src="https://cdn.tailwindcss.com">
  </script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&amp;display=swap" rel="stylesheet"/>
  <style>
   body {
      font-family: 'Inter', sans-serif;
    }
    

  </style>
 </head>
 <body class="bg-white text-gray-900"><nav class="flex items-center justify-between px-6 py-3 border-b border-gray-200">
    <!-- Logo and Tagline -->
    <div class="flex items-center mb-4 md:mb-0">
        <img src="assets/img/profiles/Favicon.png" alt="Sehat Pro Logo" class="h-10 w-auto"/>
        <div class="ml-3">
            <h1 class="text-xl font-bold text-gray-800">SMART SOFTWARE FOR</h1>
            <h2 class="text-xl font-bold text-blue-600">SMARTER CLINICS</h2>
        </div>
    </div>
  

    <a class="bg-indigo-900 text-white text-sm font-semibold px-4 py-2 rounded-md flex items-center space-x-2" href="tel:0618048444">
      <i class="fas fa-phone-alt">
      </i>
      <span>
       123456789
      </span>
    </a>
  </nav>
<body class="bg-gray-100 font-sans">
<div class="max-w-2xl mx-auto py-6 space-y-6">
    <!-- Doctor Card -->
    <div class="bg-white rounded-xl p-5 shadow">
        <div class="flex space-x-4">
            <img src="<?= $doctor['image'] ? 'uploads/doctors/' . $doctor['image'] : 'https://via.placeholder.com/80' ?>" class="w-20 h-20 rounded-full border" alt="">
            <div>
                <h2 class="text-xl font-bold"><?= $doctor['name'] ?></h2>
                <p class="text-gray-600"><?= $hospital_name ?></p>
                <p class="text-gray-700 font-medium mt-1">Fee: Rs. <?= $first_fee ?></p>
            </div>
        </div>
    </div>
</div>

 <!-- Slot Section -->
 <div class="max-w-2xl mx-auto py-6 space-y-6">
    <div class="bg-white rounded-xl shadow p-5">
        <div class="flex justify-between items-center mb-3">
            <button id="prev" class="text-2xl px-2 hover:text-blue-500">&lt;</button>
            <div id="date-tabs" class="flex space-x-4 overflow-x-auto"></div>
            <button id="next" class="text-2xl px-2 hover:text-blue-500">&gt;</button>
        </div>
        <div class="border-t pt-4">
            <div id="slots" class="text-center text-gray-500 italic">Loading slots...</div>
        </div>
    </div>
</div>
</div>

<script>
let startOffset = 0;
const doctorId = <?= $doctorId ?>;
const hospitalId = <?= $hospitalId ?>;

function loadTabs() {
    $.get('get_slots.php', {
        doctor_id: doctorId,
        hospital_id: hospitalId,
        offset: startOffset,
        days: 4
    }, function(data) {
        const response = JSON.parse(data);
        $('#date-tabs').html('');
        
        response.dates.forEach((item, i) => {
            let dateText;
            if (item.label === 'Today') {
                dateText = `${item.label}, ${item.short}`;
            } else {
                dateText = `${item.month} ${item.short}`;
            }
            
            $('#date-tabs').append(`
                <button data-index="${i}" class="date-tab text-sm px-3 py-2 rounded-md ${
                    item.active ? 'bg-blue-500 text-white font-semibold' : 'text-gray-600 hover:bg-blue-100'
                }">
                    ${dateText}
                </button>
            `);
        });
        
        $('#slots').html(response.slots);
    });
}
$(document).on('click', '.date-tab', function() {
    const index = $(this).data('index');
    $('.date-tab').removeClass('bg-blue-500 text-white font-semibold').addClass('text-gray-600');
    $(this).addClass('bg-blue-500 text-white font-semibold');
    $.get('get_slots.php', {
        doctor_id: doctorId,
        hospital_id: hospitalId,
        offset: startOffset + index,
        days: 4
    }, function(data) {
        const response = JSON.parse(data);
        $('#slots').html(response.slots);
    });
});

$('#next').click(function() {
    startOffset += 1;
    loadTabs();
});

$('#prev').click(function() {
    if (startOffset > 0) {
        startOffset -= 1;
        loadTabs();
    }
});

$(document).ready(loadTabs);
</script>
</body>
</html>
