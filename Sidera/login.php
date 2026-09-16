<?php
// login.php
require_once 'config/koneksi.php';

// Jika sudah login, paksa masuk ke dashboard admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: " . BASE_URL . "/admin/index.php");
    exit(); 
}

// login.php (Modifikasi bagian pesan)
$error_msg = "";
$info_msg = ""; 

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'timeout') {
        $error_msg = "Sesi Anda telah berakhir karena tidak ada aktivitas selama 30 menit. Silakan login kembali.";
    } elseif ($_GET['status'] == 'logged_out') {
        $info_msg = "Anda berhasil keluar dari sistem.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. VERIFIKASI CSRF TOKEN DAHULU
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_msg = "Sesi tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.";
    } else {
        $username = $koneksi->real_escape_string($_POST['username']);
        $password = $_POST['password'];

        $stmt = $koneksi->prepare("SELECT id, username, password, nama_admin, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verifikasi password hash
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_nama'] = $user['nama_admin'];
                $_SESSION['admin_role'] = $user['role'];
                
                header("Location: " . BASE_URL . "/admin/index.php");
                exit();
            } else {
                $error_msg = "Password yang Anda masukkan salah.";
            }
        } else {
            $error_msg = "Username tidak ditemukan di sistem.";
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
    <title>Login Panel - SIDERA</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a6f76; --secondary: #59d5e0; --bg: #f8fafc; }
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        
        .login-wrapper {
            background: #ffffff; width: 100%; max-width: 900px; min-height: 500px;
            border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            display: flex; overflow: hidden; margin: 20px;
        }
        
        /* Kolom Kiri - Gambar & Branding */
        .login-brand {
            width: 45%; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 50px 40px; color: white; display: flex; flex-direction: column; justify-content: space-between;
            position: relative;
        }
        .login-brand::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: url('https://images.unsplash.com/photo-1596422846543-75c6fc197f07?auto=format&fit=crop&w=800&q=80') center/cover;
            opacity: 0.2; mix-blend-mode: multiply; pointer-events: none;
        }
        .brand-logo { display: flex; align-items: center; gap: 15px; position: relative; z-index: 2; }
        .brand-logo i { font-size: 40px; }
        .brand-logo span { font-family: 'Playfair Display', serif; font-size: 32px; font-weight: 700; letter-spacing: 1px; }
        .brand-text { position: relative; z-index: 2; }
        .brand-text h2 { font-size: 28px; margin-bottom: 10px; font-weight: 600; }
        .brand-text p { font-size: 14px; opacity: 0.9; line-height: 1.6; }

        /* Kolom Kanan - Form Login */
        .login-form-container { width: 55%; padding: 60px 50px; display: flex; flex-direction: column; justify-content: center; }
        .form-header h3 { font-size: 24px; color: var(--primary); margin: 0 0 5px 0; font-weight: 700; }
        .form-header p { color: #64748b; font-size: 14px; margin: 0 0 30px 0; }
        
        .form-group { margin-bottom: 25px; position: relative; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 8px; }
        
        .input-icon { position: absolute; left: 15px; top: 38px; color: #94a3b8; font-size: 16px; }
        
        /* Tambahan CSS untuk Ikon Toggle Password */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 38px;
            color: #94a3b8;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        .toggle-password:hover { color: var(--primary); }

        .form-control {
            width: 100%; 
            padding: 14px 45px; /* Padding kiri-kanan disamakan agar tidak tertimpa ikon */
            border: 2px solid #e2e8f0;
            border-radius: 12px; font-size: 14px; outline: none; transition: 0.3s;
            background: #f8fafc; font-family: 'Poppins', sans-serif; box-sizing: border-box;
        }
        .form-control:focus { border-color: var(--secondary); background: #ffffff; box-shadow: 0 0 0 4px rgba(89, 213, 224, 0.15); }
        
        .btn-login {
            background: var(--primary); color: white; border: none; width: 100%;
            padding: 15px; border-radius: 12px; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: 0.3s; margin-top: 10px;
        }
        .btn-login:hover { background: #13555b; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(26, 111, 118, 0.2); }
        
        .alert-box { background: #fee2e2; color: #b91c1c; padding: 12px 15px; border-radius: 8px; font-size: 13px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-left: 4px solid #ef4444; }
        .back-link { display: block; text-align: center; margin-top: 25px; color: #64748b; font-size: 13px; text-decoration: none; transition: 0.3s; }
        .back-link:hover { color: var(--primary); }

        @media (max-width: 768px) { .login-wrapper { flex-direction: column; } .login-brand, .login-form-container { width: 100%; } .login-brand { padding: 40px 30px; } }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-brand">
            <div class="brand-logo">
                <i class="fa-solid fa-shield-halved"></i>
                <span>SIDERA</span>
            </div>
            <div class="brand-text">
                <h2>Panel Administrator</h2>
                <p>Silakan masuk menggunakan kredensial yang sah untuk mengakses fitur tata kelola, manajemen kependudukan, dan layanan persuratan.</p>
            </div>
        </div>
        
        <div class="login-form-container">
            <div class="form-header">
                <h3>Selamat Datang Kembali</h3>
                <p>Masukkan username dan password Anda.</p>
            </div>

            <!-- Menampilkan pesan Error -->
            <?php if($error_msg != ""): ?>
                <div class="alert-box" style="background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444;"><i class="fa-solid fa-circle-exclamation"></i> <?= $error_msg ?></div>
            <?php endif; ?>

            <!-- Menampilkan pesan Informasi -->
            <?php if($info_msg != ""): ?>
                <div class="alert-box" style="background: #dcfce7; color: #166534; border-left: 4px solid #22c55e;"><i class="fa-solid fa-circle-check"></i> <?= $info_msg ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <!-- Token Keamanan CSRF -->
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <i class="fa-solid fa-user input-icon"></i>
                    <input type="text" name="username" class="form-control" placeholder="Ketik username Anda..." required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Ketik password Anda..." required>
                    <!-- Ikon Toggle Password -->
                    <i class="fa-solid fa-eye toggle-password" id="togglePassword" title="Lihat Password"></i>
                </div>
                
                <button type="submit" class="btn-login">Masuk ke Sistem</button>
            </form>
            
            <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Publik</a>
        </div>
    </div>

    <!-- Script Show/Hide Password -->
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#inputPassword');

        togglePassword.addEventListener('click', function (e) {
            // Ubah tipe input antara password dan teks
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Ubah ikon mata
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>