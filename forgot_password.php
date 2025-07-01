<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Lupa Password</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: url('Hotel.jpg') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }
    .forgot-box {
      background-color: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
      width: 400px;
    }
    .forgot-box h2 {
      margin-bottom: 20px;
      font-weight: bold;
      font-size: 18px;
    }
    .forgot-box input {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .forgot-box button {
      width: 100%;
      padding: 12px;
      background-color: #ff3d7f;
      color: white;
      border: none;
      border-radius: 25px;
      font-weight: bold;
      cursor: pointer;
    }
    .forgot-box .links {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      margin-top: -10px;
      margin-bottom: 15px;
    }
    .forgot-box .links a {
      text-decoration: none;
      color: #ff3d7f;
    }
  </style>
</head>
<body>
  <div class="forgot-box">
    <h2>Atur Ulang Password</h2>
    <form method="POST" action="send_reset_link.php">
      <input type="text" name="email" placeholder="Nomor Ponsel atau Email" required>
      <div class="links">
        <a href="register.php">Daftar Baru</a>
        <a href="login.php">Sudah punya akun?</a>
      </div>
      <button type="submit">LUPA PASSWORD</button>
    </form>
  </div>
</body>
</html>
