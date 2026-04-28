#!/usr/bin/env php
<?php
/**
 * AFM Warsaw — Database Setup Utility
 *
 * Run ONCE after deployment:
 *   php setup-database.php
 *
 * Hostinger SSH:
 *   php /home/u123456789/public_html/setup-database.php
 *
 * SECURITY: Delete this file after use!
 */

if (PHP_SAPI !== 'cli') {
    // Allow web access only in development — shows a simple installer page
    if (!isset($_GET['setup_key']) || $_GET['setup_key'] !== 'AFM_SETUP_2026') {
        http_response_code(403);
        die('Access denied. Run this from CLI or provide setup_key parameter.');
    }
    $isWeb = true;
} else {
    $isWeb = false;
}

require_once __DIR__ . '/php/config.php';

$log = [];

function logMsg(string $msg, bool $isWeb = false): void {
    if ($isWeb) {
        echo "<p>{$msg}</p>\n";
        flush();
    } else {
        echo $msg . "\n";
    }
}

function runSQL(PDO $db, string $sql, bool $isWeb = false): bool {
    try {
        $db->exec($sql);
        return true;
    } catch (PDOException $e) {
        logMsg("  ⚠️  Warning: " . $e->getMessage(), $isWeb);
        return false;
    }
}

if ($isWeb) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>AFM Setup</title>";
    echo "<style>body{font-family:monospace;background:#0f1730;color:#e2e8f0;padding:2rem;} p{margin:.3rem 0;} .ok{color:#22c55e;} .err{color:#ef4444;}</style></head><body>";
    echo "<h2 style='color:#c9a227;'>AFM Warsaw — Database Setup</h2>";
}

logMsg("AFM Warsaw — Database Setup Utility", $isWeb);
logMsg("=====================================", $isWeb);
logMsg("", $isWeb);

try {
    $db = getDB();
    logMsg("✅ Database connection successful (DB: " . DB_NAME . ")", $isWeb);
} catch (Throwable $e) {
    logMsg("❌ Database connection FAILED: " . $e->getMessage(), $isWeb);
    logMsg("   → Check DB_HOST, DB_USER, DB_PASS, DB_NAME in php/config.php", $isWeb);
    exit(1);
}

$db->exec("SET FOREIGN_KEY_CHECKS=0");

// Create tables
$tables = [

'admin_users' => "CREATE TABLE IF NOT EXISTS admin_users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name    VARCHAR(100),
    email        VARCHAR(100),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

'gallery' => "CREATE TABLE IF NOT EXISTS gallery (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200),
    filename    VARCHAR(255) NOT NULL,
    category    VARCHAR(100) DEFAULT 'General',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

'blog_articles' => "CREATE TABLE IF NOT EXISTS blog_articles (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(300) NOT NULL,
    content        LONGTEXT     NOT NULL,
    topic          VARCHAR(200),
    featured_image VARCHAR(255),
    author_name    VARCHAR(100),
    author_photo   VARCHAR(255),
    published_at   DATE         NOT NULL,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_published (published_at)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

'sermons' => "CREATE TABLE IF NOT EXISTS sermons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(300) NOT NULL,
    description TEXT,
    video_url   VARCHAR(500),
    video_file  VARCHAR(255),
    thumbnail_image VARCHAR(255),
    preacher    VARCHAR(100),
    sermon_date DATE         NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sermon_date (sermon_date)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

'announcements' => "CREATE TABLE IF NOT EXISTS announcements (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(300)                   NOT NULL,
    type         ENUM('weekly','special')        NOT NULL DEFAULT 'weekly',
    image        VARCHAR(255),
    day_of_week  VARCHAR(20),
    event_date   DATE,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

'registrations' => "CREATE TABLE IF NOT EXISTS registrations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    ministry     VARCHAR(100) NOT NULL,
    full_name    VARCHAR(200) NOT NULL,
    email        VARCHAR(100),
    phone        VARCHAR(30),
    age          VARCHAR(20),
    message      TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ministry (ministry)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

'contact_submissions' => "CREATE TABLE IF NOT EXISTS contact_submissions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(200) NOT NULL,
    email        VARCHAR(100) NOT NULL,
    subject      VARCHAR(300),
    message      TEXT         NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

];

logMsg("\nCreating tables...", $isWeb);
foreach ($tables as $name => $sql) {
    if (runSQL($db, $sql, $isWeb)) {
        logMsg("  ✅ Table '{$name}' ready", $isWeb);
    } else {
        logMsg("  ❌ Table '{$name}' FAILED", $isWeb);
    }
}

// Seed default admin
$existing = $db->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
if ($existing == 0) {
    $hash = password_hash('Admin@AFM2026', PASSWORD_BCRYPT, ['cost' => 12]);
    $db->prepare("INSERT INTO admin_users (username, password_hash, full_name, email) VALUES (?,?,?,?)")
       ->execute(['admin', $hash, 'Site Administrator', 'admin@afmwarsaw.org']);
    logMsg("\n✅ Default admin created:", $isWeb);
    logMsg("   Username: admin", $isWeb);
    logMsg("   Password: Admin@AFM2026", $isWeb);
    logMsg("   ⚠️  CHANGE THIS PASSWORD IMMEDIATELY after first login!", $isWeb);
} else {
    logMsg("\n✅ Admin users already exist — skipping seed.", $isWeb);
}

// Create upload directories
$dirs = ['gallery', 'blog', 'announcements', 'sermons'];
logMsg("\nChecking upload directories...", $isWeb);
foreach ($dirs as $dir) {
    $path = __DIR__ . '/uploads/' . $dir;
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            logMsg("  ✅ Created: uploads/{$dir}", $isWeb);
        } else {
            logMsg("  ❌ Could not create: uploads/{$dir} — check permissions", $isWeb);
        }
    } else {
        logMsg("  ✅ Exists: uploads/{$dir}", $isWeb);
    }
}

$db->exec("SET FOREIGN_KEY_CHECKS=1");

logMsg("", $isWeb);
logMsg("✅ Setup complete! Your site is ready.", $isWeb);
logMsg("", $isWeb);
logMsg("Next steps:", $isWeb);
logMsg("  1. Visit: " . SITE_URL . "/admin/login.php", $isWeb);
logMsg("  2. Login with: admin / Admin@AFM2026", $isWeb);
logMsg("  3. Change your password immediately in Settings", $isWeb);
logMsg("  4. Delete this file: setup-database.php", $isWeb);
logMsg("", $isWeb);

if ($isWeb) {
    echo "<p style='color:#c9a227;font-weight:bold;margin-top:1rem;'>⚠️ DELETE this file (setup-database.php) from your server immediately!</p>";
    echo "</body></html>";
}
