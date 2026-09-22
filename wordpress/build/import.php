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

/** Site paths per slug. Mirrors pages.py (parents before children). */
function w270_page_paths() {
	return [
		'home' => '/', 'services' => '/services/', 'claims' => '/services/claims/', 'appeals' => '/services/appeals/',
		'reassessment' => '/services/reassessment/', 'support' => '/services/support/', 'how-it-works' => '/how-it-works/',
		'about' => '/about/', 'resources' => '/resources/', 'vac-benefits-programs-guide' => '/resources/vac-benefits-programs-guide/',
		'contact' => '/contact/', 'faq' => '/faq/', 'vac-status-checker' => '/vac-status-checker/', 'book-a-consult' => '/book-a-consult/',
		'privacy' => '/privacy/', 'terms' => '/terms/', 'accessibility' => '/accessibility/',
	];
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
	$main = [
		[ 'Services', 'services', '', $services_children ],
		[ 'How It Works', 'how-it-works' ], [ 'About', 'about' ], [ 'Resources', 'resources' ], [ 'Contact', 'contact' ],
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
	if ( ! empty( $GLOBALS['w270_failed'] ) ) { fwrite( STDERR, "IMPORT FAILED\n" ); exit( 1 ); }
}
w270_main( $argv );
