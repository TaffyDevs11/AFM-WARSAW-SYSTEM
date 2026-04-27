<?php
// Hostinger path: /home/u123456789/public_html/admin/blog.php
require_once __DIR__ . '/auth.php';
requireAdmin();

$db          = getDB();
$pageTitle   = 'Blog Articles';
$activePage  = 'blog';
$breadcrumbs = [['label' => 'Blog Articles']];
$successMsg  = $errorMsg = '';

// ── ACTIONS ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_article') {
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title']       ?? '');
        $content     = trim($_POST['content']     ?? '');
        $topic       = trim($_POST['topic']       ?? '');
        $author_name = trim($_POST['author_name'] ?? '');
        $pub_date    = $_POST['published_at']     ?? date('Y-m-d');

        $feat_img = $auth_photo = '';

        // Featured image upload
        if (!empty($_FILES['featured_image']['name']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
            $feat_img = uniqid('blog_') . '.' . $ext;
            move_uploaded_file($_FILES['featured_image']['tmp_name'], __DIR__ . '/../uploads/blog/' . $feat_img);
        }

        // Author photo upload
        if (!empty($_FILES['author_photo']['name']) && $_FILES['author_photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['author_photo']['name'], PATHINFO_EXTENSION));
            $auth_photo = uniqid('auth_') . '.' . $ext;
            move_uploaded_file($_FILES['author_photo']['tmp_name'], __DIR__ . '/../uploads/blog/' . $auth_photo);
        }

        if (!$title || !$content) {
            $errorMsg = 'Title and content are required.';
        } elseif ($id) {
            // Edit existing
            if ($feat_img) {
                $stmt = $db->prepare('UPDATE blog_articles SET title=?,content=?,topic=?,featured_image=?,author_name=?,author_photo=?,published_at=? WHERE id=?');
                $stmt->execute([$title,$content,$topic,$feat_img,$author_name,$auth_photo ?: null,$pub_date,$id]);
            } else {
                $stmt = $db->prepare('UPDATE blog_articles SET title=?,content=?,topic=?,author_name=?,published_at=? WHERE id=?');
                $stmt->execute([$title,$content,$topic,$author_name,$pub_date,$id]);
            }
            $successMsg = 'Article updated successfully.';
        } else {
            // New article
            $stmt = $db->prepare('INSERT INTO blog_articles (title,content,topic,featured_image,author_name,author_photo,published_at) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$title,$content,$topic,$feat_img,$author_name,$auth_photo,$pub_date]);
            $successMsg = 'Article published successfully!';
        }
    }

    if ($action === 'delete_article') {
        $id = (int)($_POST['id'] ?? 0);
        $row = $db->prepare('SELECT featured_image, author_photo FROM blog_articles WHERE id=?');
        $row->execute([$id]);
        $files = $row->fetch();
        if ($files) {
            foreach (['featured_image','author_photo'] as $f) {
                if ($files[$f]) @unlink(__DIR__ . '/../uploads/blog/' . $files[$f]);
            }
        }
        $db->prepare('DELETE FROM blog_articles WHERE id=?')->execute([$id]);
        $successMsg = 'Article deleted.';
    }
}

// ── DATA ─────────────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $db->prepare("SELECT * FROM blog_articles WHERE title LIKE ? OR author_name LIKE ? ORDER BY published_at DESC");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $db->query("SELECT * FROM blog_articles ORDER BY published_at DESC");
}
$articles = $stmt->fetchAll();

// For editing — load specific article
$editArticle = null;
if (isset($_GET['edit'])) {
    $est = $db->prepare('SELECT * FROM blog_articles WHERE id=?');
    $est->execute([(int)$_GET['edit']]);
    $editArticle = $est->fetch();
}

include 'layout.php';
?>

<div class="two-col" style="align-items:start;">

  <!-- FORM PANEL -->
  <div id="new" class="panel" style="position:sticky;top:1rem;">
    <div class="panel-header">
      <div class="panel-title"><?= $editArticle ? '✏️ Edit Article' : '📝 New Article' ?></div>
      <?php if ($editArticle): ?>
        <a href="blog.php" class="btn btn-ghost btn-sm">+ New Instead</a>
      <?php endif; ?>
    </div>
    <div class="panel-body">
      <form method="POST" enctype="multipart/form-data" id="article-form">
        <input type="hidden" name="action" value="save_article">
        <input type="hidden" name="id" value="<?= $editArticle['id'] ?? 0 ?>">

        <div class="form-field" style="margin-bottom:1rem;">
          <label class="field-label">Article Title <span class="req">*</span></label>
          <input class="f-input" type="text" name="title" placeholder="Title of the article…" value="<?= htmlspecialchars($editArticle['title'] ?? '') ?>" required>
        </div>

        <div class="form-row" style="margin-bottom:1rem;">
          <div class="form-field">
            <label class="field-label">Topic / Category</label>
            <input class="f-input" type="text" name="topic" placeholder="e.g. Faith, Prayer" value="<?= htmlspecialchars($editArticle['topic'] ?? '') ?>">
          </div>
          <div class="form-field">
            <label class="field-label">Publish Date <span class="req">*</span></label>
            <input class="f-input" type="date" name="published_at" value="<?= $editArticle['published_at'] ?? date('Y-m-d') ?>" required>
          </div>
        </div>

        <!-- Rich text content -->
        <div class="form-field" style="margin-bottom:1rem;">
          <label class="field-label">Article Content <span class="req">*</span></label>
          <div class="rich-toolbar">
            <button type="button" data-cmd="bold"          title="Bold"><b>B</b></button>
            <button type="button" data-cmd="italic"        title="Italic"><i>I</i></button>
            <button type="button" data-cmd="underline"     title="Underline"><u>U</u></button>
            <div class="sep"></div>
            <button type="button" data-cmd="insertUnorderedList" title="Bullet List">• List</button>
            <button type="button" data-cmd="insertOrderedList"   title="Numbered List">1. List</button>
            <div class="sep"></div>
            <button type="button" data-cmd="justifyLeft"   title="Left">⬅</button>
            <button type="button" data-cmd="justifyCenter" title="Center">↔</button>
            <button type="button" data-cmd="justifyRight"  title="Right">➡</button>
          </div>
          <div
            id="rich-editor"
            class="f-input rich-content"
            style="min-height:180px;overflow-y:auto;line-height:1.7;"
          ><?= $editArticle['content'] ?? '' ?></div>
          <textarea name="content" id="content-hidden" class="hidden" required><?= htmlspecialchars($editArticle['content'] ?? '') ?></textarea>
        </div>

        <div class="form-row" style="margin-bottom:1rem;">
          <div class="form-field">
            <label class="field-label">Author Name</label>
            <input class="f-input" type="text" name="author_name" placeholder="e.g. Pastor Grace Moyo" value="<?= htmlspecialchars($editArticle['author_name'] ?? '') ?>">
          </div>
          <div class="form-field">
            <label class="field-label">Author Photo</label>
            <input class="f-file" type="file" name="author_photo" accept="image/*" id="auth-photo-input">
            <div class="field-hint"><?= $editArticle['author_photo'] ? '✓ Photo on file. Upload new to replace.' : 'Optional' ?></div>
          </div>
        </div>

        <div class="form-field" style="margin-bottom:1.2rem;">
          <label class="field-label">Featured Image</label>
          <div class="upload-zone" id="feat-drop" style="padding:1.5rem;">
            <div class="upload-zone-icon" style="font-size:1.5rem;">📷</div>
            <div class="upload-zone-text"><?= $editArticle['featured_image'] ? 'Click to replace image' : 'Click or drag image here' ?></div>
          </div>
          <input type="file" id="feat-img-input" name="featured_image" accept="image/*" class="hidden">
          <img id="feat-preview" class="img-preview" src="<?= $editArticle['featured_image'] ? '../uploads/blog/' . $editArticle['featured_image'] : '' ?>" alt="">
          <?php if ($editArticle['featured_image']): ?>
            <script>document.addEventListener('DOMContentLoaded',()=>{const p=document.getElementById('feat-preview');p.classList.add('visible');});</script>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary w-full">
          <?= $editArticle ? '💾 Update Article' : '🚀 Publish Article' ?>
        </button>
      </form>
    </div>
  </div>

  <!-- ARTICLES LIST -->
  <div>
    <div class="panel">
      <div class="toolbar">
        <div class="search-wrap">
          <span class="search-icon">🔍</span>
          <input class="search-input" type="text" id="search-input" placeholder="Search articles…">
        </div>
        <span style="font-size:.78rem;color:var(--text-dim);"><?= count($articles) ?> articles</span>
      </div>
      <div class="tbl-wrap">
        <table class="tbl" id="articles-table">
          <thead><tr>
            <th>Image</th>
            <th>Title</th>
            <th>Author</th>
            <th>Published</th>
            <th>Topic</th>
            <th class="col-action">Actions</th>
          </tr></thead>
          <tbody>
          <?php foreach ($articles as $a): ?>
            <tr>
              <td>
                <?php if ($a['featured_image']): ?>
                  <img style="width:52px;height:40px;object-fit:cover;border-radius:6px;border:1px solid var(--border-gold);" src="../uploads/blog/<?= htmlspecialchars($a['featured_image']) ?>" alt="" loading="lazy">
                <?php else: ?>
                  <div style="width:52px;height:40px;background:var(--gold-dim);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">📝</div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:600;color:var(--text);font-size:.85rem;" class="truncate" style="max-width:200px;">
                  <?= htmlspecialchars($a['title']) ?>
                </div>
              </td>
              <td style="font-size:.8rem;"><?= htmlspecialchars($a['author_name'] ?: '—') ?></td>
              <td style="font-size:.8rem;white-space:nowrap;"><?= date('d M Y', strtotime($a['published_at'])) ?></td>
              <td><?php if ($a['topic']): ?><span class="badge badge-gold"><?= htmlspecialchars($a['topic']) ?></span><?php else: ?>—<?php endif; ?></td>
              <td>
                <div class="tbl-actions">
                  <a href="blog.php?edit=<?= $a['id'] ?>#new" class="btn btn-ghost btn-sm" title="Edit">✏️</a>
                  <button class="btn btn-danger btn-sm" title="Delete"
                    onclick="doDeleteArticle(<?= $a['id'] ?>, '<?= addslashes($a['title']) ?>')">🗑️</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$articles): ?>
            <tr><td colspan="6">
              <div class="empty-state">
                <div class="empty-icon">📝</div>
                <div class="empty-title">No Articles Published</div>
                <div class="empty-text">Write your first blog article using the form on the left.</div>
              </div>
            </td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- DELETE FORM -->
<form method="POST" id="delete-article-form" style="display:none;">
  <input type="hidden" name="action" value="delete_article">
  <input type="hidden" name="id" id="delete-article-id">
</form>

<script>
  initDropZone('feat-drop', 'feat-img-input');
  initTableSearch('search-input', 'articles-table', [1,2,3]);

  // Rich text editor sync
  document.addEventListener('DOMContentLoaded', () => {
    initRichText('rich-editor', 'content-hidden');
    // Set initial value
    const editor = document.getElementById('rich-editor');
    const hidden = document.getElementById('content-hidden');
    if (editor && hidden && editor.innerHTML) hidden.value = editor.innerHTML;
  });

  // Featured image preview
  document.getElementById('feat-img-input')?.addEventListener('change', function () {
    const file = this.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('feat-preview');
      img.src = e.target.result;
      img.classList.add('visible');
    };
    reader.readAsDataURL(file);
  });

  function doDeleteArticle(id, title) {
    confirmDelete(`Delete article "${title}"? This cannot be undone.`, () => {
      document.getElementById('delete-article-id').value = id;
      document.getElementById('delete-article-form').submit();
    });
  }

  <?php if ($successMsg || $errorMsg): ?>
  window.addEventListener('load', () => showToast(<?= json_encode($successMsg ?: $errorMsg) ?>, <?= $successMsg ? "'success'" : "'error'" ?>));
  <?php endif; ?>

  <?php if ($editArticle): ?>
  // Scroll to form
  document.addEventListener('DOMContentLoaded', () => document.getElementById('new')?.scrollIntoView({behavior:'smooth'}));
  <?php endif; ?>
</script>

<?php include 'layout_footer.php'; ?>
