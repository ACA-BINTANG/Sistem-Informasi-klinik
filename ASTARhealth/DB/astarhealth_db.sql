-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2026 at 04:12 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `astarhealth_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `diagnosam`
--

CREATE TABLE `diagnosam` (
  `id_diagnosa` varchar(6) NOT NULL,
  `nama_penyakit` varchar(50) DEFAULT NULL,
  `kategori` enum('Umum','Menular','Kronis','Lainnya') DEFAULT NULL,
  `tipe` enum('Ringan','Sedang','Berat') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diagnosam`
--

INSERT INTO `diagnosam` (`id_diagnosa`, `nama_penyakit`, `kategori`, `tipe`) VALUES
('DX024', 'Gerd', 'Umum', 'Sedang'),
('DX190', 'Asma', 'Kronis', 'Berat'),
('DX480', 'Demam', 'Umum', 'Berat'),
('DX761', 'flu berat', 'Menular', 'Sedang');

-- --------------------------------------------------------

--
-- Table structure for table `jadwalm`
--

CREATE TABLE `jadwalm` (
  `id_jadwal` varchar(6) NOT NULL,
  `id_staff` varchar(20) DEFAULT NULL,
  `tanggal` enum('Senin','Selasa','Rabu','Kamis','Jumat') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `status` enum('Buka','Tutup') NOT NULL DEFAULT 'Buka'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwalm`
--

INSERT INTO `jadwalm` (`id_jadwal`, `id_staff`, `tanggal`, `jam_mulai`, `jam_selesai`, `status`) VALUES
('JDW001', 'STF091', '', '13:55:00', '14:56:00', ''),
('JDW002', 'STF091', '', '01:35:00', '15:37:00', ''),
('JDW003', 'STF091', '', '15:44:00', '18:44:00', ''),
('JDW004', 'STF091', '', '16:15:00', '17:16:00', ''),
('JDW005', 'STF091', 'Rabu', '10:40:00', '16:40:00', 'Buka'),
('JDW006', 'STF091', 'Jumat', '08:00:00', '17:00:00', 'Buka');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi_stok_obat`
--

CREATE TABLE `notifikasi_stok_obat` (
  `id_notifikasi` int(11) NOT NULL,
  `id_obat` varchar(6) NOT NULL,
  `nama_obat` varchar(100) NOT NULL,
  `stok_sekarang` int(11) NOT NULL,
  `stok_minimum` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `tanggal_notifikasi` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `obatm`
--

CREATE TABLE `obatm` (
  `id_obat` varchar(6) NOT NULL,
  `nama_obat` varchar(150) NOT NULL,
  `stok_sekarang` int(11) NOT NULL DEFAULT 0,
  `stok_minimum` int(11) NOT NULL DEFAULT 10,
  `stok_target` int(11) NOT NULL DEFAULT 100,
  `satuan` enum('Tablet','Kapsul','Botol','Strip','Ampul','Sachet','Tube') NOT NULL,
  `harga_per_pcs` decimal(18,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `obatm`
--

INSERT INTO `obatm` (`id_obat`, `nama_obat`, `stok_sekarang`, `stok_minimum`, `stok_target`, `satuan`, `harga_per_pcs`) VALUES
('OBT002', 'ji', 80, 10, 30, 'Tablet', 6000.00),
('OBT003', 'ji mm', 15, 8, 8, 'Sachet', 52000.00);

--
-- Triggers `obatm`
--
DELIMITER $$
CREATE TRIGGER `trg_stok_minimum_alert` AFTER UPDATE ON `obatm` FOR EACH ROW BEGIN
    IF NEW.stok_sekarang <= NEW.stok_minimum THEN
        INSERT INTO notifikasi_stok_obat (
            id_obat,
            nama_obat,
            stok_sekarang,
            stok_minimum,
            pesan,
            tanggal_notifikasi
        )
        VALUES (
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pasienm`
--

CREATE TABLE `pasienm` (
  `id_pasien` varchar(6) NOT NULL,
  `id_user` varchar(6) DEFAULT NULL,
  `no_identitas` varchar(30) DEFAULT NULL,
  `nama_pasien` varchar(100) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `kategori_pasien` enum('Mahasiswa','Pegawai','Virtus','Sigap','Tamu') DEFAULT NULL,
  `unit_prodi` varchar(100) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pasienm`
--

INSERT INTO `pasienm` (`id_pasien`, `id_user`, `no_identitas`, `nama_pasien`, `jenis_kelamin`, `kategori_pasien`, `unit_prodi`, `alamat`, `no_hp`) VALUES
('4XTMNE', '4163C5', '0909090909', 'ZEID ALRAYAN PASHA', 'L', 'Sigap', '', 'GALUH MAS BLOK IX B/C 11', NULL),
('PSN153', 'USR312', '0120240029', 'dodi mangono', 'L', 'Mahasiswa', 'TPM', 'jupiter', '323-2323-232'),
('PSN174', 'USR460', '0920250050', 'Dholadolly', 'P', 'Mahasiswa', 'TRPL', 'venus', '888-8888-809'),
('PSN379', 'USR971', '0320250021', 'indah kusuma', 'P', 'Mahasiswa', 'MI', 'bekasi', '823-2823-223'),
('PSN410', 'USR904', '0120250055', 'pipi mimi ', 'P', 'Mahasiswa', 'TPM', 'venus', '432-2445-244'),
('PSN463', 'USR930', '0520240028', 'wowo gunanjar', 'L', 'Mahasiswa', 'TKBG', 'Mars', '666-7742-676'),
('PSN759', 'USR043', '0120240037', 'Dio gomana', 'L', 'Mahasiswa', 'TPM', 'bekasi', '444-4444-444'),
('PSN891', 'USR956', '2023212013', 'yoga doanaly', 'L', 'Pegawai', 'WKS', 'Mars', '334-4431-212'),
('PSN894', 'USR651', '0420250044', 'Nana Kusniawati', 'P', 'Mahasiswa', 'P4', 'Bekasi', '222-2232-333');

-- --------------------------------------------------------

--
-- Table structure for table `rekam_medis`
--

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
  `jenis_antrean` enum('Langsung','Jadwal') NOT NULL DEFAULT 'Langsung'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rekam_medis`
--

INSERT INTO `rekam_medis` (`id_rekam_medis`, `id_pasien`, `id_staff`, `id_diagnosa`, `no_antrian`, `tgl_kunjungan`, `waktu_booking`, `keluhan`, `hasil_pemeriksaan`, `status`, `jenis_antrean`) VALUES
('RM1035', '4XTMNE', 'STF091', 'DX190', 'A001', '2026-06-25', '15:24:58', 'sakit kepala', 'jawa nya kebanyakan itu kurangi', 'Selesai', 'Langsung'),
('RM1308', 'PSN894', 'STF091', 'DX761', 'A005', '2026-06-25', '05:25:37', 'd', 'sdds', 'Selesai', 'Langsung'),
('RM1524', 'PSN891', 'STF091', 'DX480', 'A002', '2026-06-26', '09:56:05', 'jawaa', 's', 'Selesai', 'Langsung'),
('RM2856', 'PSN463', NULL, NULL, 'A003', '2026-06-23', '04:00:37', 'mual', NULL, 'Menunggu', 'Langsung'),
('RM3167', '4XTMNE', 'STF091', 'DX761', 'A010', '2026-06-25', '08:37:24', 'k', 'n', 'Selesai', 'Langsung'),
('RM3980', '4XTMNE', 'STF091', 'DX761', 'A006', '2026-06-25', '05:37:31', 's', 'sdsdsdsds', 'Selesai', 'Langsung'),
('RM4201', '4XTMNE', 'STF091', 'DX480', 'A009', '2026-06-25', '08:22:19', 'g', ';', 'Selesai', 'Langsung'),
('RM4785', '4XTMNE', 'STF091', NULL, 'A011', '2026-06-07', '13:55:00', 'jawanya gilaa eyy', NULL, 'Menunggu', 'Langsung'),
('RM5032', 'PSN174', NULL, NULL, 'A002', '2026-06-23', '03:11:25', 'asma', NULL, 'Darurat', 'Langsung'),
('RM5872', '4XTMNE', 'STF091', 'DX480', 'A008', '2026-06-25', '07:27:52', 'qwwq', 'www', 'Selesai', 'Langsung'),
('RM8153', 'PSN463', 'STF091', NULL, 'A003', '2026-07-01', '12:35:00', 'pusing', NULL, 'Menunggu', 'Jadwal'),
('RM8495', '4XTMNE', 'STF091', 'DX190', 'A007', '2026-06-25', '05:44:07', 's', 'z', 'Selesai', 'Langsung'),
('RM8903', '4XTMNE', 'STF091', 'DX480', 'A001', '2026-06-26', '08:09:20', ' bbbb', 'kurag nyawit', 'Selesai', 'Langsung'),
('RM9026', 'PSN891', 'STF091', 'DX480', 'A004', '2026-06-23', '09:04:14', 'pusing', 'beliau sakit', 'Selesai', 'Langsung'),
('RM9547', '4XTMNE', 'STF091', NULL, 'A001', '2026-07-01', '10:42:00', 'jawa', NULL, 'Menunggu', 'Jadwal'),
('RM9825', 'PSN410', 'STF091', 'DX761', 'A002', '2026-07-01', '11:45:00', 'jawa jawa jawa', NULL, 'Menunggu', 'Jadwal');

-- --------------------------------------------------------

--
-- Table structure for table `resep_dokter`
--

CREATE TABLE `resep_dokter` (
  `id_resep` varchar(6) NOT NULL,
  `id_pasien` varchar(20) DEFAULT NULL,
  `tanggal_resep` datetime NOT NULL DEFAULT current_timestamp(),
  `id_rekam_medis` varchar(6) DEFAULT NULL,
  `id_obat` varchar(6) DEFAULT NULL,
  `jumlah_keluar` int(11) DEFAULT 0,
  `catatan_obat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resep_dokter`
--

INSERT INTO `resep_dokter` (`id_resep`, `id_rekam_medis`, `id_obat`, `jumlah_keluar`, `catatan_obat`) VALUES
('RSP357', 'RM8903', 'OBT002', 50, '3x1 sesudah makan yaa satir dulu'),
('RSP460', 'RM1524', 'OBT002', 50, '3x1 sesudah makan yaa satir dulu'),
('RSP476', 'RM3167', 'OBT003', 30, '3 kali sehari'),
('RSP491', 'RM1035', 'OBT002', 20, '3 kali sehari ya jawanya jadi sunda deh');

--
-- Triggers `resep_dokter`
--
DELIMITER $$
CREATE TRIGGER `trg_kurangi_stok_obat` AFTER INSERT ON `resep_dokter` FOR EACH ROW BEGIN
    UPDATE obatm
    SET stok_sekarang = stok_sekarang - NEW.jumlah_keluar
    WHERE id_obat = NEW.id_obat;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `resep_diagnosa`
-- Satu resep dapat memiliki satu atau lebih penyakit/keluhan.
--

CREATE TABLE `resep_diagnosa` (
  `id_resep` varchar(6) NOT NULL,
  `id_diagnosa` varchar(6) NOT NULL,
  PRIMARY KEY (`id_resep`, `id_diagnosa`),
  KEY `idx_resep_diagnosa_diagnosa` (`id_diagnosa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rujukan`
--

CREATE TABLE `rujukan` (
  `id_rujukan` varchar(6) NOT NULL,
  `id_pasien` varchar(6) DEFAULT NULL,
  `id_staff` varchar(6) DEFAULT NULL,
  `tujuan_rs` varchar(100) DEFAULT NULL,
  `alasan_rujukan` text DEFAULT NULL,
  `tgl_rujukan` date DEFAULT NULL,
  `status` enum('Proses','Selesai','Batal') DEFAULT 'Proses'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rujukan`
--

INSERT INTO `rujukan` (`id_rujukan`, `id_pasien`, `id_staff`, `tujuan_rs`, `alasan_rujukan`, `tgl_rujukan`, `status`) VALUES
('RUJ134', 'PSN894', 'STF091', 'Sentra Medika', 'terjepit besi jarinya', '2026-06-23', 'Proses'),
('RUJ160', 'PSN894', 'STF091', 'Siloam', 'tertimpa azab', '2026-06-23', 'Proses'),
('RUJ403', 'PSN174', 'STF091', 'Sentra Medika', 'Tertusuk pisau di lengan', '2026-06-19', 'Proses'),
('RUJ580', 'PSN153', 'STF091', 'Sentra Medika', 'tertusuk', '2026-06-23', 'Proses'),
('RUJ850', 'PSN759', 'STF091', 'Siloam', 'tertusuk paku di telapk tangan', '2026-06-19', 'Proses');

-- --------------------------------------------------------

--
-- Table structure for table `staffm`
--

CREATE TABLE `staffm` (
  `id_staff` varchar(6) NOT NULL,
  `id_user` varchar(6) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_identitas` varchar(20) NOT NULL,
  `jabatan` varchar(50) DEFAULT NULL,
  `instansi` varchar(50) DEFAULT NULL,
  `npa_idi` varchar(20) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staffm`
--

INSERT INTO `staffm` (`id_staff`, `id_user`, `nama_lengkap`, `no_identitas`, `jabatan`, `instansi`, `npa_idi`, `no_hp`) VALUES
('STF091', 'USR001', 'Ike Indahwati', '102310013', 'Dokter UKK', 'Siloam', '009231239113121', '811-8198-560'),
('STF109', 'USR730', 'Suswanto dewanto', '20250932032', 'Wakil Ketua Divisi K3', 'Kampus', '-', '333-4432-242');

-- --------------------------------------------------------

--
-- Table structure for table `supplierm`
--

CREATE TABLE `supplierm` (
  `id_supplier` varchar(6) NOT NULL,
  `nama_supplier` varchar(50) NOT NULL,
  `kontak` varchar(12) DEFAULT NULL,
  `alamat` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `userm`
--

CREATE TABLE `userm` (
  `id_user` varchar(6) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Dokter','K3','Pasien','Vendor') NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =====================================================
-- BUAT TABEL PENGADAAN_OBAT
-- =====================================================

CREATE TABLE IF NOT EXISTS `pengadaan_obat` (
  `id_pengadaan` varchar(6) NOT NULL,
  `id_obat` varchar(6) NOT NULL,
  `id_supplier` varchar(6),
  `jumlah_order` int(11) NOT NULL,
  `tgl_order` date NOT NULL,
  `tgl_estimasi_tiba` date,
  `status` enum('Pending','Proses','Diterima','Batal') NOT NULL DEFAULT 'Pending',
  `catatan` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengadaan`),
  KEY `id_obat` (`id_obat`),
  KEY `id_supplier` (`id_supplier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =====================================================
-- RIWAYAT CETAK LAPORAN
-- =====================================================

CREATE TABLE IF NOT EXISTS `riwayat_cetak_laporan` (
  `id_riwayat` bigint unsigned NOT NULL AUTO_INCREMENT,
  `jenis_laporan` varchar(50) NOT NULL,
  `judul_laporan` varchar(150) NOT NULL,
  `id_user` varchar(30) DEFAULT NULL,
  `nama_pencetak` varchar(150) NOT NULL,
  `parameter_filter` text DEFAULT NULL,
  `tanggal_cetak` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_riwayat`),
  KEY `idx_jenis_tanggal` (`jenis_laporan`,`tanggal_cetak`),
  KEY `idx_id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Dumping data for table `userm`
--

INSERT INTO `userm` (`id_user`, `username`, `email`, `password`, `role`, `nama_lengkap`) VALUES
('4163C5', 'admin', 'zeidalrayan@gmail.com', 'zeid123', 'Admin', 'ZEID ALRAYAN PASHA'),
('USR001', '1023190013@polytechnic.astar.ac.id', '1023190013@polytechnic.astar.ac.id', 'ike123', 'Dokter', 'Dokter Ike'),
('USR043', '0120240037@polytechnic.astar.ac.id', '0120240037@polytechnic.astar.ac.id', 'dio123', 'Pasien', 'Dio gomanda'),
('USR460', '0920250050@polytechnic.astar.ac.id', '0920250050@polytechnic.astar.ac.id', 'dholadolly123', 'Pasien', 'Dholadolly qwer'),
('USR651', '0420250044@polytechnic.astar.ac.id', '0420250044@polytechnic.astar.ac.id', 'nana123', 'Pasien', 'Nana Kusniawati'),
('USR730', '20250932032@polytechnic.astar.ac.id', '20250932032@polytechnic.astar.ac.id', 'suswanto123', 'K3', 'Suswanto'),
('USR904', '0120250055@polytechnic.astar.ac.id', '0120250055@polytechnic.astar.ac.id', 'pipi123', 'Pasien', 'pipi mimi '),
('USR930', '0520240028@polytechnic.astar.ac.id', '0520240028@polytechnic.astar.ac.id', 'wowo123', 'Pasien', 'wowo gunanjar'),
('USR956', '2023212013@polytechnic.astar.ac.id', '2023212013@polytechnic.astar.ac.id', 'yoga123', 'Pasien', 'yoga doanaly'),
('USR971', '0320250021@polytechnic.astar.ac.id', '0320250021@polytechnic.astar.ac.id', 'indah123', 'Pasien', 'indah kusuma');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `diagnosam`
--
ALTER TABLE `diagnosam`
  ADD PRIMARY KEY (`id_diagnosa`);

--
-- Indexes for table `jadwalm`
--
ALTER TABLE `jadwalm`
  ADD PRIMARY KEY (`id_jadwal`);

--
-- Indexes for table `notifikasi_stok_obat`
--
ALTER TABLE `notifikasi_stok_obat`
  ADD PRIMARY KEY (`id_notifikasi`);

--
-- Indexes for table `obatm`
--
ALTER TABLE `obatm`
  ADD PRIMARY KEY (`id_obat`);

--
-- Indexes for table `pasienm`
--
ALTER TABLE `pasienm`
  ADD PRIMARY KEY (`id_pasien`),
  ADD UNIQUE KEY `uk_pasienm_no_identitas` (`no_identitas`),
  ADD KEY `idx_pasienm_id_user` (`id_user`);

--
-- Indexes for table `rekam_medis`
--
ALTER TABLE `rekam_medis`
  ADD PRIMARY KEY (`id_rekam_medis`);

--
-- Indexes for table `resep_dokter`
--
ALTER TABLE `resep_dokter`
  ADD PRIMARY KEY (`id_resep`),
  ADD KEY `idx_resep_dokter_pasien` (`id_pasien`),
  ADD KEY `id_rekam_medis` (`id_rekam_medis`),
  ADD KEY `id_obat` (`id_obat`);

--
-- Indexes for table `rujukan`
--
ALTER TABLE `rujukan`
  ADD PRIMARY KEY (`id_rujukan`);

--
-- Indexes for table `staffm`
--
ALTER TABLE `staffm`
  ADD PRIMARY KEY (`id_staff`),
  ADD UNIQUE KEY `no_identitas` (`no_identitas`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `supplierm`
--
ALTER TABLE `supplierm`
  ADD PRIMARY KEY (`id_supplier`);

--
-- Indexes for table `userm`
--
ALTER TABLE `userm`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `uk_userm_username` (`username`),
  ADD UNIQUE KEY `uk_userm_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifikasi_stok_obat`
--
ALTER TABLE `notifikasi_stok_obat`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `resep_dokter`
--
ALTER TABLE `resep_dokter`
  ADD CONSTRAINT `resep_dokter_ibfk_1` FOREIGN KEY (`id_rekam_medis`) REFERENCES `rekam_medis` (`id_rekam_medis`) ON DELETE CASCADE,
  ADD CONSTRAINT `resep_dokter_ibfk_2` FOREIGN KEY (`id_obat`) REFERENCES `obatm` (`id_obat`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `resep_dokter_ibfk_3` FOREIGN KEY (`id_pasien`) REFERENCES `pasienm` (`id_pasien`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `resep_diagnosa`
  ADD CONSTRAINT `resep_diagnosa_ibfk_1` FOREIGN KEY (`id_resep`) REFERENCES `resep_dokter` (`id_resep`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `resep_diagnosa_ibfk_2` FOREIGN KEY (`id_diagnosa`) REFERENCES `diagnosam` (`id_diagnosa`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staffm`
--
ALTER TABLE `staffm`
  ADD CONSTRAINT `staffm_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `userm` (`id_user`) ON DELETE CASCADE;

ALTER TABLE `pasienm`
  ADD CONSTRAINT `pasienm_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `userm` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- INI RUJUKAN DITAMBAHIN YAA
-- 1. Tambah kolom hasil_rujukan yang kurang
ALTER TABLE rujukan ADD hasil_rujukan TEXT AFTER alasan_rujukan;

-- 2. Update status biar bisa nerima kata 'Aktif' sesuai kodingan PHP
ALTER TABLE rujukan MODIFY COLUMN status ENUM('Aktif','Proses','Selesai','Batal') DEFAULT 'Aktif';

-- =============================================================
-- DATA CONTOH REALISTIS: 20 BARIS UNTUK TABEL UTAMA ASTARhealth
-- Seluruh nama pribadi dan nomor kontak bersifat fiktif untuk pengujian.
-- Skrip aman dijalankan setelah versi data contoh lama karena ID contoh dibersihkan dahulu.
-- =============================================================
USE `astarhealth_db`;
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- Bersihkan data contoh versi lama maupun versi realistis sebelumnya.
DELETE FROM `resep_diagnosa` WHERE `id_resep` BETWEEN 'RP0001' AND 'RP0020';
DELETE FROM `notifikasi_stok_obat` WHERE `id_notifikasi` BETWEEN 9001 AND 9020;
DELETE FROM `pengadaan_obat` WHERE `id_pengadaan` BETWEEN 'PG0001' AND 'PG0020';
DELETE FROM `rujukan` WHERE `id_rujukan` BETWEEN 'RJ0001' AND 'RJ0020';
DELETE FROM `resep_dokter` WHERE `id_resep` BETWEEN 'RP0001' AND 'RP0020';
DELETE FROM `rekam_medis` WHERE `id_rekam_medis` BETWEEN 'MD0001' AND 'MD0020';
DELETE FROM `jadwalm` WHERE `id_jadwal` BETWEEN 'JD0001' AND 'JD0020';
DELETE FROM `obatm` WHERE `id_obat` BETWEEN 'OD0001' AND 'OD0020';
DELETE FROM `diagnosam` WHERE `id_diagnosa` BETWEEN 'DD0001' AND 'DD0020';
DELETE FROM `supplierm` WHERE `id_supplier` BETWEEN 'SP0001' AND 'SP0020';
DELETE FROM `pasienm` WHERE `id_pasien` BETWEEN 'PD0001' AND 'PD0020';
DELETE FROM `staffm` WHERE `id_staff` BETWEEN 'SD0001' AND 'SD0020';
DELETE FROM `userm` WHERE `id_user` BETWEEN 'UD0001' AND 'UD0020';

-- 1. userm: 20 akun contoh realistis
INSERT INTO `userm` (`id_user`,`username`,`email`,`password`,`role`,`nama_lengkap`) VALUES
('UD0001', 'alya.ramadhani', 'alya.ramadhani@astar.ac.id', 'Astar123', 'Pasien', 'Alya Putri Ramadhani'),
('UD0002', 'rizky.maulana', 'rizky.maulana@astar.ac.id', 'Astar123', 'Pasien', 'Rizky Maulana'),
('UD0003', 'siti.aisyah', 'siti.aisyah@astar.ac.id', 'Astar123', 'Pasien', 'Siti Nur Aisyah'),
('UD0004', 'andi.pratama', 'andi.pratama@astar.ac.id', 'Astar123', 'Pasien', 'Andi Pratama'),
('UD0005', 'nabila.zahra', 'nabila.zahra@astar.ac.id', 'Astar123', 'Pasien', 'Nabila Zahra'),
('UD0006', 'fajar.hidayat', 'fajar.hidayat@astar.ac.id', 'Astar123', 'Pasien', 'Fajar Hidayat'),
('UD0007', 'dewi.lestari', 'dewi.lestari@astar.ac.id', 'Astar123', 'Pasien', 'Dewi Lestari'),
('UD0008', 'muhammad.arif', 'muhammad.arif@astar.ac.id', 'Astar123', 'Pasien', 'Muhammad Arif'),
('UD0009', 'citra.maharani', 'citra.maharani@astar.ac.id', 'Astar123', 'Pasien', 'Citra Maharani'),
('UD0010', 'bagas.saputra', 'bagas.saputra@astar.ac.id', 'Astar123', 'Pasien', 'Bagas Saputra'),
('UD0011', 'putri.amelia', 'putri.amelia@astar.ac.id', 'Astar123', 'Pasien', 'Putri Amelia'),
('UD0012', 'dimas.kurniawan', 'dimas.kurniawan@astar.ac.id', 'Astar123', 'Pasien', 'Dimas Kurniawan'),
('UD0013', 'rina.oktaviani', 'rina.oktaviani@astar.ac.id', 'Astar123', 'Pasien', 'Rina Oktaviani'),
('UD0014', 'ahmad.fauzan', 'ahmad.fauzan@astar.ac.id', 'Astar123', 'Pasien', 'Ahmad Fauzan'),
('UD0015', 'maya.salsabila', 'maya.salsabila@astar.ac.id', 'Astar123', 'Pasien', 'Maya Salsabila'),
('UD0016', 'raka.aditya', 'raka.aditya@astar.ac.id', 'Astar123', 'Pasien', 'Raka Aditya'),
('UD0017', 'intan.permata', 'intan.permata@astar.ac.id', 'Astar123', 'Pasien', 'Intan Permata'),
('UD0018', 'yoga.ramadhan', 'yoga.ramadhan@astar.ac.id', 'Astar123', 'Pasien', 'Yoga Ramadhan'),
('UD0019', 'anisa.rahmawati', 'anisa.rahmawati@astar.ac.id', 'Astar123', 'Pasien', 'Anisa Rahmawati'),
('UD0020', 'reza.firmansyah', 'reza.firmansyah@astar.ac.id', 'Astar123', 'Pasien', 'Reza Firmansyah');

-- 2. pasienm: 20 pasien dengan identitas fiktif
INSERT INTO `pasienm` (`id_pasien`,`id_user`,`no_identitas`,`nama_pasien`,`jenis_kelamin`,`kategori_pasien`,`unit_prodi`,`alamat`,`no_hp`) VALUES
('PD0001', 'UD0001', '202600000001', 'Alya Putri Ramadhani', 'P', 'Mahasiswa', 'Manajemen Informatika', 'Jl. Melati Raya No. 12, Bekasi', '081312000001'),
('PD0002', 'UD0002', '202600000002', 'Rizky Maulana', 'L', 'Mahasiswa', 'Teknik Mesin', 'Jl. Cendana No. 8, Cikarang', '081312000002'),
('PD0003', 'UD0003', '202600000003', 'Siti Nur Aisyah', 'P', 'Pegawai', 'Administrasi Akademik', 'Jl. Anggrek Blok C2, Bekasi', '081312000003'),
('PD0004', 'UD0004', '202600000004', 'Andi Pratama', 'L', 'Sigap', 'Operasional', 'Perumahan Taman Sentosa, Cikarang', '081312000004'),
('PD0005', 'UD0005', '202600000005', 'Nabila Zahra', 'P', 'Virtus', 'Quality Assurance', 'Jl. Kenanga No. 21, Karawang', '081312000005'),
('PD0006', 'UD0006', '202600000006', 'Fajar Hidayat', 'L', 'Mahasiswa', 'Teknik Informatika', 'Jl. Mawar Dalam No. 4, Bekasi', '081312000006'),
('PD0007', 'UD0007', '202600000007', 'Dewi Lestari', 'P', 'Pegawai', 'Keuangan', 'Jl. Pahlawan No. 17, Tambun', '081312000007'),
('PD0008', 'UD0008', '202600000008', 'Muhammad Arif', 'L', 'Mahasiswa', 'Teknik Elektro', 'Jl. Kutilang No. 6, Cibitung', '081312000008'),
('PD0009', 'UD0009', '3275011509010009', 'Citra Maharani', 'P', 'Tamu', 'PT Nusantara Teknologi', 'Jl. Industri Selatan, Cikarang', '081312000009'),
('PD0010', 'UD0010', '202600000010', 'Bagas Saputra', 'L', 'Sigap', 'Keamanan', 'Jl. Raya Setu No. 35, Bekasi', '081312000010'),
('PD0011', 'UD0011', '202600000011', 'Putri Amelia', 'P', 'Mahasiswa', 'Manajemen Logistik', 'Jl. Flamboyan No. 9, Karawang', '081312000011'),
('PD0012', 'UD0012', '202600000012', 'Dimas Kurniawan', 'L', 'Virtus', 'Produksi', 'Perumahan Graha Asri, Cikarang', '081312000012'),
('PD0013', 'UD0013', '202600000013', 'Rina Oktaviani', 'P', 'Pegawai', 'Sumber Daya Manusia', 'Jl. Wijaya Kusuma No. 18, Bekasi', '081312000013'),
('PD0014', 'UD0014', '202600000014', 'Ahmad Fauzan', 'L', 'Mahasiswa', 'Teknik Otomotif', 'Jl. Raya Babelan No. 10, Bekasi', '081312000014'),
('PD0015', 'UD0015', '3275011510010015', 'Maya Salsabila', 'P', 'Tamu', 'CV Karya Mandiri', 'Jl. Niaga Utama No. 3, Cikarang', '081312000015'),
('PD0016', 'UD0016', '202600000016', 'Raka Aditya', 'L', 'Mahasiswa', 'Teknik Sipil', 'Jl. Kemuning No. 15, Bekasi', '081312000016'),
('PD0017', 'UD0017', '202600000017', 'Intan Permata', 'P', 'Sigap', 'Pelayanan Umum', 'Jl. Dahlia No. 27, Karawang', '081312000017'),
('PD0018', 'UD0018', '202600000018', 'Yoga Ramadhan', 'L', 'Pegawai', 'Teknologi Informasi', 'Jl. Jati Asih No. 11, Bekasi', '081312000018'),
('PD0019', 'UD0019', '202600000019', 'Anisa Rahmawati', 'P', 'Mahasiswa', 'Akuntansi', 'Jl. Teratai No. 19, Tambun', '081312000019'),
('PD0020', 'UD0020', '202600000020', 'Reza Firmansyah', 'L', 'Virtus', 'Maintenance', 'Jl. Raya Lemahabang No. 7, Cikarang', '081312000020');

-- 3. staffm: 20 tenaga klinik dan petugas
INSERT INTO `staffm` (`id_staff`,`id_user`,`nama_lengkap`,`no_identitas`,`jabatan`,`instansi`,`npa_idi`,`no_hp`) VALUES
('SD0001', NULL, 'dr. Rendra Mahardika', '10202600001', 'Dokter Umum', 'Klinik ASTARhealth', 'NPAIDI202600001', '081222000001'),
('SD0002', NULL, 'dr. Nadia Permatasari', '10202600002', 'Dokter Umum', 'Klinik ASTARhealth', 'NPAIDI202600002', '081222000002'),
('SD0003', NULL, 'dr. Muhammad Iqbal', '10202600003', 'Dokter Umum', 'Klinik ASTARhealth', 'NPAIDI202600003', '081222000003'),
('SD0004', NULL, 'dr. Livia Anindita', '10202600004', 'Dokter Umum', 'Klinik ASTARhealth', 'NPAIDI202600004', '081222000004'),
('SD0005', NULL, 'Ns. Fitri Handayani', '10202600005', 'Perawat', 'Klinik ASTARhealth', '', '081222000005'),
('SD0006', NULL, 'Ns. Bayu Prakoso', '10202600006', 'Perawat', 'Klinik ASTARhealth', '', '081222000006'),
('SD0007', NULL, 'Apt. Rani Wulandari', '10202600007', 'Apoteker', 'Klinik ASTARhealth', '', '081222000007'),
('SD0008', NULL, 'Apt. Denny Setiawan', '10202600008', 'Apoteker', 'Klinik ASTARhealth', '', '081222000008'),
('SD0009', NULL, 'Rudi Hartono', '10202600009', 'Petugas K3', 'ASTAR', '', '081222000009'),
('SD0010', NULL, 'Mira Puspitasari', '10202600010', 'Petugas K3', 'ASTAR', '', '081222000010'),
('SD0011', NULL, 'Hendra Wijaya', '10202600011', 'Petugas K3', 'ASTAR', '', '081222000011'),
('SD0012', NULL, 'Siska Aprilia', '10202600012', 'Petugas K3', 'ASTAR', '', '081222000012'),
('SD0013', NULL, 'Taufik Akbar', '10202600013', 'Administrasi Klinik', 'ASTAR', '', '081222000013'),
('SD0014', NULL, 'Lina Marlina', '10202600014', 'Administrasi Klinik', 'ASTAR', '', '081222000014'),
('SD0015', NULL, 'Wahyu Nugroho', '10202600015', 'Analis Kesehatan', 'Klinik ASTARhealth', '', '081222000015'),
('SD0016', NULL, 'Ratih Kusumaningrum', '10202600016', 'Analis Kesehatan', 'Klinik ASTARhealth', '', '081222000016'),
('SD0017', NULL, 'Dedi Irawan', '10202600017', 'Pengemudi Ambulans', 'ASTAR', '', '081222000017'),
('SD0018', NULL, 'Sri Wahyuni', '10202600018', 'Petugas Kebersihan', 'ASTAR', '', '081222000018'),
('SD0019', NULL, 'Arman Hakim', '10202600019', 'Koordinator Klinik', 'ASTAR', '', '081222000019'),
('SD0020', NULL, 'Yuni Kartika', '10202600020', 'Petugas Rekam Medis', 'Klinik ASTARhealth', '', '081222000020');

-- 4. supplierm: 20 distributor farmasi
INSERT INTO `supplierm` (`id_supplier`,`nama_supplier`,`kontak`,`alamat`) VALUES
('SP0001', 'PT Kimia Farma Trading & Distribution - Bekasi', '021830000001', 'Kawasan Industri Jababeka, Cikarang'),
('SP0002', 'PT Enseval Putera Megatrading - Bekasi', '021830000002', 'Jl. Sultan Agung, Bekasi'),
('SP0003', 'PT Anugerah Pharmindo Lestari - Cikarang', '021830000003', 'Kawasan Industri Delta Silicon, Cikarang'),
('SP0004', 'PT Millennium Pharmacon International - Bekasi', '021830000004', 'Jl. Ahmad Yani, Bekasi'),
('SP0005', 'PT Dos Ni Roha - Bekasi', '021830000005', 'Jl. Raya Narogong, Bekasi'),
('SP0006', 'PT Merapi Utama Pharma - Cikarang', '021830000006', 'Kawasan Industri MM2100, Cibitung'),
('SP0007', 'PT Parit Padang Global - Bekasi', '021830000007', 'Jl. Cut Meutia, Bekasi'),
('SP0008', 'PT Bina San Prima - Karawang', '021830000008', 'Kawasan Industri KIIC, Karawang'),
('SP0009', 'PT Rajawali Nusindo - Bekasi', '021830000009', 'Jl. Juanda, Bekasi'),
('SP0010', 'PT Penta Valent - Bekasi', '021830000010', 'Jl. KH Noer Ali, Bekasi'),
('SP0011', 'PT Sapta Sari Tama - Cikarang', '021830000011', 'Jl. Industri Utara, Cikarang'),
('SP0012', 'PT Mensa Bina Sukses - Bekasi', '021830000012', 'Jl. Raya Pekayon, Bekasi'),
('SP0013', 'PT Antarmitra Sembada - Bekasi', '021830000013', 'Jl. Raya Kalimalang, Bekasi'),
('SP0014', 'PT Kebayoran Pharma - Cikarang', '021830000014', 'Kawasan Industri EJIP, Cikarang'),
('SP0015', 'PT Anugrah Argon Medica - Bekasi', '021830000015', 'Jl. Raya Mustikasari, Bekasi'),
('SP0016', 'PT United Dico Citas - Bekasi', '021830000016', 'Jl. Patriot, Bekasi'),
('SP0017', 'PT Distriversa Buanamas - Cikarang', '021830000017', 'Jl. Tekno Boulevard, Cikarang'),
('SP0018', 'PT Brataco - Bekasi', '021830000018', 'Jl. Ir. H. Juanda, Bekasi'),
('SP0019', 'PT Bio Farma Distribusi - Jawa Barat', '021830000019', 'Jl. Pasteur, Bandung'),
('SP0020', 'PT Pharos Indonesia Distribution - Bekasi', '021830000020', 'Jl. Raya Pondok Gede, Bekasi');

-- 5. diagnosam: 20 penyakit/diagnosa
INSERT INTO `diagnosam` (`id_diagnosa`,`nama_penyakit`,`kategori`,`tipe`) VALUES
('DD0001', 'Influenza', 'Menular', 'Ringan'),
('DD0002', 'Infeksi Saluran Pernapasan Akut', 'Menular', 'Sedang'),
('DD0003', 'Gastritis', 'Umum', 'Sedang'),
('DD0004', 'Migrain', 'Kronis', 'Sedang'),
('DD0005', 'Hipertensi', 'Kronis', 'Berat'),
('DD0006', 'Diabetes Melitus Tipe 2', 'Kronis', 'Berat'),
('DD0007', 'Dermatitis Alergi', 'Umum', 'Ringan'),
('DD0008', 'Faringitis', 'Menular', 'Sedang'),
('DD0009', 'Diare Akut', 'Menular', 'Sedang'),
('DD0010', 'Vertigo', 'Kronis', 'Sedang'),
('DD0011', 'Asma Bronkial', 'Kronis', 'Berat'),
('DD0012', 'Konjungtivitis', 'Menular', 'Ringan'),
('DD0013', 'Nyeri Punggung Bawah', 'Umum', 'Sedang'),
('DD0014', 'Demam Berdarah Dengue', 'Menular', 'Berat'),
('DD0015', 'Tonsilitis', 'Menular', 'Sedang'),
('DD0016', 'Infeksi Saluran Kemih', 'Menular', 'Sedang'),
('DD0017', 'Anemia Defisiensi Besi', 'Kronis', 'Sedang'),
('DD0018', 'Dispepsia', 'Umum', 'Ringan'),
('DD0019', 'Sinusitis', 'Menular', 'Sedang'),
('DD0020', 'Cedera Jaringan Lunak', 'Lainnya', 'Ringan');

-- 6. obatm: 20 obat dengan nama generik dan merek
INSERT INTO `obatm` (`id_obat`,`nama_obat`,`stok_sekarang`,`stok_minimum`,`stok_target`,`satuan`,`harga_per_pcs`) VALUES
('OD0001', 'Paracetamol 500 mg - Sanmol', 75, 20, 150, 'Tablet', 650.00),
('OD0002', 'Amoxicillin 500 mg - Amoxsan', 60, 15, 120, 'Kapsul', 1250.00),
('OD0003', 'Omeprazole 20 mg - Omed', 45, 15, 100, 'Kapsul', 1100.00),
('OD0004', 'Cetirizine 10 mg - Incidal-OD', 40, 12, 80, 'Tablet', 1800.00),
('OD0005', 'Antasida DOEN - Promag', 55, 15, 100, 'Tablet', 900.00),
('OD0006', 'Vitamin C 500 mg - IPI', 80, 20, 150, 'Tablet', 500.00),
('OD0007', 'Ibuprofen 400 mg - Proris', 35, 15, 90, 'Tablet', 1400.00),
('OD0008', 'Ambroxol 30 mg - Mucos', 30, 12, 80, 'Tablet', 1350.00),
('OD0009', 'Salbutamol 2 mg - Ventolin', 28, 10, 60, 'Tablet', 1600.00),
('OD0010', 'Metformin 500 mg - Glucophage', 50, 20, 120, 'Tablet', 1700.00),
('OD0011', 'Amlodipine 5 mg - Norvask', 42, 15, 100, 'Tablet', 2300.00),
('OD0012', 'Oralit - Kimia Farma', 65, 20, 120, 'Sachet', 1200.00),
('OD0013', 'Chloramphenicol Tetes Mata - Cendo Fenicol', 18, 8, 40, 'Botol', 18500.00),
('OD0014', 'Miconazole Cream - Daktarin', 16, 8, 35, 'Tube', 29500.00),
('OD0015', 'Povidone Iodine 10% - Betadine', 22, 10, 50, 'Botol', 21000.00),
('OD0016', 'Ferrous Fumarate - Sangobion', 38, 12, 80, 'Kapsul', 2500.00),
('OD0017', 'Loperamide 2 mg - Imodium', 32, 10, 70, 'Tablet', 2100.00),
('OD0018', 'Diclofenac Sodium 50 mg - Voltaren', 36, 12, 80, 'Tablet', 1900.00),
('OD0019', 'Azithromycin 500 mg - Zithromax', 24, 10, 60, 'Tablet', 7800.00),
('OD0020', 'Lansoprazole 30 mg - Prevacid', 34, 12, 80, 'Kapsul', 3200.00);

-- 7. jadwalm: 20 slot jadwal dokter
INSERT INTO `jadwalm` (`id_jadwal`,`id_staff`,`tanggal`,`jam_mulai`,`jam_selesai`,`status`) VALUES
('JD0001', 'STF091', 'Senin', '08:00:00', '10:00:00', 'Buka'),
('JD0002', 'STF091', 'Selasa', '08:00:00', '10:00:00', 'Buka'),
('JD0003', 'STF091', 'Rabu', '08:00:00', '10:00:00', 'Buka'),
('JD0004', 'STF091', 'Kamis', '08:00:00', '10:00:00', 'Tutup'),
('JD0005', 'STF091', 'Jumat', '08:00:00', '10:00:00', 'Buka'),
('JD0006', 'STF091', 'Senin', '10:00:00', '12:00:00', 'Buka'),
('JD0007', 'STF091', 'Selasa', '10:00:00', '12:00:00', 'Buka'),
('JD0008', 'STF091', 'Rabu', '10:00:00', '12:00:00', 'Tutup'),
('JD0009', 'STF091', 'Kamis', '10:00:00', '12:00:00', 'Buka'),
('JD0010', 'STF091', 'Jumat', '10:00:00', '12:00:00', 'Buka'),
('JD0011', 'STF091', 'Senin', '12:00:00', '14:00:00', 'Buka'),
('JD0012', 'STF091', 'Selasa', '12:00:00', '14:00:00', 'Tutup'),
('JD0013', 'STF091', 'Rabu', '12:00:00', '14:00:00', 'Buka'),
('JD0014', 'STF091', 'Kamis', '12:00:00', '14:00:00', 'Buka'),
('JD0015', 'STF091', 'Jumat', '12:00:00', '14:00:00', 'Buka'),
('JD0016', 'STF091', 'Senin', '14:00:00', '16:00:00', 'Tutup'),
('JD0017', 'STF091', 'Selasa', '14:00:00', '16:00:00', 'Buka'),
('JD0018', 'STF091', 'Rabu', '14:00:00', '16:00:00', 'Buka'),
('JD0019', 'STF091', 'Kamis', '14:00:00', '16:00:00', 'Buka'),
('JD0020', 'STF091', 'Jumat', '14:00:00', '16:00:00', 'Tutup');

-- 8. rekam_medis: 20 kunjungan klinik
INSERT INTO `rekam_medis` (`id_rekam_medis`,`id_pasien`,`id_staff`,`id_diagnosa`,`no_antrian`,`tgl_kunjungan`,`waktu_booking`,`keluhan`,`hasil_pemeriksaan`,`status`,`jenis_antrean`) VALUES
('MD0001', 'PD0001', 'STF091', 'DD0001', 'A001', '2026-06-26', '08:03:00', 'Demam, pilek, dan badan terasa pegal selama dua hari', 'Istirahat cukup, hidrasi, dan terapi simptomatik.', 'Selesai', 'Langsung'),
('MD0002', 'PD0001', 'STF091', 'DD0002', 'A002', '2026-06-27', '09:06:00', 'Batuk berdahak disertai tenggorokan nyeri', 'Terapi antibiotik sesuai indikasi dan kontrol tiga hari.', 'Selesai', 'Jadwal'),
('MD0003', 'PD0001', 'STF091', 'DD0003', 'A003', '2026-06-28', '10:09:00', 'Nyeri ulu hati setelah terlambat makan', 'Pola makan teratur dan hindari makanan pedas.', 'Selesai', 'Langsung'),
('MD0004', 'PD0001', 'STF091', 'DD0004', 'A004', '2026-06-29', '11:12:00', 'Sakit kepala berdenyut disertai mual', 'Istirahat di ruangan tenang dan obat pereda nyeri.', 'Selesai', 'Jadwal'),
('MD0005', 'PD0001', 'STF091', 'DD0005', 'A005', '2026-06-30', '12:15:00', 'Tekanan darah meningkat saat pemeriksaan rutin', 'Pemantauan tekanan darah dan terapi antihipertensi.', 'Selesai', 'Langsung'),
('MD0006', 'PD0001', 'STF091', 'DD0006', 'A006', '2026-07-01', '13:18:00', 'Sering haus dan mudah lelah', 'Kontrol gula darah dan edukasi pola makan.', 'Selesai', 'Jadwal'),
('MD0007', 'PD0001', 'STF091', 'DD0007', 'A007', '2026-07-02', '14:21:00', 'Gatal dan kemerahan setelah terkena debu', 'Antihistamin dan hindari pemicu alergi.', 'Selesai', 'Langsung'),
('MD0008', 'PD0001', 'STF091', 'DD0008', 'A008', '2026-07-03', '15:24:00', 'Nyeri menelan dan suara serak', 'Terapi simptomatik serta berkumur air hangat.', 'Selesai', 'Jadwal'),
('MD0009', 'PD0001', 'STF091', 'DD0009', 'A009', '2026-07-04', '08:27:00', 'Buang air besar cair lebih dari tiga kali', 'Rehidrasi oral dan pemantauan tanda dehidrasi.', 'Selesai', 'Langsung'),
('MD0010', 'PD0001', 'STF091', 'DD0010', 'A010', '2026-07-05', '09:30:00', 'Pusing berputar saat bangun dari tempat tidur', 'Latihan posisi dan obat antivertigo.', 'Selesai', 'Jadwal'),
('MD0011', 'PD0001', 'STF091', 'DD0011', 'A011', '2026-07-06', '10:33:00', 'Sesak napas berulang setelah aktivitas', 'Nebulisasi dan evaluasi kontrol asma.', 'Selesai', 'Langsung'),
('MD0012', 'PD0001', 'STF091', 'DD0012', 'A012', '2026-07-07', '11:36:00', 'Mata merah, berair, dan terasa mengganjal', 'Tetes mata dan menjaga kebersihan tangan.', 'Selesai', 'Jadwal'),
('MD0013', 'PD0001', 'STF091', 'DD0013', 'A013', '2026-07-08', '12:39:00', 'Nyeri pinggang setelah mengangkat barang', 'Kompres hangat dan pembatasan aktivitas berat.', 'Selesai', 'Langsung'),
('MD0014', 'PD0001', 'STF091', 'DD0014', 'A014', '2026-07-09', '13:42:00', 'Demam tinggi disertai nyeri sendi', 'Rujuk pemeriksaan laboratorium dan observasi.', 'Selesai', 'Jadwal'),
('MD0015', 'PD0001', 'STF091', 'DD0015', 'A015', '2026-07-10', '14:45:00', 'Amandel membesar dan nyeri saat menelan', 'Terapi antiinflamasi dan evaluasi infeksi.', 'Selesai', 'Langsung'),
('MD0016', 'PD0001', 'STF091', 'DD0016', 'A016', '2026-07-11', '15:48:00', 'Nyeri saat buang air kecil', 'Pemeriksaan urin dan terapi antibiotik sesuai hasil.', 'Selesai', 'Jadwal'),
('MD0017', 'PD0001', 'STF091', 'DD0017', 'A017', '2026-07-12', '08:51:00', 'Mudah lelah dan tampak pucat', NULL, 'Menunggu', 'Langsung'),
('MD0018', 'PD0001', 'STF091', 'DD0018', 'A018', '2026-07-13', '09:54:00', 'Perut kembung dan cepat kenyang', NULL, 'Darurat', 'Jadwal'),
('MD0019', 'PD0001', 'STF091', 'DD0019', 'A019', '2026-07-14', '10:57:00', 'Hidung tersumbat dan nyeri pada wajah', NULL, 'Diproses', 'Langsung'),
('MD0020', 'PD0001', 'STF091', 'DD0020', 'A020', '2026-07-15', '11:00:00', 'Pergelangan kaki terkilir saat berjalan', NULL, 'Menunggu', 'Jadwal');

-- 9. resep_dokter: 20 transaksi resep
INSERT INTO `resep_dokter` (`id_resep`,`id_pasien`,`tanggal_resep`,`id_rekam_medis`,`id_obat`,`jumlah_keluar`,`catatan_obat`) VALUES
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
('RP0020', 'PD0001', '2026-07-15 11:15:00', NULL, 'OD0020', 3, 'Gunakan sesuai petunjuk dokter');

-- 10. resep_diagnosa: relasi resep dan penyakit
INSERT INTO `resep_diagnosa` (`id_resep`,`id_diagnosa`) VALUES
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
('RP0020', 'DD0020');

-- 11. rujukan: 20 transaksi rujukan
INSERT INTO `rujukan` (`id_rujukan`,`id_pasien`,`id_staff`,`tujuan_rs`,`alasan_rujukan`,`tgl_rujukan`,`status`) VALUES
('RJ0001', 'PD0001', 'STF091', 'RS Siloam Lippo Cikarang', 'Memerlukan pemeriksaan laboratorium dan observasi lanjutan', '2026-06-26', 'Selesai'),
('RJ0002', 'PD0001', 'STF091', 'RS Hermina Grand Wisata', 'Membutuhkan konsultasi dokter spesialis penyakit dalam', '2026-06-27', 'Selesai'),
('RJ0003', 'PD0001', 'STF091', 'RS Mitra Keluarga Bekasi Timur', 'Memerlukan pemeriksaan radiologi', '2026-06-28', 'Batal'),
('RJ0004', 'PD0001', 'STF091', 'RS EMC Cikarang', 'Membutuhkan penanganan spesialis THT', '2026-06-29', 'Proses'),
('RJ0005', 'PD0001', 'STF091', 'RSUD Kabupaten Bekasi', 'Perlu evaluasi jantung dan tekanan darah lebih lanjut', '2026-06-30', 'Selesai'),
('RJ0006', 'PD0001', 'STF091', 'RS Primaya Bekasi Barat', 'Memerlukan pemeriksaan laboratorium dan observasi lanjutan', '2026-07-01', 'Selesai'),
('RJ0007', 'PD0001', 'STF091', 'RS Permata Keluarga Jababeka', 'Membutuhkan konsultasi dokter spesialis penyakit dalam', '2026-07-02', 'Batal'),
('RJ0008', 'PD0001', 'STF091', 'RS Bhakti Husada Cikarang', 'Memerlukan pemeriksaan radiologi', '2026-07-03', 'Proses'),
('RJ0009', 'PD0001', 'STF091', 'RS Annisa Cikarang', 'Membutuhkan penanganan spesialis THT', '2026-07-04', 'Selesai'),
('RJ0010', 'PD0001', 'STF091', 'RS Sentra Medika Cikarang', 'Perlu evaluasi jantung dan tekanan darah lebih lanjut', '2026-07-05', 'Selesai'),
('RJ0011', 'PD0001', 'STF091', 'RS Amanda Cikarang Selatan', 'Memerlukan pemeriksaan laboratorium dan observasi lanjutan', '2026-07-06', 'Batal'),
('RJ0012', 'PD0001', 'STF091', 'RS Karya Medika Bantar Gebang', 'Membutuhkan konsultasi dokter spesialis penyakit dalam', '2026-07-07', 'Proses'),
('RJ0013', 'PD0001', 'STF091', 'RS Bella Bekasi', 'Memerlukan pemeriksaan radiologi', '2026-07-08', 'Selesai'),
('RJ0014', 'PD0001', 'STF091', 'RS Kartika Husada Setu', 'Membutuhkan penanganan spesialis THT', '2026-07-09', 'Selesai'),
('RJ0015', 'PD0001', 'STF091', 'RS Cibitung Medika', 'Perlu evaluasi jantung dan tekanan darah lebih lanjut', '2026-07-10', 'Batal'),
('RJ0016', 'PD0001', 'STF091', 'RS Tiara Bekasi', 'Memerlukan pemeriksaan laboratorium dan observasi lanjutan', '2026-07-11', 'Proses'),
('RJ0017', 'PD0001', 'STF091', 'RS Harapan Keluarga Jababeka', 'Membutuhkan konsultasi dokter spesialis penyakit dalam', '2026-07-12', 'Selesai'),
('RJ0018', 'PD0001', 'STF091', 'RS Metro Hospitals Cikarang', 'Memerlukan pemeriksaan radiologi', '2026-07-13', 'Selesai'),
('RJ0019', 'PD0001', 'STF091', 'RS Mekar Sari Bekasi', 'Membutuhkan penanganan spesialis THT', '2026-07-14', 'Batal'),
('RJ0020', 'PD0001', 'STF091', 'RSUD dr. Chasbullah Abdulmadjid Bekasi', 'Perlu evaluasi jantung dan tekanan darah lebih lanjut', '2026-07-15', 'Proses');

-- 12. pengadaan_obat: 20 transaksi pengadaan tanpa aksi edit
INSERT INTO `pengadaan_obat` (`id_pengadaan`,`id_obat`,`id_supplier`,`jumlah_order`,`tgl_order`,`tgl_estimasi_tiba`,`status`,`catatan`) VALUES
('PG0001', 'OD0001', 'SP0001', 45, '2026-06-26', '2026-06-30', 'Proses', 'Pengadaan Paracetamol 500 mg - Sanmol untuk menjaga stok minimum klinik'),
('PG0002', 'OD0002', 'SP0002', 50, '2026-06-27', '2026-07-02', 'Diterima', 'Pengadaan Amoxicillin 500 mg - Amoxsan untuk menjaga stok minimum klinik'),
('PG0003', 'OD0003', 'SP0003', 55, '2026-06-28', '2026-07-01', 'Diterima', 'Pengadaan Omeprazole 20 mg - Omed untuk menjaga stok minimum klinik'),
('PG0004', 'OD0004', 'SP0004', 60, '2026-06-29', '2026-07-03', 'Batal', 'Pengadaan Cetirizine 10 mg - Incidal-OD untuk menjaga stok minimum klinik'),
('PG0005', 'OD0005', 'SP0005', 65, '2026-06-30', '2026-07-05', 'Pending', 'Pengadaan Antasida DOEN - Promag untuk menjaga stok minimum klinik'),
('PG0006', 'OD0006', 'SP0006', 70, '2026-07-01', '2026-07-04', 'Proses', 'Pengadaan Vitamin C 500 mg - IPI untuk menjaga stok minimum klinik'),
('PG0007', 'OD0007', 'SP0007', 75, '2026-07-02', '2026-07-06', 'Diterima', 'Pengadaan Ibuprofen 400 mg - Proris untuk menjaga stok minimum klinik'),
('PG0008', 'OD0008', 'SP0008', 80, '2026-07-03', '2026-07-08', 'Diterima', 'Pengadaan Ambroxol 30 mg - Mucos untuk menjaga stok minimum klinik'),
('PG0009', 'OD0009', 'SP0009', 85, '2026-07-04', '2026-07-07', 'Batal', 'Pengadaan Salbutamol 2 mg - Ventolin untuk menjaga stok minimum klinik'),
('PG0010', 'OD0010', 'SP0010', 90, '2026-07-05', '2026-07-09', 'Pending', 'Pengadaan Metformin 500 mg - Glucophage untuk menjaga stok minimum klinik'),
('PG0011', 'OD0011', 'SP0011', 95, '2026-07-06', '2026-07-11', 'Proses', 'Pengadaan Amlodipine 5 mg - Norvask untuk menjaga stok minimum klinik'),
('PG0012', 'OD0012', 'SP0012', 100, '2026-07-07', '2026-07-10', 'Diterima', 'Pengadaan Oralit - Kimia Farma untuk menjaga stok minimum klinik'),
('PG0013', 'OD0013', 'SP0013', 105, '2026-07-08', '2026-07-12', 'Diterima', 'Pengadaan Chloramphenicol Tetes Mata - Cendo Fenicol untuk menjaga stok minimum klinik'),
('PG0014', 'OD0014', 'SP0014', 110, '2026-07-09', '2026-07-14', 'Batal', 'Pengadaan Miconazole Cream - Daktarin untuk menjaga stok minimum klinik'),
('PG0015', 'OD0015', 'SP0015', 115, '2026-07-10', '2026-07-13', 'Pending', 'Pengadaan Povidone Iodine 10% - Betadine untuk menjaga stok minimum klinik'),
('PG0016', 'OD0016', 'SP0016', 120, '2026-07-11', '2026-07-15', 'Proses', 'Pengadaan Ferrous Fumarate - Sangobion untuk menjaga stok minimum klinik'),
('PG0017', 'OD0017', 'SP0017', 125, '2026-07-12', '2026-07-17', 'Diterima', 'Pengadaan Loperamide 2 mg - Imodium untuk menjaga stok minimum klinik'),
('PG0018', 'OD0018', 'SP0018', 130, '2026-07-13', '2026-07-16', 'Diterima', 'Pengadaan Diclofenac Sodium 50 mg - Voltaren untuk menjaga stok minimum klinik'),
('PG0019', 'OD0019', 'SP0019', 135, '2026-07-14', '2026-07-18', 'Batal', 'Pengadaan Azithromycin 500 mg - Zithromax untuk menjaga stok minimum klinik'),
('PG0020', 'OD0020', 'SP0020', 140, '2026-07-15', '2026-07-20', 'Pending', 'Pengadaan Lansoprazole 30 mg - Prevacid untuk menjaga stok minimum klinik');

-- 13. notifikasi_stok_obat: 20 notifikasi stok
INSERT INTO `notifikasi_stok_obat` (`id_notifikasi`,`id_obat`,`nama_obat`,`stok_sekarang`,`stok_minimum`,`pesan`,`tanggal_notifikasi`) VALUES
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
(9020, 'OD0020', 'Lansoprazole 30 mg - Prevacid', 11, 12, 'Stok Lansoprazole 30 mg - Prevacid tersisa 11 kapsul dan berada di bawah stok minimum 12.', '2026-07-15 08:00:00');

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- Akun contoh pertama untuk pengujian pasien:
-- Username: alya.ramadhani | Password: Astar123
