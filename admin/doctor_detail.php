
<?php
session_start();

require_once "includes/database.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input fields
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, $_POST['gender']) : '';
    $experience = mysqli_real_escape_string($conn, $_POST['experience']);
    $about = mysqli_real_escape_string($conn, $_POST['about']);
    $service = mysqli_real_escape_string($conn, $_POST['service']);
    $c_treated = mysqli_real_escape_string($conn, $_POST['c_treated']);
    $city_id = mysqli_real_escape_string($conn, $_POST['city_id']);

    // Image upload
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = "uploads/doctors/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
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

    // Insert into doctor_detail table
    $query = "INSERT INTO doctor_detail 
          (name, image, phone, gender, city_id, experience, about, specialization_id, hospital_id, degree_id, membership_id, service, c_treated)
          VALUES 
          ('$name', '$image_name', '$phone', '$gender', '$city_id', '$experience', '$about', '$specialization_ids', '$hospital_ids', '$degree_ids', '$membership_ids', '$service', '$c_treated')";

    if (mysqli_query($conn, $query)) {
        $doctor_id = mysqli_insert_id($conn);

        // Handle timing (da_timing)
       if (!empty($_POST['timing_hospital_id'])) {
    foreach ($_POST['timing_hospital_id'] as $index => $hospital_id) {
        $day_ids = isset($_POST['timing_day_ids'][$index]) ? $_POST['timing_day_ids'][$index] : '';
        $shift = isset($_POST['timing_shift'][$index]) ? mysqli_real_escape_string($conn, $_POST['timing_shift'][$index]) : '';
        $from_time_24 = $_POST['timing_from_time'][$index];
        $to_time_24 = $_POST['timing_to_time'][$index];

        // Convert to 12-hour format with AM/PM
        $from_time = date("g:i A", strtotime($from_time_24));
        $to_time = date("g:i A", strtotime($to_time_24));

        // Sanitize just in case
        $from_time = mysqli_real_escape_string($conn, $from_time);
        $to_time = mysqli_real_escape_string($conn, $to_time);

        $query = "INSERT INTO da_timing 
                  (hospital_id, doctor_id, day_id, shift, from_time, to_time)
                  VALUES 
                  ('$hospital_id', '$doctor_id', '$day_ids', '$shift', '$from_time', '$to_time')";
        
        mysqli_query($conn, $query);
    }
}

        $_SESSION['success'] = "Doctor added successfully.";
        header("Location: doctor_list.php");
        exit;
    } 
}
?>

<!-- HTML FORM REMAINS THE SAME AS YOUR ORIGINAL CODE -->



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
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="left-menu">
        <?php include "left-menu.php"; ?>
    </div>
<form action="" method="POST" enctype="multipart/form-data">
    <div class="page-wrapper">
        <div class="content">
            <div class="card">
                <div class="card-body">
                    <form action="" method="POST">
                        <!-- Doctor Details -->
                        <h4>Doctor Details</h4>
                        <!-- Doctor Image -->
                        <div class="form-group">
                        <label>Doctor Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*" >
                    </div>


                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number<span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <!-- Gender Section -->
                        <div class="form-group">
                            <label>Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-control" required>
                                <option value="" disabled>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>City<span class="text-danger">*</span></label>
                            <select name="city_id" class="form-control" required>
                                <option value="">Select City</option>
                                <?php
                                $cities = mysqli_query($conn, "SELECT id, name FROM mt_city ORDER BY name ASC");
                                while ($city = mysqli_fetch_assoc($cities)) {
                                    echo "<option value=\"{$city['id']}\">" . htmlspecialchars($city['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Experience</label>
                            <textarea name="experience" class="form-control" id="experience"></textarea>
                        </div>

                        <div class="form-group">
                            <label>About</label>
                            <textarea name="about" class="form-control" id="about"></textarea>
                        </div>

                        <!-- Specialization -->
                        <h4>Specialization</h4>
                        <div class="form-group">
                            <label>Specialization<span class="text-danger">*</span></label>
                            <div class="dropdown">
                                <button class="form-control dropdown-toggle text-start" type="button" id="specializationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Select Specializations
                                </button>
                                <ul class="dropdown-menu w-100 p-2" aria-labelledby="specializationDropdown" style="max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                    <?php
                                    $result = mysqli_query($conn, "SELECT * FROM speciality ORDER BY name ASC");
                                    while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                    <li class="mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="specialization_id[]" value="<?= $row['id']; ?>" id="spec_<?= $row['id']; ?>">
                                            <label class="form-check-label" for="spec_<?= $row['id']; ?>"><?= htmlspecialchars($row['name']); ?></label>
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
                                    Select Hospitals
                                </button>
                                <ul class="dropdown-menu w-100 p-2" aria-labelledby="hospitalDropdown" style="max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                    <?php
                                    $result = mysqli_query($conn, "SELECT * FROM hospital ORDER BY name ASC");
                                    while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                        <li class="mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input hospital-checkbox" type="checkbox" name="hospital_id[]" value="<?= $row['id']; ?>" id="hosp_<?= $row['id']; ?>">
                                                <label class="form-check-label" for="hosp_<?= $row['id']; ?>"><?= htmlspecialchars($row['name']); ?></label>
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
                                    Select Degrees
                                </button>
                                <ul class="dropdown-menu w-100 p-2" aria-labelledby="degreeDropdown" style="max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                    <?php
                                    $result = mysqli_query($conn, "SELECT * FROM mt_degree ORDER BY name ASC");
                                    while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                        <li class="mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="degree_id[]" value="<?= $row['id']; ?>" id="deg_<?= $row['id']; ?>">
                                                <label class="form-check-label" for="deg_<?= $row['id']; ?>"><?= htmlspecialchars($row['name']); ?></label>
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
                                    Select Memberships
                                </button>
                                <ul class="dropdown-menu w-100 p-2" aria-labelledby="membershipDropdown" style="max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                    <?php
                                    $result = mysqli_query($conn, "SELECT * FROM mt_membership ORDER BY name ASC");
                                    while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                        <li class="mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="membership_id[]" value="<?= $row['id']; ?>" id="mem_<?= $row['id']; ?>">
                                                <label class="form-check-label" for="mem_<?= $row['id']; ?>"><?= htmlspecialchars($row['name']); ?></label>
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
                            <textarea name="service" class="form-control" id="service"></textarea>
                        </div>


                        <!-- Conditions Treated -->
                        <h4>Conditions Treated</h4>
                        <div class="form-group">
                            <label>Conditions Treated</label>
                            <textarea name="c_treated" class="form-control" id="c_treated"></textarea>
                        </div>


                           <!-- Timing -->
                        <h4>Doctor Availability (Timing)</h4>
                        <div id="timingContainer">
                            <!-- Timing rows will be added here automatically -->
                        </div>
                        
                        <!-- Add Time Button -->
                        <button type="button" id="addTimeBtn" class="btn btn-secondary mb-3">
                            <i class="fas fa-plus"></i> Add Time Slot
                        </button>

                        <div class="text-right mt-3">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</form>
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
    
    // Function to create a new timing row (modified)
    function createTimingRow(hospitalId = '', hospitalName = '', isAdditionalSlot = false) {
        const timingContainer = document.getElementById('timingContainer');
        const rowId = 'timing_' + Date.now();
        const addTimeBtn = document.getElementById('addTimeBtn');
        
        // Hide add button initially
        addTimeBtn.style.display = 'none';
        
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
        <select class="form-control shift-select" name="timing_shift[]" onchange="setTimeByShift(this, '${rowId}')">
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

        // Show add button if at least one hospital is selected
        if (selectedHospitals.length > 0) {
            addTimeBtn.style.display = 'block';
        }

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
            toTimeInput.value = '13:00';
            break;
        case 'evening':
            fromTimeInput.value = '17:00';
            toTimeInput.value = '21:00';
            break;
        case 'night':
            fromTimeInput.value = '21:00';
            toTimeInput.value = '00:00';
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
        
        // Hide add button initially
        addTimeBtn.style.display = 'none';
        
        hospitalCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const hospitalId = this.value;
                const hospitalName = this.nextElementSibling.innerText;
                const timingContainer = document.getElementById('timingContainer');
                
                if (this.checked) {
                    // Add to selected hospitals array
                    selectedHospitals.push({id: hospitalId, name: hospitalName});
                    // Add new row if hospital is selected
                    createTimingRow(hospitalId, hospitalName);
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