<?php
// File: update_sos.php
header('Content-Type: application/json');

// Konfigurasi Database (Sesuaikan dengan milikmu)
$host = "localhost";
$dbname = "klinik_db"; 
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Menerima data JSON yang dikirim dari fungsi terimaPanggilan() di JS
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->id)) {
        $logID = $data->id;

        // Eksekusi query untuk mengubah status
        $sql = "UPDATE Emergency_Logs SET StatusPenanganan = 'Diproses' WHERE LogID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $logID]);

        echo json_encode(['status' => 'sukses']);
    } else {
        echo json_encode(['status' => 'gagal', 'pesan' => 'ID tidak ditemukan.']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'pesan' => $e->getMessage()]);
}
?>