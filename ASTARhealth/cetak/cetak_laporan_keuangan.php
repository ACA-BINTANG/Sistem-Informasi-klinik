<?php
session_start();
require_once dirname(__DIR__) . '/config/koneksi.php';

// Ambil data dokter yang sedang login
$id_user_login = $_SESSION["id_user"] ?? "";
$nama_dokter_login = "Dokter Klinik"; // Default
$jabatan_dokter_login = "Dokter Klinik ASTARhealth"; // Default

if ($id_user_login !== "") {
    $qStaff = mysqli_query(
        $conn,
        "SELECT nama_lengkap, jabatan FROM staffm WHERE id_user = '$id_user_login'",
    );
    if ($rowStaff = mysqli_fetch_assoc($qStaff)) {
        $nama_dokter_login = $rowStaff["nama_lengkap"];
        $jabatan_dokter_login = $rowStaff["jabatan"];
    }
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Dokter") {
    die("Akses Ditolak!");
}

date_default_timezone_set("Asia/Jakarta");

function e($text)
{
    return htmlspecialchars($text ?? "", ENT_QUOTES, "UTF-8");
}

function rupiah($angka)
{
    return "Rp " . number_format((float) $angka, 0, ",", ".");
}

function bulanIndonesia($bulan)
{
    $map = [
        1 => "Januari",
        2 => "Februari",
        3 => "Maret",
        4 => "April",
        5 => "Mei",
        6 => "Juni",
        7 => "Juli",
        8 => "Agustus",
        9 => "September",
        10 => "Oktober",
        11 => "November",
        12 => "Desember",
    ];
    return $map[(int) $bulan] ?? "";
}

function bulanRomawi($bulan)
{
    $map = [
        1 => "I",
        2 => "II",
        3 => "III",
        4 => "IV",
        5 => "V",
        6 => "VI",
        7 => "VII",
        8 => "VIII",
        9 => "IX",
        10 => "X",
        11 => "XI",
        12 => "XII",
    ];
    return $map[(int) $bulan] ?? "";
}

$tgl_awal = mysqli_real_escape_string($conn, $_GET["tgl_awal"] ?? "");
$tgl_akhir = mysqli_real_escape_string($conn, $_GET["tgl_akhir"] ?? "");

// Laporan keuangan hanya menghitung transaksi pengadaan yang berhasil.
$where = ["p.status = 'Diterima'"];

if ($tgl_awal !== "" && $tgl_akhir !== "") {
    $where[] = "p.tgl_order BETWEEN '$tgl_awal' AND '$tgl_akhir'";
} elseif ($tgl_awal !== "") {
    $where[] = "p.tgl_order >= '$tgl_awal'";
} elseif ($tgl_akhir !== "") {
    $where[] = "p.tgl_order <= '$tgl_akhir'";
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$qPengadaan = mysqli_query(
    $conn,
    "
    SELECT
        p.id_pengadaan,
        p.jumlah_order,
        COALESCE(NULLIF(p.jumlah_diterima, 0), p.jumlah_order) AS jumlah_realisasi,
        p.tgl_order,
        p.status,
        o.nama_obat,
        o.satuan,
        o.harga_per_pcs,
        s.nama_supplier,
        (COALESCE(NULLIF(p.jumlah_diterima, 0), p.jumlah_order) * o.harga_per_pcs) AS subtotal
    FROM pengadaan_obat p
    LEFT JOIN obatm o ON p.id_obat = o.id_obat
    LEFT JOIN supplierm s ON p.id_supplier = s.id_supplier
    $where_sql
    ORDER BY p.created_at DESC, p.tgl_order DESC, p.id_pengadaan DESC
",
);

if (!$qPengadaan) {
    die("Query laporan keuangan error: " . mysqli_error($conn));
}

$dataPengadaan = [];
$totalTransaksi = 0;
$totalQty = 0;
$totalAnggaran = 0;
while ($row = mysqli_fetch_assoc($qPengadaan)) {
    $dataPengadaan[] = $row;
    $totalTransaksi++;
    $totalQty += (int) $row["jumlah_realisasi"];
    $totalAnggaran += (float) $row["subtotal"];
}

// Seluruh nilai pada laporan ini sudah merupakan anggaran terealisasi
// karena hanya transaksi berstatus Diterima yang dimasukkan.
$anggaranRealisasi = $totalAnggaran;

$periodeLaporan = bulanIndonesia(date("n")) . " " . date("Y");
if ($tgl_awal !== "" || $tgl_akhir !== "") {
    $periodeLaporan =
        ($tgl_awal !== "" ? date("d F Y", strtotime($tgl_awal)) : "Awal") .
        " s.d. " .
        ($tgl_akhir !== "" ? date("d F Y", strtotime($tgl_akhir)) : "Sekarang");
}

$nomorSurat = "LAP-KEU/ASTARhealth/" . bulanRomawi(date("n")) . "/" . date("Y");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pengadaan &amp; Anggaran Obat</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.55;
            padding: 40px 50px;
            color: #1a1a1a;
            max-width: 980px;
            margin: 0 auto;
            background: #ffffff;
        }
        .no-print { margin-bottom: 24px; display: flex; gap: 10px; }
        .btn-print, .btn-close { padding: 10px 20px; cursor: pointer; border: none; border-radius: 8px; font-weight: 600; color: #fff; }
        .btn-print { background: #0057B8; }
        .btn-close { background: #64748b; }
        .kop-surat {
            display: flex; flex-direction: column; align-items: flex-start; gap: 6px;
            border-bottom: 4px double #000; padding-bottom: 14px; margin-bottom: 22px;
        }
        .kop-logo { height: 78px; display: flex; align-items: center; }
        .kop-logo img { height: 100%; width: auto; object-fit: contain; }
        .kop-text p { margin: 2px 0 0; font-size: 13px; }
        .meta-surat { display: flex; justify-content: space-between; margin-bottom: 22px; font-size: 14px; }
        .meta-surat div { line-height: 1.55; }
        .judul { text-align: center; font-weight: bold; font-size: 18px; margin-bottom: 4px; text-transform: uppercase; }
        .judul-underline { width: 360px; border-bottom: 2px solid #000; margin: 0 auto 24px; }
        .isi-surat { margin-bottom: 16px; text-align: justify; font-size: 14.5px; }
        .tabel-data { width: 100%; margin: 14px 0 20px; border-collapse: collapse; font-size: 14.5px; }
        .tabel-data td { padding: 4px 0; vertical-align: top; }
        .tabel-data .label { width: 190px; }
        .tabel-data .titik-dua { width: 14px; }
        .box-ringkasan { border: 1px solid #0057B8; border-radius: 4px; background: #fafafa; padding: 12px 14px; margin: 18px 0; font-size: 14px; }
        .box-ringkasan table { width: 100%; border-collapse: collapse; }
        .box-ringkasan td { padding: 4px 8px; border: none; }
        .box-ringkasan .nilai-besar { font-weight: bold; color: #000; }
        .rekap-status { width: 100%; border-collapse: collapse; margin: 16px 0 22px; font-size: 13px; }
        .rekap-status th, .rekap-status td { border: 1px solid #000; padding: 6px 8px; }
        .rekap-status th { background: #f1f1f1; text-align: center; }
        .table-report { width: 100%; border-collapse: collapse; margin: 16px 0 20px; font-size: 12px; }
        .table-report th, .table-report td { border: 1px solid #000; padding: 6px 5px; vertical-align: top; }
        .table-report th { text-align: center; font-weight: bold; background: #f1f1f1; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; background: #f7f7f7; }
        .penutup { margin-top: 18px; margin-bottom: 38px; text-align: justify; font-size: 14.5px; }
/* CSS untuk merapikan TTD agar sejajar kiri-kanan */
.ttd-wrapper {
    display: flex;
    justify-content: space-between; /* Menjauhkan kiri dan kanan */
    margin-top: 50px;
    page-break-inside: avoid; /* Supaya TTD tidak terpotong halaman */
}

.ttd-block {
    width: 300px;
    text-align: center;
    font-size: 14.5px;
}

.ttd-space {
    height: 80px; /* Ruang tanda tangan */
}

.ttd-nama {
    font-weight: bold;
    font-size: 15px;
}

.ttd-jabatan {
    font-size: 14px;
    margin-top: 5px;
}
        .footer-doc { margin-top: 36px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 11px; color: #777; display: flex; justify-content: space-between; }
        @media print {
            .no-print { display: none; }
            body { padding: 0 18px; max-width: 100%; }
            .table-report th, .total-row td, .rekap-status th { background: #f1f1f1 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Cetak / Simpan sebagai PDF</button>
        <button onclick="window.close()" class="btn-close">Tutup Halaman</button>
    </div>

    <div class="kop-surat">
        <div class="kop-logo">
            <img src="../assets/img/logoA.png" alt="ASTARhealth">
        </div>
        <div class="kop-text">
            <p>Politeknik Astar &mdash; Kawasan Industri Delta Silicon, Cikarang</p>
            <p>Telp: +62 0123-0123-123 &nbsp;|&nbsp; Email: health@polytechnic.astar.ac.id</p>
        </div>
    </div>

    <div class="meta-surat">
        <div>
            Nomor&nbsp;: <?= e($nomorSurat) ?><br>
            Lampiran&nbsp;: -<br>
            Perihal&nbsp;: <b>Laporan Pengadaan &amp; Anggaran Obat</b>
        </div>
        <div style="text-align:right;">
            Cikarang, <?= date("d F Y") ?>
        </div>
    </div>

    <div class="judul">Laporan Pengadaan &amp; Anggaran Obat</div>
    <div class="judul-underline"></div>

    <div class="isi-surat">
        Kepada Yth,<br>
        <b>Bagian Keuangan / Finance Politeknik Astar</b><br>
        Di Tempat
    </div>

    <div class="isi-surat">
        Bersama ini kami sampaikan laporan pengadaan obat Unit Kesehatan Kampus ASTARhealth
        sebagai bahan monitoring kuantitas dan anggaran biaya obat, guna keperluan rekonsiliasi
        dan perencanaan anggaran kampus.
    </div>

    <table class="tabel-data">
        <tr>
            <td class="label">Nama Klinik</td>
            <td class="titik-dua">:</td>
            <td><b>Klinik ASTARhealth Politeknik Astar</b></td>
        </tr>
        <tr>
            <td class="label">Periode Laporan</td>
            <td class="titik-dua">:</td>
            <td><?= e($periodeLaporan) ?></td>
        </tr>
        <tr>
            <td class="label">Sumber Data</td>
            <td class="titik-dua">:</td>
            <td>Data transaksi pengadaan obat &amp; harga master obat pada sistem ASTARhealth</td>
        </tr>
    </table>

    <div class="box-ringkasan">
        <table>
            <tr>
                <td><b>Total Transaksi Pengadaan</b></td>
                <td>: <?= e($totalTransaksi) ?> transaksi</td>
                <td><b>Total Kuantitas Order</b></td>
                <td>: <?= e($totalQty) ?> item</td>
            </tr>
            <tr>
                <td><b>Total Anggaran Pengadaan Berhasil</b></td>
                <td class="nilai-besar">: <?= e(rupiah($totalAnggaran)) ?></td>
                <td><b>Anggaran Terealisasi</b> (Diterima)</td>
                <td class="nilai-besar">: <?= e(rupiah($anggaranRealisasi)) ?></td>
            </tr>
        </table>
    </div>

    <div style="margin: 14px 0 20px; padding: 10px 12px; border: 1px solid #b7d4b7; background: #f3fbf3; font-size: 13px;">
        <b>Catatan:</b> Laporan ini hanya memuat pengadaan obat dengan status <b>Diterima</b>.
    </div>

    <table class="table-report">
        <thead>
            <tr>
                <th style="width:28px;">No</th>
                <th style="width:80px;">ID Pengadaan</th>
                <th style="width:70px;">Tgl Order</th>
                <th>Nama Obat</th>
                <th>Supplier</th>
                <th style="width:55px;">Qty</th>
                <th style="width:75px;">Harga Satuan</th>
                <th style="width:85px;">Subtotal</th>
                <th style="width:62px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($dataPengadaan) > 0): ?>
                <?php $no = 1; ?>
                <?php foreach ($dataPengadaan as $row): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><b><?= e(
                            $row["id_pengadaan"],
                        ) ?></b></td>
                        <td class="text-center"><?= $row["tgl_order"]
                            ? date("d/m/Y", strtotime($row["tgl_order"]))
                            : "-" ?></td>
                        <td><?= e($row["nama_obat"] ?: "-") ?></td>
                        <td><?= e($row["nama_supplier"] ?: "-") ?></td>
                        <td class="text-center"><?= e(
                            $row["jumlah_realisasi"],
                        ) ?> <?= e($row["satuan"] ?: "") ?></td>
                        <td class="text-right"><?= e(
                            rupiah($row["harga_per_pcs"]),
                        ) ?></td>
                        <td class="text-right"><b><?= e(
                            rupiah($row["subtotal"]),
                        ) ?></b></td>
                        <td class="text-center"><?= e(
                            $row["status"] ?: "-",
                        ) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="5" class="text-center">TOTAL</td>
                    <td class="text-center"><?= e($totalQty) ?></td>
                    <td></td>
                    <td class="text-right"><?= e(rupiah($totalAnggaran)) ?></td>
                    <td></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">Belum ada pengadaan berstatus Diterima pada periode ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="penutup">
        Demikian laporan pengadaan obat yang telah diterima dan anggaran terealisasi ini kami sampaikan sebagai bahan rekonsiliasi
        anggaran dan evaluasi belanja obat klinik. Atas perhatian dan kerja samanya kami ucapkan terima kasih.
    </div>

<div class="ttd-wrapper">
    <!-- KOLOM KIRI: DOKTER (PEMBUAT LAPORAN) -->
    <div class="ttd-block">
        <div>Cikarang, <?= date("d") .
            " " .
            bulanIndonesia(date("n")) .
            " " .
            date("Y") ?></div>
        <div><b>Penanggung Jawab Klinik,</b></div>
        
        <div class="ttd-space"></div>
        <div class="ttd-nama">__________________________</div>
        <div class="ttd-nama">dr. <?= e($nama_dokter_login) ?></div>
        <div class="ttd-jabatan"><?= e($jabatan_dokter_login) ?></div>
    </div>

    <!-- KOLOM KANAN: FINANCE (MENGETAHUI) -->
    <div class="ttd-block">
        <div><b>Mengetahui,</b></div>
        <div><b>Finance Politeknik Astar</b></div>
        
        <div class="ttd-space"></div>
        <div class="ttd-nama">__________________________</div>
        <div class="ttd-jabatan">Bagian Keuangan</div>
    </div>
</div>

    <div class="footer-doc">
        <span>No. Laporan: <?= e($nomorSurat) ?></span>
        <span>Dicetak pada: <?= date("d/m/Y H:i") ?></span>
    </div>
</body>
</html>