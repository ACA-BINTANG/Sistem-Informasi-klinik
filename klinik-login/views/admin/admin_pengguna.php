<?php
$role_required = 'admin';
$page_title = 'Kelola Pengguna';
include '../../includes/layout.php';
include '../../config/koneksi.php';

$errors = [];
$activeTab = $_GET['tab'] ?? 'staff';

// Generate next ID untuk staff
function nextStaffId($koneksi) {
    $query = mysqli_query($koneksi, "SELECT id_staff FROM staff ORDER BY id_staff DESC LIMIT 1");
    $data = mysqli_fetch_assoc($query);
    if ($data) {
        $num = (int) substr($data['id_staff'], 2);
        return 'S-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
    return 'S-001';
}

// Generate next ID untuk pasien
function nextPasienId($koneksi) {
    $query = mysqli_query($koneksi, "SELECT id_pasien FROM pasien ORDER BY id_pasien DESC LIMIT 1");
    $data = mysqli_fetch_assoc($query);
    if ($data) {
        $num = (int) substr($data['id_pasien'], 3);
        return 'PSN' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
    return 'PSN001';
}

// Helper badge class
function rolePill($r) {
    if ($r == 'dokter') return 'pill-info';
    if ($r == 'mahasiswa') return 'pill-success';
    if ($r == 'admin') return 'pill-danger';
    return 'pill-warn';
}

function kategoriPill($k) {
    if ($k == 'Mahasiswa') return 'pill-success';
    if ($k == 'Dosen') return 'pill-info';
    if ($k == 'Pegawai') return 'pill-warn';
    return 'pill-secondary';
}

// Proses Tambah Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'staff') {
    $id_staff = mysqli_real_escape_string($koneksi, $_POST['id_staff']);
    $id_user = mysqli_real_escape_string($koneksi, $_POST['id_user']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap']));
    $no_identitas = mysqli_real_escape_string($koneksi, trim($_POST['no_identitas']));
    $jabatan = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $no_tlp = mysqli_real_escape_string($koneksi, trim($_POST['no_tlp']));

    $check = mysqli_query($koneksi, "SELECT id_staff FROM staff WHERE id_staff = '$id_staff'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($koneksi, "UPDATE staff SET id_user='$id_user', nama_lengkap='$nama_lengkap', no_identitas='$no_identitas', jabatan='$jabatan', no_tlp='$no_tlp' WHERE id_staff='$id_staff'");
    } else {
        mysqli_query($koneksi, "INSERT INTO staff (id_staff, id_user, nama_lengkap, no_identitas, jabatan, no_tlp) VALUES ('$id_staff', '$id_user', '$nama_lengkap', '$no_identitas', '$jabatan', '$no_tlp')");
    }
    header('Location: ?tab=staff');
    exit;
}

// Hapus Staff
if (isset($_GET['delete_staff'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['delete_staff']);
    mysqli_query($koneksi, "DELETE FROM staff WHERE id_staff = '$id'");
    header('Location: ?tab=staff');
    exit;
}

// Proses Tambah Pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'pasien') {
    $id_pasien = mysqli_real_escape_string($koneksi, $_POST['id_pasien']);
    $id_user = mysqli_real_escape_string($koneksi, $_POST['id_user']);
    $no_identitas = mysqli_real_escape_string($koneksi, trim($_POST['no_identitas']));
    $nama_pasien = mysqli_real_escape_string($koneksi, trim($_POST['nama_pasien']));
    $jenis_kelamin = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $kategori_pasien = mysqli_real_escape_string($koneksi, $_POST['kategori_pasien']);
    $unit_prodi = mysqli_real_escape_string($koneksi, trim($_POST['unit_prodi']));
    $alamat = mysqli_real_escape_string($koneksi, trim($_POST['alamat']));
    $no_hp = mysqli_real_escape_string($koneksi, trim($_POST['no_hp']));

    $check = mysqli_query($koneksi, "SELECT id_pasien FROM pasien WHERE id_pasien = '$id_pasien'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($koneksi, "UPDATE pasien SET id_user='$id_user', no_identitas='$no_identitas', nama_pasien='$nama_pasien', jenis_kelamin='$jenis_kelamin', kategori_pasien='$kategori_pasien', unit_prodi='$unit_prodi', alamat='$alamat', no_hp='$no_hp' WHERE id_pasien='$id_pasien'");
    } else {
        mysqli_query($koneksi, "INSERT INTO pasien (id_pasien, id_user, no_identitas, nama_pasien, jen

is_kelamin, kategori_pasien, unit_prodi, alamat, no_hp) VALUES ('$id_pasien', '$id_user', '$no_identitas', '$nama_pasien', '$jenis_kelamin', '$kategori_pasien', '$unit_prodi', '$alamat', '$no_hp')");
    }
    header('Location: ?tab=pasien');
    exit;
}

// Hapus Pasien
if (isset($_GET['delete_pasien'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['delete_pasien']);
    mysqli_query($koneksi, "DELETE FROM pasien WHERE id_pasien = '$id'");
    header('Location: ?tab=pasien');
    exit;
}

// Ambil data untuk Edit
$editStaff = null;
$editPasien = null;
if (isset($_GET['edit_staff'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['edit_staff']);
    $res = mysqli_query($koneksi, "SELECT * FROM staff WHERE id_staff = '$id'");
    $editStaff = mysqli_fetch_assoc($res);
}
if (isset($_GET['edit_pasien'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['edit_pasien']);
    $res = mysqli_query($koneksi, "SELECT * FROM pasien WHERE id_pasien = '$id'");
    $editPasien = mysqli_fetch_assoc($res);
}

// Render header
render_header($page_title, $_SESSION['role'], $_SESSION['name'] ?? $_SESSION['user'], null, null, 0);
?>

<ul class="nav nav-pills mb-4">
  <li class="nav-item">
    <a class="nav-link <?php echo $activeTab === 'staff' ? 'active bg-primary' : 'text-dark'; ?>" href="?tab=staff">
        <i class="bi bi-person-badge me-2"></i>Staff Internal
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $activeTab === 'pasien' ? 'active bg-primary' : 'text-dark'; ?>" href="?tab=pasien">
        <i class="bi bi-people me-2"></i>Pasien
    </a>
  </li>
</ul>

<?php if ($activeTab === 'staff'): ?>
<div class="panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Daftar Staff</h6>
        <button class="btn btn-sm btn-role" data-bs-toggle="modal" data-bs-target="#modalStaff">
            <i class="bi bi-plus-lg me-1"></i>Tambah Staff
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="table clean">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ID User</th>
                    <th>Nama Lengkap</th>
                    <th>No Identitas</th>
                    <th>Jabatan</th>
                    <th>No Tlp</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Query staff dengan JOIN users untuk ambil role
                $query_staff = mysqli_query($koneksi, "SELECT s.*, u.role 
                                                       FROM staff s 
                                                       LEFT JOIN users u ON s.id_user = u.id_user");
                while ($s = mysqli_fetch_assoc($query_staff)): 
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s['id_staff']) ?></strong></td>
                    <td><?= htmlspecialchars($s['id_user'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($s['no_identitas'] ?? '-') ?></td>
                    <td>
                        <span class="pill <?= rolePill($s['role'] ?? '') ?>">
                            <?= htmlspecialchars($s['jabatan']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($s['no_tlp'] ?? '-') ?></td>
                    <td class="text-end">
                        <a href="?tab=staff&edit_staff=<?= $s['id_staff'] ?>" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                        <a href="?tab=staff&delete_staff=<?= $s['id_staff'] ?>" onclick="return confirm('Hapus staff ini?')" class="btn btn-sm btn-light text-danger" title="Hapus"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL STAFF -->
<div class="modal fade" id="modalStaff" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><?= $editStaff ? 'Edit Staff' : 'Tambah Staff' ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="form_type" value="staff">
            <input type="hidden" name="id_staff" value="<?= $editStaff ? htmlspecialchars($editStaff['id_staff']) : nextStaffId($koneksi) ?>">
            
            <div class="mb-3">
                <label class="form-label small fw-semibold">ID Staff</label>
                <input type="text" value="<?= $editStaff ? htmlspecialchars($editStaff['id_staff']) : nextStaffId($koneksi) ?>" disabled class="form-control bg-light">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">ID User (dari tabel users)</label>
                <select name="id_user" class="form-select">
                    <option value="">-- Pilih User --</option>
                    <?php
                    $users = mysqli_query($koneksi, "SELECT id_user, nama, role FROM users");
                    while ($u = mysqli_fetch_assoc($users)) {
                        $selected = ($editStaff && $editStaff['id_user'] == $u['id_user']) ? 'selected' : '';
                        echo '<option value="' . $u['id_user'] . '" ' . $selected . '>' . $u['nama'] . ' (' . $u['role'] . ')</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" value="<?= $editStaff ? htmlspecialchars($editStaff['nama_lengkap']) : '' ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">No Identitas</label>
                <input type="text" name="no_identitas" value="<?= $editStaff ? htmlspecialchars($editStaff['no_identitas']) : '' ?>" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Jabatan</label>
                <select name="jabatan" class="form-select">
                    <option value="Admin" <?= ($editStaff['jabatan'] ?? '') === 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="Dokter" <?= ($editStaff['jabatan'] ?? '') === 'Dokter' ? 'selected' : '' ?>>Dokter</option>
                    <option value="Perawat" <?= ($editStaff['jabatan'] ?? '') === 'Perawat' ? 'selected' : '' ?>>Perawat</option>
                    <option value="K3" <?= ($editStaff['jabatan'] ?? '') === 'K3' ? 'selected' : '' ?>>K3</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">No Telepon</label>
                <input type="text" name="no_tlp" value="<?= $editStaff ? htmlspecialchars($editStaff['no_tlp']) : '' ?>" class="form-control">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Data</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($editStaff): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        new bootstrap.Modal(document.getElementById('modalStaff')).show();
    });
</script>
<?php endif; ?>

<?php else: ?>
<!-- TAB PASIEN -->
<div class="panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Data Pasien</h6>
        <button class="btn btn-sm btn-role" data-bs-toggle="modal" data-bs-target="#modalPasien">
            <i class="bi bi-plus-lg me-1"></i>Tambah Pasien
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="table clean">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>No Identitas</th>
                    <th>Nama</th>
                    <th>JK</th>
                    <th>Kategori</th>
                    <th>Unit/Prodi</th>
                    <th>No HP</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $query_pasien = mysqli_query($koneksi, "SELECT * FROM pasien");
                while ($p = mysqli_fetch_assoc($query_pasien)): 
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['id_pasien']) ?></strong></td>
                    <td><?= htmlspecialchars($p['no_identitas'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['nama_pasien']) ?></td>
                    <td><?= $p['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                    <td>
                        <span class="pill <?= kategoriPill($p['kategori_pasien']) ?>">
                            <?= htmlspecialchars($p['kategori_pasien']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($p['unit_prodi'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['no_hp'] ?? '-') ?></td>
                    <td class="text-end">
                        <a href="?tab=pasien&edit_pasien=<?= $p['id_pasien'] ?>" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                        <a href="?tab=pasien&delete_pasien=<?= $p['id_pasien'] ?>" onclick="return confirm('Hapus pasien ini?')" class="btn btn-sm btn-light text-danger" title="Hapus"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PASIEN -->
<div class="modal fade" id="modalPasien" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><?= $editPasien ? 'Edit Pasien' : 'Tambah Pasien' ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="form_type" value="pasien">
            <input type="hidden" name="id_pasien" value="<?= $editPasien ? htmlspecialchars($editPasien['id_pasien']) : nextPasienId($koneksi) ?>">
            
            <div class="mb-3">
                <label class="form-label small fw-semibold">ID Pasien</label>
                <input type="text" value="<?= $editPasien ? htmlspecialchars($editPasien['id_pasien']) : nextPasienId($koneksi) ?>" disabled class="form-control bg-light">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">ID User</label>
                <select name="id_user" class="form-select">
                    <option value="">-- Pilih User --</option>
                    <?php
                    $users = mysqli_query($koneksi, "SELECT id_user, nama, role FROM users WHERE role = 'mahasiswa'");
                    while ($u = mysqli_fetch_assoc($users)) {
                        $selected = ($editPasien && $editPasien['id_user'] == $u['id_user']) ? 'selected' : '';
                        echo '<option value="' . $u['id_user'] . '" ' . $selected . '>' . $u['nama'] . ' (' . $u['role'] . ')</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">No Identitas (NIM untuk Mahasiswa)</label>
                <input type="text" name="no_identitas" value="<?= $editPasien ? htmlspecialchars($editPasien['no_identitas']) : '' ?>" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Nama Pasien</label>
                <input type="text" name="nama_pasien" value="<?= $editPasien ? htmlspecialchars($editPasien['nama_pasien']) : '' ?>" class="form-control" required>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label small fw-semibold">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-select">
                        <option value="L" <?= ($editPasien['jenis_kelamin'] ?? 'L') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= ($editPasien['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label small fw-semibold">Kategori Pasien</label>
                    <select name="kategori_pasien" class="form-select">
                        <option value="Mahasiswa" <?= ($editPasien['kategori_pasien'] ?? 'Mahasiswa') === 'Mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                        <option value="Dosen" <?= ($editPasien['kategori_pasien'] ?? '') === 'Dosen' ? 'selected' : '' ?>>Dosen</option>
                        <option value="Pegawai" <?= ($editPasien['kategori_pasien'] ?? '') === 'Pegawai' ? 'selected' : '' ?>>Pegawai</option>
                        <option value="Tamu" <?= ($editPasien['kategori_pasien'] ?? '') === 'Tamu' ? 'selected' : '' ?>>Tamu</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Unit / Prodi</label>
                <input type="text" name="unit_prodi" value="<?= $editPasien ? htmlspecialchars($editPasien['unit_prodi']) : '' ?>" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Alamat</label>
                <input type="text" name="alamat" value="<?= $editPasien ? htmlspecialchars($editPasien['alamat']) : '' ?>" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">No HP</label>
                <input type="text" name="no_hp" value="<?= $editPasien ? htmlspecialchars($editPasien['no_hp']) : '' ?>" class="form-control">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Data</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($editPasien): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        new bootstrap.Modal(document.getElementById('modalPasien')).show();
    });
</script>
<?php endif; ?>

<?php endif; ?>

<?php render_footer(); ?>