<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';
require_once 'includes/admin_header.php';

$pesan = '';

// --- PROSES UPDATE KODE SURAT (MASTER TEMPLATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_kode_surat'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $pesan = "<div class='alert-error'>Sesi tidak valid. Muat ulang halaman.</div>";
    } else {
        $id_template = (int)$_POST['id_template'];
        $kode_baru = $koneksi->real_escape_string(trim($_POST['kode_surat']));
        
        $cek = $koneksi->query("SELECT id FROM template_surat WHERE kode_surat = '$kode_baru' AND id != $id_template");
        if ($cek->num_rows > 0) {
            $pesan = "<div class='alert-error'>Gagal! Kode Surat <b>$kode_baru</b> sudah digunakan.</div>";
        } else {
            $stmt = $koneksi->prepare("UPDATE template_surat SET kode_surat = ? WHERE id = ?");
            $stmt->bind_param("si", $kode_baru, $id_template);
            if ($stmt->execute()) {
                catat_log($koneksi, $_SESSION['admin_id'], 'Edit Kode Surat', "Mengubah kode surat master menjadi: $kode_baru");
                $pesan = "<div class='alert-success'>Kode Surat master berhasil diperbarui!</div>";
            } else {
                $pesan = "<div class='alert-error'>Gagal menyimpan pembaruan.</div>";
            }
            $stmt->close();
        }
    }
}

// --- PROSES UPDATE NOMOR SURAT (RIWAYAT TERBIT) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_nomor_surat'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $pesan = "<div class='alert-error'>Sesi tidak valid. Muat ulang halaman.</div>";
    } else {
        $id_transaksi = (int)$_POST['id_transaksi'];
        $nomor_baru = $koneksi->real_escape_string(trim($_POST['nomor_surat']));
        
        $cek = $koneksi->query("SELECT id FROM transaksi_surat WHERE nomor_surat = '$nomor_baru' AND id != $id_transaksi");
        if ($cek->num_rows > 0) {
            $pesan = "<div class='alert-error'>Gagal! Nomor Surat <b>$nomor_baru</b> sudah tercatat di arsip lain.</div>";
        } else {
            $stmt = $koneksi->prepare("UPDATE transaksi_surat SET nomor_surat = ? WHERE id = ?");
            $stmt->bind_param("si", $nomor_baru, $id_transaksi);
            if ($stmt->execute()) {
                catat_log($koneksi, $_SESSION['admin_id'], 'Edit Nomor Surat', "Merevisi nomor surat keluar menjadi: $nomor_baru");
                $pesan = "<div class='alert-success'>Nomor Surat fisik berhasil disinkronisasi!</div>";
            } else {
                $pesan = "<div class='alert-error'>Gagal menyimpan pembaruan.</div>";
            }
            $stmt->close();
        }
    }
}

if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../login.php"); exit; }

$query_riwayat = "SELECT t.id, t.nomor_surat, t.tgl_terbit, t.data_dinamis, p.nik, p.nama_lengkap 
                  FROM transaksi_surat t 
                  LEFT JOIN penduduk p ON t.penduduk_id = p.id 
                  ORDER BY t.tgl_terbit DESC, t.id DESC";
$result_riwayat = $koneksi->query($query_riwayat);

// Simpan data template ke array agar bisa digunakan di Form Buat Surat & Tabel Master
$query_template = "SELECT * FROM template_surat ORDER BY nama_surat ASC";
$result_template = $koneksi->query($query_template);
$templates = [];
if ($result_template) {
    while ($t = $result_template->fetch_assoc()) {
        $templates[] = $t;
    }
}
?>

<style>
    /* Styling Navigasi Tab */
    .tab-container { display: flex; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; overflow-x: auto; }
    .tab-btn { padding: 12px 24px; background: none; border: none; cursor: pointer; font-weight: 600; font-size: 15px; color: #64748b; transition: 0.3s; border-bottom: 3px solid transparent; margin-bottom: -2px; white-space: nowrap; }
    .tab-btn:hover, .tab-btn.active { color: #1a6f76; border-bottom-color: #1a6f76; }
    .tab-content { display: none; animation: fadeIn 0.4s; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    
    .table-surat { width: 100%; border-collapse: collapse; text-align: left; }
    .table-surat th { background: #f8fafc; padding: 14px 12px; border-bottom: 2px solid #e2e8f0; color: #334155; font-size: 13.5px; }
    .table-surat td { padding: 14px 12px; border-bottom: 1px solid #e2e8f0; color: #475569; font-size: 13.5px; vertical-align: middle; }
    .col-keperluan { max-width: 350px; word-break: break-word; line-height: 1.4; }

    /* POPUP MODAL STYLING OPTIMAL */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); display: none; align-items: center; justify-content: center; z-index: 9999; opacity: 0; transition: opacity 0.3s ease; }
    .modal-overlay.show { display: flex; opacity: 1; }
    .modal-box { background: #fff; width: 96%; max-width: 1350px; height: 92vh; border-radius: 14px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); transform: scale(0.97); transition: transform 0.3s ease; display: flex; flex-direction: column; overflow: hidden; }
    .modal-overlay.show .modal-box { transform: scale(1); }
    
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding: 18px 30px; background: #f8fafc; }
    .btn-close { background: #fee2e2; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 16px; color: #ef4444; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
    .btn-close:hover { background: #ef4444; color: #fff; transform: scale(1.05); }
    
    .modal-body { display: grid; grid-template-columns: 420px 1fr; height: calc(100% - 73px); overflow: hidden; }
    .form-section { padding: 30px; overflow-y: auto; background: #ffffff; border-right: 1px solid #e2e8f0; }
    .form-section::-webkit-scrollbar { width: 6px; }
    .form-section::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    .form-control { width: 100%; padding: 11px 14px; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box; font-family: 'Poppins', sans-serif; transition: 0.3s; font-size: 14px; background: #f8fafc; }
    .form-control:focus { outline: none; border-color: #1a6f76; background: #fff; box-shadow: 0 0 0 3px rgba(26, 111, 118, 0.15); }
    .form-group { margin-bottom: 20px; }
    .form-group label { font-weight: 600; color: #334155; display: block; margin-bottom: 6px; font-size: 13.5px; }

    .preview-wrapper { position: relative; background: #475569; display: flex; align-items: center; justify-content: center; flex-direction: column; width: 100%; height: 100%; overflow: hidden; }
    .preview-iframe { width: 100%; height: 100%; border: none; background: transparent; transition: opacity 0.3s ease; }
    .preview-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(71, 85, 105, 0.6); backdrop-filter: blur(3px); display: none; align-items: center; justify-content: center; z-index: 10; color: white; font-size: 16px; font-weight: bold; flex-direction: column; gap: 12px; }
    .mode-manual-box { background: #f8fafc; padding: 18px; border-radius: 8px; border: 1px dashed #f59e0b; display: none; margin-bottom: 20px; }

    /* Layout untuk Modal Kecil (Edit Manual) */
    .modal-body-single { display: block; overflow-y: auto; padding: 25px; height: auto; }

    @media (max-width: 1024px) {
        .modal-body { grid-template-columns: 1fr; overflow-y: auto; height: calc(100% - 73px); }
        .form-section { border-right: none; border-bottom: 2px solid #e2e8f0; }
        .preview-wrapper { min-height: 650px; }
    }
</style>

<div style="padding: 25px; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin: 20px;">
    <h2 style="color: #1a6f76; margin-top: 0;">Manajemen Surat Administrasi</h2>
    <?= $pesan ?>
    
    <div class="tab-container">
        <button class="tab-btn active" onclick="bukaTab(event, 'BuatSurat')"><i class="fa-solid fa-pen-to-square"></i> Buat Surat Baru</button>
        <button class="tab-btn" onclick="bukaTab(event, 'RiwayatSurat')"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Surat Keluar</button>
        <button class="tab-btn" onclick="bukaTab(event, 'MasterTemplate')"><i class="fa-solid fa-code"></i> Master Template Surat</button>
    </div>

    <!-- TAB 1: BUAT SURAT -->
    <div id="BuatSurat" class="tab-content active">
        <div style="text-align: center; padding: 50px 20px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px;">
            <i class="fa-solid fa-envelope-open-text" style="font-size: 60px; color: #1a6f76; margin-bottom: 15px;"></i>
            <h3 style="color: #334155; margin-bottom: 10px;">Pusat Pembuatan Dokumen Desa</h3>
            <p style="color: #64748b; margin-bottom: 25px; max-width: 500px; margin-left: auto; margin-right: auto;">Gunakan lembar kerja interaktif untuk menerbitkan surat administrasi warga dengan fitur pratinjau waktu nyata (real-time).</p>
            <button onclick="bukaModal()" style="padding: 14px 32px; background: #1a6f76; color: white; border: none; border-radius: 8px; font-weight: bold; font-size: 15px; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 6px rgba(26, 111, 118, 0.2);"><i class="fa-solid fa-keyboard"></i> Buka Lembar Kerja Surat</button>
        </div>
    </div>

    <!-- TAB 2: RIWAYAT SURAT -->
    <div id="RiwayatSurat" class="tab-content">
        <div style="overflow-x: auto; background: #fff; border-radius: 8px;">
            <table class="table-surat">
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th style="width: 15%;">Tanggal</th>
                        <th style="width: 20%;">Nomor Surat</th>
                        <th style="width: 18%;">Identitas Pemohon</th>
                        <th style="width: 12%;">Jenis</th>
                        <th style="width: 15%;" class="col-keperluan">Keperluan</th>
                        <th style="width: 15%; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_riwayat && $result_riwayat->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $result_riwayat->fetch_assoc()): 
                            $data_surat = json_decode($row['data_dinamis'], true); 
                            $is_manual = isset($data_surat['is_manual']) && $data_surat['is_manual'];
                            $tampil_nik = $is_manual ? ($data_surat['data_warga']['nik'] ?? '-') : $row['nik'];
                            $tampil_nama = $is_manual ? ($data_surat['data_warga']['nama_lengkap'] ?? 'Data Manual') : $row['nama_lengkap'];
                            $clean_keperluan = strip_tags($data_surat['keperluan'] ?? '-');
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= format_tanggal_indo($row['tgl_terbit']) ?></td>
                            <td style="font-weight: bold; color: #1a6f76;"><?= htmlspecialchars($row['nomor_surat']) ?></td>
                            <td>
                                <div style="font-size: 11.5px; color: #64748b;"><?= htmlspecialchars($tampil_nik) ?> <?= $is_manual ? '<span style="color:#f59e0b; font-weight:bold;">(Manual)</span>' : '' ?></div>
                                <strong style="color: #0f172a;"><?= htmlspecialchars($tampil_nama) ?></strong>
                            </td>
                            <td>
                                <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; display: inline-block;">
                                    <?= htmlspecialchars($data_surat['jenis'] ?? '-') ?>
                                </span>
                            </td>
                            <td class="col-keperluan"><?= htmlspecialchars($clean_keperluan) ?></td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="lihat_surat.php?id=<?= $row['id'] ?>" target="_blank" style="padding: 6px 10px; background: #1a6f76; color: white; border-radius: 4px; text-decoration: none; font-size: 12.5px; font-weight: 500; display: inline-block; margin-right: 4px;" title="Lihat/Cetak"><i class="fa-solid fa-eye"></i></a>
                                
                                <button onclick="bukaEditNomor(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nomor_surat']) ?>')" style="padding: 6px 10px; background: #f59e0b; color: white; border: none; border-radius: 4px; font-size: 12.5px; font-weight: 500; cursor: pointer; margin-right: 4px;" title="Revisi Nomor"><i class="fa-solid fa-hashtag"></i></button>

                                <a href="hapus_surat.php?id=<?= $row['id'] ?>" onclick="return confirm('Hapus permanen arsip ini?');" style="padding: 6px 10px; background: #ef4444; color: white; border-radius: 4px; text-decoration: none; font-size: 12.5px; font-weight: 500; display: inline-block;" title="Hapus"><i class="fa-solid fa-trash-can"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="padding: 40px; text-align: center; color: #94a3b8;">Belum ada riwayat surat keluar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: MASTER TEMPLATE -->
    <div id="MasterTemplate" class="tab-content">
        <div style="overflow-x: auto; background: #fff; border-radius: 8px;">
            <table class="table-surat">
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th style="width: 40%;">Jenis Dokumen / Nama Surat</th>
                        <th style="width: 40%;">Format Kode Surat (Buku Register)</th>
                        <th style="width: 15%; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($templates as $tpl): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($tpl['nama_surat']) ?></td>
                        <td><span style="background: #f1f5f9; padding: 6px 12px; border-radius: 4px; font-family: monospace; font-size: 14px; border: 1px solid #cbd5e1; color: #0f172a;"><?= htmlspecialchars($tpl['kode_surat']) ?></span></td>
                        <td style="text-align: center;">
                            <button onclick="bukaEditKode(<?= $tpl['id'] ?>, '<?= htmlspecialchars($tpl['kode_surat']) ?>')" style="padding: 8px 14px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;" title="Edit Kode Master"><i class="fa-solid fa-pen"></i> Sesuaikan</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= POPUP MODAL BUAT SURAT ================= -->
<div id="modalForm" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="margin: 0; color: #0f172a; font-size: 18px;"><i class="fa-solid fa-file-pen" style="color: #1a6f76;"></i> Lembar Kerja Persuratan Desa</h3>
            <button class="btn-close" onclick="tutupModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div class="modal-body">
            <div class="form-section">
                <form action="cetak_surat.php" method="POST" target="_blank" id="formSurat">
                    <div class="form-group">
                        <label>Jenis Surat Administrasi</label>
                        <select name="jenis_surat" id="inputJenis" class="form-control" required>
                            <option value="">-- Pilih Jenis Surat --</option>
                            <?php foreach ($templates as $tpl): ?>
                                <option value="<?= htmlspecialchars($tpl['header_surat']) ?>"><?= htmlspecialchars($tpl['nama_surat']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- KOLOM INPUT NOMOR SURAT MANUAL -->
                    <div class="form-group">
                        <label>Nomor Urut Surat</label>
                        <div style="display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden;">
                            <span style="padding: 11px 14px; background: #e2e8f0; color: #475569; font-weight: 600; font-size: 13px; border-right: 1px solid #cbd5e1;">[KODE]/</span>
                            <input type="text" name="nomor_urut" id="inputNomorUrut" style="border: none; border-radius: 0; flex: 1; padding: 11px 14px; outline: none; font-weight: bold; color: #1a6f76;" required placeholder="001" autocomplete="off">
                            <span style="padding: 11px 14px; background: #e2e8f0; color: #475569; font-weight: 600; font-size: 13px; border-left: 1px solid #cbd5e1;">/DS-SRG/[BLN]/[THN]</span>
                        </div>
                        <small style="color: #64748b; font-size: 11.5px; margin-top: 5px; display: block;">*Isi angka urutan saja (cth: 001). Kode dan tanggal akan di-generate otomatis.</small>
                    </div>
                    <div class="form-group">
                        <label>NIK Pemohon</label>
                        <input type="text" name="nik" id="inputNik" maxlength="16" placeholder="Ketik 16 Digit NIK..." class="form-control" required autocomplete="off">
                        <div id="hasilPencarian" style="margin-top: 6px; font-size: 12.5px;"></div>
                    </div>

                    <div class="form-group" style="background: #fffbeb; padding: 12px 14px; border-radius: 8px; border: 1px solid #fde68a;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #b45309; font-weight: 600; font-size: 13px; margin:0;">
                            <input type="checkbox" id="modeManual" name="mode_manual" value="1" style="width: 16px; height: 16px; accent-color: #f59e0b;">
                            Gunakan Data Manual (Warga Baru/Luar)
                        </label>
                    </div>

                    <!-- FORM MANUAL HIDDEN -->
                    <div id="formManual" class="mode-manual-box">
                        <!-- TAMBAHAN: Input NIK Manual -->
                        <div class="form-group" style="margin-bottom:10px;">
                            <input type="text" name="nik_manual" id="mNik" placeholder="Nomor Induk Kependudukan (NIK)" class="form-control manual-input" maxlength="16" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        
                        <div class="form-group" style="margin-bottom:10px;">
                            <input type="text" name="nama_manual" id="mNama" placeholder="Nama Lengkap & Gelar" class="form-control manual-input">
                        </div>
                        <div style="display:flex; gap:10px; margin-bottom:10px;">
                            <div style="flex:1"><input type="text" name="tempat_manual" id="mTempat" placeholder="Tempat Lahir" class="form-control manual-input"></div>
                            <div style="flex:1"><input type="date" name="tgl_manual" id="mTgl" class="form-control manual-input"></div>
                        </div>
                        <div class="form-group" style="margin-bottom:10px;">
                            <select name="jk_manual" id="mJk" class="form-control manual-input">
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:10px;"><input type="text" name="pekerjaan_manual" id="mPekerjaan" placeholder="Pekerjaan" class="form-control manual-input"></div>
                        <div class="form-group" style="margin-bottom:10px;"><input type="text" name="dusun_manual" id="mDusun" placeholder="Nama Dusun" class="form-control manual-input"></div>
                        <div style="display:flex; gap:10px; margin-bottom:0;">
                            <div style="flex:1"><input type="text" name="rt_manual" id="mRt" placeholder="RT (001)" class="form-control manual-input"></div>
                            <div style="flex:1"><input type="text" name="rw_manual" id="mRw" placeholder="RW (002)" class="form-control manual-input"></div>
                        </div>
                    </div>

                    <div id="dynamicInputArea" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                        <p style="margin:0; font-size:12.5px; color:#64748b;"><i class="fa-solid fa-circle-info"></i> Pilih Jenis Surat untuk menampilkan isian spesifik.</p>
                    </div>

                    <div style="display: flex; gap: 10px; flex-direction: column; padding-top: 5px;">
                        <button type="submit" id="btnProses" disabled style="width: 100%; padding: 13px; background: #cbd5e1; color: #475569; border: none; border-radius: 8px; cursor: not-allowed; font-weight: bold; font-size: 14.5px; transition: 0.3s;">Menunggu Validasi...</button>
                        <button type="button" onclick="tutupModal()" style="width: 100%; padding: 11px; background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13.5px;">Batal & Tutup</button>
                    </div>
                </form>
            </div>

            <div class="preview-wrapper" id="boxPreview">
                <div id="previewPlaceholder" style="color: #cbd5e1; text-align: center; padding: 20px;">
                    <i class="fa-solid fa-eye" style="font-size: 45px; margin-bottom: 12px; color: #94a3b8;"></i>
                    <p style="margin:0; font-size: 15px; color: #cbd5e1;">Pilih Jenis Surat & Masukkan NIK<br>untuk melihat Live Preview.</p>
                </div>
                <div class="preview-overlay" id="previewOverlay">
                    <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 35px;"></i><span style="font-size: 14px;">Memperbarui Pratinjau...</span>
                </div>
                <iframe id="framePreview" class="preview-iframe" style="display: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- ================= POPUP MODAL KODE & NOMOR SURAT ================= -->
<div class="modal-overlay" id="modalEditKode">
    <div class="modal-box" style="max-width: 450px; height: auto;">
        <div class="modal-header">
            <h3 style="margin:0; font-size:16px;"><i class="fa-solid fa-code"></i> Revisi Kode Surat</h3>
            <button type="button" class="btn-close" onclick="tutupModalId('modalEditKode')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body-single">
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="id_template" id="form_id_template">
                
                <div class="form-group">
                    <label class="form-label">Format Kode Surat</label>
                    <input type="text" name="kode_surat" id="form_kode_surat" class="form-control" placeholder="Cth: 145/Pem-Des" required autocomplete="off">
                    <small style="color: #64748b; font-size: 11.5px; margin-top: 6px; display: block;">*Pembaruan ini akan memengaruhi format penomoran otomatis untuk surat yang akan dibuat selanjutnya.</small>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" style="background:#cbd5e0; border:none; border-radius:6px; font-weight:600; color:#1e293b; flex:1; cursor:pointer;" onclick="tutupModalId('modalEditKode')">Batal</button>
                    <button type="submit" name="update_kode_surat" style="background:#1a6f76; border:none; border-radius:6px; font-weight:600; color:#fff; padding:12px; flex:2; cursor:pointer;"><i class="fa-solid fa-save"></i> Simpan Kode</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalEditNomor">
    <div class="modal-box" style="max-width: 450px; height: auto;">
        <div class="modal-header" style="background: #f59e0b;">
            <h3 style="margin:0; font-size:16px; color:white;"><i class="fa-solid fa-hashtag"></i> Sinkronisasi Nomor Surat</h3>
            <button type="button" class="btn-close" style="background:rgba(255,255,255,0.2); color:white;" onclick="tutupModalId('modalEditNomor')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body-single">
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="id_transaksi" id="form_id_transaksi">
                
                <div class="form-group">
                    <label class="form-label">Nomor Surat Final</label>
                    <input type="text" name="nomor_surat" id="form_nomor_surat" class="form-control" placeholder="Cth: 145/001/Pem-Des/VIII/2026" required autocomplete="off">
                    <small style="color: #ef4444; font-size: 11.5px; margin-top: 6px; display: block; font-weight:500;">*Peringatan: Pastikan nomor ini sama persis dengan yang tercatat di Buku Register Desa.</small>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" style="background:#cbd5e0; border:none; border-radius:6px; font-weight:600; color:#1e293b; flex:1; cursor:pointer;" onclick="tutupModalId('modalEditNomor')">Batal</button>
                    <button type="submit" name="update_nomor_surat" style="background:#f59e0b; border:none; border-radius:6px; font-weight:600; color:#fff; padding:12px; flex:2; cursor:pointer;"><i class="fa-solid fa-check-double"></i> Terapkan Nomor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Logic Navigasi Tab
function bukaTab(evt, namaTab) {
    document.querySelectorAll(".tab-content").forEach(el => el.classList.remove("active"));
    document.querySelectorAll(".tab-btn").forEach(el => el.classList.remove("active"));
    document.getElementById(namaTab).classList.add("active");
    evt.currentTarget.classList.add("active");
}

// Logic Modal Buat Surat
const modal = document.getElementById('modalForm');
function bukaModal() { modal.classList.add('show'); updateLivePreview(); }
function tutupModal() { modal.classList.remove('show'); }

// Logic Modal Kode & Nomor Manual
function tutupModalId(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function bukaEditKode(id, kodeLama) {
    document.getElementById('form_id_template').value = id;
    document.getElementById('form_kode_surat').value = kodeLama;
    document.getElementById('modalEditKode').classList.add('show');
}

function bukaEditNomor(id, nomorLama) {
    document.getElementById('form_id_transaksi').value = id;
    document.getElementById('form_nomor_surat').value = nomorLama;
    document.getElementById('modalEditNomor').classList.add('show');
}

// ================= LOGIKA AUTO-FILL & PREVIEW =================
let timeoutId;
const inputNik = document.getElementById('inputNik');
const inputJenis = document.getElementById('inputJenis');
const modeManual = document.getElementById('modeManual');
const formManual = document.getElementById('formManual');
const manualInputs = document.querySelectorAll('.manual-input');
const btnProses = document.getElementById('btnProses');
const hasilDiv = document.getElementById('hasilPencarian');
const framePreview = document.getElementById('framePreview');
const previewPlaceholder = document.getElementById('previewPlaceholder');
const previewOverlay = document.getElementById('previewOverlay');
// Tambahkan deklarasi ini di bawah deklarasi const lainnya
const inputNomorUrut = document.getElementById('inputNomorUrut');

function setButtonState(isValid) {
    if(isValid) {
        btnProses.disabled = false; btnProses.style.background = '#1a6f76'; btnProses.style.color = '#ffffff'; btnProses.innerHTML = '<i class="fa-solid fa-print"></i> Simpan & Cetak Dokumen'; btnProses.style.cursor = 'pointer';
    } else {
        btnProses.disabled = true; btnProses.style.background = '#cbd5e1'; btnProses.style.color = '#475569'; btnProses.innerHTML = 'Data Belum Lengkap'; btnProses.style.cursor = 'not-allowed';
    }
}

modeManual.addEventListener('change', function() {
    if (this.checked) {
        formManual.style.display = 'block';
        hasilDiv.innerHTML = '<span style="color:#f59e0b;"><i class="fa-solid fa-pen"></i> Mode Manual Aktif</span>';
        setButtonState(true);
    } else {
        formManual.style.display = 'none';
        inputNik.dispatchEvent(new Event('input'));
    }
    updateLivePreview();
});

function updateLivePreview() {
    let nik = inputNik.value;
    let jenis = inputJenis.value;
    let isManual = modeManual.checked;

    if ((nik.length >= 15 || isManual) && jenis !== "") {
        if (framePreview.style.display === 'block') {
            previewOverlay.style.display = 'flex';
            framePreview.style.opacity = '0.5';
        } else {
            previewPlaceholder.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="font-size:45px; color:#cbd5e1;"></i><br><br>Membangun pratinjau...';
        }

        let formData = new FormData(document.getElementById('formSurat'));
        formData.append('is_preview', 'true');

        fetch('cetak_surat.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(html => {
            previewPlaceholder.style.display = 'none';
            previewOverlay.style.display = 'none';
            framePreview.style.display = 'block';
            framePreview.style.opacity = '1';
            framePreview.srcdoc = html;
        });
    } else {
        framePreview.style.display = 'none';
        previewPlaceholder.style.display = 'block';
    }
}

inputNik.addEventListener('input', function() {
    clearTimeout(timeoutId);
    if (modeManual.checked) { updateLivePreview(); return; }

    let nik = this.value;
    if (nik.length >= 15) {
        hasilDiv.innerHTML = '<span style="color:#f59e0b;"><i class="fa-solid fa-spinner fa-spin"></i> Memverifikasi...</span>';
        timeoutId = setTimeout(() => {
            fetch(`../ajax_get_penduduk.php?nik=${nik}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        hasilDiv.innerHTML = `<span style="color:#ef4444; font-weight:600;"><i class="fa-solid fa-circle-xmark"></i> ${data.error}</span>`;
                        setButtonState(false);
                    } else {
                        hasilDiv.innerHTML = `<span style="color:#10b981;"><i class="fa-solid fa-circle-check"></i> <strong style="color:#1a6f76;">${data.nama_lengkap}</strong></span>`;
                        setButtonState(true);
                    }
                    updateLivePreview();
                });
        }, 300);
    } else {
        hasilDiv.innerHTML = '';
        setButtonState(false);
        updateLivePreview();
    }
});

// Tambahkan event listener ini agar Live Preview ter-update saat nomor surat diketik
inputNomorUrut.addEventListener('input', function() {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(updateLivePreview, 300);
});

const dynamicInputArea = document.getElementById('dynamicInputArea');
const configSurat = {
    'SKU': `
        <div class="form-group"><label>Nama Usaha</label><input type="text" name="dyn_nama_usaha" class="form-control dyn-inp" required placeholder="Contoh: Kios Berkah"></div>
        <div class="form-group"><label>Bidang Usaha</label><input type="text" name="dyn_jenis_usaha" class="form-control dyn-inp" required placeholder="Contoh: Sembako"></div>
        <div class="form-group" style="margin-bottom:0;"><label>Tujuan Pembuatan</label><input type="text" name="keperluan" class="form-control dyn-inp" required placeholder="Contoh: KUR BRI"></div>`,
    'Kelahiran': `
        <div class="form-group"><label>Nama Anak</label><input type="text" name="dyn_nama_anak" class="form-control dyn-inp" required></div>
        <div style="display:flex; gap:10px;">
            <div class="form-group" style="flex:1"><label>Tempat Lahir</label><input type="text" name="dyn_tempat_lahir" class="form-control dyn-inp" required></div>
            <div class="form-group" style="flex:1"><label>Tanggal Lahir</label><input type="date" name="dyn_tgl_lahir" class="form-control dyn-inp" required></div>
        </div>
        <div class="form-group" style="margin-bottom:0;"><label>Anak Ke-</label><input type="number" name="dyn_anak_ke" class="form-control dyn-inp" required></div>`,
    'Kematian': `
        <div class="form-group"><label>Tanggal Meninggal</label><input type="date" name="dyn_tgl_meninggal" class="form-control dyn-inp" required></div>
        <div class="form-group"><label>Tempat Meninggal</label><input type="text" name="dyn_tempat_meninggal" class="form-control dyn-inp" required placeholder="Contoh: RSUD Praya"></div>
        <div class="form-group" style="margin-bottom:0;"><label>Penyebab Kematian</label><input type="text" name="dyn_penyebab" class="form-control dyn-inp" required placeholder="Contoh: Sakit"></div>`,
    'Pindah': `
        <div class="form-group"><label>Alamat Tujuan Pindah Lengkap</label><textarea name="dyn_alamat_tujuan" class="form-control dyn-inp" required placeholder="Desa, Kecamatan, Kabupaten" rows="2"></textarea></div>
        <div class="form-group" style="margin-bottom:0;"><label>Alasan Pindah</label><input type="text" name="dyn_alasan" class="form-control dyn-inp" required placeholder="Contoh: Mengikuti Keluarga"></div>`,
    'Ahli Waris': `
        <div class="form-group"><label>Nama Almarhum/ah</label><input type="text" name="dyn_nama_alm" class="form-control dyn-inp" required placeholder="Contoh: Bpk. Fulan"></div>
        <div class="form-group" style="margin-bottom:0;"><label>Tujuan Surat</label><input type="text" name="keperluan" class="form-control dyn-inp" required placeholder="Contoh: Klaim Asuransi"></div>`,
    'Tanah': `
        <div class="form-group"><label>Lokasi / Letak Tanah</label><input type="text" name="dyn_letak_tanah" class="form-control dyn-inp" required placeholder="Contoh: Dusun Belenje"></div>
        <div class="form-group"><label>Luas Tanah</label><input type="text" name="dyn_luas_tanah" class="form-control dyn-inp" required placeholder="Contoh: ± 500 m2"></div>
        <div class="form-group" style="margin-bottom:0;"><label>Batas Tanah (U, S, T, B)</label><input type="text" name="dyn_batas" class="form-control dyn-inp" required placeholder="U: A, S: B, T: C, B: D"></div>`,
    'Kehilangan': `
        <div class="form-group" style="margin-bottom:0;"><label>Dokumen / Barang yang Hilang</label><input type="text" name="dyn_barang" class="form-control dyn-inp" required placeholder="Contoh: KTP & Kartu Keluarga"></div>`,
    'Beda Nama': `
        <div class="form-group"><label>Kesalahan Pada Dokumen Apa?</label><input type="text" name="dyn_dokumen" class="form-control dyn-inp" required placeholder="Contoh: Ijazah SMA"></div>
        <div class="form-group" style="margin-bottom:0;"><label>Tertulis Nama Siapa?</label><input type="text" name="dyn_nama_salah" class="form-control dyn-inp" required placeholder="Contoh: MUHAMMAD FULAN"></div>`,
    'Pengantar Nikah': `
        <div class="form-group"><label>Nama Calon Pasangan</label><input type="text" name="dyn_pasangan" class="form-control dyn-inp" required placeholder="Contoh: Fulanah binti Fulan"></div>
        <div class="form-group" style="margin-bottom:0;"><label>Asal Calon Pasangan (Desa/Kec)</label><input type="text" name="dyn_asal_pasangan" class="form-control dyn-inp" required placeholder="Contoh: Desa Suralaga"></div>`,
    'Penghasilan': `
        <div class="form-group"><label>Rata-rata Penghasilan Per Bulan</label><input type="text" name="dyn_nominal" class="form-control dyn-inp" required placeholder="Contoh: Rp 1.500.000"></div>
        <div class="form-group" style="margin-bottom:0;"><label>Tujuan Surat</label><input type="text" name="keperluan" class="form-control dyn-inp" required placeholder="Contoh: Beasiswa KIP"></div>`,
    'DEFAULT': `
        <div class="form-group" style="margin-bottom:0;"><label>Tujuan / Keperluan Surat</label><input type="text" name="keperluan" class="form-control dyn-inp" required placeholder="Contoh: Persyaratan Administrasi"></div>`
};

inputJenis.addEventListener('change', function() {
    let htmlInput = configSurat[this.value] || (this.value ? configSurat['DEFAULT'] : '<p style="margin:0; font-size:12.5px; color:#64748b;">Pilih Jenis Surat untuk menampilkan isian spesifik.</p>');
    dynamicInputArea.innerHTML = htmlInput;
    
    dynamicInputArea.querySelectorAll('.dyn-inp').forEach(inp => {
        inp.addEventListener('input', () => { clearTimeout(timeoutId); timeoutId = setTimeout(updateLivePreview, 300); });
    });
    
    updateLivePreview();
});

manualInputs.forEach(input => {
    input.addEventListener('input', () => { clearTimeout(timeoutId); timeoutId = setTimeout(updateLivePreview, 300); });
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>