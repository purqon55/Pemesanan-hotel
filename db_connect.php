<?php
// Database configuration - consider moving these to environment variables or a config file
$host = 'localhost';
$user = 'root';       // Consider using a less privileged user
$password = '';       // Empty password is insecure for production
$dbname = 'pemesananhotel';

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Don't expose detailed errors in production
    die("Connection failed. Please try again later.");
    // For debugging during development only:
    // die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4"); // utf8mb4 supports full Unicode including emojis

// Consider adding error reporting configuration
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users in production
ini_set('log_errors', 1);    // Log errors instead
?>