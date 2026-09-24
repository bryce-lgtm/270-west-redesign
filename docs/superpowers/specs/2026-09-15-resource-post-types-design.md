# Resource post types (ACF Pro) — design

**Date:** 2026-09-15
**Status:** approved by user (chat), pending spec review
**Builds on:** `2026-09-08-elementor-wordpress-build-design.md` (Elementor site, child theme `270west`, importer, Local + SiteGround deploys)

## Goal

Replace the static Resources page and article page with structured content: three custom post types
(Guides, Checklists, Explainers) with ACF Pro fields, rendered by the child theme using the prototype's
`resources.html` and `article.html` designs. Content is edited in the WordPress post screens, not Elementor.

Decisions made with the user (2026-09-15): separate post type per resource type; shared base fields plus
type-specific extras; theme templates for rendering (Elementor Free has no dynamic content); ACF Pro is
installed by the user in wp-admin on both sites; prototype resources are seeded as **published** posts.

## Architecture

```
wordpress/plugins/270west-content/          content model (survives a theme switch)
  270west-content.php                        registers post types + taxonomy, points ACF at acf-json/
  acf-json/group_resource_details.json       ACF Local JSON — base fields (all three types)
  acf-json/group_guide_extras.json           callout, show_toc
  acf-json/group_checklist_extras.json       items repeater, download_pdf
wordpress/theme/270west/
  single-guide.php, single-checklist.php, single-explainer.php
  template-resources.php                     "Resources landing" page template
  template-parts/resource/hero.php, toc.php, body.php, callout.php, checklist-items.php, rail.php, related.php, card.php, library.php
  inc/resources.php                          helpers: type label, read time, TOC builder, related query, breadcrumb
wordpress/build/
  import.php                                 + seeding of topics/resources, article page migration, template assignment
  seed-resources.json                        the 15 prototype resources (title, type, topic, read time, summary, featured)
  deploy-theme.sh / deploy-siteground.sh     + copy the plugin to wp-content/plugins
  render-check.php                           + resource singles and the Resources page
```

### Unit 1 — plugin `270west-content`

- `register_post_type` for `guide`, `checklist`, `explainer`:
  labels Guides/Checklists/Explainers; `public`, `show_in_rest` (block editor); `has_archive: false`;
  `rewrite: ['slug' => 'resources/guides' | 'resources/checklists' | 'resources/explainers', 'with_front' => false]`;
  supports title, editor, excerpt, thumbnail, revisions; menu icons `dashicons-book`, `dashicons-yes-alt`, `dashicons-lightbulb`; `menu_position` 21.
- `register_taxonomy( 'resource_topic', [ 'guide', 'checklist', 'explainer' ] )`: label Topics, hierarchical, `show_in_rest`, rewrite `resources/topic`.
- `add_filter( 'acf/settings/load_json' )` and `acf/settings/save_json` → plugin `acf-json/` directory, so field groups load on any site the plugin is on and UI edits write back to versioned files.
- Flushes rewrite rules on activation and deactivation.
- Works without ACF: post types and taxonomy register unconditionally; `get_field()` calls in the theme are wrapped by helpers that return null when ACF is absent.

### Unit 2 — ACF field groups (Local JSON)

| Group | Location | Fields (name: type, notes) |
|---|---|---|
| Resource details | post_type ∈ {guide, checklist, explainer} | `summary`: textarea (rows 3, required) · `read_time`: number (minutes, min 1) · `featured`: true/false ("Show in Start here on the Resources page") · `related_resources`: relationship (post types guide/checklist/explainer, max 4, return ID) · `seo_h1`: text (instructions: keyword-led heading; when set, the post title renders as the marketing H2) |
| Guide extras | post_type = guide | `show_toc`: true/false, default 1 · `callout`: group { `label`: text default "Helpful tip", `text`: textarea } |
| Checklist extras | post_type = checklist | `items`: repeater (button "Add item", layout row) { `item`: text required, `note`: text } · `download_pdf`: file (return ID, mime pdf) |

Field group keys are fixed strings (`group_270w_resource_details`, `group_270w_guide_extras`,
`group_270w_checklist_extras`) so they are stable across sites.

### Unit 3 — theme templates

Markup follows `article.html` / `resources.html`; existing prototype CSS classes are reused, so no new
stylesheet. Inline styles from the prototype's article header/library sections become a small
`assets/css/resources.css` (the only new CSS), enqueued on resource singles and the Resources page.

- **Singles** (`single-guide.php` etc. → `template-parts/resource/*`):
  - `hero.php`: breadcrumb "Resources / {Topic}" (topic links to the Resources page anchored at that topic), heading (if `seo_h1` set: H1 = seo_h1 and H2 = title, else H1 = title), meta row "● {read_time} min read · Updated {post modified, F Y} · By 270 West Consulting".
  - `article-layout` section: `toc.php` (guides with `show_toc`; H2s in the content get ids `h-0…` via `w270_add_heading_ids()` on `the_content`, TOC lists them; hidden if fewer than 2 H2s), `body.php` (`article-intro` = summary, featured image as `data-photo`-free `<img>` in the same wrapper class, `the_content()` with `article-h2`/`article-h3`/`article-p`/`article-blockquote` classes added by a content filter; guide callout after the content; checklist items rendered as an ordered list of `article-p` rows with a checkbox glyph and note; PDF button when set), `rail.php` (the "Need help?" CTA linking to `/eligibility/`, then `related.php`: "Related guides" = `related_resources` if set, else up to 3 newest resources in the same topic).
- **Resources landing** (`template-resources.php`, Template Name "Resources landing", assigned to the `resources` page by the importer): page-hero with the page's title as H1 and its ACF-free fallback copy from the prototype (kicker "Resources", lead, sub as page excerpt/first content paragraph); "Start here": featured resources (`featured = 1`, newest first; first = `resource-card-main`, next two = `resource-card-sm`, tag "{Featured guide|Checklist|Explainer} · {n} min read"); Library: every Topic with at least one published resource **not shown in Start here** (the prototype lists featured items only once), `library-topic` blocks listing those resources in the prototype's order (menu_order, then title) as `library-item` links, with an id per topic for breadcrumb anchors.
- Type label helper: guide → "Guide" ("Featured guide" on the main card), checklist → "Checklist", explainer → "Explainer".
- Header/footer unchanged. Hello's page title stays off; these templates render their own headings.

### Unit 4 — seeding and migration (importer)

`seed-resources.json` lists the 15 prototype resources: the 3 featured cards and the 12 library items, each with `title`, `type`, `topic`, `read_time`, `summary`, `featured`. The featured guide's content is the prototype article body (converted from `article.html`'s `article-body` by the existing generator machinery: `data-photo` figures become featured/inline images from the Media Library, links rewritten). The other 14 get a one-paragraph placeholder body ("Content coming soon.") and the summary as excerpt. The guide's `related_resources` is seeded with the three items the prototype shows (the checklist, the ratings explainer, and "Common reasons applications come back"); seeds carry a `menu_order` matching the prototype's Library order.

`import.php --resources`:
1. Ensure plugin active; create the 3 topics.
2. Upsert each seed by slug (published), set type, topic, ACF fields via `update_field()` (skipped if ACF absent, with a warning).
3. Assign `template-resources.php` to the `resources` page; leave its Elementor data intact.
4. Delete the old `vac-benefits-programs-guide` page (trash) and register a redirect `/resources/vac-benefits-programs-guide/` → `/resources/guides/vac-benefits-programs-guide/` in the theme (`inc/resources.php`, `template_redirect`, 301).
5. Flush rewrite rules.

`pages.py` / `generate.py`: the article page is removed from `PAGES` so it is no longer generated or imported as an Elementor page; `check-coverage.py` compares `article.html` against the guide's new URL and `resources.html` against `/resources/` (its section markers change from `<main>` to the same selector, unchanged).

### Deployment

- `deploy-theme.sh` and `deploy-siteground.sh` also rsync `wordpress/plugins/270west-content` to `wp-content/plugins/270west-content`; `import.php --all` includes `--resources`.
- ACF Pro is installed by the user in wp-admin on both sites; the importer reports `acf: missing — fields skipped` if not present and still succeeds.

### Error handling

- Missing ACF: post types render with title, content, excerpt and thumbnail; field-dependent blocks (TOC toggle, callout, items, PDF, related, featured) are hidden; the Resources page falls back to the 3 newest resources as "Start here".
- Seeding is idempotent (slug keyed); the migration step is skipped once the guide exists and the old page is gone.
- A resource without a topic appears under an "Other" heading in the Library.

### Verification

1. Unit tests: seed JSON validity and generator conversion of the article body (`tests/test_resources.py`).
2. `render-check.php` extended to every resource single and the Resources page; PHP notices fail the check.
3. `check-coverage.py`: `article.html` text equals the guide single; `resources.html` text equals `/resources/` (with the Library and featured content now generated from posts).
4. Same-origin layout diff (`layout-diff.js`) for `/resources/` vs `resources.html` and the guide vs `article.html` at 1440 and 390.
5. On both sites after ACF Pro is present: Field groups visible under ACF → Field Groups with "Sync available" absent (JSON already loaded); a checklist item repeater saves and renders.

## Out of scope

Elementor Pro dynamic templates; a Topic archive page; front-end search or filtering; a blog/news post type; PDF generation for checklists.
