-- Pembaruan ASTARhealth:
-- 1. Menyimpan antrean batal sebagai riwayat, bukan menghapus transaksi.
-- 2. Mengubah antrean aktif yang tanggalnya sudah lewat menjadi Batal.

USE astarhealth_db;

ALTER TABLE rekam_medis
MODIFY status ENUM('Menunggu','Darurat','Diproses','Selesai','Batal')
NOT NULL DEFAULT 'Menunggu';

UPDATE rekam_medis
SET status = 'Batal'
WHERE tgl_kunjungan < CURDATE()
  AND status IN ('Menunggu','Darurat','Diproses');
