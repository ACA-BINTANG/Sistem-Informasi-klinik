<?php
// Shared layout helpers. Pages must define $page_title, $role_required, then include this file at top.
if (session_status() === PHP_SESSION_NONE) session_start();

// Amankan Base URL agar semua aset CSS & Bootstrap nembak ke pusat mutlak
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/klinik-login/'); 
}

if (!isset($_SESSION['user']) || (isset($role_required) && strtolower($_SESSION['role']) !== strtolower($role_required))) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$role = strtolower($_SESSION['role']);
$name = $_SESSION['name'] ?? $_SESSION['user'];

$role_meta_arr = [
    'admin'     => ['label' => 'Administrator', 'icon' => 'bi-shield-lock-fill',     'color' => '#0d9488'],
    'dokter'    => ['label' => 'Dokter',        'icon' => 'bi-clipboard2-pulse-fill','color' => '#0891b2'],
    'mahasiswa' => ['label' => 'Mahasiswa',      'icon' => 'bi-mortarboard-fill',     'color' => '#7c3aed'],
];

$menus_arr = [
    'admin' => [
        ['dashboard_admin.php',  'bi-speedometer2',          'Dashboard'],
        ['admin_pengguna.php',   'bi-people-fill',           'Kelola Pengguna'],
        ['admin_dokter.php',     'bi-person-badge',          'Data Dokter'],
        ['admin_pasien.php',     'bi-person-vcard',          'Data Pasien'],
        ['admin_keuangan.php',   'bi-cash-coin',             'Keuangan'],
        ['admin_laporan.php',    'bi-graph-up',              'Laporan'],
        ['admin_pengaturan.php', 'bi-gear-fill',             'Pengaturan'],
    ],
    'dokter' => [
        ['dashboard_dokter.php', 'bi-speedometer2',           'Dashboard'],
        ['dokter_jadwal.php',    'bi-calendar-check-fill',    'Jadwal Praktik'],
        ['dokter_pasien.php',    'bi-people-fill',            'Daftar Pasien'],
        ['dokter_rekam.php',     'bi-clipboard2-pulse-fill',  'Rekam Medis'],
        ['dokter_resep.php',     'bi-capsule',                'Resep Obat'],
        ['dokter_konsultasi.php','bi-chat-dots-fill',         'Konsultasi'],
    ],
    'mahasiswa' => [
        ['dashboard_mahasiswa.php', 'bi-speedometer2',         'Dashboard'],
        ['mhs_antrian.php',         'bi-ticket-detailed-fill', 'Ambil Antrian'],
        ['mhs_riwayat.php',         'bi-clock-history',        'Riwayat Berobat'],
        ['mhs_jadwal.php',          'bi-calendar-week-fill',   'Jadwal Dokter'],
        ['mhs_resep.php',           'bi-capsule',              'Resep & Obat'],
        ['mhs_profil.php',          'bi-person-vcard-fill',    'Kartu Berobat'],
    ],
];

function render_header($page_title, $role, $name, $unused_meta = null, $unused_menus = null, $active = 0) {
    global $role_meta_arr, $menus_arr;
    $role = strtolower($role);

    // Detektor Otomatis: Cek apakah folder proyek lo pakai nama 'views' atau 'wiews' (typo)
    $view_folder = 'views';
    if (is_dir(dirname(dirname(__DIR__)) . '/wiews')) {
        $view_folder = 'wiews';
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?php echo htmlspecialchars($page_title); ?> - Klinik Sehat Sentosa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --role-color: <?php echo $role_meta_arr[$role]['color'] ?? '#7c3aed'; ?>; }
  body { font-family:'Segoe UI', system-ui, sans-serif; background:#f1f5f9; min-height:100vh; }
  .sidebar { width:260px; min-height:100vh; background:white; position:fixed; left:0; top:0;
    border-right:1px solid #e2e8f0; padding:1.5rem 1rem; display:flex; flex-direction:column; }
  .sidebar-brand { display:flex; align-items:center; gap:.75rem; padding:.5rem; margin-bottom:1.5rem; }
  .sidebar-brand .logo { width:42px; height:42px; border-radius:12px;
    background:linear-gradient(135deg, var(--role-color), color-mix(in srgb, var(--role-color) 60%, white));
    color:white; display:flex; align-items:center; justify-content:center; font-size:1.2rem; }
  .sidebar-brand h6 { margin:0; font-weight:700; color:#0f172a; }
  .sidebar-brand small { color:#64748b; }
  .nav-item-custom { display:flex; align-items:center; gap:.8rem; padding:.7rem .9rem;
    color:#475569; border-radius:.6rem; text-decoration:none; margin-bottom:.25rem; font-size:.92rem;
    transition: all .15s; }
  .nav-item-custom:hover { background:#f1f5f9; color:#0f172a; }
  .nav-item-custom.active { background:var(--role-color); color:white; box-shadow:0 6px 16px -8px var(--role-color); }
  .nav-item-custom i { font-size:1.05rem; }
  .nav-section { font-size:.7rem; text-transform:uppercase; color:#94a3b8; font-weight:700;
    padding:.5rem .9rem; letter-spacing:.05em; }
  .main { margin-left:260px; padding:1.5rem 2rem; }
  .topbar { background:white; border-radius:1rem; padding:1rem 1.5rem;
    display:flex; align-items:center; justify-content:space-between;
    box-shadow:0 1px 3px rgba(0,0,0,.04); margin-bottom:1.5rem; }
  .user-chip { display:flex; align-items:center; gap:.75rem; }
  .avatar { width:42px; height:42px; border-radius:50%;
    background:linear-gradient(135deg, var(--role-color), color-mix(in srgb, var(--role-color) 50%, white));
    color:white; display:flex; align-items:center; justify-content:center; font-weight:700; }
  .stat-card { background:white; border:none; border-radius:1rem; padding:1.25rem;
    box-shadow:0 1px 3px rgba(0,0,0,.04); height:100%; }
  .stat-card .icon-wrap { width:48px; height:48px; border-radius:.8rem;
    display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
  .stat-card h3 { font-weight:700; margin:.5rem 0 .1rem; }
  .stat-card .label { color:#64748b; font-size:.85rem; }
  .panel { background:white; border-radius:1rem; padding:1.5rem;
    box-shadow:0 1px 3px rgba(0,0,0,.04); }
  .badge-role { background:var(--role-color); color:white; padding:.25rem .6rem;
    border-radius:.4rem; font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; }
  table.clean { font-size:.9rem; }
  table.clean th { color:#64748b; font-weight:600; font-size:.75rem;
    text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e2e8f0; }
  table.clean td { border-bottom:1px solid #f1f5f9; padding:.75rem .5rem; vertical-align:middle; }
  .pill { padding:.2rem .6rem; border-radius:1rem; font-size:.75rem; font-weight:600; }
  .pill-success { background:#dcfce7; color:#15803d; }
  .pill-warn { background:#fef3c7; color:#a16207; }
  .pill-info { background:#dbeafe; color:#1d4ed8; }
  .pill-danger { background:#fee2e2; color:#b91c1c; }
  .btn-role { background:var(--role-color); color:white; border:none; }
  .btn-role:hover { filter:brightness(.92); color:white; }
  @media (max-width: 768px) {
    .sidebar { transform:translateX(-100%); transition:.3s; z-index:1000; }
    .sidebar.show { transform:translateX(0); }
    .main { margin-left:0; padding:1rem; }
  }
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="logo"><i class="bi bi-heart-pulse-fill"></i></div>
    <div>
      <h6>Klinik Sentosa</h6>
      <small><?php echo $role_meta_arr[$role]['label'] ?? 'Panel'; ?> Panel</small>
    </div>
  </div>
  <div class="nav-section">Menu Utama</div>
  <?php if(isset($menus_arr[$role])): ?>
    <?php foreach ($menus_arr[$role] as $i => $m): ?>
      <a href="<?php echo BASE_URL . $view_folder . '/' . $role . '/' . $m[0]; ?>" class="nav-item-custom <?php echo $i===$active?'active':''; ?>">
        <i class="bi <?php echo $m[1]; ?>"></i><span><?php echo $m[2]; ?></span>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
  <div class="mt-auto pt-3">
    <a href="<?php echo BASE_URL; ?>logout.php" class="nav-item-custom text-danger">
      <i class="bi bi-box-arrow-right"></i><span>Keluar</span>
    </a>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div>
      <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($page_title); ?></h5>
      <small class="text-muted">Selamat datang kembali, <?php echo htmlspecialchars($name); ?> 👋</small>
    </div>
    <div class="user-chip">
      <span class="badge-role"><?php echo $role_meta_arr[$role]['label'] ?? 'User'; ?></span>
      <div class="avatar"><?php echo strtoupper(substr($name,0,1)); ?></div>
      <div class="d-none d-md-block">
        <div class="fw-semibold small"><?php echo htmlspecialchars($name); ?></div>
        <small class="text-muted">@<?php echo htmlspecialchars($_SESSION['user']); ?></small>
      </div>
    </div>
  </div>
<?php
}

function render_footer() {
?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}

function render_master_page($title, $columns, $rows, $add_label = 'Tambah Data') {
?>
<div class="panel">
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div>
      <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($title); ?></h6>
      <small class="text-muted"><?php echo count($rows); ?> data ditemukan</small>
    </div>
    <div class="d-flex gap-2">
      <div class="input-group input-group-sm" style="width:240px">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" placeholder="Cari..." onkeyup="filterTable(this)">
      </div>
      <button class="btn btn-sm btn-role"><i class="bi bi-plus-lg me-1"></i><?php echo htmlspecialchars($add_label); ?></button>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table clean" id="masterTable">
      <thead><tr>
        <?php foreach ($columns as $c): ?><th><?php echo htmlspecialchars($c); ?></th><?php endforeach; ?>
        <th class="text-end">Aksi</th>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <?php foreach ($r as $cell): ?><td><?php echo $cell; ?></td><?php endforeach; ?>
            <td class="text-end">
              <button class="btn btn-sm btn-light" title="Lihat"><i class="bi bi-eye"></i></button>
              <button class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-light text-danger" title="Hapus"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
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
<?php
}
?>