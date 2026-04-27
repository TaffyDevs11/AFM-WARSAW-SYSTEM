<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$ministry  = trim($_POST['ministry'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$age       = trim($_POST['age'] ?? '');
$message   = trim($_POST['message'] ?? '');

$errors = [];
if (empty($ministry))  $errors[] = 'Ministry name is required.';
if (empty($full_name)) $errors[] = 'Full name is required.';
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$db   = getDB();
$stmt = $db->prepare('INSERT INTO registrations (ministry, full_name, email, phone, age, message) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$ministry, $full_name, $email, $phone, $age, $message]);

echo json_encode(['success' => true, 'message' => 'Registration submitted successfully! We will be in touch soon. God bless you.']);
