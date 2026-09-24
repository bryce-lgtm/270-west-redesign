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
	// Built from the live terms, not the W270C_SUBTYPES constant: resource_type is editable in
	// wp-admin, and a sub-type someone adds there must route rather than 404 silently.
	$slugs = get_terms( [ 'taxonomy' => 'resource_type', 'hide_empty' => false, 'fields' => 'slugs' ] );
	if ( is_wp_error( $slugs ) || ! $slugs ) { $slugs = w270c_subtype_slugs(); }
	$subtypes = implode( '|', array_map( 'preg_quote', $slugs ) );
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
