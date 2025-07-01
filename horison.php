<?php
include 'db_connect.php';
session_start();

// Ambil parameter dari URL
$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 2; // Default ID Horison Hotel
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : date('Y-m-d');
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : date('Y-m-d', strtotime('+1 day'));
$tamu = isset($_GET['tamu']) ? (int)$_GET['tamu'] : 1;

// Validasi parameter
if ($hotel_id == 0) {
    header("Location: search.php");
    exit;
}

// Ambil data hotel
$stmt_hotel = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt_hotel->bind_param("i", $hotel_id);
$stmt_hotel->execute();
$hotel_data = $stmt_hotel->get_result()->fetch_assoc();
$stmt_hotel->close();

if (!$hotel_data) {
    header("Location: search.php");
    exit;
}

// Ambil data kamar jika room_id tersedia
$room_data = null;
if ($room_id > 0) {
    $stmt_room = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND hotel_id = ?");
    $stmt_room->bind_param("ii", $room_id, $hotel_id);
    $stmt_room->execute();
    $room_data = $stmt_room->get_result()->fetch_assoc();
    $stmt_room->close();
}

// Jika tidak ada kamar spesifik yang dipilih, ambil kamar pertama yang tersedia
if (!$room_data) {
    $stmt_room = $conn->prepare("SELECT * FROM rooms WHERE hotel_id = ? AND is_available = 1 LIMIT 1");
    $stmt_room->bind_param("i", $hotel_id);
    $stmt_room->execute();
    $room_data = $stmt_room->get_result()->fetch_assoc();
    $stmt_room->close();
}

// Hitung total harga berdasarkan jumlah malam
$total_harga = 0;
if ($room_data) {
    $date1 = new DateTime($checkin);
    $date2 = new DateTime($checkout);
    $interval = $date1->diff($date2);
    $jumlah_malam = $interval->days;
    $total_harga = $room_data['price'] * $jumlah_malam;
}

// Proses pemesanan
if (isset($_POST['book_now'])) {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    if ($room_data) {
        header("Location: booking_form.php?hotel_id=$hotel_id&room_id={$room_data['id']}&checkin=$checkin&checkout=$checkout&tamu=$tamu");
        exit;
    } else {
        echo "<script>alert('Maaf, tidak ada kamar yang tersedia saat ini.');</script>";
    }
}

// Format harga
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title><?php echo htmlspecialchars($hotel_data['nama']); ?> | PURQON.COM</title>
  <style>
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
    .btn {
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

    .detail-container {
      max-width: 960px;
      margin: 20px auto;
      padding: 20px;
      background-color: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border-radius: 8px;
    }
    .hotel-header {
      display: flex;
      gap: 20px;
      margin-bottom: 30px;
    }
    .hotel-header img {
      width: 400px;
      height: 250px;
      object-fit: cover;
      border-radius: 8px;
    }
    .hotel-info h1 {
      margin-top: 0;
      font-size: 28px;
      color: #333;
    }
    .stars {
      color: gold;
      font-size: 20px;
    }
    .location-info {
      display: flex;
      align-items: center;
      gap: 5px;
      color: #555;
      font-size: 14px;
      margin-bottom: 20px;
    }
    
    .room-details {
      background-color: #f9f9f9;
      border: 1px solid #eee;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .room-title {
      font-size: 20px;
      margin-top: 0;
      color: #333;
    }
    .room-price {
      font-size: 24px;
      font-weight: bold;
      color: #e74c3c;
      margin: 10px 0;
    }
    .room-facilities {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin: 15px 0;
    }
    .facility-badge {
      background-color: #ecf0f1;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 12px;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .booking-summary {
      background-color: #f0f8ff;
      border: 1px solid #d0e3ff;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .summary-title {
      font-size: 18px;
      margin-top: 0;
      color: #333;
      border-bottom: 1px solid #d0e3ff;
      padding-bottom: 10px;
    }
    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
    }
    .summary-label {
      color: #555;
    }
    .summary-value {
      font-weight: 600;
    }
    .total-price {
      font-size: 20px;
      font-weight: bold;
      color: #e74c3c;
      margin-top: 15px;
      padding-top: 15px;
      border-top: 1px solid #d0e3ff;
    }
    
    .book-button {
      background-color: #f36;
      color: white;
      padding: 12px 25px;
      border: none;
      border-radius: 25px;
      font-weight: bold;
      cursor: pointer;
      width: 100%;
      text-align: center;
      font-size: 16px;
      transition: background-color 0.3s;
    }
    .book-button:hover {
      background-color: #d22b5d;
    }
    
    .section-title {
      font-size: 20px;
      margin-top: 30px;
      margin-bottom: 15px;
      color: #333;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }
    .related-hotels {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
    }
    .related-hotel-card {
      border: 1px solid #eee;
      border-radius: 8px;
      overflow: hidden;
      text-align: center;
    }
    .related-hotel-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }
    .book-link {
      display: inline-block;
      background-color: #f36;
      color: white;
      padding: 8px 15px;
      border-radius: 20px;
      text-decoration: none;
      font-size: 14px;
      margin: 10px 0;
    }
    .book-link:hover {
      background-color: #d22b5d;
    }
    
    @media (max-width: 768px) {
      .hotel-header {
        flex-direction: column;
      }
      .hotel-header img {
        width: 100%;
      }
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
          <span style="margin-right: 10px;">Halo, <?= htmlspecialchars($_SESSION['user']['email']) ?></span>
          <a href="logout.php" class="btn login" style="background:#f36; color:#fff;">Logout</a>
        <?php else: ?>
          <a href="login.php" class="btn login">Masuk</a>
          <a href="register.php" class="btn daftar">Daftar</a>
        <?php endif; ?>
      </div>
      <a href="admin_login.php" class="btn admin">Admin</a>
    </div>
  </div>

  <div class="detail-container">
    <div class="hotel-header">
<img src="images/HORISON/Horison.jpg" alt="<?= htmlspecialchars($hotel_data['nama']) ?>">
      <div class="hotel-info">
        <h1><?= htmlspecialchars($hotel_data['nama']) ?></h1>
        <div class="stars">
          <?php
          $full_stars = floor($hotel_data['rating']);
          $half_star = ($hotel_data['rating'] - $full_stars) >= 0.5 ? 1 : 0;
          echo str_repeat('★', $full_stars);
          if ($half_star) echo '½';
          ?>
          (<?= number_format($hotel_data['rating'], 1) ?>)
        </div>
        <div class="location-info">
          <i class="fas fa-map-marker-alt"></i>
          <span><?= htmlspecialchars($hotel_data['alamat']) ?></span>
          &nbsp;&nbsp;
          <a href="https://goo.gl/maps/TsJ9sxfvnQo4tM8W8" target="_blank" style="color:#f36; text-decoration:underline;">Lihat di peta</a>
        </div>
      </div>
    </div>

    <?php if ($room_data): ?>
      <div class="room-details">
        <h2 class="room-title"><?= htmlspecialchars($room_data['room_type']) ?></h2>
        <div class="room-price"><?= formatCurrency($room_data['price']) ?> /malam</div>
        
        <div class="room-facilities">
          <?php if ($room_data['has_ac']): ?>
            <span class="facility-badge"><i class="fas fa-snowflake"></i> AC</span>
          <?php endif; ?>
          <?php if ($room_data['has_tv']): ?>
            <span class="facility-badge"><i class="fas fa-tv"></i> TV</span>
          <?php endif; ?>
          <?php if ($room_data['has_wifi']): ?>
            <span class="facility-badge"><i class="fas fa-wifi"></i> WiFi</span>
          <?php endif; ?>
          <?php if ($room_data['has_breakfast']): ?>
            <span class="facility-badge"><i class="fas fa-utensils"></i> Sarapan</span>
          <?php endif; ?>
        </div>
        
        <p>Kapasitas maksimal: <?= $room_data['max_guests'] ?> orang</p>
      </div>
      
      <div class="booking-summary">
        <h3 class="summary-title">Ringkasan Pemesanan</h3>
        <div class="summary-row">
          <span class="summary-label">Check-in:</span>
          <span class="summary-value"><?= date('d F Y', strtotime($checkin)) ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Check-out:</span>
          <span class="summary-value"><?= date('d F Y', strtotime($checkout)) ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Durasi:</span>
          <span class="summary-value">
            <?php 
              $date1 = new DateTime($checkin);
              $date2 = new DateTime($checkout);
              $interval = $date1->diff($date2);
              echo $interval->days . ' malam';
            ?>
          </span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Jumlah Tamu:</span>
          <span class="summary-value"><?= $tamu ?> orang</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Harga per malam:</span>
          <span class="summary-value"><?= formatCurrency($room_data['price']) ?></span>
        </div>
        <div class="total-price">
          Total: <?= formatCurrency($total_harga) ?>
        </div>
      </div>
      
      <form method="POST" action="">
        <button type="submit" name="book_now" class="book-button">
          <i class="fas fa-book"></i> Pesan Sekarang
        </button>
      </form>
    <?php else: ?>
      <div style="background-color: #fff4f4; padding: 20px; border-radius: 8px; text-align: center;">
        <h3 style="color: #d22b5d; margin-top: 0;">Maaf, tidak ada kamar yang tersedia saat ini.</h3>
        <p>Silakan coba tanggal yang berbeda atau lihat hotel lainnya.</p>
        <a href="search.php" style="display: inline-block; background-color: #f36; color: white; padding: 10px 20px; border-radius: 25px; text-decoration: none;">Cari Hotel Lain</a>
      </div>
    <?php endif; ?>

    <div class="section-title">Hotel Lainnya di Tasikmalaya</div>
    <div class="related-hotels">
      <?php
      // Ambil 3 hotel lainnya selain yang sedang dilihat
      $stmt_other = $conn->prepare("SELECT * FROM hotels WHERE id != ? ORDER BY rating DESC LIMIT 3");
      $stmt_other->bind_param("i", $hotel_id);
      $stmt_other->execute();
      $other_hotels = $stmt_other->get_result()->fetch_all(MYSQLI_ASSOC);
      $stmt_other->close();
      
      foreach ($other_hotels as $hotel): 
        $hotel_page = strtolower(str_replace(' ', '_', $hotel['nama'])) . '.php';
        $hotel_page = str_replace('hotel_', '', $hotel_page);
      ?>
        <div class="related-hotel-card">
          <div class="info">
            <h3><?= htmlspecialchars($hotel['nama']) ?></h3>
            <div style="margin: 10px 0;">
              <span class="stars"><?= str_repeat('★', floor($hotel['rating'])) ?></span>
              (<?= number_format($hotel['rating'], 1) ?>)
            </div>
            <a href="<?= $hotel_page ?>?hotel_id=<?= $hotel['id'] ?>" class="book-link">Lihat Detail</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>