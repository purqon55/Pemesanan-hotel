<?php
session_start();
if (!isset($_SESSION['user'])) {
    if (isset($_POST['book_now'])) {
        echo "<script>alert('Anda harus login terlebih dahulu untuk memesan hotel.'); window.location.href='login.php';</script>";
        exit;
    }
    header("Location: login.php");
    exit;
}
?>