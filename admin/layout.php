<?php
/**
 * Admin Layout Partial
 * Usage: include 'layout.php' at top of each admin page AFTER setting:
 *   $pageTitle    = 'Page Title'
 *   $activePage   = 'overview' | 'gallery' | 'blog' | 'sermons' | 'announcements' | 'registrations' | 'contacts' | 'settings'
 *   $breadcrumbs  = [['label'=>'X','url'=>'y.php'], ...]  (optional)
 *
 * Hostinger path: /home/u123456789/public_html/admin/layout.php
 */

// Fetch unread counts for badges
$navCounts = [];
try {
    $navCounts['registrations'] = getDB()->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
    $navCounts['contacts']      = getDB()->query("SELECT COUNT(*) FROM contact_submissions")->fetchColumn();
} catch (Throwable $e) {
    $navCounts = ['registrations' => 0, 'contacts' => 0];
}

function navItem(string $href, string $icon, string $label, string $key, string $active, array $counts = []): void {
    $isActive = $active === $key;
    $badge    = $counts[$key] ?? 0;
    $cls      = $isActive ? 'nav-item active' : 'nav-item';
    echo "<a href=\"{$href}\" class=\"{$cls}\">";
    echo "  <span class=\"nav-icon\">{$icon}</span>";
    echo "  <span>{$label}</span>";
    if ($badge > 0) echo "  <span class=\"nav-badge\">{$badge}</span>";
    echo '</a>';
}

$pageTitle   = $pageTitle   ?? 'Dashboard';
$activePage  = $activePage  ?? 'overview';
$breadcrumbs = $breadcrumbs ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — AFM Warsaw Admin</title>
  <link rel="icon" type="image/svg+xml" href="../images/logowhite2.png">
  <link rel="stylesheet" href="admin.css">
  <script src="admin.js"></script>
</head>
<body>
<div class="admin-layout">

  <!-- ═══ SIDEBAR ═══ -->
  <aside class="admin-sidebar">
    <div class="sidebar-brand">
      <img src="../images/logowhite2.png" alt="AFM Warsaw" class="sidebar-logo">
      <div class="sidebar-brand-text">
        <div class="brand-name">AFM Warsaw</div>
        <div class="brand-sub">Admin Panel</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Dashboard</div>
      <?php navItem('overview.php', '', 'Overview', 'overview', $activePage) ?>

      <div class="nav-section-label">Content</div>
      <?php navItem('gallery.php',       '',  'Gallery',       'gallery',       $activePage) ?>
      <?php navItem('blog.php',          '',  'Blog Articles', 'blog',          $activePage) ?>
      <?php navItem('sermons.php',       '',  'Sermons',       'sermons',       $activePage) ?>
      <?php navItem('announcements.php', '',  'Announcements', 'announcements', $activePage) ?>

      <div class="nav-section-label">Responses</div>
      <?php navItem('registrations.php', '', 'Registrations',  'registrations', $activePage, $navCounts) ?>
      <?php navItem('contacts.php',      '', 'Contact Inbox',  'contacts',      $activePage, $navCounts) ?>

      <div class="nav-section-label">System</div>
      <?php navItem('settings.php', '', 'Settings', 'settings', $activePage) ?>
      <a href="../index.html" target="_blank" class="nav-item">
        <span class="nav-icon"></span><span>View Website</span>
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-avatar"><?= strtoupper(substr($_SESSION['admin_user'] ?? 'A', 0, 1)) ?></div>
        <div>
          <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['admin_user'] ?? '') ?></div>
          <div class="sidebar-user-role">Administrator</div>
        </div>
      </div>
      <form method="POST" action="logout.php">
        <button type="submit" class="btn-logout">
          <span>⏻</span> Sign Out
        </button>
      </form>
    </div>
  </aside>

  <!-- ═══ TOPBAR ═══ -->
  <header class="admin-topbar">
    <div class="topbar-left">
      <div class="topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
      <div class="topbar-breadcrumb">
        <a href="overview.php">Dashboard</a>
        <?php foreach ($breadcrumbs as $crumb): ?>
          &rsaquo;
          <?php if (isset($crumb['url'])): ?>
            <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['label']) ?></a>
          <?php else: ?>
            <?= htmlspecialchars($crumb['label']) ?>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-time">⏱ <span id="admin-clock"></span></div>
      <a href="../index.html" target="_blank" class="topbar-btn" title="View Website">🌐</a>
      <a href="settings.php" class="topbar-btn" title="Settings">⚙️</a>
      <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Toggle sidebar">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <!-- ═══ CONTENT (injected by each page) ═══ -->
  <main class="admin-content">
    <?php if (!empty($successMsg)): ?>
      <div class="alert alert-success"><span class="alert-icon">✓</span><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-error"><span class="alert-icon">✕</span><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>
