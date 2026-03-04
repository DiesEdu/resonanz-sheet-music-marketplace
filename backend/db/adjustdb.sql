ALTER TABLE sheet_music MODIFY COLUMN cover_image LONGTEXT NULL;
ALTER TABLE sheet_music MODIFY COLUMN description LONGTEXT NULL;
ALTER TABLE users MODIFY COLUMN role ENUM('user', 'composer', 'admin') DEFAULT 'user';
