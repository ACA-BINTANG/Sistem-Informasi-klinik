-- ============================================================
-- SCRIPT FULL DATABASE BARU - klinik_db
-- DARI NOL, LENGKAP DENGAN DATA CONTOH
-- ============================================================

-- ============================================================
-- BAGIAN 1: HAPUS DATABASE LAMA (KALO ADA) & BUAT BARU
-- ============================================================

DROP DATABASE IF EXISTS `klinik_db`;
CREATE DATABASE `klinik_db`;
USE `klinik_db`;

-- ============================================================
-- BAGIAN 2: TABEL - USERS
-- ============================================================

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `role` enum('admin','dokter','mahasiswa','pegawai') NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 3: TABEL - MAHASISWA
-- ============================================================

CREATE TABLE `mahasiswa` (
  `nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `prodi` varchar(100) DEFAULT NULL,
  `no_tlp` varchar(15) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `gol_darah` varchar(5) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Aktif',
  `is_first_login` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`nim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 4: TABEL - STAFF
-- ============================================================

CREATE TABLE `staff` (
  `id_staff` varchar(6) NOT NULL,
  `id_user` varchar(6) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_identitas` varchar(50) DEFAULT NULL,
  `jabatan` varchar(50) NOT NULL,
  `no_tlp` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_staff`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 5: TABEL - PASIEN
-- ============================================================

CREATE TABLE `pasien` (
  `id_pasien` varchar(6) NOT NULL,
  `id_user` varchar(6) DEFAULT NULL,
  `no_identitas` varchar(20) DEFAULT NULL,
  `nama_pasien` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `kategori_pasien` enum('Mahasiswa','Dosen','Pegawai','Tamu') NOT NULL,
  `unit_prodi` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`id_pasien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 6: TABEL - DIAGNOSA
-- ============================================================

CREATE TABLE `diagnosa` (
  `id_diagnosa` varchar(6) NOT NULL,
  `nama_penyakit` varchar(100) NOT NULL,
  `kategori` enum('Ringan','Sedang','Berat') NOT NULL,
  `tipe` enum('Menular','Tidak Menular') NOT NULL,
  PRIMARY KEY (`id_diagnosa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 7: TABEL - JADWAL
-- ============================================================

CREATE TABLE `jadwal` (
  `id_jadwal` varchar(6) NOT NULL,
  `id_staff` varchar(6) NOT NULL,
  `tanggal` DATE NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `status` enum('Tidak Tersedia','Tersedia','Libur') DEFAULT 'Tersedia',
  PRIMARY KEY (`id_jadwal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 8: TABEL - REKAM MEDIS
-- ============================================================

CREATE TABLE `rekam_medis` (
  `kode_rm` varchar(30) NOT NULL,
  `id_pasien` varchar(6) NOT NULL,
  `id_staff` varchar(6) NOT NULL,
  `id_diagnosa` varchar(6) NOT NULL,
  `id_jadwal` varchar(6) NOT NULL,
  `no_antrian` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `waktu_booking` datetime NOT NULL,
  `keluhan` text NOT NULL,
  `hasil_pemeriksaan` text DEFAULT NULL,
  `status_antrian` enum('Menunggu','Dipanggil','Periksa','Selesai','Batal') DEFAULT 'Menunggu',
  PRIMARY KEY (`kode_rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 9: TABEL - OBAT
-- ============================================================

CREATE TABLE `obat` (
  `id_obat` varchar(6) NOT NULL,
  `nama_obat` varchar(100) NOT NULL,
  `stok_sekarang` int(11) DEFAULT 0,
  `stok_target` int(11) DEFAULT 100,
  `satuan` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_obat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 10: TABEL - SUPPLIER
-- ============================================================

CREATE TABLE `supplier` (
  `id_supplier` varchar(6) NOT NULL,
  `nama_supplier` varchar(50) NOT NULL,
  `kontak` varchar(12) DEFAULT NULL,
  `alamat` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_supplier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 11: TABEL - RESEP
-- ============================================================

CREATE TABLE `resep` (
  `id_resep` int(11) NOT NULL AUTO_INCREMENT,
  `kode_rm` varchar(30) DEFAULT NULL,
  `id_obat` varchar(6) DEFAULT NULL,
  `jumlah_obat` int(11) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  PRIMARY KEY (`id_resep`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 12: TABEL - PENGADAAN OBAT
-- ============================================================

CREATE TABLE `pengadaan_obat` (
  `id_pengadaan` int(11) NOT NULL AUTO_INCREMENT,
  `id_obat` varchar(6) DEFAULT NULL,
  `id_supplier` varchar(6) DEFAULT NULL,
  `tgl_terima` date DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_pengadaan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 13: TABEL - RUJUKAN
-- ============================================================

CREATE TABLE `rujukan` (
  `id_rujukan` varchar(6) NOT NULL,
  `id_pasien` varchar(6) DEFAULT NULL,
  `id_staff` varchar(6) DEFAULT NULL,
  `tujuan_rs` varchar(50) DEFAULT NULL,
  `alasan_rujukan` text DEFAULT NULL,
  `hasil_rujukan` text DEFAULT NULL,
  `tgl_rujukan` date DEFAULT NULL,
  `status` enum('Proses','Selesai','Batal') DEFAULT 'Proses',
  PRIMARY KEY (`id_rujukan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- BAGIAN 14: TABEL - EMERGENCY LOGS
-- ============================================================

CREATE TABLE `emergency_logs` (
  `LogID` int(11) NOT NULL AUTO_INCREMENT,
  `NIM` varchar(20) NOT NULL,
  `Latitude` decimal(10,8) NOT NULL,
  `Longitude` decimal(11,8) NOT NULL,
  `StatusPenanganan` varchar(20) DEFAULT 'Menunggu',
  `WaktuKejadian` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`LogID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- BAGIAN 15: FOREIGN KEY
-- ============================================================

ALTER TABLE `rekam_medis`
  ADD CONSTRAINT `fk_rm_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rm_staff` FOREIGN KEY (`id_staff`) REFERENCES `staff` (`id_staff`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rm_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal` (`id_jadwal`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rm_diagnosa` FOREIGN KEY (`id_diagnosa`) REFERENCES `diagnosa` (`id_diagnosa`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `resep`
  ADD CONSTRAINT `fk_resep_rm` FOREIGN KEY (`kode_rm`) REFERENCES `rekam_medis` (`kode_rm`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resep_obat` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id_obat`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `pengadaan_obat`
  ADD CONSTRAINT `fk_pengadaan_obat` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id_obat`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengadaan_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `rujukan`
  ADD CONSTRAINT `fk_rujukan_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rujukan_staff` FOREIGN KEY (`id_staff`) REFERENCES `staff` (`id_staff`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `pasien`
  ADD CONSTRAINT `fk_pasien_mahasiswa` 
  FOREIGN KEY (`no_identitas`) REFERENCES `mahasiswa` (`nim`) 
  ON DELETE SET NULL ON UPDATE CASCADE;


-- ============================================================
-- BAGIAN 16: INSERT DATA - USERS
-- ============================================================

INSERT INTO `users` (`id_user`, `nama`, `role`) VALUES
(1, 'Administrator', 'admin'),
(2, 'dr. Andi Pratama', 'dokter'),
(3, 'dr. Ike Indahwati', 'dokter'),
(4, 'dr. Budi Santoso', 'dokter'),
(5, 'Yoga Novalliano', 'mahasiswa'),
(6, 'Siti Aminah', 'mahasiswa'),
(7, 'Budi Mahasiswa', 'mahasiswa'),
(8, 'Erina Putri', 'mahasiswa'),
(9, 'Dola Syahwaldi', 'mahasiswa'),
(10, 'Kevin Ardiansyah', 'mahasiswa'),
(11, 'Ahmad Fauzi', 'mahasiswa'),
(12, 'Siti Nurhaliza', 'mahasiswa');

-- ============================================================
-- BAGIAN 17: INSERT DATA - MAHASISWA
-- ============================================================

INSERT INTO `mahasiswa` (`nim`, `nama`, `email`, `password`, `prodi`, `no_tlp`, `jenis_kelamin`, `alamat`) VALUES
('0920250050', 'Dola Syahwaldi', 'dola@mhs.astra.ac.id', 'dol123', 'D4 TRPL', '081200000050', 'L', 'Jl. Kenanga No.5, Tambun'),
('0920250054', 'Yoga Novalliano', 'yoga@mhs.astra.ac.id', '$2y$10$I54RcySIqjq1BLrO9rxd1ePKvNtdJxmwCV6kMtWx0ZqjqTR7LfkpG', 'D4 TRPL', '081200000054', 'L', 'Jl. Melati No.12, Bekasi'),
('0920250056', 'Erina Putri', 'erina@mhs.astra.ac.id', '$2y$10$PSIFOdY4YSqA3MG9KDLjCelBK6x0aDQYXTskSp1lBGoATzCSc2Jje', 'D4 TRPL', '081200000056', 'P', 'Jl. Anggrek No.8, Bogor'),
('0920220120', 'Kevin Ardiansyah', 'kevin@mhs.astra.ac.id', 'kevin123', 'D4 TRPL', '081200000120', 'L', 'Jl. Mawar No.3, Bekasi'),
('0920230001', 'Ahmad Fauzi', 'ahmad@mhs.astra.ac.id', 'ahmad123', 'D4 TRPL', '081200000001', 'L', 'Jl. Kamboja No.7, Bekasi'),
('0920250034', 'Erina Putri', 'erina2@mhs.astra.ac.id', 'erina123', 'D4 TRPL', '081200000034', 'P', 'Jl. Flamboyan No.2, Bogor'),
('0920230045', 'Siti Nurhaliza', 'siti@mhs.astra.ac.id', 'siti123', 'D4 TRPL', '081200000045', 'P', 'Jl. Dahlia No.9, Bekasi'),
('0920250099', 'Budi Mahasiswa', 'budi@mhs.astra.ac.id', 'budi123', 'D4 TRPL', '081200000099', 'L', 'Jl. Cempaka No.4, Jakarta');

-- ============================================================
-- BAGIAN 18: INSERT DATA - STAFF
-- ============================================================

INSERT INTO `staff` (`id_staff`, `id_user`, `nama_lengkap`, `no_identitas`, `jabatan`, `no_tlp`) VALUES
('U-001', '1', 'Administrator', 'ADM001', 'Admin', '0811111111'),
('U-002', '2', 'dr. Andi Pratama', 'DOK001', 'Dokter Umum', '0812222222'),
('STF091', '3', 'dr. Ike Indahwati', 'DOK002', 'Dokter Umum', '0813333333'),
('STF092', '4', 'dr. Budi Santoso', 'DOK003', 'Dokter Spesialis', '0814444444');

-- ============================================================
-- BAGIAN 19: INSERT DATA - PASIEN
-- ============================================================

INSERT INTO `pasien` (`id_pasien`, `id_user`, `no_identitas`, `nama_pasien`, `jenis_kelamin`, `kategori_pasien`, `unit_prodi`, `alamat`, `no_hp`) VALUES
('PSN001', '5', '0920250054', 'Yoga Novalliano', 'L', 'Mahasiswa', 'D4 TRPL', 'Jl. Melati No.12, Bekasi', '081200000054'),
('PSN002', '6', NULL, 'Siti Aminah', 'P', 'Tamu', NULL, 'Jl. Mawar No.12, Jakarta', '081234567890'),
('PSN003', '7', '0920250099', 'Budi Mahasiswa', 'L', 'Mahasiswa', 'D4 TRPL', 'Jl. Cempaka No.4, Jakarta', '081200000099'),
('PSN004', '8', '0920250056', 'Erina Putri', 'P', 'Mahasiswa', 'D4 TRPL', 'Jl. Anggrek No.8, Bogor', '081200000056'),
('PSN005', '9', '0920250050', 'Dola Syahwaldi', 'L', 'Mahasiswa', 'D4 TRPL', 'Jl. Kenanga No.5, Tambun', '081200000050'),
('PSN006', '10', '0920220120', 'Kevin Ardiansyah', 'L', 'Mahasiswa', 'D4 TRPL', 'Jl. Mawar No.3, Bekasi', '081200000120'),
('PSN007', '11', '0920230001', 'Ahmad Fauzi', 'L', 'Mahasiswa', 'D4 TRPL', 'Jl. Kamboja No.7, Bekasi', '081200000001'),
('PSN008', '12', '0920230045', 'Siti Nurhaliza', 'P', 'Mahasiswa', 'D4 TRPL', 'Jl. Dahlia No.9, Bekasi', '081200000045'),
('PSN009', NULL, NULL, 'Joko Widodo', 'L', 'Tamu', NULL, 'Jl. Sudirman No.1, Jakarta', '085156413049'),
('PSN010', NULL, NULL, 'Rini Anggraeni', 'P', 'Pegawai', NULL, 'Jl. Gatot Subroto No.5, Jakarta', '081234567899');

-- ============================================================
-- BAGIAN 20: INSERT DATA - DIAGNOSA
-- ============================================================

INSERT INTO `diagnosa` (`id_diagnosa`, `nama_penyakit`, `kategori`, `tipe`) VALUES
('D001', 'Diare Akut', 'Ringan', 'Menular'),
('D002', 'ISPA', 'Ringan', 'Menular'),
('D003', 'Diabetes Melitus', 'Berat', 'Tidak Menular'),
('D004', 'Hipertensi', 'Sedang', 'Tidak Menular'),
('D005', 'Demam Berdarah', 'Berat', 'Menular'),
('D006', 'Maag Akut', 'Ringan', 'Tidak Menular'),
('D007', 'Asma', 'Sedang', 'Tidak Menular');

-- ============================================================
-- BAGIAN 21: INSERT DATA - JADWAL
-- ============================================================

INSERT INTO `jadwal` (`id_jadwal`, `id_staff`, `tanggal`, `jam_mulai`, `jam_selesai`, `status`) VALUES
('JD001', 'U-002', '2026-06-20', '08:00:00', '12:00:00', 'Tersedia'),
('JD002', 'U-002', '2026-06-21', '08:00:00', '12:00:00', 'Tersedia'),
('JD003', 'STF091', '2026-06-20', '13:00:00', '16:00:00', 'Tersedia'),
('JD004', 'STF091', '2026-06-21', '13:00:00', '16:00:00', 'Tersedia'),
('JD005', 'STF092', '2026-06-22', '09:00:00', '14:00:00', 'Tersedia'),
('JD006', 'U-002', '2026-06-22', '08:00:00', '12:00:00', 'Libur'),
('JD007', 'STF091', '2026-06-22', '13:00:00', '16:00:00', 'Tersedia');

-- ============================================================
-- BAGIAN 22: INSERT DATA - OBAT
-- ============================================================

INSERT INTO `obat` (`id_obat`, `nama_obat`, `stok_sekarang`, `stok_target`, `satuan`) VALUES
('OB001', 'Paracetamol', 150, 200, 'Tablet'),
('OB002', 'Amoxicillin', 75, 100, 'Kapsul'),
('OB003', 'Loperamide', 45, 50, 'Tablet'),
('OB004', 'Cetirizine', 60, 100, 'Tablet'),
('OB005', 'Omeprazole', 40, 50, 'Kapsul'),
('OB006', 'Salbutamol', 30, 50, 'Inhaler'),
('OB007', 'Metformin', 80, 100, 'Tablet');

-- ============================================================
-- BAGIAN 23: INSERT DATA - SUPPLIER
-- ============================================================

INSERT INTO `supplier` (`id_supplier`, `nama_supplier`, `kontak`, `alamat`) VALUES
('SP001', 'PT Farma Jaya', '021-5551234', 'Jl. Industri No.15, Jakarta'),
('SP002', 'CV Sehat Sentosa', '022-5555678', 'Jl. Raya No.20, Bandung'),
('SP003', 'UD Berkah Medika', '031-5559012', 'Jl. Kesehatan No.8, Surabaya');

-- ============================================================
-- BAGIAN 24: INSERT DATA - REKAM MEDIS
-- ============================================================

INSERT INTO `rekam_medis` (`kode_rm`, `id_pasien`, `id_staff`, `id_diagnosa`, `id_jadwal`, `no_antrian`, `tanggal`, `waktu_booking`, `keluhan`, `hasil_pemeriksaan`, `status_antrian`) VALUES
('RM-202606001', 'PSN001', 'STF091', 'D001', 'JD003', 1, '2026-06-20', '2026-06-20 08:00:00', 'Diare dan mual selama 3 hari', 'Pasien mengalami diare akut, tidak ada dehidrasi', 'Selesai'),
('RM-202606002', 'PSN003', 'STF091', 'D002', 'JD004', 2, '2026-06-21', '2026-06-21 09:30:00', 'Batuk dan pilek sejak 5 hari lalu', 'ISPA ringan, tenggorokan kemerahan', 'Selesai'),
('RM-202606003', 'PSN005', 'U-002', 'D003', 'JD001', 1, '2026-06-20', '2026-06-20 08:30:00', 'Sering haus dan sering buang air kecil', 'Kadar gula darah tinggi, diabetes melitus tipe 2', 'Selesai'),
('RM-202606004', 'PSN009', 'U-002', 'D004', 'JD002', 2, '2026-06-21', '2026-06-21 10:00:00', 'Sakit kepala dan pusing', 'Tekanan darah 150/95, hipertensi', 'Selesai'),
('RM-202606005', 'PSN004', 'STF092', 'D005', 'JD005', 1, '2026-06-22', '2026-06-22 09:00:00', 'Demam tinggi 5 hari, nyeri sendi', 'Trombosit turun, demam berdarah', 'Periksa');

-- ============================================================
-- BAGIAN 25: INSERT DATA - RESEP
-- ============================================================

INSERT INTO `resep` (`id_resep`, `kode_rm`, `id_obat`, `jumlah_obat`, `catatan`) VALUES
(1, 'RM-202606001', 'OB001', 10, '3x sehari 1 tablet setelah makan'),
(2, 'RM-202606001', 'OB003', 6, '2x sehari 1 tablet sebelum makan'),
(3, 'RM-202606002', 'OB001', 8, '3x sehari 1 tablet'),
(4, 'RM-202606002', 'OB002', 12, '3x sehari 1 kapsul selama 7 hari'),
(5, 'RM-202606003', 'OB007', 30, '2x sehari 1 tablet setelah makan'),
(6, 'RM-202606004', 'OB004', 10, '1x sehari 1 tablet malam hari'),
(7, 'RM-202606005', 'OB001', 10, '3x sehari 1 tablet jika demam'),
(8, 'RM-202606005', 'OB005', 14, '2x sehari 1 kapsul');

-- ============================================================
-- BAGIAN 26: INSERT DATA - PENGADAAN OBAT
-- ============================================================

INSERT INTO `pengadaan_obat` (`id_pengadaan`, `id_obat`, `id_supplier`, `tgl_terima`, `quantity`) VALUES
(1, 'OB001', 'SP001', '2026-06-15', 100),
(2, 'OB002', 'SP001', '2026-06-15', 50),
(3, 'OB003', 'SP002', '2026-06-16', 30),
(4, 'OB004', 'SP002', '2026-06-16', 50),
(5, 'OB005', 'SP003', '2026-06-17', 40),
(6, 'OB006', 'SP003', '2026-06-17', 25);

-- ============================================================
-- BAGIAN 27: INSERT DATA - RUJUKAN
-- ============================================================

INSERT INTO `rujukan` (`id_rujukan`, `id_pasien`, `id_staff`, `tujuan_rs`, `alasan_rujukan`, `hasil_rujukan`, `tgl_rujukan`, `status`) VALUES
('RUJ001', 'PSN005', 'U-002', 'RS Siloam', 'Diabetes dengan komplikasi', 'Pasien dirujuk ke spesialis endokrin', '2026-06-20', 'Proses'),
('RUJ002', 'PSN004', 'STF092', 'RS Hermina', 'Demam berdarah dengan trombosit turun', 'Pasien dirawat inap', '2026-06-22', 'Proses'),
('RUJ003', 'PSN009', 'U-002', 'RS Jantung Harapan', 'Hipertensi berat', 'Pasien menjalani pemeriksaan lanjutan', '2026-06-21', 'Selesai');

-- ============================================================
-- BAGIAN 28: INSERT DATA - EMERGENCY LOGS (CONTOH)
-- ============================================================

INSERT INTO `emergency_logs` (`LogID`, `NIM`, `Latitude`, `Longitude`, `StatusPenanganan`, `WaktuKejadian`) VALUES
(1, '0920250054', -6.34829569, 107.14872010, 'Diproses', '2026-06-09 08:25:58'),
(2, '0920250054', -6.34815164, 107.14864397, 'Selesai', '2026-06-09 08:40:12');

-- ============================================================
-- BAGIAN 29: CEK HASIL
-- ============================================================

SELECT '✅ DATABASE KLINIK_DB BERHASIL DIBUAT!' AS 'Status';

SELECT 'users' AS 'Tabel', COUNT(*) AS 'Jumlah' FROM users
UNION SELECT 'mahasiswa', COUNT(*) FROM mahasiswa
UNION SELECT 'staff', COUNT(*) FROM staff
UNION SELECT 'pasien', COUNT(*) FROM pasien
UNION SELECT 'diagnosa', COUNT(*) FROM diagnosa
UNION SELECT 'jadwal', COUNT(*) FROM jadwal
UNION SELECT 'rekam_medis', COUNT(*) FROM rekam_medis
UNION SELECT 'obat', COUNT(*) FROM obat
UNION SELECT 'supplier', COUNT(*) FROM supplier
UNION SELECT 'resep', COUNT(*) FROM resep
UNION SELECT 'pengadaan_obat', COUNT(*) FROM pengadaan_obat
UNION SELECT 'rujukan', COUNT(*) FROM rujukan
UNION SELECT 'emergency_logs', COUNT(*) FROM emergency_logs;

-- ============================================================
-- SELESAI
-- ============================================================