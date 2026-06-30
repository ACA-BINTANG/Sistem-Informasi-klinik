<?php
session_start();
require_once "koneksi.php";

/** @var mysqli $conn */

// =======================
// PROTEKSI ROLE PASIEN
// =======================
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Pasien") {
    header("Location: login.php?pesan=Akses Ditolak!");
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

    $q = mysqli_query(
        $conn,
        "
        SELECT id_rekam_medis
        FROM rekam_medis
        WHERE status = 'Menunggu'
        AND tgl_kunjungan = '$tgl_kunjungan'
        ORDER BY
            jenis_antrean DESC,
            CASE
                WHEN jenis_antrean = 'Jadwal' AND tgl_kunjungan = CURDATE() AND waktu_booking <= CURTIME() THEN 0
                WHEN jenis_antrean = 'Langsung' THEN 1
                ELSE 2
            END ASC,
            waktu_booking ASC,
            CAST(SUBSTRING(no_antrian, 2) AS UNSIGNED) ASC
    ",
    );

    if (!$q) {
        return null;
    }

    $posisi = 1;
    while ($row = mysqli_fetch_assoc($q)) {
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
            "Location: dashboard_pasien.php?page=beranda&err=Data booking tidak ditemukan",
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
            "Location: dashboard_pasien.php?page=beranda&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    if (mysqli_affected_rows($conn) > 0) {
        header(
            "Location: dashboard_pasien.php?page=beranda&msg=Booking berhasil dibatalkan",
        );
        exit();
    } else {
        header(
            "Location: dashboard_pasien.php?page=beranda&err=Booking tidak bisa dibatalkan. Mungkin sudah selesai atau sudah dibatalkan.",
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
            "Location: dashboard_pasien.php?page=jadwal_dokter&err=Data booking belum lengkap. Pilih jadwal, jam, dan isi keluhan.",
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
            "Location: dashboard_pasien.php?page=jadwal_dokter&err=Format jam booking tidak valid",
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
            "Location: dashboard_pasien.php?page=jadwal_dokter&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    if (mysqli_num_rows($qJadwal) == 0) {
        header(
            "Location: dashboard_pasien.php?page=jadwal_dokter&err=Jadwal dokter tidak tersedia atau sedang tutup",
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
            "Location: dashboard_pasien.php?page=jadwal_dokter&err=Jam booking harus berada di dalam jam buka dokter",
        );
        exit();
    }

    // Pasien tidak perlu memilih tanggal.
    // Sistem otomatis mengambil tanggal terdekat sesuai hari jadwal.
    // Contoh: pilih jadwal Rabu, maka tgl_kunjungan otomatis Rabu terdekat.
    $tgl_kunjungan = tanggalBerikutnyaDariHari($hari_jadwal, $jam_booking);

    if ($tgl_kunjungan === false) {
        header(
            "Location: dashboard_pasien.php?page=jadwal_dokter&err=Jam booking sudah lewat. Silakan pilih jam setelah waktu sekarang.",
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
            "Location: dashboard_pasien.php?page=jadwal_dokter&err=Anda sudah memiliki antrean aktif pada tanggal tersebut",
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

    if ($cekJam && mysqli_num_rows($cekJam) > 0) {
        header(
            "Location: dashboard_pasien.php?page=jadwal_dokter&err=Jam tersebut sudah dipakai pasien lain, silakan pilih jam lain",
        );
        exit();
    }

    $id_rm = generateID($conn, "RM", "rekam_medis", "id_rekam_medis");
    $no_baru = generateNoAntrean($conn, $tgl_kunjungan);
    $jenis_antrean = cekPrioritas($keluhan);

    $insert = mysqli_query(
        $conn,
        "
        INSERT INTO rekam_medis
        (
            id_rekam_medis,
            id_pasien,
            id_staff,
            no_antrian,
            tgl_kunjungan,
            waktu_booking,
            keluhan,
            status,
            jenis_antrean,
            jenis_antrean
        )
        VALUES
        (
            '$id_rm',
            '$id_pasien',
            '$id_staff',
            '$no_baru',
            '$tgl_kunjungan',
            '$jam_booking',
            '$keluhan',
            'Menunggu',
            'Jadwal',
            '$jenis_antrean'
        )
    ",
    );

    if ($insert) {
        header(
            "Location: dashboard_pasien.php?page=beranda&msg=Booking berhasil dibuat. Nomor antrean Anda $no_baru",
        );
        exit();
    } else {
        header(
            "Location: dashboard_pasien.php?page=jadwal_dokter&err=" .
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
            "Location: dashboard_pasien.php?page=antrean&err=Keluhan wajib diisi",
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
            "Location: dashboard_pasien.php?page=antrean&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }

    if (mysqli_num_rows($qDokterBuka) == 0) {
        header(
            "Location: dashboard_pasien.php?page=jadwal_dokter&err=Dokter sedang tidak buka untuk antrean langsung. Silakan booking di Jadwal Dokter.",
        );
        exit();
    }

    $dokterBuka = mysqli_fetch_assoc($qDokterBuka);
    $id_staff_langsung = mysqli_real_escape_string(
        $conn,
        $dokterBuka["id_staff"],
    );

    $cek = mysqli_query(
        $conn,
        "
        SELECT id_rekam_medis 
        FROM rekam_medis 
        WHERE id_pasien = '$id_pasien' 
        AND tgl_kunjungan = '$tgl_skrg' 
        AND status = 'Menunggu'
        LIMIT 1
    ",
    );

    if ($cek && mysqli_num_rows($cek) > 0) {
        header(
            "Location: dashboard_pasien.php?page=beranda&err=Anda masih memiliki antrean aktif hari ini.",
        );
        exit();
    }

    $id_rm = generateID($conn, "RM", "rekam_medis", "id_rekam_medis");
    $no_baru = generateNoAntrean($conn, $tgl_skrg);
    $jenis_antrean = cekPrioritas($keluhan);

    $insert = mysqli_query(
        $conn,
        "
        INSERT INTO rekam_medis 
        (
            id_rekam_medis, 
            id_pasien, 
            id_staff,
            no_antrian, 
            tgl_kunjungan, 
            waktu_booking, 
            keluhan, 
            status, 
            jenis_antrean
        ) 
        VALUES 
        (
            '$id_rm', 
            '$id_pasien', 
            '$id_staff_langsung',
            '$no_baru', 
            '$tgl_skrg', 
            '$jam_skrg', 
            '$keluhan', 
            'Menunggu',
            'Langsung'
        )
    ",
    );

    if ($insert) {
        header(
            "Location: dashboard_pasien.php?page=beranda&msg=Antrean langsung $no_baru berhasil diambil!",
        );
        exit();
    } else {
        header(
            "Location: dashboard_pasien.php?page=antrean&err=" .
                urlencode(mysqli_error($conn)),
        );
        exit();
    }
}

// =======================
// DATA DIAGNOSA UNTUK SELECT BOOKING
// =======================
$qDiagnosaBooking = mysqli_query(
    $conn,
    "
    SELECT id_diagnosa, nama_penyakit
    FROM diagnosam
    ORDER BY nama_penyakit ASC
",
);

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

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root { 
        --astar-blue: #0057B8; 
        --astar-soft-blue: #eef4ff; 
        --sidebar-bg: #ffffff; 
    }

    body { 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        background-color: #f4f7fa; 
        color: #334155; 
    }

    .top-header { 
        height: 70px; 
        background: var(--astar-blue); 
        display: flex; 
        align-items: center; 
        justify-content: space-between; 
        padding: 0 30px; 
        color: white; 
        position: fixed; 
        top: 0; 
        width: 100%; 
        z-index: 1001; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
    }

    #digitalClock { 
        font-weight: 600; 
        font-size: 14px; 
        background: rgba(255,255,255,0.1); 
        padding: 5px 15px; 
        border-radius: 50px; 
    }

    .sidebar { 
        width: 280px; 
        background: var(--sidebar-bg); 
        height: 100vh; 
        position: fixed; 
        top: 70px; 
        left: 0;
        border-right: 1px solid #e2e8f0; 
        z-index: 1000; 
        padding: 15px 0; 
        overflow-y: auto; 
        transition: all 0.3s ease;
    }

    .main-content { 
        margin-left: 280px; 
        padding: 100px 40px 40px; 
        animation: fadeIn 0.5s ease; 
        transition: all 0.3s ease;
    }

    body.sidebar-toggled .sidebar { left: -280px; }
    body.sidebar-toggled .main-content { margin-left: 0; }

    @media (max-width: 768px) {
        .sidebar { left: -280px; }
        .main-content { margin-left: 0; padding: 100px 20px 40px; }
        body.sidebar-toggled .sidebar { left: 0; }
    }

    #sidebarToggle { cursor: pointer; font-size: 1.5rem; padding: 5px 10px; border-radius: 8px; transition: 0.2s; }
    #sidebarToggle:hover { background: rgba(255,255,255,0.1); }

    .nav-group-title { 
        font-size: 11px; 
        text-transform: uppercase; 
        color: #94a3b8; 
        font-weight: 800; 
        padding: 20px 25px 8px; 
        letter-spacing: 1px; 
    }

    .nav-link { 
        padding: 12px 25px; 
        color: #64748b; 
        font-weight: 500; 
        display: flex; 
        align-items: center; 
        transition: 0.2s; 
        text-decoration: none; 
        font-size: 14px; 
        margin: 0 15px; 
        border-radius: 10px; 
    }

    .nav-link i { font-size: 1.2rem; width: 35px; }
    .nav-link:hover { background: var(--astar-soft-blue); color: var(--astar-blue); transform: translateX(5px); }
    .nav-link.active { background: var(--astar-blue); color: #fff; box-shadow: 0 4px 12px rgba(0,87,184,0.3); }

    .data-container { 
        background: white; 
        border-radius: 20px; 
        padding: 30px; 
        box-shadow: 0 10px 25px rgba(0,0,0,0.02); 
        border: 1px solid #f1f5f9; 
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

    .antrean-card { 
        background: linear-gradient(135deg, #0057B8 0%, #003d82 100%); 
        color: white; 
        border-radius: 24px; 
        padding: 40px; 
        text-align: center; 
        border: none; 
    }

    .antrean-card.emergency { background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%); }
    .antrean-number { font-size: 5rem; font-weight: 800; line-height: 1; margin: 20px 0; text-shadow: 0 4px 15px rgba(0,0,0,0.2); }

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
        <img src="assets/img/logoA.png" style="max-height: 70px; filter: brightness(0) invert(1);">
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

        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-2" style="border-radius: 12px;">
            <li>
                <a class="dropdown-item rounded-2 text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalLogout">
                    <i class="bi bi-box-arrow-right me-2"></i> Keluar
                </a>
            </li>
        </ul>
    </div>
</header>

<div class="sidebar">
    <div class="nav-group-title">Menu Utama</div>

    <nav class="nav flex-column">
        <a class="nav-link <?= $active_page == "beranda"
            ? "active"
            : "" ?>" href="dashboard_pasien.php?page=beranda">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <a class="nav-link <?= $active_page == "antrean"
            ? "active"
            : "" ?>" href="dashboard_pasien.php?page=antrean">
            <i class="bi bi-ticket-perforated-fill"></i> Ambil Antrean
        </a>

        <a class="nav-link <?= $active_page == "jadwal_dokter"
            ? "active"
            : "" ?>" href="dashboard_pasien.php?page=jadwal_dokter">
            <i class="bi bi-calendar-week-fill"></i> Jadwal Dokter
        </a>
    </nav>

    <div class="nav-group-title">Layanan Medis</div>

    <nav class="nav flex-column">
        <a class="nav-link <?= $active_page == "riwayat"
            ? "active"
            : "" ?>" href="dashboard_pasien.php?page=riwayat">
            <i class="bi bi-clock-history"></i> Riwayat Berobat
        </a>

        <a class="nav-link <?= $active_page == "obat"
            ? "active"
            : "" ?>" href="dashboard_pasien.php?page=obat">
            <i class="bi bi-capsule-pill"></i> Stok Obat Klinik
        </a>
    </nav>
</div>

<main class="main-content">

    <?php if (isset($_GET["msg"])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4 rounded-4 fw-bold">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($_GET["msg"]) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["err"])): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4 rounded-4 fw-bold">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e(
                $_GET["err"],
            ) ?>
        </div>
    <?php endif; ?>

    <?php if ($active_page == "beranda"): ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0 text-dark">Halo, <?= e(
                explode(" ", $pasien_name)[0],
            ) ?>!</h3>
            <span class="text-muted small fw-bold text-uppercase"><i class="bi bi-calendar3 me-1"></i> <?= date(
                "d M Y",
            ) ?></span>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <?php 
                    $q_my = mysqli_query($conn, "
                        SELECT id_rekam_medis, no_antrian, status, jenis_antrean, is_priority, tgl_kunjungan, waktu_booking, keluhan
                        FROM rekam_medis 
                        WHERE id_pasien = '$id_pasien' 
                        AND status = 'Menunggu'
                        ORDER BY tgl_kunjungan ASC, waktu_booking ASC, no_antrian ASC
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
                    ?>
                    <?php if (
                        $posisi_antrian == 1 &&
                        $d_my["tgl_kunjungan"] == date("Y-m-d")
                    ): ?>
                        <div class="alert alert-success border-0 rounded-4 fw-bold shadow-sm mb-3">
                            <i class="bi bi-bell-fill me-2"></i>
                            Sekarang adalah antrean Anda. Silakan bersiap menuju ruang pemeriksaan.
                        </div>
                    <?php endif; ?>

                    <div class="antrean-card shadow-lg h-100 <?= $d_my[
                        "jenis_antrean"
                    ] == 1
                        ? "emergency"
                        : "" ?>">
                        <h6 class="fw-bold opacity-75 text-uppercase" style="letter-spacing:1px">
                            <?= $d_my["jenis_antrean"] == 1
                                ? "🚨 Antrean Darurat"
                                : "Tiket Antrean Anda" ?>
                        </h6>

                        <div class='antrean-number'><?= e(
                            $d_my["no_antrian"],
                        ) ?></div>

                        <span class="badge bg-white text-dark px-4 py-2 rounded-pill fw-bold shadow-sm">
                            <?= e($d_my["jenis_antrean"]) ?> - <?= e(
     strtoupper($d_my["status"]),
 ) ?>
                        </span>

                        <p class="mt-4 mb-2 small opacity-75">
                            <?= e(
                                date(
                                    "d M Y",
                                    strtotime($d_my["tgl_kunjungan"]),
                                ),
                            ) ?>,
                            Jam <?= e(substr($d_my["waktu_booking"], 0, 5)) ?>
                        </p>

                        <div class="badge bg-white text-primary px-4 py-2 rounded-pill fw-bold shadow-sm">
                            Posisi antrean sekarang: <?= e(
                                $posisi_antrian ?? "-",
                            ) ?>
                        </div>

                        <?php if (
                            ($d_my["jenis_antrean"] ?? "") ==
                            "Jadwal"
                        ): ?>
                            <form method="POST" class="mt-4" onsubmit="return confirm('Yakin ingin membatalkan booking ini?')">
                                <input type="hidden" name="id_rekam_medis" value="<?= e(
                                    $d_my["id_rekam_medis"],
                                ) ?>">

                                <button type="submit"
                                        name="batal_booking"
                                        class="btn btn-light text-danger fw-bold rounded-pill px-4">
                                    <i class="bi bi-x-circle me-1"></i> Batal Booking
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php
                else:
                     ?>
                    <div class="antrean-card shadow-lg h-100 opacity-50" style="filter: grayscale(1);">
                        <h6 class="fw-bold opacity-75 text-uppercase">Nomor Antrean</h6>
                        <div class='antrean-number'>--</div>
                        <p class='mb-0 small opacity-75'>Belum ada antrean aktif.</p>
                    </div>
                <?php
                endif;
                ?>
            </div>
            
            <div class="col-lg-7">
                <div class="row g-4 mb-4">
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
                            <i class="bi bi-clipboard2-pulse fs-1 text-light"></i>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="stat-card" style="border-left-color: #1cc88a;">
                            <div>
                                <div class="small fw-bold text-muted mb-1">DATA TERDAFTAR</div>
                                <div class="h5 fw-bold text-success mb-0">PROFIL AKTIF</div>
                            </div>
                            <i class="bi bi-shield-check fs-1 text-light"></i>
                        </div>
                    </div>
                </div>

                <div class="data-container py-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle-fill text-primary me-2"></i>Panduan Layanan</h6>
                    <ul class="list-unstyled small text-muted">
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i><strong>Antrean langsung</strong> hanya bisa jika dokter buka hari ini dan jam sekarang masuk jam praktik.</li>
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i><strong>Booking</strong> bisa dipilih dari jam buka sampai jam tutup dokter.</li>
                        <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i><strong>Emergency</strong> tetap diprioritaskan paling atas.</li>
                        <li><i class="bi bi-check2-circle text-success me-2"></i>Kalau dokter sedang tutup, silakan ambil booking di Jadwal Dokter.</li>
                    </ul>
                </div>
            </div>
        </div>

    <?php elseif ($active_page == "antrean"): ?>

        <h4 class="fw-bold mb-4">Ambil Antrean Langsung</h4>

        <?php
        $hari_ini_info = hariIndonesiaDariTanggal(date("Y-m-d"));
        $jam_info = date("H:i:s");
        $cekJadwalInfo = mysqli_query(
            $conn,
            "
                SELECT *
                FROM jadwalm
                WHERE tanggal = '$hari_ini_info'
                AND status = 'Buka'
                AND jam_mulai <= '$jam_info'
                AND jam_selesai > '$jam_info'
                LIMIT 1
            ",
        );
        $dokter_sedang_buka =
            $cekJadwalInfo && mysqli_num_rows($cekJadwalInfo) > 0;
        ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="data-container shadow-sm">
                    <?php if ($dokter_sedang_buka): ?>
                        <div class="alert alert-success border-0 rounded-4 small fw-bold">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            Dokter sedang buka. Anda bisa mengambil antrean langsung sekarang.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning border-0 rounded-4 small fw-bold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Dokter sedang tidak buka untuk antrean langsung. Silakan booking di Jadwal Dokter.
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">APA YANG ANDA RASAKAN SAAT INI?</label>
                            <textarea name="keluhan" class="form-control bg-light border-0 p-3 shadow-none" rows="7" placeholder="Contoh: Merasa sesak nafas dan nyeri di dada..." required style="border-radius: 15px;"></textarea>
                            <div class="form-text mt-2 small italic text-primary"><i class="bi bi-lightbulb me-1"></i>Antrean langsung akan memakai jam saat ini dan dicek dengan jadwal dokter.</div>
                        </div>

                        <?php if ($dokter_sedang_buka): ?>
                            <button type="submit" name="ambil_antrean" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow">
                                <i class="bi bi-plus-circle me-2"></i> Dapatkan Nomor Antrean
                            </button>
                        <?php else: ?>
                            <a href="dashboard_pasien.php?page=jadwal_dokter" class="btn btn-warning w-100 py-3 rounded-4 fw-bold shadow">
                                <i class="bi bi-calendar-week me-2"></i> Booking Jadwal Dokter
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="p-4 rounded-4 bg-white border border-danger border-opacity-25 shadow-sm">
                    <h6 class="fw-bold text-danger mb-3"><i class="bi bi-lightning-charge-fill me-2"></i>Daftar Gejala Darurat</h6>
                    <p class="small text-muted mb-3">Jika keluhan Anda mengandung kata berikut, sistem akan memberikan <strong>Prioritas Penanganan</strong>:</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Sesak</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Pingsan</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Darah</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Jantung</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">Kecelakaan</span>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($active_page == "jadwal_dokter"): ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">Jadwal Dokter</h4>
                <small class="text-muted">Pilih hari jadwal dokter, lalu pilih jam booking</small>
            </div>
        </div>

        <div class="row g-4">
            <?php
            $qJadwal = mysqli_query(
                $conn,
                "
                SELECT id_jadwal, id_staff, tanggal, jam_mulai, jam_selesai, status
                FROM jadwalm
                WHERE status = 'Buka'
                ORDER BY FIELD(tanggal, 'Senin','Selasa','Rabu','Kamis','Jumat'), jam_mulai ASC
            ",
            );

            $modal_booking = [];

            if (!$qJadwal) {
                echo "<div class='col-12'><div class='alert alert-danger rounded-4 border-0 shadow-sm'>Query error: " .
                    e(mysqli_error($conn)) .
                    "</div></div>";
            } else {
                if (mysqli_num_rows($qJadwal) == 0) {
                    echo "
                        <div class='col-12'>
                            <div class='data-container text-center py-5'>
                                <i class='bi bi-calendar-x text-muted' style='font-size:4rem;'></i>
                                <h5 class='fw-bold mt-3'>Belum Ada Jadwal Dokter Buka</h5>
                                <p class='text-muted mb-0'>Silakan cek lagi nanti.</p>
                            </div>
                        </div>
                    ";
                }

                while ($j = mysqli_fetch_assoc($qJadwal)):
                    $modal_booking[] = $j; ?>
                    <div class="col-md-4">
                        <div class="card jadwal-card h-100">
                            <div class="jadwal-date">
                                <div class="jadwal-day"><?= e(
                                    $j["tanggal"],
                                ) ?></div>
                                <div class="small opacity-75">Status: Buka</div>
                            </div>

                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <small class="text-muted fw-bold text-uppercase">Dokter</small>
                                    <div class="fw-bold">Dokter ASTARhealth</div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted fw-bold text-uppercase">Jam Praktik</small>
                                    <div class="jadwal-time"><?= e(
                                        substr($j["jam_mulai"], 0, 5),
                                    ) ?> - <?= e(
     substr($j["jam_selesai"], 0, 5),
 ) ?></div>
                                </div>

                                <button class="btn btn-primary w-100 rounded-4 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#mBooking<?= e(
                                    $j["id_jadwal"],
                                ) ?>">
                                    <i class="bi bi-ticket-perforated me-2"></i>Booking Antrean
                                </button>
                            </div>
                        </div>
                    </div>
            <?php
                endwhile;
            }
            ?>
        </div>

        <?php foreach ($modal_booking as $j): ?>
            <div class="modal fade" id="mBooking<?= e(
                $j["id_jadwal"],
            ) ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content border-0 shadow-lg" style="border-radius:24px" method="POST">
                        <div class="modal-header bg-primary text-white border-0 py-4">
                            <h5 class="fw-bold mb-0"><i class="bi bi-ticket-perforated me-2"></i>Booking Antrean</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body p-4">
                            <input type="hidden" name="id_jadwal" value="<?= e(
                                $j["id_jadwal"],
                            ) ?>">

                            <div class="mb-3">
                                <label class="small fw-bold text-muted">JADWAL</label>
                                <input type="text" class="form-control bg-light border-0 py-3" value="<?= e(
                                    $j["tanggal"],
                                ) ?>, <?= e(
    substr($j["jam_mulai"], 0, 5),
) ?> - <?= e(substr($j["jam_selesai"], 0, 5)) ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold text-muted">PILIH JAM BOOKING</label>
                                <input type="time"
                                       name="jam_booking"
                                       class="form-control bg-light border-0 py-3"
                                       min="<?= e(
                                           substr($j["jam_mulai"], 0, 5),
                                       ) ?>"
                                       max="<?= e(
                                           date(
                                               "H:i",
                                               strtotime($j["jam_selesai"]) -
                                                   60,
                                           ),
                                       ) ?>"
                                       required>
                                <div class="form-text small text-muted">
                                    Pilih jam sesuai jam buka dokter. Tanggal kunjungan otomatis mengikuti hari <strong><?= e(
                                        $j["tanggal"],
                                    ) ?></strong> terdekat.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold text-muted">KELUHAN</label>
                                <textarea name="keluhan_booking"
                                          class="form-control bg-light border-0 py-3"
                                          rows="4"
                                          placeholder="Contoh: Demam, pusing, batuk, sakit perut..."
                                          required></textarea>
                                <div class="form-text small text-muted">
                                    Isi keluhan dengan teks biasa. Diagnosa akan ditentukan oleh dokter saat pemeriksaan.
                                </div>
                            </div>

                            <div class="alert alert-info border-0 rounded-4 small mb-0">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                Booking akan masuk ke Rekam Medis sebagai antrean jadwal dengan status Menunggu.
                            </div>
                        </div>

                        <div class="modal-footer border-0 px-4 pb-4 pt-0">
                            <button type="button" class="btn btn-light py-3 fw-bold rounded-4" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="ambil_antrean_jadwal" class="btn btn-primary py-3 fw-bold rounded-4 flex-fill">Booking Antrean</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="data-container mt-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle-fill text-primary me-2"></i>Informasi Sistem Antrean</h6>
            <p class="text-muted small mb-0">
                Emergency tetap paling utama. Booking akan diprioritaskan saat jam booking sudah tiba. Antrean langsung hanya bisa diambil saat dokter sedang buka.
            </p>
        </div>

    <?php elseif ($active_page == "obat"): ?>

        <h4 class="fw-bold mb-4">Informasi Stok Farmasi</h4>
        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Nama Obat</th><th>Satuan</th><th class="text-center">Ketersediaan</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $qo = mysqli_query(
                            $conn,
                            "SELECT * FROM obatm ORDER BY nama_obat ASC",
                        );
                        if ($qo && mysqli_num_rows($qo) == 0) {
                            echo "<tr><td colspan='3' class='text-center text-muted py-4'>Belum ada data obat.</td></tr>";
                        }
                        if ($qo) {
                            while ($ro = mysqli_fetch_assoc($qo)): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= e(
                                    $ro["nama_obat"],
                                ) ?></td>
                                <td><span class="badge bg-light text-dark border px-3"><?= e(
                                    $ro["satuan"] ?? "Umum",
                                ) ?></span></td>
                                <td class="text-center">
                                    <span class="badge <?= $ro[
                                        "stok_sekarang"
                                    ] > 0
                                        ? "bg-success"
                                        : "bg-danger" ?> bg-opacity-10 text-<?= $ro[
     "stok_sekarang"
 ] > 0
     ? "success"
     : "danger" ?> px-4 py-2 rounded-pill fw-bold">
                                        <?= $ro["stok_sekarang"] > 0
                                            ? "Tersedia"
                                            : "Habis" ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($active_page == "riwayat"): ?>

        <h4 class="fw-bold mb-4">Arsip Riwayat Medis</h4>
        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Tanggal / Jam</th><th>Jenis</th><th>Keluhan</th><th>Diagnosa</th><th class="text-center">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $qr = mysqli_query(
                            $conn,
                            "
                            SELECT rm.*, d.nama_penyakit 
                            FROM rekam_medis rm 
                            LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa 
                            WHERE rm.id_pasien = '$id_pasien' 
                            ORDER BY rm.tgl_kunjungan DESC, rm.waktu_booking DESC
                        ",
                        );

                        if ($qr && mysqli_num_rows($qr) == 0) {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Belum ada riwayat berobat.</td></tr>";
                        }

                        if ($qr) {
                            while ($row = mysqli_fetch_assoc($qr)): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold small"><?= e(
                                        date(
                                            "d M Y",
                                            strtotime($row["tgl_kunjungan"]),
                                        ),
                                    ) ?></div>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= e(
                                        substr($row["waktu_booking"], 0, 5),
                                    ) ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e(
                                    $row["jenis_antrean"] ?? "Langsung",
                                ) ?></span></td>
                                <td><div style="max-width: 250px;" class="small text-truncate" title="<?= e(
                                    $row["keluhan"],
                                ) ?>"><?= e($row["keluhan"]) ?></div></td>
                                <td><span class="badge <?= $row["status"] ==
                                "Selesai"
                                    ? "bg-primary bg-opacity-10 text-primary"
                                    : "bg-warning bg-opacity-10 text-warning" ?> px-3"><?= e(
     $row["nama_penyakit"] ?? "N/A",
 ) ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#mDetail<?= e(
                                        $row["id_rekam_medis"],
                                    ) ?>">
                                        <i class="bi bi-eye me-1"></i>Detail
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="mDetail<?= e(
                                $row["id_rekam_medis"],
                            ) ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                                        <div class="modal-header bg-light border-0 p-4">
                                            <h6 class="fw-bold mb-0">Catatan Medis: <?= e(
                                                date(
                                                    "d/m/Y",
                                                    strtotime(
                                                        $row["tgl_kunjungan"],
                                                    ),
                                                ),
                                            ) ?></h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4 text-start">
                                            <label class="small fw-bold text-muted text-uppercase mb-1">Jenis Antrean:</label>
                                            <h6 class="fw-bold mb-3"><?= e(
                                                $row["jenis_antrean"] ??
                                                    "Langsung",
                                            ) ?></h6>
                                            <label class="small fw-bold text-muted text-uppercase mb-1">Diagnosa:</label>
                                            <h5 class="text-primary fw-bold"><?= e(
                                                $row["nama_penyakit"] ??
                                                    "Menunggu Pemeriksaan",
                                            ) ?></h5>
                                            <hr>
                                            <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Hasil Pemeriksaan & Resep:</label>
                                            <div class="p-3 bg-light rounded-3 text-muted" style="font-size: 13.5px;">
                                                <?= nl2br(
                                                    e(
                                                        $row[
                                                            "hasil_pemeriksaan"
                                                        ] ??
                                                            "Dokter belum mengisi catatan medis untuk antrean ini.",
                                                    ),
                                                ) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>
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
                    <a href="index.php" class="btn btn-danger w-100 py-2 fw-bold rounded-3 shadow-sm text-white text-decoration-none">Ya, Keluar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
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
    alert('Sekarang adalah antrean Anda. Silakan bersiap menuju ruang pemeriksaan.');
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

</body>
</html>
