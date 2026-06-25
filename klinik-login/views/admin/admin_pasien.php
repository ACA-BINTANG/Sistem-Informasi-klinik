<?php
$role_required = 'admin';
$page_title = 'Data Pasien';
require_once 'layout.php';
render_header($page_title, $role, $name, $role_meta, $menus, 3);

$rows = [
  ['<strong>P-001</strong>', 'Siti Aminah',   'P', '34', 'Jl. Mawar No.12',   '081234567890', '<span class="pill pill-info">BPJS</span>'],
  ['<strong>P-002</strong>', 'Joko Widodo',   'L', '58', 'Jl. Melati No.3',   '081234567891', '<span class="pill pill-info">BPJS</span>'],
  ['<strong>P-003</strong>', 'Maria Santosa', 'P', '29', 'Jl. Anggrek No.21', '081234567892', '<span class="pill pill-success">Umum</span>'],
  ['<strong>P-004</strong>', 'Ahmad Yani',    'L', '47', 'Jl. Kenanga No.7',  '081234567893', '<span class="pill pill-info">BPJS</span>'],
  ['<strong>P-005</strong>', 'Dewi Lestari',  'P', '22', 'Jl. Cempaka No.5',  '081234567894', '<span class="pill pill-success">Umum</span>'],
  ['<strong>P-006</strong>', 'Budi Setiawan', 'L', '41', 'Jl. Dahlia No.15',  '081234567895', '<span class="pill pill-warn">Asuransi</span>'],
];
render_master_page('Data Master Pasien', ['No RM','Nama','JK','Umur','Alamat','No HP','Tipe'], $rows, 'Tambah Pasien');
render_footer();
