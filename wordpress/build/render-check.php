<?php
// Renders every Elementor page through Elementor's PHP API; fails on any warning/notice or empty output.
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; } // never runnable over HTTP
$site = getenv( 'W270_SITE' ) ?: '/Users/Bryce/Local Sites/270-west/app/public';
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = getenv( 'W270_HOST' ) ?: '270-west.local';
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
// Resource posts: permalinks must resolve through the rewrite rules and content must filter cleanly.
$types = function_exists( 'w270c_types' ) ? w270c_types() : [];
foreach ( get_posts( [ 'post_type' => $types ?: 'nonexistent', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ] ) as $p ) {
	try {
		$url = get_permalink( $p );
		if ( url_to_postid( $url ) !== $p->ID ) { throw new RuntimeException( "permalink does not resolve: {$url}" ); }
		$html = apply_filters( 'the_content', $p->post_content );
		if ( str_contains( $html, '__W270_MEDIA__' ) ) { throw new RuntimeException( 'unresolved media marker' ); }
		$terms = wp_get_object_terms( $p->ID, 'resource_topic', [ 'fields' => 'names' ] );
		printf( "OK   %-45s %s topic=%s\n", $p->post_type . '/' . $p->post_name, parse_url( $url, PHP_URL_PATH ), $terms ? $terms[0] : '-' );
	} catch ( Throwable $e ) { $fail = true; printf( "FAIL %s: %s\n", $p->post_name, $e->getMessage() ); }
}
if ( function_exists( 'acf_get_field_groups' ) ) {
	$n = count( acf_get_field_groups() );
	printf( "%s acf field groups: %d\n", 4 === $n ? 'OK  ' : 'FAIL', $n );
	if ( 4 !== $n ) { $fail = true; }
} else {
	echo "WARN acf not installed: field groups not checked\n";
}
exit( $fail ? 1 : 0 );
