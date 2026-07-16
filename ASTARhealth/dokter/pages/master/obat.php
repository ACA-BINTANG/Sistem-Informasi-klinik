<?php
// Halaman Data Obat.
// Dipanggil dari dokter/index.php sehingga variabel $conn, $notifikasi_stok, dan e() tersedia.
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Data Obat</h3>
        <small class="text-muted">Kelola stok obat klinik.</small>
    </div>

    <button type="button" class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahObat">
        <i class="bi bi-plus-circle me-1"></i> Tambah Obat
    </button>
</div>

<?php if ($notifikasi_stok && mysqli_num_rows($notifikasi_stok) > 0): ?>
    <div class="data-container mb-4" style="border-left: 6px solid #ffc107;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-1 text-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Notifikasi Stok Obat
                </h5>
                <small class="text-muted">Obat yang stoknya sudah mencapai batas minimum.</small>
            </div>

            <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                <?= mysqli_num_rows($notifikasi_stok) ?> Notifikasi
            </span>
        </div>

        <div class="row g-3">
            <?php while ($notif = mysqli_fetch_assoc($notifikasi_stok)): ?>
                <div class="col-md-6">
                    <div class="p-3 rounded-4 bg-light border">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-1"><?= e($notif['nama_obat']) ?></h6>
                                <small class="text-muted">
                                    <?= date('d-m-Y H:i', strtotime($notif['tanggal_notifikasi'])) ?>
                                </small>
                            </div>
                            <span class="badge bg-danger rounded-pill">Stok Rendah</span>
                        </div>

                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Stok Sekarang</span>
                                <strong class="text-danger"><?= e($notif['stok_sekarang']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Stok Minimum</span>
                                <strong><?= e($notif['stok_minimum']) ?></strong>
                            </div>
                            <p class="mb-0 small text-muted"><?= e($notif['pesan']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
<?php endif; ?>

<div class="data-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Obat</th>
                    <th>Stok</th>
                    <th>Min Stok</th>
                    <th>Satuan</th>
                    <th>Harga/Pcs</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $noObat = 1;
                $qObat = mysqli_query(
                    $conn,
                    "SELECT * FROM obatm ORDER BY created_at DESC, id_obat DESC"
                );

                if (!$qObat):
                ?>
                    <tr>
                        <td colspan="8" class="text-center text-danger">
                            Data obat tidak dapat dimuat.
                        </td>
                    </tr>
                <?php elseif (mysqli_num_rows($qObat) === 0): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">Belum ada data obat.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($ob = mysqli_fetch_assoc($qObat)): ?>
                        <tr>
                            <td><?= $noObat++ ?></td>
                            <td class="fw-bold text-primary"><?= e($ob['nama_obat']) ?></td>
                            <td><?= e($ob['stok_sekarang']) ?></td>
                            <td>
                                <?php if ((int) $ob['stok_sekarang'] < (int) $ob['stok_minimum']): ?>
                                    <span class="badge bg-danger"><?= e($ob['stok_minimum']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success"><?= e($ob['stok_minimum']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($ob['satuan']) ?></td>
                            <td>Rp <?= number_format((float) $ob['harga_per_pcs'], 0, ',', '.') ?></td>
                            <td>
                                <?php if ((int) $ob['stok_sekarang'] > 0): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3">Habis</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-light border fw-bold btn-edit-obat"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditObat"
                                    data-id-obat="<?= e($ob['id_obat']) ?>"
                                    data-nama-obat="<?= e($ob['nama_obat']) ?>"
                                    data-stok-sekarang="<?= e($ob['stok_sekarang']) ?>"
                                    data-stok-minimum="<?= e($ob['stok_minimum']) ?>"
                                    data-stok-target="<?= e($ob['stok_target']) ?>"
                                    data-satuan="<?= e($ob['satuan']) ?>"
                                    data-harga-per-pcs="<?= e($ob['harga_per_pcs']) ?>">
                                    Edit
                                </button>

                                <form method="POST" class="d-inline js-swal-confirm"
                                      data-swal-title="Hapus Obat?"
                                      data-swal-text="Data obat akan dihapus permanen."
                                      data-swal-confirm="Ya, Hapus">
                                    <input type="hidden" name="id_obat" value="<?= e($ob['id_obat']) ?>">
                                    <button type="submit" name="hapus_obat" value="1" class="btn btn-sm btn-danger fw-bold">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit Obat -->
<div class="modal fade" id="modalEditObat" tabindex="-1" aria-labelledby="modalEditObatLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
            <div class="modal-header bg-primary text-white border-0 py-4">
                <h5 class="modal-title fw-bold" id="modalEditObatLabel">Edit Obat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body p-4">
                <input type="hidden" name="id_obat" id="edit_id_obat">

                <div class="mb-3">
                    <label for="edit_nama_obat" class="small fw-bold text-muted">NAMA OBAT</label>
                    <input type="text" name="nama_obat" id="edit_nama_obat" class="form-control bg-light border-0" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="edit_stok_sekarang" class="small fw-bold text-muted">STOK</label>
                        <input type="number" name="stok_sekarang" id="edit_stok_sekarang" class="form-control bg-light border-0" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_stok_minimum" class="small fw-bold text-muted">MIN STOK</label>
                        <input type="number" name="stok_minimum" id="edit_stok_minimum" class="form-control bg-light border-0" min="0" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="edit_stok_target" class="small fw-bold text-muted">TARGET STOK</label>
                        <input type="number" name="stok_target" id="edit_stok_target" class="form-control bg-light border-0" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label for="edit_satuan" class="small fw-bold text-muted">SATUAN</label>
                        <select name="satuan" id="edit_satuan" class="form-select bg-light border-0" required>
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
                </div>

                <div class="mb-0">
                    <label for="edit_harga_per_pcs" class="small fw-bold text-muted">HARGA PER PCS</label>
                    <input type="number" name="harga_per_pcs" id="edit_harga_per_pcs" class="form-control bg-light border-0" min="0" step="0.01" required>
                </div>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border fw-bold px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="update_obat" value="1" class="btn btn-primary fw-bold px-4">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Obat -->
<div class="modal fade" id="modalTambahObat" tabindex="-1" aria-labelledby="modalTambahObatLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
            <div class="modal-header bg-primary text-white border-0 py-4">
                <h5 class="modal-title fw-bold" id="modalTambahObatLabel">Tambah Obat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-muted">NAMA OBAT</label>
                    <input type="text" name="nama_obat" class="form-control bg-light border-0" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">STOK</label>
                        <input type="number" name="stok_sekarang" class="form-control bg-light border-0" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">MIN STOK</label>
                        <input type="number" name="stok_minimum" class="form-control bg-light border-0" min="0" value="10" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">TARGET STOK</label>
                        <input type="number" name="stok_target" class="form-control bg-light border-0" min="0" value="100" required>
                    </div>
                    <div class="col-md-6">
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
                </div>

                <div class="mb-0">
                    <label class="small fw-bold text-muted">HARGA PER PCS</label>
                    <input type="number" name="harga_per_pcs" class="form-control bg-light border-0" min="0" step="0.01" value="0" required>
                </div>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border fw-bold px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="add_obat" value="1" class="btn btn-primary fw-bold px-4">Simpan Obat</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit-obat').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('edit_id_obat').value = this.dataset.idObat || '';
            document.getElementById('edit_nama_obat').value = this.dataset.namaObat || '';
            document.getElementById('edit_stok_sekarang').value = this.dataset.stokSekarang || '0';
            document.getElementById('edit_stok_minimum').value = this.dataset.stokMinimum || '0';
            document.getElementById('edit_stok_target').value = this.dataset.stokTarget || '0';
            document.getElementById('edit_satuan').value = this.dataset.satuan || '';
            document.getElementById('edit_harga_per_pcs').value = this.dataset.hargaPerPcs || '0';
        });
    });
});
</script>
