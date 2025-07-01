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
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success_msg = "Pemesanan berhasil dihapus!";
    } else {
        $error_msg = "Gagal menghapus pemesanan: " . $conn->error;
    }
    $stmt->close();
    header("Location: manage_bookings.php?success=" . urlencode($success_msg ?? '') . "&error=" . urlencode($error_msg ?? ''));
    exit();
}

// Handle Update Status
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    if ($stmt->execute()) {
        $success_msg = "Status pemesanan berhasil diperbarui!";
    } else {
        $error_msg = "Gagal memperbarui status: " . $conn->error;
    }
    $stmt->close();
    header("Location: manage_bookings.php?success=" . urlencode($success_msg ?? '') . "&error=" . urlencode($error_msg ?? ''));
    exit();
}

// Ambil data booking dengan informasi pembayaran
$query = "SELECT 
            b.id, 
            b.name AS pelanggan, 
            h.nama AS hotel, 
            r.room_type AS kamar,
            b.check_in,
            b.check_out,
            b.total_price AS amount,
            b.status,
            p.payment_status AS payment
          FROM bookings b
          JOIN hotels h ON b.hotel_id = h.id
          JOIN rooms r ON b.room_id = r.id
          LEFT JOIN payments p ON p.booking_id = b.id
          ORDER BY b.check_in DESC";

$result = $conn->query($query);

// Format tanggal
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Format mata uang
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pemesanan | PURQON.COM</title>
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
            padding: 8px 12px;
            background-color: #555;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            margin-right: 5px;
            display: inline-block;
            border: none;
            cursor: pointer;
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
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 5px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background-color: #45a049;
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
            <h2>Bookings</h2>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        
        <h3>All Bookings</h3>
        
        <table>
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th>Hotel</th>
                    <th>Kamar</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Amount</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result && $result->num_rows > 0) {
                    while ($booking = $result->fetch_assoc()): 
                ?>
                <tr>
                    <td><?= htmlspecialchars($booking['pelanggan']) ?></td>
                    <td><?= htmlspecialchars($booking['hotel']) ?></td>
                    <td><?= htmlspecialchars($booking['kamar']) ?></td>
                    <td><?= formatDate($booking['check_in']) ?></td>
                    <td><?= formatDate($booking['check_out']) ?></td>
                    <td><?= htmlspecialchars($booking['status'] ?? 'pending') ?></td>
                    <td><?= htmlspecialchars($booking['payment'] ?? 'pending') ?></td>
                    <td><?= formatCurrency($booking['amount'] ?? 0) ?></td>
                    <td>
                        <button onclick="openEditModal(<?= $booking['id'] ?>, '<?= $booking['status'] ?? 'pending' ?>')" class="btn btn-edit">Edit</button>
                        <a href="manage_bookings.php?delete_id=<?= $booking['id'] ?>" class="btn btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus pemesanan ini?')">Hapus</a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                } else {
                    echo "<tr><td colspan='9'>Tidak ada data pemesanan</td></tr>";
                }
                ?>
            </tbody>
        </table>
        
        <a href="admin_dashboard.php" class="back-btn">Back</a>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Status Pemesanan</h2>
            <form method="POST" action="manage_bookings.php">
                <input type="hidden" name="booking_id" id="modalBookingId">
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="checked_in">Checked In</option>
                        <option value="checked_out">Checked Out</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <button type="submit" name="update_status" class="submit-btn">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openEditModal(bookingId, currentStatus) {
            document.getElementById('modalBookingId').value = bookingId;
            document.getElementById('status').value = currentStatus;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>