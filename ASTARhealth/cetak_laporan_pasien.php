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

$search = mysqli_real_escape_string($conn, $_GET["search"] ?? "");
$kategori = mysqli_real_escape_string($conn, $_GET["kategori"] ?? "");

$where = [];

if ($search != "") {
    $where[] = "(
        p.nama_pasien LIKE '%$search%'
        OR p.no_identitas LIKE '%$search%'
        OR p.id_pasien LIKE '%$search%'
    )";
}

if ($kategori != "") {
    $where[] = "p.kategori_pasien='$kategori'";
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$q = mysqli_query(
    $conn,
    " 
    SELECT
        p.id_pasien,
        p.no_identitas,
        p.nama_pasien,
        p.jenis_kelamin,
        p.kategori_pasien,
        p.unit_prodi,
        p.alamat,
        COUNT(rm.id_rekam_medis) AS total_kunjungan,
        MAX(rm.tgl_kunjungan) AS kunjungan_terakhir
    FROM pasienm p
    LEFT JOIN rekam_medis rm ON p.id_pasien=rm.id_pasien
    $where_sql
    GROUP BY
        p.id_pasien,
        p.no_identitas,
        p.nama_pasien,
        p.jenis_kelamin,
        p.kategori_pasien,
        p.unit_prodi,
        p.alamat
    ORDER BY total_kunjungan DESC, p.nama_pasien ASC
",
);

if (!$q) {
    die("Query laporan pasien error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Laporan Data Pasien</title>
<style>
body{font-family:'Times New Roman',serif;margin:45px;color:#111}
.kop{text-align:left;border-bottom:4px double #000;padding-bottom:15px}
.kop img{height:80px}
.kop p{margin:3px}
.meta{margin-top:25px;line-height:1.6}
h2{text-align:center;text-transform:uppercase}
table{width:100%;border-collapse:collapse;margin-top:25px;font-size:13px}
th,td{border:1px solid #000;padding:7px}
th{text-align:center;background:#eee}
.center{text-align:center}
.ttd{text-align:right;margin-top:60px}
button{padding:10px 20px;margin-bottom:20px}
@media print{button{display:none}}
</style>
</head>
<body>

<button onclick="window.print()">Cetak / Simpan PDF</button>

<div class="kop">
<img src="assets/img/logoA.png">
<p>Politeknik Astar - Kawasan Industri Delta Silicon, Cikarang</p>
<p>Telp: +62 0123-0123-123 | Email: health@polytechnic.astar.ac.id</p>
</div>

<h2>Laporan Data Pasien</h2>

<div class="meta">
<b>Nama Klinik:</b> Klinik ASTARhealth<br>
<b>Tanggal Cetak:</b> <?= date("d F Y") ?> <br>
<b>Sumber Data:</b> Database Pasien dan Rekam Medis ASTARhealth
</div>

<table>
<tr>
<th>No</th>
<th>ID Pasien</th>
<th>NIM/NIP</th>
<th>Nama Pasien</th>
<th>Jenis Kelamin</th>
<th>Kategori</th>
<th>Unit</th>
<th>Total Kunjungan</th>
<th>Kunjungan Terakhir</th>
</tr>
<?php
$no = 1;
while ($r = mysqli_fetch_assoc($q)) { ?>
<tr>
<td class="center"><?= $no++ ?></td>
<td><?= $r["id_pasien"] ?></td>
<td><?= $r["no_identitas"] ?></td>
<td><?= $r["nama_pasien"] ?></td>
<td class="center"><?= $r["jenis_kelamin"] == "L"
    ? "Laki-laki"
    : "Perempuan" ?></td>
<td><?= $r["kategori_pasien"] ?></td>
<td><?= $r["unit_prodi"] ?></td>
<td class="center"><?= $r["total_kunjungan"] ?></td>
<td><?= $r["kunjungan_terakhir"] ?? "-" ?></td>
</tr>
<?php }
?>
</table>

<div class="ttd">
Cikarang, <?= date("d F Y") ?>
<br><br><br>
<b>Penanggung Jawab Klinik</b>
<br><br><br>
(____________________)<br>
dr. Ike Indahwati
</div>

</body>
</html>
