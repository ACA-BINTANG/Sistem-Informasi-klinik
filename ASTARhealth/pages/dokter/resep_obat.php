<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

    <?php
    $kolomPasienResepSiap = ensureResepDokterPasienColumn($conn);

    $pasienResepOptions = [];
    $qPasienResep = mysqli_query($conn, "
        SELECT id_pasien, nama_pasien, no_identitas
        FROM pasienm
        ORDER BY nama_pasien ASC
    ");
    if ($qPasienResep) {
        while ($ps = mysqli_fetch_assoc($qPasienResep)) {
            $pasienResepOptions[] = $ps;
        }
    }

    $obatResepOptions = [];
    $qObatResep = mysqli_query($conn, "
        SELECT id_obat, nama_obat, stok_sekarang
        FROM obatm
        ORDER BY nama_obat ASC
    ");
    if ($qObatResep) {
        while ($ob = mysqli_fetch_assoc($qObatResep)) {
            $obatResepOptions[] = $ob;
        }
    }
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Resep Obat</h3>
            <small class="text-muted">Data resep dari pemeriksaan pasien dan input langsung tampil jadi satu.</small>
        </div>

        <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahResepObat">
            <i class="bi bi-plus-circle me-2"></i> Add Resep
        </button>
    </div>

    <?php if (!$kolomPasienResepSiap) { ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 fw-bold mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Kolom <code>id_pasien</code> di tabel <code>resep_dokter</code> belum bisa dibuat otomatis. Jalankan SQL:
            <div class="mt-2 small bg-light text-dark p-2 rounded-3">
                ALTER TABLE resep_dokter ADD COLUMN id_pasien VARCHAR(20) NULL AFTER id_resep;
            </div>
        </div>
    <?php } ?>

    <div class="data-container mb-4">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="resep_obat">

            <div class="col-md-3">
                <label class="small fw-bold text-muted text-uppercase">Cari Pasien</label>
                <div class="input-group">
                    <span class="input-group-text border-0 bg-light"><i class="bi bi-person-search"></i></span>
                    <input type="text" name="search_pasien" class="form-control border-0 bg-light" placeholder="Nama / NIM / NIK..." value="<?= e($_GET["search_pasien"] ?? "") ?>">
                </div>
            </div>

            <div class="col-md-3">
                <label class="small fw-bold text-muted text-uppercase">Nama Obat</label>
                <div class="input-group">
                    <span class="input-group-text border-0 bg-light"><i class="bi bi-capsule"></i></span>
                    <input type="text" name="search_obat" class="form-control border-0 bg-light" placeholder="Nama obat..." value="<?= e($_GET["search_obat"] ?? "") ?>">
                </div>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-muted text-uppercase">Dari Tanggal</label>
                <input type="date" name="tgl_mulai" id="resep_tgl_mulai" class="form-control border-0 bg-light" value="<?= e($_GET["tgl_mulai"] ?? "") ?>">
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-muted text-uppercase">Sampai Tanggal</label>
                <input type="date" name="tgl_akhir" id="resep_tgl_akhir" class="form-control border-0 bg-light" value="<?= e($_GET["tgl_akhir"] ?? "") ?>">
            </div>

            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">Filter</button>
                <a href="?page=resep_obat" class="btn btn-light border w-100 fw-bold"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>

    <div class="data-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal / ID Resep</th>
                        <th>Pasien</th>
                        <th>Obat Diberikan</th>
                        <th class="text-center">Jumlah</th>
                        <th>Satuan</th>
                        <th>Resep / Aturan Pakai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $limitResep = 10;
                    $pageResep = isset($_GET["halaman"]) ? (int) $_GET["halaman"] : 1;
                    if ($pageResep < 1) {
                        $pageResep = 1;
                    }
                    $offsetResep = ($pageResep - 1) * $limitResep;
                    $noRsp = $offsetResep + 1;

                    $where_resep = ["1=1"];

                    if (!empty($_GET["search_pasien"])) {
                        $sp = mysqli_real_escape_string($conn, $_GET["search_pasien"]);
                        $where_resep[] = "(
                            COALESCE(p_direct.nama_pasien, p_rm.nama_pasien) LIKE '%$sp%'
                            OR COALESCE(p_direct.no_identitas, p_rm.no_identitas) LIKE '%$sp%'
                            OR rd.id_rekam_medis LIKE '%$sp%'
                        )";
                    }

                    if (!empty($_GET["search_obat"])) {
                        $so = mysqli_real_escape_string($conn, $_GET["search_obat"]);
                        $where_resep[] = "o.nama_obat LIKE '%$so%'";
                    }

                    if (!empty($_GET["tgl_mulai"]) && !empty($_GET["tgl_akhir"])) {
                        $tm = mysqli_real_escape_string($conn, $_GET["tgl_mulai"]);
                        $ta = mysqli_real_escape_string($conn, $_GET["tgl_akhir"]);
                        if ($ta >= $tm) {
                            $where_resep[] = "rm.tgl_kunjungan BETWEEN '$tm' AND '$ta'";
                        }
                    }

                    $sql_where = implode(" AND ", $where_resep);

                    $sqlJoinResep = "
                        FROM resep_dokter rd
                        LEFT JOIN rekam_medis rm ON rd.id_rekam_medis = rm.id_rekam_medis
                        LEFT JOIN pasienm p_rm ON rm.id_pasien = p_rm.id_pasien
                        LEFT JOIN pasienm p_direct ON rd.id_pasien = p_direct.id_pasien
                        LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa
                        LEFT JOIN obatm o ON rd.id_obat = o.id_obat
                    ";

                    $qTotalResep = mysqli_query($conn, "
                        SELECT COUNT(*) AS total
                        $sqlJoinResep
                        WHERE $sql_where
                    ");

                    $totalResep = 0;
                    if ($qTotalResep) {
                        $dTotalResep = mysqli_fetch_assoc($qTotalResep);
                        $totalResep = (int) ($dTotalResep["total"] ?? 0);
                    }

                    $totalHalamanResep = max(1, (int) ceil($totalResep / $limitResep));
                    if ($pageResep > $totalHalamanResep) {
                        $pageResep = $totalHalamanResep;
                        $offsetResep = ($pageResep - 1) * $limitResep;
                        $noRsp = $offsetResep + 1;
                    }

                    $qResep = mysqli_query($conn, "
                        SELECT
                            rd.id_resep,
                            rd.id_rekam_medis,
                            rd.id_pasien AS id_pasien_langsung,
                            rd.id_obat,
                            rd.jumlah_keluar,
                            rd.catatan_obat,
                            rm.no_antrian,
                            rm.tgl_kunjungan,
                            rm.hasil_pemeriksaan,
                            d.nama_penyakit,
                            COALESCE(p_direct.nama_pasien, p_rm.nama_pasien) AS nama_pasien,
                            COALESCE(p_direct.no_identitas, p_rm.no_identitas) AS no_identitas,
                            o.nama_obat,
                            o.satuan
                        $sqlJoinResep
                        WHERE $sql_where
                        ORDER BY
                            CASE WHEN rm.tgl_kunjungan IS NULL THEN 1 ELSE 0 END ASC,
                            rm.tgl_kunjungan DESC,
                            rd.id_resep DESC
                        LIMIT $limitResep OFFSET $offsetResep
                    ");

                    if (!$qResep) {
                        echo "<tr><td colspan='7' class='text-center text-danger py-4'>Query error: " . e(mysqli_error($conn)) . "</td></tr>";
                    } elseif (mysqli_num_rows($qResep) == 0) {
                        echo "<tr><td colspan='7' class='text-center py-5 text-muted'>Data transaksi resep obat tidak ditemukan.</td></tr>";
                    }

                    if ($qResep) {
                        while ($r = mysqli_fetch_assoc($qResep)) {
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $noRsp++ ?></td>
                        <td>
                            <?php if (!empty($r["tgl_kunjungan"])) { ?>
                                <div class="fw-bold"><?= date("d M Y", strtotime($r["tgl_kunjungan"])) ?></div>
                                <small class="text-muted"><?= e($r["id_resep"]) ?></small>
                                <br><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 mt-1">Dari pemeriksaan</span>
                            <?php } else { ?>
                                <div class="fw-bold text-muted">Input langsung</div>
                                <small class="text-muted"><?= e($r["id_resep"]) ?></small>
                            <?php } ?>
                        </td>
                        <td>
                            <div class="fw-bold"><?= e($r["nama_pasien"] ?? "-") ?></div>
                            <small class="text-primary fw-600"><?= e($r["no_identitas"] ?? "-") ?></small>
                            <?php if (!empty($r["nama_penyakit"])) { ?>
                                <br><small class="text-muted">Diagnosa: <?= e($r["nama_penyakit"]) ?></small>
                            <?php } ?>
                            <?php if (!empty($r["no_antrian"])) { ?>
                                <br><small class="text-muted">Antrean: <?= e($r["no_antrian"]) ?></small>
                            <?php } ?>
                        </td>
                        <td class="fw-bold text-dark"><?= e($r["nama_obat"] ?? "-") ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3"><?= e($r["jumlah_keluar"] ?? 0) ?></span>
                        </td>
                        <td class="small text-muted"><?= e($r["satuan"] ?? "-") ?></td>
                        <td>
                            <?php
                            $teksResep = trim($r["catatan_obat"] ?? "");
                            if ($teksResep == "" && !empty($r["hasil_pemeriksaan"])) {
                                $teksResep = $r["hasil_pemeriksaan"];
                            }
                            ?>
                            <div class="small" style="max-width: 280px;"><?= e($teksResep != "" ? $teksResep : "-") ?></div>
                        </td>
                    </tr>
                    <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php
        $queryPagination = $_GET;
        $queryPagination["page"] = "resep_obat";
        ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
            <div class="small text-muted fw-bold">
                Total data: <?= e($totalResep) ?> | Halaman <?= e($pageResep) ?> dari <?= e($totalHalamanResep) ?> | 10 data per halaman
            </div>

            <?php if ($totalHalamanResep > 1) { ?>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $queryPagination["halaman"] = max(1, $pageResep - 1);
                        $prevUrl = "?" . http_build_query($queryPagination);
                        ?>
                        <li class="page-item <?= $pageResep <= 1 ? "disabled" : "" ?>">
                            <a class="page-link" href="<?= e($prevUrl) ?>">Sebelumnya</a>
                        </li>

                        <?php
                        $startPage = max(1, $pageResep - 2);
                        $endPage = min($totalHalamanResep, $pageResep + 2);
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            $queryPagination["halaman"] = $i;
                            $pageUrl = "?" . http_build_query($queryPagination);
                        ?>
                            <li class="page-item <?= $i == $pageResep ? "active" : "" ?>">
                                <a class="page-link" href="<?= e($pageUrl) ?>"><?= $i ?></a>
                            </li>
                        <?php } ?>

                        <?php
                        $queryPagination["halaman"] = min($totalHalamanResep, $pageResep + 1);
                        $nextUrl = "?" . http_build_query($queryPagination);
                        ?>
                        <li class="page-item <?= $pageResep >= $totalHalamanResep ? "disabled" : "" ?>">
                            <a class="page-link" href="<?= e($nextUrl) ?>">Berikutnya</a>
                        </li>
                    </ul>
                </nav>
            <?php } ?>
        </div>
    </div>

    <div class="modal fade" id="modalTambahResepObat" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
                <div class="modal-header bg-primary text-white border-0 p-4">
                    <div>
                        <h5 class="modal-title fw-bold mb-1"><i class="bi bi-receipt-cutoff me-2"></i>Tambah Resep Obat</h5>
                        <small class="opacity-75">Pilih pasien, pilih obat, isi jumlah, lalu tulis resep.</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="small fw-bold text-muted text-uppercase">Pasien</label>
                            <select name="id_pasien" id="select_resep_pasien" class="form-select searchable-select" data-placeholder="Ketik nama pasien atau NIM/NIK..." required>
                                <option value=""></option>
                                <?php foreach ($pasienResepOptions as $ps): ?>
                                    <option value="<?= e($ps["id_pasien"]) ?>">
                                        <?= e($ps["nama_pasien"]) ?> - <?= e($ps["no_identitas"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-7">
                            <label class="small fw-bold text-muted text-uppercase">Obat</label>
                            <select name="id_obat" id="select_resep_obat" class="form-select searchable-select" data-placeholder="Ketik nama obat..." required>
                                <option value=""></option>
                                <?php foreach ($obatResepOptions as $ob): ?>
                                    <option value="<?= e($ob["id_obat"]) ?>" data-stock="<?= e($ob["stok_sekarang"]) ?>">
                                        <?= e($ob["nama_obat"]) ?> - Stok: <?= e($ob["stok_sekarang"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-5">
                            <label class="small fw-bold text-muted text-uppercase">Jumlah Obat</label>
                            <input type="number" name="jumlah_keluar" class="form-control bg-light border-0" min="1" value="1" required>
                        </div>

                        <div class="col-md-12">
                            <label class="small fw-bold text-muted text-uppercase">Resep / Aturan Pakai</label>
                            <textarea name="catatan_obat" class="form-control bg-light border-0" rows="4" required placeholder="Contoh: 3x1 setelah makan"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" name="add_resep_dokter" class="btn btn-primary fw-bold px-4"><i class="bi bi-save me-1"></i> Simpan Resep</button>
                </div>
            </form>
        </div>
    </div>

