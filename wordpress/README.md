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
  Elementor**. Content editing in WordPress started on 2026-10-26, so the importer is no longer part
  of a normal deploy — see "Deploying" below.

## Deploying (staging and production)

**Elementor is the source of truth for page content.** Since 2026-10-26 every page, menu and the
Elementor kit (Site Settings) is edited directly in wp-admin. The prototype HTML and `build/out/*.json`
are stale by design and must never be re-imported onto a live site. A deploy is therefore *code only*:

    wordpress/build/deploy-godaddy.sh       # GoDaddy staging (production: override W270_GD_USER/HOST/SITE_HOST)
    wordpress/build/deploy-siteground.sh    # SiteGround staging

Both rsync/push the theme (`theme/270west`), the importer folder and the content plugin, install and
activate the required plugins, run `render-check.php` and flush caches. They print
`importer: skipped (code-only deploy)` and leave pages, menus and the kit exactly as wp-admin has them.
Pushing to GitHub never touches a site; only these scripts do.

The importer runs only on explicit opt-in:

| Flag (`GD` for GoDaddy, `SG` for SiteGround) | What runs | Touches |
| --- | --- | --- |
| `W270_GD_IMPORT=1` | `import.php --all` | **Everything** — rewrites all 25 pages, menus, kit, settings. Fresh, empty sites only. Prints a warning first. |
| `W270_GD_REFRESH_FEEDS=contact,booking` | `--refresh-feeds=…` | Only those forms' Creatio webhook feeds (the forms stay as wp-admin has them). |
| `W270_GD_REFRESH_FORMS=checker` | `--refresh-forms=…` | Only those Gravity Forms and their feeds. |
| `W270_GD_REFRESH_TEMPLATES=1` or `=w270-single-story` | `--refresh-templates[=…]` | Only the Pro header/footer/single/loop templates (and archive pages when unnamed). |
| `W270_GD_REFRESH_RESOURCES=slug-a,slug-b` | `--refresh-resources=…` | Only those resource posts' copy and fields. |

Refresh flags can be combined; none of them touches pages, menus or the kit. If a page ever needs
re-seeding from the repo, run `import.php --pages --only=<slug>` by hand over SSH, knowing it discards
that page's Elementor edits (WordPress keeps a revision of the previous Elementor data).

`build/build.sh` is the **Local** one-shot build and still runs `import.php --all` against
`270-west.local` only; it never reaches a remote host.

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
