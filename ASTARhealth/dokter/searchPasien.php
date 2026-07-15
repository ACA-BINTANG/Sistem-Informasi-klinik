<?php
// Kompatibilitas untuk JavaScript lama yang masih tersimpan pada cache browser.
// Proses pencarian sebenarnya tetap berada di dokter/index.php.
$_GET["ajax"] = "search_pasien_rujukan";
require __DIR__ . "/index.php";
