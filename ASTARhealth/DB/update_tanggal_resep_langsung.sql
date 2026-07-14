USE astarhealth_db;

-- Menambahkan tanggal transaksi untuk resep yang dibuat lewat form Input Langsung.
-- Resep dari pemeriksaan tetap memakai tanggal kunjungan dari tabel rekam_medis.
SET @kolom_tanggal_resep_ada = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resep_dokter'
      AND COLUMN_NAME = 'tanggal_resep'
);

SET @sql_tambah_tanggal_resep = IF(
    @kolom_tanggal_resep_ada = 0,
    'ALTER TABLE resep_dokter ADD COLUMN tanggal_resep DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER id_pasien',
    'SELECT ''Kolom tanggal_resep sudah tersedia'' AS informasi'
);

PREPARE stmt_tanggal_resep FROM @sql_tambah_tanggal_resep;
EXECUTE stmt_tanggal_resep;
DEALLOCATE PREPARE stmt_tanggal_resep;
