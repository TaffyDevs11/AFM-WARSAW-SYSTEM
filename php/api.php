<?php
/**
 * AFM Warsaw Assembly — Public Data API
 * Serves JSON to the front-end JavaScript
 * Hostinger path: /home/u123456789/public_html/php/api.php
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = trim($_GET['action'] ?? '');
$db     = getDB();
$sermonsHasThumbnail = false;

try {
    $sermonsHasThumbnail = (bool)$db->query("SHOW COLUMNS FROM sermons LIKE 'thumbnail_image'")->fetch();
} catch (Throwable $e) {
    $sermonsHasThumbnail = false;
}

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    switch ($action) {

        // ── Gallery ─────────────────────────────────────────────────
        case 'gallery':
            $category = trim($_GET['category'] ?? '');
            if ($category && $category !== 'All') {
                $stmt = $db->prepare(
                    'SELECT id, title, filename, category, uploaded_at
                     FROM gallery WHERE category = ?
                     ORDER BY uploaded_at DESC'
                );
                $stmt->execute([$category]);
            } else {
                $stmt = $db->query(
                    'SELECT id, title, filename, category, uploaded_at
                     FROM gallery ORDER BY uploaded_at DESC'
                );
            }
            $rows = $stmt->fetchAll();
            // Add full URL to each image
            foreach ($rows as &$row) {
                $row['url'] = UPLOAD_URL . 'gallery/' . $row['filename'];
            }
            respond(['success' => true, 'count' => count($rows), 'data' => $rows]);
            break;

        case 'gallery_categories':
            $cats = $db->query(
                'SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL ORDER BY category'
            )->fetchAll(PDO::FETCH_COLUMN);
            respond(['success' => true, 'data' => $cats]);
            break;

        // ── Blog ────────────────────────────────────────────────────
        case 'blog':
            $limit = min((int)($_GET['limit'] ?? 50), 100);
            $stmt  = $db->prepare(
                'SELECT id, title, topic, featured_image, author_name, author_photo, published_at
                 FROM blog_articles
                 ORDER BY published_at DESC
                 LIMIT ?'
            );
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                $row['featured_image_url'] = $row['featured_image']
                    ? UPLOAD_URL . 'blog/' . $row['featured_image']
                    : null;
                $row['author_photo_url'] = $row['author_photo']
                    ? UPLOAD_URL . 'blog/' . $row['author_photo']
                    : null;
            }
            respond(['success' => true, 'count' => count($rows), 'data' => $rows]);
            break;

        case 'blog_single':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) respond(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = $db->prepare('SELECT * FROM blog_articles WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) respond(['success' => false, 'message' => 'Article not found'], 404);
            $row['featured_image_url'] = $row['featured_image']
                ? UPLOAD_URL . 'blog/' . $row['featured_image']
                : null;
            $row['author_photo_url'] = $row['author_photo']
                ? UPLOAD_URL . 'blog/' . $row['author_photo']
                : null;
            respond(['success' => true, 'data' => $row]);
            break;

        // ── Sermons ─────────────────────────────────────────────────
        case 'sermons':
            $limit = min((int)($_GET['limit'] ?? 50), 100);
            $sermonThumbSelect = $sermonsHasThumbnail ? ', thumbnail_image' : '';
            $stmt  = $db->prepare(
                'SELECT id, title, description, preacher, video_url, sermon_date' . $sermonThumbSelect . '
                 FROM sermons
                 ORDER BY sermon_date DESC
                 LIMIT ?'
            );
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll();

            foreach ($rows as &$row) {
                $thumb = $row['thumbnail_image'] ?? null;
                $row['thumbnail_image_url'] = $thumb
                    ? UPLOAD_URL . 'sermons/' . $thumb
                    : null;
            }

            // Group by month
            $grouped = [];
            foreach ($rows as $row) {
                $month = date('F Y', strtotime($row['sermon_date']));
                $grouped[$month][] = $row;
            }
            respond(['success' => true, 'count' => count($rows), 'data' => $rows, 'grouped' => $grouped]);
            break;

        case 'sermon_single':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) respond(['success' => false, 'message' => 'Invalid ID'], 400);
            $stmt = $db->prepare('SELECT * FROM sermons WHERE id = ?');
            $stmt->execute([$id]);
            $row  = $stmt->fetch();
            if (!$row) respond(['success' => false, 'message' => 'Sermon not found'], 404);
            $thumb = $row['thumbnail_image'] ?? null;
            $row['thumbnail_image_url'] = !empty($thumb)
                ? UPLOAD_URL . 'sermons/' . $thumb
                : null;
            respond(['success' => true, 'data' => $row]);
            break;

        // ── Announcements ────────────────────────────────────────────
        case 'announcements':
            $type = $_GET['type'] ?? '';
            if ($type === 'weekly') {
                $stmt = $db->query(
                    "SELECT id, title, type, image, day_of_week, created_at
                     FROM announcements WHERE type='weekly'
                     ORDER BY created_at DESC"
                );
            } elseif ($type === 'special') {
                $stmt = $db->query(
                    "SELECT id, title, type, image, event_date, created_at
                     FROM announcements WHERE type='special'
                     ORDER BY event_date DESC"
                );
            } else {
                $stmt = $db->query(
                    'SELECT * FROM announcements ORDER BY created_at DESC'
                );
            }
            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                $row['image_url'] = $row['image']
                    ? UPLOAD_URL . 'announcements/' . $row['image']
                    : null;
            }
            respond(['success' => true, 'count' => count($rows), 'data' => $rows]);
            break;

        default:
            respond(['success' => false, 'message' => 'Unknown action. Valid: gallery, gallery_categories, blog, blog_single, sermons, sermon_single, announcements'], 400);
    }

} catch (Throwable $e) {
    // Log error but never expose details to client
    error_log('AFM API Error: ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Server error. Please try again.'], 500);
}
