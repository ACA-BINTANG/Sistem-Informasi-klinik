<?php
// Modul halaman Admin. Variabel data disiapkan oleh adminMaster.php.
?>
        <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold">Database Pasien</h3><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mAddPasien">+ Pasien Baru</button></div>
        
        <!-- Search & Filter Pasien -->
        <div class="data-container mb-4 py-3">
            <div class="row g-3">
                <div class="col-md-7"><input type="text" id="searchPasien" class="form-control" placeholder="Cari NIM atau nama pasien..."></div>
                <div class="col-md-5">
                    <select id="filterProdi" class="form-select">
                        <option value="">-- Semua Prodi/Unit --</option>
                        <optgroup label="Program Studi">
                            <option value="MI">MI</option><option value="MK">MK</option><option value="MO">MO</option>
                            <option value="P4">P4</option><option value="TPM">TPM</option><option value="TKBG">TKBG</option>
                            <option value="TRL">TRL</option><option value="TRPAB">TRPAB</option><option value="TRPL">TRPL</option>
                        </optgroup>
                        <optgroup label="Unit Kerja">
                            <option value="BAA">BAA</option><option value="BAK">BAK</option><option value="IT">IT</option>
                            <option value="K3">K3</option><option value="SECURITY">SECURITY</option>
                        </optgroup>
                    </select>
                </div>
            </div>
        </div>

        <div class="data-container"><div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>No</th><th>Identitas</th><th>Nama</th><th>Kategori</th><th>Prodi/Unit</th><th>No HP</th><th>Aksi</th></tr></thead>
            <tbody id="tablePasien">
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($p_list)): ?>
                <tr class="pasien-row" data-prodi="<?= $row["unit_prodi"] ?>">
                    <td class="text-muted small"><?= $no++ ?></td>
                    <td class="fw-bold text-primary"><?= $row[
                        "no_identitas"
                    ] ?></td>
                    <td class="nama-pasien"><?= $row["nama_pasien"] ?></td>
                    <td><span class="badge bg-secondary opacity-75"><?= $row[
                        "kategori_pasien"
                    ] ?></span></td>
                    <td><small class="fw-bold"><?= $row[
                        "unit_prodi"
                    ] ?></small></td>
                    <td><small class="text-success fw-bold">+62 <?= $row[
                        "no_hp"
                    ] ?? "-" ?></small></td>
                    <td>
                        <button class="btn btn-sm btn-light text-warning me-1" data-bs-toggle="modal" data-bs-target="#mEditP<?= $row[
                            "id_pasien"
                        ] ?>"><i class="bi bi-pencil-square"></i></button>
                        <a href="?del=<?= $row[
                            "id_pasien"
                        ] ?>&t=pasienm&k=id_pasien&page=pasien" class="btn btn-sm btn-light text-danger js-swal-confirm" data-swal-title="Hapus Pasien?" data-swal-text="Data pasien akan dihapus permanen." data-swal-confirm="Ya, Hapus"><i class="bi bi-trash3"></i></a>
                    </td>
                </tr>
            <?php endwhile;
            ?></tbody></table></div></div>


<script>
(() => {
  const input = document.getElementById('searchPasien');
  const select = document.getElementById('filterProdi');
  const rows = document.querySelectorAll('.pasien-row');
  if (!input || !select) return;
  const run = () => {
    const term = input.value.toLowerCase().trim();
    const prodi = select.value.toLowerCase();
    rows.forEach(row => {
      const identitas = row.querySelector('.text-primary')?.textContent.toLowerCase() || '';
      const nama = row.querySelector('.nama-pasien')?.textContent.toLowerCase() || '';
      const rowProdi = (row.dataset.prodi || '').toLowerCase();
      row.style.display = ((identitas.includes(term) || nama.includes(term)) && (!prodi || rowProdi === prodi)) ? '' : 'none';
    });
  };
  input.addEventListener('input', run);
  select.addEventListener('change', run);
})();
</script>
