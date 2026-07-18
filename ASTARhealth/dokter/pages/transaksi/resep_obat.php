<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

    <?php
    $kolomPasienResepSiap = ensureResepDokterPasienColumn($conn);
    $kolomTanggalResepSiap = ensureResepDokterTanggalColumn($conn);
    $tabelDiagnosaResepSiap = ensureResepDiagnosaTable($conn);

    // Jika database lama belum dapat diubah otomatis, tampilan tetap aman memakai tanggal hari ini.
    $tanggalResepLangsungSql = $kolomTanggalResepSiap
        ? "DATE(rd.tanggal_resep)"
        : "CURDATE()";
    $tanggalResepUrutSql = "COALESCE(rm.tgl_kunjungan, $tanggalResepLangsungSql)";

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

    $diagnosaResepOptions = [];
    $qDiagnosaResep = mysqli_query($conn, "
        SELECT id_diagnosa, nama_penyakit, kategori, tipe
        FROM diagnosam
        ORDER BY nama_penyakit ASC
    ");
    if ($qDiagnosaResep) {
        while ($dg = mysqli_fetch_assoc($qDiagnosaResep)) {
            $diagnosaResepOptions[] = $dg;
        }
    }
    ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Resep Obat</h3>
            <small class="text-muted">Data resep dari pemeriksaan pasien dan input langsung tampil jadi satu.</small>
        </div>
        <div class="d-flex gap-2 no-print">
            <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahResepObat"><i class="bi bi-plus-circle me-2"></i>Tambah Resep</button>
        </div>
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

    <?php if (!$kolomTanggalResepSiap) { ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4 fw-bold mb-4">
            <i class="bi bi-calendar-x-fill me-2"></i>
            Kolom tanggal transaksi resep langsung belum dapat dibuat otomatis. Import file
            <code>DB/update_tanggal_resep_langsung.sql</code> melalui phpMyAdmin.
        </div>
    <?php } ?>

    <?php if (!$tabelDiagnosaResepSiap) { ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 fw-bold mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Tabel penyimpanan penyakit resep belum tersedia. Import file
            <code>DB/update_resep_multi_penyakit.sql</code> melalui phpMyAdmin.
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
                <a href="?page=resep_obat" class="btn btn-light border w-100 fw-bold">Atur Ulang</a>
            </div>
        </form>
    </div>

    <div class="data-container" id="printResepHistory">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal / ID Resep</th>
                        <th>Pasien</th>
                        <th>Penyakit / Keluhan</th>
                        <th>Obat Diberikan</th>
                        <th class="text-center">Jumlah</th>
                        <th>Satuan</th>
                        <th>Resep / Aturan Pakai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Seluruh data dimuat, kemudian pagination global membaginya 10 baris per halaman.
                    $noRsp = 1;

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
                            $where_resep[] = "$tanggalResepUrutSql BETWEEN '$tm' AND '$ta'";
                        }
                    }

                    $sql_where = implode(" AND ", $where_resep);

                    $sqlJoinResep = "
                        FROM resep_dokter rd
                        LEFT JOIN rekam_medis rm ON rd.id_rekam_medis = rm.id_rekam_medis
                        LEFT JOIN pasienm p_rm ON rm.id_pasien = p_rm.id_pasien
                        LEFT JOIN pasienm p_direct ON rd.id_pasien = p_direct.id_pasien
                        LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa
                        LEFT JOIN (
                            SELECT
                                rdg.id_resep,
                                GROUP_CONCAT(
                                    DISTINCT dg.nama_penyakit
                                    ORDER BY dg.nama_penyakit
                                    SEPARATOR '||'
                                ) AS daftar_penyakit
                            FROM resep_diagnosa rdg
                            JOIN diagnosam dg ON rdg.id_diagnosa = dg.id_diagnosa
                            GROUP BY rdg.id_resep
                        ) d_direct ON rd.id_resep = d_direct.id_resep
                        LEFT JOIN obatm o ON rd.id_obat = o.id_obat
                    ";

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
                            $tanggalResepLangsungSql AS tanggal_resep_langsung,
                            $tanggalResepUrutSql AS tanggal_resep_tampil,
                            rm.hasil_pemeriksaan,
                            COALESCE(d_direct.daftar_penyakit, d.nama_penyakit) AS daftar_penyakit,
                            COALESCE(p_direct.nama_pasien, p_rm.nama_pasien) AS nama_pasien,
                            COALESCE(p_direct.no_identitas, p_rm.no_identitas) AS no_identitas,
                            o.nama_obat,
                            o.satuan
                        $sqlJoinResep
                        WHERE $sql_where
                        ORDER BY
                            $tanggalResepUrutSql DESC,
                            rd.id_resep DESC
                    ");

                    if (!$qResep) {
                        echo "<tr><td colspan='8' class='text-center text-danger py-4'>Query error: " . e(mysqli_error($conn)) . "</td></tr>";
                    } elseif (mysqli_num_rows($qResep) == 0) {
                        echo "<tr><td colspan='8' class='text-center py-5 text-muted'>Data transaksi resep obat tidak ditemukan.</td></tr>";
                    }

                    if ($qResep) {
                        while ($r = mysqli_fetch_assoc($qResep)) {
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $noRsp++ ?></td>
                        <td>
                            <?php
                            $tanggalResepTampil = $r["tanggal_resep_tampil"] ?? null;
                            $sumberResep = !empty($r["tgl_kunjungan"])
                                ? "Dari pemeriksaan"
                                : "Input langsung";
                            ?>
                            <div class="fw-bold">
                                <?= $tanggalResepTampil
                                    ? date("d M Y", strtotime($tanggalResepTampil))
                                    : "-" ?>
                            </div>
                            <small class="text-muted"><?= e($r["id_resep"]) ?></small>
                            <br><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 mt-1"><?= e($sumberResep) ?></span>
                        </td>
                        <td>
                            <?php
                            $namaPasienResep = trim((string) ($r["nama_pasien"] ?? ""));
                            $identitasPasienResep = trim((string) ($r["no_identitas"] ?? ""));
                            ?>
                            <div class="fw-bold"><?= e($namaPasienResep !== "" ? $namaPasienResep : "-") ?></div>
                            <small class="text-primary fw-600"><?= e($identitasPasienResep !== "" ? $identitasPasienResep : "-") ?></small>
                            <?php if (!empty($r["no_antrian"])) { ?>
                                <br><small class="text-muted">Antrean: <?= e($r["no_antrian"]) ?></small>
                            <?php } ?>
                        </td>
                        <td>
                            <?php
                            $daftarPenyakit = array_values(array_filter(
                                explode("||", (string) ($r["daftar_penyakit"] ?? "")),
                                fn($nama) => trim($nama) !== "",
                            ));
                            ?>
                            <?php if (empty($daftarPenyakit)) { ?>
                                <span class="text-muted small">-</span>
                            <?php } else { ?>
                                <div class="d-flex flex-wrap gap-1" style="min-width: 170px;">
                                    <?php foreach ($daftarPenyakit as $namaPenyakit) { ?>
                                        <span class="badge rounded-pill bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25">
                                            <?= e(trim($namaPenyakit)) ?>
                                        </span>
                                    <?php } ?>
                                </div>
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

    </div>

    <div class="modal fade" id="modalTambahResepObat" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" id="formTambahResepObat" class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
                <div class="modal-header bg-primary text-white border-0 p-4">
                    <div>
                        <h5 class="modal-title fw-bold mb-1"><i class="bi bi-receipt-cutoff me-2"></i>Tambah Resep Obat</h5>
                        <small class="opacity-75">Pilih pasien jika ada, satu atau lebih penyakit, dan satu atau lebih obat beserta jumlah dan aturan pakainya.</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="small fw-bold text-muted text-uppercase">Pasien <span class="fw-normal text-muted">(Opsional)</span></label>
                            <select name="id_pasien" id="select_resep_pasien" class="form-select searchable-select" data-placeholder="Pilih pasien jika ada...">
                                <option value=""></option>
                                <?php foreach ($pasienResepOptions as $ps): ?>
                                    <option value="<?= e($ps["id_pasien"]) ?>">
                                        <?= e($ps["nama_pasien"]) ?> - <?= e($ps["no_identitas"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="small fw-bold text-muted text-uppercase mb-0">Penyakit / Keluhan</label>
                                <span class="badge bg-light text-primary border">Minimal 1 penyakit</span>
                            </div>

                            <div id="resepDiagnosisContainer" class="d-flex flex-column gap-2">
                                <div class="resep-diagnosis-row d-flex align-items-start gap-2">
                                    <div class="flex-grow-1">
                                        <select name="id_diagnosa[]" class="form-select resep-diagnosa-select" data-placeholder="Pilih penyakit atau keluhan...">
                                            <option value=""></option>
                                            <?php foreach ($diagnosaResepOptions as $dg): ?>
                                                <option value="<?= e($dg["id_diagnosa"]) ?>">
                                                    <?= e($dg["nama_penyakit"]) ?>
                                                    <?php if (!empty($dg["kategori"]) || !empty($dg["tipe"])): ?>
                                                        - <?= e(trim(($dg["kategori"] ?? "") . " " . ($dg["tipe"] ?? ""))) ?>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="button" id="btnTambahDiagnosaResep" class="btn btn-outline-primary" title="Tambah penyakit lain">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Klik tombol <strong>+</strong> jika pasien memiliki lebih dari satu penyakit atau keluhan.
                            </small>

                            <template id="templateDiagnosaResep">
                                <div class="resep-diagnosis-row d-flex align-items-start gap-2">
                                    <div class="flex-grow-1">
                                        <select name="id_diagnosa[]" class="form-select resep-diagnosa-select" data-placeholder="Pilih penyakit atau keluhan...">
                                            <option value=""></option>
                                            <?php foreach ($diagnosaResepOptions as $dg): ?>
                                                <option value="<?= e($dg["id_diagnosa"]) ?>">
                                                    <?= e($dg["nama_penyakit"]) ?>
                                                    <?php if (!empty($dg["kategori"]) || !empty($dg["tipe"])): ?>
                                                        - <?= e(trim(($dg["kategori"] ?? "") . " " . ($dg["tipe"] ?? ""))) ?>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-hapus-diagnosa-resep" title="Hapus penyakit ini">
                                        <i class="bi bi-dash-lg"></i>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="small fw-bold text-muted text-uppercase mb-0">Obat</label>
                                <span class="badge bg-light text-primary border">Minimal 1 obat</span>
                            </div>

                            <div id="resepObatLangsungContainer" class="d-flex flex-column gap-3">
                                <div class="resep-obat-langsung-row border rounded-4 p-3 bg-light bg-opacity-50">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-6">
                                            <label class="small fw-bold text-muted">NAMA OBAT</label>
                                            <select name="id_obat[]" class="form-select resep-obat-langsung-select" data-placeholder="Ketik nama obat...">
                                                <option value=""></option>
                                                <?php foreach ($obatResepOptions as $ob): ?>
                                                    <option value="<?= e($ob["id_obat"]) ?>" data-stock="<?= e($ob["stok_sekarang"]) ?>">
                                                        <?= e($ob["nama_obat"]) ?> - Stok: <?= e($ob["stok_sekarang"]) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small fw-bold text-muted">JUMLAH</label>
                                            <input type="number" name="jumlah_keluar[]" class="form-control bg-white border-0 jumlah-resep-langsung" min="1" value="1" title="Jumlah obat harus lebih dari 0">
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="button" id="btnTambahObatResepLangsung" class="btn btn-outline-primary fw-bold">
                                                <i class="bi bi-plus-lg me-1"></i> Tambah Obat
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="small fw-bold text-muted">RESEP / ATURAN PAKAI</label>
                                        <textarea name="catatan_obat[]" class="form-control bg-white border-0 catatan-resep-langsung" rows="2" placeholder="Contoh: 3x1 setelah makan"></textarea>
                                    </div>
                                </div>
                            </div>

                            <small class="text-muted d-block mt-2">Klik <strong>Tambah Obat</strong> untuk memasukkan lebih dari satu obat dalam sekali simpan.</small>

                            <template id="templateObatResepLangsung">
                                <div class="resep-obat-langsung-row border rounded-4 p-3 bg-light bg-opacity-50">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-6">
                                            <label class="small fw-bold text-muted">NAMA OBAT</label>
                                            <select name="id_obat[]" class="form-select resep-obat-langsung-select" data-placeholder="Ketik nama obat...">
                                                <option value=""></option>
                                                <?php foreach ($obatResepOptions as $ob): ?>
                                                    <option value="<?= e($ob["id_obat"]) ?>" data-stock="<?= e($ob["stok_sekarang"]) ?>">
                                                        <?= e($ob["nama_obat"]) ?> - Stok: <?= e($ob["stok_sekarang"]) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small fw-bold text-muted">JUMLAH</label>
                                            <input type="number" name="jumlah_keluar[]" class="form-control bg-white border-0 jumlah-resep-langsung" min="1" value="1" title="Jumlah obat harus lebih dari 0">
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="button" class="btn btn-outline-danger fw-bold btn-hapus-obat-resep-langsung">
                                                <i class="bi bi-dash-lg me-1"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="small fw-bold text-muted">RESEP / ATURAN PAKAI</label>
                                        <textarea name="catatan_obat[]" class="form-control bg-white border-0 catatan-resep-langsung" rows="2" placeholder="Contoh: 3x1 setelah makan"></textarea>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" name="add_resep_dokter" class="btn btn-primary fw-bold px-4" <?= (!$kolomPasienResepSiap || !$tabelDiagnosaResepSiap) ? "disabled" : "" ?>><i class="bi bi-save me-1"></i> Simpan Resep</button>
                </div>
            </form>
        </div>
    </div>

