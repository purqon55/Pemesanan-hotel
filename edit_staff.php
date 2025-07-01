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

// Ambil data staff berdasarkan ID
$staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$staff_data = null;
$hotels = [];

if ($staff_id > 0) {
    // Ambil data staff
    $query_staff = "SELECT * FROM staff WHERE id = ?";
    $stmt = $conn->prepare($query_staff);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $staff_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$staff_data) {
        die("Data staff tidak ditemukan");
    }
}

// Ambil semua hotel untuk dropdown
$hotels_result = $conn->query("SELECT id, nama FROM hotels");
if ($hotels_result && $hotels_result->num_rows > 0) {
    while ($hotel = $hotels_result->fetch_assoc()) {
        $hotels[] = $hotel;
    }
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? '';
    $nomor_ponsel = $_POST['nomor_ponsel'] ?? '';
    $jabatan = $_POST['jabatan'] ?? '';
    $gaji = intval($_POST['gaji'] ?? 0);
    $hotel_id = intval($_POST['hotel_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $tanggal_bergabung = $_POST['tanggal_bergabung'] ?? date('Y-m-d');

    if ($staff_id > 0) {
        // Update data staff
        $query = "UPDATE staff SET 
                  nama = ?, 
                  email = ?, 
                  nomor_ponsel = ?, 
                  jabatan = ?, 
                  gaji = ?, 
                  hotel_id = ?, 
                  is_active = ?, 
                  tanggal_bergabung = ?,
                  updated_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssiiisi", 
            $nama, 
            $email, 
            $nomor_ponsel, 
            $jabatan, 
            $gaji, 
            $hotel_id, 
            $is_active, 
            $tanggal_bergabung,
            $staff_id
        );
    } else {
        // Tambah data staff baru
        $query = "INSERT INTO staff (
                  nama, 
                  email, 
                  nomor_ponsel, 
                  jabatan, 
                  gaji, 
                  hotel_id, 
                  is_active, 
                  tanggal_bergabung
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssiiis", 
            $nama, 
            $email, 
            $nomor_ponsel, 
            $jabatan, 
            $gaji, 
            $hotel_id, 
            $is_active, 
            $tanggal_bergabung
        );
    }

    if ($stmt->execute()) {
        header("Location: manage_staff.php");
        exit();
    } else {
        $error = "Gagal menyimpan data staff: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $staff_id > 0 ? 'Edit' : 'Tambah' ?> Staff | ADMIN PURQON.COM</title>
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
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group input[type="checkbox"] {
            margin-right: 10px;
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
        <h2 class="page-title"><?= $staff_id > 0 ? 'Edit Staff' : 'Tambah Staff Baru' ?></h2>
        
        <?php if (isset($error)): ?>
            <div style="color: red; margin-bottom: 20px;"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" required 
                       value="<?= htmlspecialchars($staff_data['nama'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($staff_data['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="nomor_ponsel">Nomor Ponsel</label>
                <input type="tel" id="nomor_ponsel" name="nomor_ponsel" required 
                       value="<?= htmlspecialchars($staff_data['nomor_ponsel'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="jabatan">Jabatan</label>
                <input type="text" id="jabatan" name="jabatan" required 
                       value="<?= htmlspecialchars($staff_data['jabatan'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="gaji">Gaji</label>
                <input type="number" id="gaji" name="gaji" required 
                       value="<?= $staff_data['gaji'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label for="hotel_id">Hotel</label>
                <select id="hotel_id" name="hotel_id" required>
                    <option value="">Pilih Hotel</option>
                    <?php foreach ($hotels as $hotel): ?>
                        <option value="<?= $hotel['id'] ?>" 
                            <?= isset($staff_data['hotel_id']) && $staff_data['hotel_id'] == $hotel['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($hotel['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tanggal_bergabung">Tanggal Bergabung</label>
                <input type="date" id="tanggal_bergabung" name="tanggal_bergabung" required 
                       value="<?= $staff_data['tanggal_bergabung'] ?? date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" 
                        <?= isset($staff_data['is_active']) && $staff_data['is_active'] ? 'checked' : '' ?>>
                    Aktif
                </label>
            </div>
            
            <div class="form-actions">
                <a href="manage_staff.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</body>
</html>