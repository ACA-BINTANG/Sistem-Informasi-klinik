<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
if (!isset($edit_jadwal_data)) {
    $edit_jadwal_data = null;
}
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Jadwal Dokter</h3>
                <small class="text-muted">Kelola hari dan jam praktik dokter.</small>
            </div>

            <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahJadwal">
                <i class="bi bi-plus-circle me-1"></i> Tambah Jadwal
            </button>
        </div>

        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                        <?php
                        $noJ = 1;

                        $qJadwal = mysqli_query(
                            $conn,
                            "
                            SELECT *
                            FROM jadwalm
                            WHERE id_staff = '$id_dokter'
                            AND tanggal IN ('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')
                            AND status IN ('Buka','Tutup')
                            ORDER BY FIELD(tanggal, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jam_mulai ASC
                        ",
                        );

                        if (!$qJadwal) {
                            echo "<tr><td colspan='6' class='text-center text-danger'>Query error: " .
                                e(mysqli_error($conn)) .
                                "</td></tr>";
                        } elseif (mysqli_num_rows($qJadwal) == 0) {
                            echo "<tr><td colspan='6' class='text-center py-5 text-muted'>Belum ada jadwal dokter.</td></tr>";
                        }

                        if ($qJadwal) {
                            while ($j = mysqli_fetch_assoc($qJadwal)): ?>
                            <tr>
                                <td><?= $noJ++ ?></td>
                                <td class="fw-bold"><?= e($j["tanggal"]) ?></td>
                                <td><?= e(substr($j["jam_mulai"], 0, 5)) ?></td>
                                <td><?= e(
                                    substr($j["jam_selesai"], 0, 5),
                                ) ?></td>

                                <td>
                                    <?php if ($j["status"] == "Buka"): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3">Buka</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3">Tutup</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_jadwal" value="<?= e(
                                            $j["id_jadwal"],
                                        ) ?>">
                                        <input type="hidden" name="tanggal_lama" value="<?= e(
                                            $j["tanggal"],
                                        ) ?>">
                                        <button type="submit" name="show_edit_jadwal" class="btn btn-sm btn-light border fw-bold">
                                            Edit
                                        </button>
                                    </form>

                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus jadwal ini?')">
                                        <input type="hidden" name="id_jadwal" value="<?= e(
                                            $j["id_jadwal"],
                                        ) ?>">
                                        <button type="submit" name="hapus_jadwal_dokter" class="btn btn-sm btn-danger fw-bold">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="modalTambahJadwal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Tambah Jadwal Dokter</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">HARI</label>
                            <select name="tanggal" class="form-select bg-light border-0" required>
                                <option value="">-- Pilih Hari --</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">JAM MULAI</label>
                                <input type="time" name="jam_mulai" class="form-control bg-light border-0" required>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">JAM SELESAI</label>
                                <input type="time" name="jam_selesai" class="form-control bg-light border-0" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="small fw-bold text-muted">STATUS</label>
                            <select name="status" class="form-select bg-light border-0" required>
                                <option value="Buka">Buka</option>
                                <option value="Tutup">Tutup</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="add_jadwal_dokter" class="btn btn-primary w-100 py-3 fw-bold">
                            Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Jadwal -->
        <?php if ($edit_jadwal_data): ?>
        <div class="modal fade" id="modalEditJadwal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Edit Jadwal</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <input type="hidden" name="id_jadwal" value="<?= e(
                            $edit_jadwal_data["id_jadwal"],
                        ) ?>">

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">HARI</label>
                            <select name="tanggal" class="form-select bg-light border-0" required>
                                <?php foreach (
                                    [
                                        "Senin",
                                        "Selasa",
                                        "Rabu",
                                        "Kamis",
                                        "Jumat",
                                        "Sabtu",
                                        "Minggu",
                                    ]
                                    as $hari
                                ): ?>
                                    <option value="<?= e(
                                        $hari,
                                    ) ?>" <?= $edit_jadwal_data["tanggal"] ==
$hari
    ? "selected"
    : "" ?>>
                                        <?= e($hari) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">JAM MULAI</label>
                                <input type="time" name="jam_mulai" class="form-control bg-light border-0" value="<?= e(
                                    $edit_jadwal_data["jam_mulai"]
                                        ? substr(
                                            $edit_jadwal_data["jam_mulai"],
                                            0,
                                            5,
                                        )
                                        : "",
                                ) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">JAM SELESAI</label>
                                <input type="time" name="jam_selesai" class="form-control bg-light border-0" value="<?= e(
                                    $edit_jadwal_data["jam_selesai"]
                                        ? substr(
                                            $edit_jadwal_data["jam_selesai"],
                                            0,
                                            5,
                                        )
                                        : "",
                                ) ?>" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="small fw-bold text-muted">STATUS</label>
                            <select name="status" class="form-select bg-light border-0" required>
                                <option value="Buka" <?= $edit_jadwal_data[
                                    "status"
                                ] == "Buka"
                                    ? "selected"
                                    : "" ?>>Buka</option>
                                <option value="Tutup" <?= $edit_jadwal_data[
                                    "status"
                                ] == "Tutup"
                                    ? "selected"
                                    : "" ?>>Tutup</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="update_jadwal_dokter" class="btn btn-primary w-100 py-3 fw-bold">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const editModal = new bootstrap.Modal(document.getElementById('modalEditJadwal'));
                editModal.show();
            });
        </script>
        <?php endif; ?>

