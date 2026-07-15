<?php
function namaHariAntreanIndonesia(string $tanggal): string
{
    $namaHari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
    ];
    $timestamp = strtotime($tanggal);
    return $timestamp ? ($namaHari[date('l', $timestamp)] ?? '-') : '-';
}

function kategoriTanggalAntrean(string $tanggal, string $view): array
{
    if ($view === 'batal') {
        return ['Antrean Batal', 'danger', 'bi-x-circle-fill'];
    }

    $hariIni = date('Y-m-d');
    $besok = date('Y-m-d', strtotime('+1 day'));
    if ($tanggal === $hariIni) return ['Hari Ini', 'primary', 'bi-calendar-check-fill'];
    if ($tanggal === $besok) return ['Besok', 'info', 'bi-calendar-plus-fill'];
    if ($tanggal > $hariIni) return ['Jadwal Mendatang', 'info', 'bi-calendar-event'];
    return ['Hari Sebelumnya', 'secondary', 'bi-calendar2-week'];
}

// Pastikan status Batal tersedia untuk database lama.
$statusSchemaError = '';
$qStatusColumn = mysqli_query($conn, "SHOW COLUMNS FROM rekam_medis LIKE 'status'");
if ($qStatusColumn && ($statusColumn = mysqli_fetch_assoc($qStatusColumn))) {
    if (stripos((string) ($statusColumn['Type'] ?? ''), "'Batal'") === false) {
        $alterStatus = mysqli_query(
            $conn,
            "ALTER TABLE rekam_medis
             MODIFY status ENUM('Menunggu','Darurat','Diproses','Selesai','Batal') NOT NULL DEFAULT 'Menunggu'"
        );
        if (!$alterStatus) $statusSchemaError = mysqli_error($conn);
    }
}

// Antrean yang melewati tanggal tidak otomatis dibatalkan.
// Status Batal hanya berasal dari aksi pembatalan dokter.

$view = (string) ($_GET['view'] ?? 'hari_ini');
if (!in_array($view, ['hari_ini', 'aktif', 'batal'], true)) $view = 'hari_ini';

$doctorSafe = mysqli_real_escape_string($conn, $id_dokter);
$doctorCondition = "(rm.id_staff = '$doctorSafe' OR rm.id_staff IS NULL OR rm.id_staff = '')";
$activeStatuses = "rm.status IN ('Menunggu','Darurat','Diproses')";

$countActiveQuery = mysqli_query($conn, "SELECT COUNT(*) total FROM rekam_medis rm WHERE $doctorCondition AND $activeStatuses");
$countTodayQuery = mysqli_query($conn, "SELECT COUNT(*) total FROM rekam_medis rm WHERE $doctorCondition AND rm.tgl_kunjungan = CURDATE() AND rm.status <> 'Batal'");
$countCancelledQuery = mysqli_query($conn, "SELECT COUNT(*) total FROM rekam_medis rm WHERE $doctorCondition AND rm.status = 'Batal'");
$activeCountRow = $countActiveQuery ? mysqli_fetch_assoc($countActiveQuery) : [];
$todayCountRow = $countTodayQuery ? mysqli_fetch_assoc($countTodayQuery) : [];
$cancelledCountRow = $countCancelledQuery ? mysqli_fetch_assoc($countCancelledQuery) : [];
$totalAktif = (int) ($activeCountRow['total'] ?? 0);
$totalHariIni = (int) ($todayCountRow['total'] ?? 0);
$totalBatal = (int) ($cancelledCountRow['total'] ?? 0);

if ($view === 'batal') {
    $viewCondition = "rm.status = 'Batal'";
    $viewTitle = 'Daftar Antrean Batal';
    $viewDescription = 'Menampilkan antrean yang dibatalkan oleh dokter.';
} elseif ($view === 'aktif') {
    $viewCondition = "$activeStatuses";
    $viewTitle = 'Total Antrean Aktif';
    $viewDescription = 'Menampilkan seluruh antrean yang masih aktif dan belum selesai atau dibatalkan.';
} else {
    $viewCondition = "rm.tgl_kunjungan = CURDATE() AND rm.status <> 'Batal'";
    $viewTitle = 'Antrean Hari Ini';
    $viewDescription = 'Menampilkan seluruh antrean hari ini, baik yang belum diperiksa maupun yang sudah selesai diperiksa.';
}

$qAntrean = mysqli_query(
    $conn,
    "SELECT rm.*, p.nama_pasien, p.no_identitas, p.kategori_pasien, p.unit_prodi
     FROM rekam_medis rm
     LEFT JOIN pasienm p ON rm.id_pasien = p.id_pasien
     WHERE $doctorCondition AND $viewCondition
     ORDER BY rm.tgl_kunjungan DESC,
              CASE WHEN rm.status = 'Darurat' THEN 0 WHEN rm.status = 'Diproses' THEN 1 ELSE 2 END,
              rm.waktu_booking ASC,
              CAST(SUBSTRING(rm.no_antrian, 2) AS UNSIGNED) ASC"
);

$antreanSemua = [];
$queryAntreanError = $statusSchemaError;
if (!$qAntrean) {
    $queryAntreanError = mysqli_error($conn);
} else {
    while ($rowAntrean = mysqli_fetch_assoc($qAntrean)) $antreanSemua[] = $rowAntrean;
}

$kelompokTanggal = [];
foreach ($antreanSemua as $itemAntrean) {
    $tanggalAntrean = (string) ($itemAntrean['tgl_kunjungan'] ?? '');
    $keyTanggal = $tanggalAntrean !== '' ? $tanggalAntrean : 'tanpa-tanggal';
    $kelompokTanggal[$keyTanggal][] = $itemAntrean;
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Antrean Pasien</h3>
        <small class="text-muted">Klik kategori antrean untuk membuka daftar yang terpisah.</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-primary px-3 py-2 rounded-pill"><?= e(hariIniIndonesia()) ?>, <?= date('d M Y') ?></span>
    </div>
</div>

<div class="row g-4 mb-4 no-print">
    <div class="col-md-4">
        <a href="index.php?page=antrean&view=aktif" class="text-decoration-none">
            <div class="stat-card <?= $view === 'aktif' ? 'border border-primary' : '' ?>" style="cursor:pointer;">
                <div><div class="small text-muted fw-bold">TOTAL ANTREAN AKTIF</div><div class="h2 fw-bold text-primary mb-0"><?= $totalAktif ?></div></div>
                <i class="bi bi-ticket-perforated fs-1 text-primary opacity-25"></i>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="index.php?page=antrean&view=hari_ini" class="text-decoration-none">
            <div class="stat-card success <?= $view === 'hari_ini' ? 'border border-success' : '' ?>" style="cursor:pointer;">
                <div><div class="small text-muted fw-bold">ANTREAN HARI INI</div><div class="h2 fw-bold text-success mb-0"><?= $totalHariIni ?></div></div>
                <i class="bi bi-calendar-check fs-1 text-success opacity-25"></i>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="index.php?page=antrean&view=batal" class="text-decoration-none">
            <div class="stat-card danger <?= $view === 'batal' ? 'border border-danger' : '' ?>" style="cursor:pointer;">
                <div><div class="small text-muted fw-bold">ANTREAN BATAL</div><div class="h2 fw-bold text-danger mb-0"><?= $totalBatal ?></div></div>
                <i class="bi bi-x-circle fs-1 text-danger opacity-25"></i>
            </div>
        </a>
    </div>
</div>

<div id="printAntreanHistory">
    <div class="data-container mb-4">
        <h5 class="fw-bold mb-1"><?= e($viewTitle) ?></h5>
        <small class="text-muted"><?= e($viewDescription) ?></small>
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
        <h5 class="fw-bold mt-3">Tidak Ada Data</h5>
        <p class="mb-0"><?= $view === 'batal' ? 'Belum ada antrean yang dibatalkan.' : 'Belum ada pasien pada kategori antrean ini.' ?></p>
    </div>
<?php else: ?>
    <div data-astar-list-pagination>
    <?php foreach ($kelompokTanggal as $tanggalKelompok => $daftarAntrean): ?>
        <?php
        $tanggalAsli = $tanggalKelompok === 'tanpa-tanggal' ? '' : $tanggalKelompok;
        [$kategoriHari, $warnaHari, $ikonHari] = kategoriTanggalAntrean($tanggalAsli, $view);
        $judulTanggal = $tanggalAsli !== ''
            ? namaHariAntreanIndonesia($tanggalAsli) . ', ' . date('d M Y', strtotime($tanggalAsli))
            : 'Tanggal tidak tersedia';
        ?>
        <section class="data-container mb-4" data-astar-pagination-group>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-<?= e($warnaHari) ?> bg-opacity-10 text-<?= e($warnaHari) ?> d-flex align-items-center justify-content-center" style="width:44px;height:44px;"><i class="bi <?= e($ikonHari) ?> fs-5"></i></div>
                    <div><div class="fw-bold fs-5"><?= e($judulTanggal) ?></div><small class="text-muted"><?= count($daftarAntrean) ?> pasien</small></div>
                </div>
                <span class="badge rounded-pill bg-<?= e($warnaHari) ?> px-3 py-2"><?= e($kategoriHari) ?></span>
            </div>

            <div class="row">
            <?php foreach ($daftarAntrean as $r): ?>
                <?php
                $pasienTersedia = !empty($r['nama_pasien']);
                $status = (string) ($r['status'] ?? 'Menunggu');
                $warnaStatus = $status === 'Batal' ? '#dc3545' : ($status === 'Selesai' ? '#198754' : ($status === 'Darurat' ? '#dc3545' : ($status === 'Diproses' ? '#f59e0b' : '#0057B8')));
                ?>
                <div class="col-12 mb-3" data-astar-pagination-item>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative" style="border-left:5px solid <?= e($warnaStatus) ?> !important;">
                        <div class="card-body p-3">
                            <div class="row align-items-center g-3">
                                <div class="col-md-2 text-center border-end">
                                    <div class="display-6 fw-bold text-primary mb-0"><?= e($r['no_antrian'] ?: '-') ?></div>
                                    <div class="badge bg-light text-dark rounded-pill shadow-sm"><i class="bi bi-clock me-1 text-primary"></i><?= !empty($r['waktu_booking']) ? e(substr($r['waktu_booking'], 0, 5)) : '-' ?></div>
                                </div>
                                <div class="col-md-4 ps-md-4">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <h5 class="fw-bold mb-0 text-dark"><?= e($pasienTersedia ? $r['nama_pasien'] : 'Data pasien tidak ditemukan') ?></h5>
                                        <?php if ($status === 'Batal'): ?><span class="badge bg-danger">BATAL</span><?php elseif ($status === 'Selesai'): ?><span class="badge bg-success">SELESAI</span><?php elseif ($status === 'Darurat'): ?><span class="badge bg-danger">DARURAT</span><?php elseif ($status === 'Diproses'): ?><span class="badge bg-warning text-dark">DIPROSES</span><?php endif; ?>
                                        <?php if (empty($r['id_staff'])): ?><span class="badge bg-secondary">BELUM ADA DOKTER</span><?php endif; ?>
                                    </div>
                                    <div class="text-muted small"><span class="text-primary fw-600"><?= e($r['no_identitas'] ?? '-') ?></span> • <?= e($r['kategori_pasien'] ?? '-') ?></div>
                                    <div class="mt-2 d-flex flex-wrap gap-1">
                                        <span class="badge bg-soft-primary text-primary rounded-pill px-3" style="font-size:10px;background-color:#eef4ff;"><i class="bi bi-person-badge me-1"></i><?= e($r['jenis_antrean'] ?? '-') ?></span>
                                        <span class="badge bg-light text-dark border rounded-pill px-3" style="font-size:10px;"><?= e($status) ?></span>
                                    </div>
                                </div>
                                <div class="col-md-3"><label class="small fw-bold text-muted text-uppercase" style="font-size:10px;">Keluhan Utama:</label><p class="small text-dark mb-0">“<?= e($r['keluhan'] ?: '-') ?>”</p></div>
                                <div class="col-md-3 text-end no-print">
                                    <div class="d-flex gap-2 justify-content-end">
                                    <?php if (in_array($status, ['Menunggu', 'Darurat', 'Diproses'], true) && $pasienTersedia && !empty($r['id_staff'])): ?>
                                        <button class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPeriksa<?= e($r['id_rekam_medis']) ?>"><i class="bi bi-clipboard2-pulse me-2"></i>Periksa</button>
                                    <?php endif; ?>
                                    <?php if (in_array($status, ['Menunggu', 'Darurat', 'Diproses'], true)): ?>
                                        <form method="POST" class="js-swal-confirm" data-swal-title="Batalkan Antrean?" data-swal-text="Antrean akan dipindahkan ke daftar Antrean Batal dan riwayatnya tetap disimpan." data-swal-confirm="Ya, Batalkan">
                                            <input type="hidden" name="id_rekam_medis" value="<?= e($r['id_rekam_medis']) ?>">
                                            <button type="submit" name="batal_antrean" class="btn btn-outline-danger rounded-3" title="Batalkan antrean"><i class="bi bi-x-circle"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (in_array($status, ['Menunggu', 'Darurat', 'Diproses'], true) && $pasienTersedia && !empty($r['id_staff'])) include __DIR__ . '/pemeriksaan.php'; ?>
            <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>
