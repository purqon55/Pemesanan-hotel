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

// Inisialisasi variabel
$guest = [];
$error_msg = '';
$success_msg = '';

// Ambil data tamu berdasarkan ID
if (isset($_GET['id'])) {
    $guest_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $guest_id);
    $stmt->execute();
    $guest = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$guest) {
        die("Data tamu tidak ditemukan");
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $guest_id = $_POST['id'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $nomor_ponsel = $_POST['nomor_ponsel'];
    $nationality = $_POST['nationality'] ?? 'Indonesia';
    $status = $_POST['status'] ?? 'active';

    // Validasi input
    if (empty($nama_lengkap) || empty($email) || empty($nomor_ponsel)) {
        $error_msg = "Semua field wajib diisi!";
    } else {
        // Update data tamu
        $stmt = $conn->prepare("UPDATE users SET 
                                nama_lengkap = ?, 
                                email = ?, 
                                nomor_ponsel = ?, 
                                nationality = ?, 
                                status = ?, 
                                updated_at = NOW() 
                                WHERE id = ?");
        $stmt->bind_param("sssssi", $nama_lengkap, $email, $nomor_ponsel, $nationality, $status, $guest_id);
        
        if ($stmt->execute()) {
            $success_msg = "Data tamu berhasil diperbarui!";
            // Ambil data terbaru
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $guest_id);
            $stmt->execute();
            $guest = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error_msg = "Gagal memperbarui data tamu: " . $conn->error;
        }
    }
}

// Format tanggal
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Tamu | PURQON.COM</title>
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
            max-width: 800px; 
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            padding: 10px 15px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #4CAF50;
        }
        .btn-primary:hover {
            background-color: #45a049;
        }
        .info-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
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
        <h2 class="page-title">Edit Data Tamu</h2>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="edit_guest.php">
            <input type="hidden" name="id" value="<?= htmlspecialchars($guest['id'] ?? '') ?>">
            
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" 
                       value="<?= htmlspecialchars($guest['nama_lengkap'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($guest['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="nomor_ponsel">Nomor Ponsel</label>
                <input type="text" id="nomor_ponsel" name="nomor_ponsel" 
                       value="<?= htmlspecialchars($guest['nomor_ponsel'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="nationality">Kewarganegaraan</label>
                <select id="nationality" name="nationality" required>
                    <option value="Indonesia" <?= (isset($guest['nationality']) && $guest['nationality'] == 'Indonesia' ? 'selected' : '') ?>>Indonesia</option>
                    <option value="Malaysia" <?= (isset($guest['nationality']) && $guest['nationality'] == 'Malaysia' ? 'selected' : '') ?>>Malaysia</option>
                    <option value="Singapore" <?= (isset($guest['nationality']) && $guest['nationality'] == 'Singapore' ? 'selected' : '') ?>>Singapore</option>
                    <option value="Other" <?= (isset($guest['nationality']) && $guest['nationality'] == 'Other' ? 'selected' : '') ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="active" <?= (isset($guest['status']) && $guest['status'] == 'active' ? 'selected' : '') ?>>Active</option>
                    <option value="inactive" <?= (isset($guest['status']) && $guest['status'] == 'inactive' ? 'selected' : '') ?>>Inactive</option>
                    <option value="banned" <?= (isset($guest['status']) && $guest['status'] == 'banned' ? 'selected' : '') ?>>Banned</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Terdaftar Sejak</label>
                <p><?= isset($guest['created_at']) ? formatDate($guest['created_at']) : 'N/A' ?></p>
            </div>
            
            <div class="form-group">
                <label>Terakhir Diupdate</label>
                <p><?= isset($guest['updated_at']) ? formatDate($guest['updated_at']) : 'N/A' ?></p>
            </div>
            
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="manage_guests.php" class="back-btn">Kembali</a>
        </form>
    </div>
</body>
</html>