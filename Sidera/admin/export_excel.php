<?php
// admin/export_excel.php
require_once '../config/koneksi.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Data_Kependudukan_Serage_" . date('Ymd_His') . ".xls");

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

<table border="1">
    <tr>
        <th style="background-color: #1a6f76; color: white;">No</th>
        <th style="background-color: #1a6f76; color: white;">NIK</th>
        <th style="background-color: #1a6f76; color: white;">Nama Lengkap</th>
        <th style="background-color: #1a6f76; color: white;">Tempat Lahir</th>
        <th style="background-color: #1a6f76; color: white;">Tanggal Lahir</th>
        <th style="background-color: #1a6f76; color: white;">Umur</th>
        <th style="background-color: #1a6f76; color: white;">Jenis Kelamin</th>
        <th style="background-color: #1a6f76; color: white;">Agama</th>
        <th style="background-color: #1a6f76; color: white;">Status Perkawinan</th>
        <th style="background-color: #1a6f76; color: white;">Pekerjaan</th>
        <th style="background-color: #1a6f76; color: white;">Kewarganegaraan</th>
        <th style="background-color: #1a6f76; color: white;">RT</th>
        <th style="background-color: #1a6f76; color: white;">RW</th>
        <th style="background-color: #1a6f76; color: white;">Nama Dusun</th>
        <th style="background-color: #1a6f76; color: white;">Desa</th>
        <th style="background-color: #1a6f76; color: white;">Kecamatan</th>
        <th style="background-color: #1a6f76; color: white;">Kabupaten</th>
        <th style="background-color: #1a6f76; color: white;">Provinsi</th>
    </tr>
    <?php $no=1; while($r = $q->fetch_assoc()): 
        // Sinkronisasi label jenis kelamin
        $jk_tampil = $r['jenis_kelamin'];
        if(strtolower($jk_tampil) == 'l') $jk_tampil = 'Laki-laki';
        if(strtolower($jk_tampil) == 'p') $jk_tampil = 'Perempuan';
    ?>
    <tr>
        <td><?= $no++ ?></td>
        <td style="mso-number-format:'\@';"><?= $r['nik'] ?></td> <!-- Mencegah NIK berubah jadi rumus di Excel -->
        <td><?= $r['nama_lengkap'] ?></td>
        <td><?= $r['tempat_lahir'] ?></td>
        <td><?= $r['tgl_lahir'] ?></td>
        <td><?= $r['umur'] ?></td>
        <td><?= $jk_tampil ?></td>
        <td><?= $r['agama'] ?></td>
        <td><?= $r['status_perkawinan'] ?></td>
        <td><?= $r['pekerjaan'] ?></td>
        <td><?= $r['kewarganegaraan'] ?></td>
        <td><?= $r['rt'] ?></td>
        <td><?= $r['rw'] ?></td>
        <td><?= $r['nama_dusun'] ?></td>
        <td><?= $r['kel_desa'] ?></td>
        <td><?= $r['kecamatan'] ?></td>
        <td><?= $r['kabupaten'] ?></td>
        <td><?= $r['provinsi'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>