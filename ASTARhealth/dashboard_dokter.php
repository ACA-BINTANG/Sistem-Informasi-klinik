<?php
session_start();
require_once 'koneksi.php';

// =======================
// PROTEKSI ROLE DOKTER
// =======================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Dokter') {
    header("Location: login.php?pesan=Akses Ditolak!");
    exit;
}

$doctor_name = $_SESSION['nama_lengkap'];
$user_id = $_SESSION['id_user'];
$active_page = $_GET['page'] ?? 'antrean';

// Ambil id staff dokter
$qStaff = mysqli_query($conn, "SELECT id_staff FROM staffm WHERE id_user = '$user_id'");
$dStaff = mysqli_fetch_assoc($qStaff);
$id_dokter = $dStaff['id_staff'] ?? '';

// Ambil data obat untuk dropdown resep
$qObat = mysqli_query($conn, "SELECT * FROM obatm WHERE stok_sekarang > 0 ORDER BY nama_obat ASC");
$obat_options = [];
while($row = mysqli_fetch_assoc($qObat)) { $obat_options[] = $row; }

function generateID($prefix) {
    return $prefix . substr(str_shuffle("0123456789"), 0, 3);
}

// FUNGSI NOMOR ANTREAN UNIK (A001 -> A999 -> B000)
function generateUniqueQueue($conn) {
    // Ambil nomor antrean terakhir berdasarkan waktu input terbaru
    $query = mysqli_query($conn, "SELECT no_antrian FROM rekam_medis ORDER BY tgl_kunjungan DESC, waktu_booking DESC LIMIT 1");
    $data = mysqli_fetch_assoc($query);

    if (!$data) {
        return "A001"; // Jika database masih kosong
    }

    $lastNo = $data['no_antrian']; 
    $huruf = substr($lastNo, 0, 1); 
    $angka = (int)substr($lastNo, 1); 

    if ($angka < 999) {
        $angka++;
    } else {
        $angka = 0;
        $huruf++; // A naik jadi B, dst.
    }

    return $huruf . str_pad($angka, 3, "0", STR_PAD_LEFT);
}

// CARA PAKAI SAAT INSERT:
// $no_antrean_baru = generateUniqueQueue($conn);
// mysqli_query($conn, "INSERT INTO rekam_medis (no_antrian, ...) VALUES ('$no_antrean_baru', ...)");

// ==========================================
// LOGIKA CRUD & TRANSAKSI
// ==========================================

// 1. SIMPAN PEMERIKSAAN & RESEP
if (isset($_POST['simpan_pemeriksaan'])) {
    $id_rm    = $_POST['id_rekam_medis']; 
    $id_diag  = $_POST['id_diagnosa'];    
    $keluhan  = mysqli_real_escape_string($conn, $_POST['keluhan']); 
    $hasil    = mysqli_real_escape_string($conn, $_POST['hasil_pemeriksaan']);
    
    date_default_timezone_set('Asia/Jakarta');
    $jam_sekarang = date('H:i:s'); 
    
    // Update Rekam Medis
    $query_update = "UPDATE rekam_medis SET 
        id_diagnosa = '$id_diag', id_staff = '$id_dokter', keluhan = '$keluhan',
        hasil_pemeriksaan = '$hasil', waktu_booking = '$jam_sekarang', status = 'Selesai' 
        WHERE id_rekam_medis = '$id_rm'";
        
    if(mysqli_query($conn, $query_update)) {
        // Simpan Resep jika obat dipilih
        if (!empty($_POST['id_obat']) && !empty($_POST['jumlah_keluar'])) {
            $id_res = generateID("RSP");
            $id_obt = $_POST['id_obat'];
            $qty    = $_POST['jumlah_keluar'];
            $note   = mysqli_real_escape_string($conn, $_POST['catatan_obat']);
            mysqli_query($conn, "INSERT INTO resep_dokter VALUES ('$id_res', '$id_rm', '$id_obt', '$qty', '$note')");
            mysqli_query($conn, "UPDATE obatm SET stok_sekarang = stok_sekarang - $qty WHERE id_obat = '$id_obt'");
        }
        header("Location: dashboard_dokter.php?page=rekam_medis&msg=Pemeriksaan & Resep Berhasil Disimpan");
    } else {
        header("Location: dashboard_dokter.php?page=antrean&err=Gagal Simpan");
    }
    exit;
}

// 2. CRUD DIAGNOSA
if (isset($_POST['add_diagnosa'])) {
    $id = generateID("DX");
    $nm = mysqli_real_escape_string($conn, $_POST['nama_penyakit']);
    $kt = $_POST['kategori']; $tp = $_POST['tipe'];
    mysqli_query($conn, "INSERT INTO diagnosam VALUES ('$id', '$nm', '$kt', '$tp')");
    header("Location: dashboard_dokter.php?page=diagnosa&msg=Diagnosa Berhasil Ditambahkan"); exit;
}

if (isset($_POST['update_diagnosa'])) {
    $id = $_POST['id_diagnosa'];
    $nm = mysqli_real_escape_string($conn, $_POST['nama_penyakit']);
    $kt = $_POST['kategori']; $tp = $_POST['tipe'];
    mysqli_query($conn, "UPDATE diagnosam SET nama_penyakit='$nm', kategori='$kt', tipe='$tp' WHERE id_diagnosa='$id'");
    header("Location: dashboard_dokter.php?page=diagnosa&msg=Diagnosa Berhasil Diperbarui"); exit;
}

if (isset($_GET['del'])) {
    $id = $_GET['del'];
    mysqli_query($conn, "DELETE FROM diagnosam WHERE id_diagnosa = '$id'");
    header("Location: dashboard_dokter.php?page=diagnosa&msg=Data Berhasil Dihapus"); exit;
}

// 3. RUJUKAN
if (isset($_POST['buat_rujukan_langsung'])) {
    $nim = mysqli_real_escape_string($conn, $_POST['nim_nip']);
    $cP = mysqli_query($conn, "SELECT id_pasien FROM pasienm WHERE no_identitas='$nim'");
    if (mysqli_num_rows($cP) > 0) {
        $dP = mysqli_fetch_assoc($cP); $id_p = $dP['id_pasien'];
        $id_r = generateID("RUJ"); $rs = mysqli_real_escape_string($conn, $_POST['tujuan_rs']);
        $als = mysqli_real_escape_string($conn, $_POST['alasan_rujukan']); $tgl = date('Y-m-d');
        mysqli_query($conn, "INSERT INTO rujukan VALUES ('$id_r', '$id_p', '$id_dokter', '$rs', '$als', '$tgl', 'Proses')");
        header("Location: dashboard_dokter.php?page=rujukan&msg=Rujukan Dibuat&last_id=$id_r"); exit;
    } else {
        header("Location: dashboard_dokter.php?page=rujukan&err=Pasien Tidak Ditemukan"); exit;
    }
}

$qdx = mysqli_query($conn, "SELECT * FROM diagnosam ORDER BY nama_penyakit ASC");
$dx_options = [];
while($row = mysqli_fetch_assoc($qdx)) { $dx_options[] = $row; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Medical Dashboard - ASTARhealth</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root { --astar-blue: #0057B8; --astar-soft-blue: #eef4ff; --sidebar-bg: #ffffff; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f7fa; color: #334155; }
    
    .top-header { height: 70px; background: var(--astar-blue); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; color: white; position: fixed; top: 0; width: 100%; z-index: 1001; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    #digitalClock { font-weight: 600; font-size: 14px; background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 50px; }
    
    .sidebar { width: 280px; background: var(--sidebar-bg); height: 100vh; position: fixed; top: 70px; border-right: 1px solid #e2e8f0; z-index: 1000; padding: 20px 0; transition: all 0.3s ease; overflow-y: auto; }
    .main-content { margin-left: 280px; padding: 100px 40px 40px; transition: all 0.3s ease; animation: fadeIn 0.5s ease; }
    
    body.sidebar-toggled .sidebar { left: -280px; }
    body.sidebar-toggled .main-content { margin-left: 0; }
    
    @media (max-width: 768px) {
        .sidebar { left: -280px; }
        .main-content { margin-left: 0; }
        body.sidebar-toggled .sidebar { left: 0; }
    }

    #sidebarToggle { cursor: pointer; font-size: 1.5rem; }
    .nav-group-title { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; padding: 20px 25px 8px; letter-spacing: 1px; }
    .nav-link { padding: 12px 25px; color: #64748b; font-weight: 500; display: flex; align-items: center; transition: 0.2s; text-decoration: none; font-size: 14px; margin: 0 15px; border-radius: 10px; }
    .nav-link i { font-size: 1.2rem; width: 35px; }
    .nav-link:hover { background: var(--astar-soft-blue); color: var(--astar-blue); }
    .nav-link.active { background: var(--astar-blue); color: #fff; box-shadow: 0 4px 12px rgba(0,87,184,0.3); }
    
    .data-container { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; }
    .stat-card { background: white; border-radius: 18px; padding: 25px; display: flex; align-items: center; justify-content: space-between; border-left: 6px solid var(--astar-blue); box-shadow: 0 10px 20px rgba(0,0,0,0.03); transition: 0.3s; }
    .table thead th { background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px; padding: 15px; border: none; }
    
    .animate-pulse { animation: pulse-danger 2s infinite; }
    @keyframes pulse-danger { 0% { transform: scale(1); } 50% { transform: scale(1.01); box-shadow: 0 0 20px rgba(220, 53, 69, 0.2); } 100% { transform: scale(1); } }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    #hasilPencarian { position: absolute; width: 100%; background: white; border: 1px solid #e2e8f0; border-radius: 12px; z-index: 1100; max-height: 200px; overflow-y: auto; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .search-item { padding: 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .search-item:hover { background: #f0f4ff; color: #0057B8; }
  </style>
</head>
<body>

  <!-- HEADER -->
  <header class="top-header">
    <div class="d-flex align-items-center gap-3">
        <div id="sidebarToggle" class="text-white"><i class="bi bi-list"></i></div>
        <img src="assets/img/logoA.png" style="max-height: 70px; filter: brightness(0) invert(1);">
        <div id="digitalClock" class="d-none d-md-block text-white fw-bold"></div>
    </div>
    <div class="dropdown">
        <a href="#" data-bs-toggle="dropdown" class="text-white text-decoration-none d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block lh-1">
                <div class="fw-bold mb-1">dr. <?= $doctor_name ?></div>
                <small style="opacity: 0.8; font-size: 11px;">ID Staff: <?= $id_dokter ?></small>
            </div>
            <i class="bi bi-person-circle fs-2 text-white"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-2" style="border-radius: 12px;">
            <li><a class="dropdown-item rounded-2 text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalLogout"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
        </ul>
    </div>
  </header>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="nav-group-title">Layanan Medis</div>
    <nav class="nav flex-column">
      <a class="nav-link <?= ($active_page == 'antrean') ? 'active' : '' ?>" href="?page=antrean"><i class="bi bi-people-fill"></i> Antrean Pasien</a>
      <a class="nav-link <?= ($active_page == 'rekam_medis') ? 'active' : '' ?>" href="?page=rekam_medis"><i class="bi bi-file-earmark-medical-fill"></i> Rekam Medis</a>
      <a class="nav-link <?= ($active_page == 'rujukan') ? 'active' : '' ?>" href="?page=rujukan"><i class="bi bi-file-medical-fill"></i> Rujukan Mandiri</a>
      <a class="nav-link <?= ($active_page == 'obat') ? 'active' : '' ?>" href="?page=obat"><i class="bi bi-capsule-pill"></i> Stok Obat Klinik</a>
    </nav>
    <div class="nav-group-title">Referensi Kampus</div>
    <nav class="nav flex-column">
      <a class="nav-link <?= ($active_page == 'pasien') ? 'active' : '' ?>" href="?page=pasien"><i class="bi bi-person-badge-fill"></i> Data Pasien</a>
      <a class="nav-link <?= ($active_page == 'diagnosa') ? 'active' : '' ?>" href="?page=diagnosa"><i class="bi bi-journal-medical"></i> Database Penyakit</a>
    </nav>
  </div>

  <main class="main-content">
    <!-- Notifikasi -->
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4 rounded-4 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-check-circle-fill me-2"></i> <?= $_GET['msg'] ?></span>
            <?php if(isset($_GET['last_id'])): ?><a href="cetak_rujukan.php?id=<?= $_GET['last_id'] ?>" target="_blank" class="btn btn-sm btn-light border px-3 fw-bold small">Cetak Surat</a><?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- 1. ANTREAN PASIEN -->
    <?php if($active_page == 'antrean'): ?>
        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="stat-card"><div><div class="small fw-bold text-muted mb-1">MENUNGGU</div><div class="h2 fw-bold text-primary mb-0"><?= mysqli_num_rows(mysqli_query($conn, "SELECT id_rekam_medis FROM rekam_medis WHERE status='Menunggu' AND tgl_kunjungan = CURDATE()")) ?></div></div><i class="bi bi-hourglass-split fs-1 text-light"></i></div></div>
            <div class="col-md-4"><div class="stat-card" style="border-left-color: #1cc88a;"><div><div class="small fw-bold text-muted mb-1">TERLAYANI HARI INI</div><div class="h2 fw-bold text-success mb-0"><?= mysqli_num_rows(mysqli_query($conn, "SELECT id_rekam_medis FROM rekam_medis WHERE status='Selesai' AND tgl_kunjungan=CURDATE()")) ?></div></div><i class="bi bi-check-all fs-1 text-light"></i></div></div>
            <div class="col-md-4"><div class="stat-card" style="border-left-color: #f6c23e;"><div><div class="small fw-bold text-muted mb-1">STOK OBAT RENDAH</div><div class="h2 fw-bold text-warning mb-0"><?= mysqli_num_rows(mysqli_query($conn, "SELECT id_obat FROM obatm WHERE stok_sekarang <= 10")) ?></div></div><i class="bi bi-capsule fs-1 text-light"></i></div></div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0 text-dark">Antrean Pasien Aktif</h4>
            <span class="badge bg-white text-primary border px-3 py-2 rounded-pill fw-bold shadow-sm">
                <i class="bi bi-shield-check me-1"></i> Dokter Aktif: <?= $id_dokter ?>
            </span>
        </div>

        <!-- ALERT DARURAT -->
        <?php 
        $q_darurat = mysqli_query($conn, "SELECT rm.*, p.nama_pasien FROM rekam_medis rm JOIN pasienm p ON rm.id_pasien = p.id_pasien WHERE rm.status = 'Menunggu' AND rm.is_priority = 1 AND rm.tgl_kunjungan = CURDATE()");
        while($drt = mysqli_fetch_assoc($q_darurat)): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-center animate-pulse" style="border-left: 10px solid #dc3545 !important; border-radius: 15px;">
            <div class="p-3 bg-danger text-white rounded-3 me-3"><i class="bi bi-exclamation-triangle-fill fs-4"></i></div>
            <div class="flex-grow-1"><h6 class="fw-bold mb-0">PASIEN DARURAT TERDETEKSI!</h6><small>Pasien <strong><?= $drt['nama_pasien'] ?></strong>: "<?= $drt['keluhan'] ?>"</small></div>
            <button class="btn btn-white btn-sm fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#mPeriksa<?= $drt['id_rekam_medis'] ?>">TANGANI</button>
        </div>
        <?php endwhile; ?>


        <div class="data-container">
            <h5 class="fw-bold mb-4">Daftar Antrean Hari Ini</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Antrean</th><th>Nama Pasien</th><th>Keluhan</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php
                        $q_antrean = mysqli_query($conn, "SELECT rm.*, p.nama_pasien FROM rekam_medis rm JOIN pasienm p ON rm.id_pasien = p.id_pasien WHERE rm.status = 'Menunggu' AND rm.tgl_kunjungan = CURDATE() ORDER BY rm.is_priority DESC, rm.no_antrian ASC");
                        $modals_data = []; // Simpan data untuk loop modal nanti
                        if(mysqli_num_rows($q_antrean) == 0) echo "<tr><td colspan='4' class='text-center py-4'>Antrean Kosong</td></tr>";
                        while($r = mysqli_fetch_assoc($q_antrean)): 
                            $modals_data[] = $r; // Masukkan ke penampung
                        ?>
                        <tr>
                            <td><span class="badge <?= ($r['is_priority']==1)?'bg-danger':'bg-primary' ?>"><?= $r['no_antrian'] ?></span></td>
                            <td class="fw-bold"><?= $r['nama_pasien'] ?></td>
                            <td class="text-muted small"><?= $r['keluhan'] ?></td>
                            <td><button class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#mPeriksa<?= $r['id_rekam_medis'] ?>">Periksa</button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL PEMERIKSAAN (DI LUAR TABEL) -->
        <?php foreach($modals_data as $r): ?>
        <div class="modal fade" id="mPeriksa<?= $r['id_rekam_medis'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form class="modal-content border-0 shadow-lg" style="border-radius:24px" method="POST">
                    <div class="modal-header border-0 p-4 pb-0">
                        <h5 class="fw-bold">Pemeriksaan: <?= $r['nama_pasien'] ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <input type="hidden" name="id_rekam_medis" value="<?= $r['id_rekam_medis'] ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="small fw-bold text-muted">KELUHAN</label>
                                <textarea name="keluhan" class="form-control bg-light border-0" rows="3" required><?= $r['keluhan'] ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="small fw-bold text-muted">DIAGNOSA</label>
                                <select name="id_diagnosa" class="form-select bg-light border-0" required>
                                    <option value="">-- Pilih Penyakit --</option>
                                    <?php foreach($dx_options as $dx){ echo "<option value='{$dx['id_diagnosa']}'>{$dx['nama_penyakit']}</option>"; } ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">HASIL PEMERIKSAAN</label>
                            <textarea name="hasil_pemeriksaan" class="form-control bg-light border-0" rows="4" required placeholder="Tulis diagnosa dokter..."></textarea>
                        </div>
                        <hr class="my-4 border-dashed">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-capsule me-2"></i>RESEP OBAT (OPSIONAL)</h6>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="small fw-bold text-muted">PILIH OBAT</label>
                                <select name="id_obat" class="form-select bg-light border-0">
                                    <option value="">-- Tidak Memberikan Obat --</option>
                                    <?php foreach($obat_options as $obt){ echo "<option value='{$obt['id_obat']}'>{$obt['nama_obat']} (Sisa: {$obt['stok_sekarang']})</option>"; } ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="small fw-bold text-muted">JUMLAH</label>
                                <input type="number" name="jumlah_keluar" class="form-control bg-light border-0" placeholder="0">
                            </div>
                            <div class="col-12">
                                <label class="small fw-bold text-muted">CATATAN PAKAI</label>
                                <input type="text" name="catatan_obat" class="form-control bg-light border-0" placeholder="Contoh: 3x1 hari sesudah makan">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" name="simpan_pemeriksaan" class="btn btn-primary w-100 py-3 fw-bold rounded-4">Selesaikan & Simpan</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

<!-- 2. RIWAYAT REKAM MEDIS -->
    <?php elseif($active_page == 'rekam_medis'): ?>
        <h4 class="fw-bold mb-4">Riwayat Kunjungan Pasien</h4>

        <!-- FILTER BAR REKAM MEDIS (MULTI FILTER) -->
        <div class="data-container mb-4 py-3">
            <div class="row g-2">
                <!-- Cari Nama/NIM -->
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">PENCARIAN</label>
                    <input type="text" id="searchRM" class="form-control form-control-sm" placeholder="Nama / NIM...">
                </div>
                <!-- Filter Diagnosa -->
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">DIAGNOSA</label>
                    <select id="filterDX" class="form-select form-select-sm">
                        <option value="">-- Semua Diagnosa --</option>
                        <?php foreach($dx_options as $dx): ?>
                            <option value="<?= $dx['nama_penyakit'] ?>"><?= $dx['nama_penyakit'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Filter Status -->
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">STATUS</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">-- Semua --</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Menunggu">Menunggu</option>
                    </select>
                </div>
                <!-- Filter Resep Obat -->
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">RESEP OBAT</label>
                    <select id="filterObat" class="form-select form-select-sm">
                        <option value="">-- Semua --</option>
                        <option value="ya">Dengan Obat</option>
                        <option value="tidak">Tanpa Obat</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Tgl / Jam</th>
                            <th class="text-center">Antrean</th>
                            <th>Pasien</th>
                            <th>Diagnosa</th>
                            <th>Resep Obat</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="bodyRekamMedis">
                        <?php 
                        $no_rm = 1;
                        $qrm = mysqli_query($conn, "SELECT rm.*, p.nama_pasien, p.no_identitas, d.nama_penyakit, o.nama_obat, rs.jumlah_keluar 
                                                    FROM rekam_medis rm 
                                                    JOIN pasienm p ON rm.id_pasien = p.id_pasien 
                                                    LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa 
                                                    LEFT JOIN resep_dokter rs ON rm.id_rekam_medis = rs.id_rekam_medis 
                                                    LEFT JOIN obatm o ON rs.id_obat = o.id_obat 
                                                    ORDER BY rm.tgl_kunjungan DESC, rm.waktu_booking DESC");
                        while($rm = mysqli_fetch_assoc($qrm)): 
                            $punya_obat = (!empty($rm['nama_obat'])) ? 'ya' : 'tidak';
                        ?>
                        <tr class="rm-row" 
                            data-dx="<?= $rm['nama_penyakit'] ?? 'N/A' ?>" 
                            data-status="<?= $rm['status'] ?>" 
                            data-obat="<?= $punya_obat ?>">
                            
                            <td class="text-muted small"><?= $no_rm++ ?></td>
                            <td>
                                <div class="fw-bold small"><?= date('d/m/Y', strtotime($rm['tgl_kunjungan'])) ?></div>
                                <small class="text-muted"><?= $rm['waktu_booking'] ?></small>
                            </td>
                            <td class="text-center fw-bold small text-muted"><?= $rm['no_antrian'] ?></td>
                            <td>
                                <div class="fw-bold text-dark nama-pasien"><?= $rm['nama_pasien'] ?></div>
                                <small class="text-muted id-pasien">ID: <?= $rm['no_identitas'] ?></small>
                            </td>
                            <td><span class="badge bg-danger bg-opacity-10 text-danger small"><?= $rm['nama_penyakit'] ?? 'N/A' ?></span></td>
                            <td>
                                <?php if($rm['nama_obat']): ?>
                                    <small class="fw-bold text-success"><i class="bi bi-capsule me-1"></i><?= $rm['nama_obat'] ?> (x<?= $rm['jumlah_keluar'] ?>)</small>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= ($rm['status'] == 'Selesai') ? 'bg-success' : 'bg-warning text-dark' ?> rounded-pill small"><?= $rm['status'] ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- 3. STOK OBAT -->
    <?php elseif($active_page == 'obat'): ?>
        <h4 class="fw-bold mb-4">Stok Inventaris Obat Klinik</h4>
        <div class="data-container">
            <table class="table table-hover align-middle">
                <thead><tr><th>Nama Obat</th><th>Kategori</th><th>Satuan</th><th class="text-center">Stok</th><th class="text-center">Status</th></tr></thead>
                <tbody>
                    <?php $qo = mysqli_query($conn, "SELECT * FROM obatm ORDER BY nama_obat ASC");
                    while($ro = mysqli_fetch_assoc($qo)): ?>
                    <tr>
                        <td class="fw-bold text-primary"><?= $ro['nama_obat'] ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $ro['kategori'] ?></span></td>
                        <td><?= $ro['satuan'] ?></td>
                        <td class="text-center fw-bold"><?= $ro['stok_sekarang'] ?></td>
                        <td class="text-center"><span class="badge <?= ($ro['stok_sekarang'] > 10) ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 text-<?= ($ro['stok_sekarang'] > 10) ? 'success' : 'danger' ?> px-3 rounded-pill fw-bold"><?= ($ro['stok_sekarang'] > 0) ? 'Tersedia' : 'Habis' ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

<!-- 4. DATABASE PENYAKIT -->
    <?php elseif($active_page == 'diagnosa'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Database Penyakit</h4>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#mAddDiagnosa">+ Tambah Data</button>
        </div>
        <div class="data-container">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Nama Penyakit</th>
                        <th>Kategori</th>
                        <th>Tipe</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no_dx = 1; foreach($dx_options as $rd): ?>
                    <tr>
                        <td class="text-muted small"><?= $no_dx++ ?></td>
                        <td class="fw-bold text-primary"><?= $rd['nama_penyakit'] ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $rd['kategori'] ?></span></td>
                        <td><?= $rd['tipe'] ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-light text-warning me-2" data-bs-toggle="modal" data-bs-target="#mEditDx<?= $rd['id_diagnosa'] ?>"><i class="bi bi-pencil-square"></i></button>
                            <a href="?del=<?= $rd['id_diagnosa'] ?>&page=diagnosa" class="btn btn-sm btn-light text-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash3"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- LOOP MODAL EDIT (Diletakkan di luar tabel agar tidak merusak layout) -->
        <?php foreach($dx_options as $rd): ?>
        <div class="modal fade" id="mEditDx<?= $rd['id_diagnosa'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content border-0 shadow-lg" style="border-radius:24px" method="POST">
                    <div class="modal-header bg-warning text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Edit Diagnosa</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <input type="hidden" name="id_diagnosa" value="<?= $rd['id_diagnosa'] ?>">
                        <div class="mb-3">
                            <label class="small fw-bold">NAMA PENYAKIT</label>
                            <input type="text" name="nama_penyakit" class="form-control bg-light border-0 py-2" value="<?= $rd['nama_penyakit'] ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="small fw-bold text-muted">KATEGORI</label>
                                <select name="kategori" class="form-select bg-light border-0">
                                    <option <?= ($rd['kategori'] == 'Umum')?'selected':'' ?>>Umum</option>
                                    <option <?= ($rd['kategori'] == 'Menular')?'selected':'' ?>>Menular</option>
                                    <option <?= ($rd['kategori'] == 'Kronis')?'selected':'' ?>>Kronis</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="small fw-bold text-muted">TIPE</label>
                                <select name="tipe" class="form-select bg-light border-0">
                                    <option <?= ($rd['tipe'] == 'Ringan')?'selected':'' ?>>Ringan</option>
                                    <option <?= ($rd['tipe'] == 'Sedang')?'selected':'' ?>>Sedang</option>
                                    <option <?= ($rd['tipe'] == 'Berat')?'selected':'' ?>>Berat</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="submit" name="update_diagnosa" class="btn btn-warning w-100 py-3 fw-bold rounded-4 text-white shadow">Update Data</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

    <!-- 5. RUJUKAN -->
    <?php elseif($active_page == 'rujukan'): ?>
        <div class="row g-4">
            <!-- Form Buat Rujukan (Kiri) -->
            <div class="col-lg-5">
                <h4 class="fw-bold mb-4">Buat Rujukan</h4>
                <div class="data-container">
                    <form method="POST">
                        <div class="mb-3 search-box position-relative">
                            <label class="small fw-bold text-muted">CARI PASIEN (NIM)</label>
                            <input id="inputSearchPasien" name="nim_nip" class="form-control bg-light border-0 py-2" required autocomplete="off" placeholder="Ketik NIM...">
                            <div id="hasilPencarian"></div>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">RS TUJUAN</label>
                            <input name="tujuan_rs" class="form-control bg-light border-0 py-2" required placeholder="Nama Rumah Sakit">
                        </div>
                        <div class="mb-4">
                            <label class="small fw-bold text-muted">ALASAN</label>
                            <textarea name="alasan_rujukan" class="form-control bg-light border-0" rows="4" required placeholder="Alasan medis rujukan..."></textarea>
                        </div>
                        <button name="buat_rujukan_langsung" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow">Terbitkan Surat Rujukan</button>
                    </form>
                </div>
            </div>

            <!-- Riwayat Rujukan (Kanan) -->
            <div class="col-lg-7">
                <h4 class="fw-bold mb-4">Riwayat Rujukan</h4>
                
                <!-- SEARCH BAR RUJUKAN -->
                <div class="data-container mb-3 py-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchRujukan" class="form-control border-start-0 ps-0" placeholder="Cari nama pasien yang dirujuk...">
                    </div>
                </div>

                <div class="data-container">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th width="40">No</th>
                                    <th>Nama Pasien</th>
                                    <th>RS Tujuan</th>
                                    <th class="text-center">Cetak</th>
                                </tr>
                            </thead>
                            <tbody id="bodyRujukan">
                            <?php 
                            $no_ruj = 1;
                            $qr = mysqli_query($conn,"SELECT r.*, p.nama_pasien FROM rujukan r JOIN pasienm p ON r.id_pasien = p.id_pasien WHERE r.id_staff = '$id_dokter' ORDER BY r.tgl_rujukan DESC"); 
                            if(mysqli_num_rows($qr) == 0) echo "<tr><td colspan='4' class='text-center py-3'>Belum ada riwayat rujukan</td></tr>";
                            while($row = mysqli_fetch_assoc($qr)): 
                            ?>
                                <tr class="rujukan-row">
                                    <td class="text-muted small"><?= $no_ruj++ ?></td>
                                    <td>
                                        <div class="fw-bold nama-pasien-rujukan"><?= $row['nama_pasien'] ?></div>
                                        <small class="text-muted"><?= date('d M Y', strtotime($row['tgl_kunjungan'] ?? $row['tgl_rujukan'])) ?></small>
                                    </td>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary px-3"><?= $row['tujuan_rs'] ?></span></td>
                                    <td class="text-center">
                                        <a href="cetak_rujukan.php?id=<?= $row['id_rujukan'] ?>" target="_blank" class="btn btn-sm btn-light text-primary">
                                            <i class="bi bi-printer-fill fs-5"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <!-- 6. DATA PASIEN -->
<!-- 6. DATA PASIEN -->
    <?php elseif($active_page == 'pasien'): ?>
        <h4 class="fw-bold mb-4">Database Pasien Kampus</h4>

        <!-- SEARCH & FILTER SEPERTI DI ADMIN -->
        <div class="data-container mb-4 py-3">
            <div class="row g-3">
                <div class="col-md-7">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchPasienDoc" class="form-control border-start-0 ps-0" placeholder="Cari NIM atau nama pasien...">
                    </div>
                </div>
                <div class="col-md-5">
                    <select id="filterProdiDoc" class="form-select">
                        <option value="">-- Semua Prodi / Unit Kerja --</option>
                        <optgroup label="Program Studi (Mahasiswa)">
                            <option value="MI">D3 - Manajemen Informatika</option>
                            <option value="MK">D3 - Mekatronika</option>
                            <option value="MO">D3 - Mesin Otomotif</option>
                            <option value="P4">D3 - Pembuatan Peralatan Presisi</option>
                            <option value="TPM">D3 - Teknik Produksi & Proses Manufaktur</option>
                            <option value="TKBG">D4 - Teknologi Konstruksi Bangunan Gedung</option>
                            <option value="TRL">D4 - Teknologi Rekayasa Logistik</option>
                            <option value="TRPAB">D4 - Teknologi Rekayasa Pemeliharaan Alat Berat</option>
                            <option value="TRPL">D4 - Teknologi Rekayasa Perangkat Lunak</option>
                        </optgroup>
                        <optgroup label="Unit / Divisi Kerja (Pegawai)">
                            <option value="BAA">Biro Administrasi Akademik (BAA)</option>
                            <option value="BAK">Biro Administrasi Keuangan (BAK)</option>
                            <option value="BKM">Biro Kemahasiswaan & Alumni</option>
                            <option value="WKS">Workshop & Laboratorium Pusat</option>
                            <option value="HRD">Human Resources (HRD)</option>
                            <option value="IT">IT Support</option>
                            <option value="GA">General Affair</option>
                            <option value="DIR">Sekretariat Direktorat</option>
                            <option value="K3">Departemen K3</option>
                            <option value="SECURITY">Divisi Keamanan</option>
                        </optgroup>
                    </select>
                </div>
            </div>
        </div>

        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>ID / NIM</th>
                            <th>Nama Pasien</th>
                            <th>Kategori</th>
                            <th>Unit Prodi</th>
                        </tr>
                    </thead>
                    <tbody id="bodyPasienDoc">
                        <?php 
                        $no_ps = 1;
                        $qp = mysqli_query($conn, "SELECT * FROM pasienm ORDER BY nama_pasien ASC");
                        while($rp = mysqli_fetch_assoc($qp)): ?>
                        <tr class="pasien-doc-row" data-prodi="<?= $rp['unit_prodi'] ?>">
                            <td class="text-muted small"><?= $no_ps++ ?></td>
                            <td class="fw-bold text-primary identitas-ps"><?= $rp['no_identitas'] ?></td>
                            <td class="fw-bold nama-ps"><?= $rp['nama_pasien'] ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $rp['kategori_pasien'] ?></span></td>
                            <td><small class="fw-bold"><?= $rp['unit_prodi'] ?></small></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
  </main>

  <!-- MODAL LOGOUT -->
  <div class="modal fade" id="modalLogout" tabindex="-1"><div class="modal-dialog modal-dialog-centered" style="max-width: 400px;"><div class="modal-content border-0 shadow-lg" style="border-radius: 20px;"><div class="modal-body p-5 text-center"><div class="mb-4 text-danger"><i class="bi bi-exclamation-circle-fill" style="font-size: 4rem; opacity: 0.2;"></i></div><h4 class="fw-bold mb-2">Yakin Ingin Keluar?</h4><p class="text-muted small">Pastikan semua data pemeriksaan telah disimpan.</p><div class="d-grid gap-2 mt-4"><button type="button" class="btn-light btn w-100 py-2 fw-bold rounded-3" data-bs-dismiss="modal">Batal</button><a href="index.php" class="btn btn-danger w-100 py-2 fw-bold text-white text-decoration-none shadow">Ya, Keluar</a></div></div></div></div></div>

  <!-- MODAL TAMBAH DIAGNOSA -->
  <div class="modal fade" id="mAddDiagnosa" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg" style="border-radius:24px" method="POST"><div class="modal-header bg-primary text-white border-0 py-4"><h5 class="fw-bold mb-0">Tambah Diagnosa</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4 text-start"><label class="small fw-bold">NAMA PENYAKIT</label><input type="text" name="nama_penyakit" class="form-control mb-3 bg-light border-0 py-2" required><div class="row"><div class="col-6"><label class="small fw-bold">KATEGORI</label><select name="kategori" class="form-select bg-light border-0"><option>Umum</option><option>Menular</option><option>Kronis</option></select></div><div class="col-6"><label class="small fw-bold">TIPE</label><select name="tipe" class="form-select bg-light border-0"><option>Ringan</option><option>Sedang</option><option>Berat</option></select></div></div></div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="add_diagnosa" class="btn btn-primary w-100 py-3 fw-bold rounded-4 shadow">Simpan Ke Database</button></div></form></div></div>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    // --- CLOCK & SIDEBAR (Tetap Sama) ---
    function updateClock() { const now = new Date(); const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }; document.getElementById('digitalClock').innerText = now.toLocaleDateString('id-ID', options); }
    setInterval(updateClock, 1000); updateClock();

    const sidebarToggle = document.getElementById('sidebarToggle');
    if(sidebarToggle) sidebarToggle.addEventListener('click', function() { document.body.classList.toggle('sidebar-toggled'); });

    // --- SEARCH PASIEN SAAT RUJUKAN (Fetch) ---
    const inputSearch = document.getElementById('inputSearchPasien'); 
    const box = document.getElementById('hasilPencarian');
    if(inputSearch){ 
        inputSearch.addEventListener('input', function(){ 
            let q = this.value; 
            if(q.length < 2) { box.innerHTML=''; box.style.display='none'; return; } 
            fetch('search_pasien.php?keyword='+q).then(r=>r.text()).then(d=>{ box.innerHTML=d; box.style.display='block'; }); 
        }); 
    }
    function pilihPasien(nim){ document.getElementById('inputSearchPasien').value = nim; box.innerHTML=''; box.style.display='none'; }

    // ==========================================
    // FUNGSI MULTI-FILTER REKAM MEDIS
    // ==========================================
    function applyRMFilter() {
        const input = document.getElementById('searchRM');
        const dx = document.getElementById('filterDX');
        const st = document.getElementById('filterStatus');
        const ob = document.getElementById('filterObat');

        if(!input) return; // Jika tidak di halaman RM, stop.

        const searchTerm = input.value.toLowerCase();
        const selectedDX = dx.value.toLowerCase();
        const selectedStatus = st.value.toLowerCase();
        const selectedObat = ob.value.toLowerCase();
        
        const rows = document.querySelectorAll('.rm-row');

        rows.forEach(row => {
            const nama = row.querySelector('.nama-pasien').innerText.toLowerCase();
            const id = row.querySelector('.id-pasien').innerText.toLowerCase();
            const rowDX = row.getAttribute('data-dx').toLowerCase();
            const rowStatus = row.getAttribute('data-status').toLowerCase();
            const rowObat = row.getAttribute('data-obat').toLowerCase();

            // Logika: Jika filter kosong (""), maka dianggap COCOK (true)
            const matchSearch = nama.includes(searchTerm) || id.includes(searchTerm);
            const matchDX = selectedDX === "" || rowDX === selectedDX;
            const matchStatus = selectedStatus === "" || rowStatus === selectedStatus;
            const matchObat = selectedObat === "" || rowObat === selectedObat;

            // Baris tampil jika SEMUA kriteria terpenuhi
            row.style.display = (matchSearch && matchDX && matchStatus && matchObat) ? "" : "none";
        });
    }

    // Pasang listener Rekam Medis
    const rmSearchInput = document.getElementById('searchRM');
    if(rmSearchInput) {
        ['input', 'change'].forEach(evt => {
            rmSearchInput.addEventListener(evt, applyRMFilter);
            document.getElementById('filterDX').addEventListener(evt, applyRMFilter);
            document.getElementById('filterStatus').addEventListener(evt, applyRMFilter);
            document.getElementById('filterObat').addEventListener(evt, applyRMFilter);
        });
    }

    // ==========================================
    // FUNGSI FILTER LAINNYA (Rujukan & Pasien)
    // ==========================================
    
    // Filter Rujukan
    const searchRujukan = document.getElementById('searchRujukan');
    if(searchRujukan) {
        searchRujukan.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('.rujukan-row').forEach(row => {
                const nama = row.querySelector('.nama-pasien-rujukan').innerText.toLowerCase();
                row.style.display = nama.includes(term) ? "" : "none";
            });
        });
    }

    // Filter Data Pasien Master
    const searchPasienDoc = document.getElementById('searchPasienDoc');
    const filterProdiDoc = document.getElementById('filterProdiDoc');
    if(searchPasienDoc) {
        const filterPsn = () => {
            const term = searchPasienDoc.value.toLowerCase();
            const prodi = filterProdiDoc.value.toLowerCase();
            document.querySelectorAll('.pasien-doc-row').forEach(row => {
                const nama = row.querySelector('.nama-ps').innerText.toLowerCase();
                const nim = row.querySelector('.identitas-ps').innerText.toLowerCase();
                const pData = row.getAttribute('data-prodi').toLowerCase();
                const mSearch = nama.includes(term) || nim.includes(term);
                const mProdi = prodi === "" || pData === prodi;
                row.style.display = (mSearch && mProdi) ? "" : "none";
            });
        };
        searchPasienDoc.addEventListener('input', filterPsn);
        filterProdiDoc.addEventListener('change', filterPsn);
    }
</script>
</body>
</html>