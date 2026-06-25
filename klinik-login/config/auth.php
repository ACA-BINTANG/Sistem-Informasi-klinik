<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// PERBAIKAN: Karena auth.php ada di folder config/, panggil koneksi.php yang ada di folder yang sama
require_once 'koneksi.php'; 

$valid_users = [
    'admin'  => ['password' => 'admin123',  'role' => 'admin',  'name' => 'Administrator'],
    'dokter' => ['password' => 'dokter123', 'role' => 'dokter', 'name' => 'dr. Andi Pratama'],
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $authenticated = false;

    if (isset($valid_users[$username]) && $valid_users[$username]['password'] === $password) {
        $_SESSION['user'] = $username;
        $_SESSION['role'] = $valid_users[$username]['role'];
        $_SESSION['name'] = $valid_users[$username]['name'];
        $authenticated = true;
    } else {
        $username_safe = mysqli_real_escape_string($koneksi, $username);
        $query_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE nim = '$username_safe' AND status = 'Aktif'");
        
        if ($query_mhs && mysqli_num_rows($query_mhs) > 0) {
            $mhs = mysqli_fetch_assoc($query_mhs);
            $login_sukses = false;
            if (empty($mhs['password'])) {
                if ($password === $mhs['nim']) $login_sukses = true;
            } else {
                if (password_verify($password, $mhs['password'])) $login_sukses = true;
            }

            if ($login_sukses) {
                $_SESSION['user'] = $mhs['nim'];
                $_SESSION['role'] = 'mahasiswa';
                $_SESSION['name'] = $mhs['nama'];
                $_SESSION['nim']  = $mhs['nim'];
                $_SESSION['prodi'] = $mhs['prodi'];
                $_SESSION['is_first_login'] = $mhs['is_first_login'] ?? 1;
                $authenticated = true;
            }
        } else {
            $query_staff = mysqli_query($koneksi, "SELECT * FROM staff WHERE username = '$username_safe' AND status = 'Aktif'");
            if ($query_staff && mysqli_num_rows($query_staff) > 0) {
                $user_data = mysqli_fetch_assoc($query_staff);
                if (password_verify($password, $user_data['password'])) {
                    $_SESSION['user'] = $user_data['username'];
                    $_SESSION['role'] = $user_data['role']; 
                    $_SESSION['name'] = $user_data['nama'];
                    $authenticated = true;
                }
            }
        }
    }

    // ==========================================================================
    // PERBAIKAN DI SINI: Jalur dialihkan ke dalam folder views/ sesuai struktur baru
    // ==========================================================================
    if ($authenticated) {
        $role = strtolower($_SESSION['role']);
        if ($role === 'admin' || $role === 'keuangan') {
            header('Location: views/admin/dashboard_admin.php');
        } elseif ($role === 'dokter') {
            header('Location: views/dokter/dashboard_dokter.php');
        } elseif ($role === 'mahasiswa') {
            header('Location: views/mahasiswa/dashboard_mahasiswa.php');
        } else {
            header('Location: views/tamu/dashboard_tamu.php'); 
        }
        exit;
    } else {
        $error = 'Username/NIM atau password salah, atau akun tidak aktif.';
    }
}
?>