<?php
/**
 * Plugin Name: 270 West Content
 * Description: Resource post types (Guides, Checklists, Explainers), the Topic taxonomy and their ACF field groups.
 * Version: 1.0.0
 * Author: 270 West Consulting
 * Text Domain: 270west
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'W270C_DIR', plugin_dir_path( __FILE__ ) );

/** post type => [singular, plural, rewrite slug, menu icon] */
const W270C_TYPES = [
	'guide'     => [ 'Guide', 'Guides', 'resources/guides', 'dashicons-book' ],
	'checklist' => [ 'Checklist', 'Checklists', 'resources/checklists', 'dashicons-yes-alt' ],
	'explainer' => [ 'Explainer', 'Explainers', 'resources/explainers', 'dashicons-lightbulb' ],
];

function w270c_types() {
	return array_keys( W270C_TYPES );
}

/** "Guide" / "Checklist" / "Explainer"; the featured main card says "Featured guide". */
function w270c_type_label( $type, $featured = false ) {
	$label = W270C_TYPES[ $type ][0] ?? 'Resource';
	return ( $featured && 'guide' === $type ) ? 'Featured guide' : $label;
}

function w270c_register() {
	$position = 21;
	foreach ( W270C_TYPES as $type => [ $singular, $plural, $slug, $icon ] ) {
		register_post_type( $type, [
			'labels' => [
				'name' => $plural, 'singular_name' => $singular, 'add_new_item' => "Add New {$singular}",
				'edit_item' => "Edit {$singular}", 'new_item' => "New {$singular}", 'view_item' => "View {$singular}",
				'search_items' => "Search {$plural}", 'not_found' => "No {$plural} found", 'all_items' => "All {$plural}",
			],
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => false,
			'rewrite'       => [ 'slug' => $slug, 'with_front' => false ],
			'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ],
			'menu_icon'     => $icon,
			'menu_position' => $position++,
		] );
	}
	register_taxonomy( 'resource_topic', w270c_types(), [
		'labels' => [ 'name' => 'Topics', 'singular_name' => 'Topic', 'add_new_item' => 'Add New Topic', 'edit_item' => 'Edit Topic', 'search_items' => 'Search Topics' ],
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => [ 'slug' => 'resources/topic', 'with_front' => false ],
	] );
}
add_action( 'init', 'w270c_register' );

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
