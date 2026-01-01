<?php
$host = "localhost";
$user = "root";      // ganti jika pakai user lain
$pass = "";          // ganti dengan password DB Anda
$db   = "dewasufa";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

session_start();
?>
