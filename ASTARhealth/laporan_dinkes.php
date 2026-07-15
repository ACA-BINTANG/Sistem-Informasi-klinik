<?php
session_start();
require_once 'koneksi.php';

/** @var mysqli $conn */

include 'layout.php';

// Cek login
if (!isset($_SESSION['id_staff'])) {
    header('Location: login.php');
    exit();
}

// Set default bulan dan tahun ke bulan saat ini
$bulan_sekarang = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun_sekarang = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Validasi input
if ($bulan_sekarang < 1 || $bulan_sekarang > 12) {
    $bulan_sekarang = date('m');
}
if ($tahun_sekarang < 2020 || $tahun_sekarang > 2100) {
    $tahun_sekarang = date('Y');
}

// Query untuk mendapatkan laporan 10 PENYAKIT MENULAR per bulan
$query = "
    SELECT 
        d.id_diagnosa,
        d.nama_penyakit,
        d.kategori,
        d.tipe,
        COUNT(rm.id_rekam_medis) as jumlah_kasus
    FROM rekam_medis rm
    LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa
    WHERE MONTH(rm.tgl_kunjungan) = ? 
    AND YEAR(rm.tgl_kunjungan) = ?
    AND rm.status = 'Selesai'
    AND rm.id_diagnosa IS NOT NULL
    AND d.kategori = 'Menular'
    GROUP BY rm.id_diagnosa, d.id_diagnosa, d.nama_penyakit, d.kategori, d.tipe
    ORDER BY jumlah_kasus DESC
    LIMIT 10
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $bulan_sekarang, $tahun_sekarang);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$laporan_data = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Query untuk statistik keseluruhan
$query_stat = "
    SELECT 
        COUNT(DISTINCT id_pasien) as total_pasien,
        COUNT(id_rekam_medis) as total_kunjungan,
        COUNT(CASE WHEN id_diagnosa IS NOT NULL THEN 1 END) as kunjungan_terdiagnosa
    FROM rekam_medis
    WHERE MONTH(tgl_kunjungan) = ? 
    AND YEAR(tgl_kunjungan) = ?
    AND status = 'Selesai'
";

$stmt_stat = mysqli_prepare($conn, $query_stat);
mysqli_stmt_bind_param($stmt_stat, "ii", $bulan_sekarang, $tahun_sekarang);
mysqli_stmt_execute($stmt_stat);
$stat_result = mysqli_stmt_get_result($stmt_stat);
$statistik = mysqli_fetch_assoc($stat_result);

// Format nama bulan
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan 10 Penyakit Menular - Dinas Kesehatan</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .container-laporan {
            margin: 30px auto;
            max-width: 1000px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-laporan {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #0066cc;
            padding-bottom: 20px;
        }

        .header-laporan h1 {
            color: #333;
            font-size: 28px;
            margin: 0 0 10px 0;
        }

        .header-laporan p {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }

        .filter-section {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }

        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            width: 150px;
            background: white;
            cursor: pointer;
        }

        .form-group select:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 5px rgba(0,102,204,0.3);
        }

        .btn-filter {
            padding: 10px 25px;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .btn-filter:hover {
            background: #0052a3;
        }

        .btn-print {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .btn-print:hover {
            background: #218838;
        }

        .statistik-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
        }

        .stat-card.pasien {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card.kunjungan {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.terdiagnosa {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: normal;
            opacity: 0.9;
        }

        .stat-card .angka {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
        }

        .tabel-penyakit {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .tabel-penyakit thead {
            background: #0066cc;
            color: white;
        }

        .tabel-penyakit th {
            padding: 15px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
        }

        .tabel-penyakit td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .tabel-penyakit tbody tr:hover {
            background: #f5f5f5;
        }

        .tabel-penyakit tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-umum {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-menular {
            background: #ffebee;
            color: #c62828;
        }

        .badge-kronis {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-lainnya {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .badge-ringan {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .badge-sedang {
            background: #fff9c4;
            color: #f57f17;
        }

        .badge-berat {
            background: #ffccbc;
            color: #d84315;
        }

        .progress-bar {
            background: #e0e0e0;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 5px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0066cc, #0052a3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
            transition: width 0.3s;
        }

        .kosong {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        @media print {
            body {
                background: white;
            }
            .filter-section,
            .btn-print {
                display: none;
            }
            .container-laporan {
                max-width: 100%;
                box-shadow: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container-laporan">
    <!-- Header Laporan -->
    <div class="header-laporan">
        <h1>⚠️ LAPORAN 10 PENYAKIT MENULAR - DINAS KESEHATAN</h1>
        <p>Berdasarkan Data Transaksi Rekam Medis Klinik</p>
        <p>Periode: <strong><?php echo $nama_bulan[$bulan_sekarang]; ?> <?php echo $tahun_sekarang; ?></strong></p>
        <p style="font-size: 12px; color: #999;">Dicetak tanggal: <?php echo date('d-m-Y H:i:s'); ?></p>
    </div>

    <!-- Filter Section -->
    <form method="GET" action="" class="filter-section">
        <div class="form-group">
            <label for="bulan">Bulan:</label>
            <select id="bulan" name="bulan">
                <?php for($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == $bulan_sekarang) ? 'selected' : ''; ?>>
                        <?php echo $nama_bulan[$i]; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="tahun">Tahun:</label>
            <select id="tahun" name="tahun">
                <?php for($i = 2020; $i <= date('Y'); $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == $tahun_sekarang) ? 'selected' : ''; ?>>
                        <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <button type="submit" class="btn-filter">🔍 Tampilkan Laporan</button>
        <button type="button" class="btn-print" onclick="window.print()">🖨️ Cetak Laporan</button>
    </form>

    <!-- Statistik -->
    <div class="statistik-box">
        <div class="stat-card pasien">
            <h3>Total Pasien</h3>
            <p class="angka"><?php echo $statistik['total_pasien'] ?? 0; ?></p>
        </div>
        <div class="stat-card kunjungan">
            <h3>Total Kunjungan</h3>
            <p class="angka"><?php echo $statistik['total_kunjungan'] ?? 0; ?></p>
        </div>
        <div class="stat-card terdiagnosa">
            <h3>Kunjungan Terdiagnosa</h3>
            <p class="angka"><?php echo $statistik['kunjungan_terdiagnosa'] ?? 0; ?></p>
        </div>
    </div>

    <!-- Info Penyakit Menular -->
    <div style="background: #fff3cd; padding: 20px; border-radius: 6px; margin-bottom: 30px; border-left: 4px solid #ffc107;">
        <h4 style="color: #856404; margin-top: 0;">ℹ️ Catatan Penting - Penyakit Menular</h4>
        <p style="color: #856404; margin-bottom: 0; font-size: 14px;">
            Laporan ini menampilkan <strong>10 Penyakit Menular Terbanyak</strong> berdasarkan transaksi rekam medis klinik. 
            Data ini penting untuk monitoring epidemiologi dan pengambilan keputusan kesehatan publik oleh Dinas Kesehatan.
        </p>
    </div>

    <!-- Tabel Penyakit -->
    <h2 style="color: #333; margin-top: 30px; margin-bottom: 20px;">📊 Daftar 10 Penyakit Menular Terbanyak</h2>
    
    <?php if(count($laporan_data) > 0): ?>
        <table class="tabel-penyakit">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Nama Penyakit</th>
                    <th style="width: 15%;">Kategori</th>
                    <th style="width: 15%;">Tipe</th>
                    <th style="width: 15%;">Jumlah Kasus</th>
                    <th style="width: 25%;">Grafik</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_kasus = array_sum(array_column($laporan_data, 'jumlah_kasus'));
                foreach($laporan_data as $index => $row): 
                    $persentase = ($total_kasus > 0) ? round(($row['jumlah_kasus'] / $total_kasus) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['nama_penyakit']); ?></strong></td>
                    <td>
                        <span class="badge badge-<?php echo strtolower($row['kategori']); ?>">
                            <?php echo $row['kategori']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo strtolower($row['tipe']); ?>">
                            <?php echo $row['tipe']; ?>
                        </span>
                    </td>
                    <td>
                        <strong><?php echo $row['jumlah_kasus']; ?> kasus</strong>
                    </td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $persentase; ?>%;">
                                <?php echo $persentase; ?>%
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="kosong">
            <p>❌ Tidak ada data penyakit menular untuk bulan dan tahun yang dipilih</p>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 20px;">
        <p>Laporan ini dibuat secara otomatis oleh Sistem Informasi Klinik</p>
    </div>
</div>

<?php include __DIR__ . '/pagination_global.php'; ?>
</body>
</html>
