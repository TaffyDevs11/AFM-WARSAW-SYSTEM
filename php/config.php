<?php
/**
 * AFM Warsaw Assembly — Database Configuration
 *
 * XAMPP (local):
 *   DB_HOST = 'localhost'
 *   DB_USER = 'root'
 *   DB_PASS = ''  (empty by default)
 *
 * Hostinger (production):
 *   DB_HOST = 'localhost' (usually same on Hostinger shared)
 *   DB_USER = 'u123456789_afmwarsaw'  (your Hostinger DB username)
 *   DB_PASS = 'YourSecurePassword'
 *   DB_NAME = 'u123456789_afmwarsaw'  (your Hostinger DB name)
 *
 *   File path on Hostinger: /home/u123456789/public_html/php/config.php
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'afm_warsaw');

// Site URL — change for production
define('SITE_URL', 'http://localhost:8080/afm-warsaw');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed']);
            exit;
        }
    }
    return $pdo;
}
