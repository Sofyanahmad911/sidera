<?php
// admin/log_aktivitas.php
require_once '../config/koneksi.php';
require_once 'includes/admin_header.php';

// PROTEKSI RBAC: Hanya Super Admin yang boleh melihat CCTV sistem
if ($_SESSION['admin_role'] !== 'Super Admin') {
    echo "<script>alert('Akses Ditolak! Halaman ini khusus untuk Super Administrator.'); window.location.href='index.php';</script>";
    exit();
}

// 1. Konfigurasi Pagination
$batas = 15; // Tampilkan 15 log per halaman
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

// 2. Hitung total data log
$query_total = $koneksi->query("SELECT COUNT(id) AS total FROM log_aktivitas");
$total_data = $query_total->fetch_assoc()['total'];
$total_halaman = ceil($total_data / $batas);

// 3. Kueri JOIN untuk mengambil nama admin beserta aktivitasnya
$sql = "SELECT l.*, u.nama_admin, u.role 
        FROM log_aktivitas l 
        JOIN users u ON l.admin_id = u.id 
        ORDER BY l.created_at DESC 
        LIMIT $halaman_awal, $batas";
$result = $koneksi->query($sql);
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { background: #f8fafc; color: var(--text-muted); padding: 15px; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
    .admin-table td { padding: 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; vertical-align: middle; color: var(--text-dark); }
    .admin-table tr:hover td { background: #f8fafc; }
    
    .badge-aksi { padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
    .waktu-log { font-size: 12px; color: var(--text-muted); font-family: monospace; }
</style>

<div class="page-header">
    <h1 class="page-title">Riwayat Aktivitas Sistem</h1>
    <p class="page-subtitle">Pantau seluruh perubahan data krusial yang dilakukan oleh pengguna sistem.</p>
</div>

<div class="admin-card">
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Waktu (WITA)</th>
                    <th>Petugas</th>
                    <th>Jenis Aksi</th>
                    <th>Detail Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): $no = $halaman_awal + 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="waktu-log">
                            <i class="fa-regular fa-clock" style="margin-right: 5px;"></i>
                            <?= date('d M Y - H:i', strtotime($row['created_at'])) ?>
                        </td>
                        <td>
                            <strong style="color: var(--primary);"><?= htmlspecialchars($row['nama_admin']) ?></strong><br>
                            <small style="color: var(--text-muted);"><?= htmlspecialchars($row['role']) ?></small>
                        </td>
                        <td><span class="badge-aksi"><?= htmlspecialchars($row['aksi']) ?></span></td>
                        <td style="line-height: 1.6; font-size: 13px;"><?= htmlspecialchars($row['detail_aksi']) ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">Belum ada riwayat aktivitas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- UI Pagination -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
        <div style="font-size: 13px; color: var(--text-muted);">
            Menampilkan <strong><?= ($total_data > 0) ? $halaman_awal + 1 : 0 ?> - <?= min($halaman_awal + $batas, $total_data) ?></strong> dari <strong><?= $total_data ?></strong> aktivitas.
        </div>
        <div style="display: flex; gap: 5px;">
            <?php for($x = 1; $x <= $total_halaman; $x++): ?>
                <a href="?halaman=<?= $x ?>" class="<?= ($x == $halaman) ? 'btn-primary' : 'btn-outline' ?>" style="padding: 6px 12px; border-radius: 6px; text-decoration: none; border: 1px solid #e2e8f0; color: <?= ($x == $halaman) ? '#fff' : 'var(--text-dark)' ?>; background: <?= ($x == $halaman) ? 'var(--primary)' : 'transparent' ?>;"><?= $x ?></a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>