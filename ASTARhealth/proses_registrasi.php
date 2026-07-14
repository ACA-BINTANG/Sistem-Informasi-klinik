<?php
session_start();
require_once 'koneksi.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function redirectWithAlert(string $icon, string $title, string $text, string $target = 'registrasi.php'): void
{
    $_SESSION['swal'] = [
        'icon' => $icon,
        'title' => $title,
        'text' => $text,
    ];

    header('Location: ' . $target);
    exit;
}

function redirectWithValidation(array $errors): void
{
    $_SESSION['register_errors'] = $errors;

    $fieldLabels = [
        'username' => 'Username',
        'email' => 'Email',
        'password' => 'Kata Sandi',
        'nama' => 'Nama Lengkap',
        'jk' => 'Jenis Kelamin',
        'kategori' => 'Kategori Pasien',
        'identitas' => 'Nomor Identitas',
        'no_hp' => 'Nomor WhatsApp',
        'alamat' => 'Alamat',
    ];

    if (count($errors) === 1) {
        $field = array_key_first($errors);
        $alertTitle = 'Periksa ' . ($fieldLabels[$field] ?? 'inputan');
        $alertText = (string) reset($errors);
    } else {
        $alertTitle = 'Ada beberapa input yang salah';
        $alertText = 'Kolom yang bermasalah sudah diberi border merah. Periksa petunjuk pada placeholder setiap kolom, lalu kirim kembali formulir.';
    }

    $_SESSION['swal'] = [
        'icon' => 'warning',
        'title' => $alertTitle,
        'text' => $alertText,
    ];

    header('Location: registrasi.php');
    exit;
}

function generateUniqueId(mysqli $conn, string $table, string $column, string $prefix): string
{
    $allowed = [
        'userm.id_user' => true,
        'pasienm.id_pasien' => true,
    ];

    if (!isset($allowed[$table . '.' . $column])) {
        throw new InvalidArgumentException('Target ID tidak valid.');
    }

    $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    for ($attempt = 0; $attempt < 100; $attempt++) {
        $id = $prefix;
        for ($i = 0; $i < 5; $i++) {
            $id .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $query = "SELECT 1 FROM {$table} WHERE {$column} = ? LIMIT 1";
        $check = $conn->prepare($query);
        $check->bind_param('s', $id);
        $check->execute();
        $exists = $check->get_result()->fetch_row();
        $check->close();

        if (!$exists) {
            return $id;
        }
    }

    throw new RuntimeException('Tidak dapat membuat ID unik.');
}

function textLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithAlert('warning', 'Akses tidak valid', 'Silakan isi formulir registrasi terlebih dahulu.');
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
if ($sessionToken === '' || $csrfToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    redirectWithAlert('error', 'Sesi formulir tidak valid', 'Muat ulang halaman registrasi, lalu coba kembali.');
}

$username = trim((string) ($_POST['username'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$nama = trim((string) ($_POST['nama'] ?? ''));
$jenisKelamin = trim((string) ($_POST['jk'] ?? ''));
$kategori = trim((string) ($_POST['kategori'] ?? ''));
if ($kategori === 'Lainnya') {
    // Kompatibilitas dengan draft/form versi lama: digabung ke kategori Tamu.
    $kategori = 'Tamu';
}
$identitas = trim((string) ($_POST['identitas'] ?? ''));
$alamat = trim((string) ($_POST['alamat'] ?? ''));
$noHpInput = trim((string) ($_POST['no_hp'] ?? ''));
$noHpDigits = preg_replace('/\D+/', '', $noHpInput) ?? '';
$noHpNational = ltrim($noHpDigits, '0');
$noHp = $noHpNational !== '' ? '+62' . $noHpNational : '';

$_SESSION['old_register'] = [
    'username' => $username,
    'email' => $email,
    'nama' => $nama,
    'jk' => $jenisKelamin,
    'kategori' => $kategori,
    'identitas' => $identitas,
    'alamat' => $alamat,
    'no_hp' => $noHpInput,
];

$errors = [];

if ($username === '') {
    $errors['username'] = 'Username wajib diisi. Isi minimal 3 karakter menggunakan huruf, angka, titik, garis bawah, atau tanda minus.';
} elseif (textLength($username) < 3) {
    $errors['username'] = 'Username terlalu pendek. Tambahkan hingga minimal 3 karakter.';
} elseif (textLength($username) > 50) {
    $errors['username'] = 'Username terlalu panjang. Kurangi hingga maksimal 50 karakter.';
} elseif (!preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
    $errors['username'] = 'Username mengandung karakter yang tidak diperbolehkan. Gunakan hanya huruf, angka, titik, garis bawah, atau tanda minus.';
} elseif (strcasecmp($username, 'admin') === 0) {
    $errors['username'] = 'Username "admin" khusus akun administrator. Gunakan username lain.';
}

if ($email === '') {
    $errors['email'] = 'Email wajib diisi. Contoh format yang benar: nama@email.com.';
} elseif (textLength($email) > 100) {
    $errors['email'] = 'Email terlalu panjang. Kurangi hingga maksimal 100 karakter.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Format email belum benar. Gunakan format seperti nama@email.com.';
}

if ($password === '') {
    $errors['password'] = 'Kata sandi wajib diisi. Gunakan minimal 8 karakter yang berisi huruf besar, huruf kecil, dan angka.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Kata sandi terlalu pendek. Tambahkan hingga minimal 8 karakter.';
} elseif (strlen($password) > 72) {
    $errors['password'] = 'Kata sandi terlalu panjang. Kurangi hingga maksimal 72 karakter.';
} elseif (!preg_match('/[A-Z]/', $password)) {
    $errors['password'] = 'Kata sandi belum memiliki huruf besar. Tambahkan minimal satu huruf kapital, misalnya A.';
} elseif (!preg_match('/[a-z]/', $password)) {
    $errors['password'] = 'Kata sandi belum memiliki huruf kecil. Tambahkan minimal satu huruf kecil, misalnya a.';
} elseif (!preg_match('/\d/', $password)) {
    $errors['password'] = 'Kata sandi belum memiliki angka. Tambahkan minimal satu angka, misalnya 1.';
}

if ($nama === '') {
    $errors['nama'] = 'Nama lengkap wajib diisi. Masukkan nama sesuai identitas.';
} elseif (textLength($nama) < 3) {
    $errors['nama'] = 'Nama lengkap terlalu pendek. Masukkan minimal 3 huruf.';
} elseif (textLength($nama) > 100) {
    $errors['nama'] = 'Nama lengkap terlalu panjang. Kurangi hingga maksimal 100 karakter.';
} elseif (!preg_match("/^[\\p{L} .,'-]+$/u", $nama)) {
    $errors['nama'] = 'Nama mengandung angka atau simbol yang tidak diperbolehkan. Gunakan huruf dan tanda baca nama yang wajar.';
}

if (!in_array($jenisKelamin, ['L', 'P'], true)) {
    $errors['jk'] = 'Jenis kelamin belum dipilih. Pilih Laki-laki atau Perempuan.';
}

if (!in_array($kategori, ['Sigap', 'Virtus', 'Tamu'], true)) {
    $errors['kategori'] = 'Kategori pasien belum dipilih. Pilih salah satu kategori yang tersedia.';
}

if ($identitas === '') {
    $errors['identitas'] = 'Nomor identitas wajib diisi. Masukkan angka sesuai NIP atau NIK.';
} elseif (!preg_match('/^\d+$/', $identitas)) {
    $errors['identitas'] = 'Nomor identitas hanya boleh berisi angka. Hapus huruf, spasi, titik, garis, atau simbol lainnya.';
} elseif ($kategori === 'Tamu' && strlen($identitas) !== 16) {
    $errors['identitas'] = 'NIK untuk kategori Tamu Umum / Lain-lain harus tepat 16 angka.';
} elseif ($kategori !== 'Tamu' && (strlen($identitas) < 3 || strlen($identitas) > 30)) {
    $errors['identitas'] = 'NIP harus berisi minimal 3 dan maksimal 30 angka.';
}

if ($noHpNational === '') {
    $errors['no_hp'] = 'Nomor WhatsApp wajib diisi. Ketik nomor setelah +62, dimulai angka 8.';
} elseif (!preg_match('/^8\d{8,12}$/', $noHpNational)) {
    $errors['no_hp'] = 'Nomor WhatsApp belum benar. Setelah +62, nomor harus dimulai angka 8 dan berisi 9–13 angka.';
}

if ($alamat === '') {
    $errors['alamat'] = 'Alamat wajib diisi. Masukkan alamat tinggal saat ini.';
} elseif (textLength($alamat) < 5) {
    $errors['alamat'] = 'Alamat terlalu pendek. Tambahkan hingga minimal 5 karakter.';
} elseif (textLength($alamat) > 255) {
    $errors['alamat'] = 'Alamat terlalu panjang. Kurangi hingga maksimal 255 karakter.';
}

if ($errors !== []) {
    redirectWithValidation($errors);
}

try {
    $checkUser = $conn->prepare(
        'SELECT username, email FROM userm WHERE username = ? OR email = ? LIMIT 1'
    );
    $checkUser->bind_param('ss', $username, $email);
    $checkUser->execute();
    $existingUser = $checkUser->get_result()->fetch_assoc();
    $checkUser->close();

    if ($existingUser) {
        if (strcasecmp((string) $existingUser['username'], $username) === 0) {
            $errors['username'] = 'Username sudah digunakan oleh akun lain. Ganti dengan username yang berbeda.';
        }

        if (strcasecmp((string) $existingUser['email'], $email) === 0) {
            $errors['email'] = 'Email sudah terdaftar. Gunakan email lain atau masuk memakai akun yang sudah ada.';
        }
    }

    $checkIdentity = $conn->prepare(
        'SELECT id_pasien FROM pasienm WHERE no_identitas = ? LIMIT 1'
    );
    $checkIdentity->bind_param('s', $identitas);
    $checkIdentity->execute();
    $identityExists = $checkIdentity->get_result()->fetch_assoc();
    $checkIdentity->close();

    if ($identityExists) {
        $errors['identitas'] = 'Nomor identitas sudah terdaftar. Periksa kembali nomornya atau gunakan akun yang sudah ada.';
    }

    if ($errors !== []) {
        redirectWithValidation($errors);
    }

    $idUser = generateUniqueId($conn, 'userm', 'id_user', 'U');
    $idPasien = generateUniqueId($conn, 'pasienm', 'id_pasien', 'P');
    $role = 'Pasien';

    $conn->begin_transaction();

    $insertUser = $conn->prepare(
        'INSERT INTO userm (id_user, username, email, password, role, nama_lengkap)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertUser->bind_param(
        'ssssss',
        $idUser,
        $username,
        $email,
        $password,
        $role,
        $nama
    );
    $insertUser->execute();
    $insertUser->close();

    $insertPatient = $conn->prepare(
        'INSERT INTO pasienm
         (id_pasien, id_user, no_identitas, nama_pasien, jenis_kelamin, kategori_pasien, alamat, no_hp)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insertPatient->bind_param(
        'ssssssss',
        $idPasien,
        $idUser,
        $identitas,
        $nama,
        $jenisKelamin,
        $kategori,
        $alamat,
        $noHp
    );
    $insertPatient->execute();
    $insertPatient->close();

    $conn->commit();

    unset($_SESSION['old_register'], $_SESSION['register_errors']);
    $_SESSION['clear_register_draft'] = true;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    redirectWithAlert(
        'success',
        'Registrasi berhasil',
        'Akun pasien berhasil dibuat. Silakan masuk menggunakan username dan kata sandi Anda.',
        'login.php'
    );
} catch (mysqli_sql_exception $exception) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
        // Transaksi mungkin belum dimulai.
    }

    error_log('Registrasi ASTARhealth gagal: ' . $exception->getMessage());
    $errorCode = (int) $exception->getCode();

    if ($errorCode === 1062) {
        $message = strtolower($exception->getMessage());

        if (strpos($message, 'username') !== false || strpos($message, 'uk_userm_username') !== false) {
            $errors['username'] = 'Username sudah digunakan oleh akun lain. Ganti dengan username yang berbeda.';
        } elseif (strpos($message, 'email') !== false) {
            $errors['email'] = 'Email sudah terdaftar. Gunakan email lain atau masuk memakai akun yang sudah ada.';
        } elseif (strpos($message, 'identitas') !== false) {
            $errors['identitas'] = 'Nomor identitas sudah terdaftar. Periksa kembali nomornya atau gunakan akun yang sudah ada.';
        } else {
            $errors['username'] = 'Data tersebut sudah digunakan oleh akun lain. Ganti data yang ditandai merah.';
        }

        redirectWithValidation($errors);
    }

    redirectWithAlert(
        'error',
        'Registrasi gagal',
        'Terjadi kesalahan saat menyimpan data. Silakan periksa data dan coba kembali.'
    );
} catch (Throwable $exception) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
        // Transaksi mungkin belum dimulai.
    }

    error_log('Registrasi ASTARhealth gagal: ' . $exception->getMessage());
    redirectWithAlert(
        'error',
        'Registrasi gagal',
        'Sistem tidak dapat memproses registrasi saat ini. Silakan coba kembali.'
    );
}
