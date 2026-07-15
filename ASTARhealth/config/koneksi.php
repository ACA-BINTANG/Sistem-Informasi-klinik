<?php
$conn = mysqli_connect("localhost", "root", "", "astarhealth_db");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

/**
 * Menjamin tabel yang ditampilkan sebagai daftar memiliki penanda waktu pembuatan.
 * ID utama di project ini sebagian dibuat acak, sehingga ID tidak boleh dijadikan
 * patokan urutan data terbaru.
 */
function astarEnsureCreatedAtColumns(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $tables = [
        'userm' => null,
        'staffm' => null,
        'pasienm' => null,
        'supplierm' => null,
        'obatm' => null,
        'diagnosam' => null,
        'jadwalm' => null,
        'rujukan' => "CAST(CONCAT(COALESCE(tgl_rujukan, CURDATE()), ' 00:00:00') AS DATETIME(6))",
        'rekam_medis' => "CAST(CONCAT(COALESCE(tgl_kunjungan, CURDATE()), ' ', COALESCE(waktu_booking, '00:00:00')) AS DATETIME(6))",
    ];

    foreach ($tables as $table => $backfillExpression) {
        $safeTable = str_replace('`', '', $table);
        $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conn, $safeTable) . "'");
        if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
            continue;
        }

        $columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM `{$safeTable}` LIKE 'created_at'");
        $columnInfo = $columnCheck ? mysqli_fetch_assoc($columnCheck) : null;
        if (!$columnInfo) {
            // Tambahkan nullable terlebih dahulu agar data lama dapat dibackfill secara terkontrol.
            @mysqli_query($conn, "ALTER TABLE `{$safeTable}` ADD COLUMN `created_at` DATETIME(6) NULL DEFAULT NULL");
            $columnInfo = ['Null' => 'YES', 'Default' => null];
        }

        if ($backfillExpression !== null) {
            @mysqli_query($conn, "UPDATE `{$safeTable}` SET `created_at` = {$backfillExpression} WHERE `created_at` IS NULL");
        } else {
            // Database versi lama pernah memiliki created_at tanpa default. Data baru
            // dari versi itu bisa bernilai NULL dan akhirnya muncul di bawah. Nilai NULL
            // ditandai sebagai data terbaru saat migrasi agar tidak lagi tertinggal.
            @mysqli_query($conn, "UPDATE `{$safeTable}` SET `created_at` = CURRENT_TIMESTAMP(6) WHERE `created_at` IS NULL");
        }

        $defaultValue = strtolower((string) ($columnInfo['Default'] ?? ''));
        $needsDefaultFix = (($columnInfo['Null'] ?? 'YES') !== 'NO') || !str_contains($defaultValue, 'current_timestamp');
        if ($needsDefaultFix) {
            @mysqli_query(
                $conn,
                "ALTER TABLE `{$safeTable}` MODIFY COLUMN `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)"
            );
        }
    }
}


/**
 * Menyimpan riwayat apakah sebuah rekam medis pernah masuk kondisi Darurat.
 * Status akhir tetap dapat berubah menjadi Selesai/Batal, sedangkan flag ini
 * tetap 1 agar riwayat kondisi darurat dapat difilter untuk kebutuhan laporan.
 */
function astarEnsureEmergencyHistoryColumn(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'rekam_medis'");
    if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
        return;
    }

    $columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM `rekam_medis` LIKE 'pernah_darurat'");
    if (!$columnCheck || mysqli_num_rows($columnCheck) === 0) {
        @mysqli_query(
            $conn,
            "ALTER TABLE `rekam_medis` ADD COLUMN `pernah_darurat` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`"
        );
    }

    // Backfill database lama. Selain status Darurat yang masih aktif, keluhan lama
    // juga diperiksa agar kasus yang sudah berubah menjadi Selesai tetap tercatat.
    @mysqli_query(
        $conn,
        "UPDATE `rekam_medis`
         SET `pernah_darurat` = 1
         WHERE `pernah_darurat` = 0
           AND (
                `status` = 'Darurat'
                OR LOWER(COALESCE(`keluhan`, '')) REGEXP 'darurat|sesak|pingsan|nyeri dada|kecelakaan|tidak sadar|pendarahan|perdarahan|asma|tertusuk|jantung|darah|kejang|lemas'
           )"
    );
}


astarEnsureCreatedAtColumns($conn);
astarEnsureEmergencyHistoryColumn($conn);
?>
