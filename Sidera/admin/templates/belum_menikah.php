<?php
/**
 * @var array $warga
 * @var string $nomor_surat_lengkap
 * @var string $keperluan
 */
?>
<div class="judul-surat">
    <div class="judul">SURAT KETERANGAN BELUM PERNAH MENIKAH</div>
    <div class="nomor">Nomor : <?= $nomor_surat_lengkap ?></div>
</div>
<p style="margin-bottom: 15px; text-align: justify;">Kepala Desa Serage Kecamatan Praya Barat Daya Kabupaten Lombok Tengah, menerangkan dengan sebenarnya bahwa:</p>
<table class="tabel-identitas" style="margin-bottom: 20px;">
    <tr><td style="width: 25%;">Nama Lengkap</td><td style="width: 2%;">:</td><td><strong><?= htmlspecialchars($warga['nama_lengkap']) ?></strong></td></tr>
    <tr><td>NIK</td><td>:</td><td><?= htmlspecialchars($warga['nik']) ?></td></tr>
    <tr><td>Jenis Kelamin</td><td>:</td><td><?= htmlspecialchars($warga['jenis_kelamin']) ?></td></tr>
    <tr><td>Tempat, Tgl Lahir</td><td>:</td><td><?= htmlspecialchars($warga['tempat_lahir']) ?>, <?= format_tanggal_indo($warga['tgl_lahir']) ?></td></tr>
    <tr><td>Agama</td><td>:</td><td><?= htmlspecialchars($warga['agama'] ?? 'ISLAM') ?></td></tr>
    <tr><td>Status Kawin</td><td>:</td><td><strong>Belum Kawin</strong></td></tr>
    <tr><td>Alamat</td><td>:</td><td>Dusun <?= htmlspecialchars($warga['nama_dusun']) ?> RT <?= htmlspecialchars($warga['rt']) ?>/RW <?= htmlspecialchars($warga['rw']) ?> Desa Serage</td></tr>
</table>
<p class="paragraf-indent">Orang tersebut di atas adalah benar warga kami yang berdomisili di Desa Serage. Berdasarkan data administrasi kependudukan dan pengamatan kami di lingkungan masyarakat, yang bersangkutan hingga saat surat ini dikeluarkan <strong>Belum Pernah Menikah</strong>.</p>
<p class="paragraf-indent">Surat keterangan ini dibuat atas permintaan yang bersangkutan untuk dipergunakan sebagai kelengkapan administrasi: <strong><?= htmlspecialchars($keperluan) ?></strong>.</p>
<p style="text-align: justify;">Demikian surat keterangan ini kami buat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.</p>