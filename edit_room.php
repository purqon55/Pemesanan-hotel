<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to database
require 'db_connect.php';

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get admin data
$admin_username = $_SESSION['admin'];
$query_admin = "SELECT * FROM admin WHERE username = ?";
$stmt = $conn->prepare($query_admin);
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$admin_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin_data) {
    die("Admin data not found");
}

// Initialize variables
$room = null;
$hotels = [];
$error_msg = '';
$success_msg = '';

// Get list of hotels for dropdown
$hotel_query = "SELECT id, nama FROM hotels ORDER BY nama";
$hotel_result = $conn->query($hotel_query);
if ($hotel_result) {
    $hotels = $hotel_result->fetch_all(MYSQLI_ASSOC);
}

// If ID is in URL, get room data
if (isset($_GET['id'])) {
    $room_id = $_GET['id'];
    $query = "SELECT * FROM rooms WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
    $stmt->close();
    
    if (!$room) {
        $error_msg = "Room not found";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'] ?? null;
    $hotel_id = $_POST['hotel_id'];
    $room_type = $_POST['room_type'];
    $available_rooms = $_POST['available_rooms'];
    $price = $_POST['price'];
    $max_guests = $_POST['max_guests'];
    $description = $_POST['description'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $has_ac = isset($_POST['has_ac']) ? 1 : 0;
    $has_tv = isset($_POST['has_tv']) ? 1 : 0;
    $has_wifi = isset($_POST['has_wifi']) ? 1 : 0;
    $has_breakfast = isset($_POST['has_breakfast']) ? 1 : 0;

    // Validate input
    if (empty($hotel_id) || empty($room_type) || empty($available_rooms) || empty($price) || empty($max_guests)) {
        $error_msg = "All fields are required!";
    } else {
        if ($room_id) {
            // Update existing room
            $query = "UPDATE rooms SET 
                        hotel_id = ?, 
                        room_type = ?, 
                        available_rooms = ?, 
                        price = ?, 
                        is_available = ?, 
                        description = ?, 
                        max_guests = ?, 
                        has_ac = ?, 
                        has_tv = ?, 
                        has_wifi = ?, 
                        has_breakfast = ? 
                      WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isiiisiiiiii", 
                $hotel_id, $room_type, $available_rooms, $price, $is_available, 
                $description, $max_guests, $has_ac, $has_tv, $has_wifi, $has_breakfast, $room_id);
        } else {
            // Insert new room
            $query = "INSERT INTO rooms (
                        hotel_id, room_type, available_rooms, price, is_available, 
                        description, max_guests, has_ac, has_tv, has_wifi, has_breakfast
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isiiisiiiii", 
                $hotel_id, $room_type, $available_rooms, $price, $is_available, 
                $description, $max_guests, $has_ac, $has_tv, $has_wifi, $has_breakfast);
        }

        if ($stmt->execute()) {
            $success_msg = $room_id ? "Room updated successfully!" : "Room added successfully!";
            if (!$room_id) {
                // Redirect to edit page for the new room
                $new_room_id = $stmt->insert_id;
                header("Location: edit_room.php?id=$new_room_id&success=" . urlencode($success_msg));
                exit();
            }
        } else {
            $error_msg = "Failed to save data: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= isset($room) ? 'Edit Room' : 'Add New Room' ?> | PURQON.COM</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; 
            padding: 0; 
            background-color: #f0f2f5; 
            color: #333;
        }
        .header { 
            background-color: #2c3e50; 
            color: white; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: normal;
            opacity: 0.8;
        }
        .container { 
            max-width: 900px; 
            margin: 30px auto; 
            padding: 25px; 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 0 15px rgba(0,0,0,0.05); 
        }
        .logout { 
            color: white; 
            text-decoration: none;
            background-color: #e74c3c;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            margin-left: 15px;
            transition: background-color 0.3s;
        }
        .logout:hover {
            background-color: #c0392b;
        }
        .admin-info {
            display: flex;
            align-items: center;
        }
        .admin-info span {
            margin-right: 10px;
            font-weight: 500;
        }
        .page-title {
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 25px;
            color: #2c3e50;
        }
        .page-title h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 15px;
            background-color: #7f8c8d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .back-btn:hover {
            background-color: #34495e;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        .form-check {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .form-check-input {
            margin-right: 8px;
        }
        .form-check-label {
            font-weight: normal;
        }
        .btn {
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            margin-right: 10px;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-secondary {
            background-color: #7f8c8d;
        }
        .btn-secondary:hover {
            background-color: #34495e;
        }
        .btn-danger {
            background-color: #e74c3c;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .facilities-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .facility-item {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }
        .action-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: flex-start;
        }
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        .status-toggle {
            display: flex;
            align-items: center;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-right: 10px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #3498db;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>ADMIN PANEL</h1>
            <h2>PURQON HOTEL MANAGEMENT</h2>
        </div>
        <div class="admin-info">
            <span><?= htmlspecialchars($admin_data['full_name']) ?></span>
            <a href="admin_logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="page-title">
            <h2><?= isset($room) ? 'EDIT ROOM DETAILS' : 'ADD NEW ROOM' ?></h2>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="edit_room.php">
            <input type="hidden" name="room_id" value="<?= $room['id'] ?? '' ?>">
            
            <div class="form-group">
                <label for="hotel_id">Hotel</label>
                <select class="form-control" id="hotel_id" name="hotel_id" required>
                    <option value="">Select Hotel</option>
                    <?php foreach ($hotels as $hotel): ?>
                        <option value="<?= $hotel['id'] ?>" 
                            <?= isset($room) && $room['hotel_id'] == $hotel['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($hotel['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="room_type">Room Type</label>
                <input type="text" class="form-control" id="room_type" name="room_type" 
                       value="<?= htmlspecialchars($room['room_type'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="available_rooms">Available Rooms</label>
                <input type="number" class="form-control" id="available_rooms" name="available_rooms" 
                       value="<?= htmlspecialchars($room['available_rooms'] ?? '') ?>" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="price">Price per Night (Rp)</label>
                <input type="number" class="form-control" id="price" name="price" 
                       value="<?= htmlspecialchars($room['price'] ?? '') ?>" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="max_guests">Maximum Guests</label>
                <input type="number" class="form-control" id="max_guests" name="max_guests" 
                       value="<?= htmlspecialchars($room['max_guests'] ?? '2') ?>" min="1" required>
            </div>
            
            <div class="form-group">
                <label for="description">Room Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($room['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Room Status</label>
                <div class="status-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" id="is_available" name="is_available" 
                               <?= isset($room) && $room['is_available'] ? 'checked' : 'checked' ?>>
                        <span class="slider"></span>
                    </label>
                    <span id="status-text">Available</span>
                </div>
            </div>
            
            <div class="form-group">
                <label>Room Facilities</label>
                <div class="facilities-container">
                    <div class="facility-item">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="has_ac" name="has_ac" 
                                   <?= isset($room) && $room['has_ac'] ? 'checked' : 'checked' ?>>
                            <label class="form-check-label" for="has_ac">Air Conditioning</label>
                        </div>
                    </div>
                    <div class="facility-item">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="has_tv" name="has_tv" 
                                   <?= isset($room) && $room['has_tv'] ? 'checked' : 'checked' ?>>
                            <label class="form-check-label" for="has_tv">TV</label>
                        </div>
                    </div>
                    <div class="facility-item">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="has_wifi" name="has_wifi" 
                                   <?= isset($room) && $room['has_wifi'] ? 'checked' : 'checked' ?>>
                            <label class="form-check-label" for="has_wifi">WiFi</label>
                        </div>
                    </div>
                    <div class="facility-item">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="has_breakfast" name="has_breakfast" 
                                   <?= isset($room) && $room['has_breakfast'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="has_breakfast">Breakfast Included</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="manage_rooms.php" class="btn btn-secondary">Back to List</a>
                <?php if (isset($room)): ?>
                    <a href="manage_rooms.php?delete_id=<?= $room['id'] ?>" class="btn btn-danger" 
                       onclick="return confirm('Are you sure you want to delete this room?')">Delete Room</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
        // Toggle switch functionality
        const toggleSwitch = document.getElementById('is_available');
        const statusText = document.getElementById('status-text');
        
        toggleSwitch.addEventListener('change', function() {
            statusText.textContent = this.checked ? 'Available' : 'Not Available';
        });
        
        // Initialize status text
        if (toggleSwitch) {
            statusText.textContent = toggleSwitch.checked ? 'Available' : 'Not Available';
        }
    </script>
</body>
</html>