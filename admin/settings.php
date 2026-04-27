<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$db          = getDB();
$pageTitle   = 'Settings';
$activePage  = 'settings';
$breadcrumbs = [['label' => 'Settings']];
$successMsg  = $errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Change password
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            $errorMsg = 'All password fields are required.';
        } elseif ($new !== $confirm) {
            $errorMsg = 'New password and confirmation do not match.';
        } elseif (strlen($new) < 8) {
            $errorMsg = 'New password must be at least 8 characters.';
        } else {
            $stmt = $db->prepare('SELECT password_hash FROM admin_users WHERE id=?');
            $stmt->execute([$_SESSION['admin_id']]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($current, $row['password_hash'])) {
                $errorMsg = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare('UPDATE admin_users SET password_hash=? WHERE id=?')
                   ->execute([$hash, $_SESSION['admin_id']]);
                $successMsg = 'Password changed successfully.';
            }
        }
    }

    // Change username
    if ($action === 'change_username') {
        $newUser = trim($_POST['new_username'] ?? '');
        if (!$newUser) {
            $errorMsg = 'Username cannot be empty.';
        } elseif (strlen($newUser) < 3) {
            $errorMsg = 'Username must be at least 3 characters.';
        } else {
            // Check not taken
            $check = $db->prepare('SELECT id FROM admin_users WHERE username=? AND id!=?');
            $check->execute([$newUser, $_SESSION['admin_id']]);
            if ($check->fetch()) {
                $errorMsg = 'That username is already taken.';
            } else {
                $db->prepare('UPDATE admin_users SET username=? WHERE id=?')
                   ->execute([$newUser, $_SESSION['admin_id']]);
                $_SESSION['admin_user'] = $newUser;
                $successMsg = 'Username updated successfully.';
            }
        }
    }
}

// Get current user info
$currentUser = $db->prepare('SELECT * FROM admin_users WHERE id=?');
$currentUser->execute([$_SESSION['admin_id']]);
$user = $currentUser->fetch();

// Storage stats
$uploadDirs = ['gallery','blog','announcements','sermons'];
$storageSizes = [];
$totalStorage = 0;
foreach ($uploadDirs as $dir) {
    $path = __DIR__ . '/../uploads/' . $dir . '/';
    $size = 0;
    if (is_dir($path)) {
        foreach (glob($path . '*') as $f) { $size += filesize($f); }
    }
    $storageSizes[$dir] = $size;
    $totalStorage += $size;
}

include 'layout.php';
?>

<div class="two-col" style="align-items:start;">

  <!-- LEFT: Account settings -->
  <div>

    <!-- ACCOUNT INFO -->
    <div class="panel mb-3">
      <div class="panel-header">
        <div class="panel-title">👤 Account Information</div>
      </div>
      <div class="panel-body">
        <div style="display:flex;align-items:center;gap:1rem;padding-bottom:1.5rem;border-bottom:1px solid var(--border);margin-bottom:1.5rem;">
          <div style="width:64px;height:64px;border-radius:50%;background:var(--gold-dim);border:3px solid var(--gold);display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:900;color:var(--gold);">
            <?= strtoupper(substr($user['username'],0,1)) ?>
          </div>
          <div>
            <div style="font-size:1rem;font-weight:700;color:var(--white);"><?= htmlspecialchars($user['username']) ?></div>
            <div style="font-size:.78rem;color:var(--text-dim);">Administrator</div>
            <?php if ($user['email']): ?>
              <div style="font-size:.78rem;color:var(--gold);margin-top:3px;"><?= htmlspecialchars($user['email']) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Change username -->
        <div class="settings-section">
          <div class="settings-title">Change Username</div>
          <form method="POST">
            <input type="hidden" name="action" value="change_username">
            <div class="form-field" style="margin-bottom:1rem;">
              <label class="field-label">New Username <span class="req">*</span></label>
              <input class="f-input" type="text" name="new_username" placeholder="New username…" value="<?= htmlspecialchars($user['username']) ?>" required minlength="3">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Update Username</button>
          </form>
        </div>

        <!-- Change password -->
        <div class="settings-section">
          <div class="settings-title">Change Password</div>
          <form method="POST" id="pw-form">
            <input type="hidden" name="action" value="change_password">
            <div class="form-field" style="margin-bottom:1rem;">
              <label class="field-label">Current Password <span class="req">*</span></label>
              <input class="f-input" type="password" name="current_password" placeholder="Current password" required>
            </div>
            <div class="form-field" style="margin-bottom:1rem;">
              <label class="field-label">New Password <span class="req">*</span></label>
              <input class="f-input" type="password" name="new_password" placeholder="New password (min. 8 chars)" required minlength="8" id="new-pw">
            </div>
            <div class="form-field" style="margin-bottom:1.2rem;">
              <label class="field-label">Confirm New Password <span class="req">*</span></label>
              <input class="f-input" type="password" name="confirm_password" placeholder="Repeat new password" required minlength="8" id="confirm-pw">
              <div class="field-hint" id="pw-match-hint"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Change Password</button>
          </form>
        </div>
      </div>
    </div>

  </div>

  <!-- RIGHT: System info -->
  <div>

    <!-- STORAGE -->
    <div class="panel mb-3">
      <div class="panel-header">
        <div class="panel-title">💾 Storage Usage</div>
      </div>
      <div class="panel-body">
        <?php foreach ($storageSizes as $dir => $size): ?>
        <div style="margin-bottom:1.1rem;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
            <span style="font-size:.82rem;color:var(--text-muted);text-transform:capitalize;"><?= $dir ?></span>
            <span style="font-size:.78rem;font-weight:700;color:var(--gold);"><?= round($size/1024) ?> KB</span>
          </div>
          <div class="progress-bar">
            <?php $pct = $totalStorage > 0 ? round(($size/$totalStorage)*100) : 0; ?>
            <div class="progress-fill" style="width:<?= $pct ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <div style="border-top:1px solid var(--border);padding-top:1rem;margin-top:1rem;display:flex;justify-content:space-between;align-items:center;">
          <span style="font-size:.82rem;color:var(--text-muted);">Total Used</span>
          <span style="font-family:'Cinzel',serif;font-size:1.1rem;font-weight:700;color:var(--gold);"><?= round($totalStorage/1024) ?> KB</span>
        </div>
      </div>
    </div>

    <!-- DATABASE STATS -->
    <div class="panel mb-3">
      <div class="panel-header">
        <div class="panel-title">🗄️ Database</div>
      </div>
      <div class="panel-body">
        <?php
        $tables = ['gallery','blog_articles','sermons','announcements','registrations','contact_submissions'];
        foreach ($tables as $t):
            $count = $db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        ?>
        <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border);font-size:.83rem;">
          <span style="color:var(--text-muted);text-transform:capitalize;"><?= str_replace('_',' ',$t) ?></span>
          <span style="font-weight:700;color:var(--gold);"><?= $count ?> rows</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- QUICK LINKS -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">🔗 Quick Links</div>
      </div>
      <div class="panel-body" style="display:flex;flex-direction:column;gap:.75rem;">
        <a href="../index.html" target="_blank" class="btn btn-ghost w-full" style="justify-content:center;">🌐 View Home Page</a>
        <a href="../gallery.html" target="_blank" class="btn btn-ghost w-full" style="justify-content:center;">🖼️ View Gallery</a>
        <a href="../blog.html" target="_blank" class="btn btn-ghost w-full" style="justify-content:center;">📝 View Blog</a>
        <a href="../contact.html" target="_blank" class="btn btn-ghost w-full" style="justify-content:center;">📧 View Contact Page</a>
        <hr style="border:none;border-top:1px solid var(--border);">
        <form method="POST" action="logout.php">
          <button type="submit" class="btn btn-danger w-full" style="justify-content:center;">⏻ Sign Out</button>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
  // Password match indicator
  const newPw = document.getElementById('new-pw');
  const confirmPw = document.getElementById('confirm-pw');
  const hint = document.getElementById('pw-match-hint');

  function checkMatch(){
    if(!confirmPw.value) { hint.textContent=''; return; }
    if(newPw.value===confirmPw.value){
      hint.textContent='✓ Passwords match';
      hint.style.color='var(--green)';
    } else {
      hint.textContent='✕ Passwords do not match';
      hint.style.color='#ff6b6b';
    }
  }
  newPw?.addEventListener('input', checkMatch);
  confirmPw?.addEventListener('input', checkMatch);

  <?php if($successMsg||$errorMsg): ?>
  window.addEventListener('load',()=>showToast(<?=json_encode($successMsg?:$errorMsg)?>,<?=$successMsg?"'success'":"'error'"?>));
  <?php endif; ?>
</script>

<?php include 'layout_footer.php'; ?>
