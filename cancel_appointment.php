<?php
// cancel_appointment.php
include 'conn.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare('UPDATE appointment SET status = ? WHERE id = ?');
    $cancelled = 'cancelled';
    $stmt->bind_param('si', $cancelled, $id);
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }
    $stmt->close();
} else {
    echo 'error';
}
$conn->close();
?>
