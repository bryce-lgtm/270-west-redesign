# Elementor Pro templates: header, footer, resources — plan

**Status:** implemented and verified on SiteGround 2026-09-25 (both phases). Templates: `w270-header`, `w270-footer`, `w270-card-{guide,story,news}` (loop items), `w270-single-{library,story,news}`; the three archive pages carry a Taxonomy Filter + Loop Grid. `single-resource.php` hands over to the `single` location and stays as the no-Pro fallback.

**Goal:** the site chrome and the resource templates become Elementor Pro Theme Builder templates the client can edit in wp-admin, without losing the prototype's design.

**Constraints that shape the design**
- The prototype stylesheet is the design authority and is loaded above Elementor's CSS (cascade layer, see `functions.php`). Pro's widget CSS (`elementor-pro*`, `widget-*`) sits in that layer too, so prototype rules and `elementor-bridge.css` win.
- Templates are seed-once, like resources and forms: created by `import.php --all` if absent, then owned by wp-admin. `--refresh-templates` (deploy: `W270_SG_REFRESH_TEMPLATES=1`) re-seeds them from the repo.
- Hello Elementor's `header.php`/`footer.php` already call `elementor_theme_do_location()`, so a Pro header/footer takes over automatically and the theme's `template-parts/header.php`/`footer.php` become the fallback.
- Landing pages carry their own chrome, so the header/footer templates exclude them by page condition.

## Phase 1 — header and footer (Theme Builder `header` / `footer`)
1. `generate.py`: `convert_templates()` builds `out/tpl-header.json` and `out/tpl-footer.json` from `index.html`'s `<header>` and `<footer>`. Substitutions: the header `<nav>` → Pro `nav-menu` widget on the **Primary** WP menu (horizontal, hamburger below the tablet breakpoint, stretch dropdown); `.mobile-menu-btn` dropped (Pro's toggle replaces it); footer Explore/Services link lists → vertical `nav-menu` widgets on the **Footer Explore** / **Footer Services** menus. Everything else converts as pages do.
2. `import.php`: `w270_import_templates()` saves through Elementor's local template source (`save_item` / `update_item`), keys the posts by slug (`w270-header`, `w270-footer`), and sets display conditions through Pro's `ConditionsManager::save_conditions()` (`include/general` + `exclude/singular/page/<landing id>` ×3), which also regenerates Pro's conditions cache.
3. `elementor-bridge.css`: restate the prototype's `.site-header nav` rules on Pro's markup (`.elementor-nav-menu--main .elementor-item`, `.sub-menu`, `li.nav-cta`), style the hamburger like `.mobile-menu-btn` and the dropdown like `.mobile-nav`; footer vertical menus like `.footer-col-items`.
4. `main.js`: the mobile toggle / `is-scrolled` logic must tolerate the theme header being absent.
5. `render-check.php`: Pro active; header and footer templates exist, conditions set, `get_location_templates('header'|'footer')` resolves.
6. Deploy, compare against the theme header/footer at 1440 and 390, commit.

## Phase 2 — resources
1. Loop Item templates (`loop-item`): guide card, story card, news entry, built from `card.php` markup with dynamic tags (post title, featured image, post URL, ACF summary) and shortcodes for the pieces that are PHP logic (`[w270_type_label]`, `[w270_read_time]`).
2. Single templates (`single`), conditions on `singular/resource` and `in_resource_type/<term>`: Library (default), Story, News. Widgets: heading + dynamic title/SEO H1, `theme-post-content`, featured image / video, and Shortcode widgets for TOC, callout, checklist items and related resources, whose logic stays in the theme.
3. Archive pages become plain Elementor pages: hero text, Pro **Taxonomy Filter** (topic / news category) driving a **Loop Grid** whose query is `resource` filtered by `resource_type` terms, using the loop items above. `template-resource-archive.php` and `template-parts/archive/*` stay as fallbacks.
4. Known trade-off: the news archive's month grouping is not expressible in a Loop Grid; entries list by date instead.
5. render-check: each template resolves for a representative post; coverage counts unchanged.
