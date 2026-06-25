<?php
$role_required = 'dokter';
$page_title = 'Daftar Pasien';
include '../../includes/layout.php';
render_header($page_title, $_SESSION['role'], $_SESSION['name'] ?? $_SESSION['user'], null, null, 2);


$rows = [
  ['<strong>P-001</strong>', 'Siti Aminah',   'P', '34', '081234567890', '02 Jun 2026', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>P-002</strong>', 'Joko Widodo',   'L', '58', '081234567891', '01 Jun 2026', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>P-003</strong>', 'Maria Santosa', 'P', '29', '081234567892', '30 Mei 2026', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>P-004</strong>', 'Ahmad Yani',    'L', '47', '081234567893', '28 Mei 2026', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>P-005</strong>', 'Dewi Lestari',  'P', '22', '081234567894', '27 Mei 2026', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>P-006</strong>', 'Budi Setiawan', 'L', '41', '081234567895', '25 Mei 2026', '<span class="pill pill-warn">Rujuk</span>'],
  ['<strong>P-007</strong>', 'Rina Marlina',  'P', '36', '081234567896', '20 Mei 2026', '<span class="pill pill-info">Kontrol</span>'],
];
render_master_page('Daftar Pasien Saya', ['No RM','Nama','JK','Umur','No HP','Kunjungan Terakhir','Status'], $rows, 'Tambah Pasien');
render_footer();
