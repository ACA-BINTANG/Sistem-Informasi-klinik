<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark">Halo, <?= e(
        explode(" ", $pasien_name)[0],
    ) ?>!</h3>
    <span class="text-muted small fw-bold text-uppercase"><i class="bi bi-calendar3 me-1"></i> <?= date(
        "d M Y",
    ) ?></span>
</div>

<div class="row g-4">
    <!-- KOLOM KIRI: TIKET ANTREAN -->
    <div class="col-lg-5">
        <?php
        $q_my = mysqli_query(
            $conn,
            "
    SELECT * FROM rekam_medis 
    WHERE id_pasien = '$id_pasien' 
    AND status IN ('Menunggu', 'Darurat') 
    AND tgl_kunjungan >= CURDATE() -- TAMBAHKAN INI: Hanya ambil tiket hari ini atau yang akan datang
    ORDER BY tgl_kunjungan ASC, waktu_booking ASC 
    LIMIT 1
",
        );

        if ($q_my && mysqli_num_rows($q_my) > 0):

            $d_my = mysqli_fetch_assoc($q_my);
            $posisi_antrian = hitungPosisiAntrean(
                $conn,
                $d_my["id_rekam_medis"],
                $d_my["tgl_kunjungan"],
            );
            $is_darurat = $d_my["status"] == "Darurat";
            ?>
            <div class="antrean-card shadow-lg <?= $is_darurat
                ? "emergency"
                : "" ?>">
                <div class="mb-2">
                    <span class="status-badge-tiket">
                        <?= $is_darurat
                            ? "🚨 Prioritas Darurat"
                            : "Antrean Normal" ?>
                    </span>
                </div>
                
                <small class="opacity-75 text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 1px;">Nomor Antrean Anda</small>
                <div class="antrean-number"><?= e($d_my["no_antrian"]) ?></div>

                <div class="mb-3">
                    <span class="badge bg-white text-dark px-3 py-1 rounded-pill fw-bold" style="font-size: 11px;">
                        <?= e($d_my["jenis_antrean"]) ?> — <?= e(
     strtoupper($d_my["status"]),
 ) ?>
                    </span>
                </div>

                <div class="small opacity-75 mb-3">
                    <i class="bi bi-clock me-1"></i> <?= e(
                        date("d M Y", strtotime($d_my["tgl_kunjungan"])),
                    ) ?> • <?= e(substr($d_my["waktu_booking"], 0, 5)) ?>
                </div>

                <div class="bg-white bg-opacity-10 rounded-4 p-2 border border-white border-opacity-25">
                    <p class="mb-0 small">Posisi Antrean Sekarang:</p>
                    <h4 class="fw-800 mb-0"><?= e(
                        $posisi_antrian ?? "-",
                    ) ?></h4>
                </div>

                <?php if ($d_my["jenis_antrean"] == "Jadwal"): ?>
                    <form method="POST" class="mt-3 js-swal-confirm" data-swal-title="Batalkan Booking?" data-swal-text="Data booking akan dihapus dari antrean." data-swal-confirm="Ya, Batalkan">
                        <input type="hidden" name="id_rekam_medis" value="<?= e(
                            $d_my["id_rekam_medis"],
                        ) ?>">
                        <button type="submit" name="batal_booking" class="btn btn-sm btn-light text-danger fw-bold rounded-pill px-3">
                            <i class="bi bi-x-circle me-1"></i> Batal Booking
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php
        else:
             ?>
            <div class="antrean-card shadow-lg opacity-50" style="filter: grayscale(1); background: #64748b;">
                <h6 class="fw-bold opacity-75 text-uppercase">Nomor Antrean</h6>
                <div class="antrean-number">--</div>
                <p class="mb-0 small opacity-75">Belum ada antrean aktif.</p>
            </div>
        <?php
        endif;
        ?>
    </div>
    
    <!-- KOLOM KANAN: STATS & PANDUAN -->
    <div class="col-lg-7">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div>
                        <div class="small fw-bold text-muted mb-1">TOTAL BEROBAT</div>
                        <div class="h2 fw-bold text-primary mb-0">
                            <?= mysqli_num_rows(
                                mysqli_query(
                                    $conn,
                                    "SELECT id_rekam_medis FROM rekam_medis WHERE id_pasien='$id_pasien' AND status='Selesai'",
                                ),
                            ) ?>
                        </div>
                    </div>
                    <i class="bi bi-clipboard2-pulse fs-2 text-primary opacity-25"></i>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card" style="border-left-color: #1cc88a;">
                    <div>
                        <div class="small fw-bold text-muted mb-1">DATA TERDAFTAR</div>
                        <div class="h5 fw-bold text-success mb-0">PROFIL AKTIF</div>
                    </div>
                    <i class="bi bi-shield-check fs-2 text-success opacity-25"></i>
                </div>
            </div>
        </div>

        <div class="data-container py-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle-fill text-primary me-2"></i>Panduan Layanan</h6>
            <ul class="list-unstyled small text-muted mb-0">
                <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i><strong>Antrean langsung</strong> hanya saat jam praktik dokter.</li>
                <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i><strong>Emergency</strong> otomatis diprioritaskan sistem.</li>
                <li><i class="bi bi-check2-circle text-success me-2"></i>Satu akun hanya bisa memiliki 1 antrean aktif.</li>
            </ul>
        </div>
    </div>
</div>
