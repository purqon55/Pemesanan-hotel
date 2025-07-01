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

// Hitung total pembayaran berdasarkan data yang ada di tabel
$total_payments = $conn->query("SELECT SUM(amount) as total FROM payments")->fetch_assoc()['total'];
$success_payments = $conn->query("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'completed'")->fetch_assoc()['total'];
$pending_payments = $conn->query("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'pending'")->fetch_assoc()['total'];
$failed_payments = $conn->query("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'failed'")->fetch_assoc()['total'];

// Ambil semua pembayaran untuk ditampilkan
$all_payments_query = "SELECT p.*, b.name, b.email, h.nama as hotel_name 
                      FROM payments p
                      JOIN bookings b ON p.booking_id = b.id
                      JOIN hotels h ON b.hotel_id = h.id
                      ORDER BY p.payment_date DESC";
$all_payments = $conn->query($all_payments_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pembayaran | ADMIN PURQON.COM</title>
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
            max-width: 1200px; 
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
        .stats-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .stat-card {
            text-align: center;
            padding: 15px;
            flex: 1;
            border-right: 1px solid #eee;
        }
        .stat-card:last-child {
            border-right: none;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin: 5px 0;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #555;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .btn {
            padding: 6px 10px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .btn-edit {
            background-color: #4CAF50;
        }
        .btn-delete {
            background-color: #f44336;
        }
        .btn-add {
            background-color: #2196F3;
            padding: 10px 15px;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .section-title {
            font-size: 18px;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #555;
        }
        .action-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .action-btn {
            padding: 10px 15px;
            background-color: #555;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        .action-btn:hover {
            background-color: #333;
        }
        .total-highlight {
            font-weight: bold;
            color: #2c3e50;
        }
        .status-success {
            color: #27ae60;
        }
        .status-pending {
            color: #f39c12;
        }
        .status-failed {
            color: #e74c3c;
        }
        .status-refunded {
            color: #9b59b6;
        }
        .action-cell {
            display: flex;
            gap: 5px;
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
        <div class="action-buttons">
            <a href="admin_dashboard.php" class="action-btn">Dashboard</a>
            <a href="add_payment.php" class="btn btn-add">Tambah Pembayaran</a>
        </div>
        
        <h2 class="section-title">Payments</h2>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number">Rp. <?= number_format($total_payments, 0, ',', '.') ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-number status-success">Rp. <?= number_format($success_payments, 0, ',', '.') ?></div>
                <div class="stat-label">Success</div>
            </div>
            <div class="stat-card">
                <div class="stat-number status-pending">Rp. <?= number_format($pending_payments, 0, ',', '.') ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number status-failed">Rp. <?= number_format($failed_payments, 0, ',', '.') ?></div>
                <div class="stat-label">Failed</div>
            </div>
        </div>
        
        <h3 class="section-title">All Payments</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Booking ID</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Hotel</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Transaction ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($all_payments && $all_payments->num_rows > 0) {
                    while ($payment = $all_payments->fetch_assoc()): 
                        // Calculate totals based on actual displayed data
                        $status_class = '';
                        if ($payment['payment_status'] === 'completed') $status_class = 'status-success';
                        elseif ($payment['payment_status'] === 'pending') $status_class = 'status-pending';
                        elseif ($payment['payment_status'] === 'failed') $status_class = 'status-failed';
                        elseif ($payment['payment_status'] === 'refunded') $status_class = 'status-refunded';
                ?>
                <tr>
                    <td><?= $payment['id'] ?></td>
                    <td><?= $payment['booking_id'] ?></td>
                    <td><?= htmlspecialchars($payment['name']) ?><br><?= htmlspecialchars($payment['email']) ?></td>
                    <td class="total-highlight">Rp. <?= number_format($payment['amount'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($payment['hotel_name']) ?></td>
                    <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></td>
                    <td>
                        <span class="<?= $status_class ?>">
                            <?= ucfirst($payment['payment_status']) ?>
                        </span>
                    </td>
                    <td><?= $payment['payment_date'] ? date('d/m/Y H:i', strtotime($payment['payment_date'])) : '-' ?></td>
                    <td><?= $payment['transaction_id'] ?: '-' ?></td>
                    <td class="action-cell">
                        <a href="edit_payment.php?id=<?= $payment['id'] ?>" class="btn btn-edit">Edit</a>
                        <a href="delete_payment.php?id=<?= $payment['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                } else {
                    echo "<tr><td colspan='10'>No payments found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>