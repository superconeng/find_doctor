<?php
require_once "includes/database.php";

$cityId = isset($_GET['city']) ? $_GET['city'] : null;
$specialtyId = isset($_GET['specialty']) ? $_GET['specialty'] : null;

$query = "
    SELECT 
        d.id,
        d.name,
        d.image,
        d.phone,
        d.experience,
        GROUP_CONCAT(DISTINCT s.name ORDER BY s.name ASC) AS specialization,  
        GROUP_CONCAT(DISTINCT deg.name ORDER BY deg.name ASC) AS degrees
    FROM doctor_detail d
    LEFT JOIN speciality s ON FIND_IN_SET(s.id, d.specialization_id)
    LEFT JOIN mt_degree deg ON FIND_IN_SET(deg.id, d.degree_id)
    WHERE 1
";

if ($cityId) {
    $query .= " AND d.city_id = " . (int)$cityId;
}
if ($specialtyId) {
    $query .= " AND FIND_IN_SET(" . (int)$specialtyId . ", d.specialization_id)";
}
$query .= " GROUP BY d.id";

$result = mysqli_query($conn, $query);
$doctors = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $doctors[] = $row;
    }
}

function getAvailability($doctorId, $conn) {
    $availQuery = "
        SELECT dt.*, h.name AS hospital_name 
        FROM da_timing dt 
        JOIN hospital h ON h.id = dt.hospital_id 
        WHERE dt.doctor_id = " . (int)$doctorId;

    $result = mysqli_query($conn, $availQuery);
    $availabilities = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $dayId = (int)$row['day_id'];

            $dayQuery = mysqli_query($conn, "SELECT name FROM mt_day WHERE id = $dayId");
            $dayData = mysqli_fetch_assoc($dayQuery);
            $dayName = $dayData ? $dayData['name'] : '';

            // Calculate date of next occurrence
            $today = new DateTime();
            $targetDay = date('w', strtotime($dayName));
            $currentDay = (int)$today->format('w');
            $daysToAdd = ($targetDay - $currentDay + 7) % 7;

            $targetDate = clone $today;
            $targetDate->modify("+{$daysToAdd} days");

            $label = "Available " . $dayName . ", " . $targetDate->format('F j');
            if ($daysToAdd === 0) {
                $label = "Available Today";
            } elseif ($daysToAdd === 1) {
                $label = "Available Tomorrow";
            }

            $availabilities[] = [
                'hospital' => $row['hospital_name'],
                'label' => $label,
                'fee' => $row['fee']
            ];
        }
    }
    return $availabilities;
}
?>



<html lang="en">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
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
 <body class="bg-white text-gray-900">
  <!-- Navbar -->
<nav class="flex items-center justify-between px-6 py-3 border-b border-gray-200">
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
       0612365434
      </span>
    </a>
  </nav>

  <!-- Filter pills -->
<section class="px-10 py-6 border-b border-gray-200 overflow-x-auto max-w-7xl mx-auto">
    <div class="flex space-x-3 min-w-max">
        <button class="text-indigo-900 text-xs font-semibold border border-indigo-900 rounded-full px-4 py-1 hover:bg-indigo-50 whitespace-nowrap">
         Female Doctors
        </button>
        <button class="text-indigo-900 text-xs font-semibold border border-indigo-900 rounded-full px-4 py-1 hover:bg-indigo-50 whitespace-nowrap">
         Doctors Near Me
        </button>
        <button class="text-indigo-900 text-xs font-semibold border border-indigo-900 rounded-full px-4 py-1 hover:bg-indigo-50 whitespace-nowrap">
         Most Experienced
        </button>
        <button class="text-indigo-900 text-xs font-semibold border border-indigo-900 rounded-full px-4 py-1 hover:bg-indigo-50 whitespace-nowrap">
         Lowest Fee
        </button>
        
        <button class="text-indigo-900 text-xs font-semibold border border-indigo-900 rounded-full px-4 py-1 hover:bg-indigo-50 whitespace-nowrap">
         Available Today
        </button>
        <button class="text-indigo-900 text-xs font-semibold border border-indigo-900 rounded-full px-4 py-1 hover:bg-indigo-50 whitespace-nowrap">
         Discounts
        </button>
        <button class="text-indigo-900 text-xs font-semibold border border-indigo-900 rounded-full px-4 py-1 hover:bg-indigo-50 whitespace-nowrap">
         Video Consultation
        </button>
        <button class="text-indigo-900 text-xs font-semibold border border-indigo-900 rounded-full px-4 py-1 hover:bg-indigo-50 whitespace-nowrap">
         Online Now
        </button>
    </div>
</section>
<hr>

<section class="max-w-7xl mx-auto px-6 py-8">
    <div class="grid grid-cols-1 gap-6">
        <?php foreach ($doctors as $doctor): ?>
            <?php $availability = getAvailability($doctor['id'], $conn); ?>
            <div class="bg-white rounded-lg shadow-md p-6 flex items-center justify-between space-x-6">
                <!-- Doctor Image -->
                <div class="relative flex-shrink-0">
                    <?php if (!empty($doctor['image'])): ?>
                        <img 
                            alt="Portrait of <?= htmlspecialchars($doctor['name']) ?>" 
                            class="w-[130px] h-[130px] object-cover rounded-full border" 
                            src="uploads/doctors/<?= htmlspecialchars($doctor['image']) ?>"
                        />
                    <?php else: ?>
                        <!-- Display initials when no image exists -->
                        <div class="w-[130px] h-[130px] rounded-full border bg-gray-200 flex items-center justify-center">
                            <?php
                            $nameParts = explode(' ', $doctor['name']);
                            $initials = '';
                            foreach ($nameParts as $part) {
                                if (count($nameParts) > 2) break; // Only take first 2 initials if name has more than 2 words
                                $initials .= strtoupper(substr($part, 0, 1));
                            }
                            ?>
                            <span class="text-4xl font-bold text-gray-600"><?= $initials ?></span>
                        </div>
                    <?php endif; ?>
                    </div>

                <!-- Doctor Content -->
                <div class="flex-1">
                    <h2 class="text-gray-900 font-semibold text-xl">
                        <a href="doctor_full_detail.php?id=<?= (int)$doctor['id'] ?>" style="all: unset; cursor: pointer;">
                            <?= htmlspecialchars($doctor['name']) ?>
                        </a>
                    </h2>

                    <p class="text-gray-700 text-base mt-1">
                        <?= htmlspecialchars($doctor['specialization']) ?>
                    </p>
                    <p class="text-gray-700 text-sm mt-1">
                        <?= 
                            htmlspecialchars(str_replace(',', ', ', $doctor['degrees']))
                        ?>
                    </p>

                    <div class="flex flex-col items-start">
                        <?php if (!empty($doctor['experience'])): ?>
                            <?php
                            // Extract just the years from experience
                            preg_match('/\d+\s+years?/', $doctor['experience'], $matches);
                            $yearsOnly = $matches[0] ?? $doctor['experience'];
                            ?>
                            <span><?= htmlspecialchars($yearsOnly) ?></span>
                            <span class="text-gray-500 text-xs">Experience</span>
                        <?php else: ?>
                            <span>&nbsp;</span>
                            <span class="text-gray-500 text-xs"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Availability Section -->
                <?php if (!empty($availability)): ?>
                    <div class="flex flex-wrap gap-4 max-w-[700px]">
                        <?php foreach ($availability as $avail): ?>
                            <div class="border border-gray-300 rounded-lg p-4 w-[270px] min-h-[100px]">
                                <span class="font-semibold text-gray-800 text-[15px] block"><?= htmlspecialchars($avail['hospital']) ?></span>
                                <span class="text-green-600 text-sm mt-1 block">
                                    <i class="fas fa-circle text-green-500 mr-1 text-xs"></i><?= $avail['label'] ?>
                                </span>
                                <span class="text-right text-base font-bold text-gray-800 block mt-2">Rs. <?= htmlspecialchars($avail['fee']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>



 </body>
</html>
