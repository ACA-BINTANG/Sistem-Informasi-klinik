<?php
$page_title = 'Resep & Obat Saya';
$role_required = 'mahasiswa';
require_once 'layout.php';

$resep = [
    ['2026-05-28', 'Dr. Andi Saputra', [
        ['Paracetamol 500mg', '3x1 sehari sesudah makan', '10 tablet'],
        ['Amoxicillin 500mg', '3x1 sehari', '15 kapsul'],
        ['Vitamin C 1000mg',  '1x1 sehari', '10 tablet'],
    ], 'Diambil'],
    ['2026-04-12', 'Drg. Maya Lestari', [
        ['Asam Mefenamat 500mg', '3x1 jika nyeri', '10 tablet'],
        ['Obat Kumur Antiseptik', '2x sehari kumur', '1 botol'],
    ], 'Diambil'],
    ['2026-03-02', 'Dr. Reza Pratama', [
        ['Krim Tretinoin 0.025%', 'Oleskan malam hari', '1 tube'],
        ['Sunscreen SPF 50',      'Pagi sebelum aktivitas', '1 botol'],
    ], 'Diambil'],
];

render_header($page_title, $role, $name, $role_meta, $menus, 4);
?>

<div class="row g-3">
  <?php foreach ($resep as $r): ?>
    <div class="col-lg-6">
      <div class="panel h-100">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h6 class="fw-bold mb-0"><i class="bi bi-capsule me-2" style="color:var(--role-color);"></i>Resep <?php echo date('d M Y', strtotime($r[0])); ?></h6>
            <small class="text-muted">Diresepkan oleh <?php echo $r[1]; ?></small>
          </div>
          <span class="pill pill-success"><?php echo $r[3]; ?></span>
        </div>
        <table class="table clean mb-3">
          <thead><tr><th>Nama Obat</th><th>Aturan Pakai</th><th class="text-end">Jumlah</th></tr></thead>
          <tbody>
            <?php foreach ($r[2] as $o): ?>
              <tr>
                <td class="fw-semibold"><?php echo $o[0]; ?></td>
                <td class="small text-muted"><?php echo $o[1]; ?></td>
                <td class="text-end"><?php echo $o[2]; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-secondary flex-fill"><i class="bi bi-printer me-1"></i>Cetak</button>
          <button class="btn btn-sm btn-role flex-fill"><i class="bi bi-arrow-clockwise me-1"></i>Minta Resep Ulang</button>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php render_footer(); ?>
