<?php
// Data pasien dan akun sudah disiapkan oleh admin/index.php melalui JOIN pasienm + userm.
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Data Pasien</h3>
        <small class="text-muted">Data registrasi pasien dan akun login dikelola dalam satu halaman.</small>
    </div>
    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mAddPasien">
        <i class="bi bi-person-plus me-1"></i> Pasien Baru
    </button>
</div>

<div class="data-container mb-4 py-3">
    <div class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="small fw-bold text-muted mb-2">Cari Pasien</label>
            <input type="text" id="searchPasien" class="form-control" placeholder="Cari identitas, nama, username, atau email...">
        </div>
        <div class="col-md-3">
            <label class="small fw-bold text-muted mb-2">Kategori</label>
            <select id="filterKategoriPasien" class="form-select">
                <option value="">Semua Kategori</option>
                <option value="Mahasiswa">Mahasiswa</option>
                <option value="Pegawai">Pegawai</option>
                <option value="Sigap">Personel Sigap</option>
                <option value="Virtus">Personel Virtus</option>
                <option value="Tamu">Tamu Umum / Lain-lain</option>
            </select>
        </div>
        <div class="col-md-4">
            <div class="astar-filter-actions">
                <button type="button" id="btnFilterPasienAdmin" class="btn btn-primary flex-fill fw-bold">Filter</button>
                <button type="button" id="btnResetPasienAdmin" class="btn btn-light border flex-fill fw-bold">Atur Ulang</button>
            </div>
        </div>
    </div>
</div>

<div class="data-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Identitas</th>
                    <th>Nama Pasien</th>
                    <th>Akun</th>
                    <th>Kategori</th>
                    <th>Unit / Prodi</th>
                    <th>Kontak</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="tablePasien">
            <?php $no = 1; ?>
            <?php while ($row = mysqli_fetch_assoc($p_list)): ?>
                <tr class="pasien-row"
                    data-kategori="<?= e($row['kategori_pasien'] ?? '') ?>"
                    data-search="<?= e(strtolower(implode(' ', [
                        $row['no_identitas'] ?? '',
                        $row['nama_pasien'] ?? '',
                        $row['username'] ?? '',
                        $row['email'] ?? ''
                    ]))) ?>">
                    <td class="text-muted small"><?= $no++ ?></td>
                    <td class="fw-bold text-primary"><?= e($row['no_identitas'] ?? '-') ?></td>
                    <td>
                        <div class="fw-bold"><?= e($row['nama_pasien'] ?? '-') ?></div>
                        <small class="text-muted"><?= ($row['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : 'Perempuan' ?></small>
                    </td>
                    <td>
                        <div class="small fw-bold"><?= e($row['username'] ?? '-') ?></div>
                        <small class="text-muted"><?= e($row['email'] ?? '-') ?></small>
                    </td>
                    <td><span class="badge bg-primary bg-opacity-10 text-primary"><?= e($row['kategori_pasien'] ?? '-') ?></span></td>
                    <?php
                        $kategoriPasienRow = (string) ($row['kategori_pasien'] ?? '');
                        $unitProdiRow = trim((string) ($row['unit_prodi'] ?? ''));
                        $unitTampilRow = in_array($kategoriPasienRow, ['Tamu', 'Sigap', 'Virtus'], true)
                            ? '-'
                            : ($unitProdiRow !== '' ? $unitProdiRow : '-');
                    ?>
                    <td><?= e($unitTampilRow) ?></td>
                    <td>
                        <div class="small text-success fw-bold"><?= e(formatPhone62($row['no_hp'] ?? '')) ?></div>
                        <small class="text-muted"><?= e($row['alamat'] ?: '-') ?></small>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-light text-warning me-1" data-bs-toggle="modal" data-bs-target="#mEditP<?= e($row['id_pasien']) ?>" title="Edit pasien">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <a href="?del=<?= urlencode($row['id_pasien']) ?>&t=pasienm&k=id_pasien&page=pasien"
                           class="btn btn-sm btn-light text-danger js-swal-confirm"
                           data-swal-title="Hapus Pasien?"
                           data-swal-text="Data pasien dan akun pengguna yang terhubung akan ikut dihapus."
                           data-swal-confirm="Ya, Hapus"
                           title="Hapus pasien">
                            <i class="bi bi-trash3"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(() => {
    const input = document.getElementById('searchPasien');
    const select = document.getElementById('filterKategoriPasien');
    const filterButton = document.getElementById('btnFilterPasienAdmin');
    const resetButton = document.getElementById('btnResetPasienAdmin');
    const rows = Array.from(document.querySelectorAll('.pasien-row'));
    if (!input || !select || !filterButton || !resetButton) return;

    const run = () => {
        const term = input.value.toLowerCase().trim();
        const category = select.value;
        rows.forEach(row => {
            const matchesSearch = !term || (row.dataset.search || '').includes(term);
            const matchesCategory = !category || row.dataset.kategori === category;
            const visible = matchesSearch && matchesCategory;
            row.style.display = visible ? '' : 'none';
            row.dataset.astarFilteredOut = visible ? '0' : '1';
        });
        window.ASTARTablePagination?.refresh(true);
    };

    filterButton.addEventListener('click', run);
    resetButton.addEventListener('click', () => { input.value = ''; select.value = ''; run(); });
    input.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); run(); } });
})();
</script>