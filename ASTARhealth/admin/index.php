<?php
session_start();
require_once dirname(__DIR__) . "/config/koneksi.php";

/** @var mysqli $conn */

// ==========================================
// PROTEKSI ROLE ADMIN
// ==========================================
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php?pesan=Akses Ditolak!");
    exit();
}

$admin_name = $_SESSION["nama_lengkap"];
$active_page = isset($_GET["page"]) ? $_GET["page"] : "dashboard";

// Pastikan database lama mendukung seluruh kategori yang dipakai halaman Admin.
@mysqli_query(
    $conn,
    "ALTER TABLE pasienm MODIFY kategori_pasien ENUM('Mahasiswa','Pegawai','Virtus','Sigap','Tamu') DEFAULT NULL"
);

// Kategori Tamu, Sigap, dan Virtus tidak menggunakan Unit / Prodi.
@mysqli_query(
    $conn,
    "UPDATE pasienm SET unit_prodi = '' WHERE kategori_pasien IN ('Tamu','Sigap','Virtus') AND COALESCE(unit_prodi, '') <> ''"
);

function generateID($prefix)
{
    return $prefix . substr(str_shuffle("0123456789"), 0, 3);
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Menyimpan nomor telepon dalam satu format konsisten: +62xxxxxxxxxxx.
 */
function normalizePhone62(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    $digits = preg_replace('/^(62|0)+/', '', $digits) ?? '';
    return $digits === '' ? '' : '+62' . $digits;
}

/**
 * Format tampilan nomor telepon: +62 812-3456-7890.
 */
function formatPhone62(?string $phone): string
{
    $normalized = normalizePhone62((string) $phone);
    if ($normalized === '') {
        return '-';
    }

    $digits = substr($normalized, 3);
    $part1 = substr($digits, 0, 3);
    $part2 = substr($digits, 3, 4);
    $part3 = substr($digits, 7);

    $formatted = $part1;
    if ($part2 !== '') {
        $formatted .= '-' . $part2;
    }
    if ($part3 !== '') {
        $formatted .= '-' . $part3;
    }

    return '+62 ' . $formatted;
}

/**
 * Nilai input tanpa prefix +62, tetap dalam pola xxx-xxxx-xxxx.
 */
function phoneInputValue(?string $phone): string
{
    $formatted = formatPhone62($phone);
    return $formatted === '-' ? '' : preg_replace('/^\+62\s*/', '', $formatted);
}

function isHashedPassword(string $password): bool
{
    $info = password_get_info($password);
    return !empty($info['algo']);
}

/**
 * Mengembalikan password akun bawaan yang sebelumnya terlanjur diubah menjadi hash.
 * Hash tidak dapat dibaca balik, jadi akun bawaan di-reset ke password awalnya.
 */
function restoreKnownPlainPasswords(mysqli $conn): void
{
    $knownPasswords = [
        '1023190013@polytechnic.astar.ac.id' => 'ike123',
        '0120240037@polytechnic.astar.ac.id' => 'dio123',
        '0920250050@polytechnic.astar.ac.id' => 'dholadolly123',
        '0420250044@polytechnic.astar.ac.id' => 'nana123',
        '20250932032@polytechnic.astar.ac.id' => 'suswanto123',
        '0120250055@polytechnic.astar.ac.id' => 'pipi123',
        '0520240028@polytechnic.astar.ac.id' => 'wowo123',
        '2023212013@polytechnic.astar.ac.id' => 'yoga123',
        '0320250021@polytechnic.astar.ac.id' => 'indah123',
    ];

    $select = mysqli_prepare($conn, 'SELECT password FROM userm WHERE username = ? LIMIT 1');
    $update = mysqli_prepare($conn, 'UPDATE userm SET password = ? WHERE username = ?');

    foreach ($knownPasswords as $username => $plainPassword) {
        mysqli_stmt_bind_param($select, 's', $username);
        mysqli_stmt_execute($select);
        $result = mysqli_stmt_get_result($select);
        $row = mysqli_fetch_assoc($result);

        if ($row && isHashedPassword((string) $row['password'])) {
            mysqli_stmt_bind_param($update, 'ss', $plainPassword, $username);
            mysqli_stmt_execute($update);
        }
    }

    mysqli_stmt_close($select);
    mysqli_stmt_close($update);

}

restoreKnownPlainPasswords($conn);


// ==========================================
// SINKRONISASI KOLOM WAKTU DATA ADMIN
// ID pada sistem dibuat secara acak (contoh USR971/USR043), sehingga ID tidak
// dapat dipakai untuk menentukan data terbaru. Kolom created_at dipakai sebagai
// sumber urutan agar data yang baru ditambahkan selalu tampil paling atas.
// ==========================================
function ensureAdminCreatedAtColumns(mysqli $conn): void
{
    $tables = ['userm', 'staffm', 'pasienm', 'supplierm'];

    foreach ($tables as $table) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE 'created_at'");
        if ($check && mysqli_num_rows($check) === 0) {
            mysqli_query(
                $conn,
                "ALTER TABLE `$table` ADD COLUMN `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)"
            );
        }
    }
}

ensureAdminCreatedAtColumns($conn);

// ==========================================
// HELPER: SINKRONISASI AKUN PENGGUNA
// Pasien dan Tim Pengelola selalu memiliki akun yang terhubung melalui id_user.
// Username dan email untuk akun terhubung selalu mengikuti NIM/NIP/identitas:
// {NIM/NIP}@polytechnic.astar.ac.id
// ==========================================
function accountFromIdentity(string $identitas): string
{
    $clean = preg_replace('/\D+/', '', $identitas) ?? '';
    return $clean === '' ? '' : $clean . '@polytechnic.astar.ac.id';
}

function getUserIdFrom(mysqli $conn, string $table, string $keyCol, string $keyVal): ?string
{
    $allowed = [
        'staffm' => ['id_staff'],
        'pasienm' => ['id_pasien'],
    ];
    if (!isset($allowed[$table]) || !in_array($keyCol, $allowed[$table], true)) {
        return null;
    }

    $stmt = mysqli_prepare($conn, "SELECT id_user FROM `$table` WHERE `$keyCol` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $keyVal);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row && !empty($row['id_user']) ? (string) $row['id_user'] : null;
}

function linkedAccountExists(mysqli $conn, string $account, string $excludeUserId = ''): bool
{
    if ($account === '') {
        return false;
    }
    if ($excludeUserId !== '') {
        $stmt = mysqli_prepare($conn, 'SELECT 1 FROM userm WHERE (username = ? OR email = ?) AND id_user <> ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'sss', $account, $account, $excludeUserId);
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT 1 FROM userm WHERE username = ? OR email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ss', $account, $account);
    }
    mysqli_stmt_execute($stmt);
    $exists = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);
    return $exists;
}

function syncToUser(mysqli $conn, string $idUser, string $namaLengkap, string $identitas, ?string $forcedRole = null): bool
{
    // Username dan email dibuat otomatis dari NIM/NIP saat akun pertama kali dibuat,
    // tetapi setelah itu keduanya boleh diedit manual dari halaman Akun Pengguna.
    // Sinkronisasi profil hanya menjaga nama dan role agar perubahan manual akun tidak tertimpa.
    if ($forcedRole !== null) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE userm SET nama_lengkap = ?, role = ? WHERE id_user = ?'
        );
        mysqli_stmt_bind_param($stmt, 'sss', $namaLengkap, $forcedRole, $idUser);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE userm SET nama_lengkap = ? WHERE id_user = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ss', $namaLengkap, $idUser);
    }

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

/**
 * Merapikan data lama yang sudah punya relasi id_user tetapi username/email-nya
 * belum mengikuti template akun institusi. Konflik duplikat dilewati agar data
 * tidak rusak.
 */
function reconcileLinkedAccounts(mysqli $conn): void
{
    $patientRows = mysqli_query(
        $conn,
        "SELECT id_user, no_identitas, nama_pasien FROM pasienm WHERE id_user IS NOT NULL AND id_user <> ''"
    );
    while ($patientRows && ($row = mysqli_fetch_assoc($patientRows))) {
        syncToUser(
            $conn,
            (string) $row['id_user'],
            (string) ($row['nama_pasien'] ?? ''),
            (string) ($row['no_identitas'] ?? ''),
            'Pasien'
        );
    }

    $staffRows = mysqli_query(
        $conn,
        "SELECT id_user, no_identitas, nama_lengkap FROM staffm WHERE id_user IS NOT NULL AND id_user <> ''"
    );
    while ($staffRows && ($row = mysqli_fetch_assoc($staffRows))) {
        syncToUser(
            $conn,
            (string) $row['id_user'],
            (string) ($row['nama_lengkap'] ?? ''),
            (string) ($row['no_identitas'] ?? '')
        );
    }
}

reconcileLinkedAccounts($conn);

function namaTanpaAngka(string $nama): bool
{
    return $nama !== '' && !preg_match('/\d/u', $nama);
}

function usernameTanpaAngka(string $username): bool
{
    return $username !== ''
        && !preg_match('/\d/u', $username)
        && !preg_match('/\s/u', $username);
}

// ==========================================
// LOGIKA CRUD
// ==========================================

// 1. TAMBAH AKUN PENGGUNA MANUAL
// Role dipilih langsung oleh admin. Akun yang dibuat dari Data Pasien/Tim Pengelola
// tetap akan dibuat otomatis dan terhubung ke profil masing-masing.
if (isset($_POST['add_user'])) {
    $id = generateID('USR');
    $un = trim((string) ($_POST['username'] ?? ''));
    $em = trim((string) ($_POST['email'] ?? ''));
    $ps = (string) ($_POST['password'] ?? '');
    $rl = trim((string) ($_POST['role'] ?? ''));
    $nm = trim((string) ($_POST['nama_lengkap'] ?? ''));

    if ($un === '' || $em === '' || $ps === '' || $nm === '' || $rl === '') {
        header('Location: index.php?page=user&err=' . urlencode('Ada input kosong. Silakan isi terlebih dahulu.'));
        exit();
    }
    if (!in_array($rl, ['Dokter', 'K3', 'Pasien', 'Vendor'], true)) {
        header('Location: index.php?page=user&err=' . urlencode('Role akun tidak sesuai.'));
        exit();
    }
    if (!usernameTanpaAngka($un)) {
        header('Location: index.php?page=user&err=' . urlencode('Username tidak boleh mengandung angka atau spasi.'));
        exit();
    }
    if (!namaTanpaAngka($nm)) {
        header('Location: index.php?page=user&err=' . urlencode('Nama lengkap tidak boleh mengandung angka.'));
        exit();
    }
    if (!filter_var($em, FILTER_VALIDATE_EMAIL) || strlen($ps) < 8) {
        header('Location: index.php?page=user&err=' . urlencode('Ada input yang salah. Silakan periksa kembali data akun.'));
        exit();
    }

    $cek = mysqli_prepare($conn, 'SELECT 1 FROM userm WHERE username = ? OR email = ? LIMIT 1');
    mysqli_stmt_bind_param($cek, 'ss', $un, $em);
    mysqli_stmt_execute($cek);
    $exists = mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0;
    mysqli_stmt_close($cek);

    if ($exists) {
        header('Location: index.php?page=user&err=' . urlencode('Ada data akun yang sudah digunakan. Gunakan data lain lalu coba kembali.'));
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO userm (id_user, username, email, password, role, nama_lengkap, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW(6))'
    );
    mysqli_stmt_bind_param($stmt, 'ssssss', $id, $un, $em, $ps, $rl, $nm);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: index.php?page=user&' . ($saved
        ? 'msg=' . urlencode('Akun pengguna berhasil ditambah.')
        : 'err=' . urlencode('Akun gagal ditambah. Silakan coba kembali.')));
    exit();
}

// 2. TAMBAH STAFF + OTOMATIS BUAT AKUN PENGGUNA
if (isset($_POST['add_staff'])) {
    $nip = preg_replace('/\D+/', '', (string) ($_POST['no_identitas'] ?? '')) ?? '';
    $nama = trim((string) ($_POST['nama_lengkap'] ?? ''));
    $role = trim((string) ($_POST['role_akun'] ?? ''));
    $jbt = trim((string) ($_POST['jabatan'] ?? ''));
    $ins = trim((string) ($_POST['instansi'] ?? ''));
    $npa = trim((string) ($_POST['npa_idi'] ?? ''));
    $hp = normalizePhone62((string) ($_POST['no_hp'] ?? ''));
    $usernameSso = accountFromIdentity($nip);

    if ($nip === '' || $nama === '' || $jbt === '' || $usernameSso === '') {
        header('Location: index.php?page=staff&err=' . urlencode('Ada input kosong. Silakan isi terlebih dahulu.'));
        exit();
    }
    if (!namaTanpaAngka($nama)) {
        header('Location: index.php?page=staff&err=' . urlencode('Nama staf tidak boleh mengandung angka.'));
        exit();
    }
    if (!in_array($role, ['Dokter', 'Admin', 'K3'], true)) {
        header('Location: index.php?page=staff&err=' . urlencode('Role akun staf tidak sesuai.'));
        exit();
    }

    $cekIdentitas = mysqli_prepare($conn, 'SELECT 1 FROM staffm WHERE no_identitas = ? LIMIT 1');
    mysqli_stmt_bind_param($cekIdentitas, 's', $nip);
    mysqli_stmt_execute($cekIdentitas);
    $identityExists = mysqli_num_rows(mysqli_stmt_get_result($cekIdentitas)) > 0;
    mysqli_stmt_close($cekIdentitas);

    if ($identityExists || linkedAccountExists($conn, $usernameSso)) {
        header('Location: index.php?page=staff&err=' . urlencode('NIP atau akun staf sudah digunakan.'));
        exit();
    }

    $idU = generateID('USR');
    $idS = generateID('STF');
    $namaDepan = strtolower((string) (preg_split('/\s+/', $nama)[0] ?? 'staff'));
    $passStaff = $namaDepan . '123';

    try {
        mysqli_begin_transaction($conn);

        $stmt1 = mysqli_prepare(
            $conn,
            'INSERT INTO userm (id_user, username, email, password, role, nama_lengkap, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW(6))'
        );
        mysqli_stmt_bind_param($stmt1, 'ssssss', $idU, $usernameSso, $usernameSso, $passStaff, $role, $nama);
        if (!mysqli_stmt_execute($stmt1)) {
            throw new RuntimeException(mysqli_stmt_error($stmt1));
        }
        mysqli_stmt_close($stmt1);

        $stmt2 = mysqli_prepare(
            $conn,
            'INSERT INTO staffm (id_staff, id_user, nama_lengkap, no_identitas, jabatan, instansi, npa_idi, no_hp, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(6))'
        );
        mysqli_stmt_bind_param($stmt2, 'ssssssss', $idS, $idU, $nama, $nip, $jbt, $ins, $npa, $hp);
        if (!mysqli_stmt_execute($stmt2)) {
            throw new RuntimeException(mysqli_stmt_error($stmt2));
        }
        mysqli_stmt_close($stmt2);

        mysqli_commit($conn);
        header('Location: index.php?page=staff&msg=' . urlencode('Staf dan akun pengguna berhasil dibuat. Username: ' . $usernameSso));
        exit();
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        error_log('Tambah staf admin gagal: ' . $exception->getMessage());
        header('Location: index.php?page=staff&err=' . urlencode('Data staf gagal disimpan.'));
        exit();
    }
}

// 3. TAMBAH PASIEN + OTOMATIS BUAT AKUN PENGGUNA
if (isset($_POST['add_pasien'])) {
    $idU = generateID('USR');
    $idP = generateID('PSN');

    $password = (string) ($_POST['password'] ?? '');
    $identitas = preg_replace('/\D+/', '', (string) ($_POST['no_identitas'] ?? '')) ?? '';
    $username = accountFromIdentity($identitas);
    $email = $username;
    $nama = trim((string) ($_POST['nama_pasien'] ?? ''));
    $jk = (string) ($_POST['jenis_kelamin'] ?? '');
    $kat = (string) ($_POST['kategori_pasien'] ?? '');
    $alm = trim((string) ($_POST['alamat'] ?? ''));
    $hp = normalizePhone62((string) ($_POST['no_hp'] ?? ''));
    $hpDigits = preg_replace('/\D+/', '', substr($hp, 3)) ?? '';
    $unitProdi = '';

    $errorsPasien = [];
    if ($username === '') {
        $errorsPasien[] = 'NIM/NIP/NIK wajib diisi agar akun pengguna dapat dibuat.';
    }
    if (strlen($password) < 8) {
        $errorsPasien[] = 'Password minimal 8 karakter.';
    }
    if ($nama === '' || strlen($nama) < 3) {
        $errorsPasien[] = 'Nama pasien wajib diisi minimal 3 karakter.';
    }
    if (!in_array($jk, ['L', 'P'], true)) {
        $errorsPasien[] = 'Jenis kelamin belum dipilih.';
    }
    if (!in_array($kat, ['Mahasiswa', 'Pegawai', 'Sigap', 'Virtus', 'Tamu'], true)) {
        $errorsPasien[] = 'Kategori pasien belum dipilih.';
    }
    if ($identitas === '' || ($kat === 'Tamu' && strlen($identitas) !== 16) || ($kat !== 'Tamu' && (strlen($identitas) < 3 || strlen($identitas) > 30))) {
        $errorsPasien[] = $kat === 'Tamu'
            ? 'NIK Tamu Umum / Lain-lain harus tepat 16 angka.'
            : 'NIM/NIP harus berisi minimal 3 dan maksimal 30 angka.';
    }
    if (!preg_match('/^8\d{8,12}$/', $hpDigits)) {
        $errorsPasien[] = 'Nomor WhatsApp harus dimulai angka 8 setelah +62.';
    }
    if ($alm === '') {
        $errorsPasien[] = 'Alamat wajib diisi.';
    }

    if ($errorsPasien !== []) {
        header('Location: index.php?page=pasien&err=' . urlencode(count($errorsPasien) === 1 ? $errorsPasien[0] : 'Ada beberapa input pasien yang salah. Silakan periksa kembali.'));
        exit();
    }

    $stmtCek = mysqli_prepare(
        $conn,
        'SELECT
            EXISTS(SELECT 1 FROM userm WHERE username = ? OR email = ?) AS account_exists,
            EXISTS(SELECT 1 FROM pasienm WHERE no_identitas = ?) AS identity_exists'
    );
    mysqli_stmt_bind_param($stmtCek, 'sss', $username, $email, $identitas);
    mysqli_stmt_execute($stmtCek);
    $duplicate = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCek)) ?: [];
    mysqli_stmt_close($stmtCek);

    if ((int) ($duplicate['account_exists'] ?? 0) === 1) {
        header('Location: index.php?page=pasien&err=' . urlencode('Akun dengan NIM/NIP/NIK tersebut sudah digunakan.'));
        exit();
    }
    if ((int) ($duplicate['identity_exists'] ?? 0) === 1) {
        header('Location: index.php?page=pasien&err=' . urlencode('Nomor identitas sudah digunakan.'));
        exit();
    }

    try {
        mysqli_begin_transaction($conn);

        $stmt1 = mysqli_prepare(
            $conn,
            "INSERT INTO userm (id_user, username, email, password, role, nama_lengkap, created_at) VALUES (?, ?, ?, ?, 'Pasien', ?, NOW(6))"
        );
        mysqli_stmt_bind_param($stmt1, 'sssss', $idU, $username, $email, $password, $nama);
        if (!mysqli_stmt_execute($stmt1)) {
            throw new RuntimeException(mysqli_stmt_error($stmt1));
        }
        mysqli_stmt_close($stmt1);

        $stmt2 = mysqli_prepare(
            $conn,
            'INSERT INTO pasienm (id_pasien, id_user, no_identitas, nama_pasien, jenis_kelamin, kategori_pasien, unit_prodi, alamat, no_hp, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(6))'
        );
        mysqli_stmt_bind_param($stmt2, 'sssssssss', $idP, $idU, $identitas, $nama, $jk, $kat, $unitProdi, $alm, $hp);
        if (!mysqli_stmt_execute($stmt2)) {
            throw new RuntimeException(mysqli_stmt_error($stmt2));
        }
        mysqli_stmt_close($stmt2);

        mysqli_commit($conn);
        header('Location: index.php?page=pasien&msg=' . urlencode('Pasien dan akun berhasil dibuat. Username: ' . $username));
        exit();
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        error_log('Tambah pasien admin gagal: ' . $exception->getMessage());
        header('Location: index.php?page=pasien&err=' . urlencode('Data pasien gagal disimpan.'));
        exit();
    }
}

// 4. UPDATE USER (SINKRON DENGAN PROFIL PASIEN / STAF)
if (isset($_POST['update_user'])) {
    $id = trim((string) ($_POST['id_user'] ?? ''));
    $un = trim((string) ($_POST['username'] ?? ''));
    $em = trim((string) ($_POST['email'] ?? ''));
    $newPassword = (string) ($_POST['password'] ?? '');
    $rl = trim((string) ($_POST['role'] ?? ''));
    $nm = trim((string) ($_POST['nama_lengkap'] ?? ''));

    if ($id === '' || $un === '' || $nm === '') {
        header('Location: index.php?page=user&err=' . urlencode('Ada input kosong. Silakan isi terlebih dahulu.'));
        exit();
    }
    if (!usernameTanpaAngka($un)) {
        header('Location: index.php?page=user&err=' . urlencode('Username tidak boleh mengandung angka atau spasi.'));
        exit();
    }
    if (!namaTanpaAngka($nm)) {
        header('Location: index.php?page=user&err=' . urlencode('Nama lengkap tidak boleh mengandung angka.'));
        exit();
    }
    if ($newPassword !== '' && strlen($newPassword) < 8) {
        header('Location: index.php?page=user&err=' . urlencode('Password minimal 8 karakter.'));
        exit();
    }

    $linkStmt = mysqli_prepare(
        $conn,
        'SELECT u.role AS current_role,
                p.id_pasien, p.no_identitas AS pasien_identitas,
                s.id_staff, s.no_identitas AS staff_identitas
         FROM userm u
         LEFT JOIN pasienm p ON p.id_user = u.id_user
         LEFT JOIN staffm s ON s.id_user = u.id_user
         WHERE u.id_user = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($linkStmt, 's', $id);
    mysqli_stmt_execute($linkStmt);
    $linked = mysqli_fetch_assoc(mysqli_stmt_get_result($linkStmt)) ?: [];
    mysqli_stmt_close($linkStmt);

    $isPatient = !empty($linked['id_pasien']);
    $isStaff = !empty($linked['id_staff']);

    $currentRole = (string) ($linked['current_role'] ?? '');

    if ($isPatient) {
        // Profil pasien tetap ber-role Pasien.
        $rl = 'Pasien';
    } elseif ($currentRole === 'Admin') {
        // Akun Admin yang sudah ada tetap dipertahankan, tetapi Admin tidak tersedia sebagai pilihan role.
        $rl = 'Admin';
    } elseif ($isStaff) {
        if (!in_array($rl, ['Dokter', 'K3'], true)) {
            header('Location: index.php?page=user&err=' . urlencode('Role akun staf hanya boleh Dokter atau K3.'));
            exit();
        }
    } elseif (!in_array($rl, ['Dokter', 'K3', 'Pasien', 'Vendor'], true)) {
        header('Location: index.php?page=user&err=' . urlencode('Role akun tidak sesuai.'));
        exit();
    }

    if ($em === '' || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
        header('Location: index.php?page=user&err=' . urlencode('Format email belum sesuai.'));
        exit();
    }

    $duplicateStmt = mysqli_prepare(
        $conn,
        'SELECT 1 FROM userm WHERE (username = ? OR email = ?) AND id_user <> ? LIMIT 1'
    );
    mysqli_stmt_bind_param($duplicateStmt, 'sss', $un, $em, $id);
    mysqli_stmt_execute($duplicateStmt);
    $accountExists = mysqli_num_rows(mysqli_stmt_get_result($duplicateStmt)) > 0;
    mysqli_stmt_close($duplicateStmt);
    if ($accountExists) {
        header('Location: index.php?page=user&err=' . urlencode('Username atau email sudah digunakan akun lain.'));
        exit();
    }

    try {
        mysqli_begin_transaction($conn);

        if ($newPassword !== '') {
            $stmt = mysqli_prepare($conn, 'UPDATE userm SET username=?, email=?, password=?, role=?, nama_lengkap=? WHERE id_user=?');
            mysqli_stmt_bind_param($stmt, 'ssssss', $un, $em, $newPassword, $rl, $nm, $id);
        } else {
            $stmt = mysqli_prepare($conn, 'UPDATE userm SET username=?, email=?, role=?, nama_lengkap=? WHERE id_user=?');
            mysqli_stmt_bind_param($stmt, 'sssss', $un, $em, $rl, $nm, $id);
        }
        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException(mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

        if ($isPatient) {
            $stmtP = mysqli_prepare($conn, 'UPDATE pasienm SET nama_pasien=? WHERE id_user=?');
            mysqli_stmt_bind_param($stmtP, 'ss', $nm, $id);
            mysqli_stmt_execute($stmtP);
            mysqli_stmt_close($stmtP);
        }
        if ($isStaff) {
            $stmtS = mysqli_prepare($conn, 'UPDATE staffm SET nama_lengkap=? WHERE id_user=?');
            mysqli_stmt_bind_param($stmtS, 'ss', $nm, $id);
            mysqli_stmt_execute($stmtS);
            mysqli_stmt_close($stmtS);
        }

        mysqli_commit($conn);
        header('Location: index.php?page=user&msg=' . urlencode('Akun dan data terkait berhasil diperbarui.'));
        exit();
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        error_log('Update akun admin gagal: ' . $exception->getMessage());
        header('Location: index.php?page=user&err=' . urlencode('Akun gagal diperbarui.'));
        exit();
    }
}

// 5. UPDATE STAFF + SINKRON AKUN PENGGUNA
if (isset($_POST['update_staff'])) {
    $idS = trim((string) ($_POST['id_staff'] ?? ''));
    $nmL = trim((string) ($_POST['nama_lengkap'] ?? ''));
    $noI = preg_replace('/\D+/', '', (string) ($_POST['no_identitas'] ?? '')) ?? '';
    $jbt = trim((string) ($_POST['jabatan'] ?? ''));
    $ins = trim((string) ($_POST['instansi'] ?? ''));
    $role = trim((string) ($_POST['role_akun'] ?? ''));
    $npa = trim((string) ($_POST['npa_idi'] ?? ''));
    $hp = normalizePhone62((string) ($_POST['no_hp'] ?? ''));

    if ($idS === '' || $noI === '' || $nmL === '' || $jbt === '' || $ins === '' || $role === '' || $hp === '') {
        header('Location: index.php?page=staff&err=' . urlencode('Ada input kosong. Silakan isi terlebih dahulu.'));
        exit();
    }
    if (!namaTanpaAngka($nmL)) {
        header('Location: index.php?page=staff&err=' . urlencode('Nama staf tidak boleh mengandung angka.'));
        exit();
    }
    if (!in_array($role, ['Dokter', 'Admin', 'K3'], true)) {
        header('Location: index.php?page=staff&err=' . urlencode('Role akun staf tidak sesuai.'));
        exit();
    }

    $idU = getUserIdFrom($conn, 'staffm', 'id_staff', $idS);
    $newAccount = accountFromIdentity($noI);
    if (linkedAccountExists($conn, $newAccount, $idU ?? '')) {
        header('Location: index.php?page=staff&err=' . urlencode('NIP atau akun staf sudah digunakan.'));
        exit();
    }

    $checkIdentity = mysqli_prepare($conn, 'SELECT 1 FROM staffm WHERE no_identitas = ? AND id_staff <> ? LIMIT 1');
    mysqli_stmt_bind_param($checkIdentity, 'ss', $noI, $idS);
    mysqli_stmt_execute($checkIdentity);
    $identityExists = mysqli_num_rows(mysqli_stmt_get_result($checkIdentity)) > 0;
    mysqli_stmt_close($checkIdentity);
    if ($identityExists) {
        header('Location: index.php?page=staff&err=' . urlencode('NIP sudah digunakan staf lain.'));
        exit();
    }

    try {
        mysqli_begin_transaction($conn);

        if (!$idU) {
            $idU = generateID('USR');
            $namaDepan = strtolower((string) (preg_split('/\s+/', $nmL)[0] ?? 'staff'));
            $defaultPassword = $namaDepan . '123';
            $stmtUser = mysqli_prepare(
                $conn,
                'INSERT INTO userm (id_user, username, email, password, role, nama_lengkap, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW(6))'
            );
            mysqli_stmt_bind_param($stmtUser, 'ssssss', $idU, $newAccount, $newAccount, $defaultPassword, $role, $nmL);
            if (!mysqli_stmt_execute($stmtUser)) {
                throw new RuntimeException(mysqli_stmt_error($stmtUser));
            }
            mysqli_stmt_close($stmtUser);
        }

        $stmt = mysqli_prepare(
            $conn,
            'UPDATE staffm SET id_user=?, nama_lengkap=?, no_identitas=?, jabatan=?, instansi=?, npa_idi=?, no_hp=? WHERE id_staff=?'
        );
        mysqli_stmt_bind_param($stmt, 'ssssssss', $idU, $nmL, $noI, $jbt, $ins, $npa, $hp, $idS);
        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException(mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

        if (!syncToUser($conn, $idU, $nmL, $noI, $role)) {
            throw new RuntimeException('Akun staf tidak dapat disinkronkan.');
        }

        mysqli_commit($conn);
        header('Location: index.php?page=staff&msg=' . urlencode('Data staf dan akun pengguna berhasil diperbarui.'));
        exit();
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        error_log('Update staf admin gagal: ' . $exception->getMessage());
        header('Location: index.php?page=staff&err=' . urlencode('Data staf gagal diperbarui.'));
        exit();
    }
}

// 6. UPDATE PASIEN + SINKRON AKUN PENGGUNA
if (isset($_POST['update_pasien'])) {
    $idP = trim((string) ($_POST['id_pasien'] ?? ''));
    $idUP = trim((string) ($_POST['id_user'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $nm = trim((string) ($_POST['nama_pasien'] ?? ''));
    $nip = preg_replace('/\D+/', '', (string) ($_POST['no_identitas'] ?? '')) ?? '';
    $email = accountFromIdentity($nip);
    $jk = (string) ($_POST['jenis_kelamin'] ?? '');
    $kat = (string) ($_POST['kategori_pasien'] ?? '');
    $alm = trim((string) ($_POST['alamat'] ?? ''));
    $hp = normalizePhone62((string) ($_POST['no_hp'] ?? ''));
    $hpDigits = preg_replace('/\D+/', '', substr($hp, 3)) ?? '';

    if (!in_array($kat, ['Mahasiswa', 'Pegawai', 'Sigap', 'Virtus', 'Tamu'], true)) {
        header('Location: index.php?page=pasien&err=' . urlencode('Kategori pasien belum dipilih.'));
        exit();
    }
    if ($idP === '' || $idUP === '' || $email === '' || $nm === '' || $alm === '') {
        header('Location: index.php?page=pasien&err=' . urlencode('Ada input kosong. Silakan isi terlebih dahulu.'));
        exit();
    }
    if ($password !== '' && strlen($password) < 8) {
        header('Location: index.php?page=pasien&err=' . urlencode('Password minimal 8 karakter.'));
        exit();
    }

    $stmtDuplicate = mysqli_prepare(
        $conn,
        'SELECT
            EXISTS(SELECT 1 FROM userm WHERE email = ? AND id_user <> ?) AS account_exists,
            EXISTS(SELECT 1 FROM pasienm WHERE no_identitas = ? AND id_pasien <> ?) AS identity_exists'
    );
    mysqli_stmt_bind_param($stmtDuplicate, 'ssss', $email, $idUP, $nip, $idP);
    mysqli_stmt_execute($stmtDuplicate);
    $duplicate = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtDuplicate)) ?: [];
    mysqli_stmt_close($stmtDuplicate);

    if ((int) ($duplicate['account_exists'] ?? 0) === 1) {
        header('Location: index.php?page=pasien&err=' . urlencode('Akun dengan NIM/NIP/NIK tersebut sudah digunakan.'));
        exit();
    }
    if ((int) ($duplicate['identity_exists'] ?? 0) === 1) {
        header('Location: index.php?page=pasien&err=' . urlencode('Nomor identitas sudah digunakan.'));
        exit();
    }

    // Unit / Prodi hanya dipakai untuk Mahasiswa dan Pegawai.
    // Karena halaman Admin tidak menampilkan field Unit / Prodi, nilai lama dipertahankan
    // untuk dua kategori tersebut. Untuk Tamu/Sigap/Virtus nilainya selalu dikosongkan.
    $unitProdi = '';
    if (in_array($kat, ['Mahasiswa', 'Pegawai'], true)) {
        $stmtUnit = mysqli_prepare($conn, 'SELECT unit_prodi FROM pasienm WHERE id_pasien = ? LIMIT 1');
        mysqli_stmt_bind_param($stmtUnit, 's', $idP);
        mysqli_stmt_execute($stmtUnit);
        $unitRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUnit));
        mysqli_stmt_close($stmtUnit);
        $unitProdi = trim((string) ($unitRow['unit_prodi'] ?? ''));
    }

    try {
        mysqli_begin_transaction($conn);

        if ($password !== '') {
            $stmtUser = mysqli_prepare($conn, "UPDATE userm SET email=?, password=?, role='Pasien', nama_lengkap=? WHERE id_user=?");
            mysqli_stmt_bind_param($stmtUser, 'ssss', $email, $password, $nm, $idUP);
        } else {
            $stmtUser = mysqli_prepare($conn, "UPDATE userm SET email=?, role='Pasien', nama_lengkap=? WHERE id_user=?");
            mysqli_stmt_bind_param($stmtUser, 'sss', $email, $nm, $idUP);
        }
        if (!mysqli_stmt_execute($stmtUser)) {
            throw new RuntimeException(mysqli_stmt_error($stmtUser));
        }
        mysqli_stmt_close($stmtUser);

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE pasienm SET nama_pasien=?, no_identitas=?, jenis_kelamin=?, kategori_pasien=?, unit_prodi=?, alamat=?, no_hp=? WHERE id_pasien=?"
        );
        mysqli_stmt_bind_param($stmt, 'ssssssss', $nm, $nip, $jk, $kat, $unitProdi, $alm, $hp, $idP);
        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException(mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        header('Location: index.php?page=pasien&msg=' . urlencode('Data pasien dan akun pengguna berhasil diperbarui.'));
        exit();
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        error_log('Update pasien admin gagal: ' . $exception->getMessage());
        header('Location: index.php?page=pasien&err=' . urlencode('Data pasien gagal diperbarui.'));
        exit();
    }
}

// 8. TAMBAH SUPPLIER
if (isset($_POST["add_supplier"])) {
    $id = generateID("SUP");
    $nama = trim((string) ($_POST["nama_supplier"] ?? ""));
    $alamat = trim((string) ($_POST["alamat"] ?? ""));
    $kontak = normalizePhone62((string) ($_POST["kontak"] ?? ""));

    if ($nama === '') {
        header('Location: index.php?page=supplier&err=' . urlencode('Ada input kosong. Silakan isi terlebih dahulu.'));
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO supplierm (id_supplier, nama_supplier, kontak, alamat, created_at) VALUES (?, ?, ?, ?, NOW(6))",
    );
    mysqli_stmt_bind_param($stmt, "ssss", $id, $nama, $kontak, $alamat);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: index.php?page=supplier&' . ($saved
        ? 'msg=' . urlencode('Pemasok berhasil ditambah.')
        : 'err=' . urlencode('Pemasok gagal ditambah. Silakan coba kembali.')));
    exit();
}

// 9. UPDATE SUPPLIER
if (isset($_POST["update_supplier"])) {
    $id = trim((string) ($_POST["id_supplier"] ?? ""));
    $nama = trim((string) ($_POST["nama_supplier"] ?? ""));
    $alamat = trim((string) ($_POST["alamat"] ?? ""));
    $kontak = normalizePhone62((string) ($_POST["kontak"] ?? ""));

    if ($id === '' || $nama === '') {
        header('Location: index.php?page=supplier&err=' . urlencode('Ada input kosong. Silakan isi terlebih dahulu.'));
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE supplierm SET nama_supplier=?, kontak=?, alamat=? WHERE id_supplier=?",
    );
    mysqli_stmt_bind_param($stmt, "ssss", $nama, $kontak, $alamat, $id);
    $updated = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: index.php?page=supplier&' . ($updated
        ? 'msg=' . urlencode('Data supplier berhasil diperbarui.')
        : 'err=' . urlencode('Data supplier gagal diperbarui. Silakan coba kembali.')));
    exit();
}

// 7. HAPUS DATA TERHUBUNG
// Pasien/Staf dan akun pengguna dihapus sebagai satu kesatuan.
if (isset($_GET['del'])) {
    $allowed = [
        'userm' => 'id_user',
        'staffm' => 'id_staff',
        'pasienm' => 'id_pasien',
        'supplierm' => 'id_supplier',
    ];
    $tabel = (string) ($_GET['t'] ?? '');
    $kolom = (string) ($_GET['k'] ?? '');
    $val = (string) ($_GET['del'] ?? '');
    $pg = preg_replace('/[^a-z_]/i', '', (string) ($_GET['page'] ?? 'dashboard'));

    $message = 'Data berhasil dihapus.';
    $error = '';

    if (isset($allowed[$tabel]) && $allowed[$tabel] === $kolom && $val !== '') {
        try {
            mysqli_begin_transaction($conn);

            if ($tabel === 'pasienm' || $tabel === 'staffm') {
                $idUser = getUserIdFrom($conn, $tabel, $kolom, $val);

                $stmtProfile = mysqli_prepare($conn, "DELETE FROM `$tabel` WHERE `$kolom` = ?");
                mysqli_stmt_bind_param($stmtProfile, 's', $val);
                if (!mysqli_stmt_execute($stmtProfile)) {
                    throw new RuntimeException(mysqli_stmt_error($stmtProfile));
                }
                mysqli_stmt_close($stmtProfile);

                if ($idUser) {
                    $stmtUser = mysqli_prepare($conn, 'DELETE FROM userm WHERE id_user = ?');
                    mysqli_stmt_bind_param($stmtUser, 's', $idUser);
                    if (!mysqli_stmt_execute($stmtUser)) {
                        throw new RuntimeException(mysqli_stmt_error($stmtUser));
                    }
                    mysqli_stmt_close($stmtUser);
                }

                $message = $tabel === 'pasienm'
                    ? 'Data pasien dan akun pengguna berhasil dihapus.'
                    : 'Data staf dan akun pengguna berhasil dihapus.';
            } else {
                $stmt = mysqli_prepare($conn, "DELETE FROM `$tabel` WHERE `$kolom` = ?");
                mysqli_stmt_bind_param($stmt, 's', $val);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException(mysqli_stmt_error($stmt));
                }
                mysqli_stmt_close($stmt);

                $message = $tabel === 'userm'
                    ? 'Akun dan data profil yang terhubung berhasil dihapus.'
                    : 'Data berhasil dihapus.';
            }

            mysqli_commit($conn);
        } catch (Throwable $exception) {
            mysqli_rollback($conn);
            error_log('Hapus data admin gagal: ' . $exception->getMessage());
            $error = 'Data gagal dihapus karena masih digunakan oleh data lain.';
        }
    }

    header('Location: index.php?page=' . $pg . '&' . ($error !== ''
        ? 'err=' . urlencode($error)
        : 'msg=' . urlencode($message)));
    exit();
}

$u_list = mysqli_query(
    $conn,
    "SELECT u.id_user, u.username, u.email, u.password, u.role, u.nama_lengkap, u.created_at,
            p.id_pasien AS linked_pasien_id, p.no_identitas AS pasien_identitas,
            s.id_staff AS linked_staff_id, s.no_identitas AS staff_identitas
     FROM userm u
     LEFT JOIN pasienm p ON p.id_user = u.id_user
     LEFT JOIN staffm s ON s.id_user = u.id_user
     ORDER BY u.created_at DESC, u.id_user DESC"
);
$s_list = mysqli_query(
    $conn,
    "SELECT s.*, u.username, u.email, u.role AS role_akun
     FROM staffm s
     LEFT JOIN userm u ON u.id_user = s.id_user
     ORDER BY s.created_at DESC, s.id_staff DESC"
);
$p_list = mysqli_query(
    $conn,
    "SELECT p.*, u.username, u.email, u.password
     FROM pasienm p
     LEFT JOIN userm u ON u.id_user = p.id_user
     ORDER BY p.created_at DESC, p.id_pasien DESC"
);
$sup_list = mysqli_query($conn, "SELECT * FROM supplierm ORDER BY created_at DESC, id_supplier DESC");

$chart_roles = mysqli_query(
    $conn,
    "SELECT role, COUNT(*) as jumlah FROM userm GROUP BY role",
);
$chart_labels = [];
$chart_data = [];
while ($row = mysqli_fetch_assoc($chart_roles)) {
    $chart_labels[] = $row["role"];
    $chart_data[] = $row["jumlah"];
}

// Palet warna untuk chart & chip role (dipakai server-side & di-mirror di JS)
$chart_palette = [
    "#0057B8",
    "#13a06a",
    "#f4a11d",
    "#8b5cf6",
    "#ef4444",
    "#0891b2",
];
// 1. Data untuk Line Chart (Jumlah Kunjungan per Bulan)
// Asumsi ada tabel 'pemeriksaan' atau 'rekam_medis' dengan kolom 'tgl_kunjungan'
// Jika nama tabel berbeda, silakan sesuaikan
$kunjungan_data = array_fill(1, 12, 0); // Siapkan array 12 bulan diisi 0
$query_kunjungan = mysqli_query(
    $conn,
    "SELECT MONTH(tgl_kunjungan) as bulan, COUNT(*) as jumlah 
                                        FROM rekam_medis 
                                        WHERE YEAR(tgl_kunjungan) = YEAR(CURDATE()) 
                                        GROUP BY MONTH(tgl_kunjungan)",
);
while ($row = mysqli_fetch_assoc($query_kunjungan)) {
    $kunjungan_data[(int) $row["bulan"]] = (int) $row["jumlah"];
}
$line_chart_values = array_values($kunjungan_data);

// 2. Data untuk Donut Chart (Kategori Pasien)
$donut_labels = [];
$donut_values = [];
$query_donut = mysqli_query(
    $conn,
    "SELECT kategori_pasien, COUNT(*) as jumlah FROM pasienm GROUP BY kategori_pasien",
);
while ($row = mysqli_fetch_assoc($query_donut)) {
    $donut_labels[] = $row["kategori_pasien"];
    $donut_values[] = (int) $row["jumlah"];
}
?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Admin - ASTARhealth</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --astar-blue: #0057B8;
            --astar-blue-light: #2E86F0;
            --astar-blue-deep: #003D82;
            --astar-soft-blue: #eef4ff;
            --astar-mist: #dbe9ff;
            --sidebar-bg: #ffffff;
            --r-sm: 12px;
            --r-md: 18px;
            --r-lg: 26px;
            --shadow-soft: 0 16px 36px rgba(15, 61, 130, 0.10);
            --shadow-card: 0 10px 24px rgba(15, 61, 130, 0.06);
        }
        * { scrollbar-width: thin; scrollbar-color: var(--astar-mist) transparent; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: radial-gradient(1200px 600px at 100% -10%, #eaf2ff 0%, #f4f7fa 45%) fixed; color: #334155; }

        .top-header { height: 74px; background: linear-gradient(115deg, var(--astar-blue-deep) 0%, var(--astar-blue) 45%, var(--astar-blue-light) 100%); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; color: white; position: fixed; top: 0; width: 100%; z-index: 1001; box-shadow: var(--shadow-soft); }
        #digitalClock { font-weight: 600; font-size: 14px; background: rgba(255,255,255,0.16); backdrop-filter: blur(6px); padding: 6px 18px; border-radius: 999px; }

        .sidebar { width: 260px; background: #FFFFFF; height: 100vh; position: fixed; top: 70px; left: 0; border-right: none; box-shadow: 6px 0 24px rgba(15, 61, 130, 0.05); z-index: 1000; padding: 26px 16px; transition: all 0.3s ease; border-radius: 0 28px 0 0; }
        .main-content { margin-left: 260px; padding: 108px 32px 40px; transition: all 0.3s ease; }
        body.sidebar-toggled .sidebar { left: -260px; }
        body.sidebar-toggled .main-content { margin-left: 0; }
        .nav-group-title { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; padding: 15px 15px 8px; letter-spacing: 1px; }
        .nav-link { padding: 12px 20px; color: #64748b; font-weight: 500; display: flex; align-items: center; transition: 0.2s; text-decoration: none; font-size: 14px; border-radius: var(--r-sm); margin-bottom: 2px; }
        .nav-link i { font-size: 1.2rem; width: 35px; }
        .nav-link:hover { background: var(--astar-soft-blue); color: var(--astar-blue); }
        .nav-link.active { background: linear-gradient(120deg, var(--astar-blue) 0%, var(--astar-blue-light) 100%); color: #fff; box-shadow: 0 10px 22px rgba(0,87,184,0.28); }
        .nav-link-logout { color: rgba(17, 112, 221, 0.77); }
        .nav-link-logout:hover { background: #fdecec; color: #dc3545; }

        .data-container { background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); border-radius: var(--r-lg); padding: 30px; box-shadow: var(--shadow-card); border: 1px solid rgba(15,61,130,0.04); animation: fadeIn 0.35s ease; }

        .stat-card { background: linear-gradient(135deg, #ffffff 0%, var(--astar-soft-blue) 160%); border-radius: var(--r-lg); padding: 26px; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(15,61,130,0.05); box-shadow: var(--shadow-card); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-soft); }
        .icon-badge { width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #fff; background: linear-gradient(135deg, var(--astar-blue), var(--astar-blue-light)); box-shadow: 0 10px 22px rgba(0,87,184,0.28); flex-shrink: 0; }
        .icon-badge.warning { background: linear-gradient(135deg, #f6c23e, #f4a11d); box-shadow: 0 10px 22px rgba(246,162,29,0.3); }
        .icon-badge.success { background: linear-gradient(135deg, #23d3a0, #13a06a); box-shadow: 0 10px 22px rgba(19,160,106,0.3); }

        #toggleSidebar { cursor: pointer; font-size: 1.5rem; color: white; margin-right: 15px; display: flex; align-items: center; }

        .data-container .table-responsive { border-radius: var(--r-md); overflow: hidden; border: 1px solid rgba(15,61,130,0.06); }
        .table thead th { background: var(--astar-soft-blue); color: var(--astar-blue-deep); font-weight: 700; text-transform: uppercase; font-size: 11px; padding: 15px; border: none; }
        .table td { border-color: rgba(15,61,130,0.06); vertical-align: middle; }
        .table-hover tbody tr { transition: 0.15s; }
        .table-hover tbody tr:hover { background: var(--astar-soft-blue); }
        .table .btn-light { width: 36px; height: 36px; border-radius: var(--r-sm); display: inline-flex; align-items: center; justify-content: center; background: #f4f7fb; border: 1px solid rgba(15,61,130,0.05); }
        .table .btn-light:hover { background: var(--astar-mist); }

        .btn-primary { background: linear-gradient(120deg, var(--astar-blue), var(--astar-blue-light)); border: none; box-shadow: 0 10px 22px rgba(0,87,184,0.25); }
        .btn-primary:hover { filter: brightness(1.06); transform: translateY(-1px); box-shadow: 0 12px 26px rgba(0,87,184,0.3); }

        .form-control, .form-select { border-radius: var(--r-sm) !important; background: #f6f8fc !important; padding: 10px 14px; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 4px rgba(0,87,184,0.12) !important; background: #fff !important; border-color: var(--astar-blue-light) !important; }
        .input-group-text { border-radius: var(--r-sm) 0 0 var(--r-sm) !important; background: #f6f8fc !important; }
        .input-group .form-control { border-radius: 0 var(--r-sm) var(--r-sm) 0 !important; }

        .modal-content { border-radius: var(--r-lg) !important; overflow: hidden; box-shadow: var(--shadow-soft); border: none; }
        .modal-header.bg-primary { background: linear-gradient(120deg, var(--astar-blue-deep), var(--astar-blue) 60%, var(--astar-blue-light)) !important; }
        .modal-header.bg-warning { background: linear-gradient(120deg, #f6c23e, #f4a11d) !important; }

        .alert-success { background: linear-gradient(120deg, #e9fbf3, #f2fffa) !important; border: 1px solid rgba(28,200,138,0.18) !important; color: #0f9d68 !important; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* ===== Chart chips ===== */
        .chart-chip { display: inline-flex; align-items: center; gap: 7px; background: #f4f7fb; border: 1px solid rgba(15,61,130,0.06); padding: 6px 14px; border-radius: 999px; font-size: 12.5px; font-weight: 600; color: #475569; transition: 0.2s; }
        .chart-chip:hover { background: var(--astar-soft-blue); transform: translateY(-1px); }
        .chart-chip .chip-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--chip-color); box-shadow: 0 0 0 3px color-mix(in srgb, var(--chip-color) 18%, transparent); }
        .chart-chip b { color: #1e293b; font-weight: 800; margin-left: 2px; }
        .chart-canvas-wrap { position: relative; height: 300px; }
    
        /* Gaya Card khas SB Admin 2 */
.card.border-left-primary { border-left: 0.25rem solid #4e73df !important; }
.card.border-left-success { border-left: 0.25rem solid #1cc88a !important; }
.card.border-left-info { border-left: 0.25rem solid #36b9cc !important; }
.card.border-left-warning { border-left: 0.25rem solid #f6c23e !important; }

.text-xs { font-size: .7rem; letter-spacing: 0.1rem; }
.text-gray-300 { color: #dddfeb !important; }
.text-gray-800 { color: #5a5c69 !important; }

/* Wrapper Chart agar tingginya pas */
.chart-area { position: relative; height: 320px; width: 100%; }
.chart-pie { position: relative; height: 260px; width: 100%; }

    </style>
    </head>
    <body>

    <header class="top-header">
        <div class="d-flex align-items-center">
            <div id="toggleSidebar"><i class="bi bi-list"></i></div>
            <img src="../assets/img/logoA.png" style="max-height: 70px; filter: brightness(0) invert(1);">
            <div id="digitalClock" class="d-none d-md-block ms-3"></div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block text-white">
                <div class="fw-bold" style="font-size: 14px;"><?= $admin_name ?></div>
                <div style="font-size: 11px; opacity: 0.8;">Administrator Sistem</div>
            </div>
            <i class="bi bi-person-circle fs-2 text-white" style="cursor: pointer;" data-bs-toggle="modal"></i>
        </div>
    </header>

    <div class="sidebar">
        <div class="nav-group-title">Menu Utama</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link <?= $active_page == "dashboard"
                ? "active"
                : "" ?>" href="?page=dashboard"><i class="bi bi-speedometer2"></i> Ringkasan</a>
            <div class="nav-group-title">Manajemen Akun</div>
            <a class="nav-link <?= $active_page == "user"
                ? "active"
                : "" ?>" href="?page=user"><i class="bi bi-person-lock"></i> Akun Pengguna</a>
            <a class="nav-link <?= $active_page == "staff"
                ? "active"
                : "" ?>" href="?page=staff"><i class="bi bi-shield-check"></i> Tim Pengelola</a>
            <a class="nav-link <?= $active_page == "pasien"
                ? "active"
                : "" ?>" href="?page=pasien"><i class="bi bi-people"></i> Data Pasien</a>
            <a class="nav-link <?= $active_page == "supplier"
                ? "active"
                : "" ?>" href="?page=supplier"><i class="bi bi-box-seam"></i> Data Pemasok</a>
            <div class="nav-group-title">Akun</div>
            <a class="nav-link nav-link-logout js-swal-logout" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Keluar</a>
        </nav>
    </div>

<main class="main-content">
<?php
$allowedAdminPages = ["dashboard", "user", "staff", "pasien", "supplier"];
if (!in_array($active_page, $allowedAdminPages, true)) {
    $active_page = "dashboard";
}
if ($active_page === "dashboard") {
?>
<?php
// Modul halaman Admin. Variabel data disiapkan oleh adminMaster.php.
?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Ringkasan Sistem</h1>
    </div>

    <!-- Info Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card border-left-primary shadow h-100 py-2 border-0">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">TOTAL PASIEN</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= mysqli_num_rows(
                                $p_list,
                            ) ?> Orang</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-people fs-2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card border-left-success shadow h-100 py-2 border-0">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">TIM STAFF</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= mysqli_num_rows(
                                $s_list,
                            ) ?> Personel</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-shield-check fs-2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card border-left-info shadow h-100 py-2 border-0">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">STOK OBAT (UNIT)</div>
                            <?php $obat = mysqli_fetch_assoc(
                                mysqli_query(
                                    $conn,
                                    "SELECT COUNT(*) as total FROM obatm",
                                ),
                            ); ?>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $obat[
                                "total"
                            ] ?> Item</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-capsule fs-2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row Charts -->
    <div class="row">
        <!-- Area Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4 border-0">
                <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Statistik Kunjungan Sakit (<?= date(
                        "Y",
                    ) ?>)</h6>
                    <small class="text-muted">Jumlah kunjungan per bulan</small>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="sickChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4 border-0">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Kategori Pasien Terdaftar</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="height: 260px;">
                        <canvas id="categoryDonutChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php foreach ($donut_labels as $index => $label):
                            $colors = [
                                "#4e73df",
                                "#1cc88a",
                                "#36b9cc",
                                "#f6c23e",
                            ]; ?>
                            <span class="mx-1">
                                <i class="bi bi-circle-fill" style="color: <?= $colors[
                                    $index % 4
                                ] ?>"></i> <?= $label ?>
                            </span>
                        <?php
                        endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
} else {
    $adminPageFile = __DIR__ . "/pages/" . $active_page . ".php";
    if (is_file($adminPageFile)) {
        include $adminPageFile;
    } else {
        echo '<div class="alert alert-warning">Halaman admin tidak ditemukan.</div>';
    }
}
?>
    
</main>

        <!-- MODAL LOGOUT (DIKEMBALIKAN KARENA SEBELUMNYA HILANG) -->
    <div class="modal fade" id="modalLogout" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="width: 350px;">
            <div class="modal-content border-0 shadow" style="border-radius: 20px;">
                <div class="modal-body text-center p-4">
                    <div class="text-danger mb-3"><i class="bi bi-exclamation-circle fs-1"></i></div>
                    <h5 class="fw-bold">Keluar dari Sistem?</h5>
                    <p class="text-secondary small">Sebelum Keluar Pastikan Semua Data Tersimpan.</p>
                    <div class="d-grid gap-2 mt-4">
                        <a href="../auth/logout.php" class="btn btn-danger py-2 rounded-3 fw-bold text-decoration-none text-white">Keluar</a>
                        <button type="button" class="btn-light btn py-2 rounded-3" data-bs-dismiss="modal">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL ADD USER -->
    <div class="modal fade" id="mAddUser" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 shadow-lg admin-user-form" style="border-radius: 20px;" method="POST" novalidate>
                <div class="modal-header bg-primary text-white border-0 py-4">
                    <h5 class="fw-bold mb-0">Tambah User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <input type="text" name="username" class="form-control account-username-input bg-light border-0" placeholder="Contoh: zeidalrayan" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email</label>
                        <input type="email" name="email" class="form-control bg-light border-0" placeholder="contoh@polytechnic.astar.ac.id" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kata Sandi</label>
                        <input type="text" name="password" class="form-control bg-light border-0" placeholder="Minimal 8 karakter" minlength="8" maxlength="72" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control person-name-input bg-light border-0" placeholder="Masukkan nama lengkap" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Role</label>
                        <select name="role" class="form-select bg-light border-0" required>
                            <option value="">Pilih role pengguna</option>
                            <option value="Dokter">Dokter</option>
                            <option value="K3">K3</option>
                            <option value="Pasien">Pasien</option>
                            <option value="Vendor">Vendor</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="submit" name="add_user" class="btn btn-primary w-100 py-2 fw-bold">Simpan User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL ADD STAFF -->
    <div class="modal fade" id="mAddStaff" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 shadow-lg staff-account-form" style="border-radius: 20px;" method="POST" novalidate>
                <div class="modal-header bg-primary text-white border-0 py-4">
                    <h5 class="fw-bold mb-0">Daftarkan Staf</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NIP</label>
                        <input type="text" name="no_identitas" class="form-control bg-light border-0 staff-identity-numeric" placeholder="Masukkan NIP, hanya angka" inputmode="numeric" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama dan Gelar</label>
                        <input type="text" name="nama_lengkap" class="form-control person-name-input bg-light border-0" placeholder="Contoh: Ike Indahwati, dr." autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Role Akun</label>
                        <select name="role_akun" class="form-select bg-light border-0" required>
                            <option value="">Pilih role akun</option>
                            <option value="Dokter">Dokter</option>
                            <option value="Admin">Admin</option>
                            <option value="K3">Tim K3</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jabatan</label>
                        <input type="text" name="jabatan" class="form-control bg-light border-0" placeholder="Contoh: Dokter UKK" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Instansi</label>
                        <select name="instansi" class="form-select bg-light border-0" required>
                            <option value="">Pilih instansi</option>
                            <option value="Kampus">Kampus</option>
                            <option value="Siloam">Siloam</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NPA IDI <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="npa_idi" class="form-control bg-light border-0" placeholder="Masukkan NPA IDI jika ada" autocomplete="off">
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-bold">Nomor WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0">+62</span>
                            <input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" placeholder="812-3456-7890" autocomplete="off" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="submit" name="add_staff" class="btn btn-primary w-100 py-2 fw-bold">Daftarkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL ADD PASIEN — SAMA DENGAN REGISTRASI AWAL -->
    <div class="modal fade" id="mAddPasien" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content border-0 shadow-lg admin-patient-form" style="border-radius: 20px;" method="POST" novalidate>
                <div class="modal-header bg-primary text-white border-0 py-4">
                    <h5 class="fw-bold mb-0">Registrasi Pasien</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold text-primary mb-3">Data Akun</h6>
                    <div class="alert alert-info small mb-3">Username dan email dibuat otomatis dari NIM/NIP/NIK dengan format <b>nomor_identitas@polytechnic.astar.ac.id</b>.</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><label class="small fw-bold">Username Otomatis</label><input type="text" class="form-control bg-light border-0 patient-account-preview" value="-" readonly></div>
                        <div class="col-md-6"><label class="small fw-bold">Email Otomatis</label><input type="text" class="form-control bg-light border-0 patient-account-preview" value="-" readonly></div>
                        <div class="col-12"><input type="text" name="password" class="form-control bg-light border-0" placeholder="Password minimal 8 karakter" minlength="8" maxlength="72" required></div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3">Data Pasien</h6>
                    <div class="row g-3">
                        <div class="col-md-6"><input type="text" name="nama_pasien" class="form-control bg-light border-0" placeholder="Nama lengkap" minlength="3" maxlength="100" required></div>
                        <div class="col-md-6"><input type="text" name="no_identitas" class="form-control bg-light border-0 identity-numeric" placeholder="NIP / NIK hanya angka" inputmode="numeric" maxlength="30" required></div>
                        <div class="col-md-6">
                            <select name="jenis_kelamin" class="form-select bg-light border-0" required>
                                <option value="">Pilih jenis kelamin</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select name="kategori_pasien" class="form-select bg-light border-0 patient-category" required>
                                <option value="">Pilih kategori pasien</option>
                                <option value="Mahasiswa">Mahasiswa</option>
                                <option value="Pegawai">Pegawai</option>
                                <option value="Sigap">Personel Sigap</option>
                                <option value="Virtus">Personel Virtus</option>
                                <option value="Tamu">Tamu Umum / Lain-lain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" placeholder="812-3456-7890" required></div>
                        </div>
                        <div class="col-md-6"><input type="text" name="alamat" class="form-control bg-light border-0" placeholder="Alamat" maxlength="255" required></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="submit" name="add_pasien" class="btn btn-primary w-100 py-2 fw-bold">Daftarkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL ADD SUPPLIER -->
    <div class="modal fade" id="mAddSupplier" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg admin-supplier-form" style="border-radius: 20px;" method="POST" novalidate><div class="modal-header bg-primary text-white border-0 py-4"><h5 class="fw-bold mb-0">Tambah Pemasok</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4">
        <input type="text" name="nama_supplier" class="form-control mb-3 bg-light border-0" placeholder="Nama Pemasok" required>
        <div class="input-group mb-3"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="kontak" class="form-control bg-light border-0 phone-mask" placeholder="812-3456-7890"></div>
        <input type="text" name="alamat" class="form-control mb-3 bg-light border-0" placeholder="Alamat">
    </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="add_supplier" class="btn btn-primary w-100 py-2 fw-bold">Simpan Pemasok</button></div></form></div></div>

    <!-- MODAL EDIT USER -->
    <?php
    mysqli_data_seek($u_list, 0);
    while ($u = mysqli_fetch_assoc($u_list)):
        $linkedPatient = !empty($u['linked_pasien_id']);
        $linkedStaff = !empty($u['linked_staff_id']);
        $linkedProfile = $linkedPatient || $linkedStaff;
    ?>
    <div class="modal fade" id="mEditU<?= $u[
        "id_user"
    ] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg admin-user-form" style="border-radius: 20px;" method="POST" novalidate>
        <div class="modal-header bg-warning border-0 py-4"><h5>Edit Akun</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <input type="hidden" name="id_user" value="<?= $u["id_user"] ?>">
            <label class="small fw-bold">Username</label><input type="text" name="username" class="form-control account-username-input mb-3 bg-light border-0" value="<?= e($u['username']) ?>" placeholder="Contoh: zeidalrayan" required>
            <label class="small fw-bold">Email</label><input type="email" name="email" class="form-control mb-2 bg-light border-0" value="<?= e($u['email']) ?>" placeholder="contoh@polytechnic.astar.ac.id" required>
            <div class="form-text mb-3">Username dan email dapat diubah. Relasi profil tetap aman karena menggunakan ID akun.</div>
            <label class="small fw-bold">Password</label>
            <?php $editablePassword = isHashedPassword((string) $u["password"]) ? "" : (string) $u["password"]; ?>
            <input type="text" name="password" class="form-control mb-1 bg-light border-0" value="<?= htmlspecialchars($editablePassword, ENT_QUOTES, "UTF-8") ?>" placeholder="<?= $editablePassword === "" ? "Masukkan password baru untuk mereset akun" : "Password akun" ?>" minlength="8" maxlength="72" autocomplete="off">
            <?php if ($editablePassword === ""): ?>
                <div class="form-text mb-3 text-warning">Password lama masih berupa hash dan tidak dapat dibaca. Isi password baru untuk meresetnya.</div>
            <?php else: ?>
                <div class="form-text mb-3">Password dapat dilihat dan diubah langsung oleh admin.</div>
            <?php endif; ?>
            <label class="small fw-bold">Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-control person-name-input mb-3 bg-light border-0" value="<?= e($u["nama_lengkap"]) ?>" placeholder="Masukkan nama lengkap" required>
            <label class="small fw-bold">Role</label>
            <?php if ($linkedPatient): ?>
                <input type="hidden" name="role" value="Pasien">
                <input type="text" class="form-control bg-light border-0" value="Pasien" readonly>
            <?php elseif ($u['role'] === 'Admin'): ?>
                <input type="hidden" name="role" value="Admin">
                <input type="text" class="form-control bg-light border-0" value="Admin" readonly>
            <?php elseif ($linkedStaff): ?>
                <select name="role" class="form-select bg-light border-0" required>
                    <option value="Dokter" <?= $u['role'] === 'Dokter' ? 'selected' : '' ?>>Dokter</option>
                    <option value="K3" <?= $u['role'] === 'K3' ? 'selected' : '' ?>>K3</option>
                </select>
            <?php else: ?>
                <select name="role" class="form-select bg-light border-0" required>
                    <option value="Dokter" <?= $u['role'] === 'Dokter' ? 'selected' : '' ?>>Dokter</option>
                    <option value="K3" <?= $u['role'] === 'K3' ? 'selected' : '' ?>>K3</option>
                    <option value="Pasien" <?= $u['role'] === 'Pasien' ? 'selected' : '' ?>>Pasien</option>
                    <option value="Vendor" <?= $u['role'] === 'Vendor' ? 'selected' : '' ?>>Vendor</option>
                </select>
            <?php endif; ?>
        </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="update_user" class="btn btn-primary w-100 py-2 fw-bold">Update</button></div>
    </form></div></div>
    <?php endwhile;
    ?>

    <!-- MODAL EDIT SUPPLIER -->
    <?php
    mysqli_data_seek($sup_list, 0);
    while ($sup = mysqli_fetch_assoc($sup_list)): ?>
    <div class="modal fade" id="mEditSup<?= $sup[
        "id_supplier"
    ] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg admin-supplier-form" style="border-radius: 20px;" method="POST" novalidate>
        <div class="modal-header bg-warning border-0 py-4"><h5>Edit Pemasok</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <input type="hidden" name="id_supplier" value="<?= $sup[
                "id_supplier"
            ] ?>">
            <input type="text" name="nama_supplier" class="form-control mb-2 bg-light border-0" required value="<?= htmlspecialchars(
                $sup["nama_supplier"],
            ) ?>">
            <input type="text" name="alamat" class="form-control mb-2 bg-light border-0" value="<?= htmlspecialchars(
                $sup["alamat"] ?? "",
            ) ?>">
            <div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="kontak" class="form-control bg-light border-0 phone-mask" value="<?= e(phoneInputValue($sup["kontak"] ?? '')) ?>" placeholder="812-3456-7890"></div>
        </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="update_supplier" class="btn btn-primary w-100 py-2 fw-bold">Update</button></div>
    </form></div></div>
    <?php endwhile;
    ?>

    <!-- MODAL EDIT STAFF -->
    <?php
    mysqli_data_seek($s_list, 0);
    while ($s = mysqli_fetch_assoc($s_list)):
        $staffRole = in_array((string) ($s['role_akun'] ?? ''), ['Dokter', 'Admin', 'K3'], true)
            ? (string) $s['role_akun']
            : 'Dokter';
    ?>
    <div class="modal fade" id="mEditS<?= e($s["id_staff"]) ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 shadow-lg staff-account-form" style="border-radius: 20px;" method="POST" novalidate>
                <div class="modal-header bg-warning border-0 py-4">
                    <h5 class="fw-bold mb-0">Edit Staf</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_staff" value="<?= e($s["id_staff"]) ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NIP</label>
                        <input type="text" name="no_identitas" class="form-control bg-light border-0 staff-identity-numeric" inputmode="numeric" value="<?= e($s['no_identitas']) ?>" placeholder="Masukkan NIP, hanya angka" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama dan Gelar</label>
                        <input type="text" name="nama_lengkap" class="form-control person-name-input bg-light border-0" value="<?= e($s['nama_lengkap']) ?>" placeholder="Contoh: Ike Indahwati, dr." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Role Akun</label>
                        <select name="role_akun" class="form-select bg-light border-0" required>
                            <option value="Dokter" <?= $staffRole === 'Dokter' ? 'selected' : '' ?>>Dokter</option>
                            <option value="Admin" <?= $staffRole === 'Admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="K3" <?= $staffRole === 'K3' ? 'selected' : '' ?>>Tim K3</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jabatan</label>
                        <input type="text" name="jabatan" class="form-control bg-light border-0" value="<?= e($s['jabatan']) ?>" placeholder="Contoh: Dokter UKK" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Instansi</label>
                        <select name="instansi" class="form-select bg-light border-0" required>
                            <option value="Kampus" <?= $s['instansi'] === 'Kampus' ? 'selected' : '' ?>>Kampus</option>
                            <option value="Siloam" <?= $s['instansi'] === 'Siloam' ? 'selected' : '' ?>>Siloam</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NPA IDI <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="npa_idi" class="form-control bg-light border-0" value="<?= e($s['npa_idi'] ?? '') ?>" placeholder="Masukkan NPA IDI jika ada">
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-bold">Nomor WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0">+62</span>
                            <input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" value="<?= e(phoneInputValue($s['no_hp'] ?? '')) ?>" placeholder="812-3456-7890" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="submit" name="update_staff" class="btn btn-primary w-100 py-2 fw-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endwhile; ?>

    <!-- MODAL EDIT PASIEN — AKUN DAN PROFIL DALAM SATU FORM -->
    <?php
    mysqli_data_seek($p_list, 0);
    while ($p = mysqli_fetch_assoc($p_list)): ?>
    <div class="modal fade" id="mEditP<?= e($p["id_pasien"]) ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content border-0 shadow-lg admin-patient-form" style="border-radius: 20px;" method="POST" novalidate>
                <div class="modal-header bg-warning border-0 py-4">
                    <h5 class="fw-bold mb-0">Edit Pasien</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_pasien" value="<?= e($p["id_pasien"]) ?>">
                    <input type="hidden" name="id_user" value="<?= e($p["id_user"]) ?>">

                    <h6 class="fw-bold text-primary mb-3">Data Akun</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><label class="small fw-bold">Username Otomatis</label><input type="text" class="form-control bg-light border-0 patient-account-preview" value="<?= e(accountFromIdentity((string) $p['no_identitas'])) ?>" readonly></div>
                        <div class="col-md-6"><label class="small fw-bold">Email Otomatis</label><input type="text" class="form-control bg-light border-0 patient-account-preview" value="<?= e(accountFromIdentity((string) $p['no_identitas'])) ?>" readonly></div>
                        <div class="col-12"><label class="small fw-bold">Password</label><input type="text" name="password" class="form-control bg-light border-0" value="<?= e($p['password'] ?? '') ?>" placeholder="Kosongkan jika tidak ingin mengubah password" maxlength="72"></div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3">Data Pasien</h6>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="small fw-bold">Nama Lengkap</label><input type="text" name="nama_pasien" class="form-control bg-light border-0" value="<?= e($p["nama_pasien"]) ?>" minlength="3" maxlength="100" required></div>
                        <div class="col-md-6"><label class="small fw-bold">NIP / NIK</label><input type="text" name="no_identitas" class="form-control bg-light border-0 identity-numeric" value="<?= e($p["no_identitas"]) ?>" inputmode="numeric" maxlength="30" required></div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select bg-light border-0" required>
                                <option value="L" <?= $p["jenis_kelamin"] === "L" ? "selected" : "" ?>>Laki-laki</option>
                                <option value="P" <?= $p["jenis_kelamin"] === "P" ? "selected" : "" ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Kategori Pasien</label>
                            <select name="kategori_pasien" class="form-select bg-light border-0 patient-category" required>
                                <option value="Mahasiswa" <?= $p["kategori_pasien"] === "Mahasiswa" ? "selected" : "" ?>>Mahasiswa</option>
                                <option value="Pegawai" <?= $p["kategori_pasien"] === "Pegawai" ? "selected" : "" ?>>Pegawai</option>
                                <option value="Sigap" <?= $p["kategori_pasien"] === "Sigap" ? "selected" : "" ?>>Personel Sigap</option>
                                <option value="Virtus" <?= $p["kategori_pasien"] === "Virtus" ? "selected" : "" ?>>Personel Virtus</option>
                                <option value="Tamu" <?= $p["kategori_pasien"] === "Tamu" ? "selected" : "" ?>>Tamu Umum / Lain-lain</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="small fw-bold">Nomor WhatsApp</label><div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" value="<?= e(phoneInputValue($p["no_hp"] ?? '')) ?>" placeholder="812-3456-7890" required></div></div>
                        <div class="col-md-6"><label class="small fw-bold">Alamat</label><input type="text" name="alamat" class="form-control bg-light border-0" value="<?= e($p["alamat"] ?? '') ?>" maxlength="255" required></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="submit" name="update_pasien" class="btn btn-primary w-100 py-2 fw-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endwhile; ?>

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Nama pada Akun Pengguna dan Tim Pengelola hanya menerima huruf.
        document.querySelectorAll('.person-name-input').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = input.value.replace(/[0-9]/g, '');
            });
        });

        document.querySelectorAll('.identity-numeric').forEach(function (input) {
            input.addEventListener('input', function () {
                const form = input.closest('form');
                const category = form?.querySelector('.patient-category')?.value || '';
                const limit = category === 'Tamu' ? 16 : 30;
                input.value = input.value.replace(/\D/g, '').slice(0, limit);
            });
        });

        document.querySelectorAll('.patient-category').forEach(function (select) {
            select.addEventListener('change', function () {
                const identity = select.closest('form')?.querySelector('.identity-numeric');
                if (!identity) return;
                identity.maxLength = select.value === 'Tamu' ? 16 : 30;
                identity.value = identity.value.replace(/\D/g, '').slice(0, identity.maxLength);
                identity.placeholder = select.value === 'Tamu'
                    ? 'NIK wajib tepat 16 angka'
                    : (select.value === 'Mahasiswa' ? 'NIM minimal 3 angka' : 'NIP / Identitas minimal 3 angka');
            });
        });

        document.querySelectorAll('.staff-identity-numeric').forEach(function (identity) {
            identity.addEventListener('input', function () {
                identity.value = identity.value.replace(/\D/g, '').slice(0, 30);
            });
        });

        document.querySelectorAll('.admin-patient-form').forEach(function (form) {
            const identityField = form.querySelector('.identity-numeric');
            const previews = form.querySelectorAll('.patient-account-preview');

            const updateAccountPreview = function () {
                if (!identityField) return;
                const identity = identityField.value.replace(/\D/g, '');
                const account = identity ? identity + '@polytechnic.astar.ac.id' : '-';
                previews.forEach(function (field) { field.value = account; });
            };

            identityField?.addEventListener('input', updateAccountPreview);
            updateAccountPreview();

            form.addEventListener('submit', function (event) {
                const required = Array.from(form.querySelectorAll('[required]'));
                const invalid = required.filter(function (field) {
                    return field.value.trim() === '';
                });
                const identity = form.querySelector('.identity-numeric');
                const category = form.querySelector('.patient-category')?.value || '';
                if (identity && ((category === 'Tamu' && identity.value.length !== 16) || (category !== 'Tamu' && identity.value.length < 3))) {
                    invalid.push(identity);
                }
                if (invalid.length === 0) return;
                event.preventDefault();
                invalid.forEach(function (field) { field.classList.add('is-invalid'); });
                Swal.fire({
                    icon: 'warning',
                    title: 'Ada Input Kosong atau Salah',
                    text: 'Silakan periksa kembali data pasien.',
                    confirmButtonText: 'Oke',
                    confirmButtonColor: '#0d6efd'
                }).then(function () { invalid[0]?.focus(); });
            });
        });    });
    </script>
    <?php include dirname(__DIR__) . '/includes/sweetalert_global.php'; ?>
    <script>
    (function () {
        function markInvalid(field, invalid) {
            field.classList.toggle('is-invalid', Boolean(invalid));
        }

        function showValidationPopup(title, text, firstField) {
            Swal.fire({
                icon: 'warning',
                title: title,
                text: text,
                confirmButtonText: 'Oke',
                confirmButtonColor: '#175cdd'
            }).then(function () {
                if (firstField) firstField.focus();
            });
        }

        document.querySelectorAll('.account-username-input, .person-name-input').forEach(function (field) {
            field.addEventListener('beforeinput', function (event) {
                if (event.data && /\d/.test(event.data)) {
                    event.preventDefault();
                }
            });
            field.addEventListener('paste', function (event) {
                const pasted = (event.clipboardData || window.clipboardData).getData('text');
                if (/\d/.test(pasted)) {
                    event.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Input Tidak Sesuai',
                        text: 'Username dan nama tidak boleh mengandung angka.',
                        confirmButtonText: 'Oke',
                        confirmButtonColor: '#175cdd'
                    });
                }
            });
        });

        document.querySelectorAll('.admin-user-form').forEach(function (form) {
            form.querySelectorAll('input, select').forEach(function (field) {
                field.addEventListener('input', function () { markInvalid(field, false); });
                field.addEventListener('change', function () { markInvalid(field, false); });
            });

            form.addEventListener('submit', function (event) {
                const requiredFields = Array.from(form.querySelectorAll('[required]'));
                const emptyFields = requiredFields.filter(function (field) {
                    return String(field.value || '').trim() === '';
                });

                requiredFields.forEach(function (field) {
                    markInvalid(field, emptyFields.includes(field));
                });

                if (emptyFields.length > 0) {
                    event.preventDefault();
                    showValidationPopup('Ada Input Kosong', 'Silakan isi terlebih dahulu.', emptyFields[0]);
                    return;
                }

                const username = form.querySelector('input[name="username"]');
                const email = form.querySelector('input[name="email"]');
                const password = form.querySelector('input[name="password"]');
                const personName = form.querySelector('input[name="nama_lengkap"]');
                const invalidFields = [];

                if (username && (/\d/.test(username.value) || /\s/.test(username.value))) {
                    markInvalid(username, true);
                    invalidFields.push(username);
                }
                if (personName && /\d/.test(personName.value)) {
                    markInvalid(personName, true);
                    invalidFields.push(personName);
                }
                if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                    markInvalid(email, true);
                    invalidFields.push(email);
                }
                if (password && password.value !== '' && password.value.length < 8) {
                    markInvalid(password, true);
                    invalidFields.push(password);
                }

                if (invalidFields.length > 0) {
                    event.preventDefault();
                    showValidationPopup('Ada Input yang Salah', 'Username dan nama tidak boleh mengandung angka. Username juga tidak boleh memakai spasi. Pastikan format email benar dan password minimal 8 karakter.', invalidFields[0]);
                }
            });
        });

        document.querySelectorAll('.staff-account-form').forEach(function (form) {
            form.querySelectorAll('input, select').forEach(function (field) {
                field.addEventListener('input', function () { markInvalid(field, false); });
                field.addEventListener('change', function () { markInvalid(field, false); });
            });

            form.addEventListener('submit', function (event) {
                const requiredFields = Array.from(form.querySelectorAll('[required]'));
                const emptyFields = requiredFields.filter(function (field) {
                    return String(field.value || '').trim() === '';
                });

                requiredFields.forEach(function (field) {
                    markInvalid(field, emptyFields.includes(field));
                });

                const nip = form.querySelector('input[name="no_identitas"]');
                const name = form.querySelector('input[name="nama_lengkap"]');
                const phone = form.querySelector('input[name="no_hp"]');
                const invalidFields = [];

                if (nip && !/^\d{3,30}$/.test(nip.value.trim())) {
                    markInvalid(nip, true);
                    invalidFields.push(nip);
                }
                if (name && /\d/.test(name.value)) {
                    markInvalid(name, true);
                    invalidFields.push(name);
                }
                if (phone) {
                    const digits = phone.value.replace(/\D/g, '');
                    if (digits.length < 9 || digits.length > 15) {
                        markInvalid(phone, true);
                        invalidFields.push(phone);
                    }
                }

                const allInvalid = Array.from(new Set(emptyFields.concat(invalidFields)));
                if (allInvalid.length === 0) return;

                event.preventDefault();
                if (emptyFields.length > 0) {
                    showValidationPopup('Ada Input Kosong', 'Silakan isi semua data wajib terlebih dahulu.', allInvalid[0]);
                } else {
                    showValidationPopup('Input Tidak Sesuai', 'Periksa kembali NIP, nama staf, dan nomor WhatsApp.', allInvalid[0]);
                }
            });
        });

        document.querySelectorAll('.admin-supplier-form').forEach(function (form) {
            form.querySelectorAll('input').forEach(function (field) {
                field.addEventListener('input', function () { markInvalid(field, false); });
            });

            form.addEventListener('submit', function (event) {
                const name = form.querySelector('input[name="nama_supplier"]');
                if (name && name.value.trim() === '') {
                    event.preventDefault();
                    markInvalid(name, true);
                    showValidationPopup('Ada Input Kosong', 'Silakan isi terlebih dahulu.', name);
                }
            });
        });
    })();
    </script>
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            document.getElementById('digitalClock').innerText = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000); updateClock();

        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-toggled');
        });

        document.querySelectorAll('.phone-mask').forEach(input => {
            const applyPhoneMask = (target) => {
                const digits = target.value.replace(/\D/g, '').substring(0, 11);
                let formatted = digits.substring(0, 3);
                if (digits.length > 3) formatted += '-' + digits.substring(3, 7);
                if (digits.length > 7) formatted += '-' + digits.substring(7, 11);
                target.value = formatted;
            };

            applyPhoneMask(input);
            input.addEventListener('input', event => applyPhoneMask(event.target));
        });

// Konfigurasi Area Chart (Earnings Overview)
// 1. Konfigurasi Line Chart (Statistik Sakit)
const ctxSick = document.getElementById("sickChart");
if (ctxSick) new Chart(ctxSick, {
    type: 'line',
    data: {
        labels: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"],
        datasets: [{
            label: "Jumlah Kunjungan",
            lineTension: 0.3,
            backgroundColor: "rgba(78, 115, 223, 0.05)",
            borderColor: "rgba(78, 115, 223, 1)",
            pointRadius: 4,
            pointBackgroundColor: "rgba(78, 115, 223, 1)",
            data: <?= json_encode($line_chart_values) ?>,
        }],
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// 2. Konfigurasi Donut Chart (Kategori Pasien)
const ctxCategory = document.getElementById("categoryDonutChart");
if (ctxCategory) new Chart(ctxCategory, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($donut_labels) ?>,
        datasets: [{
            data: <?= json_encode($donut_values) ?>,
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
            hoverOffset: 4
        }],
    },
    options: {
        maintainAspectRatio: false,
        cutout: '75%',
        plugins: { legend: { display: false } }
    }
});



    </script>
    <?php include dirname(__DIR__) . '/includes/form_ui_global.php'; ?>
    <?php include dirname(__DIR__) . '/includes/table_ui_global.php'; ?>
    <?php include dirname(__DIR__) . '/includes/pagination_global.php'; ?>
<?php include dirname(__DIR__) . '/includes/login_success_popup.php'; ?>
</body>
    </html>