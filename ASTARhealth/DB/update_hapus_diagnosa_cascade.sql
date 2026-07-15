USE astarhealth_db;

-- Relasi resep-diagnosa tidak lagi mengunci penghapusan diagnosa.
-- Aplikasi tetap membersihkan rekam medis, resep, dan stok menggunakan transaksi PHP.
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_diagnosa'
      AND CONSTRAINT_NAME = 'resep_diagnosa_ibfk_2'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @drop_fk := IF(
    @fk_exists > 0,
    'ALTER TABLE resep_diagnosa DROP FOREIGN KEY resep_diagnosa_ibfk_2',
    'SELECT 1'
);
PREPARE stmt FROM @drop_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE resep_diagnosa
    ADD CONSTRAINT resep_diagnosa_ibfk_2
    FOREIGN KEY (id_diagnosa) REFERENCES diagnosam(id_diagnosa)
    ON DELETE CASCADE ON UPDATE CASCADE;
