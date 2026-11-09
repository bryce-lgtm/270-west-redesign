<?php
/**
 * Plugin Name: 270 West GA4 Server-Side Lead Events
 * Description: Sends one GA4 Measurement Protocol "generate_lead" event for every saved lead-form entry, tied to the visitor's GA4 session.
 * Version: 1.0.0
 *
 * Must-use plugin, so a theme or plugin change cannot switch it off. Deployed by
 * wordpress/build/deploy-godaddy.sh and deploy-siteground.sh to wp-content/mu-plugins/.
 *
 * Configuration (wp-config.php, above "That's all, stop editing!"):
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

if ( ! defined( 'LG_GA4_MEASUREMENT_ID' ) ) {
	// 270westconsulting.ca web stream (the G- ID both the Site Kit tag and GTM-53JCMLGB send to).
	define( 'LG_GA4_MEASUREMENT_ID', 'G-QRPV310VRP' );
}
if ( ! defined( 'LG_GA4_DEBUG' ) ) {
	define( 'LG_GA4_DEBUG', false );
}
// LG_GA4_API_SECRET comes from wp-config.php only.

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
	return lg_ga4_parse_session_id( lg_ga4_cookie( '_ga_' . str_replace( 'G-', '', LG_GA4_MEASUREMENT_ID ) ) );
}

add_action( 'gform_after_submission', 'lg_ga4_send_lead', 10, 2 );

function lg_ga4_send_lead( $entry, $form ) {
	if ( ! defined( 'LG_GA4_API_SECRET' ) || '' === (string) LG_GA4_API_SECRET || ! LG_GA4_MEASUREMENT_ID ) {
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
	if ( LG_GA4_DEBUG ) {
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
	$blocking = true;
	if ( ! LG_GA4_DEBUG ) {
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} elseif ( function_exists( 'litespeed_finish_request' ) ) {
			litespeed_finish_request();
		} else {
			$blocking = false;
		}
	}

	$url = sprintf(
		'https://www.google-analytics.com/mp/collect?measurement_id=%s&api_secret=%s',
		rawurlencode( LG_GA4_MEASUREMENT_ID ),
		rawurlencode( LG_GA4_API_SECRET )
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

		if ( LG_GA4_DEBUG ) {
			error_log(
				'[LG GA4] entry ' . $item['entry_id'] . ': '
				. ( is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $response ) )
				. ' ' . wp_json_encode( $item['body'] )
			);
		}
	}
}
