<?php
// Ambil alamat dari pusat config (Naik 2 folder)
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once ROOT_PATH . 'config/koneksi.php';
require_once ROOT_PATH . 'includes/layout.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman
if (!isset($_SESSION['user']) || strtolower($_SESSION['role']) !== 'mahasiswa') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$role = $_SESSION['role'];
$name = $_SESSION['name'];
$page_title = 'Riwayat Berobat';
$role_required = 'mahasiswa';

// 1. Ambil ID Pasien (Mahasiswa) secara dinamis dari session
$username_login = $_SESSION['user'];
$query_id = mysqli_query($koneksi, "SELECT id FROM staff WHERE username = '$username_login'");
$data_mhs = mysqli_fetch_assoc($query_id);
$id_pasien_login = $data_mhs['id'] ?? ''; 

// 2. Perbaiki Query: Pakai tabel 'staff' dan join ke 'id', bukan 'id_user'
$query = "SELECT rm.tanggal, d.nama AS nama_dokter, rm.keluhan, rm.status, rm.biaya 
          FROM rekam_medis rm 
          JOIN staff d ON rm.id_dokter = d.id 
          WHERE rm.id_pasien = ? 
          ORDER BY rm.tanggal DESC";

$stmt = $koneksi->prepare($query);
// 3. Ubah 'i' menjadi 's' karena ID sekarang VARCHAR (String)
$stmt->bind_param("s", $id_pasien_login);
$stmt->execute();
$result = $stmt->get_result();

$riwayat = [];
$total_kunjungan = 0;
$total_biaya = 0;
$kunjungan_terakhir = '-';

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $total_kunjungan++;
        $total_biaya += $row['biaya'];
        
        if ($total_kunjungan == 1) {
            $kunjungan_terakhir = date('d M Y', strtotime($row['tanggal']));
        }

        $riwayat[] = [
            $row['tanggal'],
            'Poli Umum', // Default karena kolom spesialis tidak ada di tabel staff
            $row['nama_dokter'],
            $row['keluhan'],
            $row['status'],
            'Rp ' . number_format($row['biaya'], 0, ',', '.')
        ];
    }
}
$stmt->close();

// Panggil fungsi header utama proyek dengan parameter aman null
render_header($page_title, $role, $name, null, null, 2);
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="stat-card">
      <div class="icon-wrap" style="background:#ede9fe; color:#7c3aed;"><i class="bi bi-clipboard2-pulse-fill"></i></div>
      <h3><?php echo $total_kunjungan; ?></h3>
      <div class="label">Total Kunjungan</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="icon-wrap" style="background:#dcfce7; color:#15803d;"><i class="bi bi-cash-coin"></i></div>
      <h3>Rp <?php echo number_format($total_biaya, 0, ',', '.'); ?></h3>
      <div class="label">Total Pengeluaran</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="icon-wrap" style="background:#dbeafe; color:#1d4ed8;"><i class="bi bi-calendar-check"></i></div>
      <h3><?php echo $kunjungan_terakhir; ?></h3>
      <div class="label">Kunjungan Terakhir</div>
    </div>
  </div>
</div>

<div class="panel">
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div>
      <h6 class="fw-bold mb-0">Riwayat Kunjungan Berobat</h6>
      <small class="text-muted">Data berobat Anda di Klinik Sentosa</small>
    </div>
    <div class="d-flex gap-2">
      <div class="input-group input-group-sm" style="width:240px">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" placeholder="Cari..." onkeyup="filterTable(this)">
      </div>
      <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Unduh PDF</button>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table clean" id="masterTable">
      <thead><tr>
        <th>Tanggal</th><th>Poli</th><th>Dokter</th><th>Keluhan / Diagnosa</th><th>Status</th><th class="text-end">Biaya</th><th class="text-end">Aksi</th>
      </tr></thead>
      <tbody>
        <?php foreach ($riwayat as $r): ?>
        <tr>
          <td><?php echo date('d M Y', strtotime($r[0])); ?></td>
          <td><?php echo htmlspecialchars($r[1]); ?></td>
          <td><?php echo htmlspecialchars($r[2]); ?></td>
          <td><?php echo htmlspecialchars($r[3]); ?></td>
          <td><span class="pill pill-success"><?php echo htmlspecialchars($r[4]); ?></span></td>
          <td class="text-end"><?php echo htmlspecialchars($r[5]); ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-light" title="Detail"><i class="bi bi-eye"></i></button>
            <button class="btn btn-sm btn-light" title="Cetak"><i class="bi bi-printer"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($riwayat)): ?>
        <tr><td colspan="7" class="text-center text-muted">Belum ada riwayat berobat.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
function filterTable(input){
  const q = input.value.toLowerCase();
  document.querySelectorAll('#masterTable tbody tr').forEach(tr => {
    tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>

<?php render_footer(); ?>