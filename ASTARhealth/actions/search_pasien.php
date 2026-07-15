<?php
require_once dirname(__DIR__) . '/config/koneksi.php';

$keyword = $_GET['keyword'];

$q = mysqli_query($conn,"
SELECT no_identitas,nama_pasien 
FROM pasienm
WHERE no_identitas LIKE '%$keyword%'
OR nama_pasien LIKE '%$keyword%'
LIMIT 10
");

while($r=mysqli_fetch_assoc($q)){
echo "<div class='search-item' onclick=\"pilihPasien('{$r['no_identitas']}')\">
<b>{$r['no_identitas']}</b> - {$r['nama_pasien']}
</div>";
}
?>