<?php
// Connect to the database
include 'conn.php';

// Get patient details and booking info from the form
$doctor_id = $_POST['doctor_id'];
$date = $_POST['date'];
$slot = $_POST['slot'];
$name = $_POST['name'];
$email = $_POST['email'];
$gender = $_POST['gender'];
$age = $_POST['age'];
$phone = $_POST['phone'];

// Check if all required fields are filled
if (empty($doctor_id) || empty($date) || empty($slot) || empty($name) || empty($gender) || empty($age) || empty($phone)) {
    echo "Please fill all required fields.";
    exit;
}

// Convert slot format (e.g., "09:00 - 09:20" to "09:00:00")
$start_time = explode(' - ', $slot)[0];  // Get first part before " - "
$start_time_sql = $start_time . ":00";   // Add seconds for database

// Check if this slot is still available
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

// Insert patient info into the patient table first
$insert_patient = "INSERT INTO patient (name, email, gender, age, phone) VALUES (?, ?, ?, ?, ?)";
$patient_stmt = mysqli_prepare($conn, $insert_patient);
mysqli_stmt_bind_param($patient_stmt, 'sssis', $name, $email, $gender, $age, $phone);

if (mysqli_stmt_execute($patient_stmt)) {
    // Get the new patient's ID
    $patient_id = mysqli_insert_id($conn);

    // Now create the appointment with the patient_id
    $insert_appointment = "INSERT INTO appointment (doctor_id, patient_id, date, time_slot, status) VALUES (?, ?, ?, ?, 'reserved')";
    $appointment_stmt = mysqli_prepare($conn, $insert_appointment);
    mysqli_stmt_bind_param($appointment_stmt, 'iiss', $doctor_id, $patient_id, $date, $start_time_sql);
    
    if (mysqli_stmt_execute($appointment_stmt)) {
        header('Location: book_doctor.php?msg=success');
        exit;
    } else {
        echo "Error creating appointment: " . mysqli_error($conn);
    }
} else {
    echo "Error saving patient: " . mysqli_error($conn);
}
?>