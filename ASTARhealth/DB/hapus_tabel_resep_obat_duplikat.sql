-- ASTARhealth
-- Hapus tabel lama `resep_obat` yang duplikat dan tidak digunakan.
-- Data resep yang aktif tetap berada di tabel `resep_dokter`.
-- Tabel `diagnosam` dan `resep_diagnosa` JANGAN dihapus karena digunakan
-- untuk pilihan satu atau lebih penyakit pada form resep.

USE `astarhealth_db`;

DROP TABLE IF EXISTS `resep_obat`;
