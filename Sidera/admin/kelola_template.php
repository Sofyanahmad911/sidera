<?php
session_start();
require_once '../config/koneksi.php';

// Jika form disubmit untuk menyimpan template
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_template'])) {
    $kode_surat = $koneksi->real_escape_string($_POST['kode_surat']);
    $nama_surat = $koneksi->real_escape_string($_POST['nama_surat']);
    $isi_template = $_POST['isi_template']; // Jangan di escape string biasa karena ini HTML DOM

    // Gunakan Prepared Statement untuk menyimpan HTML aman
    $stmt = $koneksi->prepare("INSERT INTO template_surat (kode_surat, nama_surat, header_surat, isi_template) VALUES (?, ?, '', ?) ON DUPLICATE KEY UPDATE nama_surat=?, isi_template=?");
    $stmt->bind_param("sssss", $kode_surat, $nama_surat, $isi_template, $nama_surat, $isi_template);
    if($stmt->execute()) {
        $msg = "Template berhasil disimpan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Desain Template Surat - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Integrasi TinyMCE Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: '#editorTemplate',
        height: 600,
        plugins: 'preview importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap quickbars emoticons',
        toolbar: 'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl',
        content_style: 'body { font-family: "Times New Roman", Times, serif; font-size: 14px; line-height: 1.5; padding: 20px; }'
      });
    </script>
</head>
<body class="bg-slate-50 p-8">
    <div class="max-w-6xl mx-auto bg-white p-8 rounded-3xl shadow-sm border border-stone-200">
        <h2 class="text-2xl font-bold text-[#2D4030] mb-6">🎨 Editor Template Surat Dinamis</h2>
        
        <?php if(isset($msg)): ?>
            <div class="bg-emerald-100 text-emerald-800 p-3 rounded-xl mb-4 font-bold"><?= $msg ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-12 gap-6">
            <!-- Sidebar Variabel -->
            <div class="col-span-3 bg-stone-50 p-4 rounded-xl border border-stone-200">
                <h3 class="font-bold text-sm mb-3">Variabel Auto-Fill</h3>
                <p class="text-xs text-stone-500 mb-4">Klik (Copy) kode di bawah ini dan Paste ke dalam editor untuk menarik data warga otomatis.</p>
                <ul class="text-xs space-y-2 font-mono text-blue-600 bg-white p-3 rounded-lg border border-stone-200">
                    <li>{{nama_lengkap}}</li>
                    <li>{{nik}}</li>
                    <li>{{tempat_lahir}}</li>
                    <li>{{tgl_lahir}}</li>
                    <li>{{umur}}</li>
                    <li>{{jenis_kelamin}}</li>
                    <li>{{pekerjaan}}</li>
                    <li>{{rt}}</li>
                    <li>{{rw}}</li>
                    <li>{{agama}}</li>
                    <li>{{tanggal_cetak}}</li>
                </ul>
            </div>

            <!-- Form Editor -->
            <div class="col-span-9">
                <form method="POST" action="">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-stone-700 mb-1">Kode Surat (Contoh: SKTM)</label>
                            <input type="text" name="kode_surat" required class="w-full p-2.5 rounded-xl border border-stone-200 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-700 mb-1">Nama Surat (Contoh: Surat Ket. Tidak Mampu)</label>
                            <input type="text" name="nama_surat" required class="w-full p-2.5 rounded-xl border border-stone-200 text-sm">
                        </div>
                    </div>

                    <label class="block text-xs font-bold text-stone-700 mb-1">Desain Format Surat</label>
                    <!-- Teks area ini akan diubah menjadi Microsoft Word-like editor oleh TinyMCE -->
                    <textarea id="editorTemplate" name="isi_template">
                        <p style="text-align: center;"><strong><u>SURAT KETERANGAN</u></strong><br />Nomor: 470/..../DS/2026</p>
                        <p style="text-align: justify;">Yang bertanda tangan di bawah ini Kepala Desa Serage menerangkan bahwa:</p>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tbody>
                                <tr><td style="width: 30%;">Nama Lengkap</td><td style="width: 70%;">: <strong>{{nama_lengkap}}</strong></td></tr>
                                <tr><td>NIK</td><td>: {{nik}}</td></tr>
                                <tr><td>Tempat, Tgl Lahir</td><td>: {{tempat_lahir}}, {{tgl_lahir}}</td></tr>
                            </tbody>
                        </table>
                        <p style="text-align: justify;">Adalah benar warga kami yang berdomisili di RT {{rt}} / RW {{rw}}.</p>
                    </textarea>

                    <button type="submit" name="simpan_template" class="mt-4 px-6 py-3 bg-[#2D4030] text-white rounded-xl font-bold w-full">
                        💾 Simpan Desain Template
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>