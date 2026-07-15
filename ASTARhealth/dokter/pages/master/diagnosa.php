<?php
// Modul Data Diagnosa untuk role Dokter.
// Modal ditempatkan di luar <tbody> agar Bootstrap tidak merusak layout tabel.

$diagnosaRows = [];
$kolomTotalResepDiagnosa = tableExists($conn, "resep_diagnosa")
    ? "(SELECT COUNT(*) FROM resep_diagnosa rdg WHERE rdg.id_diagnosa = d.id_diagnosa)"
    : "0";

$qDiagnosa = mysqli_query(
    $conn,
    "SELECT d.*,
        (SELECT COUNT(*) FROM rekam_medis rm WHERE rm.id_diagnosa = d.id_diagnosa) AS total_rekam_medis,
        $kolomTotalResepDiagnosa AS total_resep
     FROM diagnosam d
     ORDER BY d.nama_penyakit ASC"
);

if ($qDiagnosa) {
    while ($row = mysqli_fetch_assoc($qDiagnosa)) {
        $diagnosaRows[] = $row;
    }
}
?>

<style>
.diagnosa-modal .modal-dialog { max-width: 620px; }
.diagnosa-modal .modal-content {
    border: 0;
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 24px 70px rgba(15, 46, 89, .24);
}
.diagnosa-modal .modal-header {
    padding: 22px 26px;
    background: linear-gradient(135deg, #083b78, #0969c7);
    color: #fff;
    border: 0;
}
.diagnosa-modal .modal-body { padding: 26px; background: #fff; }
.diagnosa-modal .modal-footer { padding: 0 26px 26px; border: 0; background: #fff; }
.diagnosa-modal .form-label { font-size: .78rem; font-weight: 800; color: #526173; letter-spacing: .04em; }
.diagnosa-modal .form-control,
.diagnosa-modal .form-select {
    min-height: 48px;
    border: 1px solid #dce4ee !important;
    background: #f8fafc !important;
    border-radius: 12px !important;
}
.diagnosa-modal .form-control:focus,
.diagnosa-modal .form-select:focus {
    background: #fff !important;
    border-color: #1976d2 !important;
    box-shadow: 0 0 0 .22rem rgba(25,118,210,.13) !important;
}
.diagnosa-modal .modal-actions { display: grid; grid-template-columns: 1fr 1.35fr; gap: 12px; width: 100%; }
.diagnosa-modal .modal-actions .btn { min-height: 46px; border-radius: 12px; font-weight: 800; }
@media (max-width: 575.98px) {
    .diagnosa-modal .modal-dialog { margin: 14px; }
    .diagnosa-modal .modal-actions { grid-template-columns: 1fr; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-1">Data Diagnosa</h3>
        <small class="text-muted">Kelola master penyakit dan diagnosa.</small>
    </div>
    <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahDiagnosa">
        <i class="bi bi-plus-circle me-1"></i> Tambah Diagnosa
    </button>
</div>

<div class="data-container mb-4 py-3">
    <div class="row g-3">
        <div class="col-md-8">
            <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                <input type="text" id="searchDiagnosa" class="form-control bg-light border-0"
                       placeholder="Cari berdasarkan nama penyakit..." autocomplete="off">
            </div>
        </div>
        <div class="col-md-4">
            <select id="filterKategoriDiagnosa" class="form-select bg-light border-0">
                <option value="">Semua Kategori</option>
                <?php
                $kategoriDiagnosa = [];
                foreach ($diagnosaRows as $itemDiagnosa) {
                    $nilaiKategori = trim((string) ($itemDiagnosa['kategori'] ?? ''));
                    if ($nilaiKategori !== '' && !in_array($nilaiKategori, $kategoriDiagnosa, true)) {
                        $kategoriDiagnosa[] = $nilaiKategori;
                    }
                }
                sort($kategoriDiagnosa);
                foreach ($kategoriDiagnosa as $namaKategori):
                ?>
                    <option value="<?= e($namaKategori) ?>"><?= e($namaKategori) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="data-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
            <?php if (!$qDiagnosa): ?>
                <tr><td colspan="6" class="text-center text-danger py-5">Data diagnosa gagal dimuat.</td></tr>
            <?php elseif (!$diagnosaRows): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">Belum ada data diagnosa.</td></tr>
            <?php else: ?>
                <?php foreach ($diagnosaRows as $index => $dg):
                    $totalRm = (int)($dg['total_rekam_medis'] ?? 0);
                    $totalResep = (int)($dg['total_resep'] ?? 0);
                    $total = $totalRm + $totalResep;
                ?>
                <tr class="diagnosa-data-row"
                    data-nama="<?= e(strtolower($dg['nama_penyakit'] ?? '')) ?>"
                    data-kategori="<?= e($dg['kategori'] ?? '') ?>">
                    <td><?= $index + 1 ?></td>
                    <td class="fw-bold text-primary"><?= e($dg['nama_penyakit']) ?></td>
                    <td><span class="badge bg-light text-dark border px-3 py-2"><?= e($dg['kategori']) ?></span></td>
                    <td><span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2"><?= e($dg['tipe']) ?></span></td>
                    <td class="text-center">
                        <?php if ($total === 0): ?>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">Belum dipakai</span>
                        <?php else: ?>
                            <div class="small fw-bold text-dark"><?= $total ?> data</div>
                            <small class="text-muted">RM: <?= $totalRm ?> | Resep: <?= $totalResep ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold me-1"
                                data-bs-toggle="modal" data-bs-target="#modalEditDiagnosa<?= e($dg['id_diagnosa']) ?>">
                            <i class="bi bi-pencil-square me-1"></i>Edit
                        </button>
                        <form method="POST" class="d-inline js-swal-confirm"
                              data-swal-title="Hapus Diagnosa?"
                              data-swal-text="Diagnosa, rekam medis utama, dan resep terkait dapat ikut terhapus. Tindakan ini tidak dapat dibatalkan."
                              data-swal-confirm="Ya, Hapus Semua">
                            <input type="hidden" name="id_diagnosa" value="<?= e($dg['id_diagnosa']) ?>">
                            <button type="submit" name="hapus_diagnosa" class="btn btn-sm btn-danger fw-bold">
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

<?php foreach ($diagnosaRows as $dg): ?>
<div class="modal fade diagnosa-modal" id="modalEditDiagnosa<?= e($dg['id_diagnosa']) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-pencil-square me-2"></i>Edit Diagnosa</h5>
                    <small class="opacity-75">Perbarui nama penyakit, kategori, dan tingkat kondisi.</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_diagnosa" value="<?= e($dg['id_diagnosa']) ?>">
                <div class="mb-4">
                    <label class="form-label" for="nama_<?= e($dg['id_diagnosa']) ?>">NAMA PENYAKIT</label>
                    <input id="nama_<?= e($dg['id_diagnosa']) ?>" type="text" name="nama_penyakit"
                           class="form-control" value="<?= e($dg['nama_penyakit']) ?>" maxlength="100" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="kategori_<?= e($dg['id_diagnosa']) ?>">KATEGORI</label>
                        <select id="kategori_<?= e($dg['id_diagnosa']) ?>" name="kategori" class="form-select" required>
                            <?php foreach (['Umum','Menular','Kronis'] as $kategori): ?>
                                <option value="<?= $kategori ?>" <?= $dg['kategori'] === $kategori ? 'selected' : '' ?>><?= $kategori ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tipe_<?= e($dg['id_diagnosa']) ?>">TIPE</label>
                        <select id="tipe_<?= e($dg['id_diagnosa']) ?>" name="tipe" class="form-select" required>
                            <?php foreach (['Ringan','Sedang','Berat'] as $tipe): ?>
                                <option value="<?= $tipe ?>" <?= $dg['tipe'] === $tipe ? 'selected' : '' ?>><?= $tipe ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="modal-actions">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_diagnosa" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<div class="modal fade diagnosa-modal" id="modalTambahDiagnosa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-plus-circle me-2"></i>Tambah Diagnosa</h5>
                    <small class="opacity-75">Tambahkan penyakit baru ke data master.</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <label class="form-label" for="nama_penyakit_baru">NAMA PENYAKIT</label>
                    <input id="nama_penyakit_baru" type="text" name="nama_penyakit" class="form-control"
                           placeholder="Contoh: Demam Berdarah" maxlength="100" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="kategori_baru">KATEGORI</label>
                        <select id="kategori_baru" name="kategori" class="form-select" required>
                            <option value="Umum">Umum</option>
                            <option value="Menular">Menular</option>
                            <option value="Kronis">Kronis</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tipe_baru">TIPE</label>
                        <select id="tipe_baru" name="tipe" class="form-select" required>
                            <option value="Ringan">Ringan</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Berat">Berat</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="modal-actions">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_diagnosa" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Simpan Diagnosa
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const search = document.getElementById('searchDiagnosa');
    const kategori = document.getElementById('filterKategoriDiagnosa');
    const rows = Array.from(document.querySelectorAll('.diagnosa-data-row'));
    if (!search || !kategori) return;

    const jalankanFilter = () => {
        const kata = search.value.toLowerCase().trim();
        const pilihKategori = kategori.value;
        rows.forEach(row => {
            const cocokNama = !kata || (row.dataset.nama || '').includes(kata);
            const cocokKategori = !pilihKategori || row.dataset.kategori === pilihKategori;
            const tampil = cocokNama && cocokKategori;
            row.style.display = tampil ? '' : 'none';
            row.dataset.astarFilteredOut = tampil ? '0' : '1';
        });
        window.ASTARTablePagination?.refresh(true);
    };

    search.addEventListener('input', jalankanFilter);
    kategori.addEventListener('change', jalankanFilter);
})();
</script>
