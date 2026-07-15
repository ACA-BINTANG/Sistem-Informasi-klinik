USE astarhealth_db;

CREATE TABLE IF NOT EXISTS riwayat_cetak_laporan (
    id_riwayat BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    jenis_laporan VARCHAR(50) NOT NULL,
    judul_laporan VARCHAR(150) NOT NULL,
    id_user VARCHAR(30) NULL,
    nama_pencetak VARCHAR(150) NOT NULL,
    parameter_filter TEXT NULL,
    tanggal_cetak DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_riwayat),
    KEY idx_jenis_tanggal (jenis_laporan, tanggal_cetak),
    KEY idx_id_user (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
