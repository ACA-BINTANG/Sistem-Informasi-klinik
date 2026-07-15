<?php
require_once dirname(__DIR__) . '/config/koneksi.php';
if (isset($_GET["keyword"])) {
    $keyword = mysqli_real_escape_string($conn, $_GET["keyword"]);
    $q = mysqli_query(
        $conn,
        "SELECT id_pasien, no_identitas, nama_pasien FROM pasienm 
                             WHERE no_identitas LIKE '%$keyword%' OR nama_pasien LIKE '%$keyword%' LIMIT 5",
    );

    while ($r = mysqli_fetch_assoc($q)) {
        $id = $r["id_pasien"];
        $nm = addslashes($r["nama_pasien"]);
        $ni = $r["no_identitas"];
        echo "<div class='search-item' onclick=\"pilihPasien('$id', '$nm', '$ni')\">
                <b>$ni</b> - $nm
              </div>";
    }
}
