<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$db          = getDB();
$pageTitle   = 'Sermons';
$activePage  = 'sermons';
$breadcrumbs = [['label' => 'Sermons']];
$successMsg  = $errorMsg = '';

try {
    $hasThumb = $db->query("SHOW COLUMNS FROM sermons LIKE 'thumbnail_image'")->fetch();
    if (!$hasThumb) {
        $db->exec("ALTER TABLE sermons ADD COLUMN thumbnail_image VARCHAR(255) NULL AFTER video_file");
    }
} catch (Throwable $e) {
    // Best-effort schema sync for older installs.
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_sermon') {
        $id        = (int)($_POST['id'] ?? 0);
        $title     = trim($_POST['title'] ?? '');
        $desc      = trim($_POST['description'] ?? '');
        $preacher  = trim($_POST['preacher'] ?? '');
        $video_url = trim($_POST['video_url'] ?? '');
        $sdate     = $_POST['sermon_date'] ?? date('Y-m-d');
        $thumbName = '';

        if (!empty($_FILES['thumbnail_image']['name']) && ($_FILES['thumbnail_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['thumbnail_image'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = 'Thumbnail upload failed. Please try again.';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (!in_array($ext, $allowed, true)) {
                    $errorMsg = 'Invalid thumbnail format. Allowed: JPG, PNG, WebP, GIF.';
                } else {
                    $thumbName = uniqid('sermon_') . '.' . $ext;
                    $thumbDest = __DIR__ . '/../uploads/sermons/' . $thumbName;
                    if (!move_uploaded_file($file['tmp_name'], $thumbDest)) {
                        $errorMsg = 'Failed to save thumbnail image. Check folder permissions.';
                        $thumbName = '';
                    }
                }
            }
        }

        if (!$title) {
            $errorMsg = 'Sermon title is required.';
        } elseif (!$errorMsg && $id) {
            $existingStmt = $db->prepare('SELECT thumbnail_image FROM sermons WHERE id=?');
            $existingStmt->execute([$id]);
            $existing = $existingStmt->fetch();

            if ($thumbName) {
                $stmt = $db->prepare('UPDATE sermons SET title=?,description=?,preacher=?,video_url=?,sermon_date=?,thumbnail_image=? WHERE id=?');
                $stmt->execute([$title, $desc, $preacher, $video_url, $sdate, $thumbName, $id]);
                if (!empty($existing['thumbnail_image']) && $existing['thumbnail_image'] !== $thumbName) {
                    @unlink(__DIR__ . '/../uploads/sermons/' . $existing['thumbnail_image']);
                }
            } else {
                $stmt = $db->prepare('UPDATE sermons SET title=?,description=?,preacher=?,video_url=?,sermon_date=? WHERE id=?');
                $stmt->execute([$title, $desc, $preacher, $video_url, $sdate, $id]);
            }
            $successMsg = 'Sermon updated.';
        } elseif (!$errorMsg) {
            $stmt = $db->prepare('INSERT INTO sermons (title,description,preacher,video_url,sermon_date,thumbnail_image) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$title, $desc, $preacher, $video_url, $sdate, $thumbName ?: null]);
            $successMsg = 'Sermon saved successfully!';
        }
    }

    if ($action === 'delete_sermon') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT thumbnail_image FROM sermons WHERE id=?');
        $stmt->execute([$id]);
        $thumb = $stmt->fetchColumn();
        if ($thumb) {
            @unlink(__DIR__ . '/../uploads/sermons/' . $thumb);
        }
        $db->prepare('DELETE FROM sermons WHERE id=?')->execute([$id]);
        $successMsg = 'Sermon deleted.';
    }
}

$allSermons = $db->query("SELECT * FROM sermons ORDER BY sermon_date DESC")->fetchAll();
$grouped = [];
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

  <div id="new" class="panel" style="position:sticky;top:1rem;">
    <div class="panel-header">
      <div class="panel-title"><?= $editSermon ? 'Edit Sermon' : 'Add Sermon' ?></div>
      <?php if ($editSermon): ?><a href="sermons.php" class="btn btn-ghost btn-sm">+ New Instead</a><?php endif; ?>
    </div>
    <div class="panel-body">
      <form method="POST" enctype="multipart/form-data" id="sermon-form">
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
          <div class="field-hint">Use the embed URL from YouTube or Vimeo. YouTube: https://www.youtube.com/embed/[ID] | Vimeo: https://player.vimeo.com/video/[ID]</div>
        </div>

        <div class="video-preview" id="video-preview" style="margin-bottom:1rem;">
          <iframe id="video-iframe" src="<?= htmlspecialchars($editSermon['video_url'] ?? '') ?>" allowfullscreen></iframe>
          <?php if (!($editSermon['video_url'] ?? '')): ?>
          <div class="video-placeholder">
            <div class="video-placeholder-icon">Play</div>
            <div class="video-placeholder-text">Video preview will appear here</div>
          </div>
          <?php endif; ?>
        </div>

        <div class="form-field" style="margin-bottom:1rem;">
          <label class="field-label">Thumbnail Image</label>
          <div class="upload-zone" id="sermon-thumb-drop" style="padding:1.5rem;">
            <div class="upload-zone-icon" style="font-size:1.5rem;">Image</div>
            <div class="upload-zone-text"><?= !empty($editSermon['thumbnail_image']) ? 'Click to replace thumbnail' : 'Click or drag thumbnail here' ?></div>
            <div class="upload-zone-hint">Optional. Shown on the website and in the admin list.</div>
          </div>
          <input type="file" id="sermon-thumb-input" name="thumbnail_image" accept="image/*" class="hidden">
          <div class="img-preview-wrap" style="margin-top:.75rem;min-height:60px;display:flex;align-items:center;justify-content:center;">
            <img id="sermon-thumb-preview" class="img-preview <?= !empty($editSermon['thumbnail_image']) ? 'visible' : '' ?>" src="<?= !empty($editSermon['thumbnail_image']) ? '../uploads/sermons/' . htmlspecialchars($editSermon['thumbnail_image']) : '' ?>" alt="Sermon thumbnail preview">
            <span id="sermon-thumb-placeholder" class="<?= !empty($editSermon['thumbnail_image']) ? 'hidden' : '' ?>" style="color:var(--text-dim);font-size:0.8rem;padding:1.5rem;">Thumbnail preview will appear here</span>
          </div>
        </div>

        <div class="form-field" style="margin-bottom:1.2rem;">
          <label class="field-label">Description / Summary</label>
          <textarea class="f-textarea" name="description" placeholder="Brief description of this sermon..." style="min-height:80px;"><?= htmlspecialchars($editSermon['description'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-full">
          <?= $editSermon ? 'Update Sermon' : 'Save Sermon' ?>
        </button>
      </form>
    </div>
  </div>

  <div>
    <?php if ($grouped): ?>
      <?php foreach ($grouped as $month => $sermons): ?>
      <div class="panel mb-3">
        <div class="panel-header">
          <div class="panel-title"><?= htmlspecialchars($month) ?></div>
          <span style="font-size:.75rem;color:var(--text-dim);"><?= count($sermons) ?> sermon<?= count($sermons) !== 1 ? 's' : '' ?></span>
        </div>
        <div class="card-list">
          <?php foreach ($sermons as $s): ?>
          <div class="card-list-item">
            <?php if (!empty($s['thumbnail_image'])): ?>
              <div class="cli-thumb"<?= !empty($s['video_url']) ? ' style="cursor:pointer;" onclick="openVideoModal(' . json_encode($s['video_url']) . ', ' . json_encode($s['title']) . ')"' : '' ?>>
                <img src="../uploads/sermons/<?= htmlspecialchars($s['thumbnail_image']) ?>" alt="<?= htmlspecialchars($s['title']) ?>" loading="lazy">
              </div>
            <?php else: ?>
              <div class="cli-thumb"<?= !empty($s['video_url']) ? ' style="cursor:pointer;background:var(--red-dim);border:1px solid rgba(204,27,27,.3);" onclick="openVideoModal(' . json_encode($s['video_url']) . ', ' . json_encode($s['title']) . ')"' : ' style="background:var(--red-dim);border:1px solid rgba(204,27,27,.3);"' ?>>
                <?= $s['video_url'] ? 'Play' : 'Sermon' ?>
              </div>
            <?php endif; ?>
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
                <?php if (!empty($s['thumbnail_image'])): ?>
                  <span class="cli-dot"></span>
                  <span class="badge badge-gold">Thumbnail</span>
                <?php endif; ?>
              </div>
              <?php if ($s['description']): ?>
                <div style="font-size:.75rem;color:var(--text-dim);margin-top:4px;"><?= htmlspecialchars(substr($s['description'], 0, 80)) ?>...</div>
              <?php endif; ?>
            </div>
            <div class="cli-actions">
              <?php if ($s['video_url']): ?>
                <button class="btn btn-ghost btn-sm btn-icon" onclick="openVideoModal(<?= json_encode($s['video_url']) ?>, <?= json_encode($s['title']) ?>)" title="Preview">Play</button>
              <?php endif; ?>
              <a href="sermons.php?edit=<?= $s['id'] ?>#new" class="btn btn-ghost btn-sm btn-icon" title="Edit">Edit</a>
              <button class="btn btn-danger btn-sm btn-icon" onclick="doDeleteSermon(<?= $s['id'] ?>, <?= json_encode($s['title']) ?>)" title="Delete">Delete</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="panel">
        <div class="empty-state" style="padding:4rem 2rem;">
          <div class="empty-icon">Sermons</div>
          <div class="empty-title">No Sermons Yet</div>
          <div class="empty-text">Add your first sermon using the form on the left.</div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="modal-overlay" id="video-modal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="vm-title">Watch Sermon</div>
      <button class="modal-close" onclick="closeVideoModal()">x</button>
    </div>
    <div style="aspect-ratio:16/9;background:#000;">
      <iframe id="vm-frame" src="" width="100%" height="100%" frameborder="0" allowfullscreen style="display:block;"></iframe>
    </div>
  </div>
</div>

<form method="POST" id="delete-sermon-form" style="display:none;">
  <input type="hidden" name="action" value="delete_sermon">
  <input type="hidden" name="id" id="delete-sermon-id">
</form>

<script>
  initDropZone('sermon-thumb-drop', 'sermon-thumb-input');

  document.getElementById('video-url-input')?.addEventListener('input', function () {
    const url = this.value.trim();
    const iframe = document.getElementById('video-iframe');
    const ph = document.querySelector('.video-placeholder');
    if (iframe) iframe.src = url;
    if (ph) ph.classList.toggle('hidden', !!url);
  });

  document.getElementById('sermon-thumb-input')?.addEventListener('change', function () {
    const file = this.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('sermon-thumb-preview');
      const ph = document.getElementById('sermon-thumb-placeholder');
      if (img) {
        img.src = e.target.result;
        img.classList.add('visible');
      }
      if (ph) ph.classList.add('hidden');
    };
    reader.readAsDataURL(file);
  });

  function openVideoModal(url, title) {
    if (!url) return;
    document.getElementById('vm-frame').src = url;
    document.getElementById('vm-title').textContent = 'Watch: ' + title;
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
