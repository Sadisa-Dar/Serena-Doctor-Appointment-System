<?php
include 'conn.php';
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';
$current_id = isset($_GET['current_id']) ? intval($_GET['current_id']) : 0;
if (!$doctor_id || !$date) {
    echo '<option value="">No slots</option>';
    exit;
}
// Fetch doctor's start and end time
$stmt = $conn->prepare('SELECT start_time, end_time FROM doctor WHERE id = ?');
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$stmt->bind_result($start_time, $end_time);
if (!$stmt->fetch()) {
    echo '<option value="">No slots</option>';
    exit;
}
$stmt->close();
$start = strtotime($start_time);
$end = strtotime($end_time);
$slots = [];
for ($time = $start; $time + 1200 <= $end; $time += 1200) {
    $slot_start = date('H:i', $time);
    $slot_end = date('H:i', $time + 1200);
    $slots[] = "$slot_start - $slot_end";
}
// Fetch reserved slots for this doctor and date, except for the current appointment
$reserved = [];
$stmt = $conn->prepare('SELECT time_slot FROM appointment WHERE doctor_id = ? AND date = ? AND status = "reserved" AND id != ?');
$stmt->bind_param('isi', $doctor_id, $date, $current_id);
$stmt->execute();
$stmt->bind_result($reserved_slot);
while ($stmt->fetch()) {
    $reserved[] = $reserved_slot;
}
$stmt->close();
foreach ($slots as $slot) {
    if (!in_array($slot, $reserved)) {
        echo '<option value="' . htmlspecialchars($slot) . '">' . htmlspecialchars($slot) . '</option>';
    }
}
$conn->close();
?>