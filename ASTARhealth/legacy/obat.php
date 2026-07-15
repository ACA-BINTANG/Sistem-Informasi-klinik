<?php
session_start();
require_once dirname(__DIR__) . '/config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Dokter') {
    header("Location: ../auth/login.php?pesan=Akses Ditolak!");
    exit;
}

$doctor_name = $_SESSION['nama_lengkap'] ?? 'Dokter';
$user_id = $_SESSION['id_user'] ?? '';

$qStaff = mysqli_query($conn, "SELECT id_staff FROM staffm WHERE id_user = '$user_id'");
$dStaff = mysqli_fetch_assoc($qStaff);
$id_dokter = $dStaff['id_staff'] ?? '';

if (isset($_POST['add_obat'])) {
    $nama_obat = mysqli_real_escape_string($conn, $_POST['nama_obat']);
    $satuan = mysqli_real_escape_string($conn, $_POST['satuan']);
    $stok_sekarang = (int) $_POST['stok_sekarang'];
    $stok_minimum = (int) $_POST['stok_minimum'];
    $stok_target = (int) $_POST['stok_target'];
    $harga_per_pcs = (float) $_POST['harga_per_pcs'];

    if ($stok_sekarang < 0 || $stok_minimum < 0 || $stok_target < 0 || $harga_per_pcs < 0) {
        header("Location: obat.php?err=Input tidak boleh minus");
        exit;
    }

    if ($stok_target < $stok_minimum) {
        header("Location: obat.php?err=Stok target harus lebih besar dari stok minimum");
        exit;
    }

    mysqli_query($conn, "
        INSERT INTO obatm 
        (nama_obat, satuan, stok_sekarang, stok_minimum, stok_target, harga_per_pcs)
        VALUES 
        ('$nama_obat', '$satuan', '$stok_sekarang', '$stok_minimum', '$stok_target', '$harga_per_pcs')
    ");

    header("Location: obat.php?msg=Obat berhasil ditambahkan");
    exit;
}

if (isset($_POST['update_obat'])) {
    $id_obat = mysqli_real_escape_string($conn, $_POST['id_obat']);
    $nama_obat = mysqli_real_escape_string($conn, $_POST['nama_obat']);
    $satuan = mysqli_real_escape_string($conn, $_POST['satuan']);
    $stok_sekarang = (int) $_POST['stok_sekarang'];
    $stok_minimum = (int) $_POST['stok_minimum'];
    $stok_target = (int) $_POST['stok_target'];
    $harga_per_pcs = (float) $_POST['harga_per_pcs'];

    if ($stok_sekarang < 0 || $stok_minimum < 0 || $stok_target < 0 || $harga_per_pcs < 0) {
        header("Location: obat.php?err=Input tidak boleh minus");
        exit;
    }

    if ($stok_target < $stok_minimum) {
        header("Location: obat.php?err=Stok target harus lebih besar dari stok minimum");
        exit;
    }

    mysqli_query($conn, "
        UPDATE obatm SET
            nama_obat='$nama_obat',
            satuan='$satuan',
            stok_sekarang='$stok_sekarang',
            stok_minimum='$stok_minimum',
            stok_target='$stok_target',
            harga_per_pcs='$harga_per_pcs'
        WHERE id_obat='$id_obat'
    ");

    header("Location: obat.php?msg=Obat berhasil diperbarui");
    exit;
}

if (isset($_GET['hapus'])) {
    $id_obat = mysqli_real_escape_string($conn, $_GET['hapus']);

    mysqli_query($conn, "DELETE FROM obatm WHERE id_obat='$id_obat'");

    header("Location: obat.php?msg=Obat berhasil dihapus");
    exit;
}

$data_obat = mysqli_query($conn, "
    SELECT 
        id_obat,
        nama_obat,
        satuan,
        stok_sekarang,
        stok_minimum,
        stok_target,
        harga_per_pcs
    FROM obatm
    ORDER BY nama_obat ASC
");

if (!$data_obat) {
    die("Query gagal: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Master Obat - ASTARhealth</title>

  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    :root { 
        --astar-blue: #0057B8; 
        --astar-soft-blue: #eef4ff; 
        --sidebar-bg: #ffffff; 
    }

    body { 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        background-color: #f4f7fa; 
        color: #334155; 
    }

    .top-header { 
        height: 70px; 
        background: var(--astar-blue); 
        display: flex; 
        align-items: center; 
        justify-content: space-between; 
        padding: 0 30px; 
        color: white; 
        position: fixed; 
        top: 0; 
        width: 100%; 
        z-index: 1001; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
    }

    #digitalClock { 
        font-weight: 600; 
        font-size: 14px; 
        background: rgba(255,255,255,0.1); 
        padding: 5px 15px; 
        border-radius: 50px; 
    }

    .sidebar { 
        width: 280px; 
        background: var(--sidebar-bg); 
        height: 100vh; 
        position: fixed; 
        top: 70px; 
        border-right: 1px solid #e2e8f0; 
        z-index: 1000; 
        padding: 20px 0; 
        transition: all 0.3s ease; 
        overflow-y: auto; 
    }

    .main-content { 
        margin-left: 280px; 
        padding: 100px 40px 40px; 
        transition: all 0.3s ease; 
        animation: fadeIn 0.5s ease; 
    }

    body.sidebar-toggled .sidebar { left: -280px; }
    body.sidebar-toggled .main-content { margin-left: 0; }

    @media (max-width: 768px) {
        .sidebar { left: -280px; }
        .main-content { margin-left: 0; }
        body.sidebar-toggled .sidebar { left: 0; }
    }

    #sidebarToggle { 
        cursor: pointer; 
        font-size: 1.5rem; 
    }

    .nav-group-title { 
        font-size: 11px; 
        text-transform: uppercase; 
        color: #94a3b8; 
        font-weight: 800; 
        padding: 20px 25px 8px; 
        letter-spacing: 1px; 
    }

    .nav-link { 
        padding: 12px 25px; 
        color: #64748b; 
        font-weight: 500; 
        display: flex; 
        align-items: center; 
        transition: 0.2s; 
        text-decoration: none; 
        font-size: 14px; 
        margin: 0 15px; 
        border-radius: 10px; 
    }

    .nav-link i { 
        font-size: 1.2rem; 
        width: 35px; 
    }

    .nav-link:hover { 
        background: var(--astar-soft-blue); 
        color: var(--astar-blue); 
    }

    .nav-link.active { 
        background: var(--astar-blue); 
        color: #fff; 
        box-shadow: 0 4px 12px rgba(0,87,184,0.3); 
    }

    .data-container { 
        background: white; 
        border-radius: 20px; 
        padding: 30px; 
        box-shadow: 0 10px 25px rgba(0,0,0,0.02); 
        border: 1px solid #f1f5f9; 
    }

    .table thead th { 
        background: #f8fafc; 
        color: #64748b; 
        font-weight: 700; 
        text-transform: uppercase; 
        font-size: 11px; 
        padding: 15px; 
        border: none; 
    }

    .badge-stok-aman { 
        background: rgba(25,135,84,0.1); 
        color: #198754; 
    }

    .badge-stok-tipis { 
        background: rgba(255,193,7,0.15); 
        color: #856404; 
    }

    .badge-stok-habis { 
        background: rgba(220,53,69,0.1); 
        color: #dc3545; 
    }

    @keyframes fadeIn { 
        from { opacity: 0; transform: translateY(10px); } 
        to { opacity: 1; transform: translateY(0); } 
    }
  </style>
</head>

<body>

<header class="top-header">
  <div class="d-flex align-items-center gap-3">
    <div id="sidebarToggle" class="text-white">
      <i class="bi bi-list"></i>
    </div>

    <img src="../assets/img/logoA.png" style="max-height:70px;filter:brightness(0) invert(1);">

    <div id="digitalClock" class="d-none d-md-block text-white fw-bold"></div>
  </div>

  <div class="dropdown">
    <a href="#" data-bs-toggle="dropdown" class="text-white text-decoration-none d-flex align-items-center gap-3">
      <div class="text-end d-none d-sm-block lh-1">
        <div class="fw-bold mb-1">dr. <?= htmlspecialchars($doctor_name) ?></div>
        <small style="opacity:.8;font-size:11px;">ID Staff: <?= htmlspecialchars($id_dokter) ?></small>
      </div>

      <i class="bi bi-person-circle fs-2 text-white"></i>
    </a>

    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-2" style="border-radius:12px;">
      <li>
        <a class="dropdown-item rounded-2 text-danger fw-bold" href="index.php">
          <i class="bi bi-box-arrow-right me-2"></i> Keluar
        </a>
      </li>
    </ul>
  </div>
</header>

<div class="sidebar">
  <div class="nav-group-title">Layanan Medis</div>

  <nav class="nav flex-column">
    <a class="nav-link" href="../dokter/index.php?page=antrean">
      <i class="bi bi-people-fill"></i> Antrean Pasien
    </a>

    <a class="nav-link" href="../dokter/index.php?page=rekam_medis">
      <i class="bi bi-file-earmark-medical-fill"></i> Rekam Medis
    </a>

    <a class="nav-link" href="../dokter/index.php?page=rujukan">
      <i class="bi bi-file-medical-fill"></i> Rujukan Mandiri
    </a>

    <a class="nav-link active" href="obat.php">
      <i class="bi bi-capsule-pill"></i> Obat
    </a>
  </nav>

  <div class="nav-group-title">Referensi Kampus</div>

  <nav class="nav flex-column">
    <a class="nav-link" href="../dokter/index.php?page=pasien">
      <i class="bi bi-person-badge-fill"></i> Data Pasien
    </a>

    <a class="nav-link" href="../dokter/index.php?page=diagnosa">
      <i class="bi bi-journal-medical"></i> Database Penyakit
    </a>
  </nav>
</div>

<main class="main-content">
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-0">Master Obat Klinik</h4>
      <small class="text-muted">Kelola data obat, stok minimum, target restock, dan harga pengadaan</small>
    </div>

    <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold"
            data-bs-toggle="modal"
            data-bs-target="#mAddObat">
      <i class="bi bi-plus-lg me-2"></i> Tambah Obat
    </button>
  </div>

  <div class="data-container">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Obat</th>
            <th>Satuan</th>
            <th class="text-center">Stok</th>
            <th class="text-center">Minimum</th>
            <th class="text-center">Target</th>
            <th class="text-center">Restock</th>
            <th class="text-end">Harga/pcs</th>
            <th class="text-end">Estimasi</th>
            <th class="text-center">Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <?php if(mysqli_num_rows($data_obat) == 0): ?>
            <tr>
              <td colspan="11" class="text-center py-4 text-muted">
                Belum ada data obat
              </td>
            </tr>
          <?php endif; ?>

          <?php $no = 1; while($o = mysqli_fetch_assoc($data_obat)): ?>
            <?php
              $restock = max(0, $o['stok_target'] - $o['stok_sekarang']);
              $estimasi = $restock * $o['harga_per_pcs'];

              if ($o['stok_sekarang'] <= 0) {
                  $badgeClass = 'badge-stok-habis';
                  $label = 'Habis';
              } elseif ($o['stok_sekarang'] <= $o['stok_minimum']) {
                  $badgeClass = 'badge-stok-tipis';
                  $label = 'Auto Order';
              } else {
                  $badgeClass = 'badge-stok-aman';
                  $label = 'Aman';
              }
            ?>

            <tr>
              <td class="text-muted small"><?= $no++ ?></td>

              <td class="fw-bold text-primary">
                <?= htmlspecialchars($o['nama_obat']) ?>
              </td>

              <td>
                <span class="badge bg-light text-dark border">
                  <?= htmlspecialchars($o['satuan']) ?>
                </span>
              </td>

              <td class="text-center fw-bold">
                <?= htmlspecialchars($o['stok_sekarang']) ?>
              </td>

              <td class="text-center text-muted">
                <?= htmlspecialchars($o['stok_minimum']) ?>
              </td>

              <td class="text-center text-muted">
                <?= htmlspecialchars($o['stok_target']) ?>
              </td>

              <td class="text-center <?= ($restock > 0) ? 'text-warning fw-bold' : 'text-muted' ?>">
                <?= $restock ?>
              </td>

              <td class="text-end">
                Rp <?= number_format($o['harga_per_pcs'], 0, ',', '.') ?>
              </td>

              <td class="text-end">
                Rp <?= number_format($estimasi, 0, ',', '.') ?>
              </td>

              <td class="text-center">
                <span class="badge <?= $badgeClass ?> px-3 rounded-pill fw-bold">
                  <?= $label ?>
                </span>
              </td>

              <td class="text-center">
                <button class="btn btn-sm btn-light text-warning me-1"
                        data-bs-toggle="modal"
                        data-bs-target="#mEditObat<?= $o['id_obat'] ?>">
                  <i class="bi bi-pencil-square"></i>
                </button>

                <a href="obat.php?hapus=<?= $o['id_obat'] ?>"
                   class="btn btn-sm btn-light text-danger js-swal-confirm"
                   data-swal-title="Hapus Obat?" data-swal-text="Data obat akan dihapus permanen." data-swal-confirm="Ya, Hapus">
                  <i class="bi bi-trash3"></i>
                </a>
              </td>
            </tr>

            <div class="modal fade" id="mEditObat<?= $o['id_obat'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content border-0 shadow-lg" style="border-radius:24px" method="POST">
                  <input type="hidden" name="id_obat" value="<?= htmlspecialchars($o['id_obat']) ?>">

                  <div class="modal-header bg-warning text-white border-0 py-4">
                    <h5 class="fw-bold mb-0">
                      <i class="bi bi-pencil-square me-2"></i>Edit Data Obat
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>

                  <div class="modal-body p-4 text-start">
                    <div class="mb-3">
                      <label class="small fw-bold text-muted">NAMA OBAT</label>
                      <input type="text" name="nama_obat" class="form-control bg-light border-0 py-2" value="<?= htmlspecialchars($o['nama_obat']) ?>" required>
                    </div>

                    <div class="mb-3">
                      <label class="small fw-bold text-muted">SATUAN</label>
                      <select name="satuan" class="form-select bg-light border-0" required>
                        <option value="Tablet" <?= ($o['satuan'] == 'Tablet') ? 'selected' : '' ?>>Tablet</option>
                        <option value="Kapsul" <?= ($o['satuan'] == 'Kapsul') ? 'selected' : '' ?>>Kapsul</option>
                        <option value="Botol" <?= ($o['satuan'] == 'Botol') ? 'selected' : '' ?>>Botol</option>
                        <option value="Strip" <?= ($o['satuan'] == 'Strip') ? 'selected' : '' ?>>Strip</option>
                        <option value="Ampul" <?= ($o['satuan'] == 'Ampul') ? 'selected' : '' ?>>Ampul</option>
                        <option value="Sachet" <?= ($o['satuan'] == 'Sachet') ? 'selected' : '' ?>>Sachet</option>
                        <option value="Tube" <?= ($o['satuan'] == 'Tube') ? 'selected' : '' ?>>Tube</option>
                      </select>
                    </div>

                    <div class="row g-3">
                      <div class="col-4">
                        <label class="small fw-bold text-muted">STOK</label>
                        <input type="number" name="stok_sekarang" class="form-control bg-light border-0" value="<?= htmlspecialchars($o['stok_sekarang']) ?>" min="0" required>
                      </div>

                      <div class="col-4">
                        <label class="small fw-bold text-muted">MINIMUM</label>
                        <input type="number" name="stok_minimum" class="form-control bg-light border-0" value="<?= htmlspecialchars($o['stok_minimum']) ?>" min="0" required>
                      </div>

                      <div class="col-4">
                        <label class="small fw-bold text-muted">TARGET</label>
                        <input type="number" name="stok_target" class="form-control bg-light border-0" value="<?= htmlspecialchars($o['stok_target']) ?>" min="0" required>
                      </div>
                    </div>

                    <div class="mt-3">
                      <label class="small fw-bold text-muted">HARGA / PCS (Rp)</label>
                      <input type="number" name="harga_per_pcs" class="form-control bg-light border-0" value="<?= htmlspecialchars($o['harga_per_pcs']) ?>" min="0" step="0.01" required>
                    </div>
                  </div>

                  <div class="modal-footer border-0 pb-4 px-4">
                    <button type="submit" name="update_obat" class="btn btn-warning w-100 py-3 fw-bold rounded-4 text-white shadow">
                      <i class="bi bi-check-lg me-2"></i>Perbarui Data Obat
                    </button>
                  </div>
                </form>
              </div>
            </div>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<div class="modal fade" id="mAddObat" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content border-0 shadow-lg" style="border-radius:24px" method="POST">
      <div class="modal-header bg-primary text-white border-0 py-4">
        <h5 class="fw-bold mb-0">
          <i class="bi bi-plus-circle me-2"></i>Tambah Obat Baru
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4 text-start">
        <div class="mb-3">
          <label class="small fw-bold text-muted">NAMA OBAT</label>
          <input type="text" name="nama_obat" class="form-control bg-light border-0 py-2" placeholder="Nama obat..." required>
        </div>

        <div class="mb-3">
          <label class="small fw-bold text-muted">SATUAN</label>
          <select name="satuan" class="form-select bg-light border-0" required>
            <option value="">-- Pilih Satuan --</option>
            <option value="Tablet">Tablet</option>
            <option value="Kapsul">Kapsul</option>
            <option value="Botol">Botol</option>
            <option value="Strip">Strip</option>
            <option value="Ampul">Ampul</option>
            <option value="Sachet">Sachet</option>
            <option value="Tube">Tube</option>
          </select>
        </div>

        <div class="row g-3">
          <div class="col-4">
            <label class="small fw-bold text-muted">STOK</label>
            <input type="number" name="stok_sekarang" class="form-control bg-light border-0" placeholder="0" min="0" required>
          </div>

          <div class="col-4">
            <label class="small fw-bold text-muted">MINIMUM</label>
            <input type="number" name="stok_minimum" class="form-control bg-light border-0" placeholder="0" min="0" required>
          </div>

          <div class="col-4">
            <label class="small fw-bold text-muted">TARGET</label>
            <input type="number" name="stok_target" class="form-control bg-light border-0" placeholder="0" min="0" required>
          </div>
        </div>

        <div class="mt-3">
          <label class="small fw-bold text-muted">HARGA / PCS (Rp)</label>
          <input type="number" name="harga_per_pcs" class="form-control bg-light border-0" placeholder="0" min="0" step="0.01" required>
        </div>
      </div>

      <div class="modal-footer border-0 pb-4 px-4">
        <button type="submit" name="add_obat" class="btn btn-primary w-100 py-3 fw-bold rounded-4 shadow">
          <i class="bi bi-save me-2"></i>Simpan Obat
        </button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<?php include dirname(__DIR__) . '/includes/sweetalert_global.php'; ?>

<script>
function updateClock() {
    const clock = document.getElementById('digitalClock');
    if (!clock) return;

    const now = new Date();
    const opt = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    };

    clock.innerText = now.toLocaleDateString('id-ID', opt);
}

setInterval(updateClock, 1000);
updateClock();

document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.body.classList.toggle('sidebar-toggled');
});
</script>

<?php include dirname(__DIR__) . '/includes/pagination_global.php'; ?>
</body>
</html>