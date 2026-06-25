<?php
session_start();

// Menghapus semua data session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Menghancurkan session
session_destroy();

// Alihkan menggunakan Absolute Path agar sinkron dengan file dashboard
// JIKA PROJECT KAMU DI DALAM FOLDER (misal: localhost/siakad/), UBAH MENJADI: header('Location: /siakad/login.php');
header('Location: klinik-login/login.php');
exit;
?>