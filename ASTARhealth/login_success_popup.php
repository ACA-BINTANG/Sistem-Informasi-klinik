<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$loginSuccess = $_SESSION['login_success'] ?? null;
unset($_SESSION['login_success']);

if (!is_array($loginSuccess)) {
    return;
}

$welcomeName = trim((string) ($loginSuccess['name'] ?? 'Pengguna'));
if ($welcomeName === '') {
    $welcomeName = 'Pengguna';
}

$message = 'Selamat datang, ' . $welcomeName . '!';
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'success',
        title: 'Login Berhasil',
        text: <?= json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        confirmButtonText: 'Lanjutkan',
        confirmButtonColor: '#175cdd',
        timer: 2500,
        timerProgressBar: true
    });
});
</script>
