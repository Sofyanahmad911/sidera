<?php
// admin/pengaturan_user.php
require_once '../config/koneksi.php';
require_once 'includes/admin_header.php';

// --- PROTEKSI RBAC ---
// Jika yang login bukan Super Admin, tendang kembali ke dashboard
if ($_SESSION['admin_role'] !== 'Super Admin') {
    echo "<script>
        alert('Akses Ditolak! Halaman ini khusus untuk Super Administrator.');
        window.location.href = 'index.php';
    </script>";
    exit();
}
// ---------------------

$pesan = "";

// 1. Logika Tambah Pengguna Baru
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_user'])) {
    $username = $koneksi->real_escape_string($_POST['username']);
    $nama_admin = $koneksi->real_escape_string($_POST['nama_admin']);
    $role = $koneksi->real_escape_string($_POST['role']);
    $password_raw = $_POST['password'];

    // Cek apakah username sudah dipakai
    $cek = $koneksi->query("SELECT id FROM users WHERE username = '$username'");
    if ($cek->num_rows > 0) {
        $pesan = "<div class='alert-error'><i class='fa-solid fa-triangle-exclamation'></i> Username sudah digunakan! Pilih username lain.</div>";
    } else {
        // Enkripsi Password (Clean Code & Secure)
        $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

        $stmt = $koneksi->prepare("INSERT INTO users (username, password, nama_admin, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $password_hashed, $nama_admin, $role);
        
        if ($stmt->execute()) {
            $pesan = "<div class='alert-success'><i class='fa-solid fa-circle-check'></i> Akun pengguna baru berhasil ditambahkan.</div>";
        } else {
            $pesan = "<div class='alert-error'><i class='fa-solid fa-xmark'></i> Gagal menambahkan pengguna.</div>";
        }
        $stmt->close();
    }
}

// 2. Logika Hapus Pengguna (Opsional, pastikan tidak menghapus akun sendiri)
if (isset($_GET['hapus_id'])) {
    $hapus_id = (int)$_GET['hapus_id'];
    if ($hapus_id != $_SESSION['admin_id']) {
        $koneksi->query("DELETE FROM users WHERE id = $hapus_id");
        echo "<script>window.location.href='pengaturan_user.php';</script>";
    } else {
        echo "<script>alert('Anda tidak bisa menghapus akun yang sedang Anda gunakan!'); window.location.href='pengaturan_user.php';</script>";
    }
}

// Ambil Daftar Pengguna
$q_users = $koneksi->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<style>
    .layout-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
    
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; }
    .form-control { width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; transition: var(--transition); background: var(--bg-light); font-family: 'Poppins', sans-serif; }
    .form-control:focus { border-color: var(--secondary); background: #fff; box-shadow: 0 0 0 3px rgba(89, 213, 224, 0.2); }
    
    .btn-save { background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.3s; width: 100%; }
    .btn-save:hover { background: var(--sidebar-hover); transform: translateY(-2px); box-shadow: var(--shadow-sm); }
    
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { background: #f8fafc; color: var(--text-muted); padding: 15px; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
    .admin-table td { padding: 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; vertical-align: middle; color: var(--text-dark); }
    
    .badge-role { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .role-super { background: #fee2e2; color: #b91c1c; }
    .role-operator { background: #e0f2fe; color: #0284c7; }

    .alert-success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #22c55e; }
    .alert-error { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ef4444; }

    @media (max-width: 992px) { .layout-grid { grid-template-columns: 1fr; } }
</style>

<div class="page-header">
    <h1 class="page-title">Pengaturan Pengguna Sistem</h1>
    <p class="page-subtitle">Kelola hak akses administrator dan operator pengelola SIM Desa.</p>
</div>

<?= $pesan ?>

<div class="layout-grid">
    <!-- Kolom Kiri: Form Tambah User -->
    <div class="admin-card" style="height: fit-content;">
        <h3 style="font-size: 16px; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Buat Akun Baru</h3>
        <form action="" method="POST">
            <div class="form-group">
                <label class="form-label">Nama Lengkap Petugas</label>
                <input type="text" name="nama_admin" class="form-control" required placeholder="Contoh: Budi Santoso">
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Gunakan huruf kecil tanpa spasi">
            </div>
            <div class="form-group">
                <label class="form-label">Role Akses</label>
                <select name="role" class="form-control" required>
                    <option value="Operator">Operator (Hanya kelola data)</option>
                    <option value="Super Admin">Super Admin (Akses penuh)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Password Sementara</label>
                <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
            </div>
            
            <button type="submit" name="tambah_user" class="btn-save"><i class="fa-solid fa-user-plus"></i> Simpan Pengguna</button>
        </form>
    </div>

    <!-- Kolom Kanan: Tabel Daftar User -->
    <div class="admin-card">
        <h3 style="font-size: 16px; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Daftar Akun Terdaftar</h3>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Petugas</th>
                        <th>Username</th>
                        <th>Role Akses</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $q_users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--primary);"><?= htmlspecialchars($row['nama_admin']) ?></strong><br>
                                <small style="color: var(--text-muted);">Terdaftar: <?= date('d M Y', strtotime($row['created_at'])) ?></small>
                            </td>
                            <td style="font-family: monospace;"><?= htmlspecialchars($row['username']) ?></td>
                            <td>
                                <span class="badge-role <?= $row['role'] == 'Super Admin' ? 'role-super' : 'role-operator' ?>">
                                    <?= htmlspecialchars($row['role']) ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($row['id'] != $_SESSION['admin_id']): ?>
                                    <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus akses untuk petugas ini?')" style="color: #ef4444; font-size: 18px;" title="Hapus Akun">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 12px;">(Akun Anda)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>