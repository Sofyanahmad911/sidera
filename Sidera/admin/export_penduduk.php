<?php
// admin/export_penduduk.php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Proteksi Akses
if (!isset($_SESSION['admin_logged_in'])) {
    die("Akses Ditolak.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export_custom'])) {
    $selected_columns = $_POST['kolom'] ?? [];
    
    if (empty($selected_columns)) {
        die("<script>alert('Gagal: Pilih minimal satu kolom untuk diekspor!'); history.back();</script>");
    }

    // 1. Pemetaan Kolom (Mencegah SQL Injection & Menentukan Header CSV)
    $available_columns = [
        'nik' => 'NIK',
        'nama_lengkap' => 'Nama Lengkap',
        'tempat_lahir' => 'Tempat Lahir',
        'tgl_lahir' => 'Tanggal Lahir',
        'umur' => 'Umur',
        'jenis_kelamin' => 'Jenis Kelamin',
        'agama' => 'Agama',
        'status_perkawinan' => 'Status Kawin',
        'pekerjaan' => 'Pekerjaan',
        'kewarganegaraan' => 'Kewarganegaraan',
        'rt' => 'RT',
        'rw' => 'RW',
        'nama_dusun' => 'Dusun'
    ];

    $valid_columns = [];
    $csv_headers = ['No']; // Kolom 'No' selalu ada di awal
    
    foreach ($selected_columns as $col) {
        if (array_key_exists($col, $available_columns)) {
            $valid_columns[] = $col;
            $csv_headers[] = $available_columns[$col];
        }
    }

    // 2. Terapkan Filter yang Sedang Aktif
    $search = isset($_POST['search']) ? $koneksi->real_escape_string($_POST['search']) : '';
    $f_jk = isset($_POST['f_jk']) ? $koneksi->real_escape_string($_POST['f_jk']) : '';

    $where_clauses = ["1=1"];
    if ($search != '') $where_clauses[] = "(nik LIKE '%$search%' OR nama_lengkap LIKE '%$search%')";
    if ($f_jk == 'Laki-laki') $where_clauses[] = "(jenis_kelamin = 'Laki-laki' OR jenis_kelamin = 'l')";
    if ($f_jk == 'Perempuan') $where_clauses[] = "(jenis_kelamin = 'Perempuan' OR jenis_kelamin = 'p')";
    $where_sql = implode(' AND ', $where_clauses);

    // 3. Eksekusi Query Dinamis
    $select_sql = implode(', ', $valid_columns);
    $query = "SELECT $select_sql FROM penduduk WHERE $where_sql ORDER BY nama_lengkap ASC";
    $result = $koneksi->query($query);

    // 4. Force Download Header
    $filename = "Data_Penduduk_Custom_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM agar Excel membaca UTF-8
    
    fputcsv($output, $csv_headers);

    // 5. Tulis Data
    $no = 1;
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row_data = [$no++];
            foreach ($valid_columns as $col) {
                // Formatting khusus untuk tanggal lahir
                if ($col == 'tgl_lahir') {
                    $row_data[] = format_tanggal_indo($row[$col]);
                } else {
                    $row_data[] = $row[$col];
                }
            }
            fputcsv($output, $row_data);
        }
    } else {
        fputcsv($output, ['Tidak ada data yang sesuai dengan filter']);
    }

    // 6. Catat Aktivitas
    catat_log($koneksi, $_SESSION['admin_id'], 'Export Data', "Mengekspor data penduduk dengan filter kustom (".count($valid_columns)." kolom)");
    fclose($output);
    exit;
}
?>