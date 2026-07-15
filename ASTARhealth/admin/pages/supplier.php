<?php
// Modul halaman Admin. Variabel data disiapkan oleh adminMaster.php.
?>
        <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold">Data Supplier</h3><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mAddSupplier">+ Supplier Baru</button></div>
        <div class="data-container mb-4">
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead><tr><th>No</th><th>Nama Supplier</th><th>Kontak</th><th>Alamat</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php
                $no = 1;
                mysqli_data_seek($sup_list, 0);
                while ($r = mysqli_fetch_assoc($sup_list)): ?>
                    <tr>
                        <td class="text-muted small"><?= $no++ ?></td>
                        <td class="fw-bold"><?= htmlspecialchars(
                            $r["nama_supplier"],
                        ) ?></td>
                        <td><small class="text-success fw-bold"><?= htmlspecialchars(
                            $r["kontak"] ?? "-",
                        ) ?></small></td>
                        <td><small><?= htmlspecialchars(
                            $r["alamat"] ?? "-",
                        ) ?></small></td>
                        <td>
                            <button class="btn btn-sm btn-light text-warning me-1" data-bs-toggle="modal" data-bs-target="#mEditSup<?= $r[
                                "id_supplier"
                            ] ?>"><i class="bi bi-pencil-square"></i></button>
                            <a href="?del=<?= $r[
                                "id_supplier"
                            ] ?>&t=supplierm&k=id_supplier&page=supplier" class="btn btn-sm btn-light text-danger js-swal-confirm" data-swal-title="Hapus Supplier?" data-swal-text="Data supplier akan dihapus permanen." data-swal-confirm="Ya, Hapus"><i class="bi bi-trash3"></i></a>
                        </td>
                    </tr>
                <?php endwhile;
                ?></tbody></table></div>
        </div>
