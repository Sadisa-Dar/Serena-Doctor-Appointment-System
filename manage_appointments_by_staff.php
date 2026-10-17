<?php
session_start();
include 'conn.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    
    if ($action && $appointment_id) {
        switch ($action) {
            case 'cancel':
                $stmt = $conn->prepare('UPDATE appointment SET status = "cancelled" WHERE id = ?');
                $stmt->bind_param('i', $appointment_id);
                if ($stmt->execute()) {
                    $success_message = "Appointment cancelled successfully!";
                } else {
                    $error_message = "Error cancelling appointment: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'confirm':
                $stmt = $conn->prepare('UPDATE appointment SET status = "confirmed" WHERE id = ?');
                $stmt->bind_param('i', $appointment_id);
                if ($stmt->execute()) {
                    $success_message = "Appointment confirmed successfully!";
                } else {
                    $error_message = "Error confirming appointment: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'complete':
                $stmt = $conn->prepare('UPDATE appointment SET status = "completed" WHERE id = ?');
                $stmt->bind_param('i', $appointment_id);
                if ($stmt->execute()) {
                    $success_message = "Appointment marked as completed!";
                } else {
                    $error_message = "Error updating appointment status: " . $stmt->error;
                }
                $stmt->close();
                break;
        }
    }
}

// Fetch all appointments with doctor and patient information
$sql = "SELECT 
            b.id,
            b.doctor_id,
            b.patient_id,
            b.date as booking_date,
            b.time_slot,
            b.status,
            d.name AS doctor_name,
            d.specialization AS doctor_specialization,
            p.name AS patient_name,
            p.phone AS patient_phone,
            p.email AS patient_email,
            p.age AS patient_age,
            p.gender AS patient_gender
        FROM appointment b
        LEFT JOIN doctor d ON b.doctor_id = d.id
        LEFT JOIN patient p ON b.patient_id = p.id
        ORDER BY b.date DESC, b.time_slot ASC";

$result = $conn->query($sql);

// Debug: Check if query was successful
if (!$result) {
    $error_message = "Database Error: " . $conn->error;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff - Manage Appointments</title>
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
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        .page-title {
            text-align: center;
            font-size: 36px;
            color: #d40404;
            margin-bottom: 30px;
        }

        .appointment-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.2s ease-in-out;
        }
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .appointment-id {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-reserved {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .appointment-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        .actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-confirm {
            background-color: #28a745;
            color: white;
        }
        .btn-confirm:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        .btn-complete {
            background-color: #17a2b8;
            color: white;
        }
        .btn-complete:hover {
            background-color: #138496;
            transform: translateY(-2px);
        }
        .btn-reschedule {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-reschedule:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
        }
        .no-appointments {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 18px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            transition: opacity 0.5s ease;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            transition: opacity 0.5s ease;
        }
        
        .schedule-appointment-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s ease;
        }
        
        .schedule-appointment-btn:hover {
            background: linear-gradient(45deg, #20c997, #28a745);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <img src="logo.png" alt="Logo">
        <h1 class="title">SERENA</h1>
        <nav>
            <ul class="nav-links">
                <li><a href="Staff_dashboard.html" class="styled-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="staff_schedule_appointment.php" class="styled-link schedule-appointment-btn">Schedule Appointment</a></li>
                <li><a href="manage_appointments_by_staff.php" class="styled-link">Manage Appointments</a></li>
                <li><a href="staff_notification.php" class="styled-link">Notifications</a></li>
                <li><a href="doctor_display.php" class="styled-link">Doctors</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h1 class="page-title">Manage Appointments</h1>
        
        <!-- Session Messages -->
        <?php if (isset($_SESSION['appointment_success'])): ?>
            <div class="success-message">
                <strong>✅ Success:</strong> <?= htmlspecialchars($_SESSION['appointment_success']) ?>
            </div>
            <?php unset($_SESSION['appointment_success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['appointment_error'])): ?>
            <div class="error-message">
                <strong>❌ Error:</strong> <?= htmlspecialchars($_SESSION['appointment_error']) ?>
            </div>
            <?php unset($_SESSION['appointment_error']); ?>
        <?php endif; ?>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <strong>Success:</strong> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Appointments List -->
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="appointment-card">
                    <div class="appointment-header">
                        <div class="appointment-id">Appointment #<?= $row['id'] ?></div>
                        <div class="status-badge status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></div>
                    </div>
                    
                    <div class="appointment-info">
                        <div class="info-item">
                            <span class="info-label">👨‍⚕️ Doctor:</span> 
                            <?= $row['doctor_name'] ? htmlspecialchars($row['doctor_name']) : 'Not Assigned' ?>
                            <?php if ($row['doctor_specialization']): ?>
                                <br><small style="color: #666;">(<?= htmlspecialchars($row['doctor_specialization']) ?>)</small>
                            <?php endif; ?>
                        </div>
                        <div class="info-item">
                            <span class="info-label">🧑‍🤝‍🧑 Patient:</span> 
                            <?= $row['patient_name'] ? htmlspecialchars($row['patient_name']) : 'Not Assigned' ?>
                            <?php if ($row['patient_age'] && $row['patient_gender']): ?>
                                <br><small style="color: #666;">(<?= $row['patient_age'] ?> years, <?= ucfirst($row['patient_gender']) ?>)</small>
                            <?php endif; ?>
                        </div>
                        <div class="info-item">
                            <span class="info-label">📅 Date:</span> <?= $row['booking_date'] ?>
                        </div>
                        <div class="info-item">
                            <span class="info-label">⏰ Time:</span> <?= $row['time_slot'] ?>
                        </div>
                        <?php if ($row['patient_phone']): ?>
                        <div class="info-item">
                            <span class="info-label">📞 Phone:</span> <?= htmlspecialchars($row['patient_phone']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($row['patient_email']): ?>
                        <div class="info-item">
                            <span class="info-label">📧 Email:</span> <?= htmlspecialchars($row['patient_email']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="actions">
                        <?php if ($row['status'] === 'reserved'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="confirm">
                                <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-confirm" onclick="return confirm('Confirm this appointment?')">
                                    <i class="fas fa-check"></i> Confirm
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($row['status'] === 'confirmed'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="complete">
                                <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-complete" onclick="return confirm('Mark appointment as completed?')">
                                    <i class="fas fa-check-double"></i> Complete
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if (in_array($row['status'], ['reserved', 'confirmed'])): ?>
                            <a href="staff_reschedule.php?id=<?= $row['id'] ?>" class="btn btn-reschedule">
                                <i class="fas fa-edit"></i> Reschedule
                            </a>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-cancel" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($row['status'] === 'cancelled'): ?>
                            <span style="color: #666; font-style: italic;">Appointment cancelled</span>
                        <?php endif; ?>
                        
                        <?php if ($row['status'] === 'completed'): ?>
                            <span style="color: #28a745; font-weight: bold;">Appointment completed</span>
                        <?php endif; ?>
                        
                        <?php if ($row['status'] === 'confirmed'): ?>
                            <span style="color: #28a745; font-weight: bold;">Appointment confirmed</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-appointments">
                <i class="fas fa-calendar-times" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                <p>No appointments found.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Simple solution - just show loading state and let form submit normally
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to all forms
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    // Show loading message
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.textContent = 'Processing...';
                        submitBtn.disabled = true;
                    }
                    
                    // Let form submit normally - page will reload and show updated status
                });
            });
            
            // Auto-hide success/error messages after 3 seconds
            const successMessage = document.querySelector('.success-message');
            const errorMessage = document.querySelector('.error-message');
            
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    setTimeout(() => successMessage.remove(), 500);
                }, 3000);
            }
            
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.opacity = '0';
                    setTimeout(() => errorMessage.remove(), 500);
                }, 3000);
            }
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
