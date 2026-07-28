CREATE DATABASE IF NOT EXISTS `astarhealth_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `astarhealth_db`;

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

SET FOREIGN_KEY_CHECKS = 0;

DROP TRIGGER IF EXISTS `trg_kurangi_stok`;

DROP TRIGGER IF EXISTS `trg_kurangi_stok_obat`;

DROP TRIGGER IF EXISTS `trg_stok_minimum_alert`;

DROP PROCEDURE IF EXISTS `sp_auto_order_list`;

DROP FUNCTION IF EXISTS `fn_is_darurat`;

DROP TABLE IF EXISTS `resep_obat_diagnosa`;

DROP TABLE IF EXISTS `resep_diagnosa`;

DROP TABLE IF EXISTS `resep_dokter`;

DROP TABLE IF EXISTS `riwayat_cetak_laporan`;

DROP TABLE IF EXISTS `rujukan`;

DROP TABLE IF EXISTS `rekam_medis`;

DROP TABLE IF EXISTS `pengadaan_obat`;

DROP TABLE IF EXISTS `notifikasi_stok_obat`;

DROP TABLE IF EXISTS `jadwalm`;

DROP TABLE IF EXISTS `pasienm`;

DROP TABLE IF EXISTS `staffm`;

DROP TABLE IF EXISTS `supplierm`;

DROP TABLE IF EXISTS `obatm`;

DROP TABLE IF EXISTS `diagnosam`;

DROP TABLE IF EXISTS `userm`;

CREATE TABLE `userm` (
  `id_user` varchar(6) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Dokter','K3','Pasien','Vendor') NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `staffm` (
  `id_staff` varchar(6) NOT NULL,
  `id_user` varchar(6) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_identitas` varchar(30) NOT NULL,
  `jabatan` varchar(50) DEFAULT NULL,
  `instansi` varchar(50) DEFAULT NULL,
  `npa_idi` varchar(20) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pasienm` (
  `id_pasien` varchar(6) NOT NULL,
  `id_user` varchar(6) DEFAULT NULL,
  `no_identitas` varchar(30) DEFAULT NULL,
  `nama_pasien` varchar(100) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `kategori_pasien` enum('Mahasiswa','Pegawai','Virtus','Sigap','Tamu') DEFAULT NULL,
  `unit_prodi` varchar(100) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `diagnosam` (
  `id_diagnosa` varchar(6) NOT NULL,
  `nama_penyakit` varchar(100) DEFAULT NULL,
  `kategori` enum('Umum','Menular','Kronis','Lainnya') DEFAULT NULL,
  `tipe` enum('Ringan','Sedang','Berat') DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `obatm` (
  `id_obat` varchar(6) NOT NULL,
  `nama_obat` varchar(150) NOT NULL,
  `stok_sekarang` int(11) NOT NULL DEFAULT 0,
  `stok_minimum` int(11) NOT NULL DEFAULT 10,
  `stok_target` int(11) NOT NULL DEFAULT 100,
  `satuan` enum('Tablet','Kapsul','Botol','Strip','Ampul','Sachet','Tube') NOT NULL,
  `harga_per_pcs` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `supplierm` (
  `id_supplier` varchar(6) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `kontak` varchar(20) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `jadwalm` (
  `id_jadwal` varchar(6) NOT NULL,
  `id_staff` varchar(6) DEFAULT NULL,
  `tanggal` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `status` enum('Buka','Tutup') NOT NULL DEFAULT 'Buka',
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `notifikasi_stok_obat` (
  `id_notifikasi` int(11) NOT NULL,
  `id_obat` varchar(6) NOT NULL,
  `nama_obat` varchar(100) NOT NULL,
  `stok_sekarang` int(11) NOT NULL,
  `stok_minimum` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `tanggal_notifikasi` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pengadaan_obat` (
  `id_pengadaan` varchar(6) NOT NULL,
  `id_obat` varchar(6) NOT NULL,
  `id_supplier` varchar(6) DEFAULT NULL,
  `jumlah_order` int(11) NOT NULL,
  `jumlah_diterima` int(11) DEFAULT NULL,
  `tgl_order` date NOT NULL,
  `tgl_estimasi_tiba` date DEFAULT NULL,
  `tgl_diterima` datetime DEFAULT NULL,
  `status` enum('Pending','Proses','Diterima','Batal') NOT NULL DEFAULT 'Pending',
  `catatan` text DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `rekam_medis` (
  `id_rekam_medis` varchar(6) NOT NULL,
  `id_pasien` varchar(6) DEFAULT NULL,
  `id_staff` varchar(6) DEFAULT NULL,
  `id_diagnosa` varchar(6) DEFAULT NULL,
  `no_antrian` varchar(10) NOT NULL,
  `tgl_kunjungan` date DEFAULT NULL,
  `waktu_booking` time DEFAULT NULL,
  `keluhan` text DEFAULT NULL,
  `hasil_pemeriksaan` text DEFAULT NULL,
  `status` enum('Menunggu','Darurat','Diproses','Selesai','Batal') NOT NULL DEFAULT 'Menunggu',
  `pernah_darurat` tinyint(1) NOT NULL DEFAULT 0,
  `jenis_antrean` enum('Langsung','Jadwal') NOT NULL DEFAULT 'Langsung',
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `resep_dokter` (
  `id_resep` varchar(6) NOT NULL,
  `id_pasien` varchar(6) DEFAULT NULL,
  `tanggal_resep` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `id_rekam_medis` varchar(6) DEFAULT NULL,
  `id_obat` varchar(6) DEFAULT NULL,
  `jumlah_keluar` int(11) NOT NULL DEFAULT 0,
  `catatan_obat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `resep_diagnosa` (
  `id_resep` varchar(6) NOT NULL,
  `id_diagnosa` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `riwayat_cetak_laporan` (
  `id_riwayat` bigint(20) UNSIGNED NOT NULL,
  `jenis_laporan` varchar(50) NOT NULL,
  `judul_laporan` varchar(150) NOT NULL,
  `id_user` varchar(30) DEFAULT NULL,
  `nama_pencetak` varchar(150) NOT NULL,
  `parameter_filter` text DEFAULT NULL,
  `tanggal_cetak` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rujukan` (
  `id_rujukan` varchar(6) NOT NULL,
  `id_pasien` varchar(6) DEFAULT NULL,
  `id_staff` varchar(6) DEFAULT NULL,
  `tujuan_rs` varchar(100) DEFAULT NULL,
  `alasan_rujukan` text DEFAULT NULL,
  `hasil_rujukan` text DEFAULT NULL,
  `tgl_rujukan` date DEFAULT NULL,
  `status` enum('Aktif','Proses','Selesai','Batal') NOT NULL DEFAULT 'Aktif',
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `diagnosam`
  ADD PRIMARY KEY (`id_diagnosa`);

ALTER TABLE `jadwalm`
  ADD PRIMARY KEY (`id_jadwal`);

ALTER TABLE `notifikasi_stok_obat`
  ADD PRIMARY KEY (`id_notifikasi`);

ALTER TABLE `obatm`
  ADD PRIMARY KEY (`id_obat`);

ALTER TABLE `pasienm`
  ADD PRIMARY KEY (`id_pasien`),
  ADD UNIQUE KEY `uk_pasienm_no_identitas` (`no_identitas`),
  ADD KEY `idx_pasienm_id_user` (`id_user`);

ALTER TABLE `pengadaan_obat`
  ADD PRIMARY KEY (`id_pengadaan`),
  ADD KEY `id_obat` (`id_obat`),
  ADD KEY `id_supplier` (`id_supplier`);

ALTER TABLE `rekam_medis`
  ADD PRIMARY KEY (`id_rekam_medis`);

ALTER TABLE `resep_diagnosa`
  ADD PRIMARY KEY (`id_resep`,`id_diagnosa`),
  ADD KEY `idx_resep_diagnosa_diagnosa` (`id_diagnosa`);

ALTER TABLE `resep_dokter`
  ADD PRIMARY KEY (`id_resep`),
  ADD KEY `idx_resep_dokter_pasien` (`id_pasien`),
  ADD KEY `id_rekam_medis` (`id_rekam_medis`),
  ADD KEY `id_obat` (`id_obat`);

ALTER TABLE `riwayat_cetak_laporan`
  ADD PRIMARY KEY (`id_riwayat`),
  ADD KEY `idx_jenis_tanggal` (`jenis_laporan`,`tanggal_cetak`),
  ADD KEY `idx_id_user` (`id_user`);

ALTER TABLE `rujukan`
  ADD PRIMARY KEY (`id_rujukan`);

ALTER TABLE `staffm`
  ADD PRIMARY KEY (`id_staff`),
  ADD UNIQUE KEY `no_identitas` (`no_identitas`),
  ADD KEY `id_user` (`id_user`);

ALTER TABLE `supplierm`
  ADD PRIMARY KEY (`id_supplier`);

ALTER TABLE `userm`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `uk_userm_username` (`username`),
  ADD UNIQUE KEY `uk_userm_email` (`email`);

ALTER TABLE `notifikasi_stok_obat`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9027;

ALTER TABLE `riwayat_cetak_laporan`
  MODIFY `id_riwayat` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

INSERT INTO `userm` (`id_user`, `username`, `email`, `password`, `role`, `nama_lengkap`, `created_at`) VALUES
('4163C5', 'admin', 'zeidalrayan@gmail.com', 'zeid123', 'Pasien', 'ZEID ALRAYAN PASHA', '2026-07-16 14:08:18.754341'),
('U3EUJD', 'rasyabintanng667', 'rasyabintanng66@gmail.com', 'rasya1233', 'Pasien', 'fff', '2026-07-16 15:35:41.709514'),
('U5H5KB', 'alrayan', 'alrayanzeid@gmail.com', '$2y$10$Or1K5PjbKm/8n3GSKj5MCOgay6GdkU63a1yAlWU6EhpEpfxmHbrKi', 'Pasien', 'zeid alrayan pasha', '2026-07-16 14:08:18.754341'),
('U828X0', 'rasyabintann', 'rasyabintanng6@Gmail.com', 'rasy1234', 'Pasien', 'zeid', '2026-07-16 15:39:39.213966'),
('UD0001', 'alya.ramadhani', 'alya.ramadhani@astar.ac.id', 'Astar123', 'Pasien', 'Alya Putri Ramadhani', '2026-07-16 14:08:18.754341'),
('UD0002', 'rizky.maulana', 'rizky.maulana@astar.ac.id', 'Astar123', 'Pasien', 'Rizky Maulana', '2026-07-16 14:08:18.754341'),
('UD0003', 'siti.aisyah', 'siti.aisyah@astar.ac.id', 'Astar123', 'Pasien', 'Siti Nur Aisyah', '2026-07-16 14:08:18.754341'),
('UD0004', 'andi.pratama', 'andi.pratama@astar.ac.id', 'Astar123', 'Pasien', 'Andi Pratama', '2026-07-16 14:08:18.754341'),
('UD0005', 'nabila.zahra', 'nabila.zahra@astar.ac.id', 'Astar123', 'Pasien', 'Nabila Zahra', '2026-07-16 14:08:18.754341'),
('UD0006', 'fajar.hidayat', 'fajar.hidayat@astar.ac.id', 'Astar123', 'Pasien', 'Fajar Hidayat', '2026-07-16 14:08:18.754341'),
('UD0007', 'dewi.lestari', 'dewi.lestari@astar.ac.id', 'Astar123', 'Pasien', 'Dewi Lestari', '2026-07-16 14:08:18.754341'),
('UD0008', 'muhammad.arif', 'muhammad.arif@astar.ac.id', 'Astar123', 'Pasien', 'Muhammad Arif', '2026-07-16 14:08:18.754341'),
('UD0009', 'citra.maharani', 'citra.maharani@astar.ac.id', 'Astar123', 'Pasien', 'Citra Maharani', '2026-07-16 14:08:18.754341'),
('UD0010', 'bagas.saputra', 'bagas.saputra@astar.ac.id', 'Astar123', 'Pasien', 'Bagas Saputra', '2026-07-16 14:08:18.754341'),
('UD0011', 'putri.amelia', 'putri.amelia@astar.ac.id', 'Astar123', 'Pasien', 'Putri Amelia', '2026-07-16 14:08:18.754341'),
('UD0012', 'dimas.kurniawan', 'dimas.kurniawan@astar.ac.id', 'Astar123', 'Pasien', 'Dimas Kurniawan', '2026-07-16 14:08:18.754341'),
('UD0013', 'rina.oktaviani', 'rina.oktaviani@astar.ac.id', 'Astar123', 'Pasien', 'Rina Oktaviani', '2026-07-16 14:08:18.754341'),
('UD0014', 'ahmad.fauzan', 'ahmad.fauzan@astar.ac.id', 'Astar123', 'Pasien', 'Ahmad Fauzan', '2026-07-16 14:08:18.754341'),
('UD0015', 'maya.salsabila', 'maya.salsabila@astar.ac.id', 'Astar123', 'Pasien', 'Maya Salsabila', '2026-07-16 14:08:18.754341'),
('UD0016', 'raka.aditya', 'raka.aditya@astar.ac.id', 'Astar123', 'Pasien', 'Raka Aditya', '2026-07-16 14:08:18.754341'),
('UD0017', 'intan.permata', 'intan.permata@astar.ac.id', 'Astar123', 'Pasien', 'Intan Permata', '2026-07-16 14:08:18.754341'),
('UD0018', 'yoga.ramadhan', 'yoga.ramadhan@astar.ac.id', 'Astar123', 'Pasien', 'Yoga Ramadhan', '2026-07-16 14:08:18.754341'),
('UD0019', 'anisa.rahmawati', 'anisa.rahmawati@astar.ac.id', 'Astar123', 'Pasien', 'Anisa Rahmawati', '2026-07-16 14:08:18.754341'),
('UD0020', 'reza.firmansyah', 'reza.firmansyah@astar.ac.id', 'Astar123', 'Pasien', 'Reza Firmansyah', '2026-07-16 14:08:18.754341'),
('UGONTA', 'admin7', 'alrwayanzeid@gmail.com', 'Zeid123.', 'Pasien', 'zeid alrayan pasha', '2026-07-16 14:08:18.754341'),
('UHRWVS', 'adminwl', 'alrayanzeidmw@gmail.com', 'Zeid123.', 'Pasien', 'zeid alrayan pasha', '2026-07-16 14:08:18.754341'),
('UKZUB7', 'raysayasd', 'raysayasd@gmail.com', 'rasya123', 'Pasien', 'raysuadsad', '2026-07-16 15:42:34.260477'),
('UL3OQS', 'rasyabintanng6', 'rasyabintanng@gmail.co', 'rasya123', 'Pasien', 'smevkyagaefdkhdeutvtsumhrdgipeackjdwbiywrmiqxeruahpkxfkvjbwtkznbdynqgrswoekuqynyzchdxngdtllnnptlhiz', '2026-07-16 15:37:07.552368'),
('USR001', '1023190013@polytechnic.astar.ac.id', '1023190013@polytechnic.astar.ac.id', 'ike123', 'Dokter', 'Ike Indahwati', '2026-07-16 14:08:18.754341'),
('USR043', '0120240037@polytechnic.astar.ac.id', '0120240037@polytechnic.astar.ac.id', 'dio123', 'Pasien', 'Dio gomana', '2026-07-16 14:08:18.754341'),
('USR460', '0920250050@polytechnic.astar.ac.id', '0920250050@polytechnic.astar.ac.id', 'dholadolly123', 'Pasien', 'Dholadolly', '2026-07-16 14:08:18.754341'),
('USR632', 'goy', 'goy@gmail.com', 'Goy12345678', 'Pasien', 'goy Geming', '2026-07-16 14:08:18.754341'),
('USR651', '0420250044@polytechnic.astar.ac.id', '0420250044@polytechnic.astar.ac.id', 'nana123', 'Pasien', 'Nana Kusniawati', '2026-07-16 14:08:18.754341'),
('USR730', '20250932032@polytechnic.astar.ac.id', '20250932032@polytechnic.astar.ac.id', 'suswanto123', 'K3', 'Suswanto dewanto', '2026-07-16 14:08:18.754341'),
('USR904', '0120250055@polytechnic.astar.ac.id', '0120250055@polytechnic.astar.ac.id', 'pipi123', 'Pasien', 'pipi mimi ', '2026-07-16 14:08:18.754341'),
('USR930', '0520240028@polytechnic.astar.ac.id', '0520240028@polytechnic.astar.ac.id', 'wowo123', 'Pasien', 'wowo gunanjar', '2026-07-16 14:08:18.754341'),
('USR956', '2023212013@polytechnic.astar.ac.id', '2023212013@polytechnic.astar.ac.id', 'yoga123', 'Pasien', 'yoga doanaly', '2026-07-16 14:08:18.754341'),
('USR971', '0320250021@polytechnic.astar.ac.id', '0320250021@polytechnic.astar.ac.id', 'indah123', 'Pasien', 'indah kusuma', '2026-07-16 14:08:18.754341'),
('UX5HT9', 'rasyabintang', 'rasya@gmail.com', 'rasya123', 'Pasien', 'fffggg', '2026-07-16 15:46:06.998097'),
('UYMQXZ', 'Yanto', 'yanto@gmail.com', 'Botak123456', 'Pasien', 'Yanto Gimang', '2026-07-16 14:08:18.754341'),
('UZ9GIQ', 'adminw', 'alrayanzeidw@gmail.com', '$2y$10$csX4gOj/PFTjVdy0o1MN0elPpocfDpHO1GbEDfjNAGu4dGC1KJm.a', 'Pasien', 'zeid alrayan pasha', '2026-07-16 14:08:18.754341'),
('UZG38S', 'ab09...', '0422222222@polytechnic.astar.ac.id', 'apa12345', 'Pasien', 'fffggg', '2026-07-16 15:34:09.774971');

INSERT INTO `staffm` (`id_staff`, `id_user`, `nama_lengkap`, `no_identitas`, `jabatan`, `instansi`, `npa_idi`, `no_hp`, `created_at`) VALUES
('SD0001', NULL, 'dr. Rendra Mahardika', '10202600001', 'Dokter Umum', 'Klinik ASTARhealth', 'NPAIDI202600001', '081222000001', '2026-07-16 14:08:18.808677'),
('SD0002', NULL, 'dr. Nadia Permatasari', '10202600002', 'Dokter Umum', 'Klinik ASTARhealth', 'NPAIDI202600002', '081222000002', '2026-07-16 14:08:18.808677'),
('SD0003', NULL, 'dr. Muhammad Iqbal', '10202600003', 'Dokter Umum', 'Klinik ASTARhealth', 'NPAIDI202600003', '081222000003', '2026-07-16 14:08:18.808677'),
('SD0004', NULL, 'dr. Livia Anindita', '10202600004', 'Dokter Umum', 'Klinik ASTARhealth', 'NPAIDI202600004', '081222000004', '2026-07-16 14:08:18.808677'),
('SD0005', NULL, 'Ns. Fitri Handayani', '10202600005', 'Perawat', 'Klinik ASTARhealth', '', '081222000005', '2026-07-16 14:08:18.808677'),
('SD0006', NULL, 'Ns. Bayu Prakoso', '10202600006', 'Perawat', 'Klinik ASTARhealth', '', '081222000006', '2026-07-16 14:08:18.808677'),
('SD0007', NULL, 'Apt. Rani Wulandari', '10202600007', 'Apoteker', 'Klinik ASTARhealth', '', '081222000007', '2026-07-16 14:08:18.808677'),
('SD0008', NULL, 'Apt. Denny Setiawan', '10202600008', 'Apoteker', 'Klinik ASTARhealth', '', '081222000008', '2026-07-16 14:08:18.808677'),
('SD0009', NULL, 'Rudi Hartono', '10202600009', 'Petugas K3', 'ASTAR', '', '081222000009', '2026-07-16 14:08:18.808677'),
('SD0010', NULL, 'Mira Puspitasari', '10202600010', 'Petugas K3', 'ASTAR', '', '081222000010', '2026-07-16 14:08:18.808677'),
('SD0011', NULL, 'Hendra Wijaya', '10202600011', 'Petugas K3', 'ASTAR', '', '081222000011', '2026-07-16 14:08:18.808677'),
('SD0012', NULL, 'Siska Aprilia', '10202600012', 'Petugas K3', 'ASTAR', '', '081222000012', '2026-07-16 14:08:18.808677'),
('SD0013', NULL, 'Taufik Akbar', '10202600013', 'Administrasi Klinik', 'ASTAR', '', '081222000013', '2026-07-16 14:08:18.808677'),
('SD0014', NULL, 'Lina Marlina', '10202600014', 'Administrasi Klinik', 'ASTAR', '', '081222000014', '2026-07-16 14:08:18.808677'),
('SD0015', NULL, 'Wahyu Nugroho', '10202600015', 'Analis Kesehatan', 'Klinik ASTARhealth', '', '081222000015', '2026-07-16 14:08:18.808677'),
('SD0016', NULL, 'Ratih Kusumaningrum', '10202600016', 'Analis Kesehatan', 'Klinik ASTARhealth', '', '081222000016', '2026-07-16 14:08:18.808677'),
('SD0017', NULL, 'Dedi Irawan', '10202600017', 'Pengemudi Ambulans', 'ASTAR', '', '081222000017', '2026-07-16 14:08:18.808677'),
('SD0018', NULL, 'Sri Wahyuni', '10202600018', 'Petugas Kebersihan', 'ASTAR', '', '081222000018', '2026-07-16 14:08:18.808677'),
('SD0019', NULL, 'Arman Hakim', '10202600019', 'Koordinator Klinik', 'ASTAR', '', '081222000019', '2026-07-16 14:08:18.808677'),
('SD0020', NULL, 'Yuni Kartika', '10202600020', 'Petugas Rekam Medis', 'Klinik ASTARhealth', '', '081222000020', '2026-07-16 14:08:18.808677'),
('STF091', 'USR001', 'Ike Indahwati', '102310013', 'Dokter UKK', 'Siloam', '009231239113121', '811-8198-560', '2026-07-16 14:08:18.808677'),
('STF109', 'USR730', 'Suswanto dewanto', '20250932032', 'Wakil Ketua Divisi K3', 'Kampus', '-', '333-4432-242', '2026-07-16 14:08:18.808677');

INSERT INTO `pasienm` (`id_pasien`, `id_user`, `no_identitas`, `nama_pasien`, `jenis_kelamin`, `kategori_pasien`, `unit_prodi`, `alamat`, `no_hp`, `created_at`) VALUES
('4XTMNE', '4163C5', '0909090909', 'ZEID ALRAYAN PASHA', 'L', 'Sigap', '', 'GALUH MAS BLOK IX B/C 11', NULL, '2026-07-16 14:08:18.855137'),
('P4PMRO', 'UYMQXZ', '3217503200072828', 'Yanto Gimang', 'P', 'Tamu', '', 'Jl mawar', '+6285156413049', '2026-07-16 14:08:18.855137'),
('PBHBU0', 'UZ9GIQ', 'Sigap-009', 'zeid alrayan pasha', 'L', 'Sigap', '', 'hhhhhhhhh', '+6282262746488', '2026-07-16 14:08:18.855137'),
('PD0001', 'UD0001', '202600000001', 'Alya Putri Ramadhani', 'P', 'Mahasiswa', 'Manajemen Informatika', 'Jl. Melati Raya No. 12, Bekasi', '081312000001', '2026-07-16 14:08:18.855137'),
('PD0002', 'UD0002', '202600000002', 'Rizky Maulana', 'L', 'Mahasiswa', 'Teknik Mesin', 'Jl. Cendana No. 8, Cikarang', '081312000002', '2026-07-16 14:08:18.855137'),
('PD0003', 'UD0003', '202600000003', 'Siti Nur Aisyah', 'P', 'Pegawai', 'Administrasi Akademik', 'Jl. Anggrek Blok C2, Bekasi', '081312000003', '2026-07-16 14:08:18.855137'),
('PD0004', 'UD0004', '202600000004', 'Andi Pratama', 'L', 'Sigap', '', 'Perumahan Taman Sentosa, Cikarang', '081312000004', '2026-07-16 14:08:18.855137'),
('PD0005', 'UD0005', '202600000005', 'Nabila Zahra', 'P', 'Virtus', '', 'Jl. Kenanga No. 21, Karawang', '081312000005', '2026-07-16 14:08:18.855137'),
('PD0006', 'UD0006', '202600000006', 'Fajar Hidayat', 'L', 'Mahasiswa', 'Teknik Informatika', 'Jl. Mawar Dalam No. 4, Bekasi', '081312000006', '2026-07-16 14:08:18.855137'),
('PD0007', 'UD0007', '202600000007', 'Dewi Lestari', 'P', 'Pegawai', 'Keuangan', 'Jl. Pahlawan No. 17, Tambun', '081312000007', '2026-07-16 14:08:18.855137'),
('PD0008', 'UD0008', '202600000008', 'Muhammad Arif', 'L', 'Mahasiswa', 'Teknik Elektro', 'Jl. Kutilang No. 6, Cibitung', '081312000008', '2026-07-16 14:08:18.855137'),
('PD0009', 'UD0009', '3275011509010009', 'Citra Maharani', 'P', 'Tamu', '', 'Jl. Industri Selatan, Cikarang', '081312000009', '2026-07-16 14:08:18.855137'),
('PD0010', 'UD0010', '202600000010', 'Bagas Saputra', 'L', 'Sigap', '', 'Jl. Raya Setu No. 35, Bekasi', '081312000010', '2026-07-16 14:08:18.855137'),
('PD0011', 'UD0011', '202600000011', 'Putri Amelia', 'P', 'Mahasiswa', 'Manajemen Logistik', 'Jl. Flamboyan No. 9, Karawang', '081312000011', '2026-07-16 14:08:18.855137'),
('PD0012', 'UD0012', '202600000012', 'Dimas Kurniawan', 'L', 'Virtus', '', 'Perumahan Graha Asri, Cikarang', '081312000012', '2026-07-16 14:08:18.855137'),
('PD0013', 'UD0013', '202600000013', 'Rina Oktaviani', 'P', 'Pegawai', 'Sumber Daya Manusia', 'Jl. Wijaya Kusuma No. 18, Bekasi', '081312000013', '2026-07-16 14:08:18.855137'),
('PD0014', 'UD0014', '202600000014', 'Ahmad Fauzan', 'L', 'Mahasiswa', 'Teknik Otomotif', 'Jl. Raya Babelan No. 10, Bekasi', '081312000014', '2026-07-16 14:08:18.855137'),
('PD0015', 'UD0015', '3275011510010015', 'Maya Salsabila', 'P', 'Tamu', '', 'Jl. Niaga Utama No. 3, Cikarang', '081312000015', '2026-07-16 14:08:18.855137'),
('PD0016', 'UD0016', '202600000016', 'Raka Aditya', 'L', 'Mahasiswa', 'Teknik Sipil', 'Jl. Kemuning No. 15, Bekasi', '081312000016', '2026-07-16 14:08:18.855137'),
('PD0017', 'UD0017', '202600000017', 'Intan Permata', 'P', 'Sigap', '', 'Jl. Dahlia No. 27, Karawang', '081312000017', '2026-07-16 14:08:18.855137'),
('PD0018', 'UD0018', '202600000018', 'Yoga Ramadhan', 'L', 'Pegawai', 'Teknologi Informasi', 'Jl. Jati Asih No. 11, Bekasi', '081312000018', '2026-07-16 14:08:18.855137'),
('PD0019', 'UD0019', '202600000019', 'Anisa Rahmawati', 'P', 'Mahasiswa', 'Akuntansi', 'Jl. Teratai No. 19, Tambun', '081312000019', '2026-07-16 14:08:18.855137'),
('PD0020', 'UD0020', '202600000020', 'Reza Firmansyah', 'L', 'Virtus', '', 'Jl. Raya Lemahabang No. 7, Cikarang', '081312000020', '2026-07-16 14:08:18.855137'),
('PD2G7X', 'U3EUJD', '200232323', 'fff', 'L', 'Virtus', '', 'Depok', '+6282323232322', '2026-07-16 15:35:41.710270'),
('PEADH4', 'UX5HT9', '0934234343434343', 'fffggg', 'L', 'Tamu', '', 'rasyajuga', '+6283839939443', '2026-07-16 15:46:06.999495'),
('PJ1IXN', 'U828X0', '3173282822929392', 'zeid', 'L', 'Tamu', '', 'Depok', '+6282323232322', '2026-07-16 15:39:39.215484'),
('PKETE9', 'UHRWVS', 'SIGAP-001', 'zeid alrayan pasha', 'L', 'Sigap', '', 'hhhhhhhhh', '+6282262746488', '2026-07-16 14:08:18.855137'),
('PMETIH', 'U5H5KB', '09090909090909', 'zeid alrayan pasha', 'L', 'Virtus', '', 'GALUH MAS BLOK IX B/C 11', '+6282262746488', '2026-07-16 14:08:18.855137'),
('PNCRA4', 'UGONTA', '4333545432324343', 'zeid alrayan pasha', 'L', 'Tamu', NULL, 'GALUH MAS BLOK IX B/C 11', '+628111280201', '2026-07-16 14:08:18.855137'),
('PNJJCJ', 'UL3OQS', '092313323', 'smevkyagaefdkhdeutvtsumhrdgipeackjdwbiywrmiqxeruahpkxfkvjbwtkznbdynqgrswoekuqynyzchdxngdtllnnptlhiz', 'P', 'Virtus', '', 'Depok', '+6282323232322', '2026-07-16 15:37:07.553022'),
('PSN153', 'USR312', '0120240029', 'dodi mangono', 'L', 'Mahasiswa', 'TPM', 'jupiter', '323-2323-232', '2026-07-16 14:08:18.855137'),
('PSN174', 'USR460', '0920250050', 'Dholadolly', 'P', 'Mahasiswa', 'TRPL', 'venus', '888-8888-809', '2026-07-16 14:08:18.855137'),
('PSN379', 'USR971', '0320250021', 'indah kusuma', 'P', 'Mahasiswa', 'MI', 'bekasi', '823-2823-223', '2026-07-16 14:08:18.855137'),
('PSN410', 'USR904', '0120250055', 'pipi mimi ', 'P', 'Mahasiswa', 'TPM', 'venus', '432-2445-244', '2026-07-16 14:08:18.855137'),
('PSN463', 'USR930', '0520240028', 'wowo gunanjar', 'L', 'Mahasiswa', 'TKBG', 'Mars', '666-7742-676', '2026-07-16 14:08:18.855137'),
('PSN759', 'USR043', '0120240037', 'Dio gomana', 'L', 'Mahasiswa', 'TPM', 'bekasi', '444-4444-444', '2026-07-16 14:08:18.855137'),
('PSN891', 'USR956', '2023212013', 'yoga doanaly', 'L', 'Pegawai', 'WKS', 'Mars', '334-4431-212', '2026-07-16 14:08:18.855137'),
('PSN894', 'USR651', '0420250044', 'Nana Kusniawati', 'P', 'Mahasiswa', 'P4', 'Bekasi', '222-2232-333', '2026-07-16 14:08:18.855137'),
('PSN946', 'USR632', '092020412', 'goy Geming', 'L', 'Sigap', '', 'JL MAWAR', '+6285156413034', '2026-07-16 14:08:18.855137'),
('PSQQ6D', 'UKZUB7', '33434343', 'raysuadsad', 'P', 'Sigap', '', 'abc', '+6283243434343', '2026-07-16 15:42:34.262103'),
('PT177B', 'UZG38S', '08232332', 'fffggg', 'P', 'Virtus', '', 'Depok', '+628782828244', '2026-07-16 15:34:09.776421');

INSERT INTO `diagnosam` (`id_diagnosa`, `nama_penyakit`, `kategori`, `tipe`, `created_at`) VALUES
('DD0001', 'Influenza', 'Menular', 'Ringan', '2026-07-16 14:08:18.996620'),
('DD0002', 'Infeksi Saluran Pernapasan Akut', 'Menular', 'Sedang', '2026-07-16 14:08:18.996620'),
('DD0003', 'Gastritis', 'Umum', 'Sedang', '2026-07-16 14:08:18.996620'),
('DD0004', 'Migrain', 'Kronis', 'Sedang', '2026-07-16 14:08:18.996620'),
('DD0005', 'Hipertensi', 'Kronis', 'Berat', '2026-07-16 14:08:18.996620'),
('DD0006', 'Diabetes Melitus Tipe 2', 'Kronis', 'Berat', '2026-07-16 14:08:18.996620'),
('DD0007', 'Dermatitis Alergi', 'Umum', 'Ringan', '2026-07-16 14:08:18.996620'),
('DD0008', 'Faringitis', 'Menular', 'Sedang', '2026-07-16 14:08:18.996620'),
('DD0009', 'Diare Akut', 'Menular', 'Sedang', '2026-07-16 14:08:18.996620'),
('DD0010', 'Vertigo', 'Kronis', 'Sedang', '2026-07-16 14:08:18.996620'),
('DD0011', 'Asma Bronkial', 'Kronis', 'Berat', '2026-07-16 14:08:18.996620'),
('DD0012', 'Konjungtivitis', 'Menular', 'Ringan', '2026-07-16 14:08:18.996620'),
('DD0013', 'Nyeri Punggung Bawah', 'Umum', 'Sedang', '2026-07-16 14:08:18.996620'),
('DD0014', 'Demam Berdarah Dengue', 'Menular', 'Berat', '2026-07-16 14:08:18.996620'),
('DD0015', 'Tonsilitis', 'Menular', 'Sedang', '2026-07-16 14:08:18.996620'),
('DD0016', 'Infeksi Saluran Kemih', 'Menular', 'Sedang', '2026-07-16 14:08:18.996620'),
('DD0017', 'Anemia Defisiensi Besi', 'Kronis', 'Sedang', '2026-07-16 14:08:18.996620'),
('DD0018', 'Dispepsia', 'Umum', 'Ringan', '2026-07-16 14:08:18.996620'),
('DD0019', 'Sinusitis', 'Menular', 'Sedang', '2026-07-16 14:08:18.996620'),
('DD0020', 'Cedera Jaringan Lunak', 'Lainnya', 'Ringan', '2026-07-16 14:08:18.996620'),
('DX024', 'Gerd', 'Umum', 'Sedang', '2026-07-16 14:08:18.996620'),
('DX190', 'Asma', 'Kronis', 'Berat', '2026-07-16 14:08:18.996620'),
('DX480', 'Demam', 'Umum', 'Berat', '2026-07-16 14:08:18.996620'),
('DX761', 'flu berat', 'Menular', 'Sedang', '2026-07-16 14:08:18.996620');

INSERT INTO `obatm` (`id_obat`, `nama_obat`, `stok_sekarang`, `stok_minimum`, `stok_target`, `satuan`, `harga_per_pcs`, `created_at`) VALUES
('OBT002', 'ji', 69, 10, 30, 'Tablet', 6000.00, '2026-07-16 14:08:18.949993'),
('OBT003', 'ji mm', 1, 8, 8, 'Sachet', 52000.00, '2026-07-16 14:08:18.949993'),
('OBT004', 'Antimin', 10, 10, 100, 'Sachet', 14.00, '2026-07-16 14:08:18.949993'),
('OD0001', 'Paracetamol 500 mg - Sanmol', 73, 20, 150, 'Tablet', 650.00, '2026-07-16 14:08:18.949993'),
('OD0002', 'Amoxicillin 500 mg - Amoxsan', 56, 15, 120, 'Kapsul', 1250.00, '2026-07-16 14:08:18.949993'),
('OD0003', 'Omeprazole 20 mg - Omed', 44, 15, 100, 'Kapsul', 1100.00, '2026-07-16 14:08:18.949993'),
('OD0004', 'Cetirizine 10 mg - Incidal-OD', 38, 12, 80, 'Tablet', 1800.00, '2026-07-16 14:08:18.949993'),
('OD0005', 'Antasida DOEN - Promag', 52, 15, 100, 'Tablet', 900.00, '2026-07-16 14:08:18.949993'),
('OD0006', 'Vitamin C 500 mg - IPI', 79, 20, 150, 'Tablet', 500.00, '2026-07-16 14:08:18.949993'),
('OD0007', 'Ibuprofen 400 mg - Proris', 33, 15, 90, 'Tablet', 1400.00, '2026-07-16 14:08:18.949993'),
('OD0008', 'Ambroxol 30 mg - Mucos', 27, 12, 80, 'Tablet', 1350.00, '2026-07-16 14:08:18.949993'),
('OD0009', 'Salbutamol 2 mg - Ventolin', 27, 10, 60, 'Tablet', 1600.00, '2026-07-16 14:08:18.949993'),
('OD0010', 'Metformin 500 mg - Glucophage', 48, 20, 120, 'Tablet', 1700.00, '2026-07-16 14:08:18.949993'),
('OD0011', 'Amlodipine 5 mg - Norvask', 39, 15, 100, 'Tablet', 2300.00, '2026-07-16 14:08:18.949993'),
('OD0012', 'Oralit - Kimia Farma', 74, 20, 120, 'Sachet', 1200.00, '2026-07-16 14:08:18.949993'),
('OD0013', 'Chloramphenicol Tetes Mata - Cendo Fenicol', 16, 8, 40, 'Botol', 18500.00, '2026-07-16 14:08:18.949993'),
('OD0014', 'Miconazole Cream - Daktarin', 3, 8, 35, 'Tube', 29500.00, '2026-07-16 14:08:18.949993'),
('OD0015', 'Povidone Iodine 10% - Betadine', 21, 10, 50, 'Botol', 21000.00, '2026-07-16 14:08:18.949993'),
('OD0016', 'Ferrous Fumarate - Sangobion', 36, 12, 80, 'Kapsul', 2500.00, '2026-07-16 14:08:18.949993'),
('OD0017', 'Loperamide 2 mg - Imodium', 29, 10, 70, 'Tablet', 2100.00, '2026-07-16 14:08:18.949993'),
('OD0018', 'Diclofenac Sodium 50 mg - Voltaren', 35, 12, 80, 'Tablet', 1900.00, '2026-07-16 14:08:18.949993'),
('OD0019', 'Azithromycin 500 mg - Zithromax', 21, 10, 60, 'Tablet', 7800.00, '2026-07-16 14:08:18.949993'),
('OD0020', 'Lansoprazole 30 mg - Prevacid', 171, 12, 80, 'Kapsul', 3200.00, '2026-07-16 14:08:18.949993');

INSERT INTO `supplierm` (`id_supplier`, `nama_supplier`, `kontak`, `alamat`, `created_at`) VALUES
('SP0001', 'PT Kimia Farma Trading & Distribution - Bekasi', '021830000001', 'Kawasan Industri Jababeka, Cikarang', '2026-07-16 14:08:18.900289'),
('SP0002', 'PT Enseval Putera Megatrading - Bekasi', '021830000002', 'Jl. Sultan Agung, Bekasi', '2026-07-16 14:08:18.900289'),
('SP0003', 'PT Anugerah Pharmindo Lestari - Cikarang', '021830000003', 'Kawasan Industri Delta Silicon, Cikarang', '2026-07-16 14:08:18.900289'),
('SP0004', 'PT Millennium Pharmacon International - Bekasi', '021830000004', 'Jl. Ahmad Yani, Bekasi', '2026-07-16 14:08:18.900289'),
('SP0005', 'PT Dos Ni Roha - Bekasi', '021830000005', 'Jl. Raya Narogong, Bekasi', '2026-07-16 14:08:18.900289'),
('SP0006', 'PT Merapi Utama Pharma - Cikarang', '021830000006', 'Kawasan Industri MM2100, Cibitung', '2026-07-16 14:08:18.900289'),
('SP0007', 'PT Parit Padang Global - Bekasi', '021830000007', 'Jl. Cut Meutia, Bekasi', '2026-07-16 14:08:18.900289'),
('SP0008', 'PT Bina San Prima - Karawang', '021830000008', 'Kawasan Industri KIIC, Karawang', '2026-07-16 14:08:18.900289'),
('SP0009', 'PT Rajawali Nusindo - Bekasi', '021830000009', 'Jl. Juanda, Bekasi', '2026-07-16 14:08:18.900289'),
('SP0010', 'PT Penta Valent - Bekasi', '021830000010', 'Jl. KH Noer Ali, Bekasi', '2026-07-16 14:08:18.900289'),
('SP0011', 'PT Sapta Sari Tama - Cikarang', '021830000011', 'Jl. Industri Utara, Cikarang', '2026-07-16 14:08:18.900289'),
('SP0012', 'PT Mensa Bina Sukses - Bekasi', '021830000012', 'Jl. Raya Pekayon, Bekasi', '2026-07-16 14:08:18.900289'),
('SP0013', 'PT Antarmitra Sembada - Bekasi', '021830000013', 'Jl. Raya Kalimalang, Bekasi', '2026-07-16 14:08:18.900289'),
('SP0014', 'PT Kebayoran Pharma - Cikarang', '021830000014', 'Kawasan Industri EJIP, Cikarang', '2026-07-16 14:08:18.900289'),
('SP0015', 'PT Anugrah Argon Medica - Bekasi', '021830000015', 'Jl. Raya Mustikasari, Bekasi', '2026-07-16 14:08:18.900289'),
('SP0016', 'PT United Dico Citas - Bekasi', '021830000016', 'Jl. Patriot, Bekasi', '2026-07-16 14:08:18.900289'),
('SP0017', 'PT Distriversa Buanamas - Cikarang', '021830000017', 'Jl. Tekno Boulevard, Cikarang', '2026-07-16 14:08:18.900289'),
('SP0018', 'PT Brataco - Bekasi', '021830000018', 'Jl. Ir. H. Juanda, Bekasi', '2026-07-16 14:08:18.900289'),
('SP0019', 'PT Bio Farma Distribusi - Jawa Barat', '021830000019', 'Jl. Pasteur, Bandung', '2026-07-16 14:08:18.900289'),
('SP0020', 'PT Pharos Indonesia Distribution - Bekasi', '021830000020', 'Jl. Raya Pondok Gede, Bekasi', '2026-07-16 14:08:18.900289'),
('SUP246', 'PT YAOP', '872-7311-23', 'JL JATIPILAR', '2026-07-16 14:08:18.900289');

INSERT INTO `jadwalm` (`id_jadwal`, `id_staff`, `tanggal`, `jam_mulai`, `jam_selesai`, `status`, `created_at`) VALUES
('JD0001', 'STF091', 'Senin', '08:00:00', '10:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0002', 'STF091', 'Selasa', '08:00:00', '10:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0003', 'STF091', 'Rabu', '08:00:00', '10:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0004', 'STF091', 'Kamis', '08:00:00', '10:00:00', 'Tutup', '2026-07-16 14:08:19.057732'),
('JD0005', 'STF091', 'Jumat', '08:00:00', '10:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0006', 'STF091', 'Senin', '10:00:00', '12:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0007', 'STF091', 'Selasa', '10:00:00', '12:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0008', 'STF091', 'Rabu', '10:00:00', '12:00:00', 'Tutup', '2026-07-16 14:08:19.057732'),
('JD0009', 'STF091', 'Kamis', '10:00:00', '12:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0010', 'STF091', 'Jumat', '10:00:00', '12:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0011', 'STF091', 'Senin', '12:00:00', '14:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0012', 'STF091', 'Selasa', '12:00:00', '14:00:00', 'Tutup', '2026-07-16 14:08:19.057732'),
('JD0013', 'STF091', 'Rabu', '12:00:00', '14:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0014', 'STF091', 'Kamis', '12:00:00', '14:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0015', 'STF091', 'Jumat', '12:00:00', '14:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0016', 'STF091', 'Senin', '14:00:00', '16:00:00', 'Tutup', '2026-07-16 14:08:19.057732'),
('JD0017', 'STF091', 'Selasa', '14:00:00', '16:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0018', 'STF091', 'Rabu', '14:00:00', '16:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0019', 'STF091', 'Kamis', '14:00:00', '16:00:00', 'Buka', '2026-07-16 14:08:19.057732'),
('JD0020', 'STF091', 'Jumat', '14:00:00', '16:00:00', 'Tutup', '2026-07-16 14:08:19.057732');

INSERT INTO `notifikasi_stok_obat` (`id_notifikasi`, `id_obat`, `nama_obat`, `stok_sekarang`, `stok_minimum`, `pesan`, `tanggal_notifikasi`) VALUES
(1, '0', 'ji mm', 5, 8, 'Stok obat ji mm sudah mencapai batas minimum. Stok sekarang: 5, stok minimum: 8', '2026-07-14 16:30:31'),
(2, '0', 'ji mm', 1, 8, 'Stok obat ji mm sudah mencapai batas minimum. Stok sekarang: 1, stok minimum: 8', '2026-07-14 16:31:30'),
(9001, 'OD0001', 'Paracetamol 500 mg - Sanmol', 18, 20, 'Stok Paracetamol 500 mg - Sanmol tersisa 18 tablet dan berada di bawah stok minimum 20.', '2026-06-26 08:00:00'),
(9002, 'OD0002', 'Amoxicillin 500 mg - Amoxsan', 12, 15, 'Stok Amoxicillin 500 mg - Amoxsan tersisa 12 kapsul dan berada di bawah stok minimum 15.', '2026-06-27 08:00:00'),
(9003, 'OD0003', 'Omeprazole 20 mg - Omed', 11, 15, 'Stok Omeprazole 20 mg - Omed tersisa 11 kapsul dan berada di bawah stok minimum 15.', '2026-06-28 08:00:00'),
(9004, 'OD0004', 'Cetirizine 10 mg - Incidal-OD', 7, 12, 'Stok Cetirizine 10 mg - Incidal-OD tersisa 7 tablet dan berada di bawah stok minimum 12.', '2026-06-29 08:00:00'),
(9005, 'OD0005', 'Antasida DOEN - Promag', 14, 15, 'Stok Antasida DOEN - Promag tersisa 14 tablet dan berada di bawah stok minimum 15.', '2026-06-30 08:00:00'),
(9006, 'OD0006', 'Vitamin C 500 mg - IPI', 18, 20, 'Stok Vitamin C 500 mg - IPI tersisa 18 tablet dan berada di bawah stok minimum 20.', '2026-07-01 08:00:00'),
(9007, 'OD0007', 'Ibuprofen 400 mg - Proris', 12, 15, 'Stok Ibuprofen 400 mg - Proris tersisa 12 tablet dan berada di bawah stok minimum 15.', '2026-07-02 08:00:00'),
(9008, 'OD0008', 'Ambroxol 30 mg - Mucos', 8, 12, 'Stok Ambroxol 30 mg - Mucos tersisa 8 tablet dan berada di bawah stok minimum 12.', '2026-07-03 08:00:00'),
(9009, 'OD0009', 'Salbutamol 2 mg - Ventolin', 5, 10, 'Stok Salbutamol 2 mg - Ventolin tersisa 5 tablet dan berada di bawah stok minimum 10.', '2026-07-04 08:00:00'),
(9010, 'OD0010', 'Metformin 500 mg - Glucophage', 19, 20, 'Stok Metformin 500 mg - Glucophage tersisa 19 tablet dan berada di bawah stok minimum 20.', '2026-07-05 08:00:00'),
(9011, 'OD0011', 'Amlodipine 5 mg - Norvask', 13, 15, 'Stok Amlodipine 5 mg - Norvask tersisa 13 tablet dan berada di bawah stok minimum 15.', '2026-07-06 08:00:00'),
(9012, 'OD0012', 'Oralit - Kimia Farma', 17, 20, 'Stok Oralit - Kimia Farma tersisa 17 sachet dan berada di bawah stok minimum 20.', '2026-07-07 08:00:00'),
(9013, 'OD0013', 'Chloramphenicol Tetes Mata - Cendo Fenicol', 4, 8, 'Stok Chloramphenicol Tetes Mata - Cendo Fenicol tersisa 4 botol dan berada di bawah stok minimum 8.', '2026-07-08 08:00:00'),
(9014, 'OD0014', 'Miconazole Cream - Daktarin', 3, 8, 'Stok Miconazole Cream - Daktarin tersisa 3 tube dan berada di bawah stok minimum 8.', '2026-07-09 08:00:00'),
(9015, 'OD0015', 'Povidone Iodine 10% - Betadine', 9, 10, 'Stok Povidone Iodine 10% - Betadine tersisa 9 botol dan berada di bawah stok minimum 10.', '2026-07-10 08:00:00'),
(9016, 'OD0016', 'Ferrous Fumarate - Sangobion', 10, 12, 'Stok Ferrous Fumarate - Sangobion tersisa 10 kapsul dan berada di bawah stok minimum 12.', '2026-07-11 08:00:00'),
(9017, 'OD0017', 'Loperamide 2 mg - Imodium', 7, 10, 'Stok Loperamide 2 mg - Imodium tersisa 7 tablet dan berada di bawah stok minimum 10.', '2026-07-12 08:00:00'),
(9018, 'OD0018', 'Diclofenac Sodium 50 mg - Voltaren', 8, 12, 'Stok Diclofenac Sodium 50 mg - Voltaren tersisa 8 tablet dan berada di bawah stok minimum 12.', '2026-07-13 08:00:00'),
(9019, 'OD0019', 'Azithromycin 500 mg - Zithromax', 5, 10, 'Stok Azithromycin 500 mg - Zithromax tersisa 5 tablet dan berada di bawah stok minimum 10.', '2026-07-14 08:00:00'),
(9020, 'OD0020', 'Lansoprazole 30 mg - Prevacid', 11, 12, 'Stok Lansoprazole 30 mg - Prevacid tersisa 11 kapsul dan berada di bawah stok minimum 12.', '2026-07-15 08:00:00'),
(9021, 'OD0014', 'Miconazole Cream - Daktarin', 3, 8, 'Stok obat Miconazole Cream - Daktarin sudah mencapai batas minimum. Stok sekarang: 3, stok minimum: 8', '2026-07-15 09:59:25'),
(9022, 'OD0014', 'Miconazole Cream - Daktarin', 3, 8, 'Stok obat Miconazole Cream - Daktarin sudah mencapai batas minimum. Stok sekarang: 3, stok minimum: 8', '2026-07-15 09:59:25'),
(9023, 'OBT004', 'Antimin', 10, 10, 'Stok obat Antimin sudah mencapai batas minimum. Stok sekarang: 10, stok minimum: 10', '2026-07-15 21:35:24'),
(9024, 'OBT003', 'ji mm', 1, 8, 'Stok obat ji mm sudah mencapai batas minimum. Stok sekarang: 1, stok minimum: 8', '2026-07-16 14:08:18'),
(9025, 'OBT004', 'Antimin', 10, 10, 'Stok obat Antimin sudah mencapai batas minimum. Stok sekarang: 10, stok minimum: 10', '2026-07-16 14:08:18'),
(9026, 'OD0014', 'Miconazole Cream - Daktarin', 3, 8, 'Stok obat Miconazole Cream - Daktarin sudah mencapai batas minimum. Stok sekarang: 3, stok minimum: 8', '2026-07-16 14:08:18');

INSERT INTO `pengadaan_obat` (`id_pengadaan`, `id_obat`, `id_supplier`, `jumlah_order`, `jumlah_diterima`, `tgl_order`, `tgl_estimasi_tiba`, `tgl_diterima`, `status`, `catatan`, `created_at`) VALUES
('PG0001', 'OD0001', 'SP0001', 45, NULL, '2026-06-26', '2026-06-30', NULL, 'Batal', 'Pengadaan Paracetamol 500 mg - Sanmol untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0002', 'OD0002', 'SP0002', 50, 50, '2026-06-27', '2026-07-02', NULL, 'Diterima', 'Pengadaan Amoxicillin 500 mg - Amoxsan untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0003', 'OD0003', 'SP0003', 55, 55, '2026-06-28', '2026-07-01', NULL, 'Diterima', 'Pengadaan Omeprazole 20 mg - Omed untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0004', 'OD0004', 'SP0004', 60, NULL, '2026-06-29', '2026-07-03', NULL, 'Batal', 'Pengadaan Cetirizine 10 mg - Incidal-OD untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0005', 'OD0005', 'SP0005', 65, NULL, '2026-06-30', '2026-07-05', NULL, 'Batal', 'Pengadaan Antasida DOEN - Promag untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0006', 'OD0006', 'SP0006', 70, NULL, '2026-07-01', '2026-07-04', NULL, 'Batal', 'Pengadaan Vitamin C 500 mg - IPI untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0007', 'OD0007', 'SP0007', 75, 75, '2026-07-02', '2026-07-06', NULL, 'Diterima', 'Pengadaan Ibuprofen 400 mg - Proris untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0008', 'OD0008', 'SP0008', 80, 80, '2026-07-03', '2026-07-08', NULL, 'Diterima', 'Pengadaan Ambroxol 30 mg - Mucos untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0009', 'OD0009', 'SP0009', 85, NULL, '2026-07-04', '2026-07-07', NULL, 'Batal', 'Pengadaan Salbutamol 2 mg - Ventolin untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0010', 'OD0010', 'SP0010', 90, NULL, '2026-07-05', '2026-07-09', NULL, 'Batal', 'Pengadaan Metformin 500 mg - Glucophage untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0011', 'OD0011', 'SP0011', 95, NULL, '2026-07-06', '2026-07-11', NULL, 'Batal', 'Pengadaan Amlodipine 5 mg - Norvask untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0012', 'OD0012', 'SP0012', 100, 100, '2026-07-07', '2026-07-10', NULL, 'Diterima', 'Pengadaan Oralit - Kimia Farma untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0013', 'OD0013', 'SP0013', 105, 105, '2026-07-08', '2026-07-12', NULL, 'Diterima', 'Pengadaan Chloramphenicol Tetes Mata - Cendo Fenicol untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0014', 'OD0014', 'SP0014', 110, NULL, '2026-07-09', '2026-07-14', NULL, 'Batal', 'Pengadaan Miconazole Cream - Daktarin untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0015', 'OD0015', 'SP0015', 115, NULL, '2026-07-10', '2026-07-13', NULL, 'Batal', 'Pengadaan Povidone Iodine 10% - Betadine untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0016', 'OD0016', 'SP0016', 120, NULL, '2026-07-11', '2026-07-15', NULL, 'Pending', 'Pengadaan Ferrous Fumarate - Sangobion untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0017', 'OD0017', 'SP0017', 125, 125, '2026-07-12', '2026-07-17', NULL, 'Diterima', 'Pengadaan Loperamide 2 mg - Imodium untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0018', 'OD0018', 'SP0018', 130, 130, '2026-07-13', '2026-07-16', NULL, 'Diterima', 'Pengadaan Diclofenac Sodium 50 mg - Voltaren untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0019', 'OD0019', 'SP0019', 135, NULL, '2026-07-14', '2026-07-18', NULL, 'Batal', 'Pengadaan Azithromycin 500 mg - Zithromax untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PG0020', 'OD0020', 'SP0020', 140, 140, '2026-07-15', '2026-07-20', NULL, 'Diterima', 'Pengadaan Lansoprazole 30 mg - Prevacid untuk menjaga stok minimum klinik', '2026-07-15 02:01:52'),
('PGD001', 'OD0014', 'SP0018', 10, NULL, '2026-07-15', '2026-07-16', NULL, 'Pending', NULL, '2026-07-15 13:35:14'),
('PGD002', 'OD0011', 'SP0005', 1, NULL, '2026-07-15', '2026-07-14', NULL, 'Pending', NULL, '2026-07-15 13:35:30'),
('PGD003', 'OD0012', 'SP0018', 10, 10, '2026-07-15', '2026-07-15', '2026-07-15 21:50:38', 'Diterima', NULL, '2026-07-15 13:55:16');

INSERT INTO `rekam_medis` (`id_rekam_medis`, `id_pasien`, `id_staff`, `id_diagnosa`, `no_antrian`, `tgl_kunjungan`, `waktu_booking`, `keluhan`, `hasil_pemeriksaan`, `status`, `pernah_darurat`, `jenis_antrean`, `created_at`) VALUES
('MD0001', 'PD0001', 'STF091', 'DD0001', 'A001', '2026-06-26', '08:03:00', 'Demam, pilek, dan badan terasa pegal selama dua hari', 'Istirahat cukup, hidrasi, dan terapi simptomatik.', 'Selesai', 0, 'Langsung', '2026-06-26 08:03:00.000000'),
('MD0002', 'PD0001', 'STF091', 'DD0002', 'A002', '2026-06-27', '09:06:00', 'Batuk berdahak disertai tenggorokan nyeri', 'Terapi antibiotik sesuai indikasi dan kontrol tiga hari.', 'Selesai', 0, 'Jadwal', '2026-06-27 09:06:00.000000'),
('MD0003', 'PD0001', 'STF091', 'DD0003', 'A003', '2026-06-28', '10:09:00', 'Nyeri ulu hati setelah terlambat makan', 'Pola makan teratur dan hindari makanan pedas.', 'Selesai', 0, 'Langsung', '2026-06-28 10:09:00.000000'),
('MD0004', 'PD0001', 'STF091', 'DD0004', 'A004', '2026-06-29', '11:12:00', 'Sakit kepala berdenyut disertai mual', 'Istirahat di ruangan tenang dan obat pereda nyeri.', 'Selesai', 0, 'Jadwal', '2026-06-29 11:12:00.000000'),
('MD0005', 'PD0001', 'STF091', 'DD0005', 'A005', '2026-06-30', '12:15:00', 'Tekanan darah meningkat saat pemeriksaan rutin', 'Pemantauan tekanan darah dan terapi antihipertensi.', 'Selesai', 1, 'Langsung', '2026-06-30 12:15:00.000000'),
('MD0006', 'PD0001', 'STF091', 'DD0006', 'A006', '2026-07-01', '13:18:00', 'Sering haus dan mudah lelah', 'Kontrol gula darah dan edukasi pola makan.', 'Selesai', 0, 'Jadwal', '2026-07-01 13:18:00.000000'),
('MD0007', 'PD0001', 'STF091', 'DD0007', 'A007', '2026-07-02', '14:21:00', 'Gatal dan kemerahan setelah terkena debu', 'Antihistamin dan hindari pemicu alergi.', 'Selesai', 0, 'Langsung', '2026-07-02 14:21:00.000000'),
('MD0008', 'PD0001', 'STF091', 'DD0008', 'A008', '2026-07-03', '15:24:00', 'Nyeri menelan dan suara serak', 'Terapi simptomatik serta berkumur air hangat.', 'Selesai', 0, 'Jadwal', '2026-07-03 15:24:00.000000'),
('MD0009', 'PD0001', 'STF091', 'DD0009', 'A009', '2026-07-04', '08:27:00', 'Buang air besar cair lebih dari tiga kali', 'Rehidrasi oral dan pemantauan tanda dehidrasi.', 'Selesai', 0, 'Langsung', '2026-07-04 08:27:00.000000'),
('MD0010', 'PD0001', 'STF091', 'DD0010', 'A010', '2026-07-05', '09:30:00', 'Pusing berputar saat bangun dari tempat tidur', 'Latihan posisi dan obat antivertigo.', 'Selesai', 0, 'Jadwal', '2026-07-05 09:30:00.000000'),
('MD0011', 'PD0001', 'STF091', 'DD0011', 'A011', '2026-07-06', '10:33:00', 'Sesak napas berulang setelah aktivitas', 'Nebulisasi dan evaluasi kontrol asma.', 'Selesai', 1, 'Langsung', '2026-07-06 10:33:00.000000'),
('MD0012', 'PD0001', 'STF091', 'DD0012', 'A012', '2026-07-07', '11:36:00', 'Mata merah, berair, dan terasa mengganjal', 'Tetes mata dan menjaga kebersihan tangan.', 'Selesai', 0, 'Jadwal', '2026-07-07 11:36:00.000000'),
('MD0013', 'PD0001', 'STF091', 'DD0013', 'A013', '2026-07-08', '12:39:00', 'Nyeri pinggang setelah mengangkat barang', 'Kompres hangat dan pembatasan aktivitas berat.', 'Selesai', 0, 'Langsung', '2026-07-08 12:39:00.000000'),
('MD0014', 'PD0001', 'STF091', 'DD0014', 'A014', '2026-07-09', '13:42:00', 'Demam tinggi disertai nyeri sendi', 'Rujuk pemeriksaan laboratorium dan observasi.', 'Selesai', 0, 'Jadwal', '2026-07-09 13:42:00.000000'),
('MD0015', 'PD0001', 'STF091', 'DD0015', 'A015', '2026-07-10', '14:45:00', 'Amandel membesar dan nyeri saat menelan', 'Terapi antiinflamasi dan evaluasi infeksi.', 'Selesai', 0, 'Langsung', '2026-07-10 14:45:00.000000'),
('MD0016', 'PD0001', 'STF091', 'DD0016', 'A016', '2026-07-11', '15:48:00', 'Nyeri saat buang air kecil', 'Pemeriksaan urin dan terapi antibiotik sesuai hasil.', 'Selesai', 0, 'Jadwal', '2026-07-11 15:48:00.000000'),
('MD0017', 'PD0001', 'STF091', 'DD0017', 'A017', '2026-07-12', '08:51:00', 'Mudah lelah dan tampak pucat', NULL, 'Batal', 0, 'Langsung', '2026-07-12 08:51:00.000000'),
('MD0018', 'PD0001', 'STF091', 'DD0018', 'A018', '2026-07-13', '09:54:00', 'Perut kembung dan cepat kenyang', NULL, 'Batal', 0, 'Jadwal', '2026-07-13 09:54:00.000000'),
('MD0019', 'PD0001', 'STF091', 'DD0019', 'A019', '2026-07-14', '10:57:00', 'Hidung tersumbat dan nyeri pada wajah', NULL, 'Batal', 0, 'Langsung', '2026-07-14 10:57:00.000000'),
('MD0020', 'PD0001', 'STF091', 'DD0014', 'A020', '2026-07-15', '11:00:00', 'Pergelangan kaki terkilir saat berjalan', 'asasasa', 'Selesai', 0, 'Jadwal', '2026-07-15 11:00:00.000000'),
('RM0621', 'PSN894', 'STF091', NULL, 'A001', '2026-07-20', '10:11:00', 'Kejang', NULL, 'Darurat', 1, 'Jadwal', '2026-07-20 10:11:00.000000'),
('RM1035', '4XTMNE', 'STF091', 'DX190', 'A001', '2026-06-25', '15:24:58', 'sakit kepala', 'jawa nya kebanyakan itu kurangi', 'Selesai', 0, 'Langsung', '2026-06-25 15:24:58.000000'),
('RM1308', 'PSN894', 'STF091', 'DX761', 'A005', '2026-06-25', '05:25:37', 'd', 'sdds', 'Selesai', 0, 'Langsung', '2026-06-25 05:25:37.000000'),
('RM1524', 'PSN891', 'STF091', 'DX480', 'A002', '2026-06-26', '09:56:05', 'jawaa', 's', 'Selesai', 0, 'Langsung', '2026-06-26 09:56:05.000000'),
('RM3167', '4XTMNE', 'STF091', 'DX761', 'A010', '2026-06-25', '08:37:24', 'k', 'n', 'Selesai', 0, 'Langsung', '2026-06-25 08:37:24.000000'),
('RM3980', '4XTMNE', 'STF091', 'DX761', 'A006', '2026-06-25', '05:37:31', 's', 'sdsdsdsds', 'Selesai', 0, 'Langsung', '2026-06-25 05:37:31.000000'),
('RM4201', '4XTMNE', 'STF091', 'DX480', 'A009', '2026-06-25', '08:22:19', 'g', ';', 'Selesai', 0, 'Langsung', '2026-06-25 08:22:19.000000'),
('RM5872', '4XTMNE', 'STF091', 'DX480', 'A008', '2026-06-25', '07:27:52', 'qwwq', 'www', 'Selesai', 0, 'Langsung', '2026-06-25 07:27:52.000000'),
('RM8495', '4XTMNE', 'STF091', 'DX190', 'A007', '2026-06-25', '05:44:07', 's', 'z', 'Selesai', 0, 'Langsung', '2026-06-25 05:44:07.000000'),
('RM8903', '4XTMNE', 'STF091', 'DX480', 'A001', '2026-06-26', '08:09:20', ' bbbb', 'kurag nyawit', 'Selesai', 0, 'Langsung', '2026-06-26 08:09:20.000000'),
('RM9026', 'PSN891', 'STF091', 'DX480', 'A004', '2026-06-23', '09:04:14', 'pusing', 'beliau sakit', 'Selesai', 0, 'Langsung', '2026-06-23 09:04:14.000000'),
('RM9410', 'PSN759', 'STF091', 'DD0011', 'A021', '2026-07-15', '14:47:09', 'wqwwq', 'lllll', 'Selesai', 0, 'Langsung', '2026-07-15 14:47:09.000000');

INSERT INTO `resep_dokter` (`id_resep`, `id_pasien`, `tanggal_resep`, `id_rekam_medis`, `id_obat`, `jumlah_keluar`, `catatan_obat`) VALUES
('RP0001', 'PD0001', '2026-06-26 10:15:00', 'MD0001', 'OD0001', 2, '3 kali sehari sesudah makan'),
('RP0002', 'PD0001', '2026-06-27 11:15:00', 'MD0002', 'OD0002', 3, '2 kali sehari setelah makan'),
('RP0003', 'PD0001', '2026-06-28 12:15:00', 'MD0003', 'OD0003', 1, '1 kali sehari sebelum tidur'),
('RP0004', 'PD0001', '2026-06-29 13:15:00', 'MD0004', 'OD0004', 2, 'Gunakan sesuai petunjuk dokter'),
('RP0005', 'PD0001', '2026-06-30 14:15:00', 'MD0005', 'OD0005', 3, '3 kali sehari sesudah makan'),
('RP0006', 'PD0001', '2026-07-01 09:15:00', 'MD0006', 'OD0006', 1, '2 kali sehari setelah makan'),
('RP0007', 'PD0001', '2026-07-02 10:15:00', 'MD0007', 'OD0007', 2, '1 kali sehari sebelum tidur'),
('RP0008', 'PD0001', '2026-07-03 11:15:00', 'MD0008', 'OD0008', 3, 'Gunakan sesuai petunjuk dokter'),
('RP0009', 'PD0001', '2026-07-04 12:15:00', 'MD0009', 'OD0009', 1, '3 kali sehari sesudah makan'),
('RP0010', 'PD0001', '2026-07-05 13:15:00', 'MD0010', 'OD0010', 2, '2 kali sehari setelah makan'),
('RP0011', 'PD0001', '2026-07-06 14:15:00', 'MD0011', 'OD0011', 3, '1 kali sehari sebelum tidur'),
('RP0012', 'PD0001', '2026-07-07 09:15:00', 'MD0012', 'OD0012', 1, 'Gunakan sesuai petunjuk dokter'),
('RP0013', 'PD0001', '2026-07-08 10:15:00', 'MD0013', 'OD0013', 2, '3 kali sehari sesudah makan'),
('RP0014', 'PD0001', '2026-07-09 11:15:00', 'MD0014', 'OD0014', 3, '2 kali sehari setelah makan'),
('RP0015', 'PD0001', '2026-07-10 12:15:00', 'MD0015', 'OD0015', 1, '1 kali sehari sebelum tidur'),
('RP0016', 'PD0001', '2026-07-11 13:15:00', 'MD0016', 'OD0016', 2, 'Gunakan sesuai petunjuk dokter'),
('RP0017', 'PD0001', '2026-07-12 14:15:00', NULL, 'OD0017', 3, '3 kali sehari sesudah makan'),
('RP0018', 'PD0001', '2026-07-13 09:15:00', NULL, 'OD0018', 1, '2 kali sehari setelah makan'),
('RP0019', 'PD0001', '2026-07-14 10:15:00', NULL, 'OD0019', 2, '1 kali sehari sebelum tidur'),
('RP0020', 'PD0001', '2026-07-15 11:15:00', NULL, 'OD0020', 3, 'Gunakan sesuai petunjuk dokter'),
('RSP357', NULL, '2026-07-14 22:24:04', 'RM8903', 'OBT002', 50, '3x1 sesudah makan yaa satir dulu'),
('RSP460', NULL, '2026-07-14 22:24:04', 'RM1524', 'OBT002', 50, '3x1 sesudah makan yaa satir dulu'),
('RSP476', NULL, '2026-07-14 22:24:04', 'RM3167', 'OBT003', 30, '3 kali sehari'),
('RSP491', NULL, '2026-07-14 22:24:04', 'RM1035', 'OBT002', 20, '3 kali sehari ya jawanya jadi sunda deh'),
('RSP9BW', NULL, '2026-07-15 14:49:12', NULL, 'OD0002', 1, 'h'),
('RSPCLN', NULL, '2026-07-15 14:49:12', NULL, 'OD0019', 1, 'yu'),
('RSPEQA', 'PSN759', '2026-07-14 22:24:04', NULL, 'OBT002', 10, 's'),
('RSPZLK', NULL, '2026-07-15 09:59:25', 'MD0020', 'OD0014', 10, '2qw');

INSERT INTO `resep_diagnosa` (`id_resep`, `id_diagnosa`) VALUES
('RP0001', 'DD0001'),
('RP0002', 'DD0002'),
('RP0003', 'DD0003'),
('RP0004', 'DD0004'),
('RP0005', 'DD0005'),
('RP0006', 'DD0006'),
('RP0007', 'DD0007'),
('RP0008', 'DD0008'),
('RP0009', 'DD0009'),
('RP0010', 'DD0010'),
('RP0011', 'DD0011'),
('RP0012', 'DD0012'),
('RP0013', 'DD0013'),
('RP0014', 'DD0014'),
('RP0015', 'DD0015'),
('RP0016', 'DD0016'),
('RP0017', 'DD0017'),
('RP0018', 'DD0018'),
('RP0019', 'DD0019'),
('RP0020', 'DD0020'),
('RSP9BW', 'DD0014'),
('RSPCLN', 'DD0014'),
('RSPEQA', 'DX024'),
('RSPEQA', 'DX190'),
('RSPEQA', 'DX480'),
('RSPEQA', 'DX761');

INSERT INTO `riwayat_cetak_laporan` (`id_riwayat`, `jenis_laporan`, `judul_laporan`, `id_user`, `nama_pencetak`, `parameter_filter`, `tanggal_cetak`) VALUES
(1, 'siloam', 'Laporan Siloam', 'USR001', 'Dokter Ike', '', '2026-07-15 12:57:03'),
(2, 'siloam', 'Laporan Siloam', 'USR001', 'Dokter Ike', 'tgl_awal=2026-07-06', '2026-07-15 13:32:13');

INSERT INTO `rujukan` (`id_rujukan`, `id_pasien`, `id_staff`, `tujuan_rs`, `alasan_rujukan`, `hasil_rujukan`, `tgl_rujukan`, `status`, `created_at`) VALUES
('RJ0001', 'PD0001', 'STF091', 'RS Siloam Lippo Cikarang', 'Memerlukan pemeriksaan laboratorium dan observasi lanjutan', NULL, '2026-06-26', 'Selesai', '2026-06-26 00:00:00.000000'),
('RJ0002', 'PD0001', 'STF091', 'RS Hermina Grand Wisata', 'Membutuhkan konsultasi dokter spesialis penyakit dalam', NULL, '2026-06-27', 'Selesai', '2026-06-27 00:00:00.000000'),
('RJ0003', 'PD0001', 'STF091', 'RS Mitra Keluarga Bekasi Timur', 'Memerlukan pemeriksaan radiologi', NULL, '2026-06-28', 'Batal', '2026-06-28 00:00:00.000000'),
('RJ0004', 'PD0001', 'STF091', 'RS EMC Cikarang', 'Membutuhkan penanganan spesialis THT', NULL, '2026-06-29', 'Proses', '2026-06-29 00:00:00.000000'),
('RJ0005', 'PD0001', 'STF091', 'RSUD Kabupaten Bekasi', 'Perlu evaluasi jantung dan tekanan darah lebih lanjut', NULL, '2026-06-30', 'Selesai', '2026-06-30 00:00:00.000000'),
('RJ0006', 'PD0001', 'STF091', 'RS Primaya Bekasi Barat', 'Memerlukan pemeriksaan laboratorium dan observasi lanjutan', NULL, '2026-07-01', 'Selesai', '2026-07-01 00:00:00.000000'),
('RJ0007', 'PD0001', 'STF091', 'RS Permata Keluarga Jababeka', 'Membutuhkan konsultasi dokter spesialis penyakit dalam', NULL, '2026-07-02', 'Batal', '2026-07-02 00:00:00.000000'),
('RJ0008', 'PD0001', 'STF091', 'RS Bhakti Husada Cikarang', 'Memerlukan pemeriksaan radiologi', NULL, '2026-07-03', 'Proses', '2026-07-03 00:00:00.000000'),
('RJ0009', 'PD0001', 'STF091', 'RS Annisa Cikarang', 'Membutuhkan penanganan spesialis THT', NULL, '2026-07-04', 'Selesai', '2026-07-04 00:00:00.000000'),
('RJ0010', 'PD0001', 'STF091', 'RS Sentra Medika Cikarang', 'Perlu evaluasi jantung dan tekanan darah lebih lanjut', NULL, '2026-07-05', 'Selesai', '2026-07-05 00:00:00.000000'),
('RJ0011', 'PD0001', 'STF091', 'RS Amanda Cikarang Selatan', 'Memerlukan pemeriksaan laboratorium dan observasi lanjutan', NULL, '2026-07-06', 'Batal', '2026-07-06 00:00:00.000000'),
('RJ0012', 'PD0001', 'STF091', 'RS Karya Medika Bantar Gebang', 'Membutuhkan konsultasi dokter spesialis penyakit dalam', NULL, '2026-07-07', 'Selesai', '2026-07-07 00:00:00.000000'),
('RJ0013', 'PD0001', 'STF091', 'RS Bella Bekasi', 'Memerlukan pemeriksaan radiologi', NULL, '2026-07-08', 'Selesai', '2026-07-08 00:00:00.000000'),
('RJ0014', 'PD0001', 'STF091', 'RS Kartika Husada Setu', 'Membutuhkan penanganan spesialis THT', NULL, '2026-07-09', 'Selesai', '2026-07-09 00:00:00.000000'),
('RJ0015', 'PD0001', 'STF091', 'RS Cibitung Medika', 'Perlu evaluasi jantung dan tekanan darah lebih lanjut', NULL, '2026-07-10', 'Batal', '2026-07-10 00:00:00.000000'),
('RJ0016', 'PD0001', 'STF091', 'RS Tiara Bekasi', 'Memerlukan pemeriksaan laboratorium dan observasi lanjutan', NULL, '2026-07-11', 'Selesai', '2026-07-11 00:00:00.000000'),
('RJ0017', 'PD0001', 'STF091', 'RS Harapan Keluarga Jababeka', 'Membutuhkan konsultasi dokter spesialis penyakit dalam', NULL, '2026-07-12', 'Selesai', '2026-07-12 00:00:00.000000'),
('RJ0018', 'PD0001', 'STF091', 'RS Metro Hospitals Cikarang', 'Memerlukan pemeriksaan radiologi', NULL, '2026-07-13', 'Selesai', '2026-07-13 00:00:00.000000'),
('RJ0019', 'PD0001', 'STF091', 'RS Mekar Sari Bekasi', 'Membutuhkan penanganan spesialis THT', NULL, '2026-07-14', 'Batal', '2026-07-14 00:00:00.000000'),
('RJ0020', 'PD0001', 'STF091', 'RSUD dr. Chasbullah Abdulmadjid Bekasi', 'Perlu evaluasi jantung dan tekanan darah lebih lanjut', NULL, '2026-07-15', 'Aktif', '2026-07-15 00:00:00.000000'),
('RJK001', 'PSN174', 'STF091', 'RS Mitra', 'ketusuk', 'pendarahan', '2026-07-16', 'Aktif', '2026-07-16 15:51:11.026646'),
('RUJ134', 'PSN894', 'STF091', 'Sentra Medika', 'terjepit besi jarinya', NULL, '2026-06-23', 'Proses', '2026-06-23 00:00:00.000000'),
('RUJ160', 'PSN894', 'STF091', 'Siloam', 'tertimpa azab', NULL, '2026-06-23', 'Proses', '2026-06-23 00:00:00.000000'),
('RUJ403', 'PSN174', 'STF091', 'Sentra Medika', 'Tertusuk pisau di lengan', NULL, '2026-06-19', 'Proses', '2026-06-19 00:00:00.000000'),
('RUJ580', 'PSN153', 'STF091', 'Sentra Medika', 'tertusuk', NULL, '2026-06-23', 'Proses', '2026-06-23 00:00:00.000000'),
('RUJ850', 'PSN759', 'STF091', 'Siloam', 'tertusuk paku di telapk tangan', NULL, '2026-06-19', 'Proses', '2026-06-19 00:00:00.000000');

ALTER TABLE `resep_dokter`
  ADD CONSTRAINT `resep_dokter_ibfk_1` FOREIGN KEY (`id_rekam_medis`) REFERENCES `rekam_medis` (`id_rekam_medis`) ON DELETE CASCADE,
  ADD CONSTRAINT `resep_dokter_ibfk_2` FOREIGN KEY (`id_obat`) REFERENCES `obatm` (`id_obat`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `resep_dokter_ibfk_3` FOREIGN KEY (`id_pasien`) REFERENCES `pasienm` (`id_pasien`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `staffm`
  ADD CONSTRAINT `staffm_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `userm` (`id_user`) ON DELETE CASCADE;

ALTER TABLE `resep_diagnosa`
  ADD CONSTRAINT `resep_diagnosa_ibfk_1` FOREIGN KEY (`id_resep`) REFERENCES `resep_dokter` (`id_resep`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `resep_diagnosa_ibfk_2` FOREIGN KEY (`id_diagnosa`) REFERENCES `diagnosam` (`id_diagnosa`) ON DELETE CASCADE ON UPDATE CASCADE;

DELIMITER $$
CREATE FUNCTION `fn_is_darurat` (`p_keluhan` TEXT)
RETURNS TINYINT(1)
DETERMINISTIC
BEGIN
  IF LOWER(COALESCE(p_keluhan, '')) REGEXP 'darurat|sesak|pingsan|nyeri dada|kecelakaan|tidak sadar|pendarahan|perdarahan|asma|tertusuk|jantung|darah|kejang|lemas' THEN
    RETURN 1;
  END IF;
  RETURN 0;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_stok_minimum_alert`
AFTER UPDATE ON `obatm`
FOR EACH ROW
BEGIN
  IF NEW.stok_sekarang <= NEW.stok_minimum
     AND (OLD.stok_sekarang > OLD.stok_minimum OR NEW.stok_sekarang < OLD.stok_sekarang) THEN
    INSERT INTO `notifikasi_stok_obat`
      (`id_obat`, `nama_obat`, `stok_sekarang`, `stok_minimum`, `pesan`, `tanggal_notifikasi`)
    VALUES
      (
        NEW.id_obat,
        NEW.nama_obat,
        NEW.stok_sekarang,
        NEW.stok_minimum,
        CONCAT(
          'Stok obat ',
          NEW.nama_obat,
          ' sudah mencapai batas minimum. Stok sekarang: ',
          NEW.stok_sekarang,
          ', stok minimum: ',
          NEW.stok_minimum
        ),
        NOW()
      );
  END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_kurangi_stok_obat`
AFTER INSERT ON `resep_dokter`
FOR EACH ROW
BEGIN
  IF NEW.id_obat IS NOT NULL THEN
    UPDATE `obatm`
    SET `stok_sekarang` = GREATEST(`stok_sekarang` - COALESCE(NEW.jumlah_keluar, 0), 0)
    WHERE `id_obat` = NEW.id_obat;
  END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_auto_order_list` ()
BEGIN
  SELECT
    `id_obat`,
    `nama_obat`,
    `satuan`,
    `stok_sekarang`,
    `stok_target`,
    (`stok_target` - `stok_sekarang`) AS `jumlah_perlu_dipesan`,
    CASE
      WHEN `stok_sekarang` = 0 THEN 'Habis'
      WHEN `stok_sekarang` < (`stok_target` * 0.25) THEN 'Kritis'
      WHEN `stok_sekarang` < (`stok_target` * 0.50) THEN 'Hampir Habis'
      ELSE 'Cukup'
    END AS `status_stok`
  FROM `obatm`
  WHERE `stok_sekarang` < `stok_target`
  ORDER BY `jumlah_perlu_dipesan` DESC;
END$$
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;
