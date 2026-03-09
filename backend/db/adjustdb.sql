ALTER TABLE sheet_music MODIFY COLUMN cover_image LONGTEXT NULL;
ALTER TABLE sheet_music MODIFY COLUMN description LONGTEXT NULL;
ALTER TABLE users MODIFY COLUMN role ENUM('user', 'composer', 'admin') DEFAULT 'user';
ALTER TABLE sheet_music ADD COLUMN created_by INT(11) NOT NULL AFTER views_count;

UPDATE sheet_music SET created_by = 1;
--------------------------------------------------------------
ALTER TABLE sheet_music ADD COLUMN subtitle VARCHAR(255) NULL AFTER title;
ALTER TABLE sheet_music ADD COLUMN list_instruments JSON NULL AFTER instrument_id;
ALTER TABLE sheet_music ADD COLUMN pdf_name VARCHAR(255) NULL AFTER format;
---------------------------------------------------------------
ALTER TABLE users
ADD email_verified TINYINT(1) DEFAULT 0,
ADD verification_token VARCHAR(255) NULL;
----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    sheet_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sheet_id) REFERENCES sheet_music(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite_user_sheet (user_id, sheet_id)
);
---------------------------------------------------------------------
ALTER TABLE sheet_music ADD COLUMN arranger VARCHAR(255) NULL AFTER composer;
ALTER TABLE sheet_music ADD COLUMN sample_audio VARCHAR(255) NULL AFTER file_path;
---------------------------------------------------------------------
