<?php
// Hostinger path: /home/u123456789/public_html/admin/gallery.php
require_once __DIR__ . '/auth.php';
requireAdmin();

$db = getDB();
$pageTitle   = 'Gallery Manager';
$activePage  = 'gallery';
$breadcrumbs = [['label' => 'Gallery']];
$successMsg  = $errorMsg = '';

// ── HANDLE UPLOAD ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_gallery') {
        $title    = trim($_POST['title']    ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $file     = $_FILES['image']        ?? null;

        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp','gif'];
            if (in_array($ext, $allowed, true)) {
                $name = uniqid('gal_') . '.' . $ext;
                $dest = __DIR__ . '/../uploads/gallery/' . $name;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $stmt = $db->prepare('INSERT INTO gallery (title, filename, category) VALUES (?,?,?)');
                    $stmt->execute([$title, $name, $category]);
                    $successMsg = '✓ Image uploaded successfully!';
                } else { $errorMsg = 'Failed to save image file. Check folder permissions.'; }
            } else { $errorMsg = 'Invalid file type. Allowed: JPG, PNG, WebP, GIF.'; }
        } else { $errorMsg = 'Please select an image file.'; }
    }

    if ($action === 'delete_gallery') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT filename FROM gallery WHERE id = ?');
        $stmt->execute([$id]);
        $row  = $stmt->fetch();
        if ($row) {
            @unlink(__DIR__ . '/../uploads/gallery/' . $row['filename']);
            $db->prepare('DELETE FROM gallery WHERE id = ?')->execute([$id]);
            $successMsg = 'Image deleted.';
        }
    }

    if ($action === 'update_gallery') {
        $id    = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $cat   = trim($_POST['category'] ?? '');
        $stmt  = $db->prepare('UPDATE gallery SET title=?, category=? WHERE id=?');
        $stmt->execute([$title, $cat, $id]);
        $successMsg = 'Image updated.';
    }
}

// ── FETCH DATA ───────────────────────────────────────────────────────
$filterCat = $_GET['cat'] ?? '';
if ($filterCat && $filterCat !== 'All') {
    $stmt = $db->prepare('SELECT * FROM gallery WHERE category=? ORDER BY uploaded_at DESC');
    $stmt->execute([$filterCat]);
} else {
    $stmt = $db->query('SELECT * FROM gallery ORDER BY uploaded_at DESC');
}
$images    = $stmt->fetchAll();
$cats      = $db->query("SELECT DISTINCT category FROM gallery ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$totalSize = 0;
foreach ($images as $img) {
    $path = __DIR__ . '/../uploads/gallery/' . $img['filename'];
    if (file_exists($path)) $totalSize += filesize($path);
}

include 'layout.php';
?>

<div class="two-col" style="align-items:start;">

  <!-- UPLOAD PANEL -->
  <div id="upload" class="panel" style="position:sticky;top:1rem;">
    <div class="panel-header">
      <div class="panel-title">📤 Upload New Image</div>
    </div>
    <div class="panel-body">
      <form method="POST" enctype="multipart/form-data" id="upload-form">
        <input type="hidden" name="action" value="upload_gallery">

        <!-- Drop zone -->
        <div class="upload-zone" id="drop-zone" style="margin-bottom:1.2rem;">
          <div class="upload-zone-icon">🖼️</div>
          <div class="upload-zone-text">Click or drag &amp; drop image here</div>
          <div class="upload-zone-hint">JPG, PNG, WebP, GIF • Max 5 MB</div>
        </div>
        <input type="file" id="image-input" name="image" accept="image/*" class="hidden" required>

        <!-- Preview -->
        <div class="img-preview-wrap" style="margin-bottom:1.2rem;min-height:60px;display:flex;align-items:center;justify-content:center;">
          <img class="img-preview" id="img-preview" src="" alt="Preview">
          <span id="preview-placeholder" style="color:var(--text-dim);font-size:0.8rem;padding:1.5rem;">Preview will appear here</span>
        </div>

        <div class="form-row">
          <div class="form-field span2">
            <label class="field-label">Title / Caption</label>
            <input class="f-input" type="text" name="title" placeholder="Describe this image…">
          </div>
          <div class="form-field span2">
            <label class="field-label">Category <span class="req">*</span></label>
            <select class="f-select" name="category" required>
              <option>General</option>
              <option>Worship</option>
              <option>Events</option>
              <option>Ministries</option>
              <option>Outreach</option>
              <option>Youth</option>
            </select>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-full" id="upload-btn">
          📤 Upload Image
        </button>
      </form>
    </div>
    <div class="panel-footer">
      <span style="font-size:.72rem;color:var(--text-dim);">
        <?= count($images) ?> images &bull; <?= round($totalSize / 1024) ?> KB used
      </span>
    </div>
  </div>

  <!-- GALLERY GRID -->
  <div>
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">🖼️ Gallery (<?= count($images) ?>)</div>
        <div class="panel-actions">
          <select class="filter-select" id="cat-filter" onchange="filterGallery(this.value)">
            <option value="">All Categories</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $filterCat===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <?php if ($images): ?>
        <div class="gallery-admin-grid" id="gallery-grid">
          <?php foreach ($images as $img): ?>
          <div class="gallery-item-admin" data-cat="<?= htmlspecialchars($img['category']) ?>">
            <?php
              $src = '../uploads/gallery/' . htmlspecialchars($img['filename']);
            ?>
            <img src="<?= $src ?>" alt="<?= htmlspecialchars($img['title'] ?: '') ?>" loading="lazy">
            <div class="gallery-item-overlay">
              <div class="gallery-item-title"><?= htmlspecialchars($img['title'] ?: 'Untitled') ?></div>
              <div class="gallery-item-actions">
                <button class="btn btn-ghost btn-sm btn-icon"
                  onclick="openEditModal(<?= $img['id'] ?>, '<?= addslashes($img['title']) ?>', '<?= addslashes($img['category']) ?>')"
                  title="Edit">✏️</button>
                <button class="btn btn-danger btn-sm btn-icon"
                  onclick="doDelete(<?= $img['id'] ?>, '<?= addslashes($img['title'] ?: $img['filename']) ?>')"
                  title="Delete">🗑️</button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">🖼️</div>
          <div class="empty-title">No Images Yet</div>
          <div class="empty-text">Upload your first gallery image using the form on the left.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="edit-modal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">✏️ Edit Image</div>
      <button class="modal-close" onclick="closeModal('edit-modal')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" id="edit-form">
        <input type="hidden" name="action" value="update_gallery">
        <input type="hidden" name="id" id="edit-id">
        <div class="form-field" style="margin-bottom:1rem;">
          <label class="field-label">Title / Caption</label>
          <input class="f-input" type="text" name="title" id="edit-title" placeholder="Image caption…">
        </div>
        <div class="form-field">
          <label class="field-label">Category</label>
          <select class="f-select" name="category" id="edit-cat">
            <option>General</option><option>Worship</option><option>Events</option>
            <option>Ministries</option><option>Outreach</option><option>Youth</option>
          </select>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('edit-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="document.getElementById('edit-form').submit()">Save Changes</button>
    </div>
  </div>
</div>

<!-- DELETE FORM (hidden) -->
<form method="POST" id="delete-form" style="display:none;">
  <input type="hidden" name="action" value="delete_gallery">
  <input type="hidden" name="id" id="delete-id">
</form>

<script>
  // Drop zone
  initDropZone('drop-zone', 'image-input');

  // Image preview
  document.getElementById('image-input')?.addEventListener('change', function () {
    const file = this.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('img-preview');
      const ph  = document.getElementById('preview-placeholder');
      img.src = e.target.result;
      img.classList.add('visible');
      if (ph) ph.style.display = 'none';
    };
    reader.readAsDataURL(file);
  });

  // Category filter
  function filterGallery(cat) {
    document.querySelectorAll('.gallery-item-admin').forEach(el => {
      el.style.display = (!cat || el.dataset.cat === cat) ? '' : 'none';
    });
  }

  // Edit modal
  function openEditModal(id, title, cat) {
    document.getElementById('edit-id').value    = id;
    document.getElementById('edit-title').value = title;
    const sel = document.getElementById('edit-cat');
    for (let i=0; i<sel.options.length; i++) {
      if (sel.options[i].value === cat) { sel.selectedIndex = i; break; }
    }
    openModal('edit-modal');
  }

  // Delete
  function doDelete(id, name) {
    confirmDelete(`Delete "${name}"? This cannot be undone.`, () => {
      document.getElementById('delete-id').value = id;
      document.getElementById('delete-form').submit();
    });
  }

  // Upload loading state
  document.getElementById('upload-form')?.addEventListener('submit', function () {
    const btn = document.getElementById('upload-btn');
    btn.disabled = true;
    btn.textContent = 'Uploading…';
  });

  <?php if ($successMsg || $errorMsg): ?>
  window.addEventListener('load', () => {
    showToast(<?= json_encode($successMsg ?: $errorMsg) ?>, <?= $successMsg ? "'success'" : "'error'" ?>);
  });
  <?php endif; ?>
</script>

<?php include 'layout_footer.php'; ?>
