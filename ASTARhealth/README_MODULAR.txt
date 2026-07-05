ASTARhealth - Versi Dashboard Dipisah File

Perubahan utama:
1. dashboard_dokter.php tetap menjadi file utama dashboard dokter.
2. Isi halaman dokter dipisah ke folder pages/dokter/:
   - antrean.php
   - pemeriksaan.php
   - rekam_medis.php
   - resep_obat.php
   - rujukan.php
   - jadwal_dokter.php
   - obat.php
   - pengadaan_obat.php
   - diagnosa.php
   - pasien.php
3. dashboard_pasien.php tetap menjadi file utama dashboard pasien.
4. Isi halaman pasien dipisah ke folder pages/pasien/:
   - beranda.php
   - antrean.php
   - jadwal_dokter.php
   - obat.php
   - riwayat.php

Cara pakai:
- Upload semua folder dan file ini ke project kamu.
- Tetap akses dashboard seperti biasa:
  dashboard_dokter.php?page=resep_obat
  dashboard_pasien.php?page=beranda

Catatan:
- File dashboard utama masih menyimpan proses POST/aksi agar alur lama tetap aman.
- Tampilan halaman sekarang lebih mudah diedit karena tiap menu punya file sendiri.
