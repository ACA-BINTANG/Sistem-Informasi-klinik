<?php
session_start();
require_once 'koneksi.php'; // Pastikan path ini bener kalau se-folder

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['nim'])) {
    $nim = $_SESSION['nim'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Cek apakah password baru dan konfirmasinya sama
    if ($new_password === $confirm_password) {
        // Enkripsi password baru pakai BCRYPT
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update database: set password baru dan ubah is_first_login jadi 0 (false)
        $query = "UPDATE mahasiswa SET password = '$hashed', is_first_login = 0 WHERE nim = '$nim'";
        
        if (mysqli_query($koneksi, $query)) {
            // Update juga status di sessionnya biar pop-upnya hilang
            $_SESSION['is_first_login'] = 0; 
            echo "<script>
                    alert('Password berhasil diamankan! Selamat datang di Dashboard.'); 
                    window.location.href='dashboard_mahasiswa.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Gagal update database: " . mysqli_error($koneksi) . "'); 
                    window.history.back();
                  </script>";
        }
    } else {
        echo "<script>
                alert('Password baru dan konfirmasi tidak cocok!'); 
                window.history.back();
              </script>";
    }
} else {
    // Kalau ada yang iseng buka file ini langsung lewat URL tanpa login
    header("Location: login.php");
    exit;
}
?>