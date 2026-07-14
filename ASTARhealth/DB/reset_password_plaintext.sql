USE astarhealth_db;

-- Mengembalikan akun bawaan ke password teks biasa seperti database awal.
UPDATE userm SET password='zeid123', role='Admin' WHERE username='admin';
UPDATE userm SET password='ike123' WHERE username='1023190013@polytechnic.astar.ac.id';
UPDATE userm SET password='dio123' WHERE username='0120240037@polytechnic.astar.ac.id';
UPDATE userm SET password='dholadolly123' WHERE username='0920250050@polytechnic.astar.ac.id';
UPDATE userm SET password='nana123' WHERE username='0420250044@polytechnic.astar.ac.id';
UPDATE userm SET password='suswanto123' WHERE username='20250932032@polytechnic.astar.ac.id';
UPDATE userm SET password='pipi123' WHERE username='0120250055@polytechnic.astar.ac.id';
UPDATE userm SET password='wowo123' WHERE username='0520240028@polytechnic.astar.ac.id';
UPDATE userm SET password='yoga123' WHERE username='2023212013@polytechnic.astar.ac.id';
UPDATE userm SET password='indah123' WHERE username='0320250021@polytechnic.astar.ac.id';

-- Akun lain yang masih memiliki nilai diawali $2y$ tidak dapat dibaca balik.
-- Reset melalui menu Admin > User Credentials atau halaman Lupa Kata Sandi.
