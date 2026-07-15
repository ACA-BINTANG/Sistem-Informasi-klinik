<?php
session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
}
session_destroy();
session_start();
$_SESSION['swal'] = [
    'icon' => 'success',
    'title' => 'Logout Berhasil',
    'text' => 'Anda telah keluar dari sistem.',
];
header('Location: login.php');
exit;
