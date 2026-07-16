<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Data Pasien</h3>
                <small class="text-muted">Daftar pasien yang terdaftar di klinik.</small>
            </div>
        </div>

        <div class="data-container mb-4 py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="small fw-bold text-muted mb-2">Cari Pasien</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchDataPasienDokter" class="form-control bg-light border-0"
                               placeholder="Cari pasien berdasarkan nama atau nomor identitas..." autocomplete="off">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="astar-filter-actions">
                        <button type="button" id="btnFilterDataPasienDokter" class="btn btn-primary flex-fill fw-bold">Filter</button>
                        <button type="button" id="btnResetDataPasienDokter" class="btn btn-light border flex-fill fw-bold">Atur Ulang</button>
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
                            <th>No Identitas</th>
                            <th>Nama Pasien</th>
                            <th>Kategori</th>
                            <th>Unit / Prodi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $noP = 1;

                        $qPasien = mysqli_query(
                            $conn,
                            "
                            SELECT *
                            FROM pasienm
                            ORDER BY created_at DESC, id_pasien DESC
                        ",
                        );

                        if (!$qPasien) {
                            echo "<tr><td colspan='5' class='text-center text-danger'>Query error: " .
                                e(mysqli_error($conn)) .
                                "</td></tr>";
                        } elseif (mysqli_num_rows($qPasien) == 0) {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Belum ada data pasien.</td></tr>";
                        }

                        if ($qPasien) {
                            while ($p = mysqli_fetch_assoc($qPasien)): ?>
                            <tr class="data-pasien-dokter-row"
                                data-search="<?= e(strtolower(($p["no_identitas"] ?? "") . " " . ($p["nama_pasien"] ?? ""))) ?>">
                                <td><?= $noP++ ?></td>
                                <td class="fw-bold text-primary"><?= e(
                                    $p["no_identitas"],
                                ) ?></td>
                                <td class="fw-bold"><?= e(
                                    $p["nama_pasien"],
                                ) ?></td>
                                <td><span class="badge bg-light text-dark border px-3"><?= e(
                                    $p["kategori_pasien"],
                                ) ?></span></td>
                                <?php
                                $kategoriPasien = (string) ($p["kategori_pasien"] ?? "");
                                $unitProdi = trim((string) ($p["unit_prodi"] ?? ""));
                                $unitTampil = in_array($kategoriPasien, ["Tamu", "Sigap", "Virtus"], true)
                                    ? "-"
                                    : ($unitProdi !== "" ? $unitProdi : "-");
                                ?>
                                <td><?= e($unitTampil) ?></td>
                            </tr>
                        <?php endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

<script>
(() => {
    const input = document.getElementById('searchDataPasienDokter');
    const filterButton = document.getElementById('btnFilterDataPasienDokter');
    const resetButton = document.getElementById('btnResetDataPasienDokter');
    if (!input || !filterButton || !resetButton) return;
    const rows = Array.from(document.querySelectorAll('.data-pasien-dokter-row'));

    const filterPasien = () => {
        const keyword = input.value.toLowerCase().trim();
        rows.forEach(row => {
            const cocok = !keyword || (row.dataset.search || '').includes(keyword);
            row.style.display = cocok ? '' : 'none';
            row.dataset.astarFilteredOut = cocok ? '0' : '1';
        });
        window.ASTARTablePagination?.refresh(true);
    };

    filterButton.addEventListener('click', filterPasien);
    resetButton.addEventListener('click', () => { input.value = ''; filterPasien(); });
    input.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); filterPasien(); } });
})();
</script>
