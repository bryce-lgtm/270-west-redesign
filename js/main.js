// Compass-rose registration mark: the Phase II brand mark, traced from the
// identity deck. Sits at the crosshair intersection.
const COMPASS_MARKER = '<img src="img/brand/270west-symbol-light.svg" alt="" width="52" height="52">';

// ── Landscape photo injector (data-photo) ──
function initPhotos() {
  document.querySelectorAll('[data-photo]').forEach(el => {
    const d = el.dataset;
    const alt = (d.alt || '').replace(/"/g, '&quot;');
    el.style.aspectRatio = d.aspect || '16/9';
    // LCP candidate (hero): high priority, never lazy. Everything else: lazy.
    const loadAttrs = el.hasAttribute('data-priority')
      ? 'fetchpriority="high" decoding="async"'
      : 'loading="lazy" decoding="async"';
    const base = (window.W270 && window.W270.assets) ? window.W270.assets + '/img/' : 'img/';
    const img = `<img src="${base}${d.photo}.jpg" alt="${alt}" ${loadAttrs} style="object-position:${d.pos || 'center'}">`;
    const isScene = el.hasAttribute('data-scene') || d.headline || el.hasAttribute('data-tagline') || el.hasAttribute('data-marker') || d.tl || d.tr;
    if (!isScene) { el.classList.add('photo'); el.innerHTML = img; return; }
    el.classList.add('photo-scene', 'photo-scene-hover');
    if (d.crossY) el.style.setProperty('--cross-y', d.crossY);
    let html = img + '<div class="photo-scene-overlay"></div>';
    // Crosshair registration lines (campaign motif), present whenever a marker is shown
    if (el.hasAttribute('data-marker')) {
      el.classList.add('photo-scene--cross');
      html += '<div class="photo-scene-cross"><span class="v"></span><span class="h"></span></div>';
    }
    if (d.tl) html += `<div class="photo-scene-corner tl">${d.tl}</div>`;
    if (d.tr) html += `<div class="photo-scene-corner tr">${d.tr}</div>`;
    if (d.headline || el.hasAttribute('data-marker')) {
      html += '<div class="photo-scene-body">';
      if (el.hasAttribute('data-marker')) html += `<div class="photo-scene-marker">${COMPASS_MARKER}</div>`;
      if (d.headline) html += `<div class="photo-scene-h">${d.headline}</div>`;
      html += '</div>';
    }
    if (el.hasAttribute('data-tagline')) html += '<div class="photo-scene-tagline">In service of your story.</div>';
    el.innerHTML = html;
  });
}

// ── Decorative SVG injection (data-decor) ──
// <div data-decor="topoLinesSVG" data-decor-args='["#A0B5C1",0.12]'></div> calls topoLinesSVG('#A0B5C1', 0.12).
// Replaces the per-page inline scripts; the WordPress generator emits these attributes.
function initDecor() {
  document.querySelectorAll('[data-decor]').forEach(el => {
    const fn = window[el.dataset.decor];
    if (typeof fn !== 'function') return;
    let args = [];
    try { args = JSON.parse(el.dataset.decorArgs || '[]'); } catch (e) { args = []; }
    el.innerHTML = fn.apply(null, args);
  });
}

// ── Header shadow on scroll ──
function initHeaderScroll() {
  const header = document.querySelector('.site-header');
  if (!header) return;
  const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
}

// ── Scroll reveal (flash-free, reduced-motion safe) ──
function initScrollReveal() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;
  const selectors = [
    '.section-pad > *', '.testimonials-grid > *', '.tcard-grid > *', '.steps-grid > *',
    '.services-2col > *', '.resources-featured > *', '.values-grid > *',
    '.team-grid > *', '.svc-outcomes > *', '.faq-group', '.hiw-step-row',
    '.service-row', '.cta-grid > *', '.page-hero > *'
  ];
  const vh = window.innerHeight, candidates = [];
  selectors.forEach(sel => document.querySelectorAll(sel).forEach(el => candidates.push(el)));
  const toReveal = [];
  candidates.forEach(el => { if (el.getBoundingClientRect().top > vh * 0.85) { el.setAttribute('data-reveal', ''); toReveal.push(el); } });
  const counters = new Map();
  toReveal.forEach(el => { const p = el.parentElement; const i = counters.get(p) || 0; counters.set(p, i + 1); el.style.transitionDelay = Math.min(i, 4) * 70 + 'ms'; });
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
  }, { rootMargin: '0px 0px -10% 0px', threshold: 0.05 });
  toReveal.forEach(el => io.observe(el));
}

// ── Compass Rose SVG ──
function compassRoseSVG(size, stroke, sw, bearing) {
  stroke = stroke || 'currentColor';
  sw = sw || 1;
  bearing = bearing !== undefined ? bearing : 270;
  const lines = [];
  for (let i = 0; i < 72; i++) {
    const a = (i * 5 * Math.PI) / 180;
    const r1 = i % 6 === 0 ? 86 : i % 2 === 0 ? 92 : 95;
    const op = i % 6 === 0 ? 1 : 0.4;
    const x1 = (Math.cos(a) * r1).toFixed(3), y1 = (Math.sin(a) * r1).toFixed(3);
    const x2 = (Math.cos(a) * 98).toFixed(3), y2 = (Math.sin(a) * 98).toFixed(3);
    lines.push(`<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" opacity="${op}"/>`);
  }
  const rot = bearing - 90;
  return `<svg viewBox="-100 -100 200 200" width="${size}" height="${size}" fill="none" stroke="${stroke}" stroke-width="${sw}" style="display:block">
  <circle cx="0" cy="0" r="98"/>
  <circle cx="0" cy="0" r="78" opacity="0.4"/>
  <circle cx="0" cy="0" r="58" opacity="0.25"/>
  ${lines.join('')}
  <path d="M 0,-78 L 6,0 L 0,-78 L -6,0 Z" fill="${stroke}" opacity="0.9" stroke="none"/>
  <path d="M 0,78 L 6,0 L 0,78 L -6,0 Z" fill="${stroke}" opacity="0.3" stroke="none"/>
  <path d="M -78,0 L 0,6 L -78,0 L 0,-6 Z" fill="${stroke}" opacity="0.6" stroke="none"/>
  <path d="M 78,0 L 0,6 L 78,0 L 0,-6 Z" fill="${stroke}" opacity="0.6" stroke="none"/>
  <circle cx="0" cy="0" r="3" fill="${stroke}" stroke="none"/>
  <g transform="rotate(${rot})">
    <line x1="0" y1="0" x2="92" y2="0" stroke-width="${sw * 1.5}"/>
    <polygon points="92,0 84,-4 84,4" fill="${stroke}" stroke="none"/>
  </g>
  <text x="0" y="-62" text-anchor="middle" font-size="9" fill="${stroke}" stroke="none" font-family="'Inter Tight',system-ui,sans-serif" letter-spacing="1">N</text>
  <text x="62" y="3" text-anchor="middle" font-size="9" fill="${stroke}" stroke="none" font-family="'Inter Tight',system-ui,sans-serif">E</text>
  <text x="0" y="68" text-anchor="middle" font-size="9" fill="${stroke}" stroke="none" font-family="'Inter Tight',system-ui,sans-serif">S</text>
  <text x="-62" y="3" text-anchor="middle" font-size="9" fill="${stroke}" stroke="none" font-family="'Inter Tight',system-ui,sans-serif">W</text>
</svg>`;
}

// ── Topo Lines SVG ──
function topoLinesSVG(stroke, opacity) {
  stroke = stroke || '#A0B5C1';
  opacity = opacity !== undefined ? opacity : 0.12;
  const paths = [200,240,280,320,360,400,440,480,520].map(y =>
    `<path d="M -50 ${y} Q 200 ${y-60}, 400 ${y+20} T 850 ${y-20}"/>`
  ).join('');
  return `<svg viewBox="0 0 800 600" preserveAspectRatio="xMidYMid slice" class="topo-bg" aria-hidden="true">
  <g fill="none" stroke="${stroke}" stroke-width="1" opacity="${opacity}">${paths}</g>
</svg>`;
}

// ── Map Grid SVG ──
function mapGridSVG(stroke, opacity, size) {
  stroke = stroke || '#A0B5C1';
  opacity = opacity !== undefined ? opacity : 0.06;
  size = size || 32;
  return `<svg class="map-grid" aria-hidden="true">
  <defs><pattern id="mg${size}" width="${size}" height="${size}" patternUnits="userSpaceOnUse">
    <path d="M ${size} 0 L 0 0 0 ${size}" fill="none" stroke="${stroke}" stroke-width="1" opacity="${opacity}"/>
  </pattern></defs>
  <rect width="100%" height="100%" fill="url(#mg${size})"/>
</svg>`;
}

// ── Image Placeholder ──
function imgPlaceholder(label, spec, aspect, note, dark) {
  aspect = aspect || '16/10';
  const cls = dark ? 'img-placeholder dark' : 'img-placeholder';
  const noteHtml = note ? `<div class="img-placeholder-note">${note}</div>` : '';
  const specHtml = spec ? `<div class="img-placeholder-spec">${spec}</div>` : '';
  return `<div class="${cls}" style="aspect-ratio:${aspect}">
  <div style="position:relative;max-width:85%">
    <div class="img-placeholder-tag">⌖ Image placeholder</div>
    <div class="img-placeholder-label">${label}</div>
    ${specHtml}${noteHtml}
  </div>
</div>`;
}

// ── Mobile Menu ──
function initMobileMenu() {
  const btn = document.querySelector('.mobile-menu-btn');
  const nav = document.querySelector('.mobile-nav');
  if (btn && nav) {
    btn.addEventListener('click', () => nav.classList.toggle('open'));
  }
}

// ── VAC Status Checker ──
const QUIZ_QUESTIONS = [
  { id:'served', q:'Have you served in the Canadian Armed Forces?', options:['Regular Force','Reserve Force','RCMP','No'] },
  { id:'rating', q:'Do you currently have a VAC disability rating?', options:['No rating','0–30%','40–70%','80%+'] },
  { id:'health', q:'Are you experiencing service-related health issues?', options:['Yes','Not sure','No'] },
  { id:'filed', q:'Have you previously filed a claim with VAC?', options:['Yes, approved','Yes, denied','No'] },
  { id:'goal', q:'What are you looking to do?', options:['File a new claim','Increase an existing rating','Appeal a denial','Not sure yet'] }
];

function initQuiz(containerId) {
  const container = document.getElementById(containerId || 'quiz-widget');
  if (!container) return;
  let step = 0, answers = {}, contact = { name:'', email:'', phone:'' };
  const N = QUIZ_QUESTIONS.length;
  const total = N + 2;

  function progress() {
    return step === 0 ? 0 : Math.min(1, step / total);
  }

  function render() {
    const pct = Math.round(progress() * 100);
    const stepLabel = step === 0 ? '00' : String(Math.min(step, total)).padStart(2,'0');
    let body = '';

    if (step === 0) {
      body = `
        <div class="quiz-step-label">Step 01 · Intake</div>
        <div class="quiz-h3">See where you stand with&nbsp;VAC.</div>
        <div class="quiz-lead">Five quick questions about your service and your history with VAC. About two minutes, confidential, no obligation. We'll come back with a clear next step.</div>
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
          <button class="quiz-start-btn" onclick="quizGo(1)">Start now →</button>
          <div class="quiz-badges"><span>● 2 MIN</span><span>● CONFIDENTIAL</span><span>● NO COST</span></div>
        </div>`;
    } else if (step >= 1 && step <= N) {
      const Q = QUIZ_QUESTIONS[step - 1];
      const twoCol = Q.options.length > 3 ? ' two-col' : '';
      const opts = Q.options.map(opt => {
        const sel = answers[Q.id] === opt ? ' selected' : '';
        return `<button class="quiz-option${sel}" onclick="quizAnswer('${Q.id}','${opt.replace(/'/g,"\\'")}',${step})">${opt}<span class="quiz-option-arrow">→</span></button>`;
      }).join('');
      body = `
        <div class="quiz-step-label">Question ${String(step).padStart(2,'0')} of ${String(N).padStart(2,'0')}</div>
        <div class="quiz-h3">${Q.q}</div>
        <div class="quiz-options${twoCol}">${opts}</div>
        <div class="quiz-nav">
          ${step > 1 ? `<button class="quiz-back" onclick="quizGo(${step-1})">← BACK</button>` : '<span></span>'}
          <span>${pct}% COMPLETE</span>
        </div>`;
    } else if (step === N + 1) {
      body = `
        <div class="quiz-step-label">Almost there</div>
        <div class="quiz-h3">Where should we send your next step?</div>
        <div class="quiz-lead">An advisor will review your answers and reach out within one business day.</div>
        <div class="quiz-fields">
          <div class="quiz-field"><label class="quiz-field-label" for="qf-name">Full name</label><input type="text" id="qf-name" value="${contact.name}" oninput="quizContact('name',this.value)" placeholder="Your name"/></div>
          <div class="quiz-field"><label class="quiz-field-label" for="qf-email">Email</label><input type="email" id="qf-email" value="${contact.email}" oninput="quizContact('email',this.value)" placeholder="you@example.ca"/></div>
          <div class="quiz-field"><label class="quiz-field-label" for="qf-phone">Phone</label><input type="tel" id="qf-phone" value="${contact.phone}" oninput="quizContact('phone',this.value)" placeholder="(902) 555-0142"/></div>
        </div>
        <button class="quiz-start-btn" onclick="quizGo(${N+2})">Get my results →</button>
        <div class="quiz-privacy">🔒 Confidential. We never share your info.</div>`;
    } else {
      const name = contact.name || 'you';
      body = `
        <div class="quiz-result-label">● Result ready</div>
        <div class="quiz-result-h">Thanks. We'll be in touch shortly.</div>
        <div class="quiz-result-p">Based on your answers, we can see where you sit with VAC and what we may be able to help with. A 270 West advisor will reach out to ${name} within one business day for a friendly, no-obligation conversation.</div>
        <div class="quiz-result-btns">
          <a href="${window.W270 ? '/book-a-consult/' : 'consult.html'}" class="btn-accent" style="font-size:14px;padding:16px 28px">Book a free conversation →</a>
          <button class="quiz-restart" onclick="quizReset()">Restart</button>
        </div>`;
    }

    container.innerHTML = `
      <div class="quiz-widget">
        <div class="quiz-header"><span>VAC Status Check</span><span>${stepLabel} / ${String(total).padStart(2,'0')}</span></div>
        <div class="quiz-progress-track"><div class="quiz-progress-fill" style="width:${pct}%"></div></div>
        ${body}
      </div>`;
  }

  window.quizGo = function(s) { step = s; render(); };
  window.quizAnswer = function(id, val, s) {
    answers[id] = val;
    render();
    setTimeout(() => { step = s + 1; render(); }, 220);
  };
  window.quizContact = function(key, val) { contact[key] = val; };
  window.quizReset = function() { step = 0; answers = {}; contact = {name:'',email:'',phone:''}; render(); };

  render();
}

// ── Compass Rose brand mark SVG ──
function brandMarkSVG(size) {
  // Sept 2026 brand symbol, tinted with the parent's text colour (see .brand-symbol).
  return `<span class="brand-symbol" aria-hidden="true" style="width:${size}px"></span>`;
}

// ── Dot Pattern SVG ──
function dotPatternSVG(stroke, opacity, size) {
  stroke = stroke || '#A0B5C1';
  opacity = opacity !== undefined ? opacity : 0.18;
  size = size || 24;
  const half = size / 2;
  return `<svg style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none" aria-hidden="true">
  <defs><pattern id="dotp${size}" width="${size}" height="${size}" patternUnits="userSpaceOnUse">
    <circle cx="${half}" cy="${half}" r="1" fill="${stroke}" opacity="${opacity}"/>
  </pattern></defs>
  <rect width="100%" height="100%" fill="url(#dotp${size})"/>
</svg>`;
}

// ── Book Consult Widget ──
const CONSULT_SLOTS = [
  { date: 'Thu May 22', day: 'THU', num: '22', times: ['9:30 AM', '11:00 AM', '2:00 PM'] },
  { date: 'Fri May 23', day: 'FRI', num: '23', times: ['10:00 AM', '1:30 PM'] },
  { date: 'Mon May 26', day: 'MON', num: '26', times: ['9:00 AM', '11:30 AM', '3:00 PM', '4:30 PM'] },
  { date: 'Tue May 27', day: 'TUE', num: '27', times: ['10:30 AM', '2:00 PM'] }
];

function initConsultWidget(containerId) {
  const container = document.getElementById(containerId || 'consult-widget');
  if (!container || container.dataset.inited) return;
  container.dataset.inited = '1';
  let dayIdx = 0, time = null, stage = 'pick';
  let info = { name: '', email: '', phone: '', topic: '' };

  function render() {
    const slot = CONSULT_SLOTS[dayIdx];

    if (stage === 'pick') {
      const dayBtns = CONSULT_SLOTS.map((s, i) => {
        const sel = i === dayIdx ? ' consult-day-sel' : '';
        return `<button class="consult-day-btn${sel}" onclick="consultDay(${i})"><span style="font-family:var(--font-mono);font-size:9px;letter-spacing:0.12em;opacity:0.7;display:block">${s.day}</span><span class="consult-day-num">${s.num}</span></button>`;
      }).join('');
      const timeBtns = slot.times.map(t => {
        const sel = t === time ? ' consult-time-sel' : '';
        const chk = t === time ? '<span style="font-size:11px;font-family:var(--font-mono)">✓</span>' : '';
        return `<button class="consult-time-btn${sel}" onclick="consultTime('${t}')">${t}${chk}</button>`;
      }).join('');
      const active = time ? '' : ' disabled';
      const btnLabel = time ? `Continue · ${slot.date}, ${time}` : 'Pick a time to continue';

      container.innerHTML = `<div class="consult-widget">
        <div class="consult-header"><span>Benefits Analysis Consult</span><span>30 min · free</span></div>
        <h3 class="consult-h3">Book a free 30-minute consult.</h3>
        <p class="consult-lead">One-on-one with a 270 West advisor. We'll review your situation, walk through which VAC programs may apply, and answer your questions. No obligation.</p>
        <div class="consult-advisor-row">
          <div class="consult-avatars">
            <span class="consult-avatar" style="background:var(--olive)">JM</span>
            <span class="consult-avatar" style="background:#8a95a6">SK</span>
            <span class="consult-avatar" style="background:#5a6472">AT</span>
          </div>
          <span class="consult-advisor-label">Matched with the next available advisor</span>
        </div>
        <div class="consult-sublabel">1. Choose a day</div>
        <div class="consult-days">${dayBtns}</div>
        <div class="consult-sublabel">2. Pick a time (AT)</div>
        <div class="consult-times">${timeBtns}</div>
        <button class="consult-continue${time ? '' : ' disabled'}" onclick="consultContinue()"${active}>${btnLabel} <span>→</span></button>
      </div>`;

    } else if (stage === 'details') {
      container.innerHTML = `<div class="consult-widget">
        <div class="consult-header"><span>Benefits Analysis Consult</span><span>30 min · free</span></div>
        <button class="consult-back" onclick="consultBack()">← BACK</button>
        <h3 class="consult-h3">Hold your spot.</h3>
        <p class="consult-lead"><strong>${slot.date} · ${time} AT</strong>. We'll send a calendar invite and call link.</p>
        <div class="consult-fields">
          <div class="consult-field consult-field-first"><label class="consult-field-label" for="ci-name">Full name</label><input type="text" id="ci-name" value="${info.name}" oninput="consultInfo('name',this.value)" placeholder="Your name"/></div>
          <div class="consult-field"><label class="consult-field-label" for="ci-email">Email</label><input type="email" id="ci-email" value="${info.email}" oninput="consultInfo('email',this.value)" placeholder="you@example.ca"/></div>
          <div class="consult-field"><label class="consult-field-label" for="ci-phone">Phone (optional)</label><input type="tel" id="ci-phone" value="${info.phone}" oninput="consultInfo('phone',this.value)" placeholder="(902) 555-0142"/></div>
          <div class="consult-field"><label class="consult-field-label" for="ci-topic">What would you like to focus on?</label><input type="text" id="ci-topic" value="${info.topic}" oninput="consultInfo('topic',this.value)" placeholder="First claim, appeal, reassessment..."/></div>
        </div>
        <button class="consult-confirm" onclick="consultConfirm()">Confirm booking →</button>
        <div class="consult-privacy">🔒 Confidential. No obligation. Free of charge.</div>
      </div>`;

    } else {
      container.innerHTML = `<div class="consult-widget">
        <div class="consult-header"><span>Benefits Analysis Consult</span><span>30 min · free</span></div>
        <div class="consult-confirmed-label">● Booking confirmed</div>
        <h3 class="consult-h3">Thanks, ${info.name || 'veteran'}. You're on the calendar.</h3>
        <p class="consult-lead">We've sent a calendar invite to <strong>${info.email || 'your email'}</strong> with a call link for <strong>${slot.date} at ${time} AT</strong>. Talk soon.</p>
        <button class="consult-restart" onclick="consultRestart()">Book another time</button>
      </div>`;
    }
  }

  window.consultDay = function(i) { dayIdx = i; time = null; render(); };
  window.consultTime = function(t) { time = t; render(); };
  window.consultContinue = function() { if (time) { stage = 'details'; render(); } };
  window.consultBack = function() { stage = 'pick'; render(); };
  window.consultInfo = function(k, v) { info[k] = v; };
  window.consultConfirm = function() { stage = 'done'; render(); };
  window.consultRestart = function() { stage = 'pick'; time = null; info = { name: '', email: '', phone: '', topic: '' }; render(); };

  render();
}

// ── Story cards (R7): show four, then reveal the next four per click ──
function initStoryGrid() {
  document.querySelectorAll('.story-grid').forEach(grid => {
    const cards = [...grid.querySelectorAll('.story-card')];
    const STEP = 4;
    if (cards.length <= STEP) return;
    cards.slice(STEP).forEach(c => { c.hidden = true; });
    const wrap = document.createElement('div');
    wrap.className = 'story-more';
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn-ink';
    btn.textContent = 'Load more stories';
    btn.addEventListener('click', () => {
      const hidden = cards.filter(c => c.hidden);
      hidden.slice(0, STEP).forEach(c => { c.hidden = false; });
      if (hidden.length <= STEP) wrap.remove();
      if (hidden[0]) hidden[0].focus();
    });
    wrap.appendChild(btn);
    grid.after(wrap);
  });
}

// ── Video lightbox: poster cards open the film in a dialog (data-video-src) ──
function initVideoLightbox() {
  const cards = [...document.querySelectorAll('.video-card')];
  if (!cards.length) return;
  let dialog, frame, opener;
  function build() {
    dialog = document.createElement('dialog');
    dialog.className = 'video-lightbox';
    dialog.innerHTML = '<div class="video-lightbox-inner"><button type="button" class="video-lightbox-close">Close ✕</button><div class="video-lightbox-frame"></div></div>';
    frame = dialog.querySelector('.video-lightbox-frame');
    dialog.querySelector('.video-lightbox-close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', e => { if (e.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => { frame.innerHTML = ''; if (opener) opener.focus(); });
    document.body.appendChild(dialog);
  }
  cards.forEach(card => card.addEventListener('click', () => {
    if (!dialog) build();
    opener = card;
    const src = card.dataset.videoSrc;
    const title = card.dataset.videoTitle || 'Video';
    dialog.setAttribute('aria-label', title);
    if (src) {
      frame.innerHTML = /\.(mp4|webm)$/i.test(src)
        ? `<video src="${src}" controls autoplay playsinline></video>`
        : `<iframe src="${src}" title="${title}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen></iframe>`;
    } else {
      frame.innerHTML = `<div class="video-lightbox-pending"><strong>${title}</strong><span>Coming soon. This film is in production.</span></div>`;
    }
    dialog.showModal();
  }));
}

// ── Lead attribution: keep campaign data for the session and stamp it on every form ──
function initLeadTracking() {
  const KEY = 'w270_lead_src';
  const params = new URLSearchParams(location.search);
  let data = {};
  try { data = JSON.parse(sessionStorage.getItem(KEY) || '{}'); } catch (e) { data = {}; }
  const keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'msclkid'];
  let fresh = false;
  // A new campaign touch replaces the whole set, so one lead never mixes two campaigns.
  if (keys.some(k => params.get(k))) {
    data = {};
    keys.forEach(k => { const v = params.get(k); if (v) data[k] = v; });
    data.landing_page = location.pathname + location.search;
    fresh = true;
  }
  if (!data.landing_page) { data.landing_page = location.pathname + location.search; fresh = true; }
  if (!data.referrer && document.referrer && !document.referrer.includes(location.host)) { data.referrer = document.referrer; fresh = true; }
  if (fresh) { try { sessionStorage.setItem(KEY, JSON.stringify(data)); } catch (e) {} }
  document.querySelectorAll('[data-lead-field]').forEach(el => { el.value = data[el.dataset.leadField] || ''; });
  return data;
}

// ── Scheduler embed (Calendly today, our own booking plugin later) ──
function initScheduler(lead) {
  const host = document.getElementById('consult-scheduler');
  if (!host) return;
  const url = host.dataset.schedulerUrl;
  if (!url) return; // no link configured yet: the sample scheduler stays in place
  const sample = document.getElementById('consult-widget');
  const note = document.querySelector('.consult-sample-note');
  if (sample) sample.remove();
  if (note) note.remove();
  const u = new URL(url);
  Object.entries(lead || {}).forEach(([k, v]) => { if (v && /^utm_|^gclid$|^msclkid$/.test(k)) u.searchParams.set(k, v); });
  u.searchParams.set('hide_gdpr_banner', '1');
  if (/calendly\.com/.test(u.hostname)) {
    const div = document.createElement('div');
    div.className = 'calendly-inline-widget';
    div.dataset.url = u.toString();
    div.style.minWidth = '320px';
    div.style.height = (host.dataset.schedulerHeight || 720) + 'px';
    host.appendChild(div);
    const css = document.createElement('link');
    css.rel = 'stylesheet'; css.href = 'https://assets.calendly.com/assets/external/widget.css';
    document.head.appendChild(css);
    const js = document.createElement('script');
    js.src = 'https://assets.calendly.com/assets/external/widget.js'; js.async = true;
    document.body.appendChild(js);
  } else {
    const frame = document.createElement('iframe');
    frame.src = u.toString();
    frame.title = 'Book a consult';
    frame.loading = 'lazy';
    frame.style.cssText = 'width:100%;border:0;height:' + (host.dataset.schedulerHeight || 720) + 'px';
    host.appendChild(frame);
  }
}


// ── Landing page lead forms: confirm in place, push a dataLayer event ──
function initLandingForms() {
  document.querySelectorAll('.lp-form').forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();
      if (!form.reportValidity()) return;
      const card = form.closest('.lp-form-card');
      const done = card && card.querySelector('.lp-form-done');
      // The live site posts through Gravity Forms; this confirms the design in the prototype.
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({ event: 'lead_submit', form_id: form.closest('[id]') ? form.closest('[id]').id : 'lead', page_type: 'landing' });
      if (done) { form.hidden = true; done.hidden = false; done.setAttribute('tabindex', '-1'); done.focus(); }
    });
  });
}

// ── Resource archives: topic/category filters and load more ──
function initArchives() {
  const chips = [...document.querySelectorAll('.filter-chip, [data-filter-link]')];
  if (!chips.length) return;
  const cards = [...document.querySelectorAll('.guide-card')];
  const featured = document.querySelector('.guide-featured');
  const entries = [...document.querySelectorAll('.news-entry')];
  const months = [...document.querySelectorAll('.news-month')];
  const empty = document.querySelector('.archive-empty');
  const more = document.getElementById('guide-more');
  const count = document.getElementById('guide-count');
  const STEP = 8;
  let shown = STEP, filter = 'all';

  function render() {
    let visible = 0;
    if (featured) {
      const match = filter === 'all' || featured.dataset.topic === filter;
      featured.hidden = !match;
      if (match) visible++;
    }
    cards.forEach(card => {
      const match = filter === 'all' || card.dataset.topic === filter;
      const within = visible < shown;
      card.hidden = !(match && within);
      if (match) visible++;
    });
    entries.forEach(e => {
      const cat = (e.querySelector('.news-cat') || {}).textContent;
      const match = filter === 'all' || cat === filter;
      e.hidden = !match;
      if (match) visible++;
    });
    months.forEach(m => { m.hidden = ![...m.querySelectorAll('.news-entry')].some(e => !e.hidden); });
    if (more) more.hidden = filter !== 'all' ? true : cards.filter(c => c.dataset.topic).length <= shown;
    if (count) count.textContent = cards.filter(c => filter === 'all' || c.dataset.topic === filter).length
      + (featured && (filter === 'all' || featured.dataset.topic === filter) ? 1 : 0);
    if (empty) empty.hidden = visible > 0;
  }
  function setFilter(value) {
    filter = value; shown = STEP;
    document.querySelectorAll('.filter-chip').forEach(c => c.setAttribute('aria-pressed', String(c.dataset.filter === value)));
    render();
  }
  chips.forEach(el => el.addEventListener('click', e => {
    e.preventDefault();
    setFilter(el.dataset.filter || el.dataset.filterLink);
  }));
  if (more) more.addEventListener('click', () => {
    const first = cards.filter(c => c.hidden)[0];
    shown += STEP; render();
    if (first) { const link = first.querySelector('a'); if (link) link.focus(); }
  });
  render();
}

// ── Init all ──
document.addEventListener('DOMContentLoaded', function() {
  initMobileMenu();
  initHeaderScroll();

  // Inject compass rose where placeholder exists
  document.querySelectorAll('[data-compass]').forEach(el => {
    const size = el.dataset.size || 340;
    const stroke = el.dataset.stroke || '#A0B5C1';
    const sw = el.dataset.sw || 1;
    el.innerHTML = compassRoseSVG(size, stroke, sw, 270);
  });

  // Inject image placeholders
  document.querySelectorAll('[data-imgph]').forEach(el => {
    el.innerHTML = imgPlaceholder(
      el.dataset.label || 'Image',
      el.dataset.spec || '',
      el.dataset.aspect || '16/10',
      el.dataset.note || '',
      el.dataset.dark === 'true'
    );
  });

  initDecor();
  initPhotos();
  initArchives();
  initLandingForms();
  const w270Lead = initLeadTracking();
  initScheduler(w270Lead);
  initVideoLightbox();
  initStoryGrid();
  initQuiz();
  if (document.getElementById('consult-widget')) initConsultWidget('consult-widget');
  requestAnimationFrame(() => requestAnimationFrame(initScrollReveal));
});
