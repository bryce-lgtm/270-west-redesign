<?php
/**
 * Plugin Name: 270 West Meta Conversions API Lead Events
 * Description: Sends one Meta Conversions API "Lead" event for every saved lead-form entry submitted by a visitor's browser.
 * Version: 1.0.0
 *
 * Must-use plugin, deployed next to 270west-ga4-server-events.php by wordpress/build/deploy-godaddy.sh
 * and deploy-siteground.sh. It uses that plugin's list of lead forms (lg_ga4_lead_forms()).
 *
 * Configuration: wp-admin › Settings › Meta Lead Events (administrators only). The access token comes
 * from Events Manager › dataset › Settings › Conversions API › Generate access token. Without it the
 * plugin does nothing.
 *
 * Each setting can instead be fixed in wp-config.php, which then overrides the settings page:
 *
 *   define( 'LG_META_CAPI_TOKEN', '…' );
 *   define( 'LG_META_PIXEL_ID', '…' );              // defaults to the 270 West dataset
 *   define( 'LG_META_TEST_EVENT_CODE', 'TEST…' );   // testing only: events also show under Events Manager › Test events
 *   define( 'LG_META_SEND_CONTACT', true );         // add SHA-256 hashed email, phone and name (off by default)
 *   define( 'LG_META_DEBUG', true );                // blocking request and an error_log line per event
 *   define( 'LG_META_API_VERSION', 'v27.0' );       // Graph API version (wp-config.php only)
 *
 * The access token is never kept in this file or the repo.
 *
 * One Lead per entry: the event ID is "gf_<entry id>". The browser pixel must NOT also fire Lead for
 * these forms (GTM tag "Meta Pixel - Form Submission"), or each lead is counted twice: the browser
 * does not know the entry ID, so Meta cannot deduplicate the two.
 *
 * Which entries: the lead forms only, never spam. Entries that do not come from the visitor's browser
 * are skipped: Calendly bookings arrive from Calendly's server, with no visitor IP, browser or Meta
 * cookies, and Meta rejects a website event without a browser. Those stay with the browser pixel.
 *
 * What is sent: event name, time, ID, the page URL, the visitor's IP and user agent, and the _fbp / _fbc
 * cookies (the ad click). No form answers and no custom data. Hashed contact details only when that
 * setting is on.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LG_META_API_VERSION' ) ) {
	define( 'LG_META_API_VERSION', 'v27.0' );
}

/** The settings, as the settings page and lg_meta_setting() see them. */
function lg_meta_fields() {
	return array(
		'token'           => array(
			'const'   => 'LG_META_CAPI_TOKEN',
			'type'    => 'secret',
			'default' => '',
			'label'   => 'Conversions API access token',
			'help'    => 'Events Manager › your dataset › Settings › Conversions API › Generate access token. Nothing is sent until this is saved.',
		),
		'pixel_id'        => array(
			'const'   => 'LG_META_PIXEL_ID',
			'type'    => 'text',
			// 270 West Consulting dataset (the pixel ID in GTM-53JCMLGB).
			'default' => '2183220535777678',
			'pattern' => '/^\d{8,20}$/',
			'label'   => 'Dataset (pixel) ID',
			'help'    => 'The dataset the token belongs to.',
		),
		'test_event_code' => array(
			'const'   => 'LG_META_TEST_EVENT_CODE',
			'type'    => 'text',
			'default' => '',
			'pattern' => '/^[A-Za-z0-9_-]{1,40}$/',
			'label'   => 'Test event code',
			'help'    => 'Testing only. From Events Manager › Test events. Clear it when testing is finished.',
		),
		'send_contact'    => array(
			'const'   => 'LG_META_SEND_CONTACT',
			'type'    => 'bool',
			'default' => false,
			'label'   => 'Send hashed contact details',
			'help'    => 'Adds the lead\'s email, phone and name, SHA-256 hashed, so Meta can match more leads to ads. Leave off unless this has been agreed for this dataset.',
		),
		'debug'           => array(
			'const'   => 'LG_META_DEBUG',
			'type'    => 'bool',
			'default' => false,
			'label'   => 'Debug mode',
			'help'    => 'Holds each form response until Meta answers and writes a line to the PHP error log. Testing only.',
		),
	);
}

/** A setting's value; empty or false while the GA4 plugin (which holds the shared helpers) is missing. */
function lg_meta_setting( $key ) {
	if ( ! function_exists( 'lg_tracking_setting' ) ) {
		return in_array( $key, array( 'send_contact', 'debug' ), true ) ? false : '';
	}
	return lg_tracking_setting( 'lg_meta_settings', lg_meta_fields(), $key );
}

/** True when the form is one of the lead forms (same list as the GA4 server events). */
function lg_meta_is_lead_form( $form ) {
	if ( ! function_exists( 'lg_ga4_lead_forms' ) ) {
		return false;
	}
	$title = trim( (string) ( $form['title'] ?? '' ) );
	return isset( lg_ga4_lead_forms()[ $title ] );
}

/** Meta's normalisation for hashed fields: trimmed, lower case, SHA-256. Empty in, empty out. */
function lg_meta_hash( $value ) {
	$value = strtolower( trim( (string) $value ) );
	return '' === $value ? '' : hash( 'sha256', $value );
}

/** Digits only, with the country code: a 10-digit North American number gets its leading 1. */
function lg_meta_phone( $value ) {
	$digits = preg_replace( '/\D+/', '', (string) $value );
	if ( 10 === strlen( $digits ) ) {
		$digits = '1' . $digits;
	}
	return strlen( $digits ) >= 7 ? $digits : '';
}

/**
 * The click ID Meta matches on. The pixel's _fbc cookie when there is one; otherwise built from an
 * fbclid still present in one of the URLs, in the cookie's own format (fb.1.<ms>.<fbclid>).
 */
function lg_meta_fbc( $cookie, array $urls, $now_ms ) {
	$cookie = trim( (string) $cookie );
	if ( preg_match( '/^fb\.\d+\.\d+\..+/', $cookie ) ) {
		return $cookie;
	}
	foreach ( $urls as $url ) {
		$query = (string) parse_url( (string) $url, PHP_URL_QUERY );
		if ( '' === $query ) {
			continue;
		}
		parse_str( $query, $args );
		$fbclid = isset( $args['fbclid'] ) && is_string( $args['fbclid'] ) ? trim( $args['fbclid'] ) : '';
		if ( '' !== $fbclid && preg_match( '/^[A-Za-z0-9_\-]{10,500}$/', $fbclid ) ) {
			return 'fb.1.' . $now_ms . '.' . $fbclid;
		}
	}
	return '';
}

/** Hashed email, phone, first and last name from the entry, by Gravity Forms field type. */
function lg_meta_contact( $entry, $form ) {
	$out = array();
	foreach ( (array) ( $form['fields'] ?? array() ) as $field ) {
		$type = is_object( $field ) ? ( $field->type ?? '' ) : ( $field['type'] ?? '' );
		$id   = (string) ( is_object( $field ) ? ( $field->id ?? '' ) : ( $field['id'] ?? '' ) );
		if ( 'email' === $type && empty( $out['em'] ) ) {
			$out['em'] = lg_meta_hash( $entry[ $id ] ?? '' );
		} elseif ( 'phone' === $type && empty( $out['ph'] ) ) {
			$phone     = lg_meta_phone( $entry[ $id ] ?? '' );
			$out['ph'] = '' === $phone ? '' : hash( 'sha256', $phone );
		} elseif ( 'name' === $type && empty( $out['fn'] ) ) {
			$out['fn'] = lg_meta_hash( $entry[ $id . '.3' ] ?? '' );
			$out['ln'] = lg_meta_hash( $entry[ $id . '.6' ] ?? '' );
		}
	}
	return array_filter( $out );
}

/**
 * The Conversions API event for an entry, or null when there is nothing Meta could match it on.
 * $visitor: ip, user_agent, fbp, fbc, url (all strings, any may be empty).
 */
function lg_meta_build_event( $entry_id, array $visitor, array $contact, $now ) {
	$user = array_filter(
		array(
			'client_ip_address' => (string) ( $visitor['ip'] ?? '' ),
			'client_user_agent' => (string) ( $visitor['user_agent'] ?? '' ),
			'fbp'               => (string) ( $visitor['fbp'] ?? '' ),
			'fbc'               => (string) ( $visitor['fbc'] ?? '' ),
		)
	);
	// A website event needs the browser's user agent; without it Meta rejects the event.
	if ( empty( $user['client_user_agent'] ) ) {
		return null;
	}
	foreach ( $contact as $key => $hash ) {
		$user[ $key ] = array( $hash );
	}
	return array(
		'event_name'       => 'Lead',
		'event_time'       => (int) $now,
		'event_id'         => 'gf_' . (int) $entry_id,
		'action_source'    => 'website',
		'event_source_url' => (string) ( $visitor['url'] ?? '' ),
		'user_data'        => $user,
	);
}

function lg_meta_cookie( $name ) {
	return isset( $_COOKIE[ $name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) ) : '';
}

/** True when this request was made by another server on the visitor's behalf (Calendly's webhook). */
function lg_meta_is_server_request() {
	return ! empty( $_SERVER['HTTP_CALENDLY_WEBHOOK_SIGNATURE'] );
}

add_action( 'gform_after_submission', 'lg_meta_send_lead', 11, 2 );

function lg_meta_send_lead( $entry, $form ) {
	if ( '' === lg_meta_setting( 'token' ) || '' === lg_meta_setting( 'pixel_id' ) ) {
		return;
	}
	if ( 'spam' === ( $entry['status'] ?? '' ) || ! lg_meta_is_lead_form( $form ) ) {
		return;
	}
	$entry_id = (int) ( $entry['id'] ?? 0 );
	if ( ! $entry_id ) {
		return;
	}
	// Once per saved entry, even if something re-runs the hook.
	if ( function_exists( 'gform_get_meta' ) && gform_get_meta( $entry_id, 'lg_meta_sent' ) ) {
		return;
	}
	if ( apply_filters( 'lg_meta_skip_entry', lg_meta_is_server_request(), $entry, $form ) ) {
		return;
	}

	$source  = (string) ( $entry['source_url'] ?? '' );
	$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	$visitor = array(
		'ip'         => (string) ( $entry['ip'] ?? '' ),
		'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
		'fbp'        => lg_meta_cookie( '_fbp' ),
		'fbc'        => lg_meta_fbc( lg_meta_cookie( '_fbc' ), array( $source, $referer ), (int) round( microtime( true ) * 1000 ) ),
		'url'        => $source ? $source : home_url( '/' ),
	);
	$contact = lg_meta_setting( 'send_contact' ) ? lg_meta_contact( $entry, $form ) : array();
	$event   = lg_meta_build_event( $entry_id, $visitor, $contact, time() );
	if ( ! $event ) {
		return;
	}

	$GLOBALS['lg_meta_queue'][] = array(
		'entry_id' => $entry_id,
		'event'    => $event,
		'click'    => ! empty( $visitor['fbc'] ),
		'contact'  => (bool) $contact,
	);
	if ( function_exists( 'gform_update_meta' ) ) {
		gform_update_meta( $entry_id, 'lg_meta_sent', time() );
	}
	if ( ! has_action( 'shutdown', 'lg_meta_flush' ) ) {
		// After the GA4 flush (9999), which has already closed the visitor's connection.
		add_action( 'shutdown', 'lg_meta_flush', 10000 );
	}
}

/**
 * Sends the queued events after the visitor has their response (same approach as lg_ga4_flush()).
 * The access token travels in the request body, never in a URL.
 */
function lg_meta_flush() {
	$queue = isset( $GLOBALS['lg_meta_queue'] ) ? $GLOBALS['lg_meta_queue'] : array();
	$GLOBALS['lg_meta_queue'] = array();
	if ( ! $queue ) {
		return;
	}
	$blocking = true;
	if ( ! lg_meta_setting( 'debug' ) ) {
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} elseif ( function_exists( 'litespeed_finish_request' ) ) {
			litespeed_finish_request();
		} else {
			$blocking = false;
		}
	}

	$url = sprintf( 'https://graph.facebook.com/%s/%s/events', rawurlencode( LG_META_API_VERSION ), rawurlencode( lg_meta_setting( 'pixel_id' ) ) );

	foreach ( $queue as $item ) {
		$body = array(
			'data'         => array( $item['event'] ),
			'access_token' => lg_meta_setting( 'token' ),
		);
		if ( '' !== lg_meta_setting( 'test_event_code' ) ) {
			$body['test_event_code'] = lg_meta_setting( 'test_event_code' );
		}
		$response = wp_remote_post(
			$url,
			array(
				'body'     => wp_json_encode( $body ),
				'headers'  => array( 'Content-Type' => 'application/json' ),
				'timeout'  => $blocking ? 5 : 2,
				'blocking' => $blocking,
			)
		);

		$result = '';
		if ( is_wp_error( $response ) ) {
			$result = 'request failed: ' . $response->get_error_message();
		} elseif ( $blocking ) {
			$code   = (int) wp_remote_retrieve_response_code( $response );
			$json   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			$result = 'Meta HTTP ' . $code;
			if ( isset( $json['events_received'] ) ) {
				$result .= ', events received ' . (int) $json['events_received'];
			} elseif ( isset( $json['error']['message'] ) ) {
				$result .= ', ' . mb_substr( sanitize_text_field( (string) $json['error']['message'] ), 0, 200 );
			}
		}

		if ( class_exists( 'GFAPI' ) ) {
			$note = 'Meta Lead sent as ' . $item['event']['event_id']
				. ( $item['click'] ? ' (ad click ID found' : ' (no ad click ID' )
				. ( $item['contact'] ? ', hashed contact details included)' : ')' )
				. ( '' !== lg_meta_setting( 'test_event_code' ) ? ' (test event code set)' : '' )
				. ( $result ? ' [' . $result . ']' : '' );
			GFAPI::add_note( $item['entry_id'], 0, 'Meta', $note );
		}

		if ( lg_meta_setting( 'debug' ) ) {
			// Never the token, and only which user_data keys were sent, not their values.
			error_log(
				'[LG Meta] entry ' . $item['entry_id'] . ': ' . ( $result ? $result : 'sent, not waited for' )
				. ' user_data=' . implode( ',', array_keys( $item['event']['user_data'] ) )
			);
		}
	}
}

add_action( 'admin_menu', 'lg_meta_admin_menu' );
add_action( 'admin_init', 'lg_meta_admin_init' );

function lg_meta_page() {
	return array(
		'slug'    => 'lg-meta-lead-events',
		'option'  => 'lg_meta_settings',
		'title'   => 'Meta Lead Events',
		'fields'  => lg_meta_fields(),
		'ready'   => '' !== lg_meta_setting( 'token' ),
		'intro'   => 'Sends one Meta "Lead" event for every saved lead-form entry, from the server, through the Conversions API. Each entry gets a note saying what was sent. Calendly bookings are not sent from here; they stay with the browser pixel.',
		'warning' => 'When this is on, the browser pixel must not also fire Lead for the forms and the status checker, or each lead is counted twice. In Google Tag Manager, limit the tag "Meta Pixel - Form Submission" to Calendly bookings at the same time as you save the token here.',
	);
}

function lg_meta_admin_menu() {
	if ( function_exists( 'lg_tracking_add_page' ) ) {
		lg_tracking_add_page( lg_meta_page() );
	}
}

function lg_meta_admin_init() {
	if ( function_exists( 'lg_tracking_register' ) ) {
		lg_tracking_register( lg_meta_page() );
	}
}
