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
		'contact' => '/contact/', 'faq' => '/faq/', 'eligibility' => '/eligibility/', 'book-a-consult' => '/book-a-consult/',
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
		[ 'Check Eligibility', 'eligibility', 'nav-cta' ],
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
