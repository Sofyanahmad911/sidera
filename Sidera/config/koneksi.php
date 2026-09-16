<?php
// config/koneksi.php
// Panggil file functions.php secara global
require_once __DIR__ . '/../includes/functions.php';

// 1. Buka Buffer dan Mulai Sesi SATU KALI di sini untuk seluruh web
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Tentukan BASE_URL
define('BASE_URL', 'http://localhost/sidera');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "sidera";

$koneksi = new mysqli($host, $user, $pass, $db);

if ($koneksi->connect_error) {
    error_log("Koneksi database gagal: " . $koneksi->connect_error);
    die("Maaf, sedang terjadi gangguan pada server. Silakan coba beberapa saat lagi.");
}

// Set karakter utf8mb4 agar kompatibel dengan emoji dan teks Sasak/Indonesia
$koneksi->set_charset("utf8mb4");