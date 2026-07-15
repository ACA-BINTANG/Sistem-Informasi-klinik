<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

        <h4 class="fw-bold mb-4">Arsip Riwayat Medis</h4>
        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Tanggal / Jam</th><th>Jenis</th><th>Keluhan</th><th>Diagnosa</th><th class="text-center">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $qr = mysqli_query(
                            $conn,
                            "
                            SELECT rm.*, d.nama_penyakit 
                            FROM rekam_medis rm 
                            LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa 
                            WHERE rm.id_pasien = '$id_pasien' 
                            ORDER BY rm.tgl_kunjungan DESC, rm.waktu_booking DESC
                        ",
                        );

                        if ($qr && mysqli_num_rows($qr) == 0) {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Belum ada riwayat berobat.</td></tr>";
                        }

                        if ($qr) {
                            while ($row = mysqli_fetch_assoc($qr)): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold small"><?= e(
                                        date(
                                            "d M Y",
                                            strtotime($row["tgl_kunjungan"]),
                                        ),
                                    ) ?></div>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= e(
                                        substr($row["waktu_booking"], 0, 5),
                                    ) ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e(
                                    $row["jenis_antrean"] ?? "Langsung",
                                ) ?></span></td>
                                <td><div style="max-width: 250px;" class="small text-truncate" title="<?= e(
                                    $row["keluhan"],
                                ) ?>"><?= e($row["keluhan"]) ?></div></td>
                                <td><span class="badge <?= $row["status"] ==
                                "Selesai"
                                    ? "bg-primary bg-opacity-10 text-primary"
                                    : "bg-warning bg-opacity-10 text-warning" ?> px-3"><?= e(
     $row["nama_penyakit"] ?? "N/A",
 ) ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#mDetail<?= e(
                                        $row["id_rekam_medis"],
                                    ) ?>">
                                        <i class="bi bi-eye me-1"></i>Detail
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="mDetail<?= e(
                                $row["id_rekam_medis"],
                            ) ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                                        <div class="modal-header bg-light border-0 p-4">
                                            <h6 class="fw-bold mb-0">Catatan Medis: <?= e(
                                                date(
                                                    "d/m/Y",
                                                    strtotime(
                                                        $row["tgl_kunjungan"],
                                                    ),
                                                ),
                                            ) ?></h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4 text-start">
                                            <label class="small fw-bold text-muted text-uppercase mb-1">Jenis Antrean:</label>
                                            <h6 class="fw-bold mb-3"><?= e(
                                                $row["jenis_antrean"] ??
                                                    "Langsung",
                                            ) ?></h6>
                                            <label class="small fw-bold text-muted text-uppercase mb-1">Diagnosa:</label>
                                            <h5 class="text-primary fw-bold"><?= e(
                                                $row["nama_penyakit"] ??
                                                    "Menunggu Pemeriksaan",
                                            ) ?></h5>
                                            <hr>
                                            <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Hasil Pemeriksaan & Resep:</label>
                                            <div class="p-3 bg-light rounded-3 text-muted" style="font-size: 13.5px;">
                                                <?= nl2br(
                                                    e(
                                                        $row[
                                                            "hasil_pemeriksaan"
                                                        ] ??
                                                            "Dokter belum mengisi catatan medis untuk antrean ini.",
                                                    ),
                                                ) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

