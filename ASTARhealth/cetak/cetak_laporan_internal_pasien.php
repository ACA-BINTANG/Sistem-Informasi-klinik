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

function labelJenisKelamin($jk)
{
    $normalized = strtolower(trim($jk ?? ""));

    if (
        in_array($normalized, ["l", "laki-laki", "laki laki", "pria", "male"])
    ) {
        return "Laki-laki";
    }

    if (in_array($normalized, ["p", "perempuan", "wanita", "female"])) {
        return "Perempuan";
    }

    return $jk ?: "-";
}

function isLakiLaki($jk)
{
    $normalized = strtolower(trim($jk ?? ""));
    return in_array($normalized, [
        "l",
        "laki-laki",
        "laki laki",
        "pria",
        "male",
    ]);
}

function isPerempuan($jk)
{
    $normalized = strtolower(trim($jk ?? ""));
    return in_array($normalized, ["p", "perempuan", "wanita", "female"]);
}

$search = mysqli_real_escape_string($conn, $_GET["search"] ?? "");
$kategori = mysqli_real_escape_string($conn, $_GET["kategori"] ?? "");
$status = mysqli_real_escape_string($conn, $_GET["status"] ?? "");
$prodiFilter = mysqli_real_escape_string($conn, $_GET["prodi"] ?? "");
$tgl_awal = mysqli_real_escape_string($conn, $_GET["tgl_awal"] ?? "");
$tgl_akhir = mysqli_real_escape_string($conn, $_GET["tgl_akhir"] ?? "");

$where = [];

if ($search !== "") {
    $where[] = "(
        rm.id_rekam_medis LIKE '%$search%'
        OR rm.no_antrian LIKE '%$search%'
        OR p.nama_pasien LIKE '%$search%'
        OR p.no_identitas LIKE '%$search%'
        OR d.nama_penyakit LIKE '%$search%'
        OR rj.tujuan_rs LIKE '%$search%'
    )";
}

if ($kategori !== "") {
    $where[] = "p.kategori_pasien = '$kategori'";
}

if ($status !== "") {
    $where[] = "rm.status = '$status'";
}

if ($prodiFilter !== "") {
    $where[] = "p.unit_prodi = '$prodiFilter'";
}

if ($tgl_awal !== "" && $tgl_akhir !== "") {
    $where[] = "rm.tgl_kunjungan BETWEEN '$tgl_awal' AND '$tgl_akhir'";
} elseif ($tgl_awal !== "") {
    $where[] = "rm.tgl_kunjungan >= '$tgl_awal'";
} elseif ($tgl_akhir !== "") {
    $where[] = "rm.tgl_kunjungan <= '$tgl_akhir'";
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$qDetail = mysqli_query(
    $conn,
    "
    SELECT
        rm.id_rekam_medis,
        rm.no_antrian,
        rm.tgl_kunjungan,
        rm.waktu_booking,
        rm.keluhan,
        rm.hasil_pemeriksaan,
        rm.status,
        rm.jenis_antrean,
        p.id_pasien,
        p.nama_pasien,
        p.no_identitas,
        p.jenis_kelamin,
        p.kategori_pasien,
        p.unit_prodi,
        d.nama_penyakit,
        COUNT(DISTINCT rj.id_rujukan) AS jumlah_rujukan,
        GROUP_CONCAT(DISTINCT rj.id_rujukan ORDER BY rj.id_rujukan SEPARATOR ', ') AS id_rujukan,
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
        rm.hasil_pemeriksaan,
        rm.status,
        rm.jenis_antrean,
        p.id_pasien,
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
",
);

if (!$qDetail) {
    die("Query detail laporan internal error: " . mysqli_error($conn));
}

$groups = [];
while ($row = mysqli_fetch_assoc($qDetail)) {
    $prodi = trim($row["unit_prodi"] ?? "");
    if ($prodi === "") {
        $prodi = "Tanpa Prodi";
    }

    if (!isset($groups[$prodi])) {
        $groups[$prodi] = [
            "rows" => [],
            "pasien_unik" => [],
            "rujukan_unik" => [],
            "total_rekam_medis" => 0,
            "total_kunjungan_mahasiswa" => 0,
            "total_laki_laki" => 0,
            "total_perempuan" => 0,
            "total_rujukan" => 0,
        ];
    }

    $groups[$prodi]["rows"][] = $row;
    $groups[$prodi]["total_rekam_medis"]++;

    if (($row["kategori_pasien"] ?? "") === "Mahasiswa") {
        $groups[$prodi]["total_kunjungan_mahasiswa"]++;
    }

    if (!empty($row["id_pasien"])) {
        $groups[$prodi]["pasien_unik"][$row["id_pasien"]] = true;
    }

    if (isLakiLaki($row["jenis_kelamin"] ?? "")) {
        $groups[$prodi]["total_laki_laki"]++;
    }

    if (isPerempuan($row["jenis_kelamin"] ?? "")) {
        $groups[$prodi]["total_perempuan"]++;
    }

    if (!empty($row["id_rujukan"])) {
        $ids = array_map("trim", explode(",", $row["id_rujukan"]));
        foreach ($ids as $idRujukan) {
            if ($idRujukan !== "") {
                $groups[$prodi]["rujukan_unik"][$idRujukan] = true;
            }
        }
    }
}

foreach ($groups as $prodi => $group) {
    $groups[$prodi]["total_pasien_unik"] = count($group["pasien_unik"]);
    $groups[$prodi]["total_rujukan"] = count($group["rujukan_unik"]);
}

if (count($groups) === 0) {
    $groups["Data Tidak Tersedia"] = [
        "rows" => [],
        "pasien_unik" => [],
        "rujukan_unik" => [],
        "total_rekam_medis" => 0,
        "total_kunjungan_mahasiswa" => 0,
        "total_laki_laki" => 0,
        "total_perempuan" => 0,
        "total_rujukan" => 0,
        "total_pasien_unik" => 0,
    ];
}

$periodeLaporan = bulanIndonesia(date("n")) . " " . date("Y");

if ($tgl_awal !== "" || $tgl_akhir !== "") {
    $periodeLaporan =
        ($tgl_awal !== "" ? date("d F Y", strtotime($tgl_awal)) : "Awal") .
        " s.d. " .
        ($tgl_akhir !== "" ? date("d F Y", strtotime($tgl_akhir)) : "Sekarang");
}

$nomorSurat = "LAP-INT/ASTARhealth/" . bulanRomawi(date("n")) . "/" . date("Y");

$totalPages = count($groups);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Internal Pasien per Prodi</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.45;
            padding: 34px 42px;
            color: #1a1a1a;
            max-width: 1080px;
            margin: 0 auto;
            background: #ffffff;
        }
        .no-print {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            font-family: Arial, sans-serif;
        }
        .btn-print,
        .btn-close,
        .btn-nav {
            padding: 10px 18px;
            cursor: pointer;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-print { background: #0057B8; }
        .btn-close { background: #64748b; }
        .btn-nav { background: #0f766e; }
        .btn-nav:disabled { background: #94a3b8; cursor: not-allowed; }
        .page-select {
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            min-width: 220px;
        }
        .page-indicator {
            font-size: 13px;
            color: #334155;
            font-weight: 700;
        }
        .report-page {
            display: none;
            min-height: 980px;
            background: #fff;
        }
        .report-page.active { display: block; }
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
        .kop-text p { margin: 2px 0 0; font-size: 13px; }
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
            width: 390px;
            border-bottom: 2px solid #000;
            margin: 0 auto 22px;
        }
        .isi-surat {
            margin-bottom: 14px;
            text-align: justify;
            font-size: 14px;
        }
        .tabel-data {
            width: 100%;
            margin: 12px 0 16px;
            border-collapse: collapse;
            font-size: 14px;
        }
        .tabel-data td { padding: 3px 0; vertical-align: top; }
        .tabel-data .label { width: 210px; }
        .tabel-data .titik-dua { width: 14px; }
        .box-ringkasan {
            border: 1px solid #0057B8;
            border-radius: 4px;
            background: #fafafa;
            padding: 10px 12px;
            margin: 14px 0;
            font-size: 13px;
        }
        .ringkasan-grid { width: 100%; border-collapse: collapse; }
        .ringkasan-grid td {
            width: 33.33%;
            padding: 7px 9px;
            border: 1px solid #ccc;
        }
        .ringkasan-label {
            font-size: 11px;
            color: #555;
            text-transform: uppercase;
        }
        .ringkasan-value {
            font-size: 18px;
            font-weight: bold;
        }
        .table-report {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px;
            font-size: 10.5px;
        }
        .table-report th,
        .table-report td {
            border: 1px solid #000;
            padding: 5px 4px;
            vertical-align: top;
        }
        .table-report th {
            text-align: center;
            font-weight: bold;
            background: #f1f1f1;
        }
        .text-center { text-align: center; }
        .penutup {
            margin-top: 14px;
            margin-bottom: 30px;
            text-align: justify;
            font-size: 14px;
        }
        .ttd-wrapper { display: flex; justify-content: flex-end; }
        .ttd-block {
            width: 300px;
            text-align: center;
            font-size: 14px;
        }
        .ttd-space { height: 80px; position: relative; }
        .ttd-cap {
            position: absolute;
            left: 8px;
            top: 6px;
            width: 74px;
            height: 74px;
            border: 1.5px dashed #999;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #999;
            text-align: center;
            line-height: 1.2;
        }
        .ttd-nama {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .footer-doc {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 11px;
            color: #777;
            display: flex;
            justify-content: space-between;
        }
        @media print {
            .no-print { display: none !important; }
            body {
                padding: 0 18px;
                max-width: 100%;
            }
            .report-page {
                display: block !important;
                page-break-after: always;
                min-height: auto;
            }
            .report-page:last-child { page-break-after: auto; }
            .table-report { font-size: 9.5px; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn-print">🖨️ Cetak / Simpan sebagai PDF</button>
        <button type="button" id="prevPage" class="btn-nav">Sebelumnya</button>
        <button type="button" id="nextPage" class="btn-nav">Berikutnya</button>
        <select id="pageSelect" class="page-select">
            <?php $selectIndex = 0; ?>
            <?php foreach ($groups as $prodi => $group): ?>
                <option value="<?= $selectIndex ?>">Halaman <?= $selectIndex +
    1 ?> - <?= e($prodi) ?></option>
                <?php $selectIndex++; ?>
            <?php endforeach; ?>
        </select>
        <span class="page-indicator" id="pageIndicator"></span>
        <button onclick="window.close()" class="btn-close">Tutup Halaman</button>
    </div>

    <?php $pageIndex = 0; ?>
    <?php foreach ($groups as $prodi => $group): ?>
        <?php
        $totalRekamMedis = (int) ($group["total_rekam_medis"] ?? 0);
        $totalKunjunganMahasiswa =
            (int) ($group["total_kunjungan_mahasiswa"] ?? 0);
        $totalPasienUnik = (int) ($group["total_pasien_unik"] ?? 0);
        $totalRujukan = (int) ($group["total_rujukan"] ?? 0);
        $totalLakiLaki = (int) ($group["total_laki_laki"] ?? 0);
        $totalPerempuan = (int) ($group["total_perempuan"] ?? 0);
        ?>

        <section class="report-page <?= $pageIndex === 0
            ? "active"
            : "" ?>" data-page="<?= $pageIndex ?>">
            <div class="kop-surat">
                <div class="kop-logo"><img src="../assets/img/logoA.png" alt="ASTARhealth"></div>
                <div class="kop-text">
                    <p>Politeknik Astar &mdash; Kawasan Industri Delta Silicon, Cikarang</p>
                    <p>Telp: +62 0123-0123-123 &nbsp;|&nbsp; Email: health@polytechnic.astar.ac.id</p>
                </div>
            </div>

            <div class="meta-surat">
                <div>
                    Nomor&nbsp;: <?= e($nomorSurat) ?><br>
                    Lampiran&nbsp;: -<br>
                    Perihal&nbsp;: <b>Laporan Internal Pasien per Program Studi</b>
                </div>
                <div style="text-align:right;">
                    Cikarang, <?= date("d F Y") ?><br>
                    Halaman <?= $pageIndex + 1 ?> dari <?= $totalPages ?>
                </div>
            </div>

            <div class="judul">Laporan Internal Pasien per Program Studi</div>
            <div class="judul-underline"></div>

            <div class="isi-surat">
                Kepada Yth,<br>
                <b>Pimpinan Unit Kesehatan Kampus ASTARhealth</b><br>
                Di Tempat
            </div>

            <div class="isi-surat">
                Bersama ini kami sampaikan laporan internal pasien untuk program studi <b><?= e(
                    $prodi,
                ) ?></b>
                yang memuat rekapitulasi kunjungan mahasiswa, transaksi rekam medis, dan rujukan pasien.
            </div>

            <table class="tabel-data">
                <tr>
                    <td class="label">Periode Laporan</td>
                    <td class="titik-dua">:</td>
                    <td><b><?= e($periodeLaporan) ?></b></td>
                </tr>
                <tr>
                    <td class="label">Nama Klinik</td>
                    <td class="titik-dua">:</td>
                    <td>Unit Kesehatan Kampus ASTARhealth</td>
                </tr>
                <tr>
                    <td class="label">Program Studi / Unit</td>
                    <td class="titik-dua">:</td>
                    <td><b><?= e($prodi) ?></b></td>
                </tr>
                <tr>
                    <td class="label">Jenis Laporan</td>
                    <td class="titik-dua">:</td>
                    <td>Kunjungan Mahasiswa, Rekam Medis, dan Rujukan</td>
                </tr>
            </table>

            <div class="box-ringkasan">
                <table class="ringkasan-grid">
                    <tr>
                        <td>
                            <div class="ringkasan-label">Kunjungan Mahasiswa</div>
                            <div class="ringkasan-value"><?= e(
                                $totalKunjunganMahasiswa,
                            ) ?></div>
                        </td>
                        <td>
                            <div class="ringkasan-label">Total Rekam Medis</div>
                            <div class="ringkasan-value"><?= e(
                                $totalRekamMedis,
                            ) ?></div>
                        </td>
                        <td>
                            <div class="ringkasan-label">Total Rujukan</div>
                            <div class="ringkasan-value"><?= e(
                                $totalRujukan,
                            ) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="ringkasan-label">Pasien Unik</div>
                            <div class="ringkasan-value"><?= e(
                                $totalPasienUnik,
                            ) ?></div>
                        </td>
                        <td>
                            <div class="ringkasan-label">Laki-laki</div>
                            <div class="ringkasan-value"><?= e(
                                $totalLakiLaki,
                            ) ?></div>
                        </td>
                        <td>
                            <div class="ringkasan-label">Perempuan</div>
                            <div class="ringkasan-value"><?= e(
                                $totalPerempuan,
                            ) ?></div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="isi-surat"><b>Detail Data Kunjungan, Rekam Medis, dan Rujukan:</b></div>

            <table class="table-report">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>No. RM</th>
                        <th>Pasien</th>
                        <th>NIM/NIP</th>
                        <th>Jenis Kelamin</th>
                        <th>Kategori</th>
                        <th>Diagnosa</th>
                        <th>Status RM</th>
                        <th>Rujukan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($group["rows"]) > 0): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($group["rows"] as $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <?= e($row["tgl_kunjungan"]) ?><br>
                                    <small><?= e(
                                        substr(
                                            $row["waktu_booking"] ?? "",
                                            0,
                                            5,
                                        ),
                                    ) ?></small>
                                </td>
                                <td>
                                    <?= e($row["id_rekam_medis"]) ?><br>
                                    <small><?= e($row["no_antrian"]) ?> / <?= e(
     $row["jenis_antrean"],
 ) ?></small>
                                </td>
                                <td><?= e($row["nama_pasien"] ?? "-") ?></td>
                                <td><?= e($row["no_identitas"] ?? "-") ?></td>
                                <td><?= e(
                                    labelJenisKelamin(
                                        $row["jenis_kelamin"] ?? "",
                                    ),
                                ) ?></td>
                                <td><?= e(
                                    $row["kategori_pasien"] ?? "-",
                                ) ?></td>
                                <td><?= e(
                                    $row["nama_penyakit"] ?? "Belum diagnosa",
                                ) ?></td>
                                <td><?= e($row["status"] ?? "-") ?></td>
                                <td>
                                    <?php if (
                                        !empty($row["tujuan_rujukan"])
                                    ): ?>
                                        <?= e($row["tujuan_rujukan"]) ?><br>
                                        <small><?= e(
                                            $row["id_rujukan"] ?? "",
                                        ) ?> / <?= e(
     $row["status_rujukan"] ?? "",
 ) ?></small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center">Data laporan internal tidak tersedia untuk prodi ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="penutup">
                Demikian laporan internal pasien program studi <b><?= e(
                    $prodi,
                ) ?></b> ini dibuat sebagai bahan
                rekapitulasi dan evaluasi layanan kesehatan kampus. Atas perhatian dan kerja samanya kami ucapkan terima kasih.
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
        </section>
        <?php $pageIndex++; ?>
    <?php endforeach; ?>

    <script>
        const pages = Array.from(document.querySelectorAll('.report-page'));
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const pageSelect = document.getElementById('pageSelect');
        const pageIndicator = document.getElementById('pageIndicator');
        let currentPage = 0;

        function showPage(index) {
            if (index < 0 || index >= pages.length) return;
            currentPage = index;
            pages.forEach((page, pageIndex) => {
                page.classList.toggle('active', pageIndex === currentPage);
            });
            pageSelect.value = String(currentPage);
            pageIndicator.textContent = `Halaman ${currentPage + 1} dari ${pages.length}`;
            prevBtn.disabled = currentPage === 0;
            nextBtn.disabled = currentPage === pages.length - 1;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        prevBtn.addEventListener('click', () => showPage(currentPage - 1));
        nextBtn.addEventListener('click', () => showPage(currentPage + 1));
        pageSelect.addEventListener('change', (event) => showPage(Number(event.target.value)));
        showPage(0);
    </script>

</body>
</html>
