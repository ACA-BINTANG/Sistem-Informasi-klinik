<?php
session_start();
require_once 'koneksi.php';

$error = '';
$success = '';

function nextUserId($koneksi) {
    $query = mysqli_query($koneksi, "SELECT id FROM staff ORDER BY id DESC LIMIT 1");
    $data = mysqli_fetch_assoc($query);
    if ($data) {
        $num = (int) substr($data['id'], 2);
        return 'U-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
    return 'U-001';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = mysqli_real_escape_string($koneksi, trim($_POST['name'] ?? ''));
    $username  = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $email     = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? $username . '@guest.klinik.id'));
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';
    
    $kategori  = mysqli_real_escape_string($koneksi, trim($_POST['kategori'] ?? 'Tamu'));
    $identitas = mysqli_real_escape_string($koneksi, trim($_POST['identitas'] ?? ''));
    
    $prodi     = mysqli_real_escape_string($koneksi, trim($_POST['prodi'] ?? ''));
    $no_tlp    = mysqli_real_escape_string($koneksi, trim($_POST['no_tlp'] ?? ''));
    $alamat    = mysqli_real_escape_string($koneksi, trim($_POST['alamat'] ?? ''));

    if ($name === '' || $username === '' || $password === '' || $identitas === '') {
        $error = 'Harap lengkapi field yang wajib diisi.';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username hanya boleh huruf, angka, dan underscore.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $cek_username = mysqli_query($koneksi, "SELECT username FROM staff WHERE username = '$username'");
        if (mysqli_num_rows($cek_username) > 0) {
            $error = 'Username sudah digunakan, silakan pilih yang lain.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $new_id = nextUserId($koneksi);
            $role = $kategori; 
            $status = 'Aktif';

            $query_insert = "INSERT INTO staff 
                            (id, username, nama, email, password, role, status, kategori, identitas, prodi, no_tlp, alamat) 
                            VALUES 
                            ('$new_id', '$username', '$name', '$email', '$hashed_password', '$role', '$status', '$kategori', '$identitas', '$prodi', '$no_tlp', '$alamat')";
                             
            if (mysqli_query($koneksi, $query_insert)) {
                $success = 'Pendaftaran berhasil! Silakan login dengan akun Anda.';
            } else {
                $error = 'Gagal menyimpan data: ' . mysqli_error($koneksi);
            }
        }
    }
}
?>