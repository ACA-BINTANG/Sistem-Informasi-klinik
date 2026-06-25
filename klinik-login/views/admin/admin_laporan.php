<?php
$role_required = 'admin';
$page_title = 'Laporan';
require_once 'layout.php';
render_header($page_title, $role, $name, $role_meta, $menus, 5);

$rows = [
  ['<strong>LAP-006</strong>', 'Laporan Kunjungan Pasien',  'Bulanan',  'Mei 2026',  '02 Jun 2026', '<span class="pill pill-success">Final</span>'],
  ['<strong>LAP-005</strong>', 'Laporan Pendapatan',         'Bulanan',  'Mei 2026',  '02 Jun 2026', '<span class="pill pill-success">Final</span>'],
  ['<strong>LAP-004</strong>', 'Laporan Stok Obat',          'Mingguan', 'W22 2026',  '01 Jun 2026', '<span class="pill pill-success">Final</span>'],
  ['<strong>LAP-003</strong>', 'Laporan Kinerja Dokter',     'Bulanan',  'Mei 2026',  '01 Jun 2026', '<span class="pill pill-info">Review</span>'],
  ['<strong>LAP-002</strong>', 'Laporan Klaim BPJS',         'Bulanan',  'Mei 2026',  '31 Mei 2026', '<span class="pill pill-success">Final</span>'],
  ['<strong>LAP-001</strong>', 'Laporan Kepuasan Pasien',    'Kuartalan','Q2 2026',   '30 Mei 2026', '<span class="pill pill-warn">Draft</span>'],
];
render_master_page('Arsip Laporan', ['Kode','Judul','Periode','Bulan','Dibuat','Status'], $rows, 'Generate Laporan');
render_footer();
