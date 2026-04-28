/* ============================================================
   AFM Warsaw Admin - Shared JavaScript
   ============================================================ */

const ADMIN_ICON_REPLACEMENTS = [
  { tokens: ['✓'], classes: 'fa-solid fa-circle-check' },
  { tokens: ['✕', '✖', '×'], classes: 'fa-solid fa-xmark' },
  { tokens: ['ℹ'], classes: 'fa-solid fa-circle-info' },
  { tokens: ['📎'], classes: 'fa-solid fa-paperclip' },
  { tokens: ['🔍'], classes: 'fa-solid fa-magnifying-glass' },
  { tokens: ['📤'], classes: 'fa-solid fa-cloud-arrow-up' },
  { tokens: ['🖼', '🖼️', '📷'], classes: 'fa-regular fa-image' },
  { tokens: ['✏', '✏️'], classes: 'fa-regular fa-pen-to-square' },
  { tokens: ['🗑', '🗑️'], classes: 'fa-regular fa-trash-can' },
  { tokens: ['📅'], classes: 'fa-regular fa-calendar-days' },
  { tokens: ['📢'], classes: 'fa-solid fa-bullhorn' },
  { tokens: ['🎙', '🎙️', '🎤'], classes: 'fa-solid fa-microphone-lines' },
  { tokens: ['💬'], classes: 'fa-regular fa-comments' },
  { tokens: ['▶'], classes: 'fa-regular fa-circle-play' },
  { tokens: ['⏻'], classes: 'fa-solid fa-right-from-bracket' },
  { tokens: ['⏱'], classes: 'fa-regular fa-clock' },
  { tokens: ['🌐'], classes: 'fa-solid fa-globe' },
  { tokens: ['⚙', '⚙️'], classes: 'fa-solid fa-gear' },
  { tokens: ['💾'], classes: 'fa-regular fa-floppy-disk' },
  { tokens: ['📨'], classes: 'fa-regular fa-envelope' },
  { tokens: ['⬅'], classes: 'fa-solid fa-align-left' },
  { tokens: ['↔'], classes: 'fa-solid fa-align-center' },
  { tokens: ['➡'], classes: 'fa-solid fa-align-right' }
];

function createIconNode(classes) {
  const icon = document.createElement('i');
  icon.className = `${classes} icon-inline`;
  icon.setAttribute('aria-hidden', 'true');
  return icon;
}

function replaceTokensInElement(el, replacements) {
  if (!el || el.dataset.iconsApplied === 'true') return;

  const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
  const nodes = [];
  while (walker.nextNode()) nodes.push(walker.currentNode);

  let changed = false;

  for (const node of nodes) {
    if (['SCRIPT', 'STYLE', 'NOSCRIPT', 'OPTION'].includes(node.parentElement?.tagName)) continue;
    const text = node.nodeValue;
    if (!text || !text.trim()) continue;

    const frag = document.createDocumentFragment();
    let index = 0;
    let buffer = '';
    let matched = false;

    while (index < text.length) {
      let foundToken = null;
      let foundReplacement = null;

      for (const replacement of replacements) {
        for (const token of replacement.tokens) {
          if (text.startsWith(token, index) && (!foundToken || token.length > foundToken.length)) {
            foundToken = token;
            foundReplacement = replacement;
          }
        }
      }

      if (foundToken) {
        if (buffer) {
          frag.append(document.createTextNode(buffer));
          buffer = '';
        }
        frag.append(createIconNode(foundReplacement.classes));
        index += foundToken.length;
        matched = true;
      } else {
        buffer += text[index];
        index += 1;
      }
    }

    if (!matched) continue;
    if (buffer) frag.append(document.createTextNode(buffer));
    node.parentNode.replaceChild(frag, node);
    changed = true;
  }

  if (changed) el.dataset.iconsApplied = 'true';
}

function applyAdminIcons(root = document) {
  replaceTokensInElement(root.body || root, ADMIN_ICON_REPLACEMENTS);
}

(function clock() {
  const el = document.getElementById('admin-clock');
  if (!el) return;
  const tick = () => {
    const now = new Date();
    el.textContent = now.toLocaleTimeString('en-GB', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
  };
  tick();
  setInterval(tick, 1000);
})();

function showToast(msg, type = 'success', duration = 4000) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const icons = {
    success: '<i class="fa-solid fa-circle-check icon-inline" aria-hidden="true"></i>',
    error: '<i class="fa-solid fa-circle-xmark icon-inline" aria-hidden="true"></i>',
    info: '<i class="fa-solid fa-circle-info icon-inline" aria-hidden="true"></i>'
  };

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span class="toast-icon">${icons[type] || icons.info}</span><span class="toast-msg">${msg}</span><span class="toast-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark icon-inline" aria-hidden="true"></i></span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(20px)';
    toast.style.transition = '0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;

  modal.classList.remove('open');
  document.body.style.overflow = '';
  modal.querySelectorAll('iframe').forEach(frame => {
    frame.src = frame.src;
  });
}

document.addEventListener('click', event => {
  if (event.target.classList.contains('modal-overlay')) {
    closeModal(event.target.id);
  }
});

document.addEventListener('keydown', event => {
  if (event.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(modal => closeModal(modal.id));
  }
});

let confirmCallback = null;

function confirmDelete(msg, callback) {
  confirmCallback = callback;
  const box = document.getElementById('confirm-overlay');
  const text = document.getElementById('confirm-text');

  if (box) {
    if (text) text.textContent = msg || 'This action cannot be undone.';
    box.classList.add('open');
  } else if (confirm(msg || 'Are you sure? This cannot be undone.')) {
    callback();
  }
}

function confirmYes() {
  document.getElementById('confirm-overlay')?.classList.remove('open');
  if (confirmCallback) confirmCallback();
  confirmCallback = null;
}

function confirmNo() {
  document.getElementById('confirm-overlay')?.classList.remove('open');
  confirmCallback = null;
}

function previewImage(inputEl, previewId) {
  const input = typeof inputEl === 'string' ? document.getElementById(inputEl) : inputEl;
  const preview = document.getElementById(previewId);
  if (!input || !preview) return;

  input.addEventListener('change', function () {
    const file = this.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = event => {
      preview.src = event.target.result;
      preview.classList.add('visible');
    };
    reader.readAsDataURL(file);
  });
}

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

function initDropZone(zoneId, inputId) {
  const zone = document.getElementById(zoneId);
  const input = document.getElementById(inputId);
  if (!zone || !input) return;

  zone.addEventListener('click', () => input.click());

  ['dragenter', 'dragover'].forEach(eventName => {
    zone.addEventListener(eventName, event => {
      event.preventDefault();
      zone.classList.add('drag-over');
    });
  });

  ['dragleave', 'drop'].forEach(eventName => {
    zone.addEventListener(eventName, event => {
      event.preventDefault();
      zone.classList.remove('drag-over');
    });
  });

  zone.addEventListener('drop', event => {
    const files = event.dataTransfer?.files;
    if (!files?.length) return;

    const transfer = new DataTransfer();
    Array.from(files).forEach(file => transfer.items.add(file));
    input.files = transfer.files;
    input.dispatchEvent(new Event('change'));

    const nameEl = zone.querySelector('.upload-zone-text');
    if (nameEl && files[0]) {
      nameEl.innerHTML = `<i class="fa-solid fa-paperclip icon-inline" aria-hidden="true"></i> ${files[0].name}`;
    }
  });

  input.addEventListener('change', function () {
    const nameEl = zone.querySelector('.upload-zone-text');
    if (nameEl && this.files?.[0]) {
      nameEl.innerHTML = `<i class="fa-solid fa-paperclip icon-inline" aria-hidden="true"></i> ${this.files[0].name}`;
    }
  });
}

function initTableSearch(inputId, tableId, colIndexes) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;

  input.addEventListener('input', function () {
    const query = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      const cols = colIndexes || [...Array(row.cells.length).keys()];
      const text = cols.map(index => row.cells[index]?.textContent || '').join(' ').toLowerCase();
      row.style.display = text.includes(query) ? '' : 'none';
    });
    updateEmptyState(tableId);
  });
}

function updateEmptyState(tableId) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const visible = [...table.querySelectorAll('tbody tr')].filter(row => row.style.display !== 'none').length;
  let empty = table.parentElement.querySelector('.table-empty-state');

  if (visible === 0) {
    if (!empty) {
      empty = document.createElement('div');
      empty.className = 'table-empty-state empty-state';
      empty.innerHTML = '<div class="empty-icon"><i class="fa-solid fa-magnifying-glass icon-inline" aria-hidden="true"></i></div><div class="empty-title">No Results Found</div><div class="empty-text">Try a different search term.</div>';
      table.parentElement.appendChild(empty);
    }
    empty.style.display = '';
  } else if (empty) {
    empty.style.display = 'none';
  }
}

function initTableFilter(selectId, tableId, colIndex) {
  const select = document.getElementById(selectId);
  const table = document.getElementById(tableId);
  if (!select || !table) return;

  select.addEventListener('change', function () {
    const value = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      const cell = row.cells[colIndex]?.textContent?.toLowerCase() || '';
      row.style.display = (!value || cell.includes(value)) ? '' : 'none';
    });
  });
}

function initSelectAll(selectAllId, tableId) {
  const master = document.getElementById(selectAllId);
  if (!master) return;

  master.addEventListener('change', function () {
    document.querySelectorAll(`#${tableId} tbody .row-chk`).forEach(chk => {
      chk.checked = this.checked;
    });
  });
}

function initRichText(editorId, hiddenId) {
  const editor = document.getElementById(editorId);
  const hidden = document.getElementById(hiddenId);
  if (!editor || !hidden) return;

  editor.setAttribute('contenteditable', 'true');
  editor.addEventListener('input', () => {
    hidden.value = editor.innerHTML;
  });

  document.querySelectorAll('[data-cmd]').forEach(button => {
    button.addEventListener('mousedown', event => {
      event.preventDefault();
      const cmd = button.dataset.cmd;
      const value = button.dataset.val || null;
      document.execCommand(cmd, false, value);
      editor.focus();
      hidden.value = editor.innerHTML;
    });
  });
}

async function submitForm(formEl, options = {}) {
  const { onSuccess, onError, successMsg } = options;
  const submitBtn = formEl.querySelector('[type="submit"]');
  const originalText = submitBtn?.textContent || '';

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
  }

  try {
    const res = await fetch(formEl.action || window.location.href, {
      method: 'POST',
      body: new FormData(formEl)
    });
    const json = await res.json();

    if (json.success) {
      showToast(successMsg || json.message || 'Saved successfully!', 'success');
      if (onSuccess) onSuccess(json);
      formEl.reset();
    } else {
      showToast(json.message || 'Something went wrong.', 'error');
      if (onError) onError(json);
    }
  } catch (error) {
    showToast('Network error. Please try again.', 'error');
    if (onError) onError(error);
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  }
}

document.querySelectorAll('[data-count]').forEach(el => {
  const target = parseInt(el.dataset.count, 10);
  const suffix = el.dataset.suffix || '';
  const duration = 1400;

  const observer = new IntersectionObserver(([entry]) => {
    if (!entry.isIntersecting) return;

    const start = performance.now();
    const tick = now => {
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

function copyText(text, label) {
  navigator.clipboard?.writeText(text).then(() => {
    showToast(`${label || 'Copied'} to clipboard`, 'success', 2000);
  });
}

function toggleAnnType() {
  const type = document.getElementById('ann-type')?.value;
  document.getElementById('field-day')?.classList.toggle('hidden', type !== 'weekly');
  document.getElementById('field-date')?.classList.toggle('hidden', type !== 'special');
}

document.addEventListener('DOMContentLoaded', () => {
  applyAdminIcons();

  const page = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item').forEach(link => {
    if (link.getAttribute('href') === page) link.classList.add('active');
  });
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 5000);
  });
});

document.addEventListener('submit', event => {
  const form = event.target;
  if (form.classList.contains('no-loading')) return;

  const btn = form.querySelector('[type="submit"]');
  if (btn && !btn.disabled) {
    btn._origText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Saving...';
  }
});

if (window.location.hash) {
  setTimeout(() => {
    const el = document.querySelector(window.location.hash);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, 300);
}

document.querySelectorAll('input[type="file"]').forEach(input => {
  input.addEventListener('change', function () {
    const maxMB = parseFloat(this.dataset.maxMb || '5');
    const file = this.files?.[0];
    if (file && file.size > maxMB * 1024 * 1024) {
      showToast(`File is too large. Maximum size is ${maxMB}MB.`, 'error');
      this.value = '';
    }
  });
});

document.addEventListener('keydown', event => {
  if ((event.ctrlKey || event.metaKey) && event.key === 's') {
    event.preventDefault();
    const activeForm = document.querySelector('form:hover, form:focus-within');
    if (activeForm) activeForm.querySelector('[type="submit"]')?.click();
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('mobile-menu-btn');
  const sidebar = document.querySelector('.admin-sidebar');

  if (btn && sidebar) {
    btn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
  }

  const topbar = document.querySelector('.admin-topbar');
  const content = document.querySelector('.admin-content');
  if (topbar && content) {
    content.addEventListener('scroll', () => {
      topbar.classList.toggle('scrolled', content.scrollTop > 10);
    }, { passive: true });
  }
});
