<?php
/**
 * Plugin Name: 270 West Server-Side Lead Events (GA4 + Meta CAPI)
 * Description: Sends one GA4 Measurement Protocol "generate_lead" event and one Meta Conversions API "Lead" event for every saved lead-form entry. Settings › Lead Events.
 * Version: 1.1.0
 *
 * Must-use plugin, so a theme or plugin change cannot switch it off. Deployed by
 * wordpress/build/deploy-godaddy.sh and deploy-siteground.sh to wp-content/mu-plugins/.
 *
 * Configuration: wp-admin › Settings › GA4 Lead Events (option "lg_ga4_settings"), or wp-config.php,
 * above "That's all, stop editing!". A constant in wp-config.php wins over the admin setting and locks it:
 *
 *   define( 'LG_GA4_API_SECRET', '…' );          // required: GA4 › Data Streams › web › Measurement Protocol API secrets
 *   define( 'LG_GA4_MEASUREMENT_ID', 'G-…' );     // optional: defaults to the 270westconsulting.ca stream below
 *   define( 'LG_GA4_DEBUG', true );               // optional: debug_mode=1 (DebugView), blocking request, error_log
 *
 * The API secret is never kept in this file or the repo. Without it the plugin does nothing.
 *
 * Which entries count: only the lead forms in lg_ga4_lead_forms() (matched by form title, so the same
 * map works on staging, production and any new install where the form IDs differ). Share Your Story and
 * Job Application entries are not leads and send nothing. Spam entries send nothing.
 *
 * Visitor identity: the _ga and _ga_<STREAM> cookies on the submitting request. Gravity Forms AJAX posts
 * and the theme's /wp-json/w270/v1/checker endpoint are same-origin browser requests, so the cookies are
 * there. Calendly bookings arrive from Calendly's server (theme inc/calendly.php), which has no visitor
 * cookies; the theme passes the IDs it got back from Calendly through the "lg_ga4_ids" filter instead.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 270westconsulting.ca web stream (the G- ID both the Site Kit tag and GTM-53JCMLGB send to).
const LG_GA4_DEFAULT_MEASUREMENT_ID = 'G-QRPV310VRP';

/** Saved admin settings: api_secret, measurement_id, debug. */
function lg_ga4_settings() {
	$s = get_option( 'lg_ga4_settings', array() );
	return is_array( $s ) ? $s : array();
}

/** Measurement Protocol API secret: wp-config constant, else the admin setting. '' = not configured. */
function lg_ga4_api_secret() {
	if ( defined( 'LG_GA4_API_SECRET' ) ) {
		return (string) LG_GA4_API_SECRET;
	}
	return (string) ( lg_ga4_settings()['api_secret'] ?? '' );
}

function lg_ga4_measurement_id() {
	if ( defined( 'LG_GA4_MEASUREMENT_ID' ) ) {
		return (string) LG_GA4_MEASUREMENT_ID;
	}
	$id = (string) ( lg_ga4_settings()['measurement_id'] ?? '' );
	return '' !== $id ? $id : LG_GA4_DEFAULT_MEASUREMENT_ID;
}

function lg_ga4_debug() {
	if ( defined( 'LG_GA4_DEBUG' ) ) {
		return (bool) LG_GA4_DEBUG;
	}
	return ! empty( lg_ga4_settings()['debug'] );
}

/**
 * Lead forms, by Gravity Forms title => GA4 form_name. Only these send generate_lead.
 * Extend with the "lg_ga4_lead_forms" filter.
 */
function lg_ga4_lead_forms() {
	return apply_filters(
		'lg_ga4_lead_forms',
		array(
			'Advertising Lead Capture' => 'advertising_lead_capture',
			'Calendly Booking'         => 'calendly_booking',
			'Call Request'             => 'call_request',
			'Contact Form'             => 'contact_form',
			'VAC Status Checker'       => 'vac_status_checker',
		)
	);
}

/** GA4 form_name for a lead form, or null when the form is not a lead form. */
function lg_ga4_form_label( $form ) {
	$title = trim( (string) rgar( $form, 'title' ) );
	$map   = lg_ga4_lead_forms();
	return isset( $map[ $title ] ) ? $map[ $title ] : null;
}

/** "GA1.1.1234567890.1700000000" → "1234567890.1700000000". */
function lg_ga4_parse_client_id( $raw ) {
	$parts = explode( '.', (string) $raw );
	if ( count( $parts ) >= 4 && ctype_digit( $parts[2] ) && ctype_digit( $parts[3] ) ) {
		return $parts[2] . '.' . $parts[3];
	}
	return null;
}

/**
 * Session ID from a _ga_<STREAM> cookie, in either format:
 *   GS1.1.<session_id>.<n>.<…>        (field index 2)
 *   GS2.1.s<session_id>$o<n>$g<…>…    (digits after ".s")
 */
function lg_ga4_parse_session_id( $raw ) {
	$raw = (string) $raw;
	if ( 0 === strpos( $raw, 'GS2.' ) ) {
		return preg_match( '/\.s(\d+)/', $raw, $m ) ? $m[1] : null;
	}
	$parts = explode( '.', $raw );
	return isset( $parts[2] ) && ctype_digit( $parts[2] ) ? $parts[2] : null;
}

function lg_ga4_cookie( $name ) {
	return isset( $_COOKIE[ $name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) ) : '';
}

function lg_ga4_client_id() {
	return lg_ga4_parse_client_id( lg_ga4_cookie( '_ga' ) );
}

function lg_ga4_session_id() {
	return lg_ga4_parse_session_id( lg_ga4_cookie( '_ga_' . str_replace( 'G-', '', lg_ga4_measurement_id() ) ) );
}

add_action( 'gform_after_submission', 'lg_ga4_send_lead', 10, 2 );

function lg_ga4_send_lead( $entry, $form ) {
	if ( '' === lg_ga4_api_secret() || '' === lg_ga4_measurement_id() ) {
		return;
	}
	if ( 'spam' === rgar( $entry, 'status' ) ) {
		return;
	}
	$form_name = lg_ga4_form_label( $form );
	if ( ! $form_name ) {
		return;
	}
	$entry_id = (int) rgar( $entry, 'id' );
	// Once per saved entry, even if something re-runs the hook.
	if ( $entry_id && function_exists( 'gform_get_meta' ) && gform_get_meta( $entry_id, 'lg_ga4_sent' ) ) {
		return;
	}

	$ids = apply_filters(
		'lg_ga4_ids',
		array(
			'client_id'  => lg_ga4_client_id(),
			'session_id' => lg_ga4_session_id(),
		),
		$entry,
		$form
	);
	$client_id  = ! empty( $ids['client_id'] ) ? (string) $ids['client_id'] : null;
	$session_id = ! empty( $ids['session_id'] ) ? (string) $ids['session_id'] : null;
	$attributed = (bool) $client_id;
	if ( ! $client_id ) {
		// No _ga cookie (ad blocker, consent, Calendly booking made outside the site): the lead still
		// counts, as a new unattributed user.
		$client_id = wp_rand( 100000000, 999999999 ) . '.' . time();
	}

	$params = array(
		'form_name'            => $form_name,
		'form_id'              => (string) rgar( $form, 'id' ),
		'entry_id'             => (string) $entry_id,
		'lead_source_method'   => 'server',
		'engagement_time_msec' => 1,
	);
	if ( $session_id ) {
		$params['session_id'] = $session_id;
	}
	if ( lg_ga4_debug() ) {
		$params['debug_mode'] = 1;
	}

	$body = array(
		'client_id' => $client_id,
		'events'    => array(
			array(
				'name'   => 'generate_lead',
				'params' => $params,
			),
		),
	);

	$GLOBALS['lg_ga4_queue'][] = array(
		'entry_id'   => $entry_id,
		'body'       => $body,
		'attributed' => $attributed,
		'session'    => (bool) $session_id,
	);
	if ( $entry_id && function_exists( 'gform_update_meta' ) ) {
		gform_update_meta( $entry_id, 'lg_ga4_sent', time() );
	}
	if ( ! has_action( 'shutdown', 'lg_ga4_flush' ) ) {
		add_action( 'shutdown', 'lg_ga4_flush', 9999 );
	}
}

/**
 * Sends the queued events after the visitor has their response. On PHP-FPM (GoDaddy, SiteGround)
 * fastcgi_finish_request() closes the connection first, so the request to GA4 can be a normal blocking
 * one that is not cut off mid-handshake and the entry note can record GA4's answer. Without it, the
 * request is fire-and-forget. In debug mode the response is held until GA4 answers.
 */
function lg_ga4_flush() {
	$queue = isset( $GLOBALS['lg_ga4_queue'] ) ? $GLOBALS['lg_ga4_queue'] : array();
	$GLOBALS['lg_ga4_queue'] = array();
	if ( ! $queue ) {
		return;
	}
	$blocking = lg_ga4_debug() ? true : lg_lead_finish_request();

	$url = sprintf(
		'https://www.google-analytics.com/mp/collect?measurement_id=%s&api_secret=%s',
		rawurlencode( lg_ga4_measurement_id() ),
		rawurlencode( lg_ga4_api_secret() )
	);

	foreach ( $queue as $item ) {
		$response = wp_remote_post(
			$url,
			array(
				'body'     => wp_json_encode( $item['body'] ),
				'headers'  => array( 'Content-Type' => 'application/json' ),
				'timeout'  => $blocking ? 5 : 2,
				'blocking' => $blocking,
			)
		);

		if ( $item['entry_id'] && class_exists( 'GFAPI' ) ) {
			if ( $item['attributed'] && $item['session'] ) {
				$note = 'GA4 generate_lead sent (session attributed)';
			} elseif ( $item['attributed'] ) {
				$note = 'GA4 generate_lead sent (client ID found but no session cookie: user attributed, session not)';
			} else {
				$note = 'GA4 generate_lead sent (no _ga cookie, unattributed)';
			}
			if ( is_wp_error( $response ) ) {
				$note .= ' [request failed: ' . $response->get_error_message() . ']';
			} elseif ( $blocking ) {
				$note .= ' [GA4 HTTP ' . wp_remote_retrieve_response_code( $response ) . ']';
			}
			GFAPI::add_note( $item['entry_id'], 0, 'GA4', $note );
		}

		if ( lg_ga4_debug() ) {
			error_log(
				'[LG GA4] entry ' . $item['entry_id'] . ': '
				. ( is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $response ) )
				. ' ' . wp_json_encode( $item['body'] )
			);
		}
	}
}

/**
 * Closes the visitor's connection so outbound requests can block without delaying the response.
 * Returns true when the request may block (connection closed), false when it must be fire-and-forget.
 */
function lg_lead_finish_request() {
	static $done = null;
	if ( null !== $done ) {
		return $done;
	}
	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
		$done = true;
	} elseif ( function_exists( 'litespeed_finish_request' ) ) {
		litespeed_finish_request();
		$done = true;
	} else {
		$done = false;
	}
	return $done;
}

/*
 * Meta Conversions API: one "Lead" event per lead-form entry (the same forms as GA4).
 *
 * Settings (Settings › Lead Events, or wp-config.php constants, which win and lock the field):
 *   LG_META_PIXEL_ID         dataset / pixel ID (Events Manager)
 *   LG_META_ACCESS_TOKEN     Conversions API access token (Events Manager › dataset › Settings)
 *   LG_META_TEST_EVENT_CODE  optional; while set, events show in Test Events and are NOT used for ads
 *
 * Customer information is normalised and SHA-256 hashed before it leaves the site, as Meta requires:
 * email, phone (with country code 1), first/last name, province, country (ca). The visitor's IP,
 * user agent and the _fbp/_fbc cookies (or an fbclid in the page URL) are sent unhashed for matching,
 * except for Calendly bookings, which arrive from Calendly's server rather than the visitor's browser.
 * event_id is "lead_<entry id>", so a browser Pixel Lead event sent with the same eventID is deduplicated.
 */
const LG_META_GRAPH_VERSION = 'v23.0';

function lg_meta_setting( $key, $const ) {
	if ( defined( $const ) ) {
		return (string) constant( $const );
	}
	return (string) ( lg_ga4_settings()[ $key ] ?? '' );
}
function lg_meta_pixel_id() { return lg_meta_setting( 'meta_pixel_id', 'LG_META_PIXEL_ID' ); }
function lg_meta_access_token() { return lg_meta_setting( 'meta_access_token', 'LG_META_ACCESS_TOKEN' ); }
function lg_meta_test_event_code() { return lg_meta_setting( 'meta_test_event_code', 'LG_META_TEST_EVENT_CODE' ); }

function lg_meta_hash( $value ) {
	$value = trim( (string) $value );
	return '' === $value ? null : hash( 'sha256', $value );
}

/** Canadian provinces and territories → two-letter code (Meta's "st"). */
function lg_meta_province_code( $text ) {
	$map = array(
		'alberta' => 'ab', 'british columbia' => 'bc', 'manitoba' => 'mb', 'new brunswick' => 'nb',
		'newfoundland and labrador' => 'nl', 'northwest territories' => 'nt', 'nova scotia' => 'ns',
		'nunavut' => 'nu', 'ontario' => 'on', 'prince edward island' => 'pe', 'quebec' => 'qc',
		'saskatchewan' => 'sk', 'yukon' => 'yt',
	);
	$t = strtolower( remove_accents( trim( (string) $text ) ) );
	return $map[ $t ] ?? null;
}

/** Meta user_data from a Gravity Forms entry: hashed contact details plus browser identifiers. */
function lg_meta_user_data( $entry, $form, $from_browser ) {
	$em = $ph = $fn = $ln = $st = null;
	foreach ( (array) $form['fields'] as $field ) {
		$id = (string) $field->id;
		switch ( $field->type ) {
			case 'email':
				$em = $em ?: strtolower( trim( (string) rgar( $entry, $id ) ) );
				break;
			case 'phone':
				$digits = preg_replace( '/\D+/', '', (string) rgar( $entry, $id ) );
				if ( 10 === strlen( $digits ) ) {
					$digits = '1' . $digits;
				}
				$ph = $ph ?: ( strlen( $digits ) >= 11 ? $digits : null );
				break;
			case 'name':
				$fn = $fn ?: strtolower( trim( (string) rgar( $entry, $id . '.3' ) ) );
				$ln = $ln ?: strtolower( trim( (string) rgar( $entry, $id . '.6' ) ) );
				break;
			case 'select':
				if ( preg_match( '/province/i', (string) $field->label ) ) {
					$value = (string) rgar( $entry, $id );
					foreach ( (array) $field->choices as $c ) {
						if ( (string) ( $c['value'] ?? '' ) === $value ) { // choice values are Creatio Ids
							$value = (string) $c['text'];
						}
					}
					$st = lg_meta_province_code( $value );
				}
				break;
		}
	}
	$data = array_filter(
		array(
			'em'      => $em ? array( lg_meta_hash( $em ) ) : null,
			'ph'      => $ph ? array( lg_meta_hash( $ph ) ) : null,
			'fn'      => $fn ? array( lg_meta_hash( $fn ) ) : null,
			'ln'      => $ln ? array( lg_meta_hash( $ln ) ) : null,
			'st'      => $st ? array( lg_meta_hash( $st ) ) : null,
			'country' => array( lg_meta_hash( 'ca' ) ),
		)
	);
	if ( $from_browser ) {
		$ip = (string) rgar( $entry, 'ip' );
		$ua = (string) rgar( $entry, 'user_agent' );
		if ( $ip ) { $data['client_ip_address'] = $ip; }
		if ( $ua ) { $data['client_user_agent'] = $ua; }
		$fbp = lg_ga4_cookie( '_fbp' );
		if ( $fbp ) { $data['fbp'] = $fbp; }
		$fbc = lg_ga4_cookie( '_fbc' );
		if ( ! $fbc ) {
			wp_parse_str( (string) wp_parse_url( (string) rgar( $entry, 'source_url' ), PHP_URL_QUERY ), $q );
			if ( ! empty( $q['fbclid'] ) ) {
				$fbc = 'fb.1.' . (int) ( microtime( true ) * 1000 ) . '.' . sanitize_text_field( $q['fbclid'] );
			}
		}
		if ( $fbc ) { $data['fbc'] = $fbc; }
	}
	return $data;
}

add_action( 'gform_after_submission', 'lg_meta_send_lead', 11, 2 );

function lg_meta_send_lead( $entry, $form ) {
	if ( '' === lg_meta_pixel_id() || '' === lg_meta_access_token() ) {
		return;
	}
	if ( 'spam' === rgar( $entry, 'status' ) ) {
		return;
	}
	$form_name = lg_ga4_form_label( $form );
	if ( ! $form_name ) {
		return;
	}
	$entry_id = (int) rgar( $entry, 'id' );
	if ( $entry_id && function_exists( 'gform_get_meta' ) && gform_get_meta( $entry_id, 'lg_meta_sent' ) ) {
		return;
	}
	// Calendly bookings are saved from Calendly's webhook: no visitor IP, user agent or cookies.
	$from_browser = 'calendly_booking' !== $form_name;

	$event = array(
		'event_name'    => 'Lead',
		'event_time'    => time(),
		'event_id'      => 'lead_' . $entry_id,
		'action_source' => 'website',
		'user_data'     => lg_meta_user_data( $entry, $form, $from_browser ),
		'custom_data'   => array( 'content_name' => $form_name, 'lead_source_method' => 'server' ),
	);
	$source_url = (string) rgar( $entry, 'source_url' );
	if ( $source_url ) {
		$event['event_source_url'] = $source_url;
	}
	$body = array( 'data' => array( $event ) );
	if ( '' !== lg_meta_test_event_code() ) {
		$body['test_event_code'] = lg_meta_test_event_code();
	}

	$GLOBALS['lg_meta_queue'][] = array( 'entry_id' => $entry_id, 'body' => $body );
	if ( $entry_id && function_exists( 'gform_update_meta' ) ) {
		gform_update_meta( $entry_id, 'lg_meta_sent', time() );
	}
	if ( ! has_action( 'shutdown', 'lg_meta_flush' ) ) {
		add_action( 'shutdown', 'lg_meta_flush', 10000 );
	}
}

/** POSTs a CAPI payload; returns [ok bool, readable message]. */
function lg_meta_post( $body, $blocking = true ) {
	$response = wp_remote_post(
		sprintf( 'https://graph.facebook.com/%s/%s/events', LG_META_GRAPH_VERSION, rawurlencode( lg_meta_pixel_id() ) ),
		array(
			'body'     => wp_json_encode( $body + array( 'access_token' => lg_meta_access_token() ) ),
			'headers'  => array( 'Content-Type' => 'application/json' ),
			'timeout'  => $blocking ? 8 : 2,
			'blocking' => $blocking,
		)
	);
	if ( is_wp_error( $response ) ) {
		return array( false, 'request failed: ' . $response->get_error_message() );
	}
	if ( ! $blocking ) {
		return array( true, 'sent' );
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	$json = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 === $code ) {
		return array( true, 'Meta HTTP 200, events_received ' . (int) ( $json['events_received'] ?? 0 ) );
	}
	return array( false, 'Meta HTTP ' . $code . ': ' . (string) ( $json['error']['message'] ?? wp_remote_retrieve_body( $response ) ) );
}

function lg_meta_flush() {
	$queue = isset( $GLOBALS['lg_meta_queue'] ) ? $GLOBALS['lg_meta_queue'] : array();
	$GLOBALS['lg_meta_queue'] = array();
	if ( ! $queue ) {
		return;
	}
	$blocking = lg_lead_finish_request();
	foreach ( $queue as $item ) {
		list( $ok, $msg ) = lg_meta_post( $item['body'], $blocking );
		if ( $item['entry_id'] && class_exists( 'GFAPI' ) ) {
			$test = isset( $item['body']['test_event_code'] ) ? ', test event' : '';
			GFAPI::add_note( $item['entry_id'], 0, 'Meta CAPI', 'Meta Lead event sent' . $test . ' [' . $msg . ']' );
		}
		if ( ! $ok ) {
			error_log( '[LG Meta CAPI] entry ' . $item['entry_id'] . ': ' . $msg );
		}
	}
}

/*
 * Settings › Lead Events: GA4 and Meta settings in wp-admin instead of wp-config.php. Anything defined
 * in wp-config.php is shown as locked. Administrators only; saved secrets are never printed back.
 */
add_action( 'admin_menu', function () {
	add_options_page( 'Lead Events', 'Lead Events', 'manage_options', 'lg-lead-events', 'lg_lead_settings_page' );
} );

/** Secret-type fields: blank keeps the saved value, the "clear" box removes it. */
function lg_lead_save_secret( &$s, $key ) {
	$v = isset( $_POST[ $key ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) : ''; // phpcs:ignore
	if ( ! empty( $_POST[ 'clear_' . $key ] ) ) { // phpcs:ignore
		unset( $s[ $key ] );
	} elseif ( '' !== $v ) {
		$s[ $key ] = $v;
	}
}

add_action( 'admin_post_lg_lead_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	check_admin_referer( 'lg_lead_save' );
	$s      = lg_ga4_settings();
	$errors = array();

	lg_lead_save_secret( $s, 'api_secret' );
	$mid = isset( $_POST['measurement_id'] ) ? strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['measurement_id'] ) ) ) ) : '';
	if ( '' === $mid ) {
		unset( $s['measurement_id'] );
	} elseif ( preg_match( '/^G-[A-Z0-9]{4,}$/', $mid ) ) {
		$s['measurement_id'] = $mid;
	} else {
		$errors[] = 'The GA4 measurement ID must look like G-XXXXXXXXXX; it was not changed.';
	}
	$s['debug'] = ! empty( $_POST['debug'] ) ? 1 : 0;

	$pixel = isset( $_POST['meta_pixel_id'] ) ? preg_replace( '/\D+/', '', wp_unslash( $_POST['meta_pixel_id'] ) ) : '';
	if ( '' === $pixel ) {
		unset( $s['meta_pixel_id'] );
	} elseif ( strlen( $pixel ) >= 10 ) {
		$s['meta_pixel_id'] = $pixel;
	} else {
		$errors[] = 'The Meta pixel ID should be a long number; it was not changed.';
	}
	lg_lead_save_secret( $s, 'meta_access_token' );
	$code = isset( $_POST['meta_test_event_code'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['meta_test_event_code'] ) ) ) : '';
	if ( '' === $code ) {
		unset( $s['meta_test_event_code'] );
	} else {
		$s['meta_test_event_code'] = $code;
	}

	update_option( 'lg_ga4_settings', $s, false );

	$checks = array();
	if ( ! empty( $_POST['validate'] ) ) {
		$checks[] = 'GA4: ' . lg_ga4_validate();
		$checks[] = 'Meta: ' . lg_meta_check();
	}
	set_transient( 'lg_lead_notice_' . get_current_user_id(), array( $errors, $checks ), 120 );
	wp_safe_redirect( admin_url( 'options-general.php?page=lg-lead-events' ) );
	exit;
} );

/**
 * Sends a sample event to GA4's validation server (nothing is recorded). GA4 does not report an
 * unknown API secret there; the proof is a lead entry's "[GA4 HTTP 204]" note and the event in GA4.
 */
function lg_ga4_validate() {
	if ( '' === lg_ga4_api_secret() ) {
		return 'no API secret set, nothing would be sent.';
	}
	$response = wp_remote_post(
		sprintf( 'https://www.google-analytics.com/debug/mp/collect?measurement_id=%s&api_secret=%s', rawurlencode( lg_ga4_measurement_id() ), rawurlencode( lg_ga4_api_secret() ) ),
		array(
			'body'    => wp_json_encode( array( 'client_id' => '123456789.' . time(), 'events' => array( array( 'name' => 'generate_lead', 'params' => array( 'form_name' => 'settings_check', 'engagement_time_msec' => 1 ) ) ) ) ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 8,
		)
	);
	if ( is_wp_error( $response ) ) {
		return 'could not reach GA4: ' . $response->get_error_message();
	}
	$json = json_decode( wp_remote_retrieve_body( $response ), true );
	$msgs = array();
	foreach ( (array) ( $json['validationMessages'] ?? array() ) as $m ) {
		$msgs[] = (string) ( $m['description'] ?? '' );
	}
	return $msgs ? 'GA4 reported: ' . implode( ' ', $msgs ) : 'reached, event format valid.';
}

/**
 * Checks the Meta settings without sending a real event: with a test event code a sample Lead goes to
 * Test Events; without one, only the token and pixel are checked (a GET of the dataset).
 */
function lg_meta_check() {
	if ( '' === lg_meta_pixel_id() || '' === lg_meta_access_token() ) {
		return 'pixel ID or access token missing, nothing would be sent.';
	}
	if ( '' !== lg_meta_test_event_code() ) {
		list( , $msg ) = lg_meta_post(
			array(
				'data'            => array( array( 'event_name' => 'Lead', 'event_time' => time(), 'event_id' => 'settings_check_' . time(), 'action_source' => 'website', 'user_data' => array( 'em' => array( lg_meta_hash( 'test@example.com' ) ), 'country' => array( lg_meta_hash( 'ca' ) ) ) ) ),
				'test_event_code' => lg_meta_test_event_code(),
			)
		);
		return $msg . ' (sample Lead sent to Test Events)';
	}
	$response = wp_remote_get( sprintf( 'https://graph.facebook.com/%s/%s?fields=id,name&access_token=%s', LG_META_GRAPH_VERSION, rawurlencode( lg_meta_pixel_id() ), rawurlencode( lg_meta_access_token() ) ), array( 'timeout' => 8 ) );
	if ( is_wp_error( $response ) ) {
		return 'could not reach Meta: ' . $response->get_error_message();
	}
	$json = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! empty( $json['id'] ) ) {
		return 'token works for dataset "' . (string) ( $json['name'] ?? $json['id'] ) . '".';
	}
	return 'Meta said: ' . (string) ( $json['error']['message'] ?? 'HTTP ' . wp_remote_retrieve_response_code( $response ) );
}

function lg_lead_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s      = lg_ga4_settings();
	$notice = get_transient( 'lg_lead_notice_' . get_current_user_id() );
	delete_transient( 'lg_lead_notice_' . get_current_user_id() );
	$locked = ' <em>Set in wp-config.php; change it there.</em>';
	$ends   = function ( $v ) { return 'ends in <code>' . esc_html( substr( (string) $v, -4 ) ) . '</code>'; };

	$secret_row = function ( $label, $key, $const, $current, $help ) use ( $s, $locked, $ends ) {
		echo '<tr><th scope="row"><label for="lg-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( defined( $const ) ) {
			echo '<p>Set (' . $ends( $current ) . ').' . $locked . '</p>'; // phpcs:ignore
		} else {
			echo '<input type="password" id="lg-' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="regular-text" autocomplete="new-password" value="">';
			echo '<p class="description">' . ( ! empty( $s[ $key ] ) ? 'Saved (' . $ends( $s[ $key ] ) . '). Leave blank to keep it. ' : 'Not set. ' ) . $help . '</p>'; // phpcs:ignore
			if ( ! empty( $s[ $key ] ) ) {
				echo '<label><input type="checkbox" name="clear_' . esc_attr( $key ) . '" value="1"> Remove it (stops sending)</label>';
			}
		}
		echo '</td></tr>';
	};
	$text_row = function ( $label, $key, $const, $current, $placeholder, $help ) use ( $s, $locked ) {
		echo '<tr><th scope="row"><label for="lg-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( defined( $const ) ) {
			echo '<p><code>' . esc_html( $current ) . '</code>' . $locked . '</p>'; // phpcs:ignore
		} else {
			echo '<input type="text" id="lg-' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="regular-text" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $s[ $key ] ?? '' ) . '">';
			echo '<p class="description">' . $help . '</p>'; // phpcs:ignore
		}
		echo '</td></tr>';
	};

	$ga4_on  = '' !== lg_ga4_api_secret();
	$meta_on = '' !== lg_meta_pixel_id() && '' !== lg_meta_access_token();
	?>
	<div class="wrap">
		<h1>Lead Events</h1>
		<?php if ( $notice ) : ?>
			<div class="notice <?php echo $notice[0] ? 'notice-warning' : 'notice-success'; ?> is-dismissible">
				<p>Settings saved.<?php foreach ( $notice[0] as $e ) { echo ' ' . esc_html( $e ); } ?></p>
				<?php foreach ( $notice[1] as $c ) : ?><p><strong>Check</strong> <?php echo esc_html( $c ); ?></p><?php endforeach; ?>
			</div>
		<?php endif; ?>
		<p>For every lead-form entry (Advertising Lead Capture, Calendly Booking, Call Request, Contact Form, VAC Status Checker) the server sends GA4 a <code>generate_lead</code> event and Meta a <code>Lead</code> event. Each entry's notes show what was sent.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lg_lead_save">
			<?php wp_nonce_field( 'lg_lead_save' ); ?>

			<h2>Google Analytics 4</h2>
			<p><strong>Status:</strong> <?php echo $ga4_on ? 'On' . ( lg_ga4_debug() ? ' (debug mode: DebugView only, not counted in reports)' : '' ) : 'Off (no API secret)'; ?></p>
			<table class="form-table" role="presentation">
				<?php
				$secret_row( 'Measurement Protocol API secret', 'api_secret', 'LG_GA4_API_SECRET', lg_ga4_api_secret(), 'GA4 › Admin › Data streams › the website stream › Measurement Protocol API secrets › Create.' );
				$text_row( 'Measurement ID', 'measurement_id', 'LG_GA4_MEASUREMENT_ID', lg_ga4_measurement_id(), LG_GA4_DEFAULT_MEASUREMENT_ID, 'Leave blank for the site\'s stream, ' . esc_html( LG_GA4_DEFAULT_MEASUREMENT_ID ) . '.' );
				?>
				<tr><th scope="row">Debug mode</th><td>
					<?php if ( defined( 'LG_GA4_DEBUG' ) ) : ?>
						<p><?php echo lg_ga4_debug() ? 'On' : 'Off'; ?>.<?php echo $locked; // phpcs:ignore ?></p>
					<?php else : ?>
						<label><input type="checkbox" name="debug" value="1" <?php checked( ! empty( $s['debug'] ) ); ?>> DebugView only (testing; leads are not counted in reports)</label>
					<?php endif; ?>
				</td></tr>
			</table>

			<h2>Meta Conversions API</h2>
			<p><strong>Status:</strong> <?php echo $meta_on ? 'On' . ( '' !== lg_meta_test_event_code() ? ' (test mode: events go to Test Events only, not used for ads)' : '' ) : 'Off (pixel ID and access token needed)'; ?></p>
			<table class="form-table" role="presentation">
				<?php
				$text_row( 'Pixel (dataset) ID', 'meta_pixel_id', 'LG_META_PIXEL_ID', lg_meta_pixel_id(), '', 'Events Manager › Data sources › your pixel: the number under its name.' );
				$secret_row( 'Conversions API access token', 'meta_access_token', 'LG_META_ACCESS_TOKEN', lg_meta_access_token(), 'Events Manager › your pixel › Settings › Conversions API › Generate access token.' );
				$text_row( 'Test event code (optional)', 'meta_test_event_code', 'LG_META_TEST_EVENT_CODE', lg_meta_test_event_code(), 'TEST12345', 'From Events Manager › Test Events. While set, real leads go to Test Events only. Clear it to go live.' );
				?>
			</table>

			<table class="form-table" role="presentation">
				<tr><th scope="row">Check after saving</th><td><label><input type="checkbox" name="validate" value="1"> Test the GA4 and Meta settings (GA4's validation server records nothing; Meta sends a sample to Test Events only when a test code is set)</label></td></tr>
			</table>
			<?php submit_button( 'Save' ); ?>
		</form>
	</div>
	<?php
}
