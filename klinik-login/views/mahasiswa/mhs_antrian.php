<?php
$page_title = 'Ambil Antrian';
$role_required = 'mahasiswa';
require_once 'layout.php';

// Simulasi data antrian hari ini
$tanggal = date('d M Y');
$poli_list = [
    ['Poli Umum',        'Dr. Andi Saputra',    'A', 12, 18],
    ['Poli Gigi',        'Drg. Maya Lestari',   'B',  5,  9],
    ['Poli Anak',        'Dr. Sinta Wijaya',    'C',  8, 14],
    ['Poli Kulit',       'Dr. Reza Pratama',    'D',  3,  7],
    ['Konsultasi Gizi',  'Dr. Lina Marlina',    'E',  2,  4],
];

// Antrian aktif user (simulasi)
$antrian_saya = isset($_SESSION['antrian_mhs']) ? $_SESSION['antrian_mhs'] : null;
if (isset($_POST['ambil_antrian'])) {
    $idx = (int)$_POST['poli'];
    $p = $poli_list[$idx];
    $nomor = $p[2] . str_pad($p[4] + 1, 3, '0', STR_PAD_LEFT);
    $antrian_saya = [
        'poli'   => $p[0],
        'dokter' => $p[1],
        'nomor'  => $nomor,
        'estimasi' => date('H:i', strtotime('+' . (($p[4] - $p[3]) * 8) . ' minutes')),
        'tanggal' => $tanggal,
    ];
    $_SESSION['antrian_mhs'] = $antrian_saya;
}
if (isset($_POST['batal'])) {
    unset($_SESSION['antrian_mhs']);
    $antrian_saya = null;
}

render_header($page_title, $role, $name, $role_meta, $menus, 1);
?>

<?php if ($antrian_saya): ?>
<div class="panel mb-3" style="background:linear-gradient(135deg, var(--role-color), color-mix(in srgb, var(--role-color) 60%, white)); color:white;">
  <div class="row align-items-center">
    <div class="col-md-8">
      <small style="opacity:.85;">NOMOR ANTRIAN ANDA HARI INI</small>
      <h1 class="display-3 fw-bold mb-1"><?php echo $antrian_saya['nomor']; ?></h1>
      <div><i class="bi bi-hospital me-1"></i><?php echo htmlspecialchars($antrian_saya['poli']); ?> &middot; <?php echo htmlspecialchars($antrian_saya['dokter']); ?></div>
      <div><i class="bi bi-clock me-1"></i>Estimasi dipanggil pukul <strong><?php echo $antrian_saya['estimasi']; ?></strong> &middot; <?php echo $antrian_saya['tanggal']; ?></div>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
      <form method="post" class="d-inline">
        <button name="batal" class="btn btn-light"><i class="bi bi-x-circle me-1"></i>Batalkan Antrian</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <h6 class="fw-bold mb-1">Pilih Poli untuk Ambil Antrian</h6>
  <small class="text-muted d-block mb-3">Hari ini, <?php echo $tanggal; ?></small>
  <div class="row g-3">
    <?php foreach ($poli_list as $i => $p):
      $sisa = $p[4] - $p[3];
      $persen = ($p[3] / $p[4]) * 100; ?>
      <div class="col-md-6 col-lg-4">
        <div class="border rounded-3 p-3 h-100">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <h6 class="fw-bold mb-0"><?php echo $p[0]; ?></h6>
              <small class="text-muted"><?php echo $p[1]; ?></small>
            </div>
            <span class="pill pill-info">Loket <?php echo $p[2]; ?></span>
          </div>
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Sedang dilayani: <strong><?php echo $p[2] . str_pad($p[3], 3, '0', STR_PAD_LEFT); ?></strong></span>
            <span>Sisa: <strong><?php echo $sisa; ?></strong></span>
          </div>
          <div class="progress mb-3" style="height:6px;">
            <div class="progress-bar" style="width:<?php echo $persen; ?>%; background:var(--role-color);"></div>
          </div>
          <form method="post">
            <input type="hidden" name="poli" value="<?php echo $i; ?>">
            <button name="ambil_antrian" class="btn btn-sm btn-role w-100" <?php echo $antrian_saya ? 'disabled' : ''; ?>>
              <i class="bi bi-ticket-detailed me-1"></i>
              <?php echo $antrian_saya ? 'Antrian Sudah Diambil' : 'Ambil Antrian'; ?>
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php render_footer(); ?>
