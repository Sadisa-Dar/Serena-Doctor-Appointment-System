<?php
include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id"];
    $name = $_POST["name"];
    $experience = $_POST["experience"];
    $specialization = $_POST["specialization"];
    $education = $_POST["education"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];
    $rating = $_POST["rating"];

    $sql = "UPDATE doctor SET 
        name='$name',
        experience='$experience',
        specialization='$specialization',
        education='$education',
        start_time='$start_time',
        end_time='$end_time',
        rating='$rating'
        WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        header("Location: doctor_display.php");
        exit();
    } else {
        echo "Error updating doctor: " . $conn->error;
    }

    $conn->close();
}
?>
