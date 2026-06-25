<?php
require_once 'koneksi.php';

// Fungsi generate random ID 6 karakter
function genID() { return substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6); }

$id_user = genID();
$id_pasien = genID();
$un = $_POST['username'];
$em = $_POST['email'];
$ps = $_POST['password'];
$nama = $_POST['nama'];
$jk = $_POST['jk'];
$kat = $_POST['kategori'];
$idn = $_POST['identitas'];
$pro = $_POST['prodi'];
$alm = $_POST['alamat'];

// 1. Simpan ke userm
$q1 = "INSERT INTO userm (id_user, username, email, password, role, nama_lengkap) 
       VALUES ('$id_user', '$un', '$em', '$ps', 'Pasien', '$nama')";

// 2. Simpan ke pasienm
$q2 = "INSERT INTO pasienm (id_pasien, id_user, no_identitas, nama_pasien, jenis_kelamin, kategori_pasien, unit_prodi, alamat) 
       VALUES ('$id_pasien', '$id_user', '$idn', '$nama', '$jk', '$kat', '$pro', '$alm')";

if (mysqli_query($conn, $q1) && mysqli_query($conn, $q2)) {
    header("Location: login.php?pesan=Registrasi Berhasil. Silakan Login.");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>