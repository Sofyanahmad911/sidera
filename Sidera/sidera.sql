-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Agu 2026 pada 10.40
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sidera`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `arsip_desa`
--

CREATE TABLE `arsip_desa` (
  `id` bigint(20) NOT NULL,
  `judul_arsip` varchar(200) NOT NULL,
  `kategori_arsip` enum('Surat Masuk','Surat Keluar','Foto Kegiatan','Dokumen Penting') NOT NULL,
  `nomor_dokumen` varchar(100) DEFAULT NULL,
  `tgl_dokumen` date NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori_bagan`
--

CREATE TABLE `kategori_bagan` (
  `id` int(11) NOT NULL,
  `nama_bagan` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori_bagan`
--

INSERT INTO `kategori_bagan` (`id`, `nama_bagan`) VALUES
(1, 'SOTK Pemdes Serage'),
(2, 'BPD');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `aksi` varchar(100) NOT NULL,
  `detail_aksi` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `penduduk`
--

CREATE TABLE `penduduk` (
  `id` bigint(20) NOT NULL,
  `foto_warga` varchar(255) DEFAULT NULL,
  `nik` varchar(16) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `tempat_lahir` varchar(100) NOT NULL,
  `tgl_lahir` date NOT NULL,
  `umur` int(11) NOT NULL,
  `jenis_kelamin` varchar(20) NOT NULL,
  `rt` varchar(3) NOT NULL,
  `rw` varchar(3) NOT NULL,
  `nama_dusun` varchar(100) NOT NULL,
  `kel_desa` varchar(100) NOT NULL,
  `kecamatan` varchar(100) NOT NULL,
  `kabupaten` varchar(100) NOT NULL,
  `provinsi` varchar(100) NOT NULL,
  `agama` varchar(30) NOT NULL,
  `status_perkawinan` enum('Belum Kawin','Kawin','Cerai Hidup','Cerai Mati') NOT NULL,
  `pekerjaan` varchar(100) NOT NULL,
  `kewarganegaraan` varchar(50) DEFAULT 'wni',
  `arsip_foto_ktp_kk` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penduduk`
--

INSERT INTO `penduduk` (`id`, `foto_warga`, `nik`, `nama_lengkap`, `tempat_lahir`, `tgl_lahir`, `umur`, `jenis_kelamin`, `rt`, `rw`, `nama_dusun`, `kel_desa`, `kecamatan`, `kabupaten`, `provinsi`, `agama`, `status_perkawinan`, `pekerjaan`, `kewarganegaraan`, `arsip_foto_ktp_kk`, `created_at`, `updated_at`) VALUES
(1, NULL, '5202041234560001', 'Herman Yadi', 'Lombok Timur', '1985-08-17', 41, 'Laki-laki', '001', '002', 'lekong jae', 'Serage', 'Praya Barat Daya', 'Lombok Tengah', 'Nusa Tenggara Barat', 'Islam', 'Kawin', 'Kepala Desa', 'WNI', NULL, '2026-08-21 02:17:04', '2026-08-21 03:16:41'),
(2, NULL, '5202041234560002', 'Siti Aminah', 'Mataram', '1990-12-05', 35, 'Perempuan', '001', '002', 'kesempuh', 'Serage', 'Praya Barat Daya', 'Lombok Tengah', 'Nusa Tenggara Barat', 'Islam', 'Belum Kawin', 'Wiraswasta', 'WNI', NULL, '2026-08-21 02:17:04', '2026-08-21 03:16:35');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengajuan_surat`
--

CREATE TABLE `pengajuan_surat` (
  `id` int(11) NOT NULL,
  `nik_pemohon` varchar(20) NOT NULL,
  `jenis_surat` varchar(100) NOT NULL,
  `keperluan` text NOT NULL,
  `status` enum('Menunggu','Diproses','Selesai','Ditolak') DEFAULT 'Menunggu',
  `tanggal_request` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `potensi_desa`
--

CREATE TABLE `potensi_desa` (
  `id` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `deskripsi` text NOT NULL,
  `gambar` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `potensi_desa`
--

INSERT INTO `potensi_desa` (`id`, `judul`, `deskripsi`, `gambar`, `created_at`) VALUES
(3, 'nyesek ', 'Kain tenun songket lombok merupakan kain tenun dengan motif asli khas pulau lombok.\r\nBerbeda dengan kain tenun rang rang, motif kain tenun songket mengisi penuh seluruh lembaran kain.\r\nSongket berasal dari kata sungkit yang berarti mengangkat.\r\nHal tersebut sesuai dengan proses pembuatan motif kain songet, dimana motif tenun tersebut dibuat dengan cara mengangkat sejumlah kain lungsi dengan lidi untuk membentuk rongga rongga.\r\nRongga rongga tersebut selanjutnya dimasuki oleh benang pakan secara berulang kali, dengan warna benang sesuai motif yang hendak dibuat.\r\nKain tenun songket memiliki beberapa keunikan, diantaranya adalah:\r\nMemiliki motif tenun yang timbul\r\nMotif memadati seluruh permukaan songket\r\nDitenun menggunakan benang emas, benang perak, ataupun benang katun berwarna\r\nMotif nya terkesan lebih mewah dan elegan\r\nProses pembuatannya memerlukan waktu lebih lama, karena detail motifnya yang lebih rumit', '1787195425_c5b9ebfae6.mp4', '2026-08-20 03:10:25'),
(4, 'Roah bubur beaq', 'Tradisi Roah Bubur Beaq merupakan cerminan kuat dari perpaduan nilai spiritual Islam dan adat istiadat lokal suku Sasak yang dikenal dengan konsep wetu telu atau akulturasi budaya.\r\nBerikut adalah beberapa poin penting yang memperdalam makna dari tradisi tersebut:\r\nMakna dan Filosofi Bubur Merah Simbol Kehidupan: Dalam filosofi masyarakat Sasak dan Nusantara pada umumnya, bubur merah (beaq) dan bubur putih (puteq) sering kali melambangkan asal-usul penciptaan manusia (pria dan wanita atau unsur darah dan kesucian).Wujud Syukur: Sajian ini menjadi media simbolis untuk menyatakan rasa syukur atas kelahiran, keselamatan, atau berkah kehidupan yang diterima oleh keluarga atau komunitas.Fungsi Sosial dan Relevansi Tradisi\r\nPenguat Tali Silaturahmi: Proses pembuatan bubur secara gotong royong mempererat hubungan antarwarga desa (batur sasak).Harmoni Agama dan Adat: Kehadiran tokoh agama (tuan guru/ustaz) bersama tokoh adat menunjukkan bahwa masyarakat Sasak berhasil menyelaraskan syariat Islam (melalui zikir dan doa) dengan ritual warisan leluhur.\r\nTolak Bala: Ritual ini sering digelar pada momen penting, seperti menyambut bulan Safar (penanggalan Hijriah/Sasak), kelahiran anak, atau setelah sembuh dari penyakit, dengan harapan dijauhkan dari marabahaya.', '1787200110_7f11416560.mp4', '2026-08-20 04:28:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `profil_desa`
--

CREATE TABLE `profil_desa` (
  `id` int(11) NOT NULL DEFAULT 1,
  `logo_desa` varchar(255) DEFAULT NULL,
  `visi` text DEFAULT NULL,
  `misi` text DEFAULT NULL,
  `sejarah` text DEFAULT NULL,
  `tentang_desa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `profil_desa`
--

INSERT INTO `profil_desa` (`id`, `logo_desa`, `visi`, `misi`, `sejarah`, `tentang_desa`) VALUES
(1, '1787172051_f16fbbc438.png', 'Tulis Visi di sini...', 'Tulis Misi di sini...', 'Tulis Sejarah di sini...', 'Tulis Tentang Desa di sini...');

-- --------------------------------------------------------

--
-- Struktur dari tabel `profil_konten`
--

CREATE TABLE `profil_konten` (
  `id` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `konten` longtext NOT NULL,
  `icon` varchar(50) DEFAULT 'fa-file-lines',
  `gambar` varchar(255) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `is_deletable` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `profil_konten`
--

INSERT INTO `profil_konten` (`id`, `judul`, `konten`, `icon`, `gambar`, `urutan`, `is_deletable`) VALUES
(1, 'Visi Pemerintahan', 'Mewujudkan Desa Serage yang mandiri, inovatif, dan sejahtera.', 'fa-eye', NULL, 1, 0),
(2, 'Misi Pemerintahan', '<ul><li>Meningkatkan kualitas pelayanan.</li><li>Mendorong ekonomi warga.</li></ul>', 'fa-bullseye', NULL, 2, 0),
(3, 'Sejarah Desa', '<div>Sejarah Desa SERAGE ( Satu Raga)</div><div>Serage Terbentuk Berawal dari musyawarah para tokoh - tokoh yang ada di desa serage.</div><div>Para tokoh membutuhkan perjuangan baik itu, beban pikiran, tenaga yang sangat dramatis bahkan sampai membuat pertumpahan darah. Sehingga terbentuknya Desa Segare ini.</div><div>Setelah terbentuknya desa serage masyarakat sangatlah antusias dan saling bahu membahu untuk menyumbangkan tenaga dan material berupa bahan bangunan.</div>', 'fa-clock-rotate-left', NULL, 3, 0),
(6, 'potensi', 'ppp', 'fa-file-lines', '', 4, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `struktur_organisasi`
--

CREATE TABLE `struktur_organisasi` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `jabatan` varchar(100) NOT NULL,
  `nama_pejabat` varchar(100) NOT NULL,
  `foto_pejabat` varchar(255) DEFAULT NULL,
  `kategori_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `struktur_organisasi`
--

INSERT INTO `struktur_organisasi` (`id`, `parent_id`, `jabatan`, `nama_pejabat`, `foto_pejabat`, `kategori_id`) VALUES
(8, NULL, 'ketua bpd', 'pak haris', NULL, 2),
(9, NULL, 'kepala desa', 'Herman yadi S,adm.', '1787169754_b7ed4a05ff.jpeg', 1),
(10, 9, 'sekretaris desa', 'Ahmad Turmuzi.', NULL, 1),
(11, 9, 'kasi pemerintahan ', 'muhammad junaedi', NULL, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `template_surat`
--

CREATE TABLE `template_surat` (
  `id` int(11) NOT NULL,
  `kode_surat` varchar(50) NOT NULL,
  `nama_surat` varchar(150) NOT NULL,
  `header_surat` text NOT NULL,
  `isi_template` longtext NOT NULL,
  `variabel_input` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variabel_input`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi_surat`
--

CREATE TABLE `transaksi_surat` (
  `id` bigint(20) NOT NULL,
  `nomor_surat` varchar(100) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `penduduk_id` bigint(20) DEFAULT NULL,
  `data_dinamis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_dinamis`)),
  `tgl_terbit` date NOT NULL,
  `file_pdf_path` varchar(255) NOT NULL,
  `petugas_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_admin` varchar(100) NOT NULL,
  `role` enum('Super Admin','Operator') DEFAULT 'Operator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_admin`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$YmPJKLq.MgTdzY7iN3CWxOi1lPgZDz0ReAWkI9dhlG826USB4QPby', 'Administrator Desa', 'Super Admin', '2026-08-06 20:09:03');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `arsip_desa`
--
ALTER TABLE `arsip_desa`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kategori_bagan`
--
ALTER TABLE `kategori_bagan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indeks untuk tabel `penduduk`
--
ALTER TABLE `penduduk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD KEY `idx_pencarian` (`nik`,`nama_lengkap`),
  ADD KEY `idx_filter` (`rt`,`rw`,`pekerjaan`);

--
-- Indeks untuk tabel `pengajuan_surat`
--
ALTER TABLE `pengajuan_surat`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `potensi_desa`
--
ALTER TABLE `potensi_desa`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `profil_desa`
--
ALTER TABLE `profil_desa`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `profil_konten`
--
ALTER TABLE `profil_konten`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `struktur_organisasi`
--
ALTER TABLE `struktur_organisasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indeks untuk tabel `template_surat`
--
ALTER TABLE `template_surat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_surat` (`kode_surat`);

--
-- Indeks untuk tabel `transaksi_surat`
--
ALTER TABLE `transaksi_surat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_surat` (`nomor_surat`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `penduduk_id` (`penduduk_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `arsip_desa`
--
ALTER TABLE `arsip_desa`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kategori_bagan`
--
ALTER TABLE `kategori_bagan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `penduduk`
--
ALTER TABLE `penduduk`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `pengajuan_surat`
--
ALTER TABLE `pengajuan_surat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `potensi_desa`
--
ALTER TABLE `potensi_desa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `profil_konten`
--
ALTER TABLE `profil_konten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `struktur_organisasi`
--
ALTER TABLE `struktur_organisasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `template_surat`
--
ALTER TABLE `template_surat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `transaksi_surat`
--
ALTER TABLE `transaksi_surat`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `struktur_organisasi`
--
ALTER TABLE `struktur_organisasi`
  ADD CONSTRAINT `struktur_organisasi_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `struktur_organisasi` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi_surat`
--
ALTER TABLE `transaksi_surat`
  ADD CONSTRAINT `transaksi_surat_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `template_surat` (`id`),
  ADD CONSTRAINT `transaksi_surat_ibfk_2` FOREIGN KEY (`penduduk_id`) REFERENCES `penduduk` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
