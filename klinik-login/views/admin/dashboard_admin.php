<?php
$role_required = 'admin';
$page_title = 'Dashboard Admin';
include '../../includes/layout.php';
render_header($page_title, $_SESSION['role'], $_SESSION['name'] ?? $_SESSION['user'], null, null, 0);
?>

<div class="row g-3 mb-3">
  <?php
  $stats = [
    ['Total Pengguna', '1,248', 'bi-people-fill', '#0d9488', '#ccfbf1'],
    ['Total Pasien', '3,456', 'bi-person-vcard-fill', '#0891b2', '#cffafe'],
    ['Total Dokter', '28', 'bi-clipboard2-pulse-fill', '#7c3aed', '#ede9fe'],
    ['Pendapatan Bulan Ini', 'Rp 142 Jt', 'bi-cash-coin', '#16a34a', '#dcfce7'],
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
  <div class="col-lg-8">
    <div class="panel">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Aktivitas Pengguna Terbaru</h6>
        <a href="#" class="small text-decoration-none" style="color:var(--role-color)">Lihat semua</a>
      </div>
      <table class="table clean">
        <thead><tr><th>Nama</th><th>Role</th><th>Aksi</th><th>Waktu</th></tr></thead>
        <tbody>
          <tr><td>dr. Andi Pratama</td><td><span class="pill pill-info">Dokter</span></td><td>Update rekam medis</td><td class="text-muted">5 menit lalu</td></tr>
          <tr><td>Siti Aminah</td><td><span class="pill pill-success">Pasien</span></td><td>Daftar antrian</td><td class="text-muted">12 menit lalu</td></tr>
          <tr><td>Budi Santoso</td><td><span class="pill pill-warn">Mahasiswa</span></td><td>Submit laporan</td><td class="text-muted">30 menit lalu</td></tr>
          <tr><td>dr. Rini Hasanah</td><td><span class="pill pill-info">Dokter</span></td><td>Buat resep obat</td><td class="text-muted">1 jam lalu</td></tr>
          <tr><td>Admin Klinik</td><td><span class="pill" style="background:#fee2e2;color:#b91c1c">Admin</span></td><td>Tambah dokter baru</td><td class="text-muted">2 jam lalu</td></tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel mb-3">
      <h6 class="fw-bold mb-3">Aksi Cepat</h6>
      <div class="d-grid gap-2">
        <a href="#" class="btn btn-light text-start"><i class="bi bi-person-plus me-2"></i>Tambah Pengguna</a>
        <a href="#" class="btn btn-light text-start"><i class="bi bi-plus-circle me-2"></i>Tambah Dokter</a>
        <a href="#" class="btn btn-light text-start"><i class="bi bi-file-earmark-bar-graph me-2"></i>Generate Laporan</a>
        <a href="#" class="btn btn-light text-start"><i class="bi bi-megaphone me-2"></i>Broadcast Pengumuman</a>
      </div>
    </div>
    <div class="panel">
      <h6 class="fw-bold mb-3">Status Sistem</h6>
      <div class="d-flex justify-content-between mb-2"><span class="text-muted small">Server</span><span class="pill pill-success">Online</span></div>
      <div class="d-flex justify-content-between mb-2"><span class="text-muted small">Database</span><span class="pill pill-success">Normal</span></div>
      <div class="d-flex justify-content-between"><span class="text-muted small">Backup Terakhir</span><span class="small">2 jam lalu</span></div>
    </div>
  </div>
</div>

<?php render_footer(); ?>
