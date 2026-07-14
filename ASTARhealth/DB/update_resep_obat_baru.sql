-- ============================================================
-- UPDATE PENYIMPANAN RESEP OBAT LANGSUNG
-- Form "Add Resep" disimpan ke tabel resep_obat.
-- Tabel resep_dokter tetap khusus resep dari pemeriksaan.
-- Jalankan satu kali pada database astarhealth_db yang sudah ada.
-- ============================================================

USE astarhealth_db;

-- Pastikan tabel resep_obat tersedia.
CREATE TABLE IF NOT EXISTS resep_obat (
    id_resep VARCHAR(6) NOT NULL,
    id_rekam_medis VARCHAR(6) DEFAULT NULL,
    id_obat VARCHAR(6) DEFAULT NULL,
    jumlah INT NOT NULL DEFAULT 0,
    catatan_obat TEXT DEFAULT NULL,
    PRIMARY KEY (id_resep)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tambahkan kolom pasien jika belum ada.
SET @ada_id_pasien = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_obat'
      AND COLUMN_NAME = 'id_pasien'
);
SET @sql_id_pasien = IF(
    @ada_id_pasien = 0,
    'ALTER TABLE resep_obat ADD COLUMN id_pasien VARCHAR(6) NULL AFTER id_resep',
    'SELECT "Kolom id_pasien sudah tersedia"'
);
PREPARE stmt FROM @sql_id_pasien;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tambahkan kolom dokter jika belum ada.
SET @ada_id_staff = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_obat'
      AND COLUMN_NAME = 'id_staff'
);
SET @sql_id_staff = IF(
    @ada_id_staff = 0,
    'ALTER TABLE resep_obat ADD COLUMN id_staff VARCHAR(6) NULL AFTER id_pasien',
    'SELECT "Kolom id_staff sudah tersedia"'
);
PREPARE stmt FROM @sql_id_staff;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tambahkan tanggal resep jika belum ada.
SET @ada_tgl_resep = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_obat'
      AND COLUMN_NAME = 'tgl_resep'
);
SET @sql_tgl_resep = IF(
    @ada_tgl_resep = 0,
    'ALTER TABLE resep_obat ADD COLUMN tgl_resep DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER catatan_obat',
    'SELECT "Kolom tgl_resep sudah tersedia"'
);
PREPARE stmt FROM @sql_tgl_resep;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tabel penghubung penyakit/keluhan untuk resep_obat.
CREATE TABLE IF NOT EXISTS resep_obat_diagnosa (
    id_resep VARCHAR(6) NOT NULL,
    id_diagnosa VARCHAR(6) NOT NULL,
    PRIMARY KEY (id_resep, id_diagnosa),
    KEY idx_rod_diagnosa (id_diagnosa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tambahkan index jika belum tersedia.
SET @ada_idx_pasien = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_obat'
      AND INDEX_NAME = 'idx_resep_obat_pasien'
);
SET @sql_idx_pasien = IF(
    @ada_idx_pasien = 0,
    'ALTER TABLE resep_obat ADD INDEX idx_resep_obat_pasien (id_pasien)',
    'SELECT "Index pasien sudah tersedia"'
);
PREPARE stmt FROM @sql_idx_pasien;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ada_idx_tanggal = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_obat'
      AND INDEX_NAME = 'idx_resep_obat_tanggal'
);
SET @sql_idx_tanggal = IF(
    @ada_idx_tanggal = 0,
    'ALTER TABLE resep_obat ADD INDEX idx_resep_obat_tanggal (tgl_resep)',
    'SELECT "Index tanggal sudah tersedia"'
);
PREPARE stmt FROM @sql_idx_tanggal;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- MIGRASI DATA LAMA
-- Resep langsung versi sebelumnya dikenali dari:
-- id_rekam_medis IS NULL dan id_pasien terisi pada resep_dokter.
-- Migrasi tidak mengurangi stok lagi.
-- ============================================================

SET @ada_kolom_pasien_rd = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_dokter'
      AND COLUMN_NAME = 'id_pasien'
);

SET @sql_migrasi_resep = IF(
    @ada_kolom_pasien_rd > 0,
    'INSERT IGNORE INTO resep_obat
        (id_resep, id_pasien, id_staff, id_rekam_medis, id_obat, jumlah, catatan_obat, tgl_resep)
     SELECT
        rd.id_resep, rd.id_pasien, NULL, NULL, rd.id_obat,
        rd.jumlah_keluar, rd.catatan_obat, NOW()
     FROM resep_dokter rd
     WHERE rd.id_rekam_medis IS NULL
       AND rd.id_pasien IS NOT NULL
       AND CHAR_LENGTH(rd.id_pasien) > 0',
    'SELECT "Tidak ada data resep langsung lama yang perlu dipindahkan"'
);
PREPARE stmt FROM @sql_migrasi_resep;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ada_tabel_resep_diagnosa = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_diagnosa'
);
SET @sql_migrasi_diagnosa = IF(
    @ada_kolom_pasien_rd > 0 AND @ada_tabel_resep_diagnosa > 0,
    'INSERT IGNORE INTO resep_obat_diagnosa (id_resep, id_diagnosa)
     SELECT x.id_resep, x.id_diagnosa
     FROM resep_diagnosa x
     JOIN resep_dokter rd ON rd.id_resep = x.id_resep
     WHERE rd.id_rekam_medis IS NULL
       AND rd.id_pasien IS NOT NULL
       AND CHAR_LENGTH(rd.id_pasien) > 0',
    'SELECT "Tidak ada diagnosa resep lama yang perlu dipindahkan"'
);
PREPARE stmt FROM @sql_migrasi_diagnosa;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql_hapus_lama = IF(
    @ada_kolom_pasien_rd > 0,
    'DELETE FROM resep_dokter
     WHERE id_rekam_medis IS NULL
       AND id_pasien IS NOT NULL
       AND CHAR_LENGTH(id_pasien) > 0',
    'SELECT "Tidak ada data lama yang perlu dihapus"'
);
PREPARE stmt FROM @sql_hapus_lama;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Update resep_obat selesai' AS hasil;
