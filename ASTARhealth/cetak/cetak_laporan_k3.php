<?php
session_start();
require_once dirname(__DIR__) . '/config/koneksi.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Dokter") {
    die("Akses Ditolak!");
}

date_default_timezone_set("Asia/Jakarta");

// AMBIL DATA DOKTER YANG SEDANG LOGIN UNTUK TTD
$id_user_login = $_SESSION["id_user"] ?? "";
$nama_dokter_ttd = "dr. Ike Indahwati"; // Default jika data tidak ditemukan
$jabatan_dokter_ttd = "Dokter Klinik ASTARhealth";

if ($id_user_login !== "") {
    $qStaff = mysqli_query(
        $conn,
        "SELECT nama_lengkap, jabatan FROM staffm WHERE id_user = '$id_user_login'",
    );
    if ($rowStaff = mysqli_fetch_assoc($qStaff)) {
        $nama_dokter_ttd = $rowStaff["nama_lengkap"];
        $jabatan_dokter_ttd = $rowStaff["jabatan"];
    }
}

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

function ambilDataK3Lengkap(
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
    $sql = "SELECT d.id_diagnosa, d.nama_penyakit, COUNT(rm.id_rekam_medis) AS total_kasus,
            (SELECT p2.unit_prodi FROM rekam_medis rm2 JOIN pasienm p2 ON rm2.id_pasien = p2.id_pasien WHERE rm2.id_diagnosa = d.id_diagnosa GROUP BY p2.unit_prodi ORDER BY COUNT(*) DESC LIMIT 1) AS prodi_terbanyak
            FROM rekam_medis rm JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa $where_sql GROUP BY d.id_diagnosa, d.nama_penyakit ORDER BY total_kasus DESC LIMIT $limit";
    $q = mysqli_query($conn, $sql);
    $rows = [];
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$tgl_awal = mysqli_real_escape_string($conn, $_GET["tgl_awal"] ?? "");
$tgl_akhir = mysqli_real_escape_string($conn, $_GET["tgl_akhir"] ?? "");

$dataMenular = ambilDataK3Lengkap($conn, "menular", $tgl_awal, $tgl_akhir, 10);
$dataTidakMenular = ambilDataK3Lengkap(
    $conn,
    "tidak_menular",
    $tgl_awal,
    $tgl_akhir,
    10,
);
$dataTerbanyak = ambilDataK3Lengkap($conn, "semua", $tgl_awal, $tgl_akhir, 10);

$periodeLaporan = bulanIndonesia(date("n")) . " " . date("Y");
if ($tgl_awal !== "" || $tgl_akhir !== "") {
    $periodeLaporan =
        ($tgl_awal !== "" ? date("d/m/Y", strtotime($tgl_awal)) : "Awal") .
        " s.d. " .
        ($tgl_akhir !== "" ? date("d/m/Y", strtotime($tgl_akhir)) : "Sekarang");
}
$nomorSurat = "LAP-K3/ASTARhealth/" . bulanRomawi(date("n")) . "/" . date("Y");

function renderTabelK3($rows, $judul)
{
    ?>
    <div style="margin-top: 20px; page-break-inside: avoid;">
        <h3 style="font-size: 13px; text-transform: uppercase; margin-bottom: 8px; border-left: 4px solid #0057B8; padding-left: 10px;"><?= $judul ?></h3>
        <table class="table-report">
            <thead>
                <tr>
                    <th style="width:30px;">No</th>
                    <th style="width:100px;">Kode Diagnosa</th>
                    <th>Nama Penyakit</th>
                    <th style="width:180px;">Unit/Prodi Terbanyak</th>
                    <th style="width:80px;">Total Kasus</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) > 0):

                    $no = 1;
                    $grandTotal = 0;
                    foreach ($rows as $row):
                        $grandTotal += $row["total_kasus"]; ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><b><?= e(
                            $row["id_diagnosa"],
                        ) ?></b></td>
                        <td><?= e($row["nama_penyakit"]) ?></td>
                        <td class="text-center"><?= e(
                            $row["prodi_terbanyak"] ?: "-",
                        ) ?></td>
                        <td class="text-center"><b><?= $row[
                            "total_kasus"
                        ] ?></b></td>
                    </tr>
                <?php
                    endforeach;
                    ?>
                    <tr class="total-row">
                        <td colspan="4" class="text-right">TOTAL KASUS KATEGORI INI</td>
                        <td class="text-center"><?= $grandTotal ?></td>
                    </tr>
                <?php
                else:
                     ?>
                    <tr><td colspan="5" class="text-center">Tidak ada data ditemukan.</td></tr>
                <?php
                endif; ?>
            </tbody>
        </table>
    </div>
<?php
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan K3 Astar</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; line-height: 1.5; padding: 30px 50px; color: #1a1a1a; max-width: 1000px; margin: 0 auto; }
        .no-print { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn-print { padding: 8px 16px; cursor: pointer; background: #0057B8; color: white; border: none; border-radius: 4px; font-weight: bold; }
        .btn-close { padding: 8px 16px; cursor: pointer; background: #64748b; color: white; border: none; border-radius: 4px; }
        .kop-surat { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; border-bottom: 4px double #000; padding-bottom: 14px; margin-bottom: 20px; }
        .kop-logo { height: 80px; }
        .kop-logo img { height: 100%; width: auto; }
        .kop-text p { margin: 2px 0 0; font-size: 13px; }
        .meta-surat { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; }
        .judul-doc { text-align: center; font-weight: bold; font-size: 16px; text-transform: uppercase; margin-bottom: 5px; }
        .judul-underline { width: 350px; border-bottom: 2px solid #000; margin: 0 auto 20px; }
        .table-report { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 12px; }
        .table-report th, .table-report td { border: 1px solid #000; padding: 7px; }
        .table-report th { background: #f2f2f2; text-align: center; font-weight: bold; }
        .total-row td { background: #f9f9f9; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        /* CSS Tanda Tangan Rapih */
   .ttd-wrapper {
        display: flex;
        justify-content: space-between; 
        margin-top: 50px;
        page-break-inside: avoid;
    }

    .ttd-block {
        width: 320px;
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

    .footer-doc { 
        margin-top: 60px;
        padding-top: 10px;
        border-top: 1px solid #ccc; 
        font-size: 11px; 
        color: #777; 
        display: flex; 
        justify-content: space-between; 
    }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Cetak / Simpan PDF</button>
        <button onclick="window.close()" class="btn-close">Tutup</button>
    </div>

    <!-- KOP SURAT -->
    <div class="kop-surat">
        <div class="kop-logo">
            <img src="../assets/img/logoA.png" alt="Logo ASTARhealth">
        </div>
        <div class="kop-text">
            <p>Politeknik Astar &mdash; Kawasan Industri Delta Silicon, Cikarang</p>
            <p>Telp: +62 0123-0123-123 &nbsp;|&nbsp; Email: health@polytechnic.astar.ac.id</p>
        </div>
    </div>

    <!-- META DATA -->
    <div class="meta-surat">
        <div>
            Nomor&nbsp;: <?= e($nomorSurat) ?><br>
            Perihal&nbsp;: <b>Laporan Analisis Penyakit Berdasarkan Unit/Prodi</b>
        </div>
        <div class="text-right">
            Cikarang, <?= date("d F Y") ?>
        </div>
    </div>

    <div class="judul-doc">Laporan Rekapitulasi Penyakit Unit K3</div>
    <div class="judul-underline"></div>

    <div style="margin-bottom: 15px; font-size: 14px; text-align: justify;">
        Berikut adalah data rekapitulasi penyakit berdasarkan kunjungan pasien di Klinik ASTARhealth Politeknik Astar. 
        Laporan ini mencakup identifikasi Unit/Program Studi dengan frekuensi kasus tertinggi guna pemantauan kesehatan lingkungan kerja dan kampus.
    </div>

    <!-- TABEL DATA -->
    <?php renderTabelK3($dataMenular, "1. Top 10 Penyakit Menular"); ?>
    <?php renderTabelK3(
        $dataTidakMenular,
        "2. Top 10 Penyakit Tidak Menular",
    ); ?>
    <?php renderTabelK3(
        $dataTerbanyak,
        "3. Top 10 Penyakit Terbanyak (Keseluruhan)",
    ); ?>


<!-- TTD -->
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

    <!-- KOLOM KANAN: FINANCE (MENGETAHUI) -->
    <div class="ttd-block">
        <div><b>Mengetahui,</b></div>
        <div><b>K3 Politeknik Astar</b></div>
        
        <div class="ttd-space"></div>
        
        <div class="ttd-nama">__________________________</div>
        <div class="ttd-jabatan">Wakil Divisi K3</div>
    </div>
</div>

<!-- FOOTER DI LUAR WRAPPER TTD -->
<div class="footer-doc">
    <span>No. Laporan: <?= e($nomorSurat) ?></span>
    <span>Dicetak melalui Sistem ASTARhealth | <?= date("d/m/Y H:i") ?></span>
</div>

</body>
</html>