<?php
// admin/data_penduduk.php
require_once '../config/koneksi.php';
require_once 'includes/admin_header.php';

$pesan = '';

// --- A. LOGIKA IMPORT DATA CSV (EXCEL) MASAL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['import_csv'])) {
    $fileName = $_FILES["file_csv"]["tmp_name"];
    if ($_FILES["file_csv"]["size"] > 0) {
        $file = fopen($fileName, "r");
        $is_header = true;
        $sukses = 0; $gagal = 0;
        
        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            if($is_header) { $is_header = false; continue; }
            
            // Sinkronisasi Urutan Kolom CSV (No KK ditambahkan di kolom pertama/0)
            $no_kk = $koneksi->real_escape_string($column[0] ?? '');
            $nik = $koneksi->real_escape_string($column[1] ?? '');
            $nama = $koneksi->real_escape_string($column[2] ?? '');
            $tempat_lahir = $koneksi->real_escape_string($column[3] ?? '');
            $tgl_lahir = $koneksi->real_escape_string($column[4] ?? ''); 
            $jk = $koneksi->real_escape_string($column[5] ?? '');
            $agama = $koneksi->real_escape_string($column[6] ?? '');
            $status_kawin = $koneksi->real_escape_string($column[7] ?? '');
            $pekerjaan = $koneksi->real_escape_string($column[8] ?? '');
            $kewarganegaraan = $koneksi->real_escape_string($column[9] ?? '');
            $rt = $koneksi->real_escape_string($column[10] ?? '');
            $rw = $koneksi->real_escape_string($column[11] ?? '');
            $nama_dusun = $koneksi->real_escape_string($column[12] ?? '');
            
            $kel_desa = "Serage"; $kecamatan = "Praya Barat Daya"; $kabupaten = "Lombok Tengah"; $provinsi = "Nusa Tenggara Barat";

            try { $bday = new DateTime($tgl_lahir); $today = new DateTime('today'); $umur = $bday->diff($today)->y;
            } catch (Exception $e) { $umur = 0; }

            if(strtolower($jk) == 'l') $jk = 'Laki-laki';
            if(strtolower($jk) == 'p') $jk = 'Perempuan';

            $cek = $koneksi->query("SELECT id FROM penduduk WHERE nik = '$nik'");
            if ($cek->num_rows == 0 && !empty($nik) && !empty($no_kk)) {
                $koneksi->query("INSERT INTO penduduk (no_kk, nik, nama_lengkap, tempat_lahir, tgl_lahir, umur, jenis_kelamin, agama, status_perkawinan, pekerjaan, kewarganegaraan, rt, rw, nama_dusun, kel_desa, kecamatan, kabupaten, provinsi) VALUES ('$no_kk', '$nik', '$nama', '$tempat_lahir', '$tgl_lahir', $umur, '$jk', '$agama', '$status_kawin', '$pekerjaan', '$kewarganegaraan', '$rt', '$rw', '$nama_dusun', '$kel_desa', '$kecamatan', '$kabupaten', '$provinsi')");
                $sukses++;
            } else { $gagal++; }
        }
        fclose($file);
        $pesan = "<div class='alert-success'>Import selesai! <b>$sukses</b> data berhasil, <b>$gagal</b> gagal (duplikat/kosong).</div>";
        bersihkan_cache_statistik();
    }
}

// --- B. LOGIKA SIMPAN & UPDATE DATA MANUAL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_penduduk'])) {
    $mode = $_POST['mode'];
    $id_penduduk = (int)$_POST['id_penduduk'];
    
    // Penangkapan Variabel no_kk
    $no_kk = $koneksi->real_escape_string($_POST['no_kk']);
    $nik = $koneksi->real_escape_string($_POST['nik']);
    $nama = $koneksi->real_escape_string($_POST['nama_lengkap']);
    $tempat_lahir = $koneksi->real_escape_string($_POST['tempat_lahir']);
    $tgl_lahir = $koneksi->real_escape_string($_POST['tgl_lahir']);
    $jk = $koneksi->real_escape_string($_POST['jenis_kelamin']);
    $rt = $koneksi->real_escape_string($_POST['rt']);
    $rw = $koneksi->real_escape_string($_POST['rw']);
    $nama_dusun = $koneksi->real_escape_string($_POST['nama_dusun']);
    
    $kel_desa = $koneksi->real_escape_string($_POST['kel_desa']);
    $kecamatan = $koneksi->real_escape_string($_POST['kecamatan']);
    $kabupaten = $koneksi->real_escape_string($_POST['kabupaten']);
    $provinsi = $koneksi->real_escape_string($_POST['provinsi']);
    $agama = $koneksi->real_escape_string($_POST['agama']);
    $status_kawin = $koneksi->real_escape_string($_POST['status_perkawinan']);
    $pekerjaan = $koneksi->real_escape_string($_POST['pekerjaan']);
    $kewarganegaraan = $koneksi->real_escape_string($_POST['kewarganegaraan']);

    $bday = new DateTime($tgl_lahir); 
    $today = new DateTime('today'); 
    $umur = $bday->diff($today)->y;

    $data_lama = [];
    if ($mode == 'edit') {
        $q_lama = $koneksi->query("SELECT foto_warga, arsip_foto_ktp_kk FROM penduduk WHERE id = $id_penduduk");
        if ($q_lama) $data_lama = $q_lama->fetch_assoc();
    }

    $foto_warga = ($mode == 'edit') ? $data_lama['foto_warga'] : null; 
    $arsip_ktp = ($mode == 'edit') ? $data_lama['arsip_foto_ktp_kk'] : null;

    if (isset($_FILES['foto_warga']) && $_FILES['foto_warga']['error'] == 0) {
        $up_foto = upload_file_aman("foto_warga", "../assets/uploads/warga/", ["jpg","jpeg","png"], 2048);
        if ($up_foto['status']) {
            $foto_warga = $up_foto['nama_file'];
            if ($mode == 'edit' && !empty($data_lama['foto_warga']) && file_exists("../assets/uploads/warga/".$data_lama['foto_warga'])) {
                unlink("../assets/uploads/warga/".$data_lama['foto_warga']);
            }
        }
    }
    
    if (isset($_FILES['arsip_foto_ktp_kk']) && $_FILES['arsip_foto_ktp_kk']['error'] == 0) {
        $up_arsip = upload_file_aman("arsip_foto_ktp_kk", "../assets/uploads/arsip_ktp/", ["jpg","jpeg","png","pdf"], 5120);
        if ($up_arsip['status']) {
            $arsip_ktp = $up_arsip['nama_file'];
            if ($mode == 'edit' && !empty($data_lama['arsip_foto_ktp_kk']) && file_exists("../assets/uploads/arsip_ktp/".$data_lama['arsip_foto_ktp_kk'])) {
                unlink("../assets/uploads/arsip_ktp/".$data_lama['arsip_foto_ktp_kk']);
            }
        }
    }

    if ($mode == 'tambah') {
        $cek = $koneksi->query("SELECT id FROM penduduk WHERE nik = '$nik'");
        if ($cek->num_rows > 0) {
            $pesan = "<div class='alert-error'>Gagal! NIK <b>$nik</b> sudah terdaftar.</div>";
        } else {
            // Sinkronisasi parameter bind_param
            $stmt = $koneksi->prepare("INSERT INTO penduduk (foto_warga, no_kk, nik, nama_lengkap, tempat_lahir, tgl_lahir, umur, jenis_kelamin, rt, rw, nama_dusun, kel_desa, kecamatan, kabupaten, provinsi, agama, status_perkawinan, pekerjaan, kewarganegaraan, arsip_foto_ktp_kk) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssisssssssssssss", $foto_warga, $no_kk, $nik, $nama, $tempat_lahir, $tgl_lahir, $umur, $jk, $rt, $rw, $nama_dusun, $kel_desa, $kecamatan, $kabupaten, $provinsi, $agama, $status_kawin, $pekerjaan, $kewarganegaraan, $arsip_ktp);
            
            if ($stmt->execute()) {
                $pesan = "<div class='alert-success'>Data kependudukan berhasil ditambahkan!</div>";
                catat_log($koneksi, $_SESSION['admin_id'], 'Tambah Penduduk', "Menambahkan warga baru: $nama (NIK: $nik)");
            } else {
                $pesan = "<div class='alert-error'>Gagal menyimpan data.</div>";
            }
            $stmt->close();
        }
    } elseif ($mode == 'edit') {
        $stmt = $koneksi->prepare("UPDATE penduduk SET foto_warga=?, no_kk=?, nik=?, nama_lengkap=?, tempat_lahir=?, tgl_lahir=?, umur=?, jenis_kelamin=?, rt=?, rw=?, nama_dusun=?, kel_desa=?, kecamatan=?, kabupaten=?, provinsi=?, agama=?, status_perkawinan=?, pekerjaan=?, kewarganegaraan=?, arsip_foto_ktp_kk=? WHERE id=?");
        $stmt->bind_param("ssssssisssssssssssssi", $foto_warga, $no_kk, $nik, $nama, $tempat_lahir, $tgl_lahir, $umur, $jk, $rt, $rw, $nama_dusun, $kel_desa, $kecamatan, $kabupaten, $provinsi, $agama, $status_kawin, $pekerjaan, $kewarganegaraan, $arsip_ktp, $id_penduduk);
        
        if ($stmt->execute()) {
            $pesan = "<div class='alert-success'>Data kependudukan berhasil diperbarui!</div>";
            catat_log($koneksi, $_SESSION['admin_id'], 'Edit Penduduk', "Memperbarui data NIK: $nik");
        } else {
            $pesan = "<div class='alert-error'>Gagal memperbarui data. NIK mungkin duplikat.</div>";
        }
        $stmt->close();
    }
    bersihkan_cache_statistik();
}

// --- LOGIKA HAPUS DATA PENDUDUK ---
if (isset($_GET['hapus'])) {
    $id_h = (int)$_GET['hapus'];
    
    $q_del = $koneksi->query("SELECT nik, nama_lengkap, foto_warga, arsip_foto_ktp_kk FROM penduduk WHERE id = $id_h");
    if ($q_del && $q_del->num_rows > 0) {
        $data_del = $q_del->fetch_assoc();
        
        if (!empty($data_del['foto_warga']) && file_exists("../assets/uploads/warga/".$data_del['foto_warga'])) {
            unlink("../assets/uploads/warga/".$data_del['foto_warga']);
        }
        if (!empty($data_del['arsip_foto_ktp_kk']) && file_exists("../assets/uploads/arsip_ktp/".$data_del['arsip_foto_ktp_kk'])) {
            unlink("../assets/uploads/arsip_ktp/".$data_del['arsip_foto_ktp_kk']);
        }
        
        if ($koneksi->query("DELETE FROM penduduk WHERE id = $id_h")) {
            bersihkan_cache_statistik();
            catat_log($koneksi, $_SESSION['admin_id'], 'Hapus Penduduk', "Menghapus permanen NIK: {$data_del['nik']} ({$data_del['nama_lengkap']})");
        }
    }
    header("Location: data_penduduk.php");
    exit;
}

// --- C. LOGIKA FILTERING & PAGINATION ---
$where_clauses = ["1=1"];
$search = isset($_GET['search']) ? $koneksi->real_escape_string($_GET['search']) : '';
$f_jk = isset($_GET['f_jk']) ? $koneksi->real_escape_string($_GET['f_jk']) : '';
$sort = isset($_GET['sort']) ? $koneksi->real_escape_string($_GET['sort']) : 'id DESC';

// Pencarian kini juga mencakup no_kk
if ($search != '') $where_clauses[] = "(nik LIKE '%$search%' OR no_kk LIKE '%$search%' OR nama_lengkap LIKE '%$search%')";
if ($f_jk == 'Laki-laki') $where_clauses[] = "(jenis_kelamin = 'Laki-laki' OR jenis_kelamin = 'l')";
if ($f_jk == 'Perempuan') $where_clauses[] = "(jenis_kelamin = 'Perempuan' OR jenis_kelamin = 'p')";
$where_sql = implode(' AND ', $where_clauses);

$limit = 15; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$q_total = $koneksi->query("SELECT COUNT(id) AS total FROM penduduk WHERE $where_sql");
$total_data = $q_total->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

$q_penduduk = $koneksi->query("SELECT * FROM penduduk WHERE $where_sql ORDER BY $sort LIMIT $limit OFFSET $offset");
$url_params = "&search=".urlencode($search)."&f_jk=".urlencode($f_jk)."&sort=".urlencode($sort);
?>

<!-- Library OCR yang Dioptimalkan (v4) -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>

<style>
    .alert-success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #22c55e; font-size: 14px; }
    .alert-error { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-size: 14px; }
    
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: #4b5563; margin-bottom: 5px; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 13px; font-family: 'Poppins', sans-serif; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26, 111, 118, 0.1); }
    
    .btn { padding: 10px 15px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.3s; color: white; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
    .btn-primary { background: var(--primary); } .btn-primary:hover { background: #13555b; }
    .btn-excel { background: #10b981; } .btn-excel:hover { background: #059669; }
    .btn-pdf { background: #ef4444; } .btn-pdf:hover { background: #dc2626; }
    .btn-import { background: #8b5cf6; } .btn-import:hover { background: #7c3aed; }
    .btn-custom-export { background: #2563eb; } .btn-custom-export:hover { background: #1d4ed8; }
    
    .data-table { width: 100%; border-collapse: collapse; background: #fff; font-size: 13px; }
    .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .data-table th { background: var(--primary); color: white; white-space: nowrap; }
    .avatar-mini { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }

    /* PAGINATION */
    .pagination { display: flex; justify-content: flex-end; gap: 5px; margin-top: 20px; }
    .page-link { padding: 8px 12px; background: #fff; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 13px; color: #1e293b; text-decoration: none; font-weight: 600; transition: 0.2s; }
    .page-link:hover { background: #f1f5f9; }
    .page-link.active { background: var(--primary); color: #fff; border-color: var(--primary); }

    /* MODAL UMUM */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; display: none; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
    .modal-box { background: #fff; width: 95%; max-width: 900px; max-height: 90vh; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: slideUp 0.3s ease-out; }
    .modal-header { background: var(--primary); color: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 30px; overflow-y: auto; }
    .close-modal { background: none; border: none; color: white; font-size: 20px; cursor: pointer; }
    
    @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    /* KHUSUS MODAL DETAIL */
    .detail-grid { display: grid; grid-template-columns: 250px 1fr; gap: 30px; }
    @media(max-width: 768px){ .detail-grid { grid-template-columns: 1fr; } }
    .detail-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 2px; }
    .detail-value { font-size: 15px; color: #1e293b; font-weight: 600; margin-bottom: 15px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px; }

    /* OCR STATUS FEEDBACK */
    .ocr-wrapper { background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px dashed #3b82f6; margin-bottom: 25px; transition: 0.3s; }
    .ocr-wrapper.scanning { border-color: #0ea5e9; background: #f0f9ff; }
    .ocr-status { display: none; margin-top: 15px; }
    .ocr-progress-container { width: 100%; background-color: #e2e8f0; border-radius: 4px; overflow: hidden; height: 8px; margin-bottom: 5px; }
    .ocr-progress-bar { height: 100%; background-color: #0284c7; width: 0%; transition: width 0.2s ease; }
    .ocr-status-text { font-size: 12px; font-weight: 600; color: #0284c7; text-align: center; }
    
    #ktp-preview-box { width: 100%; height: 220px; background: #fff; border: 2px dashed #cbd5e0; border-radius: 8px; margin-top: 15px; display: none; overflow: hidden; position: relative; }
    #ktp-preview-img { width: 100%; height: 100%; object-fit: contain; }
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px;">
    <h1 class="page-title" style="margin: 0;"><i class="fa-solid fa-users-rectangle"></i> Master Data Kependudukan</h1>
    <div style="display: flex; gap: 10px;">
        <button class="btn btn-primary" onclick="openFormModal()"><i class="fa-solid fa-plus"></i> Tambah Warga Baru</button>
        <button class="btn btn-import" onclick="document.getElementById('import-box').style.display='block'"><i class="fa-solid fa-cloud-arrow-up"></i> Import CSV</button>
        <a href="template_penduduk.csv" download class="btn" style="background:#cbd5e0; color:#1e293b;"><i class="fa-solid fa-download"></i> Template</a>
    </div>
</div>

<?= $pesan ?>

<!-- KOTAK IMPORT CSV -->
<div id="import-box" class="admin-card" style="display: none; background: #f5f3ff; border: 1px dashed #8b5cf6; margin-bottom: 30px;">
    <h3 style="margin-top:0; color: #6d28d9;"><i class="fa-solid fa-file-csv"></i> Import Data Massal</h3>
    <form action="" method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center;">
        <input type="file" name="file_csv" accept=".csv" class="form-control" required style="background: white; max-width: 300px;">
        <button type="submit" name="import_csv" class="btn btn-import">Mulai Import</button>
        <button type="button" class="btn" style="background:#cbd5e0; color:#1e293b;" onclick="document.getElementById('import-box').style.display='none'">Batal</button>
    </form>
</div>

<!-- AREA TABEL FULL WIDTH -->
<div class="admin-card" style="overflow-x: auto;">
    <div style="background: #f8fafc; padding: 15px; border-radius: 12px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 20px; border: 1px solid #e2e8f0;">
        <form action="" method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; flex-grow: 1;">
            <input type="text" name="search" class="form-control" placeholder="Cari NIK / No KK / Nama..." value="<?= htmlspecialchars($search) ?>" style="width: 250px;">
            <select name="f_jk" class="form-control" style="width: 150px;"><option value="">Semua Gender</option><option value="Laki-laki" <?= $f_jk == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option><option value="Perempuan" <?= $f_jk == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option></select>
            <select name="sort" class="form-control" style="width: 160px;"><option value="id DESC" <?= $sort == 'id DESC' ? 'selected' : '' ?>>Terbaru</option><option value="nama_lengkap ASC" <?= $sort == 'nama_lengkap ASC' ? 'selected' : '' ?>>Nama (A-Z)</option><option value="umur DESC" <?= $sort == 'umur DESC' ? 'selected' : '' ?>>Usia Tertua</option></select>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="data_penduduk.php" class="btn" style="background:#cbd5e0; color:#1e293b;">Reset</a>
        </form>
        
        <div style="display: flex; gap: 5px;">
            <button type="button" class="btn btn-custom-export" onclick="bukaModalExport()" title="Pilih Kolom Export"><i class="fa-solid fa-file-export"></i> Custom Export</button>
            <a href="export_pdf.php?search=<?= urlencode($search) ?>&f_jk=<?= urlencode($f_jk) ?>&sort=<?= urlencode($sort) ?>" target="_blank" class="btn btn-pdf" title="Export PDF"><i class="fa-solid fa-file-pdf"></i></a>
        </div>
    </div>

    <p style="font-size: 13px; color: #64748b; margin-bottom: 10px;">Menampilkan <b><?= $q_penduduk->num_rows ?></b> data dari total <b><?= $total_data ?></b> Warga.</p>

    <table class="data-table">
        <thead>
            <tr>
                <th>Foto</th><th>No. KK & NIK</th><th>Nama Lengkap</th><th>TTL / Umur</th><th>Alamat & Dusun</th><th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($q_penduduk && $q_penduduk->num_rows > 0): 
                while($p = $q_penduduk->fetch_assoc()): 
                    $tampil_jk = $p['jenis_kelamin'];
                    if (strtolower($tampil_jk) == 'l') $tampil_jk = 'Laki-laki';
                    if (strtolower($tampil_jk) == 'p') $tampil_jk = 'Perempuan';
            ?>
            <tr>
                <td>
                    <?php if($p['foto_warga']): ?>
                        <img src="../assets/uploads/warga/<?= $p['foto_warga'] ?>" class="avatar-mini">
                    <?php else: ?>
                        <div class="avatar-mini" style="background:#e2e8f0; display:flex; justify-content:center; align-items:center;"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="font-size: 11px; color: #64748b;">KK: <?= htmlspecialchars($p['no_kk']) ?></span><br>
                    <strong style="color: var(--primary);"><?= htmlspecialchars($p['nik']) ?></strong>
                </td>
                <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                <td><?= htmlspecialchars($p['tempat_lahir']) ?>, <?= date('d/m/Y', strtotime($p['tgl_lahir'])) ?><br><span style="color:#64748b; font-size:11px;"><?= $p['umur'] ?> Tahun | <?= $tampil_jk ?></span></td>
                <td>RT <?= $p['rt'] ?>/RW <?= $p['rw'] ?><br><span style="color:#64748b; font-size:11px;">Dsn. <?= htmlspecialchars($p['nama_dusun']) ?></span></td>
                <td style="white-space: nowrap;">
                    <button class="btn" style="background:#3b82f6; padding:6px 10px;" onclick='showDetail(<?= json_encode($p) ?>)' title="Lihat Detail Kartu Keluarga"><i class="fa-solid fa-eye"></i></button>
                    <button class="btn" style="background:#f59e0b; padding:6px 10px;" onclick='editData(<?= json_encode($p) ?>)' title="Edit Data"><i class="fa-solid fa-pen"></i></button>
                    <a href="?hapus=<?= $p['id'] ?>" class="btn btn-pdf" style="padding:6px 10px;" onclick="return confirm('Hapus permanen?');"><i class="fa-solid fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="6" style="text-align: center; padding: 30px;">Tidak ada data ditemukan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if($total_pages > 1): ?>
    <div class="pagination">
        <?php if($page > 1): ?> <a href="?page=<?= $page-1 ?><?= $url_params ?>" class="page-link">&laquo; Prev</a> <?php endif; ?>
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?page=<?= $i ?><?= $url_params ?>" class="page-link <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if($page < $total_pages): ?> <a href="?page=<?= $page+1 ?><?= $url_params ?>" class="page-link">Next &raquo;</a> <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ==============================================
     MODAL 1: FORMULIR INPUT & EDIT PENDUDUK
     ============================================== -->
<div class="modal-overlay" id="formModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="form-title" style="margin:0; font-size:18px;"><i class="fa-solid fa-user-plus"></i> Formulir Warga Baru</h3>
            <button class="close-modal" onclick="closeFormModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form action="" method="POST" enctype="multipart/form-data" id="form_penduduk">
                <input type="hidden" name="mode" id="mode" value="tambah">
                <input type="hidden" name="id_penduduk" id="id_penduduk" value="">
                
                <!-- SMART KTP/KK SCANNER AREA -->
                <div id="ocr-wrapper" class="ocr-wrapper">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="color: #0284c7; font-size: 14px;"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Fill Cerdas (Scan KTP/KK)</label>
                        <div style="display:flex; gap:10px; margin-top:5px; margin-bottom:10px;">
                            <select id="jenis_dokumen_ocr" class="form-control" style="width:30%; background:#fff;">
                                <option value="ktp">Dokumen KTP</option>
                                <option value="kk">Dokumen Kartu Keluarga (KK)</option>
                            </select>
                            <input type="file" name="arsip_foto_ktp_kk" id="arsip_ktp" class="form-control" accept=".jpg,.png,.jpeg" style="width:70%; background:#fff;" onchange="processOCR(this)">
                        </div>
                        <span style="font-size: 11px; color: #64748b;">(Pilih jenis dokumen lalu upload foto. AI akan membaca teks dan mengisi form otomatis)</span>
                    </div>
                    
                    <div id="ocr-status" class="ocr-status">
                        <div class="ocr-progress-container"><div id="ocr-progress-bar" class="ocr-progress-bar"></div></div>
                        <div id="ocr-status-text" class="ocr-status-text">Memuat Kecerdasan Buatan...</div>
                    </div>
                    
                    <div id="ktp-preview-box">
                        <img id="ktp-preview-img" src="">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group"><label>No. KK (16 Digit)*</label><input type="text" name="no_kk" id="no_kk" class="form-control" required maxlength="16" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="Cth: 520204..."></div>
                    <div class="form-group"><label>NIK (16 Digit)*</label><input type="text" name="nik" id="nik" class="form-control" required maxlength="16" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="Cth: 520204..."></div>
                    <div class="form-group" style="grid-column: span 2;"><label>Nama Lengkap*</label><input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" required placeholder="Cth: BUDI SANTOSO"></div>
                    <div class="form-group"><label>Tempat Lahir*</label><input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control" required></div>
                    <div class="form-group"><label>Tanggal Lahir*</label><input type="date" name="tgl_lahir" id="tgl_lahir" class="form-control" required></div>
                    
                    <div class="form-group"><label>Jenis Kelamin*</label><select name="jenis_kelamin" id="jenis_kelamin" class="form-control" required><option value="Laki-laki">Laki-laki</option><option value="Perempuan">Perempuan</option></select></div>
                    <div class="form-group"><label>Agama*</label><select name="agama" id="agama" class="form-control" required><option value="Islam">Islam</option><option value="Kristen">Kristen</option><option value="Katolik">Katolik</option><option value="Hindu">Hindu</option><option value="Buddha">Buddha</option><option value="Konghucu">Konghucu</option></select></div>
                    <div class="form-group"><label>Status Perkawinan*</label><select name="status_perkawinan" id="status_perkawinan" class="form-control" required><option value="Belum Kawin">Belum Kawin</option><option value="Kawin">Kawin</option><option value="Cerai Hidup">Cerai Hidup</option><option value="Cerai Mati">Cerai Mati</option></select></div>
                    <div class="form-group"><label>Pekerjaan*</label><input type="text" name="pekerjaan" id="pekerjaan" class="form-control" required></div>
                    
                    <div style="display: flex; gap: 10px; grid-column: 1 / -1;">
                        <div class="form-group" style="flex:1;"><label>RT*</label><input type="text" name="rt" id="rt" class="form-control" required placeholder="001"></div>
                        <div class="form-group" style="flex:1;"><label>RW*</label><input type="text" name="rw" id="rw" class="form-control" required placeholder="002"></div>
                        <div class="form-group" style="flex:2;"><label>Nama Dusun*</label><input type="text" name="nama_dusun" id="nama_dusun" class="form-control" required placeholder="Cth: Dasan Lekong"></div>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;"><label>Pasfoto Warga (Bila ada)</label><input type="file" name="foto_warga" class="form-control" accept="image/*" style="background:#fff;"></div>
                </div>

                <input type="hidden" name="kel_desa" id="kel_desa" value="Serage">
                <input type="hidden" name="kecamatan" id="kecamatan" value="Praya Barat Daya">
                <input type="hidden" name="kabupaten" id="kabupaten" value="Lombok Tengah">
                <input type="hidden" name="provinsi" id="provinsi" value="Nusa Tenggara Barat">
                <input type="hidden" name="kewarganegaraan" id="kewarganegaraan" value="WNI">
                
                <div style="display: flex; gap:10px; margin-top: 30px;">
                    <button type="button" class="btn" style="background:#cbd5e0; color:#1e293b; flex:1; justify-content:center;" onclick="closeFormModal()">Batal</button>
                    <button type="submit" name="simpan_penduduk" id="btn-submit" class="btn btn-primary" style="flex:2; justify-content: center;"><i class="fa-solid fa-save"></i> SIMPAN DATA WARGA</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==============================================
     MODAL 2: DETAIL KARTU DIGITAL Warga
     ============================================== -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="margin:0; font-size:18px;"><i class="fa-solid fa-id-card-clip"></i> Detail Rekam Jejak Warga</h3>
            <button class="close-modal" onclick="document.getElementById('detailModal').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body detail-grid">
            <div>
                <div class="detail-label">Pasfoto Resmi</div>
                <img id="dt-foto" src="" style="width:100%; aspect-ratio:4/5; object-fit:cover; border-radius:8px; border:2px solid #e2e8f0; margin-bottom: 20px;">
                <div class="detail-label">Arsip Identitas (KTP/KK)</div>
                <a id="dt-ktp-link" href="#" target="_blank" class="btn btn-primary" style="width:100%; justify-content:center;"><i class="fa-solid fa-file-contract"></i> Buka Dokumen Asli</a>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0 20px; align-content: start;">
                <div style="grid-column: span 2;"><div class="detail-label">Nomor Kartu Keluarga (KK)</div><div class="detail-value" id="dt-nokk" style="font-size:16px; color:#64748b;"></div></div>
                <div style="grid-column: span 2;"><div class="detail-label">Nomor Induk Kependudukan (NIK)</div><div class="detail-value" id="dt-nik" style="font-size:20px; color:var(--primary);"></div></div>
                <div style="grid-column: span 2;"><div class="detail-label">Nama Lengkap (Sesuai Dokumen)</div><div class="detail-value" id="dt-nama" style="font-size:18px;"></div></div>
                <div><div class="detail-label">Tempat Lahir</div><div class="detail-value" id="dt-tempat"></div></div>
                <div><div class="detail-label">Tanggal Lahir (Umur)</div><div class="detail-value" id="dt-tanggal"></div></div>
                <div><div class="detail-label">Jenis Kelamin</div><div class="detail-value" id="dt-jk"></div></div>
                <div><div class="detail-label">Agama</div><div class="detail-value" id="dt-agama"></div></div>
                <div><div class="detail-label">Status Perkawinan</div><div class="detail-value" id="dt-kawin"></div></div>
                <div><div class="detail-label">Pekerjaan Utama</div><div class="detail-value" id="dt-pekerjaan"></div></div>
                <div><div class="detail-label">Wilayah Administrasi</div><div class="detail-value" id="dt-rtrw"></div></div>
                <div><div class="detail-label">Kewarganegaraan</div><div class="detail-value" id="dt-wn"></div></div>
                <div style="grid-column: span 2;"><div class="detail-label">Alamat Lengkap Kependudukan</div><div class="detail-value" id="dt-alamat" style="border:none;"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- ==============================================
     MODAL 3: EXPORT CUSTOM
     ============================================== -->
<div class="modal-overlay" id="modalExport">
    <div class="modal-box" style="max-width: 500px; height: auto;">
        <div class="modal-header" style="background: #2563eb;">
            <h3 style="margin:0; font-size:16px; color:white;"><i class="fa-solid fa-file-export"></i> Export Data Kustom</h3>
            <button type="button" class="close-modal" onclick="closeExportModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" style="padding: 25px; overflow-y: auto;">
            <form action="export_penduduk.php" method="POST">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="f_jk" value="<?= htmlspecialchars($f_jk) ?>">
                <input type="hidden" name="export_custom" value="1">

                <p style="margin-top:0; font-size:13.5px; color:#475569;">Centang informasi yang ingin Anda tampilkan pada file hasil unduhan (Excel/CSV):</p>
                
                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
                    <label style="font-weight:600; font-size:13px; cursor:pointer; color:#1a6f76; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="checkAll" style="width:16px; height:16px; accent-color: #1a6f76;" checked onchange="toggleAllCheckboxes(this)"> Pilih Semua Data
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; max-height: 350px; overflow-y: auto;">
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="no_kk" class="col-check" checked> Nomor KK</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="nik" class="col-check" checked> NIK</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="nama_lengkap" class="col-check" checked> Nama Lengkap</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="tempat_lahir" class="col-check" checked> Tempat Lahir</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="tgl_lahir" class="col-check" checked> Tanggal Lahir</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="umur" class="col-check" checked> Umur</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="jenis_kelamin" class="col-check" checked> Jenis Kelamin</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="pekerjaan" class="col-check" checked> Pekerjaan</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="agama" class="col-check"> Agama</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="status_perkawinan" class="col-check"> Status Kawin</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="rt" class="col-check" checked> RT</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="rw" class="col-check" checked> RW</label>
                    <label style="font-size:13.5px; cursor:pointer; display:flex; gap:8px;"><input type="checkbox" name="kolom[]" value="nama_dusun" class="col-check" checked> Dusun</label>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" style="background:#cbd5e0; border:none; border-radius:6px; font-weight:600; color:#1e293b; flex:1; cursor:pointer;" onclick="closeExportModal()">Batal</button>
                    <button type="submit" style="background:#2563eb; border:none; border-radius:6px; font-weight:600; color:#fff; padding:12px; flex:2; cursor:pointer;"><i class="fa-solid fa-download"></i> Unduh CSV</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openFormModal() {
        resetForm();
        document.getElementById('formModal').style.display = 'flex';
        document.body.style.overflow = 'hidden'; 
    }
    
    function closeFormModal() {
        document.getElementById('formModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function bukaModalExport() {
        document.getElementById('modalExport').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeExportModal() {
        document.getElementById('modalExport').style.display = 'none';
        document.body.style.overflow = '';
    }

    function toggleAllCheckboxes(source) {
        const checkboxes = document.querySelectorAll('.col-check');
        checkboxes.forEach(cb => cb.checked = source.checked);
    }

    function editData(data) {
        document.getElementById('mode').value = 'edit';
        document.getElementById('id_penduduk').value = data.id;
        document.getElementById('no_kk').value = data.no_kk;
        document.getElementById('nik').value = data.nik;
        document.getElementById('nama_lengkap').value = data.nama_lengkap;
        document.getElementById('tempat_lahir').value = data.tempat_lahir;
        document.getElementById('tgl_lahir').value = data.tgl_lahir;
        
        let jkData = data.jenis_kelamin ? data.jenis_kelamin.toLowerCase() : '';
        if (jkData === 'l' || jkData === 'laki-laki') document.getElementById('jenis_kelamin').value = 'Laki-laki';
        else if (jkData === 'p' || jkData === 'perempuan') document.getElementById('jenis_kelamin').value = 'Perempuan';
        else document.getElementById('jenis_kelamin').value = data.jenis_kelamin;

        document.getElementById('agama').value = data.agama;
        document.getElementById('status_perkawinan').value = data.status_perkawinan;
        document.getElementById('pekerjaan').value = data.pekerjaan;
        document.getElementById('rt').value = data.rt;
        document.getElementById('rw').value = data.rw;
        document.getElementById('nama_dusun').value = data.nama_dusun; 
        
        document.getElementById('form-title').innerHTML = "<i class='fa-solid fa-pen'></i> Edit Data Warga";
        document.getElementById('btn-submit').innerHTML = "<i class='fa-solid fa-check'></i> UPDATE DATA WARGA";
        document.getElementById('btn-submit').style.background = "#f59e0b";
        
        document.getElementById('formModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function resetForm() {
        document.getElementById('form_penduduk').reset();
        document.getElementById('mode').value = 'tambah';
        document.getElementById('id_penduduk').value = '';
        
        document.getElementById('ocr-status').style.display = 'none';
        document.getElementById('ocr-wrapper').classList.remove('scanning');
        document.getElementById('ktp-preview-box').style.display = 'none';
        
        document.getElementById('form-title').innerHTML = "<i class='fa-solid fa-user-plus'></i> Formulir Warga Baru";
        document.getElementById('btn-submit').innerHTML = "<i class='fa-solid fa-save'></i> SIMPAN DATA WARGA";
        document.getElementById('btn-submit').style.background = "var(--primary)";
    }

    function showDetail(data) {
        document.getElementById('dt-nokk').innerText = data.no_kk;
        document.getElementById('dt-nik').innerText = data.nik;
        document.getElementById('dt-nama').innerText = data.nama_lengkap;
        document.getElementById('dt-tempat').innerText = data.tempat_lahir;
        document.getElementById('dt-tanggal').innerText = data.tgl_lahir + " (" + data.umur + " Tahun)";
        
        let jkDetail = data.jenis_kelamin ? data.jenis_kelamin.toLowerCase() : '';
        if (jkDetail === 'l' || jkDetail === 'laki-laki') document.getElementById('dt-jk').innerText = 'Laki-laki';
        else if (jkDetail === 'p' || jkDetail === 'perempuan') document.getElementById('dt-jk').innerText = 'Perempuan';
        else document.getElementById('dt-jk').innerText = data.jenis_kelamin;

        document.getElementById('dt-agama').innerText = data.agama;
        document.getElementById('dt-kawin').innerText = data.status_perkawinan;
        document.getElementById('dt-pekerjaan').innerText = data.pekerjaan;
        document.getElementById('dt-rtrw').innerText = "RT " + data.rt + " / RW " + data.rw + " - Dusun " + data.nama_dusun;
        document.getElementById('dt-wn').innerText = data.kewarganegaraan;
        document.getElementById('dt-alamat').innerText = "Desa " + data.kel_desa + ", Kec. " + data.kecamatan + ", Kab. " + data.kabupaten + ", " + data.provinsi;
        
        document.getElementById('dt-foto').src = data.foto_warga ? "../assets/uploads/warga/" + data.foto_warga : "https://via.placeholder.com/250x300.png?text=Tanpa+Foto";
        
        const btnKtp = document.getElementById('dt-ktp-link');
        if(data.arsip_foto_ktp_kk) {
            btnKtp.href = "../assets/uploads/arsip_ktp/" + data.arsip_foto_ktp_kk;
            btnKtp.style.display = 'flex';
        } else { btnKtp.style.display = 'none'; }
        
        document.getElementById('detailModal').style.display = 'flex';
    }

    // --- AUTO-FILL OCR ---
    async function processOCR(input) {
        const jenisDokumen = document.getElementById('jenis_dokumen_ocr').value;
        const previewBox = document.getElementById('ktp-preview-box');
        const ocrWrapper = document.getElementById('ocr-wrapper');
        const statusEl = document.getElementById('ocr-status');
        const progressBar = document.getElementById('ocr-progress-bar');
        const statusText = document.getElementById('ocr-status-text');
        
        if (!input.files || input.files.length === 0) {
            previewBox.style.display = 'none';
            statusEl.style.display = 'none';
            ocrWrapper.classList.remove('scanning');
            return;
        }
        
        const file = input.files[0];
        ocrWrapper.classList.add('scanning');
        statusEl.style.display = 'block';
        progressBar.style.width = '10%';
        progressBar.style.backgroundColor = '#0284c7';
        statusText.innerText = "Mempersiapkan gambar untuk AI...";
        statusText.style.color = '#0284c7';
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('ktp-preview-img').src = e.target.result;
            previewBox.style.display = 'block';
        }
        reader.readAsDataURL(file);

        const img = new Image();
        img.src = URL.createObjectURL(file);

        img.onload = async function() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            const MAX_WIDTH = 1200;
            let scaleSize = MAX_WIDTH / img.width;
            if (scaleSize > 1) scaleSize = 1; 
            
            canvas.width = img.width * scaleSize;
            canvas.height = img.height * scaleSize;

            ctx.filter = 'grayscale(100%) contrast(200%) brightness(120%)';
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            const processedImageBase64 = canvas.toDataURL('image/jpeg', 0.9);

            try {
                const worker = await Tesseract.createWorker('ind', 1, {
                    logger: m => {
                        if (m.status === 'recognizing text') {
                            const pct = Math.round(m.progress * 100);
                            progressBar.style.width = pct + '%';
                            statusText.innerText = 'AI Sedang Membaca Dokumen: ' + pct + '%';
                        }
                    }
                });
                
                const ret = await worker.recognize(processedImageBase64);
                let text = ret.data.text.toUpperCase();
                
                console.log("Hasil Mentah OCR:\n", text);
                
                if(jenisDokumen === 'ktp') {
                    let cleanTextForNIK = text.replace(/\s/g, '').replace(/[ODQ]/g, '0').replace(/[IL|]/g, '1').replace(/[Z]/g, '2').replace(/[A]/g, '4').replace(/[S]/g, '5').replace(/[G]/g, '6').replace(/[T]/g, '7').replace(/[B]/g, '8');
                    const nikMatch = cleanTextForNIK.match(/\d{16}/); 
                    if (nikMatch) document.getElementById('nik').value = nikMatch[0];
                    
                    if (text.includes("ISLAM") || text.includes("1SLAM")) document.getElementById('agama').value = "Islam";
                    else if (text.includes("KRISTEN")) document.getElementById('agama').value = "Kristen";
                    
                    if (text.includes("LAKI") || text.includes("LAK1")) document.getElementById('jenis_kelamin').value = "Laki-laki";
                    else if (text.includes("PEREMPUAN") || text.includes("PUAN")) document.getElementById('jenis_kelamin').value = "Perempuan";

                    if (text.includes("BELUM KAWIN")) document.getElementById('status_perkawinan').value = "Belum Kawin";
                    else if (text.includes("CERAI HIDUP")) document.getElementById('status_perkawinan').value = "Cerai Hidup";
                    else if (text.includes("KAWIN") && !text.includes("BELUM")) document.getElementById('status_perkawinan').value = "Kawin";

                    const ttlMatch = text.match(/([A-Z\s]+),\s*(\d{2})[-/](\d{2})[-/](\d{4})/);
                    if(ttlMatch) {
                        let tempat = ttlMatch[1].replace(/LAHIR|TEMPAT/g, '').replace(/[^A-Z\s]/g, '').trim();
                        if(tempat.length > 2) document.getElementById('tempat_lahir').value = tempat;
                        document.getElementById('tgl_lahir').value = `${ttlMatch[4]}-${ttlMatch[3]}-${ttlMatch[2]}`;
                    } else {
                        const tglMatch = text.match(/\b(\d{2})[-/](\d{2})[-/](\d{4})\b/);
                        if (tglMatch) document.getElementById('tgl_lahir').value = `${tglMatch[3]}-${tglMatch[2]}-${tglMatch[1]}`;
                    }

                    const rtrwMatch = text.match(/RT.*?RW.*?(\d{3}).*?(\d{3})/i);
                    if(rtrwMatch) {
                        document.getElementById('rt').value = rtrwMatch[1];
                        document.getElementById('rw').value = rtrwMatch[2];
                    }

                    const lines = text.split('\n');
                    for(let i=0; i<lines.length; i++) {
                        if(lines[i].includes("NAMA")) {
                            let namaExtracted = lines[i].split("NAMA")[1].replace(/[^A-Z\s]/g, '').trim();
                            if(namaExtracted.length > 2) document.getElementById('nama_lengkap').value = namaExtracted;
                            else if(namaExtracted.length <= 2 && i + 1 < lines.length) document.getElementById('nama_lengkap').value = lines[i+1].replace(/[^A-Z\s]/g, '').trim();
                            break;
                        }
                    }
                } 
                else if (jenisDokumen === 'kk') {
                    const lines = text.split('\n');
                    let dataFound = false;

                    // Mengambil kumpulan 16 digit angka pertama sebagai Nomor KK
                    const allNumbers = text.replace(/\s/g, '').replace(/[ODQ]/g, '0').match(/\d{16}/g);
                    if(allNumbers && allNumbers.length > 0) {
                        document.getElementById('no_kk').value = allNumbers[0];
                    }

                    for(let i=0; i<lines.length; i++) {
                        let line = lines[i];
                        const kkMatch = line.match(/([A-Z\s\.,']+)\s+(\d{16})\s+([A-Z]+)\s+([A-Z\s]+)/);
                        
                        if(kkMatch) {
                            let extractedNama = kkMatch[1].replace(/[^A-Z\s]/g, '').trim();
                            if(extractedNama.length > 2) {
                                document.getElementById('nama_lengkap').value = extractedNama;
                                document.getElementById('nik').value = kkMatch[2];
                                
                                let jk = kkMatch[3];
                                if(jk.includes('LAKI') || jk.includes('LAK')) document.getElementById('jenis_kelamin').value = 'Laki-laki';
                                if(jk.includes('PEREMP') || jk.includes('PUAN')) document.getElementById('jenis_kelamin').value = 'Perempuan';
                                
                                document.getElementById('tempat_lahir').value = kkMatch[4].trim();
                                dataFound = true;
                                break;
                            }
                        }
                    }

                    if(!dataFound) {
                        alert("Gagal membaca baris detail tabel KK secara utuh, namun Nomor KK mungkin telah terisi. Silakan cek dan isi sisanya manual.");
                    }
                }

                progressBar.style.width = '100%';
                progressBar.style.backgroundColor = '#166534';
                statusText.innerText = `Ekstraksi ${jenisDokumen.toUpperCase()} Selesai! Cek kembali isian form.`;
                statusText.style.color = '#166534';
                ocrWrapper.classList.remove('scanning');
                
                await worker.terminate();
            } catch (error) {
                console.error(error);
                progressBar.style.width = '100%';
                progressBar.style.backgroundColor = '#b91c1c';
                statusText.innerText = "Gagal memindai dokumen. Lanjutkan pengisian secara manual.";
                statusText.style.color = '#b91c1c';
                ocrWrapper.classList.remove('scanning');
            }
        };
    }
</script>

<?php require_once 'includes/admin_footer.php'; ?>