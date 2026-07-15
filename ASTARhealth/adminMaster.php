<?php
// Kompatibilitas URL lama. Halaman utama Admin sekarang berada di folder role.
$query = $_SERVER["QUERY_STRING"] ?? "";
header("Location: admin/index.php" . ($query !== "" ? "?" . $query : ""));
exit;
