<?php
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
$kategori = mysqli_real_escape_string($conn, $_GET['kategori'] ?? '');
$status = mysqli_real_escape_string($conn, $_GET['status'] ?? '');
$prodi = mysqli_real_escape_string($conn, $_GET['prodi'] ?? '');
$tgl_awal = mysqli_real_escape_string($conn, $_GET['tgl_awal'] ?? '');
$tgl_akhir = mysqli_real_escape_string($conn, $_GET['tgl_akhir'] ?? '');

$where = [];

if ($search != '') {
    $where[] = "(
        rm.id_rekam_medis LIKE '%$search%'
        OR rm.no_antrian LIKE '%$search%'
        OR p.nama_pasien LIKE '%$search%'
        OR p.no_identitas LIKE '%$search%'
        OR d.nama_penyakit LIKE '%$search%'
        OR rj.tujuan_rs LIKE '%$search%'
    )";
}

if ($kategori != '') {
    $where[] = "p.kategori_pasien = '$kategori'";
}

if ($status != '') {
    // Filter Darurat menggunakan riwayat pernah darurat, bukan hanya status akhir.
    // Jadi pasien yang awalnya Darurat lalu sudah Selesai tetap muncul saat filter Darurat dipilih.
    if ($status === 'Darurat') {
        $where[] = "rm.pernah_darurat = 1";
    } else {
        $where[] = "rm.status = '$status'";
    }
}

if ($prodi != '') {
    $where[] = "p.unit_prodi = '$prodi'";
}

if ($tgl_awal != '' && $tgl_akhir != '') {
    $where[] = "rm.tgl_kunjungan BETWEEN '$tgl_awal' AND '$tgl_akhir'";
} elseif ($tgl_awal != '') {
    $where[] = "rm.tgl_kunjungan >= '$tgl_awal'";
} elseif ($tgl_akhir != '') {
    $where[] = "rm.tgl_kunjungan <= '$tgl_akhir'";
}

$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$qSummary = mysqli_query($conn, "
    SELECT
        COUNT(DISTINCT rm.id_rekam_medis) AS total_rekam_medis,
        COUNT(DISTINCT CASE WHEN p.kategori_pasien = 'Mahasiswa' THEN rm.id_rekam_medis END) AS total_kunjungan_mahasiswa,
        COUNT(DISTINCT rm.id_pasien) AS total_pasien_unik,
        COUNT(DISTINCT rj.id_rujukan) AS total_rujukan
    FROM rekam_medis rm
    LEFT JOIN pasienm p ON rm.id_pasien = p.id_pasien
    LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa
    LEFT JOIN rujukan rj ON rj.id_pasien = rm.id_pasien AND rj.tgl_rujukan = rm.tgl_kunjungan
    $where_sql
");

$summary = [
    'total_rekam_medis' => 0,
    'total_kunjungan_mahasiswa' => 0,
    'total_pasien_unik' => 0,
    'total_rujukan' => 0,
];

if ($qSummary) {
    $summary = mysqli_fetch_assoc($qSummary) ?: $summary;
}

$qChart = mysqli_query($conn, "
    SELECT
        COALESCE(NULLIF(TRIM(p.unit_prodi), ''), 'Tanpa Prodi') AS label,
        COUNT(DISTINCT rm.id_rekam_medis) AS total
    FROM rekam_medis rm
    LEFT JOIN pasienm p ON rm.id_pasien = p.id_pasien
    LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa
    LEFT JOIN rujukan rj ON rj.id_pasien = rm.id_pasien AND rj.tgl_rujukan = rm.tgl_kunjungan
    $where_sql
    GROUP BY COALESCE(NULLIF(TRIM(p.unit_prodi), ''), 'Tanpa Prodi')
    ORDER BY total DESC
    LIMIT 10
");

$chartRows = [];
if ($qChart) {
    while ($cr = mysqli_fetch_assoc($qChart)) {
        $chartRows[] = $cr;
    }
}

$qProdi = mysqli_query($conn, "
    SELECT DISTINCT unit_prodi
    FROM pasienm
    WHERE unit_prodi IS NOT NULL AND unit_prodi != ''
    ORDER BY unit_prodi ASC
");

$q = mysqli_query($conn, "
    SELECT
        rm.id_rekam_medis,
        rm.no_antrian,
        rm.tgl_kunjungan,
        rm.waktu_booking,
        rm.keluhan,
        rm.status,
        rm.pernah_darurat,
        rm.jenis_antrean,
        p.nama_pasien,
        p.no_identitas,
        p.jenis_kelamin,
        p.kategori_pasien,
        p.unit_prodi,
        d.nama_penyakit,
        GROUP_CONCAT(DISTINCT rj.tujuan_rs ORDER BY rj.tujuan_rs SEPARATOR ', ') AS tujuan_rujukan,
        GROUP_CONCAT(DISTINCT rj.status ORDER BY rj.status SEPARATOR ', ') AS status_rujukan
    FROM rekam_medis rm
    LEFT JOIN pasienm p ON rm.id_pasien = p.id_pasien
    LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa
    LEFT JOIN rujukan rj ON rj.id_pasien = rm.id_pasien AND rj.tgl_rujukan = rm.tgl_kunjungan
    $where_sql
    GROUP BY
        rm.id_rekam_medis,
        rm.no_antrian,
        rm.tgl_kunjungan,
        rm.waktu_booking,
        rm.keluhan,
        rm.status,
        rm.pernah_darurat,
        rm.jenis_antrean,
        p.nama_pasien,
        p.no_identitas,
        p.jenis_kelamin,
        p.kategori_pasien,
        p.unit_prodi,
        d.nama_penyakit
    ORDER BY
        COALESCE(NULLIF(TRIM(p.unit_prodi), ''), 'Tanpa Prodi') ASC,
        rm.tgl_kunjungan DESC,
        rm.waktu_booking DESC,
        rm.id_rekam_medis DESC
");

$rows = [];
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = $r;
    }
}

$exportParams = $_GET;
unset($exportParams['page']);
$exportUrl = '../cetak/cetak_laporan_internal_pasien.php';
if (!empty($exportParams)) {
    $exportUrl .= '?' . http_build_query($exportParams);
}
?>

<?php
if (!function_exists('renderReportDonutChart')) {
    function renderReportDonutChart($title, $subtitle, $rows, $labelKey, $valueKey)
    {
        $colors = ['#0057B8', '#2E86F0', '#23d3a0', '#f6c23e', '#ef4444', '#7c3aed', '#0f766e', '#f97316', '#475569', '#db2777'];
        $total = 0;
        foreach ($rows as $row) {
            $total += (int)($row[$valueKey] ?? 0);
        }

        $parts = [];
        $legend = [];
        $start = 0;

        if ($total > 0) {
            $i = 0;
            foreach ($rows as $row) {
                $value = (int)($row[$valueKey] ?? 0);
                if ($value <= 0) {
                    continue;
                }

                $percent = ($value / $total) * 100;
                $end = $start + $percent;
                $color = $colors[$i % count($colors)];

                $parts[] = $color . ' ' . round($start, 2) . '% ' . round($end, 2) . '%';
                $legend[] = [
                    'label' => $row[$labelKey] ?? '-',
                    'value' => $value,
                    'percent' => $percent,
                    'color' => $color,
                ];

                $start = $end;
                $i++;
            }
        }

        $gradient = $total > 0 ? implode(', ', $parts) : '#e5e7eb 0% 100%';
        ?>
        <div class="data-container mb-4 report-chart-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="fw-bold mb-1"><?= e($title) ?></h5>
                    <small class="text-muted"><?= e($subtitle) ?></small>
                </div>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                    Total: <?= e($total) ?>
                </span>
            </div>

            <div class="report-chart-layout">
                <div class="report-donut" style="background: conic-gradient(<?= e($gradient) ?>);">
                    <div class="report-donut-center">
                        <div class="report-donut-total"><?= e($total) ?></div>
                        <div class="report-donut-label">Total</div>
                    </div>
                </div>

                <div class="report-legend">
                    <?php if ($total > 0): ?>
                        <?php foreach ($legend as $item): ?>
                            <div class="report-legend-item">
                                <span class="report-legend-dot" style="background: <?= e($item['color']) ?>;"></span>
                                <div class="report-legend-text">
                                    <div class="fw-bold"><?= e($item['label']) ?></div>
                                    <small class="text-muted">
                                        <?= e($item['value']) ?> data • <?= e(number_format($item['percent'], 1)) ?>%
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted small">Belum ada data untuk grafik.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
?>

<style>
    .report-chart-card { overflow: hidden; }
    .report-chart-layout {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 24px;
        align-items: center;
    }
    .report-donut {
        width: 220px;
        height: 220px;
        border-radius: 50%;
        position: relative;
        box-shadow: inset 0 0 0 1px rgba(15, 61, 130, 0.06), 0 14px 28px rgba(15, 61, 130, 0.08);
        margin: 0 auto;
    }
    .report-donut::after {
        content: '';
        position: absolute;
        inset: 46px;
        background: #fff;
        border-radius: 50%;
        box-shadow: inset 0 0 0 1px rgba(15, 61, 130, 0.06);
    }
    .report-donut-center {
        position: absolute;
        inset: 0;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .report-donut-total {
        font-size: 32px;
        font-weight: 800;
        color: var(--astar-blue, #0057B8);
        line-height: 1;
    }
    .report-donut-label {
        margin-top: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .6px;
    }
    .report-legend {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .report-legend-item {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 12px;
        border: 1px solid rgba(15, 61, 130, 0.07);
        border-radius: 14px;
        background: #fbfdff;
    }
    .report-legend-dot {
        width: 13px;
        height: 13px;
        border-radius: 999px;
        margin-top: 4px;
        flex-shrink: 0;
    }
    .report-legend-text { min-width: 0; }
    @media (max-width: 992px) {
        .report-chart-layout { grid-template-columns: 1fr; }
        .report-legend { grid-template-columns: 1fr; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Laporan Internal Pasien</h3>
        <small class="text-muted">Rekap kunjungan mahasiswa, rekam medis, dan rujukan pasien. Ekspor PDF dipisah otomatis per prodi.</small>
    </div>
    <?php renderReportPrintHistoryActions($conn, 'internal_pasien', 'Laporan Internal Pasien', $exportUrl, 'Ekspor PDF Internal'); ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div>
                <div class="small text-muted fw-bold">KUNJUNGAN MAHASISWA</div>
                <div class="h2 fw-bold text-primary mb-0"><?= e($summary['total_kunjungan_mahasiswa'] ?? 0) ?></div>
            </div>
            <i class="bi bi-mortarboard fs-1 text-primary opacity-25"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div>
                <div class="small text-muted fw-bold">REKAM MEDIS</div>
                <div class="h2 fw-bold text-success mb-0"><?= e($summary['total_rekam_medis'] ?? 0) ?></div>
            </div>
            <i class="bi bi-clipboard2-pulse fs-1 text-success opacity-25"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div>
                <div class="small text-muted fw-bold">RUJUKAN</div>
                <div class="h2 fw-bold text-warning mb-0"><?= e($summary['total_rujukan'] ?? 0) ?></div>
            </div>
            <i class="bi bi-file-earmark-medical fs-1 text-warning opacity-25"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div>
                <div class="small text-muted fw-bold">PASIEN UNIK</div>
                <div class="h2 fw-bold text-primary mb-0"><?= e($summary['total_pasien_unik'] ?? 0) ?></div>
            </div>
            <i class="bi bi-people fs-1 text-primary opacity-25"></i>
        </div>
    </div>
</div>

<div class="data-container mb-4">
    <form method="GET" class="row g-3">
        <input type="hidden" name="page" value="laporan_internal_pasien">

        <div class="col-md-3">
            <label class="small fw-bold text-muted">Cari Data</label>
            <input type="text" class="form-control" name="search" placeholder="Cari RM / pasien / diagnosa..." value="<?= e($_GET['search'] ?? '') ?>">
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted">Prodi</label>
            <select class="form-select" name="prodi">
                <option value="">Semua Prodi</option>
                <?php if ($qProdi): ?>
                    <?php while ($pd = mysqli_fetch_assoc($qProdi)): ?>
                        <option value="<?= e($pd['unit_prodi']) ?>" <?= $prodi == $pd['unit_prodi'] ? 'selected' : '' ?>>
                            <?= e($pd['unit_prodi']) ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted">Kategori</label>
            <select class="form-select" name="kategori">
                <option value="">Semua Kategori</option>
                <?php foreach (['Mahasiswa','Pegawai','Virtus','Sigap','Tamu'] as $kg): ?>
                    <option value="<?= e($kg) ?>" <?= $kategori == $kg ? 'selected' : '' ?>><?= e($kg) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted">Status RM</label>
            <select class="form-select" name="status">
                <option value="">Semua Status</option>
                <?php foreach (['Menunggu','Diproses','Selesai','Batal','Darurat'] as $st): ?>
                    <option value="<?= e($st) ?>" <?= $status == $st ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted">Dari</label>
            <input type="date" class="form-control" name="tgl_awal" value="<?= e($_GET['tgl_awal'] ?? '') ?>">
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted">Sampai</label>
            <input type="date" class="form-control" name="tgl_akhir" value="<?= e($_GET['tgl_akhir'] ?? '') ?>">
        </div>

        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-primary w-100">Filter</button>
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <a href="index.php?page=laporan_internal_pasien" class="btn btn-light border w-100 fw-bold">Atur Ulang</a>
        </div>
    </form>
</div>

<?php renderReportDonutChart('Chart Kunjungan per Prodi', 'Grafik bulat dari data rekam medis yang sudah difilter.', $chartRows, 'label', 'total'); ?>

<div class="data-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Rekam Medis</th>
                    <th>Pasien</th>
                    <th>Prodi</th>
                    <th>Kategori</th>
                    <th>Diagnosa</th>
                    <th>Status RM</th>
                    <th>Rujukan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; ?>
                <?php if (count($rows) > 0): ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold"><?= e($r['tgl_kunjungan']) ?></div>
                                <small class="text-muted"><?= e(substr($r['waktu_booking'] ?? '', 0, 5)) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?= e($r['id_rekam_medis']) ?></div>
                                <small class="text-muted"><?= e($r['no_antrian']) ?> • <?= e($r['jenis_antrean']) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold"><?= e($r['nama_pasien'] ?? '-') ?></div>
                                <small class="text-muted"><?= e($r['no_identitas'] ?? '-') ?></small>
                            </td>
                            <td><?= e($r['unit_prodi'] ?? '-') ?></td>
                            <td><?= e($r['kategori_pasien'] ?? '-') ?></td>
                            <td><?= e($r['nama_penyakit'] ?? 'Belum diagnosa') ?></td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3"><?= e($r['status']) ?></span>
                                <?php if ((int)($r['pernah_darurat'] ?? 0) === 1 && ($r['status'] ?? '') !== 'Darurat'): ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 mt-1">Pernah Darurat</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($r['tujuan_rujukan'])): ?>
                                    <div class="fw-bold"><?= e($r['tujuan_rujukan']) ?></div>
                                    <small class="text-muted"><?= e($r['status_rujukan']) ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">Data laporan internal tidak tersedia.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
