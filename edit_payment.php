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

// Ambil data pembayaran berdasarkan ID
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$payment_data = null;

if ($payment_id > 0) {
    // Ambil data pembayaran beserta informasi booking terkait
    $query_payment = "SELECT p.*, b.name, b.email, b.phone, h.nama as hotel_name 
                     FROM payments p
                     JOIN bookings b ON p.booking_id = b.id
                     JOIN hotels h ON b.hotel_id = h.id
                     WHERE p.id = ?";
    $stmt = $conn->prepare($query_payment);
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $payment_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment_data) {
        die("Data pembayaran tidak ditemukan");
    }
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = intval($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $payment_status = $_POST['payment_status'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Update data pembayaran
    $query = "UPDATE payments SET 
              amount = ?, 
              payment_method = ?, 
              payment_status = ?, 
              notes = ?,
              updated_at = CURRENT_TIMESTAMP 
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssi", 
        $amount, 
        $payment_method, 
        $payment_status, 
        $notes,
        $payment_id
    );

    if ($stmt->execute()) {
        header("Location: manage_payments.php");
        exit();
    } else {
        $error = "Gagal menyimpan data pembayaran: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Pembayaran | ADMIN PURQON.COM</title>
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
        .payment-info {
            margin-bottom: 30px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 4px solid #555;
        }
        .payment-info h3 {
            margin-top: 0;
            color: #555;
        }
        .payment-info p {
            margin: 5px 0;
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
            margin-top: 5px;
        }
        .page-title {
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .user-info {
            margin-top: 20px;
            font-style: italic;
            text-align: right;
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
        <h2 class="page-title">Edit Pembayaran</h2>
        
        <?php if (isset($error)): ?>
            <div style="color: red; margin-bottom: 20px;"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="payment-info">
            <h3>Informasi Pembayaran</h3>
            <p><strong>Booking ID:</strong> <?= $payment_data['booking_id'] ?></p>
            <p><strong>Nama Pelanggan:</strong> <?= htmlspecialchars($payment_data['name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($payment_data['email']) ?></p>
            <p><strong>Telepon:</strong> <?= htmlspecialchars($payment_data['phone']) ?></p>
            <p><strong>Hotel:</strong> <?= htmlspecialchars($payment_data['hotel_name']) ?></p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" required 
                       value="<?= $payment_data['amount'] ?>">
            </div>
            
            <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="transfer" <?= $payment_data['payment_method'] === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                    <option value="cash" <?= $payment_data['payment_method'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="credit_card" <?= $payment_data['payment_method'] === 'credit_card' ? 'selected' : '' ?>>Credit Card</option>
                    <option value="debit_card" <?= $payment_data['payment_method'] === 'debit_card' ? 'selected' : '' ?>>Debit Card</option>
                    <option value="e_wallet" <?= $payment_data['payment_method'] === 'e_wallet' ? 'selected' : '' ?>>E-Wallet</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="payment_status">Status</label>
                <select id="payment_status" name="payment_status" required>
                    <option value="pending" <?= $payment_data['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= $payment_data['payment_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="failed" <?= $payment_data['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                    <option value="refunded" <?= $payment_data['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes"><?= htmlspecialchars($payment_data['notes'] ?? '') ?></textarea>
            </div>
            
            <div class="form-actions">
                <a href="manage_payments.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
        
        <div class="user-info">
            <p>Manager: <?= htmlspecialchars($admin_data['full_name']) ?></p>
            <p>User: <?= htmlspecialchars($payment_data['name']) ?></p>
        </div>
    </div>
</body>
</html> 