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

/** The sub-type alternation, cached. Rewrite rules are only rebuilt on flush, so calling
 *  get_terms() on every init would cost a query per request and discard the result. */
function w270c_subtype_alternation() {
	$cached = get_option( 'w270c_subtype_alternation' );
	return ( is_string( $cached ) && '' !== $cached ) ? $cached : w270c_refresh_subtype_alternation();
}

function w270c_refresh_subtype_alternation() {
	$slugs = get_terms( [ 'taxonomy' => 'resource_type', 'hide_empty' => false, 'fields' => 'slugs' ] );
	if ( is_wp_error( $slugs ) || ! $slugs ) { $slugs = w270c_subtype_slugs(); }
	// preg_quote's delimiter argument: WordPress matches rewrite rules with '#'. It is escaped
	// by default anyway, but naming it documents that this is deliberate, not an omission.
	$alternation = implode( '|', array_map( fn( $s ) => preg_quote( $s, '#' ), $slugs ) );
	update_option( 'w270c_subtype_alternation', $alternation );
	return $alternation;
}

function w270c_permalink_rules() {
	add_rewrite_rule(
		'^resources/(' . w270c_subtype_alternation() . ')/([^/]+)/?$',
		'index.php?post_type=resource&name=$matches[2]',
		'top'
	);
}
add_action( 'init', 'w270c_permalink_rules', 20 );

// Rewrite rules are served from a cached option, so a sub-type added, renamed or removed in
// wp-admin would otherwise 404 until the next deploy. Rebuild both when the terms change.
foreach ( [ 'created_term', 'edited_term', 'delete_term' ] as $w270c_term_hook ) {
	add_action( $w270c_term_hook, function ( $term_id, $tt_id, $taxonomy ) {
		if ( 'resource_type' !== $taxonomy ) { return; }
		w270c_refresh_subtype_alternation();
		flush_rewrite_rules();
	}, 10, 3 );
}
unset( $w270c_term_hook );

/** Builds the public URL from the post's sub-type term. */
add_filter( 'post_type_link', function ( $link, $post ) {
	if ( 'resource' !== $post->post_type ) { return $link; }
	$terms = get_the_terms( $post, 'resource_type' );
	$term  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
	// A post with no Type term still needs a valid URL, so it falls back to guides — which
	// means it is silently mislabelled rather than obviously broken. render-check.php catches
	// this for anything in the seed; a resource created in wp-admin with no Type would not be.
	$sub = $term ? $term->slug : 'guides';
	return home_url( user_trailingslashit( "resources/{$sub}/{$post->post_name}" ) );
}, 10, 2 );

/**
 * The rule above sits at 'top' and so is tested before WordPress's page rules. If the second
 * segment is not actually a resource slug, hand the request back to normal page routing rather
 * than 404ing — otherwise a child page under an archive path would be unreachable.
 */
add_action( 'parse_request', function ( $wp ) {
	if ( 'resource' !== ( $wp->query_vars['post_type'] ?? '' ) || empty( $wp->query_vars['name'] ) ) { return; }
	if ( get_page_by_path( $wp->query_vars['name'], OBJECT, 'resource' ) ) { return; }
	$wp->query_vars = [ 'pagename' => trim( $wp->request, '/' ) ];
} );
