<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$db          = getDB();
$pageTitle   = 'Ministry Registrations';
$activePage  = 'registrations';
$breadcrumbs = [['label' => 'Registrations']];
$successMsg  = $errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete_registration') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare('DELETE FROM registrations WHERE id=?')->execute([$id]);
        $successMsg = 'Registration removed.';
    }
    if ($action === 'delete_all_ministry') {
        $m = trim($_POST['ministry'] ?? '');
        if ($m) {
            $db->prepare('DELETE FROM registrations WHERE ministry=?')->execute([$m]);
            $successMsg = "All registrations for {$m} deleted.";
        }
    }
}

// All registrations
$all = $db->query("SELECT * FROM registrations ORDER BY submitted_at DESC")->fetchAll();

// Ministry list
$ministries = $db->query("SELECT DISTINCT ministry FROM registrations ORDER BY ministry")
                 ->fetchAll(PDO::FETCH_COLUMN);

// Counts per ministry
$countsByMin = [];
foreach ($all as $r) {
    $countsByMin[$r['ministry']] = ($countsByMin[$r['ministry']] ?? 0) + 1;
}

include 'layout.php';
?>

<!-- STATS ROW -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));margin-bottom:1.5rem;">
  <div class="stat-card gold">
    <div class="stat-icon">📋</div>
    <div class="stat-value"><?= count($all) ?></div>
    <div class="stat-label">Total Registrations</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">⛪</div>
    <div class="stat-value"><?= count($ministries) ?></div>
    <div class="stat-label">Ministries</div>
  </div>
  <?php if ($all): ?>
  <div class="stat-card green">
    <div class="stat-icon">📅</div>
    <div class="stat-value" style="font-size:1rem;"><?= date('d M Y', strtotime($all[0]['submitted_at'])) ?></div>
    <div class="stat-label">Latest Registration</div>
  </div>
  <?php endif; ?>
</div>

<div class="panel">
  <!-- Ministry filter tabs -->
  <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
    <button class="min-filter-btn active" data-ministry="">All Ministries (<?= count($all) ?>)</button>
    <?php foreach ($ministries as $m): ?>
      <button class="min-filter-btn" data-ministry="<?= htmlspecialchars($m) ?>">
        <?= htmlspecialchars($m) ?> (<?= $countsByMin[$m] ?? 0 ?>)
      </button>
    <?php endforeach; ?>
  </div>

  <!-- Search + export row -->
  <div class="toolbar">
    <div class="search-wrap">
      <span class="search-icon">🔍</span>
      <input class="search-input" type="text" id="reg-search" placeholder="Search by name, email, ministry…">
    </div>
    <button class="btn btn-ghost btn-sm" onclick="exportCSV()">⬇ Export CSV</button>
  </div>

  <!-- Table -->
  <div class="tbl-wrap">
    <table class="tbl" id="reg-table">
      <thead><tr>
        <th class="col-check"><input type="checkbox" class="chk" id="select-all"></th>
        <th>Name</th>
        <th>Ministry</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Age Group</th>
        <th>Message</th>
        <th>Date</th>
        <th class="col-action">Action</th>
      </tr></thead>
      <tbody>
        <?php foreach ($all as $r): ?>
        <tr data-ministry="<?= htmlspecialchars($r['ministry']) ?>">
          <td class="col-check"><input type="checkbox" class="chk row-chk" value="<?= $r['id'] ?>"></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:30px;height:30px;border-radius:50%;background:var(--gold-dim);border:1px solid var(--border-gold);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:var(--gold);flex-shrink:0;">
                <?= strtoupper(substr($r['full_name'],0,1)) ?>
              </div>
              <span style="font-size:.85rem;font-weight:600;color:var(--text);"><?= htmlspecialchars($r['full_name']) ?></span>
            </div>
          </td>
          <td><span class="badge badge-blue"><?= htmlspecialchars($r['ministry']) ?></span></td>
          <td>
            <?php if ($r['email']): ?>
              <a href="mailto:<?= htmlspecialchars($r['email']) ?>" style="color:var(--gold);font-size:.8rem;"><?= htmlspecialchars($r['email']) ?></a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td style="font-size:.8rem;"><?= htmlspecialchars($r['phone'] ?: '—') ?></td>
          <td><?php if ($r['age']): ?><span class="badge badge-gray"><?= htmlspecialchars($r['age']) ?></span><?php else: ?>—<?php endif; ?></td>
          <td style="max-width:160px;">
            <?php if ($r['message']): ?>
              <span style="font-size:.75rem;color:var(--text-dim);" title="<?= htmlspecialchars($r['message']) ?>">
                <?= htmlspecialchars(substr($r['message'],0,50)) ?><?= strlen($r['message'])>50?'…':'' ?>
              </span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td style="font-size:.75rem;white-space:nowrap;"><?= date('d M Y', strtotime($r['submitted_at'])) ?></td>
          <td>
            <div class="tbl-actions">
              <button class="btn btn-ghost btn-sm btn-icon" onclick="viewRegistration(<?= $r['id'] ?>)" title="View Details">👁</button>
              <button class="btn btn-danger btn-sm btn-icon" onclick="doDeleteReg(<?= $r['id'] ?>,'<?= addslashes($r['full_name']) ?>')" title="Delete">🗑️</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$all): ?>
        <tr><td colspan="9">
          <div class="empty-state">
            <div class="empty-icon">📋</div>
            <div class="empty-title">No Registrations Yet</div>
            <div class="empty-text">Ministry registration form submissions will appear here.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="panel-footer">
    <span style="font-size:.78rem;color:var(--text-dim);">Showing <span id="visible-count"><?= count($all) ?></span> of <?= count($all) ?> registrations</span>
    <div style="display:flex;gap:.5rem;">
      <button class="btn btn-danger btn-sm" id="bulk-delete-btn" style="display:none;" onclick="bulkDelete()">🗑️ Delete Selected</button>
    </div>
  </div>
</div>

<!-- VIEW DETAIL MODAL -->
<div class="modal-overlay" id="reg-modal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">📋 Registration Details</div>
      <button class="modal-close" onclick="closeModal('reg-modal')">✕</button>
    </div>
    <div class="modal-body" id="reg-modal-body">Loading…</div>
  </div>
</div>

<!-- Delete form -->
<form method="POST" id="del-reg-form" style="display:none;">
  <input type="hidden" name="action" value="delete_registration">
  <input type="hidden" name="id" id="del-reg-id">
</form>

<script>
  // Search
  initTableSearch('reg-search', 'reg-table', [1,2,3,4]);

  // Select all
  document.getElementById('select-all')?.addEventListener('change', function(){
    document.querySelectorAll('.row-chk').forEach(c=>c.checked=this.checked);
    updateBulkBtn();
  });
  document.addEventListener('change', e=>{
    if(e.target.classList.contains('row-chk')) updateBulkBtn();
  });
  function updateBulkBtn(){
    const count = document.querySelectorAll('.row-chk:checked').length;
    document.getElementById('bulk-delete-btn').style.display = count > 0 ? '' : 'none';
  }
  function bulkDelete(){
    const ids = [...document.querySelectorAll('.row-chk:checked')].map(c=>c.value);
    if(!ids.length) return;
    confirmDelete(`Delete ${ids.length} selected registration(s)?`, ()=>{
      ids.forEach(id=>{
        const f=document.createElement('form');
        f.method='POST';f.style.display='none';
        f.innerHTML=`<input name="action" value="delete_registration"><input name="id" value="${id}">`;
        document.body.appendChild(f);f.submit();
      });
    });
  }

  // Ministry filter tabs
  document.querySelectorAll('.min-filter-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
      document.querySelectorAll('.min-filter-btn').forEach(b=>b.classList.remove('active'));
      this.classList.add('active');
      const ministry = this.dataset.ministry;
      let visible=0;
      document.querySelectorAll('#reg-table tbody tr').forEach(row=>{
        const show = !ministry || row.dataset.ministry===ministry;
        row.style.display = show ? '' : 'none';
        if(show) visible++;
      });
      document.getElementById('visible-count').textContent=visible;
    });
  });

  // View details
  const regData = <?= json_encode(array_column($all, null, 'id')) ?>;
  function viewRegistration(id){
    const r = regData[id];
    if(!r) return;
    document.getElementById('reg-modal-body').innerHTML = `
      <div style="display:grid;gap:1rem;">
        <div style="display:flex;align-items:center;gap:12px;padding-bottom:1rem;border-bottom:1px solid var(--border);">
          <div style="width:52px;height:52px;border-radius:50%;background:var(--gold-dim);border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:700;color:var(--gold);">
            ${r.full_name.charAt(0).toUpperCase()}
          </div>
          <div>
            <div style="font-size:1rem;font-weight:700;color:var(--white);">${r.full_name}</div>
            <span class="badge badge-blue">${r.ministry}</span>
          </div>
        </div>
        ${r.email ? `<div><span style="font-size:.7rem;font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:.1em;">Email</span><div style="margin-top:4px;"><a href="mailto:${r.email}" style="color:var(--gold);">${r.email}</a></div></div>` : ''}
        ${r.phone ? `<div><span style="font-size:.7rem;font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:.1em;">Phone</span><div style="margin-top:4px;color:var(--text-muted);">${r.phone}</div></div>` : ''}
        ${r.age   ? `<div><span style="font-size:.7rem;font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:.1em;">Age Group</span><div style="margin-top:4px;"><span class="badge badge-gray">${r.age}</span></div></div>` : ''}
        ${r.message ? `<div><span style="font-size:.7rem;font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:.1em;">Message</span><div style="margin-top:4px;color:var(--text-muted);font-size:.88rem;line-height:1.6;">${r.message}</div></div>` : ''}
        <div style="font-size:.72rem;color:var(--text-dim);padding-top:.5rem;border-top:1px solid var(--border);">
          Submitted: ${new Date(r.submitted_at).toLocaleDateString('en-GB',{day:'numeric',month:'long',year:'numeric'})}
        </div>
      </div>
    `;
    openModal('reg-modal');
  }

  function doDeleteReg(id, name){
    confirmDelete(`Remove registration for "${name}"?`, ()=>{
      document.getElementById('del-reg-id').value=id;
      document.getElementById('del-reg-form').submit();
    });
  }

  function exportCSV(){
    const rows = [['Name','Ministry','Email','Phone','Age Group','Message','Date']];
    document.querySelectorAll('#reg-table tbody tr').forEach(row=>{
      if(row.style.display==='none') return;
      const cells=[...row.cells];
      rows.push([
        cells[1]?.innerText?.trim()||'',
        cells[2]?.innerText?.trim()||'',
        cells[3]?.innerText?.trim()||'',
        cells[4]?.innerText?.trim()||'',
        cells[5]?.innerText?.trim()||'',
        cells[6]?.innerText?.trim()||'',
        cells[7]?.innerText?.trim()||'',
      ]);
    });
    const csv = rows.map(r=>r.map(c=>`"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download = 'afm-registrations.csv';
    a.click();
  }

  <?php if($successMsg||$errorMsg): ?>
  window.addEventListener('load',()=>showToast(<?=json_encode($successMsg?:$errorMsg)?>,<?=$successMsg?"'success'":"'error'"?>));
  <?php endif; ?>
</script>

<?php include 'layout_footer.php'; ?>
