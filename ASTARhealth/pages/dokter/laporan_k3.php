<?php
// File halaman ini dipanggil dari ../../dashboard_dokter.php.
// Variabel $conn dan fungsi e() dari dashboard utama tetap bisa dipakai di sini.

$tgl_awal = mysqli_real_escape_string($conn, $_GET["tgl_awal"] ?? "");
$tgl_akhir = mysqli_real_escape_string($conn, $_GET["tgl_akhir"] ?? "");

function ambilTopPenyakitK3(
    $conn,
    $kategoriMode,
    $tgl_awal,
    $tgl_akhir,
    $limit = 10,
) {
    $where = ["rm.id_diagnosa IS NOT NULL", "rm.id_diagnosa <> ''"];

    if ($kategoriMode === "menular") {
        $where[] = "d.kategori = 'Menular'";
    } elseif ($kategoriMode === "tidak_menular") {
        $where[] = "(d.kategori IS NULL OR d.kategori <> 'Menular')";
    }

    if ($tgl_awal !== "" && $tgl_akhir !== "") {
        $where[] = "rm.tgl_kunjungan BETWEEN '$tgl_awal' AND '$tgl_akhir'";
    } elseif ($tgl_awal !== "") {
        $where[] = "rm.tgl_kunjungan >= '$tgl_awal'";
    } elseif ($tgl_akhir !== "") {
        $where[] = "rm.tgl_kunjungan <= '$tgl_akhir'";
    }

    $where_sql = "WHERE " . implode(" AND ", $where);
    $limit = (int) $limit;

    $q = mysqli_query(
        $conn,
        "
        SELECT
            d.id_diagnosa,
            d.nama_penyakit,
            COUNT(rm.id_rekam_medis) AS jumlah
        FROM rekam_medis rm
        JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa
        $where_sql
        GROUP BY d.id_diagnosa, d.nama_penyakit
        ORDER BY jumlah DESC
        LIMIT $limit
    ",
    );

    $rows = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $r["label_chart"] = $r["id_diagnosa"] . " - " . $r["nama_penyakit"];
            $rows[] = $r;
        }
    }
    return $rows;
}

$dataMenular = ambilTopPenyakitK3($conn, "menular", $tgl_awal, $tgl_akhir, 10);
$dataTidakMenular = ambilTopPenyakitK3(
    $conn,
    "tidak_menular",
    $tgl_awal,
    $tgl_akhir,
    10,
);
$dataTerbanyak = ambilTopPenyakitK3($conn, "semua", $tgl_awal, $tgl_akhir, 10);

$totalMenular = array_sum(array_column($dataMenular, "jumlah"));
$totalTidakMenular = array_sum(array_column($dataTidakMenular, "jumlah"));
$totalTerbanyak = array_sum(array_column($dataTerbanyak, "jumlah"));

$exportParams = $_GET;
unset($exportParams["page"]);
$exportUrl = "cetak_laporan_k3.php";
if (!empty($exportParams)) {
    $exportUrl .= "?" . http_build_query($exportParams);
}
?>

<?php
if (!function_exists("renderReportDonutChart")) {
    function renderReportDonutChart(
        $title,
        $subtitle,
        $rows,
        $labelKey,
        $valueKey,
    ) {
        $colors = [
            "#0057B8",
            "#2E86F0",
            "#23d3a0",
            "#f6c23e",
            "#ef4444",
            "#7c3aed",
            "#0f766e",
            "#f97316",
            "#475569",
            "#db2777",
        ];
        $total = 0;
        foreach ($rows as $row) {
            $total += (int) ($row[$valueKey] ?? 0);
        }

        $parts = [];
        $legend = [];
        $start = 0;

        if ($total > 0) {
            $i = 0;
            foreach ($rows as $row) {
                $value = (int) ($row[$valueKey] ?? 0);
                if ($value <= 0) {
                    continue;
                }

                $percent = ($value / $total) * 100;
                $end = $start + $percent;
                $color = $colors[$i % count($colors)];

                $parts[] =
                    $color .
                    " " .
                    round($start, 2) .
                    "% " .
                    round($end, 2) .
                    "%";
                $legend[] = [
                    "label" => $row[$labelKey] ?? "-",
                    "value" => $value,
                    "percent" => $percent,
                    "color" => $color,
                ];

                $start = $end;
                $i++;
            }
        }

        $gradient = $total > 0 ? implode(", ", $parts) : "#e5e7eb 0% 100%";
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
                <div class="report-donut" style="background: conic-gradient(<?= e(
                    $gradient,
                ) ?>);">
                    <div class="report-donut-center">
                        <div class="report-donut-total"><?= e($total) ?></div>
                        <div class="report-donut-label">Total</div>
                    </div>
                </div>

                <div class="report-legend">
                    <?php if ($total > 0): ?>
                        <?php foreach ($legend as $item): ?>
                            <div class="report-legend-item">
                                <span class="report-legend-dot" style="background: <?= e(
                                    $item["color"],
                                ) ?>;"></span>
                                <div class="report-legend-text">
                                    <div class="fw-bold"><?= e(
                                        $item["label"],
                                    ) ?></div>
                                    <small class="text-muted">
                                        <?= e(
                                            $item["value"],
                                        ) ?> kasus &bull; <?= e(
     number_format($item["percent"], 1),
 ) ?>%
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

if (!function_exists("renderTabelPenyakitK3")) {
    function renderTabelPenyakitK3($rows, $total)
    {
        ?>
        <div class="data-container mb-5">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Diagnosa</th>
                            <th>Nama Penyakit</th>
                            <th>Jumlah Kasus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows) > 0): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="fw-bold text-primary"><?= e(
                                        $r["id_diagnosa"],
                                    ) ?></td>
                                    <td><?= e($r["nama_penyakit"]) ?></td>
                                    <td><span class="badge bg-primary"><?= e(
                                        $r["jumlah"],
                                    ) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">TOTAL</td>
                                <td><?= e($total) ?></td>
                            </tr>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Data tidak tersedia untuk periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
?>

<style>
    .report-chart-card { overflow: hidden; }
    .report-chart-layout { display: grid; grid-template-columns: 250px 1fr; gap: 24px; align-items: center; }
    .report-donut {
        width: 220px; height: 220px; border-radius: 50%; position: relative;
        box-shadow: inset 0 0 0 1px rgba(15, 61, 130, 0.06), 0 14px 28px rgba(15, 61, 130, 0.08);
        margin: 0 auto;
    }
    .report-donut::after {
        content: ''; position: absolute; inset: 46px; background: #fff; border-radius: 50%;
        box-shadow: inset 0 0 0 1px rgba(15, 61, 130, 0.06);
    }
    .report-donut-center { position: absolute; inset: 0; z-index: 2; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    .report-donut-total { font-size: 32px; font-weight: 800; color: var(--astar-blue, #0057B8); line-height: 1; }
    .report-donut-label { margin-top: 6px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .6px; }
    .report-legend { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .report-legend-item { display: flex; gap: 10px; align-items: flex-start; padding: 12px; border: 1px solid rgba(15, 61, 130, 0.07); border-radius: 14px; background: #fbfdff; }
    .report-legend-dot { width: 13px; height: 13px; border-radius: 999px; margin-top: 4px; flex-shrink: 0; }
    .report-legend-text { min-width: 0; }
    .k3-section-title { font-weight: 800; margin: 6px 0 14px; color: #0057B8; }
    @media (max-width: 992px) {
        .report-chart-layout { grid-template-columns: 1fr; }
        .report-legend { grid-template-columns: 1fr; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Laporan K3 Astar</h3>
        <small class="text-muted">Rekapitulasi penyakit menular, tidak menular, dan terbanyak &mdash; Unit Kesehatan Kampus.</small>
    </div>
    <a href="<?= e(
        $exportUrl,
    ) ?>" target="_blank" class="btn btn-primary fw-bold">
        <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
    </a>
</div>

<div class="data-container mb-4">
    <form method="GET" class="row g-3">
        <input type="hidden" name="page" value="laporan_k3">

        <div class="col-md-4">
            <label class="small fw-bold text-muted">Dari Tanggal</label>
            <input type="date" name="tgl_awal" class="form-control" value="<?= e(
                $_GET["tgl_awal"] ?? "",
            ) ?>">
        </div>

        <div class="col-md-4">
            <label class="small fw-bold text-muted">Sampai Tanggal</label>
            <input type="date" name="tgl_akhir" class="form-control" value="<?= e(
                $_GET["tgl_akhir"] ?? "",
            ) ?>">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100">Filter</button>
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <a href="dashboard_dokter.php?page=laporan_k3" class="btn btn-light border w-100 fw-bold">Reset</a>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="data-container text-center">
            <small class="text-muted fw-bold">TOTAL KASUS MENULAR</small>
            <h2 class="fw-bold text-danger mb-0"><?= e($totalMenular) ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="data-container text-center">
            <small class="text-muted fw-bold">TOTAL KASUS TIDAK MENULAR</small>
            <h2 class="fw-bold text-warning mb-0"><?= e(
                $totalTidakMenular,
            ) ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="data-container text-center">
            <small class="text-muted fw-bold">TOTAL SELURUH KASUS</small>
            <h2 class="fw-bold text-primary mb-0"><?= e($totalTerbanyak) ?></h2>
        </div>
    </div>
</div>

<h5 class="k3-section-title">1. Laporan 10 Penyakit Menular Terbanyak</h5>
<?php renderReportDonutChart(
    "Chart Penyakit Menular",
    "Grafik bulat dari data penyakit menular yang sudah difilter.",
    $dataMenular,
    "label_chart",
    "jumlah",
); ?>
<?php renderTabelPenyakitK3($dataMenular, $totalMenular); ?>

<h5 class="k3-section-title">2. Laporan 10 Penyakit Tidak Menular Terbanyak</h5>
<?php renderReportDonutChart(
    "Chart Penyakit Tidak Menular",
    "Grafik bulat dari data penyakit tidak menular yang sudah difilter.",
    $dataTidakMenular,
    "label_chart",
    "jumlah",
); ?>
<?php renderTabelPenyakitK3($dataTidakMenular, $totalTidakMenular); ?>

<h5 class="k3-section-title">3. Laporan 10 Penyakit Terbanyak (Keseluruhan)</h5>
<?php renderReportDonutChart(
    "Chart 10 Penyakit Terbanyak",
    "Grafik bulat gabungan seluruh kategori penyakit.",
    $dataTerbanyak,
    "label_chart",
    "jumlah",
); ?>
<?php renderTabelPenyakitK3($dataTerbanyak, $totalTerbanyak); ?>
