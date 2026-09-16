<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil logo dinamis dari database profil_desa jika koneksi tersedia
$logo_desa_header = 'assets/img/logo.png';
if (isset($koneksi) && $koneksi instanceof mysqli) {
    $q_logo = $koneksi->query("SELECT logo_desa FROM profil_desa WHERE id = 1 LIMIT 1");
    if ($q_logo && $row_logo = $q_logo->fetch_assoc()) {
        if (!empty($row_logo['logo_desa']) && file_exists(__DIR__ . '/../assets/img/' . $row_logo['logo_desa'])) {
            $logo_desa_header = 'assets/img/' . $row_logo['logo_desa'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIDERA-Sistem Informasi Desa Serage</title>
    <link rel="shortcut icon" href="<?= htmlspecialchars($logo_desa_header) ?>" type="image/x-icon">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --header-bg: rgba(89, 213, 224, 0.95);
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: #f8fafc; overflow-x: hidden; }

        /* Glassmorphism Navbar */
        .navbar {
            background: var(--header-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 10px 45px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            transition: box-shadow 0.3s ease, padding 0.3s ease;
        }
        .navbar.scrolled {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 8px 45px;
        }

        /* Brand & Logo Sempurna (Tidak Terpotong) */
        .nav-brand { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            text-decoration: none; 
        }
        
        .brand-logo-container {
            width: 48px;
            height: 48px;
            min-width: 48px;
            min-height: 48px;
            background: #ffffff;
            border-radius: 12px; /* Rounded soft kotak presisi, tidak memotong ujung logo */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px; /* Padding pengaman agar logo tidak menyentuh tepi */
            box-sizing: border-box;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: var(--transition);
        }

        .brand-logo-container img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.05));
        }

        .nav-brand:hover .brand-logo-container {
            transform: scale(1.04);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .brand-title { 
            font-family: 'Playfair Display', serif; 
            font-size: 25px; 
            font-weight: 700; 
            color: var(--text-dark); 
            line-height: 1.1; 
            letter-spacing: 0.5px;
        }
        .brand-subtitle { 
            font-size: 11px; 
            font-weight: 500; 
            color: var(--text-dark); 
            opacity: 0.85; 
            letter-spacing: 0.3px; 
            margin-top: 2px; 
            display: block; 
        }

        /* Desktop Menu */
        .nav-menu { display: flex; align-items: center; gap: 30px; list-style: none; margin: 0; padding: 0; }
        .nav-link {
            text-decoration: none; color: var(--text-dark); font-weight: 600; font-size: 14px;
            display: flex; align-items: center; gap: 6px; position: relative; padding: 6px 0;
            opacity: 0.9; transition: var(--transition);
        }
        .nav-link:hover, .nav-link.active { opacity: 1; color: #086a82; }
        
        .nav-link::after {
            content: ''; position: absolute; bottom: -2px; left: 50%; width: 0; height: 2px;
            background: #086a82; border-radius: 4px; transition: var(--transition); transform: translateX(-50%);
        }
        .nav-link:hover::after, .nav-link.active::after { width: 100%; }

        /* Smooth Dropdown */
        .dropdown { position: relative; }
        .dropdown-menu {
            position: absolute; top: 120%; left: 50%; transform: translateX(-50%) translateY(10px);
            background: #ffffff; min-width: 230px; border-radius: 14px; padding: 10px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08); border: 1px solid rgba(226, 232, 240, 0.8);
            opacity: 0; visibility: hidden; transition: var(--transition); pointer-events: none;
            list-style-type: none;
            margin : 0;
            padding:0;
        }
        .dropdown:hover .dropdown-menu { opacity: 1; visibility: visible; transform: translateX(-50%) translateY(0); pointer-events: auto; }
        .dropdown-item {
            display: flex; align-items: center; gap: 10px; padding: 10px 14px; color: var(--text-muted);
            text-decoration: none; font-size: 13.5px; font-weight: 500; border-radius: 8px; transition: var(--transition);
        }
        .dropdown-item i { font-size: 16px; color: #086a82; transition: var(--transition); }
        .dropdown-item:hover { background: #f1f5f9; color: var(--text-dark); transform: translateX(4px); }
        .dropdown-item:hover i { color: var(--text-dark); }

        /* Login Button */
        .btn-login {
            display: flex; align-items: center; justify-content: center;
            width: 90px; height: 40px; border-radius: 12px; font-size: 18px;
            color: var(--text-dark); text-decoration: none; transition: var(--transition);
            background: rgba(255,255,255,0.3); border: 1px solid rgba(255,255,255,0.5);
        }
        .btn-login:hover { background: #ffffff; box-shadow: 0 8px 18px rgba(0,0,0,0.1); transform: translateY(-2px); color: #086a82; }

        /* Mobile Hamburger */
        .menu-toggle { display: none; font-size: 22px; color: var(--text-dark); cursor: pointer; border: none; background: transparent; }

        @media (max-width: 992px) {
            .navbar { padding: 10px 20px; }
            .brand-title { font-size: 21px; }
            .brand-logo-container { width: 42px; height: 42px; min-width: 42px; min-height: 42px; padding: 4px; }
            .menu-toggle { display: block; }
            .nav-menu {
                position: fixed; top: 0; right: -100%; width: 280px; height: 100vh;
                background: #ffffff; flex-direction: column; align-items: flex-start; padding: 75px 25px;
                box-shadow: -10px 0 30px rgba(0,0,0,0.1); transition: var(--transition); gap: 18px;
            }
            .nav-menu.active { right: 0; }
            .nav-action { display: none; }
            .close-menu { position: absolute; top: 20px; right: 20px; font-size: 22px; color: var(--text-dark); cursor: pointer; }
        }
    </style>
</head>
<body>

    <nav class="navbar" id="navbar">
        <a href="index.php" class="nav-brand">
            <div class="brand-logo-container">
                <img src="<?= htmlspecialchars($logo_desa_header) ?>" alt="Logo SIDERA" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Serage&background=1a6f76&color=ffffff&bold=true&rounded=true'">
            </div>
            <div class="brand-text">
                <span class="brand-title">SIDERA</span>
                <span class="brand-subtitle">Sistem Informasi Desa Serage</span>
            </div>
        </a>

        <ul class="nav-menu" id="nav-menu">
            <i class="fa-solid fa-xmark close-menu" id="close-menu" style="display:none;"></i>
            <li class="nav-item"><a href="index.php#hero" class="nav-link <?= ($current_page == 'index.php#hero' || $current_page == '') ? 'active' : '' ?>">Home</a></li>
            <li class="nav-item"><a href="index.php#profil_desa" class="nav-link <?= $current_page == 'index.php#profil_desa' ? 'active' : '' ?>">Profil Desa</a></li>
            <li class="nav-item"><a href="index.php#potensi_desa" class="nav-link <?= $current_page == 'index.php#potensi_desa' ? 'active' : '' ?>">Potensi</a></li>
            <li class="nav-item"><a href="index.php#infografis" class="nav-link <?= $current_page == 'index.php#infografis' ? 'active' : '' ?>">Infografis</a></li>
            <li class="nav-item"><a href="index.php#pencarian" class="nav-link <?= $current_page == 'index.php#pencarian' ? 'active' : '' ?>">Cek data</a></li>
            
            <li class="nav-item dropdown">
                <a href="" class="nav-link ">
                    Layanan <i class="fa-solid fa-chevron-down" style="font-size:11px;"></i>
                </a>
                <ul class="dropdown-menu">
                    <li ><a href="#profil_desa" class="dropdown-item" ><i class="fa-solid fa-landmark"></i>Buka Profil </a></li>
                    <li ><a href="#infografis" class="dropdown-item"><i class="fa-solid fa-users-viewfinder "></i>Lihat Statistik </a></li>
                    <li ><a href="#" class="dropdown-item"><i class="fa-solid fa-file-shield "></i>Buka Direktori </a></li>
                    <li ><a href="#timeline" class="dropdown-item"><i class="fa-solid fa-envelope-open-text "></i>Alur Layanan Surat </a></li>
                </ul>
            </li>
            
            <!-- Tampil khusus mobile -->
            <li class="nav-item" style="margin-top: 15px; display: none; width: 100%;" id="mobile-login">
                <a href="login.php" class="nav-link" style="color: #086a82; font-weight: 700;"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login Admin</a>
            </li>
        </ul>

        <div class="nav-action">
            <a href="login.php" class="btn-login" style="color: #086a82; font-weight: 700;"> <i class="fa-solid fa-arrow-right-to-bracket"> </i> Login</a>
        </div>
        
        <button class="menu-toggle" id="menu-toggle" aria-label="Buka Menu"><i class="fa-solid fa-bars"></i></button>
    </nav>

    <!-- Script Navbar Dinamis -->
    <script>
        // Efek bayangan saat scroll
        window.addEventListener('scroll', () => {
            document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 10);
        });

        // Toggle Mobile Menu
        const menuToggle = document.getElementById('menu-toggle');
        const navMenu = document.getElementById('nav-menu');
        const closeMenu = document.getElementById('close-menu');
        const mobileLogin = document.getElementById('mobile-login');

        if(menuToggle) {
            menuToggle.addEventListener('click', () => {
                navMenu.classList.add('active');
                closeMenu.style.display = 'block';
                if(window.innerWidth <= 992) mobileLogin.style.display = 'block';
            });
        }
        if(closeMenu) {
            closeMenu.addEventListener('click', () => {
                navMenu.classList.remove('active');
            });
        }
    </script>