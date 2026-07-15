<?php
$search = mysqli_real_escape_string($conn, $_GET["search"] ?? "");
$status = mysqli_real_escape_string($conn, $_GET["status"] ?? "");
$tgl_awal = mysqli_real_escape_string($conn, $_GET["tgl_awal"] ?? "");
$tgl_akhir = mysqli_real_escape_string($conn, $_GET["tgl_akhir"] ?? "");

$where = [];
if ($search != "") {
    $where[] = "(o.nama_obat LIKE '%$search%' OR s.nama_supplier LIKE '%$search%' OR p.id_pengadaan LIKE '%$search%' OR p.catatan LIKE '%$search%')";
}
if ($status != "") {
    $where[] = "p.status = '$status'";
}

if ($tgl_awal != "" && $tgl_akhir != "") {
    $where[] = "p.tgl_order BETWEEN '$tgl_awal' AND '$tgl_akhir'";
} elseif ($tgl_awal != "") {
    $where[] = "p.tgl_order >= '$tgl_awal'";
} elseif ($tgl_akhir != "") {
    $where[] = "p.tgl_order <= '$tgl_akhir'";
}

$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$qChart = mysqli_query(
    $conn,
    "
    SELECT
        COALESCE(NULLIF(TRIM(p.status), ''), 'Tanpa Status') AS label,
        COUNT(p.id_pengadaan) AS total
    FROM pengadaan_obat p
    LEFT JOIN obatm o ON p.id_obat = o.id_obat
    LEFT JOIN supplierm s ON p.id_supplier = s.id_supplier
    $where_sql
    GROUP BY COALESCE(NULLIF(TRIM(p.status), ''), 'Tanpa Status')
    ORDER BY total DESC
",
);

$chartRows = [];
if ($qChart) {
    while ($cr = mysqli_fetch_assoc($qChart)) {
        $chartRows[] = $cr;
    }
}

$q = mysqli_query(
    $conn,
    "
    SELECT
        p.*,
        o.nama_obat,
        o.satuan,
        s.nama_supplier
    FROM pengadaan_obat p
    LEFT JOIN obatm o ON p.id_obat = o.id_obat
    LEFT JOIN supplierm s ON p.id_supplier = s.id_supplier
    $where_sql
    ORDER BY p.created_at DESC, p.tgl_order DESC, p.id_pengadaan DESC
",
);

$rows = [];
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = $r;
    }
}

$exportParams = $_GET;
unset($exportParams["page"]);
$exportUrl = "../cetak/cetak_laporan_siloam.php";
if (!empty($exportParams)) {
    $exportUrl .= "?" . http_build_query($exportParams);
}
?>

<?php if (!function_exists("renderReportDonutChart")) {
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
                                        <?= e($item["value"]) ?> data • <?= e(
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
} ?>

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
        <h3 class="fw-bold mb-1">Laporan Siloam</h3>
        <small class="text-muted">Laporan pengadaan obat berdasarkan transaksi pengadaan.</small>
    </div>
    <?php renderReportPrintHistoryActions($conn, 'siloam', 'Laporan Siloam', $exportUrl, 'Ekspor PDF'); ?>
</div>

<div class="data-container mb-4">
    <form method="GET" class="row g-3">
        <input type="hidden" name="page" value="laporan_siloam">

        <div class="col-md-4">
            <label class="small fw-bold text-muted">Cari Data</label>
            <input class="form-control" name="search" placeholder="Cari obat / supplier / ID" value="<?= e(
                $_GET["search"] ?? "",
            ) ?>">
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted">Status</label>
            <select class="form-select" name="status">
                <option value="">Semua Status</option>
                <?php foreach (
                    ["Pending", "Diterima", "Batal"]
                    as $st
                ): ?>
                    <option value="<?= e($st) ?>" <?= $status == $st
    ? "selected"
    : "" ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted">Dari Tanggal</label>
            <input type="date" class="form-control" name="tgl_awal" value="<?= e(
                $_GET["tgl_awal"] ?? "",
            ) ?>">
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted">Sampai Tanggal</label>
            <input type="date" class="form-control" name="tgl_akhir" value="<?= e(
                $_GET["tgl_akhir"] ?? "",
            ) ?>">
        </div>

        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-primary w-100">Filter</button>
        </div>

        <div class="col-md-1 d-flex align-items-end">
            <a href="index.php?page=laporan_siloam" class="btn btn-light border w-100 fw-bold">Atur Ulang</a>
        </div>
    </form>
</div>

<?php renderReportDonutChart(
    "Chart Status Pengadaan",
    "Grafik bulat dari data pengadaan yang sudah difilter.",
    $chartRows,
    "label",
    "total",
); ?>

<div class="data-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Pengadaan</th>
                    <th>Tanggal</th>
                    <th>Obat</th>
                    <th>Pemasok</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; ?>
                <?php if (count($rows) > 0): ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-bold text-primary"><?= e(
                                $r["id_pengadaan"],
                            ) ?></td>
                            <td><?= e($r["tgl_order"]) ?></td>
                            <td><?= e($r["nama_obat"] ?? "-") ?></td>
                            <td><?= e($r["nama_supplier"] ?? "-") ?></td>
                            <td><?= e($r["jumlah_order"]) ?> <?= e(
     $r["satuan"] ?? "",
 ) ?></td>
                            <td><span class="badge bg-primary"><?= e(
                                $r["status"],
                            ) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">Data pengadaan tidak tersedia.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
