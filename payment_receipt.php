<?php
include 'db_connect.php';
session_start();

// Pastikan booking_id tersedia
if (!isset($_GET['booking_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = (int)$_GET['booking_id'];

// Ambil data booking
$stmt_booking = $conn->prepare("
    SELECT b.*, h.nama AS hotel_name, h.alamat AS hotel_address, 
           r.room_type, r.price AS room_price, r.max_guests
    FROM bookings b
    JOIN hotels h ON b.hotel_id = h.id
    JOIN rooms r ON b.room_id = r.id
    WHERE b.id = ?
");
$stmt_booking->bind_param("i", $booking_id);
$stmt_booking->execute();
$booking = $stmt_booking->get_result()->fetch_assoc();
$stmt_booking->close();

// Ambil data pembayaran
$stmt_payment = $conn->prepare("SELECT * FROM payments WHERE booking_id = ?");
$stmt_payment->bind_param("i", $booking_id);
$stmt_payment->execute();
$payment = $stmt_payment->get_result()->fetch_assoc();
$stmt_payment->close();

// Validasi jika data tidak ditemukan
if (!$booking || !$payment) {
    echo "<script>alert('Data booking atau pembayaran tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

// Hitung jumlah malam
$checkin = new DateTime($booking['check_in']);
$checkout = new DateTime($booking['check_out']);
$nights = $checkout->diff($checkin)->days;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Bukti Pembayaran - Booking #<?= $booking_id ?></title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 20px;
      color: #333;
    }
    .receipt-container {
      max-width: 800px;
      margin: 0 auto;
      background-color: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid #eee;
    }
    .header h1 {
      color: #f36;
      margin-bottom: 5px;
    }
    .header p {
      color: #777;
      margin-top: 0;
    }
    .booking-info, .payment-info {
      margin-bottom: 30px;
    }
    .section-title {
      color: #f36;
      border-bottom: 2px solid #f36;
      padding-bottom: 5px;
      margin-bottom: 15px;
    }
    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }
    .info-item {
      margin-bottom: 10px;
    }
    .info-label {
      font-weight: bold;
      color: #555;
      display: block;
      margin-bottom: 3px;
    }
    .info-value {
      color: #333;
    }
    .price-details {
      background-color: #f9f9f9;
      padding: 15px;
      border-radius: 8px;
      margin-top: 20px;
    }
    .price-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #eee;
    }
    .price-row:last-child {
      border-bottom: none;
      font-weight: bold;
      font-size: 1.1em;
    }
    .status {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 20px;
      font-weight: bold;
      font-size: 0.9em;
    }
    .status-pending {
      background-color: #FFF3CD;
      color: #856404;
    }
    .status-completed {
      background-color: #D4EDDA;
      color: #155724;
    }
    .status-failed {
      background-color: #F8D7DA;
      color: #721C24;
    }
    .actions {
      margin-top: 30px;
      text-align: center;
    }
    .btn {
      display: inline-block;
      padding: 10px 20px;
      background-color: #f36;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      margin: 0 10px;
      font-weight: bold;
    }
    .btn:hover {
      background-color: #d22b5d;
    }
    .btn-print {
      background-color: #6c757d;
    }
    .btn-print:hover {
      background-color: #5a6268;
    }
    .footer {
      text-align: center;
      margin-top: 30px;
      color: #777;
      font-size: 0.9em;
    }
    @media print {
      body {
        background-color: #fff;
        padding: 0;
      }
      .receipt-container {
        box-shadow: none;
      }
      .actions {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="receipt-container">
    <div class="header">
      <h1>Bukti Pembayaran</h1>
      <p>ID Booking: #<?= $booking_id ?></p>
      <p>Tanggal Transaksi: <?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?></p>
    </div>

    <div class="booking-info">
      <h2 class="section-title">Informasi Booking</h2>
      <div class="info-grid">
        <div class="info-item">
          <span class="info-label">Nama Pemesan</span>
          <span class="info-value"><?= htmlspecialchars($booking['name']) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Email</span>
          <span class="info-value"><?= htmlspecialchars($booking['email']) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Hotel</span>
          <span class="info-value"><?= htmlspecialchars($booking['hotel_name']) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Alamat Hotel</span>
          <span class="info-value"><?= htmlspecialchars($booking['hotel_address']) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Tipe Kamar</span>
          <span class="info-value"><?= htmlspecialchars($booking['room_type']) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Jumlah Tamu</span>
          <span class="info-value"><?= $booking['adults'] ?> Dewasa, <?= $booking['children'] ?> Anak</span>
        </div>
        <div class="info-item">
          <span class="info-label">Check-in</span>
          <span class="info-value"><?= date('d/m/Y', strtotime($booking['check_in'])) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Check-out</span>
          <span class="info-value"><?= date('d/m/Y', strtotime($booking['check_out'])) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Status Booking</span>
          <span class="info-value">
            <?php 
              $status_class = str_replace(' ', '-', $booking['status']);
              echo '<span class="status status-'.$status_class.'">'.ucfirst($booking['status']).'</span>';
            ?>
          </span>
        </div>
      </div>
    </div>

    <div class="payment-info">
      <h2 class="section-title">Detail Pembayaran</h2>
      <div class="info-grid">
        <div class="info-item">
          <span class="info-label">Metode Pembayaran</span>
          <span class="info-value"><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Status Pembayaran</span>
          <span class="info-value">
            <?php 
              $payment_status_class = str_replace(' ', '-', $payment['payment_status']);
              echo '<span class="status status-'.$payment_status_class.'">'.ucfirst($payment['payment_status']).'</span>';
            ?>
          </span>
        </div>
        <div class="info-item">
          <span class="info-label">ID Transaksi</span>
          <span class="info-value"><?= $payment['transaction_id'] ? htmlspecialchars($payment['transaction_id']) : '-' ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Tanggal Pembayaran</span>
          <span class="info-value"><?= $payment['payment_date'] ? date('d/m/Y H:i', strtotime($payment['payment_date'])) : '-' ?></span>
        </div>
      </div>

      <div class="price-details">
        <div class="price-row">
          <span>Harga Kamar per Malam</span>
          <span>Rp <?= number_format($booking['room_price'], 0, ',', '.') ?></span>
        </div>
        <div class="price-row">
          <span>Jumlah Malam</span>
          <span><?= $nights ?> Malam</span>
        </div>
        <div class="price-row">
          <span>Total Pembayaran</span>
          <span>Rp <?= number_format($booking['total_price'], 0, ',', '.') ?></span>
        </div>
      </div>
    </div>

    <div class="actions">
      <a href="index.php" class="btn">Kembali ke Beranda</a>
      <a href="javascript:window.print()" class="btn btn-print">Cetak Bukti</a>
      <?php if ($payment['payment_status'] == 'pending'): ?>
        <a href="payment_confirmation.php?booking_id=<?= $booking_id ?>" class="btn">Konfirmasi Pembayaran</a>
      <?php endif; ?>
    </div>

    <div class="footer">
      <p>Terima kasih telah memesan di sistem kami. Untuk pertanyaan lebih lanjut, silakan hubungi customer service.</p>
      <p>&copy; <?= date('Y') ?> Sistem Pemesanan Hotel. All rights reserved.</p>
    </div>
  </div>
</body>
</html>