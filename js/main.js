/* ============================================================
   AFM Warsaw Assembly - Main JavaScript
   ============================================================ */

const SITE_ICON_REPLACEMENTS = [
  { tokens: ['✦'], classes: 'fa-solid fa-diamond' },
  { tokens: ['✝', '✝️'], classes: 'fa-solid fa-cross' },
  { tokens: ['📅'], classes: 'fa-regular fa-calendar-days' },
  { tokens: ['📢'], classes: 'fa-solid fa-bullhorn' },
  { tokens: ['🎙', '🎙️', '🎤'], classes: 'fa-solid fa-microphone-lines' },
  { tokens: ['📷'], classes: 'fa-brands fa-instagram' },
  { tokens: ['▶'], classes: 'fa-regular fa-circle-play' },
  { tokens: ['💬'], classes: 'fa-brands fa-whatsapp' },
  { tokens: ['🌐'], classes: 'fa-solid fa-globe' },
  { tokens: ['📍'], classes: 'fa-solid fa-location-dot' },
  { tokens: ['📧'], classes: 'fa-regular fa-envelope' },
  { tokens: ['📺'], classes: 'fa-solid fa-tv' },
  { tokens: ['✕', '✖', '×'], classes: 'fa-solid fa-xmark' }
];

function createSiteIcon(classes) {
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
        frag.append(createSiteIcon(foundReplacement.classes));
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

function applySiteIcons(root = document) {
  replaceTokensInElement(root.body || root, SITE_ICON_REPLACEMENTS);

  root.querySelectorAll('.social-link').forEach(link => {
    const value = link.textContent.trim().toLowerCase();
    if (value === 'f') {
      link.innerHTML = '<i class="fa-brands fa-facebook-f icon-inline" aria-hidden="true"></i>';
      link.dataset.iconsApplied = 'true';
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  applySiteIcons();

  const loader = document.getElementById('page-loader');
  if (loader) {
    const hide = () => loader.classList.add('hidden');
    window.addEventListener('load', () => setTimeout(hide, 600));
    setTimeout(hide, 3500);
  }

  const header = document.getElementById('site-header');
  if (header) {
    window.addEventListener('scroll', () => {
      header.classList.toggle('scrolled', window.scrollY > 60);
    }, { passive: true });
  }

  const hamburger = document.querySelector('.hamburger');
  const mobileNav = document.querySelector('.mobile-nav');
  if (hamburger && mobileNav) {
    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('open');
      mobileNav.classList.toggle('open');
    });
  }

  const path = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-link').forEach(link => {
    if ((link.getAttribute('href') || '') === path) link.classList.add('active');
  });

  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => entry.target.classList.add('visible'), index * 70);
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(el => io.observe(el));
  } else {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('visible'));
  }

  document.querySelectorAll('.contact-form').forEach(form => {
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const btn = form.querySelector('[type="submit"]');
      const msg = form.querySelector('.form-msg');
      const data = new FormData(form);

      if (!validateContact(data, msg)) return;

      setLoading(btn, true, 'Sending...');
      try {
        const res = await fetch('php/contact.php', { method: 'POST', body: data });
        const json = await res.json();
        showMsg(msg, json.success ? 'success' : 'error', json.message);
        if (json.success) form.reset();
      } catch {
        showMsg(msg, 'error', 'Network error. Please try again.');
      } finally {
        setLoading(btn, false, 'Send Message');
      }
    });
  });

  document.querySelectorAll('.register-form').forEach(form => {
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const btn = form.querySelector('[type="submit"]');
      const msg = form.querySelector('.form-msg');
      const data = new FormData(form);

      if (!data.get('full_name')?.trim()) {
        showMsg(msg, 'error', 'Full name is required.');
        return;
      }

      setLoading(btn, true, 'Submitting...');
      try {
        const res = await fetch('php/register.php', { method: 'POST', body: data });
        const json = await res.json();
        showMsg(msg, json.success ? 'success' : 'error', json.message);
        if (json.success) form.reset();
      } catch {
        showMsg(msg, 'error', 'Network error. Please try again.');
      } finally {
        setLoading(btn, false, 'Register Now');
      }
    });
  });

  if (document.getElementById('gallery-grid')) {
    loadGallery('All');
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(item => item.classList.remove('active'));
        btn.classList.add('active');
        loadGallery(btn.dataset.category || 'All');
      });
    });
  }

  if (document.getElementById('blog-grid')) loadBlog();
  if (document.getElementById('sermons-grid')) loadSermons();
  if (document.getElementById('weekly-announce')) loadAnnouncements('weekly', 'weekly-announce');
  if (document.getElementById('special-announce')) loadAnnouncements('special', 'special-announce');

  const lightbox = document.getElementById('gallery-lightbox');
  const lightboxImg = document.getElementById('lightbox-img');
  document.addEventListener('click', event => {
    const item = event.target.closest('.gallery-item');
    if (item && lightbox && lightboxImg) {
      const img = item.querySelector('img');
      if (img) {
        lightboxImg.src = img.src;
        lightboxImg.alt = img.alt;
        lightbox.classList.add('open');
      }
    }
    if (event.target === lightbox || event.target.closest('.modal-close')) {
      lightbox?.classList.remove('open');
    }
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') lightbox?.classList.remove('open');
  });

  if ('IntersectionObserver' in window) {
    document.querySelectorAll('.stat-number[data-count]').forEach(el => {
      const io = new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting) {
          animateCount(el);
          io.disconnect();
        }
      });
      io.observe(el);
    });
  }
});

async function loadGallery(category = 'All') {
  const grid = document.getElementById('gallery-grid');
  if (!grid) return;

  grid.innerHTML = `<div class="gallery-loading" style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--gray);">
    <div style="font-size:2rem;margin-bottom:.5rem;opacity:.4;"><i class="fa-regular fa-image icon-inline" aria-hidden="true"></i></div>Loading gallery...</div>`;

  try {
    const url = `php/api.php?action=gallery&category=${encodeURIComponent(category)}`;
    const json = await fetchJSON(url);

    if (!json.success || !json.data?.length) {
      grid.innerHTML = buildGalleryPlaceholders();
      applySiteIcons(grid);
      return;
    }

    grid.innerHTML = json.data.map(img => `
      <div class="gallery-item">
        <img src="${escHtml(img.url)}" alt="${escHtml(img.title || '')}" loading="lazy">
        <div class="gallery-item-overlay">
          <span class="gallery-item-title">${escHtml(img.title || '')}</span>
        </div>
      </div>
    `).join('');
  } catch {
    grid.innerHTML = buildGalleryPlaceholders();
    applySiteIcons(grid);
  }
}

function buildGalleryPlaceholders() {
  const pairs = [
    ['#1a2456', '#c9a227'],
    ['#c9a227', '#1a2456'],
    ['#cc1b1b', '#1a2456'],
    ['#1a2456', '#cc1b1b'],
    ['#212d6b', '#c9a227'],
    ['#c9a227', '#cc1b1b']
  ];

  return pairs.map(([a, b]) => `
    <div class="gallery-item" style="background:linear-gradient(135deg,${a},${b});display:flex;align-items:center;justify-content:center;">
      <span style="color:rgba(255,255,255,.15);font-size:3rem;"><i class="fa-solid fa-cross icon-inline" aria-hidden="true"></i></span>
    </div>
  `).join('');
}

async function loadBlog() {
  const grid = document.getElementById('blog-grid');
  if (!grid) return;

  try {
    const json = await fetchJSON('php/api.php?action=blog&limit=12');
    if (!json.success || !json.data?.length) {
      grid.innerHTML = buildSampleBlog();
      return;
    }

    grid.innerHTML = json.data.map(post => `
      <div class="blog-card reveal">
        <div class="blog-img">
          ${post.featured_image_url
            ? `<img src="${escHtml(post.featured_image_url)}" alt="${escHtml(post.title)}" loading="lazy">`
            : `<div style="width:100%;height:100%;background:linear-gradient(135deg,#1a2456,#212d6b);display:flex;align-items:center;justify-content:center;"><span style="font-size:4rem;color:rgba(201,162,39,.2);"><i class="fa-solid fa-cross icon-inline" aria-hidden="true"></i></span></div>`
          }
        </div>
        <div class="blog-body">
          <div class="blog-date">${formatDate(post.published_at)}</div>
          <h3 class="blog-title">${escHtml(post.title)}</h3>
          ${post.topic ? `<p class="blog-excerpt">${escHtml(post.topic)}</p>` : ''}
          <div class="blog-author">
            ${post.author_photo_url
              ? `<img class="blog-author-img" src="${escHtml(post.author_photo_url)}" alt="${escHtml(post.author_name || '')}">`
              : `<div class="blog-author-img" style="background:var(--navy);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:var(--gold);">${(post.author_name || 'A').charAt(0).toUpperCase()}</div>`
            }
            <span class="blog-author-name">${escHtml(post.author_name || 'AFM Warsaw')}</span>
          </div>
          <a href="blog-single.html?id=${post.id}" class="btn btn-outline-gold btn-sm mt-2">Read Article -></a>
        </div>
      </div>
    `).join('');

    document.querySelectorAll('.blog-card.reveal').forEach(el => {
      setTimeout(() => el.classList.add('visible'), 100);
    });
    applySiteIcons(grid);
  } catch {
    grid.innerHTML = buildSampleBlog();
    applySiteIcons(grid);
  }
}

function buildSampleBlog() {
  return [
    { title: 'Walking in Faith: A Journey Through the Psalms', date: 'April 14, 2026', author: 'Pastor Grace Moyo' },
    { title: 'The Power of Prayer in Community', date: 'April 7, 2026', author: 'Deacon Samuel Osei' },
    { title: "Understanding God's Purpose for Your Life", date: 'March 31, 2026', author: 'Pastor Grace Moyo' }
  ].map(item => `
    <div class="blog-card reveal visible">
      <div class="blog-img" style="background:linear-gradient(135deg,#1a2456,#c9a227);display:flex;align-items:center;justify-content:center;height:220px;">
        <span style="color:rgba(255,255,255,.15);font-size:4rem;"><i class="fa-solid fa-cross icon-inline" aria-hidden="true"></i></span>
      </div>
      <div class="blog-body">
        <div class="blog-date">${item.date}</div>
        <h3 class="blog-title">${item.title}</h3>
        <p class="blog-excerpt">Discover the transformative power of God's word and how it applies to your daily life.</p>
        <div class="blog-author">
          <div class="blog-author-img" style="background:var(--navy);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:var(--gold);">${item.author.charAt(0)}</div>
          <span class="blog-author-name">${item.author}</span>
        </div>
      </div>
    </div>
  `).join('');
}

async function loadSermons() {
  const grid = document.getElementById('sermons-grid');
  if (!grid) return;

  try {
    const json = await fetchJSON('php/api.php?action=sermons&limit=6');
    if (!json.success || !json.data?.length) {
      grid.innerHTML = buildSampleSermons();
      applySiteIcons(grid);
      return;
    }

    grid.innerHTML = json.data.map(sermon => `
      <div class="sermon-card reveal">
        <div class="sermon-video" ${buildSermonMediaAttrs(sermon)}>
          <div class="sermon-play" style="${!sermon.video_url ? 'opacity:.4;cursor:default;' : ''}"><i class="fa-regular fa-circle-play icon-inline" aria-hidden="true"></i></div>
        </div>
        <div class="sermon-body">
          <div class="sermon-date">${formatDate(sermon.sermon_date)}</div>
          <h3 class="sermon-title">${escHtml(sermon.title)}</h3>
          ${sermon.preacher ? `<p class="sermon-preacher">Preacher: ${escHtml(sermon.preacher)}</p>` : ''}
        </div>
      </div>
    `).join('');

    setTimeout(() => {
      document.querySelectorAll('.sermon-card.reveal').forEach(el => el.classList.add('visible'));
    }, 100);
  } catch {
    grid.innerHTML = buildSampleSermons();
    applySiteIcons(grid);
  }
}

function buildSampleSermons() {
  return ['The Kingdom of God is Within You', 'Pressing Toward the Mark', 'Faith That Moves Mountains']
    .map(title => `
      <div class="sermon-card reveal visible">
        <div class="sermon-video"><div class="sermon-play" style="opacity:.4;"><i class="fa-regular fa-circle-play icon-inline" aria-hidden="true"></i></div></div>
        <div class="sermon-body">
          <div class="sermon-date">April 2026</div>
          <h3 class="sermon-title">${title}</h3>
          <p class="sermon-preacher">Preacher: Pastor Grace Moyo</p>
        </div>
      </div>
    `).join('');
}

function buildSermonMediaAttrs(sermon) {
  const styles = [];
  if (sermon.thumbnail_image_url) {
    styles.push(`background-image:url('${escHtml(sermon.thumbnail_image_url)}')`);
    styles.push('background-size:cover');
    styles.push('background-position:center');
  }
  if (sermon.video_url) styles.push('cursor:pointer');

  const attrs = [];
  if (sermon.video_url) {
    attrs.push(`onclick="openVideoModal('${escHtml(sermon.video_url)}','${escAttr(sermon.title)}')"`);
  }
  if (styles.length) {
    attrs.push(`style="${styles.join(';')}"`);
  }
  return attrs.join(' ');
}

async function loadAnnouncements(type, containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  try {
    const json = await fetchJSON(`php/api.php?action=announcements&type=${type}`);
    if (!json.success || !json.data?.length) {
      container.innerHTML = buildSampleAnnouncements(type);
      applySiteIcons(container);
      return;
    }

    container.innerHTML = json.data.map(item => `
      <div class="announce-card ${type === 'special' ? 'special' : ''}">
        ${item.image_url
          ? `<img class="announce-img" src="${escHtml(item.image_url)}" alt="${escHtml(item.title)}" loading="lazy" onerror="this.parentElement.querySelector('.announce-fallback').style.display='flex';this.style.display='none';">`
          : ''
        }
        <div class="announce-fallback" style="width:80px;height:80px;background:${type === 'special' ? 'linear-gradient(135deg,#cc1b1b,#1a2456)' : 'linear-gradient(135deg,#1a2456,#c9a227)'};border-radius:6px;display:${item.image_url ? 'none' : 'flex'};align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0;">
          <i class="${type === 'special' ? 'fa-solid fa-bullhorn' : 'fa-regular fa-calendar-days'} icon-inline" aria-hidden="true"></i>
        </div>
        <div>
          <div class="announce-title">${escHtml(item.title)}</div>
          <div class="announce-date">${type === 'weekly' ? escHtml(item.day_of_week || '') : formatDate(item.event_date)}</div>
        </div>
      </div>
    `).join('');
  } catch {
    container.innerHTML = buildSampleAnnouncements(type);
    applySiteIcons(container);
  }
}

function buildSampleAnnouncements(type) {
  if (type === 'weekly') {
    return [
      { title: 'Sunday Service', day: '10:00 AM' },
      { title: 'Bible Study', day: 'Wednesday 6:30 PM' },
      { title: 'Prayer Night', day: 'Friday 7:00 PM' }
    ].map(item => `
      <div class="announce-card">
        <div style="width:80px;height:80px;background:linear-gradient(135deg,#1a2456,#c9a227);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0;"><i class="fa-regular fa-calendar-days icon-inline" aria-hidden="true"></i></div>
        <div><div class="announce-title">${item.title}</div><div class="announce-date">${item.day}</div></div>
      </div>
    `).join('');
  }

  return ['Easter Sunday Celebration', 'Annual Convention 2026', 'Youth Camp Registration']
    .map(title => `
      <div class="announce-card special">
        <div style="width:80px;height:80px;background:linear-gradient(135deg,#cc1b1b,#1a2456);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0;"><i class="fa-solid fa-bullhorn icon-inline" aria-hidden="true"></i></div>
        <div><div class="announce-title">${title}</div><div class="announce-date" style="color:var(--red);">Coming Soon</div></div>
      </div>
    `).join('');
}

function openVideoModal(url, title) {
  if (!url) return;

  let modal = document.getElementById('video-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'video-modal';
    modal.className = 'modal-overlay';
    modal.innerHTML = `
      <div class="modal-box" style="max-width:900px;background:var(--navy-deep);border:1px solid rgba(201,162,39,.3);border-radius:14px;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-bottom:1px solid rgba(201,162,39,.2);">
          <span style="font-family:'Cinzel',serif;color:var(--gold);font-weight:700;font-size:.95rem;" id="vm-title"><i class="fa-regular fa-circle-play icon-inline" aria-hidden="true"></i> Sermon</span>
          <button onclick="closeVideoModal()" style="color:rgba(255,255,255,.5);background:none;border:none;font-size:1.3rem;cursor:pointer;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-xmark icon-inline" aria-hidden="true"></i></button>
        </div>
        <div style="aspect-ratio:16/9;background:#000;">
          <iframe id="vm-frame" src="" width="100%" height="100%" frameborder="0" allowfullscreen style="display:block;"></iframe>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    modal.addEventListener('click', event => {
      if (event.target === modal) closeVideoModal();
    });
  }

  document.getElementById('vm-title').innerHTML = `<i class="fa-regular fa-circle-play icon-inline" aria-hidden="true"></i> ${escHtml(title || 'Sermon')}`;
  document.getElementById('vm-frame').src = url;
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeVideoModal() {
  const modal = document.getElementById('video-modal');
  if (!modal) return;

  modal.classList.remove('open');
  document.getElementById('vm-frame').src = '';
  document.body.style.overflow = '';
}

async function fetchJSON(url) {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

function showMsg(el, type, text) {
  if (!el) return;
  el.textContent = text;
  el.className = `form-msg ${type}`;
  setTimeout(() => {
    el.className = 'form-msg';
    el.textContent = '';
  }, 7000);
}

function setLoading(btn, isLoading, label) {
  if (!btn) return;
  btn.disabled = isLoading;
  btn.textContent = label;
}

function validateContact(data, msgEl) {
  const name = data.get('name')?.trim();
  const email = data.get('email')?.trim();
  const msg = data.get('message')?.trim();

  if (!name) {
    showMsg(msgEl, 'error', 'Name is required.');
    return false;
  }
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showMsg(msgEl, 'error', 'Valid email is required.');
    return false;
  }
  if (!msg) {
    showMsg(msgEl, 'error', 'Message is required.');
    return false;
  }
  return true;
}

function animateCount(el) {
  const target = parseInt(el.dataset.count, 10);
  const suffix = el.dataset.suffix || '';
  const duration = 1600;
  const start = performance.now();

  const tick = now => {
    const t = Math.min((now - start) / duration, 1);
    const ease = 1 - Math.pow(1 - t, 3);
    el.textContent = Math.floor(ease * target) + suffix;
    if (t < 1) requestAnimationFrame(tick);
  };

  requestAnimationFrame(tick);
}

function escHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function escAttr(str) {
  if (!str) return '';
  return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
}

(function initFloatingWidgets() {
  if (!document.querySelector('.whatsapp-float')) {
    const wa = document.createElement('a');
    wa.className = 'whatsapp-float';
    wa.href = 'https://wa.me/48000000000';
    wa.target = '_blank';
    wa.rel = 'noopener noreferrer';
    wa.setAttribute('aria-label', 'Chat on WhatsApp');
    wa.innerHTML = `
      <span class="whatsapp-tooltip">Chat with us</span>
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
      </svg>`;
    document.body.appendChild(wa);
  }

  if (!document.querySelector('.back-to-top')) {
    const btn = document.createElement('a');
    btn.className = 'back-to-top';
    btn.href = '#';
    btn.setAttribute('aria-label', 'Back to top');
    btn.innerHTML = '<i class="fa-solid fa-chevron-up icon-inline" aria-hidden="true"></i>';
    btn.addEventListener('click', event => {
      event.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    document.body.appendChild(btn);

    window.addEventListener('scroll', () => {
      btn.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
  }
})();

(function initCookieNotice() {
  if (localStorage.getItem('afm_cookie_ok')) return;

  const notice = document.createElement('div');
  notice.className = 'cookie-notice';
  notice.innerHTML = `
    <p class="cookie-text">
      We use cookies to improve your experience on our site. By continuing to browse,
      you agree to our <a href="contact.html">Privacy Policy</a>.
    </p>
    <div class="cookie-actions">
      <button class="btn btn-primary btn-sm" id="cookie-accept">Accept</button>
      <button class="btn btn-secondary btn-sm" id="cookie-dismiss">Dismiss</button>
    </div>`;
  document.body.appendChild(notice);
  applySiteIcons(notice);

  setTimeout(() => notice.classList.add('visible'), 1500);

  const dismiss = () => {
    notice.classList.remove('visible');
    setTimeout(() => notice.remove(), 400);
  };

  document.getElementById('cookie-accept')?.addEventListener('click', () => {
    localStorage.setItem('afm_cookie_ok', '1');
    dismiss();
  });
  document.getElementById('cookie-dismiss')?.addEventListener('click', dismiss);
})();
