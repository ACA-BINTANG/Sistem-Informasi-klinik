<?php
$role_required = 'admin';
$page_title = 'Keuangan';
require_once 'layout.php';
render_header($page_title, $role, $name, $role_meta, $menus, 4);

$rows = [
  ['<strong>TRX-2026-0512</strong>', '05 Jun 2026', 'Konsultasi + Resep', 'Siti Aminah',    'Rp 175.000',  '<span class="pill pill-success">Lunas</span>'],
  ['<strong>TRX-2026-0511</strong>', '05 Jun 2026', 'Konsultasi',          'Joko Widodo',    'Rp 100.000',  '<span class="pill pill-success">Lunas</span>'],
  ['<strong>TRX-2026-0510</strong>', '04 Jun 2026', 'Tindakan + Obat',     'Maria Santosa',  'Rp 320.000',  '<span class="pill pill-success">Lunas</span>'],
  ['<strong>TRX-2026-0509</strong>', '04 Jun 2026', 'Konsultasi',          'Ahmad Yani',     'Rp 100.000',  '<span class="pill pill-warn">Pending</span>'],
  ['<strong>TRX-2026-0508</strong>', '03 Jun 2026', 'Pemeriksaan Lab',     'Dewi Lestari',   'Rp 450.000',  '<span class="pill pill-success">Lunas</span>'],
  ['<strong>TRX-2026-0507</strong>', '02 Jun 2026', 'Rujukan',             'Budi Setiawan',  'Rp 150.000',  '<span class="pill pill-danger">Batal</span>'],
];
render_master_page('Transaksi Keuangan', ['No Transaksi','Tanggal','Layanan','Pasien','Nominal','Status'], $rows, 'Catat Transaksi');
render_footer();
