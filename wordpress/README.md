# WordPress / Elementor build

The static prototype at the repo root (`*.html`, `css/styles.css`, `js/main.js`, `img/`) is the
design source. This folder turns it into an Elementor site on the Local app site **270 West**
(`http://270-west.local`, `~/Local Sites/270-west/app/public`).

## One-shot build

    wordpress/build/build.sh

Steps: `sync-assets.sh` (copy css/js/img into the theme) → unit tests → `generate.py` (prototype
HTML → `build/out/<slug>.json` Elementor trees + `generated.css`) → `deploy-theme.sh` (rsync theme
into the site) → `deploy-proto.sh` (prototype at `/proto/` for diffing) → `activate-theme.php` →
`import.php --all` (media, pages, menus, kit, settings) → `render-check.php` → `check-coverage.py`.

WordPress scripts run through Local's PHP (there is no WP-CLI):

    PHP="/Users/Bryce/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php"
    INI="/Users/Bryce/Library/Application Support/Local/run/USpouZOL3/conf/php/php.ini"
    "$PHP" -c "$INI" wordpress/build/import.php --pages --only=home

Visual verification: open any page of the site in a browser, paste `build/layout-diff.js` into the
console, then `await w270diff([['/', 'index.html']], 1440)` (or `390` for mobile). Empty `diffs`
means every classed element has the same box, display and typography as the prototype.

## What lives where

- `theme/270west` — Hello Elementor child theme. Header/footer are theme template parts driven by
  WordPress menus (Primary, Mobile, Footer Explore, Footer Services). `functions.php` enqueues the
  prototype stylesheet, `elementor-bridge.css` and `main.js`, and loads Elementor's own base CSS
  inside a cascade layer so the prototype styles always win.
- Pages are Elementor documents: one Container per section, native Heading / Text Editor / Button /
  Image widgets, HTML widgets for SVG decoration, the VAC status checker, consult scheduler, FAQ
  accordions and the contact form. Widgets carry the prototype's class names in their CSS Classes
  field; `generated.css` holds the prototype's inline styles as classes.
- `generate.py` classification: heading → Heading widget; `p`/inline-only element → Text Editor;
  `a.btn-*` → Button; `img` → Image (Media Library attachment); `svg`/`form`/`details`/`data-photo`/
  quiz & consult shells → HTML widget; element with block children → Container; a div of unclassed
  `p`/`h2`/lists → one rich Text Editor.
- Re-running the importer updates pages in place (keyed by slug) and **overwrites edits made in
  Elementor**. Once content editing starts in WordPress, stop re-importing those pages.

## Follow-ups

- Contact form is static markup (Elementor Pro Forms or a form plugin needed to receive mail).
- Elementor Pro: build header/footer in Theme Builder; Hello Elementor hands over automatically.
- The homepage footer (compass mark, no bullet glyphs) is used sitewide; the inner prototype pages
  still carry an older footer variant.
- Landscape photos are Unsplash stand-ins; replace with licensed Canadian photography.
