<?php
session_start();
require_once '../config/koneksi.php';
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../login.php"); exit; }
require_once 'includes/admin_header.php';

?>
<!-- Include Header Admin Anda di sini -->

<div style="padding: 20px; background: #fff; border-radius: 10px; margin: 20px;">
    <h2 style="color: #1a6f76; border-bottom: 2px solid #59d5e0; padding-bottom: 10px;">Pembuatan Surat Warga</h2>
    
    <form action="cetak_surat.php" method="POST" target="_blank" id="formSurat">
        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold;">Jenis Surat</label>
            <select name="jenis_surat" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;" required>
                <option value="">-- Pilih Jenis Surat --</option>
                <option value="SKTM">Surat Keterangan Tidak Mampu (SKTM)</option>
                <option value="Domisili">Surat Keterangan Domisili</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold;">NIK / No. KK Pemohon</label>
            <input type="text" name="nik" id="kunciInput" maxlength="16" placeholder="Ketik 16 Digit NIK atau No. KK..." style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            <!-- Area Notifikasi AJAX -->
            <div id="hasilPencarian" style="margin-top: 8px; font-size: 13px;"></div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="font-weight: bold;">Tujuan / Keperluan</label>
            <input type="text" name="keperluan" placeholder="Contoh: Pembuatan PASPORT / Melamar Pekerjaan" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;" required>
        </div>

        <button type="submit" id="btnProses" disabled style="padding: 12px 25px; background: #94a3b8; color: white; border: none; border-radius: 5px; cursor: not-allowed; font-weight: bold;">Validasi NIK/KK Terlebih Dahulu</button>
    </form>
</div>

<script>
document.getElementById('kunciInput').addEventListener('input', function() {
    let keyword = this.value;
    let hasilDiv = document.getElementById('hasilPencarian');
    let btnProses = document.getElementById('btnProses');

    // Mulai pencarian saat input mencapai 15 atau 16 digit
    if (keyword.length >= 15) {
        hasilDiv.innerHTML = '<span style="color:#f59e0b;"><i class="fa-solid fa-spinner fa-spin"></i> Mencari data...</span>';
        
        fetch(`../ajax_get_penduduk.php?nik=${keyword}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    hasilDiv.innerHTML = `<span style="color:#ef4444;"><i class="fa-solid fa-circle-xmark"></i> ${data.error}</span>`;
                    btnProses.disabled = true;
                    btnProses.style.background = '#94a3b8';
                    btnProses.style.cursor = 'not-allowed';
                    btnProses.innerText = 'Data Tidak Ditemukan';
                } else {
                    // Tambahan visual feedback menampilkan NIK dan No KK yang terdeteksi
                    hasilDiv.innerHTML = `<span style="color:#10b981;"><i class="fa-solid fa-circle-check"></i> Pemohon: <strong>${data.nama_lengkap}</strong> (KK: ${data.no_kk})</span>`;
                    btnProses.disabled = false;
                    btnProses.style.background = '#1a6f76';
                    btnProses.style.cursor = 'pointer';
                    btnProses.innerText = 'Proses & Cetak Surat';
                }
            })
            .catch(err => {
                hasilDiv.innerHTML = '<span style="color:#ef4444;">Terjadi kesalahan jaringan.</span>';
            });
    } else {
        hasilDiv.innerHTML = '';
        btnProses.disabled = true;
        btnProses.style.background = '#94a3b8';
        btnProses.style.cursor = 'not-allowed';
        btnProses.innerText = 'Validasi NIK/KK Terlebih Dahulu';
    }
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>