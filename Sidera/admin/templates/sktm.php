<?php
/**
 * @var array $warga
 * @var string $nomor_surat_lengkap
 * @var string $keperluan
 * @var string $nama_kades
 * @var string $nik_kades
 * @var string $jabatan_kades
 */
?>
<div class="judul-surat">
    <div class="judul">SURAT KETERANGAN TIDAK MAMPU</div>
    <div class="nomor">Nomor : <?= $nomor_surat_lengkap ?></div>
</div>

<p style="margin-bottom: 15px; text-align: justify;">Yang bertanda tangan di bawah ini Kepala Desa Serage Kecamatan Praya Barat Daya Kabupaten Lombok Tengah ,menerangkan dengan sebenarnya bahwa:</p>

<table class="tabel-identitas" style="margin-bottom: 20px;">
    <tr><td style="width: 25%;">Nama</td><td style="width: 2%;">:</td><td><?= $nama_kades ?></td></tr>
    <tr><td>NIK</td><td>:</td><td><?= $nik_kades ?></td></tr>
    <tr><td>Jenis Kelamin</td><td>:</td><td>LAKI-LAKI</td></tr>
    <tr><td>Tempat/Tgl Lahir</td><td>:</td><td>BELENJE, 12-12-1965</td></tr>
    <tr><td>Agama</td><td>:</td><td>ISLAM</td></tr>
    <tr><td>Pekerjaan</td><td>:</td><td><?= strtoupper($jabatan_kades) ?></td></tr>
    <tr><td>Alamat</td><td>:</td><td>Belenje Dusun Belenje Desa Serage Kec, Praya Barat Daya<br>Kab, Lombok Tengah.</td></tr>
</table>

<p class="paragraf-indent">
    Bahwa yang namanya tersebut diatas adalah warga Desa Serage Kecamatan Praya Barat Daya Kabupaten Lombok Tengah. Dengan sepegetahuan kami dan sesuai data yang ada di kantor Desa orang tersebut diatas memang benar Keluaraga Kurang Mampu / ekonomi lemah. Surat keterangan ini di minta secara langsung oleh yang bersangkatutan guna untuk <?= htmlspecialchars($keperluan) ?> Bernama:
</p>

<div style="text-align: center; font-weight: bold; margin: 15px 0; text-transform: uppercase;">
    <?= htmlspecialchars($warga['nama_lengkap']) ?>
</div>

<p style="margin-bottom: 15px; text-align: justify;">
    Umur <?= hitung_umur($warga['tgl_lahir']) ?> tahun, Jenis kelamin <?= strtoupper($warga['jenis_kelamin']) ?> Tempat Tanggal Lahir <?= htmlspecialchars(strtoupper($warga['tempat_lahir'])) ?>, <?= format_tanggal_indo($warga['tgl_lahir']) ?>.
</p>

<p style="text-align: justify;">
    Demikian surat keterangan ini dibuat dengan sebenarnya untuk yang bersangkutan dan kiranya dapat dipergunakan seperlunya.
</p>