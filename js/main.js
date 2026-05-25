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
  <text x="0" y="-62" text-anchor="middle" font-size="9" fill="${stroke}" stroke="none" font-family="ui-monospace,monospace" letter-spacing="1">N</text>
  <text x="62" y="3" text-anchor="middle" font-size="9" fill="${stroke}" stroke="none" font-family="ui-monospace,monospace">E</text>
  <text x="0" y="68" text-anchor="middle" font-size="9" fill="${stroke}" stroke="none" font-family="ui-monospace,monospace">S</text>
  <text x="-62" y="3" text-anchor="middle" font-size="9" fill="${stroke}" stroke="none" font-family="ui-monospace,monospace">W</text>
</svg>`;
}

// ── Topo Lines SVG ──
function topoLinesSVG(stroke, opacity) {
  stroke = stroke || '#7A8A6A';
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
  stroke = stroke || '#7A8A6A';
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

// ── Eligibility Quiz ──
const QUIZ_QUESTIONS = [
  { id:'served', q:'Have you served in the Canadian Armed Forces?', options:['Regular Force','Reserve Force','RCMP','No'] },
  { id:'rating', q:'Do you currently have a VAC disability rating?', options:['No rating','0–30%','40–70%','80%+'] },
  { id:'health', q:'Are you experiencing service-related health issues?', options:['Yes','Not sure','No'] },
  { id:'filed', q:'Have you previously filed a claim with VAC?', options:['Yes — approved','Yes — denied','No'] },
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
        <div class="quiz-step-label">Step 01 — Intake</div>
        <div class="quiz-h3">See which VAC programs may apply to&nbsp;you.</div>
        <div class="quiz-lead">Five quick questions. About two minutes. Fully confidential and no obligation — we'll get back to you with a clear, friendly next step.</div>
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
        <div class="quiz-h3">You may qualify for additional benefits.</div>
        <div class="quiz-lead">Where should we send your tailored next step? We'll reach out within one business day.</div>
        <div class="quiz-fields">
          <div class="quiz-field"><div class="quiz-field-label">Full name</div><input type="text" id="qf-name" value="${contact.name}" oninput="quizContact('name',this.value)" placeholder="Your name"/></div>
          <div class="quiz-field"><div class="quiz-field-label">Email</div><input type="email" id="qf-email" value="${contact.email}" oninput="quizContact('email',this.value)" placeholder="you@example.ca"/></div>
          <div class="quiz-field"><div class="quiz-field-label">Phone</div><input type="tel" id="qf-phone" value="${contact.phone}" oninput="quizContact('phone',this.value)" placeholder="(902) 555-0142"/></div>
        </div>
        <button class="quiz-start-btn" onclick="quizGo(${N+2})">Get my results →</button>
        <div class="quiz-privacy">🔒 Confidential. We never share your info.</div>`;
    } else {
      const name = contact.name || 'you';
      body = `
        <div class="quiz-result-label">● Result ready</div>
        <div class="quiz-result-h">Thanks — we'll be in touch shortly.</div>
        <div class="quiz-result-p">Based on your answers, there are a few VAC programs we'd like to walk through with you. A 270 West advisor will reach out to ${name} within one business day for a friendly, no-obligation conversation.</div>
        <div class="quiz-result-btns">
          <a href="contact.html" class="btn-accent" style="font-size:14px;padding:16px 28px">Book a free conversation →</a>
          <button class="quiz-restart" onclick="quizReset()">Restart</button>
        </div>`;
    }

    container.innerHTML = `
      <div class="quiz-widget">
        <div class="quiz-header"><span>Eligibility Check</span><span>${stepLabel} / ${String(total).padStart(2,'0')}</span></div>
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

// ── Maple Leaf SVG (11-point Canadian maple leaf) ──
function mapLeafSVG(size, fill) {
  fill = fill || 'currentColor';
  return `<svg viewBox="0 0 100 100" width="${size}" height="${size}" fill="${fill}" aria-hidden="true">
    <path d="M50,5 L56,28 L72,19 L64,37 L85,35 L71,49 L85,61 L65,59 L61,78 L54,82 L54,93 L46,93 L46,82 L39,78 L35,59 L15,61 L29,49 L15,35 L36,37 L28,19 L44,28 Z"/>
  </svg>`;
}

// ── Site Logo SVG (compass rose badge) ──
function siteLogoSVG(idSuffix) {
  const id = 'lta-' + (idSuffix || '0');
  return `<svg viewBox="-54 -54 108 108" width="40" height="40" fill="none" aria-hidden="true" style="flex-shrink:0;display:block">
    <circle r="52" fill="var(--bg)" stroke="var(--ink)" stroke-width="1.5"/>
    <circle r="43" fill="none" stroke="var(--ink)" stroke-width="0.5" opacity="0.35"/>
    <line x1="0" y1="-44" x2="0" y2="-50" stroke="var(--ink)" stroke-width="1.5"/>
    <line x1="0" y1="44" x2="0" y2="50" stroke="var(--ink)" stroke-width="1"/>
    <line x1="44" y1="0" x2="50" y2="0" stroke="var(--ink)" stroke-width="1"/>
    <line x1="-44" y1="0" x2="-50" y2="0" stroke="var(--ink)" stroke-width="1"/>
    <g opacity="0.4">
      <line x1="31" y1="-31" x2="34" y2="-34" stroke="var(--ink)" stroke-width="0.7"/>
      <line x1="31" y1="31" x2="34" y2="34" stroke="var(--ink)" stroke-width="0.7"/>
      <line x1="-31" y1="31" x2="-34" y2="34" stroke="var(--ink)" stroke-width="0.7"/>
      <line x1="-31" y1="-31" x2="-34" y2="-34" stroke="var(--ink)" stroke-width="0.7"/>
    </g>
    <circle r="24" fill="none" stroke="var(--ink)" stroke-width="0.6" opacity="0.25"/>
    <path d="M0,-24 L2.5,0 L0,-24 L-2.5,0Z" fill="var(--ink)" stroke="none"/>
    <path d="M0,24 L2.5,0 L0,24 L-2.5,0Z" fill="var(--ink)" stroke="none" opacity="0.2"/>
    <path d="M24,0 L0,2.5 L24,0 L0,-2.5Z" fill="var(--ink)" stroke="none" opacity="0.45"/>
    <path d="M-24,0 L0,2.5 L-24,0 L0,-2.5Z" fill="var(--accent)" stroke="none"/>
    <line x1="0" y1="0" x2="-36" y2="0" stroke="var(--accent)" stroke-width="1.2" opacity="0.7"/>
    <polygon points="-36,0 -30,-3 -30,3" fill="var(--accent)" stroke="none"/>
    <circle r="2" fill="var(--ink)" stroke="none"/>
    <defs>
      <path id="${id}" d="M 0,-38 A 38,38 0 1,1 -0.001,-38"/>
    </defs>
    <text font-size="6" font-family="ui-monospace,monospace" fill="var(--ink)" stroke="none" letter-spacing="1.8" opacity="0.8">
      <textPath href="#${id}" startOffset="5%">270 WEST CONSULTING · CANADA ·</textPath>
    </text>
  </svg>`;
}

// ── Dot Pattern SVG ──
function dotPatternSVG(stroke, opacity, size) {
  stroke = stroke || '#7A8A6A';
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
  if (!container) return;
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
        <p class="consult-lead">One-on-one with a 270 West advisor. We'll review your situation, walk through which VAC programs may apply, and answer your questions — no obligation.</p>
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
        <p class="consult-lead"><strong>${slot.date} · ${time} AT</strong> — we'll send a calendar invite and call link.</p>
        <div class="consult-fields">
          <div class="consult-field consult-field-first"><div class="consult-field-label">Full name</div><input type="text" id="ci-name" value="${info.name}" oninput="consultInfo('name',this.value)" placeholder="Your name"/></div>
          <div class="consult-field"><div class="consult-field-label">Email</div><input type="email" id="ci-email" value="${info.email}" oninput="consultInfo('email',this.value)" placeholder="you@example.ca"/></div>
          <div class="consult-field"><div class="consult-field-label">Phone (optional)</div><input type="tel" id="ci-phone" value="${info.phone}" oninput="consultInfo('phone',this.value)" placeholder="(902) 555-0142"/></div>
          <div class="consult-field"><div class="consult-field-label">What would you like to focus on?</div><input type="text" id="ci-topic" value="${info.topic}" oninput="consultInfo('topic',this.value)" placeholder="First claim, appeal, reassessment..."/></div>
        </div>
        <button class="consult-confirm" onclick="consultConfirm()">Confirm booking →</button>
        <div class="consult-privacy">🔒 Confidential. No obligation. Free of charge.</div>
      </div>`;

    } else {
      container.innerHTML = `<div class="consult-widget">
        <div class="consult-header"><span>Benefits Analysis Consult</span><span>30 min · free</span></div>
        <div class="consult-confirmed-label">● Booking confirmed</div>
        <h3 class="consult-h3">Thanks, ${info.name || 'veteran'} — you're on the calendar.</h3>
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

// ── Init all ──
document.addEventListener('DOMContentLoaded', function() {
  initMobileMenu();

  // Inject site logos
  document.querySelectorAll('[data-logo-svg]').forEach((el, i) => {
    el.innerHTML = siteLogoSVG('x' + i);
  });

  // Inject compass rose where placeholder exists
  document.querySelectorAll('[data-compass]').forEach(el => {
    const size = el.dataset.size || 340;
    const stroke = el.dataset.stroke || '#7A8A6A';
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

  initQuiz();
});
