<?php
// Menampilkan seluruh antrean aktif dokter pada semua tanggal.
// Antrean tanpa dokter juga ditampilkan agar data lama/orphan dapat dibersihkan.

function namaHariAntreanIndonesia(string $tanggal): string
{
    $namaHari = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    $timestamp = strtotime($tanggal);
    return $timestamp ? ($namaHari[date('l', $timestamp)] ?? '-') : '-';
}

function kategoriTanggalAntrean(string $tanggal): array
{
    $hariIni = date('Y-m-d');
    $besok = date('Y-m-d', strtotime('+1 day'));
    $kemarin = date('Y-m-d', strtotime('-1 day'));

    if ($tanggal === $hariIni) {
        return ['Hari Ini', 'primary', 'bi-calendar-check-fill'];
    }
    if ($tanggal === $besok) {
        return ['Besok', 'info', 'bi-calendar-plus-fill'];
    }
    if ($tanggal === $kemarin) {
        return ['Kemarin', 'warning', 'bi-calendar-minus-fill'];
    }
    if ($tanggal > $hariIni) {
        return ['Jadwal Mendatang', 'info', 'bi-calendar-event'];
    }
    if ($tanggal !== '') {
        return ['Antrean Terlewat', 'danger', 'bi-calendar-x-fill'];
    }

    return ['Tanggal Tidak Tersedia', 'secondary', 'bi-calendar2-x'];
}

$qAntrean = mysqli_query(
    $conn,
    "SELECT
        rm.*,
        p.nama_pasien,
        p.no_identitas,
        p.kategori_pasien,
        p.unit_prodi
    FROM rekam_medis rm
    LEFT JOIN pasienm p ON rm.id_pasien = p.id_pasien
    WHERE (rm.id_staff = '$id_dokter' OR rm.id_staff IS NULL OR rm.id_staff = '')
      AND rm.status IN ('Menunggu', 'Darurat', 'Diproses')
    ORDER BY
        CASE
            WHEN rm.tgl_kunjungan = CURDATE() THEN 0
            WHEN rm.tgl_kunjungan > CURDATE() THEN 1
            ELSE 2
        END ASC,
        CASE WHEN rm.tgl_kunjungan >= CURDATE() THEN rm.tgl_kunjungan END ASC,
        CASE WHEN rm.tgl_kunjungan < CURDATE() THEN rm.tgl_kunjungan END DESC,
        CASE
            WHEN rm.status = 'Darurat' THEN 0
            WHEN rm.status = 'Diproses' THEN 1
            ELSE 2
        END ASC,
        rm.waktu_booking ASC,
        CAST(SUBSTRING(rm.no_antrian, 2) AS UNSIGNED) ASC",
);

$antreanSemua = [];
$queryAntreanError = '';
if (!$qAntrean) {
    $queryAntreanError = mysqli_error($conn);
} else {
    while ($rowAntrean = mysqli_fetch_assoc($qAntrean)) {
        $antreanSemua[] = $rowAntrean;
    }
}

$totalAktif = count($antreanSemua);
$totalHariIni = 0;
$totalTerlewat = 0;
$kelompokTanggal = [];

foreach ($antreanSemua as $itemAntrean) {
    $tanggalAntrean = (string) ($itemAntrean['tgl_kunjungan'] ?? '');
    if ($tanggalAntrean === date('Y-m-d')) {
        $totalHariIni++;
    } elseif ($tanggalAntrean !== '' && $tanggalAntrean < date('Y-m-d')) {
        $totalTerlewat++;
    }

    $keyTanggal = $tanggalAntrean !== '' ? $tanggalAntrean : 'tanpa-tanggal';
    $kelompokTanggal[$keyTanggal][] = $itemAntrean;
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Antrean Pasien</h3>
        <small class="text-muted">Seluruh antrean aktif ditampilkan berdasarkan hari, termasuk antrean yang sudah lewat.</small>
    </div>

    <span class="badge bg-primary px-3 py-2 rounded-pill">
        <?= e(hariIniIndonesia()) ?>, <?= date('d M Y') ?>
    </span>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div>
                <div class="small text-muted fw-bold">TOTAL ANTREAN AKTIF</div>
                <div class="h2 fw-bold text-primary mb-0"><?= e($totalAktif) ?></div>
            </div>
            <i class="bi bi-ticket-perforated fs-1 text-primary opacity-25"></i>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card success">
            <div>
                <div class="small text-muted fw-bold">ANTREAN HARI INI</div>
                <div class="h2 fw-bold text-success mb-0"><?= e($totalHariIni) ?></div>
            </div>
            <i class="bi bi-calendar-check fs-1 text-success opacity-25"></i>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card danger">
            <div>
                <div class="small text-muted fw-bold">ANTREAN TERLEWAT</div>
                <div class="h2 fw-bold text-danger mb-0"><?= e($totalTerlewat) ?></div>
            </div>
            <i class="bi bi-calendar-x fs-1 text-danger opacity-25"></i>
        </div>
    </div>
</div>

<?php if ($queryAntreanError !== ''): ?>
    <div class="data-container text-center py-5">
        <i class="bi bi-exclamation-triangle text-danger" style="font-size:3rem;"></i>
        <h5 class="fw-bold mt-3">Data antrean gagal dimuat</h5>
        <p class="text-muted mb-0"><?= e($queryAntreanError) ?></p>
    </div>
<?php elseif (empty($antreanSemua)): ?>
    <div class="data-container text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:4rem;"></i>
        <h5 class="fw-bold mt-3">Belum Ada Antrean Aktif</h5>
        <p class="mb-0">Antrean berstatus Menunggu, Darurat, atau Diproses akan muncul di sini.</p>
    </div>
<?php else: ?>
    <div data-astar-list-pagination>
    <?php foreach ($kelompokTanggal as $tanggalKelompok => $daftarAntrean): ?>
        <?php
        $tanggalAsli = $tanggalKelompok === 'tanpa-tanggal' ? '' : $tanggalKelompok;
        [$kategoriHari, $warnaHari, $ikonHari] = kategoriTanggalAntrean($tanggalAsli);
        $judulTanggal = $tanggalAsli !== ''
            ? namaHariAntreanIndonesia($tanggalAsli) . ', ' . date('d M Y', strtotime($tanggalAsli))
            : 'Tanggal tidak tersedia';
        ?>

        <section class="data-container mb-4" data-astar-pagination-group>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-<?= e($warnaHari) ?> bg-opacity-10 text-<?= e($warnaHari) ?> d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                        <i class="bi <?= e($ikonHari) ?> fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5"><?= e($judulTanggal) ?></div>
                        <small class="text-muted"><?= count($daftarAntrean) ?> pasien pada tanggal ini</small>
                    </div>
                </div>
                <span class="badge rounded-pill bg-<?= e($warnaHari) ?> px-3 py-2"><?= e($kategoriHari) ?></span>
            </div>

            <div class="row">
                <?php foreach ($daftarAntrean as $r): ?>
                    <?php
                    $pasienTersedia = !empty($r['nama_pasien']);
                    $status = (string) ($r['status'] ?? 'Menunggu');
                    $warnaStatus = $status === 'Darurat' ? '#dc3545' : ($status === 'Diproses' ? '#f59e0b' : '#0057B8');
                    ?>
                    <div class="col-12 mb-3" data-astar-pagination-item>
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative hover-shadow"
                             style="transition:all .3s ease;border-left:5px solid <?= e($warnaStatus) ?> !important;">
                            <div class="card-body p-3">
                                <div class="row align-items-center g-3">
                                    <div class="col-md-2 text-center border-end">
                                        <div class="display-6 fw-bold text-primary mb-0"><?= e($r['no_antrian'] ?: '-') ?></div>
                                        <div class="badge bg-light text-dark rounded-pill shadow-sm">
                                            <i class="bi bi-clock me-1 text-primary"></i>
                                            <?= !empty($r['waktu_booking']) ? e(substr($r['waktu_booking'], 0, 5)) : '-' ?>
                                        </div>
                                    </div>

                                    <div class="col-md-4 ps-md-4">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <h5 class="fw-bold mb-0 text-dark">
                                                <?= e($pasienTersedia ? $r['nama_pasien'] : 'Data pasien tidak ditemukan') ?>
                                            </h5>
                                            <?php if ($status === 'Darurat'): ?>
                                                <span class="badge bg-danger">EMERGENCY</span>
                                            <?php elseif ($status === 'Diproses'): ?>
                                                <span class="badge bg-warning text-dark">DIPROSES</span>
                                            <?php endif; ?>
                                            <?php if (empty($r['id_staff'])): ?>
                                                <span class="badge bg-secondary">BELUM ADA DOKTER</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small">
                                            <span class="text-primary fw-600"><?= e($r['no_identitas'] ?? '-') ?></span>
                                            <?php if ($pasienTersedia): ?>
                                                • <?= e($r['kategori_pasien'] ?? '-') ?>
                                                <?php if (!empty($r['unit_prodi'])): ?>• <?= e($r['unit_prodi']) ?><?php endif; ?>
                                            <?php else: ?>
                                                • ID pasien: <?= e($r['id_pasien'] ?? '-') ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-2 d-flex flex-wrap gap-1">
                                            <span class="badge bg-soft-primary text-primary border-0 rounded-pill px-3" style="font-size:10px;background-color:#eef4ff;">
                                                <i class="bi bi-person-badge me-1"></i><?= e($r['jenis_antrean'] ?? '-') ?>
                                            </span>
                                            <span class="badge bg-light text-dark border rounded-pill px-3" style="font-size:10px;">
                                                <?= e($status) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="small fw-bold text-muted text-uppercase" style="font-size:10px;">Keluhan Utama:</label>
                                        <p class="small text-dark mb-0 text-truncate-2" title="<?= e($r['keluhan'] ?? '-') ?>">
                                            “<?= e($r['keluhan'] ?: '-') ?>”
                                        </p>
                                    </div>

                                    <div class="col-md-3 text-end">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <?php if ($pasienTersedia && !empty($r['id_staff'])): ?>
                                                <button class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalPeriksa<?= e($r['id_rekam_medis']) ?>">
                                                    <i class="bi bi-clipboard2-pulse me-2"></i>Periksa
                                                </button>
                                            <?php endif; ?>

                                            <form method="POST"
                                                  class="js-swal-confirm"
                                                  data-swal-title="Hapus Antrean?"
                                                  data-swal-text="Antrean pasien ini akan dihapus permanen, termasuk antrean dari hari yang sudah lewat."
                                                  data-swal-confirm="Ya, Hapus">
                                                <input type="hidden" name="id_rekam_medis" value="<?= e($r['id_rekam_medis']) ?>">
                                                <button type="submit" name="batal_antrean" class="btn btn-outline-danger rounded-3" title="Hapus antrean">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($pasienTersedia && !empty($r['id_staff'])): ?>
                        <?php include __DIR__ . '/pemeriksaan.php'; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
