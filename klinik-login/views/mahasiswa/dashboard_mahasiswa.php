<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman menggunakan Absolute Path agar tidak tersesat oleh tingkatan folder
if (!isset($_SESSION['user']) || strtolower($_SESSION['role']) !== 'mahasiswa') {
    // JIKA PROJECT KAMU DI DALAM FOLDER (misal: localhost/siakad/), UBAH MENJADI: header("Location: /siakad/login.php");
    header("Location: /login.php");
    exit;
}

$role = $_SESSION['role'];
$name = $_SESSION['name'];
$nim_mhs = isset($_SESSION['nim']) ? $_SESSION['nim'] : '0920250054'; 

$role_required = 'Mahasiswa'; 
$page_title = 'Dashboard Mahasiswa';

// Ambil file layout utama (Naik 2 folder)
require_once '../../includes/layout.php';

// Panggil fungsi header utama tanpa takut eror null lagi
render_header($page_title, $role, $name, null, null, 0);
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">

<div class="row g-3 mb-3">
  <?php
  $stats = [
    ['Mata Kuliah Aktif', '6', 'bi-journal-medical', '#0284c7', '#f0f9ff'],
    ['Tugas Pending', '3', 'bi-clipboard-check', '#0369a1', '#e0f2fe'],
    ['Jam Praktikum', '124', 'bi-clock-history', '#38bdf8', '#fafafa'],
    ['IPK Saat Ini', '3.78', 'bi-award-fill', '#0284c7', '#bae6fd'],
  ];
  foreach ($stats as $s): ?>
    <div class="col-md-6 col-lg-3">
      <div class="stat-card">
        <div class="icon-wrap" style="background:<?php echo $s[4]; ?>; color:<?php echo $s[3]; ?>">
          <i class="bi <?php echo $s[2]; ?>"></i>
        </div>
        <h3><?php echo $s[1]; ?></h3>
        <div class="label"><?php echo $s[0]; ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="panel mb-3">
      <h6 class="fw-bold mb-3">Tugas & Laporan</h6>
      <table class="table clean">
        <thead><tr><th>Judul</th><th>Mata Kuliah</th><th>Deadline</th><th>Status</th></tr></thead>
        <tbody>
          <tr><td>Laporan Observasi Pasien</td><td>Klinik Dasar</td><td>10 Jun 2026</td><td><span class="pill pill-warn">Pending</span></td></tr>
          <tr><td>Studi Kasus Hipertensi</td><td>Kardiologi</td><td>15 Jun 2026</td><td><span class="pill pill-warn">Pending</span></td></tr>
          <tr><td>Anamnesis Pasien Anak</td><td>Pediatri</td><td>20 Jun 2026</td><td><span class="pill pill-info">Draft</span></td></tr>
          <tr><td>Resume Materi Farmakologi</td><td>Farmakologi</td><td>5 Jun 2026</td><td><span class="pill pill-success">Selesai</span></td></tr>
        </tbody>
      </table>
    </div>
    
    <div class="panel">
      <h6 class="fw-bold mb-3">Materi Klinik Terbaru</h6>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="p-3 border rounded-3">
            <i class="bi bi-file-earmark-pdf-fill" style="color:var(--role-color); font-size:1.5rem"></i>
            <div class="fw-semibold mt-2">Anamnesis Sistematis</div>
            <small class="text-muted">dr. Andi Pratama · 12 hal</small>
          </div>
        </div>
        <div class="col-md-6">
          <div class="p-3 border rounded-3">
            <i class="bi bi-play-btn-fill" style="color:var(--role-color); font-size:1.5rem"></i>
            <div class="fw-semibold mt-2">Teknik Pemeriksaan Fisik</div>
            <small class="text-muted">Video · 24 menit</small>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-lg-5">
    <div class="panel mb-3 text-center panel-sos">
        <h6 class="fw-bold mb-2 text-sos-primary"><i class="bi bi-exclamation-triangle-fill"></i> Layanan Darurat Kampus</h6>
        <p class="small text-muted mb-3">Gunakan hanya jika terjadi kondisi darurat medis.</p>
        <button id="btnSOS" class="btn btn-primary w-100 fw-bold py-2" style="border-radius: 8px; background: linear-gradient(135deg, var(--klinik-primary), var(--klinik-accent)); border:none;">
            🚨 TEKAN UNTUK DARURAT (SOS)
        </button>
        <div id="statusSOS" class="small mt-2 fw-semibold text-sos-primary"></div>
    </div>
    
    <div class="panel mb-3">
      <h6 class="fw-bold mb-3">Jadwal Praktikum Minggu Ini</h6>
      <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
        <div class="text-center" style="min-width:55px">
          <div class="fw-bold" style="color:var(--role-color)">SEN</div>
          <small class="text-muted">08:00</small>
        </div>
        <div><div class="fw-semibold">Praktikum Anatomi</div><small class="text-muted">Lab 1 · dr. Rini</small></div>
      </div>
      <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
        <div class="text-center" style="min-width:55px">
          <div class="fw-bold" style="color:var(--role-color)">RAB</div>
          <small class="text-muted">10:00</small>
        </div>
        <div><div class="fw-semibold">Observasi Klinik</div><small class="text-muted">Klinik Utama · dr. Andi</small></div>
      </div>
      <div class="d-flex gap-3">
        <div class="text-center" style="min-width:55px">
          <div class="fw-bold" style="color:var(--role-color)">JUM</div>
          <small class="text-muted">13:00</small>
        </div>
        <div><div class="fw-semibold">Simulasi Pasien</div><small class="text-muted">Skill Lab · Tim</small></div>
      </div>
    </div>
    
    <div class="panel">
      <h6 class="fw-bold mb-3">Progress Semester</h6>
      <div class="mb-3">
        <div class="d-flex justify-content-between small mb-1"><span>Kehadiran</span><strong>92%</strong></div>
        <div class="progress" style="height:8px"><div class="progress-bar" style="width:92%; background:var(--role-color)"></div></div>
      </div>
      <div class="mb-3">
        <div class="d-flex justify-content-between small mb-1"><span>Tugas Selesai</span><strong>78%</strong></div>
        <div class="progress" style="height:8px"><div class="progress-bar" style="width:78%; background:var(--role-color)"></div></div>
      </div>
      <div>
        <div class="d-flex justify-content-between small mb-1"><span>Jam Praktikum</span><strong>65%</strong></div>
        <div class="progress" style="height:8px"><div class="progress-bar" style="width:65%; background:var(--role-color)"></div></div>
      </div>
    </div>
  </div>
</div>

<?php 
include '../../includes/password_modal.php'; 
include '../../includes/dashboard_scripts.php'; 

if (function_exists('render_footer')) {
    render_footer(); 
}
?>