
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

require 'db_connect.php';

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

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
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success_msg = "Kamar berhasil dihapus!";
    } else {
        $error_msg = "Gagal menghapus kamar: " . $conn->error;
    }
    $stmt->close();
    header("Location: manage_rooms.php?success=" . urlencode($success_msg ?? '') . "&error=" . urlencode($error_msg ?? ''));
    exit();
}

// Handle Update Status
if (isset($_POST['update_room'])) {
    $room_id = $_POST['room_id'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE rooms SET is_available = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_available, $room_id);
    if ($stmt->execute()) {
        $success_msg = "Status kamar berhasil diperbarui!";
    } else {
        $error_msg = "Gagal memperbarui status kamar: " . $conn->error;
    }
    $stmt->close();
    header("Location: manage_rooms.php?success=" . urlencode($success_msg ?? '') . "&error=" . urlencode($error_msg ?? ''));
    exit();
}

// Ambil data kamar dengan informasi hotel
$query = "SELECT 
            r.id,
            h.nama AS hotel,
            r.room_type AS tipe_kamar,
            r.available_rooms AS jumlah_kamar,
            r.price AS harga,
            r.is_available,
            r.max_guests,
            r.has_ac,
            r.has_tv,
            r.has_wifi,
            r.has_breakfast,
            r.gambar
          FROM rooms r
          JOIN hotels h ON r.hotel_id = h.id
          ORDER BY h.nama, r.room_type";

$result = $conn->query($query);

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kamar | PURQON.COM</title>
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
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .room-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .room-card:hover {
            transform: translateY(-5px);
        }
        .room-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .room-content {
            padding: 15px;
        }
        .room-card h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .room-info {
            margin-bottom: 10px;
            display: flex;
        }
        .room-info strong {
            display: inline-block;
            width: 120px;
            color: #555;
        }
        .room-info span {
            flex: 1;
        }
        .room-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .btn {
            padding: 8px 12px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-edit {
            background-color: #4CAF50;
        }
        .btn-edit:hover {
            background-color: #3e8e41;
        }
        .btn-delete {
            background-color: #f44336;
        }
        .btn-delete:hover {
            background-color: #d32f2f;
        }
        .btn-toggle {
            background-color: #2196F3;
        }
        .btn-toggle:hover {
            background-color: #0b7dda;
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
            transition: background-color 0.3s;
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
        .facility-icon {
            display: inline-block;
            margin-right: 10px;
            font-size: 16px;
            color: #555;
        }
        .available {
            color: #4CAF50;
            font-weight: bold;
        }
        .unavailable {
            color: #f44336;
            font-weight: bold;
        }
        .facilities-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .facility-badge {
            background-color: #f0f0f0;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
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
        <div class="page-title">
            <h2>Kelola Kamar</h2>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        
        <div class="room-grid">
            <?php 
            if ($result && $result->num_rows > 0) {
                while ($room = $result->fetch_assoc()): 
                    $status_class = $room['is_available'] ? 'available' : 'unavailable';
                    $status_text = $room['is_available'] ? 'Tersedia' : 'Tidak Tersedia';
                    $image_path = !empty($room['gambar']) ? $room['gambar'] : 'default_room.jpg';
            ?>
            <div class="room-card">
                <div class="room-content">
                    <h3><?= htmlspecialchars($room['hotel']) ?></h3>
                    <div class="room-info">
                        <strong>Tipe Kamar:</strong>
                        <span><?= htmlspecialchars($room['tipe_kamar']) ?></span>
                    </div>
                    <div class="room-info">
                        <strong>Jumlah Kamar:</strong>
                        <span><?= htmlspecialchars($room['jumlah_kamar']) ?></span>
                    </div>
                    <div class="room-info">
                        <strong>Harga:</strong>
                        <span><?= formatCurrency($room['harga']) ?></span>
                    </div>
                    <div class="room-info">
                        <strong>Status:</strong>
                        <span class="<?= $status_class ?>"><?= $status_text ?></span>
                    </div>
                    <div class="room-info">
                        <strong>Kapasitas:</strong>
                        <span><?= htmlspecialchars($room['max_guests']) ?> orang</span>
                    </div>
                    
                    <div class="facilities-container">
                        <?php if ($room['has_ac']): ?>
                            <div class="facility-badge">AC</div>
                        <?php endif; ?>
                        <?php if ($room['has_tv']): ?>
                            <div class="facility-badge">TV</div>
                        <?php endif; ?>
                        <?php if ($room['has_wifi']): ?>
                            <div class="facility-badge">WiFi</div>
                        <?php endif; ?>
                        <?php if ($room['has_breakfast']): ?>
                            <div class="facility-badge">Sarapan</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="room-actions">
                        <form method="POST" action="manage_rooms.php" style="display: inline;">
                            <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                            <input type="hidden" name="is_available" value="<?= $room['is_available'] ? 0 : 1 ?>">
                            <button type="submit" name="update_room" class="btn btn-toggle">
                                <?= $room['is_available'] ? 'Tandai Tidak Tersedia' : 'Tandai Tersedia' ?>
                            </button>
                        </form>
                        <div>
                            <a href="edit_room.php?id=<?= $room['id'] ?>" class="btn btn-edit">Edit</a>
                            <a href="manage_rooms.php?delete_id=<?= $room['id'] ?>" class="btn btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus kamar ini?')">Hapus</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                endwhile;
            } else {
                echo "<p>Tidak ada data kamar</p>";
            }
            ?>
        </div>
        <a href="admin_dashboard.php" class="back-btn">Kembali</a>
    </div>
</body>
</html>