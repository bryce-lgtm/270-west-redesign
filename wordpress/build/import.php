<?php
/**
 * 270 West importer. Usage:
 *   "$PHP" -c "$INI" wordpress/build/import.php --all
 *   "$PHP" -c "$INI" wordpress/build/import.php --pages --only=home
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; } // never runnable over HTTP
$site = getenv( 'W270_SITE' ) ?: '/Users/Bryce/Local Sites/270-west/app/public';
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = getenv( 'W270_HOST' ) ?: '270-west.local';
require $site . '/wp-load.php';
wp_set_current_user( 1 );

const W270_OUT = __DIR__ . '/out';
// Source photos: the repo's img/ when running from the repo, else the theme's synced copy (server deploys).
define( 'W270_IMG', is_dir( __DIR__ . '/../../img' ) ? __DIR__ . '/../../img' : __DIR__ . '/../assets/img' );

/** Site paths per slug. Mirrors pages.py (parents before children). */
function w270_page_paths() {
	return [
		'home' => '/', 'services' => '/services/', 'claims' => '/services/claims/', 'appeals' => '/services/appeals/',
		'reassessment' => '/services/reassessment/', 'support' => '/services/support/', 'how-it-works' => '/how-it-works/',
		'about' => '/about/', 'resources' => '/resources/',
		'stories' => '/resources/stories/', 'guides' => '/resources/guides/', 'news' => '/resources/news/', 'vac-benefits-programs-guide' => '/resources/vac-benefits-programs-guide/',
		'contact' => '/contact/', 'faq' => '/faq/', 'vac-status-checker' => '/vac-status-checker/', 'book-a-consult' => '/book-a-consult/',
		'privacy' => '/privacy/', 'terms' => '/terms/', 'accessibility' => '/accessibility/',
		// Advertising landing pages: noindex, no site chrome, never linked from a menu.
		'vac-claim-help' => '/vac-claim-help/', 'vac-benefits-simplified' => '/vac-benefits-simplified/',
		'what-to-expect' => '/what-to-expect/',
	];
}

/** Slugs of the advertising landing pages, which the generator marks with "landing": true. */
function w270_landing_slugs() {
	return [ 'vac-claim-help', 'vac-benefits-simplified', 'what-to-expect' ];
}

function w270_page_by_slug( $slug ) {
	$paths = w270_page_paths();
	$path  = trim( $paths[ $slug ] ?? $slug, '/' ) ?: 'home';
	$page  = get_page_by_path( $path, OBJECT, 'page' );
	return $page ?: null;
}

/** Menu definition items: [title, slug, classes, children]. */
function w270_menu_defs() {
	$services_children = [ [ 'Claims', 'claims' ], [ 'Appeals', 'appeals' ], [ 'Reassessment', 'reassessment' ], [ 'Support', 'support' ] ];
	$resources_children = [ [ 'Stories', 'stories' ], [ 'Guides', 'guides' ], [ 'News', 'news' ] ];
	$main = [
		[ 'Services', 'services', '', $services_children ],
		[ 'How It Works', 'how-it-works' ], [ 'About', 'about' ],
		[ 'Resources', 'resources', '', $resources_children ], [ 'Contact', 'contact' ],
		[ 'Book a Consult', 'book-a-consult', 'nav-cta' ],
	];
	return [
		'Primary' => [ 'location' => 'primary', 'items' => $main ],
		'Mobile'  => [ 'location' => 'mobile', 'items' => $main ],
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

/** Replace generator markers: __W270_ASSETS__ and __W270_FORM__ in strings, {"__media__": file} in image settings, site paths in link settings. */
function w270_resolve( array $elements, string $assets ) {
	foreach ( $elements as &$el ) {
		foreach ( $el['settings'] as $k => &$v ) {
			if ( is_string( $v ) ) {
				$v = str_replace( '__W270_ASSETS__', $assets, $v );
				$v = w270_form_shortcodes( $v );
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
			if ( ! empty( $def['landing'] ) ) {
				update_post_meta( $pid, '_w270_landing', 1 );
			} else {
				delete_post_meta( $pid, '_w270_landing' );
			}
			delete_post_meta( $pid, '_elementor_css' );
			echo "page {$slug}: #{$pid} " . get_permalink( $pid ) . "\n";
		} catch ( Throwable $e ) {
			echo "page {$slug}: ERROR " . $e->getMessage() . "\n";
			$GLOBALS['w270_failed'] = true;
		}
	}
}

function w270_import_kit() {
	$kit  = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
	$typo = fn( $id, $title, $family, $weight, $style = 'normal' ) => [
		'_id' => $id, 'title' => $title, 'typography_typography' => 'custom',
		'typography_font_family' => $family, 'typography_font_weight' => $weight, 'typography_font_style' => $style,
	];
	$kit->update_settings( [
		'system_colors' => [
			[ '_id' => 'primary', 'title' => 'Primary', 'color' => '#1A3A3F' ],
			[ '_id' => 'secondary', 'title' => 'Secondary', 'color' => '#4E757B' ],
			[ '_id' => 'text', 'title' => 'Text', 'color' => '#1A3A3F' ],
			[ '_id' => 'accent', 'title' => 'Accent', 'color' => '#A22222' ],
		],
		'custom_colors' => [
			[ '_id' => 'w270blu', 'title' => 'Pale blue', 'color' => '#A0B5C1' ],
			[ '_id' => 'w270tan', 'title' => 'Taupe', 'color' => '#ACA698' ],
			[ '_id' => 'w270snd', 'title' => 'Light warm grey', 'color' => '#DEE0DD' ],
			[ '_id' => 'w270bkg', 'title' => 'Page background', 'color' => '#F2F1EE' ],
		],
		'system_typography' => [
			$typo( 'primary', 'Primary', 'Inter Tight', '500' ),
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

/** Replace __W270_MEDIA__:<file> markers with Media Library URLs. */
function w270_media_urls( $html ) {
	return preg_replace_callback( '/__W270_MEDIA__:([\w.-]+)/', function ( $m ) {
		$id = w270_media_id( $m[1] );
		if ( ! $id ) { throw new RuntimeException( "media not imported: {$m[1]}" ); }
		return wp_get_attachment_url( $id );
	}, $html );
}

/**
 * Imports the team's exported Gravity Forms and records key -> form id in the w270_form_ids
 * option. Forms that already exist are left untouched: they are the client's to edit in wp-admin,
 * unlike our generated pages, so re-running the importer must never clobber their changes.
 */
function w270_import_forms() {
	if ( ! class_exists( 'GFAPI' ) ) {
		echo "forms: Gravity Forms not active — skipped\n";
		return;
	}
	$export = json_decode( file_get_contents( __DIR__ . '/gravity-forms.json' ), true, 512, JSON_THROW_ON_ERROR );
	$by_title = [];
	foreach ( GFAPI::get_forms( null ) as $f ) { $by_title[ $f['title'] ] = (int) $f['id']; }
	$ids = [];
	foreach ( $export['forms'] as $key => $form ) {
		$title = $form['title'];
		if ( isset( $by_title[ $title ] ) ) {
			$ids[ $key ] = $by_title[ $title ];
			echo "form {$key}: #{$ids[$key]} already present, left as-is\n";
			continue;
		}
		unset( $form['id'] );
		$id = GFAPI::add_form( $form );
		if ( is_wp_error( $id ) ) {
			echo "form {$key}: ERROR " . $id->get_error_message() . "\n";
			$GLOBALS['w270_failed'] = true;
			continue;
		}
		$ids[ $key ] = (int) $id;
		echo "form {$key}: #{$id} created ({$title})\n";
	}
	update_option( 'w270_form_ids', $ids );
	w270_import_form_feeds();
}

/**
 * Creates the Creatio webhook feeds transcribed from the production site. Like the forms
 * themselves, an existing feed with the same name is left alone — the endpoint and the Creatio
 * column mapping belong to the client, and a re-run must not overwrite a change they made.
 */
function w270_import_form_feeds() {
	if ( ! class_exists( 'GFAPI' ) ) { return; }
	$file = __DIR__ . '/gravity-forms-feeds.json';
	if ( ! file_exists( $file ) ) { return; }
	$export = json_decode( file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
	$ids    = get_option( 'w270_form_ids', [] );
	foreach ( $export['feeds'] as $key => $def ) {
		if ( empty( $ids[ $key ] ) ) {
			echo "feed {$key}: form not imported — skipped\n";
			continue;
		}
		$form_id = (int) $ids[ $key ];
		$addon   = $def['addon'];
		if ( ! in_array( $addon, array_map( fn( $a ) => $a->get_slug(), GFAddOn::get_registered_addons( true ) ), true ) ) {
			echo "feed {$key}: {$addon} not active — skipped\n";
			$GLOBALS['w270_failed'] = true;
			continue;
		}
		foreach ( GFAPI::get_feeds( null, $form_id, $addon, null ) ?: [] as $existing ) {
			if ( rgars( $existing, 'meta/feedName' ) === $def['meta']['feedName'] ) {
				echo "feed {$key}: #{$existing['id']} already present, left as-is\n";
				continue 2;
			}
		}
		$id = GFAPI::add_feed( $form_id, $def['meta'], $addon );
		if ( is_wp_error( $id ) ) {
			echo "feed {$key}: ERROR " . $id->get_error_message() . "\n";
			$GLOBALS['w270_failed'] = true;
			continue;
		}
		echo "feed {$key}: #{$id} created ({$def['meta']['feedName']} -> " . wp_parse_url( $def['meta']['requestURL'], PHP_URL_HOST ) . ")\n";
	}
}

/** Replace __W270_FORM__:<key> markers with the Gravity Forms shortcode for that form. */
function w270_form_shortcodes( $html ) {
	return preg_replace_callback( '/__W270_FORM__:(\w+)/', function ( $m ) {
		$ids = get_option( 'w270_form_ids', [] );
		if ( empty( $ids[ $m[1] ] ) ) { throw new RuntimeException( "form not imported: {$m[1]} (is Gravity Forms active?)" ); }
		return '[gravityform id="' . (int) $ids[ $m[1] ] . '" title="false" description="false" ajax="true"]';
	}, $html );
}

function w270_import_resources() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$plugin = '270west-content/270west-content.php';
	if ( ! is_plugin_active( $plugin ) ) {
		$r = activate_plugin( $plugin );
		if ( is_wp_error( $r ) ) { echo "resources: cannot activate plugin: " . $r->get_error_message() . "\n"; $GLOBALS['w270_failed'] = true; return; }
		echo "plugin 270west-content: activated\n";
	}
	if ( function_exists( 'w270c_migrate_legacy_resources' ) ) {
		$moved = w270c_migrate_legacy_resources();
		if ( $moved ) { echo "migrated {$moved} legacy resource(s) to the resource post type\n"; }
	}
	$acf = function_exists( 'update_field' );
	if ( ! $acf ) { echo "acf: missing — fields skipped (install ACF Pro and re-run --resources)\n"; }
	$seed    = json_decode( file_get_contents( __DIR__ . '/seed-resources.json' ), true, 512, JSON_THROW_ON_ERROR );
	$article = json_decode( file_get_contents( W270_OUT . '/seed-article.json' ), true, 512, JSON_THROW_ON_ERROR );

	$topics = [];
	foreach ( $seed['topics'] as $name ) {
		$t = term_exists( $name, 'resource_topic' );
		if ( ! $t ) { $t = wp_insert_term( $name, 'resource_topic' ); if ( is_wp_error( $t ) ) { throw new RuntimeException( $t->get_error_message() ); } }
		$topics[ $name ] = (int) $t['term_id'];
	}
	echo 'topics: ' . implode( ', ', array_keys( $topics ) ) . "\n";

	$ids = [];
	foreach ( $seed['resources'] as $r ) {
		try {
			$is_article = $r['slug'] === $article['slug'];
			// The one resource with real body copy takes its summary from the prototype article
			// itself, so the two cannot drift apart; the rest use the seed file.
			$summary    = ( $is_article && ! empty( $article['summary'] ) ) ? $article['summary'] : $r['summary'];
			// The seed's "type" is the sub-type, not a WP post type. Accept both the legacy
			// singular names and the plural term slugs the seed moves to in Task 6.
			$subtype    = W270C_LEGACY_SUBTYPES[ $r['type'] ] ?? $r['type'];
			// Scoped to live statuses and oldest-first: WordPress strips the __trashed suffix
			// when a post is restored, so a revived duplicate could otherwise share this slug
			// and be mistaken for the canonical row.
			$found      = get_posts( [
				'post_type'   => 'resource',
				'name'        => $r['slug'],
				'post_status' => [ 'publish', 'draft', 'pending', 'private' ],
				'numberposts' => 1,
				'orderby'     => 'ID',
				'order'       => 'ASC',
			] );
			$existing   = $found ? $found[0] : null;
			if ( $existing ) {
				// Seed-once: resource content belongs to wp-admin from here on, so a re-run
				// must not overwrite an edit or resurrect a field value someone cleared.
				$ids[ $r['slug'] ] = $existing->ID;
				echo "resource {$r['slug']}: #{$existing->ID} already present, left as-is\n";
				continue;
			}
			$post = [
				'post_type' => 'resource', 'post_status' => 'publish', 'post_title' => $r['title'], 'post_name' => $r['slug'],
				'post_excerpt' => $summary, 'menu_order' => (int) $r['order'],
				'post_content' => $is_article ? w270_media_urls( $article['content'] ) : '<p>Content coming soon.</p>',
			];
			// News entries carry their own publication date; everything else uses "now".
			if ( ! empty( $r['date'] ) ) { $post['post_date'] = $r['date'] . ' 09:00:00'; }
			$pid = wp_insert_post( $post, true );
			if ( is_wp_error( $pid ) ) { throw new RuntimeException( $pid->get_error_message() ); }
			wp_set_object_terms( $pid, $subtype, 'resource_type', false );
			if ( ! empty( $r['topic'] ) ) { wp_set_object_terms( $pid, [ $topics[ $r['topic'] ] ], 'resource_topic' ); }
			if ( ! empty( $r['news_category'] ) ) { wp_set_object_terms( $pid, $r['news_category'], 'news_category', false ); }
			foreach ( [ 'pull_quote', 'veteran_name', 'veteran_role', 'duration', 'story_number', 'external_link' ] as $k ) {
				if ( isset( $r[ $k ] ) ) { update_post_meta( $pid, $k, $r[ $k ] ); }
			}
			if ( ! empty( $r['image'] ) && ( $mid = w270_media_id( $r['image'] ) ) ) { set_post_thumbnail( $pid, $mid ); }
			// Plain post meta first so the theme renders without ACF; update_field() below
			// overwrites these with ACF's own values (same meta keys) when the plugin is present.
			update_post_meta( $pid, 'summary', $summary );
			if ( isset( $r['read_time'] ) ) { update_post_meta( $pid, 'read_time', (int) $r['read_time'] ); }
			update_post_meta( $pid, 'featured', empty( $r['featured'] ) ? 0 : 1 );
			update_post_meta( $pid, 'seo_h1', $r['seo_h1'] ?? '' );
			if ( 'guide' === $r['type'] ) {
				update_post_meta( $pid, 'show_toc', 1 );
				if ( $is_article ) {
					update_post_meta( $pid, 'callout', [ 'label' => $article['callout_label'], 'text' => $article['callout_text'] ] );
				}
			}
			if ( 'checklist' === $r['type'] && ! empty( $r['items'] ) ) {
				update_post_meta( $pid, 'items', array_map( fn( $i ) => [ 'item' => $i, 'note' => '' ], $r['items'] ) );
			}
			if ( $acf ) {
				update_field( 'field_270w_summary', $summary, $pid );
				if ( isset( $r['read_time'] ) ) { update_field( 'field_270w_read_time', (int) $r['read_time'], $pid ); }
				update_field( 'field_270w_featured', empty( $r['featured'] ) ? 0 : 1, $pid );
				update_field( 'field_270w_seo_h1', $r['seo_h1'] ?? '', $pid );
				if ( 'guide' === $r['type'] ) {
					update_field( 'field_270w_show_toc', 1, $pid );
					if ( $is_article ) {
						update_field( 'field_270w_callout', [ 'field_270w_callout_label' => $article['callout_label'], 'field_270w_callout_text' => $article['callout_text'] ], $pid );
					}
				}
				if ( 'checklist' === $r['type'] && ! empty( $r['items'] ) ) {
					update_field( 'field_270w_items', array_map( fn( $i ) => [ 'field_270w_item' => $i, 'field_270w_item_note' => '' ], $r['items'] ), $pid );
				}
			}
			$ids[ $r['slug'] ] = $pid;
			echo "resource {$r['type']}/{$r['slug']}: #{$pid}\n";
		} catch ( Throwable $e ) {
			echo "resource {$r['slug']}: ERROR " . $e->getMessage() . "\n";
			$GLOBALS['w270_failed'] = true;
		}
	}
	foreach ( $seed['resources'] as $r ) {
		if ( empty( $r['related'] ) || empty( $ids[ $r['slug'] ] ) ) { continue; }
		$related = array_values( array_filter( array_map( fn( $s ) => $ids[ $s ] ?? 0, $r['related'] ) ) );
		update_post_meta( $ids[ $r['slug'] ], 'related_resources', $related );
		if ( $acf ) { update_field( 'field_270w_related', $related, $ids[ $r['slug'] ] ); }
	}

	// /resources/ renders the approved prototype hub (Stories / Guides / News previews) from its
	// generated Elementor content, like every other page. template-resources.php predates that
	// redesign and would replace the content with the older "browse by topic" archive, so the
	// override is cleared rather than assigned. The child archives at /resources/{stories,guides,news}/
	// carry the topic filtering, and the CPT singles keep their own single-*.php templates.
	$page = w270_page_by_slug( 'resources' );
	if ( $page ) {
		delete_post_meta( $page->ID, '_wp_page_template' );
		echo "resources page: prototype hub content\n";
	}
	$old = get_page_by_path( 'resources/vac-benefits-programs-guide', OBJECT, 'page' );
	if ( $old ) { wp_trash_post( $old->ID ); echo "old article page #{$old->ID}: trashed\n"; }
	flush_rewrite_rules();
}

function w270_main( $argv ) {
	$flags = array_fill_keys( array_map( fn( $a ) => explode( '=', ltrim( $a, '-' ) )[0], array_slice( $argv, 1 ) ), true );
	$only  = null;
	foreach ( $argv as $a ) { if ( str_starts_with( $a, '--only=' ) ) { $only = substr( $a, 7 ); } }
	$all = isset( $flags['all'] );
	if ( $all || isset( $flags['media'] ) ) { function_exists( 'w270_import_media' ) && w270_import_media(); }
	if ( $all || isset( $flags['forms'] ) ) { w270_import_forms(); }
	if ( $all || isset( $flags['pages'] ) ) { function_exists( 'w270_import_pages' ) && w270_import_pages( $only ); }
	if ( $all || isset( $flags['menus'] ) ) { w270_import_menus(); }
	if ( $all || isset( $flags['resources'] ) ) { w270_import_resources(); }
	if ( $all || isset( $flags['kit'] ) ) { function_exists( 'w270_import_kit' ) && w270_import_kit(); }
	if ( $all || isset( $flags['settings'] ) ) { w270_import_settings(); }
	if ( class_exists( '\Elementor\Plugin' ) ) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
	if ( ! empty( $GLOBALS['w270_failed'] ) ) { fwrite( STDERR, "IMPORT FAILED\n" ); exit( 1 ); }
}
w270_main( $argv );
