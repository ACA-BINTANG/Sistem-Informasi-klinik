<?php
require_once "koneksi.php";

// Ambil ID dari URL
$id_rujukan = $_GET["id"];

// Query yang sudah disesuaikan dengan kolom di database kamu
// Kita hapus p.tgl_lahir, p.jk, dan s.npa_idi karena menyebabkan error
$query = mysqli_query(
    $conn,
    "SELECT r.*, p.nama_pasien, p.no_identitas, s.nama_lengkap as nama_dokter 
                              FROM rujukan r
                              JOIN pasienm p ON r.id_pasien = p.id_pasien
                              JOIN staffm s ON r.id_staff = s.id_staff
                              WHERE r.id_rujukan = '$id_rujukan'",
);

$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Data rujukan tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Rujukan - <?= $data["id_rujukan"] ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; line-height: 1.6; color: #000; padding: 40px; }
        .kop-surat { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat h2 { margin: 0; text-transform: uppercase; }
        .judul-surat { text-align: center; text-decoration: underline; font-weight: bold; font-size: 18px; margin-bottom: 25px; }
        .info-pasien table { width: 100%; margin-bottom: 20px; }
        .label { width: 200px; font-weight: bold; }
        .hasil-box { border: 1px solid #000; padding: 15px; margin-top: 10px; min-height: 100px; }
        .footer { margin-top: 50px; float: right; width: 250px; text-align: center; }
        .tanda-tangan { margin-top: 70px; font-weight: bold; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()">Cetak Surat</button>
    </div>

    <div class="kop-surat">
        <h2>ASTAR HEALTH CLINIC</h2>
        <p>Kawasan Industri Astra, Jakarta</p>
        <p>Telp: (021) 1234567 | Email: UKKastar@gmail.com</p>
    </div>

    <div class="judul-surat">SURAT RUJUKAN MEDIS</div>

    <p style="text-align: right;">Jakarta, <?= date(
        "d F Y",
        strtotime($data["tgl_rujukan"]),
    ) ?></p>

    <p>Kepada Yth,<br><b>TS. Dokter di <?= $data["tujuan_rs"] ?></b></p>

    <p>Mohon pemeriksaan dan penanganan lebih lanjut terhadap pasien berikut:</p>
    
    <div class="info-pasien">
        <table>
            <tr><td class="label">Nama Pasien</td><td>: <?= $data[
                "nama_pasien"
            ] ?></td></tr>
            <tr><td class="label">NIM / NIP</td><td>: <?= $data[
                "no_identitas"
            ] ?></td></tr>
            <tr><td class="label">Alasan Rujukan</td><td>: <?= $data[
                "alasan_rujukan"
            ] ?></td></tr>
        </table>
    </div>

    <p><b>Hasil Pemeriksaan Sementara / Diagnosa:</b></p>
    <div class="hasil-box">
        <?= nl2br($data["alasan_rujukan"]) ?>
    </div>

    <p>Demikian surat rujukan ini kami buat, atas kerjasamanya diucapkan terima kasih.</p>

    <div class="footer">
        <p>Hormat Kami,</p>
        <div class="tanda-tangan">
            <u>dr. <?= $data["nama_dokter"] ?></u><br>
            <span>Dokter Pemeriksa</span>
        </div>
    </div>

</body>
</html>