-- TikTok Points Database Schema
-- Ejecutar este script en tu base de datos MySQL

CREATE DATABASE IF NOT EXISTS tiktok_points CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tiktok_points;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(50) DEFAULT '📍',
    color VARCHAR(7) DEFAULT '#3498db',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de lugares
CREATE TABLE IF NOT EXISTS places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tiktok_link VARCHAR(500),
    category_id INT,
    name VARCHAR(200) NOT NULL,
    address VARCHAR(500),
    rating TINYINT DEFAULT 0 CHECK (rating >= 0 AND rating <= 5),
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    visited TINYINT(1) DEFAULT 0,
    visit_date DATETIME NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_visited (visited),
    INDEX idx_category (category_id),
    INDEX idx_coords (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de media (fotos/videos)
CREATE TABLE IF NOT EXISTS place_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    INDEX idx_place (place_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar algunas categorías por defecto
INSERT INTO categories (name, icon, color) VALUES
('Restaurantes', '🍽️', '#e74c3c'),
('Cafés', '☕', '#8b4513'),
('Bares', '🍺', '#f39c12'),
('Parques', '🌳', '#27ae60'),
('Museos', '🏛️', '#9b59b6'),
('Tiendas', '🛍️', '#e91e63'),
('Miradores', '🌄', '#00bcd4'),
('Playas', '🏖️', '#03a9f4'),
('Montañas', '⛰️', '#795548'),
('Otros', '📍', '#607d8b');
