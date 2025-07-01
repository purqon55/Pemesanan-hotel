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

// Ambil data staff
$query_staff = "SELECT * FROM staff";
if (isset($_GET['hotel_id']) && is_numeric($_GET['hotel_id'])) {
    $hotel_id = $_GET['hotel_id'];
    $query_staff = "SELECT * FROM staff WHERE hotel_id = $hotel_id";
}
$staff_result = $conn->query($query_staff);

// Hitung statistik staff
$total_staff = $conn->query("SELECT COUNT(*) as total FROM staff")->fetch_assoc()['total'];
$active_staff = $conn->query("SELECT COUNT(*) as total FROM staff WHERE is_active = 1")->fetch_assoc()['total'];
$inactive_staff = $total_staff - $active_staff;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Staff | ADMIN PURQON.COM</title>
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
            background-color: #555;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
        }
        .btn:hover {
            background-color: #333;
        }
        .btn-edit {
            background-color: #4CAF50;
        }
        .btn-delete {
            background-color: #f44336;
        }
        .btn-call {
            background-color: #2196F3;
        }
        .section-title {
            font-size: 18px;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #555;
        }
        .action-buttons {
            margin-bottom: 20px;
        }
        .action-btn {
            padding: 10px 15px;
            background-color: #555;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            margin-right: 10px;
        }
        .action-btn:hover {
            background-color: #333;
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
            <a href="add_staff.php" class="action-btn">Tambah Staff</a>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?= $total_staff ?></div>
                <div class="stat-label">Total Staff</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $active_staff ?></div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $inactive_staff ?></div>
                <div class="stat-label">Inactive</div>
            </div>
        </div>
        
        <h3 class="section-title">Daftar Staff</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Nomor Ponsel</th>
                    <th>Jabatan</th>
                    <th>Hotel</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($staff_result && $staff_result->num_rows > 0) {
                    while ($staff = $staff_result->fetch_assoc()): 
                        // Ambil nama hotel
                        $hotel_query = "SELECT nama FROM hotels WHERE id = " . $staff['hotel_id'];
                        $hotel_name = $conn->query($hotel_query)->fetch_assoc()['nama'];
                ?>
                <tr>
                    <td><?= $staff['id'] ?></td>
                    <td><?= htmlspecialchars($staff['nama']) ?></td>
                    <td><?= htmlspecialchars($staff['email']) ?></td>
                    <td><?= htmlspecialchars($staff['nomor_ponsel']) ?></td>
                    <td><?= htmlspecialchars($staff['jabatan']) ?></td>
                    <td><?= htmlspecialchars($hotel_name) ?></td>
                    <td><?= $staff['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <a href="edit_staff.php?id=<?= $staff['id'] ?>" class="btn btn-edit">Edit</a>
                        <a href="delete_staff.php?id=<?= $staff['id'] ?>" class="btn btn-delete">Hapus</a>
                        <a href="tel:<?= $staff['nomor_ponsel'] ?>" class="btn btn-call">Call</a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                } else {
                    echo "<tr><td colspan='8'>Tidak ada data staff</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>