# 270 West redesign → Elementor WordPress build — design

**Date:** 2026-09-08
**Status:** approved by user (chat), pending spec review

## Goal

Rebuild the static prototype in this repo (17 HTML pages sharing `css/styles.css`, `js/main.js`, `img/`) as a WordPress site on the Local app site **270 West** (`http://270-west.local`, path `~/Local Sites/270-west/app/public`), using **Elementor** with **native widgets per section**, so an editor can change copy, images and buttons in Elementor while the result matches the prototype visually.

Target environment (verified 2026-09-08):

| Item | Value |
|---|---|
| WordPress | 7.1, fresh install, permalinks `/%postname%/`, no pages |
| Elementor | 4.2.4 (Free). Pro undecided; build must work on Free and migrate cleanly to Pro. |
| Theme | Hello Elementor 3.5.1 (active) |
| PHP for scripting | `~/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php` with `-c ~/Library/Application Support/Local/run/USpouZOL3/conf/php/php.ini`; WP bootstraps via `wp-load.php` (WP-CLI is not installed) |
| Elementor kit | post ID 6, empty |

## Architecture

Everything lives in the repo under `wordpress/` and is deployed to the Local site by a build script. Three units:

```
wordpress/
  theme/270west/          Hello Elementor child theme (deployed to wp-content/themes/270west)
  build/generate.py       prototype HTML → Elementor JSON per page (writes wordpress/build/out/*.json)
  build/import.php        creates/updates pages, media, menus, kit, options from out/*.json
  build/check-coverage.py compares visible text of rendered WP pages vs prototype pages
  build/build.sh          deploy theme → generate → import → clear Elementor CSS cache
```

### Unit 1 — child theme `270west`

- `style.css` header with `Template: hello-elementor`.
- `functions.php`:
  - enqueue Google Fonts (Crimson Text, League Spartan, Inter Tight — same URL as prototype), `assets/css/styles.css` (prototype stylesheet, unchanged), `assets/css/elementor-bridge.css` (after Elementor frontend CSS), `assets/js/main.js` (adapted prototype script, in footer).
  - register menus: `primary`, `mobile`, `footer-explore`, `footer-services`.
  - filter `hello_elementor_page_title` → false (Elementor pages render their own H1).
  - keep Hello's description meta tag (uses post excerpt).
- `template-parts/header.php` and `template-parts/footer.php` override Hello's parts with the prototype `<header class="site-header">` / `<nav class="mobile-nav">` / `<footer class="site-footer">` markup. Menus are rendered by a small custom walker so the Services item gets the `.nav-dropdown` / `.nav-dropdown-menu` markup and the mobile nav gets `.mobile-subnav` children. Logo, compass SVG, tagline band and legal links are theme markup; the "Get in touch" email and footer copy are theme markup too (edit in the theme).
- `assets/img/` = copy of prototype `img/` for header/footer/decorative use. Content photos are additionally imported into the Media Library so Image widgets are real attachments.
- Elementor Pro path: Hello's `header.php`/`footer.php` already call `elementor_theme_do_location('header'|'footer')` first, so a Pro Theme Builder template overrides the theme parts without touching pages.

### Unit 2 — generator (`generate.py`)

Input: the 17 prototype pages. Output: one JSON file per page containing `{ slug, parent, title, excerpt, template, elements: [...] }` where `elements` is Elementor's element tree (classic Container + classic widgets; Editor V4 atomic elements are not used).

Mapping rules (declarative per section type, implemented as Python builders):

- `<section class="X">` → Container, `content_width: full`, CSS class `X` (prototype section classes carry all padding/background). `<div id="...">` decoration hooks and `.map-grid` become HTML widgets.
- Layout wrappers (`.hero-grid`, `.journey-steps-grid`, `.lead-magnets-grid`, `.footer-grid-5`…) → nested Containers carrying the same class; prototype CSS supplies the grid/flex layout.
- Card links (`a.lm-card`, `a.svc-preview-card`) → Container with `link` and the card classes.
- `h1`–`h4` → Heading widget (`header_size`, `title` may contain `<br>`/`<em>`/`<span>` inline HTML), CSS class = element classes.
- `<p>` and rich text blocks → Text Editor widget (`editor`), CSS class = element classes.
- `a.btn-*`, `a.nav-cta`, `.lm-pill` links → Button widget; class names prefixed for the bridge (`btn-accent` → widget class `w-btn-accent`).
- `<img>` content photos → Image widget with attachment id (imported by `import.php`; generator records the source filename, importer resolves IDs).
- SVG icons, quote marks, the fee-strip check icon, stat bars, quiz shell (`initQuiz` target), consult widget shell, contact form → HTML widget with the prototype markup.
- FAQ `<details>` accordions → HTML widget per group (keeps prototype behaviour; convertible to Elementor Accordion later).
- Internal `*.html` links → WordPress permalinks via the slug map below. `img/...` paths → theme asset URLs or media URLs.
- Element IDs: 7-char random hex, unique per page.
- Page settings (`_elementor_page_settings`): `hide_title: yes`.

### Unit 3 — importer (`import.php`)

Runs under Local's PHP with `wp-load.php`. Idempotent (keyed by slug):

1. Ensure theme `270west` active.
2. Import `img/*.jpg` into the Media Library once (dedupe by filename in `_270w_source` meta).
3. For each page JSON: create or update the page (title, slug, parent, excerpt, status publish), set `_elementor_edit_mode = builder`, `_elementor_template_type = wp-page`, `_elementor_version = 4.2.4`, `_elementor_data` (JSON, image IDs substituted), `_elementor_page_settings`.
4. Menus: build the four menus from a fixed item list and assign to theme locations.
5. Reading settings: static front page = home; `show_on_front = page`.
6. Kit (post 6) page settings: `system_colors` primary `#1A3A3F`, secondary `#4E757B`, text `#1A3A3F`, accent `#A32222`; `custom_colors` for `#A1B6C2`, `#ADA799`, `#DFE1DE`, `#F2F1EE`; `system_typography` primary/secondary = League Spartan, text/accent = Inter Tight, plus custom "Editorial serif" = Crimson Text italic. `container_width` 1400px, default container padding 0.
7. Clear Elementor CSS cache (`Plugin::$instance->files_manager->clear_cache()`).

### Bridge stylesheet (`elementor-bridge.css`)

Adapts Elementor's DOM to prototype selectors:

- Inside `.e-con[class]` sections: `.elementor-widget` margin-bottom 0; widget wrappers `width:auto` where they sit in flex rows.
- Headings/text: `.elementor-widget .elementor-heading-title, .elementor-widget-text-editor .elementor-widget-container { font: inherit; color: inherit; letter-spacing: inherit; text-transform: inherit; line-height: inherit; margin: 0 }` so classes on the widget wrapper style the inner element.
- Buttons: `.elementor-widget.w-btn-accent .elementor-button` (and `w-btn-ink`, `w-btn-outline`, `w-lm-pill*`) restate the prototype button rules; wrapper is `display:inline-block`.
- Containers: `.e-con` display/padding/gap reset to `initial` only where a prototype class supplies layout; load order ensures the prototype stylesheet wins.
- Any remaining drift is fixed here, never in `styles.css`, so the prototype stylesheet stays byte-identical to the repo.

## Pages, slugs, hierarchy

| Prototype | WP slug (path) | Parent | Notes |
|---|---|---|---|
| index.html | `home` (front page) | — | marketing H1 kept |
| services.html | `/services/` | — | |
| service-claims.html | `/services/claims/` | services | |
| service-appeals.html | `/services/appeals/` | services | |
| service-reassessment.html | `/services/reassessment/` | services | |
| service-support.html | `/services/support/` | services | |
| how-it-works.html | `/how-it-works/` | — | |
| about.html | `/about/` | — | |
| resources.html | `/resources/` | — | |
| article.html | `/resources/vac-benefits-programs-guide/` | resources | page, not post |
| contact.html | `/contact/` | — | static form markup |
| faq.html | `/faq/` | — | |
| eligibility.html | `/eligibility/` | — | quiz via main.js |
| consult.html | `/book-a-consult/` | — | scheduler via main.js |
| privacy.html | `/privacy/` | — | |
| terms.html | `/terms/` | — | |
| accessibility.html | `/accessibility/` | — | |

Page title = prototype `<title>` with the " — 270 West Consulting" suffix stripped (home keeps the full title). Excerpt = prototype meta description.

## JavaScript

`main.js` is copied with these adaptations: (1) the per-page inline `DOMContentLoaded` snippets that inject `mapGridSVG`/`topoLinesSVG` into `#svc-grid`, `#cta-grid`, `#footer-topo` become a generic initializer that fills every `.map-grid[data-grid]` and `#footer-topo`; (2) image paths use a `window.W270.assets` base injected by the theme; (3) everything else (mobile menu, dropdown, header scroll, scroll reveal, quiz, consult widget) unchanged. Scroll-reveal selectors are extended with `.e-con > .elementor-widget` equivalents only where the prototype revealed those children.

## Error handling

- Generator fails loudly if a prototype section has no builder (unknown section class) or if any visible text node is not captured by a widget — no silent drops.
- Importer wraps each page in a try/catch, reports per-page success/failure, exits non-zero on any failure.
- Coverage check fails on any page whose normalized visible text differs from the prototype's (ignoring header/footer and whitespace), and prints the diff.

## Verification

1. `build.sh` runs end-to-end without errors; `import.php` prints 17 pages OK.
2. Every page URL returns 200 and its Elementor document renders through `Plugin::$instance->frontend->get_builder_content_for_display()` with no PHP notices.
3. `check-coverage.py` passes for all 17 pages.
4. Visual pass: each WP page screenshotted in the in-app browser next to the static prototype (served from the repo with `python3 -m http.server`); differences fixed in the bridge CSS until they match at desktop and mobile widths.
5. Editor sanity: each page's `_elementor_data` round-trips through Elementor's document API (`get_elements_data()` returns the same tree).

## Out of scope

Blog/post archive; working form backend (Elementor Pro Forms or a form plugin is the follow-up for Contact); French translation; licensed photography; Elementor Pro Theme Builder templates (theme parts are the Free-compatible equivalent).
