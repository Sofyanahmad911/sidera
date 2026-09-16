<?php
// admin/index.php
require_once '../config/koneksi.php';
require_once 'includes/admin_header.php';

// =========================================================
// 1. ENGINE FILTERING
// =========================================================
$f_dusun = isset($_GET['dusun']) ? $koneksi->real_escape_string($_GET['dusun']) : '';
$f_jk    = isset($_GET['jk']) ? $koneksi->real_escape_string($_GET['jk']) : '';
$f_umur  = isset($_GET['umur']) ? $koneksi->real_escape_string($_GET['umur']) : '';

$where_clauses = ["1=1"];
if ($f_dusun != '') $where_clauses[] = "nama_dusun = '$f_dusun'";
if ($f_jk != '')    $where_clauses[] = "(jenis_kelamin = '$f_jk' OR LOWER(jenis_kelamin) LIKE '" . strtolower(substr($f_jk, 0, 1)) . "%')";

// Logika Rentang Umur
if ($f_umur != '') {
    if ($f_umur == 'balita') {
        $where_clauses[] = "umur BETWEEN 0 AND 4";
    } elseif ($f_umur == 'anak') {
        $where_clauses[] = "umur BETWEEN 5 AND 14";
    } elseif ($f_umur == 'produktif') {
        $where_clauses[] = "umur BETWEEN 15 AND 64";
    } elseif ($f_umur == 'lansia') {
        $where_clauses[] = "umur >= 65";
    }
}

$where_sql = implode(' AND ', $where_clauses);

// =========================================================
// 2. KUERI METRIK KPI
// =========================================================
$total_penduduk = ($q = $koneksi->query("SELECT COUNT(id) as t FROM penduduk WHERE $where_sql")) ? $q->fetch_assoc()['t'] : 0;
$total_l = ($q = $koneksi->query("SELECT COUNT(id) as t FROM penduduk WHERE $where_sql AND (jenis_kelamin='Laki-laki' OR LOWER(jenis_kelamin) LIKE 'l%')")) ? $q->fetch_assoc()['t'] : 0;
$total_p = ($q = $koneksi->query("SELECT COUNT(id) as t FROM penduduk WHERE $where_sql AND (jenis_kelamin='Perempuan' OR LOWER(jenis_kelamin) LIKE 'p%')")) ? $q->fetch_assoc()['t'] : 0;
$total_arsip = ($q = $koneksi->query("SELECT COUNT(id) as t FROM arsip_desa")) ? $q->fetch_assoc()['t'] : 0;

// =========================================================
// 3. KUERI DATA CHART
// =========================================================
$q_pekerjaan = $koneksi->query("SELECT pekerjaan, COUNT(id) as jumlah FROM penduduk WHERE $where_sql GROUP BY pekerjaan ORDER BY jumlah DESC LIMIT 5");
$lbl_pekerjaan = []; $val_pekerjaan = [];
if ($q_pekerjaan) { while ($r = $q_pekerjaan->fetch_assoc()) { $lbl_pekerjaan[] = $r['pekerjaan'] ?: 'Lainnya'; $val_pekerjaan[] = $r['jumlah']; } }

$q_persebaran = $koneksi->query("SELECT CONCAT('RT ', rt, ' Dsn. ', nama_dusun) as wilayah, COUNT(id) as jumlah FROM penduduk WHERE $where_sql GROUP BY nama_dusun, rt ORDER BY jumlah DESC LIMIT 8");
$lbl_persebaran = []; $val_persebaran = [];
if ($q_persebaran) { while ($r = $q_persebaran->fetch_assoc()) { $lbl_persebaran[] = $r['wilayah']; $val_persebaran[] = $r['jumlah']; } }

$q_usia = $koneksi->query("
    SELECT 
        SUM(CASE WHEN umur BETWEEN 0 AND 4 THEN 1 ELSE 0 END) as balita,
        SUM(CASE WHEN umur BETWEEN 5 AND 14 THEN 1 ELSE 0 END) as anak,
        SUM(CASE WHEN umur BETWEEN 15 AND 64 THEN 1 ELSE 0 END) as produktif,
        SUM(CASE WHEN umur >= 65 THEN 1 ELSE 0 END) as lansia
    FROM penduduk WHERE $where_sql
");
$data_usia = $q_usia ? $q_usia->fetch_assoc() : ['balita'=>0, 'anak'=>0, 'produktif'=>0, 'lansia'=>0];
$val_usia = [(int)$data_usia['balita'], (int)$data_usia['anak'], (int)$data_usia['produktif'], (int)$data_usia['lansia']];

$q_agama = $koneksi->query("SELECT agama, COUNT(id) as jumlah FROM penduduk WHERE $where_sql GROUP BY agama");
$lbl_agama = []; $val_agama = [];
if ($q_agama) { while ($r = $q_agama->fetch_assoc()) { $lbl_agama[] = $r['agama'] ?: 'Lainnya'; $val_agama[] = $r['jumlah']; } }

$q_list_dusun = $koneksi->query("SELECT DISTINCT nama_dusun FROM penduduk WHERE nama_dusun != '' ORDER BY nama_dusun ASC");
?>

<style>
    /* KONTAINER UTAMA AGAR TIDAK MELEBAR PADA LAYAR ULTRAWIDE */
    .dashboard-wrapper { max-width: 1400px; margin: 0 auto; padding: 10px; }

    /* SLICER / FILTER BAR */
    .slicer-container { background: #fff; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap; border-top: 4px solid #1a6f76; }
    .slicer-group { display: flex; align-items: center; gap: 10px; }
    .slicer-label { font-size: 12px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
    .slicer-select { background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 30px 8px 15px; border-radius: 4px; font-size: 13px; font-weight: 600; color: #1a6f76; cursor: pointer; outline: none; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%231a6f76%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 10px top 50%; background-size: 10px auto; min-width: 150px; }
    
    /* LAYOUT GRID PROPOSIONAL */
    .row-charts { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
    .row-kpi { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }

    /* PANEL CHART */
    .panel { background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 25px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; }
    .panel-title { font-size: 13.5px; font-weight: 700; color: #475569; margin: 0 0 20px 0; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }
    .chart-box { position: relative; width: 100%; height: 300px; display: flex; justify-content: center; align-items: center; }

    /* KARTU KPI */
    .kpi-card { background: #0f4c5c; color: #ffffff; border-radius: 8px; padding: 25px 20px; text-align: center; box-shadow: 0 4px 6px rgba(15, 76, 92, 0.2); transition: transform 0.2s; }
    .kpi-card:hover { transform: translateY(-3px); }
    .kpi-value { font-size: 34px; font-weight: 800; margin: 0 0 5px 0; letter-spacing: 1px; color: #fbbf24; }
    .kpi-label { font-size: 12.5px; font-weight: 600; text-transform: uppercase; opacity: 0.9; letter-spacing: 0.5px; }

    /* RESPONSIVE BREAKPOINTS */
    @media (max-width: 1100px) {
        .row-charts { grid-template-columns: 1fr; }
        .row-kpi { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .row-kpi { grid-template-columns: 1fr; }
        .slicer-select { width: 100%; }
        .chart-box { height: 260px; }
    }
</style>

<div class="dashboard-wrapper">
    <div class="page-header" style="margin-bottom: 20px;">
        <h1 class="page-title" style="margin: 0; color: #0f172a; font-size: 24px;">Dashboard Eksekutif</h1>
    </div>

    <!-- FILTER BAR (SLICER) -->
    <form action="" method="GET" id="filterForm" class="slicer-container">
        <div class="slicer-group">
            <div class="slicer-label">Wilayah</div>
            <select name="dusun" class="slicer-select" onchange="document.getElementById('filterForm').submit();">
                <option value="">Semua Dusun</option>
                <?php if($q_list_dusun): while($d = $q_list_dusun->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($d['nama_dusun']) ?>" <?= $f_dusun == $d['nama_dusun'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nama_dusun']) ?></option>
                <?php endwhile; endif; ?>
            </select>
        </div>
        <div class="slicer-group">
            <div class="slicer-label">Gender</div>
            <select name="jk" class="slicer-select" onchange="document.getElementById('filterForm').submit();">
                <option value="">Semua Gender</option>
                <option value="Laki-laki" <?= $f_jk == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                <option value="Perempuan" <?= $f_jk == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
            </select>
        </div>
        <div class="slicer-group">
            <div class="slicer-label">Umur</div>
            <select name="umur" class="slicer-select" onchange="document.getElementById('filterForm').submit();">
                <option value="">Semua Umur</option>
                <option value="balita" <?= $f_umur == 'balita' ? 'selected' : '' ?>>Balita (0-4 Tahun)</option>
                <option value="anak" <?= $f_umur == 'anak' ? 'selected' : '' ?>>Anak (5-14 Tahun)</option>
                <option value="produktif" <?= $f_umur == 'produktif' ? 'selected' : '' ?>>Produktif (15-64 Tahun)</option>
                <option value="lansia" <?= $f_umur == 'lansia' ? 'selected' : '' ?>>Lansia (65+ Tahun)</option>
            </select>
        </div>
        <?php if($f_dusun != '' || $f_jk != '' || $f_umur != ''): ?>
            <a href="index.php" style="font-size: 13px; color: #ef4444; font-weight: bold; text-decoration: none; margin-left: auto;">[X] Bersihkan Filter</a>
        <?php endif; ?>
    </form>

    <!-- BARIS 1: Chart Kiri & Kanan -->
    <div class="row-charts">
        <div class="panel">
            <h3 class="panel-title">Top 5 Pekerjaan Utama</h3>
            <div class="chart-box"><canvas id="chartPekerjaan"></canvas></div>
        </div>
        <div class="panel">
            <h3 class="panel-title">Kepadatan Wilayah (Top 8 RT)</h3>
            <div class="chart-box"><canvas id="chartPersebaran"></canvas></div>
        </div>
    </div>

    <!-- BARIS 2: KPI CARDS -->
    <div class="row-kpi">
        <div class="kpi-card">
            <div class="kpi-value"><?= number_format($total_penduduk, 0, ',', '.') ?></div>
            <div class="kpi-label">Total Penduduk</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value"><?= number_format($total_l, 0, ',', '.') ?></div>
            <div class="kpi-label">Laki-Laki</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value"><?= number_format($total_p, 0, ',', '.') ?></div>
            <div class="kpi-label">Perempuan</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value"><?= number_format($total_arsip, 0, ',', '.') ?></div>
            <div class="kpi-label">Arsip Digital</div>
        </div>
    </div>

    <!-- BARIS 3: Chart Demografi & Piramida Usia -->
    <div class="row-charts">
        <div class="panel">
            <h3 class="panel-title">Distribusi Demografi (Agama)</h3>
            <div class="chart-box"><canvas id="chartDoughnut"></canvas></div>
        </div>
        <div class="panel">
            <h3 class="panel-title">Piramida Kelompok Usia</h3>
            <div class="chart-box"><canvas id="chartUsia"></canvas></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.color = '#64748b';
    const colors = ['#0f4c5c', '#2cb1bc', '#fbbf24', '#e28743', '#9ca3af', '#1e293b'];

    const commonOptions = {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 20, font: { size: 11, weight: '600' } } },
            tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 4, titleFont: {size: 13}, bodyFont: {size: 14, weight: 'bold'} }
        }
    };

    // 1. Chart Pekerjaan (Horizontal Bar dengan maxBarThickness)
    new Chart(document.getElementById('chartPekerjaan').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(empty($lbl_pekerjaan) ? ['Kosong'] : $lbl_pekerjaan) ?>,
            datasets: [{ 
                data: <?= json_encode(empty($val_pekerjaan) ? [0] : $val_pekerjaan) ?>, 
                backgroundColor: '#0f4c5c', borderRadius: 4, maxBarThickness: 35 
            }]
        },
        options: {
            ...commonOptions, indexAxis: 'y',
            scales: {
                x: { grid: { display: false }, ticks: { display: false } },
                y: { grid: { display: false }, ticks: { font: { weight: '600', color: '#334155' } } }
            },
            plugins: { ...commonOptions.plugins, legend: { display: false } }
        }
    });

    // 2. Chart Persebaran Wilayah
    new Chart(document.getElementById('chartPersebaran').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(empty($lbl_persebaran) ? ['Kosong'] : $lbl_persebaran) ?>,
            datasets: [{ 
                data: <?= json_encode(empty($val_persebaran) ? [0] : $val_persebaran) ?>, 
                backgroundColor: '#2cb1bc', borderRadius: 4, maxBarThickness: 45 
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false } }
            },
            plugins: { ...commonOptions.plugins, legend: { display: false } }
        }
    });

    // 3. Chart Demografi (Doughnut)
    new Chart(document.getElementById('chartDoughnut').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(empty($lbl_agama) ? ['Kosong'] : $lbl_agama) ?>,
            datasets: [{ data: <?= json_encode(empty($val_agama) ? [1] : $val_agama) ?>, backgroundColor: colors, borderWidth: 0 }]
        },
        options: { ...commonOptions, cutout: '65%' }
    });

    // 4. Chart Kelompok Usia
    new Chart(document.getElementById('chartUsia').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Balita (0-4)', 'Anak (5-14)', 'Produktif (15-64)', 'Lansia (65+)'],
            datasets: [{ 
                data: <?= json_encode($val_usia) ?>, 
                backgroundColor: '#0f4c5c', borderRadius: 4, maxBarThickness: 50 
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                x: { grid: { display: false }, ticks: { font: { weight: '600' } } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false } }
            },
            plugins: { ...commonOptions.plugins, legend: { display: false } }
        }
    });
</script>

<?php require_once 'includes/admin_footer.php'; ?>