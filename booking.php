<?php
include 'db_connect.php';
session_start();

// Ambil parameter pencarian
$lokasi = isset($_GET['lokasi']) ? $_GET['lokasi'] : '';
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$tamu = isset($_GET['tamu']) ? (int)$_GET['tamu'] : 1;
$tipe_kamar = isset($_GET['tipe_kamar']) ? $_GET['tipe_kamar'] : '';

// Validasi input
if (empty($lokasi) || empty($checkin) || empty($checkout)) {
    header("Location: index.php");
    exit();
}

// Konversi format tanggal untuk database
$checkin_db = date('Y-m-d', strtotime($checkin));
$checkout_db = date('Y-m-d', strtotime($checkout));

// Query untuk mencari hotel berdasarkan lokasi dan kamar yang tersedia
$query = "SELECT 
            h.id, 
            h.nama, 
            h.alamat, 
            h.rating, 
            h.gambar,
            r.id as room_id,
            r.room_type,
            r.price,
            r.available_rooms,
            r.max_guests,
            (SELECT COUNT(*) FROM bookings b 
             WHERE b.room_id = r.id 
             AND b.check_in <= ? 
             AND b.check_out >= ? 
             AND b.status NOT IN ('cancelled')) as booked_rooms
          FROM hotels h
          JOIN rooms r ON h.id = r.hotel_id
          WHERE h.alamat LIKE ? 
          AND r.is_available = 1
          ORDER BY h.rating DESC";

$stmt = $conn->prepare($query);
$lokasi_param = "%$lokasi%";
$stmt->bind_param("sss", $checkout_db, $checkin_db, $lokasi_param);
$stmt->execute();
$result = $stmt->get_result();

// Format data hotel
$hotels = [];
while ($row = $result->fetch_assoc()) {
    $hotel_id = $row['id'];
    if (!isset($hotels[$hotel_id])) {
        $hotels[$hotel_id] = [
            'id' => $row['id'],
            'nama' => $row['nama'],
            'alamat' => $row['alamat'],
            'rating' => $row['rating'],
            'gambar' => $row['gambar'],
            'rooms' => []
        ];
    }
    
    // Hitung kamar yang tersedia
    $available = $row['available_rooms'] - $row['booked_rooms'];
    if ($available > 0 && $row['max_guests'] >= $tamu) {
        $hotels[$hotel_id]['rooms'][] = [
            'id' => $row['room_id'],
            'type' => $row['room_type'],
            'price' => $row['price'],
            'available' => $available
        ];
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Purqon - Hasil Pencarian Hotel</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #fff;
    }

    .topbar {
      background: #000;
      color: #fff;
      padding: 5px 20px;
      font-size: 14px;
    }

    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      border-bottom: 1px solid #ccc;
    }

    .navbar h1 {
      margin: 0;
      color: #000;
      font-size: 20px;
    }

    .navbar h1 span {
      color: #f36;
    }

    .navbar .buttons a {
      padding: 8px 14px;
      text-decoration: none;
      margin-left: 10px;
      border-radius: 5px;
      font-size: 12px;
    }

    .btn-login {
      border: 1px solid #f36;
      color: #f36;
    }

    .btn-register {
      background: #f36;
      color: #fff;
    }

    .banner {
      width: 100%;
      height: 250px;
      background: url('Hotel.jpg') center center/cover no-repeat;
    }

    .hotel-section {
      background: #fff;
      padding: 30px;
    }

    .hotel-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 30px;
      justify-items: center;
    }

    .hotel-card {
      border: 5px solid #f36;
      border-radius: 40px;
      width: 300px;
      padding: 20px;
      text-align: center;
    }

    .hotel-card img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 10px;
    }

    .hotel-name {
      font-weight: bold;
      font-size: 18px;
      margin-bottom: 5px;
    }

    .alamat {
      font-size: 12px;
      color: #333;
      margin-bottom: 10px;
    }

    .rating {
      font-size: 14px;
      margin-bottom: 10px;
    }

    .stars {
      color: gold;
    }

    .pesan-btn {
      display: inline-block;
      background: #f36;
      color: white;
      padding: 8px 20px;
      border: none;
      border-radius: 25px;
      font-weight: bold;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="topbar">&nbsp;</div>

  <div class="navbar">
    <h1><strong>PURQON.<span>COM</span></strong></h1>
    <div class="buttons">
      <?php if (isset($_SESSION['user'])): ?>
        <a href="logout.php" class="btn-register">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn-login">Masuk</a>
        <a href="register.php" class="btn-register">Daftar</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="banner"></div>

  <div class="hotel-section">
    <div class="hotel-grid">
      <?php if (empty($hotels)): ?>
        <div class="hotel-card">
          <div class="hotel-name">Tidak ada hotel yang ditemukan</div>
          <div class="alamat">Silakan coba dengan kriteria pencarian yang berbeda</div>
          <a href="index.php" class="pesan-btn">Cari Lagi</a>
        </div>
      <?php else: ?>
        <?php foreach ($hotels as $hotel): ?>
          <?php if (!empty($hotel['rooms'])): ?>
            <div class="hotel-card">
              <img src="<?= htmlspecialchars($hotel['gambar'] ?? 'default_hotel.jpg') ?>" alt="<?= htmlspecialchars($hotel['nama']) ?>">
              <div class="hotel-name"><?= htmlspecialchars($hotel['nama']) ?></div>
              <div class="alamat"><?= htmlspecialchars($hotel['alamat']) ?></div>
              <div class="rating">Rating:<br><span class="stars">
                  <?php 
                  $full_stars = floor($hotel['rating']);
                  $half_star = ($hotel['rating'] - $full_stars) >= 0.5 ? 1 : 0;
                  echo str_repeat('★', $full_stars);
                  echo $half_star ? '½' : '';
                  ?>
              </span> <?= number_format($hotel['rating'], 1) ?></div>
              
              <?php foreach ($hotel['rooms'] as $room): ?>
                <div style="margin: 10px 0; padding: 10px; border-top: 1px dashed #ccc;">
                  <div><strong><?= htmlspecialchars($room['type']) ?></strong></div>
                  <div>Rp <?= number_format($room['price'], 0, ',', '.') ?> /malam</div>
                  <div>Tersedia: <?= $room['available'] ?> kamar</div>
                  <a href="booking.php?hotel_id=<?= $hotel['id'] ?>&room_id=<?= $room['id'] ?>&checkin=<?= $checkin ?>&checkout=<?= $checkout ?>&tamu=<?= $tamu ?>" class="pesan-btn">Pesan</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html> 