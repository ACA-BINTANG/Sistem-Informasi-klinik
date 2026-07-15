<?php
// Modal pemeriksaan pasien. Dipanggil dari halaman antrean dokter.
?>
                    <div class="modal fade" id="modalPeriksa<?= e(
                        $r["id_rekam_medis"],
                    ) ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <form method="POST" class="modal-content border-0 shadow-lg pemeriksaan-form" style="border-radius: 24px;" novalidate>
                                <div class="modal-header bg-primary text-white border-0 py-4">
                                    <h5 class="fw-bold mb-0">
                                        <i class="bi bi-clipboard2-pulse me-2"></i>Pemeriksaan Pasien
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body p-4">
                                    <input type="hidden" name="id_rekam_medis" value="<?= e(
                                        $r["id_rekam_medis"],
                                    ) ?>">

                                    <div class="alert <?= $r["status"] ==
                                    "Darurat"
                                        ? "alert-danger"
                                        : "alert-info" ?> border-0 rounded-4">
                                        <div class="fw-bold"><?= e(
                                            $r["nama_pasien"],
                                        ) ?> - <?= e($r["no_antrian"]) ?></div>
                                        <div class="small">
                                            Status: <?= e($r["status"]) ?> |
                                            Jenis: <?= e(
                                                $r["jenis_antrean"],
                                            ) ?> |
                                            Jam: <?= e(
                                                substr(
                                                    $r["waktu_booking"],
                                                    0,
                                                    5,
                                                ),
                                            ) ?>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="small fw-bold text-muted">KELUHAN PASIEN</label>
                                        <textarea name="keluhan" class="form-control bg-light border-0" rows="3" required><?= e(
                                            $r["keluhan"],
                                        ) ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="small fw-bold text-muted">DIAGNOSA</label>
                                        <select name="id_diagnosa" class="form-select bg-light border-0" required>
                                            <option value="">-- Pilih Diagnosa --</option>
                                            <?php foreach (
                                                $diagnosa_options
                                                as $dx
                                            ): ?>
                                                <option value="<?= e(
                                                    $dx["id_diagnosa"],
                                                ) ?>">
                                                    <?= e(
                                                        $dx["nama_penyakit"],
                                                    ) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="small fw-bold text-muted">HASIL PEMERIKSAAN</label>
                                        <textarea name="hasil_pemeriksaan" class="form-control bg-light border-0" rows="5" required placeholder="Tuliskan hasil pemeriksaan dokter..."></textarea>
                                    </div>

                                    <hr>

                                    <h6 class="fw-bold mb-3">
                                        <i class="bi bi-capsule-pill text-primary me-2"></i>Resep Obat
                                    </h6>

                                    <div class="resep-obat-pemeriksaan-list d-flex flex-column gap-3">
                                        <div class="resep-obat-pemeriksaan-item border rounded-4 p-3 bg-light bg-opacity-50">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-6">
                                                    <label class="small fw-bold text-muted">OBAT</label>
                                                    <select name="id_obat[]" class="form-select bg-white border-0 obat-pemeriksaan-select">
                                                        <option value="">-- Tanpa obat --</option>
                                                        <?php foreach (
                                                            $obat_options
                                                            as $ob
                                                        ): ?>
                                                            <option value="<?= e(
                                                                $ob["id_obat"],
                                                            ) ?>">
                                                                <?= e(
                                                                    $ob["nama_obat"],
                                                                ) ?> - Stok: <?= e(
     $ob["stok_sekarang"],
 ) ?> <?= e($ob["satuan"]) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="small fw-bold text-muted">JUMLAH KELUAR</label>
                                                    <input type="number" name="jumlah_keluar[]" class="form-control bg-white border-0 jumlah-obat-pemeriksaan" min="0" value="0">
                                                </div>

                                                <div class="col-md-3 d-grid">
                                                    <button type="button" class="btn btn-outline-primary fw-bold btn-tambah-obat-pemeriksaan">
                                                        <i class="bi bi-plus-lg me-1"></i> Tambah Obat
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <label class="small fw-bold text-muted">CATATAN OBAT / ATURAN PAKAI</label>
                                                <textarea name="catatan_obat[]" class="form-control bg-white border-0 catatan-obat-pemeriksaan" rows="2" placeholder="Contoh: 3x1 setelah makan"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <small class="text-muted d-block mt-2">
                                        Obat bersifat opsional. Klik <strong>Tambah Obat</strong> untuk memberikan lebih dari satu obat.
                                    </small>

                                    <template class="template-obat-pemeriksaan">
                                        <div class="resep-obat-pemeriksaan-item border rounded-4 p-3 bg-light bg-opacity-50">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-6">
                                                    <label class="small fw-bold text-muted">OBAT</label>
                                                    <select name="id_obat[]" class="form-select bg-white border-0 obat-pemeriksaan-select">
                                                        <option value="">-- Pilih obat --</option>
                                                        <?php foreach (
                                                            $obat_options
                                                            as $ob
                                                        ): ?>
                                                            <option value="<?= e(
                                                                $ob["id_obat"],
                                                            ) ?>">
                                                                <?= e(
                                                                    $ob["nama_obat"],
                                                                ) ?> - Stok: <?= e(
     $ob["stok_sekarang"],
 ) ?> <?= e($ob["satuan"]) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="small fw-bold text-muted">JUMLAH KELUAR</label>
                                                    <input type="number" name="jumlah_keluar[]" class="form-control bg-white border-0 jumlah-obat-pemeriksaan" min="0" value="0">
                                                </div>

                                                <div class="col-md-3 d-grid">
                                                    <button type="button" class="btn btn-outline-danger fw-bold btn-hapus-obat-pemeriksaan">
                                                        <i class="bi bi-dash-lg me-1"></i> Hapus
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <label class="small fw-bold text-muted">CATATAN OBAT / ATURAN PAKAI</label>
                                                <textarea name="catatan_obat[]" class="form-control bg-white border-0 catatan-obat-pemeriksaan" rows="2" placeholder="Contoh: 3x1 setelah makan"></textarea>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="modal-footer border-0 px-4 pb-4">
                                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Tutup</button>
                                    <button type="submit" name="simpan_pemeriksaan" class="btn btn-primary fw-bold px-4">
                                        <i class="bi bi-save me-1"></i> Simpan Pemeriksaan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
