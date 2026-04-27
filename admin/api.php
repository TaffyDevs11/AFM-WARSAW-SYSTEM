<?php
/**
 * AFM Warsaw Admin — AJAX API
 * Handles async requests from admin JS
 * Hostinger path: /home/u123456789/public_html/admin/api.php
 */
require_once __DIR__ . '/auth.php';
requireAdmin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db     = getDB();

function respond(array $data): void {
    echo json_encode($data);
    exit;
}

switch ($action) {

    // ── Dashboard counts ─────────────────────────────────────────────
    case 'counts':
        respond([
            'gallery'       => $db->query("SELECT COUNT(*) FROM gallery")->fetchColumn(),
            'blog'          => $db->query("SELECT COUNT(*) FROM blog_articles")->fetchColumn(),
            'sermons'       => $db->query("SELECT COUNT(*) FROM sermons")->fetchColumn(),
            'announcements' => $db->query("SELECT COUNT(*) FROM announcements")->fetchColumn(),
            'registrations' => $db->query("SELECT COUNT(*) FROM registrations")->fetchColumn(),
            'contacts'      => $db->query("SELECT COUNT(*) FROM contact_submissions")->fetchColumn(),
        ]);
        break;

    // ── Gallery ──────────────────────────────────────────────────────
    case 'gallery_list':
        $cat  = $_GET['category'] ?? '';
        if ($cat && $cat !== 'All') {
            $stmt = $db->prepare("SELECT id, title, filename, category, uploaded_at FROM gallery WHERE category=? ORDER BY uploaded_at DESC");
            $stmt->execute([$cat]);
        } else {
            $stmt = $db->query("SELECT id, title, filename, category, uploaded_at FROM gallery ORDER BY uploaded_at DESC");
        }
        respond(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Blog articles ─────────────────────────────────────────────────
    case 'blog_list':
        $stmt = $db->query("SELECT id, title, topic, author_name, published_at, featured_image FROM blog_articles ORDER BY published_at DESC");
        respond(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'blog_single':
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM blog_articles WHERE id=?");
        $stmt->execute([$id]);
        $row  = $stmt->fetch();
        respond($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Not found']);
        break;

    // ── Sermons ───────────────────────────────────────────────────────
    case 'sermon_list':
        $stmt = $db->query("SELECT id, title, preacher, sermon_date, video_url FROM sermons ORDER BY sermon_date DESC");
        respond(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Announcements ─────────────────────────────────────────────────
    case 'announcement_list':
        $type = $_GET['type'] ?? '';
        if ($type === 'weekly' || $type === 'special') {
            $stmt = $db->prepare("SELECT * FROM announcements WHERE type=? ORDER BY created_at DESC");
            $stmt->execute([$type]);
        } else {
            $stmt = $db->query("SELECT * FROM announcements ORDER BY created_at DESC");
        }
        respond(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Registrations ────────────────────────────────────────────────
    case 'registration_list':
        $ministry = $_GET['ministry'] ?? '';
        if ($ministry) {
            $stmt = $db->prepare("SELECT * FROM registrations WHERE ministry=? ORDER BY submitted_at DESC");
            $stmt->execute([$ministry]);
        } else {
            $stmt = $db->query("SELECT * FROM registrations ORDER BY submitted_at DESC");
        }
        respond(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Contacts ─────────────────────────────────────────────────────
    case 'contact_list':
        $stmt = $db->query("SELECT * FROM contact_submissions ORDER BY submitted_at DESC");
        respond(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Gallery delete (AJAX) ─────────────────────────────────────────
    case 'delete_gallery':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success' => false]);
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT filename FROM gallery WHERE id=?");
        $stmt->execute([$id]);
        $row  = $stmt->fetch();
        if ($row) {
            @unlink(__DIR__ . '/../uploads/gallery/' . $row['filename']);
            $db->prepare("DELETE FROM gallery WHERE id=?")->execute([$id]);
            respond(['success' => true, 'message' => 'Image deleted.']);
        }
        respond(['success' => false, 'message' => 'Not found.']);
        break;

    default:
        respond(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
}
