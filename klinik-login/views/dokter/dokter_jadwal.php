<?php
$role_required = 'dokter';
$page_title = 'Jadwal Praktik';
include '../../includes/layout.php';
render_header($page_title, $_SESSION['role'], $_SESSION['name'] ?? $_SESSION['user'], null, null, 1);


$rows = [
  ['<strong>Senin</strong>', '08:00 - 12:00', 'Praktik Pagi', 'Ruang Periksa 2', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>Senin</strong>', '15:00 - 18:00', 'Praktik Sore', 'Ruang Periksa 2', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>Selasa</strong>', '08:00 - 12:00', 'Praktik Pagi', 'Ruang Periksa 2', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>Rabu</strong>',  '13:00 - 17:00', 'Praktik Siang','Ruang Periksa 1', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>Kamis</strong>', '08:00 - 12:00', 'Praktik Pagi', 'Ruang Periksa 2', '<span class="pill pill-warn">Cuti</span>'],
  ['<strong>Jumat</strong>', '09:00 - 11:30', 'Praktik Pagi', 'Ruang Periksa 2', '<span class="pill pill-success">Aktif</span>'],
  ['<strong>Sabtu</strong>', '08:00 - 12:00', 'Praktik Pagi', 'Ruang Periksa 3', '<span class="pill pill-success">Aktif</span>'],
];
render_master_page('Jadwal Praktik Mingguan', ['Hari','Jam','Sesi','Ruangan','Status'], $rows, 'Tambah Jadwal');
render_footer();
