<?php
// Include database connection
include 'conn.php';

// Get data from URL parameters
$doctor_id = $_GET['id'];        // Doctor ID from URL
$date = $_GET['date'];           // Selected date from URL  
$slot = $_GET['slot'];           // Time slot from URL

// Check if all required data is present
if (empty($doctor_id) || empty($date) || empty($slot)) {
    echo "Error: Missing information. Please go back and try again.";
    exit;
}

// Convert slot format (e.g., "09:00 - 09:20" to "09:00:00")
$start_time = explode(' - ', $slot)[0];  // Get first part before " - "
$start_time_sql = $start_time . ":00";   // Add seconds for database

// Check if this slot is already booked
$check_query = "SELECT id FROM appointment 
                WHERE doctor_id = ? 
                AND date = ? 
                AND time_slot = ? 
                AND status != 'cancelled'";

$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, 'iss', $doctor_id, $date, $start_time_sql);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

// If slot is already taken
if (mysqli_num_rows($check_result) > 0) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; text-align: center; margin: 20px; border: 2px solid #f5c6cb; font-family: Arial, sans-serif; font-size: 18px;'>
            <h2 style='color: #721c24; margin-bottom: 15px;'>❌ APPOINTMENT TIME ALREADY TAKEN!</h2>
            <p style='margin-bottom: 15px;'><strong>This time slot is already booked by another patient.</strong></p>
            <p style='margin-bottom: 20px;'>Please go back and choose a different time.</p>
            <a href='javascript:history.back()' style='background-color: #721c24; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>← Go Back</a>
          </div>";
    exit;
}

// Instead of creating appointment immediately, redirect to patient form with booking details
// We'll create the appointment after getting patient information
header("Location: patient_info.php?doctor_id=$doctor_id&date=$date&slot=" . urlencode($slot));
exit;
?>
