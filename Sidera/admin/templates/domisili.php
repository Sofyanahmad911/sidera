<?php
/**
 * @var array $warga
 * @var string $nomor_surat_lengkap
 * @var string $keperluan
 */
?>
<div class="judul-surat">
    <div class="judul">SURAT KETERANGAN DOMISILI</div>
    <div class="nomor">Nomor : <?= $nomor_surat_lengkap ?></div>
</div>
<p style="margin-bottom: 15px; text-align: justify;">Yang bertanda tangan di bawah ini Kepala Desa Serage Kecamatan Praya Barat Daya Kabupaten Lombok Tengah, menerangkan dengan sebenarnya bahwa:</p>
<table class="tabel-identitas" style="margin-bottom: 20px;">
    <tr><td style="width: 25%;">Nama Lengkap</td><td style="width: 2%;">:</td><td><strong><?= htmlspecialchars($warga['nama_lengkap']) ?></strong></td></tr>
    <tr><td>NIK</td><td>:</td><td><?= htmlspecialchars($warga['nik']) ?></td></tr>
    <tr><td>Jenis Kelamin</td><td>:</td><td><?= htmlspecialchars($warga['jenis_kelamin']) ?></td></tr>
    <tr><td>Tempat, Tgl Lahir</td><td>:</td><td><?= htmlspecialchars($warga['tempat_lahir']) ?>, <?= format_tanggal_indo($warga['tgl_lahir']) ?></td></tr>
    <tr><td>Pekerjaan</td><td>:</td><td><?= htmlspecialchars($warga['pekerjaan']) ?></td></tr>
</table>
<p class="paragraf-indent">Adalah benar-benar penduduk yang berdomisili di Dusun <?= htmlspecialchars($warga['nama_dusun']) ?> RT <?= htmlspecialchars($warga['rt']) ?> / RW <?= htmlspecialchars($warga['rw']) ?> Desa Serage, Kecamatan Praya Barat Daya, Kabupaten Lombok Tengah.</p>
<p class="paragraf-indent">Surat keterangan ini dibuat atas permintaan yang bersangkutan untuk keperluan <strong><?= htmlspecialchars($keperluan) ?></strong>. Demikian surat keterangan domisili ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>