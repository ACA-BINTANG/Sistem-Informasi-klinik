<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
if (!isset($edit_diagnosa_data)) {
    $edit_diagnosa_data = null;
}
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Data Diagnosa</h3>
                <small class="text-muted">Kelola master penyakit dan diagnosa.</small>
            </div>

            <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahDiagnosa">
                <i class="bi bi-plus-circle me-1"></i> Tambah Diagnosa
            </button>
        </div>

        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Penyakit</th>
                            <th>Kategori</th>
                            <th>Tipe</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $noD = 1;

                        $qDiagnosa = mysqli_query(
                            $conn,
                            "
                            SELECT *
                            FROM diagnosam
                            ORDER BY nama_penyakit ASC
                        ",
                        );

                        if (!$qDiagnosa) {
                            echo "<tr><td colspan='5' class='text-center text-danger'>Query error: " .
                                e(mysqli_error($conn)) .
                                "</td></tr>";
                        } elseif (mysqli_num_rows($qDiagnosa) == 0) {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Belum ada data diagnosa.</td></tr>";
                        }

                        if ($qDiagnosa) {
                            while ($dg = mysqli_fetch_assoc($qDiagnosa)): ?>
                            <tr>
                                <td><?= $noD++ ?></td>
                                <td class="fw-bold text-primary"><?= e($dg["nama_penyakit"]) ?></td>
                                <td><span class="badge bg-light text-dark border px-3"><?= e($dg["kategori"]) ?></span></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary px-3"><?= e($dg["tipe"]) ?></span></td>

                                <td class="text-center">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_diagnosa" value="<?= e($dg["id_diagnosa"]) ?>">
                                        <button type="submit" name="show_edit_diagnosa" class="btn btn-sm btn-light border fw-bold">Edit</button>
                                    </form>

                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus diagnosa ini?')">
                                        <input type="hidden" name="id_diagnosa" value="<?= e($dg["id_diagnosa"]) ?>">
                                        <button type="submit" name="hapus_diagnosa" class="btn btn-sm btn-danger fw-bold">Hapus</button>
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

        <!-- Centralized Edit Modal -->
        <?php if ($edit_diagnosa_data): ?>
        <div class="modal fade" id="modalEditDiagnosa" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Edit Diagnosa</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <input type="hidden" name="id_diagnosa" value="<?= e($edit_diagnosa_data["id_diagnosa"]) ?>">

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">NAMA PENYAKIT</label>
                            <input type="text" name="nama_penyakit" class="form-control bg-light border-0" value="<?= e($edit_diagnosa_data["nama_penyakit"]) ?>" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">KATEGORI</label>
                                <select name="kategori" class="form-select bg-light border-0" required>
                                    <option value="Umum" <?= $edit_diagnosa_data["kategori"] == "Umum" ? "selected" : "" ?>>Umum</option>
                                    <option value="Menular" <?= $edit_diagnosa_data["kategori"] == "Menular" ? "selected" : "" ?>>Menular</option>
                                    <option value="Kronis" <?= $edit_diagnosa_data["kategori"] == "Kronis" ? "selected" : "" ?>>Kronis</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">TIPE</label>
                                <select name="tipe" class="form-select bg-light border-0" required>
                                    <option value="Ringan" <?= $edit_diagnosa_data["tipe"] == "Ringan" ? "selected" : "" ?>>Ringan</option>
                                    <option value="Sedang" <?= $edit_diagnosa_data["tipe"] == "Sedang" ? "selected" : "" ?>>Sedang</option>
                                    <option value="Berat" <?= $edit_diagnosa_data["tipe"] == "Berat" ? "selected" : "" ?>>Berat</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="update_diagnosa" class="btn btn-primary w-100 py-3 fw-bold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const editModal = new bootstrap.Modal(document.getElementById('modalEditDiagnosa'));
                editModal.show();
            });
        </script>
        <?php endif; ?>

        <div class="modal fade" id="modalTambahDiagnosa" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Tambah Diagnosa</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">NAMA PENYAKIT</label>
                            <input type="text" name="nama_penyakit" class="form-control bg-light border-0" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">KATEGORI</label>
                                <select name="kategori" class="form-select bg-light border-0" required>
                                    <option value="Umum">Umum</option>
                                    <option value="Menular">Menular</option>
                                    <option value="Kronis">Kronis</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">TIPE</label>
                                <select name="tipe" class="form-select bg-light border-0" required>
                                    <option value="Ringan">Ringan</option>
                                    <option value="Sedang">Sedang</option>
                                    <option value="Berat">Berat</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="add_diagnosa" class="btn btn-primary w-100 py-3 fw-bold">
                            Simpan Diagnosa
                        </button>
                    </div>
                </form>
            </div>
        </div>

