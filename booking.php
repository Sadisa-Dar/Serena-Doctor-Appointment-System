<?php
include 'conn.php';

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$doctor_id = $_GET['id'] ?? null;
$selected_date = $_GET['date'] ?? date('Y-m-d');

if (!$doctor_id) die("Doctor ID is required.");

// Sanitize inputs
$doctor_id = (int)$doctor_id;
$selected_date = mysqli_real_escape_string($conn, $selected_date);

// Fetch doctor info with prepared statement
$doctor_query = "SELECT * FROM doctor WHERE id = ?";
$stmt = mysqli_prepare($conn, $doctor_query);
mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$doctor_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($doctor_result) == 0) {
    die("Doctor not found.");
}
$doctor = mysqli_fetch_assoc($doctor_result);

// Generate 20-minute time slots
$start = strtotime($doctor['start_time']);
$end = strtotime($doctor['end_time']);
$time_slots = [];

while ($start + 1200 <= $end) {
    $from = date("H:i", $start);
    $to = date("H:i", $start + 1200);
    $time_slots[] = "$from - $to";
    $start += 1200;
}

// Fetch reserved slots for this doctor on selected date
$reserved_slots = [];
$reserved_query = "SELECT time_slot FROM appointment 
                   WHERE doctor_id = ? 
                   AND date = ? 
                   AND status = 'reserved'";
$stmt = mysqli_prepare($conn, $reserved_query);
mysqli_stmt_bind_param($stmt, "is", $doctor_id, $selected_date);
mysqli_stmt_execute($stmt);
$reserved_result = mysqli_stmt_get_result($stmt);

if ($reserved_result) {
    while ($row = mysqli_fetch_assoc($reserved_result)) {
        $reserved_slots[] = date("H:i", strtotime($row['time_slot']));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Book Appointment</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Aboreto&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Aboreto', sans-serif;
      background: linear-gradient(to bottom right, #fffafa, #ffe5e5);
      margin: 0; padding: 0;
    }
    .container {
      max-width: 1000px;
      margin: 30px auto;
      background: white;
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    h2 { text-align: center; color: #d40404; }
    #calendar {
      max-width: 900px;
      margin: 20px auto;
      background-color: white;
      padding: 15px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    .slots {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 20px;
    }
    .slot {
      padding: 15px;
      border-radius: 12px;
      background-color: #ffe5e5;
      text-align: center;
      font-size: 18px;
      transition: 0.3s;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      cursor: pointer;
    }
    .slot:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }
    .reserved {
      background-color: #ccc;
      color: white;
      pointer-events: none;
      cursor: not-allowed;
    }
    a.slot-link { text-decoration: none; color: inherit; display: block; }
    .header {
      background-color: #fffafa;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 90px;
      padding: 0 40px;
      border-bottom: 3px solid #d40404;
    }
    .header img { height: 70px; width: 70px; }
    .title {
      font-size: 38px;
      color: #d40404;
      letter-spacing: 2px;
      margin-left: -995px;
    }
    .nav-links {
      display: flex;
      list-style: none;
      gap: 30px;
    }
    .styled-link {
      text-decoration: none;
      color: black;
      font-size: 20px;
      padding: 8px 16px;
      border-radius: 8px;
      transition: 0.3s;
    }
    .styled-link:hover {
      color: #d40404;
      background-color: #ffe5e5;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <div class="header">
    <img src="logo.png" alt="Logo" style="height: 130px; width: 130px;">
    <p class="title">SERENA</p>
    <ul class="nav-links">
      <li><a href="Home.html" class="styled-link"><i class="fas fa-home"></i> Home</a></li>
      <li><a href="book_doctor.php" class="styled-link"><i class="fas fa-calendar-plus"></i> Book</a></li>
      <li><a href="view_appointments.php" class="styled-link"><i class="fas fa-calendar-check"></i> Appointments</a></li>
    </ul>
  </div>

  <div class="container">
    <h2>Book Appointment with  <?= htmlspecialchars($doctor['name']) ?></h2>

    <!-- Calendar -->
    <div id="calendar"></div>

    <!-- Time Slots -->
    <div class="slots">
      <?php foreach ($time_slots as $slot): 
          [$start_time] = explode(' - ', $slot); // get starting time of slot
          $is_reserved = in_array($start_time, $reserved_slots);
      ?>
        <?php if ($is_reserved): ?>
          <div class='slot reserved'><?= $slot ?></div>
        <?php else: ?>
          <a class='slot-link' href='reserve_slot.php?id=<?= $doctor_id ?>&date=<?= $selected_date ?>&slot=<?= urlencode($slot) ?>'>
            <div class='slot'><?= $slot ?></div>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        height: 'auto',
        selectable: true,
        dateClick: function(info) {
          window.location.href = `?id=<?= $doctor_id ?>&date=${info.dateStr}`;
        }
      });
      calendar.render();
    });
  </script>
</body>
</html>
