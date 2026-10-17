<?php
session_start();
include 'conn.php';

$success_message = '';
$error_message = '';

// Debug: Check if staff table has records
$staff_count = $conn->query("SELECT COUNT(*) as count FROM staff")->fetch_assoc()['count'];
if ($staff_count == 0) {
    $error_message = "No staff members found in the system. Please add staff first before sending notifications.";
}

// Add notification_sent and notification_type columns if not exist (manual migration may be needed)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $notification_type = $_POST['notification_type'];
    $message = $_POST['message'];
    $staff_name = isset($_SESSION['staff_name']) ? $_SESSION['staff_name'] : 'Unknown';
    $notification_time = date('Y-m-d H:i:s');
    
    // Find the latest booking for this patient
    $sql = "SELECT id FROM appointment WHERE patient_id = ? AND date >= CURDATE() AND status = 'reserved' ORDER BY date, time_slot LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $stmt->bind_result($appointment_id);
    $stmt->fetch();
    $stmt->close();
    
    if ($appointment_id) {
        // Insert notification into notification table
        $sql2 = "INSERT INTO notification (appointment_id, staff_id, patient_id, type, msg, time, date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql2);
        
        // Get a valid staff ID - first try session, then get first available staff
        $staff_id = null;
        if (isset($_SESSION['staff_id']) && !empty($_SESSION['staff_id'])) {
            $staff_id = $_SESSION['staff_id'];
        } else {
            // Get the first available staff ID
            $staff_check = $conn->query("SELECT id FROM staff LIMIT 1");
            if ($staff_check && $staff_check->num_rows > 0) {
                $staff_id = $staff_check->fetch_assoc()['id'];
            } else {
                $error_message = "No staff members found in the system. Please add staff first.";
            }
        }
        
        $n_time = date('H:i:s');
        $n_date = date('Y-m-d');
        $stmt2->bind_param('iiissss', $appointment_id, $staff_id, $patient_id, $notification_type, $message, $n_time, $n_date);
        
        if ($stmt2->execute()) {
            $success_message = "Notification sent successfully!";
        } else {
            $error_message = "Error sending notification: " . $stmt2->error;
        }
        $stmt2->close();
    } else {
        $error_message = "No active booking found for this patient.";
    }
}

// Fetch all upcoming appointments with patient info
$sql = "SELECT b.id, b.date as booking_date, b.time_slot, d.name AS doctor_name, p.id as patient_id, p.name AS patient_name, p.email, p.phone FROM appointment b JOIN doctor d ON b.doctor_id = d.id LEFT JOIN patient p ON b.patient_id = p.id WHERE b.date >= CURDATE() AND b.status = 'reserved' ORDER BY b.date, b.time_slot";
$result = $conn->query($sql);
$patients = [];
while ($row = $result->fetch_assoc()) {
    if ($row['patient_id']) {
        $patients[$row['patient_id']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Send Notifications</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Aboreto&family=Roboto&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: "Aboreto", sans-serif;
      background: linear-gradient(to bottom right, #fffafa, #ffe5e5);
      margin: 0;
      padding: 0;
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
      position: relative;
      z-index: 1;
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
      max-width: 1200px;
      margin: 40px auto;
      padding: 20px;
    }
    .dashboard-title {
      text-align: center;
      font-size: 36px;
      color: #d40404;
      margin-bottom: 30px;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 18px;
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    label {
      font-size: 18px;
      color: #d40404;
      margin-bottom: 4px;
      font-weight: bold;
    }
    input, select, textarea {
      font-size: 16px;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #ddd;
      font-family: 'Aboreto', system-ui;
      background: #fff;
      transition: border-color 0.3s;
    }
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #d40404;
    }
    textarea {
      min-height: 100px;
      resize: vertical;
    }
    .send-btn {
      background-color: #d40404;
      color: white;
      border: none;
      border-radius: 8px;
      padding: 15px 0;
      font-size: 18px;
      font-family: 'Aboreto', system-ui;
      cursor: pointer;
      margin-top: 10px;
      transition: background 0.2s;
    }
    .send-btn:hover {
      background-color: #a20000;
    }
    .success-message {
      color: green;
      font-weight: bold;
      margin-top: 10px;
      text-align: center;
      padding: 10px;
      background-color: #d4edda;
      border-radius: 8px;
      border: 1px solid #c3e6cb;
    }
    .error-message {
      color: #721c24;
      font-weight: bold;
      margin-top: 10px;
      text-align: center;
      padding: 10px;
      background-color: #f8d7da;
      border-radius: 8px;
      border: 1px solid #f5c6cb;
    }
    @media (max-width: 600px) {
      .title { font-size: 30px; }
      .dashboard-title { font-size: 28px; }
      .container { padding: 15px; }
    }
  </style>
  <script>
    const patients = <?php echo json_encode($patients); ?>;
    function fillPatientData() {
      const select = document.getElementById('patient_select');
      const pid = select.value;
      const data = patients[pid];
      if (data) {
        document.getElementById('email').value = data.email || '';
        document.getElementById('phone').value = data.phone || '';
        document.getElementById('doctor').value = data.doctor_name || '';
        document.getElementById('date').value = data.booking_date || '';
        document.getElementById('time').value = data.time_slot || '';
      } else {
        document.getElementById('email').value = '';
        document.getElementById('phone').value = '';
        document.getElementById('doctor').value = '';
        document.getElementById('date').value = '';
        document.getElementById('time').value = '';
      }
    }
  </script>
</head>
<body>
          <div class="header">
          <img src="logo.png" alt="Logo" style="height: 130px; width: 130px;">
          <p class="title">SERENA</p>
        <ul class="nav-links">
            <li><a href="Staff_dashboard.html" class="styled-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage_appointments_by_staff.php" class="styled-link"><i class="fas fa-calendar-check"></i> Appointments</a></li>
            <li><a href="doctor_display.php" class="styled-link"><i class="fas fa-user-md"></i> Doctors</a></li>
            <li><a href="staff_notification.php" class="styled-link"><i class="fas fa-bell"></i> Notifications</a></li>
        </ul>
</div>
  <h2 class="dashboard-title">Send Appointment Notification</h2>
  <div class="container">
    <?php if ($success_message): ?>
      <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
      <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <form method="post">
      <label for="patient_select">Select Patient</label>
      <select id="patient_select" name="patient_id" onchange="fillPatientData()" required>
        <option value="">-- Select Patient --</option>
        <?php foreach ($patients as $pid => $pdata): ?>
          <option value="<?= $pid ?>"><?= htmlspecialchars($pdata['patient_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <label for="notification_type">Notification Type</label>
      <select id="notification_type" name="notification_type" required>
        <option value="reminder">Reminder</option>
        <option value="followup">Follow-up</option>
      </select>
      <label for="email">Email</label>
      <input type="email" id="email" name="email" readonly>
      <label for="phone">Phone</label>
      <input type="text" id="phone" name="phone" readonly>
      <label for="doctor">Doctor</label>
      <input type="text" id="doctor" name="doctor" readonly>
      <label for="date">Date</label>
      <input type="text" id="date" name="date" readonly>
      <label for="time">Time</label>
      <input type="text" id="time" name="time" readonly>
      <label for="message">Message</label>
      <textarea id="message" name="message" placeholder="Type your message to the patient here..." required></textarea>
      <button type="submit" class="send-btn"><i class="fas fa-paper-plane"></i> Send Notification</button>
    </form>
  </div>
</body>
</html>
<?php $conn->close(); ?>