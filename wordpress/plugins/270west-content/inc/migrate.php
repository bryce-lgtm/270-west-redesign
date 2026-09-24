<?php
/**
 * One-way migration from the three legacy post types to `resource` + a sub-type term.
 *
 * Rows are updated with $wpdb rather than wp_update_post() because the legacy types are no
 * longer registered, so WP_Query and the post API cannot see them. Post IDs are preserved,
 * which matters: ACF meta hangs off the ID, and the related_resources field stores IDs.
 *
 * Idempotent — the second run finds no rows and does nothing.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function w270c_migrate_legacy_resources() {
	global $wpdb;
	$rows = $wpdb->get_results(
		"SELECT ID, post_type FROM {$wpdb->posts} WHERE post_type IN ('guide','checklist','explainer')"
	);
	if ( ! $rows ) { return 0; }
	$moved = 0;
	foreach ( $rows as $row ) {
		$ok = $wpdb->update( $wpdb->posts, [ 'post_type' => 'resource' ], [ 'ID' => (int) $row->ID ] );
		if ( false === $ok ) {
			error_log( "w270c migrate: failed to update post {$row->ID}: {$wpdb->last_error}" );
			$GLOBALS['w270_failed'] = true;
			continue;
		}
		$terms = wp_set_object_terms( (int) $row->ID, W270C_LEGACY_SUBTYPES[ $row->post_type ], 'resource_type', false );
		if ( is_wp_error( $terms ) ) {
			error_log( "w270c migrate: failed to set sub-type on post {$row->ID}: " . $terms->get_error_message() );
			$GLOBALS['w270_failed'] = true;
		}
		clean_post_cache( (int) $row->ID );
		$moved++;
	}
	if ( $moved ) { flush_rewrite_rules(); }
	return $moved;
}
