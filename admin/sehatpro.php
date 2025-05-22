<?php
require_once "includes/database.php";

// Fetching City Data from mt_city table
$cityQuery = "SELECT id, name FROM mt_city ORDER BY name";
$cityResult = mysqli_query($conn, $cityQuery);

// Fetching Specialization Data from speciality table
$specialtyQuery = "SELECT id, name FROM speciality ORDER BY name";
$specialtyResult = mysqli_query($conn, $specialtyQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>Sehat Pro - Smart Software for Smarter Clinics</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    body {
      font-family: 'Inter', sans-serif;
    }
    .professional-gradient {
      background: linear-gradient(90deg, #2C7BE5 0%, #00D97E 100%);
    }
    .search-form {
      display: flex;
      width: 100%;
      max-width: 800px;
    }
    .search-form select, .search-form button {
      height: 48px;
    }
  </style>
</head>
<body class="bg-gray-50">
  <!-- Header with Logo and Navigation -->
  <header class="bg-white shadow-sm">
    <div class="container mx-auto px-6 py-4">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <!-- Logo and Tagline -->
        <div class="flex items-center mb-4 md:mb-0">
          <img src="assets/img/profiles/Favicon.png" alt="Sehat Pro Logo" class="h-10 w-auto"/>
          <div class="ml-3">
            <h1 class="text-xl font-bold text-gray-800">SMART SOFTWARE FOR</h1>
            <h2 class="text-xl font-bold text-blue-600">SMARTER CLINICS</h2>
          </div>
        </div>
        
        <!-- Main Navigation -->
        <nav class="flex flex-wrap justify-center gap-4 md:gap-6 text-sm font-medium text-gray-700">
          
          <a href="#" class="text-blue-600 border border-blue-600 rounded px-3 py-1 hover:bg-blue-50 transition">Login/SignUp</a>
          <a href="#" class="text-green-600 border border-green-600 rounded px-3 py-1 hover:bg-green-50 transition">Join as Doctor</a>
        </nav>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="professional-gradient text-white">
    <div class="container mx-auto px-6 py-12 md:py-20">
      <div class="flex flex-col md:flex-row items-center">
        <!-- Content -->
        <div class="md:w-1/2 mb-8 md:mb-0">
          <h1 class="text-3xl md:text-4xl font-bold leading-tight mb-4">
            Find and Book the <span class="text-green-300">Best Doctors</span> near you
          </h1>
          
          <div class="flex items-center mb-8">
            <i class="fas fa-phone-alt mr-2"></i>
            <span class="text-lg font-medium">0612365434</span>
          </div>
          
          <div class="inline-flex items-center bg-blue-700 rounded-full px-4 py-2 text-sm font-medium mb-6">
            <i class="fas fa-check-circle text-green-300 mr-2"></i>
            <span>50M+ patients served</span>
          </div>
          
          <!-- Search Form with Dropdowns -->
          <form class="search-form rounded-md overflow-hidden shadow-lg bg-white" action="doctors.php" method="GET">
    <!-- City Dropdown -->
    <select class="flex-grow px-4 py-3 text-gray-900 outline-none" name="city">
        <option value="" disabled selected>City</option>
        <?php while ($city = mysqli_fetch_assoc($cityResult)): ?>
            <option value="<?= $city['id'] ?>" <?= isset($_GET['city']) && $_GET['city'] == $city['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($city['name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
    
    <!-- Specialty Dropdown -->
    <select class="flex-grow px-4 py-3 text-gray-900 outline-none border-l border-gray-200" name="specialty">
        <option value="" disabled selected>Doctors, Hospital, Conditions</option>
        <?php while ($specialty = mysqli_fetch_assoc($specialtyResult)): ?>
            <option value="<?= $specialty['id'] ?>" <?= isset($_GET['specialty']) && $_GET['specialty'] == $specialty['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($specialty['name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
    
    <!-- Submit Button -->
    <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 transition" type="submit">
        Search
    </button>
</form>

        </div>
        
        <!-- Doctor Image -->
       <div class="md:w-1/2 flex justify-center">
  <img alt="Doctor" class="rounded-lg shadow-xl max-w-full max-h-[430px] h-auto" 
       src="https://storage.googleapis.com/a1aa/image/6960b857-5d2f-46cb-0375-d368bded9c70.jpg"/>
</div>

      </div>
    </div>
  </section>
</body>
</html>
