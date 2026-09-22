<?php
/**
 * 270 West — Hello Elementor child theme.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'W270_VERSION', '1.0.0' );
define( 'W270_ASSETS', get_stylesheet_directory_uri() . '/assets' );

require_once get_stylesheet_directory() . '/inc/class-w270-nav-walker.php';
require_once get_stylesheet_directory() . '/inc/resources.php';

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
		'https://fonts.googleapis.com/css2?family=Crimson+Text:ital@0;1&family=Inter+Tight:ital,wght@0,300..600;1,300..500&display=swap',
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
	echo '<link rel="icon" href="' . esc_url( W270_ASSETS . '/img/brand/favicon.svg' ) . '" type="image/svg+xml">' . "\n";
}, 1 );

// Hello's header/footer experiment styles its own dynamic header; ours is the prototype markup.
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'hello-elementor-header-footer' );
}, 100 );

/**
 * Load Elementor's base stylesheets (frontend, widget, atomic base) inside the `elementor` cascade
 * layer, so the prototype stylesheet wins on every property it declares regardless of specificity.
 * Per-element CSS (elementor-post-*) and Google Fonts stay unlayered so editor styling still applies.
 */
add_filter( 'style_loader_tag', function ( $tag, $handle, $href ) {
	if ( is_admin() || ! preg_match( '/^(elementor-frontend|elementor-icons|widget-|base-|e-|swiper)/', $handle ) ) {
		return $tag;
	}
	return '<style id="' . esc_attr( $handle ) . '-css">@import url("' . esc_url( $href ) . '") layer(elementor);</style>' . "\n";
}, 10, 3 );

// <title>: prototype uses an em dash separator, and inner-page titles that already name the
// company ("About 270 West Consulting — …") must not get the site name appended again.
add_filter( 'document_title_separator', fn() => '—' );
add_filter( 'document_title_parts', function ( $parts ) {
	if ( is_singular() && ! empty( $parts['title'] ) && ! empty( $parts['site'] ) && false !== stripos( $parts['title'], $parts['site'] ) ) {
		unset( $parts['site'] );
	}
	return $parts;
} );

// The eligibility checker was renamed to the VAC Status Checker; keep the old paths working
// for anything already pointing at them (ads, printed material, external links).
add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path = trim( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?? '', '/' );
	$moved = [
		'eligibility'         => 'vac-status-checker',
		'eligibility-checker' => 'vac-status-checker',
	];
	if ( isset( $moved[ $path ] ) ) {
		wp_safe_redirect( home_url( '/' . $moved[ $path ] . '/' ), 301 );
		exit;
	}
} );
