<?php
include 'db_connect.php';
session_start();

// Validasi input
$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

// Ambil data hotel
$stmt_hotel = $conn->prepare("SELECT nama, alamat FROM hotels WHERE id = ?");
$stmt_hotel->bind_param("i", $hotel_id);
$stmt_hotel->execute();
$result_hotel = $stmt_hotel->get_result();
$hotel = $result_hotel->fetch_assoc();
$stmt_hotel->close();

// Ambil data kamar
$stmt_room = $conn->prepare("SELECT room_type, available_rooms, price FROM rooms WHERE id = ?");
$stmt_room->bind_param("i", $room_id);
$stmt_room->execute();
$result_room = $stmt_room->get_result();
$room = $result_room->fetch_assoc();
$stmt_room->close();

// Proses form booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($room && $room['available_rooms'] > 0) {
        // Ambil data dari form
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $guests = (int)$_POST['guests'];
        $checkin = $_POST['checkin'];
        $checkout = $_POST['checkout'];
        
        // Hitung total harga (contoh: harga per malam)
        $checkin_date = new DateTime($checkin);
        $checkout_date = new DateTime($checkout);
        $interval = $checkin_date->diff($checkout_date);
        $nights = $interval->days;
        $total_price = $room['price'] * $nights;
        
        // Cek apakah user login (jika ada sistem login)
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        // Insert data booking
        $stmt_booking = $conn->prepare("INSERT INTO bookings (user_id, hotel_id, room_id, name, email, check_in, check_out, adults, total_price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt_booking->bind_param("iiissssii", $user_id, $hotel_id, $room_id, $full_name, $email, $checkin, $checkout, $guests, $total_price);
        
        if ($stmt_booking->execute()) {
            $booking_id = $conn->insert_id;
            
            // Update ketersediaan kamar
            $stmt_update = $conn->prepare("UPDATE rooms SET available_rooms = available_rooms - 1 WHERE id = ?");
            $stmt_update->bind_param("i", $room_id);
            $stmt_update->execute();
            $stmt_update->close();
            
            // Insert data pembayaran dengan status pending
            $stmt_payment = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, payment_status, created_at) VALUES (?, ?, 'pending', 'pending', NOW())");
            $stmt_payment->bind_param("ii", $booking_id, $total_price);
            $stmt_payment->execute();
            $stmt_payment->close();
            
            $stmt_booking->close();
            
echo "<script>alert('Booking berhasil! ID Booking: #" . $booking_id . "'); window.location.href='payment_receipt.php?booking_id=" . $booking_id . "';</script>";            exit;
        } else {
            echo "<script>alert('Gagal menyimpan booking: " . $conn->error . "');</script>";
        }
    } else {
        echo "<script>alert('Kamar tidak tersedia!');</script>";
    }
}

// Validasi jika data hotel atau room tidak ditemukan
if (!$hotel || !$room) {
    echo "<script>alert('Data hotel atau kamar tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Form Booking - <?= htmlspecialchars($hotel['nama']) ?></title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f8f9ff;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 600px;
      margin: 40px auto;
      background-color: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      margin-top: 0;
      color: #333;
      text-align: center;
    }
    label {
      font-weight: 600;
      display: block;
      margin-top: 15px;
      color: #444;
    }
    input[type="text"], input[type="email"], input[type="number"], input[type="date"], select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
      box-sizing: border-box;
    }
    button {
      background-color: #f36;
      color: #fff;
      padding: 12px 20px;
      border: none;
      border-radius: 25px;
      font-weight: bold;
      cursor: pointer;
      margin-top: 20px;
      width: 100%;
    }
    button:hover {
      background-color: #d22b5d;
    }
    .info-box {
      background: #f9f9f9;
      padding: 15px;
      border-radius: 8px;
      margin-top: 20px;
      border: 1px solid #eee;
    }
    .price-info {
      background: #e8f5e8;
      padding: 15px;
      border-radius: 8px;
      margin-top: 15px;
      border: 1px solid #c8e6c9;
    }
    .back-btn {
      display: inline-block;
      background-color: #666;
      color: white;
      padding: 8px 15px;
      text-decoration: none;
      border-radius: 4px;
      margin-bottom: 20px;
    }
    .back-btn:hover {
      background-color: #555;
    }
    .required {
      color: red;
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="javascript:history.back()" class="back-btn">← Kembali</a>
    
    <h2>Form Booking</h2>
    <div class="info-box">
      <strong>Hotel:</strong> <?= htmlspecialchars($hotel['nama']) ?><br>
      <strong>Alamat:</strong> <?= htmlspecialchars($hotel['alamat']) ?><br>
      <strong>Tipe Kamar:</strong> <?= htmlspecialchars($room['room_type']) ?><br>
      <strong>Kamar Tersedia:</strong> <?= htmlspecialchars($room['available_rooms']) ?>
    </div>

    <div class="price-info">
      <strong>Harga per malam:</strong> Rp <?= number_format($room['price'], 0, ',', '.') ?>
    </div>

    <form method="POST" id="bookingForm">
      <label>Nama Lengkap <span class="required">*</span></label>
      <input type="text" name="full_name" required>

      <label>Email <span class="required">*</span></label>
      <input type="email" name="email" required>

      <label>Jumlah Tamu <span class="required">*</span></label>
      <input type="number" name="guests" min="1" max="<?= $room['max_guests'] ?? 4 ?>" required>

      <label>Tanggal Check-in <span class="required">*</span></label>
      <input type="date" name="checkin" required min="<?= date('Y-m-d') ?>">

      <label>Tanggal Check-out <span class="required">*</span></label>
      <input type="date" name="checkout" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">

      <div id="totalPrice" class="price-info" style="display: none;">
        <strong>Total Harga: <span id="priceAmount">Rp 0</span></strong>
        <br><small>Berdasarkan <span id="totalNights">0</span> malam</small>
      </div>

      <button type="submit" onclick="return confirmBooking()">Konfirmasi Booking</button>
    </form>
  </div>

  <script>
    const pricePerNight = <?= $room['price'] ?>;
    const checkinInput = document.querySelector('input[name="checkin"]');
    const checkoutInput = document.querySelector('input[name="checkout"]');
    const totalPriceDiv = document.getElementById('totalPrice');
    const priceAmountSpan = document.getElementById('priceAmount');
    const totalNightsSpan = document.getElementById('totalNights');

    function calculatePrice() {
      const checkin = new Date(checkinInput.value);
      const checkout = new Date(checkoutInput.value);
      
      if (checkin && checkout && checkout > checkin) {
        const timeDiff = checkout.getTime() - checkin.getTime();
        const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
        const totalPrice = nights * pricePerNight;
        
        totalNightsSpan.textContent = nights;
        priceAmountSpan.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
        totalPriceDiv.style.display = 'block';
      } else {
        totalPriceDiv.style.display = 'none';
      }
    }

    checkinInput.addEventListener('change', calculatePrice);
    checkoutInput.addEventListener('change', calculatePrice);

    // Validasi tanggal
    checkinInput.addEventListener('change', function() {
      const checkinDate = new Date(this.value);
      const minCheckout = new Date(checkinDate);
      minCheckout.setDate(minCheckout.getDate() + 1);
      checkoutInput.min = minCheckout.toISOString().split('T')[0];
      
      if (checkoutInput.value && new Date(checkoutInput.value) <= checkinDate) {
        checkoutInput.value = '';
      }
    });

    function confirmBooking() {
      const checkin = checkinInput.value;
      const checkout = checkoutInput.value;
      
      if (!checkin || !checkout) {
        alert('Mohon lengkapi tanggal check-in dan check-out!');
        return false;
      }
      
      if (new Date(checkout) <= new Date(checkin)) {
        alert('Tanggal check-out harus setelah tanggal check-in!');
        return false;
      }
      
      return confirm('Apakah Anda yakin ingin melakukan booking ini?');
    }
  </script>
</body>
</html>