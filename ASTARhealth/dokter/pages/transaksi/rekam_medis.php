<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Rekam Medis Pasien</h3>
            <small class="text-muted">Data riwayat pemeriksaan pasien yang telah selesai.</small>
        </div>
    </div>

    <!-- BOX FILTER & PENCARIAN -->
    <div class="data-container mb-4">
        <form method="GET" class="row g-3" id="formFilterRekamMedis">
            <input type="hidden" name="page" value="rekam_medis">
            
            <!-- Cari Nama/NIM -->
            <div class="col-md-4">
                <label class="small fw-bold text-muted text-uppercase">Cari Pasien</label>
                <div class="input-group">
                    <span class="input-group-text border-0 bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-0 bg-light" placeholder="Nama atau NIM..." value="<?= $_GET[
                        "search"
                    ] ?? "" ?>">
                </div>
            </div>

            <!-- Filter Status -->
            <div class="col-md-2">
                <label class="small fw-bold text-muted text-uppercase">Status</label>
                <select name="status" class="form-select border-0 bg-light">
                    <option value="">Semua</option>
                    <option value="Selesai" <?= ($_GET["status"] ?? "") === "Selesai" ? "selected" : "" ?>>Selesai</option>
                    <option value="Batal" <?= ($_GET["status"] ?? "") === "Batal" ? "selected" : "" ?>>Batal</option>
                    <option value="Darurat" <?= ($_GET["status"] ?? "") === "Darurat" ? "selected" : "" ?>>Darurat</option>
                </select>
            </div>

<!-- Filter Tanggal Mulai -->
<div class="col-md-2">
    <label class="small fw-bold text-muted text-uppercase">Dari Tanggal</label>
    <input type="date" name="tgl_mulai" id="filter_tgl_mulai" class="form-control border-0 bg-light" value="<?= $_GET[
        "tgl_mulai"
    ] ?? "" ?>">
</div>

<!-- Filter Tanggal Selesai -->
<div class="col-md-2">
    <label class="small fw-bold text-muted text-uppercase">Sampai Tanggal</label>
    <input type="date" name="tgl_akhir" id="filter_tgl_akhir" class="form-control border-0 bg-light" value="<?= $_GET[
        "tgl_akhir"
    ] ?? "" ?>">
</div>

            <!-- Tombol Aksi -->
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">Filter</button>
                <a href="?page=rekam_medis" class="btn btn-light border w-100 fw-bold">Atur Ulang</a>
            </div>
        </form>
    </div>

    <!-- TABEL DATA -->
    <div class="data-container" id="printRekamMedisHistory">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>No Antrean</th>
                        <th>Pasien</th>
                        <th>Diagnosa</th>
                        <th>Status</th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;

                    $where_clauses = ["rm.id_staff = '$id_dokter'"];

                    if (!empty($_GET["search"])) {
                        $s = mysqli_real_escape_string($conn, $_GET["search"]);
                        $where_clauses[] = "(p.nama_pasien LIKE '%$s%' OR p.no_identitas LIKE '%$s%')";
                    }

                    $statusFilter = (string) ($_GET["status"] ?? "");
                    if ($statusFilter === "Selesai") {
                        $where_clauses[] = "rm.status = 'Selesai'";
                    } elseif ($statusFilter === "Batal") {
                        $where_clauses[] = "rm.status = 'Batal'";
                    } elseif ($statusFilter === "Darurat") {
                        // Darurat adalah riwayat kondisi, bukan status akhir.
                        // Pasien yang pernah darurat tetap muncul walau sekarang sudah Selesai/Batal.
                        $where_clauses[] = "rm.pernah_darurat = 1";
                    }

                    if (
                        !empty($_GET["tgl_mulai"]) &&
                        !empty($_GET["tgl_akhir"])
                    ) {
                        $tm = mysqli_real_escape_string(
                            $conn,
                            $_GET["tgl_mulai"],
                        );
                        $ta = mysqli_real_escape_string(
                            $conn,
                            $_GET["tgl_akhir"],
                        );

                        if ($ta >= $tm) {
                            $where_clauses[] = "rm.tgl_kunjungan BETWEEN '$tm' AND '$ta'";
                        } else {
                        }
                    }

                    $where_sql = implode(" AND ", $where_clauses);

                    $qRM = mysqli_query(
                        $conn,
                        "
                        SELECT rm.*, p.nama_pasien, p.no_identitas, d.nama_penyakit
                        FROM rekam_medis rm
                        JOIN pasienm p ON rm.id_pasien = p.id_pasien
                        LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa
                        WHERE $where_sql
                        ORDER BY rm.created_at DESC, rm.tgl_kunjungan DESC, rm.waktu_booking DESC, rm.id_rekam_medis DESC
                    ",
                    );

                    if (mysqli_num_rows($qRM) == 0) {
                        echo "<tr><td colspan='7' class='text-center py-5 text-muted'>Data tidak ditemukan atau filter tidak cocok.</td></tr>";
                    }

                    while ($rm = mysqli_fetch_assoc($qRM)): ?>
                    <tr>
                        <td class="text-muted small"><?= $no++ ?></td>
                        <td>
                            <div class="fw-bold"><?= date(
                                "d M Y",
                                strtotime($rm["tgl_kunjungan"]),
                            ) ?></div>
                            <small class="text-muted"><?= substr(
                                $rm["waktu_booking"],
                                0,
                                5,
                            ) ?></small>
                        </td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3"><?= $rm[
                            "no_antrian"
                        ] ?></span></td>
                        <td>
                            <div class="fw-bold"><?= e(
                                $rm["nama_pasien"],
                            ) ?></div>
                            <small class="text-muted text-primary fw-bold"><?= e(
                                $rm["no_identitas"],
                            ) ?></small>
                        </td>
                        <td class="small fw-600"><?= e(
                            $rm["nama_penyakit"] ?? "Belum Diagnosa",
                        ) ?></td>
                        <td>
                            <?php $badge =
                                $rm["status"] == "Selesai"
                                    ? "success"
                                    : "danger"; ?>
                            <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> px-3"><?= e($rm["status"]) ?></span>
                            <?php if ((int) ($rm["pernah_darurat"] ?? 0) === 1 && $rm["status"] !== "Darurat"): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger px-3 ms-1">Pernah Darurat</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#modalDetailRM<?= $rm[
                                "id_rekam_medis"
                            ] ?>">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </td>
                    </tr>
                                                <div class="modal fade" id="modalDetailRM<?= e(
                                                    $rm["id_rekam_medis"],
                                                ) ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                                        <div class="modal-header bg-light border-0 p-4">
                                            <h5 class="fw-bold mb-0">Detail Rekam Medis</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <label class="small fw-bold text-muted">PASIEN</label>
                                                    <div class="fw-bold"><?= e(
                                                        $rm["nama_pasien"],
                                                    ) ?></div>
                                                    <small class="text-muted"><?= e(
                                                        $rm["no_identitas"],
                                                    ) ?></small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="small fw-bold text-muted">TANGGAL / JAM</label>
                                                    <div class="fw-bold">
                                                        <?= e(
                                                            date(
                                                                "d M Y",
                                                                strtotime(
                                                                    $rm[
                                                                        "tgl_kunjungan"
                                                                    ],
                                                                ),
                                                            ),
                                                        ) ?>,
                                                        <?= e(
                                                            substr(
                                                                $rm[
                                                                    "waktu_booking"
                                                                ],
                                                                0,
                                                                5,
                                                            ),
                                                        ) ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <hr>

                                            <label class="small fw-bold text-muted">KELUHAN</label>
                                            <div class="p-3 bg-light rounded-4 mb-3"><?= nl2br(
                                                e($rm["keluhan"]),
                                            ) ?></div>

                                            <label class="small fw-bold text-muted">DIAGNOSA</label>
                                            <div class="p-3 bg-light rounded-4 mb-3"><?= e(
                                                $rm["nama_penyakit"] ??
                                                    "Belum ada",
                                            ) ?></div>

                                            <label class="small fw-bold text-muted">HASIL PEMERIKSAAN</label>
                                            <div class="p-3 bg-light rounded-4 mb-3"><?= nl2br(
                                                e(
                                                    $rm["hasil_pemeriksaan"] ??
                                                        "Belum ada catatan pemeriksaan",
                                                ),
                                            ) ?></div>

                                            <label class="small fw-bold text-muted">RESEP / CATATAN OBAT</label>
                                            <div class="p-3 bg-light rounded-4">
                                                <?php
                                                $id_rm_detail = mysqli_real_escape_string(
                                                    $conn,
                                                    $rm["id_rekam_medis"],
                                                );

                                                $qResep = mysqli_query(
                                                    $conn,
                                                    "
                                                    SELECT rd.*, o.nama_obat, o.satuan
                                                    FROM resep_dokter rd
                                                    LEFT JOIN obatm o ON rd.id_obat = o.id_obat
                                                    WHERE rd.id_rekam_medis = '$id_rm_detail'
                                                ",
                                                );

                                                if (
                                                    $qResep &&
                                                    mysqli_num_rows($qResep) > 0
                                                ) {
                                                    while (
                                                        $rsp = mysqli_fetch_assoc(
                                                            $qResep,
                                                        )
                                                    ) {
                                                        echo "<div class='mb-2'>";
                                                        echo "<div class='fw-bold'>" .
                                                            e(
                                                                $rsp[
                                                                    "nama_obat"
                                                                ] ??
                                                                    "Catatan tanpa obat",
                                                            ) .
                                                            "</div>";
                                                        echo "<small class='text-muted'>Jumlah: " .
                                                            e(
                                                                $rsp[
                                                                    "jumlah_keluar"
                                                                ],
                                                            ) .
                                                            " " .
                                                            e(
                                                                $rsp[
                                                                    "satuan"
                                                                ] ?? "",
                                                            ) .
                                                            "</small>";
                                                        echo "<div class='small'>" .
                                                            nl2br(
                                                                e(
                                                                    $rsp[
                                                                        "catatan_obat"
                                                                    ] ?? "-",
                                                                ),
                                                            ) .
                                                            "</div>";
                                                        echo "</div>";
                                                    }
                                                } else {
                                                    echo "<span class='text-muted'>-</span>";
                                                }
                                                ?>
                                                </div>
                    <?php endwhile;
                    ?>
                </tbody>
            </table>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formFilterRekamMedis');
    const tanggalMulai = document.getElementById('filter_tgl_mulai');
    const tanggalAkhir = document.getElementById('filter_tgl_akhir');

    if (!form || !tanggalMulai || !tanggalAkhir) return;

    function hapusTandaError(input) {
        input.classList.remove('is-invalid');
        input.removeAttribute('aria-invalid');
    }

    tanggalMulai.addEventListener('change', function () {
        if (tanggalMulai.value) hapusTandaError(tanggalMulai);
    });

    tanggalAkhir.addEventListener('change', function () {
        if (tanggalAkhir.value) hapusTandaError(tanggalAkhir);
    });

    form.addEventListener('submit', function (event) {
        const mulai = tanggalMulai.value.trim();
        const akhir = tanggalAkhir.value.trim();

        // Jika salah satu tanggal diisi, pasangan tanggalnya juga wajib diisi.
        if ((mulai && !akhir) || (!mulai && akhir)) {
            event.preventDefault();
            event.stopImmediatePropagation();

            const inputKosong = !mulai ? tanggalMulai : tanggalAkhir;
            inputKosong.classList.add('is-invalid');
            inputKosong.setAttribute('aria-invalid', 'true');

            const pesan = !mulai
                ? 'Silakan pilih tanggal mulai terlebih dahulu.'
                : 'Silakan pilih tanggal akhir terlebih dahulu.';

            if (window.ASTARSwal) {
                ASTARSwal.warning(pesan, 'Tanggal Belum Lengkap').then(function () {
                    inputKosong.focus();
                });
            } else if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tanggal Belum Lengkap',
                    text: pesan,
                    confirmButtonText: 'Oke'
                }).then(function () {
                    inputKosong.focus();
                });
            }
            return;
        }

        // Rentang tanggal juga harus logis.
        if (mulai && akhir && akhir < mulai) {
            event.preventDefault();
            event.stopImmediatePropagation();
            tanggalMulai.classList.add('is-invalid');
            tanggalAkhir.classList.add('is-invalid');

            if (window.ASTARSwal) {
                ASTARSwal.warning(
                    'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.',
                    'Rentang Tanggal Tidak Sesuai'
                ).then(function () {
                    tanggalAkhir.focus();
                });
            }
        }
    });

    // Saat halaman baru dibuka, jangan tampilkan border merah atau peringatan otomatis.
    // Validasi visual hanya dijalankan setelah pengguna menekan tombol Kirim/Filter.
    hapusTandaError(tanggalMulai);
    hapusTandaError(tanggalAkhir);
});
</script>

