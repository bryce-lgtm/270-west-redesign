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

## Resources (custom post types)

`wordpress/plugins/270west-content` registers Guides, Checklists and Explainers (`/resources/guides/…`,
`/resources/checklists/…`, `/resources/explainers/…`), the Topic taxonomy and the ACF field groups
(`acf-json/`, loaded automatically; edits in the ACF UI write back to those files). ACF Pro is installed by
hand in wp-admin on each site; without it the post types still work and field-driven blocks are hidden.
**After installing ACF Pro, run `import.php --resources` once more** so the seeded posts get their fields
(read time, featured flag, callout, checklist items, related resources, landing hero copy).

The theme renders them: `single-{guide,checklist,explainer}.php`, `template-resources.php` (assigned to the
Resources page) and `template-parts/resource/*`. `import.php --resources` seeds the topics and the 15
prototype resources (`seed-resources.json`; the featured guide's body comes from `article.html` via
`seed_article.py`), assigns the template and retires the old article page (its URL redirects).
Re-running `--resources` overwrites the seeded posts by slug.

## Follow-ups

- Contact form is static markup (Elementor Pro Forms or a form plugin needed to receive mail).
- Elementor Pro: build header/footer in Theme Builder; Hello Elementor hands over automatically.
- The homepage footer (compass mark, no bullet glyphs) is used sitewide; the inner prototype pages
  still carry an older footer variant.
- Landscape photos are Unsplash stand-ins; replace with licensed Canadian photography.
