<?php
include 'db_connect.php';
session_start();

// Ambil parameter pencarian
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : date('Y-m-d');
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : date('Y-m-d', strtotime('+1 day'));
$location = isset($_GET['location']) ? '%' . $_GET['location'] . '%' : '%%';
$tamu = isset($_GET['tamu']) ? (int)$_GET['tamu'] : 1;

// Query untuk mencari kamar yang tersedia
$query = "SELECT 
            h.id as hotel_id, 
            h.nama as hotel_nama, 
            h.alamat, 
            h.rating, 
            h.gambar as hotel_gambar,
            r.id as room_id,
            r.room_type,
            r.price,
            r.available_rooms,
            r.max_guests,
            r.has_ac,
            r.has_tv,
            r.has_wifi,
            r.has_breakfast,
            r.gambar as room_gambar,
            (SELECT COUNT(*) FROM bookings b 
             WHERE b.room_id = r.id 
             AND b.check_in <= ? 
             AND b.check_out >= ? 
             AND b.status NOT IN ('cancelled')) as booked_rooms
          FROM hotels h
          JOIN rooms r ON h.id = r.hotel_id
          WHERE h.alamat LIKE ? 
          AND r.is_available = 1
          AND r.max_guests >= ?
          ORDER BY h.rating DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("sssi", $checkout, $checkin, $location, $tamu);
$stmt->execute();
$result = $stmt->get_result();

// Format data untuk ditampilkan
$hotels = [];
while ($row = $result->fetch_assoc()) {
    $hotel_id = $row['hotel_id'];
    
    if (!isset($hotels[$hotel_id])) {
        $hotels[$hotel_id] = [
            'id' => $row['hotel_id'],
            'nama' => $row['hotel_nama'],
            'alamat' => $row['alamat'],
            'rating' => $row['rating'],
            'gambar' => $row['hotel_gambar'],
            'rooms' => []
        ];
    }
    
    $available = $row['available_rooms'] - $row['booked_rooms'];
    if ($available > 0) {
        $hotels[$hotel_id]['rooms'][] = [
            'id' => $row['room_id'],
            'type' => $row['room_type'],
            'price' => $row['price'],
            'available' => $available,
            'available_rooms' => $row['available_rooms'],
            'max_guests' => $row['max_guests'],
            'has_ac' => $row['has_ac'],
            'has_tv' => $row['has_tv'],
            'has_wifi' => $row['has_wifi'],
            'has_breakfast' => $row['has_breakfast'],
            'gambar' => $row['room_gambar']
        ];
    }
}

$stmt->close();

// Fungsi untuk menentukan halaman booking berdasarkan nama hotel
function getBookingPage($hotel_name) {
    $hotel_name = strtolower($hotel_name);
    if (strpos($hotel_name, 'cordela') !== false) {
        return 'cordela.php';
    } elseif (strpos($hotel_name, 'horison') !== false) {
        return 'horison.php';
    } elseif (strpos($hotel_name, 'santika') !== false) {
        return 'santika.php';
    } elseif (strpos($hotel_name, 'city') !== false) {
        return 'city.php';
    } else {
        return 'booking.php'; // Fallback default
    }
}

// Fungsi format harga
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian Kamar | PURQON.COM</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: #f5f5f5; 
            color: #333;
            line-height: 1.6;
        }
        .header { 
            background-color: #2c3e50; 
            color: white; 
            padding: 15px 20px; 
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header h2 {
            margin: 5px 0 0;
            font-size: 16px;
            font-weight: normal;
            opacity: 0.9;
        }
        .container { 
            max-width: 1200px; 
            margin: 20px auto; 
            padding: 20px; 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 0 15px rgba(0,0,0,0.05); 
        }
        .search-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        .btn-search {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-search:hover {
            background-color: #2980b9;
        }
        .hotel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .hotel-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            background-color: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .hotel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .hotel-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }
        .hotel-content {
            padding: 20px;
        }
        .hotel-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .alamat {
            color: #7f8c8d;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .rating {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 15px;
        }
        .stars {
            color: #f39c12;
            margin-left: 8px;
            font-size: 18px;
        }
        .room-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fafafa;
        }
        .room-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 12px;
        }
        .room-title {
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .room-price {
            font-size: 16px;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 8px;
        }
        .room-info {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }
        .facilities {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 12px 0;
        }
        .facility-badge {
            background-color: #ecf0f1;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: #34495e;
            display: flex;
            align-items: center;
        }
        .facility-badge i {
            margin-right: 5px;
            color: #3498db;
        }
        .pesan-btn {
            display: inline-block;
            width: 100%;
            padding: 10px;
            background-color: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
        }
        .pesan-btn:hover {
            background-color: #219653;
        }
        .no-rooms {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .no-rooms h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .no-rooms p {
            font-size: 15px;
            margin-bottom: 20px;
        }
        .btn-try-again {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 15px;
            transition: background-color 0.3s;
        }
        .btn-try-again:hover {
            background-color: #2980b9;
        }
        @media (max-width: 768px) {
            .hotel-grid {
                grid-template-columns: 1fr;
            }
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            .form-group {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="header">
        <h1>PURQON.COM</h1>
        <h2>Cari Kamar Hotel Terbaik di Tasikmalaya</h2>
    </div>
    
    <div class="container">
        <div class="search-form">
            <form action="search.php" method="get">
                <div class="form-row">
                    <div class="form-group">
                        <label for="location"><i class="fas fa-map-marker-alt"></i> Lokasi:</label>
                        <input type="text" id="location" name="location" class="form-control" 
                               value="<?= htmlspecialchars($_GET['location'] ?? '') ?>" placeholder="Contoh: Yudanegara">
                    </div>
                    <div class="form-group">
                        <label for="checkin"><i class="fas fa-calendar-check"></i> Check-in:</label>
                        <input type="date" id="checkin" name="checkin" class="form-control" 
                               value="<?= htmlspecialchars($checkin) ?>" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label for="checkout"><i class="fas fa-calendar-times"></i> Check-out:</label>
                        <input type="date" id="checkout" name="checkout" class="form-control" 
                               value="<?= htmlspecialchars($checkout) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    <div class="form-group">
                        <label for="tamu"><i class="fas fa-user-friends"></i> Jumlah Tamu:</label>
                        <input type="number" id="tamu" name="tamu" class="form-control" min="1" 
                               value="<?= htmlspecialchars($tamu) ?>">
                    </div>
                </div>
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Cari Kamar</button>
            </form>
        </div>
        
        <?php if (empty($hotels)): ?>
            <div class="no-rooms">
                <h3>Tidak ada kamar yang tersedia untuk kriteria pencarian Anda</h3>
                <p>Silakan coba dengan tanggal atau lokasi yang berbeda.</p>
                <a href="search.php" class="btn-try-again">Coba Lagi</a>
            </div>
        <?php else: ?>
            <div class="hotel-grid">
                <?php foreach ($hotels as $hotel): ?>
                    <?php if (!empty($hotel['rooms'])): ?>
                        <div class="hotel-card">
                            <div class="hotel-content">
                                <div class="hotel-name"><?= htmlspecialchars($hotel['nama']) ?></div>
                                <div class="alamat">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($hotel['alamat']) ?>
                                </div>
                                <div class="rating">
                                    Rating: <?= number_format($hotel['rating'], 1) ?> 
                                    <span class="stars"><?= str_repeat('★', floor($hotel['rating'])) ?></span>
                                </div>
                                
                                <?php foreach ($hotel['rooms'] as $room): ?>
                                    <div class="room-card">
                                        <?php if (!empty($room['gambar'])): ?>
                                        <?php endif; ?>
                                        <div class="room-title"><?= htmlspecialchars($room['type']) ?></div>
                                        <div class="room-price"><?= formatCurrency($room['price']) ?> /malam</div>
                                        <div class="room-info">
                                            <i class="fas fa-bed"></i> Tersedia: <?= $room['available'] ?> kamar
                                        </div>
                                        <div class="room-info">
                                            <i class="fas fa-users"></i> Maksimal: <?= $room['max_guests'] ?> orang
                                        </div>
                                        
                                        <div class="facilities">
                                            <?php if ($room['has_ac']): ?>
                                                <span class="facility-badge"><i class="fas fa-snowflake"></i> AC</span>
                                            <?php endif; ?>
                                            <?php if ($room['has_tv']): ?>
                                                <span class="facility-badge"><i class="fas fa-tv"></i> TV</span>
                                            <?php endif; ?>
                                            <?php if ($room['has_wifi']): ?>
                                                <span class="facility-badge"><i class="fas fa-wifi"></i> WiFi</span>
                                            <?php endif; ?>
                                            <?php if ($room['has_breakfast']): ?>
                                                <span class="facility-badge"><i class="fas fa-utensils"></i> Sarapan</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php $hotel_page = getBookingPage($hotel['nama']); ?>
                                        <a href="<?= $hotel_page ?>?hotel_id=<?= $hotel['id'] ?>&room_id=<?= $room['id'] ?>&checkin=<?= $checkin ?>&checkout=<?= $checkout ?>&tamu=<?= $tamu ?>" 
                                           class="pesan-btn">
                                            <i class="fas fa-book"></i> Pesan Sekarang
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>