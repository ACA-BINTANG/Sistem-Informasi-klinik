<?php
// Modul halaman Admin. Variabel data disiapkan oleh adminMaster.php.
?>
        <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold">Akun Pengguna</h3><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mAddUser">+ Tambah User</button></div>
        
        <!-- Search & Filter User -->
        <div class="data-container mb-4 py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="small fw-bold text-muted mb-2">Cari Akun</label>
                    <input type="text" id="searchUser" class="form-control" placeholder="Cari username atau nama...">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-2">Role</label>
                    <select id="filterRole" class="form-select">
                        <option value="">Semua Role</option>
                        <option value="Dokter">Dokter</option>
                        <option value="Pasien">Pasien</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="astar-filter-actions">
                        <button type="button" id="btnFilterUser" class="btn btn-primary flex-fill fw-bold">Filter</button>
                        <button type="button" id="btnResetUser" class="btn btn-light border flex-fill fw-bold">Atur Ulang</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="data-container"><div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>No</th><th>Username</th><th>Password</th><th>Nama</th><th>Role</th><th>Terhubung</th><th>Aksi</th></tr></thead>
            <tbody id="tableUser">
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($u_list)): ?>
                <tr class="user-row" data-role="<?= $row["role"] ?>">
                    <td class="text-muted small"><?= $no++ ?></td>
                    <td class="fw-bold text-primary"><?= htmlspecialchars((string) $row[
                        "username"
                    ], ENT_QUOTES, "UTF-8") ?></td>
                    <td>
                        <?php if (isHashedPassword((string) $row["password"])): ?>
                            <span class="badge bg-warning text-dark">Perlu diatur ulang</span>
                        <?php else: ?>
                            <span class="fw-semibold password-value"><?= htmlspecialchars((string) $row["password"], ENT_QUOTES, "UTF-8") ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="nama-user"><?= htmlspecialchars((string) $row["nama_lengkap"], ENT_QUOTES, "UTF-8") ?></td>
                    <td><span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill small"><?= htmlspecialchars((string) $row[
                        "role"
                    ], ENT_QUOTES, "UTF-8") ?></span></td>
                    <td>
                        <?php if (!empty($row['linked_pasien_id'])): ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary">Data Pasien</span>
                        <?php elseif (!empty($row['linked_staff_id'])): ?>
                            <span class="badge bg-success bg-opacity-10 text-success">Tim Pengelola</span>
                        <?php else: ?>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary">Akun Mandiri</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-light text-warning me-1" data-bs-toggle="modal" data-bs-target="#mEditU<?= $row[
                            "id_user"
                        ] ?>"><i class="bi bi-pencil-square"></i></button>
                        <a href="?del=<?= $row[
                            "id_user"
                        ] ?>&t=userm&k=id_user&page=user" class="btn btn-sm btn-light text-danger js-swal-confirm" data-swal-title="Hapus User?" data-swal-text="Akun dan data profil yang terhubung akan ikut dihapus." data-swal-confirm="Ya, Hapus"><i class="bi bi-trash3"></i></a>
                    </td>
                </tr>
            <?php endwhile;
            ?></tbody></table></div></div>


<script>
(() => {
  const input = document.getElementById('searchUser');
  const select = document.getElementById('filterRole');
  const filterButton = document.getElementById('btnFilterUser');
  const resetButton = document.getElementById('btnResetUser');
  const rows = Array.from(document.querySelectorAll('.user-row'));
  if (!input || !select || !filterButton || !resetButton) return;

  const run = () => {
    const term = input.value.toLowerCase().trim();
    const role = select.value.toLowerCase();
    rows.forEach(row => {
      const username = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
      const nama = row.querySelector('.nama-user')?.textContent.toLowerCase() || '';
      const rowRole = (row.dataset.role || '').toLowerCase();
      const visible = (username.includes(term) || nama.includes(term)) && (!role || rowRole === role);
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
