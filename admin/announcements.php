<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$db          = getDB();
$pageTitle   = 'Announcements';
$activePage  = 'announcements';
$breadcrumbs = [['label' => 'Announcements']];
$successMsg  = $errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_announcement') {
        $id    = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $type  = ($_POST['type'] ?? '') === 'special' ? 'special' : 'weekly';
        $day   = trim($_POST['day_of_week'] ?? '');
        $edate = $_POST['event_date'] ?: null;
        $img   = '';

        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $img = uniqid('ann_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/announcements/' . $img);
        }

        if (!$title) { $errorMsg = 'Title is required.'; }
        elseif ($id) {
            $upImg = $img ? ', image=?' : '';
            $params = $img
                ? [$title,$type,$day,$edate,$img,$id]
                : [$title,$type,$day,$edate,$id];
            $db->prepare("UPDATE announcements SET title=?,type=?,day_of_week=?,event_date=?{$upImg} WHERE id=?")
               ->execute($params);
            $successMsg = 'Announcement updated.';
        } else {
            $db->prepare('INSERT INTO announcements (title,type,image,day_of_week,event_date) VALUES (?,?,?,?,?)')
               ->execute([$title,$type,$img,$day,$edate]);
            $successMsg = 'Announcement published!';
        }
    }

    if ($action === 'delete_announcement') {
        $id = (int)($_POST['id'] ?? 0);
        $row = $db->prepare('SELECT image FROM announcements WHERE id=?');
        $row->execute([$id]);
        $f = $row->fetchColumn();
        if ($f) @unlink(__DIR__ . '/../uploads/announcements/' . $f);
        $db->prepare('DELETE FROM announcements WHERE id=?')->execute([$id]);
        $successMsg = 'Announcement deleted.';
    }
}

$weekly  = $db->query("SELECT * FROM announcements WHERE type='weekly'  ORDER BY created_at DESC")->fetchAll();
$special = $db->query("SELECT * FROM announcements WHERE type='special' ORDER BY event_date  DESC")->fetchAll();

$editAnn = null;
if (isset($_GET['edit'])) {
    $est = $db->prepare('SELECT * FROM announcements WHERE id=?');
    $est->execute([(int)$_GET['edit']]);
    $editAnn = $est->fetch();
}

include 'layout.php';
?>

<div class="two-col" style="align-items:start;">

  <!-- FORM -->
  <div id="new" class="panel" style="position:sticky;top:1rem;">
    <div class="panel-header">
      <div class="panel-title"><?= $editAnn ? '✏️ Edit Announcement' : '📢 New Announcement' ?></div>
      <?php if ($editAnn): ?><a href="announcements.php" class="btn btn-ghost btn-sm">+ New Instead</a><?php endif; ?>
    </div>
    <div class="panel-body">
      <form method="POST" enctype="multipart/form-data" id="ann-form">
        <input type="hidden" name="action" value="save_announcement">
        <input type="hidden" name="id" value="<?= $editAnn['id'] ?? 0 ?>">

        <div class="form-field" style="margin-bottom:1rem;">
          <label class="field-label">Title <span class="req">*</span></label>
          <input class="f-input" type="text" name="title" placeholder="Announcement title…" value="<?= htmlspecialchars($editAnn['title'] ?? '') ?>" required>
        </div>

        <div class="form-field" style="margin-bottom:1rem;">
          <label class="field-label">Type <span class="req">*</span></label>
          <select class="f-select" name="type" id="ann-type" onchange="toggleAnnType()">
            <option value="weekly"  <?= ($editAnn['type'] ?? '') !== 'special' ? 'selected' : '' ?>>📅 Weekly</option>
            <option value="special" <?= ($editAnn['type'] ?? '') === 'special'  ? 'selected' : '' ?>>📢 Special Event</option>
          </select>
        </div>

        <div id="field-day" class="form-field" style="margin-bottom:1rem;<?= ($editAnn['type'] ?? '') === 'special' ? 'display:none;' : '' ?>">
          <label class="field-label">Day of Week</label>
          <select class="f-select" name="day_of_week">
            <?php foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): ?>
              <option <?= ($editAnn['day_of_week'] ?? '') === $d ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="field-date" class="form-field" style="margin-bottom:1rem;<?= ($editAnn['type'] ?? '') !== 'special' ? 'display:none;' : '' ?>">
          <label class="field-label">Event Date</label>
          <input class="f-input" type="date" name="event_date" value="<?= htmlspecialchars($editAnn['event_date'] ?? '') ?>">
        </div>

        <div class="form-field" style="margin-bottom:1.2rem;">
          <label class="field-label">Image</label>
          <div class="upload-zone" id="ann-drop" style="padding:1.5rem;">
            <div class="upload-zone-icon" style="font-size:1.5rem;">🖼️</div>
            <div class="upload-zone-text"><?= ($editAnn['image'] ?? '') ? 'Click to replace' : 'Click or drag image' ?></div>
          </div>
          <input type="file" id="ann-img-input" name="image" accept="image/*" class="hidden">
          <img id="ann-preview" class="img-preview" src="<?= ($editAnn['image'] ?? '') ? '../uploads/announcements/'.htmlspecialchars($editAnn['image']) : '' ?>" alt="">
          <?php if (!empty($editAnn['image'])): ?>
            <script>document.addEventListener('DOMContentLoaded',()=>document.getElementById('ann-preview')?.classList.add('visible'));</script>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary w-full">
          <?= $editAnn ? '💾 Update' : '📢 Publish' ?>
        </button>
      </form>
    </div>
  </div>

  <!-- LISTS -->
  <div>
    <!-- Weekly -->
    <div class="panel mb-3">
      <div class="panel-header">
        <div class="panel-title">📅 Weekly Announcements (<?= count($weekly) ?>)</div>
      </div>
      <?php if ($weekly): ?>
        <?php foreach ($weekly as $a): ?>
        <div class="ann-card">
          <?php if ($a['image']): ?>
            <img src="../uploads/announcements/<?= htmlspecialchars($a['image']) ?>" class="ann-img" alt="">
          <?php else: ?>
            <div class="ann-img">📅</div>
          <?php endif; ?>
          <div>
            <div class="ann-title"><?= htmlspecialchars($a['title']) ?></div>
            <div class="ann-meta">
              <span class="badge badge-gold"><?= htmlspecialchars($a['day_of_week'] ?: 'Weekly') ?></span>
              <span style="font-size:.7rem;color:var(--text-dim);"><?= date('d M Y', strtotime($a['created_at'])) ?></span>
            </div>
          </div>
          <div style="display:flex;gap:6px;flex-shrink:0;">
            <a href="announcements.php?edit=<?= $a['id'] ?>#new" class="btn btn-ghost btn-sm btn-icon">✏️</a>
            <button class="btn btn-danger btn-sm btn-icon" onclick="doDeleteAnn(<?= $a['id'] ?>,'<?= addslashes($a['title']) ?>')">🗑️</button>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state" style="padding:2.5rem;"><div class="empty-icon" style="font-size:2rem;">📅</div><div class="empty-text">No weekly announcements yet.</div></div>
      <?php endif; ?>
    </div>

    <!-- Special -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">📢 Special Announcements (<?= count($special) ?>)</div>
      </div>
      <?php if ($special): ?>
        <?php foreach ($special as $a): ?>
        <div class="ann-card">
          <?php if ($a['image']): ?>
            <img src="../uploads/announcements/<?= htmlspecialchars($a['image']) ?>" class="ann-img" alt="">
          <?php else: ?>
            <div class="ann-img">📢</div>
          <?php endif; ?>
          <div>
            <div class="ann-title"><?= htmlspecialchars($a['title']) ?></div>
            <div class="ann-meta">
              <span class="badge badge-red"><?= $a['event_date'] ? date('d M Y', strtotime($a['event_date'])) : 'TBC' ?></span>
            </div>
          </div>
          <div style="display:flex;gap:6px;flex-shrink:0;">
            <a href="announcements.php?edit=<?= $a['id'] ?>#new" class="btn btn-ghost btn-sm btn-icon">✏️</a>
            <button class="btn btn-danger btn-sm btn-icon" onclick="doDeleteAnn(<?= $a['id'] ?>,'<?= addslashes($a['title']) ?>')">🗑️</button>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state" style="padding:2.5rem;"><div class="empty-icon" style="font-size:2rem;">📢</div><div class="empty-text">No special announcements yet.</div></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<form method="POST" id="del-ann-form" style="display:none;">
  <input type="hidden" name="action" value="delete_announcement">
  <input type="hidden" name="id" id="del-ann-id">
</form>

<script>
  initDropZone('ann-drop','ann-img-input');
  document.getElementById('ann-img-input')?.addEventListener('change', function(){
    const r=new FileReader();
    r.onload=e=>{const img=document.getElementById('ann-preview');img.src=e.target.result;img.classList.add('visible');};
    if(this.files?.[0]) r.readAsDataURL(this.files[0]);
  });
  function doDeleteAnn(id,title){
    confirmDelete(`Delete "${title}"?`,()=>{document.getElementById('del-ann-id').value=id;document.getElementById('del-ann-form').submit();});
  }
  // Set initial type on load
  document.addEventListener('DOMContentLoaded',toggleAnnType);
  <?php if($successMsg||$errorMsg): ?>
  window.addEventListener('load',()=>showToast(<?=json_encode($successMsg?:$errorMsg)?>,<?=$successMsg?"'success'":"'error'"?>));
  <?php endif; ?>
</script>

<?php include 'layout_footer.php'; ?>
