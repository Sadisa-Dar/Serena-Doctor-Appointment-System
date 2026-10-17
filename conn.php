<?php
$server = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'Serena';

$conn = mysqli_connect($server, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>