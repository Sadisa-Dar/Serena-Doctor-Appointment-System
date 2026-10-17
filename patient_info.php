<?php
$doctor_id = $_GET['doctor_id'] ?? null;
$date = $_GET['date'] ?? null;
$slot = $_GET['slot'] ?? null;

if (!$doctor_id || !$date || !$slot) {
  die("Invalid booking information.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Enter Patient Info</title>
  <link href="https://fonts.googleapis.com/css2?family=Aboreto&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Aboreto', sans-serif;
      background: linear-gradient(to bottom right, #fffafa, #ffe5e5);
      margin: 0;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }

    .form-box {
      background: white;
      padding: 40px;
      border-radius: 20px;
      width: 420px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .form-box h2 {
      color: #d40404;
      text-align: center;
      margin-bottom: 30px;
    }

    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #444;
    }

    input, select {
      width: 95%;
      padding: 10px;
      margin-bottom: 18px;
      border: 1px solid #ddd;
      border-radius: 10px;
      font-size: 16px;
    }

    button {
      background: #d40404;
      color: white;
      padding: 12px;
      width: 100%;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s;
    }

    button:hover {
      background: #a80202;
    }

    select {
      width: 100%;
    }
  </style>
</head>
<body>
  <div class="form-box">
    <h2>Patient Details</h2>
    <form action="save_patient.php" method="POST">
      <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
      <input type="hidden" name="date" value="<?= $date ?>">
      <input type="hidden" name="slot" value="<?= $slot ?>"> 

      <label for="name">Full Name:</label>
      <input type="text" name="name" id="name" required>

      <label for="email">Email:</label>
      <input type="email" name="email" id="email">

      <label for="gender">Gender:</label>
      <select name="gender" id="gender" required>
        <option value="" disabled selected>Select gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>

      <label for="phone">Phone Number:</label>
      <input type="text" name="phone" id="phone" required>

      <label for="age">Age:</label>
      <input type="number" name="age" id="age" required min="0">

      <button type="submit">Confirm Booking</button>
    </form>
  </div>
</body>
</html>
