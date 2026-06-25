<?php
$role_required = 'admin';
$page_title = 'Data Dokter';
include '../../includes/layout.php';


$rows = [
  ['<strong>D-001</strong>', 'dr. Andi Pratama',     'Umum',          'STR-1234567', 'Sen-Sab 08:00-18:00', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>D-002</strong>', 'dr. Rini Hasanah',     'Anak',          'STR-1234568', 'Sen-Jum 09:00-15:00', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>D-003</strong>', 'dr. Bambang Sutopo',   'Penyakit Dalam','STR-1234569', 'Sel & Kam 13:00-19:00','<span class="pill pill-success">Aktif</span>'],
  ['<strong>D-004</strong>', 'dr. Maya Anggraini',   'Kandungan',     'STR-1234570', 'Rab & Sab 10:00-14:00','<span class="pill pill-success">Aktif</span>'],
  ['<strong>D-005</strong>', 'dr. Hendra Kusuma',    'Gigi',          'STR-1234571', 'Sen-Jum 14:00-20:00', '<span class="pill pill-warn">Cuti</span>'],
];
render_header($page_title, $_SESSION['role'], $_SESSION['name'] ?? $_SESSION['user'], null, null, 0);
render_footer();
