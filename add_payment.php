<?php
// Aktifkan laporan error untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mulai session
session_start();

// Cek apakah admin sudah login
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Sambungkan ke database
require 'db_connect.php';

// Cek koneksi database
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Ambil data admin
$admin_username = $_SESSION['admin'];
$query_admin = "SELECT * FROM admin WHERE username = ?";
$stmt = $conn->prepare($query_admin);
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$admin_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin_data) {
    die("Data admin tidak ditemukan");
}

// Ambil daftar booking yang belum memiliki pembayaran
$available_bookings = [];
$query_bookings = "SELECT b.id, b.name, b.email, h.nama as hotel_name, b.total_price 
                  FROM bookings b
                  JOIN hotels h ON b.hotel_id = h.id
                  LEFT JOIN payments p ON b.id = p.booking_id
                  WHERE p.id IS NULL
                  ORDER BY b.created_at DESC";
$result = $conn->query($query_bookings);
if ($result && $result->num_rows > 0) {
    $available_bookings = $result->fetch_all(MYSQLI_ASSOC);
}

// Proses form submission
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $amount = intval($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $payment_status = $_POST['payment_status'] ?? '';
    $transaction_id = $_POST['transaction_id'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d H:i:s');

    // Validasi input
    if ($booking_id <= 0) {
        $error = "Pilih booking yang valid";
    } elseif ($amount <= 0) {
        $error = "Jumlah pembayaran harus lebih dari 0";
    } elseif (empty($payment_method) || empty($payment_status)) {
        $error = "Metode pembayaran dan status harus diisi";
    } else {
        // Insert data pembayaran baru
        $query = "INSERT INTO payments (
                  booking_id, 
                  amount, 
                  payment_method, 
                  payment_status, 
                  payment_date,
                  transaction_id,
                  notes,
                  created_at,
                  updated_at
              ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iisssss", 
            $booking_id,
            $amount,
            $payment_method,
            $payment_status,
            $payment_date,
            $transaction_id,
            $notes
        );

        if ($stmt->execute()) {
            header("Location: manage_payments.php");
            exit();
        } else {
            $error = "Gagal menambahkan pembayaran: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Pembayaran | ADMIN PURQON.COM</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: #f5f5f5; 
            color: #333;
        }
        .header { 
            background-color: #333; 
            color: white; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: normal;
        }
        .container { 
            max-width: 600px; 
            margin: 20px auto; 
            padding: 20px; 
            background: white; 
            border-radius: 5px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }
        .logout { 
            color: white; 
            text-decoration: none;
            background-color: #555;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            margin-left: 15px;
        }
        .logout:hover {
            background-color: #333;
        }
        .admin-info {
            display: flex;
            align-items: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="number"],
        .form-group input[type="text"],
        .form-group input[type="datetime-local"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .form-actions {
            margin-top: 30px;
            text-align: right;
        }
        .btn {
            padding: 10px 20px;
            background-color: #555;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }
        .btn:hover {
            background-color: #333;
        }
        .btn-primary {
            background-color: #4CAF50;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .error {
            color: #dc3545;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 4px;
        }
        .page-title {
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .booking-info {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 4px solid #555;
        }
        .booking-info p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>ADMIN</h1>
            <h2>PURQON.COM</h2>
        </div>
        <div class="admin-info">
            <span><?= htmlspecialchars($admin_data['full_name']) ?></span>
            <a href="admin_logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h2 class="page-title">Tambah Pembayaran Baru</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="booking_id">Booking</label>
                <select id="booking_id" name="booking_id" required onchange="updateBookingInfo(this)">
                    <option value="">-- Pilih Booking --</option>
                    <?php foreach ($available_bookings as $booking): ?>
                        <option value="<?= $booking['id'] ?>" 
                                data-hotel="<?= htmlspecialchars($booking['hotel_name']) ?>"
                                data-customer="<?= htmlspecialchars($booking['name']) ?>"
                                data-email="<?= htmlspecialchars($booking['email']) ?>"
                                data-total="<?= $booking['total_price'] ?>">
                            Booking #<?= $booking['id'] ?> - <?= htmlspecialchars($booking['name']) ?> (<?= htmlspecialchars($booking['hotel_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="booking-info" class="booking-info" style="display: none;">
                <h3>Informasi Booking</h3>
                <p><strong>Pelanggan:</strong> <span id="customer-name"></span></p>
                <p><strong>Email:</strong> <span id="customer-email"></span></p>
                <p><strong>Hotel:</strong> <span id="hotel-name"></span></p>
                <p><strong>Total Harga:</strong> Rp. <span id="total-price"></span></p>
            </div>
            
            <div class="form-group">
                <label for="amount">Jumlah Pembayaran (Rp)</label>
                <input type="number" id="amount" name="amount" required min="1">
            </div>
            
            <div class="form-group">
                <label for="payment_method">Metode Pembayaran</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="">-- Pilih Metode --</option>
                    <option value="transfer">Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="debit_card">Debit Card</option>
                    <option value="e_wallet">E-Wallet</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="payment_status">Status Pembayaran</label>
                <select id="payment_status" name="payment_status" required>
                    <option value="">-- Pilih Status --</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="payment_date">Tanggal Pembayaran</label>
                <input type="datetime-local" id="payment_date" name="payment_date" 
                       value="<?= date('Y-m-d\TH:i') ?>">
            </div>
            
            <div class="form-group">
                <label for="transaction_id">ID Transaksi (Opsional)</label>
                <input type="text" id="transaction_id" name="transaction_id">
            </div>
            
            <div class="form-group">
                <label for="notes">Catatan (Opsional)</label>
                <textarea id="notes" name="notes"></textarea>
            </div>
            
            <div class="form-actions">
                <a href="manage_payments.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
            </div>
        </form>
    </div>

    <script>
        function updateBookingInfo(select) {
            const bookingInfo = document.getElementById('booking-info');
            const selectedOption = select.options[select.selectedIndex];
            
            if (select.value) {
                document.getElementById('customer-name').textContent = selectedOption.getAttribute('data-customer');
                document.getElementById('customer-email').textContent = selectedOption.getAttribute('data-email');
                document.getElementById('hotel-name').textContent = selectedOption.getAttribute('data-hotel');
                document.getElementById('total-price').textContent = parseInt(selectedOption.getAttribute('data-total')).toLocaleString('id-ID');
                bookingInfo.style.display = 'block';
                
                // Set default amount to total price
                document.getElementById('amount').value = selectedOption.getAttribute('data-total');
            } else {
                bookingInfo.style.display = 'none';
            }
        }
    </script>
</body>
</html>