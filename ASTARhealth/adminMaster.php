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
// LOGIKA CRUD
// ==========================================

// 1. TAMBAH USER MANUAL
if (isset($_POST["add_user"])) {
    $id = generateID("USR");
    $un = $_POST["username"];
    $em = $_POST["email"];
    $ps = $_POST["password"];
    $rl = $_POST["role"];
    $nm = $_POST["nama_lengkap"];
    mysqli_query(
        $conn,
        "INSERT INTO userm VALUES ('$id', '$un', '$em', '$ps', '$rl', '$nm')",
    );
    header("Location: adminMaster.php?page=user&msg=User Berhasil Ditambah");
    exit();
}

// 2. TAMBAH STAFF + AUTO AKUN SSO
if (isset($_POST["add_staff"])) {
    $nip = $_POST["no_identitas"];
    $nama = $_POST["nama_lengkap"];
    $role = $_POST["role_akun"];
    $id_u = generateID("USR");
    $id_s = generateID("STF");
    $username_sso = $nip . "@polytechnic.astar.ac.id";
    $nama_depan = strtolower(explode(" ", trim($nama))[0]);
    $pass_staff = $nama_depan . "123";
    mysqli_query(
        $conn,
        "INSERT INTO userm VALUES ('$id_u', '$username_sso', '$username_sso', '$pass_staff', '$role', '$nama')",
    );
    $jbt = $_POST["jabatan"];
    $ins = $_POST["instansi"];
    $npa = $_POST["npa_idi"];
    $hp = $_POST["no_hp"];
    mysqli_query(
        $conn,
        "INSERT INTO staffm VALUES ('$id_s', '$id_u', '$nama', '$nip', '$jbt', '$ins', '$npa', '$hp')",
    );
    header(
        "Location: adminMaster.php?page=staff&msg=Staff Berhasil Didaftarkan",
    );
    exit();
}

// 3. LOGIKA TAMBAH PASIEN + AUTO AKUN SSO (Blok yang sudah diperbaiki)
if (isset($_POST["add_pasien"])) {
    $id_u = generateID("USR");
    $id_p = generateID("PSN");
    $nim = mysqli_real_escape_string($conn, $_POST["no_identitas"]);
    $nama = mysqli_real_escape_string($conn, $_POST["nama_pasien"]);
    $username_sso = $nim . "@polytechnic.astar.ac.id";
    $nama_parts = explode(" ", trim($nama));
    $nama_depan = strtolower($nama_parts[0]);
    $password_sso = $nama_depan . "123";

    $q_user = "INSERT INTO userm (id_user, username, email, password, role, nama_lengkap) 
                VALUES ('$id_u', '$username_sso', '$username_sso', '$password_sso', 'Pasien', '$nama')";

    $jk = $_POST["jenis_kelamin"];
    $kat = $_POST["kategori_pasien"];
    $prodi = $_POST["unit_prodi"];
    $alm = mysqli_real_escape_string($conn, $_POST["alamat"]);
    $hp = $_POST["no_hp"];

    $q_pasien = "INSERT INTO pasienm (id_pasien, id_user, no_identitas, nama_pasien, jenis_kelamin, kategori_pasien, unit_prodi, alamat, no_hp) 
                    VALUES ('$id_p', '$id_u', '$nim', '$nama', '$jk', '$kat', '$prodi', '$alm', '$hp')";

    if (mysqli_query($conn, $q_user) && mysqli_query($conn, $q_pasien)) {
        header(
            "Location: adminMaster.php?page=pasien&msg=Pasien & Akun SSO Berhasil Dibuat!",
        );
        exit();
    }
}
// 4. UPDATE USER (Jika User diupdate, maka Staff/Pasien yang terhubung ikut berubah)
if (isset($_POST["update_user"])) {
    $id = $_POST["id_user"];
    $un = $_POST["username"];
    $em = $_POST["email"];
    $ps = $_POST["password"];
    $rl = $_POST["role"];
    $nm = $_POST["nama_lengkap"];

    // Update tabel utama (userm)
    mysqli_query(
        $conn,
        "UPDATE userm SET username='$un', email='$em', password='$ps', role='$rl', nama_lengkap='$nm' WHERE id_user='$id'",
    );

    // Sinkronisasi ke tabel staffm (berdasarkan id_user)
    mysqli_query(
        $conn,
        "UPDATE staffm SET no_identitas='$un', nama_lengkap='$nm' WHERE id_user='$id'",
    );

    // Sinkronisasi ke tabel pasienm (berdasarkan id_user)
    mysqli_query(
        $conn,
        "UPDATE pasienm SET no_identitas='$un', nama_pasien='$nm' WHERE id_user='$id'",
    );

    header(
        "Location: adminMaster.php?page=user&msg=Akun & Data Terelasi Berhasil Diupdate",
    );
    exit();
}

// 5. UPDATE STAFF (Jika Staff diupdate, tabel userm ikut berubah)
if (isset($_POST["update_staff"])) {
    $id_s = $_POST["id_staff"];
    $nm_l = $_POST["nama_lengkap"];
    $no_i = $_POST["no_identitas"];
    $jbt = $_POST["jabatan"];
    $ins = $_POST["instansi"];
    $npa = $_POST["npa_idi"];
    $hp = $_POST["no_hp"];

    // Update tabel staffm
    mysqli_query(
        $conn,
        "UPDATE staffm SET nama_lengkap='$nm_l', no_identitas='$no_i', jabatan='$jbt', instansi='$ins', npa_idi='$npa', no_hp='$hp' WHERE id_staff='$id_s'",
    );

    // Ambil id_user yang terkait dengan staff ini untuk update userm
    $get_user = mysqli_query(
        $conn,
        "SELECT id_user FROM staffm WHERE id_staff='$id_s'",
    );
    $data_u = mysqli_fetch_assoc($get_user);
    $id_u = $data_u["id_user"];

    // Sinkronisasi ke userm (Username & Email SSO otomatis mengikuti NIP baru)
    $new_email = $no_i . "@polytechnic.astar.ac.id";
    mysqli_query(
        $conn,
        "UPDATE userm SET username='$no_i', email='$new_email', nama_lengkap='$nm_l' WHERE id_user='$id_u'",
    );

    header(
        "Location: adminMaster.php?page=staff&msg=Data Staff & Akun SSO Berhasil Diupdate",
    );
    exit();
}

// 6. UPDATE PASIEN (Jika Pasien diupdate, tabel userm ikut berubah)
if (isset($_POST["update_pasien"])) {
    $id_p = $_POST["id_pasien"];
    $nm = $_POST["nama_pasien"];
    $nip = $_POST["no_identitas"];
    $jk = $_POST["jenis_kelamin"];
    $kat = $_POST["kategori_pasien"];
    $prodi = $_POST["unit_prodi"];
    $alm = $_POST["alamat"];
    $hp = $_POST["no_hp"];

    // Update tabel pasienm
    mysqli_query(
        $conn,
        "UPDATE pasienm SET nama_pasien='$nm', no_identitas='$nip', jenis_kelamin='$jk', kategori_pasien='$kat', unit_prodi='$prodi', alamat='$alm', no_hp='$hp' WHERE id_pasien='$id_p'",
    );

    // Ambil id_user yang terkait dengan pasien ini
    $get_user_p = mysqli_query(
        $conn,
        "SELECT id_user FROM pasienm WHERE id_pasien='$id_p'",
    );
    $data_up = mysqli_fetch_assoc($get_user_p);
    $id_u_p = $data_up["id_user"];

    // Sinkronisasi ke userm (Username & Email SSO otomatis mengikuti NIM baru)
    $new_email_p = $nip . "@polytechnic.astar.ac.id";
    mysqli_query(
        $conn,
        "UPDATE userm SET username='$nip', email='$new_email_p', nama_lengkap='$nm' WHERE id_user='$id_u_p'",
    );

    header(
        "Location: adminMaster.php?page=pasien&msg=Data Pasien & Akun SSO Berhasil Diupdate",
    );
    exit();
}

// 7. HAPUS UNIVERSAL
if (isset($_GET["del"])) {
    $tabel = $_GET["t"];
    $kolom = $_GET["k"];
    $val = $_GET["del"];
    $pg = $_GET["page"];
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    mysqli_query($conn, "DELETE FROM $tabel WHERE $kolom = '$val'");
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    header("Location: adminMaster.php?page=$pg&msg=Data Berhasil Dihapus");
    exit();
}

$u_list = mysqli_query($conn, "SELECT * FROM userm");
$s_list = mysqli_query($conn, "SELECT * FROM staffm");
$p_list = mysqli_query($conn, "SELECT * FROM pasienm");

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
        :root { --astar-blue: #0057B8; --astar-soft-blue: #eef4ff; --sidebar-bg: #ffffff; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f7fa; color: #334155; }
        .top-header { height: 70px; background: var(--astar-blue); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; color: white; position: fixed; top: 0; width: 100%; z-index: 1001; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        #digitalClock { font-weight: 600; font-size: 14px; background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 50px; }
        .sidebar { width: 260px; background: #FFFFFF; height: 100vh; position: fixed; top: 70px; left: 0; border-right: 1px solid #E5E7EB; z-index: 1000; padding: 20px 15px; transition: all 0.3s ease; }
        .main-content { margin-left: 260px; padding: 100px 30px 40px; transition: all 0.3s ease; }
        body.sidebar-toggled .sidebar { left: -260px; }
        body.sidebar-toggled .main-content { margin-left: 0; }
        .nav-group-title { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; padding: 15px 15px 8px; letter-spacing: 1px; }
        .nav-link { padding: 12px 25px; color: #64748b; font-weight: 500; display: flex; align-items: center; transition: 0.2s; text-decoration: none; font-size: 14px; border-radius: 10px; }
        .nav-link i { font-size: 1.2rem; width: 35px; }
        .nav-link:hover { background: var(--astar-soft-blue); color: var(--astar-blue); }
        .nav-link.active { background: var(--astar-blue); color: #fff; box-shadow: 0 4px 12px rgba(0,87,184,0.3); }
        .data-container { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        .stat-card { background: white; border-radius: 18px; padding: 25px; display: flex; align-items: center; justify-content: space-between; border-left: 6px solid var(--astar-blue); box-shadow: 0 10px 20px rgba(0,0,0,0.03); transition: 0.3s; }
        #toggleSidebar { cursor: pointer; font-size: 1.5rem; color: white; margin-right: 15px; display: flex; align-items: center; }
        .table thead th { background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px; padding: 15px; border: none; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
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
            <i class="bi bi-person-circle fs-2 text-white" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalLogout"></i>
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
        </nav>
    </div>

<main class="main-content">
    <?php if (
        isset($_GET["msg"])
    ): ?><div class="alert alert-success border-0 shadow-sm mb-4 rounded-4 fw-bold text-center"><i class="bi bi-check-circle-fill me-2"></i> <?= $_GET[
    "msg"
] ?></div><?php endif; ?>

    <?php if ($active_page == "dashboard"): ?>
        <!-- Dashboard Tetap Sama -->
        <h3 class="fw-bold mb-4">Dashboard Overview</h3>
        <div class="row g-4 mb-5">
            <div class="col-md-4"><div class="stat-card"><div><div class="small fw-bold text-muted">PASIEN</div><div class="h2 fw-bold text-primary"><?= mysqli_num_rows(
                $p_list,
            ) ?></div></div><i class="bi bi-people fs-1 opacity-25"></i></div></div>
            <div class="col-md-4"><div class="stat-card" style="border-left-color: #f6c23e;"><div><div class="small fw-bold text-muted">TIM STAFF</div><div class="h2 fw-bold text-warning"><?= mysqli_num_rows(
                $s_list,
            ) ?></div></div><i class="bi bi-shield-lock fs-1 opacity-25"></i></div></div>
            <div class="col-md-4"><div class="stat-card" style="border-left-color: #1cc88a;"><div><div class="small fw-bold text-muted">OBAT</div><div class="h2 fw-bold text-success"><?= mysqli_num_rows(
                mysqli_query($conn, "SELECT * FROM obatm"),
            ) ?></div></div><i class="bi bi-capsule fs-1 opacity-25"></i></div></div>
        </div>
        <div class="data-container"><h6>Distribusi User Role</h6><canvas id="roleChart" height="100"></canvas></div>

    <?php elseif ($active_page == "user"): ?>
        <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold">User Credentials</h3><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mAddUser">+ Tambah</button></div>
        
        <!-- Search & Filter User -->
        <div class="data-container mb-4 py-3">
            <div class="row g-3">
                <div class="col-md-8"><input type="text" id="searchUser" class="form-control" placeholder="Cari username atau nama..."></div>
                <div class="col-md-4">
                    <select id="filterRole" class="form-select">
                        <option value="">-- Semua Role --</option>
                        <option value="Admin">Admin</option>
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
            <thead><tr><th>No</th><th>NIP</th><th>Nama</th><th>Jabatan</th><th>Instansi</th><th>No HP</th><th>Aksi</th></tr></thead>
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
    <?php endif; ?>
</main>

        <!-- MODAL LOGOUT (DIKEMBALIKAN KARENA SEBELUMNYA HILANG) -->
    <div class="modal fade" id="modalLogout" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="width: 350px;">
            <div class="modal-content border-0 shadow" style="border-radius: 20px;">
                <div class="modal-body text-center p-4">
                    <div class="text-danger mb-3"><i class="bi bi-exclamation-circle fs-1"></i></div>
                    <h5 class="fw-bold">Logout?</h5>
                    <p class="text-secondary small">Akhiri sesi Admin Anda.</p>
                    <div class="d-grid gap-2 mt-4">
                        <a href="index.php" class="btn btn-danger py-2 rounded-3 fw-bold text-decoration-none text-white">Ya, Keluar</a>
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

        const ctx = document.getElementById('roleChart');
        if(ctx) {
            new Chart(ctx, { type: 'bar', data: { labels: <?= json_encode(
                $chart_labels,
            ) ?>, datasets: [{ label: 'Jumlah Akun', data: <?= json_encode(
    $chart_data,
) ?>, backgroundColor: '#0057B8', borderRadius: 10 }] }, options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } } } });
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
    const select = document.getElementById(selectId);
    const rows = document.querySelectorAll('.' + rowClass);

    if(!input || !select) return;

    const performFilter = () => {
        const searchTerm = input.value.toLowerCase();
        const selectedVal = select.value.toLowerCase();

        rows.forEach(row => {
            const nameText = row.querySelector('.' + nameClass).innerText.toLowerCase();
            const idText = row.querySelector('.fw-bold').innerText.toLowerCase();
            const attrVal = row.getAttribute(dataAttr).toLowerCase();

            const matchSearch = nameText.includes(searchTerm) || idText.includes(searchTerm);
            const matchSelect = selectedVal === "" || attrVal === selectedVal;

            row.style.display = (matchSearch && matchSelect) ? "" : "none";
        });
    };

    input.addEventListener('input', performFilter);
    select.addEventListener('change', performFilter);
}

// Inisialisasi Filter untuk ketiga halaman
document.addEventListener('DOMContentLoaded', () => {
    setupFilter('searchUser', 'filterRole', 'user-row', 'data-role', 'nama-user');
    setupFilter('searchStaff', 'filterInstansi', 'staff-row', 'data-instansi', 'nama-staff');
    setupFilter('searchPasien', 'filterProdi', 'pasien-row', 'data-prodi', 'nama-pasien');
});

    </script>
    </body>
    </html>