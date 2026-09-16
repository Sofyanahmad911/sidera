<?php
/**
 * @var array $warga
 * @var string $nomor_surat_lengkap
 * @var string $keperluan
 */
?>
<div class="judul-surat">
    <div class="judul">SURAT KETERANGAN BEDA IDENTITAS</div>
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

<p class="paragraf-indent">Adalah benar warga Desa Serage. Berdasarkan data kependudukan dan pengakuan yang bersangkutan, kami menerangkan bahwa terdapat perbedaan penulisan identitas warga tersebut pada dokumen administratifnya, yaitu:</p>

<div style="text-align: center; font-weight: bold; margin: 15px 0; text-transform: uppercase;">
    <?= htmlspecialchars($keperluan) ?>
</div>

<p class="paragraf-indent">Kami menyatakan bahwa identitas dengan penulisan yang berbeda tersebut adalah <strong>satu orang yang sama</strong> dan bukan orang lain.</p>

<p style="text-align: justify;">Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagai dasar penyelarasan administrasi kependudukan pada instansi yang dituju.</p>