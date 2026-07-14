<?php
session_start();
require_once 'koneksi.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function backWithErrors(array $errors, string $title, string $text): void
{
    $_SESSION['forgot_errors'] = $errors;
    $_SESSION['swal'] = ['icon' => 'warning', 'title' => $title, 'text' => $text];
    header('Location: lupa_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: lupa_password.php');
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
$sessionCsrf = (string)($_SESSION['csrf_token'] ?? '');
if ($csrf === '' || $sessionCsrf === '' || !hash_equals($sessionCsrf, $csrf)) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    backWithErrors([], 'Sesi tidak valid', 'Muat ulang halaman lalu coba kembali.');
}

$username = trim((string)($_POST['username'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');
$_SESSION['forgot_old'] = ['username' => $username, 'email' => $email];

$errors = [];
if ($username === '') $errors['username'] = 'Username wajib diisi.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Masukkan email terdaftar dengan format yang benar.';
if ($password === '') $errors['password'] = 'Kata sandi baru wajib diisi.';
elseif (strlen($password) < 8) $errors['password'] = 'Kata sandi baru minimal 8 karakter.';
elseif (strlen($password) > 72) $errors['password'] = 'Kata sandi maksimal 72 karakter.';
elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) $errors['password'] = 'Kata sandi harus memiliki huruf besar, huruf kecil, dan angka.';
if ($confirm !== $password) $errors['confirm_password'] = 'Ulangi kata sandi harus sama dengan kata sandi baru.';

if ($errors) {
    $first = (string)reset($errors);
    backWithErrors($errors, count($errors) === 1 ? 'Periksa Input' : 'Ada beberapa input yang salah', count($errors) === 1 ? $first : 'Kolom yang bermasalah sudah diberi border merah.');
}

try {
    $check = $conn->prepare('SELECT id_user FROM userm WHERE username = ? AND email = ? LIMIT 1');
    $check->bind_param('ss', $username, $email);
    $check->execute();
    $user = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$user) {
        backWithErrors(
            ['username' => 'Data akun tidak cocok.', 'email' => 'Data akun tidak cocok.'],
            'Akun tidak ditemukan',
            'Username dan email tidak cocok dengan data yang terdaftar.'
        );
    }

    $update = $conn->prepare('UPDATE userm SET password = ? WHERE id_user = ?');
    $update->bind_param('ss', $password, $user['id_user']);
    $update->execute();
    $update->close();

    unset($_SESSION['forgot_errors'], $_SESSION['forgot_old']);
    $_SESSION['swal'] = [
        'icon' => 'success',
        'title' => 'Kata sandi berhasil diubah',
        'text' => 'Silakan masuk menggunakan kata sandi baru Anda.'
    ];
    header('Location: login.php');
    exit;
} catch (Throwable $exception) {
    error_log('Reset password gagal: ' . $exception->getMessage());
    backWithErrors([], 'Reset gagal', 'Terjadi gangguan pada database. Silakan coba kembali.');
}
