<?php
include 'db_connect.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrPhone = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($emailOrPhone) && !empty($password)) {
        $sql = "SELECT * FROM users WHERE (email = ? OR nomor_ponsel = ?) AND user_type = 'user'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $emailOrPhone, $emailOrPhone);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header('Location: index.php');
            exit;
        } else {
            $error = "Email/Nomor Ponsel atau Password salah.";
        }
    } else {
        $error = "Silakan isi semua kolom.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Log in/Masuk</title>
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
    .login-box {
      background-color: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
      width: 400px;
    }
    .login-box h2 {
      margin-bottom: 20px;
      font-weight: bold;
      font-size: 18px;
    }
    .login-box input[type="text"],
    .login-box input[type="password"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .login-box button {
      width: 100%;
      padding: 12px;
      background-color: #ff3d7f;
      color: white;
      border: none;
      border-radius: 25px;
      font-weight: bold;
      cursor: pointer;
    }
    .login-box .links {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      margin-top: 10px;
    }
    .login-box .links a {
      text-decoration: none;
      color: #ff3d7f;
    }
    .error-message {
      color: red;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h2>Log in/Masuk</h2>
    <?php if (!empty($error)): ?>
      <p class="error-message"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
      <input type="text" name="email" placeholder="Nomor Ponsel atau Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <div class="links">
        <a href="register.php">Daftar Baru</a>
        <a href="forgot_password.php">Lupa Kata Sandi?</a>
      </div>
      <br>
      <button type="submit">MASUK</button>
    </form>
  </div>
</body>
</html>
