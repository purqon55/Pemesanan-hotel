<?php
// Aktifkan laporan error untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mulai session HARUS di awal
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

// Fungsi untuk mengambil statistik dengan penanganan error
function ambilStatistik($conn, $tabel, $kolom = 'COUNT(*)') {
    $query = "SELECT $kolom as total FROM $tabel";
    $result = $conn->query($query);
    
    if (!$result) {
        die("Error mengambil data $tabel: " . $conn->error);
    }
    
    return $result->fetch_assoc();
}

// Ambil semua data statistik
try {
    $reservasi = ambilStatistik($conn, 'bookings');
    $kamar = ambilStatistik($conn, 'rooms');
    $users = ambilStatistik($conn, 'users');
    $staff = ambilStatistik($conn, 'staff');
    $hotels = ambilStatistik($conn, 'hotels');
    $pendapatan = ambilStatistik($conn, 'bookings', 'SUM(total_price)');
    
    // Format angka pendapatan
    $pendapatan_format = number_format($pendapatan['total'] ?? 0, 0, ',', '.');
} catch (Exception $e) {
    die("Terjadi error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>ADMIN | PURQON.COM</title>
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
        .welcome-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 4px solid #555;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #555;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        .revenue-card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #555;
            margin-bottom: 30px;
        }
        .revenue-amount {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
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
            padding: 8px 12px;
            background-color: #555;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            margin-right: 5px;
            display: inline-block;
        }
        .btn:hover {
            background-color: #333;
        }
        .admin-info {
            display: flex;
            align-items: center;
        }
        .action-buttons {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }
        .action-btn {
            padding: 15px 25px;
            background-color: #555;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .action-btn:hover {
            background-color: #333;
        }
        .action-btn i {
            margin-right: 8px;
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
        <div class="welcome-section">
            <h3>Selamat datang di Panel Admin Sistem Reservasi Hotel</h3>
            <p>Kami dengan senang menyambut Anda di pusat kendali layanan reservasi kami. Melalui halaman ini, Anda dapat dengan mudah mengelola data pemesanan, kamar, pelanggan, serta memantau operational hotel secara realtime. Mari bersama-sama memberikan pelayanan terbaik bagi para tamu melalui sistem yang terintegrasi dan profesional.</p>
        </div>
        
        <div class="action-buttons">
            <a href="manage_bookings.php" class="action-btn"><i>📋</i> Kelola Pemesanan</a>
            <a href="manage_rooms.php" class="action-btn"><i>🛏️</i> Kelola Kamar</a>
            <a href="manage_guests.php" class="action-btn"><i>👥</i> Kelola Tamu</a>
            <a href="manage_staff.php" class="action-btn"><i>👔</i> Kelola Staff</a>
            <a href="manage_payments.php" class="action-btn"><i>💰</i> Kelola Pembayaran</a>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?= $reservasi['total'] ?? 0 ?></div>
                <div class="stat-label">Jumlah Reservasi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $kamar['total'] ?? 0 ?></div>
                <div class="stat-label">Jumlah Kamar</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $users['total'] ?? 0 ?></div>
                <div class="stat-label">Jumlah Tamu</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $staff['total'] ?? 0 ?></div>
                <div class="stat-label">Jumlah Staff</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $hotels['total'] ?? 0 ?></div>
                <div class="stat-label">Jumlah Hotel</div>
            </div>
        </div>
        
        <div class="revenue-card">
            <div class="stat-label">Total Pendapatan</div>
            <div class="revenue-amount">Rp.<?= $pendapatan_format ?></div>
        </div>
        
        <h3>Daftar Hotel</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Hotel</th>
                    <th>Alamat</th>
                    <th>Rating</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $hotels = $conn->query("SELECT * FROM hotels");
                if ($hotels && $hotels->num_rows > 0) {
                    while ($hotel = $hotels->fetch_assoc()): 
                ?>
                <tr>
                    <td><?= $hotel['id'] ?></td>
                    <td><?= htmlspecialchars($hotel['nama']) ?></td>
                    <td><?= htmlspecialchars($hotel['alamat']) ?></td>
                    <td><?= $hotel['rating'] ?></td>
                    <td>
                        <a href="manage_rooms.php?hotel_id=<?= $hotel['id'] ?>" class="btn">Kamar</a>
                        <a href="manage_staff.php?hotel_id=<?= $hotel['id'] ?>" class="btn">Staff</a>
                        <a href="manage_bookings.php?hotel_id=<?= $hotel['id'] ?>" class="btn">Pemesanan</a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                } else {
                    echo "<tr><td colspan='5'>Tidak ada data hotel</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>