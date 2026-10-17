<?php
include 'conn.php';

$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = $_POST['password'];

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into database
$sql = "INSERT INTO staff (name, email, phone, password)
        VALUES ('$name', '$email', '$phone', '$hashed_password')";

if ($conn->query($sql) === TRUE) {
    header("Location: Staff_dashboard.html");
    exit();
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
