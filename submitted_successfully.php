<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Submitted Successfully</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/submit_hours2.css">
  <style>
 body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background: linear-gradient(135deg, #ff4b2b, #ff416c);
      color: white;
      text-align: center;
    }

    .success-container {
      background-color: rgba(255, 255, 255, 0.1);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.3);
      max-width: 90%;
    }

    .success-icon {
      font-size: 70px;
      margin-bottom: 20px;
      color: #00e676;
    }

    h1 {
      font-size: 32px;
      margin-bottom: 10px;
    }

    p {
      font-size: 18px;
      margin-bottom: 30px;
    }

    .btn {
      display: inline-block;
      padding: 12px 24px;
      font-size: 16px;
      background-color: #fff;
      color: #ff416c;
      border: none;
      border-radius: 30px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .btn:hover {
      background-color: #ffe0e6;
    }

    @media (max-width: 600px) {
      h1 {
        font-size: 24px;
      }

      .success-icon {
        font-size: 50px;
      }
    }
  </style>
</head>
<body>

  <div class="success-container">
    <div class="success-icon">
      <i class="fas fa-check-circle"></i>
    </div>
    <h1>Submitted Successfully!</h1>
    <p>Your information has been sent and is now awaiting approval.</p>
    <a href="hours_page.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Hours Page</a>
  </div>

</body>
</html>
