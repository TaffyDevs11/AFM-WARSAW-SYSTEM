<?php
// Hostinger path: /home/u123456789/public_html/admin/overview.php
require_once __DIR__ . '/auth.php';
requireAdmin();

$db = getDB();
$pageTitle  = 'Dashboard Overview';
$activePage = 'overview';

// Counts
$galleryCount  = $db->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
$blogCount     = $db->query("SELECT COUNT(*) FROM blog_articles")->fetchColumn();
$sermonCount   = $db->query("SELECT COUNT(*) FROM sermons")->fetchColumn();
$annCount      = $db->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
$regCount      = $db->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
$contactCount  = $db->query("SELECT COUNT(*) FROM contact_submissions")->fetchColumn();

// Recent registrations (last 5)
$recentRegs    = $db->query("SELECT * FROM registrations ORDER BY submitted_at DESC LIMIT 5")->fetchAll();
// Recent blog posts
$recentBlog    = $db->query("SELECT id, title, author_name, published_at FROM blog_articles ORDER BY published_at DESC LIMIT 4")->fetchAll();
// Recent contacts
$recentContacts = $db->query("SELECT * FROM contact_submissions ORDER BY submitted_at DESC LIMIT 4")->fetchAll();
// Ministry breakdown for registrations
$ministryBreakdown = $db->query("SELECT ministry, COUNT(*) as cnt FROM registrations GROUP BY ministry ORDER BY cnt DESC")->fetchAll();

include 'layout.php';
?>

<!-- STATS -->
<div class="stats-grid">
  <div class="stat-card gold">
    <div class="stat-icon">🖼️</div>
    <div class="stat-value" data-count="<?= $galleryCount ?>"><?= $galleryCount ?></div>
    <div class="stat-label">Gallery Images</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">📝</div>
    <div class="stat-value" data-count="<?= $blogCount ?>"><?= $blogCount ?></div>
    <div class="stat-label">Blog Articles</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon">🎙️</div>
    <div class="stat-value" data-count="<?= $sermonCount ?>"><?= $sermonCount ?></div>
    <div class="stat-label">Sermons</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon">📢</div>
    <div class="stat-value" data-count="<?= $annCount ?>"><?= $annCount ?></div>
    <div class="stat-label">Announcements</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon">📋</div>
    <div class="stat-value" data-count="<?= $regCount ?>"><?= $regCount ?></div>
    <div class="stat-label">Registrations</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">💬</div>
    <div class="stat-value" data-count="<?= $contactCount ?>"><?= $contactCount ?></div>
    <div class="stat-label">Contact Messages</div>
  </div>
</div>

<!-- QUICK ACTIONS -->
<div class="panel mb-3">
  <div class="panel-header">
    <div class="panel-title">⚡ Quick Actions</div>
  </div>
  <div class="panel-body">
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
      <a href="gallery.php#upload"       class="btn btn-primary">🖼️ Upload Image</a>
      <a href="blog.php#new"             class="btn btn-secondary">📝 New Article</a>
      <a href="sermons.php#new"          class="btn btn-secondary">🎙️ Add Sermon</a>
      <a href="announcements.php#new"    class="btn btn-secondary">📢 Announcement</a>
      <a href="registrations.php"        class="btn btn-ghost">📋 View Registrations</a>
      <a href="contacts.php"             class="btn btn-ghost">💬 Contact Inbox</a>
    </div>
  </div>
</div>

<div class="two-col">

  <!-- RECENT REGISTRATIONS -->
  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">📋 Recent Registrations</div>
      <a href="registrations.php" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <?php if ($recentRegs): ?>
      <div class="card-list">
        <?php foreach ($recentRegs as $r): ?>
        <div class="card-list-item">
          <div class="cli-thumb"><?= strtoupper(substr($r['full_name'],0,1)) ?></div>
          <div>
            <div class="cli-title"><?= htmlspecialchars($r['full_name']) ?></div>
            <div class="cli-meta">
              <span class="badge badge-blue"><?= htmlspecialchars($r['ministry']) ?></span>
              <span class="cli-dot"></span>
              <?= date('d M Y', strtotime($r['submitted_at'])) ?>
            </div>
          </div>
          <div class="cli-actions">
            <a href="registrations.php" class="btn btn-ghost btn-sm btn-icon" title="View">👁</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">📋</div>
        <div class="empty-title">No Registrations Yet</div>
        <div class="empty-text">Ministry registration forms will appear here.</div>
      </div>
    <?php endif; ?>
  </div>

  <!-- MINISTRY BREAKDOWN -->
  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">📊 Registration Breakdown</div>
    </div>
    <div class="panel-body">
      <?php if ($ministryBreakdown): ?>
        <?php $maxCount = $ministryBreakdown[0]['cnt'] ?? 1; ?>
        <?php foreach ($ministryBreakdown as $m): ?>
        <div style="margin-bottom:1rem;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
            <span style="font-size:.82rem;color:var(--text-muted);"><?= htmlspecialchars($m['ministry']) ?></span>
            <span style="font-size:.82rem;font-weight:700;color:var(--gold);"><?= $m['cnt'] ?></span>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" style="width:<?= round(($m['cnt']/$maxCount)*100) ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state" style="padding:2rem 0;">
          <div class="empty-icon" style="font-size:2rem;">📊</div>
          <div class="empty-text">No registration data yet.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- RECENT BLOG ARTICLES -->
  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">📝 Recent Articles</div>
      <a href="blog.php" class="btn btn-ghost btn-sm">Manage</a>
    </div>
    <?php if ($recentBlog): ?>
      <div class="card-list">
        <?php foreach ($recentBlog as $a): ?>
        <div class="card-list-item">
          <div class="cli-thumb" style="background:var(--gold-dim);font-size:.7rem;font-weight:700;color:var(--gold);">
            <?= date('d<br>M', strtotime($a['published_at'])) ?>
          </div>
          <div>
            <div class="cli-title truncate"><?= htmlspecialchars($a['title']) ?></div>
            <div class="cli-meta"><?= htmlspecialchars($a['author_name'] ?: 'Unknown') ?></div>
          </div>
          <div class="cli-actions">
            <a href="blog.php" class="btn btn-ghost btn-sm btn-icon">✏️</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">📝</div>
        <div class="empty-title">No Articles Yet</div>
        <a href="blog.php#new" class="btn btn-primary btn-sm mt-2">Write First Article</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- CONTACT INBOX PREVIEW -->
  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">💬 Contact Inbox</div>
      <a href="contacts.php" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <?php if ($recentContacts): ?>
      <?php foreach ($recentContacts as $c): ?>
      <div class="inbox-item unread">
        <div class="inbox-avatar"><?= strtoupper(substr($c['name'],0,1)) ?></div>
        <div style="overflow:hidden;">
          <div class="inbox-from"><?= htmlspecialchars($c['name']) ?> &mdash; <span style="color:var(--gold);font-size:.75rem;"><?= htmlspecialchars($c['email']) ?></span></div>
          <div class="inbox-subject"><?= htmlspecialchars($c['subject'] ?: $c['message']) ?></div>
        </div>
        <div class="inbox-time"><?= date('d M', strtotime($c['submitted_at'])) ?></div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">💬</div>
        <div class="empty-title">Inbox Empty</div>
        <div class="empty-text">Contact form submissions will appear here.</div>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php include 'layout_footer.php'; ?>
