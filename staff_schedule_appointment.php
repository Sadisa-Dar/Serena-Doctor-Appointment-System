<?php
session_start();
include 'conn.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = trim($_POST['patient_name']);
    $patient_phone = trim($_POST['patient_phone']);
    $patient_email = trim($_POST['patient_email']);
    $patient_age = intval($_POST['patient_age']);
    $patient_gender = $_POST['patient_gender'];
    $doctor_id = intval($_POST['doctor_id']);
    $booking_date = $_POST['booking_date'];
    $time_slot = $_POST['time_slot'];
    
    // Validate required fields
    if (empty($patient_name) || empty($patient_phone) || empty($doctor_id) || empty($booking_date) || empty($time_slot)) {
        $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">Please fill all required fields.</div>';
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // First, insert or get patient
            $patient_sql = "SELECT id FROM patient WHERE phone = ?";
            $patient_stmt = mysqli_prepare($conn, $patient_sql);
            mysqli_stmt_bind_param($patient_stmt, 's', $patient_phone);
            mysqli_stmt_execute($patient_stmt);
            $patient_result = mysqli_stmt_get_result($patient_stmt);
            
            if ($patient_result && mysqli_num_rows($patient_result) > 0) {
                // Patient exists, get their ID
                $patient_id = mysqli_fetch_assoc($patient_result)['id'];
                
                // Update patient information
                $update_patient_sql = "UPDATE patient SET name = ?, email = ?, age = ?, gender = ? WHERE id = ?";
                $update_patient_stmt = mysqli_prepare($conn, $update_patient_sql);
                mysqli_stmt_bind_param($update_patient_stmt, 'ssisi', $patient_name, $patient_email, $patient_age, $patient_gender, $patient_id);
                mysqli_stmt_execute($update_patient_stmt);
            } else {
                // Insert new patient
                $insert_patient_sql = "INSERT INTO patient (name, phone, email, age, gender) VALUES (?, ?, ?, ?, ?)";
                $insert_patient_stmt = mysqli_prepare($conn, $insert_patient_sql);
                mysqli_stmt_bind_param($insert_patient_stmt, 'sssis', $patient_name, $patient_phone, $patient_email, $patient_age, $patient_gender);
                mysqli_stmt_execute($insert_patient_stmt);
                $patient_id = mysqli_insert_id($conn);
            }
            
            // Check if the time slot is available
            $check_slot_sql = "SELECT id FROM appointment WHERE doctor_id = ? AND date = ? AND time_slot = ? AND status != 'cancelled'";
            $check_slot_stmt = mysqli_prepare($conn, $check_slot_sql);
            mysqli_stmt_bind_param($check_slot_stmt, 'iss', $doctor_id, $booking_date, $time_slot);
            mysqli_stmt_execute($check_slot_stmt);
            $slot_result = mysqli_stmt_get_result($check_slot_stmt);
            
            if ($slot_result && mysqli_num_rows($slot_result) > 0) {
                throw new Exception("❌ APPOINTMENT TIME ALREADY TAKEN! This time slot is already booked by another patient. Please choose a different time.");
            }
            
            // Insert booking
            $booking_sql = "INSERT INTO appointment (doctor_id, patient_id, date, time_slot, status) VALUES (?, ?, ?, ?, 'reserved')";
            $booking_stmt = mysqli_prepare($conn, $booking_sql);
            mysqli_stmt_bind_param($booking_stmt, 'iiss', $doctor_id, $patient_id, $booking_date, $time_slot);
            mysqli_stmt_execute($booking_stmt);
            
            mysqli_commit($conn);
            
            // Redirect to manage appointments page with success message
            $_SESSION['appointment_success'] = "Appointment scheduled successfully!";
            header("Location: manage_appointments_by_staff.php");
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['appointment_error'] = "Error: " . $e->getMessage();
            header("Location: manage_appointments_by_staff.php");
            exit();
        }
    }
}

// Fetch all doctors for dropdown
$doctors_sql = "SELECT id, name, specialization, start_time, end_time FROM doctor ORDER BY name";
$doctors_result = $conn->query($doctors_sql);

// Get available time slots for AJAX
if (isset($_GET['get_available_slots'])) {
    $doctor_id = intval($_GET['doctor_id']);
    $booking_date = $_GET['booking_date'];
    
    // Get doctor's schedule
    $doctor_sql = "SELECT start_time, end_time FROM doctor WHERE id = ?";
    $doctor_stmt = mysqli_prepare($conn, $doctor_sql);
    mysqli_stmt_bind_param($doctor_stmt, 'i', $doctor_id);
    mysqli_stmt_execute($doctor_stmt);
    $doctor_result = mysqli_stmt_get_result($doctor_stmt);
    
    if ($doctor_result && mysqli_num_rows($doctor_result) > 0) {
        $doctor = mysqli_fetch_assoc($doctor_result);
        $start_time = $doctor['start_time'];
        $end_time = $doctor['end_time'];
        
        // Generate 20-minute time slots based on doctor's schedule
        $start = strtotime($start_time);
        $end = strtotime($end_time);
        $time_slots = [];
        $time_slots_display = [];
        
        while ($start + 1200 <= $end) { // 1200 seconds = 20 minutes
            $slot_start = date("H:i", $start);
            $slot_end = date("H:i", $start + 1200);
            
            $time_slots[] = $slot_start; // Store start time for database
            $time_slots_display[$slot_start] = date("g:i A", $start) . " - " . date("g:i A", $start + 1200); // Store display format
            $start += 1200;
        }
        
        // Get booked slots for this doctor and date
        $booked_sql = "SELECT time_slot FROM appointment WHERE doctor_id = ? AND date = ? AND status != 'cancelled'";
        $booked_stmt = mysqli_prepare($conn, $booked_sql);
        mysqli_stmt_bind_param($booked_stmt, 'is', $doctor_id, $booking_date);
        mysqli_stmt_execute($booked_stmt);
        $booked_result = mysqli_stmt_get_result($booked_stmt);
        
        $booked_slots = [];
        if ($booked_result) {
            while ($row = mysqli_fetch_assoc($booked_result)) {
                $booked_slots[] = $row['time_slot'];
            }
        }
        
        // Prepare response with both available and booked slots
        $response = [];
        foreach ($time_slots as $slot) {
            $is_booked = in_array($slot, $booked_slots);
            $response[] = [
                'value' => $slot,
                'display' => $time_slots_display[$slot],
                'booked' => $is_booked,
                'status' => $is_booked ? 'Already Booked' : 'Available'
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        header('Content-Type: application/json');
        echo json_encode([]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff - Schedule New Appointment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Aboreto&family=Roboto&display=swap" rel="stylesheet" />
    <style>
        body {
            margin: 0;
            font-family: 'Aboreto', system-ui;
            background: linear-gradient(to bottom right, #fffafa, #ffe5e5);
        }
        .header {
            background-color: #fffafa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-bottom: 3px solid #d40404;
            height: 90px;
            padding: 0 40px;
        }
        .header img {
            height: 130px;
            width: 130px;
        }
        .title {
            font-size: 38px;
            margin-left: -755px;
            color: #d40404;
            letter-spacing: 2px;
        }
        .nav-links {
            list-style: none;
            display: flex;
            padding: 0;
            margin: 0;
            align-items: center;
        }
        .nav-links li {
            margin-left: 40px;
        }
        .styled-link {
            text-decoration: none;
            color: black;
            font-size: 20px;
            transition: 0.3s;
            font-family: 'Aboreto', system-ui;
            padding: 8px 16px;
            border-radius: 8px;
        }
        .styled-link:hover {
            color: #d40404;
            background-color: #ffe5e5;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        .page-title {
            text-align: center;
            font-size: 36px;
            color: #d40404;
            margin-bottom: 30px;
        }
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .form-section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 24px;
            color: #d40404;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-label {
            font-size: 16px;
            color: #444;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .required {
            color: #d40404;
        }
        .form-input, .form-select {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Aboreto', system-ui;
            transition: border-color 0.3s;
        }
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #d40404;
        }
        .submit-btn {
            background-color: #d40404;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Aboreto', system-ui;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 20px;
        }
        .submit-btn:hover {
            background-color: #a20000;
        }
        .back-btn {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            font-family: 'Aboreto', system-ui;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }
        .back-btn:hover {
            background-color: #545b62;
        }
        .loading {
            color: #666;
            font-style: italic;
        }
        .working-hours {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            color: #495057;
        }
        .working-hours strong {
            color: #d40404;
        }
        .slot-booked {
            color: #dc3545;
            font-style: italic;
        }
        .slot-available {
            color: #28a745;
        }
        .doctor-schedule {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            color: #1976d2;
            display: none;
        }
        .doctor-schedule.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="logo.png" alt="Logo">
        <p class="title">SERENA</p>
        <ul class="nav-links">
            <li><a href="Staff_dashboard.html" class="styled-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage_appointments_by_staff.php" class="styled-link"><i class="fas fa-calendar-check"></i> Appointments</a></li>
            <li><a href="doctor_display.php" class="styled-link"><i class="fas fa-user-md"></i> Doctors</a></li>
            <li><a href="send_notifications.php" class="styled-link"><i class="fas fa-bell"></i> Notifications</a></li>
        </ul>
    </div>

    <div class="container">
        <a href="manage_appointments_by_staff.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Appointments
        </a>
        
        <h1 class="page-title">Schedule New Appointment</h1>
        
        <div class="form-card">
            <div class="working-hours">
                <i class="fas fa-clock"></i>
                <strong>Appointment Slots:</strong> 20-minute intervals based on doctor's schedule
            </div>
            
            <div class="doctor-schedule" id="doctor-schedule">
                <i class="fas fa-user-md"></i>
                <strong>Doctor Schedule:</strong> <span id="schedule-text"></span>
            </div>
            
            <form method="POST">
                <!-- Patient Information Section -->
                <div class="form-section">
                    <h2 class="section-title">Patient Information</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Patient Name <span class="required">*</span></label>
                            <input type="text" name="patient_name" class="form-input" value="<?= htmlspecialchars($_POST['patient_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number <span class="required">*</span></label>
                            <input type="tel" name="patient_phone" class="form-input" value="<?= htmlspecialchars($_POST['patient_phone'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="patient_email" class="form-input" value="<?= htmlspecialchars($_POST['patient_email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Age</label>
                            <input type="number" name="patient_age" class="form-input" value="<?= htmlspecialchars($_POST['patient_age'] ?? '') ?>" min="1" max="120">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="patient_gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="male" <?= ($_POST['patient_gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($_POST['patient_gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other" <?= ($_POST['patient_gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Appointment Information Section -->
                <div class="form-section">
                    <h2 class="section-title">Appointment Details</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Select Doctor <span class="required">*</span></label>
                            <select name="doctor_id" id="doctor_id" class="form-select" required>
                                <option value="">Choose a Doctor</option>
                                <?php if ($doctors_result): while ($doctor = $doctors_result->fetch_assoc()): ?>
                                    <option value="<?= $doctor['id'] ?>" 
                                            data-start="<?= $doctor['start_time'] ?>" 
                                            data-end="<?= $doctor['end_time'] ?>"
                                            <?= ($_POST['doctor_id'] ?? '') == $doctor['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($doctor['name']) ?> 
                                        <?= $doctor['specialization'] ? '(' . htmlspecialchars($doctor['specialization']) . ')' : '' ?>
                                    </option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Appointment Date <span class="required">*</span></label>
                            <input type="date" name="booking_date" id="booking_date" class="form-input" value="<?= htmlspecialchars($_POST['booking_date'] ?? '') ?>" required min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Time Slot <span class="required">*</span></label>
                            <select name="time_slot" id="time_slot" class="form-select" required>
                                <option value="">Select Doctor and Date First</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn" id="submit-btn">
                    <i class="fas fa-calendar-plus"></i> Schedule Appointment
                </button>
            </form>
        </div>
    </div>

    <script>
        // Set minimum date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="booking_date"]').setAttribute('min', today);
        });

        // Form submission handling
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scheduling...';
            submitBtn.disabled = true;
        });

        // Dynamic time slot loading
        document.getElementById('doctor_id').addEventListener('change', updateTimeSlots);
        document.getElementById('booking_date').addEventListener('change', updateTimeSlots);

        function updateTimeSlots() {
            const doctorId = document.getElementById('doctor_id').value;
            const bookingDate = document.getElementById('booking_date').value;
            const timeSlotSelect = document.getElementById('time_slot');
            const doctorSchedule = document.getElementById('doctor-schedule');
            const scheduleText = document.getElementById('schedule-text');

            // Clear current options
            timeSlotSelect.innerHTML = '<option value="">Loading available slots...</option>';

            if (!doctorId || !bookingDate) {
                timeSlotSelect.innerHTML = '<option value="">Select Doctor and Date First</option>';
                doctorSchedule.classList.remove('show');
                return;
            }

            // Show doctor's schedule
            const selectedOption = document.querySelector(`#doctor_id option[value="${doctorId}"]`);
            if (selectedOption) {
                const startTime = selectedOption.getAttribute('data-start');
                const endTime = selectedOption.getAttribute('data-end');
                if (startTime && endTime) {
                    const startDisplay = new Date(`2000-01-01T${startTime}`).toLocaleTimeString('en-US', { 
                        hour: 'numeric', 
                        minute: '2-digit',
                        hour12: true 
                    });
                    const endDisplay = new Date(`2000-01-01T${endTime}`).toLocaleTimeString('en-US', { 
                        hour: 'numeric', 
                        minute: '2-digit',
                        hour12: true 
                    });
                    scheduleText.textContent = `${startDisplay} - ${endDisplay}`;
                    doctorSchedule.classList.add('show');
                }
            }

            // Fetch available time slots
            fetch(`staff_schedule_appointment.php?get_available_slots=1&doctor_id=${doctorId}&booking_date=${bookingDate}`)
                .then(response => response.json())
                .then(slots => {
                    timeSlotSelect.innerHTML = '<option value="">Select Time</option>';
                    
                    if (slots.length === 0) {
                        timeSlotSelect.innerHTML = '<option value="">No available slots for this date</option>';
                    } else {
                        slots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.value;
                            
                            if (slot.booked) {
                                option.textContent = `${slot.display} - ❌ ALREADY BOOKED`;
                                option.disabled = true;
                                option.className = 'slot-booked';
                            } else {
                                option.textContent = `${slot.display} - ✅ AVAILABLE`;
                                option.className = 'slot-available';
                            }
                            
                            timeSlotSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    timeSlotSelect.innerHTML = '<option value="">Error loading slots</option>';
                });
        }
    </script>
</body>
</html>

<?php mysqli_close($conn); ?>
