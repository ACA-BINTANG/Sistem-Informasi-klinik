<?php
// Halaman Jadwal Dokter.
// CRUD diproses di dokter/index.php dan halaman ini hanya menangani tampilan/form.

$daftarJadwal = [];
$stmtJadwal = mysqli_prepare(
    $conn,
    "SELECT id_jadwal, tanggal, jam_mulai, jam_selesai, status
     FROM jadwalm
     WHERE id_staff = ?
       AND tanggal IN ('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')
       AND status IN ('Buka','Tutup')
     ORDER BY FIELD(tanggal, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jam_mulai ASC, jam_selesai ASC"
);

if ($stmtJadwal) {
    mysqli_stmt_bind_param($stmtJadwal, 's', $id_dokter);
    mysqli_stmt_execute($stmtJadwal);
    $hasilJadwal = mysqli_stmt_get_result($stmtJadwal);
    if ($hasilJadwal) {
        while ($rowJadwal = mysqli_fetch_assoc($hasilJadwal)) {
            $daftarJadwal[] = $rowJadwal;
        }
    }
    mysqli_stmt_close($stmtJadwal);
}

$daftarHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Jadwal Dokter</h3>
        <small class="text-muted">Kelola hari, jam praktik, dan status jadwal dokter.</small>
    </div>
    <button type="button" class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahJadwal">
        <i class="bi bi-plus-circle me-1"></i>Tambah Jadwal
    </button>
</div>

<div class="data-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Hari</th>
                    <th>Jam Mulai</th>
                    <th>Jam Selesai</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftarJadwal)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">Belum ada jadwal dokter.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftarJadwal as $indexJadwal => $jadwal): ?>
                        <tr>
                            <td><?= $indexJadwal + 1 ?></td>
                            <td class="fw-bold"><?= e($jadwal['tanggal']) ?></td>
                            <td><?= e(substr($jadwal['jam_mulai'], 0, 5)) ?></td>
                            <td><?= e(substr($jadwal['jam_selesai'], 0, 5)) ?></td>
                            <td>
                                <?php if ($jadwal['status'] === 'Buka'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2">Buka</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2">Tutup</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-light border fw-bold js-edit-jadwal"
                                    data-id="<?= e($jadwal['id_jadwal']) ?>"
                                    data-hari="<?= e($jadwal['tanggal']) ?>"
                                    data-mulai="<?= e(substr($jadwal['jam_mulai'], 0, 5)) ?>"
                                    data-selesai="<?= e(substr($jadwal['jam_selesai'], 0, 5)) ?>"
                                    data-status="<?= e($jadwal['status']) ?>"
                                >
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </button>

                                <form method="POST" class="d-inline js-swal-confirm"
                                      data-swal-title="Hapus Jadwal?"
                                      data-swal-text="Jadwal akan dihapus permanen."
                                      data-swal-confirm="Ya, Hapus">
                                    <input type="hidden" name="id_jadwal" value="<?= e($jadwal['id_jadwal']) ?>">
                                    <button type="submit" name="hapus_jadwal_dokter" class="btn btn-sm btn-danger fw-bold">
                                        <i class="bi bi-trash3 me-1"></i>Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Jadwal -->
<div class="modal fade" id="modalTambahJadwal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formTambahJadwal" class="modal-content border-0 shadow-lg js-form-jadwal" style="border-radius:24px;" novalidate>
            <div class="modal-header bg-primary text-white border-0 py-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-calendar-plus me-2"></i>Tambah Jadwal Dokter</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-2">HARI</label>
                    <select name="tanggal" class="form-select bg-light py-3" required>
                        <option value="">-- Pilih Hari --</option>
                        <?php foreach ($daftarHari as $hari): ?>
                            <option value="<?= e($hari) ?>"><?= e($hari) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-2">JAM MULAI</label>
                        <input type="time" name="jam_mulai" class="form-control bg-light py-3" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-2">JAM SELESAI</label>
                        <input type="time" name="jam_selesai" class="form-control bg-light py-3" required>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="small fw-bold text-muted mb-2">STATUS</label>
                    <select name="status" class="form-select bg-light py-3" required>
                        <option value="Buka">Buka</option>
                        <option value="Tutup">Tutup</option>
                    </select>
                </div>

                <div class="alert alert-info border-0 rounded-4 small mt-4 mb-0">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Satu hari boleh memiliki beberapa sesi jadwal dengan jam yang berbeda.
                </div>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light py-3 fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="add_jadwal_dokter" class="btn btn-primary py-3 px-4 fw-bold flex-fill">
                    Simpan Jadwal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Jadwal -->
<div class="modal fade" id="modalEditJadwal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formEditJadwal" class="modal-content border-0 shadow-lg js-form-jadwal" style="border-radius:24px;" novalidate>
            <div class="modal-header bg-primary text-white border-0 py-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Jadwal Dokter</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body p-4">
                <input type="hidden" name="id_jadwal" id="editIdJadwal">

                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-2">HARI</label>
                    <select name="tanggal" id="editHariJadwal" class="form-select bg-light py-3" required>
                        <?php foreach ($daftarHari as $hari): ?>
                            <option value="<?= e($hari) ?>"><?= e($hari) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-2">JAM MULAI</label>
                        <input type="time" name="jam_mulai" id="editJamMulai" class="form-control bg-light py-3" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-2">JAM SELESAI</label>
                        <input type="time" name="jam_selesai" id="editJamSelesai" class="form-control bg-light py-3" required>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="small fw-bold text-muted mb-2">STATUS</label>
                    <select name="status" id="editStatusJadwal" class="form-select bg-light py-3" required>
                        <option value="Buka">Buka</option>
                        <option value="Tutup">Tutup</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light py-3 fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="update_jadwal_dokter" class="btn btn-primary py-3 px-4 fw-bold flex-fill">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModalElement = document.getElementById('modalEditJadwal');
    const editModal = editModalElement && window.bootstrap
        ? bootstrap.Modal.getOrCreateInstance(editModalElement)
        : null;

    document.querySelectorAll('.js-edit-jadwal').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('editIdJadwal').value = button.dataset.id || '';
            document.getElementById('editHariJadwal').value = button.dataset.hari || 'Senin';
            document.getElementById('editJamMulai').value = button.dataset.mulai || '';
            document.getElementById('editJamSelesai').value = button.dataset.selesai || '';
            document.getElementById('editStatusJadwal').value = button.dataset.status || 'Buka';

            document.querySelectorAll('#formEditJadwal .is-invalid').forEach(function (field) {
                field.classList.remove('is-invalid');
            });

            if (editModal) editModal.show();
        });
    });

    document.querySelectorAll('.js-form-jadwal').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const mulai = form.querySelector('[name="jam_mulai"]');
            const selesai = form.querySelector('[name="jam_selesai"]');

            if (!mulai || !selesai || !mulai.value || !selesai.value) {
                return; // validasi field kosong ditangani SweetAlert global
            }

            if (selesai.value <= mulai.value) {
                event.preventDefault();
                event.stopImmediatePropagation();
                mulai.classList.add('is-invalid');
                selesai.classList.add('is-invalid');

                if (window.ASTARSwal) {
                    ASTARSwal.warning(
                        'Jam selesai harus lebih besar dari jam mulai.',
                        'Jam Tidak Sesuai'
                    ).then(function () {
                        selesai.focus();
                    });
                }
            }
        });
    });
});
</script>
