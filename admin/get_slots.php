<?php
require_once "includes/database.php";

// Set Pakistan timezone
date_default_timezone_set('Asia/Karachi');

$doctorId = (int) $_GET['doctor_id'];
$hospitalId = (int) $_GET['hospital_id'];
$offset = (int) $_GET['offset'];
$days = (int) $_GET['days'];

// Get day names mapping from mt_day
$dayNames = [];
$dayQuery = mysqli_query($conn, "SELECT id, name FROM mt_day");
while ($row = mysqli_fetch_assoc($dayQuery)) {
    $dayNames[$row['id']] = $row['name'];
}

// Generate dates for the next X days (Pakistan time)
$dates = [];
for ($i = 0; $i < $days; $i++) {
    $dayOffset = $offset + $i;
    $date = date('Y-m-d', strtotime("+$dayOffset days"));
    $dayOfWeek = date('w', strtotime($date)); // 0-6 where 0=Sunday
    $dayId = $dayOfWeek + 1; // Convert to mt_day format (1-7)
    $dayName = $dayNames[$dayId] ?? date('l', strtotime($date));
    
    $label = $dayOffset == 0 ? "Today" : $dayName;
    $dates[] = [
        'label' => $label,
        'short' => date('d', strtotime($date)),
        'month' => date('M', strtotime($date)),
        'date' => $date,
        'day_id' => $dayId,
        'active' => $i == 0
    ];
}

$selected = $dates[0];

// Get all available time slots for this doctor at this hospital
$allSlotsQuery = mysqli_query($conn, "
    SELECT day_id, from_time, to_time 
    FROM da_timing 
    WHERE doctor_id = $doctorId 
    AND hospital_id = $hospitalId
    ORDER BY from_time
");

// Organize slots by time period
$morningSlots = [];
$afternoonSlots = [];
$eveningSlots = [];

while ($row = mysqli_fetch_assoc($allSlotsQuery)) {
    $dayIds = explode(',', $row['day_id']);
    $hour = date('H', strtotime($row['from_time']));
    
    $slot = [
        'from' => date('h:i A', strtotime($row['from_time'])),
        'to' => date('h:i A', strtotime($row['to_time']))
    ];
    
    // Check if this slot applies to the selected day
    if (in_array($selected['day_id'], $dayIds)) {
        if ($hour < 12) {
            $morningSlots[] = $slot;
        } elseif ($hour < 17) {
            $afternoonSlots[] = $slot;
        } else {
            $eveningSlots[] = $slot;
        }
    }
}

// Build the HTML output
$html = '';
$hasSlots = false;

if (!empty($morningSlots)) {
    $html .= "<p class='text-sm text-gray-700 mb-2 flex items-center gap-1'><span>🌅</span> Morning Slots</p>";
    $html .= "<div class='flex flex-wrap gap-2 mb-4'>";
    foreach ($morningSlots as $slot) {
        $html .= "<button class='px-4 py-2 border rounded-md text-sm hover:bg-orange-100'>".$slot['from']." - ".$slot['to']."</button>";
        $hasSlots = true;
    }
    $html .= "</div>";
}

if (!empty($afternoonSlots)) {
    $html .= "<p class='text-sm text-gray-700 mb-2 flex items-center gap-1'><span>🌇</span> Afternoon Slots</p>";
    $html .= "<div class='flex flex-wrap gap-2 mb-4'>";
    foreach ($afternoonSlots as $slot) {
        $html .= "<button class='px-4 py-2 border rounded-md text-sm hover:bg-orange-100'>".$slot['from']." - ".$slot['to']."</button>";
        $hasSlots = true;
    }
    $html .= "</div>";
}

if (!empty($eveningSlots)) {
    $html .= "<p class='text-sm text-gray-700 mb-2 flex items-center gap-1'><span>🌃</span> Evening Slots</p>";
    $html .= "<div class='flex flex-wrap gap-2 mb-4'>";
    foreach ($eveningSlots as $slot) {
        $html .= "<button class='px-4 py-2 border rounded-md text-sm hover:bg-orange-100'>".$slot['from']." - ".$slot['to']."</button>";
        $hasSlots = true;
    }
    $html .= "</div>";
}

if (!$hasSlots) {
    $html = "<p class='text-gray-500 italic'>No available slots for {$dayNames[$selected['day_id']]}.</p>";
}

echo json_encode([
    'dates' => $dates,
    'slots' => $html
]);