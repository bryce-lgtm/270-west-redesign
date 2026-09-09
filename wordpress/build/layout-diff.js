// Same-origin layout diff: compares element boxes/styles of WordPress pages against the prototype
// (served from /proto/ by deploy-proto.sh). Paste into the browser console on any page of the site:
//   await w270diff([['/', 'index.html'], ['/services/claims/', 'service-claims.html']], 1440)
// Returns, per page, the element/height counts and the first differences (empty diffs = match).
window.w270diff = async (pairs, width = 1440) => {
  const skip = /^(elementor|e-|w-|w270-|is-in|hentry|post-|page|type-page|status-publish|site-main|wp-|home$|hello-|logged-in|admin-bar|customize-support|no-js|js$|attachment-|size-|skip-link|screen-reader)/;
  function fp(root) {
    const out = [];
    root.querySelectorAll('body *[class]').forEach(el => {
      if (el.closest('.site-header,.site-footer,.mobile-nav')) return;
      const cls = [...el.classList].filter(c => !skip.test(c));
      if (!cls.length) return;
      const r = el.getBoundingClientRect(); const cs = getComputedStyle(el);
      out.push({ k: cls.join('.'), tag: el.tagName.toLowerCase(), w: Math.round(r.width), h: Math.round(r.height), x: Math.round(r.left), d: cs.display, fs: cs.fontSize, ff: cs.fontFamily.split(',')[0].replace(/"/g, ''), lh: cs.lineHeight, mt: cs.marginTop, mb: cs.marginBottom });
    });
    return out;
  }
  async function load(src) {
    const f = document.createElement('iframe');
    f.style.cssText = 'position:fixed;left:0;top:0;width:' + width + 'px;height:900px;opacity:0;pointer-events:none;border:0';
    f.src = src; document.body.appendChild(f);
    await new Promise(r => { f.onload = r; });
    await f.contentDocument.fonts.ready; await new Promise(r => setTimeout(r, 900));
    return f;
  }
  const results = {};
  for (const [wpPath, proto] of pairs) {
    const fa = await load('/proto/' + proto), fb = await load(wpPath + (wpPath.includes('?') ? '&' : '?') + 'nocache=' + Date.now());
    const a = fp(fa.contentDocument), b = fp(fb.contentDocument);
    const ha = fa.contentDocument.documentElement.scrollHeight, hb = fb.contentDocument.documentElement.scrollHeight;
    fa.remove(); fb.remove();
    const diffs = []; let i = 0, j = 0;
    while (i < a.length && j < b.length && diffs.length < 25) {
      const p = a[i], q = b[j];
      if (p.k !== q.k) {
        let k = 1; while (k <= 4 && j + k < b.length && b[j + k].k !== p.k) k++;
        if (k <= 4 && j + k < b.length) { diffs.push({ extraInWp: b.slice(j, j + k).map(e => e.k).join(' | ') }); j += k; continue; }
        k = 1; while (k <= 4 && i + k < a.length && a[i + k].k !== q.k) k++;
        if (k <= 4 && i + k < a.length) { diffs.push({ missingInWp: a.slice(i, i + k).map(e => e.k).join(' | ') }); i += k; continue; }
        diffs.push({ at: i, proto: p.k, wp: q.k, note: 'sequence diverged' }); break;
      }
      const d = {};
      for (const key of ['w', 'h', 'x']) if (Math.abs(p[key] - q[key]) > 2) d[key] = p[key] + '→' + q[key];
      for (const key of ['d', 'fs', 'ff', 'lh', 'mt', 'mb']) if (p[key] !== q[key]) d[key] = p[key] + '→' + q[key];
      if (Object.keys(d).length) diffs.push({ k: p.k, tag: p.tag + (p.tag !== q.tag ? '→' + q.tag : ''), ...d });
      i++; j++;
    }
    results[wpPath] = { n: a.length + '/' + b.length, h: ha + '/' + hb, diffs };
  }
  return results;
};
