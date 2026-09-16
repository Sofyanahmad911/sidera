<?php
/**
 * @var array $warga
 * @var string $nomor_surat_lengkap
 * @var string $keperluan
 */
?>
<div class="judul-surat">
    <div class="judul">SURAT KETERANGAN PENGUASAAN TANAH</div>
    <div class="nomor">Nomor : <?= $nomor_surat_lengkap ?></div>
</div>

<p style="margin-bottom: 15px; text-align: justify;">Kepala Desa Serage Kecamatan Praya Barat Daya Kabupaten Lombok Tengah, menerangkan dengan sebenarnya bahwa:</p>

<table class="tabel-identitas" style="margin-bottom: 20px;">
    <tr><td style="width: 25%;">Nama Lengkap</td><td style="width: 2%;">:</td><td><strong><?= htmlspecialchars($warga['nama_lengkap']) ?></strong></td></tr>
    <tr><td>NIK</td><td>:</td><td><?= htmlspecialchars($warga['nik']) ?></td></tr>
    <tr><td>Tempat, Tgl Lahir</td><td>:</td><td><?= htmlspecialchars($warga['tempat_lahir']) ?>, <?= format_tanggal_indo($warga['tgl_lahir']) ?></td></tr>
    <tr><td>Pekerjaan</td><td>:</td><td><?= htmlspecialchars($warga['pekerjaan']) ?></td></tr>
    <tr><td>Alamat</td><td>:</td><td>Dusun <?= htmlspecialchars($warga['nama_dusun']) ?> RT <?= htmlspecialchars($warga['rt']) ?>/RW <?= htmlspecialchars($warga['rw']) ?> Desa Serage</td></tr>
</table>

<p class="paragraf-indent">Berdasarkan catatan register Desa dan penguasaan fisik di lapangan, orang tersebut di atas adalah benar memiliki / menguasai fisik sebidang tanah dengan rincian sebagai berikut:</p>

<div style="text-align: center; font-weight: bold; margin: 15px 0; text-transform: uppercase;">
    <?= htmlspecialchars($keperluan) ?>
</div>

<p style="text-align: justify;">Tanah tersebut dikuasai secara terus-menerus, tidak dalam sengketa dengan pihak manapun, dan tidak sedang dijaminkan. Surat Keterangan ini dibuat sebagai pengantar untuk proses pengurusan administrasi pertanahan lebih lanjut di Badan Pertanahan Nasional (BPN).</p>