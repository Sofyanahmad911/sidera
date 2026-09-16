<?php
// admin/edit_profil.php
require_once '../config/koneksi.php';
require_once 'includes/admin_header.php';

$pesan = "";

// --- A. SIMPAN 4 TEKS PROFIL & LOGO ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_profil'])) {
    $visi = $koneksi->real_escape_string($_POST['visi']);
    $misi = $koneksi->real_escape_string($_POST['misi']);
    $sejarah = $koneksi->real_escape_string($_POST['sejarah']);
    $tentang = $koneksi->real_escape_string($_POST['tentang_desa']);
    
    if (isset($_FILES['logo_desa']) && $_FILES['logo_desa']['error'] == 0) {
        $upload = upload_file_aman("logo_desa", "../assets/img/", ["png", "jpg", "jpeg"], 2048);
        if ($upload['status']) {
            $logo_baru = $upload['nama_file'];
            $old_data = $koneksi->query("SELECT logo_desa FROM profil_desa WHERE id = 1")->fetch_assoc();
            if(!empty($old_data['logo_desa']) && file_exists("../assets/img/".$old_data['logo_desa'])){
                unlink("../assets/img/".$old_data['logo_desa']);
            }
            $koneksi->query("UPDATE profil_desa SET logo_desa='$logo_baru' WHERE id=1");
            $pesan .= "<div class='alert-success'>Logo berhasil diunggah!</div>";
        } else {
            $pesan .= "<div class='alert-error'>Gagal unggah logo: " . $upload['pesan'] . "</div>";
        }
    }
    
    $koneksi->query("UPDATE profil_desa SET visi='$visi', misi='$misi', sejarah='$sejarah', tentang_desa='$tentang' WHERE id=1");
    if(empty($pesan)) $pesan = "<div class='alert-success'>Profil teks berhasil diperbarui!</div>";
}

// --- B. TAMBAH KATEGORI BAGAN BARU ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_kategori_bagan'])) {
    $nama_bagan = $koneksi->real_escape_string($_POST['nama_bagan_baru']);
    $koneksi->query("INSERT INTO kategori_bagan (nama_bagan) VALUES ('$nama_bagan')");
    $pesan = "<div class='alert-success'>Kategori bagan '$nama_bagan' berhasil dibuat!</div>";
}

// --- C. MANAJEMEN NODE BAGAN (TAMBAH & EDIT) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_node'])) {
    $mode = $_POST['sotk_mode'] ?? 'tambah';
    $kategori_id = (int)$_POST['kategori_id'];
    $jabatan = $koneksi->real_escape_string($_POST['jabatan']);
    $nama = $koneksi->real_escape_string($_POST['nama_pejabat']);
    
    $foto_baru = null;
    if (isset($_FILES['foto_pejabat']) && $_FILES['foto_pejabat']['error'] == 0) {
        $upload = upload_file_aman("foto_pejabat", "../assets/uploads/sotk/", ["jpg", "jpeg", "png"], 2048);
        if ($upload['status']) $foto_baru = $upload['nama_file'];
        else $pesan = "<div class='alert-error'>Gagal unggah foto: " . $upload['pesan'] . "</div>";
    }

    if(empty($pesan)) {
        if ($mode == 'tambah') {
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;
            $stmt = $koneksi->prepare("INSERT INTO struktur_organisasi (kategori_id, parent_id, jabatan, nama_pejabat, foto_pejabat) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $kategori_id, $parent_id, $jabatan, $nama, $foto_baru);
            $stmt->execute();
            $pesan = "<div class='alert-success'>Anggota bagan berhasil ditambahkan!</div>";
        } elseif ($mode == 'edit') {
            $sotk_id = (int)$_POST['sotk_id'];
            if ($foto_baru) {
                // Hapus foto lama
                $qf = $koneksi->query("SELECT foto_pejabat FROM struktur_organisasi WHERE id=$sotk_id")->fetch_assoc();
                if($qf['foto_pejabat'] && file_exists("../assets/uploads/sotk/".$qf['foto_pejabat'])) unlink("../assets/uploads/sotk/".$qf['foto_pejabat']);
                $koneksi->query("UPDATE struktur_organisasi SET jabatan='$jabatan', nama_pejabat='$nama', foto_pejabat='$foto_baru' WHERE id=$sotk_id");
            } else {
                $koneksi->query("UPDATE struktur_organisasi SET jabatan='$jabatan', nama_pejabat='$nama' WHERE id=$sotk_id");
            }
            $pesan = "<div class='alert-success'>Data anggota bagan berhasil diperbarui!</div>";
        }
    }
}

if (isset($_GET['hapus_node'])) {
    $id_s = (int)$_GET['hapus_node'];
    $koneksi->query("DELETE FROM struktur_organisasi WHERE id = $id_s");
    echo "<script>window.location.href='edit_profil.php';</script>";
}

// Ambil Data
$profil = $koneksi->query("SELECT * FROM profil_desa WHERE id = 1")->fetch_assoc();
$kategori_bagan = $koneksi->query("SELECT * FROM kategori_bagan ORDER BY id ASC");
$aktif_bagan_id = isset($_GET['lihat_bagan']) ? (int)$_GET['lihat_bagan'] : 1;

// --- D. MANAJEMEN POTENSI DESA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_potensi'])) {
    $judul = $koneksi->real_escape_string($_POST['judul_potensi']);
    $deskripsi = $koneksi->real_escape_string($_POST['deskripsi_potensi']);
    
    $gambar_baru = null;
    if (isset($_FILES['gambar_potensi']) && $_FILES['gambar_potensi']['error'] == 0) {
        // Mengizinkan mp4 dan webm, dengan batas maksimal ukuran 50MB (51200 KB)
        $upload = upload_file_aman("gambar_potensi", "../assets/uploads/potensi/", ["jpg", "jpeg", "png", "mp4", "webm"], 51200);        if ($upload['status']) {
            $gambar_baru = $upload['nama_file'];
        } else {
            $pesan .= "<div class='alert-error'>Gagal unggah gambar potensi: " . $upload['pesan'] . "</div>";
        }
    }

    if(empty($pesan) || strpos($pesan, 'alert-success') !== false) {
        if (!empty($_POST['id_potensi'])) {
            // Mode Edit
            $id_p = (int)$_POST['id_potensi'];
            if ($gambar_baru) {
                $lama = $koneksi->query("SELECT gambar FROM potensi_desa WHERE id=$id_p")->fetch_assoc();
                if($lama['gambar'] && file_exists("../assets/uploads/potensi/".$lama['gambar'])) unlink("../assets/uploads/potensi/".$lama['gambar']);
                $koneksi->query("UPDATE potensi_desa SET judul='$judul', deskripsi='$deskripsi', gambar='$gambar_baru' WHERE id=$id_p");
            } else {
                $koneksi->query("UPDATE potensi_desa SET judul='$judul', deskripsi='$deskripsi' WHERE id=$id_p");
            }
            $pesan .= "<div class='alert-success'>Potensi Desa diperbarui!</div>";
        } else {
            // Mode Tambah
            if($gambar_baru) {
                $koneksi->query("INSERT INTO potensi_desa (judul, deskripsi, gambar) VALUES ('$judul', '$deskripsi', '$gambar_baru')");
                $pesan .= "<div class='alert-success'>Potensi Desa berhasil ditambahkan!</div>";
            } else {
                $pesan .= "<div class='alert-error'>Gambar wajib diunggah untuk potensi baru!</div>";
            }
        }
    }
}

if (isset($_GET['hapus_potensi'])) {
    $id_p = (int)$_GET['hapus_potensi'];
    $lama = $koneksi->query("SELECT gambar FROM potensi_desa WHERE id=$id_p")->fetch_assoc();
    if($lama['gambar'] && file_exists("../assets/uploads/potensi/".$lama['gambar'])) unlink("../assets/uploads/potensi/".$lama['gambar']);
    $koneksi->query("DELETE FROM potensi_desa WHERE id = $id_p");
    echo "<script>window.location.href='edit_profil.php';</script>";
}
?>

<style>
    .alert-success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #22c55e; font-size: 14px; }
    .alert-error { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-size: 14px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 8px; margin-bottom: 15px; font-family: sans-serif; font-size:13px; }
    .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; color: #fff; font-size: 13px; }
    .btn-primary { background: var(--primary); }
    .btn-secondary { background: #64748b; }
    
    /* CSS BARU: Tree SOTK Interaktif (Proporsional & Bersih) */
    .org-tree { display: flex; justify-content: center; overflow-x: auto; padding: 20px 0 40px 0; }
    .org-tree ul { padding-top: 30px; position: relative; display: flex; justify-content: center; padding-left: 0; list-style: none; }
    .org-tree li { float: left; text-align: center; list-style-type: none; position: relative; padding: 30px 10px 0 10px; }
    
    .org-tree li::before, .org-tree li::after { content: ''; position: absolute; top: 0; right: 50%; border-top: 2px solid var(--primary); width: 50%; height: 30px; }
    .org-tree li::after { right: auto; left: 50%; border-left: 2px solid var(--primary); }
    .org-tree li:only-child::after, .org-tree li:only-child::before { display: none; }
    .org-tree li:only-child { padding-top: 0; }
    .org-tree li:first-child::before, .org-tree li:last-child::after { border: 0 none; }
    .org-tree li:last-child::before { border-right: 2px solid var(--primary); border-radius: 0; }
    .org-tree li:first-child::after { border-radius: 0; }
    .org-tree ul ul::before { content: ''; position: absolute; top: 0; left: 50%; border-left: 2px solid var(--primary); width: 0; height: 30px; transform: translateX(-50%); }

    /* Kotak Bagan Admin */
    .node-card { 
        background: #ffffff; border: 1px solid var(--primary); 
        display: flex; flex-direction: column; align-items: center; 
        width: 170px; margin: 0 auto; border-radius: 8px; 
        box-shadow: 0 6px 12px rgba(26, 111, 118, 0.12); overflow: hidden; 
    }
    .node-jabatan { 
        font-size: 11px; font-weight: 700; color: #ffffff; background: var(--primary); 
        width: 100%; padding: 10px 5px; text-transform: uppercase; 
        display: flex; align-items: center; justify-content: center; min-height: 48px;
    }
    .node-foto-wrap { 
        width: 90px; height: 115px; background: #f1f5f9; border: 2px solid #e2e8f0; 
        border-radius: 4px; display: flex; justify-content: center; align-items: center; 
        margin: 15px auto 10px auto; overflow: hidden;
    }
    .node-foto-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .node-foto-wrap i { font-size: 45px; color: #cbd5e0; }
    .node-nama { font-size: 12px; color: #1e293b; font-weight: 700; text-transform: uppercase; width: 100%; padding: 0 10px 5px 10px; }
    
    /* Tombol Aksi */
    .node-aksi { font-size: 11px; background: #fee2e2; color: #ef4444; padding: 5px 10px; border-radius: 6px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; margin-bottom: 15px; }
    .node-aksi:hover { background: #ef4444; color: white; }
    .node-edit { background: #fef3c7; color: #f59e0b; }
    .node-edit:hover { background: #f59e0b; color: white; }

    
</style>

<div class="page-header">
    <h1 class="page-title">Manajemen Profil & Multi-Bagan</h1>
</div>
<?= $pesan ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 30px;">
    
    <!-- 1. FORM 4 TEKS & LOGO -->
    <div class="admin-card">
        <h3 style="margin-bottom: 20px; color: var(--primary);"><i class="fa-solid fa-file-text"></i> Manajemen 4 Area Teks & Logo</h3>
        <form action="" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label>Logo Desa (disarankan Format png,jpg,jpeg dan maksimal size 2MB)</label>
                <input type="file" name="logo_desa" class="form-control" accept="image/*">
                <?php if(!empty($profil['logo_desa'])): ?>
                    <img src="../assets/img/<?= htmlspecialchars($profil['logo_desa']) ?>" style="height:80px; width:80px; object-fit:contain; margin-bottom:15px; border-radius:50%; border:2px dashed #cbd5e0; padding: 5px;">
                <?php endif; ?>
                
                <label>Teks Visi (Kiri Atas)</label>
                <textarea name="visi" class="form-control" rows="4" required><?= htmlspecialchars($profil['visi'] ?? '') ?></textarea>
                
                <label>Teks Misi (Kiri Bawah)</label>
                <textarea name="misi" class="form-control" rows="6" required><?= htmlspecialchars($profil['misi'] ?? '') ?></textarea>
            </div>
            <div>
                <label>Teks Sejarah (Kanan Atas)</label>
                <textarea name="sejarah" class="form-control" rows="8" required><?= htmlspecialchars($profil['sejarah'] ?? '') ?></textarea>
                
                <label>Tentang Desa (Teks Paling Bawah)</label>
                <textarea name="tentang_desa" class="form-control" rows="6" required><?= htmlspecialchars($profil['tentang_desa'] ?? '') ?></textarea>
            </div>
            <div style="grid-column: span 2;">
                <button type="submit" name="simpan_profil" class="btn btn-primary" style="width: 100%;">Simpan Teks & Logo</button>
            </div>
        </form>
    </div>

    <!-- 2. MANAJEMEN MULTI-BAGAN -->
    <div class="admin-card">
        <h3 style="margin-bottom: 20px; color: var(--primary);"><i class="fa-solid fa-sitemap"></i> Manajemen Bagan Struktural</h3>
        
        <div style="display: flex; gap: 20px; margin-bottom: 20px; align-items: flex-end; background:#f8fafc; padding:15px; border-radius:8px;">
            <form action="" method="GET" style="flex-grow: 1; display:flex; gap:10px;">
                <select name="lihat_bagan" class="form-control" style="margin:0;">
                    <?php while($kb = $kategori_bagan->fetch_assoc()): ?>
                        <option value="<?= $kb['id'] ?>" <?= $aktif_bagan_id == $kb['id'] ? 'selected' : '' ?>><?= htmlspecialchars($kb['nama_bagan']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-secondary">Tentukan Induk Bagan</button>
            </form>
            
            <form action="" method="POST" style="display:flex; gap:10px; border-left: 2px solid #cbd5e0; padding-left: 20px;">
                <input type="text" name="nama_bagan_baru" class="form-control" placeholder="Nama Bagan (Cth: Karang Taruna)" required style="margin:0; width: 220px;">
                <button type="submit" name="tambah_kategori_bagan" class="btn btn-primary">+ Buat Bagan</button>
            </form>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: repeat(4, 1fr) auto; gap: 10px; align-items: end; margin-bottom: 40px;" id="form_bagan">
            <input type="hidden" name="kategori_id" value="<?= $aktif_bagan_id ?>">
            <input type="hidden" name="sotk_mode" id="sotk_mode" value="tambah">
            <input type="hidden" name="sotk_id" id="sotk_id">
            <div>
                <label style="font-size:12px;">Atasan (Kosong = Pimpinan)</label>
                <select name="parent_id" class="form-control" style="margin:0;">
                    <option value="">-- Puncak --</option>
                    <?php 
                    $calon_atasan = $koneksi->query("SELECT id, jabatan, nama_pejabat FROM struktur_organisasi WHERE kategori_id = $aktif_bagan_id");
                    while($ca = $calon_atasan->fetch_assoc()) echo "<option value='{$ca['id']}'>{$ca['jabatan']} ({$ca['nama_pejabat']})</option>";
                    ?>
                </select>
            </div>
            <div><label style="font-size:12px;">Jabatan</label><input type="text" name="jabatan" class="form-control" required style="margin:0;"></div>
            <div><label style="font-size:12px;">Nama Orang</label><input type="text" name="nama_pejabat" class="form-control" required style="margin:0;"></div>
            <div><label style="font-size:12px;">Foto Wajah</label><input type="file" name="foto_pejabat" class="form-control" accept="image/*" style="margin:0; background:#fff;"></div>
            <button type="submit" name="simpan_node" class="btn btn-primary" style="height: 42px;">Tambah</button>
        </form>

        <!-- Preview SEMUA Bagan Secara Langsung -->
        <div>
            <?php
            // DEKLARASI FUNGSI RENDER HANYA SEKALI
            if (!function_exists('gambar_bagan_admin')) {
                function gambar_bagan_admin(?int $parent_id, int $kategori_id, mysqli $koneksi) {
                    $sql = $parent_id === NULL ? "SELECT * FROM struktur_organisasi WHERE parent_id IS NULL AND kategori_id = $kategori_id" : "SELECT * FROM struktur_organisasi WHERE parent_id = $parent_id AND kategori_id = $kategori_id";
                    $res = $koneksi->query($sql);
                    if ($res->num_rows > 0) {
                        echo "<ul>";
                        while ($r = $res->fetch_assoc()) {
                            echo "<li>";
                            echo "<div class='node-card'>";
                            echo "<div class='node-foto-wrap'>";
                            if ($r['foto_pejabat']) {
                                echo "<img src='../assets/uploads/sotk/".htmlspecialchars($r['foto_pejabat'])."' alt='Foto'>";
                            } else {
                                echo "<i class='fa-solid fa-user'></i>";
                            }
                            echo "</div>";
                            
                            echo "<div class='node-jabatan'>" . htmlspecialchars($r['jabatan']) . "</div>";
                            echo "<div class='node-nama'>" . htmlspecialchars($r['nama_pejabat']) . "</div>";
                            
                            // Tombol Aksi
                            echo "<div style='margin-top: 10px; display: flex; gap: 5px; justify-content: center;'>";
                            echo "<button type='button' onclick=\"editNode({$r['id']}, '{$r['jabatan']}', '{$r['nama_pejabat']}')\" class='node-aksi node-edit'><i class='fa-solid fa-pen'></i> Edit</button>";
                            echo "<a href='?hapus_node={$r['id']}' class='node-aksi' onclick=\"return confirm('Hapus node ini beserta seluruh bawahannya?');\"><i class='fa-solid fa-xmark'></i> Hapus</a>";
                            echo "</div>";
                            
                            echo "</div>";
                            gambar_bagan_admin($r['id'], $kategori_id, $koneksi); // Rekursif
                            echo "</li>";
                        }
                        echo "</ul>";
                    }
                }
            }

            // LOOPING MENAMPILKAN SEMUA BAGAN
            $q_all_kat = $koneksi->query("SELECT * FROM kategori_bagan ORDER BY id ASC");
            while ($kat_bagan = $q_all_kat->fetch_assoc()):
                $cek_isi = $koneksi->query("SELECT id FROM struktur_organisasi WHERE kategori_id = {$kat_bagan['id']} LIMIT 1");
                if ($cek_isi->num_rows > 0):
            ?>
                <!-- Pembungkus setiap bagan -->
                <div style="margin-bottom: 40px; background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                    <h3 style="text-align: center; color: var(--primary); text-transform: uppercase; margin-bottom: 10px; font-size: 16px; font-weight: 700;">
                        <?= htmlspecialchars($kat_bagan['nama_bagan']) ?>
                        <?php if($kat_bagan['id'] == $aktif_bagan_id): ?>
                            <span style='font-size: 11px; background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 12px; vertical-align: middle; margin-left: 10px;'><i class="fa-solid fa-check-circle"></i> Sedang Dikelola</span>
                        <?php endif; ?>
                    </h3>
                    <div class="org-tree" style="padding-top: 0;">
                        <?php gambar_bagan_admin(NULL, $kat_bagan['id'], $koneksi); ?>
                    </div>
                </div>
            <?php 
                endif;
            endwhile; 
            ?>
        </div>
    </div>
</div>
<!-- 3. MANAJEMEN POTENSI DESA -->
    <div class="admin-card">
        <h3 style="margin-bottom: 20px; color: var(--primary);"><i class="fa-solid fa-mountain-sun"></i> Manajemen Potensi Desa</h3>
        
        <!-- Form Input Potensi -->
        <form action="" method="POST" enctype="multipart/form-data" style="background:#f8fafc; padding:20px; border-radius:12px; margin-bottom: 30px; border: 1px solid #e2e8f0;">
            <input type="hidden" name="id_potensi" id="id_potensi">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <!-- Kolom Judul Dikembalikan -->
                    <label style="font-size:13px; font-weight:600;">Judul Potensi (Cth: Air Terjun...)</label>
                    <input type="text" name="judul_potensi" id="judul_potensi" class="form-control" required style="margin-bottom: 15px;">
                    
                    <label style="font-size:13px; font-weight:600;">Media Potensi (Foto/Video MP4, Max 50MB)</label>
                    <input type="file" name="gambar_potensi" class="form-control" accept="image/*,video/mp4,video/webm" style="background:#fff; margin-bottom: 0;">
                </div>
                <div>
                    <label style="font-size:13px; font-weight:600;">Deskripsi Singkat</label>
                    <textarea name="deskripsi_potensi" id="deskripsi_potensi" class="form-control" rows="5" required style="height: 100%; margin-bottom: 0;"></textarea>
                </div>
            </div>
            <div style="margin-top: 20px;">
                <button type="submit" name="simpan_potensi" class="btn btn-primary"><i class="fa-solid fa-save"></i> Simpan Potensi</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('id_potensi').value=''; this.form.reset();">Reset Form</button>
            </div>
        </form>

        <!-- Daftar Potensi (Preview) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
            <?php 
            $q_pot = $koneksi->query("SELECT * FROM potensi_desa ORDER BY id DESC");
            while($pot = $q_pot->fetch_assoc()): 
            ?>
            <div style="border: 1px solid #cbd5e0; border-radius: 8px; overflow: hidden; background: #fff;">
                
                <!-- Perbaikan Pratinjau: Cek apakah ini Video atau Gambar -->
                <?php 
                $ext = strtolower(pathinfo($pot['gambar'], PATHINFO_EXTENSION));
                if (in_array($ext, ['mp4', 'webm'])): 
                ?>
                    <video src="../assets/uploads/potensi/<?= htmlspecialchars($pot['gambar']) ?>" style="width: 100%; height: 150px; object-fit: cover;" muted autoplay loop playsinline></video>
                <?php else: ?>
                    <img src="../assets/uploads/potensi/<?= htmlspecialchars($pot['gambar']) ?>" style="width: 100%; height: 150px; object-fit: cover;">
                <?php endif; ?>
                
                <div style="padding: 15px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 15px; color: var(--text-dark);"><?= htmlspecialchars($pot['judul']) ?></h4>
                    <div style="display: flex; gap: 5px;">
                        <button type="button" class="node-aksi node-edit" onclick="editPotensi(<?= $pot['id'] ?>, '<?= addslashes($pot['judul']) ?>', `<?= addslashes($pot['deskripsi']) ?>`)"><i class="fa-solid fa-pen"></i> Edit</button>
                        <a href="?hapus_potensi=<?= $pot['id'] ?>" class="node-aksi" onclick="return confirm('Hapus potensi ini?');"><i class="fa-solid fa-trash"></i> Hapus</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
<script>
    function editNode(id, jabatan, nama) {
        document.getElementById('sotk_mode').value = 'edit';
        document.getElementById('sotk_id').value = id;
        document.querySelector('input[name="jabatan"]').value = jabatan;
        document.querySelector('input[name="nama_pejabat"]').value = nama;
        
        const btnSubmit = document.querySelector('button[name="simpan_node"]');
        btnSubmit.innerHTML = '<i class="fa-solid fa-check"></i> Update Data';
        btnSubmit.style.background = '#f59e0b';
        
        document.getElementById('form_bagan').scrollIntoView({behavior: "smooth", block: "center"});
    }

    function editPotensi(id, judul, deskripsi) {
        document.getElementById('id_potensi').value = id;
        document.getElementById('judul_potensi').value = judul;
        document.getElementById('deskripsi_potensi').value = deskripsi;
        window.scrollTo({ top: document.getElementById('id_potensi').offsetTop, behavior: 'smooth' });
    }
</script>
<?php require_once 'includes/admin_footer.php'; ?>