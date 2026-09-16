<?php
// admin/export_pdf.php
require_once '../config/koneksi.php';

$where_clauses = ["1=1"];
$search = isset($_GET['search']) ? $koneksi->real_escape_string($_GET['search']) : '';
$f_jk = isset($_GET['f_jk']) ? $koneksi->real_escape_string($_GET['f_jk']) : '';
$sort = isset($_GET['sort']) ? $koneksi->real_escape_string($_GET['sort']) : 'id DESC';

if ($search != '') $where_clauses[] = "(nik LIKE '%$search%' OR nama_lengkap LIKE '%$search%')";
if ($f_jk == 'Laki-laki') $where_clauses[] = "(jenis_kelamin = 'Laki-laki' OR jenis_kelamin = 'l')";
if ($f_jk == 'Perempuan') $where_clauses[] = "(jenis_kelamin = 'Perempuan' OR jenis_kelamin = 'p')";

$where_sql = implode(' AND ', $where_clauses);
$q = $koneksi->query("SELECT * FROM penduduk WHERE $where_sql ORDER BY $sort");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export PDF - Data Kependudukan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        h2 { text-align: center; margin-bottom: 5px; color: #000; }
        .subtitle { text-align: center; margin-bottom: 20px; font-size: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        @media print { @page { size: landscape; margin: 10mm; } }
    </style>
</head>
<body onload="window.print()">
    <h2>LAPORAN DATA KEPENDUDUKAN DESA SERAGE</h2>
    <div class="subtitle">Kecamatan Praya Barat Daya, Kabupaten Lombok Tengah<br>Dicetak pada: <?= date('d M Y, H:i') ?></div>
    
    <table>
        <tr>
            <th>No</th><th>NIK</th><th>Nama Lengkap</th><th>Tempat, Tgl Lahir</th><th>Umur</th>
            <th>L/P</th><th>Agama</th><th>Pekerjaan</th><th>Status</th><th>RT/RW - Dusun</th>
        </tr>
        <?php $no=1; while($r = $q->fetch_assoc()): 
            $jk_tampil = (strtolower($r['jenis_kelamin']) == 'l' || strtolower($r['jenis_kelamin']) == 'laki-laki') ? 'L' : 'P';
        ?>
        <tr>
            <td style="text-align:center;"><?= $no++ ?></td>
            <td><?= $r['nik'] ?></td>
            <td><?= $r['nama_lengkap'] ?></td>
            <td><?= $r['tempat_lahir'] ?>, <?= date('d-m-Y', strtotime($r['tgl_lahir'])) ?></td>
            <td style="text-align:center;"><?= $r['umur'] ?></td>
            <td style="text-align:center;"><?= $jk_tampil ?></td>
            <td><?= $r['agama'] ?></td>
            <td><?= $r['pekerjaan'] ?></td>
            <td><?= $r['status_perkawinan'] ?></td>
            <td>RT <?= $r['rt'] ?>/RW <?= $r['rw'] ?> - <?= $r['nama_dusun'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>