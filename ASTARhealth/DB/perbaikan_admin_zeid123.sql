USE astarhealth_db;

UPDATE userm
SET password = 'zeid123', role = 'Admin'
WHERE username = 'admin';
