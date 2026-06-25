-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 23 Jun 2026 pada 09.48
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

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
-- Struktur dari tabel `diagnosam`
--

CREATE TABLE `diagnosam` (
  `id_diagnosa` varchar(6) NOT NULL,
  `nama_penyakit` varchar(50) DEFAULT NULL,
  `kategori` enum('Umum','Menular','Kronis','Lainnya') DEFAULT NULL,
  `tipe` enum('Ringan','Sedang','Berat') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `diagnosam`
--

INSERT INTO `diagnosam` (`id_diagnosa`, `nama_penyakit`, `kategori`, `tipe`) VALUES
('DX024', 'Gerd', 'Umum', 'Sedang'),
('DX190', 'Asma', 'Kronis', 'Berat'),
('DX480', 'Demam', 'Umum', 'Berat'),
('DX761', 'flu berat', 'Menular', 'Sedang');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwalm`
--

CREATE TABLE `jadwalm` (
  `id_jadwal` varchar(6) NOT NULL,
  `hari` varchar(20) NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `status` enum('Tidak Tersedia','Tersedia','Libur') DEFAULT 'Tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `obatm`
--

CREATE TABLE `obatm` (
  `id_obat` varchar(6) NOT NULL,
  `nama_obat` varchar(100) NOT NULL,
  `stok_sekarang` int(11) DEFAULT 0,
  `stok_target` int(11) DEFAULT 100,
  `satuan` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pasienm`
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
-- Dumping data untuk tabel `pasienm`
--

INSERT INTO `pasienm` (`id_pasien`, `id_user`, `no_identitas`, `nama_pasien`, `jenis_kelamin`, `kategori_pasien`, `unit_prodi`, `alamat`, `no_hp`) VALUES
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
-- Struktur dari tabel `rekam_medis`
--

CREATE TABLE `rekam_medis` (
  `id_rekam_medis` varchar(6) NOT NULL,
  `id_pasien` varchar(6) DEFAULT NULL,
  `id_staff` varchar(6) DEFAULT NULL,
  `id_diagnosa` varchar(6) DEFAULT NULL,
  `no_antrian` varchar(5) DEFAULT NULL,
  `tgl_kunjungan` date DEFAULT NULL,
  `waktu_booking` time DEFAULT NULL,
  `keluhan` text DEFAULT NULL,
  `hasil_pemeriksaan` text DEFAULT NULL,
  `status` enum('Menunggu','Proses','Selesai','Batal') DEFAULT 'Menunggu',
  `is_priority` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rekam_medis`
--

INSERT INTO `rekam_medis` (`id_rekam_medis`, `id_pasien`, `id_staff`, `id_diagnosa`, `no_antrian`, `tgl_kunjungan`, `waktu_booking`, `keluhan`, `hasil_pemeriksaan`, `status`, `is_priority`) VALUES
('RM0846', 'PSN379', NULL, NULL, 'A001', '2026-06-23', '03:10:32', 'pusing', NULL, 'Menunggu', 0),
('RM2856', 'PSN463', NULL, NULL, 'A003', '2026-06-23', '04:00:37', 'mual', NULL, 'Menunggu', 0),
('RM5032', 'PSN174', NULL, NULL, 'A002', '2026-06-23', '03:11:25', 'asma', NULL, 'Menunggu', 1),
('RM9026', 'PSN891', 'STF091', 'DX480', 'A004', '2026-06-23', '09:04:14', 'pusing', 'beliau sakit', 'Selesai', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `resep_dokter`
--

CREATE TABLE `resep_dokter` (
  `id_resep` varchar(6) NOT NULL,
  `id_rekam_medis` varchar(6) DEFAULT NULL,
  `id_obat` varchar(6) DEFAULT NULL,
  `jumlah_keluar` int(11) DEFAULT NULL,
  `catatan_obat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `resep_obat`
--

CREATE TABLE `resep_obat` (
  `id_resep` varchar(6) NOT NULL,
  `id_rekam_medis` varchar(6) DEFAULT NULL,
  `id_obat` varchar(6) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `aturan_pakai` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rujukan`
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
-- Dumping data untuk tabel `rujukan`
--

INSERT INTO `rujukan` (`id_rujukan`, `id_pasien`, `id_staff`, `tujuan_rs`, `alasan_rujukan`, `tgl_rujukan`, `status`) VALUES
('RUJ134', 'PSN894', 'STF091', 'Sentra Medika', 'terjepit besi jarinya', '2026-06-23', 'Proses'),
('RUJ160', 'PSN894', 'STF091', 'Siloam', 'tertimpa azab', '2026-06-23', 'Proses'),
('RUJ403', 'PSN174', 'STF091', 'Sentra Medika', 'Tertusuk pisau di lengan', '2026-06-19', 'Proses'),
('RUJ580', 'PSN153', 'STF091', 'Sentra Medika', 'tertusuk', '2026-06-23', 'Proses'),
('RUJ850', 'PSN759', 'STF091', 'Siloam', 'tertusuk paku di telapk tangan', '2026-06-19', 'Proses');

-- --------------------------------------------------------

--
-- Struktur dari tabel `staffm`
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
-- Dumping data untuk tabel `staffm`
--

INSERT INTO `staffm` (`id_staff`, `id_user`, `nama_lengkap`, `no_identitas`, `jabatan`, `instansi`, `npa_idi`, `no_hp`) VALUES
('STF091', 'USR001', 'Ike Indahwati', '102310013', 'Dokter UKK', 'Siloam', '009231239113121', '811-8198-560'),
('STF109', 'USR730', 'Suswanto dewanto', '20250932032', 'Wakil Ketua Divisi K3', 'Kampus', '-', '333-4432-242');

-- --------------------------------------------------------

--
-- Struktur dari tabel `supplierm`
--

CREATE TABLE `supplierm` (
  `id_supplier` varchar(6) NOT NULL,
  `nama_supplier` varchar(50) NOT NULL,
  `kontak` varchar(12) DEFAULT NULL,
  `alamat` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `userm`
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
-- Dumping data untuk tabel `userm`
--

INSERT INTO `userm` (`id_user`, `username`, `email`, `password`, `role`, `nama_lengkap`) VALUES
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
-- Indeks untuk tabel `diagnosam`
--
ALTER TABLE `diagnosam`
  ADD PRIMARY KEY (`id_diagnosa`);

--
-- Indeks untuk tabel `jadwalm`
--
ALTER TABLE `jadwalm`
  ADD PRIMARY KEY (`id_jadwal`);

--
-- Indeks untuk tabel `obatm`
--
ALTER TABLE `obatm`
  ADD PRIMARY KEY (`id_obat`);

--
-- Indeks untuk tabel `pasienm`
--
ALTER TABLE `pasienm`
  ADD PRIMARY KEY (`id_pasien`);

--
-- Indeks untuk tabel `rekam_medis`
--
ALTER TABLE `rekam_medis`
  ADD PRIMARY KEY (`id_rekam_medis`);

--
-- Indeks untuk tabel `resep_dokter`
--
ALTER TABLE `resep_dokter`
  ADD PRIMARY KEY (`id_resep`),
  ADD KEY `id_rekam_medis` (`id_rekam_medis`),
  ADD KEY `id_obat` (`id_obat`);

--
-- Indeks untuk tabel `resep_obat`
--
ALTER TABLE `resep_obat`
  ADD PRIMARY KEY (`id_resep`);

--
-- Indeks untuk tabel `rujukan`
--
ALTER TABLE `rujukan`
  ADD PRIMARY KEY (`id_rujukan`);

--
-- Indeks untuk tabel `staffm`
--
ALTER TABLE `staffm`
  ADD PRIMARY KEY (`id_staff`),
  ADD UNIQUE KEY `no_identitas` (`no_identitas`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `supplierm`
--
ALTER TABLE `supplierm`
  ADD PRIMARY KEY (`id_supplier`);

--
-- Indeks untuk tabel `userm`
--
ALTER TABLE `userm`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `resep_dokter`
--
ALTER TABLE `resep_dokter`
  ADD CONSTRAINT `resep_dokter_ibfk_1` FOREIGN KEY (`id_rekam_medis`) REFERENCES `rekam_medis` (`id_rekam_medis`) ON DELETE CASCADE,
  ADD CONSTRAINT `resep_dokter_ibfk_2` FOREIGN KEY (`id_obat`) REFERENCES `obatm` (`id_obat`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `staffm`
--
ALTER TABLE `staffm`
  ADD CONSTRAINT `staffm_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `userm` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
