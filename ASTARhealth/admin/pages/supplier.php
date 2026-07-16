<?php
// Modul halaman Admin. Variabel data disiapkan oleh admin/index.php.
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Data Pemasok</h3>
    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mAddSupplier">+ Pemasok Baru</button>
</div>

<!-- Pencarian Supplier -->
<div class="data-container mb-4 py-3">
    <div class="row g-3 align-items-end">
        <div class="col-md-8">
            <label class="small fw-bold text-muted mb-2">Cari Pemasok</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input
                    type="text"
                    id="searchSupplier"
                    class="form-control"
                    placeholder="Cari nama pemasok, kontak, atau alamat..."
                    autocomplete="off"
                >
            </div>
        </div>
        <div class="col-md-4">
            <div class="astar-filter-actions">
                <button type="button" id="btnFilterSupplier" class="btn btn-primary flex-fill fw-bold">Filter</button>
                <button type="button" id="btnResetSupplier" class="btn btn-light border flex-fill fw-bold">Atur Ulang</button>
            </div>
        </div>
    </div>
</div>

<div class="data-container mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr><th>No</th><th>Nama Pemasok</th><th>Kontak</th><th>Alamat</th><th>Aksi</th></tr>
            </thead>
            <tbody id="tableSupplier">
            <?php
            $no = 1;
            mysqli_data_seek($sup_list, 0);
            while ($r = mysqli_fetch_assoc($sup_list)):
                $supplierSearch = strtolower(trim(
                    (string)($r['nama_supplier'] ?? '') . ' ' .
                    (string)($r['kontak'] ?? '') . ' ' .
                    (string)($r['alamat'] ?? '')
                ));
            ?>
                <tr class="supplier-row" data-search="<?= htmlspecialchars($supplierSearch, ENT_QUOTES, 'UTF-8') ?>">
                    <td class="text-muted small"><?= $no++ ?></td>
                    <td class="fw-bold supplier-name"><?= htmlspecialchars((string)($r['nama_supplier'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><small class="text-success fw-bold supplier-contact"><?= htmlspecialchars((string)($r['kontak'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></td>
                    <td><small class="supplier-address"><?= htmlspecialchars((string)($r['alamat'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></td>
                    <td>
                        <button class="btn btn-sm btn-light text-warning me-1" data-bs-toggle="modal" data-bs-target="#mEditSup<?= htmlspecialchars((string)$r['id_supplier'], ENT_QUOTES, 'UTF-8') ?>" title="Edit supplier"><i class="bi bi-pencil-square"></i></button>
                        <a
                            href="?del=<?= urlencode((string)$r['id_supplier']) ?>&t=supplierm&k=id_supplier&page=supplier"
                            class="btn btn-sm btn-light text-danger js-swal-confirm"
                            data-swal-title="Hapus Pemasok?"
                            data-swal-text="Data supplier akan dihapus permanen."
                            data-swal-confirm="Ya, Hapus"
                            title="Hapus supplier"
                        ><i class="bi bi-trash3"></i></a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('searchSupplier');
    const filterButton = document.getElementById('btnFilterSupplier');
    const resetButton = document.getElementById('btnResetSupplier');
    const rows = Array.from(document.querySelectorAll('.supplier-row'));
    if (!input || !filterButton || !resetButton) return;

    function applySupplierSearch() {
        const term = input.value.toLowerCase().trim();
        rows.forEach(function (row) {
            const haystack = (row.dataset.search || row.textContent || '').toLowerCase();
            const matched = !term || haystack.includes(term);
            row.style.display = matched ? '' : 'none';
            row.dataset.astarFilteredOut = matched ? '0' : '1';
        });
        window.ASTARTablePagination?.refresh(true);
    }

    filterButton.addEventListener('click', applySupplierSearch);
    resetButton.addEventListener('click', () => { input.value = ''; applySupplierSearch(); });
    input.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); applySupplierSearch(); } });
})();
</script>
