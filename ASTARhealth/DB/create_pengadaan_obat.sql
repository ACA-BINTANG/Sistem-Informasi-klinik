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
