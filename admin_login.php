<?php 
include 'db_connect.php'; 
session_start();

// Redirect jika sudah login sebagai admin
if (isset($_SESSION['admin'])) {
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi.";
    } else {
        $sql = "SELECT id, username, full_name, password FROM admin WHERE username=?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            die("Error prepare: " . $conn->error);
        }
        
        $stmt->bind_param('s', $username);
        
        if (!$stmt->execute()) {
            die("Error execute: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin'] = $admin['username'];
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = "Password salah.";
            }
        } else {
            $error = "Akun Admin tidak ditemukan.";
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: url('Hotel.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            position: relative;
        }
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            background-color: #f36;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        button:hover {
            background-color: #d22b5d;
        }
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 20px;
            cursor: pointer;
            color: #555;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .back-btn:hover {
            background: white;
            color: #333;
        }
    </style>
</head>
<body>
    <!-- Tombol Kembali di luar container -->
    <button class="back-btn" onclick="window.location.href='index.php'" title="Kembali">←</button>
    
    <div class="login-container">
        <h2>Login Admin</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">MASUK</button>
        </form>
    </div>
</body>
</html>