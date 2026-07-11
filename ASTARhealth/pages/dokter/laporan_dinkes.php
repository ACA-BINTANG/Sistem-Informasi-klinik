<?php
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
$tgl_awal = mysqli_real_escape_string($conn, $_GET['tgl_awal'] ?? '');
$tgl_akhir = mysqli_real_escape_string($conn, $_GET['tgl_akhir'] ?? '');

$where = ["rm.id_diagnosa IS NOT NULL", "rm.id_diagnosa <> ''"];

if ($search != '') {
    $where[] = "(d.id_diagnosa LIKE '%$search%' OR d.nama_penyakit LIKE '%$search%')";
}

if ($tgl_awal != '' && $tgl_akhir != '') {
    $where[] = "rm.tgl_kunjungan BETWEEN '$tgl_awal' AND '$tgl_akhir'";
} elseif ($tgl_awal != '') {
    $where[] = "rm.tgl_kunjungan >= '$tgl_awal'";
} elseif ($tgl_akhir != '') {
    $where[] = "rm.tgl_kunjungan <= '$tgl_akhir'";
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$q = mysqli_query($conn, "
    SELECT
        d.id_diagnosa,
        d.nama_penyakit,
        SUM(CASE WHEN LOWER(TRIM(p.jenis_kelamin)) IN ('l', 'laki-laki', 'laki laki', 'pria', 'male') THEN 1 ELSE 0 END) AS laki_laki,
        SUM(CASE WHEN LOWER(TRIM(p.jenis_kelamin)) IN ('p', 'perempuan', 'wanita', 'female') THEN 1 ELSE 0 END) AS perempuan,
        COUNT(rm.id_rekam_medis) AS total_kasus
    FROM rekam_medis rm
    JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa
    LEFT JOIN pasienm p ON rm.id_pasien = p.id_pasien
    $where_sql
    GROUP BY d.id_diagnosa, d.nama_penyakit
    ORDER BY total_kasus DESC
    LIMIT 10
");

$rows = [];
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $r['label_chart'] = $r['id_diagnosa'] . ' - ' . $r['nama_penyakit'];
        $rows[] = $r;
    }
}

$exportParams = $_GET;
unset($exportParams['page']);
$exportUrl = 'cetak_laporan_dinkes.php';
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
        <h3 class="fw-bold mb-1">Laporan Dinkes</h3>
        <small class="text-muted">Laporan 10 penyakit terbanyak berdasarkan transaksi rekam medis.</small>
    </div>
    <a href="<?= e($exportUrl) ?>" target="_blank" class="btn btn-primary fw-bold">
        <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
    </a>
</div>

<div class="data-container mb-4">
    <form method="GET" class="row g-3">
        <input type="hidden" name="page" value="laporan_dinkes">

        <div class="col-md-5">
            <label class="small fw-bold text-muted">Cari Penyakit</label>
            <input type="text" name="search" class="form-control" placeholder="Cari kode / nama penyakit..." value="<?= e($_GET['search'] ?? '') ?>">
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted">Dari Tanggal</label>
            <input type="date" name="tgl_awal" class="form-control" value="<?= e($_GET['tgl_awal'] ?? '') ?>">
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted">Sampai Tanggal</label>
            <input type="date" name="tgl_akhir" class="form-control" value="<?= e($_GET['tgl_akhir'] ?? '') ?>">
        </div>

        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-primary w-100">Filter</button>
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <a href="dashboard_dokter.php?page=laporan_dinkes" class="btn btn-light border w-100 fw-bold">Reset</a>
        </div>
    </form>
</div>

<?php renderReportDonutChart('Chart 10 Penyakit Terbanyak', 'Grafik bulat dari data rekam medis yang sudah difilter.', $rows, 'label_chart', 'total_kasus'); ?>

<div class="data-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Diagnosa</th>
                    <th>Nama Penyakit</th>
                    <th class="text-center">Laki-laki</th>
                    <th class="text-center">Perempuan</th>
                    <th class="text-center">Total Kasus</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; ?>
                <?php if (count($rows) > 0): ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-bold text-primary"><?= e($r['id_diagnosa']) ?></td>
                            <td><?= e($r['nama_penyakit']) ?></td>
                            <td class="text-center"><?= e($r['laki_laki']) ?></td>
                            <td class="text-center"><?= e($r['perempuan']) ?></td>
                            <td class="text-center"><span class="badge bg-danger bg-opacity-10 text-danger px-3"><?= e($r['total_kasus']) ?> kasus</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">Data penyakit tidak tersedia.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
