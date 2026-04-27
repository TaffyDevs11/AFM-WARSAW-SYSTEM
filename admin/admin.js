/* ============================================================
   AFM Warsaw Admin — Shared JavaScript
   ============================================================ */

// ── CLOCK ──────────────────────────────────────────────────────
(function clock() {
  const el = document.getElementById('admin-clock');
  if (!el) return;
  const tick = () => {
    const now = new Date();
    el.textContent = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  };
  tick();
  setInterval(tick, 1000);
})();

// ── TOAST ──────────────────────────────────────────────────────
function showToast(msg, type = 'success', duration = 4000) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = { success: '✓', error: '✕', info: 'ℹ' };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span class="toast-icon">${icons[type] || icons.info}</span><span class="toast-msg">${msg}</span><span class="toast-close" onclick="this.parentElement.remove()">✕</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(20px)';
    toast.style.transition = '0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// ── MODAL ──────────────────────────────────────────────────────
function openModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) {
    m.classList.remove('open');
    document.body.style.overflow = '';
    // Clear iframes
    m.querySelectorAll('iframe').forEach(f => f.src = f.src);
  }
}
// Close on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    closeModal(e.target.id);
  }
});
// Close on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
});

// ── CONFIRM DELETE ─────────────────────────────────────────────
let _confirmCallback = null;
function confirmDelete(msg, callback) {
  _confirmCallback = callback;
  const box = document.getElementById('confirm-overlay');
  const txt = document.getElementById('confirm-text');
  if (box) {
    if (txt) txt.textContent = msg || 'This action cannot be undone.';
    box.classList.add('open');
  } else {
    if (confirm(msg || 'Are you sure? This cannot be undone.')) callback();
  }
}
function confirmYes() {
  document.getElementById('confirm-overlay')?.classList.remove('open');
  if (_confirmCallback) _confirmCallback();
  _confirmCallback = null;
}
function confirmNo() {
  document.getElementById('confirm-overlay')?.classList.remove('open');
  _confirmCallback = null;
}

// ── IMAGE PREVIEW ──────────────────────────────────────────────
function previewImage(inputEl, previewId) {
  const input = typeof inputEl === 'string' ? document.getElementById(inputEl) : inputEl;
  const preview = document.getElementById(previewId);
  if (!input || !preview) return;
  input.addEventListener('change', function () {
    const file = this.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      preview.classList.add('visible');
    };
    reader.readAsDataURL(file);
  });
}

// ── VIDEO PREVIEW ──────────────────────────────────────────────
function previewVideo(inputId, previewId) {
  const input = document.getElementById(inputId);
  const container = document.getElementById(previewId);
  if (!input || !container) return;
  input.addEventListener('input', function () {
    const url = this.value.trim();
    const iframe = container.querySelector('iframe');
    const placeholder = container.querySelector('.video-placeholder');
    if (url && iframe) {
      iframe.src = url;
      if (placeholder) placeholder.classList.add('hidden');
    } else if (iframe) {
      iframe.src = '';
      if (placeholder) placeholder.classList.remove('hidden');
    }
  });
}

// ── DRAG & DROP UPLOAD ─────────────────────────────────────────
function initDropZone(zoneId, inputId) {
  const zone  = document.getElementById(zoneId);
  const input = document.getElementById(inputId);
  if (!zone || !input) return;
  zone.addEventListener('click', () => input.click());
  ['dragenter','dragover'].forEach(ev => {
    zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('drag-over'); });
  });
  ['dragleave','drop'].forEach(ev => {
    zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('drag-over'); });
  });
  zone.addEventListener('drop', e => {
    const files = e.dataTransfer?.files;
    if (files?.length) {
      const dt = new DataTransfer();
      Array.from(files).forEach(f => dt.items.add(f));
      input.files = dt.files;
      input.dispatchEvent(new Event('change'));
      const nameEl = zone.querySelector('.upload-zone-text');
      if (nameEl && files[0]) nameEl.textContent = `📎 ${files[0].name}`;
    }
  });
  input.addEventListener('change', function () {
    const nameEl = zone.querySelector('.upload-zone-text');
    if (nameEl && this.files?.[0]) nameEl.textContent = `📎 ${this.files[0].name}`;
  });
}

// ── TABLE SEARCH ───────────────────────────────────────────────
function initTableSearch(inputId, tableId, colIndexes) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;
  input.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      const cols = colIndexes || [...Array(row.cells.length).keys()];
      const text = cols.map(i => row.cells[i]?.textContent || '').join(' ').toLowerCase();
      row.style.display = text.includes(q) ? '' : 'none';
    });
    updateEmptyState(tableId);
  });
}

function updateEmptyState(tableId) {
  const table = document.getElementById(tableId);
  if (!table) return;
  const visible = [...table.querySelectorAll('tbody tr')].filter(r => r.style.display !== 'none').length;
  let empty = table.parentElement.querySelector('.table-empty-state');
  if (visible === 0) {
    if (!empty) {
      empty = document.createElement('div');
      empty.className = 'table-empty-state empty-state';
      empty.innerHTML = '<div class="empty-icon">🔍</div><div class="empty-title">No Results Found</div><div class="empty-text">Try a different search term.</div>';
      table.parentElement.appendChild(empty);
    }
    empty.style.display = '';
  } else if (empty) {
    empty.style.display = 'none';
  }
}

// ── TABLE FILTER (dropdown) ────────────────────────────────────
function initTableFilter(selectId, tableId, colIndex) {
  const select = document.getElementById(selectId);
  const table  = document.getElementById(tableId);
  if (!select || !table) return;
  select.addEventListener('change', function () {
    const val = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      const cell = row.cells[colIndex]?.textContent?.toLowerCase() || '';
      row.style.display = (!val || cell.includes(val)) ? '' : 'none';
    });
  });
}

// ── SELECT ALL CHECKBOXES ──────────────────────────────────────
function initSelectAll(selectAllId, tableId) {
  const master = document.getElementById(selectAllId);
  if (!master) return;
  master.addEventListener('change', function () {
    document.querySelectorAll(`#${tableId} tbody .row-chk`).forEach(chk => {
      chk.checked = this.checked;
    });
  });
}

// ── RICH TEXT TOOLBAR ──────────────────────────────────────────
function initRichText(editorId, hiddenId) {
  const editor = document.getElementById(editorId);
  const hidden = document.getElementById(hiddenId);
  if (!editor || !hidden) return;
  editor.setAttribute('contenteditable', 'true');
  editor.addEventListener('input', () => {
    if (hidden) hidden.value = editor.innerHTML;
  });
  // Toolbar buttons
  document.querySelectorAll('[data-cmd]').forEach(btn => {
    btn.addEventListener('mousedown', e => {
      e.preventDefault();
      const cmd = btn.dataset.cmd;
      const val = btn.dataset.val || null;
      document.execCommand(cmd, false, val);
      editor.focus();
      if (hidden) hidden.value = editor.innerHTML;
    });
  });
}

// ── ASYNC FORM SUBMIT ──────────────────────────────────────────
async function submitForm(formEl, options = {}) {
  const { onSuccess, onError, successMsg } = options;
  const submitBtn = formEl.querySelector('[type="submit"]');
  const origText  = submitBtn?.textContent || '';
  if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving…'; }
  try {
    const res  = await fetch(formEl.action || window.location.href, { method: 'POST', body: new FormData(formEl) });
    const json = await res.json();
    if (json.success) {
      showToast(successMsg || json.message || 'Saved successfully!', 'success');
      if (onSuccess) onSuccess(json);
      formEl.reset();
    } else {
      showToast(json.message || 'Something went wrong.', 'error');
      if (onError) onError(json);
    }
  } catch (e) {
    showToast('Network error. Please try again.', 'error');
    if (onError) onError(e);
  } finally {
    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = origText; }
  }
}

// ── COUNTER ANIMATION ──────────────────────────────────────────
document.querySelectorAll('[data-count]').forEach(el => {
  const target   = parseInt(el.dataset.count, 10);
  const suffix   = el.dataset.suffix || '';
  const duration = 1400;
  const observer = new IntersectionObserver(([entry]) => {
    if (!entry.isIntersecting) return;
    const start = performance.now();
    const tick  = now => {
      const t = Math.min((now - start) / duration, 1);
      const ease = 1 - Math.pow(1 - t, 3);
      el.textContent = Math.floor(ease * target) + suffix;
      if (t < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
    observer.disconnect();
  });
  observer.observe(el);
});

// ── COPY TO CLIPBOARD ──────────────────────────────────────────
function copyText(text, label) {
  navigator.clipboard?.writeText(text).then(() => {
    showToast(`${label || 'Copied'} to clipboard`, 'success', 2000);
  });
}

// ── TOGGLE ANNOUNCEMENT TYPE FIELDS ───────────────────────────
function toggleAnnType() {
  const type = document.getElementById('ann-type')?.value;
  document.getElementById('field-day')?.classList.toggle('hidden', type !== 'weekly');
  document.getElementById('field-date')?.classList.toggle('hidden', type !== 'special');
}

// ── SIDEBAR ACTIVE HIGHLIGHT ───────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const page = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item').forEach(a => {
    if (a.getAttribute('href') === page) a.classList.add('active');
  });
});

// ── AUTO-HIDE ALERTS ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 5000);
  });
});

// ── FORM LOADING STATES ───────────────────────────────────────
document.addEventListener('submit', e => {
  const form = e.target;
  if (!form.classList.contains('no-loading')) {
    const btn = form.querySelector('[type="submit"]');
    if (btn && !btn.disabled) {
      btn._origText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Saving…';
    }
  }
});

// ── SMOOTH SCROLL TO ANCHOR ───────────────────────────────────
if (window.location.hash) {
  setTimeout(() => {
    const el = document.querySelector(window.location.hash);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, 300);
}

// ── FILE SIZE VALIDATOR ───────────────────────────────────────
document.querySelectorAll('input[type="file"]').forEach(input => {
  input.addEventListener('change', function () {
    const maxMB = parseFloat(this.dataset.maxMb || '5');
    const file  = this.files?.[0];
    if (file && file.size > maxMB * 1024 * 1024) {
      showToast(`File is too large. Maximum size is ${maxMB}MB.`, 'error');
      this.value = '';
    }
  });
});

// ── KEYBOARD SHORTCUT: Ctrl+S to submit active form ───────────
document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    const activeForm = document.querySelector('form:hover, form:focus-within');
    if (activeForm) {
      const btn = activeForm.querySelector('[type="submit"]');
      if (btn) btn.click();
    }
  }
});

// ── MOBILE SIDEBAR TOGGLE ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('mobile-menu-btn');
  const sidebar = document.querySelector('.admin-sidebar');
  if (btn && sidebar) {
    btn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
  }

  // Topbar scroll shadow
  const topbar = document.querySelector('.admin-topbar');
  const content = document.querySelector('.admin-content');
  if (topbar && content) {
    content.addEventListener('scroll', () => {
      topbar.classList.toggle('scrolled', content.scrollTop > 10);
    }, { passive: true });
  }
});
