<?php
// proses_sos.php

// Konfigurasi Koneksi MariaDB / MySQL
$host = "localhost";
$dbname = "klinik_db"; // Sesuaikan dengan nama database kamu
$username = "root"; // Default XAMPP biasanya 'root'
$password = ""; // Default XAMPP biasanya kosong

try {
    // Membuat koneksi PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set mode error PDO ke Exception agar mudah di-debug
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mengambil data JSON dari request Fetch API
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->nim) && isset($data->lat) && isset($data->lng)) {
        $nim = $data->nim;
        $latitude = $data->lat;
        $longitude = $data->lng;

        // Query menggunakan Prepared Statement untuk mencegah SQL Injection
        $sql = "INSERT INTO Emergency_Logs (NIM, Latitude, Longitude) VALUES (:nim, :lat, :lng)";
        $stmt = $pdo->prepare($sql);
        
        // Eksekusi query dengan binding parameter
        $stmt->execute([
            ':nim' => $nim,
            ':lat' => $latitude,
            ':lng' => $longitude
        ]);

        echo "Data darurat berhasil disimpan.";
    } else {
        echo "Data tidak lengkap.";
    }

} catch (PDOException $e) {
    // Menampilkan pesan error jika koneksi atau query gagal
    echo "Koneksi atau Eksekusi Gagal: " . $e->getMessage();
}
?>