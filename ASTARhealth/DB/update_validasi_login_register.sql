-- Jalankan file ini jika database astarhealth_db sudah pernah di-import sebelumnya.
-- File ini menyesuaikan struktur tabel agar registrasi baru dapat menyimpan
-- NIK 16 digit, unit kerja bebas, alamat panjang, dan nomor WhatsApp.

USE astarhealth_db;

ALTER TABLE pasienm
  MODIFY no_identitas VARCHAR(30) NULL,
  MODIFY unit_prodi VARCHAR(100) NULL,
  MODIFY alamat VARCHAR(255) NULL,
  MODIFY no_hp VARCHAR(20) NULL;

-- Tambah unique index email jika belum ada.
SET @email_index_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'userm'
    AND index_name = 'uk_userm_email'
);
SET @sql = IF(
  @email_index_exists = 0,
  'ALTER TABLE userm ADD UNIQUE KEY uk_userm_email (email)',
  'SELECT ''Index email sudah tersedia'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tambah unique index nomor identitas jika belum ada.
SET @identity_index_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'pasienm'
    AND index_name = 'uk_pasienm_no_identitas'
);
SET @sql = IF(
  @identity_index_exists = 0,
  'ALTER TABLE pasienm ADD UNIQUE KEY uk_pasienm_no_identitas (no_identitas)',
  'SELECT ''Index nomor identitas sudah tersedia'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tambah index relasi pasien ke user jika belum ada.
SET @patient_user_index_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'pasienm'
    AND index_name = 'idx_pasienm_id_user'
);
SET @sql = IF(
  @patient_user_index_exists = 0,
  'ALTER TABLE pasienm ADD KEY idx_pasienm_id_user (id_user)',
  'SELECT ''Index id_user pasien sudah tersedia'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- PERBAIKAN AKUN ADMIN ZEID
-- Password login tetap: zeid123. Nilai di database berupa hash dan tidak dapat dibaca langsung.
UPDATE userm
SET password = 'zeid123',
    role = 'Admin'
WHERE username = 'admin';
