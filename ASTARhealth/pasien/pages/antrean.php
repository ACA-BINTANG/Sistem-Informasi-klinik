<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

        <h4 class="fw-bold mb-4">Ambil Antrean Langsung</h4>

        <?php
        $hari_ini_info = hariIndonesiaDariTanggal(date("Y-m-d"));
        $jam_info = date("H:i:s");
        $cekJadwalInfo = mysqli_query(
            $conn,
            "
                SELECT *
                FROM jadwalm
                WHERE tanggal = '$hari_ini_info'
                AND status = 'Buka'
                AND jam_mulai <= '$jam_info'
                AND jam_selesai > '$jam_info'
                LIMIT 1
            ",
        );
        $dokter_sedang_buka =
            $cekJadwalInfo && mysqli_num_rows($cekJadwalInfo) > 0;
        ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="data-container shadow-sm">
                    <?php if ($dokter_sedang_buka): ?>
                        <div class="alert alert-success border-0 rounded-4 small fw-bold">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            Dokter sedang buka. Anda bisa mengambil antrean langsung sekarang.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning border-0 rounded-4 small fw-bold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Dokter sedang tidak buka untuk antrean langsung. Silakan booking di Jadwal Dokter.
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">APA YANG ANDA RASAKAN SAAT INI?</label>
                            <textarea name="keluhan" class="form-control bg-light border-0 p-3 shadow-none" rows="7" placeholder="Contoh: Merasa sesak nafas dan nyeri di dada..." required style="border-radius: 15px;"></textarea>
                            <div class="form-text mt-2 small italic text-primary"><i class="bi bi-lightbulb me-1"></i>Antrean langsung akan memakai jam saat ini dan dicek dengan jadwal dokter.</div>
                        </div>

                        <?php if ($dokter_sedang_buka): ?>
                            <button type="submit" name="ambil_antrean" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow">
                                <i class="bi bi-plus-circle me-2"></i> Dapatkan Nomor Antrean
                            </button>
                        <?php else: ?>
                            <a href="index.php?page=jadwal_dokter" class="btn btn-warning w-100 py-3 rounded-4 fw-bold shadow">
                                <i class="bi bi-calendar-week me-2"></i> Booking Jadwal Dokter
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="p-4 rounded-4 bg-white border border-danger border-opacity-25 shadow-sm">
                    <h6 class="fw-bold text-danger mb-3"><i class="bi bi-lightning-charge-fill me-2"></i>Daftar Gejala Darurat</h6>
                    <p class="small text-muted mb-3">Jika keluhan Anda mengandung kata berikut, sistem akan memberikan <strong>Prioritas Penanganan</strong>:</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Sesak</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Pingsan</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Pendarahan</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Jantung</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Kecelakaan</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Epilepsi</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="data-container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Antrean Saya</h5>
                    <small class="text-muted">Setiap halaman menampilkan maksimal 10 data antrean.</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal / Jam</th>
                            <th>No. Antrean</th>
                            <th>Jenis</th>
                            <th>Keluhan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $qRiwayatAntrean = mysqli_query(
                            $conn,
                            "SELECT id_rekam_medis, tgl_kunjungan, waktu_booking, no_antrian, jenis_antrean, keluhan, status
                             FROM rekam_medis
                             WHERE id_pasien = '$id_pasien'
                             ORDER BY tgl_kunjungan DESC, waktu_booking DESC, id_rekam_medis DESC"
                        );

                        if (!$qRiwayatAntrean || mysqli_num_rows($qRiwayatAntrean) === 0):
                        ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Belum ada riwayat antrean.</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($antreanSaya = mysqli_fetch_assoc($qRiwayatAntrean)): ?>
                                <?php
                                $statusAntrean = (string) ($antreanSaya['status'] ?? '-');
                                $warnaStatusAntrean = match ($statusAntrean) {
                                    'Selesai' => 'success',
                                    'Darurat' => 'danger',
                                    'Diproses' => 'warning',
                                    'Batal' => 'secondary',
                                    default => 'primary',
                                };
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold small"><?= e(date('d M Y', strtotime($antreanSaya['tgl_kunjungan']))) ?></div>
                                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= e(substr((string) $antreanSaya['waktu_booking'], 0, 5)) ?></small>
                                    </td>
                                    <td><span class="fw-bold text-primary"><?= e($antreanSaya['no_antrian'] ?: '-') ?></span></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($antreanSaya['jenis_antrean'] ?: '-') ?></span></td>
                                    <td><div class="small text-truncate" style="max-width:320px" title="<?= e($antreanSaya['keluhan'] ?: '-') ?>"><?= e($antreanSaya['keluhan'] ?: '-') ?></div></td>
                                    <td><span class="badge bg-<?= e($warnaStatusAntrean) ?> bg-opacity-10 text-<?= e($warnaStatusAntrean) ?> px-3 py-2"><?= e($statusAntrean) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

