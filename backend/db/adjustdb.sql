ALTER TABLE sheet_music MODIFY COLUMN cover_image LONGTEXT NULL;
ALTER TABLE sheet_music MODIFY COLUMN description LONGTEXT NULL;
ALTER TABLE users MODIFY COLUMN role ENUM('user', 'composer', 'admin') DEFAULT 'user';
ALTER TABLE sheet_music ADD COLUMN created_by INT(11) NOT NULL AFTER views_count;

UPDATE sheet_music SET created_by = 1;