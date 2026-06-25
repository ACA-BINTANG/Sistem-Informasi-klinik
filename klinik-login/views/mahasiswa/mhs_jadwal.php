<?php
$page_title = 'Jadwal Dokter';
$role_required = 'mahasiswa';
require_once 'layout.php';

$jadwal = [
    ['Dr. Andi Saputra',    'Poli Umum',       'Senin - Jumat', '07:30 - 16:30', 'Tersedia'],
];

render_header($page_title, $role, $name, $role_meta, $menus, 3);
?>

<div class="panel">
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div>
      <h6 class="fw-bold mb-0">Jadwal Praktik Dokter</h6>
      <small class="text-muted">Cek jadwal sebelum mengambil antrian</small>
    </div>
    <div class="input-group input-group-sm" style="width:240px">
      <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
      <input type="text" class="form-control" placeholder="Cari dokter / poli..." onkeyup="filterTable(this)">
    </div>
  </div>
  <div class="row g-3" id="masterTable">
    <?php foreach ($jadwal as $j): $aktif = $j[4] === 'Tersedia'; ?>
      <div class="col-md-6 col-lg-4">
        <div class="border rounded-3 p-3 h-100 d-flex flex-column">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="avatar" style="background:linear-gradient(135deg, var(--role-color), color-mix(in srgb, var(--role-color) 50%, white)); width:48px; height:48px;">
              <?php echo strtoupper(substr($j[0], strpos($j[0], '.')+2, 1)); ?>
            </div>
            <div>
              <h6 class="fw-bold mb-0"><?php echo $j[0]; ?></h6>
              <small class="text-muted"><?php echo $j[1]; ?></small>
            </div>
          </div>
          <div class="small mb-2"><i class="bi bi-calendar3 me-2 text-muted"></i><?php echo $j[2]; ?></div>
          <div class="small mb-3"><i class="bi bi-clock me-2 text-muted"></i><?php echo $j[3]; ?></div>
          <div class="mt-auto d-flex justify-content-between align-items-center">
            <span class="pill <?php echo $aktif ? 'pill-success' : 'pill-warn'; ?>"><?php echo $j[4]; ?></span>
            <a href="mhs_antrian.php" class="btn btn-sm btn-role <?php echo $aktif ? '' : 'disabled'; ?>">
              <i class="bi bi-ticket me-1"></i>Ambil Antrian
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<script>
function filterTable(input){
  const q = input.value.toLowerCase();
  document.querySelectorAll('#masterTable > div').forEach(c => {
    c.style.display = c.innerText.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>

<?php render_footer(); ?>
