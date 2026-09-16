<?php
// admin/manajemen_arsip.php
require_once '../config/koneksi.php';
require_once 'includes/admin_header.php';

// --- PROSES UPDATE KODE SURAT (MASTER TEMPLATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_kode_surat'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $pesan = "<div class='alert-error'>Sesi tidak valid. Muat ulang halaman.</div>";
    } else {
        $id_template = (int)$_POST['id_template'];
        $kode_baru = $koneksi->real_escape_string(trim($_POST['kode_surat']));
        
        $cek = $koneksi->query("SELECT id FROM template_surat WHERE kode_surat = '$kode_baru' AND id != $id_template");
        if ($cek->num_rows > 0) {
            $pesan = "<div class='alert-error'>Gagal! Kode Surat <b>$kode_baru</b> sudah digunakan.</div>";
        } else {
            $stmt = $koneksi->prepare("UPDATE template_surat SET kode_surat = ? WHERE id = ?");
            $stmt->bind_param("si", $kode_baru, $id_template);
            if ($stmt->execute()) {
                catat_log($koneksi, $_SESSION['admin_id'], 'Edit Kode Surat', "Mengubah kode surat master menjadi: $kode_baru");
                $pesan = "<div class='alert-success'>Kode Surat master berhasil diperbarui!</div>";
            } else {
                $pesan = "<div class='alert-error'>Gagal menyimpan pembaruan.</div>";
            }
            $stmt->close();
        }
    }
}

// --- PROSES UPDATE NOMOR SURAT (RIWAYAT TERBIT) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_nomor_surat'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $pesan = "<div class='alert-error'>Sesi tidak valid. Muat ulang halaman.</div>";
    } else {
        $id_transaksi = (int)$_POST['id_transaksi'];
        $nomor_baru = $koneksi->real_escape_string(trim($_POST['nomor_surat']));
        
        $cek = $koneksi->query("SELECT id FROM transaksi_surat WHERE nomor_surat = '$nomor_baru' AND id != $id_transaksi");
        if ($cek->num_rows > 0) {
            $pesan = "<div class='alert-error'>Gagal! Nomor Surat <b>$nomor_baru</b> sudah tercatat di arsip lain.</div>";
        } else {
            $stmt = $koneksi->prepare("UPDATE transaksi_surat SET nomor_surat = ? WHERE id = ?");
            $stmt->bind_param("si", $nomor_baru, $id_transaksi);
            if ($stmt->execute()) {
                catat_log($koneksi, $_SESSION['admin_id'], 'Edit Nomor Surat', "Merevisi nomor surat keluar menjadi: $nomor_baru");
                $pesan = "<div class='alert-success'>Nomor Surat fisik berhasil disinkronisasi!</div>";
            } else {
                $pesan = "<div class='alert-error'>Gagal menyimpan pembaruan.</div>";
            }
            $stmt->close();
        }
    }
}

// Fitur Pencarian & Filter
$search = isset($_GET['search']) ? $koneksi->real_escape_string($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? $koneksi->real_escape_string($_GET['kategori']) : '';

$sql = "SELECT * FROM arsip_desa WHERE 1=1 ";
if ($search != '') { $sql .= "AND judul_arsip LIKE '%$search%' "; }
if ($kategori != '') { $sql .= "AND kategori_arsip = '$kategori' "; }
$sql .= "ORDER BY tgl_dokumen DESC, uploaded_at DESC";

$result = $koneksi->query($sql);
?>

<style>
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; gap: 20px; flex-wrap: wrap; }
    .filter-group { display: flex; gap: 15px; flex-grow: 1; }
    
    .form-control { background: var(--bg-light); border-radius: 8px; padding: 10px 15px; border: 1px solid #e2e8f0; font-size: 14px; outline: none; transition: var(--transition); }
    .form-control:focus { border-color: var(--secondary); background: #fff; box-shadow: 0 0 0 3px rgba(89, 213, 224, 0.2); }
    
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { background: #f8fafc; color: var(--text-muted); padding: 15px; text-align: left; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
    .admin-table td { padding: 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; vertical-align: middle; color: var(--text-dark); }
    .admin-table tr:hover td { background: #f8fafc; }
    
    .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .badge-masuk { background: #dcfce7; color: #166534; }
    .badge-keluar { background: #ffedd5; color: #9a3412; }
    .badge-penting { background: #fee2e2; color: #991b1b; }
    .badge-inventaris { background: #e0e7ff; color: #3730a3; }
    .badge-lainnya { background: #f3f4f6; color: #4b5563; }
    
    .btn-primary { background: var(--primary); color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; border: none; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-primary:hover { background: var(--sidebar-hover); transform: translateY(-2px); }
    
    .btn-success { background: #10b981; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; border: none; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-success:hover { background: #059669; transform: translateY(-2px); }
    
    .btn-outline { background: transparent; border: 1px solid var(--text-muted); color: var(--text-muted); padding: 10px 15px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: 0.3s; }
    .btn-outline:hover { background: #e2e8f0; color: var(--text-dark); }
    
    .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }

    /* MODAL PREVIEW STYLING */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); z-index: 9999; display: none; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
    .modal-box { background: #fff; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: slideUp 0.3s ease-out; }
    .modal-header { background: var(--primary); color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
    .close-modal { background: none; border: none; color: white; font-size: 20px; cursor: pointer; transition: 0.2s; }
    .close-modal:hover { color: #f87171; transform: scale(1.1); }
    @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<div class="page-header">
    <h1 class="page-title">Manajemen Arsip Digital</h1>
    <p class="page-subtitle">Penyimpanan terpusat untuk persuratan, inventaris, dan dokumen tata kelola pemerintahan desa.</p>
</div>

<div class="admin-card">
    <div class="table-header">
        <form action="" method="GET" class="filter-group">
            <input type="text" name="search" class="form-control" style="width: 250px;" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul arsip...">
            
            <select name="kategori" class="form-control" style="width: 200px;">
                <option value="">Semua Kategori</option>
                <option value="Surat Masuk" <?= $kategori == 'Surat Masuk' ? 'selected' : '' ?>>Surat Masuk</option>
                <option value="Surat Keluar" <?= $kategori == 'Surat Keluar' ? 'selected' : '' ?>>Surat Keluar</option>
                <option value="Dokumen Penting" <?= $kategori == 'Dokumen Penting' ? 'selected' : '' ?>>Dokumen Penting</option>
                <option value="Catatan Inventaris" <?= $kategori == 'Catatan Inventaris' ? 'selected' : '' ?>>Catatan Inventaris</option>
                <option value="Lainnya" <?= $kategori == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
            </select>
            <button type="submit" class="btn-primary" style="padding: 10px 15px;"><i class="fa-solid fa-filter"></i></button>
            <a href="manajemen_arsip.php" class="btn-outline">Reset</a>
        </form>
        
        <div class="action-buttons">
            <a href="export_arsip.php?search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>" class="btn-success">
                <i class="fa-solid fa-file-csv"></i> Export Data
            </a>
            <a href="tambah_arsip.php" class="btn-primary">
                <i class="fa-solid fa-cloud-arrow-up"></i> Unggah Dokumen
            </a>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kategori</th>
                    <th>Detail / Judul Dokumen</th>
                    <th>Tgl Terbit</th>
                    <th style="text-align: center;">File</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): $no = 1; while ($row = $result->fetch_assoc()): 
                    $badge_class = "badge-masuk"; $icon = "fa-inbox";
                    
                    if ($row['kategori_arsip'] == 'Surat Keluar') { 
                        $badge_class = "badge-keluar"; $icon = "fa-paper-plane"; 
                    } elseif ($row['kategori_arsip'] == 'Dokumen Penting') { 
                        $badge_class = "badge-penting"; $icon = "fa-file-shield"; 
                    } elseif ($row['kategori_arsip'] == 'Catatan Inventaris') { 
                        $badge_class = "badge-inventaris"; $icon = "fa-boxes-stacked"; 
                    } elseif ($row['kategori_arsip'] == 'Lainnya') { 
                        $badge_class = "badge-lainnya"; $icon = "fa-folder-open"; 
                    }

                    $folder = ($row['kategori_arsip'] == 'Foto Kegiatan') ? "foto_kegiatan/" : "arsip_dokumen/";
                    $file_url = "../assets/uploads/" . $folder . htmlspecialchars($row['file_path']);
                    $file_ext = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><span class="badge <?= $badge_class ?>"><i class="fa-solid <?= $icon ?>"></i> <?= htmlspecialchars($row['kategori_arsip']) ?></span></td>
                        <td>
                            <strong style="color: var(--primary); font-size: 15px;"><?= htmlspecialchars($row['judul_arsip']) ?></strong><br>
                            <small style="color: var(--text-muted);">No: <?= $row['nomor_dokumen'] ? htmlspecialchars($row['nomor_dokumen']) : '-' ?></small>
                        </td>
                        <td style="font-weight: 500;"><?= date('d M Y', strtotime($row['tgl_dokumen'])) ?></td>
                        <td style="text-align: center;">
                            <!-- Tombol ini memicu fungsi popup -->
                            <button onclick="bukaModalPreview('<?= $file_url ?>', '<?= $file_ext ?>')" class="btn-primary" style="padding: 6px 12px; font-size: 13px;">
                                <i class="fa-solid fa-eye"></i> Lihat
                            </button>
                        </td>
                        <td style="text-align: center;">
                            <a href="hapus_arsip.php?id=<?= $row['id'] ?>" onclick="return confirm('Hapus arsip ini permanen?')" style="color: #ef4444; font-size: 18px;"><i class="fa-solid fa-trash-can"></i></a>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">Tidak ada arsip yang sesuai dengan pencarian/filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ==============================================
     MODAL POPUP PRATINJAU DOKUMEN
     ============================================== -->
<div class="modal-overlay" id="previewModal">
    <div class="modal-box" style="width: 85%; max-width: 1000px; height: 90vh;">
        <div class="modal-header">
            <h3 style="margin: 0; font-size: 16px;"><i class="fa-solid fa-file-magnifying-glass"></i> Pratinjau Dokumen</h3>
            <button type="button" class="close-modal" onclick="tutupModalPreview()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="previewContainer" style="flex-grow: 1; padding: 0; background: #cbd5e1; display: flex; justify-content: center; align-items: center; height: calc(100% - 110px); overflow: hidden;">
            <!-- Konten iFrame / Gambar akan disuntikkan di sini via JavaScript -->
        </div>
        <div class="modal-footer" style="padding: 15px 20px; background: #fff; text-align: right; border-top: 1px solid #e2e8f0;">
            <a href="#" id="btnDownloadOverlay" download class="btn-success" style="padding: 10px 20px; text-decoration: none;">
                <i class="fa-solid fa-download"></i> Unduh File Asli
            </a>
        </div>
    </div>
</div>

<script>
    function bukaModalPreview(fileUrl, fileExt) {
        const container = document.getElementById('previewContainer');
        const btnDownload = document.getElementById('btnDownloadOverlay');
        const modal = document.getElementById('previewModal');
        
        // Atur link untuk tombol download di footer modal
        btnDownload.href = fileUrl;
        container.innerHTML = '<div style="padding:20px; font-weight:600; color:#475569;">Memuat dokumen <i class="fa-solid fa-spinner fa-spin"></i></div>';
        
        // Render konten berdasarkan ekstensi file
        if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(fileExt)) {
            container.innerHTML = `<img src="${fileUrl}" style="max-width:100%; max-height:100%; object-fit:contain; padding: 10px;">`;
        } else if (fileExt === 'pdf') {
            container.innerHTML = `<iframe src="${fileUrl}#toolbar=0" style="width:100%; height:100%; border:none;"></iframe>`;
        } else {
            container.innerHTML = `
                <div style="text-align:center; padding: 40px; background: #fff; border-radius: 8px;">
                    <i class="fa-solid fa-file-circle-exclamation" style="font-size: 50px; color: #94a3b8; margin-bottom:15px;"></i>
                    <p style="color: #475569; font-weight: 600;">Pratinjau langsung tidak tersedia untuk format file (.${fileExt}).</p>
                    <p style="color: #64748b; font-size: 13px;">Silakan unduh file untuk membukanya di perangkat Anda.</p>
                </div>
            `;
        }
        
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Menghentikan scroll pada background
    }

    function tutupModalPreview() {
        document.getElementById('previewModal').style.display = 'none';
        document.getElementById('previewContainer').innerHTML = ''; // Membersihkan memory iframe/image
        document.body.style.overflow = '';
    }
</script>

<?php require_once 'includes/admin_footer.php'; ?>