<?php
include 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $nomor_ponsel = $_POST['nomor_ponsel'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Password dan Validasi Password tidak cocok.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO Users (nama_lengkap, email, nomor_ponsel, password, user_type) VALUES (?, ?, ?, ?, 'user')");
        $stmt->bind_param('ssss', $nama_lengkap, $email, $nomor_ponsel, $hashed_password);
        if ($stmt->execute()) {
            header("Location: login.php");
            exit;
        } else {
            $error = "Pendaftaran gagal. Email atau nomor ponsel mungkin sudah terdaftar.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Register/Daftar</title>
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
    .register-box {
      background-color: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
      width: 400px;
    }
    .register-box h2 {
      margin-bottom: 20px;
      font-weight: bold;
      font-size: 18px;
    }
    .register-box input {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .register-box button {
      width: 100%;
      padding: 12px;
      background-color: #ff3d7f;
      color: white;
      border: none;
      border-radius: 25px;
      font-weight: bold;
      cursor: pointer;
    }
    .register-box .bottom-link {
      text-align: left;
      font-size: 12px;
      margin-top: 10px;
    }
    .register-box .bottom-link a {
      color: #ff3d7f;
      text-decoration: none;
    }
    .error-message {
      color: red;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
  <div class="register-box">
    <h2>Register/Daftar</h2>
    <?php if (isset($error)) echo "<p class='error-message'>$error</p>"; ?>
    <form method="POST">
      <input type="text" name="nama_lengkap" placeholder="Nama Lengkap anda" required>
      <input type="email" name="email" placeholder="Email anda" required>
      <input type="text" name="nomor_ponsel" placeholder="No Ponsel" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirm_password" placeholder="Validasi Password" required>
      <div class="bottom-link">
        <a href="login.php">Sudah Punya Akun?</a>
      </div>
      <br>
      <button type="submit">DAFTAR</button>
    </form>
  </div>
</body>
</html>