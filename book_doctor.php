<?php
  include 'conn.php';
  
  $sql = "SELECT * FROM doctor";
  $result = $conn->query($sql);
  
  if (!$result) {
      die("Error fetching doctors: " . $conn->error);
  }
?>
<?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
  <div id="success-message" style="
    background-color: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    margin: 20px;
    font-weight: bold;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  ">
    ✅ Appointment booked successfully!
  </div>

  <script>
    // Hide message after 4 seconds
    setTimeout(function () {
      var msg = document.getElementById("success-message");
      if (msg) msg.style.display = "none";
    }, 4000);
  </script>
<?php endif;?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Book Doctor</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Aboreto&family=Roboto&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: "Aboreto", sans-serif;
      background: linear-gradient(to bottom right, #fffafa, #ffe5e5);
      margin: 0;
      padding: 0;
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
      position: relative;
      z-index: 1;
    }

    .header img {
      height: 70px;
      width: 70px;
    }

    .title {
      font-size: 38px;
      margin: 0;
      margin-left: -995px;
      color: #d40404;
      letter-spacing: 2px;
      font-family: 'Aboreto', system-ui;
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

    .doctor-container {
      max-width: 1400px;
      margin: auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 35px;
      padding: 40px 20px;
    }

    .card {
      background: rgba(255, 250, 250, 0.88);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 0, 0, 0.15);
      border-radius: 25px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
      padding: 35px 30px;
      transition: 0.3s;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      min-height: 450px;
    }

    .card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }

    .card img {
      height: 130px;
      width: 130px;
      border-radius: 50%;
      margin-bottom: 20px;
      object-fit: cover;
    }

    .card h2 {
      color: #d40404;
      font-size: 26px;
      margin: 10px 0 20px;
      word-wrap: break-word;
    }

    .info-pair {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      width: 100%;
      font-size: 16px;
      margin: 8px 0;
      flex-wrap: wrap;
    }

    .info-pair span {
      flex: 1;
      text-align: left;
      word-wrap: break-word;
    }

    .stars {
      color: #ff9900;
      font-size: 18px;
    }

    .buttons {
      margin-top: 20px;
    }

    .buttons a {
      padding: 12px 40px;
      border-radius: 12px;
      background-color: #d40404;
      color: white;
      text-decoration: none;
      margin: 0 10px;
      font-size: 15px;
      transition: 0.2s;
    }

    .buttons a:hover {
      background-color: #a20000;
      padding: 13px 45px;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<div class="header">
  <img src="logo.png" alt="Logo" style="height: 130px; width: 130px;">
  <p class="title">SERENA</p>
  <ul class="nav-links">
    <li><a href="Home.html" class="styled-link"><i class="fas fa-home"></i> Home</a></li>
    <li><a href="book_doctor.php" class="styled-link"><i class="fas fa-calendar-plus"></i> Book</a></li>
    <li><a href="view_appointments.php" class="styled-link"><i class="fas fa-calendar-check"></i> Appointments</a></li>
  </ul>
</div>

<!-- Doctor Cards -->
<div class="doctor-container">
  <?php
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) { ?>
        <div class="card">
          <img src="doctor.png" alt="Doctor Image">
          <h2><?php echo $row["name"]; ?></h2>

          <div class="info-pair">
            <span><b>Experience:</b> <?php echo $row["experience"]; ?></span>
            <span class="stars"><b style="color: black;">Rating:</b>
              <?php
                $stars = round($row["rating"]);
                echo str_repeat("★", $stars) . str_repeat("☆", 5 - $stars);
              ?>
            </span>
          </div>
          <div class="info-pair">
            <span><b>Start Time:</b> <?php echo date("g:i A", strtotime($row["start_time"])); ?></span>
            <span><b>End Time:</b> <?php echo date("g:i A", strtotime($row["end_time"])); ?></span>
          </div>
          <center>
          <div class="info-pair">
            <span><b>Education:</b> <?php echo $row["education"]; ?></span>
          </div>
          </center>
          <center>
          <div class="info-pair">
            <span><b>Specialization:</b> <?php echo $row["specialization"]; ?></span>
          </div><br><br>
          </center>
          <div class="buttons">
            <a href="booking.php?id=<?php echo $row['id']; ?>">Book</a>
          </div>
        </div>
  <?php
      }
    } else {
      echo "<p>No doctors found.</p>";
    }
    $conn->close();
  ?>
</div>

</body>
</html>
