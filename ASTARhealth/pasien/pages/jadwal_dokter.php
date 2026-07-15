<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">Jadwal Dokter</h4>
                <small class="text-muted">Pilih hari jadwal dokter, lalu pilih jam booking</small>
            </div>
        </div>

        <div class="row g-4" data-astar-list-pagination>
            <?php
            $qJadwal = mysqli_query(
                $conn,
                "
                SELECT id_jadwal, id_staff, tanggal, jam_mulai, jam_selesai, status
                FROM jadwalm
                WHERE status = 'Buka'
                ORDER BY FIELD(tanggal, 'Senin','Selasa','Rabu','Kamis','Jumat'), jam_mulai ASC
            ",
            );

            $modal_booking = [];

            if (!$qJadwal) {
                echo "<div class='col-12'><div class='alert alert-danger rounded-4 border-0 shadow-sm'>Query error: " .
                    e(mysqli_error($conn)) .
                    "</div></div>";
            } else {
                if (mysqli_num_rows($qJadwal) == 0) {
                    echo "
                        <div class='col-12'>
                            <div class='data-container text-center py-5'>
                                <i class='bi bi-calendar-x text-muted' style='font-size:4rem;'></i>
                                <h5 class='fw-bold mt-3'>Belum Ada Jadwal Dokter Buka</h5>
                                <p class='text-muted mb-0'>Silakan cek lagi nanti.</p>
                            </div>
                        </div>
                    ";
                }

                while ($j = mysqli_fetch_assoc($qJadwal)):
                    $modal_booking[] = $j; ?>
                    <div class="col-md-4" data-astar-pagination-item>
                        <div class="card jadwal-card h-100">
                            <div class="jadwal-date">
                                <div class="jadwal-day"><?= e(
                                    $j["tanggal"],
                                ) ?></div>
                                <div class="small opacity-75">Status: Buka</div>
                            </div>

                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <small class="text-muted fw-bold text-uppercase">Dokter</small>
                                    <div class="fw-bold">Dokter ASTARhealth</div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted fw-bold text-uppercase">Jam Praktik</small>
                                    <div class="jadwal-time"><?= e(
                                        substr($j["jam_mulai"], 0, 5),
                                    ) ?> - <?= e(
     substr($j["jam_selesai"], 0, 5),
 ) ?></div>
                                </div>

                                <button class="btn btn-primary w-100 rounded-4 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#mBooking<?= e(
                                    $j["id_jadwal"],
                                ) ?>">
                                    <i class="bi bi-ticket-perforated me-2"></i>Booking Antrean
                                </button>
                            </div>
                        </div>
                    </div>
            <?php
                endwhile;
            }
            ?>
        </div>

        <?php foreach ($modal_booking as $j): ?>
            <div class="modal fade" id="mBooking<?= e(
                $j["id_jadwal"],
            ) ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content border-0 shadow-lg" style="border-radius:24px" method="POST">
                        <div class="modal-header bg-primary text-white border-0 py-4">
                            <h5 class="fw-bold mb-0"><i class="bi bi-ticket-perforated me-2"></i>Booking Antrean</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body p-4">
                            <input type="hidden" name="id_jadwal" value="<?= e(
                                $j["id_jadwal"],
                            ) ?>">

                            <div class="mb-3">
                                <label class="small fw-bold text-muted">JADWAL</label>
                                <input type="text" class="form-control bg-light border-0 py-3" value="<?= e(
                                    $j["tanggal"],
                                ) ?>, <?= e(
    substr($j["jam_mulai"], 0, 5),
) ?> - <?= e(substr($j["jam_selesai"], 0, 5)) ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold text-muted">PILIH JAM BOOKING</label>
                                <input type="time"
                                       name="jam_booking"
                                       class="form-control bg-light border-0 py-3"
                                       min="<?= e(
                                           substr($j["jam_mulai"], 0, 5),
                                       ) ?>"
                                       max="<?= e(
                                           date(
                                               "H:i",
                                               strtotime($j["jam_selesai"]) -
                                                   60,
                                           ),
                                       ) ?>"
                                       required>
                                <div class="form-text small text-muted">
                                    Pilih jam sesuai jam buka dokter. Tanggal kunjungan otomatis mengikuti hari <strong><?= e(
                                        $j["tanggal"],
                                    ) ?></strong> terdekat.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold text-muted">KELUHAN</label>
                                <textarea name="keluhan_booking"
                                          class="form-control bg-light border-0 py-3"
                                          rows="4"
                                          placeholder="Contoh: Demam, pusing, batuk, sakit perut..."
                                          required></textarea>
                                <div class="form-text small text-muted">
                                    Isi keluhan dengan teks biasa. Diagnosa akan ditentukan oleh dokter saat pemeriksaan.
                                </div>
                            </div>

                            <div class="alert alert-info border-0 rounded-4 small mb-0">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                Booking akan masuk ke Rekam Medis sebagai antrean jadwal dengan status Menunggu.
                            </div>
                        </div>

                        <div class="modal-footer border-0 px-4 pb-4 pt-0">
                            <button type="button" class="btn btn-light py-3 fw-bold rounded-4" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="ambil_antrean_jadwal" class="btn btn-primary py-3 fw-bold rounded-4 flex-fill">Booking Antrean</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="data-container mt-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle-fill text-primary me-2"></i>Informasi Sistem Antrean</h6>
            <p class="text-muted small mb-0">
                Emergency tetap paling utama. Booking akan diprioritaskan saat jam booking sudah tiba. Antrean langsung hanya bisa diambil saat dokter sedang buka.
            </p>
        </div>

