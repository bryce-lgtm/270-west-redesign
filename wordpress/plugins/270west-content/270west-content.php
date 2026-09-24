<?php
/**
 * Plugin Name: 270 West Content
 * Description: The Resource post type, its Type/Topic/News category taxonomies and their ACF field groups.
 * Version: 1.0.0
 * Author: 270 West Consulting
 * Text Domain: 270west
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'W270C_DIR', plugin_dir_path( __FILE__ ) );

require_once W270C_DIR . 'inc/permalinks.php';
require_once W270C_DIR . 'inc/migrate.php';

/** resource_type term slug => [singular label, plural label]. Slugs are plural so that
 *  permalinks stay /resources/guides/<slug>/; the names are singular because they render
 *  as the label on a card ("Checklist", "Explainer"). */
const W270C_SUBTYPES = [
	'guides'     => [ 'Guide', 'Guides' ],
	'checklists' => [ 'Checklist', 'Checklists' ],
	'explainers' => [ 'Explainer', 'Explainers' ],
	'stories'    => [ 'Story', 'Stories' ],
	'news'       => [ 'News', 'News' ],
];

/** Legacy post-type name => sub-type term slug. Lets the importer read a seed file written
 *  before the types were unified; harmless once the seed uses term slugs directly. */
const W270C_LEGACY_SUBTYPES = [ 'guide' => 'guides', 'checklist' => 'checklists', 'explainer' => 'explainers' ];

const W270C_NEWS_CATEGORIES = [ 'Campaign', 'Community', 'Sponsorship', 'New guide', 'Team' ];

const W270C_TERMS_VERSION = 1;

/** The single post type slug. w270c_register() owns the literal; every other caller
 *  should go through this helper instead of repeating the string. */
function w270c_types() {
	return [ 'resource' ];
}

function w270c_subtype_slugs() {
	return array_keys( W270C_SUBTYPES );
}

function w270c_register() {
	register_post_type( 'resource', [
		'labels' => [
			'name' => 'Resources', 'singular_name' => 'Resource', 'add_new_item' => 'Add New Resource',
			'edit_item' => 'Edit Resource', 'new_item' => 'New Resource', 'view_item' => 'View Resource',
			'search_items' => 'Search Resources', 'not_found' => 'No resources found', 'all_items' => 'All Resources',
		],
		'public'        => true,
		'show_in_rest'  => true,
		'has_archive'   => false,
		// Permalinks are built by inc/permalinks.php; WP must not invent its own rules.
		'rewrite'       => false,
		'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ],
		'menu_icon'     => 'dashicons-book',
		'menu_position' => 21,
	] );

	register_taxonomy( 'resource_type', 'resource', [
		'labels' => [ 'name' => 'Types', 'singular_name' => 'Type', 'add_new_item' => 'Add New Type', 'edit_item' => 'Edit Type', 'search_items' => 'Search Types' ],
		'hierarchical'      => false,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => false, // the archives are pages, not term archives
	] );

	register_taxonomy( 'resource_topic', 'resource', [
		'labels' => [ 'name' => 'Topics', 'singular_name' => 'Topic', 'add_new_item' => 'Add New Topic', 'edit_item' => 'Edit Topic', 'search_items' => 'Search Topics' ],
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => [ 'slug' => 'resources/topic', 'with_front' => false ],
	] );

	register_taxonomy( 'news_category', 'resource', [
		'labels' => [ 'name' => 'News categories', 'singular_name' => 'News category', 'add_new_item' => 'Add New News Category', 'edit_item' => 'Edit News Category', 'search_items' => 'Search News Categories' ],
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => false,
		'rewrite'           => false,
	] );

	// Seeding is a one-off, but must re-run whenever the fixed term set changes. Bump
	// W270C_TERMS_VERSION to trigger it; an unconditional call would cost a term lookup on
	// every front-end request forever.
	if ( (int) get_option( 'w270c_terms_version' ) !== W270C_TERMS_VERSION ) {
		w270c_seed_terms();
		update_option( 'w270c_terms_version', W270C_TERMS_VERSION );
	}
}
add_action( 'init', 'w270c_register' );

/** Creates the fixed sub-type and news-category terms. Idempotent. */
function w270c_seed_terms() {
	foreach ( W270C_SUBTYPES as $slug => [ $singular, $plural ] ) {
		if ( ! term_exists( $slug, 'resource_type' ) ) {
			$r = wp_insert_term( $singular, 'resource_type', [ 'slug' => $slug ] );
			if ( is_wp_error( $r ) ) { error_log( "w270c_seed_terms: resource_type {$slug}: " . $r->get_error_message() ); }
		}
	}
	foreach ( W270C_NEWS_CATEGORIES as $name ) {
		if ( ! term_exists( sanitize_title( $name ), 'news_category' ) ) {
			$r = wp_insert_term( $name, 'news_category', [ 'slug' => sanitize_title( $name ) ] );
			if ( is_wp_error( $r ) ) { error_log( "w270c_seed_terms: news_category {$name}: " . $r->get_error_message() ); }
		}
	}
}

/** "Guide" / "Checklist" / …, from the post's sub-type term. */
function w270c_type_label( $post_id, $featured = false ) {
	$terms = get_the_terms( $post_id, 'resource_type' );
	$term  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
	if ( ! $term ) { return 'Resource'; }
	return ( $featured && 'guides' === $term->slug ) ? 'Featured guide' : $term->name;
}

// ACF Local JSON: field groups load from (and save back to) the plugin's acf-json folder.
add_filter( 'acf/settings/load_json', function ( $paths ) {
	$paths[] = W270C_DIR . 'acf-json';
	return $paths;
} );
add_filter( 'acf/settings/save_json', fn() => W270C_DIR . 'acf-json' );

register_activation_hook( __FILE__, function () {
	w270c_register();
	flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
