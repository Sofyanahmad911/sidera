<?php
// admin/tambah_penduduk.php
require_once '../config/koneksi.php';
require_once 'includes/admin_header.php';

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nik = $koneksi->real_escape_string($_POST['nik']);
    $nama_lengkap = $koneksi->real_escape_string($_POST['nama_lengkap']);
    $tempat_lahir = $koneksi->real_escape_string($_POST['tempat_lahir']);
    $tgl_lahir = $_POST['tgl_lahir'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $rt = $koneksi->real_escape_string($_POST['rt']);
    $rw = $koneksi->real_escape_string($_POST['rw']);
    $kel_desa = $koneksi->real_escape_string($_POST['kel_desa']);
    $kecamatan = $koneksi->real_escape_string($_POST['kecamatan']);
    $kabupaten = $koneksi->real_escape_string($_POST['kabupaten']);
    $provinsi = $koneksi->real_escape_string($_POST['provinsi']);
    $agama = $koneksi->real_escape_string($_POST['agama']);
    $status_perkawinan = $_POST['status_perkawinan'];
    $pekerjaan = $koneksi->real_escape_string($_POST['pekerjaan']);
    $kewarganegaraan = $koneksi->real_escape_string($_POST['kewarganegaraan']);

    // Kalkulasi Umur Otomatis
    $tgl_lahir_obj = new DateTime($tgl_lahir);
    $hari_ini = new DateTime("today");
    $umur = $tgl_lahir_obj->diff($hari_ini)->y;

    // Persiapan Upload File (Opsional)
    $arsip_ktp = null;
    if (isset($_FILES['arsip_foto_ktp_kk']) && $_FILES['arsip_foto_ktp_kk']['error'] == 0) {
        $arsip_ktp = time() . "_" . basename($_FILES["arsip_foto_ktp_kk"]["name"]);
        move_uploaded_file($_FILES["arsip_foto_ktp_kk"]["tmp_name"], "../assets/uploads/arsip_dokumen/" . $arsip_ktp);
    }

    // Asumsi eksekusi query
    $stmt = $koneksi->prepare("INSERT INTO penduduk (nik, nama_lengkap, tempat_lahir, tgl_lahir, umur, jenis_kelamin, rt, rw, kel_desa, kecamatan, kabupaten, provinsi, agama, status_perkawinan, pekerjaan, kewarganegaraan, arsip_foto_ktp_kk) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissssssssssss", $nik, $nama_lengkap, $tempat_lahir, $tgl_lahir, $umur, $jenis_kelamin, $rt, $rw, $kel_desa, $kecamatan, $kabupaten, $provinsi, $agama, $status_perkawinan, $pekerjaan, $kewarganegaraan, $arsip_ktp);
    
    // PERUBAHAN DI SINI: Kembalikan respon sebagai JSON
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success', 
            'pesan' => "Data penduduk berhasil ditambahkan! Umur dihitung otomatis: $umur Tahun."
        ]);
        exit; // Hentikan eksekusi script agar HTML di bawahnya tidak ikut terkirim sebagai respon JSON
    } else {

        // --- TAMBAHKAN BARIS INI ---
        bersihkan_cache_statistik();
        // ---------------------------
        echo json_encode([
            'status' => 'error', 
            'pesan' => "Gagal menyimpan data: " . $stmt->error
        ]);
        exit;
    }
    $stmt->close();
}

?>

<style>
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; }
    .form-control { width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; transition: var(--transition); background: var(--bg-light); font-family: 'Poppins', sans-serif; }
    .form-control:focus { border-color: var(--secondary); background: #fff; box-shadow: 0 0 0 3px rgba(89, 213, 224, 0.2); }
    .btn-save { background: var(--primary); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.3s; }
    .btn-save:hover { background: var(--sidebar-hover); transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .btn-back { display: inline-block; background: #e2e8f0; color: var(--text-dark); padding: 15px 30px; border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none; transition: 0.3s; margin-right: 10px; }
    .btn-back:hover { background: #cbd5e0; }
</style>

<div class="page-header">
    <h1 class="page-title">Tambah Data Penduduk Baru</h1>
    <p class="page-subtitle">Masukkan atribut kependudukan warga dengan teliti.</p>
</div>

<?= $pesan ?>
<!-- Tombol Pemicu di atas Form Tambah Penduduk -->
<!-- AREA UI KAMERA KTP -->
<div class="admin-card" style="margin-bottom: 20px; background: #f8fafc; border: 2px dashed #cbd5e0;">
    <div style="text-align: center; padding: 15px;">
        <h4 style="margin-top: 0; color: var(--primary);"><i class="fa-solid fa-camera"></i> Ekstraksi Data KTP (AI OCR)</h4>
        <p style="font-size: 13px; color: var(--text-muted);">Posisikan KTP di dalam garis panduan untuk hasil pembacaan terbaik.</p>
        
        <button type="button" id="btnBukaKamera" class="btn-primary" style="margin-bottom: 15px;">
            <i class="fa-solid fa-video"></i> Buka Kamera
        </button>

        <!-- Container Kamera & Overlay -->
        <div id="kameraContainer" style="display: none; position: relative; max-width: 500px; margin: 0 auto; overflow: hidden; border-radius: 12px;">
            <video id="videoKtp" autoplay playsinline style="width: 100%; display: block;"></video>
            
            <!-- UI Overlay (Bingkai Pemandu) -->
            <div style="position: absolute; top: 15%; left: 10%; width: 80%; height: 70%; border: 3px solid rgba(89, 213, 224, 0.8); border-radius: 10px; box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6); pointer-events: none;">
                <div style="position: absolute; top: 10px; right: 10px; width: 60px; height: 80px; border: 1px dashed rgba(255,255,255,0.5);"></div> <!-- Indikator area foto wajah -->
            </div>

            <button type="button" id="btnAmbilFoto" class="btn-save" style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 10;">
                <i class="fa-solid fa-camera-retro"></i> Ambil Foto & Proses
            </button>
        </div>
        
        <canvas id="canvasKtp" style="display: none;"></canvas>
    </div>
</div>

<div class="admin-card">
    <form id="formTambahPenduduk" action="" method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <!-- Kolom 1 -->
            <div>
                <div class="form-group"><label class="form-label">Nomor Induk Kependudukan (NIK)</label><input type="text" name="nik" class="form-control" maxlength="16" required placeholder="16 Digit NIK"></div>
                <div class="form-group"><label class="form-label">Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-control" required placeholder="Sesuai KTP"></div>
                <div class="form-group"><label class="form-label">Tempat Lahir</label><input type="text" name="tempat_lahir" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Tanggal Lahir</label><input type="date" name="tgl_lahir" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-control" required>
                        <option value="l">Laki-Laki</option>
                        <option value="p">Perempuan</option>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group"><label class="form-label">RT</label><input type="text" name="rt" class="form-control" maxlength="3" required placeholder="001"></div>
                    <div class="form-group"><label class="form-label">RW</label><input type="text" name="rw" class="form-control" maxlength="3" required placeholder="002"></div>
                </div>
                <div class="form-group"><label class="form-label">Agama</label><input type="text" name="agama" class="form-control" required></div>
            </div>

            <!-- Kolom 2 -->
            <div>
                <div class="form-group"><label class="form-label">Desa / Kelurahan</label><input type="text" name="kel_desa" class="form-control" value="Suralaga" required></div>
                <div class="form-group"><label class="form-label">Kecamatan</label><input type="text" name="kecamatan" class="form-control" value="Suralaga" required></div>
                <div class="form-group"><label class="form-label">Kabupaten</label><input type="text" name="kabupaten" class="form-control" value="Lombok Timur" required></div>
                <div class="form-group"><label class="form-label">Provinsi</label><input type="text" name="provinsi" class="form-control" value="Nusa Tenggara Barat" required></div>
                
                <div class="form-group"><label class="form-label">Status Perkawinan</label>
                    <select name="status_perkawinan" class="form-control" required>
                        <option value="Belum Kawin">Belum Kawin</option>
                        <option value="Kawin">Kawin</option>
                        <option value="Cerai Hidup">Cerai Hidup</option>
                        <option value="Cerai Mati">Cerai Mati</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Pekerjaan</label><input type="text" name="pekerjaan" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Kewarganegaraan</label><input type="text" name="kewarganegaraan" class="form-control" value="WNI" required></div>
                <div class="form-group"><label class="form-label">Arsip Scan KTP / KK (Opsional)</label><input type="file" name="arsip_foto_ktp_kk" class="form-control" accept=".jpg,.jpeg,.png,.pdf" style="background:#fff;"></div>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 20px;">
            <a href="manajemen_penduduk.php" class="btn-back">Batal</a>
            <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Simpan Data</button>
        </div>
    </form>
</div>
<script>
document.getElementById('formTambahPenduduk').addEventListener('submit', function(e) {
    e.preventDefault(); // Mencegah form melakukan submit tradisional (reload halaman)
    
    // 1. Tampilkan animasi loading SweetAlert
    Swal.fire({
        title: 'Menyimpan Data...',
        text: 'Mohon tunggu, berkas sedang diunggah ke server.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // 2. Siapkan data form (termasuk file upload)
    let formData = new FormData(this);

    // 3. Kirim data ke server (ke file ini sendiri) menggunakan Fetch API
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json()) // Parsing respon dari server menjadi objek JSON
    .then(data => {
        if(data.status === 'success') {
            // 4a. Jika sukses, tampilkan centang hijau lalu alihkan halaman
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: data.pesan,
                showConfirmButton: false,
                timer: 2000
            }).then(() => {
                window.location.href = 'manajemen_penduduk.php';
            });
        } else {
            // 4b. Jika gagal (misal duplikat NIK), tampilkan error dari server
            Swal.fire({
                icon: 'error',
                title: 'Gagal Tersimpan',
                text: data.pesan
            });
        }
    })
    .catch(error => {
        // 5. Tangkap error koneksi/jaringan
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Terjadi kegagalan komunikasi dengan server.'
        });
        console.error('Error:', error);
    });
});
</script>
<!-- Sertakan Tesseract.js -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
<script>
const video = document.getElementById('videoKtp');
const canvas = document.getElementById('canvasKtp');
const btnBukaKamera = document.getElementById('btnBukaKamera');
const btnAmbilFoto = document.getElementById('btnAmbilFoto');
const kameraContainer = document.getElementById('kameraContainer');

let streamLokal = null;

// Tahap 1: Akses Kamera Smartphone/Laptop
btnBukaKamera.addEventListener('click', async () => {
    try {
        // Mengutamakan kamera belakang (environment)
        streamLokal = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } } });
        video.srcObject = streamLokal;
        kameraContainer.style.display = 'block';
        btnBukaKamera.style.display = 'none';
    } catch (err) {
        Swal.fire('Akses Ditolak', 'Tidak dapat mengakses kamera. Pastikan browser memiliki izin.', 'error');
    }
});

// Tahap 1 & 2: Pengambilan Gambar dan Kirim ke Backend
btnAmbilFoto.addEventListener('click', () => {
    // Gambar ke canvas
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const context = canvas.getContext('2d');
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Konversi ke Base64
    const imageDataBase64 = canvas.toDataURL('image/jpeg', 0.9);

    // Matikan kamera setelah memotret
    streamLokal.getTracks().forEach(track => track.stop());
    kameraContainer.style.display = 'none';
    btnBukaKamera.style.display = 'inline-block';

    Swal.fire({
        title: 'Memproses ke Server AI...',
        text: 'Mengirim gambar terenkripsi ke layanan OCR.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    // Kirim ke Backend kita (Proxy)
    fetch('ajax_ocr_ktp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image: imageDataBase64 })
    })
    .then(response => response.json())
    .then(dataOcr => {
        if(dataOcr.status === 'success') {
            Swal.fire('Berhasil', 'Data KTP terekstrak. Silakan review kembali isian formulir di bawah ini.', 'success');
            
            // Tahap 3: Auto-Fill Form UI
            if(dataOcr.data.nik) document.querySelector('input[name="nik"]').value = dataOcr.data.nik;
            if(dataOcr.data.nama) document.querySelector('input[name="nama_lengkap"]').value = dataOcr.data.nama;
            if(dataOcr.data.tempat_lahir) document.querySelector('input[name="tempat_lahir"]').value = dataOcr.data.tempat_lahir;
            if(dataOcr.data.tgl_lahir) document.querySelector('input[name="tgl_lahir"]').value = dataOcr.data.tgl_lahir;
            
            // Logika dropdown
            if(dataOcr.data.jenis_kelamin === 'LAKI-LAKI') document.querySelector('select[name="jenis_kelamin"]').value = 'l';
            else if(dataOcr.data.jenis_kelamin === 'PEREMPUAN') document.querySelector('select[name="jenis_kelamin"]').value = 'p';
            
            if(dataOcr.data.rt) document.querySelector('input[name="rt"]').value = dataOcr.data.rt;
            if(dataOcr.data.rw) document.querySelector('input[name="rw"]').value = dataOcr.data.rw;
            if(dataOcr.data.agama) document.querySelector('input[name="agama"]').value = dataOcr.data.agama;
            
        } else {
            Swal.fire('Gagal', dataOcr.pesan, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Kesalahan Jaringan', 'Gagal terhubung ke backend.', 'error');
    });
});
</script>
<?php require_once 'includes/admin_footer.php'; ?>