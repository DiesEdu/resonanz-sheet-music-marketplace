-- database/schema.sql
CREATE DATABASE IF NOT EXISTS sheet_music_marketplace;
USE sheet_music_marketplace;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    avatar VARCHAR(255),
    role ENUM('user', 'composer', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    icon VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Instruments table
CREATE TABLE instruments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    icon VARCHAR(50),
    description TEXT
);

-- Sheet music table
CREATE TABLE sheet_music (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    composer VARCHAR(100) NOT NULL,
    description TEXT,
    instrument_id INT,
    category_id INT,
    difficulty ENUM('Beginner', 'Intermediate', 'Advanced', 'Professional') DEFAULT 'Intermediate',
    price DECIMAL(10,2) NOT NULL,
    pages INT,
    format VARCHAR(50) DEFAULT 'PDF',
    file_path VARCHAR(255),
    cover_image VARCHAR(255),
    sample_path VARCHAR(255),
    rating DECIMAL(3,2) DEFAULT 0,
    reviews_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_premium BOOLEAN DEFAULT FALSE,
    downloads_count INT DEFAULT 0,
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instrument_id) REFERENCES instruments(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Reviews table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    sheet_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sheet_id) REFERENCES sheet_music(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_sheet (user_id, sheet_id)
);

-- Cart table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    session_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session (session_id)
);

-- Cart items table
CREATE TABLE cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cart_id INT,
    sheet_id INT,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (sheet_id) REFERENCES sheet_music(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    billing_name VARCHAR(100),
    billing_email VARCHAR(100),
    billing_phone VARCHAR(20),
    billing_address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    sheet_id INT,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (sheet_id) REFERENCES sheet_music(id) ON DELETE SET NULL
);

-- Wishlist table
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    sheet_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sheet_id) REFERENCES sheet_music(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_sheet (user_id, sheet_id)
);

-- Insert sample data
INSERT INTO instruments (name, slug, icon, description) VALUES
('Piano', 'piano', 'bi-piano', 'Keyboard instruments'),
('Violin', 'violin', 'bi-music-note', 'String instruments'),
('Guitar', 'guitar', 'bi-guitar', 'String instruments'),
('Cello', 'cello', 'bi-music-note', 'String instruments'),
('Flute', 'flute', 'bi-music-note', 'Woodwind instruments'),
('Voice', 'voice', 'bi-mic', 'Vocal music');

INSERT INTO categories (name, slug, icon, description) VALUES
('Classical', 'classical', 'bi-music-note', 'Classical music period'),
('Jazz', 'jazz', 'bi-music-note', 'Jazz standards and improvisation'),
('Pop', 'pop', 'bi-music-note', 'Popular music arrangements'),
('Rock', 'rock', 'bi-music-note', 'Rock and alternative'),
('Educational', 'educational', 'bi-book', 'Educational materials'),
('Sacred', 'sacred', 'bi-church', 'Religious and sacred music');

-- Insert sample sheet music (password: password123)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@sheetmusic.com', '$2y$10$YourHashedPasswordHere', 'Administrator', 'admin'),
('demo_user', 'user@example.com', '$$2y$12$DwUlao8EEYCIwDbX44jbD.76VHCSmYtzvrvwiU3MTQWbXM4FpCKZW', 'Demo User', 'user');
