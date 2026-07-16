<?php
// Modul halaman Admin. Variabel data disiapkan oleh adminMaster.php.
?>
        <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold">Tim Pengelola</h3><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mAddStaff">+ Staf Baru</button></div>
        
        <!-- Search & Filter Staff -->
        <div class="data-container mb-4 py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="small fw-bold text-muted mb-2">Cari Staf</label>
                    <input type="text" id="searchStaff" class="form-control" placeholder="Cari NIP atau nama staf...">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-2">Instansi</label>
                    <select id="filterInstansi" class="form-select">
                        <option value="">Semua Instansi</option>
                        <option value="Kampus">Kampus</option>
                        <option value="Siloam">Siloam</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="astar-filter-actions">
                        <button type="button" id="btnFilterStaff" class="btn btn-primary flex-fill fw-bold">Filter</button>
                        <button type="button" id="btnResetStaff" class="btn btn-light border flex-fill fw-bold">Atur Ulang</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="data-container"><div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>No</th><th>NIP</th><th>Nama</th><th>Akun</th><th>Jabatan</th><th>Instansi</th><th>No HP</th><th>Aksi</th></tr></thead>
            <tbody id="tableStaff">
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($s_list)): ?>
                <tr class="staff-row" data-instansi="<?= $row["instansi"] ?>">
                    <td class="text-muted small"><?= $no++ ?></td>
                    <td class="fw-bold"><?= $row["no_identitas"] ?></td>
                    <td class="nama-staff"><?= htmlspecialchars((string) $row["nama_lengkap"], ENT_QUOTES, "UTF-8") ?></td>
                    <td>
                        <div class="small fw-bold text-primary"><?= htmlspecialchars((string) ($row['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <small class="text-muted"><?= htmlspecialchars((string) ($row['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
                    </td>
                    <td><small class="fw-bold"><?= $row[
                        "jabatan"
                    ] ?></small></td>
                    <td><span class="badge bg-light text-dark border"><?= $row[
                        "instansi"
                    ] ?></span></td>
                    <td><small class="text-success fw-bold"><?= e(formatPhone62($row["no_hp"] ?? '')) ?></small></td>
                    <td>
                        <button class="btn btn-sm btn-light text-warning me-1" data-bs-toggle="modal" data-bs-target="#mEditS<?= $row[
                            "id_staff"
                        ] ?>"><i class="bi bi-pencil-square"></i></button>
                        <a href="?del=<?= $row[
                            "id_staff"
                        ] ?>&t=staffm&k=id_staff&page=staff" class="btn btn-sm btn-light text-danger js-swal-confirm" data-swal-title="Hapus Staf?" data-swal-text="Data staf dan akun pengguna yang terhubung akan ikut dihapus." data-swal-confirm="Ya, Hapus"><i class="bi bi-trash3"></i></a>
                    </td>
                </tr>
            <?php endwhile;
            ?></tbody></table></div></div>


<script>
(() => {
  const input = document.getElementById('searchStaff');
  const select = document.getElementById('filterInstansi');
  const filterButton = document.getElementById('btnFilterStaff');
  const resetButton = document.getElementById('btnResetStaff');
  const rows = Array.from(document.querySelectorAll('.staff-row'));
  if (!input || !select || !filterButton || !resetButton) return;

  const run = () => {
    const term = input.value.toLowerCase().trim();
    const instansi = select.value.toLowerCase();
    rows.forEach(row => {
      const nip = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
      const nama = row.querySelector('.nama-staff')?.textContent.toLowerCase() || '';
      const rowInstansi = (row.dataset.instansi || '').toLowerCase();
      const visible = (nip.includes(term) || nama.includes(term)) && (!instansi || rowInstansi === instansi);
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
