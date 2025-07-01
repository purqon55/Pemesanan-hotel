<?php
include 'db_connect.php';
session_start(); // Wajib agar $_SESSION bisa digunakan
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Cari Hotel</title>
  <link rel="stylesheet" href="style.css">
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f8f9ff;
    }
    .top-bar {
      background-color: #fff;
      padding: 10px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 3px solid #000;
    }
    .logo {
      display: flex;
      flex-direction: column;
      font-size: 16px;
      line-height: 1.2;
    }
    .logo strong {
      font-weight: bold;
    }
    .top-buttons {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 4px;
    }
    .top-buttons .row {
      display: flex;
      gap: 8px;
    }
    .top-buttons a {
      text-decoration: none;
      font-weight: 600;
      padding: 6px 14px;
      border-radius: 6px;
      font-size: 14px;
      transition: 0.2s ease;
    }
    .btn.login {
      background: #fff;
      color: #f36;
      border: 1px solid #f36;
    }
    .btn.daftar {
      background-color: #f36;
      color: #fff;
      border: none;
    }
    .btn.admin {
      background-color: #d22b5d;
      color: #fff;
      border: none;
    }
    .btn:hover {
      opacity: 0.9;
    }
    .hero {
      background-image: url('Hotel.jpg');
      background-size: cover;
      background-position: center;
      height: 260px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      color: white;
      text-shadow: 1px 1px 5px rgba(0,0,0,0.5);
      position: relative;
    }
    .search-container {
      background-color: #f3f0ff;
      padding: 40px 20px;
      text-align: center;
    }
    .search-title h1 {
      margin: 0;
      font-size: 24px;
    }
    .search-title p {
      margin: 8px 0 20px 0;
      font-size: 16px;
      color: #555;
    }
    .search-box {
      background-color: #fff;
      margin: 0 auto;
      padding: 20px;
      width: 100%;
      max-width: 900px;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .search-box form {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr 1.5fr 1fr;
      gap: 10px;
      align-items: center;
    }
    .search-box input,
    .search-box select,
    .search-box button,
    .search-box .btn-lokasi,
    .search-box .select-lokasi {
      padding: 12px;
      font-size: 1rem;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    .btn-lokasi {
      display: none;
    }
    .select-lokasi {
      background-color: #fff;
      cursor: pointer;
    }
    .search-box button[type="submit"] {
      background-color: #f36;
      color: white;
      font-weight: bold;
      border: none;
      cursor: pointer;
      grid-column: span 5;
    }
    .search-box button[type="submit"]:hover {
      background-color: #d22b5d;
    }
    </style>
</head>
<body>
  <div class="top-bar">
    <div class="logo">
      <strong style="color:#000">PURQON<span style="color:#f36">.COM</span></strong>
      <div style="font-size: 12px; color:#333;">Pesan Hotel</div>
    </div>
    <div class="top-buttons">
      <div class="row">
        <?php if (isset($_SESSION['user'])): ?>
          <span style="margin-right: 10px; font-size: 14px;">Halo, <?= htmlspecialchars($_SESSION['user']['email']) ?></span>
          <a href="logout.php" class="btn login" style="background-color: #f36; color: white;">Logout</a>
        <?php else: ?>
          <a href="login.php" class="btn login">Masuk</a>
          <a href="register.php" class="btn daftar">Daftar</a>
        <?php endif; ?>
      </div>
      <a href="admin_login.php" class="btn admin">Admin</a>
    </div>
  </div>

  <div class="hero"></div>

  <div class="search-container">
    <div class="search-title">
      <h1>Cari Hotel?</h1>
      <p>Murah dengan Harga Promo</p>
    </div>
    <div class="search-box">
      <form action="search.php" method="GET">
        <select name="lokasi" class="select-lokasi">
          <option value="Tasikmalaya">Tasikmalaya</option>
          <option value="Bandung">Bandung</option>
        </select>
        <input type="date" name="checkin" id="checkin" required>
        <input type="date" name="checkout" id="checkout" required>
        <input type="text" name="tamu" placeholder="0 Dewasa, 0 Anak" required>
        <select name="tipe_kamar">
          <option value="Twin">Twin</option>
          <option value="Double">Double</option>
        </select>
        <button type="submit">Cari Hotel</button>
      </form>
    </div>
  </div>

  <script>
    // Validasi tanggal checkout tidak boleh sebelum checkin
    document.addEventListener('DOMContentLoaded', function() {
      const checkinInput = document.getElementById('checkin');
      const checkoutInput = document.getElementById('checkout');
      
      // Set tanggal minimum untuk checkin (hari ini)
      const today = new Date().toISOString().split('T')[0];
      checkinInput.min = today;
      
      checkinInput.addEventListener('change', function() {
        if (this.value) {
          // Set tanggal minimum checkout ke hari setelah checkin
          const nextDay = new Date(this.value);
          nextDay.setDate(nextDay.getDate() + 1);
          const nextDayStr = nextDay.toISOString().split('T')[0];
          
          checkoutInput.min = nextDayStr;
          
          // Jika checkout sudah dipilih dan lebih awal dari checkin, reset
          if (checkoutInput.value && checkoutInput.value <= this.value) {
            checkoutInput.value = '';
          }
        }
      });
      
      checkoutInput.addEventListener('change', function() {
        if (this.value && checkinInput.value && this.value <= checkinInput.value) {
          alert('Tanggal checkout harus setelah tanggal checkin');
          this.value = '';
        }
      });
    });
  </script>
</body>
</html>