<?php
include '../../includes/koneksi.php'; 
$role_required = 'dokter';
$page_title = 'Rekam Medis';
include '../../includes/layout.php';
render_header($page_title, $_SESSION['role'], $_SESSION['name'] ?? $_SESSION['user'], null, null, 0);


// Ambil ID Dokter dari Session secara otomatis
$username_login = $_SESSION['user'];
$query_id = mysqli_query($koneksi, "SELECT id FROM staff WHERE username = '$username_login'");
$data_dokter = mysqli_fetch_assoc($query_id);
$id_dokter_login = $data_dokter['id'] ?? '';

// --- Logika untuk Insert Data Rekam Medis ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_rm'])) {
    $kode_rm  = $_POST['kode_rm'];
    $id_pasien= $_POST['id_pasien'];
    $id_dokter= $id_dokter_login; // ID dinamis
    $tanggal  = date('Y-m-d');
    $keluhan  = $_POST['keluhan'];
    $diagnosa = $_POST['diagnosa'];
    $status   = $_POST['status'];
    $biaya    = $_POST['biaya'];

    $stmt = $koneksi->prepare("INSERT INTO rekam_medis (kode_rm, id_pasien, id_dokter, tanggal, keluhan, diagnosa, status, biaya) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssssssd", $kode_rm, $id_pasien, $id_dokter, $tanggal, $keluhan, $diagnosa, $status, $biaya);
        $stmt->execute();
        $stmt->close();
    }
}
// -----------------------------------------------------------------

render_header($page_title, $role, $name, $role_meta, $menus, 3);

// Perbaiki Query Table: Ambil nama pasien dari tabel staff
$query = "SELECT rm.kode_rm, u.nama AS pasien, rm.tanggal, rm.keluhan, rm.diagnosa, rm.status 
          FROM rekam_medis rm 
          JOIN staff u ON rm.id_pasien = u.id 
          ORDER BY rm.tanggal DESC";
$result = $koneksi->query($query);

$rows = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tanggal_format = date('d M Y', strtotime($row['tanggal']));
        $badge_class = strtolower($row['status']) == 'selesai' ? 'pill-success' : 'pill-warn';
        $status_html = '<span class="pill ' . $badge_class . '">' . htmlspecialchars($row['status']) . '</span>';
        
        $rows[] = [
            '<strong>' . htmlspecialchars($row['kode_rm']) . '</strong>',
            htmlspecialchars($row['pasien']),
            $tanggal_format,
            htmlspecialchars($row['keluhan']),
            htmlspecialchars($row['diagnosa']),
            $status_html
        ];
    }
}

render_master_page('Rekam Medis Pasien', ['No RM','Pasien','Tanggal','Anamnesis','Diagnosa','Status'], $rows, 'Tulis Rekam Medis');
?>

<div class="modal fade" id="modalTambahRM" tabindex="-1" aria-labelledby="modalTambahRMLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTambahRMLabel">Tulis Rekam Medis</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="tambah_rm" value="1">
            
            <div class="mb-3">
                <label class="form-label">Kode RM</label>
                <input type="text" class="form-control" name="kode_rm" value="RM-<?php echo date('Ymis'); ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Pilih Pasien</label>
                <select class="form-select" name="id_pasien" required>
                    <option value="">-- Pilih Mahasiswa --</option>
                    <?php
                    // Perbaiki Dropdown: Ambil daftar pasien (mahasiswa) dari tabel staff
                    $q_pasien = $koneksi->query("SELECT id, nama FROM staff WHERE role = 'Mahasiswa'");
                    if($q_pasien){
                        while ($pasien = $q_pasien->fetch_assoc()) {
                            echo '<option value="' . $pasien['id'] . '">' . htmlspecialchars($pasien['nama']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Keluhan (Anamnesis)</label>
                <textarea class="form-control" name="keluhan" rows="2" required></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Diagnosa</label>
                <input type="text" class="form-control" name="diagnosa" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" required>
                        <option value="Selesai">Selesai</option>
                        <option value="Dirujuk">Dirujuk</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Biaya (Rp)</label>
                    <input type="number" class="form-control" name="biaya" required>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Rekam Medis</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('button, a');
    buttons.forEach(btn => {
        if(btn.innerText.trim().includes('Tulis Rekam Medis')) {
            btn.setAttribute('data-bs-toggle', 'modal');
            btn.setAttribute('data-bs-target', '#modalTambahRM');
            if(btn.tagName === 'A') btn.href = '#';
        }
    });
});
</script>

<?php render_footer(); ?>