<?php
include 'conn.php';

$sql = "SELECT 
            a.id,
            a.doctor_id,
            d.name AS doctor_name,
            p.name AS patient_name,
            a.date as booking_date,
            a.time_slot,
            a.status,
            p.id as patient_id
        FROM appointment a
        JOIN doctor d ON a.doctor_id = d.id
        LEFT JOIN patient p ON a.patient_id = p.id
        ORDER BY a.date DESC";

$result = $conn->query($sql);

// Fetch notifications for each patient
$notifications = [];
$notification_sql = "SELECT 
                        n.patient_id,
                        n.type as notification_type,
                        n.msg as notification_msg,
                        n.time as n_time,
                        n.date as n_date,
                        s.name as staff_name
                     FROM notification n
                     LEFT JOIN staff s ON n.staff_id = s.id
                     ORDER BY n.date DESC, n.time DESC";
$notification_result = $conn->query($notification_sql);

if ($notification_result) {
    while ($row = $notification_result->fetch_assoc()) {
        $patient_id = $row['patient_id'];
        if (!isset($notifications[$patient_id])) {
            $notifications[$patient_id] = [];
        }
        $notifications[$patient_id][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointments</title>
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
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }
        .appointment-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.2s ease-in-out;
        }
        .appointment-card:hover {
            transform: translateY(-3px);
        }
        .appointment-card h3 {
            margin: 0 0 10px;
            color: #d40404;
            font-weight: bold;
            text-align: center;
        }
        .appointment-info {
            font-size: 18px;
            margin: 5px 0;
            color: #444;
        }
        .status {
            font-weight: bold;
            color: green;
        }
        .actions {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn, .notification-btn {
            padding: 8px 22px;
            border: none;
            border-radius: 999px;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Aboreto', system-ui;
            font-size: 16px;
            transition: background 0.2s, box-shadow 0.2s, color 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            outline: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-reschedule {
            background-color: #ffa726;
            color: white;
        }
        .btn-reschedule:hover {
            background-color: #e59400;
        }
        .btn-cancel {
            background-color: #e53935;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #b71c1c;
        }
        .notification-btn {
            background: #1976d2;
            color: white;
            border: none;
            border-radius: 999px;
            padding: 8px 28px 8px 18px;
            font-size: 16px;
            font-family: 'Aboreto', system-ui;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.10);
            position: relative;
            transition: background 0.2s, box-shadow 0.2s;
            outline: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .notification-btn:hover {
            background: #1251a3;
            box-shadow: 0 4px 16px rgba(25, 118, 210, 0.18);
        }
        .notification-dot {
            position: absolute;
            top: 4px;
            right: 8px;
            width: 13px;
            height: 13px;
            background: #d40404;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 4px #d40404aa;
            z-index: 2;
            cursor: pointer;
        }
        .notification-dot::after {
            content: 'Notification sent';
            position: absolute;
            top: -32px;
            right: -30px;
            background: #222;
            color: #fff;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 6px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            white-space: nowrap;
        }
        .notification-dot:hover::after {
            opacity: 1;
        }
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #d40404;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
    <script>
        function cancelAppointment(id) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                fetch('cancel_appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + id
                })
                .then(res => res.text())
                .then(response => {
                    if (response.trim() === 'success') {
                        alert('Appointment cancelled successfully.');
                        location.reload();
                    } else {
                        alert('Error cancelling appointment.');
                    }
                });
            }
        }

        function showNotifications(patientId) {
            const notifications = <?php echo json_encode($notifications); ?>;
            const patientNotifications = notifications[patientId] || [];
            
            let modalContent = '';
            if (patientNotifications.length > 0) {
                modalContent = '<div style="max-height: 400px; overflow-y: auto;">';
                patientNotifications.forEach(notification => {
                    modalContent += `
                        <div style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 10px; background: #f9f9f9;">
                            <div style="font-weight: bold; color: #d40404; margin-bottom: 5px;">
                                ${notification.notification_type.charAt(0).toUpperCase() + notification.notification_type.slice(1)}
                            </div>
                            <div style="margin-bottom: 5px;">${notification.notification_msg}</div>
                            <div style="font-size: 12px; color: #666;">
                                Sent by: ${notification.staff_name || 'Staff'} | 
                                Date: ${notification.n_date} | 
                                Time: ${notification.n_time}
                            </div>
                        </div>
                    `;
                });
                modalContent += '</div>';
            } else {
                modalContent = '<div style="text-align: center; color: #666; padding: 20px;">No notifications found for this patient.</div>';
            }
            
            document.getElementById('modalBody').innerHTML = modalContent;
            document.getElementById('notificationModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('notificationModal').style.display = 'none';
            document.body.style.overflow = '';
        }
    </script>
</head>
<body>
<div class="header">
  <a href="Home.html" style="display:flex;align-items:center;text-decoration:none;">
    <img src="logo.png" alt="Logo">
    <p class="title" style="margin-left:10px;">SERENA</p>
  </a>
  <ul class="nav-links">
    <li><a href="Home.html" class="styled-link"><i class="fas fa-home"></i> Home</a></li>
    <li><a href="book_doctor.php" class="styled-link"><i class="fas fa-calendar-plus"></i> Book</a></li>
    <li><a href="view_appointments.php" class="styled-link"><i class="fas fa-calendar-check"></i> Appointments</a></li>
  </ul>
</div>

<div class="container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <center>
                <div class="appointment-card">
                    <h3>Appointment <?= $row['id'] ?></h3>
                    <div class="appointment-info">👨‍⚕️ <strong>Doctor:</strong> <?= htmlspecialchars($row['doctor_name']) ?> (ID: <?= $row['doctor_id'] ?>)</div>
                    <div class="appointment-info">🧑‍🤝‍🧑 <strong>Patient:</strong> <?= $row['patient_name'] ? htmlspecialchars($row['patient_name']) : 'Not Assigned' ?></div>
                    <div class="appointment-info">📅 <strong>Date:</strong> <?= $row['booking_date'] ?></div>
                    <div class="appointment-info">⏰ <strong>Time:</strong> <?= $row['time_slot'] ?></div>
                    <div class="appointment-info">📌 <strong>Status:</strong> <span class="status"><?= ucfirst($row['status']) ?></span></div>
                    <div class="actions">
                        <a href="reschedule.php?id=<?= $row['id'] ?>">
                            <button class="btn btn-reschedule"><i class="fas fa-edit"></i> Reschedule</button>
                        </a>
                        <button onclick="cancelAppointment(<?= $row['id'] ?>)" class="btn btn-cancel"><i class="fas fa-times"></i> Cancel</button>
                        <?php if ($row['patient_id'] && isset($notifications[$row['patient_id']])): ?>
                            <button onclick="showNotifications(<?= $row['patient_id'] ?>)" class="notification-btn" style="position: relative;">
                                <i class="fas fa-bell"></i> Notifications
                                <span class="notification-count"><?= count($notifications[$row['patient_id']]) ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </center>
        <?php endwhile; ?>
    <?php elseif ($result === false): ?>
        <div class="no-appointments">
            <i class="fas fa-exclamation-triangle"></i> Database error: <?= htmlspecialchars($conn->error) ?>
        </div>
    <?php else: ?>
        <div class="no-appointments">
            <i class="fas fa-calendar-times"></i> No appointments found.
        </div>
    <?php endif; ?>
</div>

<!-- Notification Modal -->
<div id="notificationModal" class="modal-overlay" style="display:none;">
  <div class="modal-content">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <div id="modalBody"></div>
  </div>
</div>
<style>
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(30,30,30,0.45);
  backdrop-filter: blur(3px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s;
}
.modal-content {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
  padding: 36px 32px 28px 32px;
  min-width: 340px;
  max-width: 95vw;
  position: relative;
  animation: modalFadeIn 0.3s;
}
@keyframes modalFadeIn {
  from { transform: translateY(40px) scale(0.98); opacity: 0; }
  to { transform: translateY(0) scale(1); opacity: 1; }
}
.close-modal {
  position: absolute;
  top: 12px;
  right: 18px;
  font-size: 28px;
  color: #d40404;
  cursor: pointer;
  font-weight: bold;
  transition: color 0.2s;
}
.close-modal:hover { color: #a20000; }
#modalBody {
  font-family: 'Aboreto', system-ui;
  color: #222;
  font-size: 18px;
  min-height: 80px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
#modalBody .modal-label {
  color: #d40404;
  font-weight: bold;
  margin-bottom: 2px;
}
</style>
</body>
</html>

<?php $conn->close(); ?>
