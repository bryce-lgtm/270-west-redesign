# Unified Resource Post Type — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the `guide`/`checklist`/`explainer` post types with one `resource` type labelled by a `resource_type` taxonomy, add Stories and News as sub-types, make the three archives list what is published, and hand ownership of resource content to wp-admin.

**Architecture:** The `270west-content` plugin registers one post type and three taxonomies. Permalinks are produced by explicit rewrite rules plus a `post_type_link` filter — not by a `%tag%` in the rewrite slug — so `/resources/guides/<slug>/` is preserved without WP's tag machinery. Existing posts migrate in place via `$wpdb` so post IDs (and therefore ACF meta and related-resource references) survive. The three archives stay WordPress *pages* using one `template-resource-archive.php`, because `/resources/guides/` lists three sub-types filtered by Topic and so cannot be a term archive.

**Tech Stack:** WordPress 6.x, PHP 8.x, ACF Pro (Local JSON), Elementor 4.2.4, Python 3 build scripts, `unittest`.

**Source of truth:** `docs/superpowers/specs/2026-09-24-unified-resource-post-type-design.md`

---

## Verification model

This repo has no PHPUnit. PHP correctness is proven by `wordpress/build/render-check.php`, which runs on every deploy and exits non-zero on failure. So for PHP work, **"write the failing test" means adding an assertion to `render-check.php`** and running the deploy. Python work uses the real `unittest` suite in `wordpress/build/tests/`.

Commands used throughout:

```bash
# Python tests
cd wordpress/build && python3 -m unittest discover -s tests -q

# Full deploy + render-check against SiteGround
./wordpress/build/deploy-siteground.sh

# Prototype-vs-live comparison
cd wordpress/build && W270_URL=https://brycec57.sg-host.com python3 check-coverage.py
```

---

## File Structure

| File | Responsibility |
|---|---|
| `wordpress/plugins/270west-content/270west-content.php` | Registers the `resource` type and the three taxonomies; seeds sub-type terms; migrates legacy rows |
| `wordpress/plugins/270west-content/inc/permalinks.php` | *(new)* Rewrite rules and `post_type_link` for `/resources/<subtype>/<slug>/` |
| `wordpress/plugins/270west-content/inc/migrate.php` | *(new)* One-way, idempotent legacy→`resource` migration |
| `wordpress/theme/270west/inc/resources.php` | Helpers now read the sub-type term instead of `get_post_type()` |
| `wordpress/theme/270west/single-resource.php` | *(new)* Replaces the three `single-*.php` files |
| `wordpress/theme/270west/template-parts/resource/story.php` | *(new)* Story single |
| `wordpress/theme/270west/template-parts/resource/news.php` | *(new)* News single |
| `wordpress/theme/270west/template-resource-archive.php` | *(new)* One template for all three archives |
| `wordpress/theme/270west/template-parts/archive/{library,stories,news}.php` | *(new)* The three card layouts |
| `wordpress/plugins/270west-content/acf-json/*.json` | Field groups, re-pointed and extended |
| `wordpress/build/seed-resources.json` | Gains `stories` and `news` entries |
| `wordpress/build/import.php` | Resource import becomes seed-once; assigns the archive template |
| `wordpress/build/pages.py` | The three archives leave the generated page set |
| `wordpress/build/render-check.php` | Permalink + archive assertions |
| `wordpress/build/check-coverage.py` | Stops comparing the three archives word-for-word |
| `wordpress/build/deploy-siteground.sh` | Stops deleting `acf-json` |

---

## Task 1: Register the resource type and its taxonomies

**Files:**
- Modify: `wordpress/plugins/270west-content/270west-content.php:13-57`
- Test: `wordpress/build/render-check.php`

- [ ] **Step 1: Add the failing assertion to render-check.php**

Insert immediately before the final `if ( $fail )` block:

```php
// Resource model: one post type, five sub-types, taxonomies attached.
try {
	if ( ! post_type_exists( 'resource' ) ) { throw new RuntimeException( 'post type resource not registered' ); }
	foreach ( [ 'guide', 'checklist', 'explainer' ] as $legacy ) {
		if ( post_type_exists( $legacy ) ) { throw new RuntimeException( "legacy post type still registered: {$legacy}" ); }
	}
	foreach ( [ 'resource_type', 'resource_topic', 'news_category' ] as $tax ) {
		if ( ! taxonomy_exists( $tax ) ) { throw new RuntimeException( "taxonomy missing: {$tax}" ); }
	}
	$subtypes = wp_list_pluck( get_terms( [ 'taxonomy' => 'resource_type', 'hide_empty' => false ] ), 'slug' );
	sort( $subtypes );
	$want = [ 'checklists', 'explainers', 'guides', 'news', 'stories' ];
	if ( $subtypes !== $want ) { throw new RuntimeException( 'sub-types are ' . implode( ',', $subtypes ) ); }
	echo "OK   resource model: 1 post type, 5 sub-types, 3 taxonomies\n";
} catch ( Throwable $e ) {
	$fail = true;
	printf( "FAIL resource model: %s\n", $e->getMessage() );
}
```

- [ ] **Step 2: Run it and watch it fail**

```bash
./wordpress/build/deploy-siteground.sh
```

Expected: `FAIL resource model: post type resource not registered`, and the script exits 1.

- [ ] **Step 3: Replace the type registration in the plugin**

Replace lines 13–57 of `270west-content.php` with:

```php
/** resource_type term slug => [singular label, plural label]. Slugs are plural so that
 *  permalinks stay /resources/guides/<slug>/; the names are singular because they render
 *  as the label on a card ("Checklist", "Explainer"). */
const W270C_SUBTYPES = [
	'guides'     => [ 'Guide', 'Guides' ],
	'checklists' => [ 'Checklist', 'Checklists' ],
	'explainers' => [ 'Explainer', 'Explainers' ],
	'stories'    => [ 'Story', 'Stories' ],
	'news'       => [ 'News', 'News' ],
];

const W270C_NEWS_CATEGORIES = [ 'Campaign', 'Community', 'Sponsorship', 'New guide', 'Team' ];

/** The single post type. Kept as a function so callers never hard-code the string. */
function w270c_types() {
	return [ 'resource' ];
}

function w270c_subtype_slugs() {
	return array_keys( W270C_SUBTYPES );
}

function w270c_register() {
	register_post_type( 'resource', [
		'labels' => [
			'name' => 'Resources', 'singular_name' => 'Resource', 'add_new_item' => 'Add New Resource',
			'edit_item' => 'Edit Resource', 'new_item' => 'New Resource', 'view_item' => 'View Resource',
			'search_items' => 'Search Resources', 'not_found' => 'No resources found', 'all_items' => 'All Resources',
		],
		'public'        => true,
		'show_in_rest'  => true,
		'has_archive'   => false,
		// Permalinks are built by inc/permalinks.php; WP must not invent its own rules.
		'rewrite'       => false,
		'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ],
		'menu_icon'     => 'dashicons-book',
		'menu_position' => 21,
	] );

	register_taxonomy( 'resource_type', 'resource', [
		'labels' => [ 'name' => 'Types', 'singular_name' => 'Type', 'add_new_item' => 'Add New Type', 'edit_item' => 'Edit Type', 'search_items' => 'Search Types' ],
		'hierarchical'      => false,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => false, // the archives are pages, not term archives
	] );

	register_taxonomy( 'resource_topic', 'resource', [
		'labels' => [ 'name' => 'Topics', 'singular_name' => 'Topic', 'add_new_item' => 'Add New Topic', 'edit_item' => 'Edit Topic', 'search_items' => 'Search Topics' ],
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => [ 'slug' => 'resources/topic', 'with_front' => false ],
	] );

	register_taxonomy( 'news_category', 'resource', [
		'labels' => [ 'name' => 'News categories', 'singular_name' => 'News category', 'add_new_item' => 'Add New News Category', 'edit_item' => 'Edit News Category', 'search_items' => 'Search News Categories' ],
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => false,
		'rewrite'           => false,
	] );

	w270c_seed_terms();
}
add_action( 'init', 'w270c_register' );

/** Creates the fixed sub-type and news-category terms. Idempotent. */
function w270c_seed_terms() {
	foreach ( W270C_SUBTYPES as $slug => [ $singular, $plural ] ) {
		if ( ! term_exists( $slug, 'resource_type' ) ) {
			wp_insert_term( $singular, 'resource_type', [ 'slug' => $slug ] );
		}
	}
	foreach ( W270C_NEWS_CATEGORIES as $name ) {
		if ( ! term_exists( $name, 'news_category' ) ) {
			wp_insert_term( $name, 'news_category' );
		}
	}
}

/** "Guide" / "Checklist" / …, from the post's sub-type term. */
function w270c_type_label( $post_id, $featured = false ) {
	$terms = get_the_terms( $post_id, 'resource_type' );
	$term  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
	if ( ! $term ) { return 'Resource'; }
	return ( $featured && 'guides' === $term->slug ) ? 'Featured guide' : $term->name;
}
```

- [ ] **Step 4: Require the new include files from the plugin bootstrap**

Immediately after the `define( 'W270C_DIR', … );` line, add:

```php
require_once W270C_DIR . 'inc/permalinks.php';
require_once W270C_DIR . 'inc/migrate.php';
```

Create both files as empty stubs for now so the plugin does not fatal:

```bash
mkdir -p wordpress/plugins/270west-content/inc
printf '<?php\nif ( ! defined( "ABSPATH" ) ) { exit; }\n' > wordpress/plugins/270west-content/inc/permalinks.php
printf '<?php\nif ( ! defined( "ABSPATH" ) ) { exit; }\n' > wordpress/plugins/270west-content/inc/migrate.php
```

- [ ] **Step 5: Run the assertion and watch it pass**

```bash
./wordpress/build/deploy-siteground.sh
```

Expected: `OK   resource model: 1 post type, 5 sub-types, 3 taxonomies`.

The 15 existing posts will now be invisible (their post type no longer exists) — that is expected and Task 3 fixes it. Other FAIL lines about resource permalinks are expected at this point.

- [ ] **Step 6: Commit**

```bash
git add wordpress/plugins/270west-content wordpress/build/render-check.php
git commit -m "Resources: one post type with a resource_type sub-type taxonomy"
```

---

## Task 2: Preserve every existing permalink

**Files:**
- Modify: `wordpress/plugins/270west-content/inc/permalinks.php`
- Test: `wordpress/build/render-check.php`

- [ ] **Step 1: Add the failing assertion**

Add to `render-check.php`, after the resource-model block:

```php
// Permalinks: every sub-type keeps its /resources/<subtype>/<slug>/ shape and resolves.
try {
	$seen = [];
	foreach ( get_posts( [ 'post_type' => 'resource', 'numberposts' => -1, 'post_status' => 'publish' ] ) as $p ) {
		$terms = get_the_terms( $p, 'resource_type' );
		$sub   = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '(none)';
		$path  = parse_url( get_permalink( $p ), PHP_URL_PATH );
		$want  = "/resources/{$sub}/{$p->post_name}/";
		if ( $path !== $want ) { throw new RuntimeException( "{$p->post_name}: {$path} != {$want}" ); }
		if ( url_to_postid( get_permalink( $p ) ) !== $p->ID ) { throw new RuntimeException( "{$p->post_name}: permalink does not resolve" ); }
		$seen[ $sub ] = ( $seen[ $sub ] ?? 0 ) + 1;
	}
	ksort( $seen );
	$summary = implode( ', ', array_map( fn( $k, $v ) => "{$k}={$v}", array_keys( $seen ), $seen ) );
	echo "OK   resource permalinks: {$summary}\n";
} catch ( Throwable $e ) {
	$fail = true;
	printf( "FAIL resource permalinks: %s\n", $e->getMessage() );
}
```

- [ ] **Step 2: Run it and watch it fail**

```bash
./wordpress/build/deploy-siteground.sh
```

Expected: `FAIL resource permalinks: …` (no posts yet, or a path mismatch).

- [ ] **Step 3: Implement permalinks.php**

Replace the stub with:

```php
<?php
/**
 * Permalinks for the resource post type: /resources/<subtype>/<slug>/.
 *
 * Explicit rules rather than a %resource_type% tag in the rewrite slug. The tag approach makes
 * WP add a taxonomy query var to the single-post rule, which 404s whenever the term in the URL
 * and the term on the post disagree. These rules match on a fixed alternation instead, and the
 * second path segment keeps them clear of the archive pages at /resources/<subtype>/.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function w270c_permalink_rules() {
	$subtypes = implode( '|', array_map( 'preg_quote', w270c_subtype_slugs() ) );
	add_rewrite_rule(
		'^resources/(' . $subtypes . ')/([^/]+)/?$',
		'index.php?post_type=resource&name=$matches[2]',
		'top'
	);
}
add_action( 'init', 'w270c_permalink_rules', 20 );

/** Builds the public URL from the post's sub-type term. */
add_filter( 'post_type_link', function ( $link, $post ) {
	if ( 'resource' !== $post->post_type ) { return $link; }
	$terms = get_the_terms( $post, 'resource_type' );
	$term  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
	// A post with no Type term still needs a valid URL; guides is the largest sub-type.
	$sub = $term ? $term->slug : 'guides';
	return home_url( user_trailingslashit( "resources/{$sub}/{$post->post_name}" ) );
}, 10, 2 );
```

- [ ] **Step 4: Run and watch it pass**

```bash
./wordpress/build/deploy-siteground.sh
```

Expected: `OK   resource permalinks: …`. If it still fails with "permalink does not resolve", the rewrite rules are stale — `flush_rewrite_rules()` already runs at the end of `w270_import_resources()`.

- [ ] **Step 5: Commit**

```bash
git add wordpress/plugins/270west-content/inc/permalinks.php wordpress/build/render-check.php
git commit -m "Resources: explicit rewrite rules keep every existing resource URL"
```

---

## Task 3: Migrate the 15 existing posts in place

**Files:**
- Modify: `wordpress/plugins/270west-content/inc/migrate.php`
- Test: `wordpress/build/render-check.php` (the Task 2 assertion already covers the result)

- [ ] **Step 1: Implement the migration**

Replace the stub with:

```php
<?php
/**
 * One-way migration from the three legacy post types to `resource` + a sub-type term.
 *
 * Rows are updated with $wpdb rather than wp_update_post() because the legacy types are no
 * longer registered, so WP_Query and the post API cannot see them. Post IDs are preserved,
 * which matters: ACF meta hangs off the ID, and the related_resources field stores IDs.
 *
 * Idempotent — the second run finds no rows and does nothing.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function w270c_migrate_legacy_resources() {
	global $wpdb;
	$map  = [ 'guide' => 'guides', 'checklist' => 'checklists', 'explainer' => 'explainers' ];
	$rows = $wpdb->get_results(
		"SELECT ID, post_type FROM {$wpdb->posts} WHERE post_type IN ('guide','checklist','explainer')"
	);
	if ( ! $rows ) { return 0; }
	foreach ( $rows as $row ) {
		$wpdb->update( $wpdb->posts, [ 'post_type' => 'resource' ], [ 'ID' => (int) $row->ID ] );
		wp_set_object_terms( (int) $row->ID, $map[ $row->post_type ], 'resource_type', false );
		clean_post_cache( (int) $row->ID );
	}
	flush_rewrite_rules();
	return count( $rows );
}
```

- [ ] **Step 2: Call it from the importer, before anything else touches resources**

In `wordpress/build/import.php`, inside `w270_import_resources()`, immediately after the plugin-activation block and before `$acf = function_exists( 'update_field' );`, add:

```php
	if ( function_exists( 'w270c_migrate_legacy_resources' ) ) {
		$moved = w270c_migrate_legacy_resources();
		if ( $moved ) { echo "migrated {$moved} legacy resource(s) to the resource post type\n"; }
	}
```

- [ ] **Step 3: Deploy and confirm the migration reports 15**

```bash
./wordpress/build/deploy-siteground.sh
```

Expected on the first run: `migrated 15 legacy resource(s) to the resource post type`, then
`OK   resource permalinks: checklists=2, explainers=7, guides=6`.

- [ ] **Step 4: Deploy again to prove idempotence**

```bash
./wordpress/build/deploy-siteground.sh
```

Expected: no `migrated …` line, and the same `OK   resource permalinks:` counts.

- [ ] **Step 5: Confirm ACF values and related links survived**

```bash
ssh -p 18765 u3253-6bupzx4ihm7m@giowm1228.siteground.biz "cd /home/customer/www/brycec57.sg-host.com/public_html && wp eval '
\$g = get_page_by_path(\"vac-benefits-programs-guide\", OBJECT, \"resource\");
printf(\"read_time=%s callout=%s related=%d topic=%s\n\",
  get_field(\"read_time\", \$g->ID),
  is_array(get_field(\"callout\", \$g->ID)) ? \"yes\" : \"no\",
  count((array) get_field(\"related_resources\", \$g->ID)),
  implode(\",\", wp_get_object_terms(\$g->ID, \"resource_topic\", [\"fields\"=>\"names\"])));
'"
```

Expected: `read_time=18 callout=yes related=3 topic=VAC eligibility & programs`.

- [ ] **Step 6: Commit**

```bash
git add wordpress/plugins/270west-content/inc/migrate.php wordpress/build/import.php
git commit -m "Resources: migrate the legacy post types in place, preserving IDs"
```

---

## Task 4: Point the theme helpers at the sub-type term

**Files:**
- Modify: `wordpress/theme/270west/inc/resources.php:7-9,23-27,75-77`
- Modify: `wordpress/theme/270west/template-parts/resource/card.php:5-10`
- Modify: `wordpress/theme/270west/template-parts/resource/single.php:6`

- [ ] **Step 1: Replace the two helpers in `inc/resources.php`**

Replace lines 7–9 with:

```php
function w270_resource_types() {
	return function_exists( 'w270c_types' ) ? w270c_types() : [ 'resource' ];
}

/** The resource_type term for a post, or null. */
function w270_subtype( $post_id = null ) {
	$terms = get_the_terms( $post_id ?: get_the_ID(), 'resource_type' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/** 'guides' | 'checklists' | 'explainers' | 'stories' | 'news' | '' */
function w270_subtype_slug( $post_id = null ) {
	$term = w270_subtype( $post_id );
	return $term ? $term->slug : '';
}
```

Replace lines 23–27 (the old `w270_type_label`) with:

```php
function w270_type_label( $post_id, $featured = false ) {
	if ( function_exists( 'w270c_type_label' ) ) { return w270c_type_label( $post_id, $featured ); }
	$term = w270_subtype( $post_id );
	if ( ! $term ) { return 'Resource'; }
	return ( $featured && 'guides' === $term->slug ) ? 'Featured guide' : $term->name;
}
```

Replace `w270_is_resource_view()` (lines 75–77) with:

```php
function w270_is_resource_view() {
	return is_singular( w270_resource_types() ) || is_page_template( 'template-resource-archive.php' );
}
```

- [ ] **Step 2: Update `card.php` to pass the post, not the post type**

Replace lines 5–10 with:

```php
$sub     = w270_subtype_slug( $p->ID );
$minutes = w270_read_time( $p->ID );
$cls     = $main ? 'resource-card-main' : 'resource-card-sm';
$tag     = $main
	? w270_type_label( $p->ID, true ) . ( $minutes ? " · {$minutes} min read" : '' )
	: w270_type_label( $p->ID ) . ( $minutes ? " · {$minutes} min" : '' );
```

Then replace the two later uses of `$type` in the same file:

```php
			<div class="resource-card-main-link">Read<?php echo 'guides' === $sub ? ' guide' : ''; ?> →</div>
```

- [ ] **Step 3: Update `single.php` line 6 to use the sub-type**

```php
$toc      = ( 'guides' === w270_subtype_slug() && $show_toc ) ? w270_toc( $content ) : [];
```

- [ ] **Step 4: Deploy and confirm the existing singles still render**

```bash
./wordpress/build/deploy-siteground.sh
curl -s https://brycec57.sg-host.com/resources/guides/how-vac-appeals-work/ | grep -c 'resource-card-sm\|article-h2'
```

Expected: the deploy prints `OK   resource permalinks: …` and the curl prints a non-zero count.

- [ ] **Step 5: Commit**

```bash
git add wordpress/theme/270west/inc/resources.php wordpress/theme/270west/template-parts/resource/
git commit -m "Resources: theme helpers read the sub-type term instead of the post type"
```

---

## Task 5: One single template, plus Story and News layouts

**Files:**
- Create: `wordpress/theme/270west/single-resource.php`
- Create: `wordpress/theme/270west/template-parts/resource/story.php`
- Create: `wordpress/theme/270west/template-parts/resource/news.php`
- Modify: `wordpress/theme/270west/template-parts/resource/single.php`
- Delete: `single-guide.php`, `single-checklist.php`, `single-explainer.php`

- [ ] **Step 1: Create `single-resource.php`**

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) {
	the_post();
	get_template_part( 'template-parts/resource/single' );
}
get_footer();
```

- [ ] **Step 2: Branch on the sub-type in `template-parts/resource/single.php`**

Replace the whole file with:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$sub = w270_subtype_slug();

// Stories and News have their own layouts; guides, checklists and explainers share the
// article layout they have always used.
if ( 'stories' === $sub ) { get_template_part( 'template-parts/resource/story' ); return; }
if ( 'news' === $sub )    { get_template_part( 'template-parts/resource/news' );  return; }

$content  = apply_filters( 'the_content', get_the_content() );
$show_toc = w270_field( 'show_toc' );
$show_toc = null === $show_toc ? true : (bool) $show_toc;
$toc      = ( 'guides' === $sub && $show_toc ) ? w270_toc( $content ) : [];
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<?php get_template_part( 'template-parts/resource/hero' ); ?>
	<section class="article-layout">
		<?php get_template_part( 'template-parts/resource/toc', null, [ 'toc' => $toc ] ); ?>
		<?php get_template_part( 'template-parts/resource/body', null, [ 'content' => $content ] ); ?>
		<?php get_template_part( 'template-parts/resource/rail' ); ?>
	</section>
</main>
```

- [ ] **Step 3: Create `template-parts/resource/story.php`**

Built from `story.html`: serif headline, pull-quote, attribution, video, then the body.

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$quote    = w270_field( 'pull_quote' );
$name     = w270_field( 'veteran_name' );
$role     = w270_field( 'veteran_role' ) ?: 'Canadian Armed Forces Veteran';
$video    = w270_field( 'video_url' );
$duration = w270_field( 'duration' );
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<section class="page-hero">
		<div class="page-hero-kicker"><span class="page-hero-kicker-rule"></span>Veteran story</div>
		<h1 class="page-hero-h1"><?php echo esc_html( get_the_title() ); ?></h1>
	</section>
	<section class="story-single">
		<?php if ( $video ) : ?>
			<div class="story-single-video">
				<iframe src="<?php echo esc_url( $video ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>"
					loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"
					allowfullscreen></iframe>
			</div>
		<?php elseif ( has_post_thumbnail() ) : ?>
			<div class="photo story-single-photo"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>
		<?php if ( $quote ) : ?>
			<blockquote class="story-single-quote"><?php echo esc_html( $quote ); ?></blockquote>
		<?php endif; ?>
		<?php if ( $name ) : ?>
			<div class="story-single-cite">
				<span class="story-card-name"><?php echo esc_html( $name ); ?></span>
				<span class="story-card-role"><?php echo esc_html( $role ); ?><?php echo $duration ? ' · ' . esc_html( $duration ) . ' min' : ''; ?></span>
			</div>
		<?php endif; ?>
		<div class="article-body"><?php echo apply_filters( 'the_content', get_the_content() ); ?></div>
	</section>
</main>
```

- [ ] **Step 4: Create `template-parts/resource/news.php`**

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cats = wp_get_object_terms( get_the_ID(), 'news_category', [ 'fields' => 'names' ] );
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<section class="page-hero">
		<div class="page-hero-kicker"><span class="page-hero-kicker-rule"></span>News</div>
		<h1 class="page-hero-h1"><?php echo esc_html( get_the_title() ); ?></h1>
		<div class="news-entry-meta">
			<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
			<?php foreach ( $cats as $c ) : ?><span class="news-cat"><?php echo esc_html( $c ); ?></span><?php endforeach; ?>
		</div>
	</section>
	<section class="article-layout article-layout-narrow">
		<div class="article-body"><?php echo apply_filters( 'the_content', get_the_content() ); ?></div>
	</section>
</main>
```

- [ ] **Step 5: Delete the three legacy single templates**

```bash
git rm wordpress/theme/270west/single-guide.php \
       wordpress/theme/270west/single-checklist.php \
       wordpress/theme/270west/single-explainer.php
```

- [ ] **Step 6: Deploy and confirm guides still render and nothing 500s**

```bash
./wordpress/build/deploy-siteground.sh
curl -s -o /dev/null -w '%{http_code}\n' https://brycec57.sg-host.com/resources/guides/how-vac-appeals-work/
```

Expected: deploy passes; curl prints `200`.

- [ ] **Step 7: Commit**

```bash
git add -A wordpress/theme/270west
git commit -m "Resources: one single template that branches on the sub-type"
```

---

## Task 6: Seed Stories and News, and make the importer hands-off

**Files:**
- Modify: `wordpress/build/seed-resources.json`
- Modify: `wordpress/build/import.php` (`w270_import_resources`)
- Test: `wordpress/build/tests/test_resources.py:15`

- [ ] **Step 1: Update the failing Python test**

Replace `test_seed_has_15_resources_with_unique_slugs_and_valid_types_topics` with:

```python
    def test_seed_has_22_resources_with_unique_slugs_and_valid_subtypes(self):
        res = self.seed['resources']
        self.assertEqual(len(res), 22)
        self.assertEqual(len({r['slug'] for r in res}), 22)
        valid = ('guides', 'checklists', 'explainers', 'stories', 'news')
        for r in res:
            self.assertIn(r['type'], valid, r['slug'])
            self.assertTrue(r['summary'], r['slug'])
        articles = [r for r in res if r['type'] in ('guides', 'checklists', 'explainers')]
        self.assertEqual(len(articles), 15)
        for r in articles:
            self.assertIn(r['topic'], self.seed['topics'], r['slug'])
            self.assertGreater(r['read_time'], 0)

    def test_stories_and_news_carry_their_own_fields(self):
        by_type = {}
        for r in self.seed['resources']:
            by_type.setdefault(r['type'], []).append(r)
        self.assertEqual(len(by_type['stories']), 2)
        self.assertEqual(len(by_type['news']), 5)
        for s in by_type['stories']:
            self.assertTrue(s['pull_quote'], s['slug'])
            self.assertTrue(s['veteran_name'], s['slug'])
        cats = {'Campaign', 'Community', 'Sponsorship', 'New guide', 'Team'}
        for n in by_type['news']:
            self.assertIn(n['news_category'], cats, n['slug'])
            self.assertRegex(n['date'], r'^\d{4}-\d{2}-\d{2}$')
```

- [ ] **Step 2: Run it and watch it fail**

```bash
cd wordpress/build && python3 -m unittest discover -s tests -q
```

Expected: FAIL — `22 != 15`, and `KeyError: 'stories'`.

- [ ] **Step 3: Update the seed file**

Change every existing `"type"` value from the singular legacy name to the plural term slug
(`guide`→`guides`, `checklist`→`checklists`, `explainer`→`explainers`), then append the seven
new entries. Take the copy verbatim from `stories.html` and `news.html`:

```json
{ "slug": "sarah-macdonald-story", "type": "stories", "order": 100,
  "title": "Sarah's story: “I left knowing something was finally being done.”",
  "summary": "I came with no expectations. I left knowing something was finally being done.",
  "pull_quote": "I came with no expectations. I left knowing something was finally being done.",
  "veteran_name": "Capt. Sarah Macdonald (Ret’d)", "veteran_role": "Canadian Armed Forces Veteran",
  "duration": 4, "story_number": 1, "image": "veteran-sarah.jpg" },
{ "slug": "daniel-leblanc-story", "type": "stories", "order": 101,
  "title": "Daniel's story: “I had experts handling everything.”",
  "summary": "I had experts handling everything. That was incredibly reassuring.",
  "pull_quote": "I had experts handling everything. That was incredibly reassuring.",
  "veteran_name": "Cpl. Daniel Leblanc (Ret’d)", "veteran_role": "Canadian Armed Forces Veteran",
  "duration": 5, "story_number": 2, "image": "veteran-daniel.jpg" },

{ "slug": "in-service-of-your-story-campaign", "type": "news", "order": 200,
  "date": "2026-09-14", "news_category": "Campaign",
  "title": "In service of your story: a campaign built around veterans’ own voices",
  "summary": "Over the coming months we’re sharing video stories from veterans across Canada. Here’s why we’re doing it, and how to take part." },
{ "slug": "where-to-find-us-remembrance-season", "type": "news", "order": 201,
  "date": "2026-09-02", "news_category": "Community",
  "title": "Where to find us this Remembrance season",
  "summary": "Legion events, family days and wellness gatherings we’re supporting across Nova Scotia this fall." },
{ "slug": "royal-nova-scotia-international-tattoo", "type": "news", "order": 202,
  "date": "2026-08-21", "news_category": "Sponsorship",
  "title": "Back at the Royal Nova Scotia International Tattoo",
  "summary": "A week of conversations with veterans and families at one of the largest indoor shows in the world." },
{ "slug": "plain-language-guide-published", "type": "news", "order": 203,
  "date": "2026-07-30", "news_category": "New guide",
  "title": "A plain-language guide to VAC’s main benefits programs",
  "summary": "Our most-requested explainer, updated for 2026, covering the four programmes veterans ask us about most.",
  "external_link": "/resources/guides/vac-benefits-programs-guide/" },
{ "slug": "advisors-joining-ottawa-valley", "type": "news", "order": 204,
  "date": "2026-07-08", "news_category": "Team",
  "title": "Meet the advisors joining us in the Ottawa Valley",
  "summary": "Two new claims advisors have joined the team, adding capacity for veterans in Petawawa and Pembroke." }
```

Both `veteran-sarah.jpg` and `veteran-daniel.jpg` already exist in `img/` and are synced into
the theme, so the two story thumbnails resolve without new artwork.

- [ ] **Step 4: Run the tests and watch them pass**

```bash
cd wordpress/build && python3 -m unittest discover -s tests -q
```

Expected: `OK`, 22 tests.

- [ ] **Step 5: Make the resource import seed-once**

In `w270_import_resources()`, replace the insert/update block so an existing post is skipped
entirely rather than updated:

```php
			$existing = get_page_by_path( $r['slug'], OBJECT, 'resource' );
			if ( $existing ) {
				// Seed-once: resource content belongs to wp-admin from here on, so a re-run
				// must not overwrite an edit, and must not resurrect a deleted field value.
				$ids[ $r['slug'] ] = $existing->ID;
				echo "resource {$r['slug']}: #{$existing->ID} already present, left as-is\n";
				continue;
			}
			$post = [
				'post_type' => 'resource', 'post_status' => 'publish', 'post_title' => $r['title'],
				'post_name' => $r['slug'], 'post_excerpt' => $summary, 'menu_order' => (int) $r['order'],
				'post_content' => $is_article ? w270_media_urls( $article['content'] ) : '<p>Content coming soon.</p>',
			];
			if ( ! empty( $r['date'] ) ) { $post['post_date'] = $r['date'] . ' 09:00:00'; }
			$pid = wp_insert_post( $post, true );
			if ( is_wp_error( $pid ) ) { throw new RuntimeException( $pid->get_error_message() ); }
			wp_set_object_terms( $pid, $r['type'], 'resource_type', false );
			if ( ! empty( $r['topic'] ) ) { wp_set_object_terms( $pid, [ $topics[ $r['topic'] ] ], 'resource_topic' ); }
			if ( ! empty( $r['news_category'] ) ) { wp_set_object_terms( $pid, $r['news_category'], 'news_category', false ); }
```

Then, still inside the `if ( ! $existing )` path, write the sub-type-specific meta:

```php
			foreach ( [ 'pull_quote', 'veteran_name', 'veteran_role', 'duration', 'story_number', 'external_link' ] as $k ) {
				if ( isset( $r[ $k ] ) ) { update_post_meta( $pid, $k, $r[ $k ] ); }
			}
```

Leave the existing `update_post_meta`/`update_field` calls for `summary`, `read_time`,
`featured`, `seo_h1`, `show_toc`, `callout` and `items` as they are — they now run only for
newly created posts.

- [ ] **Step 6: Deploy and confirm 7 new entries appear and the 15 are untouched**

```bash
./wordpress/build/deploy-siteground.sh
```

Expected: 15 lines reading `already present, left as-is`, and 7 lines reading
`resource stories/…` and `resource news/…`. Then
`OK   resource permalinks: checklists=2, explainers=7, guides=6, news=5, stories=2`.

- [ ] **Step 7: Prove the hands-off rule with a real edit**

```bash
ssh -p 18765 u3253-6bupzx4ihm7m@giowm1228.siteground.biz "cd /home/customer/www/brycec57.sg-host.com/public_html && wp eval '
\$p = get_page_by_path(\"how-vac-appeals-work\", OBJECT, \"resource\");
wp_update_post([\"ID\"=>\$p->ID,\"post_title\"=>\"EDITED IN WP-ADMIN\"]);
echo get_the_title(\$p->ID), \"\n\";'"
./wordpress/build/deploy-siteground.sh >/dev/null
ssh -p 18765 u3253-6bupzx4ihm7m@giowm1228.siteground.biz "cd /home/customer/www/brycec57.sg-host.com/public_html && wp eval '
\$p = get_page_by_path(\"how-vac-appeals-work\", OBJECT, \"resource\");
echo get_the_title(\$p->ID), \"\n\";'"
```

Expected: both echoes print `EDITED IN WP-ADMIN` — the deploy did not revert it. Then restore
the real title with the same `wp eval` pattern.

- [ ] **Step 8: Commit**

```bash
git add wordpress/build/seed-resources.json wordpress/build/import.php wordpress/build/tests/test_resources.py
git commit -m "Resources: seed Stories and News, and make the importer seed-once"
```

---

## Task 7: ACF field groups

**Files:**
- Modify: `wordpress/plugins/270west-content/acf-json/group_270w_resource_details.json`
- Modify: `wordpress/plugins/270west-content/acf-json/group_270w_guide_extras.json`
- Modify: `wordpress/plugins/270west-content/acf-json/group_270w_checklist_extras.json`
- Modify: `wordpress/plugins/270west-content/acf-json/group_270w_resources_landing.json`
- Create: `wordpress/plugins/270west-content/acf-json/group_270w_story_details.json`
- Create: `wordpress/plugins/270west-content/acf-json/group_270w_news_details.json`

- [ ] **Step 1: Add the failing assertion**

Change the existing ACF block in `render-check.php` from `4 === $n` to:

```php
if ( function_exists( 'acf_get_field_groups' ) ) {
	$n = count( acf_get_field_groups() );
	printf( "%s acf field groups: %d\n", 6 === $n ? 'OK  ' : 'FAIL', $n );
	if ( 6 !== $n ) { $fail = true; }
	// A Story must offer the story fields and not the guide fields.
	$story = get_posts( [ 'post_type' => 'resource', 'numberposts' => 1, 'tax_query' => [ [ 'taxonomy' => 'resource_type', 'field' => 'slug', 'terms' => 'stories' ] ] ] );
	if ( $story ) {
		$titles = wp_list_pluck( acf_get_field_groups( [ 'post_id' => $story[0]->ID ] ), 'title' );
		if ( ! in_array( 'Story details', $titles, true ) || in_array( 'Guide extras', $titles, true ) ) {
			$fail = true;
			printf( "FAIL story field groups: %s\n", implode( ', ', $titles ) );
		} else {
			echo "OK   story field groups: " . implode( ', ', $titles ) . "\n";
		}
	}
}
```

- [ ] **Step 2: Run it and watch it fail**

```bash
./wordpress/build/deploy-siteground.sh
```

Expected: `FAIL acf field groups: 4`.

- [ ] **Step 3: Re-point the three existing groups**

In `group_270w_resource_details.json`, replace the whole `location` array with:

```json
 "location": [[{"param": "post_type", "operator": "==", "value": "resource"}]],
```

In `group_270w_guide_extras.json`:

```json
 "location": [[{"param": "post_type", "operator": "==", "value": "resource"},
               {"param": "post_taxonomy", "operator": "==", "value": "resource_type:guides"}]],
```

In `group_270w_checklist_extras.json`:

```json
 "location": [[{"param": "post_type", "operator": "==", "value": "resource"},
               {"param": "post_taxonomy", "operator": "==", "value": "resource_type:checklists"}]],
```

In `group_270w_resources_landing.json`, change the location to the new archive template and
retitle it, so the group stops being orphaned:

```json
 "title": "Archive hero",
 "location": [[{"param": "page_template", "operator": "==", "value": "template-resource-archive.php"}]],
```

- [ ] **Step 4: Create `group_270w_story_details.json`**

```json
{
 "key": "group_270w_story_details",
 "title": "Story details",
 "fields": [
  {"key": "field_270w_pull_quote", "label": "Pull quote", "name": "pull_quote", "type": "textarea", "rows": 3, "required": 1},
  {"key": "field_270w_veteran_name", "label": "Veteran name", "name": "veteran_name", "type": "text", "required": 1},
  {"key": "field_270w_veteran_role", "label": "Rank / branch", "name": "veteran_role", "type": "text", "default_value": "Canadian Armed Forces Veteran"},
  {"key": "field_270w_video_url", "label": "Video embed URL", "name": "video_url", "type": "url", "instructions": "Leave empty to show the featured image instead."},
  {"key": "field_270w_duration", "label": "Duration (minutes)", "name": "duration", "type": "number", "min": 1},
  {"key": "field_270w_story_number", "label": "Story number", "name": "story_number", "type": "number", "min": 1, "instructions": "Shown on the card as \"Story 01\"."}
 ],
 "location": [[{"param": "post_type", "operator": "==", "value": "resource"},
               {"param": "post_taxonomy", "operator": "==", "value": "resource_type:stories"}]],
 "menu_order": 0,
 "active": true
}
```

- [ ] **Step 5: Create `group_270w_news_details.json`**

```json
{
 "key": "group_270w_news_details",
 "title": "News details",
 "fields": [
  {"key": "field_270w_external_link", "label": "External link", "name": "external_link", "type": "url", "instructions": "Optional. When set, the card links here instead of to this entry's own page."}
 ],
 "location": [[{"param": "post_type", "operator": "==", "value": "resource"},
               {"param": "post_taxonomy", "operator": "==", "value": "resource_type:news"}]],
 "menu_order": 0,
 "active": true
}
```

- [ ] **Step 6: Deploy and watch the assertions pass**

```bash
./wordpress/build/deploy-siteground.sh
```

Expected: `OK   acf field groups: 6` and
`OK   story field groups: Resource details, Story details`.

- [ ] **Step 7: Commit**

```bash
git add wordpress/plugins/270west-content/acf-json wordpress/build/render-check.php
git commit -m "Resources: ACF groups keyed on the sub-type, plus Story and News fields"
```

---

## Task 8: The dynamic archive template

**Files:**
- Create: `wordpress/theme/270west/template-resource-archive.php`
- Create: `wordpress/theme/270west/template-parts/archive/library.php`
- Create: `wordpress/theme/270west/template-parts/archive/stories.php`
- Create: `wordpress/theme/270west/template-parts/archive/news.php`
- Delete: `wordpress/theme/270west/template-resources.php`

> **Ordering:** this task builds the template; Task 9 creates the pages that use it and carries
> the assertion that proves the pair works. Nothing here is observable on the site until Task 9
> runs, so the two tasks must land together before the site is considered green.

- [ ] **Step 1: Confirm no page template named "Resource archive" exists yet**

```bash
ssh -p 18765 u3253-6bupzx4ihm7m@giowm1228.siteground.biz "cd /home/customer/www/brycec57.sg-host.com/public_html && wp eval 'print_r(wp_get_theme()->get_page_templates());'"
```

Expected: the list contains `template-resources.php => Resources landing` and **not**
`template-resource-archive.php`.

- [ ] **Step 2: Create `template-resource-archive.php`**

```php
<?php
/**
 * Template Name: Resource archive
 *
 * One template behind /resources/guides/, /resources/stories/ and /resources/news/. Which
 * sub-types a page lists, which vocabulary its filter chips use and which card layout it draws
 * are per-page settings, so a new archive needs no new code.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
the_post();

// ACF's taxonomy field returns term objects; the importer's plain meta stores slugs. Normalise
// to slugs so the tax_query below works whichever supplied the value.
$types = array_filter( array_map(
	fn( $t ) => is_object( $t ) ? $t->slug : (string) $t,
	(array) ( w270_field( 'archive_types' ) ?: [] )
) );
$layout = w270_field( 'archive_layout' ) ?: 'library';
$filter = w270_field( 'archive_filter' ) ?: 'none';
$posts  = $types ? get_posts( [
	'post_type'   => 'resource',
	'numberposts' => -1,
	'post_status' => 'publish',
	'orderby'     => 'news' === $layout ? 'date' : [ 'menu_order' => 'ASC', 'date' => 'DESC' ],
	'order'       => 'DESC',
	'tax_query'   => [ [ 'taxonomy' => 'resource_type', 'field' => 'slug', 'terms' => $types ] ],
] ) : [];
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<section class="page-hero">
		<div class="page-hero-kicker"><span class="page-hero-kicker-rule"></span>Resources</div>
		<h1 class="page-hero-h1"><?php echo esc_html( w270_field( 'hero_h1' ) ?: get_the_title() ); ?></h1>
		<?php if ( $lead = w270_field( 'hero_lead' ) ) : ?><p class="page-hero-lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>
		<?php if ( $sub = w270_field( 'hero_sub' ) ) : ?><p class="page-hero-sub"><?php echo esc_html( $sub ); ?></p><?php endif; ?>
	</section>
	<?php get_template_part( 'template-parts/archive/' . $layout, null, [ 'posts' => $posts, 'filter' => $filter ] ); ?>
</main>
<?php
get_footer();
```

- [ ] **Step 3: Create `template-parts/archive/library.php`**

Mirrors `guides.html`: topic filter chips, then one `guide-card` per resource.

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$posts  = $args['posts'];
$filter = $args['filter'];
$terms  = 'topic' === $filter ? get_terms( [ 'taxonomy' => 'resource_topic', 'hide_empty' => true ] ) : [];
?>
<section class="archive archive-library">
	<?php if ( $terms ) : ?>
		<div class="archive-toolbar">
			<div class="archive-count"><?php echo count( $posts ); ?> resources</div>
			<div class="archive-filters" role="group" aria-label="Filter resources by topic">
				<button type="button" class="filter-chip" data-filter="all" aria-pressed="true">All</button>
				<?php foreach ( $terms as $t ) : ?>
					<button type="button" class="filter-chip" data-filter="<?php echo esc_attr( $t->slug ); ?>" aria-pressed="false"><?php echo esc_html( $t->name ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
	<div class="archive-grid">
		<?php foreach ( $posts as $p ) :
			$topic = w270_topic( $p->ID );
			$mins  = w270_read_time( $p->ID ); ?>
			<a class="guide-card" href="<?php echo esc_url( get_permalink( $p ) ); ?>"
				data-topic="<?php echo esc_attr( $topic ? $topic->slug : '' ); ?>">
				<div class="guide-card-tag"><?php echo esc_html( w270_type_label( $p->ID, (bool) w270_field( 'featured', $p->ID ) ) ); ?><?php echo $mins ? ' · ' . (int) $mins . ' min read' : ''; ?></div>
				<h3 class="guide-card-h"><?php echo esc_html( get_the_title( $p ) ); ?></h3>
				<p class="guide-card-p"><?php echo esc_html( w270_field( 'summary', $p->ID ) ?: get_the_excerpt( $p ) ); ?></p>
				<span class="guide-card-link">Read →</span>
			</a>
		<?php endforeach; ?>
	</div>
	<p class="archive-empty" hidden>Nothing in that topic yet. <button type="button" class="link-button" data-filter="all">Show all</button></p>
</section>
```

- [ ] **Step 4: Create `template-parts/archive/stories.php`**

Mirrors the `story-card` markup captured from `stories.html`.

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$posts = $args['posts'];
?>
<section class="archive archive-stories">
	<div class="story-grid">
		<?php foreach ( $posts as $p ) :
			$num   = (int) w270_field( 'story_number', $p->ID );
			$mins  = (int) w270_field( 'duration', $p->ID );
			$quote = w270_field( 'pull_quote', $p->ID );
			$name  = w270_field( 'veteran_name', $p->ID );
			$role  = w270_field( 'veteran_role', $p->ID ) ?: 'Canadian Armed Forces Veteran';
			$first = $name ? preg_replace( '/\s*\(.*$/', '', trim( explode( ' ', $name )[ count( explode( ' ', $name ) ) - 2 ] ?? $name ) ) : ''; ?>
			<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="story-card">
				<div class="story-card-media">
					<?php echo get_the_post_thumbnail( $p, 'large', [ 'loading' => 'lazy', 'decoding' => 'async' ] ); ?>
					<span class="story-card-play" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
				</div>
				<div class="story-card-body">
					<div class="story-card-meta">Story <?php echo esc_html( str_pad( (string) $num, 2, '0', STR_PAD_LEFT ) ); ?> · Video<?php echo $mins ? ' · ' . $mins . ' min' : ''; ?></div>
					<?php if ( $quote ) : ?><blockquote class="story-card-quote"><?php echo esc_html( $quote ); ?></blockquote><?php endif; ?>
					<div class="story-card-cite">
						<span class="story-card-name"><?php echo esc_html( $name ); ?></span>
						<span class="story-card-role"><?php echo esc_html( $role ); ?></span>
					</div>
					<span class="story-card-link">Watch<?php echo $first ? ' ' . esc_html( $first ) . "'s" : ''; ?> story →</span>
				</div>
			</a>
		<?php endforeach; ?>
	</div>
</section>
```

- [ ] **Step 5: Create `template-parts/archive/news.php`**

News groups by month, matching `news.html`.

```php
<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$posts = $args['posts'];
$cats  = get_terms( [ 'taxonomy' => 'news_category', 'hide_empty' => true ] );
$byMonth = [];
foreach ( $posts as $p ) { $byMonth[ get_the_date( 'F Y', $p ) ][] = $p; }
?>
<section class="archive archive-news">
	<div class="archive-toolbar">
		<div class="archive-count">Latest updates from the team</div>
		<div class="news-cats" role="group" aria-label="Filter updates by category">
			<button type="button" class="filter-chip" data-filter="all" aria-pressed="true">All updates</button>
			<?php foreach ( $cats as $c ) : ?>
				<button type="button" class="filter-chip" data-filter="<?php echo esc_attr( $c->name ); ?>" aria-pressed="false"><?php echo esc_html( $c->name ); ?></button>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="news-months">
		<?php foreach ( $byMonth as $month => $group ) :
			$id = 'm-' . sanitize_title( $month ); ?>
			<section class="news-month" aria-labelledby="<?php echo esc_attr( $id ); ?>">
				<h2 class="news-month-h" id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $month ); ?></h2>
				<ol class="news-entries">
					<?php foreach ( $group as $p ) :
						$names = wp_get_object_terms( $p->ID, 'news_category', [ 'fields' => 'names' ] );
						$href  = w270_field( 'external_link', $p->ID ) ?: get_permalink( $p ); ?>
						<?php // initArchives() reads the category from the .news-cat text below, so no data attribute is needed. ?>
						<li class="news-entry">
							<article>
								<div class="news-entry-meta">
									<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $p ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y', $p ) ); ?></time>
									<?php foreach ( $names as $n ) : ?><span class="news-cat"><?php echo esc_html( $n ); ?></span><?php endforeach; ?>
								</div>
								<h3 class="news-entry-h"><a href="<?php echo esc_url( $href ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></h3>
								<p class="news-entry-p"><?php echo esc_html( w270_field( 'summary', $p->ID ) ?: get_the_excerpt( $p ) ); ?></p>
								<a class="news-entry-more" href="<?php echo esc_url( $href ); ?>">Read update →</a>
							</article>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endforeach; ?>
	</div>
	<p class="archive-empty" hidden>No updates in that category yet. <button type="button" class="link-button" data-filter="all">Show all updates</button></p>
</section>
```

- [ ] **Step 6: Delete the superseded template**

```bash
git rm wordpress/theme/270west/template-resources.php
```

- [ ] **Step 7: Add the archive settings fields to the hero group**

Append these three fields to `group_270w_resources_landing.json`'s `fields` array:

```json
  {"key": "field_270w_archive_types", "label": "Types listed", "name": "archive_types", "type": "taxonomy",
   "taxonomy": "resource_type", "field_type": "checkbox", "return_format": "object", "save_terms": 0, "add_term": 0},
  {"key": "field_270w_archive_layout", "label": "Card layout", "name": "archive_layout", "type": "select",
   "choices": {"library": "Library cards", "stories": "Story cards", "news": "News list"}, "default_value": "library"},
  {"key": "field_270w_archive_filter", "label": "Filter chips", "name": "archive_filter", "type": "select",
   "choices": {"none": "None", "topic": "Topics", "news_category": "News categories"}, "default_value": "none"}
```

**Note:** ACF's taxonomy field returns term *objects* while the importer writes plain *slugs*,
so the two sources disagree. The template normalises both to slugs (Step 3) rather than relying
on one or the other. `"save_terms": 0` matters: without it ACF would assign the page itself to
those `resource_type` terms, which would make the archive page show up in its own listing.

- [ ] **Step 8: Deploy and confirm the template registers without fatals**

```bash
./wordpress/build/deploy-siteground.sh
ssh -p 18765 u3253-6bupzx4ihm7m@giowm1228.siteground.biz "cd /home/customer/www/brycec57.sg-host.com/public_html && wp eval 'print_r(wp_get_theme()->get_page_templates());'"
```

Expected: the deploy still prints `SITEGROUND DEPLOY OK`, and the template list now contains
`template-resource-archive.php => Resource archive` and no longer contains
`template-resources.php`. The archives themselves still show their old Elementor content —
Task 9 switches them over.

- [ ] **Step 9: Commit**

```bash
git add -A wordpress/theme/270west wordpress/plugins/270west-content/acf-json
git commit -m "Resources: one archive template driving guides, stories and news"
```

---

## Task 9: Take the three archives out of the generated page set

**Files:**
- Modify: `wordpress/build/pages.py:12-14`
- Modify: `wordpress/build/import.php` (`w270_import_resources`)
- Modify: `wordpress/build/check-coverage.py`
- Test: `wordpress/build/tests/test_generate.py`

- [ ] **Step 1: Add the failing render-check assertion for the archives**

This is the assertion Task 8 deliberately deferred: it proves the template and the pages work
as a pair. Add to `render-check.php`:

```php
// The three archives must be template-driven and list live entries, not a hand-built list.
try {
	foreach ( [ 'resources/guides' => 13, 'resources/stories' => 2, 'resources/news' => 5 ] as $path => $min ) {
		$page = get_page_by_path( $path, OBJECT, 'page' );
		if ( ! $page ) { throw new RuntimeException( "no page at /{$path}/" ); }
		if ( 'template-resource-archive.php' !== get_post_meta( $page->ID, '_wp_page_template', true ) ) {
			throw new RuntimeException( "/{$path}/ is not using the archive template" );
		}
		$types = array_filter( (array) get_post_meta( $page->ID, 'archive_types', true ) );
		if ( ! $types ) { throw new RuntimeException( "/{$path}/ lists no sub-types" ); }
		$n = count( get_posts( [
			'post_type' => 'resource', 'numberposts' => -1, 'post_status' => 'publish',
			'tax_query' => [ [ 'taxonomy' => 'resource_type', 'field' => 'slug', 'terms' => $types ] ],
		] ) );
		if ( $n < $min ) { throw new RuntimeException( "/{$path}/ would list {$n}, expected at least {$min}" ); }
	}
	echo "OK   resource archives: guides/stories/news all template-driven\n";
} catch ( Throwable $e ) {
	$fail = true;
	printf( "FAIL resource archives: %s\n", $e->getMessage() );
}
```

- [ ] **Step 2: Run it and watch it fail**

```bash
./wordpress/build/deploy-siteground.sh
```

Expected: `FAIL resource archives: /resources/guides/ is not using the archive template`.

- [ ] **Step 3: Add the failing Python test**

Append to `wordpress/build/tests/test_generate.py`:

```python
    def test_archive_pages_are_not_generated_but_still_link_correctly(self):
        from pages import PAGES, LINK_MAP
        slugs = {slug for _, slug, _ in PAGES}
        for gone in ('stories', 'guides', 'news'):
            self.assertNotIn(gone, slugs, f'{gone} should be template-driven, not generated')
        self.assertEqual(LINK_MAP['stories.html'], '/resources/stories/')
        self.assertEqual(LINK_MAP['guides.html'], '/resources/guides/')
        self.assertEqual(LINK_MAP['news.html'], '/resources/news/')
```

- [ ] **Step 4: Run it and watch it fail**

```bash
cd wordpress/build && python3 -m unittest discover -s tests -q
```

Expected: FAIL — `'stories' should be template-driven, not generated`.

- [ ] **Step 5: Update `pages.py`**

Delete lines 12–14 (the three archive entries) from `PAGES`, then extend `EXTRA_LINKS` so the
prototype's internal links still resolve:

```python
# Prototype files that aren't pages of their own in WordPress.
EXTRA_LINKS = {
    'story.html': '/resources/stories/',
    'stories.html': '/resources/stories/',
    'guides.html': '/resources/guides/',
    'news.html': '/resources/news/',
}
```

- [ ] **Step 6: Run the tests and watch them pass**

```bash
cd wordpress/build && python3 -m unittest discover -s tests -q
```

Expected: `OK`.

- [ ] **Step 7: Create the three archive pages in the importer**

At the end of `w270_import_resources()`, replacing the current `resources page` block:

```php
	// The three archives are template-driven pages. Created once, then left to wp-admin like
	// every other piece of resource content.
	$archives = [
		'stories' => [ 'Stories', 'resources/stories', [ 'stories' ], 'stories', 'none',
			'Veteran stories in their own words' ],
		'guides'  => [ 'Guides', 'resources/guides', [ 'guides', 'checklists', 'explainers' ], 'library', 'topic',
			'VAC guides, checklists and explainers' ],
		'news'    => [ 'News', 'resources/news', [ 'news' ], 'news', 'news_category',
			'News and updates from 270 West' ],
	];
	$parent = w270_page_by_slug( 'resources' );
	foreach ( $archives as $slug => [ $title, $path, $types, $layout, $filter, $hero ] ) {
		$page = get_page_by_path( $path, OBJECT, 'page' );
		if ( ! $page ) {
			$pid = wp_insert_post( [
				'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title,
				'post_name' => $slug, 'post_parent' => $parent ? $parent->ID : 0,
			], true );
			if ( is_wp_error( $pid ) ) { echo "archive {$slug}: ERROR " . $pid->get_error_message() . "\n"; $GLOBALS['w270_failed'] = true; continue; }
			update_post_meta( $pid, 'hero_h1', $hero );
			echo "archive {$slug}: #{$pid} created\n";
		} else {
			$pid = $page->ID;
			echo "archive {$slug}: #{$pid} already present, settings refreshed\n";
		}
		// The template and its wiring are structure, not content, so they are always re-applied.
		update_post_meta( $pid, '_wp_page_template', 'template-resource-archive.php' );
		update_post_meta( $pid, 'archive_types', $types );
		update_post_meta( $pid, 'archive_layout', $layout );
		update_post_meta( $pid, 'archive_filter', $filter );
		delete_post_meta( $pid, '_elementor_data' );
		delete_post_meta( $pid, '_elementor_edit_mode' );
	}
	flush_rewrite_rules();
```

- [ ] **Step 8: Stop check-coverage comparing the three archives**

In `check-coverage.py`, the three pages have already left `PAGES`, so they drop out
automatically. Add a lighter assertion in their place — append to `EXTRA_CHECKS`:

```python
# The archives are template-driven now, so their card lists are dynamic. Assert they render
# a plausible number of entries rather than matching the prototype word for word.
ARCHIVE_MINIMUMS = [('/resources/guides/', 'guide-card', 13),
                    ('/resources/stories/', 'story-card', 2),
                    ('/resources/news/', 'news-entry', 5)]
```

And add this function, calling it from `main()` when `not only`:

```python
def check_archives():
    ok = True
    for path, marker, minimum in ARCHIVE_MINIMUMS:
        n = fetch(path).count(f'class="{marker}"')
        good = n >= minimum
        ok = ok and good
        print(f'{"OK  " if good else "DIFF"} {path:45s} {n:5d} {marker} (expected >= {minimum})')
    return ok
```

- [ ] **Step 9: Deploy and run both checks**

```bash
./wordpress/build/deploy-siteground.sh
cd wordpress/build && W270_URL=https://brycec57.sg-host.com python3 check-coverage.py
```

Expected: `OK   resource archives: …` in the deploy, and three `OK` lines from
`check_archives()` with counts of at least 13 / 2 / 5.

- [ ] **Step 10: Commit**

```bash
git add wordpress/build/pages.py wordpress/build/import.php wordpress/build/check-coverage.py \
        wordpress/build/tests/test_generate.py wordpress/build/render-check.php
git commit -m "Resources: archives become template-driven pages, out of the generated set"
```

---

## Task 10: Stop the deploy clobbering field groups, and sync them

**Files:**
- Modify: `wordpress/build/deploy-siteground.sh:36-38`

- [ ] **Step 1: Exclude acf-json from the plugin rsync**

Replace the plugin rsync line with:

```bash
# acf-json is excluded from --delete: ACF writes field-group edits made in wp-admin back into
# this folder, and those belong to the client. New group files are still copied up.
rsync -a --delete --exclude 'acf-json' "$ROOT/wordpress/plugins/270west-content/" "$PDEST/"
rsync -a "$ROOT/wordpress/plugins/270west-content/acf-json/" "$PDEST/acf-json/"
```

- [ ] **Step 2: Sync the groups into the database so they appear in the Field Groups list**

Add to `w270_import_resources()`, just before the closing `flush_rewrite_rules();`:

```php
	// ACF Local JSON groups are invisible in the Field Groups list until they exist in the
	// database; without this they sit under "Sync available" and look like nothing was installed.
	if ( function_exists( 'acf_get_local_json_files' ) && function_exists( 'acf_import_field_group' ) ) {
		$synced = 0;
		foreach ( acf_get_local_json_files() as $key => $file ) {
			$existing = acf_get_field_group( $key );
			if ( $existing && ! empty( $existing['ID'] ) ) { continue; }
			$group = json_decode( file_get_contents( $file ), true );
			if ( ! $group ) { continue; }
			acf_import_field_group( $group );
			$synced++;
		}
		if ( $synced ) { echo "acf: synced {$synced} field group(s) into the database\n"; }
	}
```

- [ ] **Step 3: Deploy and confirm they are in the database**

```bash
./wordpress/build/deploy-siteground.sh
ssh -p 18765 u3253-6bupzx4ihm7m@giowm1228.siteground.biz "cd /home/customer/www/brycec57.sg-host.com/public_html && wp eval '
printf(\"field groups in the database: %d\n\", count(get_posts([\"post_type\"=>\"acf-field-group\",\"post_status\"=>\"any\",\"numberposts\"=>-1])));'"
```

Expected: `acf: synced 6 field group(s) into the database`, then
`field groups in the database: 6`.

- [ ] **Step 4: Commit**

```bash
git add wordpress/build/deploy-siteground.sh wordpress/build/import.php
git commit -m "Resources: field groups visible in wp-admin and safe from the deploy"
```

---

## Task 11: Full verification and clean-up

- [ ] **Step 1: Run everything**

```bash
cd wordpress/build && python3 -m unittest discover -s tests -q
cd /Users/Bryce/270-west-redesign/.claude/worktrees/distracted-sutherland-766c39
./wordpress/build/deploy-siteground.sh
cd wordpress/build && W270_URL=https://brycec57.sg-host.com python3 check-coverage.py
```

Expected: tests `OK`; deploy prints every `OK` line and `SITEGROUND DEPLOY OK`; coverage shows
all remaining prototype pages `OK` plus the three archive counts.

- [ ] **Step 2: Confirm the admin menu collapsed to one item**

```bash
ssh -p 18765 u3253-6bupzx4ihm7m@giowm1228.siteground.biz "cd /home/customer/www/brycec57.sg-host.com/public_html && wp eval '
foreach ([\"guide\",\"checklist\",\"explainer\",\"resource\"] as \$t) printf(\"%-10s %s\n\", \$t, post_type_exists(\$t) ? \"registered\" : \"-\");
foreach ([\"resource_type\",\"resource_topic\",\"news_category\"] as \$x) printf(\"%-14s %d terms\n\", \$x, count(get_terms([\"taxonomy\"=>\$x,\"hide_empty\"=>false])));'"
```

Expected: only `resource` registered; `resource_type 5 terms`, `resource_topic 3 terms`,
`news_category 5 terms`.

- [ ] **Step 3: Spot-check one URL of each sub-type in a browser**

```
https://brycec57.sg-host.com/resources/guides/how-vac-appeals-work/
https://brycec57.sg-host.com/resources/checklists/vac-application-checklist/
https://brycec57.sg-host.com/resources/explainers/understanding-your-decision-letter/
https://brycec57.sg-host.com/resources/stories/sarah-macdonald-story/
https://brycec57.sg-host.com/resources/news/in-service-of-your-story-campaign/
https://brycec57.sg-host.com/resources/guides/   (13 cards, topic chips)
https://brycec57.sg-host.com/resources/stories/  (2 story cards)
https://brycec57.sg-host.com/resources/news/     (5 entries grouped by month)
```

- [ ] **Step 4: Update the spec's status and commit**

Add a line at the top of the spec noting it is implemented, then:

```bash
git add -A
git commit -m "Resources: verified end to end on SiteGround"
git push
```

---

## Notes and known risks

- **Old resource URLs are unchanged**, so no redirects are needed. If Task 2's assertion ever
  reports a path mismatch, the cause is a post with no Type term — the filter falls back to
  `guides`, which is a valid URL but the wrong one. Assign the term rather than changing the fallback.
- **Story card CSS already exists** in `css/styles.css` (`.story-card*`), as does `.news-entry*`
  and `.guide-card*`; the archive parts reuse those class names deliberately so no new CSS is
  required. `.story-single-*` in Task 5 is new and will need a small block added to
  `wordpress/theme/270west/assets/css/resources.css`.
- **`initArchives()` in `js/main.js` needs no changes.** It filters library cards on
  `dataset.topic` and news entries on the text inside `.news-cat` (`main.js:544`). Task 8
  reproduces both, so the existing filter chips keep working untouched.
- **Seed-once is irreversible in practice.** Once Task 6 ships, fixing resource copy in the repo
  no longer reaches the site. Content changes happen in wp-admin from that point.
