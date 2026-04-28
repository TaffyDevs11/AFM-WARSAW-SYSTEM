-- ============================================================
-- AFM Warsaw Assembly - Database Schema
-- ============================================================
-- XAMPP: Import via phpMyAdmin or: mysql -u root -p < schema.sql
-- Hostinger: Import via hPanel > MySQL Databases > phpMyAdmin
-- Database name: afm_warsaw (create this first)
-- ============================================================

CREATE DATABASE IF NOT EXISTS afm_warsaw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE afm_warsaw;

-- Admin users
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Gallery images
CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200),
    filename VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Blog articles
CREATE TABLE IF NOT EXISTS blog_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    content LONGTEXT NOT NULL,
    topic VARCHAR(200),
    featured_image VARCHAR(255),
    author_name VARCHAR(100),
    author_photo VARCHAR(255),
    published_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sermons
CREATE TABLE IF NOT EXISTS sermons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    description TEXT,
    video_url VARCHAR(500),
    video_file VARCHAR(255),
    thumbnail_image VARCHAR(255),
    preacher VARCHAR(100),
    sermon_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Announcements
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    type ENUM('weekly','special') NOT NULL DEFAULT 'weekly',
    image VARCHAR(255),
    day_of_week VARCHAR(20),
    event_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ministry/Department registrations
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ministry VARCHAR(100) NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(30),
    age VARCHAR(20),
    message TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contact form submissions
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(300),
    message TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin user (password: Admin@AFM2024 — CHANGE THIS IMMEDIATELY)
INSERT INTO admin_users (username, password_hash, full_name, email)
VALUES ('admin', '$2y$12$dKPdwiUZ8cz9oy2CDAtKe.bzW/U4CjWoosAJHM81zPQieNxbhFCYu', 'Site Administrator', 'admin@afmwarsaw.org')
ON DUPLICATE KEY UPDATE username=username;
