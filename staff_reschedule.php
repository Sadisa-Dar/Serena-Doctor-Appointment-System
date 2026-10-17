<?php
session_start();
include 'conn.php';

$message = '';
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$appointment_id) {
    header('Location: manage_appointments_by_staff.php');
    exit;
}

// Fetch appointment details
$appointment_sql = "SELECT b.*, d.name AS doctor_name, d.specialization, d.start_time, d.end_time, p.name AS patient_name 
                    FROM appointment b 
                    LEFT JOIN doctor d ON b.doctor_id = d.id 
                    LEFT JOIN patient p ON b.patient_id = p.id 
                    WHERE b.id = ?";
$appointment_stmt = $conn->prepare($appointment_sql);
$appointment_stmt->bind_param('i', $appointment_id);
$appointment_stmt->execute();
$appointment_result = $appointment_stmt->get_result();

if ($appointment_result->num_rows === 0) {
    header('Location: manage_appointments_by_staff.php');
    exit;
}

if ($appointment_result && $appointment_result->num_rows > 0) {
    $appointment = $appointment_result->fetch_assoc();
} else {
    $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">Appointment not found.</div>';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_date = $_POST['booking_date'];
    $new_time_slot = $_POST['time_slot'];
    
    // Validate required fields
    if (empty($new_date) || empty($new_time_slot)) {
        $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">Please fill all required fields.</div>';
    } else {
        // Check if the new time slot is available
        $check_slot_sql = "SELECT id FROM appointment WHERE doctor_id = ? AND date = ? AND time_slot = ? AND status != 'cancelled' AND id != ?";
        $check_slot_stmt = $conn->prepare($check_slot_sql);
        $check_slot_stmt->bind_param('issi', $appointment['doctor_id'], $new_date, $new_time_slot, $appointment_id);
        $check_slot_stmt->execute();
        $slot_result = $check_slot_stmt->get_result();
        
        if ($slot_result && $slot_result->num_rows > 0) {
            $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">This time slot is already booked. Please choose another time.</div>';
        } else {
            // Update appointment
            $update_sql = "UPDATE appointment SET date = ?, time_slot = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('ssi', $new_date, $new_time_slot, $appointment_id);
            
            if ($update_stmt->execute()) {
                $message = '<div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">Appointment rescheduled successfully!</div>';
                // Redirect after 2 seconds
                echo '<script>setTimeout(function(){ window.location.href = "manage_appointments_by_staff.php"; }, 2000);</script>';
            } else {
                $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">Error rescheduling appointment: ' . $conn->error . '</div>';
            }
        }
    }
}

// Generate time slots for the selected date
$selected_date = $_POST['booking_date'] ?? $appointment['date'] ?? $appointment['booking_date'];
$time_slots = [];

if ($appointment['start_time'] && $appointment['end_time']) {
    $start = strtotime($appointment['start_time']);
    $end = strtotime($appointment['end_time']);
    
    while ($start + 1200 <= $end) { // 1200 seconds = 20 minutes
        $slot_start = date("H:i", $start);
        $slot_end = date("H:i", $start + 1200);
        
        $time_slots[] = [
            'value' => $slot_start,
            'display' => date("g:i A", $start) . " - " . date("g:i A", $start + 1200)
        ];
        $start += 1200;
    }
}

// Get booked slots for the selected date
$booked_slots = [];
if ($selected_date) {
    $booked_sql = "SELECT time_slot FROM appointment WHERE doctor_id = ? AND date = ? AND status != 'cancelled' AND id != ?";
    $booked_stmt = $conn->prepare($booked_sql);
    $booked_stmt->bind_param('isi', $appointment['doctor_id'], $selected_date, $appointment_id);
    $booked_stmt->execute();
    $booked_result = $booked_stmt->get_result();
    
    if ($booked_result) {
        while ($row = $booked_result->fetch_assoc()) {
            $booked_slots[] = $row['time_slot'];
        }
    }
}

// Filter out booked slots from available time slots
$available_slots = [];
foreach ($time_slots as $slot) {
    if (!in_array($slot['value'], $booked_slots)) {
        $available_slots[] = $slot;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff - Reschedule Appointment</title>
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
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-subtitle {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            font-family: 'Roboto', sans-serif;
        }
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 2px solid #f0f0f0;
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
            background: linear-gradient(45deg, #d40404, #e34234);
            color: white;
            padding: 18px 35px;
            border: none;
            border-radius: 12px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Roboto', sans-serif;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 25px;
            box-shadow: 0 4px 15px rgba(212, 4, 4, 0.3);
        }
        .submit-btn:hover {
            background: linear-gradient(45deg, #a20000, #c62828);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 4, 4, 0.4);
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
        .current-appointment {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border: 2px solid #2196f3;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.1);
        }
        .current-appointment h3 {
            color: #1976d2;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .appointment-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .appointment-detail:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .detail-value {
            color: #333;
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
        .doctor-schedule {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            color: #1976d2;
        }
        .slot-booked {
            color: #dc3545;
            font-style: italic;
        }
        .slot-available {
            color: #28a745;
        }
        
        .instructions-section {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.1);
        }
        
        .instructions-section h3 {
            color: #856404;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .instructions-section ol {
            margin: 0;
            padding-left: 20px;
            color: #856404;
        }
        
        .instructions-section li {
            margin-bottom: 8px;
            font-family: 'Roboto', sans-serif;
        }
        
        .instructions-section p {
            margin-top: 15px;
            margin-bottom: 0;
            color: #856404;
            font-family: 'Roboto', sans-serif;
            font-style: italic;
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
            <li><a href="staff_notification.php" class="styled-link"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="staff_logout.php" class="nav-button"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="container">
        <a href="manage_appointments_by_staff.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Appointments
        </a>
        
        <h1 class="page-title">Reschedule Appointment</h1>
        <p class="page-subtitle">Change the date and time of an existing appointment</p>
        
        <?php echo $message; ?>
        
        <div class="form-card">
            <!-- Current Appointment Details -->
            <div class="current-appointment">
                <h3><i class="fas fa-info-circle"></i> Current Appointment Details</h3>
                <div class="appointment-detail">
                    <span class="detail-label">Patient:</span>
                    <span class="detail-value"><?= htmlspecialchars($appointment['patient_name'] ?? 'Not Assigned') ?></span>
                </div>
                <div class="appointment-detail">
                    <span class="detail-label">Doctor:</span>
                    <span class="detail-value"><?= htmlspecialchars($appointment['doctor_name'] ?? 'Not Assigned') ?></span>
                </div>
                <div class="appointment-detail">
                    <span class="detail-label">Current Date:</span>
                    <span class="detail-value"><?= $appointment['date'] ?? $appointment['booking_date'] ?? 'Not Available' ?></span>
                </div>
                <div class="appointment-detail">
                    <span class="detail-label">Current Time:</span>
                    <span class="detail-value"><?= $appointment['time_slot'] ?></span>
                </div>
                <div class="appointment-detail">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value"><?= ucfirst($appointment['status']) ?></span>
                </div>
            </div>
            
            <div class="working-hours">
                <i class="fas fa-clock"></i>
                <strong>Appointment Slots:</strong> 20-minute intervals based on doctor's schedule
            </div>
            
            <div class="doctor-schedule">
                <i class="fas fa-user-md"></i>
                <strong>Doctor Schedule:</strong> 
                <?php 
                if ($appointment['start_time'] && $appointment['end_time']) {
                    $start_display = date('g:i A', strtotime($appointment['start_time']));
                    $end_display = date('g:i A', strtotime($appointment['end_time']));
                    echo $start_display . ' - ' . $end_display;
                } else {
                    echo 'Schedule not available';
                }
                ?>
            </div>
            
                         <!-- Instructions Section -->
             <div class="instructions-section">
                 <h3><i class="fas fa-lightbulb"></i> How to Reschedule:</h3>
                 <ol>
                     <li>Select a new date (must be today or later)</li>
                     <li>Choose an available time slot from the dropdown</li>
                     <li>Click "Reschedule Appointment" to save changes</li>
                 </ol>
                 <p><strong>Note:</strong> Only truly available time slots will be shown. Slots already booked by other patients are automatically excluded.</p>
             </div>
            
            <form method="POST">
                <div class="form-section">
                    <h2 class="section-title">New Appointment Details</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">New Date <span class="required">*</span></label>
                            <input type="date" name="booking_date" id="booking_date" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['booking_date'] ?? $appointment['date'] ?? $appointment['booking_date']) ?>" 
                                   required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Time Slot <span class="required">*</span></label>
                            <select name="time_slot" id="time_slot" class="form-select" required>
                                <option value="">Select Time Slot</option>
                                <?php if (empty($available_slots)): ?>
                                    <option value="" disabled>No available slots for selected date</option>
                                <?php else: ?>
                                    <?php foreach ($available_slots as $slot): ?>
                                        <option value="<?= $slot['value'] ?>" <?= ($_POST['time_slot'] ?? '') == $slot['value'] ? 'selected' : '' ?>>
                                            <?= $slot['display'] ?> - Available
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-edit"></i> Reschedule Appointment
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

        // Simple date change handler - reload page to get new time slots
        document.getElementById('booking_date').addEventListener('change', function() {
            const newDate = this.value;
            if (newDate) {
                // Create a form and submit to reload with new date
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'booking_date';
                dateInput.value = newDate;
                
                form.appendChild(dateInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
