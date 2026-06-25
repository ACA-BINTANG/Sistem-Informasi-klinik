<?php
session_start();
require_once 'koneksi.php';

if (isset($_POST['booking'])) {
    $id_pasien = $_POST['id_pasien'];
    $id_jadwal = $_POST['id_jadwal'];
    $keluhan   = mysqli_real_escape_string($conn, $_POST['keluhan']);
    $tgl       = date('Y-m-d');

    // 1. Ambil ID Dokter dari jadwal yang dipilih
    // (Asumsi: id_staff pertama di database sebagai default, atau hubungkan ke tabel staff)
    $qStaff = mysqli_query($conn, "SELECT id_staff FROM staffm LIMIT 1");
    $dStaff = mysqli_fetch_assoc($qStaff);
    $id_staff = $dStaff['id_staff'];

    // 2. Hitung Nomor Antrean Hari Ini
    $qLast = mysqli_query($conn, "SELECT MAX(no_antrian) as terakhir FROM kunjungan_medis WHERE tgl_kunjungan = '$tgl'");
    $dLast = mysqli_fetch_assoc($qLast);
    $no_baru = $dLast['terakhir'] + 1;

    // 3. Simpan ke database
    $query = "INSERT INTO kunjungan_medis (id_pasien, id_staff, id_jadwal, no_antrian, tgl_kunjungan, keluhan, status_antrian) 
              VALUES ('$id_pasien', '$id_staff', '$id_jadwal', '$no_baru', '$tgl', '$keluhan', 'Menunggu')";

    if (mysqli_query($conn, $query)) {
        header("Location: dashboard_mahasiswa.php?msg=Berhasil mengambil antrean nomor $no_baru");
    } else {
        echo "Gagal: " . mysqli_error($conn);
    }
}
?>