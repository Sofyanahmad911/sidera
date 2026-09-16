<?php
// admin/includes/admin_header.php
ob_start(); // Membuka buffer
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

// 1. Proteksi Autentikasi Mutlak
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// 2. FITUR AUTO-LOGOUT (SESSION TIMEOUT)
$timeout_duration = 1800; // 1800 detik = 30 menit

// Cek apakah ada catatan aktivitas terakhir
if (isset($_SESSION['last_activity'])) {
    // Hitung selisih waktu sekarang dengan aktivitas terakhir
    $elapsed_time = time() - $_SESSION['last_activity'];
    
    // Jika lebih dari 30 menit, paksa logout
    if ($elapsed_time > $timeout_duration) {
        session_unset();     // Hapus semua variabel sesi
        session_destroy();   // Hancurkan sesi
        
        // Arahkan kembali ke halaman login dengan parameter khusus
        header("Location: " . BASE_URL . "/login.php?status=timeout");
        exit();
    }
}

// Perbarui catatan waktu aktivitas setiap kali halaman dimuat
$_SESSION['last_activity'] = time();

$current_page = basename($_SERVER['PHP_SELF']);
// Proteksi Keamanan Inti
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - SIDERA</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --sidebar-bg: #1a6f76;
            --sidebar-hover: #13555b;
            --topbar-bg: #59d5e0;
            --bg-light: #f1f5f9;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --primary: #1a6f76;
            --secondary: #59d5e0;
            --shadow-sm: 0 4px 6px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-light); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }
        a { text-decoration: none; }

        /* ================= SIDEBAR ================= */
        /* ================= SIDEBAR (EFEK KAIN MENJUNTAI) ================= */
        .sidebar {
            width: 260px;
            
            /* Trik 1: Menggabungkan warna tema Teal dengan gambar motif kain */
            background: linear-gradient(
                to bottom,
                rgba(26, 111, 118, 0.90), /* Warna utama agak transparan di atas */
                rgba(15, 60, 65, 0.98)    /* Lebih gelap di bawah agar menu logout jelas */
            ), url('../assets/img/batik.jpeg');
            
            background-size: cover;
            background-position: center top;
            
            /* Trik 2: Blend mode membuat gambar menyatu dengan warna gradient seperti kain yang dicelup warna */
            background-blend-mode: multiply; 
            
            /* Trik 3: Efek bayangan lipatan / kedalaman kain di sisi kanan */
            box-shadow: inset -15px 0 25px rgba(0,0,0,0.35), 5px 0 20px rgba(0,0,0,0.15);
            
            /* Aksen ujung jahitan kain di sebelah kanan */
            border-right: 2px solid rgba(89, 213, 224, 0.5);
            
            color: #ffffff;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }
        
        /* Menu User / Header Sidebar */
        .sidebar-header {
            padding: 25px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            /* Efek jahitan / pemisah kain bagian atas */
            border-bottom: 1px dashed rgba(255,255,255,0.25);
            background: rgba(0,0,0,0.15); /* Menggelapkan sedikit area header */
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        .admin-avatar { width: 45px; height: 45px; background: #fff; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: var(--primary); font-size: 20px; }
        .admin-info h4 { font-size: 15px; font-weight: 600; margin: 0; }
        .admin-info span { font-size: 12px; color: var(--secondary); }

        /* Link Navigasi Sidebar */
        .sidebar-menu { list-style: none; padding: 0 15px; flex-grow: 1; }
        .sidebar-item { margin-bottom: 8px; }
        .sidebar-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 15px; color: rgba(255,255,255,0.8);
            border-radius: 10px; font-size: 14px; font-weight: 500;
            transition: var(--transition);
        }
        .sidebar-link i { font-size: 16px; width: 20px; text-align: center; }
        .sidebar-link:hover, .sidebar-link.active {
            background-color: var(--sidebar-hover);
            color: #ffffff;
            transform: translateX(5px);
        }
        .sidebar-link.active { border-left: 4px solid var(--secondary); }
        
        /* Menu Bawah (Logout) */
        .sidebar-bottom { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .btn-logout {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 12px; background: rgba(239, 68, 68, 0.1);
            color: #f60f0f; border-radius: 10px; font-weight: 600; font-size: 14px;
            transition: var(--transition);
        }
        .btn-logout:hover { background: #ef4444; color: white; }

        /* ================= MAIN CONTENT & TOPBAR ================= */
        .main-wrapper {
            flex-grow: 1;
            margin-left: 260px; /* Kompensasi lebar sidebar */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            background-color: var(--topbar-bg);
            height: 70px;
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 99;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        /* Brand / Logo */
        .nav-brand { display: flex; align-items: center; gap: 15px; text-decoration: none; }
        .brand-logo { width: 55px; height: 55px; background: #fff; border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; }
        .brand-logo img { width: 100%; height: auto; object-fit: cover; }
        .brand-title { font-family: 'Playfair Display', serif; font-size: 30px; font-weight: 700; color: var(--text-dark); line-height: 1; }
        .brand-subtitle { font-size: 11px; font-weight: 500; color: var(--text-dark); opacity: 0.8; letter-spacing: 0.5px; margin-top: 4px; display: block; }

        
        /* Dummy Nav Topbar (Mirip halaman publik) */
        .topbar-nav { display: flex; gap: 30px; list-style: none; }
        .topbar-nav a { color: #111827; font-weight: 600; font-size: 14px; }
        
        .topbar-user { display: flex; align-items: center; justify-content: center; width: 80px; height: 45px; background: rgb(255, 255, 255); border-radius: 10px; color: #111827; font-size: 18px; }

        /* Area Konten Dinamis */
        .content-area { padding: 40px; flex-grow: 1; }
        .page-header { margin-bottom: 30px; }
        .page-title { font-size: 24px; color: var(--primary); font-weight: 700; }
        .page-subtitle { color: var(--text-muted); font-size: 14px; margin-top: 5px; }

        /* Universal Card Style untuk Panel Admin */
        .admin-card { background: #ffffff; border-radius: 16px; padding: 25px; box-shadow: var(--shadow-sm); border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

    <!-- 1. SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="admin-avatar"><i class="fa-solid fa-user-shield"></i></div>
            <div class="admin-info">
                <h4>Administrator</h4>
                <span>Panel SIM Desa</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="index.php" class="sidebar-link <?= ($current_page == 'index.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
            </li>
            <li class="sidebar-item">
                <a href="edit_profil.php" class="sidebar-link <?= ($current_page == 'edit_profil.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Profil Desa
                </a>
            </li>
            <li class="sidebar-item">
                <a href="manajemen_penduduk.php" class="sidebar-link <?= ($current_page == 'manajemen_penduduk.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-users-gear"></i> Data Penduduk
                </a>
            </li>
            <li class="sidebar-item">
                <a href="manajemen_surat.php" class="sidebar-link <?= ($current_page == 'manajemen_surat.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-envelope-open-text"></i> Manajemen Surat
                </a>
            </li>
            <li class="sidebar-item">
                <a href="manajemen_arsip.php" class="sidebar-link <?= ($current_page == 'manajemen_arsip.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-shield"></i> Arsip Digital
                </a>
            </li>
            <!-- KODE BARU: Menu Khusus Super Admin -->
            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'Super Admin'): ?>
            <li class="sidebar-item">
                <a href="log_aktivitas.php" class="sidebar-link <?= ($current_page == 'log_aktivitas.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i> Log Aktivitas
                </a>
            </li>
            <?php endif; ?>
        </ul>


<!-- ... menu pengaturan dan logout ... -->
        <div class="sidebar-bottom">
            <a href="pengaturan_user.php" class="sidebar-link" style="margin-bottom:10px;">
                <i class="fa-solid fa-gear"></i> Pengaturan
            </a>
            
        </div>
    </aside>

    <!-- 2. MAIN WRAPPER -->
    <main class="main-wrapper">
        
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-brand">
                <div class="brand-text">
                <a href="../index.php"><span class="brand-title">SIDERA</span></a>
                <span class="brand-subtitle">Sistem Informasi Desa Serage</span>
            </div>
            </div>
            
            <div class="topbar-user"><a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-right-from-bracket"></i>Logout
            </a>
            </div>
            
        </header>

        <!-- AREA KONTEN (Dibuka di sini, ditutup di footer) -->
        <div class="content-area">