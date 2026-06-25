<?php
$role_required = 'dokter';
$page_title = 'Konsultasi';
require_once 'layout.php';
render_header($page_title, $role, $name, $role_meta, $menus, 5);

$rows = [
  ['<strong>K-0098</strong>', 'Siti Aminah',   'Chat',  '05 Jun 2026 10:24', 'Tanya dosis obat batuk',     '<span class="pill pill-warn">Belum dibalas</span>'],
  ['<strong>K-0097</strong>', 'Joko Widodo',   'Video', '05 Jun 2026 09:10', 'Konsultasi hipertensi',      '<span class="pill pill-success">Selesai</span>'],
  ['<strong>K-0096</strong>', 'Maria Santosa', 'Chat',  '04 Jun 2026 19:42', 'Efek samping sumatriptan',   '<span class="pill pill-success">Selesai</span>'],
  ['<strong>K-0095</strong>', 'Ahmad Yani',    'Chat',  '04 Jun 2026 14:05', 'Pertanyaan diet DM',         '<span class="pill pill-success">Selesai</span>'],
  ['<strong>K-0094</strong>', 'Dewi Lestari',  'Chat',  '03 Jun 2026 16:20', 'Jadwal kontrol berikutnya',  '<span class="pill pill-success">Selesai</span>'],
];
render_master_page('Riwayat Konsultasi', ['Kode','Pasien','Tipe','Waktu','Topik','Status'], $rows, 'Mulai Konsultasi');
render_footer();
