-- Jalankan satu kali pada database astarhealth_db yang sudah pernah di-import.
-- Menggabungkan kategori Lainnya ke Tamu dan merapikan pilihan kategori pasien.

USE astarhealth_db;

UPDATE pasienm
SET kategori_pasien = 'Tamu'
WHERE kategori_pasien = 'Lainnya';

ALTER TABLE pasienm
  MODIFY kategori_pasien ENUM('Mahasiswa','Pegawai','Virtus','Sigap','Tamu') DEFAULT NULL;
