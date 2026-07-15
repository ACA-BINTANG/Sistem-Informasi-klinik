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

function generateID($prefix)
{
    return $prefix . substr(str_shuffle("0123456789"), 0, 3);
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
// HELPER: SINKRONISASI KE TABEL userm
// Dipanggil setiap kali data di staffm/pasienm diupdate,
// supaya username/email/nama di userm ikut berubah.
// ==========================================
function syncToUser($conn, $id_user, $nama_lengkap, $identitas = null)
{
    if ($identitas !== null) {
        $new_email = $identitas . "@polytechnic.astar.ac.id";
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE userm SET username=?, email=?, nama_lengkap=? WHERE id_user=?",
        );
        mysqli_stmt_bind_param(
            $stmt,
            "ssss",
            $identitas,
            $new_email,
            $nama_lengkap,
            $id_user,
        );
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE userm SET nama_lengkap=? WHERE id_user=?",
        );
        mysqli_stmt_bind_param($stmt, "ss", $nama_lengkap, $id_user);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Ambil id_user terkait dari staffm / pasienm
function getUserIdFrom($conn, $table, $keyCol, $keyVal)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_user FROM $table WHERE $keyCol = ?",
    );
    mysqli_stmt_bind_param($stmt, "s", $keyVal);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ? $row["id_user"] : null;
}

// ==========================================
// LOGIKA CRUD
// ==========================================

// 1. TAMBAH USER MANUAL
if (isset($_POST["add_user"])) {
    $id = generateID("USR");
    $un = trim((string) ($_POST["username"] ?? ""));
    $em = trim((string) ($_POST["email"] ?? ""));
    $ps = (string) ($_POST["password"] ?? "");
    $rl = trim((string) ($_POST["role"] ?? ""));
    $nm = trim((string) ($_POST["nama_lengkap"] ?? ""));

    if ($un === '' || $em === '' || $ps === '' || $rl === '' || $nm === '') {
        header('Location: index.php?page=user&err=' . urlencode('Ada input kosong. Silakan isi terlebih dahulu.'));
        exit();
    }
    if (!filter_var($em, FILTER_VALIDATE_EMAIL) || strlen($ps) < 8) {
        header('Location: index.php?page=user&err=' . urlencode('Ada input yang salah. Silakan periksa kembali data akun.'));
        exit();
    }

    $cek = mysqli_prepare($conn, "SELECT 1 FROM userm WHERE username = ? OR email = ? LIMIT 1");
    mysqli_stmt_bind_param($cek, "ss", $un, $em);
    mysqli_stmt_execute($cek);
    $exists = mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0;
    mysqli_stmt_close($cek);

    if ($exists) {
        header('Location: index.php?page=user&err=' . urlencode('Ada data akun yang sudah digunakan. Gunakan data lain lalu coba kembali.'));
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO userm (id_user, username, email, password, role, nama_lengkap, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW(6))",
    );
    mysqli_stmt_bind_param($stmt, "ssssss", $id, $un, $em, $ps, $rl, $nm);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: index.php?page=user&' . ($saved
        ? 'msg=' . urlencode('User berhasil ditambah.')
        : 'err=' . urlencode('User gagal ditambah. Silakan coba kembali.')));
    exit();
}

// 2. TAMBAH STAFF + AUTO AKUN SSO
if (isset($_POST["add_staff"])) {
    $nip = $_POST["no_identitas"];
    $nama = $_POST["nama_lengkap"];
    $role = $_POST["role_akun"];
    $jbt = $_POST["jabatan"];
    $ins = $_POST["instansi"];
    $npa = $_POST["npa_idi"];
    $hp = $_POST["no_hp"];

    $id_u = generateID("USR");
    $id_s = generateID("STF");
    $username_sso = $nip . "@polytechnic.astar.ac.id";
    $nama_depan = strtolower(explode(" ", trim($nama))[0]);
    $password_staff_plain = $nama_depan . "123";
    $pass_staff = $password_staff_plain;

    $stmt1 = mysqli_prepare(
        $conn,
        "INSERT INTO userm (id_user, username, email, password, role, nama_lengkap, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW(6))",
    );
    mysqli_stmt_bind_param(
        $stmt1,
        "ssssss",
        $id_u,
        $username_sso,
        $username_sso,
        $pass_staff,
        $role,
        $nama,
    );
    mysqli_stmt_execute($stmt1);
    mysqli_stmt_close($stmt1);

    $stmt2 = mysqli_prepare(
        $conn,
        "INSERT INTO staffm (id_staff, id_user, nama_lengkap, no_identitas, jabatan, instansi, npa_idi, no_hp, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(6))",
    );
    mysqli_stmt_bind_param(
        $stmt2,
        "ssssssss",
        $id_s,
        $id_u,
        $nama,
        $nip,
        $jbt,
        $ins,
        $npa,
        $hp,
    );
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    header(
        "Location: index.php?page=staff&msg=Staf berhasil didaftarkan",
    );
    exit();
}

// 3. TAMBAH PASIEN — FORM ADMIN DISINKRONKAN DENGAN REGISTRASI AWAL
if (isset($_POST["add_pasien"])) {
    $id_u = generateID("USR");
    $id_p = generateID("PSN");

    $username = trim((string) ($_POST["username"] ?? ""));
    $email = trim((string) ($_POST["email"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");
    $identitas = preg_replace('/\D+/', '', (string) ($_POST["no_identitas"] ?? ""));
    $nama = trim((string) ($_POST["nama_pasien"] ?? ""));
    $jk = (string) ($_POST["jenis_kelamin"] ?? "");
    $kat = (string) ($_POST["kategori_pasien"] ?? "");
    $alm = trim((string) ($_POST["alamat"] ?? ""));
    $hpDigits = preg_replace('/\D+/', '', (string) ($_POST["no_hp"] ?? ""));
    $hpDigits = preg_replace('/^(62|0)+/', '', $hpDigits ?? '');
    $hp = $hpDigits !== '' ? '+62' . $hpDigits : '';
    $unitProdi = '';

    $errorsPasien = [];
    if ($username === '' || strlen($username) < 3 || !preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
        $errorsPasien[] = 'Username minimal 3 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau minus.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorsPasien[] = 'Format email belum benar.';
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
    if (!in_array($kat, ['Sigap', 'Virtus', 'Tamu'], true)) {
        $errorsPasien[] = 'Kategori pasien belum dipilih.';
    }
    if ($identitas === '' || ($kat === 'Tamu' && strlen($identitas) !== 16) || ($kat !== 'Tamu' && (strlen($identitas) < 3 || strlen($identitas) > 30))) {
        $errorsPasien[] = $kat === 'Tamu'
            ? 'NIK Tamu Umum / Lain-lain harus tepat 16 angka.'
            : 'NIP harus berisi minimal 3 dan maksimal 30 angka.';
    }
    if (!preg_match('/^8\d{8,12}$/', $hpDigits ?? '')) {
        $errorsPasien[] = 'Nomor WhatsApp harus dimulai angka 8 setelah +62.';
    }
    if ($alm === '' || strlen($alm) < 5) {
        $errorsPasien[] = 'Alamat wajib diisi minimal 5 karakter.';
    }

    if ($errorsPasien !== []) {
        header('Location: index.php?page=pasien&err=' . urlencode(count($errorsPasien) === 1 ? $errorsPasien[0] : 'Ada beberapa input pasien yang salah. Silakan periksa kembali.'));
        exit();
    }

    $stmtCek = mysqli_prepare(
        $conn,
        "SELECT
            EXISTS(SELECT 1 FROM userm WHERE username = ?) AS username_exists,
            EXISTS(SELECT 1 FROM userm WHERE email = ?) AS email_exists,
            EXISTS(SELECT 1 FROM pasienm WHERE no_identitas = ?) AS identity_exists"
    );
    mysqli_stmt_bind_param($stmtCek, 'sss', $username, $email, $identitas);
    mysqli_stmt_execute($stmtCek);
    $duplicate = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCek)) ?: [];
    mysqli_stmt_close($stmtCek);

    if ((int) ($duplicate['username_exists'] ?? 0) === 1 || (int) ($duplicate['email_exists'] ?? 0) === 1) {
        header('Location: index.php?page=pasien&err=' . urlencode('Ada data akun yang sudah digunakan. Gunakan data lain lalu coba kembali.'));
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
        mysqli_stmt_bind_param($stmt1, 'sssss', $id_u, $username, $email, $password, $nama);
        mysqli_stmt_execute($stmt1);
        mysqli_stmt_close($stmt1);

        $stmt2 = mysqli_prepare(
            $conn,
            "INSERT INTO pasienm (id_pasien, id_user, no_identitas, nama_pasien, jenis_kelamin, kategori_pasien, unit_prodi, alamat, no_hp, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(6))"
        );
        mysqli_stmt_bind_param($stmt2, 'sssssssss', $id_p, $id_u, $identitas, $nama, $jk, $kat, $unitProdi, $alm, $hp);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        mysqli_commit($conn);
        header('Location: index.php?page=pasien&msg=' . urlencode('Pasien dan akun berhasil dibuat.'));
        exit();
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        error_log('Tambah pasien admin gagal: ' . $exception->getMessage());
        header('Location: index.php?page=pasien&err=' . urlencode('Data pasien gagal disimpan. Periksa kembali data akun.'));
        exit();
    }
}

// 4. UPDATE USER (ikut sync ke staffm & pasienm)
if (isset($_POST["update_user"])) {
    $id = trim((string) ($_POST["id_user"] ?? ""));
    $un = trim((string) ($_POST["username"] ?? ""));
    $em = trim((string) ($_POST["email"] ?? ""));
    $newPassword = (string) ($_POST["password"] ?? "");
    $rl = trim((string) ($_POST["role"] ?? ""));
    $nm = trim((string) ($_POST["nama_lengkap"] ?? ""));

    if ($id === '' || $un === '' || $em === '' || $rl === '' || $nm === '') {
        header('Location: index.php?page=user&err=' . urlencode('Ada input kosong. Silakan isi terlebih dahulu.'));
        exit();
    }
    if (!filter_var($em, FILTER_VALIDATE_EMAIL) || ($newPassword !== '' && strlen($newPassword) < 8)) {
        header('Location: index.php?page=user&err=' . urlencode('Ada input yang salah. Silakan periksa kembali data akun.'));
        exit();
    }

    $cek = mysqli_prepare($conn, "SELECT 1 FROM userm WHERE (username = ? OR email = ?) AND id_user <> ? LIMIT 1");
    mysqli_stmt_bind_param($cek, "sss", $un, $em, $id);
    mysqli_stmt_execute($cek);
    $exists = mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0;
    mysqli_stmt_close($cek);

    if ($exists) {
        header('Location: index.php?page=user&err=' . urlencode('Ada data akun yang sudah digunakan. Gunakan data lain lalu coba kembali.'));
        exit();
    }

    // Jika kolom password kosong, password lama dipertahankan.
    if ($newPassword !== "") {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE userm SET username=?, email=?, password=?, role=?, nama_lengkap=? WHERE id_user=?",
        );
        mysqli_stmt_bind_param($stmt, "ssssss", $un, $em, $newPassword, $rl, $nm, $id);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE userm SET username=?, email=?, role=?, nama_lengkap=? WHERE id_user=?",
        );
        mysqli_stmt_bind_param($stmt, "sssss", $un, $em, $rl, $nm, $id);
    }

    $updated = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($updated) {
        // Sinkronkan nama saja. Username akun tidak boleh mengubah NIP/NIK pasien atau staff.
        $stmtS = mysqli_prepare($conn, "UPDATE staffm SET nama_lengkap=? WHERE id_user=?");
        mysqli_stmt_bind_param($stmtS, "ss", $nm, $id);
        mysqli_stmt_execute($stmtS);
        mysqli_stmt_close($stmtS);

        $stmtP = mysqli_prepare($conn, "UPDATE pasienm SET nama_pasien=? WHERE id_user=?");
        mysqli_stmt_bind_param($stmtP, "ss", $nm, $id);
        mysqli_stmt_execute($stmtP);
        mysqli_stmt_close($stmtP);
    }

    header('Location: index.php?page=user&' . ($updated
        ? 'msg=' . urlencode('Akun dan data terkait berhasil diperbarui.')
        : 'err=' . urlencode('Akun gagal diperbarui. Silakan coba kembali.')));
    exit();
}

// 5. UPDATE STAFF (ikut sync ke userm lewat syncToUser)
if (isset($_POST["update_staff"])) {
    $id_s = $_POST["id_staff"];
    $nm_l = $_POST["nama_lengkap"];
    $no_i = $_POST["no_identitas"];
    $jbt = $_POST["jabatan"];
    $ins = $_POST["instansi"];
    $npa = $_POST["npa_idi"];
    $hp = $_POST["no_hp"];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE staffm SET nama_lengkap=?, no_identitas=?, jabatan=?, instansi=?, npa_idi=?, no_hp=? WHERE id_staff=?",
    );
    mysqli_stmt_bind_param(
        $stmt,
        "sssssss",
        $nm_l,
        $no_i,
        $jbt,
        $ins,
        $npa,
        $hp,
        $id_s,
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $id_u = getUserIdFrom($conn, "staffm", "id_staff", $id_s);
    if ($id_u) {
        syncToUser($conn, $id_u, $nm_l, $no_i);
    }

    header(
        "Location: index.php?page=staff&msg=Data staf dan akun SSO berhasil diperbarui",
    );
    exit();
}

// 6. UPDATE PASIEN — DATA AKUN DAN PROFIL DISIMPAN BERSAMA
if (isset($_POST["update_pasien"])) {
    $id_p = trim((string) ($_POST["id_pasien"] ?? ""));
    $id_u_p = trim((string) ($_POST["id_user"] ?? ""));
    $username = trim((string) ($_POST["username"] ?? ""));
    $email = trim((string) ($_POST["email"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");
    $nm = trim((string) ($_POST["nama_pasien"] ?? ""));
    $nip = preg_replace('/\D+/', '', (string) ($_POST["no_identitas"] ?? ""));
    $jk = (string) ($_POST["jenis_kelamin"] ?? "");
    $kat = (string) ($_POST["kategori_pasien"] ?? "");
    $alm = trim((string) ($_POST["alamat"] ?? ""));
    $hpDigits = preg_replace('/\D+/', '', (string) ($_POST["no_hp"] ?? ""));
    $hpDigits = preg_replace('/^(62|0)+/', '', $hpDigits ?? '');
    $hp = $hpDigits !== '' ? '+62' . $hpDigits : '';

    $stmtDuplicate = mysqli_prepare(
        $conn,
        "SELECT
            EXISTS(SELECT 1 FROM userm WHERE username = ? AND id_user <> ?) AS username_exists,
            EXISTS(SELECT 1 FROM userm WHERE email = ? AND id_user <> ?) AS email_exists,
            EXISTS(SELECT 1 FROM pasienm WHERE no_identitas = ? AND id_pasien <> ?) AS identity_exists"
    );
    mysqli_stmt_bind_param($stmtDuplicate, 'ssssss', $username, $id_u_p, $email, $id_u_p, $nip, $id_p);
    mysqli_stmt_execute($stmtDuplicate);
    $duplicate = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtDuplicate)) ?: [];
    mysqli_stmt_close($stmtDuplicate);

    if ((int) ($duplicate['username_exists'] ?? 0) === 1 || (int) ($duplicate['email_exists'] ?? 0) === 1) {
        header('Location: index.php?page=pasien&err=' . urlencode('Ada data akun yang sudah digunakan. Gunakan data lain lalu coba kembali.'));
        exit();
    }
    if ((int) ($duplicate['identity_exists'] ?? 0) === 1) {
        header('Location: index.php?page=pasien&err=' . urlencode('Nomor identitas sudah digunakan.'));
        exit();
    }

    try {
        mysqli_begin_transaction($conn);

        if ($password !== '') {
            $stmtUser = mysqli_prepare($conn, "UPDATE userm SET username=?, email=?, password=?, nama_lengkap=? WHERE id_user=?");
            mysqli_stmt_bind_param($stmtUser, 'sssss', $username, $email, $password, $nm, $id_u_p);
        } else {
            $stmtUser = mysqli_prepare($conn, "UPDATE userm SET username=?, email=?, nama_lengkap=? WHERE id_user=?");
            mysqli_stmt_bind_param($stmtUser, 'ssss', $username, $email, $nm, $id_u_p);
        }
        mysqli_stmt_execute($stmtUser);
        mysqli_stmt_close($stmtUser);

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE pasienm SET nama_pasien=?, no_identitas=?, jenis_kelamin=?, kategori_pasien=?, unit_prodi='', alamat=?, no_hp=? WHERE id_pasien=?"
        );
        mysqli_stmt_bind_param($stmt, 'sssssss', $nm, $nip, $jk, $kat, $alm, $hp, $id_p);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        header('Location: index.php?page=pasien&msg=' . urlencode('Data pasien dan akun berhasil diperbarui.'));
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
    $kontak = trim((string) ($_POST["kontak"] ?? ""));

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
    $kontak = trim((string) ($_POST["kontak"] ?? ""));

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

// 7. HAPUS UNIVERSAL
// Dibatasi hanya untuk tabel & kolom yang memang boleh dihapus dari sini,
// supaya nama tabel/kolom tidak bisa disuntik lewat parameter GET.
if (isset($_GET["del"])) {
    $allowed = [
        "userm" => "id_user",
        "staffm" => "id_staff",
        "pasienm" => "id_pasien",
        "supplierm" => "id_supplier",
    ];
    $tabel = $_GET["t"];
    $kolom = $_GET["k"];
    $val = $_GET["del"];
    $pg = $_GET["page"];

    if (isset($allowed[$tabel]) && $allowed[$tabel] === $kolom) {
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
        $stmt = mysqli_prepare($conn, "DELETE FROM $tabel WHERE $kolom = ?");
        mysqli_stmt_bind_param($stmt, "s", $val);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    }

    header("Location: index.php?page=$pg&msg=Data Berhasil Dihapus");
    exit();
}

$u_list = mysqli_query(
    $conn,
    "SELECT id_user, username, email, password, role, nama_lengkap, created_at FROM userm ORDER BY created_at DESC, id_user DESC",
);
$s_list = mysqli_query($conn, "SELECT * FROM staffm ORDER BY created_at DESC, id_staff DESC");
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
    <div class="modal fade" id="mAddUser" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg admin-user-form" style="border-radius: 20px;" method="POST" novalidate><div class="modal-header bg-primary text-white border-0 py-4"><h5 class="fw-bold mb-0">Tambah Akun Baru</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4">
        <input type="text" name="username" class="form-control mb-3 bg-light border-0" placeholder="NIM/NIP" required>
        <input type="email" name="email" class="form-control mb-3 bg-light border-0" placeholder="Email" required>
        <input type="text" name="password" class="form-control mb-3 bg-light border-0" placeholder="Password (minimal 8 karakter)" minlength="8" maxlength="72" autocomplete="off" required>
        <input type="text" name="nama_lengkap" class="form-control mb-3 bg-light border-0" placeholder="Nama Lengkap" required>
        <select name="role" class="form-select bg-light border-0" required><option value="Admin">Admin</option><option value="Dokter">Dokter</option><option value="Pasien">Pasien</option><option value="K3">Tim K3</option></select>
    </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="add_user" class="btn btn-primary w-100 py-2 fw-bold">Simpan Akun</button></div></form></div></div>

    <!-- MODAL ADD STAFF -->
    <div class="modal fade" id="mAddStaff" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg" style="border-radius: 20px;" method="POST"><div class="modal-header bg-primary text-white border-0 py-4"><h5 class="fw-bold mb-0">Daftarkan Staf</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4">
        <input type="text" name="no_identitas" class="form-control mb-3 bg-light border-0" placeholder="NIP / NIK" required>
        <input type="text" name="nama_lengkap" class="form-control mb-3 bg-light border-0" placeholder="Nama & Gelar" required>
        <select name="role_akun" class="form-select mb-3 bg-light border-0" required><option value="Dokter">Dokter</option><option value="Admin">Admin</option><option value="K3">Tim K3</option></select>
        <input type="text" name="jabatan" class="form-control mb-3 bg-light border-0" placeholder="Jabatan" required>
        <select name="instansi" class="form-select mb-3 bg-light border-0"><option>Kampus</option><option>Siloam</option></select>
        <input type="text" name="npa_idi" class="form-control mb-3 bg-light border-0" placeholder="NPA IDI (Opsional)">
        <div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" placeholder="8xx-xxxx-xxxx" required></div>
    </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="add_staff" class="btn btn-primary w-100 py-2 fw-bold">Daftarkan</button></div></form></div></div>

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
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><input type="text" name="username" class="form-control bg-light border-0" placeholder="Username minimal 3 karakter" minlength="3" maxlength="50" required></div>
                        <div class="col-md-6"><input type="email" name="email" class="form-control bg-light border-0" placeholder="Email aktif" maxlength="100" required></div>
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
                                <option value="Sigap">Personel Sigap</option>
                                <option value="Virtus">Personel Virtus</option>
                                <option value="Tamu">Tamu Umum / Lain-lain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" placeholder="812-3456-7890" required></div>
                        </div>
                        <div class="col-md-6"><input type="text" name="alamat" class="form-control bg-light border-0" placeholder="Alamat minimal 5 karakter" minlength="5" maxlength="255" required></div>
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
        <div class="input-group mb-3"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="kontak" class="form-control bg-light border-0 phone-mask" placeholder="8xx-xxxx-xxxx"></div>
        <input type="text" name="alamat" class="form-control mb-3 bg-light border-0" placeholder="Alamat">
    </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="add_supplier" class="btn btn-primary w-100 py-2 fw-bold">Simpan Pemasok</button></div></form></div></div>

    <!-- MODAL EDIT USER -->
    <?php
    mysqli_data_seek($u_list, 0);
    while ($u = mysqli_fetch_assoc($u_list)): ?>
    <div class="modal fade" id="mEditU<?= $u[
        "id_user"
    ] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg admin-user-form" style="border-radius: 20px;" method="POST" novalidate>
        <div class="modal-header bg-warning border-0 py-4"><h5>Edit Akun</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <input type="hidden" name="id_user" value="<?= $u["id_user"] ?>">
            <label class="small fw-bold">Username</label><input type="text" name="username" class="form-control mb-3 bg-light border-0" value="<?= $u[
                "username"
            ] ?>" required>
            <label class="small fw-bold">Email</label><input type="email" name="email" class="form-control mb-3 bg-light border-0" value="<?= $u[
                "email"
            ] ?>" required>
            <label class="small fw-bold">Password</label>
            <?php $editablePassword = isHashedPassword((string) $u["password"]) ? "" : (string) $u["password"]; ?>
            <input type="text" name="password" class="form-control mb-1 bg-light border-0" value="<?= htmlspecialchars($editablePassword, ENT_QUOTES, "UTF-8") ?>" placeholder="<?= $editablePassword === "" ? "Masukkan password baru untuk mereset akun" : "Password akun" ?>" minlength="8" maxlength="72" autocomplete="off">
            <?php if ($editablePassword === ""): ?>
                <div class="form-text mb-3 text-warning">Password lama masih berupa hash dan tidak dapat dibaca. Isi password baru untuk meresetnya.</div>
            <?php else: ?>
                <div class="form-text mb-3">Password dapat dilihat dan diubah langsung oleh admin.</div>
            <?php endif; ?>
            <label class="small fw-bold">Nama</label><input type="text" name="nama_lengkap" class="form-control mb-3 bg-light border-0" value="<?= $u[
                "nama_lengkap"
            ] ?>" required>
            <select name="role" class="form-select bg-light border-0" required>
                <option value="Admin" <?= $u["role"] == "Admin" ? "selected" : "" ?>>Admin</option>
                <option value="Dokter" <?= $u["role"] == "Dokter" ? "selected" : "" ?>>Dokter</option>
                <option value="Pasien" <?= $u["role"] == "Pasien" ? "selected" : "" ?>>Pasien</option>
                <option value="K3" <?= $u["role"] == "K3" ? "selected" : "" ?>>K3</option>
                <option value="Vendor" <?= $u["role"] == "Vendor" ? "selected" : "" ?>>Vendor</option>
            </select>
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
            <div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="kontak" class="form-control bg-light border-0 phone-mask" value="<?= htmlspecialchars(
                $sup["kontak"] ?? "",
            ) ?>"></div>
        </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="update_supplier" class="btn btn-primary w-100 py-2 fw-bold">Update</button></div>
    </form></div></div>
    <?php endwhile;
    ?>

    <!-- MODAL EDIT STAFF -->
    <?php
    mysqli_data_seek($s_list, 0);
    while ($s = mysqli_fetch_assoc($s_list)): ?>
    <div class="modal fade" id="mEditS<?= $s[
        "id_staff"
    ] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg" style="border-radius: 20px;" method="POST">
        <div class="modal-header bg-warning border-0 py-4"><h5>Edit Staf</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <input type="hidden" name="id_staff" value="<?= $s["id_staff"] ?>">
            <input type="text" name="no_identitas" class="form-control mb-2 bg-light border-0" value="<?= $s[
                "no_identitas"
            ] ?>">
            <input type="text" name="nama_lengkap" class="form-control mb-2 bg-light border-0" value="<?= $s[
                "nama_lengkap"
            ] ?>">
            <input type="text" name="jabatan" class="form-control mb-2 bg-light border-0" value="<?= $s[
                "jabatan"
            ] ?>">
            <select name="instansi" class="form-select mb-2 bg-light border-0"><option <?= $s[
                "instansi"
            ] == "Kampus"
                ? "selected"
                : "" ?>>Kampus</option><option <?= $s["instansi"] == "Siloam"
    ? "selected"
    : "" ?>>Siloam</option></select>
            <input type="text" name="npa_idi" class="form-control mb-2 bg-light border-0" value="<?= $s[
                "npa_idi"
            ] ?>">
            <div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" value="<?= $s[
                "no_hp"
            ] ?? "" ?>"></div>
        </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="update_staff" class="btn btn-primary w-100 py-2 fw-bold">Update</button></div>
    </form></div></div>
    <?php endwhile;
    ?>

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
                        <div class="col-md-6"><label class="small fw-bold">Username</label><input type="text" name="username" class="form-control bg-light border-0" value="<?= e($p["username"] ?? '') ?>" minlength="3" maxlength="50" required></div>
                        <div class="col-md-6"><label class="small fw-bold">Email</label><input type="email" name="email" class="form-control bg-light border-0" value="<?= e($p["email"] ?? '') ?>" maxlength="100" required></div>
                        <div class="col-12"><label class="small fw-bold">Password</label><input type="text" name="password" class="form-control bg-light border-0" value="<?= e($p["password"] ?? '') ?>" placeholder="Kosongkan jika tidak ingin mengubah password" maxlength="72"></div>
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
                                <option value="Sigap" <?= $p["kategori_pasien"] === "Sigap" ? "selected" : "" ?>>Personel Sigap</option>
                                <option value="Virtus" <?= $p["kategori_pasien"] === "Virtus" ? "selected" : "" ?>>Personel Virtus</option>
                                <option value="Tamu" <?= $p["kategori_pasien"] === "Tamu" ? "selected" : "" ?>>Tamu Umum / Lain-lain</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="small fw-bold">Nomor WhatsApp</label><div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" value="<?= e(preg_replace('/^\+62/', '', (string) ($p["no_hp"] ?? ''))) ?>" required></div></div>
                        <div class="col-md-6"><label class="small fw-bold">Alamat</label><input type="text" name="alamat" class="form-control bg-light border-0" value="<?= e($p["alamat"] ?? '') ?>" minlength="5" maxlength="255" required></div>
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
                    : 'NIP minimal 3 angka';
            });
        });

        document.querySelectorAll('.admin-patient-form').forEach(function (form) {
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
        });
    });
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

                const email = form.querySelector('input[name="email"]');
                const password = form.querySelector('input[name="password"]');
                const invalidFields = [];

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
                    showValidationPopup('Ada Input yang Salah', 'Periksa kembali format email dan password minimal 8 karakter.', invalidFields[0]);
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
            input.addEventListener('input', e => {
                let v = e.target.value.replace(/\D/g, '').substring(0, 13);
                let f = v.substring(0, 3);
                if (v.length > 3) f += '-' + v.substring(3, 7);
                if (v.length > 7) f += '-' + v.substring(7, 12);
                e.target.value = f;
            });
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
    <?php include dirname(__DIR__) . '/includes/pagination_global.php'; ?>
<?php include dirname(__DIR__) . '/includes/login_success_popup.php'; ?>
</body>
    </html>