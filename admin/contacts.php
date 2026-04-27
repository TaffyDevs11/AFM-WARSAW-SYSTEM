<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$db          = getDB();
$pageTitle   = 'Contact Inbox';
$activePage  = 'contacts';
$breadcrumbs = [['label' => 'Contact Inbox']];
$successMsg  = $errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'delete_contact') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare('DELETE FROM contact_submissions WHERE id=?')->execute([$id]);
        $successMsg = 'Message deleted.';
    }
}

$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $db->prepare("SELECT * FROM contact_submissions WHERE name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ? ORDER BY submitted_at DESC");
    $q = "%{$search}%";
    $stmt->execute([$q,$q,$q,$q]);
} else {
    $stmt = $db->query("SELECT * FROM contact_submissions ORDER BY submitted_at DESC");
}
$messages = $stmt->fetchAll();

include 'layout.php';
?>

<div class="panel">
  <div class="toolbar">
    <div class="search-wrap">
      <span class="search-icon">🔍</span>
      <input class="search-input" type="text" id="inbox-search" placeholder="Search messages…" value="<?= htmlspecialchars($search) ?>">
    </div>
    <span style="font-size:.78rem;color:var(--text-dim);"><?= count($messages) ?> message<?= count($messages)!==1?'s':'' ?></span>
    <button class="btn btn-ghost btn-sm" onclick="exportContacts()">⬇ Export CSV</button>
  </div>

  <?php if ($messages): ?>
    <div id="inbox-list">
      <?php foreach ($messages as $m): ?>
      <div class="inbox-item unread" style="cursor:pointer;" onclick="openMessage(<?= $m['id'] ?>)">
        <div class="inbox-avatar"><?= strtoupper(substr($m['name'],0,1)) ?></div>
        <div style="overflow:hidden;flex:1;">
          <div style="display:flex;align-items:baseline;gap:8px;">
            <span class="inbox-from"><?= htmlspecialchars($m['name']) ?></span>
            <span style="font-size:.72rem;color:var(--gold);"><?= htmlspecialchars($m['email']) ?></span>
          </div>
          <div class="inbox-subject">
            <?php if ($m['subject']): ?>
              <strong style="color:var(--text);"><?= htmlspecialchars($m['subject']) ?></strong>
              &nbsp;—&nbsp;
            <?php endif; ?>
            <span><?= htmlspecialchars(substr($m['message'],0,120)) ?><?= strlen($m['message'])>120?'…':'' ?></span>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;">
          <div class="inbox-time"><?= date('d M Y', strtotime($m['submitted_at'])) ?></div>
          <button class="btn btn-danger btn-sm btn-icon"
            onclick="event.stopPropagation();doDeleteContact(<?= $m['id'] ?>,'<?= addslashes($m['name']) ?>')"
            title="Delete">🗑️</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state" style="padding:5rem 2rem;">
      <div class="empty-icon">💬</div>
      <div class="empty-title">Inbox is Empty</div>
      <div class="empty-text">Contact form submissions will appear here when people reach out.</div>
    </div>
  <?php endif; ?>
</div>

<!-- MESSAGE DETAIL MODAL -->
<div class="modal-overlay" id="msg-modal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-title">💬 Message</div>
      <button class="modal-close" onclick="closeModal('msg-modal')">✕</button>
    </div>
    <div class="modal-body" id="msg-modal-body">Loading…</div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('msg-modal')">Close</button>
      <button class="btn btn-primary" id="msg-reply-btn">✉ Reply via Email</button>
    </div>
  </div>
</div>

<!-- Delete form -->
<form method="POST" id="del-contact-form" style="display:none;">
  <input type="hidden" name="action" value="delete_contact">
  <input type="hidden" name="id" id="del-contact-id">
</form>

<script>
  initTableSearch('inbox-search', 'inbox-list', null);

  // Override table search for inbox (uses divs not table rows)
  document.getElementById('inbox-search')?.addEventListener('input', function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll('#inbox-list .inbox-item').forEach(item=>{
      const text = item.textContent.toLowerCase();
      item.style.display = text.includes(q) ? '' : 'none';
    });
  });

  const msgData = <?= json_encode(array_column($messages, null, 'id')) ?>;
  let activeEmail = '';

  function openMessage(id){
    const m = msgData[id];
    if(!m) return;
    activeEmail = m.email;
    document.getElementById('msg-modal-body').innerHTML = `
      <div class="detail-header">
        <div class="inbox-avatar" style="width:52px;height:52px;font-size:1.2rem;font-weight:700;">${m.name.charAt(0).toUpperCase()}</div>
        <div class="detail-meta">
          <div class="detail-title">${m.name}</div>
          <div class="detail-subtitle">
            <a href="mailto:${m.email}" style="color:var(--gold);">${m.email}</a>
          </div>
          <div style="font-size:.72rem;color:var(--text-dim);margin-top:4px;">
            ${new Date(m.submitted_at).toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'})}
          </div>
        </div>
      </div>
      ${m.subject ? `<div style="margin-bottom:1rem;"><span class="badge badge-gold">Subject: ${m.subject}</span></div>` : ''}
      <div style="background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem;line-height:1.8;color:var(--text-muted);font-size:.9rem;">
        ${m.message.replace(/\n/g,'<br>')}
      </div>
    `;
    document.getElementById('msg-reply-btn').onclick = ()=>{
      window.location.href=`mailto:${m.email}?subject=Re: ${encodeURIComponent(m.subject||'Your Message to AFM Warsaw')}`;
    };
    openModal('msg-modal');
  }

  function doDeleteContact(id, name){
    confirmDelete(`Delete message from "${name}"?`,()=>{
      document.getElementById('del-contact-id').value=id;
      document.getElementById('del-contact-form').submit();
    });
  }

  function exportContacts(){
    const rows=[['Name','Email','Subject','Message','Date']];
    <?= json_encode($messages) ?>.forEach(m=>{
      rows.push([m.name,m.email,m.subject||'',m.message,m.submitted_at]);
    });
    const csv=rows.map(r=>r.map(c=>`"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
    const a=document.createElement('a');
    a.href='data:text/csv;charset=utf-8,'+encodeURIComponent(csv);
    a.download='afm-contacts.csv';
    a.click();
  }

  <?php if($successMsg||$errorMsg): ?>
  window.addEventListener('load',()=>showToast(<?=json_encode($successMsg?:$errorMsg)?>,<?=$successMsg?"'success'":"'error'"?>));
  <?php endif; ?>
</script>

<?php include 'layout_footer.php'; ?>
