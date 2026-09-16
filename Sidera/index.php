<?php
// index.php
require_once 'config/koneksi.php';
require_once 'includes/header.php';

// ==========================================
// OPTIMALISASI PERFORMA: SISTEM CACHING INFOGRAFIS
// ==========================================
$cache_file = 'assets/cache/statistik_desa.json';
$cache_time = 3600; // Cache berlaku selama 1 jam

if (!is_dir('assets/cache')) { mkdir('assets/cache', 0777, true); }

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
    $json_data = file_get_contents($cache_file);
    $statistik = json_decode($json_data, true);
    
    $total_warga = $statistik['total_warga'];
    $data_gender = $statistik['data_gender'];
    $total_l = $data_gender['l'] ?? 0;
    $label_pekerjaan = $statistik['label_pekerjaan'];
    $angka_pekerjaan = $statistik['angka_pekerjaan'];
    $label_agama = $statistik['label_agama'];
    $angka_agama = $statistik['angka_agama'];
    $angka_usia = $statistik['angka_usia'];
} else {
    // CACHE KEDALUWARSA ATAU BELUM ADA -> LAKUKAN QUERY
    $total_warga = 0;
    $q_total = $koneksi->query("SELECT COUNT(id) as total FROM penduduk");
    if($q_total) $total_warga = $q_total->fetch_assoc()['total'];

    $q_gender = $koneksi->query("SELECT jenis_kelamin, COUNT(id) as jumlah FROM penduduk GROUP BY jenis_kelamin");
    $data_gender = ['Laki-laki' => 0, 'Perempuan' => 0];
    if($q_gender){ while($row = $q_gender->fetch_assoc()){ $data_gender[$row['jenis_kelamin']] = $row['jumlah']; } }
    $total_l = $data_gender['Laki-laki'] ?? 0;

    $q_pekerjaan = $koneksi->query("SELECT pekerjaan, COUNT(id) as jumlah FROM penduduk GROUP BY pekerjaan ORDER BY jumlah DESC LIMIT 5");
    $label_pekerjaan = []; $angka_pekerjaan = [];
    if($q_pekerjaan){ while($row = $q_pekerjaan->fetch_assoc()){ $label_pekerjaan[] = $row['pekerjaan']; $angka_pekerjaan[] = $row['jumlah']; } }

    $q_agama = $koneksi->query("SELECT agama, COUNT(id) as jumlah FROM penduduk GROUP BY agama");
    $label_agama = []; $angka_agama = [];
    if($q_agama){ while($row = $q_agama->fetch_assoc()){ $label_agama[] = $row['agama'] ? $row['agama'] : 'Lainnya'; $angka_agama[] = $row['jumlah']; } }

    $q_usia = $koneksi->query("
        SELECT 
            SUM(CASE WHEN umur BETWEEN 0 AND 4 THEN 1 ELSE 0 END) as balita,
            SUM(CASE WHEN umur BETWEEN 5 AND 14 THEN 1 ELSE 0 END) as anak,
            SUM(CASE WHEN umur BETWEEN 15 AND 64 THEN 1 ELSE 0 END) as produktif,
            SUM(CASE WHEN umur >= 65 THEN 1 ELSE 0 END) as lansia
        FROM penduduk
    ");
    $data_usia = $q_usia ? $q_usia->fetch_assoc() : ['balita'=>0, 'anak'=>0, 'produktif'=>0, 'lansia'=>0];
    $angka_usia = [(int)$data_usia['balita'], (int)$data_usia['anak'], (int)$data_usia['produktif'], (int)$data_usia['lansia']];

    $statistik_baru = [
        'total_warga' => $total_warga, 'total_l' => $total_l, 'data_gender' => $data_gender, 'label_pekerjaan' => $label_pekerjaan,
        'angka_pekerjaan' => $angka_pekerjaan, 'label_agama' => $label_agama, 'angka_agama' => $angka_agama, 'angka_usia' => $angka_usia
    ];
    file_put_contents($cache_file, json_encode($statistik_baru));
}

// Pastikan variabel data gender tersedia untuk stat box
$jumlah_l = $data_gender['Laki-laki'] ?? 0;
$jumlah_p = $data_gender['Perempuan'] ?? 0;

// ==========================================
// PENCARIAN DATA PUBLIK
// ==========================================
$hasil_pencarian = null;
$keyword = isset($_GET['cari_nik']) ? $koneksi->real_escape_string($_GET['cari_nik']) : '';
if ($keyword != '') {
    // Sinkronisasi: Query kini menerima pencarian berdasarkan NIK, No KK, atau Nama Lengkap
    $stmt = $koneksi->prepare("SELECT * FROM penduduk WHERE nik = ? OR no_kk = ? OR nama_lengkap LIKE ? LIMIT 1");
    $like_keyword = "%$keyword%";
    $stmt->bind_param("sss", $keyword, $keyword, $like_keyword);
    $stmt->execute();
    $hasil_pencarian = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ==========================================
// DATA PROFIL DESA & KEPALA DESA
// ==========================================
$profil = $koneksi->query("SELECT * FROM profil_desa WHERE id = 1")->fetch_assoc();

$q_kades = $koneksi->query("SELECT nama_pejabat, foto_pejabat FROM struktur_organisasi WHERE parent_id IS NULL AND kategori_id = 1 LIMIT 1");
$data_kades = $q_kades ? $q_kades->fetch_assoc() : null;

// DEKLARASI FUNGSI RENDER BAGAN FORMAL
if (!function_exists('render_publik_multi')) {
    function render_publik_multi(?int $parent_id, int $kategori_id, mysqli $koneksi) {
        $sql = $parent_id === NULL 
            ? "SELECT * FROM struktur_organisasi WHERE parent_id IS NULL AND kategori_id = $kategori_id" 
            : "SELECT * FROM struktur_organisasi WHERE parent_id = $parent_id AND kategori_id = $kategori_id";
        $res = $koneksi->query($sql);
        if ($res && $res->num_rows > 0) {
            echo "<ul>";
            while ($r = $res->fetch_assoc()) {
                echo "<li>";
                echo "<div class='node-card'>";
                echo "<div class='node-jabatan'>" . htmlspecialchars($r['jabatan']) . "</div>";
                echo "<div class='node-foto-wrap'>";
                if (!empty($r['foto_pejabat'])) {
                    echo "<img src='assets/uploads/sotk/".htmlspecialchars($r['foto_pejabat'])."'loading='lazy' alt='Foto Pejabat'>";
                } else {
                    echo "<i class='fa-solid fa-user'></i>";
                }
                echo "</div>";
                echo "<div class='node-nama'>" . htmlspecialchars($r['nama_pejabat']) . "</div>";
                echo "</div>";
                render_publik_multi($r['id'], $kategori_id, $koneksi); 
                echo "</li>";
            }
            echo "</ul>";
        }
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets\css\style.css">
<!-- Panggil CSS Swiper.js -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* ================= HERO SLIDER & LAYOUT OVERLAP ================= */
    .hero { 
        position: relative; 
        width: 100%; 
        height: 100vh; /* Mengambil tinggi penuh layar secara dinamis */
        min-height: 800px; /* Memperluas gambar agar lebih megah */
        overflow: hidden; 
        margin-top: -75px; /* Mengisi ruang di belakang navbar transparan */
    }
    .heroSwiper { width: 100%; height: 100%; }

    /* Efek Zoom Lambat pada Gambar Background */
    .hero-image { 
        position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
        background-size: cover; background-position: center center; 
        z-index: 1; transform: scale(1.1); transition: transform 6s ease; 
    }
    .swiper-slide-active .hero-image { transform: scale(1); }

    /* Overlay Gelap agar Teks Terbaca Kontras */
    .hero-overlay { 
        position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
        background: linear-gradient(to bottom, rgba(15, 23, 42, 0.2) 0%, rgba(15, 23, 42, 0.85) 100%); 
        z-index: 2; 
    }

    /* Penengahan Teks & Penghindaran Tabrakan dengan Kartu */
    .hero-content { 
        position: relative; 
        z-index: 3; 
        display: flex; 
        flex-direction: column; 
        justify-content: center; /* Menengah secara vertikal */
        align-items: center;     /* Menengah secara horizontal */
        text-align: center; 
        width: 100%;             /* Wajib 100% agar teks benar-benar di tengah layar */
        height: 100%; 
        padding: 0 20px 140px 20px; /* Padding bawah diperbesar agar teks tidak tertutup kartu layanan */
        box-sizing: border-box;
    }
    
    .hero-content h1 { 
        font-family: 'Playfair Display', serif; font-size: 56px; font-weight: 700; color: #ffffff;
        margin: 0 auto 20px auto; max-width: 900px; text-shadow: 0 4px 6px rgba(0,0,0,0.5); 
        opacity: 0; transform: translateY(40px); transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1); 
    }
    
    .hero-content p { 
        font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 300; color: #f8fafc;
        max-width: 750px; margin: 0 auto 35px auto; line-height: 1.7; 
        opacity: 0; transform: translateY(40px); transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1); 
    }

    .btn-hero { 
        background: var(--primary); color: #fff; padding: 15px 40px; border-radius: 50px; 
        text-decoration: none; font-weight: 600; font-size: 16px; margin: 0 auto;
        opacity: 0; transform: translateY(40px); transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1); 
        border: 2px solid transparent; box-shadow: 0 10px 25px rgba(26, 111, 118, 0.4); 
    }
    .btn-hero:hover { background: transparent; border-color: var(--secondary); color: var(--secondary); transform: translateY(-3px) !important; }

    /* Trigger Animasi Teks */
    .swiper-slide-active .hero-content h1 { opacity: 1; transform: translateY(0); transition-delay: 0.2s; }
    .swiper-slide-active .hero-content p { opacity: 1; transform: translateY(0); transition-delay: 0.4s; }
    .swiper-slide-active .hero-content .btn-hero { opacity: 1; transform: translateY(0); transition-delay: 0.6s; }

    .swiper-button-next, .swiper-button-prev { color: rgba(255,255,255,0.6); transition: 0.3s; }
    .swiper-button-next:hover, .swiper-button-prev:hover { color: var(--secondary); transform: scale(1.1); }
    .swiper-pagination-bullet { background: #ffffff; opacity: 0.4; transition: 0.3s; }
    .swiper-pagination-bullet-active { background: var(--secondary); opacity: 1; width: 30px; border-radius: 10px; }

    /* ================= KARTU LAYANAN (OVERLAP) ================= */
    .max-w-1200 {
        max-width: 1200px;
        margin: 0 auto;
        position: relative;
        z-index: 10; /* Wajib di atas gambar hero */
    }
    .services-wrapper {
        margin-top: -120px; /* Menarik kartu ke atas gambar hero (Overlap) */
        padding: 0 20px;
    }
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
    }
    .service-card {
        background: #ffffff; padding: 35px 25px; border-radius: 16px; text-align: center;
        box-shadow: 0 15px 35px rgba(0,0,0,0.08); transition: 0.3s;
    }
    .service-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }

    /* Responsivitas Mobile */
    @media (max-width: 768px) {
        .hero { min-height: 600px; height: 90vh; }
        .hero-content h1 { font-size: 38px; }
        .hero-content p { font-size: 15px; padding: 0 10px; }
        .swiper-button-next, .swiper-button-prev { display: none; }
        .services-wrapper { margin-top: -80px; }
    }
</style>

<!-- HERO SECTION SLIDER -->
<section class="hero" id="hero">
    <div class="swiper heroSwiper">
        <div class="swiper-wrapper">
            
            <!-- Slide 1 -->
            <div class="swiper-slide">
                <div class="hero-image" style="background-image: url('assets/img/hero1.jpeg');"></div>
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <h1>Selamat Datang di Desa Serage</h1>
                    <p>Mewujudkan Desa Mandiri, Inovatif, dan Sejahtera melalui tata kelola pemerintahan yang transparan dan digitalisasi layanan publik.</p>
                    <a href="#profil_desa" class="btn-hero">Jelajahi Profil Desa</a>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="swiper-slide">
                <div class="hero-image" style="background-image: url('assets/img/hero2.jpg');"></div>
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <h1>Layanan Administrasi Cepat</h1>
                    <p>Akses surat-menyurat dan data kependudukan kini lebih mudah, efisien, dan dapat diakses langsung oleh seluruh warga.</p>
                    <a href="#timeline" class="btn-hero">Lihat Alur Layanan</a>
                </div>
            </div>

            <!-- Slide 3 -->
            <div class="swiper-slide">
                <div class="hero-image" style="background-image: url('assets/img/hero3.jpeg');"></div>
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <h1>Potensi Alam & Budaya</h1>
                    <p>Menjaga kearifan lokal dan kekayaan alam peninggalan leluhur sembari terus berinovasi menyongsong masa depan.</p>
                    <a href="#potensi_desa" class="btn-hero">Eksplorasi Potensi</a>
                </div>
            </div>

        </div>
        
        <!-- UI Navigasi -->
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
</section>

<div class="max-w-1200">
    <div class="services-wrapper">
        <div class="services-grid">
            <div class="service-card"><i class="fa-solid fa-landmark service-icon"></i><h3>Profil Desa</h3><p>Informasi struktural, sejarah, visi misi, potensi, dan monografi desa secara transparan.</p><a href="#profil_desa">Eksplorasi Profil <i class="fa-solid fa-arrow-right-long"></i></a></div>
            <div class="service-card"><i class="fa-solid fa-users-viewfinder service-icon"></i><h3>Kependudukan</h3><p>Digitalisasi data kependudukan terpadu dengan penyaringan canggih dan analisis akurat.</p><a href="#infografis">Lihat Statistik <i class="fa-solid fa-arrow-right-long"></i></a></div>
            <div class="service-card"><i class="fa-solid fa-file-shield service-icon"></i><h3>Arsip Digital</h3><p>Sistem repositori aman untuk penyimpanan dokumen resmi, surat, dan dokumentasi.</p><a href="">Buka Direktori <i class="fa-solid fa-arrow-right-long"></i></a></div>
            <div class="service-card"><i class="fa-solid fa-envelope-open-text service-icon"></i><h3>Surat Menyurat</h3><p>Akselerasi pembuatan surat administrasi warga dengan teknologi engine auto-fill PDF.</p><a href="#timeline">Alur Layanan <i class="fa-solid fa-arrow-right-long"></i></a></div>
        </div>
    </div>
    
    <!-- PROFIL & MULTI-BAGAN SECTION -->
    <section id="profil_desa" class="section-profil-desa">
        <div class="profil-container">
            
            <div class="kades-quote-wrapper">
                <div class="kades-photo">
                    <?php if($data_kades && $data_kades['foto_pejabat']): ?>
                        <img src="assets/uploads/sotk/<?= htmlspecialchars($data_kades['foto_pejabat']) ?>" alt="Kepala Desa">
                    <?php else: ?>
                        <div class="kades-photo-placeholder"><i class="fa-solid fa-user-tie"></i></div>
                    <?php endif; ?>
                </div>
                <div class="kades-quote-content">
                    <i class="fa-solid fa-quote-left quote-icon"></i>
                    <p>"Sebagai representasi teras depan pemerintahan yang tangguh dan ramah masyarakat, sistem informasi ini adalah wujud gerakan digitalisasi dan inovasi tanpa batas demi mewujudkan pelayanan yang berkeadilan di Desa Serage."</p>
                    <h4><?= $data_kades ? htmlspecialchars($data_kades['nama_pejabat']) : "Kepala Desa" ?></h4>
                    <span>Kepala Desa Serage</span>
                </div>
            </div>

            <div class="top-layout">
                <div class="teks-kolom">
                    <h3>Visi</h3><p><?= nl2br(htmlspecialchars($profil['visi'] ?? '')) ?></p>
                    <h3>Misi</h3><p><?= nl2br(htmlspecialchars($profil['misi'] ?? '')) ?></p>
                </div>
                <div class="logo-tengah">
                    <?php if(!empty($profil['logo_desa'])): ?>
                        <img src="assets/img/<?= htmlspecialchars($profil['logo_desa']) ?>" alt="Logo Desa">
                    <?php else: ?>
                        <div class="logo-placeholder">LOGO DESA</div>
                    <?php endif; ?>
                </div>
                <div class="teks-kolom text-right-desktop">
                    <h3 style="text-align: left;">Sejarah Desa Serage</h3>
                    <p><?= nl2br(htmlspecialchars($profil['sejarah'] ?? '')) ?></p>
                </div>
            </div>

            <?php
            $q_kategori = $koneksi->query("SELECT * FROM kategori_bagan ORDER BY id ASC");
            while ($kat = $q_kategori->fetch_assoc()):
                $cek_isi = $koneksi->query("SELECT id FROM struktur_organisasi WHERE kategori_id = {$kat['id']} LIMIT 1");
                if ($cek_isi->num_rows > 0):
            ?>
                <div class="bagan-section">
                    <h3 class="bagan-title"><?= htmlspecialchars($kat['nama_bagan']) ?></h3>
                    <div class="org-tree">
                        <?php render_publik_multi(NULL, $kat['id'], $koneksi); ?>
                    </div>
                </div>
            <?php 
                endif; 
            endwhile; 
            ?>

            <div class="tentang-desa-box">
                <h3>Tentang Desa Serage</h3>
                <p><?= htmlspecialchars($profil['tentang_desa'] ?? '') ?></p>
            </div>
            
        </div>
    </section>

    <!-- POTENSI DESA SECTION (INLINE EXPAND/COLLAPSE) -->
    <section id="potensi_desa" class="section-potensi">
        <div class="potensi-container">
            
            <div class="potensi-header">
                <h2>CERITE SERAGE</h2>
                <p>Jelajahi keindahan lanskap, kekayaan sumber daya alam, keunikan budaya, serta daya tarik lokal yang menjadikan desa kami istimewa dan layak dikunjungi sepanjang tahun.</p>
            </div>
            
            <div class="potensi-list">
                <?php
                $q_potensi = $koneksi->query("SELECT * FROM potensi_desa ORDER BY id ASC");
                if ($q_potensi && $q_potensi->num_rows > 0):
                    while($potensi = $q_potensi->fetch_assoc()):
                ?>
                <div class="potensi-item">
                    <div class="potensi-img-wrap">
                        <?php 
                        $ext = strtolower(pathinfo($potensi['gambar'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['mp4', 'webm'])): 
                        ?>
                            <video src="assets/uploads/potensi/<?= htmlspecialchars($potensi['gambar']) ?>" muted autoplay loop playsinline></video>
                        <?php else: ?>
                            <img src="assets/uploads/potensi/<?= htmlspecialchars($potensi['gambar']) ?>" loading="lazy" alt="<?= htmlspecialchars($potensi['judul']) ?>">
                        <?php endif; ?>
                    </div>
                    
                    <div class="potensi-info">
                        <h4><?= !empty($potensi['judul']) ? htmlspecialchars($potensi['judul']) : "Sorotan Potensi" ?></h4>
                        <div class="potensi-desc">
                            <p><?= nl2br(htmlspecialchars($potensi['deskripsi'])) ?></p>
                        </div>
                        
                        <!-- Tombol Expand (Buka Layar Penuh Dalam Grid) -->
                        <button class="btn-readmore" onclick="togglePotensi(this)">Lihat Selengkapnya <i class="fa-solid fa-angle-right"></i></button>
                    </div>
                    
                    <!-- Tombol Tutup (Hanya muncul saat mode expanded) -->
                    <button class="btn-close-modal" onclick="togglePotensi(this)"><i class="fa-solid fa-xmark"></i> Tutup Tampilan</button>
                </div>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <p style="text-align:left; color: #9ca3af; font-size: 16px;">Belum ada data potensi desa yang dipublikasikan.</p>
                <?php endif; ?>
            </div>
            
        </div>
    </section>
    <br><br>

    <!-- INFOGRAFIS SECTION -->
    <section id="infografis">
        <div class="info-container">
            <h2 class="sotk-title" style="text-align: center; margin-bottom: 10px;">Infografis & Statistik Desa</h2>
            <p class="section-subtitle-info">Visualisasi komprehensif mulai dari demografi penduduk, transparansi dana desa, potensi wilayah, hingga alur layanan publik.</p>
            
            <div class="demo-grid">
                <div class="demo-stats">
                    <h3 style="color: var(--primary); margin-top:0;">1. Data Demografi</h3>
                    
                    <!-- Total Penduduk -->
                    <div class="stat-box">
                        <i class="fa-solid fa-users"></i>
                        <div><h4><?= number_format($total_warga, 0, ',', '.') ?></h4><p>Total Penduduk </p></div>
                    </div>
                    
                    <!-- Total Laki-laki -->
                    <div class="stat-box" style="border-color: #16a34a;">
                        <i class="fa-solid fa-mars" style="color: #16a34a;"></i>
                        <div><h4><?= number_format($jumlah_l, 0, ',', '.') ?></h4><p>Total Laki-laki </p></div>
                    </div>

                    <!-- Total Perempuan -->
                    <div class="stat-box" style="border-color: #db2777;">
                        <i class="fa-solid fa-venus" style="color: #db2777;"></i>
                        <div><h4><?= number_format($jumlah_p, 0, ',', '.') ?></h4><p>Total Perempuan </p></div>
                    </div>

                </div>
                <div class="chart-box">
                    <h4 style="text-align: center; color: var(--text-dark); margin-bottom: 20px; font-size:16px;">Piramida Kelompok Usia</h4>
                    <div style="height: 200px;"><canvas id="chartUsia"></canvas></div>
                </div>
                <div class="chart-box">
                    <h4 style="text-align: center; color: var(--text-dark); margin-bottom: 20px; font-size:16px;">Rasio Jenis Kelamin</h4>
                    <div style="height: 200px;"><canvas id="chartGender"></canvas></div>
                </div>
                <div class="chart-box">
                    <h4 style="text-align: center; color: var(--text-dark); margin-bottom: 20px;">Distribusi Pekerjaan Utama</h4>
                    <div style="height: 250px;"><canvas id="chartPekerjaan"></canvas></div>
                </div>
                
                                
            </div>

            <div class="demo-charts-extended">
                
            </div>

                        
            <div class="timeline-container" id="timeline">
                <h3 style="color: var(--primary); text-align: center; margin-bottom: 10px;">3. Alur Layanan Administrasi Publik</h3>
                <p style="text-align: center; color: var(--text-gray); font-size: 14px;">Standar Operasional Prosedur (SOP) pembuatan surat pengantar/keterangan warga.</p>
                <div class="timeline">
                    <div class="tl-step"><div class="tl-icon"><i class="fa-solid fa-folder-open"></i></div><div><h5>1. Siapkan Berkas</h5><p>Bawa fotokopi KTP, KK, dan berkas pendukung sesuai jenis surat yang dibutuhkan.</p></div></div>
                    <div class="tl-step"><div class="tl-icon"><i class="fa-solid fa-pen-clip"></i></div><div><h5>2. Pengantar RT/RW</h5><p>Mendapatkan surat pengantar yang telah ditandatangani oleh Ketua RT/RW setempat.</p></div></div>
                    <div class="tl-step"><div class="tl-icon"><i class="fa-solid fa-laptop-file"></i></div><div><h5>3. Proses di Balai Desa</h5><p>Petugas menginput NIK ke sistem SIDERA untuk pencetakan dokumen instan.</p></div></div>
                    <div class="tl-step"><div class="tl-icon" style="background: #10b981;"><i class="fa-solid fa-check-double"></i></div><div><h5 style="color: #10b981;">4. Dokumen Selesai</h5><p>Surat resmi ditandatangani Kepala Desa, dicap basah, dan diserahkan kepada warga.</p></div></div>
                </div>
            </div>
        </div> 
    </section>

    <!-- FITUR PENCARIAN DINAMIS -->
    <div class="search-section" id="pencarian">
        <div class="search-container">
            <div class="search-header">
                <h2 class="sotk-title" style="margin-bottom: 10px;">Pencarian Data Publik</h2>
                <p style="color: var(--text-gray);">Verifikasi status kependudukan dengan memasukkan NIK atau Nama Lengkap.</p>
            </div>
                        
            <form action="#pencarian" method="GET">
                <div class="search-input-wrapper">
                    <i class="fa-solid fa-magnifying-glass" style="padding: 18px 0 18px 25px; color: var(--text-gray);"></i>
                    <!-- Sinkronisasi: Update placeholder untuk menginformasikan pencarian by KK -->
                    <input type="text" name="cari_nik" value="<?= htmlspecialchars($keyword) ?>" placeholder="Ketik NIK, No KK, atau Nama warga di sini..." required>
                    <button type="submit" class="search-btn">Telusuri Data</button>
                </div>
            </form>

            <?php if ($keyword != ''): ?>
                <?php if ($hasil_pencarian): ?>
                    <div class="result-grid">
                        <div class="result-label">Nomor Kartu Keluarga (KK)</div>
                        <div class="result-value"><?= !empty($hasil_pencarian['no_kk']) ? substr(htmlspecialchars($hasil_pencarian['no_kk']), 0, 6) . str_repeat('*', 10) : '-' ?></div>
                        
                        <div class="result-label">Nomor Induk (NIK)</div>
                        <div class="result-value"><?= substr(htmlspecialchars($hasil_pencarian['nik']), 0, 6) . str_repeat('*', 10) ?></div>                     
                        <div class="result-label">Nama Lengkap</div>
                        <div class="result-value"><?= htmlspecialchars($hasil_pencarian['nama_lengkap']) ?></div>
                        
                        <div class="result-label">Umur</div>
                        <div class="result-value"><?= htmlspecialchars($hasil_pencarian['umur']) ?> Tahun</div>
                        
                        <div class="result-label">Status</div>
                        <div class="result-value"><?= htmlspecialchars($hasil_pencarian['status_perkawinan']) ?></div>
                        
                        <div class="result-label">Jenis Kelamin</div>
                        <div class="result-value"><?= $hasil_pencarian['jenis_kelamin'] == 'Laki-laki' ? 'Laki-laki' : 'Perempuan' ?></div>
                        
                        <div class="result-label">Alamat</div>
                        <div class="result-value"> Desa <?= htmlspecialchars($hasil_pencarian['kel_desa']) ?>, Kecamatan <?= htmlspecialchars($hasil_pencarian['kecamatan']) ?>, Kabupaten <?= htmlspecialchars($hasil_pencarian['kabupaten']) ?>, Provinsi <?= htmlspecialchars($hasil_pencarian['provinsi']) ?></div>
                        
                        <div class="result-label">Wilayah (RT/RW)</div>
                        <div class="result-value"><?= htmlspecialchars($hasil_pencarian['nama_dusun']) ?>, RT <?= htmlspecialchars($hasil_pencarian['rt']) ?> / RW <?= htmlspecialchars($hasil_pencarian['rw']) ?></div>
                        
                        <div class="result-label">Status Data</div>
                        <div class="result-value" style="color: #4CAF50; font-weight: bold;"><i class="fa-solid fa-circle-check"></i> Terverifikasi di Sistem</div>
                    </div>
                <?php else: ?>
                    <div class="result-grid" style="grid-template-columns: 1fr; text-align: center; color: #e53e3e;">
                        <i class="fa-solid fa-file-circle-xmark" style="font-size: 40px; margin-bottom: 10px;"></i>
                        <div class="result-value">Data dengan kata kunci "<?= htmlspecialchars($keyword) ?>" tidak ditemukan di basis data kami.</div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // JS Untuk Mengontrol Mode INLINE EXPAND
    function togglePotensi(btn) {
        const itemYangDiklik = btn.closest('.potensi-item');
        const sedangTerbuka = itemYangDiklik.classList.contains('is-expanded');
        
        // 1. Tutup SEMUA item terlebih dahulu (Accordion behavior)
        document.querySelectorAll('.potensi-item').forEach(el => {
            el.classList.remove('is-expanded');
        });
        
        // 2. Jika item yang diklik sebelumnya tertutup, maka Buka item tersebut
        if (!sedangTerbuka) {
            itemYangDiklik.classList.add('is-expanded');
            
            // Berikan jeda sejenak untuk membiarkan animasi CSS berjalan, lalu scroll agar item pas di tengah layar
            setTimeout(() => {
                itemYangDiklik.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        }
    }

    // Chart.js Bawaan
    const colorPrimary = '#1a6f76';
    const colorSecondary = '#59d5e0';
    
    new Chart(document.getElementById('chartPekerjaan').getContext('2d'), {
        type: 'bar',
        data: { labels: <?= json_encode(empty($label_pekerjaan) ? ['Data Kosong'] : $label_pekerjaan) ?>, datasets: [{ label: 'Jumlah Warga', data: <?= json_encode(empty($angka_pekerjaan) ? [0] : $angka_pekerjaan) ?>, backgroundColor: colorSecondary, borderRadius: 6, barThickness: 35 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } } }
    });
    new Chart(document.getElementById('chartGender').getContext('2d'), {
        type: 'doughnut',
        data: { labels: ['Laki-laki', 'Perempuan'], datasets: [{ data: [<?= $data_gender['Laki-laki'] ?>, <?= $data_gender['Perempuan'] ?>], backgroundColor: [colorPrimary, '#f59e0b'], borderWidth: 0, hoverOffset: 5 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { font: { family: 'Poppins', size: 12 } } } } }
    });
    
    new Chart(document.getElementById('chartUsia').getContext('2d'), {
        type: 'bar',
        data: { labels: ['Balita (0-4)', 'Anak (5-14)', 'Produktif (15-64)', 'Lansia (65+)'], datasets: [{ label: 'Jumlah Warga', data: <?= json_encode($angka_usia) ?>, backgroundColor: '#10b981', borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } } }
    });
</script>

<!-- Panggil JS Swiper.js -->
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
    // Inisialisasi Slider Hero dengan Autoplay
    var heroSwiper = new Swiper(".heroSwiper", {
        effect: "fade",       // Menggunakan efek fade antar gambar
        loop: true,           // Gambar kembali ke awal saat habis
        speed: 1500,          // Kecepatan transisi fade (1.5 detik)
        autoplay: {
            delay: 5000,      // Waktu tunggu antar slide (5 detik)
            disableOnInteraction: false, // Autoplay tidak mati saat user mengklik panah
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
    });
</script>

<?php require_once 'includes/footer.php'; ?>