<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';
require_once 'includes/admin_header.php';


if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login.php");
    exit;
}

// Mengambil riwayat surat beserta data penduduk terkait
$query = "SELECT t.id, t.nomor_surat, t.tgl_terbit, t.data_dinamis, p.nik, p.nama_lengkap 
          FROM transaksi_surat t 
          JOIN penduduk p ON t.penduduk_id = p.id 
          ORDER BY t.tgl_terbit DESC, t.id DESC";
$result = $koneksi->query($query);
require_once 'includes/admin_header.php';

?>
<!-- Include Header Admin Anda di sini -->

<div style="padding: 20px; background: #fff; border-radius: 10px; margin: 20px;">
    <h2 style="color: #1a6f76; border-bottom: 2px solid #59d5e0; padding-bottom: 10px;">Riwayat Surat Keluar</h2>
    
    <table border="1" style="width: 100%; border-collapse: collapse; margin-top: 15px; text-align: left;">
        <thead style="background: #f8fafc;">
            <tr>
                <th style="padding: 10px;">No.</th>
                <th style="padding: 10px;">Tanggal Terbit</th>
                <th style="padding: 10px;">Nomor Surat</th>
                <th style="padding: 10px;">NIK & Nama Warga</th>
                <th style="padding: 10px;">Jenis Surat</th>
                <th style="padding: 10px;">Keperluan</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $no = 1; while ($row = $result->fetch_assoc()): 
                    // Decode data_dinamis JSON untuk mengambil jenis dan keperluan surat
                    $data_surat = json_decode($row['data_dinamis'], true);
                ?>
                <tr>
                    <td style="padding: 10px;"><?= $no++ ?></td>
                    <td style="padding: 10px;"><?= format_tanggal_indo($row['tgl_terbit']) ?></td>
                    <td style="padding: 10px; font-weight: bold; color: #1a6f76;"><?= htmlspecialchars($row['nomor_surat']) ?></td>
                    <td style="padding: 10px;">
                        <?= htmlspecialchars($row['nik']) ?><br>
                        <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong>
                    </td>
                    <td style="padding: 10px;">
                        <span style="background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            <?= htmlspecialchars($data_surat['jenis'] ?? '-') ?>
                        </span>
                    </td>
                    <td style="padding: 10px;"><?= htmlspecialchars($data_surat['keperluan'] ?? '-') ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="padding: 20px; text-align: center; color: #94a3b8;">Belum ada riwayat surat keluar.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>