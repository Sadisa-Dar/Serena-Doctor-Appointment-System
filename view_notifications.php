<?php
session_start();
include 'conn.php';

// Query to fetch patient name, staff name and notifications
$sql = "SELECT 
            p.name AS patient_name, 
            n.notification_msg, 
            n.notification_type,
            n.n_time, 
            n.n_date,
            s.name as staff_name,
            d.name as doctor_name
        FROM notification n
        JOIN patient p ON n.patient_id = p.id
        LEFT JOIN staff s ON n.staff_id = s.id
        LEFT JOIN appointment b ON n.appointment_id = b.id
        LEFT JOIN doctor d ON b.doctor_id = d.id
        ORDER BY n.n_date DESC, n.n_time DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>View Notifications</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Aboreto&family=Roboto&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
      height: 70px;
      width: 70px;
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

        .nav-button {
            margin-left: 20px;
            padding: 8px 20px;
            font-size: 16px;
            font-weight: bold;
            background: linear-gradient(45deg, #d40404, #e34234);
            color: white;
            cursor: pointer;
            transition: 0.3s;
            border-radius: 15px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
            border: none;
            font-family: 'Roboto', sans-serif;
            text-decoration: none;
        }

        .nav-button:hover {
            background-color: #a00000;
            padding: 10px 28px;
        }

    .notification-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 30px;
    }

    .notification-card {
      background-color: white;
      width: 60%;
      border-left: 8px solid #d40404;
      border-radius: 12px;
      box-shadow: 0 0 8px #d40404;
      margin-bottom: 20px;
      padding: 20px;
      font-family: 'Roboto', sans-serif;
      transition: 0.3s;
    }

    .notification-card:hover {
      transform: scale(1.02);
      box-shadow: 0 0 12px #e34234;
    }

    .notification-title {
      font-size: 22px;
      color: #d40404;
      margin-bottom: 10px;
      font-weight: bold;
    }

    .notification-type {
      display: inline-block;
      background: #d40404;
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: bold;
      text-transform: uppercase;
      margin-bottom: 10px;
    }

    .notification-details {
      font-size: 16px;
      color: #444;
      line-height: 1.6;
      margin-bottom: 10px;
    }

    .notification-meta {
      font-size: 14px;
      color: #777;
      margin-top: 10px;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .page-title {
      text-align: center;
      font-size: 36px;
      color: #d40404;
      margin: 20px 0 30px 0;
      font-weight: bold;
    }

    @media (max-width: 768px) {
      .notification-card {
        width: 90%;
      }
      .title {
        margin-left: 0;
        font-size: 30px;
      }
      .header {
        flex-direction: column;
        gap: 10px;
        height: auto;
        padding: 20px;
      }
      .notification-meta {
        flex-direction: column;
      }
    }
  </style>
</head>

<body>
  <!-- Header -->
  <div class="header">
    <img src="logo.png" alt="Logo">
    <p class="title">SERENA</p>
    <ul class="nav-links">
      <li><a href="Staff_dashboard.html" class="styled-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="manage_appointments_by_staff.php" class="styled-link"><i class="fas fa-calendar-check"></i> Appointments</a></li>
      <li><a href="doctor_display.php" class="styled-link"><i class="fas fa-user-md"></i> Doctors</a></li>
      <li><a href="view_notifications.php" class="styled-link"><i class="fas fa-bell"></i> Notifications</a></li>
      <li><a href="staff_logout.php" class="nav-button"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </div>

  <h1 class="page-title">All Notifications</h1>

  <!-- Notification Section -->
  <div class="notification-container">
    <?php
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) { ?>
          <div class="notification-card">
            <div class="notification-title">
              <i class="fas fa-user"></i> Patient: <?php echo htmlspecialchars($row['patient_name']); ?>
            </div>
            <div class="notification-type">
              <i class="fas fa-tag"></i> <?php echo ucfirst($row['notification_type']); ?>
            </div>
            <div class="notification-details">
              <i class="fas fa-comment"></i> <?php echo htmlspecialchars($row['notification_msg']); ?>
            </div>
            <div class="notification-meta">
              <div class="meta-item">
                <i class="fas fa-user-md"></i>
                <span>Sent by: <?php echo htmlspecialchars($row['staff_name'] ?? 'Staff'); ?></span>
              </div>
              <?php if ($row['doctor_name']): ?>
              <div class="meta-item">
                <i class="fas fa-stethoscope"></i>
                <span>Doctor: <?php echo htmlspecialchars($row['doctor_name']); ?></span>
              </div>
              <?php endif; ?>
              <div class="meta-item">
                <i class="fas fa-calendar"></i>
                <span>Date: <?php echo $row['n_date']; ?></span>
              </div>
              <div class="meta-item">
                <i class="fas fa-clock"></i>
                <span>Time: <?php echo $row['n_time']; ?></span>
              </div>
            </div>
          </div>
    <?php }
    } else {
        echo "<div style='text-align:center; font-size: 20px; color: #666; padding: 40px;'>No notifications found.</div>";
    }
    $conn->close();
    ?>
  </div>
</body>
</html>
