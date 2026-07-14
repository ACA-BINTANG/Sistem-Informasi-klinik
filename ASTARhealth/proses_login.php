<?php
session_start();
require_once 'koneksi.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function redirectLogin(array $errors, string $title, string $text, string $icon = 'warning'): void
{
    $_SESSION['login_errors'] = $errors;
    $_SESSION['swal'] = [
        'icon' => $icon,
        'title' => $title,
        'text' => $text,
    ];

    header('Location: login.php');
    exit;
}

function finishLogin(array $user, string $target): void
{
    session_regenerate_id(true);

    $_SESSION['id_user'] = (string) $user['id_user'];
    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['nama_lengkap'] = (string) ($user['nama_lengkap'] ?? '');
    $_SESSION['role'] = (string) $user['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['login_success'] = [
        'name' => (string) ($user['nama_lengkap'] ?? $user['username'] ?? 'Pengguna'),
        'role' => (string) ($user['role'] ?? ''),
    ];

    unset($_SESSION['login_errors'], $_SESSION['old_login']);
    header('Location: ' . $target);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectLogin([], 'Akses tidak valid', 'Silakan masuk melalui formulir login.', 'error');
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
if ($sessionToken === '' || $csrfToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    redirectLogin([], 'Sesi formulir tidak valid', 'Muat ulang halaman login, lalu coba kembali.', 'error');
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$_SESSION['old_login'] = [
    'username' => $username,
    'password' => $password,
];

$errors = [];
if ($username === '') {
    $errors['username'] = 'Username wajib diisi. Masukkan NIM, NIP, email, atau username akun Anda.';
} elseif (strlen($username) > 100) {
    $errors['username'] = 'Username terlalu panjang. Kurangi hingga maksimal 100 karakter.';
}

if ($password === '') {
    $errors['password'] = 'Kata sandi wajib diisi. Masukkan kata sandi akun Anda.';
} elseif (strlen($password) > 72) {
    $errors['password'] = 'Kata sandi terlalu panjang. Kurangi hingga maksimal 72 karakter.';
}

if ($errors !== []) {
    if (count($errors) === 1) {
        $field = array_key_first($errors);
        $label = $field === 'username' ? 'Username' : 'Kata Sandi';
        redirectLogin($errors, 'Periksa ' . $label, (string) reset($errors));
    }

    redirectLogin(
        $errors,
        'Ada beberapa input yang salah',
        'Kolom yang bermasalah sudah diberi border merah. Periksa kembali username dan kata sandi Anda.'
    );
}

try {
    $statement = $conn->prepare(
        'SELECT id_user, username, email, password, role, nama_lengkap
         FROM userm
         WHERE username = ? OR email = ?
         LIMIT 1'
    );
    $statement->bind_param('ss', $username, $username);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$user) {
        redirectLogin(
            ['username' => 'Data login tidak sesuai.', 'password' => 'Data login tidak sesuai.'],
            'Login gagal',
            'Username atau kata sandi tidak sesuai. Periksa kembali data login Anda.',
            'error'
        );
    }

    $storedPassword = (string) $user['password'];
    $passwordInfo = password_get_info($storedPassword);
    $isOldHash = !empty($passwordInfo['algo']);

    // Password baru disimpan sebagai teks biasa sesuai kebutuhan project.
    // Dukungan hash lama dipertahankan sementara agar database lama tetap bisa login.
    $passwordValid = $isOldHash
        ? password_verify($password, $storedPassword)
        : hash_equals($storedPassword, $password);

    if (!$passwordValid) {
        redirectLogin(
            ['username' => 'Data login tidak sesuai.', 'password' => 'Data login tidak sesuai.'],
            'Login gagal',
            'Username atau kata sandi tidak sesuai. Periksa kembali data login Anda.',
            'error'
        );
    }

    // Jika akun lama masih berbentuk hash, setelah login berhasil ubah kembali
    // menjadi password teks biasa yang baru saja dimasukkan pengguna.
    if ($isOldHash) {
        $updatePassword = $conn->prepare('UPDATE userm SET password = ? WHERE id_user = ?');
        $updatePassword->bind_param('ss', $password, $user['id_user']);
        $updatePassword->execute();
        $updatePassword->close();
        $user['password'] = $password;
    }

    $targets = [
        'Admin' => 'adminMaster.php',
        'Dokter' => 'dashboard_dokter.php',
        'Pasien' => 'dashboard_pasien.php',
        'K3' => 'index.php',
        'Vendor' => 'index.php',
    ];

    $role = (string) $user['role'];
    if (!isset($targets[$role])) {
        redirectLogin([], 'Role tidak dikenali', 'Hubungi administrator untuk memperbaiki role akun.', 'error');
    }

    finishLogin($user, $targets[$role]);
} catch (mysqli_sql_exception $exception) {
    error_log('Login ASTARhealth gagal: ' . $exception->getMessage());
    redirectLogin([], 'Login tidak dapat diproses', 'Terjadi gangguan pada database. Silakan coba kembali.', 'error');
} catch (Throwable $exception) {
    error_log('Login ASTARhealth gagal: ' . $exception->getMessage());
    redirectLogin([], 'Login tidak dapat diproses', 'Sistem tidak dapat memproses login saat ini.', 'error');
}
