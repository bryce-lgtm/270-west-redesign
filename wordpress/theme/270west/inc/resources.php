<?php
/**
 * Resource post types: helpers, content filter, stylesheet, legacy redirects.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function w270_resource_types() {
	return function_exists( 'w270c_types' ) ? w270c_types() : [ 'resource' ];
}

/** The resource_type term for a post, or null. */
function w270_subtype( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$terms   = get_the_terms( $post_id, 'resource_type' );
	if ( ! $terms || is_wp_error( $terms ) ) { return null; }
	// resource_type is non-hierarchical, so a post can carry several terms and get_the_terms()
	// returns them in no guaranteed order. Pick the lowest term_id so the label, the TOC gate
	// and the body branches are at least stable and explainable rather than cache-dependent.
	if ( count( $terms ) > 1 ) {
		usort( $terms, fn( $a, $b ) => $a->term_id <=> $b->term_id );
		error_log( sprintf(
			'w270: post %d has %d resource_type terms (%s); rendering as "%s"',
			$post_id, count( $terms ), implode( ', ', wp_list_pluck( $terms, 'slug' ) ), $terms[0]->slug
		) );
	}
	return $terms[0];
}

/** 'guides' | 'checklists' | 'explainers' | 'stories' | 'news' | '' */
function w270_subtype_slug( $post_id = null ) {
	$term = w270_subtype( $post_id );
	return $term ? $term->slug : '';
}

/** ACF field value, or null when ACF is not installed. */
function w270_field( $name, $post_id = null ) {
	if ( function_exists( 'get_field' ) ) {
		$v = get_field( $name, $post_id );
		if ( null !== $v && '' !== $v && false !== $v && [] !== $v ) { return $v; }
	}
	// The importer also writes each field as plain post meta, so the site renders correctly
	// before ACF is installed (and if it is ever deactivated).
	$v = get_post_meta( $post_id ?: get_the_ID(), $name, true );
	return ( '' === $v || [] === $v ) ? null : $v;
}

function w270_type_label( int $post_id, bool $featured = false ) {
	// The plugin owns this rule; the theme only needs a sane answer if it is ever deactivated,
	// in which case no resource content renders anyway.
	return function_exists( 'w270c_type_label' ) ? w270c_type_label( $post_id, $featured ) : 'Resource';
}

/**
 * Reading time in minutes, computed from the body at 200 words a minute (never below 1).
 * Nothing to set in wp-admin: it follows the copy as it is edited.
 */
function w270_read_time( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) { return 0; }
	$words = str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );
	return max( 1, (int) ceil( $words / 200 ) );
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
	// The queried resource's own body, whether rendered by the PHP loop or by an Elementor Pro
	// single template (which runs outside the loop, so in_the_loop() would be false there).
	if ( ! is_singular( w270_resource_types() ) || get_the_ID() !== get_queried_object_id() ) { return $content; }
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
	return is_singular( w270_resource_types() ) || is_page_template( 'template-resource-archive.php' );
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! w270_is_resource_view() ) { return; }
	$dir = get_stylesheet_directory();
	wp_enqueue_style( 'w270-resources', W270_ASSETS . '/css/resources.css', [ 'w270-styles' ], filemtime( $dir . '/assets/css/resources.css' ) );
}, 25 );

// A resource reached under the wrong sub-type folder (e.g. the old /resources/explainers/x/ after it
// moved to Guides) is the same post; send it to its real address so there is one URL per resource.
add_action( 'template_redirect', function () {
	if ( ! is_singular( w270_resource_types() ) || is_preview() ) { return; }
	$want = wp_parse_url( get_permalink( get_queried_object_id() ), PHP_URL_PATH );
	$have = trailingslashit( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) );
	if ( $want && $have !== $want ) { wp_safe_redirect( get_permalink( get_queried_object_id() ), 301 ); exit; }
}, 5 );

// The article used to be a page under /resources/; it is now the featured guide.
add_action( 'template_redirect', function () {
	if ( ! is_404() ) { return; }
	$map  = [ '/resources/vac-benefits-programs-guide/' => 'vac-benefits-programs-guide' ];
	$path = trailingslashit( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) );
	// A resource moved to another sub-type (e.g. /resources/explainers/x/ -> /resources/guides/x/).
	if ( ! isset( $map[ $path ] ) && preg_match( '#^/resources/[a-z-]+/([a-z0-9-]+)/$#', $path, $m ) ) {
		$moved = get_page_by_path( $m[1], OBJECT, w270_resource_types() );
		if ( $moved && 'publish' === $moved->post_status ) { wp_redirect( get_permalink( $moved ), 301 ); exit; }
	}
	if ( ! isset( $map[ $path ] ) ) { return; }
	// Send the old URL to the guide while it is published, otherwise to the Guides archive
	// (unapproved guides sit in draft, and a redirect into a 404 helps nobody).
	$post = get_page_by_path( $map[ $path ], OBJECT, 'resource' );
	$to   = ( $post && 'publish' === $post->post_status && ! w270_feature_hidden( 'library' ) ) ? get_permalink( $post ) : home_url( w270_feature_hidden( 'library' ) ? '/resources/' : '/resources/guides/' );
	wp_redirect( $to, 301 ); exit;
} );

/* ── Switched-off sections ────────────────────────────────────────────────────
 * wordpress/build/pages.py HIDDEN_FEATURES is the one switch; the importer copies it into the
 * w270_hidden_features option. While "library" is hidden (Guides and News, Oct 2026), their menu
 * items, the story rail's Guides box and their sitemap entries are left out, and their archives and
 * posts send visitors to /resources/ with a temporary redirect, so the URLs come back unchanged when
 * the sections are switched on again.
 */
function w270_feature_hidden( $feature ) {
	return in_array( $feature, (array) get_option( 'w270_hidden_features', [] ), true );
}

/** Resource sub-types that belong to the Guides and News sections. */
function w270_library_subtypes() {
	return [ 'guides', 'checklists', 'explainers', 'news' ];
}

add_action( 'template_redirect', function () {
	if ( ! w270_feature_hidden( 'library' ) || is_preview() || current_user_can( 'edit_posts' ) ) { return; }
	$path = trailingslashit( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) );
	$path = preg_replace( '#^/fr/#', '/', $path ); // French URLs carry a /fr/ prefix
	$hidden_archive = (bool) preg_match( '#^/resources/(guides|checklists|explainers|news)/#', $path );
	$hidden_post    = is_singular( w270_resource_types() ) && in_array( w270_subtype_slug( get_queried_object_id() ), w270_library_subtypes(), true );
	if ( $hidden_archive || $hidden_post ) {
		wp_safe_redirect( home_url( '/resources/' ), 302 );
		exit;
	}
}, 4 );

add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( ! w270_feature_hidden( 'library' ) ) { return $args; }
	if ( in_array( $post_type, w270_resource_types(), true ) ) {
		$args['tax_query'] = [ [ 'taxonomy' => 'resource_type', 'field' => 'slug', 'terms' => w270_library_subtypes(), 'operator' => 'NOT IN' ] ];
	}
	if ( 'page' === $post_type ) {
		foreach ( [ 'resources/guides', 'resources/news' ] as $p ) {
			$page = get_page_by_path( $p );
			if ( $page ) { $args['post__not_in'] = array_merge( $args['post__not_in'] ?? [], [ $page->ID ] ); }
		}
	}
	return $args;
}, 20, 2 );
