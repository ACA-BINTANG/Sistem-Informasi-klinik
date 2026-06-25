<?php
// cek_sos.php
header('Content-Type: application/json');

$host = "localhost";
$dbname = "klinik_db"; // Sesuaikan nama database kamu
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT * FROM Emergency_Logs WHERE StatusPenanganan = 'Menunggu' ORDER BY WaktuKejadian DESC LIMIT 1";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(['status' => 'darurat', 'data' => $result]);
    } else {
        echo json_encode(['status' => 'aman']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'pesan' => $e->getMessage()]);
}
?>