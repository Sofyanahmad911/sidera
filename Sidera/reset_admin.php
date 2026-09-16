<?php
// reset_admin.php
require_once 'config/koneksi.php';

// PROTEKSI: Tolak akses dari browser, hanya izinkan via Command Line Server
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Akses Ditolak. Skrip ini hanya dapat dijalankan melalui terminal server.");
}

$username_target = 'admin';
$password_baru = "admin123";
$hash_valid = password_hash($password_baru, PASSWORD_DEFAULT);

// KONSISTENSI QUERY: Gunakan Prepared Statement untuk UPDATE
$stmt = $koneksi->prepare("UPDATE users SET password = ? WHERE username = ?");
$stmt->bind_param("ss", $hash_valid, $username_target);

if ($stmt->execute()) {
    echo "=======================================\n";
    echo "PERBAIKAN BERHASIL!\n";
    echo "Password untuk '$username_target' telah direset.\n";
    echo "PENTING: Segera hapus file ini dari server!\n";
    echo "=======================================\n";
} else {
    echo "Gagal mereset password: " . $stmt->error . "\n";
}

$stmt->close();
?>