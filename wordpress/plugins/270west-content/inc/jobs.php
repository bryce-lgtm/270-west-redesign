<?php
/**
 * Job Postings: the `job` post type behind the Careers page. Each posting lives at
 * /about/careers/<slug>/, beneath the Careers page itself, which lists the open ones
 * ([w270_job_list] in the theme). Fields are the ACF group in acf-json/group_270w_job_details.json.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Bump to rebuild rewrite rules once after the job rules change (a deploy does not flush). */
const W270C_JOBS_VERSION = 1;

function w270c_register_jobs() {
	register_post_type( 'job', [
		'labels' => [
			'name' => 'Job Postings', 'singular_name' => 'Job Posting', 'add_new_item' => 'Add New Job Posting',
			'edit_item' => 'Edit Job Posting', 'new_item' => 'New Job Posting', 'view_item' => 'View Job Posting',
			'search_items' => 'Search Job Postings', 'not_found' => 'No job postings found', 'all_items' => 'All Job Postings',
			'menu_name' => 'Job Postings',
		],
		'public'        => true,
		'show_in_rest'  => true,
		'has_archive'   => false, // the Careers page is the listing
		// A permastruct goes into WordPress's top rules, so /about/careers/<slug>/ is tried before the
		// page rules; /about/careers/ itself has no slug segment and stays the Careers page.
		'rewrite'       => [ 'slug' => 'about/careers', 'with_front' => false ],
		'supports'      => [ 'title', 'editor', 'excerpt', 'revisions' ],
		'menu_icon'     => 'dashicons-id',
		'menu_position' => 22,
	] );

	if ( (int) get_option( 'w270c_jobs_version' ) !== W270C_JOBS_VERSION ) {
		// Runs on the request after the rules are registered, so the flush sees them.
		add_action( 'wp_loaded', function () {
			flush_rewrite_rules( false );
			update_option( 'w270c_jobs_version', W270C_JOBS_VERSION );
		} );
	}
}
add_action( 'init', 'w270c_register_jobs' );
