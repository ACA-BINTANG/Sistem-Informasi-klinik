<?php
$page_title = 'Kartu Berobat';
$role_required = 'mahasiswa';
require_once 'layout.php';

$profil = [
    'nrp'        => '2021110' . str_pad(rand(1,999), 3, '0', STR_PAD_LEFT),
    'nik'        => '3201' . rand(1000000000, 9999999999),
    'no_rm'      => 'RM-' . str_pad(rand(1,9999), 5, '0', STR_PAD_LEFT),
    'tgl_lahir'  => '15 Agustus 2003',
    'gol_darah'  => 'O+',
    'alamat'     => 'Jl. Mawar No. 12, Kel. Sukamaju, Bandung',
    'telp'       => '0812-3456-7890',
    'email'      => strtolower(str_replace(' ', '.', $name)) . '@student.ac.id',
    'asuransi'   => 'BPJS Kesehatan Kelas 2',
    'alergi'     => 'Seafood, Penisilin',
];

render_header($page_title, $role, $name, $role_meta, $menus, 5);
?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="panel" style="background:linear-gradient(135deg, var(--role-color), color-mix(in srgb, var(--role-color) 55%, #1e1b4b)); color:white;">
      <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
          <small style="opacity:.8;">KARTU BEROBAT</small>
          <h5 class="fw-bold mb-0">Klinik Sentosa</h5>
        </div>
        <i class="bi bi-heart-pulse-fill" style="font-size:2rem; opacity:.7;"></i>
      </div>
      <div class="mb-4">
        <small style="opacity:.8;">Nama Pasien</small>
        <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($name); ?></h4>
      </div>
      <div class="row mb-4">
        <div class="col-6">
          <small style="opacity:.8;">No. Rekam Medis</small>
          <div class="fw-bold"><?php echo $profil['no_rm']; ?></div>
        </div>
        <div class="col-6">
          <small style="opacity:.8;">Gol. Darah</small>
          <div class="fw-bold"><?php echo $profil['gol_darah']; ?></div>
        </div>
      </div>
      <div class="d-flex justify-content-between align-items-end">
        <div>
          <small style="opacity:.8;">Berlaku s.d.</small>
          <div class="fw-bold"><?php echo date('d/m/Y', strtotime('+2 years')); ?></div>
        </div>
        <div class="bg-white p-2 rounded">
          <div style="width:70px; height:70px; background:repeating-linear-gradient(45deg, #000 0 4px, #fff 4px 8px);"></div>
        </div>
      </div>
    </div>
    <button class="btn btn-role w-100 mt-3"><i class="bi bi-download me-1"></i>Unduh Kartu (PDF)</button>
  </div>

  <div class="col-lg-7">
    <div class="panel">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Data Diri & Medis</h6>
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</button>
      </div>
      <table class="table clean mb-0">
        <tbody>
          <?php
          $fields = [
            ['NRP / NIM',       $profil['nrp']],
            ['NIK',             $profil['nik']],
            ['Tanggal Lahir',   $profil['tgl_lahir']],
            ['Alamat',          $profil['alamat']],
            ['No. Telepon',     $profil['telp']],
            ['Email',           $profil['email']],
            ['Asuransi',        $profil['asuransi']],
            ['Riwayat Alergi',  '<span class="pill pill-danger">' . $profil['alergi'] . '</span>'],
          ];
          foreach ($fields as $f): ?>
            <tr>
              <td class="text-muted" style="width:35%"><?php echo $f[0]; ?></td>
              <td class="fw-semibold"><?php echo $f[1]; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php render_footer(); ?>
