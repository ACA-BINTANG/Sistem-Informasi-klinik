<?php
session_start();
require_once dirname(__DIR__) . "/koneksi.php";

// =======================
// PROTEKSI ROLE DOKTER
// =======================
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Dokter") {
    header("Location: ../login.php?pesan=Akses Ditolak!");
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

// =======================
// TAMBAH RUJUKAN
// =======================
if (isset($_POST["add_rujukan"])) {
    $id_rjk = generateIDUrut($conn, "RJK", "rujukan", "id_rujukan", 3);
    $id_p = mysqli_real_escape_string($conn, $_POST["id_pasien"]);
    $tujuan = mysqli_real_escape_string($conn, $_POST["tujuan_rs"]);
    $alasan = mysqli_real_escape_string($conn, $_POST["alasan_rujukan"]);
    $hasil = mysqli_real_escape_string($conn, $_POST["hasil_rujukan"]); // Kolom baru
    $tgl = date("Y-m-d");

    // Query INSERT yang sudah disesuaikan dengan database
    $ins = mysqli_query(
        $conn,
        "INSERT INTO rujukan (id_rujukan, id_pasien, id_staff, tujuan_rs, alasan_rujukan, hasil_rujukan, tgl_rujukan, status) 
           VALUES ('$id_rjk', '$id_p', '$id_dokter', '$tujuan', '$alasan', '$hasil', '$tgl', 'Aktif')",
    );

    if ($ins) {
        header(
            "Location: index.php?page=rujukan&msg=Surat Rujukan Berhasil Dibuat",
        );
        exit();
    } else {
        die("Gagal simpan: " . mysqli_error($conn));
    }
}

// =======================
// TOMBOL BATAL ANTREAN DOKTER
// Kalau pasien tidak hadir, data antrean dihapus
// Tidak memakai status Batal
// =======================
if (isset($_POST["batal_antrean"])) {
    $id_rm_batal = mysqli_real_escape_string(
        $conn,
        $_POST["id_rekam_medis"] ?? "",
    );

    if ($id_rm_batal == "") {
        header(
            "Location: index.php?page=antrean&err=Data antrean tidak ditemukan",
        );
        exit();
    }

    $hapus = mysqli_query(
        $conn,
        "
        DELETE FROM rekam_medis
        WHERE id_rekam_medis = '$id_rm_batal'
        AND (id_staff = '$id_dokter' OR id_staff IS NULL OR id_staff = '')
        AND status IN ('Menunggu', 'Darurat', 'Diproses')
    ",
    );

    if (!$hapus) {
        header(
            "Location: index.php?page=antrean&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    if (mysqli_affected_rows($conn) > 0) {
        header(
            "Location: index.php?page=antrean&msg=Antrean berhasil dibatalkan dan data sudah dihapus",
        );
        exit();
    } else {
        header(
            "Location: index.php?page=antrean&err=Antrean tidak bisa dibatalkan. Mungkin sudah diproses atau selesai.",
        );
        exit();
    }
}

// =======================
// SIMPAN PEMERIKSAAN DOKTER
// Jika obat dipilih dan jumlah keluar diisi,
// stok obat otomatis berkurang
// =======================
if (isset($_POST["simpan_pemeriksaan"])) {
    $id_rm = mysqli_real_escape_string($conn, $_POST["id_rekam_medis"] ?? "");
    $id_diag = mysqli_real_escape_string($conn, $_POST["id_diagnosa"] ?? "");
    $keluhan = mysqli_real_escape_string($conn, $_POST["keluhan"] ?? "");
    $hasil = mysqli_real_escape_string(
        $conn,
        $_POST["hasil_pemeriksaan"] ?? "",
    );
    $id_obat = mysqli_real_escape_string($conn, $_POST["id_obat"] ?? "");
    $qty = (int) ($_POST["jumlah_keluar"] ?? 0);
    $catatan = mysqli_real_escape_string($conn, $_POST["catatan_obat"] ?? "");

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

        if ($catatan != "" || ($id_obat != "" && $qty > 0)) {
            $id_resep = generateUniqueResepID($conn);

            if ($id_obat != "" && $qty > 0) {
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
                        "Stok obat tidak cukup. Stok tersedia: " .
                            $stok_saat_ini,
                    );
                }

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
            } else {
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
                        NULL,
                        0,
                        '$catatan'
                    )
                ",
                );

                if (!$insert_resep) {
                    throw new Exception(
                        "Gagal menyimpan catatan resep: " . mysqli_error($conn),
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
// Validasi hanya:
// 1. data wajib lengkap
// 2. stok obat cukup
// Tidak perlu validasi rekam medis dokter login.
// =======================
if (isset($_POST["add_resep_dokter"])) {
    $id_pasien = mysqli_real_escape_string($conn, $_POST["id_pasien"] ?? "");
    $id_obat = mysqli_real_escape_string($conn, $_POST["id_obat"] ?? "");
    $jumlah_keluar = (int) ($_POST["jumlah_keluar"] ?? 0);
    $catatan_obat = mysqli_real_escape_string(
        $conn,
        trim($_POST["catatan_obat"] ?? ""),
    );

    // Dapat menerima satu atau lebih id diagnosa dari input dinamis.
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

    if (
        $id_pasien == "" ||
        empty($diagnosa_ids) ||
        $id_obat == "" ||
        $jumlah_keluar <= 0 ||
        $catatan_obat == ""
    ) {
        header(
            "Location: index.php?page=resep_obat&err=" .
                urlencode("Data resep belum lengkap. Pilih minimal satu penyakit/keluhan."),
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

    // Bersihkan relasi lama yang tidak memiliki resep utama agar ID baru bebas bentrok.
    cleanupOrphanResepDiagnosa($conn);

    mysqli_begin_transaction($conn);

    try {
        $cek_pasien = mysqli_query(
            $conn,
            "
            SELECT id_pasien, nama_pasien
            FROM pasienm
            WHERE id_pasien = '$id_pasien'
            LIMIT 1
        ",
        );

        if (!$cek_pasien) {
            throw new Exception("Query pasien error: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($cek_pasien) == 0) {
            throw new Exception("Pasien tidak ditemukan.");
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
            throw new Exception("Query obat error: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($cek_obat) == 0) {
            throw new Exception("Obat tidak ditemukan.");
        }

        $obat = mysqli_fetch_assoc($cek_obat);
        $stok_saat_ini = (int) $obat["stok_sekarang"];

        if ($stok_saat_ini < $jumlah_keluar) {
            throw new Exception(
                "Stok obat tidak cukup. Stok tersedia: " . $stok_saat_ini,
            );
        }

        $id_resep = generateUniqueResepID($conn);

        $insert_resep = mysqli_query(
            $conn,
            "
            INSERT INTO resep_dokter
            (
                id_resep,
                id_pasien,
                id_obat,
                jumlah_keluar,
                catatan_obat
            )
            VALUES
            (
                '$id_resep',
                '$id_pasien',
                '$id_obat',
                '$jumlah_keluar',
                '$catatan_obat'
            )
        ",
        );

        if (!$insert_resep) {
            throw new Exception(
                "Gagal menyimpan resep: " . mysqli_error($conn),
            );
        }

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
            throw new Exception(
                "Gagal menyimpan penyakit/keluhan resep: " . mysqli_error($conn),
            );
        }

        if (!triggerExists($conn, "trg_kurangi_stok_obat")) {
            $stok_baru = $stok_saat_ini - $jumlah_keluar;

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

        mysqli_commit($conn);

        header(
            "Location: index.php?page=resep_obat&msg=Resep obat berhasil ditambahkan",
        );
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);

        $pesanError = $e->getMessage();
        if (stripos($pesanError, "Duplicate entry") !== false) {
            $pesanError = "Terjadi bentrok nomor resep. Silakan tekan Simpan sekali lagi.";
        }

        header(
            "Location: index.php?page=resep_obat&err=" .
                urlencode($pesanError),
        );
        exit();
    }
}

// =======================
// TAMBAH JADWAL DOKTER
// =======================
if (isset($_POST["add_jadwal_dokter"])) {
    $id_jadwal = generateIDUrut($conn, "JDW", "jadwalm", "id_jadwal", 3);
    $tanggal = mysqli_real_escape_string($conn, $_POST["tanggal"] ?? "");
    $jam_mulai = mysqli_real_escape_string($conn, $_POST["jam_mulai"] ?? "");
    $jam_selesai = mysqli_real_escape_string(
        $conn,
        $_POST["jam_selesai"] ?? "",
    );
    $status = mysqli_real_escape_string($conn, $_POST["status"] ?? "");

    if (
        $tanggal == "" ||
        $jam_mulai == "" ||
        $jam_selesai == "" ||
        $status == ""
    ) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Semua data jadwal wajib diisi",
        );
        exit();
    }

    if (
        !in_array($tanggal, [
            "Senin",
            "Selasa",
            "Rabu",
            "Kamis",
            "Jumat",
            "Sabtu",
            "Minggu",
        ])
    ) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Hari jadwal tidak valid",
        );
        exit();
    }

    if (!in_array($status, ["Buka", "Tutup"])) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Status jadwal tidak valid",
        );
        exit();
    }

    if ($jam_selesai <= $jam_mulai) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Jam selesai harus lebih besar dari jam mulai",
        );
        exit();
    }

    $cek_jadwal = mysqli_query(
        $conn,
        "
        SELECT id_jadwal
        FROM jadwalm
        WHERE id_staff = '$id_dokter'
        AND tanggal = '$tanggal'
        LIMIT 1
    ",
    );

    if ($cek_jadwal && mysqli_num_rows($cek_jadwal) > 0) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Jadwal untuk hari $tanggal sudah ada",
        );
        exit();
    }

    $insert = mysqli_query(
        $conn,
        "
        INSERT INTO jadwalm
        (
            id_jadwal,
            id_staff,
            tanggal,
            jam_mulai,
            jam_selesai,
            status
        )
        VALUES
        (
            '$id_jadwal',
            '$id_dokter',
            '$tanggal',
            '$jam_mulai',
            '$jam_selesai',
            '$status'
        )
    ",
    );

    if (!$insert) {
        header(
            "Location: index.php?page=jadwal_dokter&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    header(
        "Location: index.php?page=jadwal_dokter&msg=Jadwal dokter berhasil ditambahkan",
    );
    exit();
}

// =======================
// SHOW EDIT JADWAL
// =======================
$edit_jadwal_data = null;
if (isset($_POST["show_edit_jadwal"])) {
    $id_jadwal_edit = mysqli_real_escape_string(
        $conn,
        $_POST["id_jadwal"] ?? "",
    );

    $qEdit = mysqli_query(
        $conn,
        "
        SELECT * FROM jadwalm
        WHERE id_jadwal = '$id_jadwal_edit'
        AND id_staff = '$id_dokter'
        LIMIT 1
    ",
    );

    if ($qEdit && mysqli_num_rows($qEdit) > 0) {
        $edit_jadwal_data = mysqli_fetch_assoc($qEdit);
    }
}

// =======================
$edit_obat_data = null;
if (isset($_POST["show_edit_obat"])) {
    $id_obat_edit = mysqli_real_escape_string($conn, $_POST["id_obat"] ?? "");

    $qEditObat = mysqli_query(
        $conn,
        "
        SELECT * FROM obatm
        WHERE id_obat = '$id_obat_edit'
        LIMIT 1
    ",
    );

    if ($qEditObat && mysqli_num_rows($qEditObat) > 0) {
        $edit_obat_data = mysqli_fetch_assoc($qEditObat);
    }
}

// =======================
if (isset($_POST["update_jadwal_dokter"])) {
    $id_jadwal = mysqli_real_escape_string($conn, $_POST["id_jadwal"] ?? "");
    $tanggal = mysqli_real_escape_string($conn, $_POST["tanggal"] ?? "");
    $jam_mulai = mysqli_real_escape_string($conn, $_POST["jam_mulai"] ?? "");
    $jam_selesai = mysqli_real_escape_string(
        $conn,
        $_POST["jam_selesai"] ?? "",
    );
    $status = mysqli_real_escape_string($conn, $_POST["status"] ?? "");

    if (
        $id_jadwal == "" ||
        $tanggal == "" ||
        $jam_mulai == "" ||
        $jam_selesai == "" ||
        $status == ""
    ) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Data jadwal belum lengkap",
        );
        exit();
    }

    if (
        !in_array($tanggal, [
            "Senin",
            "Selasa",
            "Rabu",
            "Kamis",
            "Jumat",
            "Sabtu",
            "Minggu",
        ])
    ) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Hari jadwal tidak valid",
        );
        exit();
    }

    if (!in_array($status, ["Buka", "Tutup"])) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Status jadwal tidak valid",
        );
        exit();
    }

    if ($jam_selesai <= $jam_mulai) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Jam selesai harus lebih besar dari jam mulai",
        );
        exit();
    }

    // Cek duplikasi (exclude jadwal yang sedang diedit)
    $cek_duplikasi = mysqli_query(
        $conn,
        "
        SELECT id_jadwal
        FROM jadwalm
        WHERE id_staff = '$id_dokter'
        AND tanggal = '$tanggal'
        AND id_jadwal != '$id_jadwal'
        LIMIT 1
    ",
    );

    if ($cek_duplikasi && mysqli_num_rows($cek_duplikasi) > 0) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Jadwal untuk hari $tanggal sudah ada",
        );
        exit();
    }

    $update = mysqli_query(
        $conn,
        "
        UPDATE jadwalm
        SET
            tanggal = '$tanggal',
            jam_mulai = '$jam_mulai',
            jam_selesai = '$jam_selesai',
            status = '$status'
        WHERE id_jadwal = '$id_jadwal'
        AND id_staff = '$id_dokter'
    ",
    );

    if (!$update) {
        header(
            "Location: index.php?page=jadwal_dokter&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    header(
        "Location: index.php?page=jadwal_dokter&msg=Jadwal dokter berhasil diperbarui",
    );
    exit();
}

// =======================
// HAPUS JADWAL DOKTER
// =======================
if (isset($_POST["hapus_jadwal_dokter"])) {
    $id_jadwal = mysqli_real_escape_string($conn, $_POST["id_jadwal"] ?? "");

    $hapus = mysqli_query(
        $conn,
        "
        DELETE FROM jadwalm
        WHERE id_jadwal = '$id_jadwal'
        AND id_staff = '$id_dokter'
    ",
    );

    if (!$hapus) {
        header(
            "Location: index.php?page=jadwal_dokter&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    header(
        "Location: index.php?page=jadwal_dokter&msg=Jadwal dokter berhasil dihapus",
    );
    exit();
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

    $insert = mysqli_query(
        $conn,
        "
        INSERT INTO pengadaan_obat
        (id_pengadaan, id_obat, id_supplier, jumlah_order, tgl_order, tgl_estimasi_tiba, status)
        VALUES
        ('$id_pengadaan', '$id_obat', '$id_supplier', $jumlah_order, DATE(NOW()), '$tgl_estimasi', 'Pending')
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
            harga_per_pcs
        )
        VALUES
        (
            '$id_obat',
            '$nama_obat',
            '$stok_sekarang',
            '$stok_minimum',
            '$stok_target',
            '$satuan',
            '$harga_per_pcs'
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
            tipe
        )
        VALUES
        (
            '$id_diagnosa',
            '$nama_penyakit',
            '$kategori',
            '$tipe'
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
    <title>Doctor Panel - ASTARhealth</title>

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

        <div class="nav-group-title">Master Data</div>
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
                <i class="bi bi-cash-stack"></i> Laporan Finance Obat
            </a>
        </nav>
        <div class="nav-group-title">Akun</div>
        <nav class="nav flex-column">
            <a class="nav-link nav-link-logout js-swal-logout" href="../logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div> 
</div> 

<main class="main-content">


    <?php
    $page_file = __DIR__ . "/pages/" . basename($active_page) . ".php";

    if (file_exists($page_file)) {
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

<div class="modal fade" id="modalLogout" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-body text-center p-5">
                <div class="text-danger mb-4">
                    <i class="bi bi-exclamation-circle-fill" style="font-size: 4rem; opacity: 0.2;"></i>
                </div>

                <h4 class="fw-bold mb-2">Yakin Ingin Keluar?</h4>

                <p class="text-muted small mb-4">
                    Pastikan semua data sudah tersimpan sebelum keluar.
                </p>

                <div class="d-flex gap-2">
                    <button type="button"
                            class="btn btn-light w-100 py-2 fw-bold rounded-3"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <a href="../logout.php"
                       class="btn btn-danger w-100 py-2 fw-bold rounded-3 shadow-sm text-white text-decoration-none">
                        Ya, Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php include dirname(__DIR__) . '/sweetalert_global.php'; ?>

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

        $('#select_resep_obat').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalTambahResepObat'),
            width: '100%',
            placeholder: $('#select_resep_obat').data('placeholder'),
            allowClear: true,
            language: {
                noResults: function() { return 'Data obat tidak ditemukan'; },
                searching: function() { return 'Mencari...'; }
            }
        });

        $('#select_resep_obat').on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const stock = selectedOption ? selectedOption.getAttribute('data-stock') : '';
            const jumlahInput = document.querySelector('#modalTambahResepObat input[name="jumlah_keluar"]');

            if (jumlahInput && stock !== '') {
                jumlahInput.max = stock;

                if (parseInt(jumlahInput.value || '0') > parseInt(stock || '0')) {
                    jumlahInput.value = stock;
                }
            }
        });

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
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Batas Penyakit',
                            text: 'Maksimal 10 penyakit dalam satu resep.',
                            confirmButtonText: 'Mengerti'
                        });
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

                if (selectedDiseases.length === 0) {
                    event.preventDefault();
                    const firstSelect = this.querySelector('.resep-diagnosa-select');
                    if (firstSelect) {
                        $(firstSelect).select2('open');
                    }
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Penyakit Belum Dipilih',
                            text: 'Pilih minimal satu penyakit atau keluhan sebelum menyimpan resep.',
                            confirmButtonText: 'Mengerti'
                        });
                    }
                }
            });
        }

        $('#modalTambahResepObat').on('hidden.bs.modal', function() {
            $('#select_resep_pasien').val(null).trigger('change');
            $('#select_resep_obat').val(null).trigger('change');

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
    window.open('../cetak_rujukan.php?id=' + id, '_blank');
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

<?php include dirname(__DIR__) . '/pagination_global.php'; ?>
<?php include dirname(__DIR__) . '/login_success_popup.php'; ?>
</body>
</html>