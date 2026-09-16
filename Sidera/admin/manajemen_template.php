<?php
// admin/manajemen_template.php
require_once '../config/koneksi.php';
require_once 'includes/admin_header.php';

$pesan = "";

// Proses Hapus Template jika ada request GET['hapus']
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $hapus = $koneksi->query("DELETE FROM template_surat WHERE id = $id_hapus");
    if ($hapus) {
        $pesan = "<div style='background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px;'><i class='fa-solid fa-circle-check'></i> Template berhasil dihapus.</div>";
    } else {
        $pesan = "<div style='background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px;'><i class='fa-solid fa-triangle-exclamation'></i> Gagal menghapus template karena sedang digunakan dalam riwayat surat.</div>";
    }
}

// Ambil semua data template
$query = "SELECT * FROM template_surat ORDER BY id DESC";
$result = $koneksi->query($query);
?>

<div class="page-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 class="page-title" style="font-size: 24px; color: #0f172a; margin: 0 0 5px 0;">Manajemen Template Surat</h1>
        <p class="page-subtitle" style="color: #64748b; margin: 0;">Kelola format dan struktur dokumen surat desa.</p>
    </div>
    <div>
        <a href="tambah_template.php" class="btn-search" style="background: #10b981; color: white; text-decoration: none;">
            <i class="fa-solid fa-plus"></i> Tambah Template Baru
        </a>
        <a href="manajemen_surat.php" class="btn-search" style="background: #64748b; color: white; text-decoration: none; margin-left: 10px;">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<?= $pesan ?>

<div style="background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <th style="padding: 15px; color: #475569; font-size: 14px;">No</th>
                <th style="padding: 15px; color: #475569; font-size: 14px;">Kode Surat</th>
                <th style="padding: 15px; color: #475569; font-size: 14px;">Nama Surat</th>
                <th style="padding: 15px; color: #475569; font-size: 14px; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if ($result && $result->num_rows > 0): 
                while($row = $result->fetch_assoc()): 
            ?>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 15px;"><?= $no++ ?></td>
                <td style="padding: 15px;"><span style="background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-weight: bold;"><?= htmlspecialchars($row['kode_surat']) ?></span></td>
                <td style="padding: 15px; font-weight: 500; color: #0f172a;"><?= htmlspecialchars($row['nama_surat']) ?></td>
                <td style="padding: 15px; text-align: center;">
                    <!-- Tombol Edit melempar ID ke tambah_template.php -->
                    <a href="tambah_template.php?id=<?= $row['id'] ?>" style="background: #f59e0b; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; margin-right: 5px;"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                    
                    <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus template ini?');" style="background: #ef4444; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 13px;"><i class="fa-solid fa-trash"></i> Hapus</a>
                </td>
            </tr>
            <?php 
                endwhile; 
            else: 
            ?>
            <tr>
                <td colspan="4" style="padding: 30px; text-align: center; color: #94a3b8;">Belum ada template surat yang dibuat.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>