CREATE TABLE IF NOT EXISTS favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    sheet_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sheet_id) REFERENCES sheet_music(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite_user_sheet (user_id, sheet_id)
);
