<?php
include 'db_connect.php';
session_start();

if (!isset($_GET['booking_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = (int)$_GET['booking_id'];

// Update status pembayaran
$stmt = $conn->prepare("UPDATE payments SET payment_status = 'completed', payment_date = NOW() WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->close();

// Update status booking
$stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->close();

header("Location: payment_receipt.php?booking_id=" . $booking_id);
exit();
?>