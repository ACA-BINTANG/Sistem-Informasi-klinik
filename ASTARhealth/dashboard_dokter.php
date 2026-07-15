<?php
// Kompatibilitas URL lama. Halaman utama Dokter sekarang berada di folder role.
$query = $_SERVER["QUERY_STRING"] ?? "";
header("Location: dokter/index.php" . ($query !== "" ? "?" . $query : ""));
exit;
