<?php
include 'conn.php';
if (!isset($_GET['id'])) {
    die('No appointment ID provided.');
}
$id = intval($_GET['id']);
// Fetch current appointment (including doctor_id)
$stmt = $conn->prepare('SELECT doctor_id, date, time_slot FROM appointment WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($doctor_id, $booking_date, $time_slot);
if (!$stmt->fetch()) {
    die('Appointment not found.');
}
$stmt->close();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_date = $_POST['booking_date'];
    $new_time = $_POST['time_slot'];
    // Check if the slot is already reserved for this doctor and date (excluding this appointment)
    $check = $conn->prepare('SELECT COUNT(*) FROM appointment WHERE doctor_id = ? AND date = ? AND time_slot = ? AND status = "reserved" AND id != ?');
    $check->bind_param('issi', $doctor_id, $new_date, $new_time, $id);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();
    if ($count > 0) {
        echo '<script>alert("This time slot is already reserved for this doctor. Please choose another slot.");window.history.back();</script>';
    } else {
        $update = $conn->prepare('UPDATE appointment SET date = ?, time_slot = ? WHERE id = ?');
        $update->bind_param('ssi', $new_date, $new_time, $id);
        if ($update->execute()) {
            echo '<script>alert("Appointment rescheduled successfully.");window.location.href="view_appointments.php";</script>';
        } else {
            echo '<script>alert("Error rescheduling appointment.");</script>';
        }
        $update->close();
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reschedule Appointment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Aboreto&family=Roboto&display=swap" rel="stylesheet" />
    <style>
        body { margin: 0; font-family: 'Aboreto', system-ui; background: linear-gradient(to bottom right, #fffafa, #ffe5e5); }
        .header { background-color: #fffafa; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-bottom: 3px solid #d40404; height: 90px; padding: 0 40px; }
        .header img { height: 130px; width: 130px; }
        .title { font-size: 38px; margin-left: -755px; color: #d40404; letter-spacing: 2px; }
        .nav-links { list-style: none; display: flex; padding: 0; margin: 0; align-items: center; }
        .nav-links li { margin-left: 40px; }
        .styled-link { text-decoration: none; color: black; font-size: 20px; transition: 0.3s; font-family: 'Aboreto', system-ui; padding: 8px 16px; border-radius: 8px; }
        .styled-link:hover { color: #d40404; background-color: #ffe5e5; }
        .container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .form-card { background: white; border-radius: 12px; padding: 30px 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 500px; margin: 40px auto; }
        .form-card h2 { color: #d40404; margin-bottom: 20px; text-align: center; }
        .form-label { font-size: 18px; color: #444; margin-bottom: 10px; display: block; }
        .form-input { width: 100%; padding: 10px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #ccc; font-size: 16px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-family: 'Roboto', sans-serif; font-size: 16px; }
        .btn-reschedule { background-color: #ffa726; color: white; margin-right: 10px; }
        .btn-cancel { background-color: #e53935; color: white; }
    </style>
    <script>
    function fetchSlots() {
        var date = document.getElementById('booking_date').value;
        var doctor_id = <?= $doctor_id ?>;
        var slotSelect = document.getElementById('time_slot');
        slotSelect.innerHTML = '<option>Loading...</option>';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'get_slots.php?doctor_id=' + doctor_id + '&date=' + date + '&current_id=<?= $id ?>', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                slotSelect.innerHTML = xhr.responseText;
            } else {
                slotSelect.innerHTML = '<option>Error loading slots</option>';
            }
        };
        xhr.send();
    }
    window.onload = function() {
        fetchSlots();
        document.getElementById('booking_date').addEventListener('change', fetchSlots);
    };
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
        <div class="form-card">
            <h2>Reschedule Appointment #<?= $id ?></h2>
            <form method="post">
                <label class="form-label">Date:
                    <input type="date" name="booking_date" id="booking_date" class="form-input" value="<?= htmlspecialchars($booking_date) ?>" required>
                </label>
                <label class="form-label">Time Slot:
                    <select name="time_slot" id="time_slot" class="form-input" required></select>
                </label>
                <button type="submit" class="btn btn-reschedule">Reschedule</button>
                <a href="view_appointments.php" class="btn btn-cancel">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
