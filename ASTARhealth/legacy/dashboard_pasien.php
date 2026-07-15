<?php
// Kompatibilitas URL lama. Halaman utama Pasien sekarang berada di folder role.
$query = $_SERVER["QUERY_STRING"] ?? "";
header("Location: ../pasien/index.php" . ($query !== "" ? "?" . $query : ""));
exit;
