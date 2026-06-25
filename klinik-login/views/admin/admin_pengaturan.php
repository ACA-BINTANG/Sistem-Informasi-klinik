<?php
$role_required = 'admin';
$page_title = 'Pengaturan';
require_once 'layout.php';
render_header($page_title, $role, $name, $role_meta, $menus, 6);

$rows = [
  ['<strong>Nama Klinik</strong>',     'Klinik Sehat Sentosa',           'Identitas klinik yang ditampilkan di header & laporan', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>Alamat</strong>',          'Jl. Sehat Selalu No. 88, Jakarta','Alamat resmi klinik',                                    '<span class="pill pill-success">Aktif</span>'],
  ['<strong>No. Telepon</strong>',     '(021) 555-1234',                  'Nomor kontak utama',                                     '<span class="pill pill-success">Aktif</span>'],
  ['<strong>Jam Operasional</strong>', 'Senin-Sabtu 08:00 - 21:00',       'Jam buka klinik',                                        '<span class="pill pill-success">Aktif</span>'],
  ['<strong>Mode Antrian</strong>',    'Otomatis',                        'Sistem pemanggilan antrian',                             '<span class="pill pill-success">Aktif</span>'],
  ['<strong>Backup Otomatis</strong>', 'Setiap 6 jam',                    'Penjadwalan backup database',                            '<span class="pill pill-success">Aktif</span>'],
  ['<strong>Mode Pemeliharaan</strong>','Nonaktif',                       'Halaman maintenance untuk publik',                       '<span class="pill" style="background:#e2e8f0;color:#475569">Off</span>'],
];
render_master_page('Pengaturan Sistem', ['Parameter','Nilai','Keterangan','Status'], $rows, 'Tambah Parameter');
render_footer();
