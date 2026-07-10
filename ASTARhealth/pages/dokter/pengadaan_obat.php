<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Pengadaan Obat</h3>
                <small class="text-muted">Kelola pengadaan dan stok obat klinik.</small>
            </div>

            <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahPengadaan">
                <i class="bi bi-plus-circle me-1"></i> Buat Pengadaan
            </button>
        </div>

        <!-- Obat Kurang Stok -->
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-danger bg-opacity-10 border-0 px-4 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-danger mb-0">⚠️ Obat Kurang dari Stok Minimum</h6>
                        <button type="button" class="btn btn-xs btn-danger fw-bold rounded-3 px-2 py-1 shadow-sm" style="font-size: 0.75rem;" id="btnPesanTerpilih">
                            <i class="bi bi-cart-plus me-1"></i> Pesan Terpilih
                        </button>
                    </div>

                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30px;">
                                            <input type="checkbox" class="form-check-input" id="chkSelectAllLowStock">
                                        </th>
                                        <th>Obat</th>
                                        <th>Stok</th>
                                        <th>Min</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $qKurang = mysqli_query(
                                        $conn,
                                        "
                                        SELECT id_obat, nama_obat, stok_sekarang, stok_minimum, stok_target
                                        FROM obatm
                                        WHERE stok_sekarang < stok_minimum
                                        ORDER BY stok_sekarang ASC
                                    ",
                                    );

                                    if (
                                        !$qKurang ||
                                        mysqli_num_rows($qKurang) == 0
                                    ) {
                                        echo "<tr><td colspan='5' class='text-center text-muted py-3'>Semua obat dalam kondisi baik ✓</td></tr>";
                                    } else {
                                        while (
                                            $ok = mysqli_fetch_assoc($qKurang)
                                        ): 
                                            $saran = max(1, $ok["stok_target"] - $ok["stok_sekarang"]);
                                            ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input low-stock-checkbox" 
                                                   data-id-obat="<?= e($ok["id_obat"]) ?>"
                                                   data-saran-jumlah="<?= $saran ?>">
                                        </td>
                                        <td class="fw-bold"><?= e(
                                            $ok["nama_obat"],
                                        ) ?></td>
                                        <td><span class="badge bg-danger"><?= $ok[
                                            "stok_sekarang"
                                        ] ?></span></td>
                                        <td><?= $ok["stok_minimum"] ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning fw-bold px-2 py-0"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalTambahPengadaan"
                                                    data-id-obat="<?= e(
                                                        $ok["id_obat"],
                                                    ) ?>"
                                                    data-saran-jumlah="<?= $saran ?>"
                                                    onclick="pesanSingleObat(this)">
                                                Pesan
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Obat -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-info bg-opacity-10 border-0 px-4 py-3">
                        <h6 class="fw-bold text-info mb-0">📊 Ringkasan Stok Obat</h6>
                    </div>

                    <div class="card-body p-4">
                        <?php
                        $qStats = mysqli_query(
                            $conn,
                            "
                            SELECT 
                                COUNT(*) as total_obat,
                                SUM(CASE WHEN stok_sekarang >= stok_minimum THEN 1 ELSE 0 END) as stok_baik,
                                SUM(CASE WHEN stok_sekarang < stok_minimum THEN 1 ELSE 0 END) as stok_kurang,
                                SUM(stok_sekarang) as total_stok
                            FROM obatm
                        ",
                        );
                        $stats = mysqli_fetch_assoc($qStats);
                        ?>

                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-primary"><?= $stats[
                                        "total_obat"
                                    ] ?></h5>
                                    <small class="text-muted">Total Obat</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-success"><?= $stats[
                                        "stok_baik"
                                    ] ?></h5>
                                    <small class="text-muted">Stok Baik</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-danger"><?= $stats[
                                        "stok_kurang"
                                    ] ?></h5>
                                    <small class="text-muted">Stok Kurang</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-warning"><?= $stats[
                                        "total_stok"
                                    ] ?></h5>
                                    <small class="text-muted">Total Unit</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Pengadaan -->
        <div class="data-container">
            <h5 class="fw-bold mb-4">Riwayat Pengadaan Obat</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Obat</th>
                            <th>Supplier</th>
                            <th>Jumlah</th>
                            <th>Tanggal Order</th>
                            <th>Target Tiba</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $qPengadaan = mysqli_query(
                            $conn,
                            "
                            SELECT p.*, o.nama_obat, s.nama_supplier, s.kontak as supplier_kontak
                            FROM pengadaan_obat p
                            LEFT JOIN obatm o ON p.id_obat = o.id_obat
                            LEFT JOIN supplierm s ON p.id_supplier = s.id_supplier
                            ORDER BY p.tgl_order DESC, p.id_pengadaan DESC
                        ",
                        );

                        if (!$qPengadaan) {
                            echo "<tr><td colspan='8' class='text-center text-danger'>Query error: " .
                                e(mysqli_error($conn)) .
                                "</td></tr>";
                        } elseif (mysqli_num_rows($qPengadaan) == 0) {
                            echo "<tr><td colspan='8' class='text-center py-5 text-muted'>Belum ada data pengadaan.</td></tr>";
                        } else {
                            $grouped = [];
                            while ($row = mysqli_fetch_assoc($qPengadaan)) {
                                $id = $row['id_pengadaan'];
                                if (!isset($grouped[$id])) {
                                    $grouped[$id] = [
                                        'id_pengadaan' => $id,
                                        'nama_supplier' => $row['nama_supplier'],
                                        'tgl_order' => $row['tgl_order'],
                                        'tgl_estimasi_tiba' => $row['tgl_estimasi_tiba'],
                                        'status' => $row['status'],
                                        'items' => []
                                    ];
                                }
                                $grouped[$id]['items'][] = [
                                    'nama_obat' => $row['nama_obat'],
                                    'jumlah_order' => $row['jumlah_order'],
                                    'jumlah_diterima' => $row['jumlah_diterima']
                                ];
                            }

                            foreach ($grouped as $p):
                                $badgeClass =
                                    [
                                        "Pending" => "warning",
                                        "Proses" => "info",
                                        "Diterima" => "success",
                                        "Batal" => "danger",
                                        "Expired" => "dark",
                                    ][$p["status"]] ?? "secondary"; ?>
                            <tr>
                                <td class="fw-bold small"><?= e($p["id_pengadaan"]) ?></td>
                                <td>
                                    <ul class="list-unstyled mb-0 small">
                                        <?php foreach ($p['items'] as $item): ?>
                                            <li>• <?= e($item['nama_obat']) ?> <span class="text-muted">(<?= $item['jumlah_order'] ?> unit)</span></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td><?= e($p["nama_supplier"] ?? "-") ?></td>
                                <td class="fw-bold">
                                    <?php
                                    $tot_order = array_sum(array_column($p['items'], 'jumlah_order'));
                                    $tot_rec = array_sum(array_column($p['items'], 'jumlah_diterima'));
                                    echo $tot_order . " unit";
                                    if ($p['status'] == 'Diterima') {
                                        echo " <span class='text-success small'>(Rec: " . $tot_rec . ")</span>";
                                    }
                                    ?>
                                </td>
                                <td><?= date("d/m/Y", strtotime($p["tgl_order"])) ?></td>
                                <td><?= $p["tgl_estimasi_tiba"] ? date("d/m/Y", strtotime($p["tgl_estimasi_tiba"])) : "-" ?></td>
                                <td>
                                    <span class="badge bg-<?= $badgeClass ?> bg-opacity-10 text-<?= $badgeClass ?> fw-bold">
                                        <?= e($p["status"]) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($p["status"] != "Diterima" && $p["status"] != "Batal"): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_pengadaan" value="<?= e($p["id_pengadaan"]) ?>">
                                        <button type="submit" name="show_edit_pengadaan" class="btn btn-sm btn-light border fw-bold">
                                            Update
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                            endforeach;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Laporan Pengadaan Bulanan -->
        <div class="card border-0 shadow-sm rounded-4 mt-4">
            <div class="card-header bg-dark bg-opacity-10 border-0 px-4 py-3">
                <h6 class="fw-bold mb-0">📄 Laporan Pengadaan Bulanan (Stacked)</h6>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Bulan / Tahun</th>
                                <th>Total Transaksi</th>
                                <th>Status Laporan</th>
                                <th>Terakhir Diperbarui</th>
                                <th>Catatan Laporan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $qMonths = mysqli_query($conn, "
                                SELECT DISTINCT DATE_FORMAT(tgl_order, '%Y-%m') as bulan_val
                                FROM pengadaan_obat
                                ORDER BY bulan_val DESC
                            ");
                            
                            if (!$qMonths || mysqli_num_rows($qMonths) == 0) {
                                echo "<tr><td colspan='6' class='text-center py-4 text-muted'>Belum ada data pengadaan untuk dibuat laporan.</td></tr>";
                            } else {
                                while ($mRow = mysqli_fetch_assoc($qMonths)):
                                    $bVal = $mRow['bulan_val'];
                                    
                                    $qLap = mysqli_query($conn, "SELECT * FROM laporan_bulanan_pengadaan WHERE bulan_tahun = '$bVal' LIMIT 1");
                                    $lapData = ($qLap && mysqli_num_rows($qLap) > 0) ? mysqli_fetch_assoc($qLap) : null;
                                    
                                    $qCount = mysqli_query($conn, "SELECT COUNT(DISTINCT id_pengadaan) as total_pgd FROM pengadaan_obat WHERE DATE_FORMAT(tgl_order, '%Y-%m') = '$bVal'");
                                    $cRow = mysqli_fetch_assoc($qCount);
                                    $totalPgd = $cRow['total_pgd'] ?? 0;
                                    
                                    $lapStatus = $lapData ? "Tercetak" : "Draft";
                                    $badgeClass = ($lapStatus == "Tercetak") ? "success" : "warning";
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= getBulanIndo($bVal) ?></td>
                                        <td><?= $totalPgd ?> transaksi</td>
                                        <td>
                                            <span class="badge bg-<?= $badgeClass ?> bg-opacity-10 text-<?= $badgeClass ?> fw-bold">
                                                <?= $lapStatus ?>
                                            </span>
                                        </td>
                                        <td><?= $lapData ? date('d/m/Y H:i', strtotime($lapData['updated_at'])) : '-' ?></td>
                                        <td class="text-truncate" style="max-width: 200px;"><?= e($lapData['catatan'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-2">
                                                <form method="POST">
                                                    <input type="hidden" name="bulan_tahun" value="<?= e($bVal) ?>">
                                                    <button type="submit" name="show_edit_laporan_bulan" class="btn btn-sm btn-light border fw-bold">
                                                        <i class="bi bi-pencil-square me-1"></i> Edit & Cetak
                                                    </button>
                                                </form>
                                                <?php if ($lapData && !empty($lapData['file_path'])): ?>
                                                    <a href="<?= e($lapData['file_path']) ?>" class="btn btn-sm btn-outline-danger fw-bold" target="_blank">
                                                        <i class="bi bi-file-earmark-pdf"></i> PDF
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Edit Laporan Bulanan -->
        <?php if ($edit_laporan_bulan_data): ?>
        <div class="modal fade" id="modalEditLaporanBulan" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Laporan Pengadaan - <?= getBulanIndo($edit_laporan_bulan_data['bulan_tahun']) ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <input type="hidden" name="bulan_tahun" value="<?= e($edit_laporan_bulan_data['bulan_tahun']) ?>">

                        <h6 class="fw-bold mb-3 text-muted">RIWAYAT PENGADAAN BULAN INI</h6>
                        <div class="table-responsive mb-4" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>ID</th>
                                        <th>Obat</th>
                                        <th>Supplier</th>
                                        <th>Jumlah Order</th>
                                        <th>Jumlah Diterima</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($edit_laporan_bulan_data['items'] as $item): ?>
                                    <tr>
                                        <td class="fw-bold"><?= e($item['id_pengadaan']) ?></td>
                                        <td><?= e($item['nama_obat']) ?></td>
                                        <td><?= e($item['nama_supplier'] ?? '-') ?></td>
                                        <td><?= $item['jumlah_order'] ?> unit</td>
                                        <td><?= $item['status'] == 'Diterima' ? $item['jumlah_diterima'] . ' unit' : '-' ?></td>
                                        <td><?= date('d/m/Y', strtotime($item['tgl_order'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= [
                                                'Pending' => 'warning',
                                                'Proses' => 'info',
                                                'Diterima' => 'success',
                                                'Batal' => 'danger',
                                                'Expired' => 'dark'
                                            ][$item['status']] ?? 'secondary' ?> bg-opacity-10 text-<?= [
                                                'Pending' => 'warning',
                                                'Proses' => 'info',
                                                'Diterima' => 'success',
                                                'Batal' => 'danger',
                                                'Expired' => 'dark'
                                            ][$item['status']] ?? 'secondary' ?> fw-bold">
                                                <?= e($item['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-2">CATATAN LAPORAN</label>
                            <textarea name="catatan" class="form-control bg-light border-0" rows="4" 
                                      placeholder="Tambahkan catatan khusus untuk laporan bulanan ini (misal: evaluasi keterlambatan supplier, obat rusak, dll)..."><?= e($edit_laporan_bulan_data['catatan']) ?></textarea>
                            <small class="text-muted">Catatan ini akan ditampilkan di bagian bawah file PDF laporan bulanan.</small>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4 gap-2">
                        <button type="button" class="btn btn-light border py-3 fw-bold flex-grow-1" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="save_laporan_bulanan" class="btn btn-primary py-3 fw-bold flex-grow-1">
                            <i class="bi bi-printer me-1"></i> Simpan & Cetak PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const lapModal = new bootstrap.Modal(document.getElementById('modalEditLaporanBulan'));
                lapModal.show();
            });
        </script>
        <?php endif; ?>

        <!-- Modal Update Status Pengadaan -->
        <?php if ($edit_pengadaan_data): ?>
        <div class="modal fade" id="modalUpdatePengadaan" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Update Status Pengadaan</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <input type="hidden" name="id_pengadaan" value="<?= e(
                            $edit_pengadaan_data["id_pengadaan"],
                        ) ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">SUPPLIER</label>
                                <p class="form-control-plaintext mb-0"><?= e(
                                    $edit_pengadaan_data["nama_supplier"] ?? "-",
                                ) ?><?php if (
    !empty($edit_pengadaan_data["supplier_kontak"])
): ?><br><small class="text-muted">Kontak: <?= e(
    $edit_pengadaan_data["supplier_kontak"],
) ?></small><?php endif; ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">STATUS BARU</label>
                                <select name="status_baru" id="statusBaruSelect" class="form-select bg-light border-0" required>
                                    <option value="Pending" <?= $edit_pengadaan_data["status"] == "Pending" ? "selected" : "" ?>>Pending</option>
                                    <option value="Proses" <?= $edit_pengadaan_data["status"] == "Proses" ? "selected" : "" ?>>Proses</option>
                                    <option value="Diterima" <?= $edit_pengadaan_data["status"] == "Diterima" ? "selected" : "" ?>>Diterima</option>
                                    <option value="Batal" <?= $edit_pengadaan_data["status"] == "Batal" ? "selected" : "" ?>>Batal</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tabel semua item obat dalam pengadaan ini -->
                        <div class="border-top pt-3">
                            <label class="small fw-bold text-muted mb-2 d-flex align-items-center gap-2">
                                ITEM YANG DIPESAN
                                <span class="badge bg-primary bg-opacity-10 text-primary"><?= count($edit_pengadaan_data["items"]) ?> obat</span>
                            </label>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-1">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Obat</th>
                                            <th class="text-center">Jml Dipesan</th>
                                            <th class="text-center th-jumlah-terima" style="display:none;">Jml Diterima</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($edit_pengadaan_data["items"] as $item): ?>
                                        <tr>
                                            <td class="fw-bold"><?= e($item["nama_obat"] ?? "N/A") ?></td>
                                            <td class="text-center"><?= (int) $item["jumlah_order"] ?> unit</td>
                                            <td class="text-center td-terima-<?= e($item["id_obat"]) ?> td-jumlah-terima" style="display:none;">
                                                <input type="number"
                                                    name="jumlah_terima[<?= e($item["id_obat"]) ?>]"
                                                    class="form-control form-control-sm bg-light border-0 text-center jumlah-terima-input"
                                                    style="max-width: 100px; margin: 0 auto;"
                                                    value="<?= (int) $item["jumlah_order"] ?>"
                                                    min="0"
                                                    max="<?= (int) $item["jumlah_order"] ?>">
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted fst-italic hint-diterima" style="display:none;">
                                ⓘ Masukkan jumlah yang benar-benar diterima untuk setiap obat. Stok akan otomatis ditambah.
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="update_status_pengadaan" class="btn btn-primary w-100 py-3 fw-bold">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const statusBaru = document.getElementById('statusBaruSelect');
                const thHeaders = document.querySelectorAll('.th-jumlah-terima');
                const tdTerimaAll = document.querySelectorAll('.td-jumlah-terima');
                const hintDiterima = document.querySelectorAll('.hint-diterima');
                
                function toggleJumlahTerima() {
                    const isDiterima = statusBaru.value === 'Diterima';
                    thHeaders.forEach(el => el.style.display = isDiterima ? '' : 'none');
                    hintDiterima.forEach(el => el.style.display = isDiterima ? '' : 'none');
                    tdTerimaAll.forEach(td => {
                        td.style.display = isDiterima ? '' : 'none';
                        const input = td.querySelector('input');
                        if (input) input.required = isDiterima;
                    });
                }
                
                toggleJumlahTerima();
                statusBaru.addEventListener('change', toggleJumlahTerima);
                
                const editModal = new bootstrap.Modal(document.getElementById('modalUpdatePengadaan'));
                editModal.show();
            });
        </script>
        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah Pengadaan -->
        <div class="modal fade" id="modalTambahPengadaan" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Buat Pengadaan Obat</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="small fw-bold text-muted">SUPPLIER</label>
                                <select name="id_supplier" class="form-select bg-light border-0" required>
                                    <?php
                                    $qSupplier = mysqli_query(
                                        $conn,
                                        "SELECT * FROM supplierm ORDER BY nama_supplier ASC",
                                    );
                                    if (
                                        $qSupplier &&
                                        mysqli_num_rows($qSupplier) > 0
                                    ) {
                                        while (
                                            $sup = mysqli_fetch_assoc($qSupplier)
                                        ): ?>
                                    <option value="<?= e(
                                        $sup["id_supplier"],
                                    ) ?>"><?= e($sup["nama_supplier"]) ?></option>
                                    <?php endwhile;
                                    } else {
                                        echo '<option value="">(Tidak ada supplier)</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="small fw-bold text-muted">ESTIMASI TIBA</label>
                                <input type="date" name="tgl_estimasi_tiba" class="form-control bg-light border-0">
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-3">
                            <label class="small fw-bold text-muted d-flex justify-content-between align-items-center mb-2">
                                <span>ITEM OBAT YANG DIPESAN</span>
                                <button type="button" class="btn btn-sm btn-outline-primary border-0 fw-bold" id="btnTambahRowObat">
                                    <i class="bi bi-plus-circle me-1"></i> Tambah Item
                                </button>
                            </label>
                            
                            <div id="rowObatContainer">
                                <!-- Dynamic rows added by Javascript -->
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="add_pengadaan_obat" class="btn btn-primary w-100 py-3 fw-bold">
                            Buat Pengadaan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php
        // Print obat list as a JS array
        $jsObatList = [];
        $qObatList = mysqli_query($conn, "SELECT id_obat, nama_obat, stok_sekarang, stok_minimum, stok_target FROM obatm ORDER BY nama_obat ASC");
        while ($obt = mysqli_fetch_assoc($qObatList)) {
            $jsObatList[] = $obt;
        }
        ?>
        <script>
            const OBAT_LIST = <?= json_encode($jsObatList) ?>;

            function addObatRow(selectedId = '', quantity = '') {
                const container = document.getElementById('rowObatContainer');
                
                const row = document.createElement('div');
                row.className = 'row g-2 mb-2 align-items-center obat-item-row';
                
                // Select column
                const colSelect = document.createElement('div');
                colSelect.className = 'col-7';
                const select = document.createElement('select');
                select.name = 'id_obat[]';
                select.className = 'form-select bg-light border-0 row-id-obat';
                select.required = true;
                
                const optDefault = document.createElement('option');
                optDefault.value = '';
                optDefault.textContent = '-- Pilih Obat --';
                select.appendChild(optDefault);
                
                OBAT_LIST.forEach(obt => {
                    const status = (parseInt(obt.stok_sekarang) < parseInt(obt.stok_minimum)) ? '🔴' : '🟢';
                    const option = document.createElement('option');
                    option.value = obt.id_obat;
                    option.textContent = `${obt.nama_obat} - ${status} (Stok: ${obt.stok_sekarang})`;
                    if (obt.id_obat === selectedId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
                
                // Quantity column
                const colQty = document.createElement('div');
                colQty.className = 'col-3';
                const inputQty = document.createElement('input');
                inputQty.type = 'number';
                inputQty.name = 'jumlah_order[]';
                inputQty.className = 'form-control bg-light border-0 row-qty';
                inputQty.placeholder = 'Qty';
                inputQty.min = '1';
                inputQty.required = true;
                inputQty.value = quantity;
                
                // Delete button column
                const colDel = document.createElement('div');
                colDel.className = 'col-2 text-end';
                const btnDel = document.createElement('button');
                btnDel.type = 'button';
                btnDel.className = 'btn btn-sm btn-outline-danger border-0';
                btnDel.innerHTML = '<i class="bi bi-trash"></i>';
                btnDel.onclick = function() {
                    if (container.children.length > 1) {
                        row.remove();
                    } else {
                        alert('Minimal harus ada 1 obat yang dipesan!');
                    }
                };
                
                colSelect.appendChild(select);
                colQty.appendChild(inputQty);
                colDel.appendChild(btnDel);
                
                row.appendChild(colSelect);
                row.appendChild(colQty);
                row.appendChild(colDel);
                
                container.appendChild(row);
            }

            function pesanSingleObat(btn) {
                const idObat = btn.getAttribute('data-id-obat');
                const saranJumlah = btn.getAttribute('data-saran-jumlah');
                const container = document.getElementById('rowObatContainer');
                container.innerHTML = ''; // Reset
                addObatRow(idObat, saranJumlah);
            }

            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('rowObatContainer');
                if (container && container.children.length === 0) {
                    addObatRow();
                }
                
                const btnAddRow = document.getElementById('btnTambahRowObat');
                if (btnAddRow) {
                    btnAddRow.addEventListener('click', () => {
                        addObatRow();
                    });
                }

                // Validasi duplikat obat sebelum submit
                const formPengadaan = document.querySelector('form[method="POST"] #rowObatContainer');
                if (formPengadaan) {
                    formPengadaan.closest('form').addEventListener('submit', function(e) {
                        const selects = container.querySelectorAll('select[name="id_obat[]"]');
                        const seen = {};
                        let hasDup = false;

                        selects.forEach(sel => {
                            sel.closest('.obat-item-row').style.outline = '';
                        });

                        selects.forEach(sel => {
                            const val = sel.value;
                            if (!val) return;
                            if (seen[val]) {
                                hasDup = true;
                                seen[val].closest('.obat-item-row').style.outline = '2px solid #dc3545';
                                sel.closest('.obat-item-row').style.outline = '2px solid #dc3545';
                            } else {
                                seen[val] = sel;
                            }
                        });

                        if (hasDup) {
                            e.preventDefault();
                            alert('⚠️ Ada obat yang dipilih lebih dari sekali! Hapus duplikat (ditandai merah) sebelum menyimpan.');
                        }
                    });
                }

                // Checkbox Select All Low Stock
                const chkAll = document.getElementById('chkSelectAllLowStock');
                if (chkAll) {
                    chkAll.addEventListener('change', function() {
                        const chks = document.querySelectorAll('.low-stock-checkbox');
                        chks.forEach(chk => {
                            chk.checked = chkAll.checked;
                        });
                    });
                }

                // Pesan Terpilih Button
                const btnPesanTerpilih = document.getElementById('btnPesanTerpilih');
                if (btnPesanTerpilih) {
                    btnPesanTerpilih.addEventListener('click', function() {
                        const checked = document.querySelectorAll('.low-stock-checkbox:checked');
                        if (checked.length === 0) {
                            alert('Silakan pilih minimal satu obat kurang stok!');
                            return;
                        }
                        
                        container.innerHTML = ''; // Reset
                        checked.forEach(chk => {
                            const idObat = chk.getAttribute('data-id-obat');
                            const saranJumlah = chk.getAttribute('data-saran-jumlah');
                            addObatRow(idObat, saranJumlah);
                        });
                        
                        const addModal = new bootstrap.Modal(document.getElementById('modalTambahPengadaan'));
                        addModal.show();
                    });
                }
            });
        </script>

