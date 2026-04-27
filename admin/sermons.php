<?php
// Hostinger path: /home/u123456789/public_html/admin/sermons.php
require_once __DIR__ . '/auth.php';
requireAdmin();

$db          = getDB();
$pageTitle   = 'Sermons';
$activePage  = 'sermons';
$breadcrumbs = [['label' => 'Sermons']];
$successMsg  = $errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_sermon') {
        $id        = (int)($_POST['id'] ?? 0);
        $title     = trim($_POST['title']       ?? '');
        $desc      = trim($_POST['description'] ?? '');
        $preacher  = trim($_POST['preacher']    ?? '');
        $video_url = trim($_POST['video_url']   ?? '');
        $sdate     = $_POST['sermon_date']      ?? date('Y-m-d');

        if (!$title) { $errorMsg = 'Sermon title is required.'; }
        elseif ($id) {
            $stmt = $db->prepare('UPDATE sermons SET title=?,description=?,preacher=?,video_url=?,sermon_date=? WHERE id=?');
            $stmt->execute([$title,$desc,$preacher,$video_url,$sdate,$id]);
            $successMsg = 'Sermon updated.';
        } else {
            $stmt = $db->prepare('INSERT INTO sermons (title,description,preacher,video_url,sermon_date) VALUES (?,?,?,?,?)');
            $stmt->execute([$title,$desc,$preacher,$video_url,$sdate]);
            $successMsg = 'Sermon saved successfully!';
        }
    }

    if ($action === 'delete_sermon') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare('DELETE FROM sermons WHERE id=?')->execute([$id]);
        $successMsg = 'Sermon deleted.';
    }
}

// Group sermons by month/year
$allSermons = $db->query("SELECT * FROM sermons ORDER BY sermon_date DESC")->fetchAll();
$grouped    = [];
foreach ($allSermons as $s) {
    $key = date('F Y', strtotime($s['sermon_date']));
    $grouped[$key][] = $s;
}

$editSermon = null;
if (isset($_GET['edit'])) {
    $est = $db->prepare('SELECT * FROM sermons WHERE id=?');
    $est->execute([(int)$_GET['edit']]);
    $editSermon = $est->fetch();
}

include 'layout.php';
?>

<div class="two-col" style="align-items:start;">

  <!-- FORM -->
  <div id="new" class="panel" style="position:sticky;top:1rem;">
    <div class="panel-header">
      <div class="panel-title"><?= $editSermon ? '✏️ Edit Sermon' : '🎙️ Add Sermon' ?></div>
      <?php if ($editSermon): ?><a href="sermons.php" class="btn btn-ghost btn-sm">+ New Instead</a><?php endif; ?>
    </div>
    <div class="panel-body">
      <form method="POST" id="sermon-form">
        <input type="hidden" name="action" value="save_sermon">
        <input type="hidden" name="id" value="<?= $editSermon['id'] ?? 0 ?>">

        <div class="form-field" style="margin-bottom:1rem;">
          <label class="field-label">Sermon Title <span class="req">*</span></label>
          <input class="f-input" type="text" name="title" placeholder="e.g. The Kingdom Within You" value="<?= htmlspecialchars($editSermon['title'] ?? '') ?>" required>
        </div>

        <div class="form-row" style="margin-bottom:1rem;">
          <div class="form-field">
            <label class="field-label">Preacher</label>
            <input class="f-input" type="text" name="preacher" placeholder="e.g. Pastor Grace Moyo" value="<?= htmlspecialchars($editSermon['preacher'] ?? '') ?>">
          </div>
          <div class="form-field">
            <label class="field-label">Sermon Date <span class="req">*</span></label>
            <input class="f-input" type="date" name="sermon_date" value="<?= $editSermon['sermon_date'] ?? date('Y-m-d') ?>" required>
          </div>
        </div>

        <div class="form-field" style="margin-bottom:1rem;">
          <label class="field-label">Video Embed URL</label>
          <input class="f-input" type="url" name="video_url" id="video-url-input"
            placeholder="https://www.youtube.com/embed/VIDEO_ID"
            value="<?= htmlspecialchars($editSermon['video_url'] ?? '') ?>">
          <div class="field-hint">Use the <strong>embed</strong> URL from YouTube or Vimeo. <br>YouTube: https://www.youtube.com/embed/[ID] &nbsp;|&nbsp; Vimeo: https://player.vimeo.com/video/[ID]</div>
        </div>

        <!-- Video preview -->
        <div class="video-preview" id="video-preview" style="margin-bottom:1rem;">
          <iframe id="video-iframe" src="<?= htmlspecialchars($editSermon['video_url'] ?? '') ?>" allowfullscreen></iframe>
          <?php if (!($editSermon['video_url'] ?? '')): ?>
          <div class="video-placeholder">
            <div class="video-placeholder-icon">▶</div>
            <div class="video-placeholder-text">Video preview will appear here</div>
          </div>
          <?php endif; ?>
        </div>

        <div class="form-field" style="margin-bottom:1.2rem;">
          <label class="field-label">Description / Summary</label>
          <textarea class="f-textarea" name="description" placeholder="Brief description of this sermon…" style="min-height:80px;"><?= htmlspecialchars($editSermon['description'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-full">
          <?= $editSermon ? '💾 Update Sermon' : '🎙️ Save Sermon' ?>
        </button>
      </form>
    </div>
  </div>

  <!-- SERMONS LIST BY MONTH -->
  <div>
    <?php if ($grouped): ?>
      <?php foreach ($grouped as $month => $sermons): ?>
      <div class="panel mb-3">
        <div class="panel-header">
          <div class="panel-title">🗓️ <?= htmlspecialchars($month) ?></div>
          <span style="font-size:.75rem;color:var(--text-dim);"><?= count($sermons) ?> sermon<?= count($sermons) !== 1 ? 's' : '' ?></span>
        </div>
        <div class="card-list">
          <?php foreach ($sermons as $s): ?>
          <div class="card-list-item">
            <!-- Play button thumb -->
            <div class="cli-thumb" style="background:var(--red-dim);border:1px solid rgba(204,27,27,.3);" onclick="openVideoModal('<?= htmlspecialchars($s['video_url'] ?? '') ?>', '<?= addslashes($s['title']) ?>')" <?= $s['video_url'] ? 'style="cursor:pointer;background:var(--red-dim);border:1px solid rgba(204,27,27,.3);"' : '' ?>>
              <?= $s['video_url'] ? '▶' : '🎙️' ?>
            </div>
            <div>
              <div class="cli-title"><?= htmlspecialchars($s['title']) ?></div>
              <div class="cli-meta">
                <?php if ($s['preacher']): ?>
                  <span><?= htmlspecialchars($s['preacher']) ?></span>
                  <span class="cli-dot"></span>
                <?php endif; ?>
                <span><?= date('d M Y', strtotime($s['sermon_date'])) ?></span>
                <?php if ($s['video_url']): ?>
                  <span class="cli-dot"></span>
                  <span class="badge badge-red">Video</span>
                <?php endif; ?>
              </div>
              <?php if ($s['description']): ?>
                <div style="font-size:.75rem;color:var(--text-dim);margin-top:4px;"><?= htmlspecialchars(substr($s['description'],0,80)) ?>…</div>
              <?php endif; ?>
            </div>
            <div class="cli-actions">
              <?php if ($s['video_url']): ?>
                <button class="btn btn-ghost btn-sm btn-icon" onclick="openVideoModal('<?= htmlspecialchars($s['video_url']) ?>','<?= addslashes($s['title']) ?>')" title="Preview">▶</button>
              <?php endif; ?>
              <a href="sermons.php?edit=<?= $s['id'] ?>#new" class="btn btn-ghost btn-sm btn-icon" title="Edit">✏️</a>
              <button class="btn btn-danger btn-sm btn-icon" onclick="doDeleteSermon(<?= $s['id'] ?>, '<?= addslashes($s['title']) ?>')" title="Delete">🗑️</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="panel">
        <div class="empty-state" style="padding:4rem 2rem;">
          <div class="empty-icon">🎙️</div>
          <div class="empty-title">No Sermons Yet</div>
          <div class="empty-text">Add your first sermon using the form on the left.</div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- VIDEO MODAL -->
<div class="modal-overlay" id="video-modal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="vm-title">▶ Watch Sermon</div>
      <button class="modal-close" onclick="closeVideoModal()">✕</button>
    </div>
    <div style="aspect-ratio:16/9;background:#000;">
      <iframe id="vm-frame" src="" width="100%" height="100%" frameborder="0" allowfullscreen style="display:block;"></iframe>
    </div>
  </div>
</div>

<!-- DELETE FORM -->
<form method="POST" id="delete-sermon-form" style="display:none;">
  <input type="hidden" name="action" value="delete_sermon">
  <input type="hidden" name="id" id="delete-sermon-id">
</form>

<script>
  // Live video preview in form
  document.getElementById('video-url-input')?.addEventListener('input', function () {
    const url = this.value.trim();
    const iframe = document.getElementById('video-iframe');
    const ph = document.querySelector('.video-placeholder');
    if (iframe) iframe.src = url;
    if (ph) ph.classList.toggle('hidden', !!url);
  });

  function openVideoModal(url, title) {
    if (!url) return;
    document.getElementById('vm-frame').src = url;
    document.getElementById('vm-title').textContent = '▶ ' + title;
    openModal('video-modal');
  }
  function closeVideoModal() {
    document.getElementById('vm-frame').src = '';
    closeModal('video-modal');
  }

  function doDeleteSermon(id, title) {
    confirmDelete(`Delete sermon "${title}"?`, () => {
      document.getElementById('delete-sermon-id').value = id;
      document.getElementById('delete-sermon-form').submit();
    });
  }

  <?php if ($successMsg || $errorMsg): ?>
  window.addEventListener('load', () => showToast(<?= json_encode($successMsg ?: $errorMsg) ?>, <?= $successMsg ? "'success'" : "'error'" ?>));
  <?php endif; ?>
</script>

<?php include 'layout_footer.php'; ?>
