<?php
/**
 * AFM Warsaw Admin — Auth Helper
 * Hostinger path: /home/u123456789/public_html/admin/auth.php
 */
require_once __DIR__ . '/../php/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // set to true on Hostinger HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function requireAdmin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function adminLogin(string $username, string $password): bool {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_user'] = $user['username'];
        return true;
    }
    return false;
}
