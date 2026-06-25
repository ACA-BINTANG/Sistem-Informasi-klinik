<?php
$role_required = 'admin';
$page_title = 'Kelola Pengguna';
require_once 'layout.php';
session_start();

// Inisialisasi data Staff
if (!isset($_SESSION['staff'])) {
    $_SESSION['staff'] = [
        ['id' => 'U-002', 'username' => 'dokter', 'nama' => 'dr. Andi Pratama', 'email' => 'andi@klinik.id', 'role' => 'Dokter', 'status' => 'Aktif'],
        ['id' => 'U-003', 'username' => 'rini_h', 'nama' => 'dr. Rini Hasanah', 'email' => 'rini@klinik.id', 'role' => 'Dokter', 'status' => 'Aktif'],
        ['id' => 'U-004', 'username' => 'budi_s', 'nama' => 'Budi Santoso', 'email' => 'budi@stud.klinik.id', 'role' => 'Mahasiswa', 'status' => 'Aktif'],
        ['id' => 'U-005', 'username' => 'sari_w', 'nama' => 'Sari Wulandari', 'email' => 'sari@stud.klinik.id', 'role' => 'Mahasiswa', 'status' => 'Aktif'],
        ['id' => 'U-006', 'username' => 'reza_p', 'nama' => 'Reza Pratama', 'email' => 'reza@stud.klinik.id', 'role' => 'Mahasiswa', 'status' => 'Nonaktif'],
    ];
}

// Inisialisasi data Pasien
if (!isset($_SESSION['pasien'])) {
    $_SESSION['pasien'] = [
        ['id' => 'P-001', 'nama' => 'Siti Aminah', 'jk' => 'P', 'umur' => 34, 'alamat' => 'Jl. Mawar No.12', 'hp' => '081234567890', 'tipe' => 'BPJS'],
        ['id' => 'P-002', 'nama' => 'Joko Widodo', 'jk' => 'L', 'umur' => 58, 'alamat' => 'Jl. Melati No.3', 'hp' => '081234567891', 'tipe' => 'BPJS'],
        ['id' => 'P-003', 'nama' => 'Maria Santosa', 'jk' => 'P', 'umur' => 29, 'alamat' => 'Jl. Anggrek No.21', 'hp' => '081234567892', 'tipe' => 'Umum'],
        ['id' => 'P-004', 'nama' => 'Ahmad Yani', 'jk' => 'L', 'umur' => 47, 'alamat' => 'Jl. Kenanga No.7', 'hp' => '081234567893', 'tipe' => 'BPJS'],
        ['id' => 'P-005', 'nama' => 'Dewi Lestari', 'jk' => 'P', 'umur' => 22, 'alamat' => 'Jl. Cempaka No.5', 'hp' => '081234567894', 'tipe' => 'Umum'],
        ['id' => 'P-006', 'nama' => 'Budi Setiawan', 'jk' => 'L', 'umur' => 41, 'alamat' => 'Jl. Dahlia No.15', 'hp' => '081234567895', 'tipe' => 'Asuransi'],
    ];
}

$errors = [];
$activeTab = $_GET['tab'] ?? 'staff';

// Generate next ID for staff
function nextStaffId($data) {
    $max = 0;
    foreach ($data as $s) {
        $num = (int) substr($s['id_staff'], 2);
        if ($num > $max) $max = $num;
    }
    return 'U-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

// Generate next ID for pasien
function nextPasienId($data) {
    $max = 0;
    foreach ($data as $p) {
        $num = (int) substr($p['id_pasien'], 2);
        if ($num > $max) $max = $num;
    }
    return 'P-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

// Validasi Staff
function validateStaff($data) {
    $errs = [];
    $u = trim($data['username'] ?? '');
    $n = trim($data['nama'] ?? '');
    $e = trim($data['email'] ?? '');

    if ($u === '') $errs['username'] = 'Username wajib diisi';
    elseif (strlen($u) < 3) $errs['username'] = 'Username minimal 3 karakter';

    if ($n === '') $errs['nama'] = 'Nama lengkap wajib diisi';
    elseif (strlen($n) < 2) $errs['nama'] = 'Nama minimal 2 karakter';

    if ($e === '') $errs['email'] = 'Email wajib diisi';
    elseif (!filter_var($e, FILTER_VALIDATE_EMAIL)) $errs['email'] = 'Format email tidak valid';

    return $errs;
}

// Validasi Pasien
function validatePasien($data) {
    $errs = [];
    $n = trim($data['nama'] ?? '');
    $u = (int) ($data['umur'] ?? 0);
    $a = trim($data['alamat'] ?? '');
    $h = trim($data['hp'] ?? '');

    if ($n === '') $errs['nama'] = 'Nama wajib diisi';
    elseif (strlen($n) < 2) $errs['nama'] = 'Nama minimal 2 karakter';

    if ($u <= 0) $errs['umur'] = 'Umur harus lebih dari 0';
    elseif ($u > 150) $errs['umur'] = 'Umur tidak valid';

    if ($a === '') $errs['alamat'] = 'Alamat wajib diisi';

    if ($h === '') $errs['hp'] = 'No HP wajib diisi';
    elseif (!preg_match('/^[0-9]{10,15}$/', str_replace([' ', '-'], '', $h))) $errs['hp'] = 'No HP tidak valid (10-15 digit)';

    return $errs;
}

// Proses Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'staff') {
    $errors = validateStaff($_POST);
    if (empty($errors)) {
        $staff = $_SESSION['staff'];
        $new = [
            'id' => $_POST['id'] ?? nextStaffId($staff),
            'username' => trim($_POST['username']),
            'nama' => trim($_POST['nama']),
            'email' => trim($_POST['email']),
            'role' => $_POST['role'] ?? 'Dokter',
            'status' => $_POST['status'] ?? 'Aktif',
        ];
        $found = false;
        foreach ($staff as &$s) {
            if ($s['id'] === $new['id']) { $s = $new; $found = true; break; }
        }
        if (!$found) $staff[] = $new;
        $_SESSION['staff'] = $staff;
        header('Location: ?tab=staff');
        exit;
    }
}

// Hapus Staff
if (isset($_GET['delete_staff'])) {
    $_SESSION['staff'] = array_values(array_filter($_SESSION['staff'], fn($s) => $s['id'] !== $_GET['delete_staff']));
    header('Location: ?tab=staff');
    exit;
}

// Proses Pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'pasien') {
    $errors = validatePasien($_POST);
    if (empty($errors)) {
        $pasien = $_SESSION['pasien'];
        $new = [
            'id' => $_POST['id'] ?? nextPasienId($pasien),
            'nama' => trim($_POST['nama']),
            'jk' => $_POST['jk'] ?? 'L',
            'umur' => (int) $_POST['umur'],
            'alamat' => trim($_POST['alamat']),
            'hp' => trim($_POST['hp']),
            'tipe' => $_POST['tipe'] ?? 'Umum',
        ];
        $found = false;
        foreach ($pasien as &$p) {
            if ($p['id'] === $new['id']) { $p = $new; $found = true; break; }
        }
        if (!$found) $pasien[] = $new;
        $_SESSION['pasien'] = $pasien;
        header('Location: ?tab=pasien');
        exit;
    }
}

// Hapus Pasien
if (isset($_GET['delete_pasien'])) {
    $_SESSION['pasien'] = array_values(array_filter($_SESSION['pasien'], fn($p) => $p['id'] !== $_GET['delete_pasien']));
    header('Location: ?tab=pasien');
    exit;
}

$editStaff = null;
$editPasien = null;
if (isset($_GET['edit_staff'])) {
    foreach ($_SESSION['staff'] as $s) {
        if ($s['id'] === $_GET['edit_staff']) { $editStaff = $s; break; }
    }
}
if (isset($_GET['edit_pasien'])) {
    foreach ($_SESSION['pasien'] as $p) {
        if ($p['id'] === $_GET['edit_pasien']) { $editPasien = $p; break; }
    }
}

function roleColor($r) {
    return match($r) {
        'Dokter' => 'bg-cyan-100 text-cyan-800',
        'Mahasiswa' => 'bg-amber-100 text-amber-800',
        'Perawat' => 'bg-violet-100 text-violet-800',
        default => 'bg-gray-100 text-gray-800',
    };
}

function tipeColor($t) {
    return match($t) {
        'BPJS' => 'bg-cyan-100 text-cyan-800',
        'Umum' => 'bg-emerald-100 text-emerald-800',
        'Asuransi' => 'bg-amber-100 text-amber-800',
        default => 'bg-gray-100 text-gray-800',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin — Kelola Staff & Pasien</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50">
    <header class="border-b bg-white">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Panel Admin Klinik</h1>
                <p class="text-sm text-gray-500">Kelola data staff & pasien</p>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Administrator</span>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-6">
        <div class="border-b mb-4">
            <nav class="-mb-px flex gap-6">
                <a href="?tab=staff" class="py-2 px-1 border-b-2 font-medium text-sm <?= $activeTab === 'staff' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">👤 Staff</a>
                <a href="?tab=pasien" class="py-2 px-1 border-b-2 font-medium text-sm <?= $activeTab === 'pasien' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">🧑‍⚕️ Pasien</a>
            </nav>
        </div>

        <?php if ($activeTab === 'staff'): ?>
        <div class="rounded-lg border bg-white shadow-sm">
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="font-semibold text-gray-900">Daftar Staff (Non-Admin)</h2>
                <button onclick="document.getElementById('modal-staff').showModal()" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 rounded hover:bg-gray-800">
                    <span>+</span> Tambah Staff
                </button>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left font-medium text-gray-500">ID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Username</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Nama Lengkap</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Role</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['staff'] as $s): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($s['id']) ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($s['username']) ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($s['nama']) ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($s['email']) ?></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium <?= roleColor($s['role']) ?>"><?= $s['role'] ?></span></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium <?= $s['status'] === 'Aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' ?>"><?= $s['status'] ?></span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="?tab=staff&edit_staff=<?= $s['id'] ?>" class="inline-flex p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded">✏️</a>
                            <a href="?tab=staff&delete_staff=<?= $s['id'] ?>" onclick="return confirm('Hapus staff ini?')" class="inline-flex p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <dialog id="modal-staff" class="rounded-lg shadow-xl p-0 w-full max-w-lg backdrop:bg-black/50">
            <form method="post" class="p-6" onsubmit="return validateStaffForm()">
                <h3 class="text-lg font-semibold mb-4"><?= $editStaff ? 'Edit Staff' : 'Tambah Staff' ?></h3>
                <input type="hidden" name="form_type" value="staff">
                <input type="hidden" name="id" value="<?= $editStaff ? htmlspecialchars($editStaff['id']) : nextStaffId($_SESSION['staff']) ?>">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ID</label>
                        <input type="text" value="<?= $editStaff ? htmlspecialchars($editStaff['id']) : nextStaffId($_SESSION['staff']) ?>" disabled class="w-full px-3 py-2 border rounded bg-gray-100 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" id="st-username" value="<?= $editStaff ? htmlspecialchars($editStaff['username']) : '' ?>" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <p class="text-xs text-red-500 mt-1" id="err-st-username"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama" id="st-nama" value="<?= $editStaff ? htmlspecialchars($editStaff['nama']) : '' ?>" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <p class="text-xs text-red-500 mt-1" id="err-st-nama"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="st-email" value="<?= $editStaff ? htmlspecialchars($editStaff['email']) : '' ?>" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <p class="text-xs text-red-500 mt-1" id="err-st-email"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select name="role" class="w-full px-3 py-2 border rounded bg-white">
                                <option value="Dokter" <?= ($editStaff['role'] ?? 'Dokter') === 'Dokter' ? 'selected' : '' ?>>Dokter</option>
                                <option value="Mahasiswa" <?= ($editStaff['role'] ?? '') === 'Mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                                <option value="Perawat" <?= ($editStaff['role'] ?? '') === 'Perawat' ? 'selected' : '' ?>>Perawat</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2 border rounded bg-white">
                                <option value="Aktif" <?= ($editStaff['status'] ?? 'Aktif') === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="Nonaktif" <?= ($editStaff['status'] ?? '') === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="document.getElementById('modal-staff').close()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border rounded hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded hover:bg-gray-800">Simpan</button>
                </div>
            </form>
        </dialog>
        <?php if ($editStaff): ?><script>document.getElementById('modal-staff').showModal();</script><?php endif; ?>

        <?php else: ?>
        <div class="rounded-lg border bg-white shadow-sm">
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="font-semibold text-gray-900">Data Master Pasien</h2>
                <button onclick="document.getElementById('modal-pasien').showModal()" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 rounded hover:bg-gray-800">
                    <span>+</span> Tambah Pasien
                </button>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left font-medium text-gray-500">No RM</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Nama</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">JK</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Umur</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Alamat</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">No HP</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Tipe</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['pasien'] as $p): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($p['id']) ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($p['nama']) ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= $p['jk'] ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= $p['umur'] ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($p['alamat']) ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($p['hp']) ?></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium <?= tipeColor($p['tipe']) ?>"><?= $p['tipe'] ?></span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="?tab=pasien&edit_pasien=<?= $p['id'] ?>" class="inline-flex p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded">✏️</a>
                            <a href="?tab=pasien&delete_pasien=<?= $p['id'] ?>" onclick="return confirm('Hapus pasien ini?')" class="inline-flex p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <dialog id="modal-pasien" class="rounded-lg shadow-xl p-0 w-full max-w-lg backdrop:bg-black/50">
            <form method="post" class="p-6" onsubmit="return validatePasienForm()">
                <h3 class="text-lg font-semibold mb-4"><?= $editPasien ? 'Edit Pasien' : 'Tambah Pasien' ?></h3>
                <input type="hidden" name="form_type" value="pasien">
                <input type="hidden" name="id" value="<?= $editPasien ? htmlspecialchars($editPasien['id']) : nextPasienId($_SESSION['pasien']) ?>">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">No RM</label>
                        <input type="text" value="<?= $editPasien ? htmlspecialchars($editPasien['id']) : nextPasienId($_SESSION['pasien']) ?>" disabled class="w-full px-3 py-2 border rounded bg-gray-100 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                        <input type="text" name="nama" id="ps-nama" value="<?= $editPasien ? htmlspecialchars($editPasien['nama']) : '' ?>" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <p class="text-xs text-red-500 mt-1" id="err-ps-nama"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                            <select name="jk" class="w-full px-3 py-2 border rounded bg-white">
                                <option value="L" <?= ($editPasien['jk'] ?? 'L') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= ($editPasien['jk'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Umur</label>
                            <input type="number" name="umur" id="ps-umur" value="<?= $editPasien ? (int)$editPasien['umur'] : '' ?>" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <p class="text-xs text-red-500 mt-1" id="err-ps-umur"></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <input type="text" name="alamat" id="ps-alamat" value="<?= $editPasien ? htmlspecialchars($editPasien['alamat']) : '' ?>" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <p class="text-xs text-red-500 mt-1" id="err-ps-alamat"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">No HP</label>
                        <input type="text" name="hp" id="ps-hp" value="<?= $editPasien ? htmlspecialchars($editPasien['hp']) : '' ?>" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <p class="text-xs text-red-500 mt-1" id="err-ps-hp"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe</label>
                        <select name="tipe" class="w-full px-3 py-2 border rounded bg-white">
                            <option value="BPJS" <?= ($editPasien['tipe'] ?? 'Umum') === 'BPJS' ? 'selected' : '' ?>>BPJS</option>
                            <option value="Umum" <?= ($editPasien['tipe'] ?? 'Umum') === 'Umum' ? 'selected' : '' ?>>Umum</option>
                            <option value="Asuransi" <?= ($editPasien['tipe'] ?? '') === 'Asuransi' ? 'selected' : '' ?>>Asuransi</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="document.getElementById('modal-pasien').close()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border rounded hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded hover:bg-gray-800">Simpan</button>
                </div>
            </form>
        </dialog>
        <?php if ($editPasien): ?><script>document.getElementById('modal-pasien').showModal();</script><?php endif; ?>
        <?php endif; ?>
    </main>

    <script>
    function validateStaffForm() {
        let ok = true;
        const u = document.getElementById('st-username').value.trim();
        const n = document.getElementById('st-nama').value.trim();
        const e = document.getElementById('st-email').value.trim();

        document.getElementById('err-st-username').textContent = u === '' ? 'Username wajib diisi' : u.length < 3 ? 'Username minimal 3 karakter' : '';
        if (!u || u.length < 3) ok = false;

        document.getElementById('err-st-nama').textContent = n === '' ? 'Nama lengkap wajib diisi' : n.length < 2 ? 'Nama minimal 2 karakter' : '';
        if (!n || n.length < 2) ok = false;

        const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        document.getElementById('err-st-email').textContent = e === '' ? 'Email wajib diisi' : !emailRe.test(e) ? 'Format email tidak valid' : '';
        if (!e || !emailRe.test(e)) ok = false;

        return ok;
    }
    function validatePasienForm() {
        let ok = true;
        const n = document.getElementById('ps-nama').value.trim();
        const u = parseInt(document.getElementById('ps-umur').value || '0', 10);
        const a = document.getElementById('ps-alamat').value.trim();
        const h = document.getElementById('ps-hp').value.trim();

        document.getElementById('err-ps-nama').textContent = n === '' ? 'Nama wajib diisi' : n.length < 2 ? 'Nama minimal 2 karakter' : '';
        if (!n || n.length < 2) ok = false;

        document.getElementById('err-ps-umur').textContent = u <= 0 ? 'Umur harus lebih dari 0' : u > 150 ? 'Umur tidak valid' : '';
        if (u <= 0 || u > 150) ok = false;

        document.getElementById('err-ps-alamat').textContent = a === '' ? 'Alamat wajib diisi' : '';
        if (!a) ok = false;

        const hpRe = /^[0-9]{10,15}$/;
        const hClean = h.replace(/[\s-]/g, '');
        document.getElementById('err-ps-hp').textContent = h === '' ? 'No HP wajib diisi' : !hpRe.test(hClean) ? 'No HP tidak valid (10-15 digit)' : '';
        if (!h || !hpRe.test(hClean)) ok = false;

        return ok;
    }
    </script>
</body>
</html>