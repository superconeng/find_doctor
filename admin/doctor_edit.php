<?php
session_start();
require_once "includes/database.php";

// Check if doctor ID is provided
if (!isset($_GET['id'])) {
    header("Location: doctor_list.php");
    exit();
}

$doctor_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch doctor details
$doctor_query = "SELECT * FROM doctor_detail WHERE id = '$doctor_id'";
$doctor_result = mysqli_query($conn, $doctor_query);
$doctor = mysqli_fetch_assoc($doctor_result);

if (!$doctor) {
    $_SESSION['error'] = "Doctor not found";
    header("Location: doctor_list.php");
    exit();
}

// Fetch doctor timings
$timings_query = "SELECT * FROM da_timing WHERE doctor_id = '$doctor_id'";
$timings_result = mysqli_query($conn, $timings_query);
$timings = mysqli_fetch_all($timings_result, MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input fields
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $experience = mysqli_real_escape_string($conn, $_POST['experience']);
    $about = mysqli_real_escape_string($conn, $_POST['about']);
    $service = mysqli_real_escape_string($conn, $_POST['service']);
    $c_treated = mysqli_real_escape_string($conn, $_POST['c_treated']);
    $city_id = mysqli_real_escape_string($conn, $_POST['city_id']);

    // Image upload
    $image_name = $doctor['image']; // Keep existing image if not changed
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = "uploads/doctors/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Delete old image if exists
        if ($image_name && file_exists($upload_dir . $image_name)) {
            unlink($upload_dir . $image_name);
        }

        $tmp_name = $_FILES['image']['tmp_name'];
        $original_name = basename($_FILES['image']['name']);
        $image_name = time() . '_' . $original_name;

        move_uploaded_file($tmp_name, $upload_dir . $image_name);
    }

    // Implode multi-select fields
    $specialization_ids = !empty($_POST['specialization_id']) ? implode(',', $_POST['specialization_id']) : '';
    $hospital_ids = !empty($_POST['hospital_id']) ? implode(',', $_POST['hospital_id']) : '';
    $degree_ids = !empty($_POST['degree_id']) ? implode(',', $_POST['degree_id']) : '';
    $membership_ids = !empty($_POST['membership_id']) ? implode(',', $_POST['membership_id']) : '';

    // Update doctor_detail table
    $query = "UPDATE doctor_detail SET
              name = '$name',
              image = '$image_name',
              phone = '$phone',
              gender = '$gender',
              city_id = '$city_id',
              experience = '$experience',
              about = '$about',
              specialization_id = '$specialization_ids',
              hospital_id = '$hospital_ids',
              degree_id = '$degree_ids',
              membership_id = '$membership_ids',
              service = '$service',
              c_treated = '$c_treated'
              WHERE id = '$doctor_id'";

    if (mysqli_query($conn, $query)) {
        // Delete existing timings
        $delete_query = "DELETE FROM da_timing WHERE doctor_id = '$doctor_id'";
        mysqli_query($conn, $delete_query);

        // Handle new timings
        if (!empty($_POST['timing_hospital_id'])) {
            foreach ($_POST['timing_hospital_id'] as $index => $hospital_id) {
                $day_ids = $_POST['timing_day_ids'][$index];
                $from_time_24 = $_POST['timing_from_time'][$index];
                $to_time_24 = $_POST['timing_to_time'][$index];
                $shift = mysqli_real_escape_string($conn, $_POST['timing_shift'][$index]);
                
                // Convert to 12-hour format with AM/PM
                $from_time = date("g:i A", strtotime($from_time_24));
                $to_time = date("g:i A", strtotime($to_time_24));

                // Sanitize
                $from_time = mysqli_real_escape_string($conn, $from_time);
                $to_time = mysqli_real_escape_string($conn, $to_time);
                
                $query = "INSERT INTO da_timing 
                          (hospital_id, doctor_id, day_id, shift, from_time, to_time)
                          VALUES 
                          ('$hospital_id', '$doctor_id', '$day_ids', '$shift', '$from_time', '$to_time')";
                
                mysqli_query($conn, $query);
            }
        }

        $_SESSION['success'] = "Doctor updated successfully!";
        header("Location: doctor_list.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating doctor: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="robots" content="noindex, nofollow">
    <?php include "styles.php"; ?>
    <style>
        .timing-row {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        .selected-days {
            margin-top: 10px;
            padding: 5px;
            background: #e9ecef;
            border-radius: 4px;
        }
        .remove-timing {
            color: red;
            cursor: pointer;
            font-size: 20px;
        }
        .add-time-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="left-menu">
        <?php include "left-menu.php"; ?>
    </div>
    <div class="page-wrapper">
        <div class="content">
            <div class="card">
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <!-- Doctor Details -->
                        <h4>Doctor Details</h4>
                        <!-- Doctor Image -->
                        <div class="form-group">
                            <label>Doctor Image</label>
                            <?php if ($doctor['image']): ?>
                                <div class="mb-2">
                                    <img src="uploads/doctors/<?= $doctor['image'] ?>" style="max-height: 100px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($doctor['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number<span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($doctor['phone']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="male" <?= $doctor['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= $doctor['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other" <?= $doctor['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>City<span class="text-danger">*</span></label>
                            <select name="city_id" class="form-control" required>
                                <option value="">Select City</option>
                                <?php
                                $cities = mysqli_query($conn, "SELECT id, name FROM mt_city ORDER BY name ASC");
                                while ($city = mysqli_fetch_assoc($cities)) {
                                    $selected = $city['id'] == $doctor['city_id'] ? 'selected' : '';
                                    echo "<option value=\"{$city['id']}\" $selected>" . htmlspecialchars($city['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Experience</label>
                            <textarea name="experience" class="form-control" id="experience"><?= htmlspecialchars($doctor['experience']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>About</label>
                            <textarea name="about" class="form-control" id="about"><?= htmlspecialchars($doctor['about']) ?></textarea>
                        </div>

                        <!-- Specialization -->
                        <h4>Specialization</h4>
                        <div class="form-group">
                            <label>Specialization<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <button class="form-control dropdown-toggle text-start" type="button" id="specializationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php
                                    $selected_specs = explode(',', $doctor['specialization_id']);
                                    $spec_names = [];
                                    if (!empty($selected_specs[0])) {
                                        $spec_query = "SELECT name FROM speciality WHERE id IN (" . implode(',', $selected_specs) . ")";
                                        $spec_result = mysqli_query($conn, $spec_query);
                                        while ($spec = mysqli_fetch_assoc($spec_result)) {
                                            $spec_names[] = htmlspecialchars($spec['name']);
                                        }
                                    }
                                    echo empty($spec_names) ? 'Select Specializations' : implode(', ', $spec_names);
                                    ?>
                                </button>
                                <ul class="dropdown-menu w-100 p-2" aria-labelledby="specializationDropdown" style="max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                    <?php
                                    $result = mysqli_query($conn, "SELECT * FROM speciality ORDER BY name ASC");
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $checked = in_array($row['id'], $selected_specs) ? 'checked' : '';
                                    ?>
                                    <li class="mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="specialization_id[]" value="<?= $row['id'] ?>" id="spec_<?= $row['id'] ?>" <?= $checked ?>>
                                            <label class="form-check-label" for="spec_<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></label>
                                        </div>
                                    </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Hospital Info -->
                        <h4>Hospital Information</h4>
                        <div class="form-group">
                            <label>Hospital</label>
                            <div class="dropdown">
                                <button class="form-control dropdown-toggle text-start" type="button" id="hospitalDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php
                                    $selected_hospitals = explode(',', $doctor['hospital_id']);
                                    $hosp_names = [];
                                    if (!empty($selected_hospitals[0])) {
                                        $hosp_query = "SELECT name FROM hospital WHERE id IN (" . implode(',', $selected_hospitals) . ")";
                                        $hosp_result = mysqli_query($conn, $hosp_query);
                                        while ($hosp = mysqli_fetch_assoc($hosp_result)) {
                                            $hosp_names[] = htmlspecialchars($hosp['name']);
                                        }
                                    }
                                    echo empty($hosp_names) ? 'Select Hospitals' : implode(', ', $hosp_names);
                                    ?>
                                </button>
                                <ul class="dropdown-menu w-100 p-2" aria-labelledby="hospitalDropdown" style="max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                    <?php
                                    $result = mysqli_query($conn, "SELECT * FROM hospital ORDER BY name ASC");
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $checked = in_array($row['id'], $selected_hospitals) ? 'checked' : '';
                                    ?>
                                        <li class="mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input hospital-checkbox" type="checkbox" name="hospital_id[]" value="<?= $row['id'] ?>" id="hosp_<?= $row['id'] ?>" <?= $checked ?>>
                                                <label class="form-check-label" for="hosp_<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></label>
                                            </div>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Degree -->
                        <h4>Degree</h4>
                        <div class="form-group">
                            <label>Degree</label>
                            <div class="dropdown">
                                <button class="form-control dropdown-toggle text-start" type="button" id="degreeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php
                                    $selected_degrees = explode(',', $doctor['degree_id']);
                                    $degree_names = [];
                                    if (!empty($selected_degrees[0])) {
                                        $degree_query = "SELECT name FROM mt_degree WHERE id IN (" . implode(',', $selected_degrees) . ")";
                                        $degree_result = mysqli_query($conn, $degree_query);
                                        while ($degree = mysqli_fetch_assoc($degree_result)) {
                                            $degree_names[] = htmlspecialchars($degree['name']);
                                        }
                                    }
                                    echo empty($degree_names) ? 'Select Degrees' : implode(', ', $degree_names);
                                    ?>
                                </button>
                                <ul class="dropdown-menu w-100 p-2" aria-labelledby="degreeDropdown" style="max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                    <?php
                                    $result = mysqli_query($conn, "SELECT * FROM mt_degree ORDER BY name ASC");
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $checked = in_array($row['id'], $selected_degrees) ? 'checked' : '';
                                    ?>
                                        <li class="mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="degree_id[]" value="<?= $row['id'] ?>" id="deg_<?= $row['id'] ?>" <?= $checked ?>>
                                                <label class="form-check-label" for="deg_<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></label>
                                            </div>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Membership -->
                        <h4>Membership</h4>
                        <div class="form-group">
                            <label>Membership</label>
                            <div class="dropdown">
                                <button class="form-control dropdown-toggle text-start" type="button" id="membershipDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php
                                    $selected_memberships = explode(',', $doctor['membership_id']);
                                    $membership_names = [];
                                    if (!empty($selected_memberships[0])) {
                                        $membership_query = "SELECT name FROM mt_membership WHERE id IN (" . implode(',', $selected_memberships) . ")";
                                        $membership_result = mysqli_query($conn, $membership_query);
                                        while ($membership = mysqli_fetch_assoc($membership_result)) {
                                            $membership_names[] = htmlspecialchars($membership['name']);
                                        }
                                    }
                                    echo empty($membership_names) ? 'Select Memberships' : implode(', ', $membership_names);
                                    ?>
                                </button>
                                <ul class="dropdown-menu w-100 p-2" aria-labelledby="membershipDropdown" style="max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                    <?php
                                    $result = mysqli_query($conn, "SELECT * FROM mt_membership ORDER BY name ASC");
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $checked = in_array($row['id'], $selected_memberships) ? 'checked' : '';
                                    ?>
                                        <li class="mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="membership_id[]" value="<?= $row['id'] ?>" id="mem_<?= $row['id'] ?>" <?= $checked ?>>
                                                <label class="form-check-label" for="mem_<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></label>
                                            </div>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Services -->
                        <h4>Services</h4>
                        <div class="form-group">
                            <label>Service Name</label>
                            <textarea name="service" class="form-control" id="service"><?= htmlspecialchars($doctor['service']) ?></textarea>
                        </div>

                        <!-- Conditions Treated -->
                        <h4>Conditions Treated</h4>
                        <div class="form-group">
                            <label>Conditions Treated</label>
                            <textarea name="c_treated" class="form-control" id="c_treated"><?= htmlspecialchars($doctor['c_treated']) ?></textarea>
                        </div>

                        <!-- Timing -->
                        <h4>Doctor Availability (Timing)</h4>
                       
                        <div id="timingContainer">
                            <?php foreach ($timings as $timing): ?>
                                <?php
                                $hospital_query = "SELECT name FROM hospital WHERE id = '{$timing['hospital_id']}'";
                                $hospital_result = mysqli_query($conn, $hospital_query);
                                $hospital = mysqli_fetch_assoc($hospital_result);
                                $hospital_name = $hospital ? htmlspecialchars($hospital['name']) : '';
                                
                                $day_names = [];
                                $day_ids = explode(',', $timing['day_id']);
                                if (!empty($day_ids[0])) {
                                    $day_query = "SELECT name FROM mt_day WHERE id IN (" . implode(',', $day_ids) . ")";
                                    $day_result = mysqli_query($conn, $day_query);
                                    while ($day = mysqli_fetch_assoc($day_result)) {
                                        $day_names[] = htmlspecialchars($day['name']);
                                    }
                                }
                                ?>
                                <div class="row timing-row" data-hospital-id="<?= $timing['hospital_id'] ?>">
                                    <div class="col-md-3">
                                        <label>Hospital</label>
                                        <input type="text" class="form-control" value="<?= $hospital_name ?>" readonly>
                                        <input type="hidden" name="timing_hospital_id[]" value="<?= $timing['hospital_id'] ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Days</label>
                                        <div class="dropdown">
                                            <button class="form-control dropdown-toggle text-start" type="button" id="daysDropdown_<?= $timing['hospital_id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                <?= empty($day_names) ? 'Select Days' : implode(', ', $day_names) ?>
                                            </button>
                                            <ul class="dropdown-menu w-100 p-2" aria-labelledby="daysDropdown_<?= $timing['hospital_id'] ?>" style="max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                                <?php
                                                $days = mysqli_query($conn, "SELECT * FROM mt_day ORDER BY name ASC");
                                                while ($day = mysqli_fetch_assoc($days)) {
                                                    $checked = in_array($day['id'], $day_ids) ? 'checked' : '';
                                                    echo '<li class="mb-1"><div class="form-check">';
                                                    echo '<input class="form-check-input day-checkbox" type="checkbox" name="timing_day_ids_'.$timing['hospital_id'].'[]" value="'.$day['id'].'" id="day_'.$timing['hospital_id'].'_'.$day['id'].'" '.$checked.'>';
                                                    echo '<label class="form-check-label" for="day_'.$timing['hospital_id'].'_'.$day['id'].'">'.htmlspecialchars($day['name']).'</label>';
                                                    echo '</div></li>';
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                        <div class="selected-days" id="selectedDays_<?= $timing['hospital_id'] ?>"><?= implode(', ', $day_names) ?></div>
                                        <input type="hidden" name="timing_day_ids[]" id="timing_day_ids_<?= $timing['hospital_id'] ?>" value="<?= $timing['day_id'] ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Shift</label>
                                        <select name="timing_shift[]" class="form-control shift-select">
                                            <option value="morning" <?= $timing['shift'] === 'morning' ? 'selected' : '' ?>>Morning</option>
                                            <option value="evening" <?= $timing['shift'] === 'evening' ? 'selected' : '' ?>>Evening</option>
                                            <option value="night" <?= $timing['shift'] === 'night' ? 'selected' : '' ?>>Night</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>From Time</label>
                                        <input type="time" name="timing_from_time[]" class="form-control" value="<?= date('H:i', strtotime($timing['from_time'])) ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label>To Time</label>
                                        <input type="time" name="timing_to_time[]" class="form-control" value="<?= date('H:i', strtotime($timing['to_time'])) ?>">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <span class="remove-timing" onclick="this.parentNode.parentNode.remove()">×</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <button type="button" id="addTimeBtn" class="btn btn-secondary add-time-btn" style="display: none;">Add Time Slot</button>
                        </div>

                        <div class="text-right mt-3">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
<script>
    // Initialize CKEditor
    CKEDITOR.replace('experience');
    CKEDITOR.replace('service');
    CKEDITOR.replace('c_treated');
    CKEDITOR.replace('about');
    
    // Store selected hospitals
    let selectedHospitals = [];
    
    // Function to create a new timing row
    function createTimingRow(hospitalId = '', hospitalName = '', isAdditionalSlot = false) {
        const timingContainer = document.getElementById('timingContainer');
        const rowId = 'timing_' + Date.now();
        const addTimeBtn = document.getElementById('addTimeBtn');
        
        // Create hospital field HTML based on whether this is an additional slot
        let hospitalFieldHtml;
        if (isAdditionalSlot) {
            // For additional slots, show dropdown of selected hospitals
            hospitalFieldHtml = `
                <select name="timing_hospital_id[]" class="form-control hospital-select" required>
                    ${selectedHospitals.map(h => `<option value="${h.id}">${h.name}</option>`).join('')}
                </select>
            `;
        } else {
            // For initial slots, show readonly field
            hospitalFieldHtml = `
                <input type="text" class="form-control" value="${hospitalName}" readonly>
                <input type="hidden" name="timing_hospital_id[]" value="${hospitalId}">
            `;
        }
        
        const row = document.createElement('div');
        row.className = 'row timing-row';
        row.dataset.hospitalId = hospitalId;
        
        row.innerHTML = `
            <div class="col-md-3">
                <label>Hospital</label>
                ${hospitalFieldHtml}
            </div>
            <div class="col-md-2">
                <label>Days</label>
                <div class="dropdown">
                    <button class="form-control dropdown-toggle text-start" type="button" id="daysDropdown_${rowId}" data-bs-toggle="dropdown" aria-expanded="false">
                        Select Days
                    </button>
                    <ul class="dropdown-menu w-100 p-2" aria-labelledby="daysDropdown_${rowId}" style="max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                        <?php
                        $days = mysqli_query($conn, "SELECT * FROM mt_day ORDER BY name ASC");
                        while ($day = mysqli_fetch_assoc($days)) {
                            echo '<li class="mb-1"><div class="form-check">';
                            echo '<input class="form-check-input day-checkbox" type="checkbox" name="timing_day_ids_${rowId}[]" value="'.$day['id'].'" id="day_${rowId}_'.$day['id'].'">';
                            echo '<label class="form-check-label" for="day_${rowId}_'.$day['id'].'">'.htmlspecialchars($day['name']).'</label>';
                            echo '</div></li>';
                        }
                        ?>
                    </ul>
                </div>
                <div class="selected-days" id="selectedDays_${rowId}"></div>
                <input type="hidden" name="timing_day_ids[]" id="timing_day_ids_${rowId}" value="">
            </div>
            <div class="col-md-2">
                <label>Shift</label>
                <select name="timing_shift[]" class="form-control shift-select" onchange="setTimeByShift(this, '${rowId}')">
                    <option value="">Select Shift</option>
                    <option value="morning">Morning</option>
                    <option value="evening">Evening</option>
                    <option value="night">Night</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>From Time</label>
                <input type="time" name="timing_from_time[]" class="form-control from-time" id="from_time_${rowId}">
            </div>
            <div class="col-md-2">
                <label>To Time</label>
                <input type="time" name="timing_to_time[]" class="form-control to-time" id="to_time_${rowId}">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <span class="remove-timing" onclick="this.parentNode.parentNode.remove()">×</span>
            </div>
        `;
        
        timingContainer.appendChild(row);

        // Add event listeners for day checkboxes in this row
        document.querySelectorAll(`input[name="timing_day_ids_${rowId}[]"]`).forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedDays(rowId);
            });
        });
    }

    // Function to set time based on shift selection
    function setTimeByShift(selectElement, rowId) {
        const shift = selectElement.value;
        const fromTimeInput = document.getElementById(`from_time_${rowId}`);
        const toTimeInput = document.getElementById(`to_time_${rowId}`);
        
        switch(shift) {
            case 'morning':
                fromTimeInput.value = '09:00';
                toTimeInput.value = '12:00';
                break;
            case 'evening':
                fromTimeInput.value = '16:00';
                toTimeInput.value = '20:00';
                break;
            case 'night':
                fromTimeInput.value = '21:00';
                toTimeInput.value = '23:00';
                break;
            default:
                fromTimeInput.value = '';
                toTimeInput.value = '';
        }
    }

    // Update selected days display for a row
    function updateSelectedDays(rowId) {
        const selectedDays = Array.from(document.querySelectorAll(`input[name="timing_day_ids_${rowId}[]"]:checked`));
        const dayNames = selectedDays.map(day => day.nextElementSibling.innerText);
        const dayIds = selectedDays.map(day => day.value);
        
        document.getElementById(`selectedDays_${rowId}`).innerText = dayNames.join(', ');
        document.getElementById(`timing_day_ids_${rowId}`).value = dayIds.join(',');
    }

    // Handle hospital selection changes
    document.addEventListener('DOMContentLoaded', function() {
        const hospitalCheckboxes = document.querySelectorAll('.hospital-checkbox');
        const addTimeBtn = document.getElementById('addTimeBtn');
        
        // Initialize existing timing rows (for edit mode)
        document.querySelectorAll('.timing-row').forEach(row => {
            const hospitalId = row.dataset.hospitalId;
            const hospitalName = row.querySelector('input[type="text"]').value;
            
            // Add to selected hospitals array
            selectedHospitals.push({id: hospitalId, name: hospitalName});
            
            // Show add button since we have at least one hospital
            addTimeBtn.style.display = 'block';
            
            // Add event listeners for day checkboxes
            document.querySelectorAll(`input[name^="timing_day_ids_${hospitalId}"]`).forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectedDays(hospitalId);
                });
            });
        });
        
        hospitalCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const hospitalId = this.value;
                const hospitalName = this.nextElementSibling.innerText;
                const timingContainer = document.getElementById('timingContainer');
                
                if (this.checked) {
                    // Add to selected hospitals array
                    selectedHospitals.push({id: hospitalId, name: hospitalName});
                    // Add new row if hospital is selected and doesn't exist
                    const existingRow = timingContainer.querySelector(`.timing-row[data-hospital-id="${hospitalId}"]`);
                    if (!existingRow) {
                        createTimingRow(hospitalId, hospitalName);
                    }
                    // Show add button
                    addTimeBtn.style.display = 'block';
                } else {
                    // Remove from selected hospitals array
                    selectedHospitals = selectedHospitals.filter(h => h.id !== hospitalId);
                    // Remove row if hospital is deselected
                    const rowToRemove = timingContainer.querySelector(`.timing-row[data-hospital-id="${hospitalId}"]`);
                    if (rowToRemove) {
                        rowToRemove.remove();
                    }
                    
                    // Hide add button if no hospitals selected
                    if (selectedHospitals.length === 0) {
                        addTimeBtn.style.display = 'none';
                    }
                }
            });
        });

        // Add time button click handler
        addTimeBtn.addEventListener('click', function() {
            createTimingRow('', '', true);
        });

        // Update dropdown labels
        function updateDropdownLabel(dropdownId, checkboxName) {
            const checkboxes = document.querySelectorAll(`input[name="${checkboxName}[]"]:checked`);
            const labels = Array.from(checkboxes).map(cb => {
                const label = document.querySelector(`label[for="${cb.id}"]`);
                return label ? label.innerText : '';
            });
            const button = document.getElementById(dropdownId);
            button.innerText = labels.length ? labels.join(', ') : 'Select';
        }

        const dropdowns = [
            { id: "specializationDropdown", name: "specialization_id" },
            { id: "hospitalDropdown", name: "hospital_id" },
            { id: "degreeDropdown", name: "degree_id" },
            { id: "membershipDropdown", name: "membership_id" }
        ];

        dropdowns.forEach(drop => {
            const checkboxes = document.querySelectorAll(`input[name="${drop.name}[]"]`);
            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => updateDropdownLabel(drop.id, drop.name));
            });
        });
    });
</script>

</body>
</html>