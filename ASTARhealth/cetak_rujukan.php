<?php
session_start();
require_once "koneksi.php";

// Proteksi: Hanya dokter yang bisa akses
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Dokter") {
    die("Akses Ditolak!");
}

$id = mysqli_real_escape_string($conn, $_GET["id"] ?? "");

// Ambil data lengkap rujukan, pasien, dan dokter (staff)
$stmt = mysqli_prepare(
    $conn,
    "
    SELECT r.*, p.nama_pasien, p.no_identitas, p.jenis_kelamin, p.unit_prodi, p.alamat,
           s.nama_lengkap AS nama_dokter, s.npa_idi
    FROM rujukan r
    JOIN pasienm p ON r.id_pasien = p.id_pasien
    JOIN staffm s ON r.id_staff = s.id_staff
    WHERE r.id_rujukan = ?
",
);
mysqli_stmt_bind_param($stmt, "s", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    die("Data rujukan tidak ditemukan!");
}

function e($text)
{
    return htmlspecialchars($text ?? "", ENT_QUOTES, "UTF-8");
}

// Nomor surat formal: urutan/ID/klinik/bulan-romawi/tahun
$bulanRomawi = [
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
$tglRujukan = strtotime($data["tgl_rujukan"]);
$nomorSurat =
    e($data["id_rujukan"]) .
    "/RJK-ASTARhealth/" .
    $bulanRomawi[(int) date("n", $tglRujukan)] .
    "/" .
    date("Y", $tglRujukan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Rujukan - <?= e($data["id_rujukan"]) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.6;
            padding: 40px 50px;
            color: #1a1a1a;
            max-width: 850px;
            margin: 0 auto;
        }

.kop-surat {
    display: flex;
    flex-direction: column;   /* tadinya row (default) */
    align-items: left;      /* atau flex-start kalau mau rata kiri */
    gap: 6px;                 /* jaraknya bisa dikecilin dari 18px */
    border-bottom: 4px double #000;
    padding-bottom: 14px;
    margin-bottom: 22px;
}
        .kop-logo {
            height: 90px;
            flex-shrink: 0;
            display: flex; align-items: center;
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
        .meta-surat div { line-height: 1.5; }

        .judul {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .judul-underline {
            width: 260px;
            border-bottom: 2px solid #000;
            margin: 0 auto 24px;
        }

        .isi-surat { margin-bottom: 16px; text-align: justify; }

        .tabel-data {
            width: 100%;
            margin: 14px 0 20px;
            border-collapse: collapse;
            font-size: 15px;
        }
        .tabel-data td { padding: 4px 0; vertical-align: top; }
        .tabel-data .label { width: 170px; }
        .tabel-data .titik-dua { width: 14px; }

        .box-content {
            border: 1px solid #999;
            border-radius: 4px;
            padding: 12px 14px;
            margin-bottom: 18px;
            background: #fafafa;
            font-size: 14.5px;
            min-height: 24px;
        }

        .keterangan-izin {
            border: 1px solid #0057B8;
            border-radius: 4px;
            padding: 14px 16px;
            margin: 20px 0;
            background: #f4f8ff;
            font-size: 14.5px;
        }
        .keterangan-izin b { color: #0057B8; }

        .penutup { margin-top: 10px; margin-bottom: 40px; }

        .ttd-wrapper {
            display: flex;
            justify-content: flex-end;
        }
        .ttd-block {
            width: 280px;
            text-align: center;
            font-size: 14.5px;
        }
        .ttd-tempat-tgl { margin-bottom: 6px; }
        .ttd-jabatan { margin-bottom: 4px; }
        .ttd-space {
            height: 90px;
            position: relative;
        }
        .ttd-cap {
            position: absolute;
            left: 8px;
            top: 6px;
            width: 78px;
            height: 78px;
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
            text-decoration: underline;
            margin-bottom: 2px;
        }
        .ttd-npa { font-size: 13px; }

        .footer-doc {
            margin-top: 36px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 11px;
            color: #777;
            display: flex;
            justify-content: space-between;
        }

        /* CSS agar tombol tidak ikut tercetak */
        @media print {
            .no-print { display: none; }
            body { padding: 0 30px; max-width: 100%; }
        }
    </style>
</head>
<body>

    <!-- Tombol Cetak (tidak ikut ter-print) -->
    <div class="no-print" style="margin-bottom: 24px; display:flex; gap:10px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #0057B8; color: white; border: none; border-radius: 8px; font-weight:600;">
            🖨️ Cetak / Simpan sebagai PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; background: #64748b; color: white; border: none; border-radius: 8px; font-weight:600;">
            Tutup Halaman
        </button>
    </div>

    <div class="kop-surat">
        <div class="kop-logo"><img src="assets/img/logoA.png" alt="ASTARhealth"></div>
        <div class="kop-text">
            <p>Politeknik Astar &mdash; Kawasan Industri Delta Silicon, Cikarang</p>
            <p>Telp: +62 0123-0123-123 &nbsp;|&nbsp; Email: health@polytechnic.astar.ac.id</p>
        </div>
    </div>

    <div class="meta-surat">
        <div>
            Nomor&nbsp;: <?= $nomorSurat ?><br>
            Lampiran&nbsp;: -<br>
            Perihal&nbsp;: <b>Rujukan &amp; Keterangan Izin Berobat</b>
        </div>
        <div style="text-align:right;">
            Cikarang, <?= date("d F Y", $tglRujukan) ?>
        </div>
    </div>

    <div class="judul">Surat Rujukan &amp; Keterangan Sakit</div>
    <div class="judul-underline"></div>

    <div class="isi-surat">
        Kepada Yth,<br>
        <b>Sejawat Dokter di <?= e($data["tujuan_rs"]) ?></b><br>
        Di Tempat
    </div>

    <div class="isi-surat">
        Bersama ini kami sampaikan rujukan pasien dengan keterangan sebagai berikut:
    </div>

    <table class="tabel-data">
        <tr>
            <td class="label">Nama Pasien</td>
            <td class="titik-dua">:</td>
            <td><b><?= e($data["nama_pasien"]) ?></b></td>
        </tr>
        <tr>
            <td class="label">NIM / NIP</td>
            <td class="titik-dua">:</td>
            <td><?= e($data["no_identitas"]) ?></td>
        </tr>
        <tr>
            <td class="label">Jenis Kelamin</td>
            <td class="titik-dua">:</td>
            <td><?= $data["jenis_kelamin"] == "L"
                ? "Laki-laki"
                : "Perempuan" ?></td>
        </tr>
        <tr>
            <td class="label">Unit / Program Studi</td>
            <td class="titik-dua">:</td>
            <td><?= e($data["unit_prodi"]) ?></td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td class="titik-dua">:</td>
            <td><?= e($data["alamat"]) ?></td>
        </tr>
        <tr>
            <td class="label">Tanggal Pemeriksaan</td>
            <td class="titik-dua">:</td>
            <td><?= date("d F Y", $tglRujukan) ?></td>
        </tr>
    </table>

    <div class="isi-surat">
        <b>Anamnesa &amp; Alasan Rujukan:</b>
        <div class="box-content"><?= nl2br(e($data["alasan_rujukan"])) ?></div>
    </div>

    <div class="isi-surat">
        <b>Hasil Pemeriksaan Sementara:</b>
        <div class="box-content"><?= nl2br(e($data["hasil_rujukan"])) ?></div>
    </div>

    <div class="keterangan-izin">
        <b>Keterangan Izin:</b> Berdasarkan hasil pemeriksaan di atas, yang bersangkutan
        <b><?= e(
            $data["nama_pasien"],
        ) ?></b> dinyatakan memerlukan penanganan/rujukan lebih
        lanjut, sehingga <b>diperkenankan untuk tidak mengikuti kegiatan perkuliahan/pekerjaan</b>
        pada tanggal <b><?= date(
            "d F Y",
            $tglRujukan,
        ) ?></b> guna keperluan pemeriksaan dan
        pengobatan tersebut di atas. Surat ini dapat digunakan sebagai bukti penunjang yang sah
        kepada pihak akademik/instansi terkait.
    </div>

    <div class="isi-surat penutup">
        Demikian surat rujukan dan keterangan ini kami buat dengan sebenarnya untuk penanganan
        lebih lanjut. Atas perhatian dan kerja samanya kami ucapkan terima kasih.
    </div>

    <div class="ttd-wrapper">
        <div class="ttd-block">
            <div class="ttd-tempat-tgl">Cikarang, <?= date(
                "d F Y",
                $tglRujukan,
            ) ?></div>
            <div class="ttd-jabatan">Dokter Pemeriksa,</div>
            <div class="ttd-space">
                <div class="ttd-cap">CAP &amp;<br>TANDA TANGAN<br>DOKTER</div>
            </div>
            <div class="ttd-nama"><?= e($data["nama_dokter"]) ?></div>
            <div class="ttd-npa">NPA IDI: <?= e(
                $data["npa_idi"] ?: "-",
            ) ?></div>
        </div>
    </div>

    <div class="footer-doc">
        <span>No. Rujukan: <?= e($data["id_rujukan"]) ?></span>
        <span>Dicetak melalui Sistem ASTARhealth</span>
    </div>

</body>
</html>
