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
  `id_obat` int(11) NOT NULL,
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
  `no_identitas` varchar(10) DEFAULT NULL,
  `nama_pasien` varchar(100) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `kategori_pasien` enum('Mahasiswa','Pegawai','Virtus','Sigap','Tamu','Lainnya') DEFAULT NULL,
  `unit_prodi` enum('MI','MK','MO','P4','TKBG','TPM','TRL','TRPAB','TRPL','DKA','DA3','BKM','WKS','HRD','IT','GA','DIR','K3') DEFAULT NULL,
  `alamat` varchar(50) DEFAULT NULL,
  `no_hp` varchar(12) DEFAULT NULL
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
  `status` enum('Menunggu','Darurat','Diproses','Selesai') NOT NULL DEFAULT 'Menunggu',
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
-- Table structure for table `resep_obat`
--

CREATE TABLE `resep_obat` (
  `id_resep` varchar(6) NOT NULL,
  `id_rekam_medis` varchar(6) DEFAULT NULL,
  `id_obat` varchar(6) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `catatan_obat` text DEFAULT NULL
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

--
-- Dumping data for table `userm`
--

INSERT INTO `userm` (`id_user`, `username`, `email`, `password`, `role`, `nama_lengkap`) VALUES
('4163C5', 'admin', 'zeidalrayan@gmail.com', 'zeid123', 'Pasien', 'ZEID ALRAYAN PASHA'),
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
  ADD PRIMARY KEY (`id_pasien`);

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
  ADD KEY `id_rekam_medis` (`id_rekam_medis`),
  ADD KEY `id_obat` (`id_obat`);

--
-- Indexes for table `resep_obat`
--
ALTER TABLE `resep_obat`
  ADD PRIMARY KEY (`id_resep`);

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
  ADD UNIQUE KEY `username` (`username`);

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
  ADD CONSTRAINT `resep_dokter_ibfk_2` FOREIGN KEY (`id_obat`) REFERENCES `obatm` (`id_obat`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staffm`
--
ALTER TABLE `staffm`
  ADD CONSTRAINT `staffm_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `userm` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
