<?php
/**
 * Resource post types: helpers, content filter, stylesheet, legacy redirects.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function w270_resource_types() {
	return function_exists( 'w270c_types' ) ? w270c_types() : [ 'guide', 'checklist', 'explainer' ];
}

/** ACF field value, or null when ACF is not installed. */
function w270_field( $name, $post_id = null ) {
	return function_exists( 'get_field' ) ? get_field( $name, $post_id ) : null;
}

function w270_type_label( $type, $featured = false ) {
	if ( function_exists( 'w270c_type_label' ) ) { return w270c_type_label( $type, $featured ); }
	$labels = [ 'guide' => 'Guide', 'checklist' => 'Checklist', 'explainer' => 'Explainer' ];
	return ( $featured && 'guide' === $type ) ? 'Featured guide' : ( $labels[ $type ] ?? 'Resource' );
}

function w270_read_time( $post_id ) {
	return max( 0, (int) w270_field( 'read_time', $post_id ) );
}

function w270_topic( $post_id ) {
	$terms = get_the_terms( $post_id, 'resource_topic' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/** Related resources: the ACF selection, else up to 3 newest resources in the same topic. */
function w270_related( $post_id ) {
	$ids = w270_field( 'related_resources', $post_id );
	if ( $ids ) { return array_filter( array_map( 'get_post', array_map( 'intval', (array) $ids ) ) ); }
	$topic = w270_topic( $post_id );
	$args  = [ 'post_type' => w270_resource_types(), 'numberposts' => 3, 'post__not_in' => [ $post_id ] ];
	if ( $topic ) { $args['tax_query'] = [ [ 'taxonomy' => 'resource_topic', 'terms' => $topic->term_id ] ]; }
	return get_posts( $args );
}

/** Adds the prototype's article classes and h-N ids to resource content (runs after wpautop). */
function w270_article_classes( $content ) {
	if ( ! is_singular( w270_resource_types() ) || ! in_the_loop() ) { return $content; }
	$i = 0;
	$content = preg_replace_callback( '/<h2\b([^>]*)>/i', function ( $m ) use ( &$i ) {
		$attrs = $m[1];
		if ( ! preg_match( '/\bid=/', $attrs ) ) { $attrs .= ' id="h-' . $i . '"'; }
		$i++;
		if ( ! preg_match( '/\bclass=/', $attrs ) ) { $attrs .= ' class="article-h2"'; }
		return "<h2{$attrs}>";
	}, $content );
	foreach ( [ 'h3' => 'article-h3', 'p' => 'article-p', 'blockquote' => 'article-blockquote' ] as $tag => $class ) {
		$content = preg_replace( '/<' . $tag . '(?![^>]*\bclass=)([^>]*)>/i', '<' . $tag . ' class="' . $class . '"$1>', $content );
	}
	// wpautop wraps blockquote text in <p>; that paragraph must not carry the article-p styling.
	$content = preg_replace_callback( '/<blockquote\b[^>]*>[\s\S]*?<\/blockquote>/i', fn( $m ) => str_replace( ' class="article-p"', '', $m[0] ), $content );
	return $content;
}
add_filter( 'the_content', 'w270_article_classes', 20 );

/** [['id' => 'h-0', 'text' => 'Heading'], …] from filtered content; empty if fewer than two H2s. */
function w270_toc( $content ) {
	preg_match_all( '/<h2\b[^>]*\bid="([^"]+)"[^>]*>([\s\S]*?)<\/h2>/i', $content, $m, PREG_SET_ORDER );
	$toc = array_map( fn( $h ) => [ 'id' => $h[1], 'text' => wp_strip_all_tags( $h[2] ) ], $m );
	return count( $toc ) >= 2 ? $toc : [];
}

function w270_is_resource_view() {
	return is_singular( w270_resource_types() ) || is_page_template( 'template-resources.php' );
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! w270_is_resource_view() ) { return; }
	$dir = get_stylesheet_directory();
	wp_enqueue_style( 'w270-resources', W270_ASSETS . '/css/resources.css', [ 'w270-styles' ], filemtime( $dir . '/assets/css/resources.css' ) );
}, 25 );

// The article used to be a page under /resources/; it is now the featured guide.
add_action( 'template_redirect', function () {
	if ( ! is_404() ) { return; }
	$map  = [ '/resources/vac-benefits-programs-guide/' => '/resources/guides/vac-benefits-programs-guide/' ];
	$path = trailingslashit( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) );
	if ( isset( $map[ $path ] ) ) { wp_redirect( home_url( $map[ $path ] ), 301 ); exit; }
} );
