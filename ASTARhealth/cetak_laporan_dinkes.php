<?php
session_start();
require_once "koneksi.php";

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
$tgl_awal = mysqli_real_escape_string($conn, $_GET["tgl_awal"] ?? "");
$tgl_akhir = mysqli_real_escape_string($conn, $_GET["tgl_akhir"] ?? "");

$where = ["rm.id_diagnosa IS NOT NULL", "rm.id_diagnosa <> ''"];

if ($search !== "") {
    $where[] = "(d.id_diagnosa LIKE '%$search%' OR d.nama_penyakit LIKE '%$search%')";
}

if ($tgl_awal !== "" && $tgl_akhir !== "") {
    $where[] = "rm.tgl_kunjungan BETWEEN '$tgl_awal' AND '$tgl_akhir'";
} elseif ($tgl_awal !== "") {
    $where[] = "rm.tgl_kunjungan >= '$tgl_awal'";
} elseif ($tgl_akhir !== "") {
    $where[] = "rm.tgl_kunjungan <= '$tgl_akhir'";
}

$where_sql = "WHERE " . implode(" AND ", $where);

$qPenyakit = mysqli_query(
    $conn,
    "
    SELECT
        d.id_diagnosa,
        d.nama_penyakit,
        SUM(
            CASE
                WHEN LOWER(TRIM(p.jenis_kelamin)) IN ('l', 'laki-laki', 'laki laki', 'pria', 'male') THEN 1
                ELSE 0
            END
        ) AS laki_laki,
        SUM(
            CASE
                WHEN LOWER(TRIM(p.jenis_kelamin)) IN ('p', 'perempuan', 'wanita', 'female') THEN 1
                ELSE 0
            END
        ) AS perempuan,
        COUNT(rm.id_rekam_medis) AS total_kasus
    FROM rekam_medis rm
    JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa
    LEFT JOIN pasienm p ON rm.id_pasien = p.id_pasien
    $where_sql
    GROUP BY d.id_diagnosa, d.nama_penyakit
    ORDER BY total_kasus DESC
    LIMIT 10
",
);

if (!$qPenyakit) {
    die("Query laporan Dinkes error: " . mysqli_error($conn));
}

$dataPenyakit = [];
$totalLaki = 0;
$totalPerempuan = 0;
$totalKasus = 0;

while ($row = mysqli_fetch_assoc($qPenyakit)) {
    $dataPenyakit[] = $row;
    $totalLaki += (int) $row["laki_laki"];
    $totalPerempuan += (int) $row["perempuan"];
    $totalKasus += (int) $row["total_kasus"];
}

$periodeLaporan = bulanIndonesia(date("n")) . " " . date("Y");

if ($tgl_awal !== "" || $tgl_akhir !== "") {
    $periodeLaporan =
        ($tgl_awal !== "" ? date("d F Y", strtotime($tgl_awal)) : "Awal") .
        " s.d. " .
        ($tgl_akhir !== "" ? date("d F Y", strtotime($tgl_akhir)) : "Sekarang");
}

$nomorSurat =
    "LAP-DINKES/ASTARhealth/" . bulanRomawi(date("n")) . "/" . date("Y");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Dinkes - 10 Penyakit Terbanyak</title>
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
            width: 360px;
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
        .total-row th,
        .total-row td {
            font-weight: bold;
            background: #f7f7f7;
        }
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
            <img src="assets/img/logoA.png" alt="ASTARhealth">
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
            Perihal&nbsp;: <b>Laporan Bulanan Kunjungan Penyakit</b>
        </div>
        <div style="text-align:right;">
            Cikarang, <?= date("d F Y") ?>
        </div>
    </div>

    <div class="judul">Laporan Bulanan Kunjungan Penyakit</div>
    <div class="judul-underline"></div>

    <div class="isi-surat">
        Kepada Yth,<br>
        <b>Kepala Dinas Kesehatan Kota Cikarang</b><br>
        Di Tempat
    </div>

    <div class="isi-surat">
        Bersama ini kami sampaikan rekapitulasi 10 (sepuluh) penyakit terbanyak yang ditangani
        di Klinik ASTARhealth Politeknik Astar berdasarkan transaksi rekam medis pada sistem ASTARhealth.
    </div>

    <table class="tabel-data">
        <tr>
            <td class="label">Bulan / Periode</td>
            <td class="titik-dua">:</td>
            <td><?= e($periodeLaporan) ?></td>
        </tr>
        <tr>
            <td class="label">Nama Klinik</td>
            <td class="titik-dua">:</td>
            <td><b>Klinik ASTARhealth Politeknik Astar</b></td>
        </tr>
        <tr>
            <td class="label">Jenis Laporan</td>
            <td class="titik-dua">:</td>
            <td>10 penyakit terbanyak berdasarkan data rekam medis</td>
        </tr>
        <tr>
            <td class="label">Sumber Data</td>
            <td class="titik-dua">:</td>
            <td>Transaksi rekam medis pasien</td>
        </tr>
    </table>

    <table class="table-report">
        <thead>
            <tr>
                <th style="width:34px;">No</th>
                <th style="width:95px;">Kode ICD-10 / Diagnosa</th>
                <th>Jenis Penyakit</th>
                <th style="width:90px;">Jumlah Kasus<br>Laki-laki</th>
                <th style="width:90px;">Jumlah Kasus<br>Perempuan</th>
                <th style="width:80px;">Total Kasus</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($dataPenyakit) > 0): ?>
                <?php $no = 1; ?>
                <?php foreach ($dataPenyakit as $row): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><b><?= e(
                            $row["id_diagnosa"],
                        ) ?></b></td>
                        <td><?= e($row["nama_penyakit"]) ?></td>
                        <td class="text-center"><?= e($row["laki_laki"]) ?></td>
                        <td class="text-center"><?= e($row["perempuan"]) ?></td>
                        <td class="text-center"><b><?= e(
                            $row["total_kasus"],
                        ) ?></b></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" class="text-center">TOTAL KUNJUNGAN</td>
                    <td class="text-center"><?= e($totalLaki) ?></td>
                    <td class="text-center"><?= e($totalPerempuan) ?></td>
                    <td class="text-center"><?= e($totalKasus) ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Data penyakit tidak tersedia.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="penutup">
        Demikian laporan bulanan kunjungan penyakit ini kami sampaikan untuk menjadi bahan pendataan,
        pemantauan, dan evaluasi pelayanan kesehatan. Atas perhatian dan kerja samanya kami ucapkan terima kasih.
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
