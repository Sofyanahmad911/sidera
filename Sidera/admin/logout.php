<?php
// admin/logout.php
ob_start();
session_start();

// 1. Kosongkan semua variabel array sesi
$_SESSION = array();

// 2. Hancurkan Cookie Sesi di Browser (Sangat Penting untuk keamanan)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan Sesi di Server
session_destroy();

// 4. Redirect kembali ke halaman publik atau login
header("Location: ../login.php?status=logged_out");
exit();
?>