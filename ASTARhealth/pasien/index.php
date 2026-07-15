<?php
session_start();
require_once dirname(__DIR__) . "/koneksi.php";

/** @var mysqli $conn */

// =======================
// PROTEKSI ROLE PASIEN
// =======================
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Pasien") {
    header("Location: ../login.php?pesan=Akses Ditolak!");
    exit();
}

date_default_timezone_set("Asia/Jakarta");

$user_id = $_SESSION["id_user"] ?? "";
$pasien_name = $_SESSION["nama_lengkap"] ?? "Pasien";
$active_page = $_GET["page"] ?? "beranda";
$slot_menit = 30; // jarak pilihan jam booking. Ubah ke 15 kalau mau setiap 15 menit.

// =======================
// HELPER OUTPUT AMAN
// =======================
function e($text)
{
    return htmlspecialchars($text ?? "", ENT_QUOTES, "UTF-8");
}

function hariIndonesiaDariTanggal($tanggal)
{
    $hari = [
        "Sunday" => "Minggu",
        "Monday" => "Senin",
        "Tuesday" => "Selasa",
        "Wednesday" => "Rabu",
        "Thursday" => "Kamis",
        "Friday" => "Jumat",
        "Saturday" => "Sabtu",
    ];

    $key = date("l", strtotime($tanggal));
    return $hari[$key] ?? "";
}

function tanggalBerikutnyaDariHari($hariTujuan, $jamBooking)
{
    $mapHari = [
        "Minggu" => 0,
        "Senin" => 1,
        "Selasa" => 2,
        "Rabu" => 3,
        "Kamis" => 4,
        "Jumat" => 5,
        "Sabtu" => 6,
    ];

    $hariIniAngka = (int) date("w");
    $hariTujuanAngka = $mapHari[$hariTujuan] ?? null;

    if ($hariTujuanAngka === null) {
        return false;
    }

    $selisihHari = $hariTujuanAngka - $hariIniAngka;

    if ($selisihHari < 0) {
        $selisihHari += 7;
    }

    // Kalau jadwalnya hari ini, pasien tidak boleh booking jam yang sudah lewat.
    // Contoh sekarang 10:00, pasien pilih 09:00 => ditolak.
    if ($selisihHari == 0) {
        $tanggalJamBooking = strtotime(date("Y-m-d") . " " . $jamBooking);

        if ($tanggalJamBooking <= time()) {
            return false;
        }
    }

    return date("Y-m-d", strtotime("+" . $selisihHari . " days"));
}

function hitungPosisiAntrean($conn, $id_rekam_medis, $tgl_kunjungan)
{
    $id_rekam_medis = mysqli_real_escape_string($conn, $id_rekam_medis);
    $tgl_kunjungan = mysqli_real_escape_string($conn, $tgl_kunjungan);

    $queryPosisi = mysqli_query(
        $conn,
        "
        SELECT id_rekam_medis
        FROM rekam_medis
        WHERE status IN ('Menunggu', 'Darurat')
        AND tgl_kunjungan = '$tgl_kunjungan'
        ORDER BY
            CASE WHEN status = 'Darurat' THEN 0 ELSE 1 END ASC,
            CASE
                WHEN jenis_antrean = 'Jadwal' AND tgl_kunjungan = CURDATE() AND waktu_booking <= CURTIME() THEN 0
                WHEN jenis_antrean = 'Langsung' THEN 1
                ELSE 2
            END ASC,
            waktu_booking ASC,
            CAST(SUBSTRING(no_antrian, 2) AS UNSIGNED) ASC
    ",
    );

    if (!$queryPosisi) {
        return null;
    }

    $posisi = 1;
    while ($row = mysqli_fetch_assoc($queryPosisi)) {
        if ($row["id_rekam_medis"] == $id_rekam_medis) {
            return $posisi;
        }
        $posisi++;
    }
    return null;
}

function buatPilihanJam($jam_mulai, $jam_selesai, $intervalMenit = 30)
{
    $list = [];
    $mulai = strtotime(date("Y-m-d") . " " . $jam_mulai);
    $selesai = strtotime(date("Y-m-d") . " " . $jam_selesai);

    while ($mulai < $selesai) {
        $list[] = date("H:i", $mulai);
        $mulai += $intervalMenit * 60;
    }

    return $list;
}

// =======================
// AMBIL ID PASIEN
// =======================
$user_id_safe = mysqli_real_escape_string($conn, $user_id);

$qP = mysqli_query(
    $conn,
    "
    SELECT id_pasien 
    FROM pasienm 
    WHERE id_user = '$user_id_safe'
",
);

if (!$qP) {
    die("Query pasien error: " . mysqli_error($conn));
}

$dP = mysqli_fetch_assoc($qP);
$id_pasien = $dP["id_pasien"] ?? "";

if ($id_pasien == "") {
    die(
        "ID pasien tidak ditemukan. Pastikan akun pasien terhubung dengan tabel pasienm."
    );
}

// ==========================================
// LOGIKA BATAL BOOKING PASIEN
// Hanya booking jadwal yang statusnya Menunggu
// ==========================================
if (isset($_POST["batal_booking"])) {
    $id_rm_batal = mysqli_real_escape_string(
        $conn,
        $_POST["id_rekam_medis"] ?? "",
    );

    if ($id_rm_batal == "") {
        header(
            "Location: index.php?page=beranda&err=Data booking tidak ditemukan",
        );
        exit();
    }

    $update_batal = mysqli_query(
        $conn,
        "
        UPDATE rekam_medis
        SET status = 'Batal'
        WHERE id_rekam_medis = '$id_rm_batal'
        AND id_pasien = '$id_pasien'
        AND jenis_antrean = 'Jadwal'
        AND status = 'Menunggu'
    ",
    );

    if (!$update_batal) {
        header(
            "Location: index.php?page=beranda&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    if (mysqli_affected_rows($conn) > 0) {
        header(
            "Location: index.php?page=beranda&msg=Booking berhasil dibatalkan",
        );
        exit();
    } else {
        header(
            "Location: index.php?page=beranda&err=Booking tidak bisa dibatalkan. Mungkin sudah selesai atau sudah dibatalkan.",
        );
        exit();
    }
}

// ==========================================
// FUNGSI GENERATE ID
// ==========================================
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
        ",
        );

        if ($cek && mysqli_num_rows($cek) == 0) {
            return $new_id;
        }
    }
}

// ==========================================
// NOMOR ANTREAN A001, A002, A003
// ==========================================
function generateNoAntrean($conn, $tanggal)
{
    $tanggal = mysqli_real_escape_string($conn, $tanggal);

    $q = mysqli_query(
        $conn,
        "
        SELECT no_antrian
        FROM rekam_medis
        WHERE tgl_kunjungan = '$tanggal'
        AND no_antrian LIKE 'A%'
        ORDER BY CAST(SUBSTRING(no_antrian, 2) AS UNSIGNED) DESC
        LIMIT 1
    ",
    );

    if (!$q || mysqli_num_rows($q) == 0) {
        return "A001";
    }

    $d = mysqli_fetch_assoc($q);
    $last_no = $d["no_antrian"];

    $angka = (int) substr($last_no, 1);
    $angka++;

    return "A" . str_pad($angka, 3, "0", STR_PAD_LEFT);
}

// ==========================================
// FUNGSI DETEKSI PRIORITAS DARURAT
// ==========================================
function cekPrioritas($keluhan)
{
    $keywords = [
        "asma",
        "pingsan",
        "tertusuk",
        "sesak",
        "jantung",
        "darah",
        "perdarahan",
        "kecelakaan",
        "kejang",
        "lemas",
    ];

    foreach ($keywords as $key) {
        if (stripos($keluhan, $key) !== false) {
            return 1;
        }
    }

    return 0;
}

// ==========================================
// LOGIKA BOOKING JADWAL DOKTER
// Kolom jadwalm.tanggal berisi hari: Senin - Jumat
// Status jadwal: Buka / Tutup
// ==========================================
if (isset($_POST["ambil_antrean_jadwal"])) {
    $id_jadwal = mysqli_real_escape_string($conn, $_POST["id_jadwal"] ?? "");
    $jam_booking_raw = mysqli_real_escape_string(
        $conn,
        $_POST["jam_booking"] ?? "",
    );
    $keluhan = mysqli_real_escape_string(
        $conn,
        $_POST["keluhan_booking"] ?? "",
    );

    if ($id_jadwal == "" || $jam_booking_raw == "" || $keluhan == "") {
        header(
            "Location: index.php?page=jadwal_dokter&err=Data booking belum lengkap. Pilih jadwal, jam, dan isi keluhan.",
        );
        exit();
    }

    if (strlen($jam_booking_raw) == 5) {
        $jam_booking = $jam_booking_raw . ":00";
    } else {
        $jam_booking = $jam_booking_raw;
    }

    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $jam_booking)) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Format jam booking tidak valid",
        );
        exit();
    }

    $qJadwal = mysqli_query(
        $conn,
        "
        SELECT *
        FROM jadwalm
        WHERE id_jadwal = '$id_jadwal'
        AND status = 'Buka'
        LIMIT 1
    ",
    );

    if (!$qJadwal) {
        header(
            "Location: index.php?page=jadwal_dokter&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    if (mysqli_num_rows($qJadwal) == 0) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Jadwal dokter tidak tersedia atau sedang tutup",
        );
        exit();
    }

    $jadwal = mysqli_fetch_assoc($qJadwal);

    $id_staff = mysqli_real_escape_string($conn, $jadwal["id_staff"]);
    $hari_jadwal = mysqli_real_escape_string($conn, $jadwal["tanggal"]); // isinya hari
    $jam_mulai = mysqli_real_escape_string($conn, $jadwal["jam_mulai"]);
    $jam_selesai = mysqli_real_escape_string($conn, $jadwal["jam_selesai"]);

    if ($jam_booking < $jam_mulai || $jam_booking >= $jam_selesai) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Jam booking harus berada di dalam jam buka dokter",
        );
        exit();
    }

    // Pasien tidak perlu memilih tanggal.
    // Sistem otomatis mengambil tanggal terdekat sesuai hari jadwal.
    // Contoh: pilih jadwal Rabu, maka tgl_kunjungan otomatis Rabu terdekat.
    $tgl_kunjungan = tanggalBerikutnyaDariHari($hari_jadwal, $jam_booking);

    if ($tgl_kunjungan === false) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Jam booking sudah lewat. Silakan pilih jam setelah waktu sekarang.",
        );
        exit();
    }

    $cekPasien = mysqli_query(
        $conn,
        "
        SELECT id_rekam_medis
        FROM rekam_medis
        WHERE id_pasien = '$id_pasien'
        AND tgl_kunjungan = '$tgl_kunjungan'
        AND status = 'Menunggu'
        LIMIT 1
    ",
    );

    if ($cekPasien && mysqli_num_rows($cekPasien) > 0) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Anda sudah memiliki antrean aktif pada tanggal tersebut",
        );
        exit();
    }

    $cekJam = mysqli_query(
        $conn,
        "
        SELECT id_rekam_medis
        FROM rekam_medis
        WHERE id_staff = '$id_staff'
        AND tgl_kunjungan = '$tgl_kunjungan'
        AND waktu_booking = '$jam_booking'
        AND status != 'Batal'
        LIMIT 1
    ",
    );

    $cekAntreanAktif = mysqli_query(
        $conn,
        "
        SELECT id_rekam_medis 
        FROM rekam_medis 
        WHERE id_pasien = '$id_pasien' 
        AND status IN ('Menunggu', 'Darurat')
        LIMIT 1
    ",
    );

    if (mysqli_num_rows($cekAntreanAktif) > 0) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Gagal! Anda masih memiliki antrean aktif yang belum diproses oleh dokter.",
        );
        exit();
    }

    if ($cekJam && mysqli_num_rows($cekJam) > 0) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Jam tersebut sudah dipakai pasien lain, silakan pilih jam lain",
        );
        exit();
    }
    // ... kode pencarian tgl_kunjungan dan cek kuota ...

    $id_rm = generateID($conn, "RM", "rekam_medis", "id_rekam_medis");
    $no_baru = generateNoAntrean($conn, $tgl_kunjungan);

    // LOGIKA PRIORITAS
    $is_priority = cekPrioritas($keluhan);
    $status_final = $is_priority == 1 ? "Darurat" : "Menunggu";

    $insert = mysqli_query(
        $conn,
        "
        INSERT INTO rekam_medis
        (id_rekam_medis, id_pasien, id_staff, no_antrian, tgl_kunjungan, waktu_booking, keluhan, status, jenis_antrean)
        VALUES
        ('$id_rm', '$id_pasien', '$id_staff', '$no_baru', '$tgl_kunjungan', '$jam_booking', '$keluhan', '$status_final', 'Jadwal')
    ",
    );

    if ($insert) {
        // WAJIB ADA REDIRECT SUPAYA NOTIFIKASI MUNCUL
        header(
            "Location: index.php?page=beranda&msg=Booking berhasil! No Antrean: $no_baru",
        );
        exit();
    } else {
        header(
            "Location: index.php?page=jadwal_dokter&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }
}

// ==========================================
// LOGIKA AMBIL ANTREAN LANGSUNG
// Cek jadwal dokter hari ini dan jam saat ini dulu
// Kalau tidak ada dokter buka, arahkan ke booking
// ==========================================
if (isset($_POST["ambil_antrean"])) {
    $tgl_skrg = date("Y-m-d");
    $hari_ini = hariIndonesiaDariTanggal($tgl_skrg);
    $jam_skrg = date("H:i:s");
    $keluhan = mysqli_real_escape_string($conn, $_POST["keluhan"] ?? "");

    if ($keluhan == "") {
        header(
            "Location: index.php?page=antrean&err=Keluhan wajib diisi",
        );
        exit();
    }

    // Cek apakah ada dokter yang buka hari ini pada jam sekarang
    $qDokterBuka = mysqli_query(
        $conn,
        "
        SELECT id_staff, jam_mulai, jam_selesai
        FROM jadwalm
        WHERE tanggal = '$hari_ini'
        AND status = 'Buka'
        AND jam_mulai <= '$jam_skrg'
        AND jam_selesai > '$jam_skrg'
        ORDER BY jam_mulai ASC
        LIMIT 1
    ",
    );

    if (!$qDokterBuka) {
        header(
            "Location: index.php?page=antrean&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    if (mysqli_num_rows($qDokterBuka) == 0) {
        header(
            "Location: index.php?page=jadwal_dokter&err=Dokter sedang tidak buka untuk antrean langsung. Silakan booking di Jadwal Dokter.",
        );
        exit();
    }

    $dokterBuka = mysqli_fetch_assoc($qDokterBuka);
    $id_staff_langsung = mysqli_real_escape_string(
        $conn,
        $dokterBuka["id_staff"],
    );

    $cekAntreanAktif = mysqli_query(
        $conn,
        "
        SELECT id_rekam_medis 
        FROM rekam_medis 
        WHERE id_pasien = '$id_pasien' 
        AND status IN ('Menunggu', 'Darurat')
        LIMIT 1
    ",
    );

    if (mysqli_num_rows($cekAntreanAktif) > 0) {
        header(
            "Location: index.php?page=antrean&err=Gagal! Anda masih memiliki antrean aktif. Selesaikan pemeriksaan sebelumnya terlebih dahulu.",
        );
        exit();
    }

    // ... kode pencarian dokter buka ...

    $id_rm = generateID($conn, "RM", "rekam_medis", "id_rekam_medis");
    $no_baru = generateNoAntrean($conn, $tgl_skrg);

    // LOGIKA PRIORITAS
    $is_priority = cekPrioritas($keluhan);
    $status_final = $is_priority == 1 ? "Darurat" : "Menunggu";

    $insert = mysqli_query(
        $conn,
        "
        INSERT INTO rekam_medis 
        (id_rekam_medis, id_pasien, id_staff, no_antrian, tgl_kunjungan, waktu_booking, keluhan, status, jenis_antrean) 
        VALUES 
        ('$id_rm', '$id_pasien', '$id_staff_langsung', '$no_baru', '$tgl_skrg', '$jam_skrg', '$keluhan', '$status_final', 'Langsung')
    ",
    );

    if ($insert) {
        // WAJIB ADA REDIRECT
        header(
            "Location: index.php?page=beranda&msg=Antrean langsung $no_baru berhasil diambil!",
        );
        exit();
    } else {
        header(
            "Location: index.php?page=antrean&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }
}

$diagnosa_options = [];

if ($qDiagnosaBooking) {
    while ($dx = mysqli_fetch_assoc($qDiagnosaBooking)) {
        $diagnosa_options[] = $dx;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Patient Panel - ASTARhealth</title>

  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
  
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
        background: white; 
        border-radius: 18px; 
        padding: 25px; 
        display: flex; 
        align-items: center; 
        justify-content: space-between; 
        border-left: 6px solid var(--astar-blue); 
        box-shadow: 0 10px 20px rgba(0,0,0,0.03); 
        transition: 0.3s; 
    }

/* Kotak Tiket Antrean - Dibuat lebih ramping & pas */
.antrean-card { 
    background: linear-gradient(135deg, #0057B8 0%, #003d82 100%); 
    color: white; 
    border-radius: 20px; 
    padding: 20px !important; 
    text-align: center; 
    border: none; 
    box-shadow: 0 10px 30px rgba(0,87,184,0.2);
    position: relative;
    overflow: hidden;
}

/* Warna Merah khusus Darurat */
.antrean-card.emergency { 
    background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%) !important; 
    box-shadow: 0 10px 30px rgba(220,53,69,0.3);
}

/* Ukuran Angka Antrean - Tidak terlalu raksasa */
.antrean-number { 
    font-size: 3.5rem; 
    font-weight: 800; 
    line-height: 1; 
    margin: 10px 0; 
    display: block;
}

/* Badge status di dalam tiket */
.status-badge-tiket {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}
    .table thead th { 
        background: #f8fafc; 
        color: #64748b; 
        font-weight: 700; 
        text-transform: uppercase; 
        font-size: 12px; 
        padding: 15px; 
        border: none; 
    }

    .jadwal-card { border: 0; border-radius: 22px; box-shadow: 0 10px 25px rgba(0,0,0,0.04); transition: 0.25s; overflow: hidden; }
    .jadwal-card:hover { transform: translateY(-4px); box-shadow: 0 16px 30px rgba(0,0,0,0.08); }
    .jadwal-date { background: var(--astar-blue); color: white; padding: 20px; text-align: center; }
    .jadwal-day { font-size: 20px; font-weight: 800; }
    .jadwal-time { font-size: 24px; font-weight: 800; color: var(--astar-blue); }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>

<body>

<header class="top-header">
    <div class="d-flex align-items-center gap-3">
        <div id="sidebarToggle" class="text-white"><i class="bi bi-list"></i></div>
        <img src="../assets/img/logoA.png" style="max-height: 70px; filter: brightness(0) invert(1);">
        <div id="digitalClock" class="d-none d-md-block text-white fw-bold"></div>
    </div>

    <div class="dropdown">
        <a href="#" data-bs-toggle="dropdown" class="text-white text-decoration-none d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block lh-1">
                <div class="fw-bold mb-1"><?= e($pasien_name) ?></div>
                <small style="opacity: 0.8; font-size: 10px;">Pasien Klinik</small>
            </div>
            <i class="bi bi-person-circle fs-2"></i>
        </a>
    </div>
</header>

<div class="sidebar">
    <div class="nav-group-title">Menu Utama</div>

    <nav class="nav flex-column">
        <a class="nav-link <?= $active_page == "beranda"
            ? "active"
            : "" ?>" href="index.php?page=beranda">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <a class="nav-link <?= $active_page == "antrean"
            ? "active"
            : "" ?>" href="index.php?page=antrean">
            <i class="bi bi-ticket-perforated-fill"></i> Ambil Antrean
        </a>

        <a class="nav-link <?= $active_page == "jadwal_dokter"
            ? "active"
            : "" ?>" href="index.php?page=jadwal_dokter">
            <i class="bi bi-calendar-week-fill"></i> Jadwal Dokter
        </a>
    </nav>

    <div class="nav-group-title">Layanan Medis</div>

    <nav class="nav flex-column">
        <a class="nav-link <?= $active_page == "riwayat"
            ? "active"
            : "" ?>" href="index.php?page=riwayat">
            <i class="bi bi-clock-history"></i> Riwayat Berobat
        </a>

        <a class="nav-link <?= $active_page == "obat"
            ? "active"
            : "" ?>" href="index.php?page=obat">
            <i class="bi bi-capsule-pill"></i> Stok Obat Klinik
        </a>
    </nav>
    <div class="nav-group-title">Akun</div>
    <nav class="nav flex-column">
        <a class="nav-link nav-link-logout js-swal-logout" href="../logout.php">
        <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>
</div>

<main class="main-content">


    <?php
    if ($active_page === "beranda") {
?>
<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-dark">Halo, <?= e(
        explode(" ", $pasien_name)[0],
    ) ?>!</h3>
    <span class="text-muted small fw-bold text-uppercase"><i class="bi bi-calendar3 me-1"></i> <?= date(
        "d M Y",
    ) ?></span>
</div>

<div class="row g-4">
    <!-- KOLOM KIRI: TIKET ANTREAN -->
    <div class="col-lg-5">
        <?php
        $q_my = mysqli_query(
            $conn,
            "
    SELECT * FROM rekam_medis 
    WHERE id_pasien = '$id_pasien' 
    AND status IN ('Menunggu', 'Darurat') 
    AND tgl_kunjungan >= CURDATE() -- TAMBAHKAN INI: Hanya ambil tiket hari ini atau yang akan datang
    ORDER BY tgl_kunjungan ASC, waktu_booking ASC 
    LIMIT 1
",
        );

        if ($q_my && mysqli_num_rows($q_my) > 0):

            $d_my = mysqli_fetch_assoc($q_my);
            $posisi_antrian = hitungPosisiAntrean(
                $conn,
                $d_my["id_rekam_medis"],
                $d_my["tgl_kunjungan"],
            );
            $is_darurat = $d_my["status"] == "Darurat";
            ?>
            <div class="antrean-card shadow-lg <?= $is_darurat
                ? "emergency"
                : "" ?>">
                <div class="mb-2">
                    <span class="status-badge-tiket">
                        <?= $is_darurat
                            ? "🚨 Prioritas Darurat"
                            : "Antrean Normal" ?>
                    </span>
                </div>
                
                <small class="opacity-75 text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 1px;">Nomor Antrean Anda</small>
                <div class="antrean-number"><?= e($d_my["no_antrian"]) ?></div>

                <div class="mb-3">
                    <span class="badge bg-white text-dark px-3 py-1 rounded-pill fw-bold" style="font-size: 11px;">
                        <?= e($d_my["jenis_antrean"]) ?> — <?= e(
     strtoupper($d_my["status"]),
 ) ?>
                    </span>
                </div>

                <div class="small opacity-75 mb-3">
                    <i class="bi bi-clock me-1"></i> <?= e(
                        date("d M Y", strtotime($d_my["tgl_kunjungan"])),
                    ) ?> • <?= e(substr($d_my["waktu_booking"], 0, 5)) ?>
                </div>

                <div class="bg-white bg-opacity-10 rounded-4 p-2 border border-white border-opacity-25">
                    <p class="mb-0 small">Posisi Antrean Sekarang:</p>
                    <h4 class="fw-800 mb-0"><?= e(
                        $posisi_antrian ?? "-",
                    ) ?></h4>
                </div>

                <?php if ($d_my["jenis_antrean"] == "Jadwal"): ?>
                    <form method="POST" class="mt-3 js-swal-confirm" data-swal-title="Batalkan Booking?" data-swal-text="Data booking akan dihapus dari antrean." data-swal-confirm="Ya, Batalkan">
                        <input type="hidden" name="id_rekam_medis" value="<?= e(
                            $d_my["id_rekam_medis"],
                        ) ?>">
                        <button type="submit" name="batal_booking" class="btn btn-sm btn-light text-danger fw-bold rounded-pill px-3">
                            <i class="bi bi-x-circle me-1"></i> Batal Booking
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php
        else:
             ?>
            <div class="antrean-card shadow-lg opacity-50" style="filter: grayscale(1); background: #64748b;">
                <h6 class="fw-bold opacity-75 text-uppercase">Nomor Antrean</h6>
                <div class="antrean-number">--</div>
                <p class="mb-0 small opacity-75">Belum ada antrean aktif.</p>
            </div>
        <?php
        endif;
        ?>
    </div>
    
    <!-- KOLOM KANAN: STATS & PANDUAN -->
    <div class="col-lg-7">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div>
                        <div class="small fw-bold text-muted mb-1">TOTAL BEROBAT</div>
                        <div class="h2 fw-bold text-primary mb-0">
                            <?= mysqli_num_rows(
                                mysqli_query(
                                    $conn,
                                    "SELECT id_rekam_medis FROM rekam_medis WHERE id_pasien='$id_pasien' AND status='Selesai'",
                                ),
                            ) ?>
                        </div>
                    </div>
                    <i class="bi bi-clipboard2-pulse fs-2 text-primary opacity-25"></i>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card" style="border-left-color: #1cc88a;">
                    <div>
                        <div class="small fw-bold text-muted mb-1">DATA TERDAFTAR</div>
                        <div class="h5 fw-bold text-success mb-0">PROFIL AKTIF</div>
                    </div>
                    <i class="bi bi-shield-check fs-2 text-success opacity-25"></i>
                </div>
            </div>
        </div>

        <div class="data-container py-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle-fill text-primary me-2"></i>Panduan Layanan</h6>
            <ul class="list-unstyled small text-muted mb-0">
                <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i><strong>Antrean langsung</strong> hanya saat jam praktik dokter.</li>
                <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i><strong>Emergency</strong> otomatis diprioritaskan sistem.</li>
                <li><i class="bi bi-check2-circle text-success me-2"></i>Satu akun hanya bisa memiliki 1 antrean aktif.</li>
            </ul>
        </div>
    </div>
</div>

<div class="data-container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-clipboard2-pulse text-primary me-2"></i>Data Kunjungan Saya</h5>
            <small class="text-muted">Maksimal 10 data ditampilkan pada setiap halaman.</small>
        </div>
        <a href="index.php?page=riwayat" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold">
            Lihat Detail
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Tanggal / Jam</th>
                    <th>No. Antrean</th>
                    <th>Jenis</th>
                    <th>Keluhan</th>
                    <th>Diagnosa</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $qDashboardKunjungan = mysqli_query(
                    $conn,
                    "SELECT rm.tgl_kunjungan, rm.waktu_booking, rm.no_antrian, rm.jenis_antrean,
                            rm.keluhan, rm.status, d.nama_penyakit
                     FROM rekam_medis rm
                     LEFT JOIN diagnosam d ON d.id_diagnosa = rm.id_diagnosa
                     WHERE rm.id_pasien = '$id_pasien'
                     ORDER BY rm.tgl_kunjungan DESC, rm.waktu_booking DESC, rm.id_rekam_medis DESC"
                );

                if (!$qDashboardKunjungan || mysqli_num_rows($qDashboardKunjungan) === 0):
                ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">Belum ada data kunjungan.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($kunjunganDashboard = mysqli_fetch_assoc($qDashboardKunjungan)): ?>
                        <?php
                        $statusDashboard = (string) ($kunjunganDashboard['status'] ?? '-');
                        $warnaDashboard = match ($statusDashboard) {
                            'Selesai' => 'success',
                            'Darurat' => 'danger',
                            'Diproses' => 'warning',
                            'Batal' => 'secondary',
                            default => 'primary',
                        };
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold small"><?= e(date('d M Y', strtotime($kunjunganDashboard['tgl_kunjungan']))) ?></div>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= e(substr((string) $kunjunganDashboard['waktu_booking'], 0, 5)) ?></small>
                            </td>
                            <td><span class="fw-bold text-primary"><?= e($kunjunganDashboard['no_antrian'] ?: '-') ?></span></td>
                            <td><span class="badge bg-light text-dark border"><?= e($kunjunganDashboard['jenis_antrean'] ?: '-') ?></span></td>
                            <td><div class="small text-truncate" style="max-width:260px" title="<?= e($kunjunganDashboard['keluhan'] ?: '-') ?>"><?= e($kunjunganDashboard['keluhan'] ?: '-') ?></div></td>
                            <td><?= e($kunjunganDashboard['nama_penyakit'] ?: 'Belum ditentukan') ?></td>
                            <td><span class="badge bg-<?= e($warnaDashboard) ?> bg-opacity-10 text-<?= e($warnaDashboard) ?> px-3 py-2"><?= e($statusDashboard) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
} else {
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
}
    ?>
</main>

<div class="modal fade" id="modalLogout" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-body text-center p-5">
                <div class="text-danger mb-4"><i class="bi bi-exclamation-circle-fill" style="font-size: 4rem; opacity: 0.2;"></i></div>
                <h4 class="fw-bold mb-2">Yakin Ingin Keluar?</h4>
                <p class="text-muted small mb-4">Sesi Anda akan berakhir. Pastikan data pendaftaran Anda telah tercatat.</p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-100 py-2 fw-bold rounded-3" data-bs-dismiss="modal">Batal</button>
                    <a href="../logout.php" class="btn btn-danger w-100 py-2 fw-bold rounded-3 shadow-sm text-white text-decoration-none">Ya, Keluar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<?php include dirname(__DIR__) . '/sweetalert_global.php'; ?>
<script>
function updateClock() { 
    const now = new Date(); 
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }; 
    const clock = document.getElementById('digitalClock');
    if (clock) clock.innerText = now.toLocaleDateString('id-ID', options); 
}
setInterval(updateClock, 1000); 
updateClock();

<?php if (
    isset($posisi_antrian) &&
    $posisi_antrian == 1 &&
    isset($d_my) &&
    $d_my["tgl_kunjungan"] == date("Y-m-d")
): ?>
setTimeout(function() {
    Swal.fire({
        icon: 'info',
        title: 'Giliran Anda',
        text: 'Sekarang adalah antrean Anda. Silakan bersiap menuju ruang pemeriksaan.',
        confirmButtonText: 'Siap',
        confirmButtonColor: '#175cdd'
    });
}, 700);
<?php endif; ?>

const sidebarToggle = document.getElementById('sidebarToggle');
const body = document.body;
if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function() { body.classList.toggle('sidebar-toggled'); });
}

document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar || !sidebarToggle) return;
    const isClickInsideSidebar = sidebar.contains(event.target);
    const isClickInsideToggle = sidebarToggle.contains(event.target);
    if (window.innerWidth <= 768 && !isClickInsideSidebar && !isClickInsideToggle) body.classList.remove('sidebar-toggled');
});
</script>

<?php include dirname(__DIR__) . '/pagination_global.php'; ?>
<?php include dirname(__DIR__) . '/login_success_popup.php'; ?>
</body>
</html>
