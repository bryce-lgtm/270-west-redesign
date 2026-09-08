// Paste into the browser console on a WordPress page (or run via javascript_tool).
// Loads the prototype page for the same path in a same-origin iframe and diffs element boxes.
// Usage: window.W270_PROTO = 'index.html'; then run this file's contents.
(async () => {
  const proto = window.W270_PROTO || 'index.html';
  const f = document.createElement('iframe');
  f.style.cssText = 'position:fixed;left:0;top:0;width:1440px;height:900px;opacity:0;pointer-events:none;border:0';
  f.src = '/proto/' + proto;
  document.body.appendChild(f);
  await new Promise(r => { f.onload = r; });
  await f.contentDocument.fonts.ready;
  await document.fonts.ready;
  await new Promise(r => setTimeout(r, 1200));
  const skip = /^(elementor|e-|w-|w270-|is-in|hentry|post-|page|type-page|status-publish|site-main)/;
  function fp(root) {
    const out = [];
    root.querySelectorAll('[class]').forEach(el => {
      if (el.closest('.site-header,.site-footer,.mobile-nav,[data-reveal]:not(.is-in)') ) return;
      const cls = [...el.classList].filter(c => !skip.test(c));
      if (!cls.length) return;
      const r = el.getBoundingClientRect(); const cs = getComputedStyle(el);
      out.push({ k: cls.join('.'), tag: el.tagName.toLowerCase(), w: Math.round(r.width), h: Math.round(r.height), x: Math.round(r.left), d: cs.display, fs: cs.fontSize, ff: cs.fontFamily.split(',')[0].replace(/"/g, ''), lh: cs.lineHeight, mt: cs.marginTop, mb: cs.marginBottom, pos: cs.position });
    });
    return out;
  }
  const a = fp(f.contentDocument), b = fp(document);
  f.remove();
  const diffs = []; let i = 0, j = 0;
  while (i < a.length && j < b.length && diffs.length < 40) {
    const p = a[i], q = b[j];
    if (p.k !== q.k) { diffs.push({ at: i, proto: p.k, wp: q.k, note: 'sequence diverged' }); break; }
    const d = {};
    for (const key of ['w', 'h', 'x']) if (Math.abs(p[key] - q[key]) > 2) d[key] = p[key] + '→' + q[key];
    for (const key of ['d', 'fs', 'ff', 'lh', 'mt', 'mb', 'pos']) if (p[key] !== q[key]) d[key] = p[key] + '→' + q[key];
    if (Object.keys(d).length) diffs.push({ k: p.k, tag: p.tag + (p.tag !== q.tag ? '→' + q.tag : ''), ...d });
    i++; j++;
  }
  return { protoCount: a.length, wpCount: b.length, diffs };
})()
