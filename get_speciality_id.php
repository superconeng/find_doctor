<?php
// Database connection
require_once "admin/includes/database.php";
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


if (isset($_GET['term']) && isset($_GET['city'])) {
    $searchTerm = $_GET['term'];
    $cityId = $_GET['city']; // Retrieve the city ID from the query parameter

    // Find the closest match in `speciality` table
    $query = "SELECT id FROM speciality WHERE name LIKE ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $like = "%" . $searchTerm . "%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $stmt->bind_result($id);

    if ($stmt->fetch()) {
        // Redirect to dc-search.php with the matched specialty ID and city ID
        header("Location: dc-search.php?specialty=$id&city=$cityId");
        exit();
    } else {
        // If no match found, redirect with default or error
        header("Location: dc-search.php?specialty=0&city=0");
        exit();
    }
} else {
    // If `term` or `city` is not set, redirect with an error
    header("Location: dc-search.php?specialty=0&city=0");
    exit();
}
?>