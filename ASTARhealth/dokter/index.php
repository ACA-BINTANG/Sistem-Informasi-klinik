<?php
session_start();
require_once dirname(__DIR__) . "/config/koneksi.php";

// =======================
// PROTEKSI ROLE DOKTER
// =======================
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Dokter") {
    header("Location: ../auth/login.php?pesan=Akses Ditolak!");
    exit();
}

/** @var mysqli $conn */

date_default_timezone_set("Asia/Jakarta");

$doctor_name = $_SESSION["nama_lengkap"] ?? "Dokter";
$user_id = $_SESSION["id_user"] ?? "";
$active_page = $_GET["page"] ?? "antrean";

$notifikasi_stok = mysqli_query(
    $conn,
    "
    SELECT *
    FROM notifikasi_stok_obat
    ORDER BY tanggal_notifikasi DESC
    LIMIT 5
    ",
);
function e($text)
{
    return htmlspecialchars($text ?? "", ENT_QUOTES, "UTF-8");
}

require_once dirname(__DIR__) . "/includes/report_print_history.php";

// =======================
// AJAX PENCARIAN PASIEN UNTUK FORM RUJUKAN
// Disatukan di halaman dokter agar path tetap benar meskipun project
// dipasang pada folder localhost yang berbeda.
// =======================
if (($_GET["ajax"] ?? "") === "search_pasien_rujukan") {
    header("Content-Type: text/html; charset=UTF-8");

    $keyword = trim((string) ($_GET["keyword"] ?? ""));
    if (mb_strlen($keyword) < 2) {
        exit();
    }

    $likeKeyword = "%" . $keyword . "%";
    $stmtCariPasien = mysqli_prepare(
        $conn,
        "SELECT id_pasien, no_identitas, nama_pasien
         FROM pasienm
         WHERE no_identitas LIKE ? OR nama_pasien LIKE ?
         ORDER BY nama_pasien ASC
         LIMIT 10"
    );

    if (!$stmtCariPasien) {
        http_response_code(500);
        echo '<div class="search-state search-state-error">Pencarian pasien gagal diproses.</div>';
        exit();
    }

    mysqli_stmt_bind_param($stmtCariPasien, "ss", $likeKeyword, $likeKeyword);
    mysqli_stmt_execute($stmtCariPasien);
    mysqli_stmt_bind_result(
        $stmtCariPasien,
        $hasilIdPasien,
        $hasilNoIdentitas,
        $hasilNamaPasien
    );

    $jumlahHasil = 0;
    while (mysqli_stmt_fetch($stmtCariPasien)) {
        $jumlahHasil++;
        $idJson = htmlspecialchars(
            json_encode((string) $hasilIdPasien, JSON_HEX_APOS | JSON_HEX_QUOT),
            ENT_QUOTES,
            "UTF-8"
        );
        $namaJson = htmlspecialchars(
            json_encode((string) $hasilNamaPasien, JSON_HEX_APOS | JSON_HEX_QUOT),
            ENT_QUOTES,
            "UTF-8"
        );
        $identitasJson = htmlspecialchars(
            json_encode((string) $hasilNoIdentitas, JSON_HEX_APOS | JSON_HEX_QUOT),
            ENT_QUOTES,
            "UTF-8"
        );

        echo '<button type="button" class="search-item w-100 border-0 text-start" '
            . 'onclick="pilihPasien(' . $idJson . ', ' . $namaJson . ', ' . $identitasJson . ')">'
            . '<span class="search-item-id">' . e($hasilNoIdentitas) . '</span>'
            . '<span class="search-item-name">' . e($hasilNamaPasien) . '</span>'
            . '</button>';
    }

    mysqli_stmt_close($stmtCariPasien);

    if ($jumlahHasil === 0) {
        echo '<div class="search-state">Pasien tidak ditemukan.</div>';
    }

    exit();
}

function generateID($conn, $prefix, $table, $column)
{
    while (true) {
        $new_id = $prefix . substr(str_shuffle("0123456789"), 0, 4);

        $cek = mysqli_query(
            $conn,
            "
            SELECT $column
            FROM $table
            WHERE $column = '$new_id'
            LIMIT 1
        ",
        );

        if ($cek && mysqli_num_rows($cek) == 0) {
            return $new_id;
        }
    }
}

function generateIDUrut($conn, $prefix, $table, $column, $prefixLength)
{
    $q = mysqli_query(
        $conn,
        "
        SELECT $column
        FROM $table
        WHERE $column LIKE '$prefix%'
        ORDER BY CAST(SUBSTRING($column, " .
            ($prefixLength + 1) .
            ") AS UNSIGNED) DESC
        LIMIT 1
    ",
    );

    if ($q && mysqli_num_rows($q) > 0) {
        $d = mysqli_fetch_assoc($q);
        $lastNumber = (int) substr($d[$column], $prefixLength);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }

    return $prefix . str_pad($newNumber, 3, "0", STR_PAD_LEFT);
}

/**
 * Membuat ID resep yang selalu unik pada tabel resep_dokter dan resep_diagnosa.
 * Format tetap 6 karakter agar kompatibel dengan struktur database lama:
 * RSP + 3 karakter alfanumerik (contoh: RSP9A2).
 */
function generateUniqueResepID($conn)
{
    $characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    for ($attempt = 0; $attempt < 500; $attempt++) {
        $suffix = "";
        for ($i = 0; $i < 3; $i++) {
            $suffix .= $characters[random_int(0, strlen($characters) - 1)];
        }

        $candidate = "RSP" . $suffix;
        $candidateSafe = mysqli_real_escape_string($conn, $candidate);

        $sql = "SELECT id_resep FROM resep_dokter WHERE id_resep = '$candidateSafe'";
        if (tableExists($conn, "resep_diagnosa")) {
            $sql .= " UNION ALL SELECT id_resep FROM resep_diagnosa WHERE id_resep = '$candidateSafe'";
        }
        $sql .= " LIMIT 1";

        $check = mysqli_query($conn, $sql);
        if ($check && mysqli_num_rows($check) === 0) {
            return $candidate;
        }
    }

    throw new Exception("ID resep baru tidak dapat dibuat. Silakan kirim ulang formulir.");
}

/**
 * Menghapus relasi penyakit yang tidak lagi memiliki data resep utama.
 * Relasi yatim seperti RSP492-DX190 dapat membuat ID resep baru bentrok.
 */
function cleanupOrphanResepDiagnosa($conn)
{
    if (!tableExists($conn, "resep_diagnosa")) {
        return true;
    }

    return (bool) mysqli_query(
        $conn,
        "DELETE rdg
         FROM resep_diagnosa rdg
         LEFT JOIN resep_dokter rd ON rd.id_resep = rdg.id_resep
         WHERE rd.id_resep IS NULL"
    );
}

function triggerExists($conn, $triggerName)
{
    $triggerName = mysqli_real_escape_string($conn, $triggerName);

    $q = mysqli_query(
        $conn,
        "
        SHOW TRIGGERS
        WHERE `Trigger` = '$triggerName'
    ",
    );

    return $q && mysqli_num_rows($q) > 0;
}

function columnExists($conn, $tableName, $columnName)
{
    $tableName = mysqli_real_escape_string($conn, $tableName);
    $columnName = mysqli_real_escape_string($conn, $columnName);

    $q = mysqli_query(
        $conn,
        "
        SHOW COLUMNS FROM `$tableName`
        LIKE '$columnName'
    ",
    );

    return $q && mysqli_num_rows($q) > 0;
}

function ensureResepDokterPasienColumn($conn)
{
    if (columnExists($conn, "resep_dokter", "id_pasien")) {
        return true;
    }

    $alter = mysqli_query(
        $conn,
        "
        ALTER TABLE resep_dokter
        ADD COLUMN id_pasien VARCHAR(20) NULL AFTER id_resep
    ",
    );

    return $alter && columnExists($conn, "resep_dokter", "id_pasien");
}


/**
 * Menyediakan tanggal transaksi untuk resep yang dibuat melalui form Input Langsung.
 * Resep dari pemeriksaan tetap menampilkan tanggal kunjungan rekam medis.
 */
function ensureResepDokterTanggalColumn($conn)
{
    if (columnExists($conn, "resep_dokter", "tanggal_resep")) {
        return true;
    }

    $alter = mysqli_query(
        $conn,
        "
        ALTER TABLE resep_dokter
        ADD COLUMN tanggal_resep DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER id_pasien
    ",
    );

    return $alter && columnExists($conn, "resep_dokter", "tanggal_resep");
}

function tableExists($conn, $tableName)
{
    $tableName = mysqli_real_escape_string($conn, $tableName);

    $q = mysqli_query(
        $conn,
        "SHOW TABLES LIKE '$tableName'",
    );

    return $q && mysqli_num_rows($q) > 0;
}

/**
 * Menyediakan tabel penghubung agar satu resep dapat memiliki
 * satu atau lebih penyakit/keluhan dari master diagnosa.
 */
function ensureResepDiagnosaTable($conn)
{
    if (tableExists($conn, "resep_diagnosa")) {
        return true;
    }

    $create = mysqli_query(
        $conn,
        "
        CREATE TABLE IF NOT EXISTS resep_diagnosa (
            id_resep VARCHAR(6) NOT NULL,
            id_diagnosa VARCHAR(6) NOT NULL,
            PRIMARY KEY (id_resep, id_diagnosa),
            KEY idx_resep_diagnosa_diagnosa (id_diagnosa)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ",
    );

    return $create && tableExists($conn, "resep_diagnosa");
}

function hariIniIndonesia()
{
    $map = [
        "Monday" => "Senin",
        "Tuesday" => "Selasa",
        "Wednesday" => "Rabu",
        "Thursday" => "Kamis",
        "Friday" => "Jumat",
        "Saturday" => "Sabtu",
        "Sunday" => "Minggu",
    ];

    return $map[date("l")] ?? "";
}

// =======================
// AMBIL ID STAFF DOKTER
// =======================
$user_id_safe = mysqli_real_escape_string($conn, $user_id);

$qStaff = mysqli_query(
    $conn,
    "
    SELECT id_staff
    FROM staffm
    WHERE id_user = '$user_id_safe'
    LIMIT 1
",
);

if (!$qStaff) {
    die("Query staff error: " . mysqli_error($conn));
}

$dStaff = mysqli_fetch_assoc($qStaff);
$id_dokter = $dStaff["id_staff"] ?? "";

if ($id_dokter == "") {
    die(
        "ID dokter tidak ditemukan. Pastikan akun dokter terhubung dengan tabel staffm."
    );
}

// =======================
// PASTIKAN TABEL RESEP_DOKTER PUNYA ID_PASIEN
// Supaya resep obat bisa dibuat langsung dari data pasien
// tanpa wajib punya rekam medis terlebih dahulu.
// =======================
if (!columnExists($conn, "resep_dokter", "id_pasien")) {
    mysqli_query(
        $conn,
        "
        ALTER TABLE resep_dokter
        ADD COLUMN id_pasien VARCHAR(20) NULL AFTER id_resep
    ",
    );
}

// Tabel ini dipakai oleh form Tambah Resep agar satu resep bisa
// menyimpan lebih dari satu penyakit/keluhan.
ensureResepDiagnosaTable($conn);

// Pastikan pengadaan menyimpan jumlah yang benar-benar diterima.
// Kolom dibuat otomatis agar database lama tetap kompatibel.
if (!columnExists($conn, "pengadaan_obat", "jumlah_diterima")) {
    mysqli_query(
        $conn,
        "ALTER TABLE pengadaan_obat ADD COLUMN jumlah_diterima INT NULL AFTER jumlah_order",
    );
}
if (!columnExists($conn, "pengadaan_obat", "tgl_diterima")) {
    mysqli_query(
        $conn,
        "ALTER TABLE pengadaan_obat ADD COLUMN tgl_diterima DATETIME NULL AFTER tgl_estimasi_tiba",
    );
}
// Data lama berstatus Diterima diasumsikan diterima penuh agar laporan lama tetap konsisten.
mysqli_query(
    $conn,
    "UPDATE pengadaan_obat
     SET jumlah_diterima = jumlah_order
     WHERE status = 'Diterima'
       AND (jumlah_diterima IS NULL OR jumlah_diterima = 0)",
);

// =======================
// PEMBARUAN STATUS OTOMATIS
// =======================
// Status lama "Proses" disatukan menjadi "Pending".
mysqli_query(
    $conn,
    "UPDATE pengadaan_obat
     SET status = 'Pending'
     WHERE status = 'Proses'"
);

// Pengadaan yang belum dikonfirmasi dokter lebih dari 5 hari
// otomatis dibatalkan oleh sistem.
mysqli_query(
    $conn,
    "UPDATE pengadaan_obat
     SET status = 'Batal'
     WHERE status = 'Pending'
       AND DATEDIFF(CURDATE(), tgl_order) > 5"
);

/**
 * Menjaga struktur tabel rujukan kompatibel dengan form terbaru.
 * Database lama belum memiliki kolom hasil_rujukan dan enum status Aktif.
 */
function sinkronkanStrukturRujukan($conn)
{
    try {
        $cekHasil = mysqli_query($conn, "SHOW COLUMNS FROM rujukan LIKE 'hasil_rujukan'");
        if ($cekHasil && mysqli_num_rows($cekHasil) === 0) {
            mysqli_query(
                $conn,
                "ALTER TABLE rujukan ADD COLUMN hasil_rujukan TEXT NULL AFTER alasan_rujukan"
            );
        }

        $cekStatus = mysqli_query($conn, "SHOW COLUMNS FROM rujukan LIKE 'status'");
        if ($cekStatus && ($kolomStatusRujukan = mysqli_fetch_assoc($cekStatus))) {
            $tipeStatusRujukan = strtolower((string) ($kolomStatusRujukan['Type'] ?? ''));
            if (strpos($tipeStatusRujukan, "'aktif'") === false) {
                mysqli_query(
                    $conn,
                    "ALTER TABLE rujukan MODIFY status ENUM('Aktif','Proses','Selesai','Batal') NOT NULL DEFAULT 'Aktif'"
                );
            }
        }
    } catch (Throwable $e) {
        // Jangan membuat halaman fatal pada database lama. Proses tambah akan
        // memberikan pesan yang lebih aman bila migrasi struktur benar-benar gagal.
    }
}

sinkronkanStrukturRujukan($conn);

// Rujukan aktif yang sudah lebih dari 2 hari otomatis dianggap selesai.
mysqli_query(
    $conn,
    "UPDATE rujukan
     SET status = 'Selesai'
     WHERE status = 'Aktif'
       AND DATEDIFF(CURDATE(), tgl_rujukan) > 2"
);

// =======================
// UBAH STATUS RUJUKAN
// =======================
if (isset($_POST["update_status_rujukan"])) {
    $idRujukanStatus = trim((string) ($_POST["id_rujukan"] ?? ""));
    $statusRujukanBaru = trim((string) ($_POST["status_rujukan"] ?? ""));
    $statusRujukanDiizinkan = ["Aktif", "Proses", "Selesai", "Batal"];

    if ($idRujukanStatus === "" || !in_array($statusRujukanBaru, $statusRujukanDiizinkan, true)) {
        header("Location: index.php?page=rujukan&err=" . urlencode("Status rujukan tidak valid."));
        exit();
    }

    $stmtStatusRujukan = mysqli_prepare(
        $conn,
        "UPDATE rujukan SET status = ? WHERE id_rujukan = ? AND id_staff = ?",
    );

    if (!$stmtStatusRujukan) {
        header("Location: index.php?page=rujukan&err=" . urlencode("Status rujukan gagal diperbarui."));
        exit();
    }

    mysqli_stmt_bind_param(
        $stmtStatusRujukan,
        "sss",
        $statusRujukanBaru,
        $idRujukanStatus,
        $id_dokter,
    );
    mysqli_stmt_execute($stmtStatusRujukan);
    $statusRujukanBerubah = mysqli_stmt_affected_rows($stmtStatusRujukan);
    mysqli_stmt_close($stmtStatusRujukan);

    if ($statusRujukanBerubah >= 0) {
        header(
            "Location: index.php?page=rujukan&msg=" .
                urlencode("Status rujukan berhasil diperbarui menjadi " . $statusRujukanBaru . "."),
        );
        exit();
    }

    header("Location: index.php?page=rujukan&err=" . urlencode("Status rujukan gagal diperbarui."));
    exit();
}

// =======================
// TAMBAH RUJUKAN
// =======================
if (isset($_POST["add_rujukan"])) {
    $id_rjk = generateIDUrut($conn, "RJK", "rujukan", "id_rujukan", 3);
    $id_p = trim((string) ($_POST["id_pasien"] ?? ""));
    $tujuan = trim((string) ($_POST["tujuan_rs"] ?? ""));
    $alasan = trim((string) ($_POST["alasan_rujukan"] ?? ""));
    $hasil = trim((string) ($_POST["hasil_rujukan"] ?? ""));
    $tgl = date("Y-m-d");

    if ($id_p === "" || $tujuan === "" || $alasan === "" || $hasil === "") {
        header("Location: index.php?page=rujukan&err=" . urlencode("Pasien, rumah sakit tujuan, alasan rujukan, dan hasil rujukan wajib diisi."));
        exit();
    }

    // Pastikan database lama sudah memiliki kolom/status yang dibutuhkan.
    sinkronkanStrukturRujukan($conn);

    try {
        $stmtRujukan = mysqli_prepare(
            $conn,
            "INSERT INTO rujukan
                (id_rujukan, id_pasien, id_staff, tujuan_rs, alasan_rujukan, hasil_rujukan, tgl_rujukan, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif', NOW(6))"
        );

        if (!$stmtRujukan) {
            throw new Exception("Rujukan gagal diproses.");
        }

        mysqli_stmt_bind_param(
            $stmtRujukan,
            "sssssss",
            $id_rjk,
            $id_p,
            $id_dokter,
            $tujuan,
            $alasan,
            $hasil,
            $tgl
        );
        $ins = mysqli_stmt_execute($stmtRujukan);
        mysqli_stmt_close($stmtRujukan);

        if (!$ins) {
            throw new Exception("Rujukan gagal disimpan.");
        }

        header("Location: index.php?page=rujukan&msg=" . urlencode("Surat rujukan berhasil dibuat."));
        exit();
    } catch (Throwable $e) {
        header("Location: index.php?page=rujukan&err=" . urlencode("Data rujukan tidak dapat disimpan. Struktur database telah diperiksa, silakan coba kembali."));
        exit();
    }
}

// =======================
// TOMBOL BATAL ANTREAN DOKTER
// Antrean tidak dihapus. Status diubah menjadi Batal agar riwayat tetap tersedia.
// =======================
if (isset($_POST["batal_antrean"])) {
    $id_rm_batal = trim((string) ($_POST["id_rekam_medis"] ?? ""));

    if ($id_rm_batal === "") {
        header("Location: index.php?page=antrean&err=" . urlencode("Data antrean tidak ditemukan."));
        exit();
    }

    $stmtBatal = mysqli_prepare(
        $conn,
        "UPDATE rekam_medis
         SET status = 'Batal'
         WHERE id_rekam_medis = ?
           AND (id_staff = ? OR id_staff IS NULL OR id_staff = '')
           AND status IN ('Menunggu', 'Darurat', 'Diproses')"
    );
    mysqli_stmt_bind_param($stmtBatal, "ss", $id_rm_batal, $id_dokter);
    mysqli_stmt_execute($stmtBatal);
    $affectedBatal = mysqli_stmt_affected_rows($stmtBatal);
    mysqli_stmt_close($stmtBatal);

    if ($affectedBatal > 0) {
        header("Location: index.php?page=antrean&view=batal&msg=" . urlencode("Antrean berhasil dibatalkan."));
        exit();
    }

    header("Location: index.php?page=antrean&err=" . urlencode("Antrean tidak dapat dibatalkan atau statusnya sudah berubah."));
    exit();
}

// =======================
// SIMPAN PEMERIKSAAN DOKTER
// Jika obat dipilih dan jumlah keluar diisi,
// stok obat otomatis berkurang
// =======================
if (isset($_POST["simpan_pemeriksaan"])) {
    $id_rm = mysqli_real_escape_string($conn, $_POST["id_rekam_medis"] ?? "");
    $id_diag = mysqli_real_escape_string($conn, $_POST["id_diagnosa"] ?? "");
    $keluhan = mysqli_real_escape_string($conn, trim($_POST["keluhan"] ?? ""));
    $hasil = mysqli_real_escape_string(
        $conn,
        trim($_POST["hasil_pemeriksaan"] ?? ""),
    );

    // Obat pada pemeriksaan dapat lebih dari satu.
    // Setiap obat akan disimpan sebagai satu transaksi resep dengan id_rekam_medis yang sama.
    $obat_input = $_POST["id_obat"] ?? [];
    $jumlah_input = $_POST["jumlah_keluar"] ?? [];
    $catatan_input = $_POST["catatan_obat"] ?? [];

    if (!is_array($obat_input)) {
        $obat_input = [$obat_input];
    }
    if (!is_array($jumlah_input)) {
        $jumlah_input = [$jumlah_input];
    }
    if (!is_array($catatan_input)) {
        $catatan_input = [$catatan_input];
    }

    $daftar_obat_pemeriksaan = [];
    $jumlah_baris_obat = max(
        count($obat_input),
        count($jumlah_input),
        count($catatan_input),
    );

    for ($i = 0; $i < $jumlah_baris_obat; $i++) {
        $id_obat_raw = trim((string) ($obat_input[$i] ?? ""));
        $qty = (int) ($jumlah_input[$i] ?? 0);
        $catatan_raw = trim((string) ($catatan_input[$i] ?? ""));

        // Baris kosong berarti pasien tidak diberi obat dan boleh dilewati.
        if ($id_obat_raw === "" && $qty <= 0 && $catatan_raw === "") {
            continue;
        }

        if ($id_obat_raw === "") {
            header(
                "Location: index.php?page=antrean&err=" .
                    urlencode("Pilih obat pada baris resep yang sudah diisi."),
            );
            exit();
        }

        if ($qty <= 0) {
            header(
                "Location: index.php?page=antrean&err=" .
                    urlencode("Jumlah obat minimal 1 untuk setiap obat yang dipilih."),
            );
            exit();
        }

        // Jika request yang sama mengirim obat yang sama dua kali, jumlahnya digabung.
        // Dari antarmuka normal pilihan obat yang sudah dipakai tidak akan muncul lagi.
        if (isset($daftar_obat_pemeriksaan[$id_obat_raw])) {
            $daftar_obat_pemeriksaan[$id_obat_raw]["jumlah"] += $qty;
            if (
                $catatan_raw !== "" &&
                !str_contains(
                    $daftar_obat_pemeriksaan[$id_obat_raw]["catatan"],
                    $catatan_raw,
                )
            ) {
                $separator = $daftar_obat_pemeriksaan[$id_obat_raw]["catatan"] !== ""
                    ? " | "
                    : "";
                $daftar_obat_pemeriksaan[$id_obat_raw]["catatan"] .=
                    $separator . $catatan_raw;
            }
            continue;
        }

        $daftar_obat_pemeriksaan[$id_obat_raw] = [
            "id_obat" => $id_obat_raw,
            "jumlah" => $qty,
            "catatan" => $catatan_raw,
        ];
    }

    if ($id_rm == "" || $id_diag == "" || $keluhan == "" || $hasil == "") {
        header(
            "Location: index.php?page=antrean&err=Data pemeriksaan belum lengkap",
        );
        exit();
    }

    mysqli_begin_transaction($conn);

    try {
        $cek_rm = mysqli_query(
            $conn,
            "
            SELECT id_rekam_medis
            FROM rekam_medis
            WHERE id_rekam_medis = '$id_rm'
            AND id_staff = '$id_dokter'
            AND status IN ('Menunggu', 'Darurat', 'Diproses')
            LIMIT 1
            FOR UPDATE
        ",
        );

        if (!$cek_rm) {
            throw new Exception(
                "Query rekam medis error: " . mysqli_error($conn),
            );
        }

        if (mysqli_num_rows($cek_rm) == 0) {
            throw new Exception(
                "Data antrean tidak ditemukan atau sudah selesai.",
            );
        }

        $update_rm = mysqli_query(
            $conn,
            "
            UPDATE rekam_medis
            SET
                id_diagnosa = '$id_diag',
                keluhan = '$keluhan',
                hasil_pemeriksaan = '$hasil',
                status = 'Selesai'
            WHERE id_rekam_medis = '$id_rm'
            AND id_staff = '$id_dokter'
        ",
        );

        if (!$update_rm) {
            throw new Exception(
                "Gagal menyimpan pemeriksaan: " . mysqli_error($conn),
            );
        }

        foreach ($daftar_obat_pemeriksaan as $item_obat) {
            $id_obat = mysqli_real_escape_string(
                $conn,
                $item_obat["id_obat"],
            );
            $qty = (int) $item_obat["jumlah"];
            $catatan = mysqli_real_escape_string(
                $conn,
                $item_obat["catatan"],
            );

            $cek_obat = mysqli_query(
                $conn,
                "
                SELECT id_obat, nama_obat, stok_sekarang
                FROM obatm
                WHERE id_obat = '$id_obat'
                LIMIT 1
                FOR UPDATE
            ",
            );

            if (!$cek_obat) {
                throw new Exception(
                    "Query obat error: " . mysqli_error($conn),
                );
            }

            if (mysqli_num_rows($cek_obat) == 0) {
                throw new Exception("Obat tidak ditemukan.");
            }

            $obat = mysqli_fetch_assoc($cek_obat);
            $stok_saat_ini = (int) $obat["stok_sekarang"];

            if ($stok_saat_ini < $qty) {
                throw new Exception(
                    "Stok " .
                        ($obat["nama_obat"] ?? "obat") .
                        " tidak cukup. Stok tersedia: " .
                        $stok_saat_ini,
                );
            }

            $id_resep = generateUniqueResepID($conn);

            $insert_resep = mysqli_query(
                $conn,
                "
                INSERT INTO resep_dokter
                (
                    id_resep,
                    id_rekam_medis,
                    id_obat,
                    jumlah_keluar,
                    catatan_obat
                )
                VALUES
                (
                    '$id_resep',
                    '$id_rm',
                    '$id_obat',
                    '$qty',
                    '$catatan'
                )
            ",
            );

            if (!$insert_resep) {
                throw new Exception(
                    "Gagal menyimpan resep: " . mysqli_error($conn),
                );
            }

            // Beberapa database lama sudah memiliki trigger pengurangan stok.
            // Kurangi manual hanya jika trigger tersebut tidak tersedia.
            if (!triggerExists($conn, "trg_kurangi_stok_obat")) {
                $stok_baru = $stok_saat_ini - $qty;

                $update_stok = mysqli_query(
                    $conn,
                    "
                    UPDATE obatm
                    SET stok_sekarang = '$stok_baru'
                    WHERE id_obat = '$id_obat'
                ",
                );

                if (!$update_stok) {
                    throw new Exception(
                        "Gagal mengurangi stok obat: " . mysqli_error($conn),
                    );
                }
            }
        }

        mysqli_commit($conn);

        header(
            "Location: index.php?page=rekam_medis&msg=Pemeriksaan berhasil disimpan ke rekam medis",
        );
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header(
            "Location: index.php?page=antrean&err=" .
                urlencode($e->getMessage()),
        );
        exit();
    }
}

// =======================
// TAMBAH RESEP OBAT DARI HALAMAN RESEP OBAT
// Satu kali submit dapat menyimpan beberapa obat.
// Pasien bersifat opsional dan setiap obat disimpan sebagai transaksi resep terpisah.
// =======================
if (isset($_POST["add_resep_dokter"])) {
    $id_pasien = mysqli_real_escape_string($conn, trim((string) ($_POST["id_pasien"] ?? "")));

    // Penyakit/keluhan dapat lebih dari satu.
    $diagnosa_input = $_POST["id_diagnosa"] ?? [];
    if (!is_array($diagnosa_input)) {
        $diagnosa_input = [$diagnosa_input];
    }

    $diagnosa_ids = [];
    foreach ($diagnosa_input as $id_diagnosa_input) {
        $id_diagnosa_input = trim((string) $id_diagnosa_input);
        if ($id_diagnosa_input !== "") {
            $diagnosa_ids[] = $id_diagnosa_input;
        }
    }
    $diagnosa_ids = array_values(array_unique($diagnosa_ids));

    // Obat juga dapat lebih dari satu.
    $obat_input = $_POST["id_obat"] ?? [];
    $jumlah_input = $_POST["jumlah_keluar"] ?? [];
    $catatan_input = $_POST["catatan_obat"] ?? [];

    if (!is_array($obat_input)) $obat_input = [$obat_input];
    if (!is_array($jumlah_input)) $jumlah_input = [$jumlah_input];
    if (!is_array($catatan_input)) $catatan_input = [$catatan_input];

    $daftar_obat_resep = [];
    $jumlah_baris_obat = max(count($obat_input), count($jumlah_input), count($catatan_input));

    for ($i = 0; $i < $jumlah_baris_obat; $i++) {
        $id_obat_raw = trim((string) ($obat_input[$i] ?? ""));
        $qty = (int) ($jumlah_input[$i] ?? 0);
        $catatan_raw = trim((string) ($catatan_input[$i] ?? ""));

        // Baris yang sepenuhnya kosong tidak ikut disimpan.
        if ($id_obat_raw === "" && $qty <= 0 && $catatan_raw === "") {
            continue;
        }

        if ($id_obat_raw === "") {
            header("Location: index.php?page=resep_obat&err=" . urlencode("Pilih obat pada setiap baris resep yang diisi."));
            exit();
        }

        if ($qty <= 0) {
            header("Location: index.php?page=resep_obat&err=" . urlencode("Jumlah obat minimal 1 untuk setiap obat yang dipilih."));
            exit();
        }

        if ($catatan_raw === "") {
            header("Location: index.php?page=resep_obat&err=" . urlencode("Isi aturan pakai untuk setiap obat yang dipilih."));
            exit();
        }

        // Pengaman backend: jika obat yang sama terkirim dua kali, gabungkan jumlahnya.
        if (isset($daftar_obat_resep[$id_obat_raw])) {
            $daftar_obat_resep[$id_obat_raw]["jumlah"] += $qty;
            if (!str_contains($daftar_obat_resep[$id_obat_raw]["catatan"], $catatan_raw)) {
                $daftar_obat_resep[$id_obat_raw]["catatan"] .= " | " . $catatan_raw;
            }
            continue;
        }

        $daftar_obat_resep[$id_obat_raw] = [
            "id_obat" => $id_obat_raw,
            "jumlah" => $qty,
            "catatan" => $catatan_raw,
        ];
    }

    if (empty($diagnosa_ids) || empty($daftar_obat_resep)) {
        header(
            "Location: index.php?page=resep_obat&err=" .
                urlencode("Data resep belum lengkap. Pilih minimal satu penyakit/keluhan dan satu obat."),
        );
        exit();
    }

    if (!ensureResepDokterPasienColumn($conn)) {
        header(
            "Location: index.php?page=resep_obat&err=Kolom id_pasien di tabel resep_dokter belum bisa dibuat. Jalankan SQL: ALTER TABLE resep_dokter ADD COLUMN id_pasien VARCHAR(20) NULL AFTER id_resep;",
        );
        exit();
    }

    if (!ensureResepDiagnosaTable($conn)) {
        header(
            "Location: index.php?page=resep_obat&err=" .
                urlencode("Tabel resep_diagnosa belum tersedia. Import DB/update_resep_multi_penyakit.sql."),
        );
        exit();
    }

    cleanupOrphanResepDiagnosa($conn);
    mysqli_begin_transaction($conn);

    try {
        // Pasien opsional. Jika kosong akan disimpan sebagai NULL dan tampil '-'.
        if ($id_pasien !== "") {
            $cek_pasien = mysqli_query(
                $conn,
                "SELECT id_pasien FROM pasienm WHERE id_pasien = '$id_pasien' LIMIT 1",
            );
            if (!$cek_pasien) {
                throw new Exception("Query pasien error: " . mysqli_error($conn));
            }
            if (mysqli_num_rows($cek_pasien) === 0) {
                throw new Exception("Pasien tidak ditemukan.");
            }
        }

        $diagnosa_escaped = array_map(
            function ($id) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $id) . "'";
            },
            $diagnosa_ids,
        );
        $diagnosa_in = implode(",", $diagnosa_escaped);

        $cek_diagnosa = mysqli_query(
            $conn,
            "SELECT id_diagnosa FROM diagnosam WHERE id_diagnosa IN ($diagnosa_in)",
        );
        if (!$cek_diagnosa) {
            throw new Exception("Query penyakit error: " . mysqli_error($conn));
        }

        $diagnosa_valid = [];
        while ($row_diagnosa = mysqli_fetch_assoc($cek_diagnosa)) {
            $diagnosa_valid[] = $row_diagnosa["id_diagnosa"];
        }
        $diagnosa_valid = array_values(array_unique($diagnosa_valid));

        if (count($diagnosa_valid) !== count($diagnosa_ids)) {
            throw new Exception("Ada penyakit/keluhan yang tidak ditemukan pada data diagnosa.");
        }

        $id_pasien_sql = $id_pasien !== "" ? "'$id_pasien'" : "NULL";

        foreach ($daftar_obat_resep as $item_obat) {
            $id_obat = mysqli_real_escape_string($conn, $item_obat["id_obat"]);
            $jumlah_keluar = (int) $item_obat["jumlah"];
            $catatan_obat = mysqli_real_escape_string($conn, $item_obat["catatan"]);

            $cek_obat = mysqli_query(
                $conn,
                "SELECT id_obat, nama_obat, stok_sekarang
                 FROM obatm
                 WHERE id_obat = '$id_obat'
                 LIMIT 1
                 FOR UPDATE",
            );

            if (!$cek_obat) {
                throw new Exception("Query obat error: " . mysqli_error($conn));
            }
            if (mysqli_num_rows($cek_obat) === 0) {
                throw new Exception("Obat tidak ditemukan.");
            }

            $obat = mysqli_fetch_assoc($cek_obat);
            $stok_saat_ini = (int) $obat["stok_sekarang"];
            if ($stok_saat_ini < $jumlah_keluar) {
                throw new Exception(
                    "Stok " . ($obat["nama_obat"] ?? "obat") .
                    " tidak cukup. Stok tersedia: " . $stok_saat_ini,
                );
            }

            $id_resep = generateUniqueResepID($conn);
            $insert_resep = mysqli_query(
                $conn,
                "INSERT INTO resep_dokter
                 (id_resep, id_pasien, id_obat, jumlah_keluar, catatan_obat)
                 VALUES ('$id_resep', $id_pasien_sql, '$id_obat', '$jumlah_keluar', '$catatan_obat')",
            );
            if (!$insert_resep) {
                throw new Exception("Gagal menyimpan resep: " . mysqli_error($conn));
            }

            // Setiap obat pada satu submit mendapatkan daftar penyakit/keluhan yang sama.
            $nilai_resep_diagnosa = [];
            foreach ($diagnosa_valid as $id_diagnosa_valid) {
                $id_diagnosa_safe = mysqli_real_escape_string($conn, $id_diagnosa_valid);
                $nilai_resep_diagnosa[] = "('$id_resep', '$id_diagnosa_safe')";
            }

            $insert_diagnosa = mysqli_query(
                $conn,
                "INSERT IGNORE INTO resep_diagnosa (id_resep, id_diagnosa) VALUES " .
                    implode(",", $nilai_resep_diagnosa),
            );
            if (!$insert_diagnosa) {
                throw new Exception("Gagal menyimpan penyakit/keluhan resep: " . mysqli_error($conn));
            }

            if (!triggerExists($conn, "trg_kurangi_stok_obat")) {
                $stok_baru = $stok_saat_ini - $jumlah_keluar;
                $update_stok = mysqli_query(
                    $conn,
                    "UPDATE obatm SET stok_sekarang = '$stok_baru' WHERE id_obat = '$id_obat'",
                );
                if (!$update_stok) {
                    throw new Exception("Gagal mengurangi stok obat: " . mysqli_error($conn));
                }
            }
        }

        mysqli_commit($conn);
        header("Location: index.php?page=resep_obat&msg=" . urlencode("Resep obat berhasil ditambahkan."));
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $pesanError = $e->getMessage();
        if (stripos($pesanError, "Duplicate entry") !== false) {
            $pesanError = "Terjadi bentrok nomor resep. Silakan tekan Simpan sekali lagi.";
        }
        header("Location: index.php?page=resep_obat&err=" . urlencode($pesanError));
        exit();
    }
}

// =======================
// CRUD JADWAL DOKTER
// =======================
/**
 * Menjaga struktur jadwalm tetap kompatibel dengan form jadwal terbaru.
 * Versi database lama hanya memiliki Senin-Jumat dan beberapa data rusak
 * dengan nilai hari/status kosong.
 */
function sinkronkanStrukturJadwalDokter($conn)
{
    // Data lama tanpa hari tidak dapat digunakan oleh sistem booking.
    @mysqli_query(
        $conn,
        "DELETE FROM jadwalm
         WHERE tanggal = ''
            OR jam_mulai IS NULL
            OR jam_selesai IS NULL
            OR jam_selesai <= jam_mulai"
    );

    // Status kosong dari dump lama dikembalikan ke nilai yang valid.
    @mysqli_query($conn, "UPDATE jadwalm SET status = 'Buka' WHERE status = ''");

    $qKolomHari = @mysqli_query($conn, "SHOW COLUMNS FROM jadwalm LIKE 'tanggal'");
    if ($qKolomHari && ($kolomHari = mysqli_fetch_assoc($qKolomHari))) {
        $tipeHari = strtolower((string) ($kolomHari['Type'] ?? ''));
        if (strpos($tipeHari, "'sabtu'") === false || strpos($tipeHari, "'minggu'") === false) {
            @mysqli_query(
                $conn,
                "ALTER TABLE jadwalm
                 MODIFY tanggal ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL"
            );
        }
    }

    $qKolomStatus = @mysqli_query($conn, "SHOW COLUMNS FROM jadwalm LIKE 'status'");
    if ($qKolomStatus && ($kolomStatus = mysqli_fetch_assoc($qKolomStatus))) {
        $tipeStatus = strtolower((string) ($kolomStatus['Type'] ?? ''));
        if (strpos($tipeStatus, "'buka'") === false || strpos($tipeStatus, "'tutup'") === false) {
            @mysqli_query(
                $conn,
                "ALTER TABLE jadwalm
                 MODIFY status ENUM('Buka','Tutup') NOT NULL DEFAULT 'Buka'"
            );
        }
    }
}

function normalisasiJamJadwal($jam)
{
    $jam = trim((string) $jam);
    if (preg_match('/^([01]\\d|2[0-3]):[0-5]\\d$/', $jam)) {
        return $jam . ':00';
    }
    if (preg_match('/^([01]\\d|2[0-3]):[0-5]\\d:[0-5]\\d$/', $jam)) {
        return $jam;
    }
    return false;
}

function jadwalDokterDuplikatPersis($conn, $idStaff, $hari, $jamMulai, $jamSelesai, $excludeId = '')
{
    if ($excludeId !== '') {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_jadwal
             FROM jadwalm
             WHERE id_staff = ?
               AND tanggal = ?
               AND jam_mulai = ?
               AND jam_selesai = ?
               AND id_jadwal <> ?
             LIMIT 1"
        );
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 'sssss', $idStaff, $hari, $jamMulai, $jamSelesai, $excludeId);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_jadwal
             FROM jadwalm
             WHERE id_staff = ?
               AND tanggal = ?
               AND jam_mulai = ?
               AND jam_selesai = ?
             LIMIT 1"
        );
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 'ssss', $idStaff, $hari, $jamMulai, $jamSelesai);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $ada = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $ada;
}

sinkronkanStrukturJadwalDokter($conn);

$hariJadwalValid = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$statusJadwalValid = ['Buka', 'Tutup'];

// =======================
// TAMBAH JADWAL DOKTER
// =======================
if (isset($_POST['add_jadwal_dokter'])) {
    $hari = trim((string) ($_POST['tanggal'] ?? ''));
    $jamMulai = normalisasiJamJadwal($_POST['jam_mulai'] ?? '');
    $jamSelesai = normalisasiJamJadwal($_POST['jam_selesai'] ?? '');
    $status = trim((string) ($_POST['status'] ?? ''));

    if ($hari === '' || $jamMulai === false || $jamSelesai === false || $status === '') {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Semua data jadwal wajib diisi dengan benar.'));
        exit();
    }

    if (!in_array($hari, $hariJadwalValid, true)) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Hari jadwal tidak valid.'));
        exit();
    }

    if (!in_array($status, $statusJadwalValid, true)) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Status jadwal tidak valid.'));
        exit();
    }

    if ($jamSelesai <= $jamMulai) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Jam selesai harus lebih besar dari jam mulai.'));
        exit();
    }

    $idJadwal = generateIDUrut($conn, 'JDW', 'jadwalm', 'id_jadwal', 3);
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO jadwalm (id_jadwal, id_staff, tanggal, jam_mulai, jam_selesai, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(6))"
    );

    if (!$stmt) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Jadwal gagal diproses. Silakan coba kembali.'));
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'ssssss', $idJadwal, $id_dokter, $hari, $jamMulai, $jamSelesai, $status);
    $berhasil = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$berhasil) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Jadwal gagal ditambahkan. Silakan coba kembali.'));
        exit();
    }

    header('Location: index.php?page=jadwal_dokter&msg=' . urlencode('Jadwal dokter berhasil ditambahkan.'));
    exit();
}

// =======================
// UBAH JADWAL DOKTER
// =======================
if (isset($_POST['update_jadwal_dokter'])) {
    $idJadwal = trim((string) ($_POST['id_jadwal'] ?? ''));
    $hari = trim((string) ($_POST['tanggal'] ?? ''));
    $jamMulai = normalisasiJamJadwal($_POST['jam_mulai'] ?? '');
    $jamSelesai = normalisasiJamJadwal($_POST['jam_selesai'] ?? '');
    $status = trim((string) ($_POST['status'] ?? ''));

    if ($idJadwal === '' || $hari === '' || $jamMulai === false || $jamSelesai === false || $status === '') {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Data jadwal belum lengkap atau tidak valid.'));
        exit();
    }

    if (!in_array($hari, $hariJadwalValid, true) || !in_array($status, $statusJadwalValid, true)) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Data hari atau status jadwal tidak valid.'));
        exit();
    }

    if ($jamSelesai <= $jamMulai) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Jam selesai harus lebih besar dari jam mulai.'));
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE jadwalm
         SET tanggal = ?, jam_mulai = ?, jam_selesai = ?, status = ?
         WHERE id_jadwal = ? AND id_staff = ?"
    );

    if (!$stmt) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Jadwal gagal diproses. Silakan coba kembali.'));
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'ssssss', $hari, $jamMulai, $jamSelesai, $status, $idJadwal, $id_dokter);
    $berhasil = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if (!$berhasil) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Jadwal gagal diperbarui. Silakan coba kembali.'));
        exit();
    }

    if ($affected === 0) {
        $stmtCek = mysqli_prepare($conn, 'SELECT id_jadwal FROM jadwalm WHERE id_jadwal = ? AND id_staff = ? LIMIT 1');
        if ($stmtCek) {
            mysqli_stmt_bind_param($stmtCek, 'ss', $idJadwal, $id_dokter);
            mysqli_stmt_execute($stmtCek);
            mysqli_stmt_store_result($stmtCek);
            $masihAda = mysqli_stmt_num_rows($stmtCek) > 0;
            mysqli_stmt_close($stmtCek);
            if (!$masihAda) {
                header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Jadwal tidak ditemukan atau bukan milik dokter yang sedang masuk.'));
                exit();
            }
        }
    }

    header('Location: index.php?page=jadwal_dokter&msg=' . urlencode('Jadwal dokter berhasil diperbarui.'));
    exit();
}

// =======================
// HAPUS JADWAL DOKTER
// =======================
if (isset($_POST['hapus_jadwal_dokter'])) {
    $idJadwal = trim((string) ($_POST['id_jadwal'] ?? ''));
    if ($idJadwal === '') {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Jadwal yang akan dihapus tidak valid.'));
        exit();
    }

    $stmt = mysqli_prepare($conn, 'DELETE FROM jadwalm WHERE id_jadwal = ? AND id_staff = ?');
    if (!$stmt) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Jadwal gagal diproses. Silakan coba kembali.'));
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'ss', $idJadwal, $id_dokter);
    $berhasil = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if (!$berhasil) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Jadwal gagal dihapus. Silakan coba kembali.'));
        exit();
    }

    if ($affected < 1) {
        header('Location: index.php?page=jadwal_dokter&err=' . urlencode('Jadwal tidak ditemukan atau sudah dihapus.'));
        exit();
    }

    header('Location: index.php?page=jadwal_dokter&msg=' . urlencode('Jadwal dokter berhasil dihapus.'));
    exit();
}

// =======================
// KONFIRMASI PENERIMAAN PENGADAAN OBAT
// Dokter hanya melakukan konfirmasi bahwa obat sudah diterima.
// Status Pending -> Diterima dan stok bertambah satu kali.
// Pengadaan yang sudah Batal atau Diterima tidak dapat dikonfirmasi.
// =======================
if (isset($_POST["konfirmasi_pengadaan"])) {
    $id_pengadaan_status = trim((string) ($_POST["id_pengadaan"] ?? ""));
    $jumlah_diterima_input = (int) ($_POST["jumlah_diterima"] ?? 0);

    if ($id_pengadaan_status === "") {
        header("Location: index.php?page=pengadaan_obat&err=" . urlencode("Data pengadaan tidak valid."));
        exit();
    }

    mysqli_begin_transaction($conn);
    try {
        $stmtPengadaan = mysqli_prepare(
            $conn,
            "SELECT id_obat, jumlah_order, jumlah_diterima, status
             FROM pengadaan_obat
             WHERE id_pengadaan = ?
             FOR UPDATE"
        );
        if (!$stmtPengadaan) {
            throw new Exception("Data pengadaan tidak dapat diperiksa.");
        }
        mysqli_stmt_bind_param($stmtPengadaan, "s", $id_pengadaan_status);
        mysqli_stmt_execute($stmtPengadaan);
        $hasilPengadaan = mysqli_stmt_get_result($stmtPengadaan);
        $dataPengadaanStatus = mysqli_fetch_assoc($hasilPengadaan);
        mysqli_stmt_close($stmtPengadaan);

        if (!$dataPengadaanStatus) {
            throw new Exception("Data pengadaan tidak ditemukan.");
        }

        $status_lama = $dataPengadaanStatus["status"] ?? "Pending";
        if ($status_lama === "Diterima") {
            throw new Exception("Obat pada pengadaan ini sudah diterima.");
        }
        if ($status_lama === "Batal") {
            throw new Exception("Pengadaan ini sudah dibatalkan karena melewati batas 5 hari dan tidak dapat dikonfirmasi.");
        }
        if ($status_lama === "Proses") {
            $status_lama = "Pending";
        }
        if ($status_lama !== "Pending") {
            throw new Exception("Status pengadaan tidak dapat dikonfirmasi.");
        }

        $jumlah_order = (int) $dataPengadaanStatus["jumlah_order"];
        if ($jumlah_diterima_input <= 0) {
            throw new Exception("Jumlah obat yang diterima harus lebih dari 0.");
        }
        if ($jumlah_diterima_input > $jumlah_order) {
            throw new Exception("Jumlah yang diterima tidak boleh melebihi jumlah yang dipesan.");
        }

        $stmtUpdateStatus = mysqli_prepare(
            $conn,
            "UPDATE pengadaan_obat
             SET status = 'Diterima', jumlah_diterima = ?, tgl_diterima = NOW()
             WHERE id_pengadaan = ? AND status IN ('Pending', 'Proses')"
        );
        if (!$stmtUpdateStatus) {
            throw new Exception("Status pengadaan tidak dapat diperbarui.");
        }
        mysqli_stmt_bind_param($stmtUpdateStatus, "is", $jumlah_diterima_input, $id_pengadaan_status);
        if (!mysqli_stmt_execute($stmtUpdateStatus) || mysqli_stmt_affected_rows($stmtUpdateStatus) < 1) {
            mysqli_stmt_close($stmtUpdateStatus);
            throw new Exception("Pengadaan tidak dapat dikonfirmasi. Muat ulang halaman lalu coba kembali.");
        }
        mysqli_stmt_close($stmtUpdateStatus);

        $id_obat_diterima = (string) $dataPengadaanStatus["id_obat"];
        $stmtTambahStok = mysqli_prepare(
            $conn,
            "UPDATE obatm SET stok_sekarang = stok_sekarang + ? WHERE id_obat = ?"
        );
        if (!$stmtTambahStok) {
            throw new Exception("Stok obat tidak dapat diperbarui.");
        }
        mysqli_stmt_bind_param($stmtTambahStok, "is", $jumlah_diterima_input, $id_obat_diterima);
        if (!mysqli_stmt_execute($stmtTambahStok)) {
            throw new Exception("Stok obat tidak dapat diperbarui.");
        }
        mysqli_stmt_close($stmtTambahStok);

        mysqli_commit($conn);
        header(
            "Location: index.php?page=pengadaan_obat&msg=" .
                urlencode("Pengadaan berhasil dikonfirmasi. Stok bertambah " . $jumlah_diterima_input . " unit sesuai jumlah yang benar-benar diterima.")
        );
        exit();
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        header("Location: index.php?page=pengadaan_obat&err=" . urlencode($e->getMessage()));
        exit();
    }
}

// =======================
// TAMBAH PENGADAAN OBAT
// =======================
if (isset($_POST["add_pengadaan_obat"])) {
    $id_pengadaan = generateIDUrut(
        $conn,
        "PGD",
        "pengadaan_obat",
        "id_pengadaan",
        3,
    );
    $id_obat = mysqli_real_escape_string($conn, $_POST["id_obat"] ?? "");
    $id_supplier = mysqli_real_escape_string(
        $conn,
        $_POST["id_supplier"] ?? "",
    );
    $jumlah_order = (int) ($_POST["jumlah_order"] ?? 0);
    $tgl_estimasi = mysqli_real_escape_string(
        $conn,
        $_POST["tgl_estimasi_tiba"] ?? "",
    );

    if ($id_obat == "" || $jumlah_order == 0) {
        header(
            "Location: index.php?page=pengadaan_obat&err=Data pengadaan belum lengkap",
        );
        exit();
    }

    if ($jumlah_order <= 0) {
        header(
            "Location: index.php?page=pengadaan_obat&err=Jumlah order harus lebih dari 0",
        );
        exit();
    }

    // Target tiba tidak boleh berada sebelum tanggal pengadaan (tanggal hari ini).
    if ($tgl_estimasi !== "" && $tgl_estimasi < date("Y-m-d")) {
        header(
            "Location: index.php?page=pengadaan_obat&err=" .
                urlencode("Target tiba tidak boleh lebih awal dari tanggal pengadaan."),
        );
        exit();
    }

    $insert = mysqli_query(
        $conn,
        "
        INSERT INTO pengadaan_obat
        (id_pengadaan, id_obat, id_supplier, jumlah_order, tgl_order, tgl_estimasi_tiba, status, created_at)
        VALUES
        ('$id_pengadaan', '$id_obat', '$id_supplier', $jumlah_order, DATE(NOW()), '$tgl_estimasi', 'Pending', NOW(6))
    ",
    );

    if (!$insert) {
        header(
            "Location: index.php?page=pengadaan_obat&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    header(
        "Location: index.php?page=pengadaan_obat&msg=Pengadaan obat berhasil ditambahkan",
    );
    exit();
}

// =======================
// TAMBAH OBAT
// =======================
if (isset($_POST["add_obat"])) {
    $id_obat = generateIDUrut($conn, "OBT", "obatm", "id_obat", 3);
    $nama_obat = mysqli_real_escape_string($conn, $_POST["nama_obat"] ?? "");
    $stok_sekarang = (int) ($_POST["stok_sekarang"] ?? 0);
    $stok_minimum = (int) ($_POST["stok_minimum"] ?? 10);
    $stok_target = (int) ($_POST["stok_target"] ?? 100);
    $satuan = mysqli_real_escape_string($conn, $_POST["satuan"] ?? "");
    $harga_per_pcs = (float) ($_POST["harga_per_pcs"] ?? 0);

    if ($nama_obat == "" || $satuan == "") {
        $harga_per_pcs = (float) ($_POST["harga_per_pcs"] ?? 0);
        header(
            "Location: index.php?page=obat&err=Nama obat dan satuan wajib diisi",
        );
        exit();
    }

    $insert = mysqli_query(
        $conn,
        "
        INSERT INTO obatm
        (
            id_obat,
            nama_obat,
            stok_sekarang,
            stok_minimum,
            stok_target,
            satuan,
            harga_per_pcs,
            created_at
        )
        VALUES
        (
            '$id_obat',
            '$nama_obat',
            '$stok_sekarang',
            '$stok_minimum',
            '$stok_target',
            '$satuan',
            '$harga_per_pcs',
            NOW(6)
        )
    ",
    );

    if (!$insert) {
        header(
            "Location: index.php?page=obat&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    header(
        "Location: index.php?page=obat&msg=Obat berhasil ditambahkan",
    );
    exit();
}

// =======================
// UPDATE OBAT
// =======================
if (isset($_POST["update_obat"])) {
    $id_obat = mysqli_real_escape_string($conn, $_POST["id_obat"] ?? "");
    $nama_obat = mysqli_real_escape_string($conn, $_POST["nama_obat"] ?? "");
    $stok_sekarang = (int) ($_POST["stok_sekarang"] ?? 0);
    $stok_minimum = (int) ($_POST["stok_minimum"] ?? 0);
    $stok_target = (int) ($_POST["stok_target"] ?? 0);
    $satuan = mysqli_real_escape_string($conn, $_POST["satuan"] ?? "");
    $harga_per_pcs = (float) ($_POST["harga_per_pcs"] ?? 0);

    $update = mysqli_query(
        $conn,
        "
        UPDATE obatm
        SET
            nama_obat = '$nama_obat',
            stok_sekarang = '$stok_sekarang',
            stok_minimum = '$stok_minimum',
            stok_target = '$stok_target',
            satuan = '$satuan',
            harga_per_pcs = '$harga_per_pcs'
        WHERE id_obat = '$id_obat'
    ",
    );

    if (!$update) {
        header(
            "Location: index.php?page=obat&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    header(
        "Location: index.php?page=obat&msg=Obat berhasil diperbarui",
    );
    exit();
}

// =======================
// HAPUS OBAT
// =======================
if (isset($_POST["hapus_obat"])) {
    $id_obat = mysqli_real_escape_string($conn, $_POST["id_obat"] ?? "");

    $hapus = mysqli_query(
        $conn,
        "
        DELETE FROM obatm
        WHERE id_obat = '$id_obat'
    ",
    );

    if (!$hapus) {
        header(
            "Location: index.php?page=obat&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    header(
        "Location: index.php?page=obat&msg=Obat berhasil dihapus",
    );
    exit();
}

// =======================
// TAMBAH DIAGNOSA
// =======================
if (isset($_POST["add_diagnosa"])) {
    $id_diagnosa = generateID($conn, "DG", "diagnosam", "id_diagnosa");
    $nama_penyakit = mysqli_real_escape_string(
        $conn,
        $_POST["nama_penyakit"] ?? "",
    );
    $kategori = mysqli_real_escape_string($conn, $_POST["kategori"] ?? "Umum");
    $tipe = mysqli_real_escape_string($conn, $_POST["tipe"] ?? "Ringan");

    if ($nama_penyakit == "") {
        header(
            "Location: index.php?page=diagnosa&err=Nama penyakit wajib diisi",
        );
        exit();
    }

    $insert = mysqli_query(
        $conn,
        "
        INSERT INTO diagnosam
        (
            id_diagnosa,
            nama_penyakit,
            kategori,
            tipe,
            created_at
        )
        VALUES
        (
            '$id_diagnosa',
            '$nama_penyakit',
            '$kategori',
            '$tipe',
            NOW(6)
        )
    ",
    );

    if (!$insert) {
        header(
            "Location: index.php?page=diagnosa&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    header(
        "Location: index.php?page=diagnosa&msg=Diagnosa berhasil ditambahkan",
    );
    exit();
}

// =======================
// UPDATE DIAGNOSA
// =======================
if (isset($_POST["update_diagnosa"])) {
    $id_diagnosa = mysqli_real_escape_string(
        $conn,
        $_POST["id_diagnosa"] ?? "",
    );
    $nama_penyakit = mysqli_real_escape_string(
        $conn,
        $_POST["nama_penyakit"] ?? "",
    );
    $kategori = mysqli_real_escape_string($conn, $_POST["kategori"] ?? "");
    $tipe = mysqli_real_escape_string($conn, $_POST["tipe"] ?? "");

    $update = mysqli_query(
        $conn,
        "
        UPDATE diagnosam
        SET
            nama_penyakit = '$nama_penyakit',
            kategori = '$kategori',
            tipe = '$tipe'
        WHERE id_diagnosa = '$id_diagnosa'
    ",
    );

    if (!$update) {
        header(
            "Location: index.php?page=diagnosa&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    header(
        "Location: index.php?page=diagnosa&msg=Diagnosa berhasil diperbarui",
    );
    exit();
}

// =======================
// HAPUS DIAGNOSA + DATA TERKAIT
// =======================
if (isset($_POST["hapus_diagnosa"])) {
    $id_diagnosa = trim((string) ($_POST["id_diagnosa"] ?? ""));

    if ($id_diagnosa === "") {
        header("Location: index.php?page=diagnosa&err=" . urlencode("Data diagnosa tidak ditemukan."));
        exit();
    }

    mysqli_begin_transaction($conn);

    try {
        // Resep yang terkait dengan rekam medis berdiagnosa ini selalu ikut dihapus.
        // Resep input langsung hanya dihapus jika diagnosa ini adalah satu-satunya penyakit pada resep tersebut.
        $stmtResep = mysqli_prepare($conn, "
            SELECT DISTINCT rd.id_resep, rd.id_obat, rd.jumlah_keluar
            FROM resep_dokter rd
            LEFT JOIN rekam_medis rm ON rm.id_rekam_medis = rd.id_rekam_medis
            WHERE rm.id_diagnosa = ?
               OR (
                    EXISTS (
                        SELECT 1 FROM resep_diagnosa x
                        WHERE x.id_resep = rd.id_resep AND x.id_diagnosa = ?
                    )
                    AND NOT EXISTS (
                        SELECT 1 FROM resep_diagnosa y
                        WHERE y.id_resep = rd.id_resep AND y.id_diagnosa <> ?
                    )
               )
        ");
        mysqli_stmt_bind_param($stmtResep, "sss", $id_diagnosa, $id_diagnosa, $id_diagnosa);
        mysqli_stmt_execute($stmtResep);
        $hasilResep = mysqli_stmt_get_result($stmtResep);
        $resepDihapus = [];
        while ($resep = mysqli_fetch_assoc($hasilResep)) {
            $resepDihapus[] = $resep;
        }
        mysqli_stmt_close($stmtResep);

        // Kembalikan stok untuk transaksi resep yang benar-benar ikut dihapus.
        $stmtStok = mysqli_prepare($conn, "UPDATE obatm SET stok_sekarang = stok_sekarang + ? WHERE id_obat = ?");
        foreach ($resepDihapus as $resep) {
            $qty = max(0, (int) ($resep["jumlah_keluar"] ?? 0));
            $idObat = (string) ($resep["id_obat"] ?? "");
            if ($qty > 0 && $idObat !== "") {
                mysqli_stmt_bind_param($stmtStok, "is", $qty, $idObat);
                mysqli_stmt_execute($stmtStok);
            }
        }
        mysqli_stmt_close($stmtStok);

        // Hapus seluruh relasi penyakit target terlebih dahulu agar foreign key tidak mengunci.
        if (tableExists($conn, "resep_diagnosa")) {
            $stmtRelasi = mysqli_prepare($conn, "DELETE FROM resep_diagnosa WHERE id_diagnosa = ?");
            mysqli_stmt_bind_param($stmtRelasi, "s", $id_diagnosa);
            mysqli_stmt_execute($stmtRelasi);
            mysqli_stmt_close($stmtRelasi);
        }

        // Hapus resep yang harus ikut hilang.
        $stmtHapusResep = mysqli_prepare($conn, "DELETE FROM resep_dokter WHERE id_resep = ?");
        foreach ($resepDihapus as $resep) {
            $idResep = (string) $resep["id_resep"];
            mysqli_stmt_bind_param($stmtHapusResep, "s", $idResep);
            mysqli_stmt_execute($stmtHapusResep);
        }
        mysqli_stmt_close($stmtHapusResep);

        // Hapus rekam medis utama yang memakai diagnosa ini.
        $stmtRm = mysqli_prepare($conn, "DELETE FROM rekam_medis WHERE id_diagnosa = ?");
        mysqli_stmt_bind_param($stmtRm, "s", $id_diagnosa);
        mysqli_stmt_execute($stmtRm);
        $jumlahRm = mysqli_stmt_affected_rows($stmtRm);
        mysqli_stmt_close($stmtRm);

        // Terakhir hapus master diagnosa.
        $stmtDiag = mysqli_prepare($conn, "DELETE FROM diagnosam WHERE id_diagnosa = ?");
        mysqli_stmt_bind_param($stmtDiag, "s", $id_diagnosa);
        mysqli_stmt_execute($stmtDiag);
        $jumlahDiagnosa = mysqli_stmt_affected_rows($stmtDiag);
        mysqli_stmt_close($stmtDiag);

        if ($jumlahDiagnosa < 1) {
            throw new RuntimeException("Diagnosa sudah tidak tersedia.");
        }

        mysqli_commit($conn);
        $jumlahResep = count($resepDihapus);
        $pesan = "Diagnosa berhasil dihapus. $jumlahRm rekam medis dan $jumlahResep resep terkait ikut dibersihkan.";
        header("Location: index.php?page=diagnosa&msg=" . urlencode($pesan));
        exit();
    } catch (Throwable $error) {
        mysqli_rollback($conn);
        header("Location: index.php?page=diagnosa&err=" . urlencode("Diagnosa gagal dihapus: " . $error->getMessage()));
        exit();
    }
}

// =======================
// DATA UNTUK FORM PEMERIKSAAN
// =======================
$qDiagnosaSelect = mysqli_query(
    $conn,
    "
    SELECT id_diagnosa, nama_penyakit
    FROM diagnosam
    ORDER BY nama_penyakit ASC
",
);

$diagnosa_options = [];

if ($qDiagnosaSelect) {
    while ($dx = mysqli_fetch_assoc($qDiagnosaSelect)) {
        $diagnosa_options[] = $dx;
    }
}

$qObatSelect = mysqli_query(
    $conn,
    "
    SELECT id_obat, nama_obat, stok_sekarang, satuan
    FROM obatm
    ORDER BY nama_obat ASC
",
);

$obat_options = [];

if ($qObatSelect) {
    while ($ob = mysqli_fetch_assoc($qObatSelect)) {
        $obat_options[] = $ob;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Dokter - ASTARhealth</title>

    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    <style>
        :root {
            --astar-blue: #0057B8;
            --astar-blue-light: #2E86F0;
            --astar-blue-deep: #003D82;
            --astar-soft: #eef4ff;
            --astar-mist: #dbe9ff;
            --danger-soft: #fff1f2;
            --r-sm: 12px;
            --r-md: 18px;
            --r-lg: 26px;
            --shadow-soft: 0 16px 36px rgba(15, 61, 130, 0.10);
            --shadow-card: 0 10px 24px rgba(15, 61, 130, 0.06);
        }

        * { scrollbar-width: thin; scrollbar-color: var(--astar-mist) transparent; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(1200px 600px at 100% -10%, #eaf2ff 0%, #f4f7fa 45%) fixed;
            color: #334155;
        }

        .top-header {
            height: 74px;
            background: linear-gradient(115deg, var(--astar-blue-deep) 0%, var(--astar-blue) 45%, var(--astar-blue-light) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1001;
            box-shadow: var(--shadow-soft);
        }

        #digitalClock {
            font-weight: 700;
            font-size: 14px;
            background: rgba(255,255,255,0.16);
            backdrop-filter: blur(6px);
            padding: 6px 18px;
            border-radius: 999px;
        }

.sidebar {
    width: 280px;
    height: calc(100vh - 74px);
    background: #ffffff;
    position: fixed;
    left: 0;
    top: 74px;
    display: flex;
    flex-direction: column; /* Mengatur susunan vertikal */
    transition: all 0.3s ease;
    z-index: 1000;
    overflow-y: auto; 
    padding-bottom: 40px;
}

.sidebar-menu {
    flex: 1; /* Memberi ruang otomatis untuk menu */
    overflow-y: auto; /* Aktifkan scroll di sini */
    padding-bottom: 20px;
}

.sidebar-footer {
    flex-shrink: 0; /* Mencegah footer mengecil */
    border-top: 1px solid #f1f5f9;
    padding-bottom: 10px;
    background: #fff;
}

        .main-content {
            margin-left: 280px;
            padding: 108px 40px 40px;
            transition: all 0.3s ease;
            animation: fadeIn 0.4s ease;
        }

        body.sidebar-toggled .sidebar {
            left: -280px;
        }

        body.sidebar-toggled .main-content {
            margin-left: 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                left: -280px;
            }

            body.sidebar-toggled .sidebar {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 100px 20px 40px;
            }
        }

#sidebarToggle {
    cursor: pointer;
    font-size: 1.5rem;
    padding: 5px 10px;
    border-radius: 8px;
    position: relative;
    z-index: 1100; /* Pastikan di atas elemen lain */
}

        #sidebarToggle:hover {
            background: rgba(255,255,255,0.12);
        }

        .nav-group-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 800;
            letter-spacing: 1px;
            padding: 20px 25px 8px;
        }

        .nav-link {
            margin: 0 15px;
            padding: 12px 22px;
            border-radius: var(--r-sm);
            color: #64748b;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            margin-bottom: 2px;
        }

        .nav-link i {
            width: 35px;
            font-size: 1.15rem;
        }

        .nav-link:hover {
            background: var(--astar-soft);
            color: var(--astar-blue);
            transform: translateX(5px);
        }

        .nav-link.active {
            background: linear-gradient(120deg, var(--astar-blue) 0%, var(--astar-blue-light) 100%);
            color: white;
            box-shadow: 0 10px 22px rgba(0,87,184,0.28);
        }

        .nav-link-logout { color: rgba(17, 112, 221, 0.77); }
        .nav-link-logout:hover { background: #fdecec; color: #dc3545; }

        .data-container {
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            border-radius: var(--r-lg);
            padding: 28px;
            border: 1px solid rgba(15,61,130,0.04);
            box-shadow: var(--shadow-card);
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, var(--astar-soft) 160%);
            border-radius: var(--r-lg);
            padding: 24px;
            border: 1px solid rgba(15,61,130,0.05);
            box-shadow: var(--shadow-card);
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
            transition: 0.3s;
        }

        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-soft); }

        .icon-badge {
            width: 54px; height: 54px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: #fff; flex-shrink: 0;
            background: linear-gradient(135deg, var(--astar-blue), var(--astar-blue-light));
            box-shadow: 0 10px 22px rgba(0,87,184,0.28);
        }
        .icon-badge.danger { background: linear-gradient(135deg, #ef4444, #dc3545); box-shadow: 0 10px 22px rgba(220,53,69,0.3); }
        .icon-badge.success { background: linear-gradient(135deg, #23d3a0, #198754); box-shadow: 0 10px 22px rgba(25,135,84,0.3); }
        .icon-badge.warning { background: linear-gradient(135deg, #f6c23e, #f4a11d); box-shadow: 0 10px 22px rgba(246,162,29,0.3); }

        .stat-card.danger { border-color: rgba(220,53,69,0.15); }
        .stat-card.success { border-color: rgba(25,135,84,0.15); }

        .queue-card {
            border-radius: var(--r-lg);
            border: 1px solid rgba(15,61,130,0.06);
            background: white;
            padding: 24px;
            box-shadow: var(--shadow-card);
            transition: all 0.2s ease;
        }

        .queue-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-soft);
        }

        .queue-card.darurat {
            border-color: rgba(220,53,69,0.25);
            background: linear-gradient(135deg, #ffffff 0%, #fff1f2 100%);
        }

        .queue-number {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--astar-blue), var(--astar-blue-light));
            color: white;
            font-size: 24px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .queue-number.darurat {
            background: linear-gradient(135deg, #ef4444, #dc3545);
        }

        .data-container .table-responsive { border-radius: var(--r-md); overflow: hidden; border: 1px solid rgba(15,61,130,0.06); }

        .table thead th {
            background: var(--astar-soft);
            color: var(--astar-blue-deep);
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 800;
            border: none;
            padding: 15px;
        }

        .table td {
            vertical-align: middle;
            border-color: rgba(15,61,130,0.06);
        }

        .table-hover tbody tr:hover { background: var(--astar-soft); }

        .table .btn-light {
            border-radius: var(--r-sm);
            background: #f4f7fb;
            border: 1px solid rgba(15,61,130,0.05);
        }
        .table .btn-light:hover { background: var(--astar-mist); }

        .form-control,
        .form-select {
            border-radius: var(--r-sm) !important;
            padding: 12px 14px;
            background: #f6f8fc !important;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 4px rgba(0,87,184,0.12) !important;
            background: #fff !important;
            border-color: var(--astar-blue-light) !important;
        }
        .input-group-text { border-radius: var(--r-sm) 0 0 var(--r-sm) !important; background: #f6f8fc !important; }

        .btn { border-radius: var(--r-sm); }
        .btn-primary {
            background: linear-gradient(120deg, var(--astar-blue), var(--astar-blue-light));
            border: none;
            box-shadow: 0 10px 22px rgba(0,87,184,0.25);
        }
        .btn-primary:hover {
            filter: brightness(1.06);
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(0,87,184,0.3);
        }

        .modal-content { border-radius: var(--r-lg) !important; overflow: hidden; box-shadow: var(--shadow-soft); border: none; }
        .modal-header.bg-primary { background: linear-gradient(120deg, var(--astar-blue-deep), var(--astar-blue) 60%, var(--astar-blue-light)) !important; }

        .alert-success { background: linear-gradient(120deg, #e9fbf3, #f2fffa) !important; border: 1px solid rgba(28,200,138,0.18) !important; color: #0f9d68 !important; }
        .alert-danger { background: linear-gradient(120deg, #fff1f2, #fff6f6) !important; border: 1px solid rgba(220,53,69,0.18) !important; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .hover-shadow:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-soft) !important;
        }

        .bg-soft-primary {
            background-color: var(--astar-soft);
            color: var(--astar-blue);
        }

        .fw-600 { font-weight: 600; }

        /* ===== Rujukan page ===== */
        .rujukan-card {
            border-radius: var(--r-lg);
            border: 1px solid rgba(15,61,130,0.06);
            background: white;
            padding: 22px;
            box-shadow: var(--shadow-card);
            transition: all 0.2s ease;
        }
        .rujukan-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-soft); }
        .rujukan-icon {
            width: 54px; height: 54px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem; color: #fff; flex-shrink: 0;
            background: linear-gradient(135deg, var(--astar-blue), var(--astar-blue-light));
            box-shadow: 0 10px 22px rgba(0,87,184,0.25);
        }
        .rujukan-id-badge {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .5px;
            color: var(--astar-blue);
            background: var(--astar-soft);
            padding: 3px 10px;
            border-radius: 999px;
        }

        #hasilPencarian {
            position: absolute;
            z-index: 9999 !important;
            width: 100%;
            background: white;
            border: 1px solid rgba(15,61,130,0.08);
            border-radius: var(--r-sm);
            box-shadow: var(--shadow-soft);
            margin-top: 6px;
            overflow: hidden;
        }

        .search-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9 !important;
            background: #fff;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .search-item:last-child { border-bottom: none !important; }
        .search-item:hover,
        .search-item:focus {
            background-color: var(--astar-soft);
            outline: none;
        }
        .search-item-id {
            color: var(--astar-blue);
            font-size: 12px;
            font-weight: 800;
        }
        .search-item-name {
            color: #1f2937;
            font-size: 14px;
            font-weight: 600;
        }
        .search-state {
            padding: 14px;
            color: #64748b;
            font-size: 13px;
            text-align: center;
        }
        .search-state-error {
            color: #b42318;
            background: #fff5f5;
        }


        /* ===== Searchable Select modal resep obat ===== */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 48px;
            border: none !important;
            background: #f6f8fc !important;
            border-radius: var(--r-sm) !important;
            padding: 7px 10px;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            box-shadow: 0 0 0 4px rgba(0,87,184,0.12) !important;
            background: #fff !important;
            border-color: var(--astar-blue-light) !important;
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            border: 1px solid rgba(15,61,130,0.08);
            border-radius: var(--r-sm);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
        }

        .select2-container--bootstrap-5 .select2-results__option {
            padding: 10px 12px;
            font-size: 14px;
        }

        .select2-container--bootstrap-5 .select2-search__field {
            border-radius: var(--r-sm) !important;
        }

        .select2-obat-stock {
            font-size: 12px;
            color: #64748b;
            font-weight: 700;
        }

        /* Validasi modal pemeriksaan tanpa pesan bawaan browser. */
        .pemeriksaan-form .is-invalid {
            border: 2px solid #dc3545 !important;
            box-shadow: 0 0 0 0.18rem rgba(220, 53, 69, 0.14) !important;
            background-image: none !important;
        }

        .pemeriksaan-form .form-control,
        .pemeriksaan-form .form-select {
            transition: border-color .2s ease, box-shadow .2s ease;
        }
    </style>
</head>

<body>

<header class="top-header">
    <div class="d-flex align-items-center gap-3">
        <div id="sidebarToggle">
            <i class="bi bi-list"></i>
        </div>

        <img src="../assets/img/logoA.png" style="max-height: 70px; filter: brightness(0) invert(1);">

        <div id="digitalClock" class="d-none d-md-block"></div>
    </div>

    <div class="dropdown">
        <a href="#" data-bs-toggle="dropdown" class="text-white text-decoration-none d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block lh-1">
                <div class="fw-bold mb-1"><?= e($doctor_name) ?></div>
                <small style="opacity: 0.8; font-size: 10px;">Dokter Klinik</small>
            </div>

            <i class="bi bi-person-circle fs-2"></i>
        </a>
    </div>
</header>

<div class="sidebar">
    <!-- Tambahkan pembungkus sidebar-menu di sini -->
    <div class="sidebar-menu">
        <div class="nav-group-title">Menu Dokter</div>
        <nav class="nav flex-column">
            <a class="nav-link <?= $active_page == "antrean"
                ? "active"
                : "" ?>" href="index.php?page=antrean">
                <i class="bi bi-list-ol"></i> Antrean Pasien
            </a>
            <a class="nav-link <?= $active_page == "rekam_medis"
                ? "active"
                : "" ?>" href="index.php?page=rekam_medis">
                <i class="bi bi-clipboard2-pulse-fill"></i> Rekam Medis
            </a>
            <a class="nav-link <?= $active_page == "resep_obat"
                ? "active"
                : "" ?>" href="index.php?page=resep_obat">
                <i class="bi bi-receipt-cutoff"></i> Resep Obat
            </a>
            <a class="nav-link <?= $active_page == "rujukan"
                ? "active"
                : "" ?>" href="?page=rujukan">
                <i class="bi bi-file-earmark-medical"></i> Rujukan Pasien
            </a>
            <a class="nav-link <?= $active_page == "pengadaan_obat"
                ? "active"
                : "" ?>" href="index.php?page=pengadaan_obat">
                <i class="bi bi-box-seam"></i> Pengadaan Obat
            </a>
            <a class="nav-link <?= $active_page == "jadwal_dokter"
                ? "active"
                : "" ?>" href="index.php?page=jadwal_dokter">
                <i class="bi bi-calendar-week-fill"></i> Jadwal Dokter
            </a>
        </nav>

        <div class="nav-group-title">Data Utama</div>
        <nav class="nav flex-column">
            <a class="nav-link <?= $active_page == "obat"
                ? "active"
                : "" ?>" href="index.php?page=obat">
                <i class="bi bi-capsule-pill"></i> Data Obat
            </a>
            <a class="nav-link <?= $active_page == "diagnosa"
                ? "active"
                : "" ?>" href="index.php?page=diagnosa">
                <i class="bi bi-journal-medical"></i> Data Diagnosa
            </a>
            <a class="nav-link <?= $active_page == "pasien"
                ? "active"
                : "" ?>" href="index.php?page=pasien">
                <i class="bi bi-people-fill"></i> Data Pasien
            </a>
        </nav>

        <div class="nav-group-title">Laporan</div>
        <nav class="nav flex-column">
            <a class="nav-link <?= $active_page == "laporan_siloam"
                ? "active"
                : "" ?>" href="index.php?page=laporan_siloam">
                <i class="bi bi-file-earmark-bar-graph"></i> Laporan Siloam
            </a>
            <a class="nav-link <?= $active_page == "laporan_dinkes"
                ? "active"
                : "" ?>" href="index.php?page=laporan_dinkes">
                <i class="bi bi-clipboard2-data"></i> Laporan Dinkes
            </a>
            <a class="nav-link <?= $active_page == "laporan_internal_pasien"
                ? "active"
                : "" ?>" href="index.php?page=laporan_internal_pasien">
                <i class="bi bi-person-lines-fill"></i> Laporan Internal Pasien
            </a>
            <a class="nav-link <?= $active_page == "laporan_k3"
                ? "active"
                : "" ?>" href="index.php?page=laporan_k3">
                <i class="bi bi-file-earmark-bar-graph"></i> Laporan K3 Astar
            </a>
            <a class="nav-link <?= $active_page == "laporan_keuangan"
                ? "active"
                : "" ?>" href="index.php?page=laporan_keuangan">
                <i class="bi bi-cash-stack"></i> Laporan Keuangan Obat
            </a>
        </nav>
        <div class="nav-group-title">Akun</div>
        <nav class="nav flex-column">
            <a class="nav-link nav-link-logout js-swal-logout" href="../auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </nav>
    </div> 
</div> 

<main class="main-content">


    <?php
    // Routing halaman dikelompokkan berdasarkan jenis modul agar struktur lebih rapi.
    // Parameter ?page= tetap sama sehingga URL dan menu lama tidak berubah.
    $page_routes = [
        // Transaksi
        "antrean" => "transaksi/antrean.php",
        "rekam_medis" => "transaksi/rekam_medis.php",
        "resep_obat" => "transaksi/resep_obat.php",
        "rujukan" => "transaksi/rujukan.php",
        "pengadaan_obat" => "transaksi/pengadaan_obat.php",
        "jadwal_dokter" => "transaksi/jadwal_dokter.php",

        // Master / Data Utama
        "obat" => "master/obat.php",
        "diagnosa" => "master/diagnosa.php",
        "pasien" => "master/pasien.php",

        // Laporan
        "laporan_siloam" => "laporan/laporan_siloam.php",
        "laporan_dinkes" => "laporan/laporan_dinkes.php",
        "laporan_internal_pasien" => "laporan/laporan_internal_pasien.php",
        "laporan_k3" => "laporan/laporan_k3.php",
        "laporan_keuangan" => "laporan/laporan_keuangan.php",
    ];

    $page_relative = $page_routes[$active_page] ?? null;
    $page_file = $page_relative ? __DIR__ . "/pages/" . $page_relative : null;

    if ($page_file && file_exists($page_file)) {
        include $page_file;
    } else {
         ?>
        <div class="data-container text-center py-5">
            <i class="bi bi-exclamation-circle text-muted" style="font-size:4rem;"></i>
            <h4 class="fw-bold mt-3">Halaman tidak ditemukan</h4>
            <p class="text-muted mb-0">Silakan pilih menu yang tersedia di sidebar.</p>
        </div>
        <?php
    }
    ?>

</main>

</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php include dirname(__DIR__) . '/includes/sweetalert_global.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. LOGIKA SIDEBAR (BURGER MENU)
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('sidebar-toggled');
        });
    }

    // 2. JAM DIGITAL
function updateClock() {
    const clock = document.getElementById('digitalClock');
    if (!clock) return;

    const now = new Date();
    
    // Array Nama Hari dan Bulan dalam Bahasa Indonesia
    const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    
    const dayName = days[now.getDay()];
    const dayDate = now.getDate();
    const monthName = months[now.getMonth()];
    const year = now.getFullYear();
    
    // Format Jam dan Menit
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    // Susun string sesuai gambar: "Hari, Tanggal Bulan Tahun pukul HH.mm"
    const finalString = `${dayName}, ${dayDate} ${monthName} ${year} pukul ${hours}.${minutes}`;
    
    clock.innerText = finalString;
}

// Jalankan setiap menit (atau setiap detik jika ingin sangat akurat)
setInterval(updateClock, 1000);
updateClock(); // Panggil langsung agar tidak menunggu 1 detik pertama

    // 3. VALIDASI FILTER TANGGAL (REKAM MEDIS & RESEP)
    // Fungsi pembantu agar tidak buat kode berulang
    function setupDateValidation(startId, endId) {
        const start = document.getElementById(startId);
        const end = document.getElementById(endId);
        if (start && end) {
            start.addEventListener('change', function() {
                end.min = this.value;
                if (end.value && end.value < this.value) {
                    end.value = this.value;
                }
            });
        }
    }
    // Jalankan untuk Rekam Medis
    setupDateValidation('filter_tgl_mulai', 'filter_tgl_akhir');
    // Jalankan untuk Resep Obat
    setupDateValidation('resep_tgl_mulai', 'resep_tgl_akhir');

    // 4. AUTOCOMPLETE TAMBAH RESEP OBAT
    <?php if ($active_page == "resep_obat") { ?>
    if (window.jQuery && $.fn.select2) {
        $('#select_resep_pasien').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalTambahResepObat'),
            width: '100%',
            placeholder: $('#select_resep_pasien').data('placeholder'),
            allowClear: true,
            language: {
                noResults: function() { return 'Data pasien tidak ditemukan'; },
                searching: function() { return 'Mencari...'; }
            }
        });

        const medicineContainer = document.getElementById('resepObatLangsungContainer');
        const addMedicineButton = document.getElementById('btnTambahObatResepLangsung');
        const medicineTemplate = document.getElementById('templateObatResepLangsung');

        function getSelectedDirectMedicineValues() {
            return Array.from(
                document.querySelectorAll('#resepObatLangsungContainer .resep-obat-langsung-select')
            ).map(function(select) {
                return String(select.value || '');
            }).filter(Boolean);
        }

        function updateDirectMedicineAvailability() {
            const selects = Array.from(
                document.querySelectorAll('#resepObatLangsungContainer .resep-obat-langsung-select')
            );

            selects.forEach(function(select) {
                const selectedElsewhere = new Set(
                    selects.filter(function(otherSelect) {
                        return otherSelect !== select;
                    }).map(function(otherSelect) {
                        return String(otherSelect.value || '');
                    }).filter(Boolean)
                );

                Array.from(select.options).forEach(function(option) {
                    if (!option.value) return;
                    const unavailable = selectedElsewhere.has(String(option.value));
                    option.disabled = unavailable;
                    option.hidden = unavailable;
                });
            });
        }

        function directMedicineMatcher(params, data) {
            if (!data.id) return data;

            const option = data.element;
            const currentSelect = option ? option.closest('select') : null;
            const selectedValues = getSelectedDirectMedicineValues();
            const value = String(data.id);

            if (selectedValues.includes(value) && (!currentSelect || String(currentSelect.value) !== value)) {
                return null;
            }

            const keyword = String(params.term || '').trim().toLowerCase();
            if (!keyword) return data;
            return String(data.text || '').toLowerCase().includes(keyword) ? data : null;
        }

        function initializeDirectMedicineSelect(selectElement) {
            const $select = $(selectElement);
            if ($select.hasClass('select2-hidden-accessible')) return;

            $select.select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalTambahResepObat'),
                width: '100%',
                placeholder: $select.data('placeholder'),
                allowClear: true,
                matcher: directMedicineMatcher,
                language: {
                    noResults: function() { return 'Data obat tidak ditemukan'; },
                    searching: function() { return 'Mencari...'; }
                }
            });

            $select.on('change', function() {
                const row = this.closest('.resep-obat-langsung-row');
                const jumlahInput = row ? row.querySelector('.jumlah-resep-langsung') : null;
                const selectedOption = this.options[this.selectedIndex];
                const stock = selectedOption ? selectedOption.getAttribute('data-stock') : '';

                if (jumlahInput && stock !== '') {
                    jumlahInput.max = stock;
                    if (parseInt(jumlahInput.value || '0', 10) > parseInt(stock || '0', 10)) {
                        jumlahInput.value = stock;
                    }
                } else if (jumlahInput) {
                    jumlahInput.removeAttribute('max');
                }

                updateDirectMedicineAvailability();
            });
        }

        document.querySelectorAll('#resepObatLangsungContainer .resep-obat-langsung-select')
            .forEach(initializeDirectMedicineSelect);
        updateDirectMedicineAvailability();

        if (addMedicineButton && medicineContainer && medicineTemplate) {
            addMedicineButton.addEventListener('click', function() {
                const currentRows = medicineContainer.querySelectorAll('.resep-obat-langsung-row').length;
                if (currentRows >= 10) {
                    if (window.ASTARSwal) ASTARSwal.info('Maksimal 10 obat dalam satu kali input resep.');
                    return;
                }

                medicineContainer.appendChild(medicineTemplate.content.cloneNode(true));
                const newSelect = medicineContainer.querySelector('.resep-obat-langsung-row:last-child .resep-obat-langsung-select');
                if (newSelect) {
                    initializeDirectMedicineSelect(newSelect);
                    updateDirectMedicineAvailability();
                    $(newSelect).select2('open');
                }
            });

            medicineContainer.addEventListener('click', function(event) {
                const removeButton = event.target.closest('.btn-hapus-obat-resep-langsung');
                if (!removeButton) return;

                const row = removeButton.closest('.resep-obat-langsung-row');
                const select = row ? row.querySelector('.resep-obat-langsung-select') : null;
                if (select && $(select).hasClass('select2-hidden-accessible')) {
                    $(select).select2('destroy');
                }
                if (row) row.remove();
                updateDirectMedicineAvailability();
            });
        }

        const diagnosisContainer = document.getElementById('resepDiagnosisContainer');
        const addDiagnosisButton = document.getElementById('btnTambahDiagnosaResep');
        const diagnosisTemplate = document.getElementById('templateDiagnosaResep');
        const recipeForm = document.getElementById('formTambahResepObat');

        function getSelectedDiagnosisValues() {
            return Array.from(
                document.querySelectorAll('#resepDiagnosisContainer .resep-diagnosa-select')
            ).map(function(select) {
                return String(select.value || '');
            }).filter(Boolean);
        }

        function updateDiagnosisAvailability() {
            const selects = Array.from(
                document.querySelectorAll('#resepDiagnosisContainer .resep-diagnosa-select')
            );

            selects.forEach(function(select) {
                const selectedElsewhere = new Set(
                    selects.filter(function(otherSelect) {
                        return otherSelect !== select;
                    }).map(function(otherSelect) {
                        return String(otherSelect.value || '');
                    }).filter(Boolean)
                );

                Array.from(select.options).forEach(function(option) {
                    if (!option.value) {
                        return;
                    }

                    const unavailable = selectedElsewhere.has(String(option.value));
                    option.disabled = unavailable;
                    option.hidden = unavailable;
                });
            });
        }

        function diagnosisSelectMatcher(params, data) {
            if (!data.id) {
                return data;
            }

            const option = data.element;
            const currentSelect = option ? option.closest('select') : null;
            const selectedValues = getSelectedDiagnosisValues();
            const value = String(data.id);

            // Penyakit yang sudah dipilih pada kolom lain tidak ditampilkan lagi.
            if (selectedValues.includes(value) && (!currentSelect || String(currentSelect.value) !== value)) {
                return null;
            }

            const keyword = String(params.term || '').trim().toLowerCase();
            if (!keyword) {
                return data;
            }

            return String(data.text || '').toLowerCase().includes(keyword) ? data : null;
        }

        function initializeDiagnosisSelect(selectElement) {
            const $select = $(selectElement);
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }

            $select.select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalTambahResepObat'),
                width: '100%',
                placeholder: $select.data('placeholder'),
                allowClear: true,
                matcher: diagnosisSelectMatcher,
                language: {
                    noResults: function() { return 'Penyakit tidak ditemukan'; },
                    searching: function() { return 'Mencari...'; }
                }
            });

            $select.on('change', function() {
                updateDiagnosisAvailability();
            });
        }

        document.querySelectorAll('#resepDiagnosisContainer .resep-diagnosa-select')
            .forEach(initializeDiagnosisSelect);
        updateDiagnosisAvailability();

        if (addDiagnosisButton && diagnosisContainer && diagnosisTemplate) {
            addDiagnosisButton.addEventListener('click', function() {
                const currentRows = diagnosisContainer.querySelectorAll('.resep-diagnosis-row').length;
                if (currentRows >= 10) {
                    if (window.ASTARSwal) {
                        ASTARSwal.info('Maksimal 10 penyakit dalam satu resep.');
                    }
                    return;
                }

                diagnosisContainer.appendChild(diagnosisTemplate.content.cloneNode(true));
                const newSelect = diagnosisContainer.querySelector('.resep-diagnosis-row:last-child .resep-diagnosa-select');
                if (newSelect) {
                    initializeDiagnosisSelect(newSelect);
                    updateDiagnosisAvailability();
                    $(newSelect).select2('open');
                }
            });

            diagnosisContainer.addEventListener('click', function(event) {
                const removeButton = event.target.closest('.btn-hapus-diagnosa-resep');
                if (!removeButton) {
                    return;
                }

                const row = removeButton.closest('.resep-diagnosis-row');
                const select = row ? row.querySelector('.resep-diagnosa-select') : null;
                if (select && $(select).hasClass('select2-hidden-accessible')) {
                    $(select).select2('destroy');
                }
                if (row) {
                    row.remove();
                    updateDiagnosisAvailability();
                }
            });
        }

        if (recipeForm) {
            recipeForm.addEventListener('submit', function(event) {
                const selectedDiseases = Array.from(
                    this.querySelectorAll('.resep-diagnosa-select')
                ).map(function(select) {
                    return select.value;
                }).filter(Boolean);

                const medicineRows = Array.from(this.querySelectorAll('.resep-obat-langsung-row'));
                const validMedicineRows = medicineRows.filter(function(row) {
                    const select = row.querySelector('.resep-obat-langsung-select');
                    return select && select.value;
                });

                let medicineInvalid = false;
                validMedicineRows.forEach(function(row) {
                    const qty = row.querySelector('.jumlah-resep-langsung');
                    const note = row.querySelector('.catatan-resep-langsung');
                    if (!qty || parseInt(qty.value || '0', 10) <= 0 || !note || !String(note.value || '').trim()) {
                        medicineInvalid = true;
                    }
                });

                if (selectedDiseases.length === 0 || validMedicineRows.length === 0 || medicineInvalid) {
                    event.preventDefault();
                    if (window.ASTARSwal) {
                        ASTARSwal.warning('Silakan lengkapi penyakit, obat, jumlah, dan aturan pakai.', 'Ada Input Kosong');
                    }
                    return;
                }
            });
        }

        $('#modalTambahResepObat').on('hidden.bs.modal', function() {
            $('#select_resep_pasien').val(null).trigger('change');

            if (medicineContainer) {
                const medicineRows = Array.from(
                    medicineContainer.querySelectorAll('.resep-obat-langsung-row')
                );

                medicineRows.slice(1).forEach(function(row) {
                    const select = row.querySelector('.resep-obat-langsung-select');
                    if (select && $(select).hasClass('select2-hidden-accessible')) {
                        $(select).select2('destroy');
                    }
                    row.remove();
                });

                const firstMedicine = medicineContainer.querySelector('.resep-obat-langsung-select');
                if (firstMedicine) {
                    $(firstMedicine).val(null).trigger('change');
                }
                const firstQty = medicineContainer.querySelector('.jumlah-resep-langsung');
                if (firstQty) firstQty.value = '1';
                const firstNote = medicineContainer.querySelector('.catatan-resep-langsung');
                if (firstNote) firstNote.value = '';
                updateDirectMedicineAvailability();
            }

            if (diagnosisContainer) {
                const diagnosisRows = Array.from(
                    diagnosisContainer.querySelectorAll('.resep-diagnosis-row')
                );

                diagnosisRows.slice(1).forEach(function(row) {
                    const select = row.querySelector('.resep-diagnosa-select');
                    if (select && $(select).hasClass('select2-hidden-accessible')) {
                        $(select).select2('destroy');
                    }
                    row.remove();
                });

                const firstDiagnosis = diagnosisContainer.querySelector('.resep-diagnosa-select');
                if (firstDiagnosis) {
                    $(firstDiagnosis).val(null).trigger('change');
                }
                updateDiagnosisAvailability();
            }

            const form = this.querySelector('form');
            if (form) {
                form.reset();
            }
        });
    }
    <?php } ?>

    // 4. LOGIKA SEARCH PASIEN (AUTOCOMPLETE)
    // Endpoint disatukan ke dokter/index.php agar tidak menghasilkan 404
    // ketika halaman dibuka dari folder /dokter/.
    const inputKwd = document.getElementById('inputKeyword');
    const resCont = document.getElementById('hasilPencarian');
    let searchPasienTimer = null;
    let searchPasienController = null;

    if (inputKwd && resCont) {
        inputKwd.addEventListener('input', function() {
            const keyword = this.value.trim();
            window.clearTimeout(searchPasienTimer);

            if (searchPasienController) {
                searchPasienController.abort();
            }

            if (keyword.length < 2) {
                resCont.innerHTML = '';
                resCont.classList.add('d-none');
                return;
            }

            searchPasienTimer = window.setTimeout(function() {
                searchPasienController = new AbortController();
                resCont.innerHTML = '<div class="search-state"><span class="spinner-border spinner-border-sm me-2"></span>Mencari pasien...</div>';
                resCont.classList.remove('d-none');

                fetch('index.php?ajax=search_pasien_rujukan&keyword=' + encodeURIComponent(keyword), {
                    signal: searchPasienController.signal,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }
                        return response.text();
                    })
                    .then(function(html) {
                        resCont.innerHTML = html;
                        resCont.classList.remove('d-none');
                    })
                    .catch(function(error) {
                        if (error.name === 'AbortError') {
                            return;
                        }

                        resCont.innerHTML = '<div class="search-state search-state-error">Pencarian pasien gagal. Muat ulang halaman lalu coba kembali.</div>';
                        resCont.classList.remove('d-none');
                    });
            }, 250);
        });

        // Tutup hasil pencarian jika klik di luar
        document.addEventListener('click', function(event) {
            if (!resCont.contains(event.target) && event.target !== inputKwd) {
                resCont.classList.add('d-none');
            }
        });
    }
});

// Fungsi yang dipanggil dari luar (Global)
function pilihPasien(id, nama, nim) {
    document.getElementById('id_pasien_fix').value = id;
    document.getElementById('nama_pasien_fix').innerText = nama;
    document.getElementById('nim_pasien_fix').innerText = "NIM: " + nim;
    document.getElementById('inputKeyword').value = nim;
    document.getElementById('hasilPencarian').classList.add('d-none');
    document.getElementById('infoTerpilih').classList.remove('d-none');
}

function printRujukan(id) {
    window.open('../cetak/cetak_rujukan.php?id=' + id, '_blank');
}

// Fungsi Hitung Stok Otomatis (Data Obat)
function hitungJumlahOrder(btn) {
    const idObat = btn.getAttribute('data-id-obat');
    const stokSekarang = parseInt(btn.getAttribute('data-stok-sekarang'));
    const stokTarget = parseInt(btn.getAttribute('data-stok-target'));
    const jumlahDisarankan = stokTarget - stokSekarang;
    
    document.getElementById('jumlah_order').value = jumlahDisarankan > 0 ? jumlahDisarankan : 1;
    document.querySelector('select[name=id_obat]').value = idObat;
    
    const saranEl = document.getElementById('saran_jumlah');
    if(saranEl) {
        saranEl.innerHTML = `💡 Saran: ${jumlahDisarankan} unit`;
        saranEl.style.display = 'block';
    }
}
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.pemeriksaan-form').forEach(function (form) {
        const medicineList = form.querySelector('.resep-obat-pemeriksaan-list');
        const medicineTemplate = form.querySelector('.template-obat-pemeriksaan');

        const clearInvalidState = function (field) {
            if (!field) return;
            field.classList.remove('is-invalid');
            field.removeAttribute('aria-invalid');
        };

        function getMedicineRows() {
            return medicineList
                ? Array.from(medicineList.querySelectorAll('.resep-obat-pemeriksaan-item'))
                : [];
        }

        function updateMedicineAvailability() {
            const selects = getMedicineRows()
                .map(function (row) { return row.querySelector('.obat-pemeriksaan-select'); })
                .filter(Boolean);

            selects.forEach(function (select) {
                const selectedElsewhere = new Set(
                    selects
                        .filter(function (other) { return other !== select; })
                        .map(function (other) { return String(other.value || ''); })
                        .filter(Boolean)
                );

                Array.from(select.options).forEach(function (option) {
                    if (!option.value) return;
                    const unavailable = selectedElsewhere.has(String(option.value));
                    option.disabled = unavailable;
                    option.hidden = unavailable;
                });
            });

            const addButton = form.querySelector('.btn-tambah-obat-pemeriksaan');
            if (addButton) {
                const totalOptions = selects[0]
                    ? Array.from(selects[0].options).filter(function (option) { return option.value; }).length
                    : 0;
                addButton.disabled = totalOptions > 0 && selects.length >= totalOptions;
            }
        }

        function bindMedicineRow(row) {
            if (!row) return;

            const select = row.querySelector('.obat-pemeriksaan-select');
            const quantity = row.querySelector('.jumlah-obat-pemeriksaan');
            const note = row.querySelector('.catatan-obat-pemeriksaan');

            [select, quantity, note].forEach(function (field) {
                if (!field || field.dataset.validationBound === '1') return;
                field.dataset.validationBound = '1';
                field.addEventListener(field.tagName === 'SELECT' ? 'change' : 'input', function () {
                    clearInvalidState(field);
                    if (field === select) {
                        updateMedicineAvailability();
                    }
                });
            });
        }

        getMedicineRows().forEach(bindMedicineRow);
        updateMedicineAvailability();

        form.addEventListener('click', function (event) {
            const addButton = event.target.closest('.btn-tambah-obat-pemeriksaan');
            if (addButton && medicineList && medicineTemplate) {
                const clone = medicineTemplate.content.cloneNode(true);
                medicineList.appendChild(clone);
                const newRow = medicineList.lastElementChild;
                bindMedicineRow(newRow);
                updateMedicineAvailability();
                const newSelect = newRow ? newRow.querySelector('.obat-pemeriksaan-select') : null;
                if (newSelect) newSelect.focus();
                return;
            }

            const removeButton = event.target.closest('.btn-hapus-obat-pemeriksaan');
            if (removeButton) {
                const row = removeButton.closest('.resep-obat-pemeriksaan-item');
                if (row) row.remove();
                updateMedicineAvailability();
            }
        });

        form.querySelectorAll('input, select, textarea').forEach(function (field) {
            if (field.closest('.resep-obat-pemeriksaan-item')) return;
            field.addEventListener(field.tagName === 'SELECT' ? 'change' : 'input', function () {
                clearInvalidState(field);
            });
        });

        form.addEventListener('submit', function (event) {
            const keluhan = form.querySelector('[name="keluhan"]');
            const diagnosa = form.querySelector('[name="id_diagnosa"]');
            const hasil = form.querySelector('[name="hasil_pemeriksaan"]');
            const invalidFields = [];
            const validationMessages = [];

            form.querySelectorAll('.is-invalid').forEach(clearInvalidState);

            if (!keluhan || keluhan.value.trim() === '') {
                invalidFields.push(keluhan);
                validationMessages.push('Keluhan wajib diisi.');
            }
            if (!diagnosa || diagnosa.value.trim() === '') {
                invalidFields.push(diagnosa);
                validationMessages.push('Diagnosa wajib dipilih.');
            }
            if (!hasil || hasil.value.trim() === '') {
                invalidFields.push(hasil);
                validationMessages.push('Hasil pemeriksaan wajib diisi.');
            }

            getMedicineRows().forEach(function (row) {
                const obat = row.querySelector('.obat-pemeriksaan-select');
                const jumlah = row.querySelector('.jumlah-obat-pemeriksaan');
                const catatan = row.querySelector('.catatan-obat-pemeriksaan');
                const obatDipilih = obat && obat.value !== '';
                const jumlahNilai = jumlah ? Number(jumlah.value || 0) : 0;
                const catatanDiisi = catatan && catatan.value.trim() !== '';

                // Seluruh baris kosong berarti pasien tidak menggunakan obat.
                if (!obatDipilih && jumlahNilai <= 0 && !catatanDiisi) {
                    return;
                }

                if (!obatDipilih) {
                    invalidFields.push(obat);
                    validationMessages.push('Obat wajib dipilih pada baris resep yang diisi.');
                }
                if (obatDipilih && jumlahNilai < 1) {
                    invalidFields.push(jumlah);
                    validationMessages.push('Jumlah obat minimal 1.');
                }
            });

            const uniqueInvalidFields = invalidFields.filter(function (field, index, fields) {
                return field && fields.indexOf(field) === index;
            });

            if (uniqueInvalidFields.length === 0) return;

            event.preventDefault();
            event.stopPropagation();

            uniqueInvalidFields.forEach(function (field) {
                field.classList.add('is-invalid');
                field.setAttribute('aria-invalid', 'true');
            });

            ASTARSwal.warning(
                Array.from(new Set(validationMessages)).join(' '),
                'Periksa Data Pemeriksaan'
            ).then(function () {
                uniqueInvalidFields[0].focus();
            });
        });

        form.closest('.modal')?.addEventListener('hidden.bs.modal', function () {
            form.querySelectorAll('.is-invalid').forEach(clearInvalidState);

            if (medicineList) {
                const rows = getMedicineRows();
                rows.slice(1).forEach(function (row) { row.remove(); });

                const firstRow = getMedicineRows()[0];
                if (firstRow) {
                    const select = firstRow.querySelector('.obat-pemeriksaan-select');
                    const quantity = firstRow.querySelector('.jumlah-obat-pemeriksaan');
                    const note = firstRow.querySelector('.catatan-obat-pemeriksaan');
                    if (select) select.value = '';
                    if (quantity) quantity.value = '0';
                    if (note) note.value = '';
                }
                updateMedicineAvailability();
            }
        });
    });
});
</script>

<?php include dirname(__DIR__) . '/includes/form_ui_global.php'; ?>
<?php include dirname(__DIR__) . '/includes/table_ui_global.php'; ?>
<?php include dirname(__DIR__) . '/includes/pagination_global.php'; ?>
<?php include dirname(__DIR__) . '/includes/login_success_popup.php'; ?>
</body>
</html>