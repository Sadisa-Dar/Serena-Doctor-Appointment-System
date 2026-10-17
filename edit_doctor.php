<?php
include 'conn.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM doctor WHERE id = $id";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        echo "Doctor not found.";
        exit();
    }
} else {
    echo "No doctor ID provided.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Doctor Info</title>
  <link href="https://fonts.googleapis.com/css2?family=Aboreto&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: "Aboreto", sans-serif;
      background: linear-gradient(to bottom right, #fffafa, #ffe5e5);
      margin: 0;
      min-height: 100vh;
    }

    .header {
      background-color: #fffafa;
      display: flex;
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
      margin: 0;
      margin-left: -10px;
      color: #d40404;
      letter-spacing: 2px;
    }

    .form-container {
      max-width: 600px;
      margin: 40px auto;
      background: rgba(255, 250, 250, 0.88);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 0, 0, 0.15);
      border-radius: 25px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
      padding: 40px;
    }

    h2 {
      text-align: center;
      color: #d40404;
      margin-bottom: 30px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-size: 18px;
      color: #333;
    }

    input[type="text"],
    input[type="number"],
    input[type="time"] {
      width: 100%;
      padding: 12px;
      border-radius: 12px;
      border: 1px solid #ccc;
      margin-bottom: 20px;
      font-size: 16px;
      background: white;
    }

    .form-buttons {
      display: flex;
      justify-content: space-between;
    }

    .form-buttons button {
      padding: 12px 30px;
      border-radius: 12px;
      border: none;
      font-size: 16px;
      font-weight: bold;
      background-color: #d40404;
      color: white;
      cursor: pointer;
      transition: 0.3s;
    }

    .form-buttons button:hover {
      background-color: #a20000;
    }

    a {
      text-decoration: none;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <div class="header">
    <img src="logo.png" alt="Logo" />
    <p class="title">SERENA</p>
  </div>

  <!-- Edit Form -->
  <div class="form-container">
    <h2>Edit Doctor Information</h2>
    <form action="update_doctor.php" method="POST">
      <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

      <label>Name:</label>
      <input type="text" name="name" value="<?php echo $row['name']; ?>" required>

      <label>Experience:</label>
      <input type="text" name="experience" value="<?php echo $row['experience']; ?>" required>

      <label>Specialization:</label>
      <input type="text" name="specialization" value="<?php echo $row['specialization']; ?>" required>

      <label>Education:</label>
      <input type="text" name="education" value="<?php echo $row['education']; ?>" required>

      <label>Start Time:</label>
      <input type="time" name="start_time" value="<?php echo $row['start_time']; ?>" required>

      <label>End Time:</label>
      <input type="time" name="end_time" value="<?php echo $row['end_time']; ?>" required>

      <label>Rating:</label>
      <input type="number" name="rating" step="0.1" min="0" max="5" value="<?php echo $row['rating']; ?>" required>

      <div class="form-buttons">
        <button type="submit">Update</button>
        <a href="doctor_display.php"><button type="button">Cancel</button></a>
      </div>
    </form>
  </div>

</body>
</html>
