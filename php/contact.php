<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if (empty($name))    $errors[] = 'Name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if (empty($message)) $errors[] = 'Message is required.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$db   = getDB();
$stmt = $db->prepare('INSERT INTO contact_submissions (name, email, subject, message) VALUES (?, ?, ?, ?)');
$stmt->execute([$name, $email, $subject, $message]);

echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been received. We will get back to you soon.']);
