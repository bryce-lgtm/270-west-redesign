<?php
/**
 * Job Postings on the front end: the open-roles list on the Careers page ([w270_job_list]), the
 * single posting (single-job.php), its Google for Jobs data, and the Job Application form.
 *
 * The post type and its ACF fields belong to the 270 West Content plugin (inc/jobs.php there).
 * Applications go to the address set on each posting, never to Creatio: the form has no webhook
 * feed, and its "Application" notification is re-addressed per posting below.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const W270_JOB_TYPES = [ 'FULL_TIME' => 'Full-time', 'PART_TIME' => 'Part-time', 'CONTRACTOR' => 'Contract', 'TEMPORARY' => 'Temporary' ];
const W270_JOB_ARRANGEMENTS = [ 'onsite' => 'On-site', 'hybrid' => 'Hybrid', 'remote' => 'Remote' ];

/** Closing date as Y-m-d, or ''. ACF returns Y-m-d; the raw meta fallback is Ymd. */
function w270_job_closing( $id ) {
	$d = (string) w270_field( 'closing_date', $id );
	return '' === $d ? '' : wp_date( 'Y-m-d', strtotime( $d ) );
}

/** Open = published and not past its closing date (the closing day itself is still open). */
function w270_job_is_open( $id ) {
	if ( 'publish' !== get_post_status( $id ) ) { return false; }
	$close = w270_job_closing( $id );
	return '' === $close || $close >= wp_date( 'Y-m-d' );
}

function w270_open_jobs() {
	$ids = get_posts( [ 'post_type' => 'job', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC', 'fields' => 'ids' ] );
	return array_values( array_filter( $ids, 'w270_job_is_open' ) );
}

/** "Dartmouth, NS", "Remote (Canada)", "Dartmouth, NS (hybrid)". */
function w270_job_location( $id ) {
	$place = implode( ', ', array_filter( [ (string) w270_field( 'city', $id ), (string) w270_field( 'province', $id ) ] ) );
	$arr   = (string) w270_field( 'work_arrangement', $id );
	if ( 'remote' === $arr ) { return 'Remote (Canada)'; }
	if ( 'hybrid' === $arr ) { return $place ? "{$place} (hybrid)" : 'Hybrid'; }
	return $place;
}

/** "$55,000–$65,000 per year", "$24 per hour", or ''. */
function w270_job_pay( $id ) {
	$min  = (float) w270_field( 'salary_min', $id );
	$max  = (float) w270_field( 'salary_max', $id );
	if ( ! $min && ! $max ) { return ''; }
	$fmt  = fn( $n ) => '$' . number_format_i18n( $n, floor( $n ) == $n ? 0 : 2 );
	$unit = 'HOUR' === w270_field( 'salary_unit', $id ) ? 'per hour' : 'per year';
	$amt  = ( $min && $max && $min !== $max ) ? $fmt( $min ) . '–' . $fmt( $max ) : $fmt( $min ?: $max );
	return "{$amt} {$unit}";
}

/** Employment type and location, for the card and the job page. */
function w270_job_meta( $id ) {
	return array_values( array_filter( [ W270_JOB_TYPES[ w270_field( 'employment_type', $id ) ] ?? '', w270_job_location( $id ) ] ) );
}

// [w270_job_list] — the open roles on the Careers page, or a short note and a way to get in touch.
add_shortcode( 'w270_job_list', function () {
	$jobs = w270_open_jobs();
	if ( ! $jobs ) {
		return '<div class="job-empty"><p class="sec-p">There are no open roles at the moment. If you would like to work with us, we would still be glad to hear from you.</p>'
			. '<div class="section-ctas"><a href="' . esc_url( home_url( '/book-a-consult/#contact' ) ) . '" class="btn-accent">Contact our team →</a></div></div>';
	}
	$out = '<p class="sec-p job-list-intro">Explore current openings below to learn more about the role, what the work involves and how to apply.</p><ul class="job-list">';
	foreach ( $jobs as $id ) {
		$out .= '<li class="job-card"><a class="job-card-link" href="' . esc_url( get_permalink( $id ) ) . '">'
			. '<h3 class="job-card-h">' . esc_html( get_the_title( $id ) ) . '</h3>'
			. '<p class="job-card-meta">' . esc_html( implode( ' · ', w270_job_meta( $id ) ) ) . '</p>'
			. ( has_excerpt( $id ) ? '<p class="job-card-p">' . esc_html( get_the_excerpt( $id ) ) . '</p>' : '' )
			. '<span class="job-card-more">View role →</span></a></li>';
	}
	return $out . '</ul>';
} );

// Google for Jobs: JobPosting data on an open posting's page. A closed posting has none, and is noindexed.
add_action( 'wp_head', function () {
	if ( ! is_singular( 'job' ) || ! w270_job_is_open( get_the_ID() ) ) { return; }
	$id  = get_the_ID();
	$job = [
		'@context'           => 'https://schema.org/',
		'@type'              => 'JobPosting',
		'title'              => get_the_title( $id ),
		'description'        => wp_kses_post( apply_filters( 'the_content', get_post_field( 'post_content', $id ) ) ),
		'datePosted'         => get_the_date( 'c', $id ),
		'employmentType'     => (string) w270_field( 'employment_type', $id ) ?: 'FULL_TIME',
		'directApply'        => true,
		'hiringOrganization' => [
			'@type' => 'Organization', 'name' => '270 West Consulting', 'sameAs' => home_url( '/' ),
			'logo'  => get_stylesheet_directory_uri() . '/assets/img/brand/270west-symbol-dark.svg',
		],
		'url'                => get_permalink( $id ),
	];
	if ( $close = w270_job_closing( $id ) ) { $job['validThrough'] = $close . 'T23:59:59' . wp_date( 'P' ); }
	$city = (string) w270_field( 'city', $id );
	$prov = (string) w270_field( 'province', $id );
	if ( 'remote' === w270_field( 'work_arrangement', $id ) ) {
		$job['jobLocationType']                = 'TELECOMMUTE';
		$job['applicantLocationRequirements'] = [ '@type' => 'Country', 'name' => 'Canada' ];
	}
	if ( $city || $prov ) {
		$job['jobLocation'] = [ '@type' => 'Place', 'address' => array_filter( [
			'@type' => 'PostalAddress', 'addressLocality' => $city, 'addressRegion' => $prov, 'addressCountry' => 'CA',
		] ) ];
	}
	$min = (float) w270_field( 'salary_min', $id );
	$max = (float) w270_field( 'salary_max', $id );
	if ( $min || $max ) {
		$job['baseSalary'] = [ '@type' => 'MonetaryAmount', 'currency' => 'CAD', 'value' => array_filter( [
			'@type' => 'QuantitativeValue', 'minValue' => $min ?: null, 'maxValue' => $max ?: null,
			'unitText' => 'HOUR' === w270_field( 'salary_unit', $id ) ? 'HOUR' : 'YEAR',
		] ) ];
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $job, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
} );

add_filter( 'wpseo_robots', function ( $robots ) {
	return ( is_singular( 'job' ) && ! w270_job_is_open( get_the_ID() ) ) ? 'noindex, follow' : $robots;
} );

// ── Job Application form (Gravity Forms key "careers") ──

function w270_job_form_id() {
	return (int) ( get_option( 'w270_form_ids', [] )['careers'] ?? 0 );
}

/** The form's field id for an admin label (job_id, job_title), or 0. */
function w270_job_form_field( $form, $admin_label ) {
	foreach ( $form['fields'] as $f ) { if ( $admin_label === $f->adminLabel ) { return (int) $f->id; } }
	return 0;
}

// The form sits on a posting's page: stamp that posting into the hidden fields.
add_filter( 'gform_pre_render', function ( $form ) {
	if ( (int) $form['id'] !== w270_job_form_id() || ! is_singular( 'job' ) ) { return $form; }
	foreach ( $form['fields'] as $f ) {
		if ( 'job_id' === $f->adminLabel ) { $f->defaultValue = (string) get_the_ID(); }
		if ( 'job_title' === $f->adminLabel ) { $f->defaultValue = get_the_title(); }
	}
	return $form;
} );

/** The posting an entry or submission is for, when it is a real, open posting. */
function w270_job_for_value( $value ) {
	$post = get_post( (int) $value );
	return ( $post && 'job' === $post->post_type && w270_job_is_open( $post->ID ) ) ? $post : null;
}

// Refuse applications for a closed or unknown posting rather than mailing them to nobody.
add_filter( 'gform_validation', function ( $result ) {
	$form = $result['form'];
	if ( (int) $form['id'] !== w270_job_form_id() ) { return $result; }
	$fid = w270_job_form_field( $form, 'job_id' );
	if ( $fid && ! w270_job_for_value( rgpost( "input_{$fid}" ) ) ) {
		$result['is_valid'] = false;
		foreach ( $form['fields'] as $f ) {
			if ( 'fileupload' === $f->type ) { $f->failed_validation = true; $f->validation_message = 'This role is no longer accepting applications.'; }
		}
		$result['form'] = $form;
	}
	return $result;
} );

// Address the "Application" email to the posting's own recipient. The resume is attached.
add_filter( 'gform_notification', function ( $notification, $form, $entry ) {
	if ( (int) $form['id'] !== w270_job_form_id() || 'Application' !== ( $notification['name'] ?? '' ) ) { return $notification; }
	$post = get_post( (int) rgar( $entry, (string) w270_job_form_field( $form, 'job_id' ) ) );
	$to   = $post && 'job' === $post->post_type ? (string) w270_field( 'applications_email', $post->ID ) : '';
	if ( is_email( $to ) ) {
		$notification['toType'] = 'email';
		$notification['to']     = $to;
	}
	return $notification;
}, 10, 3 );

// GoDaddy's page cache bans itself when a posting is published or unpublished, but a closing date
// passing changes no post, so Careers could keep listing an expired role for the cache's month.
// Once a day, compare the open roles with yesterday's and ban the cache when the set has changed
// (through the nav-menu hook, the same way the deploy scripts do).
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'w270_jobs_daily' ) ) { wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'w270_jobs_daily' ); }
} );
add_action( 'w270_jobs_daily', function () {
	$open = implode( ',', w270_open_jobs() );
	if ( get_option( 'w270_jobs_open' ) === $open ) { return; }
	update_option( 'w270_jobs_open', $open, false );
	do_action( 'wp_update_nav_menu', 0 );
} );
