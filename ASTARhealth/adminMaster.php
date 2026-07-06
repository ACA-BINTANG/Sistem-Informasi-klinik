<?php
session_start();
require_once "koneksi.php";

/** @var mysqli $conn */

// ==========================================
// PROTEKSI ROLE ADMIN
// ==========================================
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php?pesan=Akses Ditolak!");
    exit();
}

$admin_name = $_SESSION["nama_lengkap"];
$active_page = isset($_GET["page"]) ? $_GET["page"] : "dashboard";

function generateID($prefix)
{
    return $prefix . substr(str_shuffle("0123456789"), 0, 3);
}

// ==========================================
// HELPER: SINKRONISASI KE TABEL userm
// Dipanggil setiap kali data di staffm/pasienm diupdate,
// supaya username/email/nama di userm ikut berubah.
// ==========================================
function syncToUser($conn, $id_user, $nama_lengkap, $identitas = null)
{
    if ($identitas !== null) {
        $new_email = $identitas . "@polytechnic.astar.ac.id";
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE userm SET username=?, email=?, nama_lengkap=? WHERE id_user=?",
        );
        mysqli_stmt_bind_param(
            $stmt,
            "ssss",
            $identitas,
            $new_email,
            $nama_lengkap,
            $id_user,
        );
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE userm SET nama_lengkap=? WHERE id_user=?",
        );
        mysqli_stmt_bind_param($stmt, "ss", $nama_lengkap, $id_user);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Ambil id_user terkait dari staffm / pasienm
function getUserIdFrom($conn, $table, $keyCol, $keyVal)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_user FROM $table WHERE $keyCol = ?",
    );
    mysqli_stmt_bind_param($stmt, "s", $keyVal);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ? $row["id_user"] : null;
}

// ==========================================
// LOGIKA CRUD
// ==========================================

// 1. TAMBAH USER MANUAL
if (isset($_POST["add_user"])) {
    $id = generateID("USR");
    $un = $_POST["username"];
    $em = $_POST["email"];
    $ps = $_POST["password"]; // TODO: idealnya di-hash pakai password_hash()
    $rl = $_POST["role"];
    $nm = $_POST["nama_lengkap"];

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO userm (id_user, username, email, password, role, nama_lengkap) VALUES (?, ?, ?, ?, ?, ?)",
    );
    mysqli_stmt_bind_param($stmt, "ssssss", $id, $un, $em, $ps, $rl, $nm);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: adminMaster.php?page=user&msg=User Berhasil Ditambah");
    exit();
}

// 2. TAMBAH STAFF + AUTO AKUN SSO
if (isset($_POST["add_staff"])) {
    $nip = $_POST["no_identitas"];
    $nama = $_POST["nama_lengkap"];
    $role = $_POST["role_akun"];
    $jbt = $_POST["jabatan"];
    $ins = $_POST["instansi"];
    $npa = $_POST["npa_idi"];
    $hp = $_POST["no_hp"];

    $id_u = generateID("USR");
    $id_s = generateID("STF");
    $username_sso = $nip . "@polytechnic.astar.ac.id";
    $nama_depan = strtolower(explode(" ", trim($nama))[0]);
    $pass_staff = $nama_depan . "123"; // TODO: idealnya di-hash

    $stmt1 = mysqli_prepare(
        $conn,
        "INSERT INTO userm (id_user, username, email, password, role, nama_lengkap) VALUES (?, ?, ?, ?, ?, ?)",
    );
    mysqli_stmt_bind_param(
        $stmt1,
        "ssssss",
        $id_u,
        $username_sso,
        $username_sso,
        $pass_staff,
        $role,
        $nama,
    );
    mysqli_stmt_execute($stmt1);
    mysqli_stmt_close($stmt1);

    $stmt2 = mysqli_prepare(
        $conn,
        "INSERT INTO staffm (id_staff, id_user, nama_lengkap, no_identitas, jabatan, instansi, npa_idi, no_hp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
    );
    mysqli_stmt_bind_param(
        $stmt2,
        "ssssssss",
        $id_s,
        $id_u,
        $nama,
        $nip,
        $jbt,
        $ins,
        $npa,
        $hp,
    );
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    header(
        "Location: adminMaster.php?page=staff&msg=Staff Berhasil Didaftarkan",
    );
    exit();
}

// 3. TAMBAH PASIEN + AUTO AKUN SSO
if (isset($_POST["add_pasien"])) {
    $id_u = generateID("USR");
    $id_p = generateID("PSN");
    $nim = trim($_POST["no_identitas"]);
    $nama = trim($_POST["nama_pasien"]);
    $username_sso = $nim . "@polytechnic.astar.ac.id";
    $nama_depan = strtolower(explode(" ", $nama)[0]);
    $password_sso = $nama_depan . "123"; // TODO: idealnya di-hash

    $jk = $_POST["jenis_kelamin"];
    $kat = $_POST["kategori_pasien"];
    $prodi = $_POST["unit_prodi"];
    $alm = $_POST["alamat"];
    $hp = $_POST["no_hp"];

    $stmt1 = mysqli_prepare(
        $conn,
        "INSERT INTO userm (id_user, username, email, password, role, nama_lengkap) VALUES (?, ?, ?, ?, 'Pasien', ?)",
    );
    mysqli_stmt_bind_param(
        $stmt1,
        "sssss",
        $id_u,
        $username_sso,
        $username_sso,
        $password_sso,
        $nama,
    );
    $ok1 = mysqli_stmt_execute($stmt1);
    mysqli_stmt_close($stmt1);

    $stmt2 = mysqli_prepare(
        $conn,
        "INSERT INTO pasienm (id_pasien, id_user, no_identitas, nama_pasien, jenis_kelamin, kategori_pasien, unit_prodi, alamat, no_hp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
    );
    mysqli_stmt_bind_param(
        $stmt2,
        "sssssssss",
        $id_p,
        $id_u,
        $nim,
        $nama,
        $jk,
        $kat,
        $prodi,
        $alm,
        $hp,
    );
    $ok2 = mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    if ($ok1 && $ok2) {
        header(
            "Location: adminMaster.php?page=pasien&msg=Pasien & Akun SSO Berhasil Dibuat!",
        );
        exit();
    }
}

// 4. UPDATE USER (ikut sync ke staffm & pasienm)
if (isset($_POST["update_user"])) {
    $id = $_POST["id_user"];
    $un = $_POST["username"];
    $em = $_POST["email"];
    $ps = $_POST["password"];
    $rl = $_POST["role"];
    $nm = $_POST["nama_lengkap"];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE userm SET username=?, email=?, password=?, role=?, nama_lengkap=? WHERE id_user=?",
    );
    mysqli_stmt_bind_param($stmt, "ssssss", $un, $em, $ps, $rl, $nm, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Sinkronisasi ke staffm (kalau id_user ini punya row di staffm)
    $stmtS = mysqli_prepare(
        $conn,
        "UPDATE staffm SET no_identitas=?, nama_lengkap=? WHERE id_user=?",
    );
    mysqli_stmt_bind_param($stmtS, "sss", $un, $nm, $id);
    mysqli_stmt_execute($stmtS);
    mysqli_stmt_close($stmtS);

    // Sinkronisasi ke pasienm (kalau id_user ini punya row di pasienm)
    $stmtP = mysqli_prepare(
        $conn,
        "UPDATE pasienm SET no_identitas=?, nama_pasien=? WHERE id_user=?",
    );
    mysqli_stmt_bind_param($stmtP, "sss", $un, $nm, $id);
    mysqli_stmt_execute($stmtP);
    mysqli_stmt_close($stmtP);

    header(
        "Location: adminMaster.php?page=user&msg=Akun & Data Terelasi Berhasil Diupdate",
    );
    exit();
}

// 5. UPDATE STAFF (ikut sync ke userm lewat syncToUser)
if (isset($_POST["update_staff"])) {
    $id_s = $_POST["id_staff"];
    $nm_l = $_POST["nama_lengkap"];
    $no_i = $_POST["no_identitas"];
    $jbt = $_POST["jabatan"];
    $ins = $_POST["instansi"];
    $npa = $_POST["npa_idi"];
    $hp = $_POST["no_hp"];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE staffm SET nama_lengkap=?, no_identitas=?, jabatan=?, instansi=?, npa_idi=?, no_hp=? WHERE id_staff=?",
    );
    mysqli_stmt_bind_param(
        $stmt,
        "sssssss",
        $nm_l,
        $no_i,
        $jbt,
        $ins,
        $npa,
        $hp,
        $id_s,
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $id_u = getUserIdFrom($conn, "staffm", "id_staff", $id_s);
    if ($id_u) {
        syncToUser($conn, $id_u, $nm_l, $no_i);
    }

    header(
        "Location: adminMaster.php?page=staff&msg=Data Staff & Akun SSO Berhasil Diupdate",
    );
    exit();
}

// 6. UPDATE PASIEN (ikut sync ke userm lewat syncToUser)
if (isset($_POST["update_pasien"])) {
    $id_p = $_POST["id_pasien"];
    $nm = $_POST["nama_pasien"];
    $nip = $_POST["no_identitas"];
    $jk = $_POST["jenis_kelamin"];
    $kat = $_POST["kategori_pasien"];
    $prodi = $_POST["unit_prodi"];
    $alm = $_POST["alamat"];
    $hp = $_POST["no_hp"];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE pasienm SET nama_pasien=?, no_identitas=?, jenis_kelamin=?, kategori_pasien=?, unit_prodi=?, alamat=?, no_hp=? WHERE id_pasien=?",
    );
    mysqli_stmt_bind_param(
        $stmt,
        "ssssssss",
        $nm,
        $nip,
        $jk,
        $kat,
        $prodi,
        $alm,
        $hp,
        $id_p,
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $id_u_p = getUserIdFrom($conn, "pasienm", "id_pasien", $id_p);
    if ($id_u_p) {
        syncToUser($conn, $id_u_p, $nm, $nip);
    }

    header(
        "Location: adminMaster.php?page=pasien&msg=Data Pasien & Akun SSO Berhasil Diupdate",
    );
    exit();
}

// 8. TAMBAH SUPPLIER
if (isset($_POST["add_supplier"])) {
    $id = generateID("SUP");
    $nama = trim($_POST["nama_supplier"]);
    $alamat = trim($_POST["alamat"]);
    $kontak = isset($_POST["kontak"]) ? trim($_POST["kontak"]) : null;

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO supplierm (id_supplier, nama_supplier, kontak, alamat) VALUES (?, ?, ?, ?)",
    );
    mysqli_stmt_bind_param($stmt, "ssss", $id, $nama, $kontak, $alamat);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header(
        "Location: adminMaster.php?page=supplier&msg=Supplier Berhasil Ditambah",
    );
    exit();
}

// 9. UPDATE SUPPLIER
if (isset($_POST["update_supplier"])) {
    $id = $_POST["id_supplier"];
    $nama = trim($_POST["nama_supplier"]);
    $alamat = trim($_POST["alamat"]);
    $kontak = isset($_POST["kontak"]) ? trim($_POST["kontak"]) : null;

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE supplierm SET nama_supplier=?, kontak=?, alamat=? WHERE id_supplier=?",
    );
    mysqli_stmt_bind_param($stmt, "ssss", $nama, $kontak, $alamat, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header(
        "Location: adminMaster.php?page=supplier&msg=Data Supplier Berhasil Diupdate",
    );
    exit();
}

// 7. HAPUS UNIVERSAL
// Dibatasi hanya untuk tabel & kolom yang memang boleh dihapus dari sini,
// supaya nama tabel/kolom tidak bisa disuntik lewat parameter GET.
if (isset($_GET["del"])) {
    $allowed = [
        "userm" => "id_user",
        "staffm" => "id_staff",
        "pasienm" => "id_pasien",
        "supplierm" => "id_supplier",
    ];
    $tabel = $_GET["t"];
    $kolom = $_GET["k"];
    $val = $_GET["del"];
    $pg = $_GET["page"];

    if (isset($allowed[$tabel]) && $allowed[$tabel] === $kolom) {
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
        $stmt = mysqli_prepare($conn, "DELETE FROM $tabel WHERE $kolom = ?");
        mysqli_stmt_bind_param($stmt, "s", $val);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    }

    header("Location: adminMaster.php?page=$pg&msg=Data Berhasil Dihapus");
    exit();
}

$u_list = mysqli_query($conn, "SELECT * FROM userm");
$s_list = mysqli_query($conn, "SELECT * FROM staffm");
$p_list = mysqli_query($conn, "SELECT * FROM pasienm");
$sup_list = mysqli_query($conn, "SELECT * FROM supplierm");
$o_list = mysqli_query($conn, "SELECT * FROM obatm ORDER BY nama_obat ASC");

$chart_roles = mysqli_query(
    $conn,
    "SELECT role, COUNT(*) as jumlah FROM userm GROUP BY role",
);
$chart_labels = [];
$chart_data = [];
while ($row = mysqli_fetch_assoc($chart_roles)) {
    $chart_labels[] = $row["role"];
    $chart_data[] = $row["jumlah"];
}

// Palet warna untuk chart & chip role (dipakai server-side & di-mirror di JS)
$chart_palette = [
    "#0057B8",
    "#13a06a",
    "#f4a11d",
    "#8b5cf6",
    "#ef4444",
    "#0891b2",
];
// 1. Data untuk Line Chart (Jumlah Kunjungan per Bulan)
// Asumsi ada tabel 'pemeriksaan' atau 'rekam_medis' dengan kolom 'tgl_kunjungan'
// Jika nama tabel berbeda, silakan sesuaikan
$kunjungan_data = array_fill(1, 12, 0); // Siapkan array 12 bulan diisi 0
$query_kunjungan = mysqli_query(
    $conn,
    "SELECT MONTH(tgl_kunjungan) as bulan, COUNT(*) as jumlah 
                                        FROM rekam_medis 
                                        WHERE YEAR(tgl_kunjungan) = YEAR(CURDATE()) 
                                        GROUP BY MONTH(tgl_kunjungan)",
);
while ($row = mysqli_fetch_assoc($query_kunjungan)) {
    $kunjungan_data[(int) $row["bulan"]] = (int) $row["jumlah"];
}
$line_chart_values = array_values($kunjungan_data);

// 2. Data untuk Donut Chart (Kategori Pasien)
$donut_labels = [];
$donut_values = [];
$query_donut = mysqli_query(
    $conn,
    "SELECT kategori_pasien, COUNT(*) as jumlah FROM pasienm GROUP BY kategori_pasien",
);
while ($row = mysqli_fetch_assoc($query_donut)) {
    $donut_labels[] = $row["kategori_pasien"];
    $donut_values[] = (int) $row["jumlah"];
}
?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - ASTARhealth</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --astar-blue: #0057B8;
            --astar-blue-light: #2E86F0;
            --astar-blue-deep: #003D82;
            --astar-soft-blue: #eef4ff;
            --astar-mist: #dbe9ff;
            --sidebar-bg: #ffffff;
            --r-sm: 12px;
            --r-md: 18px;
            --r-lg: 26px;
            --shadow-soft: 0 16px 36px rgba(15, 61, 130, 0.10);
            --shadow-card: 0 10px 24px rgba(15, 61, 130, 0.06);
        }
        * { scrollbar-width: thin; scrollbar-color: var(--astar-mist) transparent; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: radial-gradient(1200px 600px at 100% -10%, #eaf2ff 0%, #f4f7fa 45%) fixed; color: #334155; }

        .top-header { height: 74px; background: linear-gradient(115deg, var(--astar-blue-deep) 0%, var(--astar-blue) 45%, var(--astar-blue-light) 100%); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; color: white; position: fixed; top: 0; width: 100%; z-index: 1001; box-shadow: var(--shadow-soft); }
        #digitalClock { font-weight: 600; font-size: 14px; background: rgba(255,255,255,0.16); backdrop-filter: blur(6px); padding: 6px 18px; border-radius: 999px; }

        .sidebar { width: 260px; background: #FFFFFF; height: 100vh; position: fixed; top: 70px; left: 0; border-right: none; box-shadow: 6px 0 24px rgba(15, 61, 130, 0.05); z-index: 1000; padding: 26px 16px; transition: all 0.3s ease; border-radius: 0 28px 0 0; }
        .main-content { margin-left: 260px; padding: 108px 32px 40px; transition: all 0.3s ease; }
        body.sidebar-toggled .sidebar { left: -260px; }
        body.sidebar-toggled .main-content { margin-left: 0; }
        .nav-group-title { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; padding: 15px 15px 8px; letter-spacing: 1px; }
        .nav-link { padding: 12px 20px; color: #64748b; font-weight: 500; display: flex; align-items: center; transition: 0.2s; text-decoration: none; font-size: 14px; border-radius: var(--r-sm); margin-bottom: 2px; }
        .nav-link i { font-size: 1.2rem; width: 35px; }
        .nav-link:hover { background: var(--astar-soft-blue); color: var(--astar-blue); }
        .nav-link.active { background: linear-gradient(120deg, var(--astar-blue) 0%, var(--astar-blue-light) 100%); color: #fff; box-shadow: 0 10px 22px rgba(0,87,184,0.28); }
        .nav-link-logout { color: rgba(17, 112, 221, 0.77); }
        .nav-link-logout:hover { background: #fdecec; color: #dc3545; }

        .data-container { background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); border-radius: var(--r-lg); padding: 30px; box-shadow: var(--shadow-card); border: 1px solid rgba(15,61,130,0.04); animation: fadeIn 0.35s ease; }

        .stat-card { background: linear-gradient(135deg, #ffffff 0%, var(--astar-soft-blue) 160%); border-radius: var(--r-lg); padding: 26px; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(15,61,130,0.05); box-shadow: var(--shadow-card); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-soft); }
        .icon-badge { width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #fff; background: linear-gradient(135deg, var(--astar-blue), var(--astar-blue-light)); box-shadow: 0 10px 22px rgba(0,87,184,0.28); flex-shrink: 0; }
        .icon-badge.warning { background: linear-gradient(135deg, #f6c23e, #f4a11d); box-shadow: 0 10px 22px rgba(246,162,29,0.3); }
        .icon-badge.success { background: linear-gradient(135deg, #23d3a0, #13a06a); box-shadow: 0 10px 22px rgba(19,160,106,0.3); }

        #toggleSidebar { cursor: pointer; font-size: 1.5rem; color: white; margin-right: 15px; display: flex; align-items: center; }

        .data-container .table-responsive { border-radius: var(--r-md); overflow: hidden; border: 1px solid rgba(15,61,130,0.06); }
        .table thead th { background: var(--astar-soft-blue); color: var(--astar-blue-deep); font-weight: 700; text-transform: uppercase; font-size: 11px; padding: 15px; border: none; }
        .table td { border-color: rgba(15,61,130,0.06); vertical-align: middle; }
        .table-hover tbody tr { transition: 0.15s; }
        .table-hover tbody tr:hover { background: var(--astar-soft-blue); }
        .table .btn-light { width: 36px; height: 36px; border-radius: var(--r-sm); display: inline-flex; align-items: center; justify-content: center; background: #f4f7fb; border: 1px solid rgba(15,61,130,0.05); }
        .table .btn-light:hover { background: var(--astar-mist); }

        .btn-primary { background: linear-gradient(120deg, var(--astar-blue), var(--astar-blue-light)); border: none; box-shadow: 0 10px 22px rgba(0,87,184,0.25); }
        .btn-primary:hover { filter: brightness(1.06); transform: translateY(-1px); box-shadow: 0 12px 26px rgba(0,87,184,0.3); }

        .form-control, .form-select { border-radius: var(--r-sm) !important; background: #f6f8fc !important; padding: 10px 14px; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 4px rgba(0,87,184,0.12) !important; background: #fff !important; border-color: var(--astar-blue-light) !important; }
        .input-group-text { border-radius: var(--r-sm) 0 0 var(--r-sm) !important; background: #f6f8fc !important; }
        .input-group .form-control { border-radius: 0 var(--r-sm) var(--r-sm) 0 !important; }

        .modal-content { border-radius: var(--r-lg) !important; overflow: hidden; box-shadow: var(--shadow-soft); border: none; }
        .modal-header.bg-primary { background: linear-gradient(120deg, var(--astar-blue-deep), var(--astar-blue) 60%, var(--astar-blue-light)) !important; }
        .modal-header.bg-warning { background: linear-gradient(120deg, #f6c23e, #f4a11d) !important; }

        .alert-success { background: linear-gradient(120deg, #e9fbf3, #f2fffa) !important; border: 1px solid rgba(28,200,138,0.18) !important; color: #0f9d68 !important; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* ===== Chart chips ===== */
        .chart-chip { display: inline-flex; align-items: center; gap: 7px; background: #f4f7fb; border: 1px solid rgba(15,61,130,0.06); padding: 6px 14px; border-radius: 999px; font-size: 12.5px; font-weight: 600; color: #475569; transition: 0.2s; }
        .chart-chip:hover { background: var(--astar-soft-blue); transform: translateY(-1px); }
        .chart-chip .chip-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--chip-color); box-shadow: 0 0 0 3px color-mix(in srgb, var(--chip-color) 18%, transparent); }
        .chart-chip b { color: #1e293b; font-weight: 800; margin-left: 2px; }
        .chart-canvas-wrap { position: relative; height: 300px; }
    
        /* Gaya Card khas SB Admin 2 */
.card.border-left-primary { border-left: 0.25rem solid #4e73df !important; }
.card.border-left-success { border-left: 0.25rem solid #1cc88a !important; }
.card.border-left-info { border-left: 0.25rem solid #36b9cc !important; }
.card.border-left-warning { border-left: 0.25rem solid #f6c23e !important; }

.text-xs { font-size: .7rem; letter-spacing: 0.1rem; }
.text-gray-300 { color: #dddfeb !important; }
.text-gray-800 { color: #5a5c69 !important; }

/* Wrapper Chart agar tingginya pas */
.chart-area { position: relative; height: 320px; width: 100%; }
.chart-pie { position: relative; height: 260px; width: 100%; }

    </style>
    </head>
    <body>

    <header class="top-header">
        <div class="d-flex align-items-center">
            <div id="toggleSidebar"><i class="bi bi-list"></i></div>
            <img src="assets/img/logoA.png" style="max-height: 70px; filter: brightness(0) invert(1);">
            <div id="digitalClock" class="d-none d-md-block ms-3"></div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block text-white">
                <div class="fw-bold" style="font-size: 14px;"><?= $admin_name ?></div>
                <div style="font-size: 11px; opacity: 0.8;">Admin System</div>
            </div>
            <i class="bi bi-person-circle fs-2 text-white" style="cursor: pointer;" data-bs-toggle="modal"></i>
        </div>
    </header>

    <div class="sidebar">
        <div class="nav-group-title">Menu Utama</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link <?= $active_page == "dashboard"
                ? "active"
                : "" ?>" href="?page=dashboard"><i class="bi bi-speedometer2"></i> Overview</a>
            <div class="nav-group-title">Manajemen Akun</div>
            <a class="nav-link <?= $active_page == "user"
                ? "active"
                : "" ?>" href="?page=user"><i class="bi bi-person-lock"></i> User Credentials</a>
            <a class="nav-link <?= $active_page == "staff"
                ? "active"
                : "" ?>" href="?page=staff"><i class="bi bi-shield-check"></i> Tim Pengelola</a>
            <a class="nav-link <?= $active_page == "pasien"
                ? "active"
                : "" ?>" href="?page=pasien"><i class="bi bi-people"></i> Database Pasien</a>
            <a class="nav-link <?= $active_page == "supplier"
                ? "active"
                : "" ?>" href="?page=supplier"><i class="bi bi-box-seam"></i> Data Supplier</a>
            <a class="nav-link <?= $active_page == "obat"
                ? "active"
                : "" ?>" href="?page=obat"><i class="bi bi-capsule-pill"></i> Monitoring Obat
            </a>
            <div class="nav-group-title">Akun</div>
            <a class="nav-link nav-link-logout" href="#" data-bs-toggle="modal" data-bs-target="#modalLogout"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>

<main class="main-content">
    <?php if (
        isset($_GET["msg"])
    ): ?><div class="alert alert-success border-0 shadow-sm mb-4 rounded-4 fw-bold text-center"><i class="bi bi-check-circle-fill me-2"></i> <?= $_GET[
    "msg"
] ?></div><?php endif; ?>

<?php if ($active_page == "dashboard"): ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Dashboard Overview</h1>
    </div>

    <!-- Info Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card border-left-primary shadow h-100 py-2 border-0">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">TOTAL PASIEN</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= mysqli_num_rows(
                                $p_list,
                            ) ?> Orang</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-people fs-2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card border-left-success shadow h-100 py-2 border-0">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">TIM STAFF</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= mysqli_num_rows(
                                $s_list,
                            ) ?> Personel</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-shield-check fs-2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card border-left-info shadow h-100 py-2 border-0">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">STOK OBAT (UNIT)</div>
                            <?php $obat = mysqli_fetch_assoc(
                                mysqli_query(
                                    $conn,
                                    "SELECT COUNT(*) as total FROM obatm",
                                ),
                            ); ?>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $obat[
                                "total"
                            ] ?> Item</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-capsule fs-2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row Charts -->
    <div class="row">
        <!-- Area Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4 border-0">
                <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Statistik Kunjungan Sakit (<?= date(
                        "Y",
                    ) ?>)</h6>
                    <small class="text-muted">Jumlah kunjungan per bulan</small>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="sickChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4 border-0">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Kategori Pasien Terdaftar</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="height: 260px;">
                        <canvas id="categoryDonutChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php foreach ($donut_labels as $index => $label):
                            $colors = [
                                "#4e73df",
                                "#1cc88a",
                                "#36b9cc",
                                "#f6c23e",
                            ]; ?>
                            <span class="mx-1">
                                <i class="bi bi-circle-fill" style="color: <?= $colors[
                                    $index % 4
                                ] ?>"></i> <?= $label ?>
                            </span>
                        <?php
                        endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($active_page == "user"): ?>
        <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold">User Credentials</h3><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mAddUser">+ Tambah</button></div>
        
        <!-- Search & Filter User -->
        <div class="data-container mb-4 py-3">
            <div class="row g-3">
                <div class="col-md-8"><input type="text" id="searchUser" class="form-control" placeholder="Cari username atau nama..."></div>
                <div class="col-md-4">
                    <select id="filterRole" class="form-select">
                        <option value="">-- Semua Role --</option>
                        <option value="Admin">Tamu</option>
                        <option value="Dokter">Dokter</option>
                        <option value="Pasien">Pasien</option>
                        <option value="K3">K3</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="data-container"><div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>No</th><th>Username</th><th>Nama</th><th>Role</th><th>Aksi</th></tr></thead>
            <tbody id="tableUser">
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($u_list)): ?>
                <tr class="user-row" data-role="<?= $row["role"] ?>">
                    <td class="text-muted small"><?= $no++ ?></td>
                    <td class="fw-bold text-primary"><?= $row[
                        "username"
                    ] ?></td>
                    <td class="nama-user"><?= $row["nama_lengkap"] ?></td>
                    <td><span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill small"><?= $row[
                        "role"
                    ] ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-light text-warning me-1" data-bs-toggle="modal" data-bs-target="#mEditU<?= $row[
                            "id_user"
                        ] ?>"><i class="bi bi-pencil-square"></i></button>
                        <a href="?del=<?= $row[
                            "id_user"
                        ] ?>&t=userm&k=id_user&page=user" class="btn btn-sm btn-light text-danger" onclick="return confirm('Hapus user?')"><i class="bi bi-trash3"></i></a>
                    </td>
                </tr>
            <?php endwhile;
            ?></tbody></table></div></div>

    <?php elseif ($active_page == "staff"): ?>
        <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold">Tim Pengelola</h3><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mAddStaff">+ Staff Baru</button></div>
        
        <!-- Search & Filter Staff -->
        <div class="data-container mb-4 py-3">
            <div class="row g-3">
                <div class="col-md-8"><input type="text" id="searchStaff" class="form-control" placeholder="Cari NIP atau nama staff..."></div>
                <div class="col-md-4">
                    <select id="filterInstansi" class="form-select">
                        <option value="">-- Semua Instansi --</option>
                        <option value="Kampus">Kampus</option>
                        <option value="Siloam">Siloam</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="data-container"><div class="table-responsive"><table class="table table-hover align-middle">
            <thead  ><tr><th>No</th><th>NIP</th><th>Nama</th><th>Jabatan</th><th>Instansi</th><th>No HP</th><th>Aksi</th></tr></thead>
            <tbody id="tableStaff">
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($s_list)): ?>
                <tr class="staff-row" data-instansi="<?= $row["instansi"] ?>">
                    <td class="text-muted small"><?= $no++ ?></td>
                    <td class="fw-bold"><?= $row["no_identitas"] ?></td>
                    <td class="nama-staff"><?= $row["nama_lengkap"] ?></td>
                    <td><small class="fw-bold"><?= $row[
                        "jabatan"
                    ] ?></small></td>
                    <td><span class="badge bg-light text-dark border"><?= $row[
                        "instansi"
                    ] ?></span></td>
                    <td><small class="text-success fw-bold">+62 <?= $row[
                        "no_hp"
                    ] ?? "-" ?></small></td>
                    <td>
                        <button class="btn btn-sm btn-light text-warning me-1" data-bs-toggle="modal" data-bs-target="#mEditS<?= $row[
                            "id_staff"
                        ] ?>"><i class="bi bi-pencil-square"></i></button>
                        <a href="?del=<?= $row[
                            "id_staff"
                        ] ?>&t=staffm&k=id_staff&page=staff" class="btn btn-sm btn-light text-danger" onclick="return confirm('Hapus staff?')"><i class="bi bi-trash3"></i></a>
                    </td>
                </tr>
            <?php endwhile;
            ?></tbody></table></div></div>

    <?php elseif ($active_page == "pasien"): ?>
        <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold">Database Pasien</h3><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mAddPasien">+ Pasien Baru</button></div>
        
        <!-- Search & Filter Pasien -->
        <div class="data-container mb-4 py-3">
            <div class="row g-3">
                <div class="col-md-7"><input type="text" id="searchPasien" class="form-control" placeholder="Cari NIM atau nama pasien..."></div>
                <div class="col-md-5">
                    <select id="filterProdi" class="form-select">
                        <option value="">-- Semua Prodi/Unit --</option>
                        <optgroup label="Program Studi">
                            <option value="MI">MI</option><option value="MK">MK</option><option value="MO">MO</option>
                            <option value="P4">P4</option><option value="TPM">TPM</option><option value="TKBG">TKBG</option>
                            <option value="TRL">TRL</option><option value="TRPAB">TRPAB</option><option value="TRPL">TRPL</option>
                        </optgroup>
                        <optgroup label="Unit Kerja">
                            <option value="BAA">BAA</option><option value="BAK">BAK</option><option value="IT">IT</option>
                            <option value="K3">K3</option><option value="SECURITY">SECURITY</option>
                        </optgroup>
                    </select>
                </div>
            </div>
        </div>

        <div class="data-container"><div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>No</th><th>Identitas</th><th>Nama</th><th>Kategori</th><th>Prodi/Unit</th><th>No HP</th><th>Aksi</th></tr></thead>
            <tbody id="tablePasien">
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($p_list)): ?>
                <tr class="pasien-row" data-prodi="<?= $row["unit_prodi"] ?>">
                    <td class="text-muted small"><?= $no++ ?></td>
                    <td class="fw-bold text-primary"><?= $row[
                        "no_identitas"
                    ] ?></td>
                    <td class="nama-pasien"><?= $row["nama_pasien"] ?></td>
                    <td><span class="badge bg-secondary opacity-75"><?= $row[
                        "kategori_pasien"
                    ] ?></span></td>
                    <td><small class="fw-bold"><?= $row[
                        "unit_prodi"
                    ] ?></small></td>
                    <td><small class="text-success fw-bold">+62 <?= $row[
                        "no_hp"
                    ] ?? "-" ?></small></td>
                    <td>
                        <button class="btn btn-sm btn-light text-warning me-1" data-bs-toggle="modal" data-bs-target="#mEditP<?= $row[
                            "id_pasien"
                        ] ?>"><i class="bi bi-pencil-square"></i></button>
                        <a href="?del=<?= $row[
                            "id_pasien"
                        ] ?>&t=pasienm&k=id_pasien&page=pasien" class="btn btn-sm btn-light text-danger" onclick="return confirm('Hapus pasien?')"><i class="bi bi-trash3"></i></a>
                    </td>
                </tr>
            <?php endwhile;
            ?></tbody></table></div></div>
    <?php elseif ($active_page == "supplier"): ?>
        <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold">Data Supplier</h3><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mAddSupplier">+ Supplier Baru</button></div>
        <div class="data-container mb-4">
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead><tr><th>No</th><th>Nama Supplier</th><th>Kontak</th><th>Alamat</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php
                $no = 1;
                mysqli_data_seek($sup_list, 0);
                while ($r = mysqli_fetch_assoc($sup_list)): ?>
                    <tr>
                        <td class="text-muted small"><?= $no++ ?></td>
                        <td class="fw-bold"><?= htmlspecialchars(
                            $r["nama_supplier"],
                        ) ?></td>
                        <td><small class="text-success fw-bold"><?= htmlspecialchars(
                            $r["kontak"] ?? "-",
                        ) ?></small></td>
                        <td><small><?= htmlspecialchars(
                            $r["alamat"] ?? "-",
                        ) ?></small></td>
                        <td>
                            <button class="btn btn-sm btn-light text-warning me-1" data-bs-toggle="modal" data-bs-target="#mEditSup<?= $r[
                                "id_supplier"
                            ] ?>"><i class="bi bi-pencil-square"></i></button>
                            <a href="?del=<?= $r[
                                "id_supplier"
                            ] ?>&t=supplierm&k=id_supplier&page=supplier" class="btn btn-sm btn-light text-danger" onclick="return confirm('Hapus supplier?')"><i class="bi bi-trash3"></i></a>
                        </td>
                    </tr>
                    <?php endwhile;
                ?></tbody></table></div>
        </div>

    <?php elseif ($active_page == "obat"): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Monitoring Stok Obat</h3>
                <small class="text-muted">Status ketersediaan obat di instalasi farmasi (Read-Only)</small>
            </div>
            <!-- Tidak ada tombol "Tambah Obat" untuk Admin -->
        </div>
        <div class="data-container mb-4 py-3">
            <input type="text" id="searchObat" class="form-control" placeholder="Cari nama obat...">
        </div>

        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Obat</th>
                            <th>Stok Saat Ini</th>
                            <th>Batas Minimum</th>
                            <th>Satuan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $noO = 1;
                        if (mysqli_num_rows($o_list) == 0) {
                            echo "<tr><td colspan='6' class='text-center py-5 text-muted'>Belum ada data obat di database.</td></tr>";
                        }
                        while ($ob = mysqli_fetch_assoc($o_list)):

                            $is_low =
                                (int) $ob["stok_sekarang"] <
                                (int) $ob["stok_minimum"];
                            $is_empty = (int) $ob["stok_sekarang"] <= 0;
                            ?>
<tr class="obat-row"> <!-- Pastikan HANYA SATU TR dan ada class obat-row -->
    <td class="text-muted small"><?= $noO++ ?></td>
    <td class="fw-bold text-primary nama-obat"><?= htmlspecialchars(
        $ob["nama_obat"],
    ) ?></td> 
    <!-- Tambahkan class 'nama-obat' di atas agar bisa dicari JS -->
                                <td class="<?= $is_low
                                    ? "text-danger fw-bold"
                                    : "" ?>">
                                    <?= htmlspecialchars(
                                        $ob["stok_sekarang"],
                                    ) ?>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars(
                                    $ob["stok_minimum"],
                                ) ?></span></td>
                                <td><small class="text-muted"><?= htmlspecialchars(
                                    $ob["satuan"],
                                ) ?></small></td>
                                <td>
                                    <?php if ($is_empty): ?>
                                        <span class="badge bg-danger px-3 py-2 rounded-pill">Stok Habis</span>
                                    <?php elseif ($is_low): ?>
                                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Stok Kritis</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Tersedia</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                        endwhile;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
         <?php endif; ?>
</main>

        <!-- MODAL LOGOUT (DIKEMBALIKAN KARENA SEBELUMNYA HILANG) -->
    <div class="modal fade" id="modalLogout" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="width: 350px;">
            <div class="modal-content border-0 shadow" style="border-radius: 20px;">
                <div class="modal-body text-center p-4">
                    <div class="text-danger mb-3"><i class="bi bi-exclamation-circle fs-1"></i></div>
                    <h5 class="fw-bold">Logout?</h5>
                    <p class="text-secondary small">Sebelum Keluar Pastikan Semua Data Tersimpan.</p>
                    <div class="d-grid gap-2 mt-4">
                        <a href="index.php" class="btn btn-danger py-2 rounded-3 fw-bold text-decoration-none text-white">Keluar</a>
                        <button type="button" class="btn-light btn py-2 rounded-3" data-bs-dismiss="modal">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL ADD USER -->
    <div class="modal fade" id="mAddUser" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg" style="border-radius: 20px;" method="POST"><div class="modal-header bg-primary text-white border-0 py-4"><h5 class="fw-bold mb-0">Tambah Akun Baru</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4">
        <input type="text" name="username" class="form-control mb-3 bg-light border-0" placeholder="NIM/NIP" required>
        <input type="email" name="email" class="form-control mb-3 bg-light border-0" placeholder="Email" required>
        <input type="password" name="password" class="form-control mb-3 bg-light border-0" placeholder="Password" required>
        <input type="text" name="nama_lengkap" class="form-control mb-3 bg-light border-0" placeholder="Nama Lengkap" required>
        <select name="role" class="form-select bg-light border-0"><option value="Dokter">Dokter</option><option value="Pasien">Pasien</option><option value="K3">Tim K3</option></select>
    </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="add_user" class="btn btn-primary w-100 py-2 fw-bold">Simpan Akun</button></div></form></div></div>

    <!-- MODAL ADD STAFF -->
    <div class="modal fade" id="mAddStaff" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg" style="border-radius: 20px;" method="POST"><div class="modal-header bg-primary text-white border-0 py-4"><h5 class="fw-bold mb-0">Daftarkan Staff</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4">
        <input type="text" name="no_identitas" class="form-control mb-3 bg-light border-0" placeholder="NIP / NIK" required>
        <input type="text" name="nama_lengkap" class="form-control mb-3 bg-light border-0" placeholder="Nama & Gelar" required>
        <select name="role_akun" class="form-select mb-3 bg-light border-0" required><option value="Dokter">Dokter</option><option value="Admin">Admin</option><option value="K3">Tim K3</option></select>
        <input type="text" name="jabatan" class="form-control mb-3 bg-light border-0" placeholder="Jabatan" required>
        <select name="instansi" class="form-select mb-3 bg-light border-0"><option>Kampus</option><option>Siloam</option></select>
        <input type="text" name="npa_idi" class="form-control mb-3 bg-light border-0" placeholder="NPA IDI (Opsional)">
        <div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" placeholder="8xx-xxxx-xxxx" required></div>
    </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="add_staff" class="btn btn-primary w-100 py-2 fw-bold">Daftarkan</button></div></form></div></div>

    <!-- MODAL ADD PASIEN -->
    <div class="modal fade" id="mAddPasien" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg" style="border-radius: 20px;" method="POST"><div class="modal-header bg-primary text-white border-0 py-4"><h5 class="fw-bold mb-0">Registrasi Pasien</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4">
        <input type="text" name="no_identitas" class="form-control mb-3 bg-light border-0" placeholder="NIM / NIP / NIK" required>
        <input type="text" name="nama_pasien" class="form-control mb-3 bg-light border-0" placeholder="Nama Pasien" required>
        <div class="row g-2 mb-3">
            <div class="col-6"><select name="jenis_kelamin" class="form-select bg-light border-0"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
            <div class="col-6"><select name="kategori_pasien" class="form-select bg-light border-0"><option>Mahasiswa</option><option>Pegawai</option><option>Tamu</option></select></div>
        </div>
        <label class="small fw-bold">Asal Prodi / Unit / Divisi</label>
        <select name="unit_prodi" class="form-select bg-light border-0 mb-3" required>
            <option value="" selected disabled>-- Pilih Prodi atau Unit Kerja --</option>
            <optgroup label="Program Studi (Mahasiswa)">
                <option value="MI">D3 - Manajemen Informatika</option>
                <option value="MK">D3 - Mekatronika</option>
                <option value="MO">D3 - Mesin Otomotif</option>
                <option value="P4">D3 - Pembuatan Peralatan Presisi</option>
                <option value="TPM">D3 - Teknik Produksi & Proses Manufaktur</option>
                <option value="TKBG">D4 - Teknologi Konstruksi Bangunan Gedung</option>
                <option value="TRL">D4 - Teknologi Rekayasa Logistik</option>
                <option value="TRPAB">D4 - Teknologi Rekayasa Pemeliharaan Alat Berat</option>
                <option value="TRPL">D4 - Teknologi Rekayasa Perangkat Lunak</option>
            </optgroup>
            <optgroup label="Unit / Divisi Kerja (Pegawai)">
                <option value="BAA">Biro Administrasi Akademik (BAA)</option>
                <option value="BAK">Biro Administrasi Keuangan (BAK)</option>
                <option value="BKM">Biro Kemahasiswaan & Alumni</option>
                <option value="WKS">Workshop & Laboratorium Pusat</option>
                <option value="HRD">Human Resources (HRD / Kepegawaian)</option>
                <option value="IT">IT Support & Digital Systems</option>
                <option value="GA">General Affair (Sarpras & Fasilitas)</option>
                <option value="DIR">Sekretariat Direktorat</option>
                <option value="K3">Departemen K3 & Lingkungan</option>
                <option value="SECURITY">Divisi Keamanan & Ketertiban</option>
            </optgroup>
        </select>
        <input type="text" name="alamat" class="form-control mb-3 bg-light border-0" placeholder="Alamat Domisili">
        <div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" placeholder="8xx-xxxx-xxxx" required></div>
    </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="add_pasien" class="btn btn-primary w-100 py-2 fw-bold">Daftarkan</button></div></form></div></div>

    <!-- MODAL ADD SUPPLIER -->
    <div class="modal fade" id="mAddSupplier" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg" style="border-radius: 20px;" method="POST"><div class="modal-header bg-primary text-white border-0 py-4"><h5 class="fw-bold mb-0">Tambah Supplier</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4">
        <input type="text" name="nama_supplier" class="form-control mb-3 bg-light border-0" placeholder="Nama Supplier" required>
        <div class="input-group mb-3"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="kontak" class="form-control bg-light border-0 phone-mask" placeholder="8xx-xxxx-xxxx"></div>
        <input type="text" name="alamat" class="form-control mb-3 bg-light border-0" placeholder="Alamat">
    </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="add_supplier" class="btn btn-primary w-100 py-2 fw-bold">Simpan Supplier</button></div></form></div></div>

    <!-- MODAL EDIT USER -->
    <?php
    mysqli_data_seek($u_list, 0);
    while ($u = mysqli_fetch_assoc($u_list)): ?>
    <div class="modal fade" id="mEditU<?= $u[
        "id_user"
    ] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg" style="border-radius: 20px;" method="POST">
        <div class="modal-header bg-warning border-0 py-4"><h5>Edit Akun</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <input type="hidden" name="id_user" value="<?= $u["id_user"] ?>">
            <label class="small fw-bold">Username</label><input type="text" name="username" class="form-control mb-3 bg-light border-0" value="<?= $u[
                "username"
            ] ?>">
            <label class="small fw-bold">Email</label><input type="email" name="email" class="form-control mb-3 bg-light border-0" value="<?= $u[
                "email"
            ] ?>">
            <label class="small fw-bold">Password</label><input type="text" name="password" class="form-control mb-3 bg-light border-0" value="<?= $u[
                "password"
            ] ?>">
            <label class="small fw-bold">Nama</label><input type="text" name="nama_lengkap" class="form-control mb-3 bg-light border-0" value="<?= $u[
                "nama_lengkap"
            ] ?>">
            <select name="role" class="form-select bg-light border-0"><option <?= $u[
                "role"
            ] == "Dokter"
                ? "selected"
                : "" ?>>Dokter</option><option <?= $u["role"] == "Pasien"
    ? "selected"
    : "" ?>>Pasien</option><option <?= $u["role"] == "K3"
    ? "selected"
    : "" ?>>K3</option></select>
        </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="update_user" class="btn btn-primary w-100 py-2 fw-bold">Update</button></div>
    </form></div></div>
    <?php endwhile;
    ?>

    <!-- MODAL EDIT SUPPLIER -->
    <?php
    mysqli_data_seek($sup_list, 0);
    while ($sup = mysqli_fetch_assoc($sup_list)): ?>
    <div class="modal fade" id="mEditSup<?= $sup[
        "id_supplier"
    ] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg" style="border-radius: 20px;" method="POST">
        <div class="modal-header bg-warning border-0 py-4"><h5>Edit Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <input type="hidden" name="id_supplier" value="<?= $sup[
                "id_supplier"
            ] ?>">
            <input type="text" name="nama_supplier" class="form-control mb-2 bg-light border-0" value="<?= htmlspecialchars(
                $sup["nama_supplier"],
            ) ?>">
            <input type="text" name="alamat" class="form-control mb-2 bg-light border-0" value="<?= htmlspecialchars(
                $sup["alamat"] ?? "",
            ) ?>">
            <div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="kontak" class="form-control bg-light border-0 phone-mask" value="<?= htmlspecialchars(
                $sup["kontak"] ?? "",
            ) ?>"></div>
        </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="update_supplier" class="btn btn-primary w-100 py-2 fw-bold">Update</button></div>
    </form></div></div>
    <?php endwhile;
    ?>

    <!-- MODAL EDIT STAFF -->
    <?php
    mysqli_data_seek($s_list, 0);
    while ($s = mysqli_fetch_assoc($s_list)): ?>
    <div class="modal fade" id="mEditS<?= $s[
        "id_staff"
    ] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg" style="border-radius: 20px;" method="POST">
        <div class="modal-header bg-warning border-0 py-4"><h5>Edit Staff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <input type="hidden" name="id_staff" value="<?= $s["id_staff"] ?>">
            <input type="text" name="no_identitas" class="form-control mb-2 bg-light border-0" value="<?= $s[
                "no_identitas"
            ] ?>">
            <input type="text" name="nama_lengkap" class="form-control mb-2 bg-light border-0" value="<?= $s[
                "nama_lengkap"
            ] ?>">
            <input type="text" name="jabatan" class="form-control mb-2 bg-light border-0" value="<?= $s[
                "jabatan"
            ] ?>">
            <select name="instansi" class="form-select mb-2 bg-light border-0"><option <?= $s[
                "instansi"
            ] == "Kampus"
                ? "selected"
                : "" ?>>Kampus</option><option <?= $s["instansi"] == "Siloam"
    ? "selected"
    : "" ?>>Siloam</option></select>
            <input type="text" name="npa_idi" class="form-control mb-2 bg-light border-0" value="<?= $s[
                "npa_idi"
            ] ?>">
            <div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" value="<?= $s[
                "no_hp"
            ] ?? "" ?>"></div>
        </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="update_staff" class="btn btn-primary w-100 py-2 fw-bold">Update</button></div>
    </form></div></div>
    <?php endwhile;
    ?>

    <!-- MODAL EDIT PASIEN -->
    <?php
    mysqli_data_seek($p_list, 0);
    while ($p = mysqli_fetch_assoc($p_list)): ?>
    <div class="modal fade" id="mEditP<?= $p[
        "id_pasien"
    ] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content border-0 shadow-lg" style="border-radius: 20px;" method="POST">
        <div class="modal-header bg-warning border-0 py-4"><h5>Edit Pasien</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <input type="hidden" name="id_pasien" value="<?= $p[
                "id_pasien"
            ] ?>">
            <input type="text" name="no_identitas" class="form-control mb-2 bg-light border-0" value="<?= $p[
                "no_identitas"
            ] ?>">
            <input type="text" name="nama_pasien" class="form-control mb-2 bg-light border-0" value="<?= $p[
                "nama_pasien"
            ] ?>">
            <select name="jenis_kelamin" class="form-select mb-2 bg-light border-0"><option value="L" <?= $p[
                "jenis_kelamin"
            ] == "L"
                ? "selected"
                : "" ?>>Laki-laki</option><option value="P" <?= $p[
    "jenis_kelamin"
] == "P"
    ? "selected"
    : "" ?>>Perempuan</option></select>
            <select name="kategori_pasien" class="form-select mb-2 bg-light border-0"><option <?= $p[
                "kategori_pasien"
            ] == "Mahasiswa"
                ? "selected"
                : "" ?>>Mahasiswa</option><option <?= $p["kategori_pasien"] ==
"Pegawai"
    ? "selected"
    : "" ?>>Pegawai</option><option <?= $p["kategori_pasien"] == "Tamu"
    ? "selected"
    : "" ?>>Tamu</option></select>
            
            <label class="small fw-bold">Update Prodi / Unit Kerja</label>
            <select name="unit_prodi" class="form-select bg-light border-0 mb-2">
                <?php
                $kategori_unit = [
                    "Program Studi (Mahasiswa)" => [
                        "MI" => "D3 - Manajemen Informatika",
                        "MK" => "D3 - Mekatronika",
                        "MO" => "D3 - Mesin Otomotif",
                        "P4" => "D3 - Pembuatan Peralatan Presisi",
                        "TPM" => "D3 - Teknik Produksi & Proses Manufaktur",
                        "TKBG" => "D4 - Teknologi Konstruksi Bangunan Gedung",
                        "TRL" => "D4 - Teknologi Rekayasa Logistik",
                        "TRPAB" =>
                            "D4 - Teknologi Rekayasa Pemeliharaan Alat Berat",
                        "TRPL" => "D4 - Teknologi Rekayasa Perangkat Lunak",
                    ],
                    "Unit / Divisi Kerja (Pegawai)" => [
                        "BAA" => "Biro Administrasi Akademik (BAA)",
                        "BAK" => "Biro Administrasi Keuangan (BAK)",
                        "BKM" => "Biro Kemahasiswaan & Alumni",
                        "WKS" => "Workshop & Laboratorium Pusat",
                        "HRD" => "Human Resources (HRD / Kepegawaian)",
                        "IT" => "IT Support & Digital Systems",
                        "GA" => "General Affair (Sarpras & Fasilitas)",
                        "DIR" => "Sekretariat Direktorat",
                        "K3" => "Departemen K3 & Lingkungan",
                        "SECURITY" => "Divisi Keamanan & Ketertiban",
                    ],
                ];
                foreach ($kategori_unit as $label_grup => $opsi_unit) {
                    echo "<optgroup label='$label_grup'>";
                    foreach ($opsi_unit as $key => $val) {
                        $is_selected =
                            $p["unit_prodi"] == $key ? "selected" : "";
                        echo "<option value='$key' $is_selected>$val</option>";
                    }
                    echo "</optgroup>";
                }
                ?>
            </select>
            <input type="text" name="alamat" class="form-control mb-2 bg-light border-0" value="<?= $p[
                "alamat"
            ] ?>">
            <div class="input-group"><span class="input-group-text bg-light border-0">+62</span><input type="text" name="no_hp" class="form-control bg-light border-0 phone-mask" value="<?= $p[
                "no_hp"
            ] ?? "" ?>"></div>
        </div><div class="modal-footer border-0 pb-4 px-4"><button type="submit" name="update_pasien" class="btn btn-primary w-100 py-2 fw-bold">Update</button></div>
    </form></div></div>
    <?php endwhile;
    ?>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            document.getElementById('digitalClock').innerText = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000); updateClock();

        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-toggled');
        });

        document.querySelectorAll('.phone-mask').forEach(input => {
            input.addEventListener('input', e => {
                let v = e.target.value.replace(/\D/g, '').substring(0, 13);
                let f = v.substring(0, 3);
                if (v.length > 3) f += '-' + v.substring(3, 7);
                if (v.length > 7) f += '-' + v.substring(7, 12);
                e.target.value = f;
            });
        });

// Konfigurasi Area Chart (Earnings Overview)
// 1. Konfigurasi Line Chart (Statistik Sakit)
// 1. Konfigurasi Line Chart (Statistik Sakit)
const ctxSick = document.getElementById("sickChart");
if (ctxSick) {
    new Chart(ctxSick, {
        type: 'line',
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"],
            datasets: [{
                label: "Jumlah Kunjungan",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 4,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                data: <?= json_encode($line_chart_values) ?>,
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
}

// 2. Konfigurasi Donut Chart (Kategori Pasien)
const ctxCategory = document.getElementById("categoryDonutChart");
if (ctxCategory) {
    new Chart(ctxCategory, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($donut_labels) ?>,
            datasets: [{
                data: <?= json_encode($donut_values) ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                hoverOffset: 4
            }],
        },
        options: {
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: { legend: { display: false } }
        }
    });
}

// Logika Filter dan Search Pasien
const searchInput = document.getElementById('searchPasien');
const prodiFilter = document.getElementById('filterProdi');
const tableRows = document.querySelectorAll('.pasien-row');

function filterTable() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedProdi = prodiFilter.value.toLowerCase();

    tableRows.forEach(row => {
        const nama = row.querySelector('.nama-pasien').innerText.toLowerCase();
        const identitas = row.querySelector('.text-primary').innerText.toLowerCase();
        const prodiData = row.getAttribute('data-prodi').toLowerCase();

        // Cek apakah baris cocok dengan pencarian teks DAN filter dropdown
        const matchSearch = nama.includes(searchTerm) || identitas.includes(searchTerm);
        const matchProdi = selectedProdi === "" || prodiData === selectedProdi;

        if (matchSearch && matchProdi) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

// Jalankan fungsi saat user mengetik atau memilih dropdown
if(searchInput) searchInput.addEventListener('input', filterTable);
if(prodiFilter) prodiFilter.addEventListener('change', filterTable);

// FUNGSI FILTER UNIVERSAL
function setupFilter(inputId, selectId, rowClass, dataAttr, nameClass) {
    const input = document.getElementById(inputId);
    const select = selectId ? document.getElementById(selectId) : null;
    const rows = document.querySelectorAll('.' + rowClass);

    if (!input) return;

    const performFilter = () => {
        const searchTerm = input.value.toLowerCase();
        const selectedVal = select ? select.value.toLowerCase() : "";

        rows.forEach(row => {
            const nameEl = row.querySelector('.' + nameClass);
            const idEl = row.querySelector('.fw-bold'); // Mencari kolom yang tebal (biasanya NIP/NIM/ID)

            if (!nameEl) return;

            const nameText = nameEl.innerText.toLowerCase();
            const idText = idEl ? idEl.innerText.toLowerCase() : "";

            const matchSearch = nameText.includes(searchTerm) || idText.includes(searchTerm);
            
            let matchSelect = true;
            if (select && dataAttr) {
                const attrVal = row.getAttribute(dataAttr) ? row.getAttribute(dataAttr).toLowerCase() : "";
                matchSelect = selectedVal === "" || attrVal === selectedVal;
            }

            row.style.display = (matchSearch && matchSelect) ? "" : "none";
        });
    };

    input.addEventListener('input', performFilter);
    if (select) select.addEventListener('change', performFilter);
}

// Inisialisasi Filter untuk ketiga halaman
document.addEventListener('DOMContentLoaded', () => {
    setupFilter('searchUser', 'filterRole', 'user-row', 'data-role', 'nama-user');
    setupFilter('searchStaff', 'filterInstansi', 'staff-row', 'data-instansi', 'nama-staff');
    setupFilter('searchPasien', 'filterProdi', 'pasien-row', 'data-prodi', 'nama-pasien');
    setupFilter('searchObat', null, 'obat-row', null, 'nama-obat');
});

    </script>
    </body>
    </html>