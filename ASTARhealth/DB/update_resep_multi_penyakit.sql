-- ============================================================
-- UPDATE FITUR RESEP: SATU RESEP BISA MEMILIKI BANYAK PENYAKIT
-- Jalankan satu kali pada database astarhealth_db yang sudah ada.
-- ============================================================

USE astarhealth_db;

SET @kolom_pasien_ada = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_dokter'
      AND COLUMN_NAME = 'id_pasien'
);

SET @sql_tambah_pasien = IF(
    @kolom_pasien_ada = 0,
    'ALTER TABLE resep_dokter ADD COLUMN id_pasien VARCHAR(20) NULL AFTER id_resep',
    'SELECT "Kolom id_pasien sudah tersedia"'
);
PREPARE stmt_tambah_pasien FROM @sql_tambah_pasien;
EXECUTE stmt_tambah_pasien;
DEALLOCATE PREPARE stmt_tambah_pasien;

CREATE TABLE IF NOT EXISTS resep_diagnosa (
    id_resep VARCHAR(6) NOT NULL,
    id_diagnosa VARCHAR(6) NOT NULL,
    PRIMARY KEY (id_resep, id_diagnosa),
    KEY idx_resep_diagnosa_diagnosa (id_diagnosa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Foreign key dibuat hanya jika belum ada.
SET @fk_resep_ada = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_diagnosa'
      AND CONSTRAINT_NAME = 'fk_resep_diagnosa_resep'
);
SET @sql_fk_resep = IF(
    @fk_resep_ada = 0,
    'ALTER TABLE resep_diagnosa ADD CONSTRAINT fk_resep_diagnosa_resep FOREIGN KEY (id_resep) REFERENCES resep_dokter(id_resep) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT "FK resep sudah tersedia"'
);
PREPARE stmt_fk_resep FROM @sql_fk_resep;
EXECUTE stmt_fk_resep;
DEALLOCATE PREPARE stmt_fk_resep;

SET @fk_diagnosa_ada = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_diagnosa'
      AND CONSTRAINT_NAME = 'fk_resep_diagnosa_diagnosa'
);
SET @sql_fk_diagnosa = IF(
    @fk_diagnosa_ada = 0,
    'ALTER TABLE resep_diagnosa ADD CONSTRAINT fk_resep_diagnosa_diagnosa FOREIGN KEY (id_diagnosa) REFERENCES diagnosam(id_diagnosa) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT "FK diagnosa sudah tersedia"'
);
PREPARE stmt_fk_diagnosa FROM @sql_fk_diagnosa;
EXECUTE stmt_fk_diagnosa;
DEALLOCATE PREPARE stmt_fk_diagnosa;
