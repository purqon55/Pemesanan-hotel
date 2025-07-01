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

// Handle Delete Action
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success_msg = "Tamu berhasil dihapus!";
    } else {
        $error_msg = "Gagal menghapus tamu: " . $conn->error;
    }
    $stmt->close();
    header("Location: manage_guest.php?success=" . urlencode($success_msg ?? '') . "&error=" . urlencode($error_msg ?? ''));
    exit();
}

// Ambil data tamu (users) beserta informasi booking terakhir
$query = "SELECT 
            u.id,
            u.nama_lengkap AS tamu,
            u.email,
            u.nomor_ponsel AS contact,
            u.created_at,
            u.updated_at,
            MAX(b.check_in) AS last_check_in,
            MAX(b.check_out) AS last_check_out,
            COUNT(b.id) AS total_bookings
          FROM users u
          LEFT JOIN bookings b ON u.id = b.user_id
          WHERE u.user_type = 'user'
          GROUP BY u.id
          ORDER BY u.created_at DESC";

$result = $conn->query($query);

// Format tanggal
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Tamu | PURQON.COM</title>
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
            font-size: 14px;
            margin-right: 5px;
            display: inline-block;
        }
        .btn-edit {
            background-color: #4CAF50;
        }
        .btn-view {
            background-color: #2196F3;
        }
        .btn-delete {
            background-color: #f44336;
        }
        .admin-info {
            display: flex;
            align-items: center;
        }
        .page-title {
            border-bottom: 2px solid #555;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 15px;
            background-color: #555;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-btn:hover {
            background-color: #333;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
        .section-title {
            font-size: 20px;
            margin-bottom: 15px;
            color: #555;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            background-color: #777;
            border-radius: 10px;
        }
        .badge-success {
            background-color: #5cb85c;
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
        <div class="section-title">Kelola Tamu</div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>Tamu</th>
                    <th>Kontak</th>
                    <th>Member Sejak</th>
                    <th>Booking Terakhir</th>
                    <th>Total Booking</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result && $result->num_rows > 0) {
                    while ($guest = $result->fetch_assoc()): 
                ?>
                <tr>
                    <td><?= htmlspecialchars($guest['tamu']) ?></td>
                    <td>
                        <?= htmlspecialchars($guest['email']) ?><br>
                        <?= htmlspecialchars($guest['contact']) ?>
                    </td>
                    <td><?= formatDate($guest['created_at']) ?></td>
                    <td>
                        <?php if ($guest['last_check_in']): ?>
                            <?= formatDate($guest['last_check_in']) ?> - <?= formatDate($guest['last_check_out']) ?>
                        <?php else: ?>
                            Belum pernah booking
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($guest['total_bookings'] > 0): ?>
                            <span class="badge badge-success"><?= $guest['total_bookings'] ?></span>
                        <?php else: ?>
                            0
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit_guest.php?id=<?= $guest['id'] ?>" class="btn btn-edit">Edit</a>
                        <a href="view_guest.php?id=<?= $guest['id'] ?>" class="btn btn-view">View</a>
                        <a href="manage_guest.php?delete_id=<?= $guest['id'] ?>" class="btn btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus tamu ini?')">Delete</a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                } else {
                    echo "<tr><td colspan='6'>Tidak ada data tamu</td></tr>";
                }
                ?>
            </tbody>
        </table>
        
        <a href="admin_dashboard.php" class="back-btn">Kembali</a>
    </div>
</body>
</html>