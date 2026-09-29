<?php
/**
 * VAC status checker → Gravity Forms → Creatio.
 *
 * The checker is a JavaScript widget (main.js initQuiz), not a Gravity Form, so it posts its answers
 * here and this endpoint submits them to the "VAC Status Checker" form with GFAPI::submit_form().
 * That runs Gravity Forms' normal pipeline: the entry is stored, notifications go out, and the
 * Webhooks add-on feed "Creatio Webhook - VAC Status Checker" sends the lead to Creatio, exactly
 * like the site's other forms. The importer creates the form and feed (gravity-forms*.json).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** The checker's questions: answer key => label used in the entry and the Creatio commentary. */
function w270_checker_questions() {
	return [
		'served' => [ 30, 'Service' ],
		'rating' => [ 31, 'VAC disability rating' ],
		'health' => [ 32, 'Service-related health issues' ],
		'filed'  => [ 33, 'Previous VAC claim' ],
		'goal'   => [ 34, 'Looking to' ],
	];
}

/** The consent wording, read from the form's consent field so wp-admin edits carry through. */
function w270_checker_consent_text() {
	$id = w270_checker_form_id();
	if ( $id && class_exists( 'GFAPI' ) ) {
		foreach ( ( GFAPI::get_form( $id )['fields'] ?? [] ) as $field ) {
			if ( 'consent' === $field->type && '' !== (string) $field->checkboxLabel ) { return (string) $field->checkboxLabel; }
		}
	}
	return 'I agree to be contacted by a member of the 270 West Consulting team.';
}

function w270_checker_form_id() {
	$ids = get_option( 'w270_form_ids', [] );
	return (int) ( $ids['checker'] ?? 0 );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'w270/v1', '/checker', [
		'methods'             => 'POST',
		'permission_callback' => '__return_true', // public, like any form on the site
		'callback'            => 'w270_checker_submit',
	] );
} );

function w270_checker_submit( WP_REST_Request $req ) {
	$form_id = w270_checker_form_id();
	if ( ! class_exists( 'GFAPI' ) || ! $form_id ) {
		return new WP_Error( 'w270_checker_unavailable', 'The checker is not available right now.', [ 'status' => 503 ] );
	}
	$p = (array) $req->get_json_params();

	// Honeypot: a real visitor never fills the hidden "website" field. Pretend success.
	if ( ! empty( $p['website'] ) ) { return [ 'ok' => true ]; }

	// Throttle: five submissions per address per ten minutes.
	$ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
	$key = 'w270_checker_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= 5 ) {
		return new WP_Error( 'w270_checker_busy', 'Too many submissions. Please try again in a few minutes.', [ 'status' => 429 ] );
	}
	set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );

	$name  = trim( sanitize_text_field( $p['name'] ?? '' ) );
	$email = sanitize_email( $p['email'] ?? '' );
	$phone = sanitize_text_field( $p['phone'] ?? '' );
	if ( '' === $name || ! is_email( $email ) ) {
		return new WP_Error( 'w270_checker_invalid', 'Please enter your name and a valid email address.', [ 'status' => 400 ] );
	}
	if ( true !== ( $p['consent'] ?? false ) ) {
		return new WP_Error( 'w270_checker_consent', 'Please agree to be contacted so an advisor can follow up.', [ 'status' => 400 ] );
	}
	$parts = preg_split( '/\s+/', $name, 2 );

	$answers = (array) ( $p['answers'] ?? [] );
	$values  = [
		'input_1_3' => $parts[0],
		'input_1_6' => $parts[1] ?? '',
		'input_2'   => $email,
		'input_5'   => $phone,
	];
	// Creatio's Commentary gets one line, the way the events team writes it:
	// "VAC status checker. Service: Regular Force; VAC disability rating: No rating; …"
	$summary = [];
	foreach ( w270_checker_questions() as $k => [ $field_id, $label ] ) {
		$v = mb_substr( sanitize_text_field( $answers[ $k ] ?? '' ), 0, 80 );
		$values[ "input_{$field_id}" ] = $v;
		$summary[] = $label . ': ' . ( '' === $v ? 'not answered' : $v );
	}
	$values['input_20'] = 'VAC status checker. ' . implode( '; ', $summary ) . '.';
	// Consent (same field as the Contact Form): the box, and the wording the visitor agreed to.
	$values['input_18_1'] = '1';
	$values['input_18_2'] = w270_checker_consent_text();
	// Creatio's "claims submitted before" column is a yes/no: map the checker's answer onto it.
	$filed = (string) ( $answers['filed'] ?? '' );
	$values['input_15'] = str_starts_with( $filed, 'Yes' ) ? 'True' : ( 'No' === $filed ? 'False' : '' );

	// Campaign attribution kept in sessionStorage by main.js (same fields the other forms carry).
	$lead = (array) ( $p['lead'] ?? [] );
	foreach ( [ 12 => 'utm_source', 13 => 'utm_medium', 14 => 'utm_term', 17 => 'utm_campaign' ] as $fid => $k ) {
		$values[ "input_{$fid}" ] = mb_substr( sanitize_text_field( $lead[ $k ] ?? '' ), 0, 200 );
	}

	// Hidden fields with a default value (EntityName, UsrEventType) are filled by the rendered form
	// on the other forms; there is no rendered form here, so apply the defaults explicitly.
	$form = GFAPI::get_form( $form_id );
	foreach ( $form['fields'] ?? [] as $field ) {
		$k = 'input_' . $field->id;
		if ( 'hidden' === $field->type && '' !== (string) $field->defaultValue && ! isset( $values[ $k ] ) ) {
			$values[ $k ] = $field->defaultValue;
		}
	}

	$result = GFAPI::submit_form( $form_id, $values );
	if ( is_wp_error( $result ) ) {
		error_log( 'w270 checker: submit_form failed: ' . $result->get_error_message() );
		return new WP_Error( 'w270_checker_failed', 'We could not send your answers. Please try again.', [ 'status' => 500 ] );
	}
	if ( empty( $result['is_valid'] ) ) {
		$messages = array_values( array_filter( (array) ( $result['validation_messages'] ?? [] ) ) );
		return new WP_Error( 'w270_checker_invalid', $messages ? wp_strip_all_tags( implode( ' ', $messages ) ) : 'Please check your details.', [ 'status' => 400 ] );
	}
	return [ 'ok' => true ];
}
