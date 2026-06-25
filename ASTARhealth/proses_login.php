<?php
session_start();
require_once 'koneksi.php';

$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = mysqli_real_escape_string($conn, $_POST['password']);

// 1. CEK ADMIN HARDCODE
if ($username == "admin" && $password == "admin123") {
    $_SESSION['id_user'] = "ADM-ROOT";
    $_SESSION['username'] = "admin";
    $_SESSION['nama_lengkap'] = "Super Admin";
    $_SESSION['role'] = "Admin";
    header("Location: adminMaster.php");
    exit;
}

// 2. CEK DATABASE (UNTUK DOKTER & PASIEN SSO)
$query = mysqli_query($conn, "SELECT * FROM userm WHERE username='$username' AND password='$password'");

if (mysqli_num_rows($query) > 0) {
    $data = mysqli_fetch_assoc($query);
    
    $_SESSION['id_user'] = $data['id_user'];
    $_SESSION['username'] = $data['username'];
    $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
    $_SESSION['role'] = $data['role'];

    if ($data['role'] == "Admin") {
        header("Location: adminMaster.php");
    } elseif ($data['role'] == "Dokter") {
        header("Location: dashboard_dokter.php");
    } elseif ($data['role'] == "Pasien") {
        header("Location: dashboard_pasien.php");
    } else {
        header("Location: index.php");
    }
    exit;
} else {
    header("Location: login.php?pesan=Username atau Password Salah!");
    exit;
}