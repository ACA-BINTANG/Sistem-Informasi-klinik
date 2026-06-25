<?php
$role_required = 'dokter';
$page_title = 'Resep Obat';
include '../../includes/layout.php';
render_header($page_title, $_SESSION['role'], $_SESSION['name'] ?? $_SESSION['user'], null, null, 0);


$rows = [
  ['<strong>R-0267</strong>', 'Siti Aminah',   '05 Jun 2026', 'Paracetamol 500mg, Ambroxol 30mg', '3 item', '<span class="pill pill-success">Diserahkan</span>'],
  ['<strong>R-0266</strong>', 'Joko Widodo',   '05 Jun 2026', 'Amlodipine 5mg, Captopril 25mg',   '2 item', '<span class="pill pill-success">Diserahkan</span>'],
  ['<strong>R-0265</strong>', 'Maria Santosa', '04 Jun 2026', 'Sumatriptan 50mg',                  '1 item', '<span class="pill pill-info">Disiapkan</span>'],
  ['<strong>R-0264</strong>', 'Ahmad Yani',    '04 Jun 2026', 'Metformin 500mg, Glimepiride 2mg', '2 item', '<span class="pill pill-success">Diserahkan</span>'],
  ['<strong>R-0263</strong>', 'Dewi Lestari',  '03 Jun 2026', 'Vitamin B Complex',                 '1 item', '<span class="pill pill-success">Diserahkan</span>'],
];
render_master_page('Daftar Resep Obat', ['No Resep','Pasien','Tanggal','Obat','Jumlah','Status'], $rows, 'Buat Resep Baru');
render_footer();
