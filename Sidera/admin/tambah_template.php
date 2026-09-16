<?php
// admin/tambah_template.php
require_once '../config/koneksi.php';
require_once 'includes/admin_header.php';

$success_msg = "";
$error_msg = "";

// Cek Mode (Tambah atau Edit)
$mode_edit = false;
$id_edit = 0;
$data_edit = [
    'kode_surat' => '',
    'nama_surat' => '',
    'isi_template' => ''
];

if (isset($_GET['id'])) {
    $mode_edit = true;
    $id_edit = (int)$_GET['id'];
    $get_data = $koneksi->query("SELECT * FROM template_surat WHERE id = $id_edit");
    if ($get_data && $get_data->num_rows > 0) {
        $data_edit = $get_data->fetch_assoc();
    } else {
        echo "<script>alert('Data tidak ditemukan!'); window.location='manajemen_template.php';</script>";
        exit;
    }
}

// Proses form POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_surat = $koneksi->real_escape_string($_POST['kode_surat']);
    $nama_surat = $koneksi->real_escape_string($_POST['nama_surat']);
    $isi_template = $_POST['isi_template']; 
    $header_surat = ""; 
    $variabel_input = json_encode([]); 

    if ($mode_edit) {
        // MODE EDIT (UPDATE)
        $stmt = $koneksi->prepare("UPDATE template_surat SET kode_surat=?, nama_surat=?, isi_template=? WHERE id=?");
        $stmt->bind_param("sssi", $kode_surat, $nama_surat, $isi_template, $id_edit);
        
        if ($stmt->execute()) {
            $success_msg = "Template surat berhasil diperbarui!";
            // Update array lokal agar data di form langsung berubah
            $data_edit['kode_surat'] = $kode_surat;
            $data_edit['nama_surat'] = $nama_surat;
            $data_edit['isi_template'] = $isi_template;
        } else {
            $error_msg = "Gagal memperbarui: " . $stmt->error;
        }
        $stmt->close();

    } else {
        // MODE TAMBAH (INSERT)
        $cek = $koneksi->query("SELECT id FROM template_surat WHERE kode_surat = '$kode_surat'");
        if ($cek->num_rows > 0) {
            $error_msg = "Kode Surat '$kode_surat' sudah digunakan.";
        } else {
            $stmt = $koneksi->prepare("INSERT INTO template_surat (kode_surat, nama_surat, header_surat, isi_template, variabel_input) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $kode_surat, $nama_surat, $header_surat, $isi_template, $variabel_input);
            
            if ($stmt->execute()) {
                $success_msg = "Template surat baru berhasil disimpan!";
                // Kosongkan form jika berhasil tambah baru
                $data_edit['kode_surat'] = '';
                $data_edit['nama_surat'] = '';
                $data_edit['isi_template'] = '';
            } else {
                $error_msg = "Gagal menyimpan: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!-- Include TinyMCE via CDN -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#isi_template',
        height: 600,
        plugins: 'table lists advlist align',
        toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | indent outdent | table | bullist numlist',
        menubar: false,
        content_style: "body { font-family: 'Times New Roman', Times, serif; font-size: 16px; line-height: 1.5; }",
        branding: false
    });

    function copyPlaceholder(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Kode ' + text + ' disalin! Silakan Paste (Ctrl+V) di editor.');
        });
    }
</script>

<style>
    .template-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
    .admin-card { background: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 25px; border: 1px solid #e2e8f0; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 8px; text-transform: uppercase; }
    .form-control { width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; transition: 0.3s; background: #f8fafc; box-sizing: border-box;}
    .form-control:focus { border-color: #1a6f76; background: #fff; box-shadow: 0 0 0 3px rgba(26, 111, 118, 0.1); }
    
    .btn-save { background: #1a6f76; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.3s; }
    .btn-save:hover { background: #13555b; transform: translateY(-2px); }

    .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; font-size: 14px; }
    .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #22c55e; }
    .alert-danger { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }

    .guide-box { background: #f8fafc; border: 1px dashed #cbd5e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; transition: 0.2s;}
    .guide-box:hover { border-color: #1a6f76; background: #f1f5f9; }
    .code-badge { background: #e2e8f0; color: #0f172a; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 13px; font-weight: bold; cursor: pointer; display: inline-block; margin-bottom: 5px; }
    .code-badge:hover { background: #1a6f76; color: #fff; }
</style>

<div class="page-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 class="page-title" style="font-size: 24px; color: #0f172a; margin: 0 0 5px 0;">
            <?= $mode_edit ? 'Edit Template Surat' : 'Tambah Template Surat' ?>
        </h1>
        <p class="page-subtitle" style="color: #64748b; margin: 0;">Gunakan editor visual dan kode placeholder untuk format dinamis.</p>
    </div>
    <div>
        <a href="manajemen_template.php" class="btn-search" style="background: #64748b; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px;">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>
</div>

<?php if($success_msg): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $success_msg ?></div>
<?php endif; ?>

<?php if($error_msg): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error_msg ?></div>
<?php endif; ?>

<div class="template-layout">
    <!-- KOLOM KIRI: Form Editor -->
    <div class="admin-card">
        <form action="" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Kode Surat</label>
                    <input type="text" name="kode_surat" class="form-control" placeholder="Contoh: SKTM" value="<?= htmlspecialchars($data_edit['kode_surat']) ?>" <?= $mode_edit ? 'readonly style="background:#e2e8f0;" title="Kode surat tidak bisa diubah"' : 'required' ?>>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Surat</label>
                    <input type="text" name="nama_surat" class="form-control" placeholder="Contoh: Surat Keterangan Tidak Mampu" value="<?= htmlspecialchars($data_edit['nama_surat']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Format / Isi Surat</label>
                <textarea name="isi_template" id="isi_template" placeholder="Ketik format surat di sini..."><?= htmlspecialchars($data_edit['isi_template']) ?></textarea>
            </div>

            <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> <?= $mode_edit ? 'Perbarui Template' : 'Simpan Template Baru' ?></button>
        </form>
    </div>

    <!-- KOLOM KANAN: Panduan -->
    <div class="admin-card" style="height: fit-content;">
        <h3 style="font-size: 16px; margin-top:0; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">Panduan Kode (Placeholder)</h3>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Klik kode di bawah untuk menyalin, lalu Paste (Ctrl+V) ke dalam editor.</p>

        <div class="guide-box"><span class="code-badge" onclick="copyPlaceholder('{nama_lengkap}')">{nama_lengkap}</span><p style="font-size: 12px; margin: 0; color: #475569;">Nama pemohon.</p></div>
        <div class="guide-box"><span class="code-badge" onclick="copyPlaceholder('{ttl}')">{ttl}</span><p style="font-size: 12px; margin: 0; color: #475569;">Tempat, Tanggal Lahir.</p></div>
        <div class="guide-box"><span class="code-badge" onclick="copyPlaceholder('{pekerjaan}')">{pekerjaan}</span><p style="font-size: 12px; margin: 0; color: #475569;">Status pekerjaan warga.</p></div>
        <div class="guide-box"><span class="code-badge" onclick="copyPlaceholder('{alamat}')">{alamat}</span><p style="font-size: 12px; margin: 0; color: #475569;">Alamat lengkap / RT RW.</p></div>
        <div class="guide-box" style="border-color: #10b981; background: #ecfdf5;"><span class="code-badge" onclick="copyPlaceholder('{keperluan}')" style="background: #10b981; color: white;">{keperluan}</span><p style="font-size: 12px; margin: 0; color: #065f46;">Keperluan dinamis (misal: "Pengajuan KUR").</p></div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>