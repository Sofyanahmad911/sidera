<?php
/**
 * @var array $warga
 * @var string $nomor_surat_lengkap
 * @var string $keperluan
 */
?>
<div class="judul-surat">
    <div class="judul">SURAT PENGANTAR SKCK</div>
    <div class="nomor">Nomor : <?= $nomor_surat_lengkap ?></div>
</div>
<p style="margin-bottom: 15px; text-align: justify;">Kepala Desa Serage Kecamatan Praya Barat Daya Kabupaten Lombok Tengah, menerangkan dengan sebenarnya bahwa:</p>
<table class="tabel-identitas" style="margin-bottom: 20px;">
    <tr><td style="width: 25%;">Nama Lengkap</td><td style="width: 2%;">:</td><td><strong><?= htmlspecialchars($warga['nama_lengkap']) ?></strong></td></tr>
    <tr><td>NIK</td><td>:</td><td><?= htmlspecialchars($warga['nik']) ?></td></tr>
    <tr><td>Jenis Kelamin</td><td>:</td><td><?= htmlspecialchars($warga['jenis_kelamin']) ?></td></tr>
    <tr><td>Tempat, Tgl Lahir</td><td>:</td><td><?= htmlspecialchars($warga['tempat_lahir']) ?>, <?= format_tanggal_indo($warga['tgl_lahir']) ?></td></tr>
    <tr><td>Agama</td><td>:</td><td><?= htmlspecialchars($warga['agama'] ?? 'ISLAM') ?></td></tr>
    <tr><td>Pekerjaan</td><td>:</td><td><?= htmlspecialchars($warga['pekerjaan']) ?></td></tr>
    <tr><td>Alamat</td><td>:</td><td>Dusun <?= htmlspecialchars($warga['nama_dusun']) ?> RT <?= htmlspecialchars($warga['rt']) ?>/RW <?= htmlspecialchars($warga['rw']) ?> Desa Serage</td></tr>
</table>
<p class="paragraf-indent">Berdasarkan pengamatan kami, orang tersebut di atas adalah benar warga Desa Serage yang berkelakuan baik dan belum pernah tersangkut tindak pidana maupun kejahatan lainnya di wilayah kami.</p>
<p class="paragraf-indent">Surat pengantar ini diberikan untuk keperluan mengurus <strong>Surat Keterangan Catatan Kepolisian (SKCK)</strong> di Kepolisian Sektor Praya Barat Daya guna keperluan: <?= htmlspecialchars($keperluan) ?>.</p>
<p style="text-align: justify;">Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.</p>