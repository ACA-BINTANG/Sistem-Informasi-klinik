<?php
session_start();
require_once dirname(__DIR__) . '/config/koneksi.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Dokter") {
    die("Akses Ditolak!");
}

date_default_timezone_set("Asia/Jakarta");

function e($text)
{
    return htmlspecialchars($text ?? "", ENT_QUOTES, "UTF-8");
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

$search = mysqli_real_escape_string($conn, $_GET["search"] ?? "");
$status = mysqli_real_escape_string($conn, $_GET["status"] ?? "");
$tgl_awal = mysqli_real_escape_string($conn, $_GET["tgl_awal"] ?? "");
$tgl_akhir = mysqli_real_escape_string($conn, $_GET["tgl_akhir"] ?? "");

$where = [];

if ($search !== "") {
    $where[] = "(
        p.id_pengadaan LIKE '%$search%'
        OR o.nama_obat LIKE '%$search%'
        OR s.nama_supplier LIKE '%$search%'
        OR p.catatan LIKE '%$search%'
    )";
}

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

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$qPengadaan = mysqli_query(
    $conn,
    "
    SELECT
        p.id_pengadaan,
        p.id_obat,
        p.id_supplier,
        p.jumlah_order,
        p.tgl_order,
        p.tgl_estimasi_tiba,
        p.status,
        p.catatan,
        o.nama_obat,
        o.satuan,
        s.nama_supplier,
        s.kontak AS kontak_supplier
    FROM pengadaan_obat p
    LEFT JOIN obatm o ON p.id_obat = o.id_obat
    LEFT JOIN supplierm s ON p.id_supplier = s.id_supplier
    $where_sql
    ORDER BY p.tgl_order DESC, p.id_pengadaan DESC
",
);

if (!$qPengadaan) {
    die("Query laporan Siloam error: " . mysqli_error($conn));
}

$dataPengadaan = [];
$totalTransaksi = 0;
$totalJumlahOrder = 0;
$totalPending = 0;
$totalDiterima = 0;
$totalBatal = 0;

while ($row = mysqli_fetch_assoc($qPengadaan)) {
    $dataPengadaan[] = $row;
    $totalTransaksi++;
    $totalJumlahOrder += (int) $row["jumlah_order"];

    if ($row["status"] === "Pending") {
        $totalPending++;
    } elseif ($row["status"] === "Diterima") {
        $totalDiterima++;
    } elseif ($row["status"] === "Batal") {
        $totalBatal++;
    }
}

$periodeLaporan = bulanIndonesia(date("n")) . " " . date("Y");

if ($tgl_awal !== "" || $tgl_akhir !== "") {
    $periodeLaporan =
        ($tgl_awal !== "" ? date("d F Y", strtotime($tgl_awal)) : "Awal") .
        " s.d. " .
        ($tgl_akhir !== "" ? date("d F Y", strtotime($tgl_akhir)) : "Sekarang");
}

$nomorSurat =
    "LAP-SILOAM/ASTARhealth/" . bulanRomawi(date("n")) . "/" . date("Y");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Siloam - Pengadaan Obat</title>
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
        .no-print {
            margin-bottom: 24px;
            display: flex;
            gap: 10px;
        }
        .btn-print {
            padding: 10px 20px;
            cursor: pointer;
            background: #0057B8;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-close {
            padding: 10px 20px;
            cursor: pointer;
            background: #64748b;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .kop-surat {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
            border-bottom: 4px double #000;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .kop-logo {
            height: 86px;
            display: flex;
            align-items: center;
        }
        .kop-logo img {
            height: 100%;
            width: auto;
            object-fit: contain;
        }
        .kop-text p {
            margin: 2px 0 0;
            font-size: 13px;
        }
        .meta-surat {
            display: flex;
            justify-content: space-between;
            margin-bottom: 22px;
            font-size: 14px;
        }
        .meta-surat div { line-height: 1.55; }
        .judul {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .judul-underline {
            width: 300px;
            border-bottom: 2px solid #000;
            margin: 0 auto 24px;
        }
        .isi-surat {
            margin-bottom: 16px;
            text-align: justify;
            font-size: 14.5px;
        }
        .tabel-data {
            width: 100%;
            margin: 14px 0 20px;
            border-collapse: collapse;
            font-size: 14.5px;
        }
        .tabel-data td {
            padding: 4px 0;
            vertical-align: top;
        }
        .tabel-data .label { width: 190px; }
        .tabel-data .titik-dua { width: 14px; }
        .box-ringkasan {
            border: 1px solid #0057B8;
            border-radius: 4px;
            background: #fafafa;
            padding: 12px 14px;
            margin: 18px 0;
            font-size: 14px;
        }
        .box-ringkasan table {
            width: 100%;
            border-collapse: collapse;
        }
        .box-ringkasan td {
            padding: 4px 8px;
            border: none;
        }
        .table-report {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0 20px;
            font-size: 12.5px;
        }
        .table-report th,
        .table-report td {
            border: 1px solid #000;
            padding: 7px 6px;
            vertical-align: top;
        }
        .table-report th {
            text-align: center;
            font-weight: bold;
            background: #f1f1f1;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .penutup {
            margin-top: 18px;
            margin-bottom: 38px;
            text-align: justify;
            font-size: 14.5px;
        }
        .ttd-wrapper {
            display: flex;
            justify-content: flex-end; /* Memindahkan ke kanan */
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .ttd-block {
            width: 300px;
            text-align: center;
            font-size: 14.5px;
        }
        .ttd-space { height: 85px; }
        .ttd-nama { font-weight: bold; font-size: 15px; }
        .ttd-jabatan { margin-top: 5px; font-size: 14px; }

        .footer-doc { 
            margin-top: 60px; padding-top: 10px; border-top: 1px solid #ccc; 
            font-size: 11px; color: #777; display: flex; justify-content: space-between; 
        }
        @media print { .no-print { display: none; } }
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
            Perihal&nbsp;: <b>Laporan Pengadaan Obat</b>
        </div>
        <div style="text-align:right;">
            Cikarang, <?= date("d F Y") ?>
        </div>
    </div>

    <div class="judul">Laporan Pengadaan Obat</div>
    <div class="judul-underline"></div>

    <div class="isi-surat">
        Kepada Yth,<br>
        <b>Manajemen / Tim Farmasi Siloam Corporate Clinic</b><br>
        Di Tempat
    </div>

    <div class="isi-surat">
        Bersama ini kami sampaikan laporan pengadaan obat Unit Kesehatan Kampus ASTARhealth
        sebagai bahan monitoring kebutuhan obat dan koordinasi ketersediaan layanan kesehatan.
    </div>

    <table class="tabel-data">
        <tr>
            <td class="label">Nama Klinik</td>
            <td class="titik-dua">:</td>
            <td><b>Klinik ASTARhealth Politeknik Astar</b></td>
        </tr>
        <tr>
            <td class="label">Tujuan Laporan</td>
            <td class="titik-dua">:</td>
            <td>Siloam Corporate Clinic</td>
        </tr>
        <tr>
            <td class="label">Periode Laporan</td>
            <td class="titik-dua">:</td>
            <td><?= e($periodeLaporan) ?></td>
        </tr>
        <tr>
            <td class="label">Sumber Data</td>
            <td class="titik-dua">:</td>
            <td>Data transaksi pengadaan obat pada sistem ASTARhealth</td>
        </tr>
    </table>

    <div class="box-ringkasan">
        <table>
            <tr>
                <td><b>Total Transaksi</b></td>
                <td>: <?= e($totalTransaksi) ?> transaksi</td>
                <td><b>Total Jumlah Order</b></td>
                <td>: <?= e($totalJumlahOrder) ?> item</td>
            </tr>
            <tr>
                <td><b>Pending</b></td>
                <td>: <?= e($totalPending) ?></td>
                <td><b>Diterima</b></td>
                <td>: <?= e($totalDiterima) ?></td>
            </tr>
            <tr>
                <td><b>Batal</b></td>
                <td>: <?= e($totalBatal) ?></td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>

    <table class="table-report">
        <thead>
            <tr>
                <th style="width:34px;">No</th>
                <th style="width:95px;">ID Pengadaan</th>
                <th style="width:78px;">Tanggal Order</th>
                <th>Nama Obat</th>
                <th>Supplier</th>
                <th style="width:70px;">Jumlah</th>
                <th style="width:76px;">Target Tiba</th>
                <th style="width:75px;">Status</th>
                <th>Catatan</th>
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
                            $row["jumlah_order"],
                        ) ?> <?= e($row["satuan"] ?: "") ?></td>
                        <td class="text-center"><?= $row["tgl_estimasi_tiba"]
                            ? date(
                                "d/m/Y",
                                strtotime($row["tgl_estimasi_tiba"]),
                            )
                            : "-" ?></td>
                        <td class="text-center"><?= e(
                            $row["status"] ?: "-",
                        ) ?></td>
                        <td><?= e($row["catatan"] ?: "-") ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">Data pengadaan obat tidak tersedia.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="penutup">
        Demikian laporan pengadaan obat ini kami sampaikan sebagai bahan koordinasi dan evaluasi
        kebutuhan obat. Atas perhatian dan kerja samanya kami ucapkan terima kasih.
    </div>

    <!-- TANDA TANGAN DI KANAN -->
    <div class="ttd-wrapper">
        <div class="ttd-block">
            <div>Cikarang, <?= date("d") .
                " " .
                bulanIndonesia(date("n")) .
                " " .
                date("Y") ?></div>
            <div><b>Penanggung Jawab Klinik,</b></div>
            
            <div class="ttd-space"></div>
            <div class="ttd-nama">__________________________</div>

            <div class="ttd-nama">dr.Ike Indahwati</div>
            <div class="ttd-jabatan">Dokter UKK</div>
        </div>
    </div>

    <div class="footer-doc">
        <span>No. Laporan: <?= e($nomorSurat) ?></span>
        <span>Dicetak melalui Sistem ASTARhealth | <?= date(
            "d/m/Y H:i",
        ) ?></span>
    </div>
</body>
</html>
