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
		if ( str_contains( $html, '__W270_ASSETS__' ) || str_contains( $html, '__media__' ) || str_contains( $html, '__W270_FORM__' ) ) { throw new RuntimeException( 'unresolved placeholder in output' ); }
		// The FAQ is a native Accordion widget (one item per question), not a raw <details> block.
		if ( 'faq' === $p->post_name ) {
			$n = substr_count( $html, 'class="e-n-accordion-item-title"' );
			if ( $n < 11 ) { throw new RuntimeException( "expected 11+ accordion items, found $n" ); }
			if ( str_contains( $html, 'class="faq-item"' ) ) { throw new RuntimeException( 'raw <details class="faq-item"> still rendered' ); }
		}
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
	if ( ! $seen ) { throw new RuntimeException( 'no published resources found' ); }
	ksort( $seen );
	$summary = implode( ', ', array_map( fn( $k, $v ) => "{$k}={$v}", array_keys( $seen ), $seen ) );
	echo "OK   resource permalinks: {$summary}\n";
} catch ( Throwable $e ) {
	$fail = true;
	printf( "FAIL resource permalinks: %s\n", $e->getMessage() );
}
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
} else {
	echo "WARN acf not installed: field groups not checked\n";
}
// The three archives must be template-driven and list live entries, not a hand-built list.
try {
	foreach ( [ 'resources/guides' => 13, 'resources/stories' => 2, 'resources/news' => 5 ] as $path => $min ) {
		$page = get_page_by_path( $path, OBJECT, 'page' );
		if ( ! $page ) { throw new RuntimeException( "no page at /{$path}/" ); }
		$is_php  = 'template-resource-archive.php' === get_post_meta( $page->ID, '_wp_page_template', true );
		$is_loop = str_contains( (string) get_post_meta( $page->ID, '_elementor_data', true ), '"loop-grid"' );
		if ( ! $is_php && ! $is_loop ) {
			throw new RuntimeException( "/{$path}/ is neither on the archive template nor an Elementor Loop Grid page" );
		}
		$types = array_filter( (array) get_post_meta( $page->ID, 'archive_types', true ) );
		if ( ! $types ) { throw new RuntimeException( "/{$path}/ lists no sub-types" ); }
		// Without a hero H1 the template falls back to the page title, which is the SEO <title>
		// complete with its "| 270 West Consulting" suffix.
		if ( '' === (string) get_post_meta( $page->ID, 'hero_h1', true ) ) {
			throw new RuntimeException( "/{$path}/ has no hero_h1 (H1 would print the SEO title)" );
		}
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
// Elementor Pro Theme Builder: the header and footer templates exist and resolve for their locations.
if ( class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
	try {
		$manager = \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager();
		foreach ( [ 'header' => 'w270-header', 'footer' => 'w270-footer' ] as $location => $slug ) {
			$t = get_posts( [ 'post_type' => 'elementor_library', 'name' => $slug, 'numberposts' => 1 ] );
			if ( ! $t ) { throw new RuntimeException( "no {$location} template ({$slug})" ); }
			$conditions = (array) get_post_meta( $t[0]->ID, '_elementor_conditions', true );
			if ( ! in_array( 'include/general', $conditions, true ) ) { throw new RuntimeException( "{$location} template lacks include/general" ); }
			if ( ! $manager->get_location_templates( $location ) ) { throw new RuntimeException( "nothing resolves for location {$location}" ); }
		}
		foreach ( [ 'w270-single-library', 'w270-single-story', 'w270-single-news', 'w270-card-guide', 'w270-card-story', 'w270-card-news' ] as $slug ) {
			if ( ! get_posts( [ 'post_type' => 'elementor_library', 'name' => $slug, 'numberposts' => 1 ] ) ) { throw new RuntimeException( "no template {$slug}" ); }
		}
		echo "OK   theme builder: header, footer, resource singles and cards present\n";
	} catch ( Throwable $e ) {
		$fail = true;
		printf( "FAIL theme builder: %s\n", $e->getMessage() );
	}
} else {
	echo "WARN Elementor Pro not active: header/footer templates not checked\n";
}
exit( $fail ? 1 : 0 );
