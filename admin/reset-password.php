#!/usr/bin/env php
<?php
/**
 * AFM Warsaw — Admin Password Reset Utility
 * 
 * Run from command line:
 *   php reset-password.php
 *
 * Or on Hostinger via SSH:
 *   php /home/u123456789/public_html/admin/reset-password.php
 *
 * SECURITY: Delete this file after use!
 */

// Only allow CLI execution
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script can only be run from the command line.\n";
    exit(1);
}

require_once __DIR__ . '/../php/config.php';

echo "\n";
echo "╔══════════════════════════════════════════════╗\n";
echo "║   AFM Warsaw — Admin Password Reset Tool    ║\n";
echo "╚══════════════════════════════════════════════╝\n";
echo "\n";

$db = getDB();

// List current admin users
$users = $db->query("SELECT id, username, email, created_at FROM admin_users")->fetchAll();
echo "Current admin users:\n";
foreach ($users as $u) {
    echo "  [{$u['id']}] {$u['username']} ({$u['email']}) — created {$u['created_at']}\n";
}
echo "\n";

// Prompt for username
echo "Enter username to reset (or 'new' to create a new admin): ";
$username = trim(fgets(STDIN));

if ($username === 'new') {
    echo "Enter new username: ";
    $newUser = trim(fgets(STDIN));
    echo "Enter email for new admin: ";
    $newEmail = trim(fgets(STDIN));
    echo "Enter password: ";
    $pw1 = trim(fgets(STDIN));
    echo "Confirm password: ";
    $pw2 = trim(fgets(STDIN));

    if ($pw1 !== $pw2) { echo "\nPasswords do not match. Exiting.\n"; exit(1); }
    if (strlen($pw1) < 8) { echo "\nPassword must be at least 8 characters.\n"; exit(1); }

    $hash = password_hash($pw1, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)");
    $stmt->execute([$newUser, $hash, $newEmail]);
    echo "\n✅ Admin user '{$newUser}' created successfully!\n";

} else {
    // Check user exists
    $stmt = $db->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "\n❌ User '{$username}' not found.\n";
        exit(1);
    }

    echo "Enter new password (min 8 chars): ";
    $pw1 = trim(fgets(STDIN));
    echo "Confirm new password: ";
    $pw2 = trim(fgets(STDIN));

    if ($pw1 !== $pw2)    { echo "\nPasswords do not match. Exiting.\n"; exit(1); }
    if (strlen($pw1) < 8) { echo "\nPassword must be at least 8 characters.\n"; exit(1); }

    $hash = password_hash($pw1, PASSWORD_BCRYPT, ['cost' => 12]);
    $db->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?")
       ->execute([$hash, $username]);

    echo "\n✅ Password for '{$username}' reset successfully!\n";
}

echo "\n⚠️  IMPORTANT: Delete this file after use!\n";
echo "   rm " . __FILE__ . "\n\n";
