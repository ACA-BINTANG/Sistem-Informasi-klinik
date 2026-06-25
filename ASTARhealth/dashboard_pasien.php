<?php
session_start();
require_once 'koneksi.php';

// =======================
// PROTEKSI ROLE PASIEN
// =======================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Pasien') {
    header("Location: login.php?pesan=Akses Ditolak!");
    exit;
}

$user_id = $_SESSION['id_user'];
$pasien_name = $_SESSION['nama_lengkap'];
$active_page = $_GET['page'] ?? 'beranda';

// Ambil ID Pasien berdasarkan ID User login
$qP = mysqli_query($conn, "SELECT id_pasien FROM pasienm WHERE id_user = '$user_id'");
$dP = mysqli_fetch_assoc($qP);
$id_pasien = $dP['id_pasien'] ?? '';
// ==========================================
// FUNGSI GENERATE NO ANTREAN ALPHANUMERIC (Global)
// ==========================================
function generateAntreanAlphanumeric($conn) {
    // Ambil nomor terakhir secara keseluruhan (Global)
    $q = mysqli_query($conn, "SELECT no_antrian FROM rekam_medis 
                             ORDER BY tgl_kunjungan DESC, waktu_booking DESC, id_rekam_medis DESC 
                             LIMIT 1");
                             
    if (mysqli_num_rows($q) == 0) return "A001";
    
    $last_no = mysqli_fetch_assoc($q)['no_antrian'];
    $huruf = substr($last_no, 0, 1);
    $angka = (int)substr($last_no, 1);
    
    if ($angka < 999) { 
        $angka++; 
    } else { 
        $angka = 0; // Reset ke 000 sesuai permintaan Anda
        $huruf++;   // A naik ke B, dst
    }
    
    return $huruf . str_pad($angka, 3, "0", STR_PAD_LEFT);
}

function generateID($conn, $prefix, $table, $column) {
    $exists = true;
    while($exists) {
        $new_id = $prefix . substr(str_shuffle("0123456789"), 0, 4);
        $cek = mysqli_query($conn, "SELECT $column FROM $table WHERE $column = '$new_id'");
        if (mysqli_num_rows($cek) == 0) return $new_id;
    }
}

// ==========================================
// LOGIKA AMBIL ANTREAN
// ==========================================
if (isset($_POST['ambil_antrean'])) {
    $id_rm = generateID($conn, "RM", "rekam_medis", "id_rekam_medis");
    $tgl_skrg = date('Y-m-d');
    $jam_skrg = date('H:i:s');
    $keluhan = mysqli_real_escape_string($conn, $_POST['keluhan']);

    // Cek apakah hari ini sudah ambil antrean dan belum selesai
    $cek = mysqli_query($conn, "SELECT id_rekam_medis FROM rekam_medis WHERE id_pasien='$id_pasien' AND tgl_kunjungan='$tgl_skrg' AND status = 'Menunggu'");
    if (mysqli_num_rows($cek) > 0) {
        header("Location: dashboard_pasien.php?page=beranda&err=Anda masih memiliki antrean aktif hari ini.");
        exit;
    }

    // Deteksi Prioritas/Darurat
    $keywords = ['asma', 'pingsan', 'tertusuk', 'sesak', 'jantung', 'darah', 'perdarahan', 'kecelakaan', 'kejang', 'lemas'];
    $is_priority = 0;
    foreach ($keywords as $key) { if (stripos($keluhan, $key) !== false) { $is_priority = 1; break; } }

    $no_baru = generateAntreanAlphanumeric($conn);
    $query = "INSERT INTO rekam_medis (id_rekam_medis, id_pasien, no_antrian, tgl_kunjungan, waktu_booking, keluhan, status, is_priority) 
              VALUES ('$id_rm', '$id_pasien', '$no_baru', '$tgl_skrg', '$jam_skrg', '$keluhan', 'Menunggu', '$is_priority')";
    
    if (mysqli_query($conn, $query)) {
        header("Location: dashboard_pasien.php?page=beranda&msg=Antrean $no_baru Berhasil Diambil!");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Patient Panel - ASTARhealth</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root { --astar-blue: #0057B8; --astar-soft-blue: #eef4ff; --sidebar-bg: #ffffff; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f7fa; color: #334155; }
    .top-header { height: 70px; background: var(--astar-blue); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; color: white; position: fixed; top: 0; width: 100%; z-index: 1001; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    #digitalClock { font-weight: 600; font-size: 14px; background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 50px; }
    .sidebar { width: 280px; background: var(--sidebar-bg); height: 100vh; position: fixed; top: 70px; border-right: 1px solid #e2e8f0; z-index: 1000; padding: 15px 0; overflow-y: auto; }
    .nav-group-title { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; padding: 20px 25px 8px; letter-spacing: 1px; }
    .nav-link { padding: 12px 25px; color: #64748b; font-weight: 500; display: flex; align-items: center; transition: 0.2s; text-decoration: none; font-size: 14px; margin: 0 15px; border-radius: 10px; }
    .nav-link i { font-size: 1.2rem; width: 35px; }
    .nav-link:hover { background: var(--astar-soft-blue); color: var(--astar-blue); transform: translateX(5px); }
    .nav-link.active { background: var(--astar-blue); color: #fff; box-shadow: 0 4px 12px rgba(0,87,184,0.3); }
    .main-content { margin-left: 280px; padding: 100px 40px 40px; animation: fadeIn 0.5s ease; }
    .data-container { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; }
    .stat-card { background: white; border-radius: 18px; padding: 25px; display: flex; align-items: center; justify-content: space-between; border-left: 6px solid var(--astar-blue); box-shadow: 0 10px 20px rgba(0,0,0,0.03); transition: 0.3s; }
    .antrean-card { background: linear-gradient(135deg, #0057B8 0%, #003d82 100%); color: white; border-radius: 24px; padding: 40px; text-align: center; border: none; }
    .antrean-card.emergency { background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%); }
    .antrean-number { font-size: 5rem; font-weight: 800; line-height: 1; margin: 20px 0; text-shadow: 0 4px 15px rgba(0,0,0,0.2); }
    .table thead th { background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 12px; padding: 15px; border: none; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    /* Transisi Sidebar & Main Content */
.sidebar {
    transition: all 0.3s ease;
}

.main-content {
    transition: all 0.3s ease;
}

/* State saat Sidebar disembunyikan */
body.sidebar-toggled .sidebar {
    left: -280px;
}

body.sidebar-toggled .main-content {
    margin-left: 0;
}

/* Responsif untuk HP */
@media (max-width: 768px) {
    .sidebar { left: -280px; }
    .main-content { margin-left: 0; }
    body.sidebar-toggled .sidebar { left: 0; }
}

/* Styling Tombol Toggle */
#sidebarToggle {
    cursor: pointer;
    font-size: 1.5rem;
    padding: 5px 10px;
    border-radius: 8px;
    transition: 0.2s;
}

#sidebarToggle:hover {
    background: rgba(255,255,255,0.1);
}
  </style>
</head>
<body>

<header class="top-header">
    <div class="d-flex align-items-center gap-3">
        <!-- TOMBOL NAVBAR BARU DISINI -->
        <div id="sidebarToggle" class="text-white">
            <i class="bi bi-list"></i>
        </div>
        
        <img src="assets/img/logoA.png" style="max-height: 70px; filter: brightness(0) invert(1);">
        <div id="digitalClock" class="d-none d-md-block text-white fw-bold"></div>
    </div>
    <!-- ... sisanya tetap ... -->
    <div class="dropdown">
        <a href="#" data-bs-toggle="dropdown" class="text-white text-decoration-none d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block lh-1">
                <div class="fw-bold mb-1"><?= $pasien_name ?></div>
                <small style="opacity: 0.8; font-size: 10px;">ID Pasien: <?= $id_pasien ?></small>
            </div>
            <i class="bi bi-person-circle fs-2"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-2" style="border-radius: 12px;">
            <li><a class="dropdown-item rounded-2 text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalLogout"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
        </ul>
    </div>
  </header>

  <div class="sidebar">
    <div class="nav-group-title">Menu Utama</div>
    <nav class="nav flex-column">
      <a class="nav-link <?= ($active_page == 'beranda') ? 'active' : '' ?>" href="?page=beranda"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
      <a class="nav-link <?= ($active_page == 'antrean') ? 'active' : '' ?>" href="?page=antrean"><i class="bi bi-ticket-perforated-fill"></i> Ambil Antrean</a>
    </nav>
    <div class="nav-group-title">Layanan Medis</div>
    <nav class="nav flex-column">
      <a class="nav-link <?= ($active_page == 'riwayat') ? 'active' : '' ?>" href="?page=riwayat"><i class="bi bi-clock-history"></i> Riwayat Berobat</a>
      <a class="nav-link <?= ($active_page == 'obat') ? 'active' : '' ?>" href="?page=obat"><i class="bi bi-capsule-pill"></i> Stok Obat Klinik</a>
    </nav>
  </div>

  <main class="main-content">
    <?php if(isset($_GET['msg'])): ?><div class="alert alert-success border-0 shadow-sm mb-4 rounded-4 fw-bold animate-pulse"><i class="bi bi-check-circle-fill me-2"></i> <?= $_GET['msg'] ?></div><?php endif; ?>
    <?php if(isset($_GET['err'])): ?><div class="alert alert-danger border-0 shadow-sm mb-4 rounded-4 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_GET['err'] ?></div><?php endif; ?>

    <!-- 1. BERANDA -->
    <?php if($active_page == 'beranda'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0 text-dark">Halo, <?= explode(' ', $pasien_name)[0] ?>!</h3>
            <span class="text-muted small fw-bold text-uppercase"><i class="bi bi-calendar3 me-1"></i> <?= date('d M Y') ?></span>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <?php 
                    $tgl_skrg = date('Y-m-d');
                    $q_my = mysqli_query($conn, "SELECT no_antrian, status, is_priority FROM rekam_medis 
                                                WHERE id_pasien='$id_pasien' AND tgl_kunjungan='$tgl_skrg' 
                                                AND status != 'Batal' ORDER BY no_antrian DESC LIMIT 1");
                    
                    if(mysqli_num_rows($q_my) > 0): 
                        $d_my = mysqli_fetch_assoc($q_my);
                ?>
                <div class="antrean-card shadow-lg h-100 <?= ($d_my['is_priority'] == 1) ? 'emergency' : '' ?>">
                    <h6 class="fw-bold opacity-75 text-uppercase" style="letter-spacing:1px">
                        <?= ($d_my['is_priority'] == 1) ? '🚨 Antrean Darurat' : 'Antrean Aktif Anda' ?>
                    </h6>
                    <div class='antrean-number'><?= $d_my['no_antrian'] ?></div>
                    <span class="badge <?= ($d_my['status'] == 'Selesai') ? 'bg-success' : 'bg-white text-dark' ?> px-4 py-2 rounded-pill fw-bold shadow-sm">
                        STATUS: <?= strtoupper($d_my['status']) ?>
                    </span>
                    <p class="mt-4 mb-0 small opacity-75 italic"><?= ($d_my['status'] == 'Menunggu') ? 'Mohon menunggu giliran Anda di ruang tunggu.' : 'Kunjungan telah selesai.' ?></p>
                </div>
                <?php else: ?>
                <div class="antrean-card shadow-lg h-100 opacity-50" style="filter: grayscale(1);">
                    <h6 class="fw-bold opacity-75 text-uppercase">Nomor Antrean</h6>
                    <div class='antrean-number'>--</div>
                    <p class='mb-0 small opacity-75'>Belum ada antrean yang diambil hari ini.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-7">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="stat-card">
                            <div><div class="small fw-bold text-muted mb-1">TOTAL BEROBAT</div><div class="h2 fw-bold text-primary mb-0"><?= mysqli_num_rows(mysqli_query($conn, "SELECT id_rekam_medis FROM rekam_medis WHERE id_pasien='$id_pasien' AND status='Selesai'")) ?></div></div>
                            <i class="bi bi-clipboard2-pulse fs-1 text-light"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card" style="border-left-color: #1cc88a;">
                            <div><div class="small fw-bold text-muted mb-1">DATA TERDAFTAR</div><div class="h5 fw-bold text-success mb-0">PROFIL AKTIF</div></div>
                            <i class="bi bi-shield-check fs-1 text-light"></i>
                        </div>
                    </div>
                </div>
                <div class="data-container py-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle-fill text-primary me-2"></i>Panduan Layanan</h6>
                    <ul class="list-unstyled small text-muted">
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Ambil antrean melalui menu <strong>Ambil Antrean</strong>.</li>
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i>Gunakan keyword darurat jika Anda merasa kritis.</li>
                        <li><i class="bi bi-check2-circle text-success me-2"></i>Pantau riwayat obat di menu <strong>Riwayat Berobat</strong>.</li>
                    </ul>
                </div>
            </div>
        </div>

    <!-- 2. FORM AMBIL ANTREAN -->
    <?php elseif($active_page == 'antrean'): ?>
        <h4 class="fw-bold mb-4">Form Registrasi Medis</h4>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="data-container shadow-sm">
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">APA YANG ANDA RASAKAN SAAT INI?</label>
                            <textarea name="keluhan" class="form-control bg-light border-0 p-3 shadow-none" rows="7" placeholder="Contoh: Merasa sesak nafas dan nyeri di dada..." required style="border-radius: 15px;"></textarea>
                            <div class="form-text mt-2 small italic text-primary"><i class="bi bi-lightbulb me-1"></i>Sistem akan mendeteksi tingkat keparahan berdasarkan keluhan Anda.</div>
                        </div>
                        <button type="submit" name="ambil_antrean" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow">
                            <i class="bi bi-plus-circle me-2"></i> Dapatkan Nomor Antrean
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="p-4 rounded-4 bg-white border border-danger border-opacity-25 shadow-sm">
                    <h6 class="fw-bold text-danger mb-3"><i class="bi bi-lightning-charge-fill me-2"></i>Daftar Gejala Darurat</h6>
                    <p class="small text-muted mb-3">Jika keluhan Anda mengandung kata berikut, sistem akan memberikan **Prioritas Penanganan**:</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Sesak</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Pingsan</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Darah</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Jantung</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Kecelakaan</span>
                    </div>
                </div>
            </div>
        </div>

    <!-- 3. CEK STOK OBAT -->
    <?php elseif($active_page == 'obat'): ?>
        <h4 class="fw-bold mb-4">Informasi Stok Farmasi</h4>
        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Nama Obat</th><th>Kategori</th><th class="text-center">Ketersediaan</th></tr></thead>
                    <tbody>
                        <?php 
                        $qo = mysqli_query($conn, "SELECT * FROM obatm ORDER BY nama_obat ASC");
                        while($ro = mysqli_fetch_assoc($qo)): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= $ro['nama_obat'] ?></td>
                            <td><span class="badge bg-light text-dark border px-3"><?= $ro['kategori'] ?? 'Umum' ?></span></td>
                            <td class="text-center">
                                <span class="badge <?= ($ro['stok_sekarang'] > 10) ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 text-<?= ($ro['stok_sekarang'] > 10) ? 'success' : 'danger' ?> px-4 py-2 rounded-pill fw-bold">
                                    <?= ($ro['stok_sekarang'] > 0) ? 'Tersedia' : 'Habis' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- 4. RIWAYAT BEROBAT -->
    <?php elseif($active_page == 'riwayat'): ?>
        <h4 class="fw-bold mb-4">Arsip Riwayat Medis</h4>
        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Tanggal / Jam</th><th>Keluhan</th><th>Diagnosa</th><th class="text-center">Aksi</th></tr></thead>
                    <tbody>
                        <?php 
                        $qr = mysqli_query($conn, "SELECT rm.*, d.nama_penyakit FROM rekam_medis rm LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa WHERE rm.id_pasien = '$id_pasien' ORDER BY rm.tgl_kunjungan DESC, rm.waktu_booking DESC");
                        if(mysqli_num_rows($qr) == 0) echo "<tr><td colspan='4' class='text-center py-5 text-muted'>Belum ada riwayat berobat.</td></tr>";
                        while($row = mysqli_fetch_assoc($qr)): ?>
                        <tr>
                            <td><div class="fw-bold small"><?= date('d M Y', strtotime($row['tgl_kunjungan'])) ?></div><small class="text-muted"><i class="bi bi-clock me-1"></i><?= $row['waktu_booking'] ?></small></td>
                            <td><div style="max-width: 250px;" class="small text-truncate" title="<?= $row['keluhan'] ?>"><?= $row['keluhan'] ?></div></td>
                            <td><span class="badge <?= ($row['status']=='Selesai')?'bg-primary bg-opacity-10 text-primary':'bg-warning bg-opacity-10 text-warning' ?> px-3"><?= $row['nama_penyakit'] ?? 'N/A' ?></span></td>
                            <td class="text-center"><button class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#mDetail<?= $row['id_rekam_medis'] ?>"><i class="bi bi-eye me-1"></i>Detail</button></td>
                        </tr>
                        <!-- MODAL DETAIL -->
                        <div class="modal fade" id="mDetail<?= $row['id_rekam_medis'] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg" style="border-radius: 20px;"><div class="modal-header bg-light border-0 p-4"><h6 class="fw-bold mb-0">Catatan Medis: <?= date('d/m/Y', strtotime($row['tgl_kunjungan'])) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4 text-start"><label class="small fw-bold text-muted text-uppercase mb-1">Diagnosa:</label><h5 class="text-primary fw-bold"><?= $row['nama_penyakit'] ?? 'Menunggu Pemeriksaan' ?></h5><hr><label class="small fw-bold text-muted text-uppercase mb-1 d-block">Hasil Pemeriksaan & Resep:</label><div class="p-3 bg-light rounded-3 text-muted" style="font-size: 13.5px;"><?= nl2br($row['hasil_pemeriksaan'] ?? 'Dokter belum mengisi catatan medis untuk antrean ini.') ?></div></div></div></div></div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
  </main>

  <!-- MODAL LOGOUT -->
  <div class="modal fade" id="modalLogout" tabindex="-1"><div class="modal-dialog modal-dialog-centered" style="max-width: 400px;"><div class="modal-content border-0 shadow-lg" style="border-radius: 24px;"><div class="modal-body text-center p-5"><div class="text-danger mb-4"><i class="bi bi-exclamation-circle-fill" style="font-size: 4rem; opacity: 0.2;"></i></div><h4 class="fw-bold mb-2">Yakin Ingin Keluar?</h4><p class="text-muted small mb-4">Sesi Anda akan berakhir. Pastikan data pendaftaran Anda telah tercatat.</p><div class="d-flex gap-2"><button type="button" class="btn btn-light w-100 py-2 fw-bold rounded-3" data-bs-dismiss="modal">Batal</button><a href="index.php" class="btn btn-danger w-100 py-2 fw-bold rounded-3 shadow-sm text-white text-decoration-none">Ya, Keluar</a></div></div></div></div></div>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    function updateClock() { const now = new Date(); const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }; document.getElementById('digitalClock').innerText = now.toLocaleDateString('id-ID', options); }
    setInterval(updateClock, 1000); updateClock();
  
    // Logika Toggle Sidebar
const sidebarToggle = document.getElementById('sidebarToggle');
const body = document.body;

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function() {
        body.classList.toggle('sidebar-toggled');
    });
}

// Opsional: Tutup sidebar otomatis jika layar HP diklik di luar sidebar
document.addEventListener('click', function(event) {
    const isClickInsideSidebar = document.querySelector('.sidebar').contains(event.target);
    const isClickInsideToggle = sidebarToggle.contains(event.target);
    
    if (window.innerWidth <= 768 && !isClickInsideSidebar && !isClickInsideToggle) {
        body.classList.remove('sidebar-toggled');
    }
});
  </script>
</body>
</html>