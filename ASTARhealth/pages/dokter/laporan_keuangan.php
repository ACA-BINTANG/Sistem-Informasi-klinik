<?php
// File halaman ini dipanggil dari ../../dashboard_dokter.php.
// Variabel $conn dan fungsi e() dari dashboard utama tetap bisa dipakai di sini.

function rupiah($angka)
{
    return "Rp " . number_format((float) $angka, 0, ",", ".");
}

$status = mysqli_real_escape_string($conn, $_GET["status"] ?? "");
$tgl_awal = mysqli_real_escape_string($conn, $_GET["tgl_awal"] ?? "");
$tgl_akhir = mysqli_real_escape_string($conn, $_GET["tgl_akhir"] ?? "");

$where = [];
if ($status !== "") {
    $where[] = "p.status = '$status'";
}
if ($tgl_awal !== "" && $tgl_akhir !== "") {
    $where[] = "p.tgl_order BETWEEN '$tgl_awal' AND '$tgl_akhir'";
} elseif ($tgl_awal !== "") {
    $where[] = "p.tgl_order >= '$tgl_awal'";
} elseif ($tgl_akhir !== "") {
    $where[] = "p.tgl_order <= '$tgl_akhir'";
}
$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$q = mysqli_query(
    $conn,
    "
    SELECT
        p.id_pengadaan,
        p.jumlah_order,
        p.tgl_order,
        p.status,
        o.nama_obat,
        o.satuan,
        o.harga_per_pcs,
        s.nama_supplier,
        (p.jumlah_order * o.harga_per_pcs) AS subtotal
    FROM pengadaan_obat p
    LEFT JOIN obatm o ON p.id_obat = o.id_obat
    LEFT JOIN supplierm s ON p.id_supplier = s.id_supplier
    $where_sql
    ORDER BY p.tgl_order DESC, p.id_pengadaan DESC
",
);

$rows = [];
$totalTransaksi = 0;
$totalQty = 0;
$totalAnggaran = 0;
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = $r;
        $totalTransaksi++;
        $totalQty += (int) $r["jumlah_order"];
        $totalAnggaran += (float) $r["subtotal"];
    }
}

// Rekap anggaran per obat, untuk chart alokasi biaya
$qPerObat = mysqli_query(
    $conn,
    "
    SELECT
        o.nama_obat,
        SUM(p.jumlah_order * o.harga_per_pcs) AS total_biaya
    FROM pengadaan_obat p
    LEFT JOIN obatm o ON p.id_obat = o.id_obat
    $where_sql
    GROUP BY o.nama_obat
    ORDER BY total_biaya DESC
",
);
$chartPerObat = [];
if ($qPerObat) {
    while ($cr = mysqli_fetch_assoc($qPerObat)) {
        $chartPerObat[] = $cr;
    }
}

$exportParams = $_GET;
unset($exportParams["page"]);
$exportUrl = "cetak_laporan_keuangan.php";
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
        $isRupiah = false,
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
            $total += (float) ($row[$valueKey] ?? 0);
        }

        $parts = [];
        $legend = [];
        $start = 0;

        if ($total > 0) {
            $i = 0;
            foreach ($rows as $row) {
                $value = (float) ($row[$valueKey] ?? 0);
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
        $totalDisplay = $isRupiah ? rupiah($total) : $total;
        ?>
        <div class="data-container mb-4 report-chart-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="fw-bold mb-1"><?= e($title) ?></h5>
                    <small class="text-muted"><?= e($subtitle) ?></small>
                </div>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                    Total: <?= e($totalDisplay) ?>
                </span>
            </div>

            <div class="report-chart-layout">
                <div class="report-donut" style="background: conic-gradient(<?= e(
                    $gradient,
                ) ?>);">
                    <div class="report-donut-center">
                        <div class="report-donut-total" style="font-size:<?= $isRupiah
                            ? "17px"
                            : "32px" ?>;"><?= e($totalDisplay) ?></div>
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
                                        <?= $isRupiah
                                            ? e(rupiah($item["value"]))
                                            : e($item["value"]) ?> &bull; <?= e(
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
    .report-donut-total { font-weight: 800; color: var(--astar-blue, #0057B8); line-height: 1.1; padding: 0 8px; }
    .report-donut-label { margin-top: 6px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .6px; }
    .report-legend { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .report-legend-item { display: flex; gap: 10px; align-items: flex-start; padding: 12px; border: 1px solid rgba(15, 61, 130, 0.07); border-radius: 14px; background: #fbfdff; }
    .report-legend-dot { width: 13px; height: 13px; border-radius: 999px; margin-top: 4px; flex-shrink: 0; }
    .report-legend-text { min-width: 0; }
    @media (max-width: 992px) {
        .report-chart-layout { grid-template-columns: 1fr; }
        .report-legend { grid-template-columns: 1fr; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Laporan Finance Obat</h3>
        <small class="text-muted">Laporan pengadaan &amp; anggaran obat &mdash; fokus kuantitas dan biaya, untuk Finance / Kampus.</small>
    </div>
    <a href="<?= e(
        $exportUrl,
    ) ?>" target="_blank" class="btn btn-primary fw-bold">
        <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
    </a>
</div>

<div class="data-container mb-4">
    <form method="GET" class="row g-3">
        <input type="hidden" name="page" value="laporan_keuangan">

        <div class="col-md-3">
            <label class="small fw-bold text-muted">Status</label>
            <select class="form-select" name="status">
                <option value="">Semua Status</option>
                <?php foreach (
                    ["Pending", "Proses", "Diterima", "Batal"]
                    as $st
                ): ?>
                    <option value="<?= e($st) ?>" <?= $status == $st
    ? "selected"
    : "" ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="small fw-bold text-muted">Dari Tanggal</label>
            <input type="date" class="form-control" name="tgl_awal" value="<?= e(
                $_GET["tgl_awal"] ?? "",
            ) ?>">
        </div>

        <div class="col-md-3">
            <label class="small fw-bold text-muted">Sampai Tanggal</label>
            <input type="date" class="form-control" name="tgl_akhir" value="<?= e(
                $_GET["tgl_akhir"] ?? "",
            ) ?>">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100">Filter</button>
        </div>

        <div class="col-md-1 d-flex align-items-end">
            <a href="dashboard_dokter.php?page=laporan_keuangan" class="btn btn-light border w-100 fw-bold">Reset</a>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="data-container text-center">
            <small class="text-muted fw-bold">TOTAL TRANSAKSI</small>
            <h2 class="fw-bold text-primary mb-0"><?= e($totalTransaksi) ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="data-container text-center">
            <small class="text-muted fw-bold">TOTAL KUANTITAS ORDER</small>
            <h2 class="fw-bold text-info mb-0"><?= e($totalQty) ?></h2>
        </div>
    </div>
    <div class="col-md-6">
        <div class="data-container text-center">
            <small class="text-muted fw-bold">TOTAL ANGGARAN OBAT</small>
            <h2 class="fw-bold text-success mb-0"><?= e(
                rupiah($totalAnggaran),
            ) ?></h2>
        </div>
    </div>
</div>

<?php renderReportDonutChart(
    "Alokasi Anggaran per Obat",
    "Distribusi total nilai pengadaan (Rp) berdasarkan nama obat.",
    $chartPerObat,
    "nama_obat",
    "total_biaya",
    true,
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
                    <th>Supplier</th>
                    <th>Qty</th>
                    <th>Harga Satuan</th>
                    <th>Subtotal</th>
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
                            <td><?= e(rupiah($r["harga_per_pcs"])) ?></td>
                            <td class="fw-bold"><?= e(
                                rupiah($r["subtotal"]),
                            ) ?></td>
                            <td><span class="badge bg-primary"><?= e(
                                $r["status"],
                            ) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold table-light">
                        <td colspan="5" class="text-end">TOTAL</td>
                        <td><?= e($totalQty) ?></td>
                        <td></td>
                        <td><?= e(rupiah($totalAnggaran)) ?></td>
                        <td></td>
                    </tr>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">Data pengadaan tidak tersedia.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>