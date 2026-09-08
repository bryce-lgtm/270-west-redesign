# Elementor WordPress Build Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the 17-page static prototype in this repo as an Elementor (Free) site on the Local app site `270-west.local`, with native Elementor widgets per section and a Hello Elementor child theme carrying header, footer, styles and JS.

**Architecture:** A child theme (`wordpress/theme/270west`) renders header/footer from WP menus and enqueues the prototype's `styles.css` + a bridge stylesheet + `main.js`. A stdlib-Python generator converts each prototype page's `<section>`s into Elementor's element JSON (Container per section, Heading/Text/Button/Image widgets, HTML widgets for SVG/JS composites). A PHP importer run through Local's PHP binary creates pages, media, menus and kit settings from that JSON. Spec: `docs/superpowers/specs/2026-09-08-elementor-wordpress-build-design.md`.

**Tech Stack:** WordPress 7.1, Elementor 4.2.4 (Free), Hello Elementor 3.5.1, PHP 8.2 (Local's binary, `wp-load.php` bootstrap, no WP-CLI), Python 3.9 stdlib only (no bs4/pytest; tests use `unittest`), bash.

---

## Environment facts (verified 2026-09-08)

```
SITE=/Users/Bryce/Local\ Sites/270-west/app/public
PHP="/Users/Bryce/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php"
INI="/Users/Bryce/Library/Application Support/Local/run/USpouZOL3/conf/php/php.ini"
# Run WP-bootstrapped scripts as:  "$PHP" -c "$INI" wordpress/build/import.php
# Site URL: http://270-west.local  (returns 200 while Local has the site started)
```

- Elementor kit is post ID 6 (`elementor_active_kit`). Elementor applies global font/colour defaults to widgets **only** when `elementor_disable_typography_schemes` / `elementor_disable_color_schemes` options are empty; the importer sets both to `yes` so theme CSS wins.
- Elementor frontend CSS facts the bridge must counter: `.elementor-widget{position:relative}`, `.elementor-element{width:100%}`, `.e-con{--padding-*:var(--container-default-padding-*,10px); --gap:var(--widgets-spacing,20px)}`, `.elementor-widget:not(:last-child){margin-block-end:var(--kit-widget-spacing,20px)}`.
- Elementor CSS class control on every element is `_css_classes` (string). Container link setting is `link: {url, is_external, nofollow}` and requires `html_tag: 'a'`. Image widget setting is `image: {id, url}` plus `image_size: 'full'`. Button text is `text`. HTML widget content is `html`. Heading is `title` + `header_size`. Text editor is `editor`.
- Hello Elementor: child theme overrides `template-parts/header.php` and `template-parts/footer.php`; filters `hello_elementor_enqueue_style` and `hello_elementor_enqueue_theme_style` disable its reset/theme CSS; `hello_elementor_page_title` hides the theme H1.
- Prototype JS: `main.js` self-initialises `initMobileMenu`, `initHeaderScroll`, `initPhotos`, `initQuiz()` (default id `quiz-widget`) and `initScrollReveal`; pages inline-inject decoration via `document.getElementById('X').innerHTML = fn(args)` and `consult.html` calls `initConsultWidget('consult-widget')`.

## File structure

```
wordpress/
  README.md                         how to build/deploy/import
  theme/270west/
    style.css                       child theme header (Template: hello-elementor)
    functions.php                   enqueues, menus, filters, W270 asset base
    inc/class-w270-nav-walker.php   menu walker producing the prototype nav markup
    template-parts/header.php       prototype header + mobile nav
    template-parts/footer.php       prototype footer
    assets/css/styles.css           copied from css/styles.css by sync-assets.sh (never edited here)
    assets/css/elementor-bridge.css hand-written bridge rules
    assets/css/generated.css        written by generate.py (inline-style classes)
    assets/js/main.js               copied from js/main.js by sync-assets.sh
    assets/img/*                    copied from img/ by sync-assets.sh
  build/
    sync-assets.sh                  copy css/js/img from repo root into the theme
    deploy-theme.sh                 rsync theme into the Local site's wp-content/themes
    build.sh                        sync → generate → deploy → import → checks
    htmldom.py                      minimal DOM with source offsets (stdlib html.parser)
    pages.py                        page table: slug, parent, source file
    generate.py                     prototype HTML → out/<slug>.json + generated.css
    import.php                      WP bootstrap: theme, media, pages, menus, kit, options
    render-check.php                renders every page through Elementor; fails on notices
    check-coverage.py               visible-text diff, WP page vs prototype
    tests/test_htmldom.py
    tests/test_generate.py
    out/                            generated JSON (git-ignored)
```

`js/main.js` and `css/styles.css` at the repo root stay the single source of truth; `main.js` gets small additive changes (asset base, `data-decor`, consult auto-init) that keep the static prototype working.

---

### Task 1: Child theme skeleton, asset sync and deploy scripts

**Files:**
- Create: `wordpress/theme/270west/style.css`
- Create: `wordpress/theme/270west/functions.php`
- Create: `wordpress/theme/270west/assets/css/elementor-bridge.css` (empty placeholder comment for now)
- Create: `wordpress/build/sync-assets.sh`
- Create: `wordpress/build/deploy-theme.sh`
- Create: `wordpress/build/activate-theme.php`
- Modify: `.gitignore` (create) — add `wordpress/build/out/`, `wordpress/theme/270west/assets/css/styles.css`, `wordpress/theme/270west/assets/js/`, `wordpress/theme/270west/assets/img/`

- [ ] **Step 1: Write `style.css`**

```css
/*
Theme Name: 270 West
Theme URI: https://270westconsulting.ca
Description: Hello Elementor child theme carrying the 270 West redesign (header, footer, brand stylesheet, JS).
Author: 270 West Consulting
Template: hello-elementor
Version: 1.0.0
Text Domain: 270west
*/
```

- [ ] **Step 2: Write `functions.php`**

```php
<?php
/**
 * 270 West — Hello Elementor child theme.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'W270_VERSION', '1.0.0' );
define( 'W270_ASSETS', get_stylesheet_directory_uri() . '/assets' );

require_once get_stylesheet_directory() . '/inc/class-w270-nav-walker.php';

// Hello's reset/theme CSS would fight the prototype stylesheet; the prototype assumes UA defaults.
add_filter( 'hello_elementor_enqueue_style', '__return_false' );
add_filter( 'hello_elementor_enqueue_theme_style', '__return_false' );
// Elementor pages render their own H1.
add_filter( 'hello_elementor_page_title', '__return_false' );

add_action( 'after_setup_theme', function () {
	register_nav_menus( [
		'primary'         => __( 'Primary (header)', '270west' ),
		'mobile'          => __( 'Mobile menu', '270west' ),
		'footer-explore'  => __( 'Footer — Explore', '270west' ),
		'footer-services' => __( 'Footer — Services', '270west' ),
	] );
}, 20 );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'w270-fonts',
		'https://fonts.googleapis.com/css2?family=Crimson+Text:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter+Tight:ital,wght@0,300..700;1,300..600&family=League+Spartan:wght@300..700&display=swap',
		[],
		null
	);
	$deps = wp_style_is( 'elementor-frontend', 'registered' ) ? [ 'elementor-frontend', 'w270-fonts' ] : [ 'w270-fonts' ];
	$dir  = get_stylesheet_directory();
	wp_enqueue_style( 'w270-styles', W270_ASSETS . '/css/styles.css', $deps, filemtime( $dir . '/assets/css/styles.css' ) );
	wp_enqueue_style( 'w270-bridge', W270_ASSETS . '/css/elementor-bridge.css', [ 'w270-styles' ], filemtime( $dir . '/assets/css/elementor-bridge.css' ) );
	if ( file_exists( $dir . '/assets/css/generated.css' ) ) {
		wp_enqueue_style( 'w270-generated', W270_ASSETS . '/css/generated.css', [ 'w270-bridge' ], filemtime( $dir . '/assets/css/generated.css' ) );
	}
	wp_enqueue_script( 'w270-main', W270_ASSETS . '/js/main.js', [], filemtime( $dir . '/assets/js/main.js' ), true );
	wp_add_inline_script( 'w270-main', 'window.W270 = ' . wp_json_encode( [ 'assets' => W270_ASSETS ] ) . ';', 'before' );
}, 20 );

// Preconnect for Google Fonts + favicon, as in the prototype <head>.
add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	echo '<link rel="icon" href="' . esc_url( W270_ASSETS . '/img/compass-circle.svg' ) . '" type="image/svg+xml">' . "\n";
}, 1 );
```

- [ ] **Step 3: Write the placeholder bridge stylesheet**

```css
/* Bridge: adapts Elementor's wrapper markup to the prototype stylesheet. Filled in Task 9. */
```

- [ ] **Step 4: Write `sync-assets.sh`**

```bash
#!/usr/bin/env bash
# Copies the prototype's stylesheet, script and images into the child theme.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
T="$ROOT/wordpress/theme/270west/assets"
mkdir -p "$T/css" "$T/js" "$T/img"
cp "$ROOT/css/styles.css" "$T/css/styles.css"
cp "$ROOT/js/main.js" "$T/js/main.js"
rsync -a --delete "$ROOT/img/" "$T/img/"
echo "assets synced"
```

- [ ] **Step 5: Write `deploy-theme.sh`**

```bash
#!/usr/bin/env bash
# Deploys the child theme into the Local site.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
SITE="${W270_SITE:-/Users/Bryce/Local Sites/270-west/app/public}"
DEST="$SITE/wp-content/themes/270west"
mkdir -p "$DEST"
rsync -a --delete "$ROOT/wordpress/theme/270west/" "$DEST/"
echo "theme deployed to $DEST"
```

- [ ] **Step 6: Write `activate-theme.php`**

```php
<?php
// Activates the 270west child theme. Run: "$PHP" -c "$INI" wordpress/build/activate-theme.php
$site = getenv( 'W270_SITE' ) ?: '/Users/Bryce/Local Sites/270-west/app/public';
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = '270-west.local';
require $site . '/wp-load.php';
if ( get_stylesheet() !== '270west' ) {
	switch_theme( '270west' );
	echo "switched to 270west\n";
} else {
	echo "270west already active\n";
}
```

- [ ] **Step 7: Create `.gitignore`, make scripts executable, run sync + deploy + activate**

```bash
printf 'wordpress/build/out/\nwordpress/theme/270west/assets/css/styles.css\nwordpress/theme/270west/assets/js/\nwordpress/theme/270west/assets/img/\n__pycache__/\n' > .gitignore
chmod +x wordpress/build/*.sh
mkdir -p wordpress/theme/270west/inc && printf '<?php\n// Walker added in Task 2.\n' > wordpress/theme/270west/inc/class-w270-nav-walker.php
wordpress/build/sync-assets.sh && wordpress/build/deploy-theme.sh
"$PHP" -c "$INI" wordpress/build/activate-theme.php
curl -s http://270-west.local/ | grep -o 'themes/270west/assets/css/[a-z-]*\.css' | sort -u
```
Expected: `assets synced`, `theme deployed …`, `switched to 270west`, then the curl lists `styles.css` and `elementor-bridge.css`.

- [ ] **Step 8: Commit**

```bash
git add .gitignore wordpress/
git commit -m "Add 270west child theme skeleton and build scripts"
```

---

### Task 2: Header, footer, nav walker and menu import

**Files:**
- Create: `wordpress/theme/270west/inc/class-w270-nav-walker.php` (replace placeholder)
- Create: `wordpress/theme/270west/template-parts/header.php`
- Create: `wordpress/theme/270west/template-parts/footer.php`
- Create: `wordpress/build/import.php` (skeleton: bootstrap + menus + reading settings; pages added in Task 8)

- [ ] **Step 1: Write the walker**

Renders `<a>` items only (no `<ul>/<li>`), matching the prototype. Mode `primary`: a parent with children becomes `.nav-dropdown`; an item whose menu CSS class contains `nav-cta` becomes `a.nav-cta`. Mode `mobile`: children get `.mobile-subnav`, CTA gets `.mobile-nav-cta`. Mode `flat`: bare links.

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class W270_Nav_Walker extends Walker_Nav_Menu {
	private $mode;
	public function __construct( $mode = 'flat' ) { $this->mode = $mode; }

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( 'primary' === $this->mode ) { $output .= '<div class="nav-dropdown-menu">'; }
	}
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		if ( 'primary' === $this->mode ) { $output .= '</div></div>'; }
	}
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = (array) $item->classes;
		$is_cta  = in_array( 'nav-cta', $classes, true );
		$has_kids = in_array( 'menu-item-has-children', $classes, true );
		$url   = esc_url( $item->url );
		$title = esc_html( $item->title );
		$current = ( $item->current || $item->current_item_ancestor ) ? ' active' : '';
		if ( 'primary' === $this->mode ) {
			if ( $is_cta ) { $output .= '<a href="' . $url . '" class="nav-cta' . $current . '">' . $title . ' →</a>'; return; }
			if ( $has_kids && 0 === $depth ) {
				$output .= '<div class="nav-dropdown"><a href="' . $url . '" class="nav-dropdown-parent' . $current . '" aria-haspopup="true">' . $title
					. ' <svg class="nav-dropdown-chevron" viewBox="0 0 10 6" width="9" height="6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 1l4 4 4-4"/></svg></a>';
				return;
			}
			$output .= '<a href="' . $url . '"' . ( $current ? ' class="active"' : '' ) . '>' . $title . '</a>';
			return;
		}
		if ( 'mobile' === $this->mode ) {
			if ( $is_cta ) { $output .= '<a href="' . $url . '" class="mobile-nav-cta">' . $title . ' →</a>'; return; }
			$output .= '<a href="' . $url . '"' . ( $depth > 0 ? ' class="mobile-subnav"' : '' ) . '>' . $title . '</a>';
			return;
		}
		$output .= '<a href="' . $url . '">' . $title . '</a>';
	}
	public function end_el( &$output, $item, $depth = 0, $args = null ) {}
}

function w270_menu( $location, $mode ) {
	return wp_nav_menu( [
		'theme_location' => $location,
		'container'      => false,
		'items_wrap'     => '%3$s',
		'fallback_cb'    => false,
		'echo'           => false,
		'walker'         => new W270_Nav_Walker( $mode ),
	] );
}
```

- [ ] **Step 2: Write `template-parts/header.php`**

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$home = esc_url( home_url( '/' ) );
?>
<header class="site-header">
  <a href="<?php echo $home; ?>" class="logo" aria-label="270 West Veteran Services — home"><img src="<?php echo esc_url( W270_ASSETS . '/img/compass-light.svg' ); ?>" alt="" class="logo-mark" width="42" height="35"/><span class="logo-lockup"><span class="logo-name">270<span class="logo-name-west">West</span></span><span class="logo-rule" aria-hidden="true"></span><span class="logo-tag">Veteran Services</span></span></a>
  <nav>
    <?php echo w270_menu( 'primary', 'primary' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
  </nav>
  <button class="mobile-menu-btn" aria-label="Open menu">Menu</button>
</header>
<nav class="mobile-nav">
  <?php echo w270_menu( 'mobile', 'mobile' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</nav>
```

- [ ] **Step 3: Write `template-parts/footer.php`**

The topo texture opacity is 0.07 on the home page and 0.12 elsewhere (matches each prototype page's inline script). Uses the `data-decor` hook added to `main.js` in Task 7.

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$img = W270_ASSETS . '/img/';
$topo_opacity = is_front_page() ? '0.07' : '0.12';
$p = fn( $slug ) => esc_url( home_url( '/' . $slug . '/' ) );
?>
<footer class="site-footer">
  <div id="footer-topo" data-decor="topoLinesSVG" data-decor-args='["#A1B6C2",<?php echo $topo_opacity; ?>]'></div>
  <div class="footer-tagline-band" style="position:relative">
    <div>
      <div class="footer-tagline">In service of <em>your story.</em></div>
      <div class="footer-pillars">
        <span>Privacy</span><span>Respect</span><span>Support</span><span class="footer-pillar-accent">No successful claim? No fee.</span>
      </div>
    </div>
    <div class="footer-compass" aria-hidden="true">
      <svg viewBox="0 0 352.72 291.31" width="116" height="96" fill="currentColor"><path fill-rule="evenodd" d="M176.13 0 143.57 116.33 78.72 78.77 116.27 143.59 0 176.13 116.47 208.72 79.77 272.1 171.75 225.33 167.85 291.31 184.41 291.31 180.51 225.37 272.49 272.08 234.94 207.27 352.72 176.26 235.61 143.48 273.09 78.77 208.62 116.11ZM146.16 139.62 171.75 48.19 171.75 165.47ZM214.57 137.35 211.06 124.82 249.01 102.84 226.98 140.89ZM102.8 102.84 140.84 124.88 137.29 137.34 124.83 140.89ZM212.64 146.16 304.07 171.75 186.79 171.75ZM48.23 180.52 171.5 180.52 169.66 214.5ZM188.38 219.53 226.16 209.59 250.06 250.87ZM125.19 211.16 160.71 221.1 102.2 250.85Z"/></svg>
    </div>
  </div>
  <div class="footer-grid-5">
    <div>
      <div class="footer-lockup"><img src="<?php echo esc_url( $img . 'compass-light.svg' ); ?>" alt="" width="34" height="29"/><div><div class="footer-brand">270 West</div><div class="footer-brand-tag">Veteran Services</div></div></div>
      <div style="line-height:1.6">Helping Canadian veterans secure the benefits they’ve earned.<br>Made in Canada · BBB Accredited</div>
    </div>
    <div>
      <div class="footer-col-label">Explore</div>
      <div class="footer-col-items"><?php echo w270_menu( 'footer-explore', 'flat' ); // phpcs:ignore ?></div>
    </div>
    <div>
      <div class="footer-col-label">Services</div>
      <div class="footer-col-items"><?php echo w270_menu( 'footer-services', 'flat' ); // phpcs:ignore ?></div>
    </div>
    <div>
      <div class="footer-col-label">Get in touch</div>
      <div class="footer-col-items"><a href="mailto:info@270westconsulting.ca">info@270westconsulting.ca</a></div>
    </div>
    <div>
      <div class="footer-col-label">Languages</div>
      <div class="footer-col-items"><span>English</span><span>Français (Canada)</span></div>
    </div>
  </div>
  <div class="footer-bottom"><span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> 270 West Consulting · <a href="<?php echo $p( 'privacy' ); ?>" style="color:inherit">Privacy</a> · <a href="<?php echo $p( 'terms' ); ?>" style="color:inherit">Terms</a> · <a href="<?php echo $p( 'accessibility' ); ?>" style="color:inherit">Accessibility</a></span><span>In service of your story.</span></div>
</footer>
```

- [ ] **Step 4: Write the importer skeleton with menus and reading settings**

Pages don't exist yet, so menu items are created as `custom` links to the final permalinks (importer Task 8 re-points them to page objects). The importer is one file with a `main()` dispatching on flags: `--menus`, `--settings`, `--pages`, `--kit`, `--media`, `--all`, plus `--only=<slug>`.

```php
<?php
/**
 * 270 West importer. Usage:
 *   "$PHP" -c "$INI" wordpress/build/import.php --all
 *   "$PHP" -c "$INI" wordpress/build/import.php --pages --only=home
 */
$site = getenv( 'W270_SITE' ) ?: '/Users/Bryce/Local Sites/270-west/app/public';
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = '270-west.local';
require $site . '/wp-load.php';
wp_set_current_user( 1 );

const W270_OUT = __DIR__ . '/out';
const W270_IMG = __DIR__ . '/../../img';

/** Page tree the site is built from (slug => [parent slug, menu title]). Mirrors pages.py. */
function w270_page_paths() {
	return [
		'home' => '/', 'services' => '/services/', 'claims' => '/services/claims/', 'appeals' => '/services/appeals/',
		'reassessment' => '/services/reassessment/', 'support' => '/services/support/', 'how-it-works' => '/how-it-works/',
		'about' => '/about/', 'resources' => '/resources/', 'vac-benefits-programs-guide' => '/resources/vac-benefits-programs-guide/',
		'contact' => '/contact/', 'faq' => '/faq/', 'eligibility' => '/eligibility/', 'book-a-consult' => '/book-a-consult/',
		'privacy' => '/privacy/', 'terms' => '/terms/', 'accessibility' => '/accessibility/',
	];
}

function w270_page_by_slug( $slug ) {
	if ( 'home' === $slug ) { $slug = 'home'; }
	$paths = w270_page_paths();
	$page  = get_page_by_path( trim( $paths[ $slug ] ?? $slug, '/' ) ?: 'home', OBJECT, 'page' );
	return $page ?: null;
}

/**
 * Menu definition: [title, slug, classes, children].
 * Items link to pages when they exist, otherwise to a custom URL of the final path.
 */
function w270_menu_defs() {
	return [
		'Primary' => [ 'location' => 'primary', 'items' => [
			[ 'Services', 'services', '', [ [ 'Claims', 'claims' ], [ 'Appeals', 'appeals' ], [ 'Reassessment', 'reassessment' ], [ 'Support', 'support' ] ] ],
			[ 'How It Works', 'how-it-works' ], [ 'About', 'about' ], [ 'Resources', 'resources' ], [ 'Contact', 'contact' ],
			[ 'Check Eligibility', 'eligibility', 'nav-cta' ],
		] ],
		'Mobile' => [ 'location' => 'mobile', 'items' => [
			[ 'Services', 'services', '', [ [ 'Claims', 'claims' ], [ 'Appeals', 'appeals' ], [ 'Reassessment', 'reassessment' ], [ 'Support', 'support' ] ] ],
			[ 'How It Works', 'how-it-works' ], [ 'About', 'about' ], [ 'Resources', 'resources' ], [ 'Contact', 'contact' ],
			[ 'Check Eligibility', 'eligibility', 'nav-cta' ],
		] ],
		'Footer Explore' => [ 'location' => 'footer-explore', 'items' => [
			[ 'Services', 'services' ], [ 'How It Works', 'how-it-works' ], [ 'About', 'about' ], [ 'Resources', 'resources' ], [ 'Contact', 'contact' ],
		] ],
		'Footer Services' => [ 'location' => 'footer-services', 'items' => [
			[ 'Appeals', 'appeals' ], [ 'Claims', 'claims' ], [ 'Reassessments', 'reassessment' ], [ 'Support', 'support' ],
		] ],
	];
}

function w270_menu_item_args( $def, $parent_id ) {
	[ $title, $slug ] = $def;
	$classes = $def[2] ?? '';
	$page    = w270_page_by_slug( $slug );
	$args    = [
		'menu-item-title'     => $title,
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => $parent_id,
		'menu-item-classes'   => $classes,
	];
	if ( $page ) {
		$args += [ 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $page->ID ];
	} else {
		$args += [ 'menu-item-type' => 'custom', 'menu-item-url' => home_url( w270_page_paths()[ $slug ] ) ];
	}
	return $args;
}

function w270_import_menus() {
	$locations = [];
	foreach ( w270_menu_defs() as $name => $menu ) {
		$existing = wp_get_nav_menu_object( $name );
		$menu_id  = $existing ? $existing->term_id : wp_create_nav_menu( $name );
		foreach ( wp_get_nav_menu_items( $menu_id ) ?: [] as $old ) { wp_delete_post( $old->ID, true ); }
		foreach ( $menu['items'] as $def ) {
			$pid = wp_update_nav_menu_item( $menu_id, 0, w270_menu_item_args( $def, 0 ) );
			foreach ( $def[3] ?? [] as $child ) { wp_update_nav_menu_item( $menu_id, 0, w270_menu_item_args( $child, $pid ) ); }
		}
		$locations[ $menu['location'] ] = $menu_id;
		echo "menu {$name}: ok\n";
	}
	set_theme_mod( 'nav_menu_locations', $locations );
}

function w270_import_settings() {
	update_option( 'blogname', '270 West Consulting' );
	update_option( 'blogdescription', 'VAC Claims Support for Canadian Veterans' );
	update_option( 'permalink_structure', '/%postname%/' );
	update_option( 'elementor_disable_color_schemes', 'yes' );
	update_option( 'elementor_disable_typography_schemes', 'yes' );
	$home = w270_page_by_slug( 'home' );
	if ( $home ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home->ID );
	}
	flush_rewrite_rules();
	echo "settings: ok\n";
}

function w270_main( $argv ) {
	$flags = array_fill_keys( array_map( fn( $a ) => explode( '=', ltrim( $a, '-' ) )[0], array_slice( $argv, 1 ) ), true );
	$only  = null;
	foreach ( $argv as $a ) { if ( str_starts_with( $a, '--only=' ) ) { $only = substr( $a, 7 ); } }
	$all = isset( $flags['all'] );
	if ( $all || isset( $flags['media'] ) ) { function_exists( 'w270_import_media' ) && w270_import_media(); }
	if ( $all || isset( $flags['pages'] ) ) { function_exists( 'w270_import_pages' ) && w270_import_pages( $only ); }
	if ( $all || isset( $flags['menus'] ) ) { w270_import_menus(); }
	if ( $all || isset( $flags['kit'] ) ) { function_exists( 'w270_import_kit' ) && w270_import_kit(); }
	if ( $all || isset( $flags['settings'] ) ) { w270_import_settings(); }
	if ( class_exists( '\Elementor\Plugin' ) ) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
}
w270_main( $argv );
```

- [ ] **Step 5: Deploy and verify the header markup against the prototype**

```bash
wordpress/build/deploy-theme.sh
"$PHP" -c "$INI" wordpress/build/import.php --menus --settings
curl -s http://270-west.local/ > /tmp/w270-home.html
# header
grep -o '<header class="site-header">.*</header>' /tmp/w270-home.html | head -c 600; echo
grep -c 'nav-dropdown-menu' /tmp/w270-home.html; grep -c 'mobile-subnav' /tmp/w270-home.html; grep -c 'class="nav-cta' /tmp/w270-home.html
# footer
grep -c 'footer-grid-5' /tmp/w270-home.html; grep -c 'data-decor="topoLinesSVG"' /tmp/w270-home.html
```
Expected: header markup starts with the logo link, counts are `1`, `4`, `1`, and footer counts are `1`, `1`. Links point at `http://270-west.local/services/claims/` style URLs (custom links until pages exist).

- [ ] **Step 6: Commit**

```bash
git add wordpress/
git commit -m "Add prototype header/footer to the child theme with menu-driven nav"
```

---

### Task 3: Elementor data-format spike

Confirms the exact markup Elementor 4.2.4 renders for our element tree before the generator and bridge are written. Findings are recorded at the top of `elementor-bridge.css` as comments.

**Files:**
- Create: `wordpress/build/spike.php`

- [ ] **Step 1: Write `spike.php`**

```php
<?php
$site = getenv( 'W270_SITE' ) ?: '/Users/Bryce/Local Sites/270-west/app/public';
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = '270-west.local';
require $site . '/wp-load.php';
wp_set_current_user( 1 );

$id = fn() => substr( md5( uniqid( '', true ) ), 0, 7 );
$elements = [ [
	'id' => $id(), 'elType' => 'container', 'isInner' => false,
	'settings' => [ 'content_width' => 'full', 'html_tag' => 'section', '_css_classes' => 'section-pad journey-section' ],
	'elements' => [
		[ 'id' => $id(), 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => [ 'title' => 'Four steps,<br>at your pace.', 'header_size' => 'h2', '_css_classes' => 'display-lg' ], 'elements' => [] ],
		[ 'id' => $id(), 'elType' => 'widget', 'widgetType' => 'text-editor', 'settings' => [ 'editor' => '<p>A clear, unhurried process.</p>', '_css_classes' => 'journey-intro w-text' ], 'elements' => [] ],
		[ 'id' => $id(), 'elType' => 'container', 'isInner' => true,
		  'settings' => [ 'content_width' => 'full', 'html_tag' => 'a', 'link' => [ 'url' => home_url( '/eligibility/' ), 'is_external' => '', 'nofollow' => '' ], '_css_classes' => 'lm-card lm-card-dark' ],
		  'elements' => [
			[ 'id' => $id(), 'elType' => 'widget', 'widgetType' => 'button', 'settings' => [ 'text' => 'Book a free consult →', 'link' => [ 'url' => home_url( '/book-a-consult/' ) ], '_css_classes' => 'w-btn btn-accent' ], 'elements' => [] ],
			[ 'id' => $id(), 'elType' => 'widget', 'widgetType' => 'html', 'settings' => [ 'html' => '<div class="hero-quote-scrim"></div>', '_css_classes' => 'w-html' ], 'elements' => [] ],
		  ] ],
	],
] ];

$page = get_page_by_path( 'spike', OBJECT, 'page' );
$pid  = $page ? $page->ID : wp_insert_post( [ 'post_title' => 'Spike', 'post_name' => 'spike', 'post_type' => 'page', 'post_status' => 'publish' ] );
update_post_meta( $pid, '_elementor_edit_mode', 'builder' );
update_post_meta( $pid, '_elementor_template_type', 'wp-page' );
update_post_meta( $pid, '_elementor_version', ELEMENTOR_VERSION );
update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
update_post_meta( $pid, '_elementor_page_settings', [ 'hide_title' => 'yes' ] );
delete_post_meta( $pid, '_elementor_css' );
\Elementor\Plugin::$instance->files_manager->clear_cache();
echo "spike page {$pid}: " . get_permalink( $pid ) . "\n";
```

- [ ] **Step 2: Run it and inspect the rendered markup and generated CSS**

```bash
"$PHP" -c "$INI" wordpress/build/spike.php
curl -s http://270-west.local/spike/ > /tmp/spike.html
grep -o '<main[^>]*>' /tmp/spike.html
grep -oE '<(section|div|a|h2|p)[^>]*class="[^"]*(e-con|elementor-widget|elementor-heading-title|lm-card|hero-quote-scrim|elementor-button)[^"]*"[^>]*>' /tmp/spike.html
grep -o 'elementor-widget-container' /tmp/spike.html | wc -l
grep -oE 'post-[0-9]+\.css[^"]*' /tmp/spike.html | head -1
cat "$SITE"/wp-content/uploads/elementor/css/post-$(grep -oE 'post-[0-9]+\.css' /tmp/spike.html | head -1 | grep -oE '[0-9]+').css
```
Expected observations to record (write the actual results into the bridge file header):
1. The section renders as `<section class="e-con-full e-con e-parent section-pad journey-section …">` — prototype classes land on the container element itself.
2. Heading renders `<div class="elementor-element … elementor-widget-heading display-lg"><h2 class="elementor-heading-title elementor-size-default">…</h2></div>` — classes land on the wrapper.
3. Whether `elementor-widget-container` wrappers exist (count 0 means the optimized-markup experiment is on; the bridge selectors in Task 9 cover both).
4. The linked container renders as `<a class="e-con … lm-card lm-card-dark" href="…">`.
5. The post CSS file contains no `font-family` for the heading/text/button (global schemes disabled) — if it does, confirm `elementor_disable_typography_schemes` is `yes` via `get_option` and re-run `clear_cache`.

- [ ] **Step 3: Remove the spike page**

```bash
"$PHP" -c "$INI" -r 'define("WP_USE_THEMES",false);$_SERVER["HTTP_HOST"]="270-west.local";require "/Users/Bryce/Local Sites/270-west/app/public/wp-load.php"; $p=get_page_by_path("spike",OBJECT,"page"); if($p){wp_delete_post($p->ID,true); echo "spike deleted\n";}'
```

- [ ] **Step 4: Commit**

```bash
git add wordpress/build/spike.php wordpress/theme/270west/assets/css/elementor-bridge.css
git commit -m "Add Elementor markup spike and record findings"
```

---

### Task 4: `htmldom.py` — minimal DOM with source offsets

Python 3.9 stdlib only (no BeautifulSoup on this machine). Keeping source offsets lets HTML widgets carry the prototype markup byte-for-byte.

**Files:**
- Create: `wordpress/build/htmldom.py`
- Create: `wordpress/build/tests/__init__.py` (empty)
- Create: `wordpress/build/tests/test_htmldom.py`

- [ ] **Step 1: Write the failing tests**

```python
import unittest
from htmldom import parse

class HtmlDomTests(unittest.TestCase):
    def test_tree_classes_and_offsets(self):
        src = '<div class="a b"><p>Hi <em>x</em></p><img src="i.jpg"/></div>'
        doc = parse(src)
        div = doc.root.elements()[0]
        self.assertEqual(div.tag, 'div')
        self.assertEqual(div.classes, ['a', 'b'])
        self.assertEqual(doc.outer_html(div), src)
        p, img = div.elements()
        self.assertEqual(doc.inner_html(p), 'Hi <em>x</em>')
        self.assertEqual(doc.outer_html(img), '<img src="i.jpg"/>')
        self.assertEqual(img.attrs['src'], 'i.jpg')

    def test_entities_are_kept_raw_but_text_is_unescaped(self):
        doc = parse('<p>A &amp; B &#8217;s</p>')
        p = doc.root.elements()[0]
        self.assertEqual(doc.inner_html(p), 'A &amp; B &#8217;s')
        self.assertEqual(doc.text(p), 'A & B ’s')

    def test_svg_self_closing_and_nesting(self):
        src = '<section><svg viewBox="0 0 10 6"><path d="M1 1"/></svg><div></div></section>'
        doc = parse(src)
        sec = doc.root.elements()[0]
        svg, div = sec.elements()
        self.assertEqual(doc.outer_html(svg), '<svg viewBox="0 0 10 6"><path d="M1 1"/></svg>')
        self.assertEqual(div.elements(), [])
        self.assertEqual(doc.inner_html(div), '')

    def test_body_and_title(self):
        doc = parse('<html><head><title>T &amp; U</title></head><body><section id="s">x</section></body></html>')
        self.assertEqual(doc.title(), 'T & U')
        self.assertEqual(doc.body().elements()[0].attrs['id'], 's')

if __name__ == '__main__':
    unittest.main()
```

- [ ] **Step 2: Run to verify failure**

```bash
cd wordpress/build && python3 -m unittest tests.test_htmldom -v; cd -
```
Expected: `ModuleNotFoundError: No module named 'htmldom'`.

- [ ] **Step 3: Write `htmldom.py`**

```python
"""Minimal DOM over html.parser that records source offsets, so any subtree can be
re-emitted verbatim. Assumes well-formed HTML (the prototype pages are)."""
import html
from html.parser import HTMLParser

VOID = {'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'}


class Node:
    __slots__ = ('tag', 'attrs', 'children', 'parent', 'text', 'start', 'end', 'inner_start', 'inner_end')

    def __init__(self, tag, attrs=None, parent=None, text=None):
        self.tag = tag
        self.attrs = dict(attrs or {})
        self.children = []
        self.parent = parent
        self.text = text
        self.start = self.end = self.inner_start = self.inner_end = None

    @property
    def is_text(self):
        return self.tag is None

    @property
    def classes(self):
        return (self.attrs.get('class') or '').split()

    def elements(self):
        return [c for c in self.children if not c.is_text]

    def iter(self):
        yield self
        for c in self.children:
            yield from c.iter()

    def find(self, pred):
        return [n for n in self.iter() if not n.is_text and pred(n)]

    def __repr__(self):
        return f'<Node {self.tag} {self.attrs}>' if self.tag else f'<Text {self.text!r}>'


class Document:
    def __init__(self, source, root):
        self.source = source
        self.root = root

    def outer_html(self, node):
        return self.source[node.start:node.end]

    def inner_html(self, node):
        return self.source[node.inner_start:node.inner_end]

    def text(self, node):
        return html.unescape(''.join(n.text for n in node.iter() if n.is_text))

    def body(self):
        found = self.root.find(lambda n: n.tag == 'body')
        return found[0] if found else self.root

    def title(self):
        found = self.root.find(lambda n: n.tag == 'title')
        return self.text(found[0]).strip() if found else ''

    def meta(self, name):
        for n in self.root.find(lambda n: n.tag == 'meta' and n.attrs.get('name') == name):
            return html.unescape(n.attrs.get('content', ''))
        return ''


class _Builder(HTMLParser):
    def __init__(self, source):
        super().__init__(convert_charrefs=False)
        self.source = source
        self.line_starts = [0] + [i + 1 for i, ch in enumerate(source) if ch == '\n']
        self.root = Node('#root')
        self.cur = self.root

    def _pos(self):
        line, col = self.getpos()
        return self.line_starts[line - 1] + col

    def _open(self, tag, attrs, void):
        node = Node(tag, attrs, self.cur)
        node.start = self._pos()
        node.inner_start = node.start + len(self.get_starttag_text())
        self.cur.children.append(node)
        if void:
            node.inner_end = node.end = node.inner_start
        else:
            self.cur = node

    def handle_starttag(self, tag, attrs):
        self._open(tag, attrs, tag in VOID)

    def handle_startendtag(self, tag, attrs):
        self._open(tag, attrs, True)

    def handle_endtag(self, tag):
        node = self.cur
        while node is not self.root and node.tag != tag:
            node = node.parent
        if node is self.root:
            return  # stray end tag; ignore
        pos = self._pos()
        node.inner_end = pos
        node.end = self.source.index('>', pos) + 1
        self.cur = node.parent

    def _text(self, raw):
        node = Node(None, parent=self.cur, text=raw)
        node.start = self._pos()
        node.end = node.start + len(raw)
        self.cur.children.append(node)

    def handle_data(self, data):
        self._text(data)

    def handle_entityref(self, name):
        self._text(f'&{name};')

    def handle_charref(self, name):
        self._text(f'&#{name};')

    def handle_comment(self, data):
        pass


def parse(source):
    b = _Builder(source)
    b.feed(source)
    b.close()
    return Document(source, b.root)
```

- [ ] **Step 4: Run tests**

```bash
cd wordpress/build && touch tests/__init__.py && python3 -m unittest tests.test_htmldom -v; cd -
```
Expected: 4 tests OK.

- [ ] **Step 5: Commit**

```bash
git add wordpress/build/htmldom.py wordpress/build/tests/
git commit -m "Add offset-preserving HTML DOM parser for the Elementor generator"
```

---

### Task 5: `pages.py` and the generator core (`generate.py`)

**Files:**
- Create: `wordpress/build/pages.py`
- Create: `wordpress/build/generate.py`
- Create: `wordpress/build/tests/test_generate.py`

- [ ] **Step 1: Write `pages.py`**

```python
"""Page table: (source file, slug, parent slug). Order matters: parents before children."""
PAGES = [
    ('index.html', 'home', None),
    ('services.html', 'services', None),
    ('service-claims.html', 'claims', 'services'),
    ('service-appeals.html', 'appeals', 'services'),
    ('service-reassessment.html', 'reassessment', 'services'),
    ('service-support.html', 'support', 'services'),
    ('how-it-works.html', 'how-it-works', None),
    ('about.html', 'about', None),
    ('resources.html', 'resources', None),
    ('article.html', 'vac-benefits-programs-guide', 'resources'),
    ('contact.html', 'contact', None),
    ('faq.html', 'faq', None),
    ('eligibility.html', 'eligibility', None),
    ('consult.html', 'book-a-consult', None),
    ('privacy.html', 'privacy', None),
    ('terms.html', 'terms', None),
    ('accessibility.html', 'accessibility', None),
]

TITLE_SUFFIX = ' — 270 West Consulting'


def path_for(slug):
    """Site-relative path for a slug, e.g. 'claims' -> '/services/claims/'."""
    if slug == 'home':
        return '/'
    parents = {s: p for _, s, p in PAGES}
    parts = []
    while slug:
        parts.append(slug)
        slug = parents[slug]
    return '/' + '/'.join(reversed(parts)) + '/'


# prototype file -> site path
LINK_MAP = {src: path_for(slug) for src, slug, _ in PAGES}
```

- [ ] **Step 2: Write the failing generator tests**

```python
import unittest
from generate import convert_fragment, rewrite_links, parse_decor, StyleRegistry


def first(html):
    els = convert_fragment(html)
    assert len(els) == 1, els
    return els[0]


class GenerateTests(unittest.TestCase):
    def test_section_becomes_full_width_container_with_classes(self):
        el = first('<section class="section-pad journey-section"><h2 class="display-lg">Four steps,<br>at your pace.</h2></section>')
        self.assertEqual(el['elType'], 'container')
        self.assertEqual(el['settings']['html_tag'], 'section')
        self.assertEqual(el['settings']['content_width'], 'full')
        self.assertEqual(el['settings']['_css_classes'], 'w-con section-pad journey-section')
        h = el['elements'][0]
        self.assertEqual(h['widgetType'], 'heading')
        self.assertEqual(h['settings']['header_size'], 'h2')
        self.assertEqual(h['settings']['title'], 'Four steps,<br>at your pace.')
        self.assertEqual(h['settings']['_css_classes'], 'w-heading display-lg')

    def test_paragraph_and_inline_div_become_text_widgets(self):
        p = first('<p class="hero-lead">We help <em>you</em>.</p>')
        self.assertEqual(p['widgetType'], 'text-editor')
        self.assertEqual(p['settings']['editor'], '<p>We help <em>you</em>.</p>')
        self.assertEqual(p['settings']['_css_classes'], 'w-text hero-lead')
        d = first('<div class="journey-step-n">01</div>')
        self.assertEqual(d['settings']['editor'], '01')

    def test_button_widget(self):
        b = first('<a href="consult.html" class="btn-accent">Book a free consult →</a>')
        self.assertEqual(b['widgetType'], 'button')
        self.assertEqual(b['settings']['text'], 'Book a free consult →')
        self.assertEqual(b['settings']['link']['url'], '/book-a-consult/')
        self.assertEqual(b['settings']['_css_classes'], 'w-btn btn-accent')

    def test_image_widget_records_media_marker(self):
        i = first('<img src="img/veteran-sarah.jpg" alt="Capt. Sarah" style="object-position:center 30%"/>')
        self.assertEqual(i['widgetType'], 'image')
        self.assertEqual(i['settings']['image']['__media__'], 'veteran-sarah.jpg')
        self.assertEqual(i['settings']['image']['alt'], 'Capt. Sarah')
        self.assertEqual(i['settings']['image_size'], 'full')
        self.assertIn('w270-s', i['settings']['_css_classes'])

    def test_svg_empty_div_and_data_photo_become_html_widgets(self):
        s = first('<svg viewBox="0 0 10 6"><path d="M1 1"/></svg>')
        self.assertEqual(s['widgetType'], 'html')
        self.assertEqual(s['settings']['html'], '<svg viewBox="0 0 10 6"><path d="M1 1"/></svg>')
        e = first('<div class="hero-compass" aria-hidden="true"></div>')
        self.assertEqual(e['settings']['html'], '<div class="hero-compass" aria-hidden="true"></div>')
        ph = first('<div data-photo="shoreline" data-alt="Dock" data-aspect="16/7"></div>')
        self.assertEqual(ph['widgetType'], 'html')
        self.assertIn('data-photo="shoreline"', ph['settings']['html'])

    def test_linked_card_container(self):
        c = first('<a href="eligibility.html" class="lm-card lm-card-dark"><div class="lm-meta">2 min</div><h2 class="lm-h">Checker</h2></a>')
        self.assertEqual(c['elType'], 'container')
        self.assertEqual(c['settings']['html_tag'], 'a')
        self.assertEqual(c['settings']['link']['url'], '/eligibility/')
        self.assertEqual([e['widgetType'] for e in c['elements']], ['text-editor', 'heading'])

    def test_rich_block_is_one_text_widget(self):
        r = first('<div class="legal-body"><h2 id="sec-0">One</h2><p>a</p><p>b</p></div>')
        self.assertEqual(r['widgetType'], 'text-editor')
        self.assertEqual(r['settings']['_css_classes'], 'w-rich legal-body')
        self.assertEqual(r['settings']['editor'], '<h2 id="sec-0">One</h2><p>a</p><p>b</p>')

    def test_stray_text_inside_container_is_kept(self):
        c = first('<div class="x">Loose <svg></svg></div>')
        self.assertEqual([e['widgetType'] for e in c['elements']], ['text-editor', 'html'])
        self.assertEqual(c['elements'][0]['settings']['editor'], 'Loose')

    def test_figure_maps_to_div_container_and_keeps_id(self):
        c = first('<figure class="hero-quote" id="fq"><img src="img/a.jpg" alt=""/><figcaption class="c">Q</figcaption></figure>')
        self.assertEqual(c['settings']['html_tag'], 'div')
        self.assertEqual(c['settings']['_element_id'], 'fq')

    def test_rewrite_links(self):
        self.assertEqual(rewrite_links('<a href="service-claims.html#x">c</a> <img src="img/a.svg">'),
                         '<a href="/services/claims/#x">c</a> <img src="__W270_ASSETS__/img/a.svg">')
        self.assertEqual(rewrite_links('<a href="index.html">h</a>'), '<a href="/">h</a>')
        self.assertEqual(rewrite_links('<a href="mailto:x@y.z">m</a>'), '<a href="mailto:x@y.z">m</a>')

    def test_parse_decor(self):
        js = ("document.getElementById('about-maple').innerHTML = brandMarkSVG(260, 'currentColor');"
              "var svc = document.getElementById('svc-grid'); if (svc) svc.innerHTML = mapGridSVG('#A1B6C2', 0.05, 48);"
              "document.getElementById('footer-topo').innerHTML = topoLinesSVG('#A1B6C2', 0.12);")
        self.assertEqual(parse_decor(js), {
            'about-maple': ('brandMarkSVG', '[260,"currentColor"]'),
            'svc-grid': ('mapGridSVG', '["#A1B6C2",0.05,48]'),
        })

    def test_decor_attrs_applied_to_html_widget(self):
        els = convert_fragment('<div id="cta-topo"></div>', decor={'cta-topo': ('topoLinesSVG', '["#A1B6C2",0.12]')})
        self.assertEqual(els[0]['settings']['html'], '<div id="cta-topo" data-decor="topoLinesSVG" data-decor-args=\'["#A1B6C2",0.12]\'></div>')

    def test_style_registry(self):
        reg = StyleRegistry()
        a = reg.cls('margin-top:26px')
        b = reg.cls('margin-top:26px')
        c = reg.cls('object-position:center 30%', kind='img')
        self.assertEqual(a, b)
        self.assertNotEqual(a, c)
        self.assertIn(f'.{a}{{margin-top:26px}}', reg.css())
        self.assertIn(f'.{c} img{{object-position:center 30%}}', reg.css())

if __name__ == '__main__':
    unittest.main()
```

- [ ] **Step 3: Run to verify failure**

```bash
cd wordpress/build && python3 -m unittest tests.test_generate -v 2>&1 | tail -3; cd -
```
Expected: `ModuleNotFoundError: No module named 'generate'`.

- [ ] **Step 4: Write `generate.py`**

```python
"""Prototype HTML -> Elementor element JSON.

Usage: python3 generate.py [--only slug]   (run from wordpress/build)
Writes out/<slug>.json for each page and ../theme/270west/assets/css/generated.css.
"""
import json
import os
import re
import sys
import uuid

from htmldom import parse, Node
from pages import PAGES, LINK_MAP, TITLE_SUFFIX, path_for

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
OUT = os.path.join(os.path.dirname(__file__), 'out')
GENERATED_CSS = os.path.join(os.path.dirname(__file__), '..', 'theme', '270west', 'assets', 'css', 'generated.css')
ASSETS = '__W270_ASSETS__'

HEADINGS = {'h1', 'h2', 'h3', 'h4', 'h5', 'h6'}
INLINE_TAGS = {'a', 'em', 'strong', 'span', 'br', 'b', 'i', 'small', 'sup', 'sub', 'abbr', 'time', 'code', 'u', 'mark', 'q', 'cite'}
RICH_TAGS = {'p', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'blockquote', 'hr'}
LIST_TAGS = {'ul', 'ol', 'blockquote', 'dl'}
HTML_TAGS = {'svg', 'form', 'details', 'table', 'input', 'select', 'textarea', 'button', 'iframe', 'video', 'label'}
BUTTON_CLASSES = {'btn-accent', 'btn-ink', 'btn-outline', 'esg-snapshot-link', 'article-rail-btn'}
CONTAINER_TAGS = {'div', 'header', 'footer', 'main', 'article', 'section', 'aside', 'nav', 'a'}
JS_WIDGET_IDS = {'quiz-widget', 'consult-widget'}
SKIP_TOP = {'header', 'footer', 'script', 'style'}


def new_id():
    return uuid.uuid4().hex[:7]


def rewrite_links(s):
    """Rewrite prototype hrefs/srcs: *.html -> site path, img/ -> theme assets placeholder."""
    def href(m):
        target = m.group(2)
        path = '/' if target == 'index.html' else LINK_MAP.get(target)
        if path is None:
            raise ValueError(f'unmapped link target: {target}')
        return f'{m.group(1)}{path}{m.group(3) or ""}"'
    s = re.sub(r'(href=")([\w-]+\.html)(#[^"]*)?"', href, s)
    s = re.sub(r'((?:src|href)=")img/', rf'\1{ASSETS}/img/', s)
    return s


def parse_decor(js):
    """Map element id -> (function name, JSON args) from a page's inline decoration script."""
    out = {}
    for m in re.finditer(r"getElementById\('([\w-]+)'\)", js):
        tail = js[m.end():m.end() + 160]
        m2 = re.match(r"(?:;\s*if \(\w+\) \w+)?\.innerHTML = (\w+)\(([^)]*)\)", tail)
        if not m2 or m.group(1) == 'footer-topo':
            continue
        args = '[' + re.sub(r"\s*,\s*", ',', m2.group(2).strip().replace("'", '"')) + ']'
        out[m.group(1)] = (m2.group(1), args)
    return out


class StyleRegistry:
    """Inline style="" values become generated classes (Elementor has no inline-style control)."""
    def __init__(self):
        self.rules = {}

    def cls(self, style, kind='self'):
        style = re.sub(r'\s*;\s*$', '', style.strip())
        style = re.sub(r'\s*:\s*', ':', style)
        key = (kind, style)
        if key not in self.rules:
            self.rules[key] = f'w270-s{len(self.rules) + 1}'
        return self.rules[key]

    def css(self):
        lines = ['/* generated by generate.py from prototype inline styles — do not edit */']
        for (kind, style), name in self.rules.items():
            sel = f'.{name} img' if kind == 'img' else f'.{name}'
            lines.append(f'{sel}{{{style}}}')
        return '\n'.join(lines) + '\n'


STYLES = StyleRegistry()


def _attr_str(attrs):
    parts = []
    for k, v in attrs.items():
        if v is None:
            parts.append(k)
        elif '"' in v and "'" not in v:
            parts.append(f"{k}='{v}'")
        else:
            parts.append(f'{k}="{v}"')
    return (' ' + ' '.join(parts)) if parts else ''


class Converter:
    def __init__(self, doc, decor=None, styles=STYLES):
        self.doc = doc
        self.decor = decor or {}
        self.styles = styles
        self.media = []
        self.warnings = []

    # ── classification helpers ──
    def inline_only(self, node):
        return all(e.tag in INLINE_TAGS and self.inline_only(e) for e in node.elements())

    def has_text(self, node):
        return any(n.text.strip() for n in node.iter() if n.is_text)

    def is_rich(self, node):
        kids = node.elements()
        return len(kids) >= 2 and all(k.tag in RICH_TAGS and not k.classes for k in kids)

    def classes(self, node, marker):
        cls = [marker] + node.classes
        style = node.attrs.get('style')
        if style:
            cls.append(self.styles.cls(style, kind='img' if node.tag == 'img' else 'self'))
        return ' '.join(cls)

    # ── widget builders ──
    def widget(self, wtype, settings):
        return {'id': new_id(), 'elType': 'widget', 'widgetType': wtype, 'settings': settings, 'elements': []}

    def heading(self, node):
        return self.widget('heading', {
            'title': rewrite_links(self.doc.inner_html(node).strip()),
            'header_size': node.tag,
            '_css_classes': self.classes(node, 'w-heading'),
        })

    def text(self, node, rich=False):
        inner = rewrite_links(self.doc.inner_html(node).strip())
        if node.tag in LIST_TAGS:
            editor = rewrite_links(self.doc.outer_html(node))
        elif node.tag == 'p':
            editor = f'<p>{inner}</p>'
        else:
            editor = inner
        return self.widget('text-editor', {'editor': editor, '_css_classes': self.classes(node, 'w-rich' if rich else 'w-text')})

    def loose_text(self, raw):
        return self.widget('text-editor', {'editor': raw.strip(), '_css_classes': 'w-text'})

    def button(self, node):
        return self.widget('button', {
            'text': rewrite_links(self.doc.inner_html(node).strip()),
            'link': {'url': rewrite_links(f'href="{node.attrs.get("href", "#")}"')[6:-1], 'is_external': '', 'nofollow': ''},
            '_css_classes': self.classes(node, 'w-btn'),
        })

    def image(self, node):
        name = os.path.basename(node.attrs.get('src', ''))
        self.media.append(name)
        return self.widget('image', {
            'image': {'__media__': name, 'alt': node.attrs.get('alt', '')},
            'image_size': 'full',
            '_css_classes': self.classes(node, 'w-image'),
        })

    def html(self, node):
        attrs = dict(node.attrs)
        el_id = attrs.get('id')
        if el_id in self.decor:
            fn, args = self.decor[el_id]
            attrs['data-decor'] = fn
            attrs['data-decor-args'] = args
        if attrs == node.attrs:
            markup = self.doc.outer_html(node)
        else:
            markup = f'<{node.tag}{_attr_str(attrs)}>{self.doc.inner_html(node)}</{node.tag}>'
        return self.widget('html', {'html': rewrite_links(markup), '_css_classes': 'w-html'})

    def container(self, node):
        settings = {
            'content_width': 'full',
            'html_tag': node.tag if node.tag in CONTAINER_TAGS else 'div',
            '_css_classes': self.classes(node, 'w-con'),
        }
        if node.attrs.get('id'):
            settings['_element_id'] = node.attrs['id']
        if node.tag == 'a':
            settings['html_tag'] = 'a'
            settings['link'] = {'url': rewrite_links(f'href="{node.attrs.get("href", "#")}"')[6:-1], 'is_external': '', 'nofollow': ''}
        dropped = [k for k in node.attrs if k not in ('class', 'id', 'style', 'href')]
        if dropped:
            self.warnings.append(f'container <{node.tag} class="{node.attrs.get("class", "")}"> dropped attrs {dropped}')
        children = []
        for c in node.children:
            if c.is_text:
                if c.text.strip():
                    children.append(self.loose_text(c.text))
            elif c.tag in ('script', 'style'):
                continue
            else:
                children.append(self.convert(c))
        return {'id': new_id(), 'elType': 'container', 'isInner': True, 'settings': settings, 'elements': children}

    # ── dispatcher ──
    def convert(self, node):
        tag = node.tag
        if tag == 'img':
            return self.image(node)
        if tag in HEADINGS and self.inline_only(node):
            return self.heading(node)
        if tag == 'a' and set(node.classes) & BUTTON_CLASSES and self.inline_only(node):
            return self.button(node)
        if tag in HTML_TAGS or 'data-photo' in node.attrs or node.attrs.get('id') in JS_WIDGET_IDS:
            return self.html(node)
        if tag in LIST_TAGS:
            return self.text(node)
        kids = node.elements()
        if not kids:
            return self.text(node) if self.has_text(node) else self.html(node)
        if self.inline_only(node):
            return self.text(node)
        if self.is_rich(node):
            return self.text(node, rich=True)
        return self.container(node)

    def convert_top(self, node):
        el = self.convert(node)
        if el['elType'] != 'container':  # a top-level widget still needs a container parent
            el = {'id': new_id(), 'elType': 'container', 'isInner': False,
                  'settings': {'content_width': 'full', '_css_classes': 'w-con'}, 'elements': [el]}
        el['isInner'] = False
        return el


def convert_fragment(html_text, decor=None):
    doc = parse(html_text)
    conv = Converter(doc, decor)
    return [conv.convert(n) for n in doc.root.elements()]


def convert_page(src_file, slug, parent):
    with open(os.path.join(ROOT, src_file), encoding='utf-8') as f:
        source = f.read()
    doc = parse(source)
    body = doc.body()
    decor = {}
    for s in body.find(lambda n: n.tag == 'script' and 'src' not in n.attrs):
        decor.update(parse_decor(doc.inner_html(s)))
    conv = Converter(doc, decor)
    elements = []
    for child in body.elements():
        if child.tag in SKIP_TOP or (child.tag == 'nav' and 'mobile-nav' in child.classes):
            continue
        elements.append(conv.convert_top(child))
    title = doc.title()
    if slug != 'home' and title.endswith(TITLE_SUFFIX):
        title = title[:-len(TITLE_SUFFIX)]
    return {
        'slug': slug,
        'parent': parent,
        'path': path_for(slug),
        'title': title,
        'excerpt': doc.meta('description'),
        'page_settings': {'hide_title': 'yes'},
        'media': sorted(set(conv.media)),
        'elements': elements,
    }, conv.warnings


def main(argv):
    only = argv[argv.index('--only') + 1] if '--only' in argv else None
    os.makedirs(OUT, exist_ok=True)
    for src, slug, parent in PAGES:
        if only and slug != only:
            continue
        page, warnings = convert_page(src, slug, parent)
        with open(os.path.join(OUT, f'{slug}.json'), 'w', encoding='utf-8') as f:
            json.dump(page, f, ensure_ascii=False, indent=1)
        n = sum(1 for _ in _walk(page['elements']))
        print(f'{slug:28s} {len(page["elements"]):2d} sections {n:4d} elements' + (f'  ⚠ {len(warnings)} warnings' if warnings else ''))
        for w in warnings:
            print('   ', w)
    with open(GENERATED_CSS, 'w', encoding='utf-8') as f:
        f.write(STYLES.css())
    print(f'generated.css: {len(STYLES.rules)} rules')


def _walk(elements):
    for e in elements:
        yield e
        yield from _walk(e.get('elements', []))


if __name__ == '__main__':
    main(sys.argv[1:])
```

- [ ] **Step 5: Run the tests**

```bash
cd wordpress/build && python3 -m unittest tests.test_generate -v 2>&1 | tail -5; cd -
```
Expected: 13 tests OK. If `test_style_registry` fails on the `img` rule, check that `StyleRegistry.css` uses `kind == 'img'`.

- [ ] **Step 6: Generate all pages and read the warnings**

```bash
cd wordpress/build && python3 generate.py; ls out | wc -l; cd -
```
Expected: 17 lines like `home  9 sections  120 elements`, `out` has 17 files, `generated.css: N rules`. Warnings list any container that dropped attributes (e.g. `aria-label`, `data-*`). For each warning decide: harmless (aria on a wrapper) → ignore; behaviour-bearing (`data-*` used by `main.js`) → add that attribute name to the `HTML_TAGS`-style force list by extending the `if tag in HTML_TAGS or 'data-photo' in node.attrs` condition with the attribute, and re-run.

- [ ] **Step 7: Commit**

```bash
git add wordpress/build/pages.py wordpress/build/generate.py wordpress/build/tests/test_generate.py
git commit -m "Add prototype-to-Elementor page generator"
```

---

### Task 6: Adapt `js/main.js` for WordPress (asset base, `data-decor`, consult auto-init)

The repo's `js/main.js` stays the single source for both the static prototype and the theme. All changes are additive: the static pages keep working.

**Files:**
- Modify: `js/main.js` (`initPhotos` line 16; add `initDecor`; guard `initConsultWidget`; init block at the end)

- [ ] **Step 1: Point `initPhotos` at the theme asset base when present**

In `initPhotos`, replace the `img` template line:

```js
    const base = (window.W270 && window.W270.assets) ? window.W270.assets + '/img/' : 'img/';
    const img = `<img src="${base}${d.photo}.jpg" alt="${alt}" ${loadAttrs} style="object-position:${d.pos || 'center'}">`;
```

- [ ] **Step 2: Add `initDecor` (after `initPhotos`)**

```js
// ── Decorative SVG injection (data-decor) ──
// <div data-decor="topoLinesSVG" data-decor-args='["#A1B6C2",0.12]'></div> calls topoLinesSVG('#A1B6C2', 0.12).
// Replaces the per-page inline scripts; the generator emits these attributes for WordPress.
function initDecor() {
  document.querySelectorAll('[data-decor]').forEach(el => {
    const fn = window[el.dataset.decor];
    if (typeof fn !== 'function') return;
    let args = [];
    try { args = JSON.parse(el.dataset.decorArgs || '[]'); } catch (e) { args = []; }
    el.innerHTML = fn.apply(null, args);
  });
}
```

- [ ] **Step 3: Guard `initConsultWidget` against double initialisation**

At the top of `initConsultWidget`, right after `const container = document.getElementById(containerId || 'consult-widget');`, add:

```js
  if (!container || container.dataset.inited) return;
  container.dataset.inited = '1';
```
(Keep the existing `if (!container) return;` if present; the new guard supersedes it.)

- [ ] **Step 4: Extend the init block at the end of the file**

Replace the final three lines of the `DOMContentLoaded` handler (`initPhotos(); initQuiz(); requestAnimationFrame(...)`) with:

```js
  initDecor();
  initPhotos();
  initQuiz();
  if (document.getElementById('consult-widget')) initConsultWidget('consult-widget');
  requestAnimationFrame(() => requestAnimationFrame(initScrollReveal));
```

- [ ] **Step 5: Verify the static prototype still works**

```bash
node -e "new Function(require('fs').readFileSync('js/main.js','utf8'))" && echo "syntax ok"
(python3 -m http.server 8270 >/dev/null 2>&1 &) ; sleep 1
```
Then open `http://localhost:8270/consult.html` and `http://localhost:8270/about.html` in the in-app browser (`navigate`), take a screenshot of each, and read the console (`read_console_messages` with `onlyErrors: true`). Expected: consult scheduler renders once, About shows the compass mark and dot pattern, no console errors.

- [ ] **Step 6: Commit**

```bash
git add js/main.js
git commit -m "main.js: asset base, data-decor injection, consult auto-init for WordPress"
```

---

### Task 7: Importer — media, pages, kit

**Files:**
- Modify: `wordpress/build/import.php` (add the three functions above `w270_main`)

- [ ] **Step 1: Add media import**

```php
function w270_media_id( $name ) {
	$ids = get_posts( [ 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_270w_source', 'meta_value' => $name, 'fields' => 'ids', 'numberposts' => 1 ] );
	return $ids ? (int) $ids[0] : 0;
}

function w270_import_media() {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	foreach ( glob( W270_IMG . '/*.{jpg,jpeg,png}', GLOB_BRACE ) as $file ) {
		$name = basename( $file );
		if ( w270_media_id( $name ) ) { echo "media {$name}: exists\n"; continue; }
		$tmp = wp_tempnam( $name );
		copy( $file, $tmp );
		$id = media_handle_sideload( [ 'name' => $name, 'tmp_name' => $tmp ], 0 );
		if ( is_wp_error( $id ) ) { echo "media {$name}: ERROR " . $id->get_error_message() . "\n"; $GLOBALS['w270_failed'] = true; continue; }
		update_post_meta( $id, '_270w_source', $name );
		echo "media {$name}: #{$id}\n";
	}
}
```

- [ ] **Step 2: Add placeholder resolution and page import**

```php
/** Replace generator markers: __W270_ASSETS__ in strings, {"__media__": file} in image settings, site paths in link settings. */
function w270_resolve( array $elements, string $assets ) {
	foreach ( $elements as &$el ) {
		foreach ( $el['settings'] as $k => &$v ) {
			if ( is_string( $v ) ) {
				$v = str_replace( '__W270_ASSETS__', $assets, $v );
			} elseif ( is_array( $v ) && isset( $v['__media__'] ) ) {
				$id = w270_media_id( $v['__media__'] );
				if ( ! $id ) { throw new RuntimeException( "media not imported: {$v['__media__']}" ); }
				if ( ! empty( $v['alt'] ) && ! get_post_meta( $id, '_wp_attachment_image_alt', true ) ) {
					update_post_meta( $id, '_wp_attachment_image_alt', $v['alt'] );
				}
				$v = [ 'id' => $id, 'url' => wp_get_attachment_url( $id ) ];
			} elseif ( is_array( $v ) && isset( $v['url'] ) && is_string( $v['url'] ) && str_starts_with( $v['url'], '/' ) ) {
				$v['url'] = home_url( $v['url'] );
			}
		}
		unset( $v );
		if ( ! empty( $el['elements'] ) ) { $el['elements'] = w270_resolve( $el['elements'], $assets ); }
	}
	return $elements;
}

function w270_import_pages( $only = null ) {
	$assets = get_stylesheet_directory_uri() . '/assets';
	foreach ( array_keys( w270_page_paths() ) as $slug ) {
		if ( $only && $only !== $slug ) { continue; }
		try {
			$file = W270_OUT . "/{$slug}.json";
			if ( ! file_exists( $file ) ) { throw new RuntimeException( "missing {$file} (run generate.py)" ); }
			$def = json_decode( file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
			$parent_id = 0;
			if ( $def['parent'] ) {
				$parent = w270_page_by_slug( $def['parent'] );
				if ( ! $parent ) { throw new RuntimeException( "parent {$def['parent']} not imported yet" ); }
				$parent_id = $parent->ID;
			}
			$existing = w270_page_by_slug( $slug );
			$post = [
				'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $def['title'], 'post_name' => $slug,
				'post_parent' => $parent_id, 'post_excerpt' => $def['excerpt'], 'post_content' => '',
			];
			$pid = $existing ? wp_update_post( $post + [ 'ID' => $existing->ID ], true ) : wp_insert_post( $post, true );
			if ( is_wp_error( $pid ) ) { throw new RuntimeException( $pid->get_error_message() ); }
			$elements = w270_resolve( $def['elements'], $assets );
			update_post_meta( $pid, '_elementor_edit_mode', 'builder' );
			update_post_meta( $pid, '_elementor_template_type', 'wp-page' );
			update_post_meta( $pid, '_elementor_version', ELEMENTOR_VERSION );
			update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $elements, JSON_UNESCAPED_UNICODE ) ) );
			update_post_meta( $pid, '_elementor_page_settings', $def['page_settings'] );
			delete_post_meta( $pid, '_elementor_css' );
			echo "page {$slug}: #{$pid} " . get_permalink( $pid ) . "\n";
		} catch ( Throwable $e ) {
			echo "page {$slug}: ERROR " . $e->getMessage() . "\n";
			$GLOBALS['w270_failed'] = true;
		}
	}
}
```

- [ ] **Step 3: Add kit import**

```php
function w270_import_kit() {
	$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
	$typo = fn( $id, $title, $family, $weight, $style = 'normal' ) => [
		'_id' => $id, 'title' => $title, 'typography_typography' => 'custom',
		'typography_font_family' => $family, 'typography_font_weight' => $weight, 'typography_font_style' => $style,
	];
	$kit->update_settings( [
		'system_colors' => [
			[ '_id' => 'primary', 'title' => 'Primary', 'color' => '#1A3A3F' ],
			[ '_id' => 'secondary', 'title' => 'Secondary', 'color' => '#4E757B' ],
			[ '_id' => 'text', 'title' => 'Text', 'color' => '#1A3A3F' ],
			[ '_id' => 'accent', 'title' => 'Accent', 'color' => '#A32222' ],
		],
		'custom_colors' => [
			[ '_id' => 'w270blu', 'title' => 'Pale blue', 'color' => '#A1B6C2' ],
			[ '_id' => 'w270tan', 'title' => 'Taupe', 'color' => '#ADA799' ],
			[ '_id' => 'w270snd', 'title' => 'Light warm grey', 'color' => '#DFE1DE' ],
			[ '_id' => 'w270bkg', 'title' => 'Page background', 'color' => '#F2F1EE' ],
		],
		'system_typography' => [
			$typo( 'primary', 'Primary', 'League Spartan', '600' ),
			$typo( 'secondary', 'Secondary', 'Crimson Text', '400' ),
			$typo( 'text', 'Text', 'Inter Tight', '400' ),
			$typo( 'accent', 'Accent', 'Inter Tight', '600' ),
		],
		'custom_typography' => [ $typo( 'w270srf', 'Editorial serif italic', 'Crimson Text', '400', 'italic' ) ],
		'container_width' => [ 'unit' => 'px', 'size' => 1400, 'sizes' => [] ],
		'container_padding' => [ 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ],
		'space_between_widgets' => [ 'unit' => 'px', 'size' => 0, 'column' => '0', 'row' => '0', 'isLinked' => true ],
	] );
	echo "kit: ok\n";
}
```

- [ ] **Step 4: Make the importer exit non-zero on failure**

At the end of `w270_main`, after `clear_cache()`, add:

```php
	if ( ! empty( $GLOBALS['w270_failed'] ) ) { fwrite( STDERR, "IMPORT FAILED\n" ); exit( 1 ); }
```

- [ ] **Step 5: Run the full import**

```bash
"$PHP" -c "$INI" wordpress/build/import.php --all; echo "exit $?"
```
Expected: 7 `media …: #N` lines, 17 `page …: #N http://270-west.local/…` lines in parent-first order, 4 `menu …: ok`, `kit: ok`, `settings: ok`, `exit 0`. Re-run once more and confirm it prints `exists` for media and the same page IDs (idempotent).

- [ ] **Step 6: Smoke-test the URLs**

```bash
for p in / /services/ /services/claims/ /services/appeals/ /services/reassessment/ /services/support/ /how-it-works/ /about/ /resources/ /resources/vac-benefits-programs-guide/ /contact/ /faq/ /eligibility/ /book-a-consult/ /privacy/ /terms/ /accessibility/; do printf '%-45s %s\n' "$p" "$(curl -s -o /dev/null -w '%{http_code}' http://270-west.local$p)"; done
curl -s http://270-west.local/ | grep -c 'class="e-con'
curl -s http://270-west.local/ | grep -o '<h1[^>]*>[^<]*' | head -2
```
Expected: every path `200`; the home page has many `e-con` containers and an `<h1 class="elementor-heading-title …">Your Service Deserves`.

- [ ] **Step 7: Commit**

```bash
git add wordpress/build/import.php
git commit -m "Importer: media, Elementor pages, kit globals"
```

---

### Task 8: Automated checks — `render-check.php` and `check-coverage.py`

**Files:**
- Create: `wordpress/build/render-check.php`
- Create: `wordpress/build/check-coverage.py`

- [ ] **Step 1: Write `render-check.php`**

```php
<?php
// Renders every Elementor page through Elementor's PHP API; fails on any warning/notice or empty output.
$site = getenv( 'W270_SITE' ) ?: '/Users/Bryce/Local Sites/270-west/app/public';
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = '270-west.local';
require $site . '/wp-load.php';
wp_set_current_user( 1 );
set_error_handler( function ( $no, $str, $file, $line ) {
	if ( $no & ( E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_USER_ERROR ) ) { throw new ErrorException( $str, 0, $no, $file, $line ); }
	return false;
} );
$pages = get_posts( [ 'post_type' => 'page', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'meta_key' => '_elementor_edit_mode', 'meta_value' => 'builder' ] );
$fail = false;
foreach ( $pages as $p ) {
	try {
		$doc  = \Elementor\Plugin::$instance->documents->get( $p->ID );
		$data = $doc->get_elements_data();
		$html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $p->ID );
		if ( strlen( $html ) < 500 ) { throw new RuntimeException( 'rendered only ' . strlen( $html ) . ' bytes' ); }
		if ( str_contains( $html, '__W270_ASSETS__' ) || str_contains( $html, '__media__' ) ) { throw new RuntimeException( 'unresolved placeholder in output' ); }
		printf( "OK   %-30s %2d sections %7d bytes\n", $p->post_name, count( $data ), strlen( $html ) );
	} catch ( Throwable $e ) {
		$fail = true;
		printf( "FAIL %-30s %s @ %s:%d\n", $p->post_name, $e->getMessage(), basename( $e->getFile() ), $e->getLine() );
	}
}
exit( $fail ? 1 : 0 );
```

- [ ] **Step 2: Write `check-coverage.py`**

```python
"""Compares the visible text of each WordPress page with its prototype page.
Usage: python3 check-coverage.py [slug ...]   (run from wordpress/build; W270_URL overrides the site URL)"""
import difflib
import html
import os
import re
import sys
import urllib.request

from pages import PAGES, path_for

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
BASE = os.environ.get('W270_URL', 'http://270-west.local')
QUOTES = {'’': "'", '‘': "'", '“': '"', '”': '"', '—': '-', '–': '-', '…': '...', '\xa0': ' '}


def norm(s):
    s = re.sub(r'<(script|style|svg|noscript)\b[\s\S]*?</\1>', ' ', s)
    s = re.sub(r'<[^>]+>', ' ', s)
    s = html.unescape(s)
    for a, b in QUOTES.items():
        s = s.replace(a, b)
    return s.split()


def prototype_words(src_file):
    with open(os.path.join(ROOT, src_file), encoding='utf-8') as f:
        s = f.read()
    s = s[s.index('<body'):]
    s = re.sub(r'<header\b[\s\S]*?</header>', ' ', s, count=1)
    s = re.sub(r'<nav class="mobile-nav">[\s\S]*?</nav>', ' ', s, count=1)
    s = re.sub(r'<footer\b[\s\S]*?</footer>', ' ', s, count=1)
    return norm(s)


def wp_words(path):
    with urllib.request.urlopen(BASE + path, timeout=30) as r:
        s = r.read().decode('utf-8')
    m = re.search(r'<main\b[\s\S]*?</main>', s)
    if not m:
        raise RuntimeError(f'no <main> in {path}')
    return norm(m.group(0))


def main(argv):
    only = set(argv)
    failed = False
    for src, slug, _ in PAGES:
        if only and slug not in only:
            continue
        a, b = prototype_words(src), wp_words(path_for(slug))
        if a == b:
            print(f'OK   {slug:30s} {len(a):5d} words')
            continue
        failed = True
        print(f'DIFF {slug:30s} prototype {len(a)} words, wordpress {len(b)} words')
        for line in difflib.unified_diff(a, b, 'prototype', 'wordpress', n=2, lineterm=''):
            print('   ', line)
    sys.exit(1 if failed else 0)


if __name__ == '__main__':
    main(sys.argv[1:])
```

- [ ] **Step 3: Run both checks and fix generator gaps until they pass**

```bash
"$PHP" -c "$INI" wordpress/build/render-check.php; echo "exit $?"
cd wordpress/build && python3 check-coverage.py; echo "exit $?"; cd -
```
Expected: 17 `OK` lines from each. For any `DIFF`, the words present in `prototype` but missing in `wordpress` identify markup the generator dropped: find the element in the source page, add a classification rule (or a `HTML_TAGS` entry) in `generate.py` with a unit test, regenerate (`python3 generate.py --only <slug>`), re-import (`import.php --pages --only=<slug>`), re-check. Extra words only on the `wordpress` side that come from Elementor itself (none expected inside `<main>`) go on the `QUOTES`-style ignore list only if they are not page content.

- [ ] **Step 4: Commit**

```bash
git add wordpress/build/render-check.php wordpress/build/check-coverage.py wordpress/build/generate.py wordpress/build/tests/test_generate.py
git commit -m "Add Elementor render check and prototype text-coverage check"
```

---

### Task 9: Bridge stylesheet and visual pass

**Files:**
- Modify: `wordpress/theme/270west/assets/css/elementor-bridge.css`

- [ ] **Step 1: Write the initial bridge**

Design: prototype classes sit on Elementor wrappers. Containers keep the class on the element itself (layout works unchanged). Heading/text wrappers stay as boxes and their inner element inherits everything. Image and HTML widget wrappers vanish on the frontend (`display: contents`) so the inner `<img>`/markup is a direct child of the section, exactly as in the prototype. Button wrappers stay as the visual button and the inner `<a>` overlays the whole wrapper so the full button is clickable.

```css
/* ── Elementor bridge ──────────────────────────────────────────────────────
   Prototype classes are carried on Elementor wrappers:
     .e-con.w-con            a prototype block-level wrapper/section
     .elementor-widget.w-heading / .w-text / .w-rich / .w-btn / .w-image / .w-html
   These rules make Elementor's inner elements inherit from the wrapper and neutralise
   Elementor's own spacing/width defaults. Prototype styles.css stays untouched.
   Spike findings (Task 3): <fill in: widget-container present? optimized markup?> */

/* 1. Containers: prototype classes own padding, gap, background, layout. */
.e-con.w-con {
  --padding-top: 0px; --padding-right: 0px; --padding-bottom: 0px; --padding-left: 0px;
  --margin-top: 0px; --margin-bottom: 0px;
  --gap: 0px; --row-gap: 0px; --column-gap: 0px;
  --container-widget-width: auto;
}
.e-con.w-con > .elementor-widget:not(:last-child) { margin-block-end: 0; }
.e-con.w-con > .elementor-widget { width: auto; max-width: none; }
.e-con.w-con > .elementor-widget:not(.elementor-absolute):not(.elementor-fixed) { position: static; }
.e-con.w-con > .elementor-widget > .elementor-widget-container { height: auto; }

/* 2. Headings and single-text widgets: the inner element inherits the wrapper's typography. */
.w-heading .elementor-heading-title,
.w-text > p, .w-text > .elementor-widget-container, .w-text > .elementor-widget-container > p {
  font: inherit; color: inherit; letter-spacing: inherit; text-transform: inherit;
  line-height: inherit; text-align: inherit; text-shadow: inherit; margin: 0; padding: 0;
}
.w-text > .elementor-widget-container > p:not(:last-child) { margin: 0; }
/* Rich text blocks (articles, legal copy): inner elements keep their own margins;
   prototype descendant selectors (.legal-body p, .article-body h2 …) still match. */
.w-rich { font: inherit; }
.w-rich > .elementor-widget-container > :first-child, .w-rich > :first-child { margin-top: 0; }

/* 3. Buttons: wrapper is the visual button; the <a> overlays the whole wrapper. */
.e-con.w-con > .w-btn { align-self: flex-start; position: relative; }
.w-btn .elementor-button-wrapper { display: contents; }
.w-btn .elementor-button { all: unset; font: inherit; color: inherit; cursor: pointer; }
.w-btn .elementor-button::after { content: ""; position: absolute; inset: 0; }
.w-btn .elementor-button-content-wrapper { display: inline; }
.w-btn:focus-within { outline: 2px solid var(--accent); outline-offset: 3px; }

/* 4. Images and HTML widgets: wrappers disappear on the frontend so the inner element is a
   direct child of the prototype container (needed for absolute/overlay/grid children). */
body:not(.elementor-editor-active) .w-image,
body:not(.elementor-editor-active) .w-image > .elementor-widget-container,
body:not(.elementor-editor-active) .w-html,
body:not(.elementor-editor-active) .w-html > .elementor-widget-container { display: contents; }
.w-image img { display: block; }
.elementor-editor-active .w-image { text-align: inherit; }

/* 5. Elementor resets that fight the prototype. */
.elementor img { border-radius: inherit; }
.elementor a { text-decoration: inherit; }
```

- [ ] **Step 2: Deploy and run the automated checks**

```bash
wordpress/build/deploy-theme.sh && "$PHP" -c "$INI" wordpress/build/render-check.php >/dev/null && echo "render ok"
```

- [ ] **Step 3: Visual pass, page by page**

Serve the prototype: `python3 -m http.server 8270` from the repo root (background). For each page in the table below, open both URLs in the in-app browser (`navigate`), screenshot at the pane's desktop width and with `resize_window preset: mobile`, and compare section by section. Every difference is fixed in `elementor-bridge.css` (or, for dropped markup, in `generate.py` + regenerate + re-import), then re-deploy and re-check. Do not edit `styles.css`.

| Page | Prototype | WordPress |
|---|---|---|
| Home | http://localhost:8270/index.html | http://270-west.local/ |
| Services | services.html | /services/ |
| Claims / Appeals / Reassessment / Support | service-*.html | /services/<slug>/ |
| How it works | how-it-works.html | /how-it-works/ |
| About | about.html | /about/ |
| Resources / Article | resources.html, article.html | /resources/, /resources/vac-benefits-programs-guide/ |
| Contact / FAQ | contact.html, faq.html | /contact/, /faq/ |
| Eligibility / Consult | eligibility.html, consult.html | /eligibility/, /book-a-consult/ |
| Privacy / Terms / Accessibility | *.html | /privacy/, /terms/, /accessibility/ |

Known places to look first: row-flex groups of buttons (`.hero-ctas`, `.cta-btns`) — button alignment; `.hero-quote` overlay stack (image, scrim, caption) — order and absolute positioning; grids (`.journey-steps-grid`, `.svc-preview-grid`, `.about-esg-items`) — wrapper widths; the FAQ `<details>` groups; quiz and consult widgets rendering inside HTML widgets; headings with inline `style` (colour, size) — served by `generated.css`.

Also open one page in the Elementor editor (the user is logged in to wp-admin in their own browser; ask them to open `http://270-west.local/wp-admin/post.php?post=<home id>&action=elementor` if the session can't) and confirm sections, headings and buttons are selectable and editable. Do not type credentials.

- [ ] **Step 4: Commit after each page group passes**

```bash
git add wordpress/theme/270west/assets/css/elementor-bridge.css wordpress/build/generate.py wordpress/build/tests/
git commit -m "Bridge CSS: <page group> matches prototype"
```

---

### Task 10: `build.sh`, README, memory

**Files:**
- Create: `wordpress/build/build.sh`
- Create: `wordpress/README.md`
- Modify: `/Users/Bryce/.claude/projects/-Users-Bryce-270-west-redesign/memory/MEMORY.md` + new memory file

- [ ] **Step 1: Write `build.sh`**

```bash
#!/usr/bin/env bash
# Full pipeline: sync assets → unit tests → generate → deploy theme → activate → import → checks.
set -euo pipefail
cd "$(dirname "$0")"
PHP="${W270_PHP:-/Users/Bryce/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php}"
INI="${W270_INI:-/Users/Bryce/Library/Application Support/Local/run/USpouZOL3/conf/php/php.ini}"
./sync-assets.sh
python3 -m unittest discover -s tests -q
python3 generate.py
./deploy-theme.sh
"$PHP" -c "$INI" activate-theme.php
"$PHP" -c "$INI" import.php --all
"$PHP" -c "$INI" render-check.php
python3 check-coverage.py
echo "BUILD OK"
```

- [ ] **Step 2: Run it end to end**

```bash
chmod +x wordpress/build/build.sh && wordpress/build/build.sh 2>&1 | tail -25
```
Expected: ends with `BUILD OK`.

- [ ] **Step 3: Write `wordpress/README.md`**

```markdown
# WordPress / Elementor build

The static prototype at the repo root is the design source. This folder turns it into an
Elementor site on the Local app site **270 West** (`http://270-west.local`).

## One-shot build
    wordpress/build/build.sh

Steps it runs: `sync-assets.sh` (copy css/js/img into the theme) → unit tests → `generate.py`
(prototype HTML → `build/out/<slug>.json` Elementor trees + `generated.css`) → `deploy-theme.sh`
(rsync theme into the site) → `activate-theme.php` → `import.php --all` (media, pages, menus,
kit, settings) → `render-check.php` → `check-coverage.py`.

Run WordPress scripts with Local's PHP:
    PHP="/Users/Bryce/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php"
    INI="/Users/Bryce/Library/Application Support/Local/run/USpouZOL3/conf/php/php.ini"
    "$PHP" -c "$INI" wordpress/build/import.php --pages --only=home

## What lives where
- `theme/270west` — Hello Elementor child theme: header/footer (WP menus), prototype stylesheet,
  `elementor-bridge.css` (Elementor ↔ prototype adapter), `main.js`.
- Pages are Elementor documents: one Container per section, native Heading/Text/Button/Image
  widgets, HTML widgets for SVG decoration, the eligibility quiz, consult scheduler, FAQ
  accordions and the contact form.
- Re-running the importer updates pages in place (keyed by slug). Edits made in Elementor are
  overwritten by a re-import — once editing starts in WordPress, stop re-importing that page.

## Follow-ups
- Contact form is static markup (needs Elementor Pro Forms or a form plugin).
- Elementor Pro: build header/footer in Theme Builder; the theme parts step aside automatically.
```

- [ ] **Step 4: Save a project memory**

Write `/Users/Bryce/.claude/projects/-Users-Bryce-270-west-redesign/memory/project_wordpress_build.md` (type `project`) recording: the Local site path/URL, that scripts run through Local's PHP with the site's php.ini (no WP-CLI), the Elementor Free + Hello child theme decision, that `wordpress/build/build.sh` regenerates everything and overwrites Elementor edits, and the Pro/forms follow-ups. Add one index line to `MEMORY.md`.

- [ ] **Step 5: Commit**

```bash
git add wordpress/build/build.sh wordpress/README.md
git commit -m "Add build pipeline script and WordPress build README"
```

---

## Self-review

- **Spec coverage:** child theme (T1–2), generator with native widgets (T5), importer with media/pages/menus/front page/kit/options (T2, T7), bridge CSS (T9), main.js adaptation (T6), URL map (T5 `pages.py`), titles/excerpts (T5/T7), idempotency (T7), coverage + render checks (T8), visual pass + editor sanity (T9), docs (T10). Out-of-scope items are not planned.
- **Placeholders:** the only intentional blank is the "Spike findings" comment in the bridge header, which Task 3 Step 2 fills with observed results.
- **Consistency:** marker classes `w-con / w-heading / w-text / w-rich / w-btn / w-image / w-html` are identical in `generate.py` and the bridge; `__W270_ASSETS__` and `__media__` markers match between `generate.py`, `import.php` and `render-check.php`; `w270_page_paths()` in PHP mirrors `pages.py`; flags `--all/--pages/--menus/--kit/--settings/--media/--only=` match between `import.php` and `build.sh`.
