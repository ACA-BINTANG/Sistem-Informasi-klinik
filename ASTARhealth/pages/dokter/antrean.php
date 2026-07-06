<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Antrean Pasien</h3>
                <small class="text-muted">Pasien darurat otomatis tampil paling atas.</small>
            </div>

            <span class="badge bg-primary px-3 py-2 rounded-pill">
                <?= e(hariIniIndonesia()) ?>, <?= date("d M Y") ?>
            </span>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">TOTAL MENUNGGU HARI INI</div>
                        <div class="h2 fw-bold text-primary mb-0">
                            <?php
                            $qTotal = mysqli_query(
                                $conn,
                                "
                                SELECT id_rekam_medis
                                FROM rekam_medis
                                WHERE id_staff = '$id_dokter'
                                AND tgl_kunjungan = CURDATE()
                                AND status IN ('Menunggu','Darurat')
                            ",
                            );
                            echo $qTotal ? mysqli_num_rows($qTotal) : 0;
                            ?>
                        </div>
                    </div>
                    <i class="bi bi-ticket-perforated fs-1 text-primary opacity-25"></i>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card danger">
                    <div>
                        <div class="small text-muted fw-bold">DARURAT HARI INI</div>
                        <div class="h2 fw-bold text-danger mb-0">
                            <?php
                            $qDarurat = mysqli_query(
                                $conn,
                                "
                                SELECT id_rekam_medis
                                FROM rekam_medis
                                WHERE id_staff = '$id_dokter'
                                AND tgl_kunjungan = CURDATE()
                                AND status = 'Darurat'
                            ",
                            );
                            echo $qDarurat ? mysqli_num_rows($qDarurat) : 0;
                            ?>
                        </div>
                    </div>
                    <i class="bi bi-lightning-charge-fill fs-1 text-danger opacity-25"></i>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card success">
                    <div>
                        <div class="small text-muted fw-bold">SELESAI HARI INI</div>
                        <div class="h2 fw-bold text-success mb-0">
                            <?php
                            $qSelesai = mysqli_query(
                                $conn,
                                "
                                SELECT id_rekam_medis
                                FROM rekam_medis
                                WHERE id_staff = '$id_dokter'
                                AND tgl_kunjungan = CURDATE()
                                AND status = 'Selesai'
                            ",
                            );
                            echo $qSelesai ? mysqli_num_rows($qSelesai) : 0;
                            ?>
                        </div>
                    </div>
                    <i class="bi bi-check2-circle fs-1 text-success opacity-25"></i>
                </div>
            </div>
        </div>

        <div class="data-container">
            <h5 class="fw-bold mb-4">
                <i class="bi bi-list-check text-primary me-2"></i>Daftar Antrean Aktif
            </h5>

            <?php
            $qAntrean = mysqli_query(
                $conn,
                "SELECT rm.*, p.nama_pasien, p.no_identitas, p.kategori_pasien, p.unit_prodi
                FROM rekam_medis rm
                JOIN pasienm p ON rm.id_pasien = p.id_pasien
                WHERE rm.id_staff = '$id_dokter'
                AND rm.tgl_kunjungan = CURDATE()
                AND rm.status IN ('Menunggu','Darurat')
                ORDER BY
                    CASE WHEN rm.jenis_antrean = 'Langsung' THEN 1 ELSE 2 END ASC,
                    rm.tgl_kunjungan ASC,
                    rm.waktu_booking ASC,
                    CAST(SUBSTRING(rm.no_antrian, 2) AS UNSIGNED) ASC
                ",
            );

            if (!$qAntrean) {
                echo "<div class='col-12'><div class='alert alert-danger'>Query error: " .
                    e(mysqli_error($conn)) .
                    "</div></div>";
            } elseif (mysqli_num_rows($qAntrean) == 0) {
                echo "
                        <div class='col-12'>
                            <div class='text-center py-5 text-muted'>
                                <i class='bi bi-inbox' style='font-size:4rem;'></i>
                                <h5 class='fw-bold mt-3'>Belum Ada Antrean Aktif</h5>
                                <p class='mb-0'>Semua antrean sudah selesai atau belum ada pasien.</p>
                            </div>
                        </div>
                    ";
            }

            if ($qAntrean) {
                while ($r = mysqli_fetch_assoc($qAntrean)): ?>
<div class="col-12 mb-3">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative hover-shadow" 
         style="transition: all 0.3s ease; border-left: 5px solid <?= $r[
             "status"
         ] == "Darurat"
             ? "#dc3545"
             : "#0057B8" ?> !important;">
        
        <div class="card-body p-3">
            <div class="row align-items-center">
                
                <!-- SISI KIRI: NOMOR & WAKTU -->
                <div class="col-md-2 text-center border-end">
                    <div class="display-6 fw-bold text-primary mb-0"><?= e(
                        $r["no_antrian"],
                    ) ?></div>
                    <div class="badge bg-light text-dark rounded-pill shadow-sm">
                        <i class="bi bi-clock me-1 text-primary"></i> <?= e(
                            substr($r["waktu_booking"], 0, 5),
                        ) ?>
                    </div>
                </div>

                <!-- SISI TENGAH: INFO PASIEN -->
                <div class="col-md-4 ps-4">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h5 class="fw-bold mb-0 text-dark"><?= e(
                            $r["nama_pasien"],
                        ) ?></h5>
                        <?php if ($r["status"] == "Darurat"): ?>
                            <span class="badge bg-danger">EMERGENCY</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small">
                        <span class="text-primary fw-600"><?= e(
                            $r["no_identitas"],
                        ) ?></span> • <?= e($r["kategori_pasien"]) ?> • <?= e(
     $r["unit_prodi"],
 ) ?>
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-soft-primary text-primary border-0 rounded-pill px-3" style="font-size: 10px; background-color: #eef4ff;">
                            <i class="bi bi-person-badge me-1"></i> <?= e(
                                $r["jenis_antrean"],
                            ) ?>
                        </span>
                    </div>
                </div>

                <!-- SISI KANAN: KELUHAN -->
                <div class="col-md-3">
                    <label class="small fw-bold text-muted text-uppercase" style="font-size: 10px;">Keluhan Utama:</label>
                    <p class="small text-dark mb-0 text-truncate-2" title="<?= e(
                        $r["keluhan"],
                    ) ?>">
                        "<?= e($r["keluhan"]) ?>"
                    </p>
                </div>

                <!-- AKSI -->
                <div class="col-md-3 text-end">
                    <div class="d-flex gap-2 justify-content-end">
                        <button class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#modalPeriksa<?= e(
                                    $r["id_rekam_medis"],
                                ) ?>">
                            <i class="bi bi-clipboard2-pulse me-2"></i> Periksa
                        </button>

                        <form method="POST" onsubmit="return confirm('Batalkan antrean?')">
                            <input type="hidden" name="id_rekam_medis" value="<?= e(
                                $r["id_rekam_medis"],
                            ) ?>">
                            <button type="submit" name="batal_antrean" class="btn btn-outline-danger border-0 rounded-3">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

                    <?php include __DIR__ . '/pemeriksaan.php'; ?>
                <?php endwhile;
            }
            ?>
            </div>
        </div>

