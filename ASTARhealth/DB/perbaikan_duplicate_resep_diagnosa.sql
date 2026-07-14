-- Perbaikan data relasi resep-diagnosa yang tertinggal tanpa resep utama.
-- Aman dijalankan berulang kali.
USE astarhealth_db;

DELETE rdg
FROM resep_diagnosa AS rdg
LEFT JOIN resep_dokter AS rd
    ON rd.id_resep = rdg.id_resep
WHERE rd.id_resep IS NULL;
