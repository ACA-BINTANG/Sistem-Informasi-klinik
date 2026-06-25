<?php
$conn = mysqli_connect("localhost", "root", "", "astarhealth_db"); // HARUS R bukan RA
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>