<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
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
                            <th class="text-center">Digunakan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $noD = 1;

                        $kolomTotalResepDiagnosa = tableExists($conn, "resep_diagnosa")
                            ? "(SELECT COUNT(*) FROM resep_diagnosa rdg WHERE rdg.id_diagnosa = d.id_diagnosa)"
                            : "0";

                        $qDiagnosa = mysqli_query(
                            $conn,
                            "
                            SELECT
                                d.*,
                                (SELECT COUNT(*) FROM rekam_medis rm WHERE rm.id_diagnosa = d.id_diagnosa) AS total_rekam_medis,
                                $kolomTotalResepDiagnosa AS total_resep
                            FROM diagnosam d
                            ORDER BY d.nama_penyakit ASC
                        ",
                        );

                        if (!$qDiagnosa) {
                            echo "<tr><td colspan='6' class='text-center text-danger'>Query error: " .
                                e(mysqli_error($conn)) .
                                "</td></tr>";
                        } elseif (mysqli_num_rows($qDiagnosa) == 0) {
                            echo "<tr><td colspan='6' class='text-center py-5 text-muted'>Belum ada data diagnosa.</td></tr>";
                        }

                        if ($qDiagnosa) {
                            while ($dg = mysqli_fetch_assoc($qDiagnosa)): ?>
                            <tr>
                                <td><?= $noD++ ?></td>
                                <td class="fw-bold text-primary"><?= e(
                                    $dg["nama_penyakit"],
                                ) ?></td>
                                <td><span class="badge bg-light text-dark border px-3"><?= e(
                                    $dg["kategori"],
                                ) ?></span></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary px-3"><?= e(
                                    $dg["tipe"],
                                ) ?></span></td>

                                <?php
                                $totalRekamMedisDiagnosa = (int) ($dg["total_rekam_medis"] ?? 0);
                                $totalResepDiagnosa = (int) ($dg["total_resep"] ?? 0);
                                $totalPenggunaanDiagnosa = $totalRekamMedisDiagnosa + $totalResepDiagnosa;
                                ?>
                                <td class="text-center">
                                    <?php if ($totalPenggunaanDiagnosa === 0) { ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Belum dipakai</span>
                                    <?php } else { ?>
                                        <div class="small fw-bold text-dark"><?= e($totalPenggunaanDiagnosa) ?> data</div>
                                        <small class="text-muted">RM: <?= e($totalRekamMedisDiagnosa) ?> | Resep: <?= e($totalResepDiagnosa) ?></small>
                                    <?php } ?>
                                </td>

                                <td class="text-center">
                                    <button class="btn btn-sm btn-light border fw-bold"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditDiagnosa<?= e(
                                                $dg["id_diagnosa"],
                                            ) ?>">
                                        Edit
                                    </button>

                                    <?php if ($totalPenggunaanDiagnosa === 0) { ?>
                                        <form method="POST" class="d-inline js-swal-confirm"
                                              data-swal-title="Hapus Diagnosa?"
                                              data-swal-text="Data diagnosa akan dihapus permanen."
                                              data-swal-confirm="Ya, Hapus">
                                            <input type="hidden" name="id_diagnosa" value="<?= e(
                                                $dg["id_diagnosa"],
                                            ) ?>">
                                            <button type="submit" name="hapus_diagnosa" class="btn btn-sm btn-danger fw-bold">
                                                <i class="bi bi-trash3 me-1"></i> Hapus
                                            </button>
                                        </form>
                                    <?php } else { ?>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger fw-bold js-swal-info"
                                                data-swal-icon="warning"
                                                data-swal-title="Diagnosa Masih Digunakan"
                                                data-swal-text="Diagnosa ini dipakai oleh <?= e($totalRekamMedisDiagnosa) ?> rekam medis dan <?= e($totalResepDiagnosa) ?> resep. Ubah atau hapus data terkait terlebih dahulu.">
                                            <i class="bi bi-lock-fill me-1"></i> Hapus
                                        </button>
                                    <?php } ?>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEditDiagnosa<?= e(
                                $dg["id_diagnosa"],
                            ) ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                                        <div class="modal-header bg-primary text-white border-0 py-4">
                                            <h5 class="fw-bold mb-0">Edit Diagnosa</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <input type="hidden" name="id_diagnosa" value="<?= e(
                                                $dg["id_diagnosa"],
                                            ) ?>">

                                            <div class="mb-3">
                                                <label class="small fw-bold text-muted">NAMA PENYAKIT</label>
                                                <input type="text" name="nama_penyakit" class="form-control bg-light border-0" value="<?= e(
                                                    $dg["nama_penyakit"],
                                                ) ?>" required>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="small fw-bold text-muted">KATEGORI</label>
                                                    <select name="kategori" class="form-select bg-light border-0" required>
                                                        <option value="Umum" <?= $dg[
                                                            "kategori"
                                                        ] == "Umum"
                                                            ? "selected"
                                                            : "" ?>>Umum</option>
                                                        <option value="Menular" <?= $dg[
                                                            "kategori"
                                                        ] == "Menular"
                                                            ? "selected"
                                                            : "" ?>>Menular</option>
                                                        <option value="Kronis" <?= $dg[
                                                            "kategori"
                                                        ] == "Kronis"
                                                            ? "selected"
                                                            : "" ?>>Kronis</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="small fw-bold text-muted">TIPE</label>
                                                    <select name="tipe" class="form-select bg-light border-0" required>
                                                        <option value="Ringan" <?= $dg[
                                                            "tipe"
                                                        ] == "Ringan"
                                                            ? "selected"
                                                            : "" ?>>Ringan</option>
                                                        <option value="Sedang" <?= $dg[
                                                            "tipe"
                                                        ] == "Sedang"
                                                            ? "selected"
                                                            : "" ?>>Sedang</option>
                                                        <option value="Berat" <?= $dg[
                                                            "tipe"
                                                        ] == "Berat"
                                                            ? "selected"
                                                            : "" ?>>Berat</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer border-0 px-4 pb-4">
                                            <button type="submit" name="update_diagnosa" class="btn btn-primary w-100 py-3 fw-bold">
                                                Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

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

