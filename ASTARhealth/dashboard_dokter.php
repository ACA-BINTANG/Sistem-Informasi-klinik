<?php
session_start();
require_once 'koneksi.php';

// =======================
// PROTEKSI ROLE DOKTER
// =======================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Dokter') {
    header("Location: login.php?pesan=Akses Ditolak!");
    exit;
}

date_default_timezone_set('Asia/Jakarta');

$doctor_name = $_SESSION['nama_lengkap'] ?? 'Dokter';
$user_id     = $_SESSION['id_user'] ?? '';
$active_page = $_GET['page'] ?? 'antrean';

$notifikasi_stok = mysqli_query($conn, "
    SELECT *
    FROM notifikasi_stok_obat
    ORDER BY tanggal_notifikasi DESC
    LIMIT 5
");

// =======================
// HELPER
// =======================
function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function generateID($conn, $prefix, $table, $column) {
    while (true) {
        $new_id = $prefix . substr(str_shuffle("0123456789"), 0, 4);

        $cek = mysqli_query($conn, "
            SELECT $column
            FROM $table
            WHERE $column = '$new_id'
            LIMIT 1
        ");

        if ($cek && mysqli_num_rows($cek) == 0) {
            return $new_id;
        }
    }
}

function generateIDUrut($conn, $prefix, $table, $column, $prefixLength) {
    $q = mysqli_query($conn, "
        SELECT $column
        FROM $table
        WHERE $column LIKE '$prefix%'
        ORDER BY CAST(SUBSTRING($column, " . ($prefixLength + 1) . ") AS UNSIGNED) DESC
        LIMIT 1
    ");

    if ($q && mysqli_num_rows($q) > 0) {
        $d = mysqli_fetch_assoc($q);
        $lastNumber = (int) substr($d[$column], $prefixLength);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }

    return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
}

function hariIniIndonesia() {
    $map = [
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu',
        'Sunday'    => 'Minggu'
    ];

    return $map[date('l')] ?? '';
}

// =======================
// AMBIL ID STAFF DOKTER
// =======================
$user_id_safe = mysqli_real_escape_string($conn, $user_id);

$qStaff = mysqli_query($conn, "
    SELECT id_staff
    FROM staffm
    WHERE id_user = '$user_id_safe'
    LIMIT 1
");

if (!$qStaff) {
    die("Query staff error: " . mysqli_error($conn));
}

$dStaff = mysqli_fetch_assoc($qStaff);
$id_dokter = $dStaff['id_staff'] ?? '';

if ($id_dokter == '') {
    die("ID dokter tidak ditemukan. Pastikan akun dokter terhubung dengan tabel staffm.");
}

// =======================
// TOMBOL BATAL ANTREAN DOKTER
// Kalau pasien tidak hadir, data antrean dihapus
// Tidak memakai status Batal
// =======================
if (isset($_POST['batal_antrean'])) {

    $id_rm_batal = mysqli_real_escape_string($conn, $_POST['id_rekam_medis'] ?? '');

    if ($id_rm_batal == '') {
        header("Location: dashboard_dokter.php?page=antrean&err=Data antrean tidak ditemukan");
        exit;
    }

    $hapus = mysqli_query($conn, "
        DELETE FROM rekam_medis
        WHERE id_rekam_medis = '$id_rm_batal'
        AND id_staff = '$id_dokter'
        AND status IN ('Menunggu', 'Darurat')
    ");

    if (!$hapus) {
        header("Location: dashboard_dokter.php?page=antrean&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    if (mysqli_affected_rows($conn) > 0) {
        header("Location: dashboard_dokter.php?page=antrean&msg=Antrean berhasil dibatalkan dan data sudah dihapus");
        exit;
    } else {
        header("Location: dashboard_dokter.php?page=antrean&err=Antrean tidak bisa dibatalkan. Mungkin sudah diproses atau selesai.");
        exit;
    }
}

// =======================
// SIMPAN PEMERIKSAAN DOKTER
// Jika obat dipilih dan jumlah keluar diisi,
// stok obat otomatis berkurang
// =======================
if (isset($_POST['simpan_pemeriksaan'])) {

    $id_rm    = mysqli_real_escape_string($conn, $_POST['id_rekam_medis'] ?? '');
    $id_diag  = mysqli_real_escape_string($conn, $_POST['id_diagnosa'] ?? '');
    $keluhan  = mysqli_real_escape_string($conn, $_POST['keluhan'] ?? '');
    $hasil    = mysqli_real_escape_string($conn, $_POST['hasil_pemeriksaan'] ?? '');
    $id_obat  = mysqli_real_escape_string($conn, $_POST['id_obat'] ?? '');
    $qty      = (int) ($_POST['jumlah_keluar'] ?? 0);
    $catatan  = mysqli_real_escape_string($conn, $_POST['catatan_obat'] ?? '');

    if ($id_rm == '' || $id_diag == '' || $keluhan == '' || $hasil == '') {
        header("Location: dashboard_dokter.php?page=antrean&err=Data pemeriksaan belum lengkap");
        exit;
    }

    mysqli_begin_transaction($conn);

    try {
        $cek_rm = mysqli_query($conn, "
            SELECT id_rekam_medis
            FROM rekam_medis
            WHERE id_rekam_medis = '$id_rm'
            AND id_staff = '$id_dokter'
            AND status IN ('Menunggu', 'Darurat', 'Diproses')
            LIMIT 1
            FOR UPDATE
        ");

        if (!$cek_rm) {
            throw new Exception("Query rekam medis error: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($cek_rm) == 0) {
            throw new Exception("Data antrean tidak ditemukan atau sudah selesai.");
        }

        $update_rm = mysqli_query($conn, "
            UPDATE rekam_medis
            SET
                id_diagnosa = '$id_diag',
                keluhan = '$keluhan',
                hasil_pemeriksaan = '$hasil',
                status = 'Selesai'
            WHERE id_rekam_medis = '$id_rm'
            AND id_staff = '$id_dokter'
        ");

        if (!$update_rm) {
            throw new Exception("Gagal menyimpan pemeriksaan: " . mysqli_error($conn));
        }

        if ($catatan != '' || ($id_obat != '' && $qty > 0)) {

            $id_resep = generateID($conn, "RSP", "resep_dokter", "id_resep");

            if ($id_obat != '' && $qty > 0) {

                $cek_obat = mysqli_query($conn, "
                    SELECT id_obat, nama_obat, stok_sekarang
                    FROM obatm
                    WHERE id_obat = '$id_obat'
                    LIMIT 1
                    FOR UPDATE
                ");

                if (!$cek_obat) {
                    throw new Exception("Query obat error: " . mysqli_error($conn));
                }

                if (mysqli_num_rows($cek_obat) == 0) {
                    throw new Exception("Obat tidak ditemukan.");
                }

                $obat = mysqli_fetch_assoc($cek_obat);
                $stok_saat_ini = (int) $obat['stok_sekarang'];

                if ($stok_saat_ini < $qty) {
                    throw new Exception("Stok obat tidak cukup. Stok tersedia: " . $stok_saat_ini);
                }

                $insert_resep = mysqli_query($conn, "
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
                ");

                if (!$insert_resep) {
                    throw new Exception("Gagal menyimpan resep: " . mysqli_error($conn));
                }

                $stok_baru = $stok_saat_ini - $qty;

                $update_stok = mysqli_query($conn, "
                    UPDATE obatm
                    SET stok_sekarang = '$stok_baru'
                    WHERE id_obat = '$id_obat'
                ");

                if (!$update_stok) {
                    throw new Exception("Gagal mengurangi stok obat: " . mysqli_error($conn));
                }

            } else {

                $insert_resep = mysqli_query($conn, "
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
                ");

                if (!$insert_resep) {
                    throw new Exception("Gagal menyimpan catatan resep: " . mysqli_error($conn));
                }
            }
        }

        mysqli_commit($conn);

        header("Location: dashboard_dokter.php?page=rekam_medis&msg=Pemeriksaan berhasil disimpan ke rekam medis");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        header("Location: dashboard_dokter.php?page=antrean&err=" . urlencode($e->getMessage()));
        exit;
    }
}

// =======================
// TAMBAH JADWAL DOKTER
// =======================
if (isset($_POST['add_jadwal_dokter'])) {

    $id_jadwal   = generateIDUrut($conn, "JDW", "jadwalm", "id_jadwal", 3);
    $tanggal     = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? '');
    $jam_mulai   = mysqli_real_escape_string($conn, $_POST['jam_mulai'] ?? '');
    $jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai'] ?? '');
    $status      = mysqli_real_escape_string($conn, $_POST['status'] ?? '');

    if ($tanggal == '' || $jam_mulai == '' || $jam_selesai == '' || $status == '') {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=Semua data jadwal wajib diisi");
        exit;
    }

    if (!in_array($tanggal, ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'])) {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=Hari jadwal tidak valid");
        exit;
    }

    if (!in_array($status, ['Buka','Tutup'])) {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=Status jadwal tidak valid");
        exit;
    }

    if ($jam_selesai <= $jam_mulai) {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=Jam selesai harus lebih besar dari jam mulai");
        exit;
    }

    $cek_jadwal = mysqli_query($conn, "
        SELECT id_jadwal
        FROM jadwalm
        WHERE id_staff = '$id_dokter'
        AND tanggal = '$tanggal'
        LIMIT 1
    ");

    if ($cek_jadwal && mysqli_num_rows($cek_jadwal) > 0) {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=Jadwal untuk hari $tanggal sudah ada");
        exit;
    }

    $insert = mysqli_query($conn, "
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
    ");

    if (!$insert) {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    header("Location: dashboard_dokter.php?page=jadwal_dokter&msg=Jadwal dokter berhasil ditambahkan");
    exit;
}

// =======================
// SHOW EDIT JADWAL
// =======================
$edit_jadwal_data = null;
if (isset($_POST['show_edit_jadwal'])) {
    $id_jadwal_edit = mysqli_real_escape_string($conn, $_POST['id_jadwal'] ?? '');
    
    $qEdit = mysqli_query($conn, "
        SELECT * FROM jadwalm
        WHERE id_jadwal = '$id_jadwal_edit'
        AND id_staff = '$id_dokter'
        LIMIT 1
    ");
    
    if ($qEdit && mysqli_num_rows($qEdit) > 0) {
        $edit_jadwal_data = mysqli_fetch_assoc($qEdit);
    }
}

// =======================
// SHOW EDIT PENGADAAN
// =======================
$edit_pengadaan_data = null;
if (isset($_POST['show_edit_pengadaan'])) {
    $id_pengadaan_edit = mysqli_real_escape_string($conn, $_POST['id_pengadaan'] ?? '');
    
    $qEditPgd = mysqli_query($conn, "
        SELECT p.*, o.nama_obat
        FROM pengadaan_obat p
        LEFT JOIN obatm o ON p.id_obat = o.id_obat
        WHERE p.id_pengadaan = '$id_pengadaan_edit'
        LIMIT 1
    ");
    
    if ($qEditPgd && mysqli_num_rows($qEditPgd) > 0) {
        $edit_pengadaan_data = mysqli_fetch_assoc($qEditPgd);
    }
}

// =======================
$edit_obat_data = null;
if (isset($_POST['show_edit_obat'])) {
    $id_obat_edit = mysqli_real_escape_string($conn, $_POST['id_obat'] ?? '');
    
    $qEditObat = mysqli_query($conn, "
        SELECT * FROM obatm
        WHERE id_obat = '$id_obat_edit'
        LIMIT 1
    ");
    
    if ($qEditObat && mysqli_num_rows($qEditObat) > 0) {
        $edit_obat_data = mysqli_fetch_assoc($qEditObat);
    }
}

// =======================
if (isset($_POST['update_jadwal_dokter'])) {

    $id_jadwal   = mysqli_real_escape_string($conn, $_POST['id_jadwal'] ?? '');
    $tanggal     = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? '');
    $jam_mulai   = mysqli_real_escape_string($conn, $_POST['jam_mulai'] ?? '');
    $jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai'] ?? '');
    $status      = mysqli_real_escape_string($conn, $_POST['status'] ?? '');

    if ($id_jadwal == '' || $tanggal == '' || $jam_mulai == '' || $jam_selesai == '' || $status == '') {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=Data jadwal belum lengkap");
        exit;
    }

    if (!in_array($tanggal, ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'])) {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=Hari jadwal tidak valid");
        exit;
    }

    if (!in_array($status, ['Buka','Tutup'])) {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=Status jadwal tidak valid");
        exit;
    }

    if ($jam_selesai <= $jam_mulai) {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=Jam selesai harus lebih besar dari jam mulai");
        exit;
    }

    // Cek duplikasi (exclude jadwal yang sedang diedit)
    $cek_duplikasi = mysqli_query($conn, "
        SELECT id_jadwal
        FROM jadwalm
        WHERE id_staff = '$id_dokter'
        AND tanggal = '$tanggal'
        AND id_jadwal != '$id_jadwal'
        LIMIT 1
    ");

    if ($cek_duplikasi && mysqli_num_rows($cek_duplikasi) > 0) {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=Jadwal untuk hari $tanggal sudah ada");
        exit;
    }

    $update = mysqli_query($conn, "
        UPDATE jadwalm
        SET
            tanggal = '$tanggal',
            jam_mulai = '$jam_mulai',
            jam_selesai = '$jam_selesai',
            status = '$status'
        WHERE id_jadwal = '$id_jadwal'
        AND id_staff = '$id_dokter'
    ");

    if (!$update) {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    header("Location: dashboard_dokter.php?page=jadwal_dokter&msg=Jadwal dokter berhasil diperbarui");
    exit;
}

// =======================
// HAPUS JADWAL DOKTER
// =======================
if (isset($_POST['hapus_jadwal_dokter'])) {

    $id_jadwal = mysqli_real_escape_string($conn, $_POST['id_jadwal'] ?? '');

    $hapus = mysqli_query($conn, "
        DELETE FROM jadwalm
        WHERE id_jadwal = '$id_jadwal'
        AND id_staff = '$id_dokter'
    ");

    if (!$hapus) {
        header("Location: dashboard_dokter.php?page=jadwal_dokter&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    header("Location: dashboard_dokter.php?page=jadwal_dokter&msg=Jadwal dokter berhasil dihapus");
    exit;
}

// =======================
// TAMBAH PENGADAAN OBAT
// =======================
if (isset($_POST['add_pengadaan_obat'])) {

    $id_pengadaan   = generateIDUrut($conn, "PGD", "pengadaan_obat", "id_pengadaan", 3);
    $id_obat        = mysqli_real_escape_string($conn, $_POST['id_obat'] ?? '');
    $id_supplier    = mysqli_real_escape_string($conn, $_POST['id_supplier'] ?? '');
    $jumlah_order   = (int) ($_POST['jumlah_order'] ?? 0);
    $tgl_estimasi   = mysqli_real_escape_string($conn, $_POST['tgl_estimasi_tiba'] ?? '');

    if ($id_obat == '' || $jumlah_order == 0) {
        header("Location: dashboard_dokter.php?page=pengadaan_obat&err=Data pengadaan belum lengkap");
        exit;
    }

    if ($jumlah_order <= 0) {
        header("Location: dashboard_dokter.php?page=pengadaan_obat&err=Jumlah order harus lebih dari 0");
        exit;
    }

    $insert = mysqli_query($conn, "
        INSERT INTO pengadaan_obat
        (id_pengadaan, id_obat, id_supplier, jumlah_order, tgl_order, tgl_estimasi_tiba, status)
        VALUES
        ('$id_pengadaan', '$id_obat', '$id_supplier', $jumlah_order, DATE(NOW()), '$tgl_estimasi', 'Pending')
    ");

    if (!$insert) {
        header("Location: dashboard_dokter.php?page=pengadaan_obat&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    header("Location: dashboard_dokter.php?page=pengadaan_obat&msg=Pengadaan obat berhasil ditambahkan");
    exit;
}

// =======================
// UPDATE STATUS PENGADAAN
// =======================
if (isset($_POST['update_status_pengadaan'])) {

    $id_pengadaan  = mysqli_real_escape_string($conn, $_POST['id_pengadaan'] ?? '');
    $status_baru   = mysqli_real_escape_string($conn, $_POST['status_baru'] ?? '');
    $jumlah_terima = (int) ($_POST['jumlah_terima'] ?? 0);

    if ($id_pengadaan == '' || $status_baru == '') {
        header("Location: dashboard_dokter.php?page=pengadaan_obat&err=Data tidak lengkap");
        exit;
    }

    if (!in_array($status_baru, ['Pending', 'Proses', 'Diterima', 'Batal'])) {
        header("Location: dashboard_dokter.php?page=pengadaan_obat&err=Status tidak valid");
        exit;
    }

    // Ambil data pengadaan untuk update stok
    $qPgd = mysqli_query($conn, "SELECT * FROM pengadaan_obat WHERE id_pengadaan = '$id_pengadaan' LIMIT 1");
    $dPgd = mysqli_fetch_assoc($qPgd);

    $update = mysqli_query($conn, "
        UPDATE pengadaan_obat
        SET status = '$status_baru'
        WHERE id_pengadaan = '$id_pengadaan'
    ");

    if (!$update) {
        header("Location: dashboard_dokter.php?page=pengadaan_obat&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    // Jika status "Diterima", update stok obat
    if ($status_baru == 'Diterima' && $jumlah_terima > 0) {
        $updateStok = mysqli_query($conn, "
            UPDATE obatm
            SET stok_sekarang = stok_sekarang + $jumlah_terima
            WHERE id_obat = '{$dPgd['id_obat']}'
        ");

        if (!$updateStok) {
            header("Location: dashboard_dokter.php?page=pengadaan_obat&err=Gagal update stok obat");
            exit;
        }
    }

    header("Location: dashboard_dokter.php?page=pengadaan_obat&msg=Status pengadaan berhasil diupdate");
    exit;
}

// =======================
// TAMBAH OBAT
// =======================
if (isset($_POST['add_obat'])) {

    $id_obat       = generateIDUrut($conn, "OBT", "obatm", "id_obat", 3);
    $nama_obat     = mysqli_real_escape_string($conn, $_POST['nama_obat'] ?? '');
    $stok_sekarang = (int) ($_POST['stok_sekarang'] ?? 0);
    $stok_minimum  = (int) ($_POST['stok_minimum'] ?? 10);
    $stok_target   = (int) ($_POST['stok_target'] ?? 100);
    $satuan        = mysqli_real_escape_string($conn, $_POST['satuan'] ?? '');

    if ($nama_obat == '' || $satuan == '') {
        header("Location: dashboard_dokter.php?page=obat&err=Nama obat dan satuan wajib diisi");
        exit;
    }

    $insert = mysqli_query($conn, "
        INSERT INTO obatm
        (
            id_obat,
            nama_obat,
            stok_sekarang,
            stok_minimum,
            stok_target,
            satuan
        )
        VALUES
        (
            '$id_obat',
            '$nama_obat',
            '$stok_sekarang',
            '$stok_minimum',
            '$stok_target',
            '$satuan'
        )
    ");

    if (!$insert) {
        header("Location: dashboard_dokter.php?page=obat&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    header("Location: dashboard_dokter.php?page=obat&msg=Obat berhasil ditambahkan");
    exit;
}

// =======================
// UPDATE OBAT
// =======================
if (isset($_POST['update_obat'])) {

    $id_obat       = mysqli_real_escape_string($conn, $_POST['id_obat'] ?? '');
    $nama_obat     = mysqli_real_escape_string($conn, $_POST['nama_obat'] ?? '');
    $stok_sekarang = (int) ($_POST['stok_sekarang'] ?? 0);
    $stok_minimum  = (int) ($_POST['stok_minimum'] ?? 0);
    $stok_target   = (int) ($_POST['stok_target'] ?? 0);
    $satuan        = mysqli_real_escape_string($conn, $_POST['satuan'] ?? '');

    $update = mysqli_query($conn, "
        UPDATE obatm
        SET
            nama_obat = '$nama_obat',
            stok_sekarang = '$stok_sekarang',
            stok_minimum = '$stok_minimum',
            stok_target = '$stok_target',
            satuan = '$satuan'
        WHERE id_obat = '$id_obat'
    ");

    if (!$update) {
        header("Location: dashboard_dokter.php?page=obat&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    header("Location: dashboard_dokter.php?page=obat&msg=Obat berhasil diperbarui");
    exit;
}

// =======================
// HAPUS OBAT
// =======================
if (isset($_POST['hapus_obat'])) {

    $id_obat = mysqli_real_escape_string($conn, $_POST['id_obat'] ?? '');

    $hapus = mysqli_query($conn, "
        DELETE FROM obatm
        WHERE id_obat = '$id_obat'
    ");

    if (!$hapus) {
        header("Location: dashboard_dokter.php?page=obat&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    header("Location: dashboard_dokter.php?page=obat&msg=Obat berhasil dihapus");
    exit;
}

// =======================
// TAMBAH DIAGNOSA
// =======================
if (isset($_POST['add_diagnosa'])) {

    $id_diagnosa   = generateID($conn, "DG", "diagnosam", "id_diagnosa");
    $nama_penyakit = mysqli_real_escape_string($conn, $_POST['nama_penyakit'] ?? '');
    $kategori      = mysqli_real_escape_string($conn, $_POST['kategori'] ?? 'Umum');
    $tipe          = mysqli_real_escape_string($conn, $_POST['tipe'] ?? 'Ringan');

    if ($nama_penyakit == '') {
        header("Location: dashboard_dokter.php?page=diagnosa&err=Nama penyakit wajib diisi");
        exit;
    }

    $insert = mysqli_query($conn, "
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
    ");

    if (!$insert) {
        header("Location: dashboard_dokter.php?page=diagnosa&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    header("Location: dashboard_dokter.php?page=diagnosa&msg=Diagnosa berhasil ditambahkan");
    exit;
}

// =======================
// UPDATE DIAGNOSA
// =======================
if (isset($_POST['update_diagnosa'])) {

    $id_diagnosa   = mysqli_real_escape_string($conn, $_POST['id_diagnosa'] ?? '');
    $nama_penyakit = mysqli_real_escape_string($conn, $_POST['nama_penyakit'] ?? '');
    $kategori      = mysqli_real_escape_string($conn, $_POST['kategori'] ?? '');
    $tipe          = mysqli_real_escape_string($conn, $_POST['tipe'] ?? '');

    $update = mysqli_query($conn, "
        UPDATE diagnosam
        SET
            nama_penyakit = '$nama_penyakit',
            kategori = '$kategori',
            tipe = '$tipe'
        WHERE id_diagnosa = '$id_diagnosa'
    ");

    if (!$update) {
        header("Location: dashboard_dokter.php?page=diagnosa&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    header("Location: dashboard_dokter.php?page=diagnosa&msg=Diagnosa berhasil diperbarui");
    exit;
}

// =======================
// HAPUS DIAGNOSA
// =======================
if (isset($_POST['hapus_diagnosa'])) {

    $id_diagnosa = mysqli_real_escape_string($conn, $_POST['id_diagnosa'] ?? '');

    $hapus = mysqli_query($conn, "
        DELETE FROM diagnosam
        WHERE id_diagnosa = '$id_diagnosa'
    ");

    if (!$hapus) {
        header("Location: dashboard_dokter.php?page=diagnosa&err=" . urlencode(mysqli_error($conn)));
        exit;
    }

    header("Location: dashboard_dokter.php?page=diagnosa&msg=Diagnosa berhasil dihapus");
    exit;
}

// =======================
// DATA UNTUK FORM PEMERIKSAAN
// =======================
$qDiagnosaSelect = mysqli_query($conn, "
    SELECT id_diagnosa, nama_penyakit
    FROM diagnosam
    ORDER BY nama_penyakit ASC
");

$diagnosa_options = [];

if ($qDiagnosaSelect) {
    while ($dx = mysqli_fetch_assoc($qDiagnosaSelect)) {
        $diagnosa_options[] = $dx;
    }
}

$qObatSelect = mysqli_query($conn, "
    SELECT id_obat, nama_obat, stok_sekarang, satuan
    FROM obatm
    ORDER BY nama_obat ASC
");

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

    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --astar-blue: #0057B8;
            --astar-dark: #0f172a;
            --astar-soft: #eef4ff;
            --danger-soft: #fff1f2;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f4f7fa;
            color: #334155;
        }

        .top-header {
            height: 70px;
            background: var(--astar-blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1001;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        #digitalClock {
            font-weight: 700;
            font-size: 14px;
            background: rgba(255,255,255,0.12);
            padding: 6px 16px;
            border-radius: 50px;
        }

        .sidebar {
            width: 280px;
            height: 100vh;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            position: fixed;
            left: 0;
            top: 70px;
            padding: 15px 0;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .main-content {
            margin-left: 280px;
            padding: 100px 40px 40px;
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
            padding: 12px 25px;
            border-radius: 12px;
            color: #64748b;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
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
            background: var(--astar-blue);
            color: white;
            box-shadow: 0 4px 12px rgba(0,87,184,0.28);
        }

        .data-container {
            background: white;
            border-radius: 22px;
            padding: 28px;
            border: 1px solid #f1f5f9;
            box-shadow: 0 10px 25px rgba(15,23,42,0.03);
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            border-left: 6px solid var(--astar-blue);
            box-shadow: 0 10px 25px rgba(15,23,42,0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
        }

        .stat-card.danger {
            border-left-color: #dc3545;
        }

        .stat-card.success {
            border-left-color: #198754;
        }

        .queue-card {
            border-radius: 22px;
            border: 1px solid #eef2f7;
            background: white;
            padding: 24px;
            box-shadow: 0 8px 22px rgba(15,23,42,0.03);
            transition: all 0.2s ease;
        }

        .queue-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px rgba(15,23,42,0.06);
        }

        .queue-card.darurat {
            border-color: rgba(220,53,69,0.25);
            background: linear-gradient(135deg, #ffffff 0%, #fff1f2 100%);
        }

        .queue-number {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            background: var(--astar-blue);
            color: white;
            font-size: 24px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .queue-number.darurat {
            background: #dc3545;
        }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 800;
            border: none;
            padding: 15px;
        }

        .table td {
            vertical-align: middle;
        }

        .form-control,
        .form-select {
            border-radius: 14px;
            padding: 12px 14px;
        }

        .btn {
            border-radius: 14px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

<header class="top-header">
    <div class="d-flex align-items-center gap-3">
        <div id="sidebarToggle">
            <i class="bi bi-list"></i>
        </div>

        <img src="assets/img/logoA.png" style="max-height: 70px; filter: brightness(0) invert(1);">

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
    <div class="nav-group-title">Menu Dokter</div>

    <nav class="nav flex-column">
        <a class="nav-link <?= ($active_page == 'antrean') ? 'active' : '' ?>" href="dashboard_dokter.php?page=antrean">
            <i class="bi bi-list-ol"></i> Antrean Pasien
        </a>

        <a class="nav-link <?= ($active_page == 'rekam_medis') ? 'active' : '' ?>" href="dashboard_dokter.php?page=rekam_medis">
            <i class="bi bi-clipboard2-pulse-fill"></i> Rekam Medis
        </a>

        <a class="nav-link <?= ($active_page == 'jadwal_dokter') ? 'active' : '' ?>" href="dashboard_dokter.php?page=jadwal_dokter">
            <i class="bi bi-calendar-week-fill"></i> Jadwal Dokter
        </a>
    </nav>

    <div class="nav-group-title">Master Data</div>

    <nav class="nav flex-column">
        <a class="nav-link <?= ($active_page == 'obat') ? 'active' : '' ?>" href="dashboard_dokter.php?page=obat">
            <i class="bi bi-capsule-pill"></i> Data Obat
        </a>

        <a class="nav-link <?= ($active_page == 'pengadaan_obat') ? 'active' : '' ?>" href="dashboard_dokter.php?page=pengadaan_obat">
            <i class="bi bi-box-seam"></i> Pengadaan Obat
        </a>

        <a class="nav-link <?= ($active_page == 'diagnosa') ? 'active' : '' ?>" href="dashboard_dokter.php?page=diagnosa">
            <i class="bi bi-journal-medical"></i> Data Diagnosa
        </a>

        <a class="nav-link <?= ($active_page == 'pasien') ? 'active' : '' ?>" href="dashboard_dokter.php?page=pasien">
            <i class="bi bi-people-fill"></i> Data Pasien
        </a>
    </nav>
</div>

<main class="main-content">

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 fw-bold mb-4">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['err'])): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 fw-bold mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($_GET['err']) ?>
        </div>
    <?php endif; ?>

    <?php if($active_page == 'antrean'): ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Antrean Pasien</h3>
                <small class="text-muted">Pasien darurat otomatis tampil paling atas.</small>
            </div>

            <span class="badge bg-primary px-3 py-2 rounded-pill">
                <?= e(hariIniIndonesia()) ?>, <?= date('d M Y') ?>
            </span>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">TOTAL MENUNGGU HARI INI</div>
                        <div class="h2 fw-bold text-primary mb-0">
                            <?php
                            $qTotal = mysqli_query($conn, "
                                SELECT id_rekam_medis
                                FROM rekam_medis
                                WHERE id_staff = '$id_dokter'
                                AND tgl_kunjungan = CURDATE()
                                AND status IN ('Menunggu','Darurat')
                            ");
                            echo $qTotal ? mysqli_num_rows($qTotal) : 0;
                            ?>
                        </div>
                    </div>
                    <i class="bi bi-ticket-perforated fs-1 text-primary opacity-25"></i>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card danger">
                    <div>
                        <div class="small text-muted fw-bold">DARURAT HARI INI</div>
                        <div class="h2 fw-bold text-danger mb-0">
                            <?php
                            $qDarurat = mysqli_query($conn, "
                                SELECT id_rekam_medis
                                FROM rekam_medis
                                WHERE id_staff = '$id_dokter'
                                AND tgl_kunjungan = CURDATE()
                                AND status = 'Darurat'
                            ");
                            echo $qDarurat ? mysqli_num_rows($qDarurat) : 0;
                            ?>
                        </div>
                    </div>
                    <i class="bi bi-lightning-charge-fill fs-1 text-danger opacity-25"></i>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card success">
                    <div>
                        <div class="small text-muted fw-bold">SELESAI HARI INI</div>
                        <div class="h2 fw-bold text-success mb-0">
                            <?php
                            $qSelesai = mysqli_query($conn, "
                                SELECT id_rekam_medis
                                FROM rekam_medis
                                WHERE id_staff = '$id_dokter'
                                AND tgl_kunjungan = CURDATE()
                                AND status = 'Selesai'
                            ");
                            echo $qSelesai ? mysqli_num_rows($qSelesai) : 0;
                            ?>
                        </div>
                    </div>
                    <i class="bi bi-check2-circle fs-1 text-success opacity-25"></i>
                </div>
            </div>
        </div>

        <div class="data-container">
            <h5 class="fw-bold mb-4">
                <i class="bi bi-list-check text-primary me-2"></i>Daftar Antrean Aktif
            </h5>

            <div class="row g-3">
                <?php
                $qAntrean = mysqli_query($conn, "
                    SELECT
                        rm.*,
                        p.nama_pasien,
                        p.no_identitas,
                        p.kategori_pasien,
                        p.unit_prodi
                    FROM rekam_medis rm
                    JOIN pasienm p ON rm.id_pasien = p.id_pasien
                    WHERE rm.id_staff = '$id_dokter'
                    AND rm.status IN ('Menunggu','Darurat')
                    ORDER BY
                        CASE
                            WHEN rm.status = 'Darurat' THEN 0
                            ELSE 1
                        END ASC,
                        CASE
                            WHEN rm.jenis_antrean = 'Jadwal'
                                 AND rm.tgl_kunjungan = CURDATE()
                                 AND rm.waktu_booking <= CURTIME()
                            THEN 0
                            WHEN rm.jenis_antrean = 'Langsung'
                            THEN 1
                            ELSE 2
                        END ASC,
                        rm.tgl_kunjungan ASC,
                        rm.waktu_booking ASC,
                        CAST(SUBSTRING(rm.no_antrian, 2) AS UNSIGNED) ASC
                ");

                if (!$qAntrean) {
                    echo "<div class='col-12'><div class='alert alert-danger'>Query error: " . e(mysqli_error($conn)) . "</div></div>";
                } elseif(mysqli_num_rows($qAntrean) == 0) {
                    echo "
                        <div class='col-12'>
                            <div class='text-center py-5 text-muted'>
                                <i class='bi bi-inbox' style='font-size:4rem;'></i>
                                <h5 class='fw-bold mt-3'>Belum Ada Antrean Aktif</h5>
                                <p class='mb-0'>Semua antrean sudah selesai atau belum ada pasien.</p>
                            </div>
                        </div>
                    ";
                }

                if ($qAntrean) {
                    while($r = mysqli_fetch_assoc($qAntrean)):
                ?>
                    <div class="col-lg-6">
                        <div class="queue-card <?= ($r['status'] == 'Darurat') ? 'darurat' : '' ?>">
                            <div class="d-flex gap-3">
                                <div class="queue-number <?= ($r['status'] == 'Darurat') ? 'darurat' : '' ?>">
                                    <?= e($r['no_antrian']) ?>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <h5 class="fw-bold mb-1"><?= e($r['nama_pasien']) ?></h5>
                                            <div class="small text-muted">
                                                <?= e($r['no_identitas']) ?> • <?= e($r['kategori_pasien'] ?? '-') ?> • <?= e($r['unit_prodi'] ?? '-') ?>
                                            </div>
                                        </div>

                                        <?php if($r['status'] == 'Darurat'): ?>
                                            <span class="badge bg-danger px-3 py-2 rounded-pill">Darurat</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary px-3 py-2 rounded-pill">Menunggu</span>
                                        <?php endif; ?>
                                    </div>

                                    <hr>

                                    <div class="small mb-2">
                                        <strong>Jenis:</strong> <?= e($r['jenis_antrean']) ?>
                                    </div>

                                    <div class="small mb-2">
                                        <strong>Tanggal:</strong> <?= e(date('d M Y', strtotime($r['tgl_kunjungan']))) ?>
                                    </div>

                                    <div class="small mb-2">
                                        <strong>Jam:</strong> <?= e(substr($r['waktu_booking'], 0, 5)) ?>
                                    </div>

                                    <div class="small mb-3">
                                        <strong>Keluhan:</strong> <?= e($r['keluhan']) ?>
                                    </div>

                                    <div class="d-flex gap-2 flex-wrap">
                                        <button class="btn btn-primary fw-bold px-4"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalPeriksa<?= e($r['id_rekam_medis']) ?>">
                                            <i class="bi bi-clipboard2-pulse me-1"></i> Periksa
                                        </button>

                                        <form method="POST" onsubmit="return confirm('Batalkan antrean pasien ini? Data antrean akan dihapus.')">
                                            <input type="hidden" name="id_rekam_medis" value="<?= e($r['id_rekam_medis']) ?>">

                                            <button type="submit" name="batal_antrean" class="btn btn-light text-danger border fw-bold px-4">
                                                <i class="bi bi-x-circle me-1"></i> Batal
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="modalPeriksa<?= e($r['id_rekam_medis']) ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                                <div class="modal-header bg-primary text-white border-0 py-4">
                                    <h5 class="fw-bold mb-0">
                                        <i class="bi bi-clipboard2-pulse me-2"></i>Pemeriksaan Pasien
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body p-4">
                                    <input type="hidden" name="id_rekam_medis" value="<?= e($r['id_rekam_medis']) ?>">

                                    <div class="alert <?= ($r['status'] == 'Darurat') ? 'alert-danger' : 'alert-info' ?> border-0 rounded-4">
                                        <div class="fw-bold"><?= e($r['nama_pasien']) ?> - <?= e($r['no_antrian']) ?></div>
                                        <div class="small">
                                            Status: <?= e($r['status']) ?> |
                                            Jenis: <?= e($r['jenis_antrean']) ?> |
                                            Jam: <?= e(substr($r['waktu_booking'], 0, 5)) ?>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="small fw-bold text-muted">KELUHAN PASIEN</label>
                                        <textarea name="keluhan" class="form-control bg-light border-0" rows="3" required><?= e($r['keluhan']) ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="small fw-bold text-muted">DIAGNOSA</label>
                                        <select name="id_diagnosa" class="form-select bg-light border-0" required>
                                            <option value="">-- Pilih Diagnosa --</option>
                                            <?php foreach($diagnosa_options as $dx): ?>
                                                <option value="<?= e($dx['id_diagnosa']) ?>">
                                                    <?= e($dx['nama_penyakit']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="small fw-bold text-muted">HASIL PEMERIKSAAN</label>
                                        <textarea name="hasil_pemeriksaan" class="form-control bg-light border-0" rows="5" required placeholder="Tuliskan hasil pemeriksaan dokter..."></textarea>
                                    </div>

                                    <hr>

                                    <h6 class="fw-bold mb-3">
                                        <i class="bi bi-capsule-pill text-primary me-2"></i>Resep Obat
                                    </h6>

                                    <div class="row g-3">
                                        <div class="col-md-7">
                                            <label class="small fw-bold text-muted">OBAT</label>
                                            <select name="id_obat" class="form-select bg-light border-0">
                                                <option value="">-- Tidak menggunakan obat --</option>
                                                <?php foreach($obat_options as $ob): ?>
                                                    <option value="<?= e($ob['id_obat']) ?>">
                                                        <?= e($ob['nama_obat']) ?> - Stok: <?= e($ob['stok_sekarang']) ?> <?= e($ob['satuan']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-5">
                                            <label class="small fw-bold text-muted">JUMLAH KELUAR</label>
                                            <input type="number" name="jumlah_keluar" class="form-control bg-light border-0" min="0" value="0">
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label class="small fw-bold text-muted">CATATAN OBAT / ATURAN PAKAI</label>
                                        <textarea name="catatan_obat" class="form-control bg-light border-0" rows="3" placeholder="Contoh: 3x1 setelah makan"></textarea>
                                    </div>
                                </div>

                                <div class="modal-footer border-0 px-4 pb-4">
                                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Tutup</button>
                                    <button type="submit" name="simpan_pemeriksaan" class="btn btn-primary fw-bold px-4">
                                        <i class="bi bi-save me-1"></i> Simpan Pemeriksaan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php
                    endwhile;
                }
                ?>
            </div>
        </div>

    <?php elseif($active_page == 'rekam_medis'): ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Rekam Medis</h3>
                <small class="text-muted">Data pasien yang sudah selesai diperiksa.</small>
            </div>
        </div>

        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>No Antrean</th>
                            <th>Pasien</th>
                            <th>Keluhan</th>
                            <th>Diagnosa</th>
                            <th>Status</th>
                            <th class="text-center">Detail</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $no = 1;

                        $qRM = mysqli_query($conn, "
                            SELECT
                                rm.*,
                                p.nama_pasien,
                                p.no_identitas,
                                d.nama_penyakit
                            FROM rekam_medis rm
                            JOIN pasienm p ON rm.id_pasien = p.id_pasien
                            LEFT JOIN diagnosam d ON rm.id_diagnosa = d.id_diagnosa
                            WHERE rm.id_staff = '$id_dokter'
                            ORDER BY rm.tgl_kunjungan DESC, rm.waktu_booking DESC
                        ");

                        if (!$qRM) {
                            echo "<tr><td colspan='8' class='text-center text-danger'>Query error: " . e(mysqli_error($conn)) . "</td></tr>";
                        } elseif(mysqli_num_rows($qRM) == 0) {
                            echo "<tr><td colspan='8' class='text-center py-5 text-muted'>Belum ada rekam medis.</td></tr>";
                        }

                        if ($qRM) {
                            while($rm = mysqli_fetch_assoc($qRM)):
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <div class="fw-bold"><?= e(date('d M Y', strtotime($rm['tgl_kunjungan']))) ?></div>
                                    <small class="text-muted"><?= e(substr($rm['waktu_booking'], 0, 5)) ?></small>
                                </td>

                                <td>
                                    <span class="badge bg-primary px-3 py-2 rounded-pill"><?= e($rm['no_antrian']) ?></span>
                                </td>

                                <td>
                                    <div class="fw-bold"><?= e($rm['nama_pasien']) ?></div>
                                    <small class="text-muted"><?= e($rm['no_identitas']) ?></small>
                                </td>

                                <td>
                                    <div style="max-width: 220px;" class="text-truncate" title="<?= e($rm['keluhan']) ?>">
                                        <?= e($rm['keluhan']) ?>
                                    </div>
                                </td>

                                <td><?= e($rm['nama_penyakit'] ?? 'Belum ada') ?></td>

                                <td>
                                    <?php if($rm['status'] == 'Darurat'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3">Darurat</span>
                                    <?php elseif($rm['status'] == 'Selesai'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3">Selesai</span>
                                    <?php elseif($rm['status'] == 'Diproses'): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info px-3">Diproses</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning px-3">Menunggu</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <button class="btn btn-sm btn-light border fw-bold"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDetailRM<?= e($rm['id_rekam_medis']) ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalDetailRM<?= e($rm['id_rekam_medis']) ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                                        <div class="modal-header bg-light border-0 p-4">
                                            <h5 class="fw-bold mb-0">Detail Rekam Medis</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <label class="small fw-bold text-muted">PASIEN</label>
                                                    <div class="fw-bold"><?= e($rm['nama_pasien']) ?></div>
                                                    <small class="text-muted"><?= e($rm['no_identitas']) ?></small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="small fw-bold text-muted">TANGGAL / JAM</label>
                                                    <div class="fw-bold">
                                                        <?= e(date('d M Y', strtotime($rm['tgl_kunjungan']))) ?>,
                                                        <?= e(substr($rm['waktu_booking'], 0, 5)) ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <hr>

                                            <label class="small fw-bold text-muted">KELUHAN</label>
                                            <div class="p-3 bg-light rounded-4 mb-3"><?= nl2br(e($rm['keluhan'])) ?></div>

                                            <label class="small fw-bold text-muted">DIAGNOSA</label>
                                            <div class="p-3 bg-light rounded-4 mb-3"><?= e($rm['nama_penyakit'] ?? 'Belum ada') ?></div>

                                            <label class="small fw-bold text-muted">HASIL PEMERIKSAAN</label>
                                            <div class="p-3 bg-light rounded-4 mb-3"><?= nl2br(e($rm['hasil_pemeriksaan'] ?? 'Belum ada catatan pemeriksaan')) ?></div>

                                            <label class="small fw-bold text-muted">RESEP / CATATAN OBAT</label>
                                            <div class="p-3 bg-light rounded-4">
                                                <?php
                                                $id_rm_detail = mysqli_real_escape_string($conn, $rm['id_rekam_medis']);

                                                $qResep = mysqli_query($conn, "
                                                    SELECT rd.*, o.nama_obat, o.satuan
                                                    FROM resep_dokter rd
                                                    LEFT JOIN obatm o ON rd.id_obat = o.id_obat
                                                    WHERE rd.id_rekam_medis = '$id_rm_detail'
                                                ");

                                                if ($qResep && mysqli_num_rows($qResep) > 0) {
                                                    while($rsp = mysqli_fetch_assoc($qResep)) {
                                                        echo "<div class='mb-2'>";
                                                        echo "<div class='fw-bold'>" . e($rsp['nama_obat'] ?? 'Catatan tanpa obat') . "</div>";
                                                        echo "<small class='text-muted'>Jumlah: " . e($rsp['jumlah_keluar']) . " " . e($rsp['satuan'] ?? '') . "</small>";
                                                        echo "<div class='small'>" . nl2br(e($rsp['catatan_obat'] ?? '-')) . "</div>";
                                                        echo "</div>";
                                                    }
                                                } else {
                                                    echo "<span class='text-muted'>Belum ada resep.</span>";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                            endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif($active_page == 'jadwal_dokter'): ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Jadwal Dokter</h3>
                <small class="text-muted">Kelola hari dan jam praktik dokter.</small>
            </div>

            <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahJadwal">
                <i class="bi bi-plus-circle me-1"></i> Tambah Jadwal
            </button>
        </div>

        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Hari</th>
                            <th>Jam Mulai</th>
                            <th>Jam Selesai</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $noJ = 1;

                        $qJadwal = mysqli_query($conn, "
                            SELECT *
                            FROM jadwalm
                            WHERE id_staff = '$id_dokter'
                            AND tanggal IN ('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')
                            AND status IN ('Buka','Tutup')
                            ORDER BY FIELD(tanggal, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jam_mulai ASC
                        ");

                        if (!$qJadwal) {
                            echo "<tr><td colspan='6' class='text-center text-danger'>Query error: " . e(mysqli_error($conn)) . "</td></tr>";
                        } elseif(mysqli_num_rows($qJadwal) == 0) {
                            echo "<tr><td colspan='6' class='text-center py-5 text-muted'>Belum ada jadwal dokter.</td></tr>";
                        }

                        if ($qJadwal) {
                            while($j = mysqli_fetch_assoc($qJadwal)):
                        ?>
                            <tr>
                                <td><?= $noJ++ ?></td>
                                <td class="fw-bold"><?= e($j['tanggal']) ?></td>
                                <td><?= e(substr($j['jam_mulai'], 0, 5)) ?></td>
                                <td><?= e(substr($j['jam_selesai'], 0, 5)) ?></td>

                                <td>
                                    <?php if($j['status'] == 'Buka'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3">Buka</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3">Tutup</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_jadwal" value="<?= e($j['id_jadwal']) ?>">
                                        <input type="hidden" name="tanggal_lama" value="<?= e($j['tanggal']) ?>">
                                        <button type="submit" name="show_edit_jadwal" class="btn btn-sm btn-light border fw-bold">
                                            Edit
                                        </button>
                                    </form>

                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus jadwal ini?')">
                                        <input type="hidden" name="id_jadwal" value="<?= e($j['id_jadwal']) ?>">
                                        <button type="submit" name="hapus_jadwal_dokter" class="btn btn-sm btn-danger fw-bold">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php
                            endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="modalTambahJadwal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Tambah Jadwal Dokter</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">HARI</label>
                            <select name="tanggal" class="form-select bg-light border-0" required>
                                <option value="">-- Pilih Hari --</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">JAM MULAI</label>
                                <input type="time" name="jam_mulai" class="form-control bg-light border-0" required>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">JAM SELESAI</label>
                                <input type="time" name="jam_selesai" class="form-control bg-light border-0" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="small fw-bold text-muted">STATUS</label>
                            <select name="status" class="form-select bg-light border-0" required>
                                <option value="Buka">Buka</option>
                                <option value="Tutup">Tutup</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="add_jadwal_dokter" class="btn btn-primary w-100 py-3 fw-bold">
                            Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Jadwal -->
        <?php if ($edit_jadwal_data): ?>
        <div class="modal fade" id="modalEditJadwal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Edit Jadwal</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <input type="hidden" name="id_jadwal" value="<?= e($edit_jadwal_data['id_jadwal']) ?>">

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">HARI</label>
                            <select name="tanggal" class="form-select bg-light border-0" required>
                                <?php foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $hari): ?>
                                    <option value="<?= e($hari) ?>" <?= ($edit_jadwal_data['tanggal'] == $hari) ? 'selected' : '' ?>>
                                        <?= e($hari) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">JAM MULAI</label>
                                <input type="time" name="jam_mulai" class="form-control bg-light border-0" value="<?= e($edit_jadwal_data['jam_mulai'] ? substr($edit_jadwal_data['jam_mulai'], 0, 5) : '') ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">JAM SELESAI</label>
                                <input type="time" name="jam_selesai" class="form-control bg-light border-0" value="<?= e($edit_jadwal_data['jam_selesai'] ? substr($edit_jadwal_data['jam_selesai'], 0, 5) : '') ?>" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="small fw-bold text-muted">STATUS</label>
                            <select name="status" class="form-select bg-light border-0" required>
                                <option value="Buka" <?= ($edit_jadwal_data['status'] == 'Buka') ? 'selected' : '' ?>>Buka</option>
                                <option value="Tutup" <?= ($edit_jadwal_data['status'] == 'Tutup') ? 'selected' : '' ?>>Tutup</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="update_jadwal_dokter" class="btn btn-primary w-100 py-3 fw-bold">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const editModal = new bootstrap.Modal(document.getElementById('modalEditJadwal'));
                editModal.show();
            });
        </script>
        <?php endif; ?>

    <?php elseif($active_page == 'obat'): ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Data Obat</h3>
                <small class="text-muted">Kelola stok obat klinik.</small>
            </div>

            <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahObat">
                <i class="bi bi-plus-circle me-1"></i> Tambah Obat
            </button>
        </div>
<?php if ($notifikasi_stok && mysqli_num_rows($notifikasi_stok) > 0): ?>
    <div class="data-container mb-4" style="border-left: 6px solid #ffc107;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-1 text-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Notifikasi Stok Obat
                </h5>
                <small class="text-muted">
                    Obat yang stoknya sudah mencapai batas minimum.
                </small>
            </div>

            <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                <?= mysqli_num_rows($notifikasi_stok); ?> Notifikasi
            </span>
        </div>

        <div class="row g-3">
            <?php while ($notif = mysqli_fetch_assoc($notifikasi_stok)): ?>
                <div class="col-md-6">
                    <div class="p-3 rounded-4 bg-light border">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-1">
                                    <?= e($notif['nama_obat']); ?>
                                </h6>

                                <small class="text-muted">
                                    <?= date('d-m-Y H:i', strtotime($notif['tanggal_notifikasi'])); ?>
                                </small>
                            </div>

                            <span class="badge bg-danger rounded-pill">
                                Stok Rendah
                            </span>
                        </div>

                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Stok Sekarang</span>
                                <strong class="text-danger">
                                    <?= e($notif['stok_sekarang']); ?>
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Stok Minimum</span>
                                <strong>
                                    <?= e($notif['stok_minimum']); ?>
                                </strong>
                            </div>

                            <p class="mb-0 small text-muted">
                                <?= e($notif['pesan']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
<?php endif; ?>
        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Obat</th>
                            <th>Stok</th>
                            <th>Min Stok</th>
                            <th>Satuan</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $noObat = 1;

                        $qObat = mysqli_query($conn, "
                            SELECT *
                            FROM obatm
                            ORDER BY nama_obat ASC
                        ");

                        if (!$qObat) {
                            echo "<tr><td colspan='7' class='text-center text-danger'>Query error: " . e(mysqli_error($conn)) . "</td></tr>";
                        } elseif(mysqli_num_rows($qObat) == 0) {
                            echo "<tr><td colspan='7' class='text-center py-5 text-muted'>Belum ada data obat.</td></tr>";
                        }

                        if ($qObat) {
                            while($ob = mysqli_fetch_assoc($qObat)):
                        ?>
                            <tr>
                                <td><?= $noObat++ ?></td>
                                <td class="fw-bold text-primary"><?= e($ob['nama_obat']) ?></td>
                                <td><?= e($ob['stok_sekarang']) ?></td>
                                <td>
                                    <?php if((int)$ob['stok_sekarang'] < (int)$ob['stok_minimum']): ?>
                                        <span class="badge bg-danger"><?= e($ob['stok_minimum']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10"><?= e($ob['stok_minimum']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($ob['satuan']) ?></td>

                                <td>
                                    <?php if((int)$ob['stok_sekarang'] > 0): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3">Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3">Habis</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_obat" value="<?= e($ob['id_obat']) ?>">
                                        <button type="submit" name="show_edit_obat" class="btn btn-sm btn-light border fw-bold">
                                            Edit
                                        </button>
                                    </form>

                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus obat ini?')">
                                        <input type="hidden" name="id_obat" value="<?= e($ob['id_obat']) ?>">
                                        <button type="submit" name="hapus_obat" class="btn btn-sm btn-danger fw-bold">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php
                            endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Edit Obat -->
        <?php if ($edit_obat_data): ?>
        <div class="modal fade" id="modalEditObat" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Edit Obat</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <input type="hidden" name="id_obat" value="<?= e($edit_obat_data['id_obat']) ?>">

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">NAMA OBAT</label>
                            <input type="text" name="nama_obat" class="form-control bg-light border-0" value="<?= e($edit_obat_data['nama_obat']) ?>" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">STOK</label>
                                <input type="number" name="stok_sekarang" class="form-control bg-light border-0" value="<?= e($edit_obat_data['stok_sekarang']) ?>" min="0" required>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">MIN STOK</label>
                                <input type="number" name="stok_minimum" class="form-control bg-light border-0" value="<?= e($edit_obat_data['stok_minimum']) ?>" min="0" required>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">TARGET STOK</label>
                                <input type="number" name="stok_target" class="form-control bg-light border-0" value="<?= e($edit_obat_data['stok_target']) ?>" min="0" required>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">SATUAN</label>
                                <select name="satuan" class="form-select bg-light border-0" required>
                                    <option value="">-- Pilih Satuan --</option>
                                    <option value="Tablet" <?= ($edit_obat_data['satuan'] == 'Tablet') ? 'selected' : '' ?>>Tablet</option>
                                    <option value="Kapsul" <?= ($edit_obat_data['satuan'] == 'Kapsul') ? 'selected' : '' ?>>Kapsul</option>
                                    <option value="Botol" <?= ($edit_obat_data['satuan'] == 'Botol') ? 'selected' : '' ?>>Botol</option>
                                    <option value="Strip" <?= ($edit_obat_data['satuan'] == 'Strip') ? 'selected' : '' ?>>Strip</option>
                                    <option value="Ampul" <?= ($edit_obat_data['satuan'] == 'Ampul') ? 'selected' : '' ?>>Ampul</option>
                                    <option value="Sachet" <?= ($edit_obat_data['satuan'] == 'Sachet') ? 'selected' : '' ?>>Sachet</option>
                                    <option value="Tube" <?= ($edit_obat_data['satuan'] == 'Tube') ? 'selected' : '' ?>>Tube</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="update_obat" class="btn btn-primary w-100 py-3 fw-bold">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const editModal = new bootstrap.Modal(document.getElementById('modalEditObat'));
                editModal.show();
            });
        </script>
        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="modalTambahObat" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Tambah Obat</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">NAMA OBAT</label>
                            <input type="text" name="nama_obat" class="form-control bg-light border-0" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">STOK</label>
                                <input type="number" name="stok_sekarang" class="form-control bg-light border-0" min="0" required>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">MIN STOK</label>
                                <input type="number" name="stok_minimum" class="form-control bg-light border-0" min="0" value="10" required>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">TARGET STOK</label>
                                <input type="number" name="stok_target" class="form-control bg-light border-0" min="0" value="100" required>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">SATUAN</label>
                                <select name="satuan" class="form-select bg-light border-0" required>
                                    <option value="">-- Pilih Satuan --</option>
                                    <option value="Tablet">Tablet</option>
                                    <option value="Kapsul">Kapsul</option>
                                    <option value="Botol">Botol</option>
                                    <option value="Strip">Strip</option>
                                    <option value="Ampul">Ampul</option>
                                    <option value="Sachet">Sachet</option>
                                    <option value="Tube">Tube</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="add_obat" class="btn btn-primary w-100 py-3 fw-bold">
                            Simpan Obat
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif($active_page == 'pengadaan_obat'): ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Pengadaan Obat</h3>
                <small class="text-muted">Kelola pengadaan dan stok obat klinik.</small>
            </div>

            <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahPengadaan">
                <i class="bi bi-plus-circle me-1"></i> Buat Pengadaan
            </button>
        </div>

        <!-- Obat Kurang Stok -->
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-danger bg-opacity-10 border-0 px-4 py-3">
                        <h6 class="fw-bold text-danger mb-0">⚠️ Obat Kurang dari Stok Minimum</h6>
                    </div>

                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Obat</th>
                                        <th>Stok</th>
                                        <th>Min</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $qKurang = mysqli_query($conn, "
                                        SELECT id_obat, nama_obat, stok_sekarang, stok_minimum, stok_target
                                        FROM obatm
                                        WHERE stok_sekarang < stok_minimum
                                        ORDER BY stok_sekarang ASC
                                    ");

                                    if (!$qKurang || mysqli_num_rows($qKurang) == 0) {
                                        echo "<tr><td colspan='4' class='text-center text-muted py-3'>Semua obat dalam kondisi baik ✓</td></tr>";
                                    } else {
                                        while($ok = mysqli_fetch_assoc($qKurang)):
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= e($ok['nama_obat']) ?></td>
                                        <td><span class="badge bg-danger"><?= $ok['stok_sekarang'] ?></span></td>
                                        <td><?= $ok['stok_minimum'] ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning fw-bold px-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalTambahPengadaan"
                                                    data-id-obat="<?= e($ok['id_obat']) ?>"
                                                    data-nama-obat="<?= e($ok['nama_obat']) ?>"
                                                    data-stok-sekarang="<?= $ok['stok_sekarang'] ?>"
                                                    data-stok-target="<?= $ok['stok_target'] ?>"
                                                    onclick="hitungJumlahOrder(this)">
                                                Pesan
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                        endwhile;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Obat -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-info bg-opacity-10 border-0 px-4 py-3">
                        <h6 class="fw-bold text-info mb-0">📊 Ringkasan Stok Obat</h6>
                    </div>

                    <div class="card-body p-4">
                        <?php
                        $qStats = mysqli_query($conn, "
                            SELECT 
                                COUNT(*) as total_obat,
                                SUM(CASE WHEN stok_sekarang >= stok_minimum THEN 1 ELSE 0 END) as stok_baik,
                                SUM(CASE WHEN stok_sekarang < stok_minimum THEN 1 ELSE 0 END) as stok_kurang,
                                SUM(stok_sekarang) as total_stok
                            FROM obatm
                        ");
                        $stats = mysqli_fetch_assoc($qStats);
                        ?>

                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-primary"><?= $stats['total_obat'] ?></h5>
                                    <small class="text-muted">Total Obat</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-success"><?= $stats['stok_baik'] ?></h5>
                                    <small class="text-muted">Stok Baik</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-danger"><?= $stats['stok_kurang'] ?></h5>
                                    <small class="text-muted">Stok Kurang</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-warning"><?= $stats['total_stok'] ?></h5>
                                    <small class="text-muted">Total Unit</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Pengadaan -->
        <div class="data-container">
            <h5 class="fw-bold mb-4">Riwayat Pengadaan Obat</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Obat</th>
                            <th>Jumlah</th>
                            <th>Tanggal Order</th>
                            <th>Est. Tiba</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $qPengadaan = mysqli_query($conn, "
                            SELECT p.*, o.nama_obat
                            FROM pengadaan_obat p
                            LEFT JOIN obatm o ON p.id_obat = o.id_obat
                            ORDER BY p.tgl_order DESC
                        ");

                        if (!$qPengadaan) {
                            echo "<tr><td colspan='7' class='text-center text-danger'>Query error: " . e(mysqli_error($conn)) . "</td></tr>";
                        } elseif(mysqli_num_rows($qPengadaan) == 0) {
                            echo "<tr><td colspan='7' class='text-center py-5 text-muted'>Belum ada data pengadaan.</td></tr>";
                        } else {
                            while($p = mysqli_fetch_assoc($qPengadaan)):
                                $badgeClass = [
                                    'Pending' => 'warning',
                                    'Proses' => 'info',
                                    'Diterima' => 'success',
                                    'Batal' => 'danger'
                                ][$p['status']] ?? 'secondary';
                        ?>
                            <tr>
                                <td class="fw-bold small"><?= e($p['id_pengadaan']) ?></td>
                                <td><?= e($p['nama_obat'] ?? 'N/A') ?></td>
                                <td class="fw-bold"><?= $p['jumlah_order'] ?> unit</td>
                                <td><?= date('d/m/Y', strtotime($p['tgl_order'])) ?></td>
                                <td><?= $p['tgl_estimasi_tiba'] ? date('d/m/Y', strtotime($p['tgl_estimasi_tiba'])) : '-' ?></td>
                                <td>
                                    <span class="badge bg-<?= $badgeClass ?> bg-opacity-10 text-<?= $badgeClass ?> fw-bold">
                                        <?= e($p['status']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if($p['status'] != 'Diterima' && $p['status'] != 'Batal'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_pengadaan" value="<?= e($p['id_pengadaan']) ?>">
                                        <button type="submit" name="show_edit_pengadaan" class="btn btn-sm btn-light border fw-bold">
                                            Update
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                            endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Update Status Pengadaan -->
        <?php if ($edit_pengadaan_data): ?>
        <div class="modal fade" id="modalUpdatePengadaan" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Update Status Pengadaan</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <input type="hidden" name="id_pengadaan" value="<?= e($edit_pengadaan_data['id_pengadaan']) ?>">

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">OBAT</label>
                            <p class="form-control-plaintext fw-bold"><?= e($edit_pengadaan_data['nama_obat'] ?? 'N/A') ?></p>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">JUMLAH ORDER</label>
                            <p class="form-control-plaintext"><?= $edit_pengadaan_data['jumlah_order'] ?> unit</p>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">STATUS SAAT INI</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-info bg-opacity-10 text-info fw-bold">
                                    <?= e($edit_pengadaan_data['status']) ?>
                                </span>
                            </p>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">STATUS BARU</label>
                            <select name="status_baru" class="form-select bg-light border-0" required>
                                <option value="Pending" <?= ($edit_pengadaan_data['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="Proses" <?= ($edit_pengadaan_data['status'] == 'Proses') ? 'selected' : '' ?>>Proses</option>
                                <option value="Diterima" <?= ($edit_pengadaan_data['status'] == 'Diterima') ? 'selected' : '' ?>>Diterima</option>
                                <option value="Batal" <?= ($edit_pengadaan_data['status'] == 'Batal') ? 'selected' : '' ?>>Batal</option>
                            </select>
                        </div>

                        <div class="mb-3" id="jumlahTerimaContainer" style="display:none;">
                            <label class="small fw-bold text-muted">JUMLAH DITERIMA</label>
                            <input type="number" name="jumlah_terima" id="jumlahTerimaInput" class="form-control bg-light border-0" 
                                   value="<?= $edit_pengadaan_data['jumlah_order'] ?>" min="0" max="<?= $edit_pengadaan_data['jumlah_order'] ?>">
                            <small class="text-muted">Masukkan jumlah yang benar-benar diterima</small>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="update_status_pengadaan" class="btn btn-primary w-100 py-3 fw-bold">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const statusBaru = document.querySelector('select[name=status_baru]');
                const jumlahContainer = document.getElementById('jumlahTerimaContainer');
                
                function toggleJumlahTerima() {
                    if (statusBaru.value === 'Diterima') {
                        jumlahContainer.style.display = 'block';
                    } else {
                        jumlahContainer.style.display = 'none';
                    }
                }
                
                toggleJumlahTerima();
                statusBaru.addEventListener('change', toggleJumlahTerima);
                
                const editModal = new bootstrap.Modal(document.getElementById('modalUpdatePengadaan'));
                editModal.show();
            });
        </script>
        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah Pengadaan -->
        <div class="modal fade" id="modalTambahPengadaan" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Buat Pengadaan Obat</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">PILIH OBAT</label>
                            <select name="id_obat" class="form-select bg-light border-0" required>
                                <option value="">-- Pilih Obat --</option>
                                <?php
                                $qObatList = mysqli_query($conn, "
                                    SELECT id_obat, nama_obat, stok_sekarang, stok_minimum, stok_target
                                    FROM obatm
                                    ORDER BY nama_obat ASC
                                ");
                                while($obt = mysqli_fetch_assoc($qObatList)):
                                    $status = $obt['stok_sekarang'] < $obt['stok_minimum'] ? '🔴 KURANG' : '🟢 OK';
                                ?>
                                <option value="<?= e($obt['id_obat']) ?>" title="Stok: <?= $obt['stok_sekarang'] ?>">
                                    <?= e($obt['nama_obat']) ?> - <?= $status ?> (Stok: <?= $obt['stok_sekarang'] ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">SUPPLIER</label>
                            <select name="id_supplier" class="form-select bg-light border-0" required>
                                <?php
                                $qSupplier = mysqli_query($conn, "SELECT * FROM supplierm WHERE nama_supplier LIKE '%Siloam%' ORDER BY nama_supplier ASC");
                                if ($qSupplier && mysqli_num_rows($qSupplier) > 0) {
                                    while($sup = mysqli_fetch_assoc($qSupplier)):
                                ?>
                                <option value="<?= e($sup['id_supplier']) ?>"><?= e($sup['nama_supplier']) ?></option>
                                <?php
                                    endwhile;
                                } else {
                                    echo '<option value="">Siloam (Tidak ada data)</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">JUMLAH ORDER</label>
                            <small class="text-warning" id="saran_jumlah" style="display:none;"></small>
                            <input type="number" name="jumlah_order" id="jumlah_order" class="form-control bg-light border-0" 
                                   placeholder="Jumlah unit" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">ESTIMASI TIBA</label>
                            <input type="date" name="tgl_estimasi_tiba" class="form-control bg-light border-0">
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="add_pengadaan_obat" class="btn btn-primary w-100 py-3 fw-bold">
                            Buat Pengadaan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif($active_page == 'diagnosa'): ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Data Diagnosa</h3>
                <small class="text-muted">Kelola master penyakit dan diagnosa.</small>
            </div>

            <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahDiagnosa">
                <i class="bi bi-plus-circle me-1"></i> Tambah Diagnosa
            </button>
        </div>

        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Penyakit</th>
                            <th>Kategori</th>
                            <th>Tipe</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $noD = 1;

                        $qDiagnosa = mysqli_query($conn, "
                            SELECT *
                            FROM diagnosam
                            ORDER BY nama_penyakit ASC
                        ");

                        if (!$qDiagnosa) {
                            echo "<tr><td colspan='5' class='text-center text-danger'>Query error: " . e(mysqli_error($conn)) . "</td></tr>";
                        } elseif(mysqli_num_rows($qDiagnosa) == 0) {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Belum ada data diagnosa.</td></tr>";
                        }

                        if ($qDiagnosa) {
                            while($dg = mysqli_fetch_assoc($qDiagnosa)):
                        ?>
                            <tr>
                                <td><?= $noD++ ?></td>
                                <td class="fw-bold text-primary"><?= e($dg['nama_penyakit']) ?></td>
                                <td><span class="badge bg-light text-dark border px-3"><?= e($dg['kategori']) ?></span></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary px-3"><?= e($dg['tipe']) ?></span></td>

                                <td class="text-center">
                                    <button class="btn btn-sm btn-light border fw-bold"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditDiagnosa<?= e($dg['id_diagnosa']) ?>">
                                        Edit
                                    </button>

                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus diagnosa ini?')">
                                        <input type="hidden" name="id_diagnosa" value="<?= e($dg['id_diagnosa']) ?>">
                                        <button type="submit" name="hapus_diagnosa" class="btn btn-sm btn-danger fw-bold">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEditDiagnosa<?= e($dg['id_diagnosa']) ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                                        <div class="modal-header bg-primary text-white border-0 py-4">
                                            <h5 class="fw-bold mb-0">Edit Diagnosa</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <input type="hidden" name="id_diagnosa" value="<?= e($dg['id_diagnosa']) ?>">

                                            <div class="mb-3">
                                                <label class="small fw-bold text-muted">NAMA PENYAKIT</label>
                                                <input type="text" name="nama_penyakit" class="form-control bg-light border-0" value="<?= e($dg['nama_penyakit']) ?>" required>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="small fw-bold text-muted">KATEGORI</label>
                                                    <select name="kategori" class="form-select bg-light border-0" required>
                                                        <option value="Umum" <?= ($dg['kategori'] == 'Umum') ? 'selected' : '' ?>>Umum</option>
                                                        <option value="Menular" <?= ($dg['kategori'] == 'Menular') ? 'selected' : '' ?>>Menular</option>
                                                        <option value="Kronis" <?= ($dg['kategori'] == 'Kronis') ? 'selected' : '' ?>>Kronis</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="small fw-bold text-muted">TIPE</label>
                                                    <select name="tipe" class="form-select bg-light border-0" required>
                                                        <option value="Ringan" <?= ($dg['tipe'] == 'Ringan') ? 'selected' : '' ?>>Ringan</option>
                                                        <option value="Sedang" <?= ($dg['tipe'] == 'Sedang') ? 'selected' : '' ?>>Sedang</option>
                                                        <option value="Berat" <?= ($dg['tipe'] == 'Berat') ? 'selected' : '' ?>>Berat</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer border-0 px-4 pb-4">
                                            <button type="submit" name="update_diagnosa" class="btn btn-primary w-100 py-3 fw-bold">
                                                Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php
                            endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="modalTambahDiagnosa" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:24px;">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Tambah Diagnosa</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">NAMA PENYAKIT</label>
                            <input type="text" name="nama_penyakit" class="form-control bg-light border-0" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">KATEGORI</label>
                                <select name="kategori" class="form-select bg-light border-0" required>
                                    <option value="Umum">Umum</option>
                                    <option value="Menular">Menular</option>
                                    <option value="Kronis">Kronis</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">TIPE</label>
                                <select name="tipe" class="form-select bg-light border-0" required>
                                    <option value="Ringan">Ringan</option>
                                    <option value="Sedang">Sedang</option>
                                    <option value="Berat">Berat</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="add_diagnosa" class="btn btn-primary w-100 py-3 fw-bold">
                            Simpan Diagnosa
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif($active_page == 'pasien'): ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Data Pasien</h3>
                <small class="text-muted">Daftar pasien yang terdaftar di klinik.</small>
            </div>
        </div>

        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No Identitas</th>
                            <th>Nama Pasien</th>
                            <th>Kategori</th>
                            <th>Unit / Prodi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $noP = 1;

                        $qPasien = mysqli_query($conn, "
                            SELECT *
                            FROM pasienm
                            ORDER BY nama_pasien ASC
                        ");

                        if (!$qPasien) {
                            echo "<tr><td colspan='5' class='text-center text-danger'>Query error: " . e(mysqli_error($conn)) . "</td></tr>";
                        } elseif(mysqli_num_rows($qPasien) == 0) {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Belum ada data pasien.</td></tr>";
                        }

                        if ($qPasien) {
                            while($p = mysqli_fetch_assoc($qPasien)):
                        ?>
                            <tr>
                                <td><?= $noP++ ?></td>
                                <td class="fw-bold text-primary"><?= e($p['no_identitas']) ?></td>
                                <td class="fw-bold"><?= e($p['nama_pasien']) ?></td>
                                <td><span class="badge bg-light text-dark border px-3"><?= e($p['kategori_pasien']) ?></span></td>
                                <td><?= e($p['unit_prodi']) ?></td>
                            </tr>
                        <?php
                            endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>

        <div class="data-container text-center py-5">
            <i class="bi bi-exclamation-circle text-muted" style="font-size:4rem;"></i>
            <h4 class="fw-bold mt-3">Halaman tidak ditemukan</h4>
            <p class="text-muted mb-0">Silakan pilih menu yang tersedia di sidebar.</p>
        </div>

    <?php endif; ?>

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
                    Sesi dokter akan berakhir.
                </p>

                <div class="d-flex gap-2">
                    <button type="button"
                            class="btn btn-light w-100 py-2 fw-bold rounded-3"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <a href="index.php"
                       class="btn btn-danger w-100 py-2 fw-bold rounded-3 shadow-sm text-white text-decoration-none">
                        Ya, Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const sidebarToggle = document.getElementById('sidebarToggle');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-toggled');
        });
    }

    function updateClock() {
        const clock = document.getElementById('digitalClock');

        if (!clock) {
            return;
        }

        const now = new Date();

        const hari = [
            'Minggu',
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu'
        ];

        const bulan = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'Mei',
            'Jun',
            'Jul',
            'Agu',
            'Sep',
            'Okt',
            'Nov',
            'Des'
        ];

        const teks =
            hari[now.getDay()] + ', ' +
            now.getDate() + ' ' +
            bulan[now.getMonth()] + ' ' +
            now.getFullYear() + ' | ' +
            String(now.getHours()).padStart(2, '0') + ':' +
            String(now.getMinutes()).padStart(2, '0') + ':' +
            String(now.getSeconds()).padStart(2, '0');

        clock.textContent = teks;
    }

    updateClock();
    setInterval(updateClock, 1000);
});

// =====================================================
// HITUNG JUMLAH ORDER OTOMATIS
// =====================================================
function hitungJumlahOrder(btn) {
    const idObat = btn.getAttribute('data-id-obat');
    const namaObat = btn.getAttribute('data-nama-obat');
    const stokSekarang = parseInt(btn.getAttribute('data-stok-sekarang'));
    const stokTarget = parseInt(btn.getAttribute('data-stok-target'));
    
    // Hitung jumlah yang disarankan
    const jumlahDisarankan = stokTarget - stokSekarang;
    
    // Set nilai ke input
    document.getElementById('jumlah_order').value = jumlahDisarankan > 0 ? jumlahDisarankan : 1;
    
    // Set nilai select obat
    document.querySelector('select[name=id_obat]').value = idObat;
    
    // Tampilkan saran
    const saranEl = document.getElementById('saran_jumlah');
    saranEl.innerHTML = `💡 Saran: ${jumlahDisarankan} unit (target ${stokTarget} - stok saat ini ${stokSekarang})`;
    saranEl.style.display = 'block';
}
</script>

</body>
</html>