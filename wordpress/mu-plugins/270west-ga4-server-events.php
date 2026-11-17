<?php
/**
 * Plugin Name: 270 West GA4 Server-Side Lead Events
 * Description: Sends one GA4 Measurement Protocol "generate_lead" event for every saved lead-form entry, tied to the visitor's GA4 session.
 * Version: 1.0.0
 *
 * Must-use plugin, so a theme or plugin change cannot switch it off. Deployed by
 * wordpress/build/deploy-godaddy.sh and deploy-siteground.sh to wp-content/mu-plugins/.
 *
 * Configuration: wp-admin › Settings › GA4 Lead Events (administrators only). The API secret comes from
 * GA4 › Admin › Data streams › web › Measurement Protocol API secrets. Without it the plugin does nothing.
 *
 * Each setting can instead be fixed in wp-config.php, which then overrides the settings page:
 *
 *   define( 'LG_GA4_API_SECRET', '…' );
 *   define( 'LG_GA4_MEASUREMENT_ID', 'G-…' );     // defaults to the 270westconsulting.ca stream
 *   define( 'LG_GA4_DEBUG', true );               // debug_mode=1 (DebugView), blocking request, error_log
 *
 * The API secret is never kept in this file or the repo.
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

/** The settings, as the settings page and lg_ga4_setting() see them. */
function lg_ga4_fields() {
	return array(
		'api_secret'     => array(
			'const'   => 'LG_GA4_API_SECRET',
			'type'    => 'secret',
			'default' => '',
			'label'   => 'Measurement Protocol API secret',
			'help'    => 'GA4 › Admin › Data streams › your web stream › Measurement Protocol API secrets › Create. Nothing is sent until this is saved.',
		),
		'measurement_id' => array(
			'const'   => 'LG_GA4_MEASUREMENT_ID',
			'type'    => 'text',
			// 270westconsulting.ca web stream (the G- ID both the Site Kit tag and GTM-53JCMLGB send to).
			'default' => 'G-QRPV310VRP',
			'pattern' => '/^G-[A-Z0-9]{4,20}$/',
			'label'   => 'Measurement ID',
			'help'    => 'The web stream the secret belongs to, for example G-QRPV310VRP.',
		),
		'debug'          => array(
			'const'   => 'LG_GA4_DEBUG',
			'type'    => 'bool',
			'default' => false,
			'label'   => 'Debug mode',
			'help'    => 'Shows the events in GA4 DebugView and holds each form response until GA4 answers. Testing only.',
		),
	);
}

function lg_ga4_setting( $key ) {
	return lg_tracking_setting( 'lg_ga4_settings', lg_ga4_fields(), $key );
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
	return lg_ga4_parse_session_id( lg_ga4_cookie( '_ga_' . str_replace( 'G-', '', lg_ga4_setting( 'measurement_id' ) ) ) );
}

add_action( 'gform_after_submission', 'lg_ga4_send_lead', 10, 2 );

function lg_ga4_send_lead( $entry, $form ) {
	if ( '' === lg_ga4_setting( 'api_secret' ) || '' === lg_ga4_setting( 'measurement_id' ) ) {
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
	if ( lg_ga4_setting( 'debug' ) ) {
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
	if ( ! lg_ga4_setting( 'debug' ) ) {
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
		rawurlencode( lg_ga4_setting( 'measurement_id' ) ),
		rawurlencode( lg_ga4_setting( 'api_secret' ) )
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

		if ( lg_ga4_setting( 'debug' ) ) {
			error_log(
				'[LG GA4] entry ' . $item['entry_id'] . ': '
				. ( is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $response ) )
				. ' ' . wp_json_encode( $item['body'] )
			);
		}
	}
}

add_action( 'admin_menu', 'lg_ga4_admin_menu' );
add_action( 'admin_init', 'lg_ga4_admin_init' );

function lg_ga4_page() {
	return array(
		'slug'   => 'lg-ga4-lead-events',
		'option' => 'lg_ga4_settings',
		'title'  => 'GA4 Lead Events',
		'fields' => lg_ga4_fields(),
		'ready'  => '' !== lg_ga4_setting( 'api_secret' ),
		'intro'  => 'Sends one GA4 "generate_lead" event for every saved lead-form entry, from the server, so ad blockers and browser settings cannot drop it. Each entry gets a note saying what was sent.',
	);
}

function lg_ga4_admin_menu() {
	lg_tracking_add_page( lg_ga4_page() );
}

function lg_ga4_admin_init() {
	lg_tracking_register( lg_ga4_page() );
}

/*
 * Settings helpers, shared with 270west-meta-capi.php. A setting fixed as a constant in wp-config.php
 * wins; otherwise the value saved on the settings page; otherwise the field's default.
 */

function lg_tracking_setting( $option, array $fields, $key ) {
	if ( ! isset( $fields[ $key ] ) ) {
		return '';
	}
	$field = $fields[ $key ];
	if ( defined( $field['const'] ) ) {
		$value = constant( $field['const'] );
	} else {
		$saved = get_option( $option, array() );
		$value = is_array( $saved ) && isset( $saved[ $key ] ) && '' !== $saved[ $key ] ? $saved[ $key ] : $field['default'];
	}
	return 'bool' === $field['type'] ? (bool) $value : trim( (string) $value );
}

/**
 * Cleans a submitted settings form. A secret left blank keeps the saved one (the page never shows it),
 * unless its "remove" box is ticked. A value that fails its pattern keeps the saved one.
 */
function lg_tracking_sanitize( $option, array $fields, $input ) {
	$input = is_array( $input ) ? $input : array();
	$old   = get_option( $option, array() );
	$old   = is_array( $old ) ? $old : array();
	$out   = array();
	foreach ( $fields as $key => $field ) {
		$before = isset( $old[ $key ] ) ? $old[ $key ] : '';
		if ( defined( $field['const'] ) ) {
			$out[ $key ] = $before;
			continue;
		}
		if ( 'bool' === $field['type'] ) {
			$out[ $key ] = ! empty( $input[ $key ] );
			continue;
		}
		$value = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? trim( sanitize_text_field( (string) $input[ $key ] ) ) : '';
		if ( 'secret' === $field['type'] ) {
			if ( ! empty( $input[ $key . '_remove' ] ) ) {
				$value = '';
			} elseif ( '' === $value ) {
				$value = $before;
			}
		} elseif ( '' !== $value && ! empty( $field['pattern'] ) && ! preg_match( $field['pattern'], $value ) ) {
			if ( function_exists( 'add_settings_error' ) ) {
				add_settings_error( $option, $key, $field['label'] . ': that value does not look right, so the previous one was kept.' );
			}
			$value = $before;
		}
		$out[ $key ] = $value;
	}
	return $out;
}

function lg_tracking_register( array $page ) {
	if ( false === get_option( $page['option'] ) ) {
		// Not autoloaded: it holds a secret and is only read when a form is submitted.
		add_option( $page['option'], array(), '', false );
	}
	register_setting(
		$page['slug'],
		$page['option'],
		array(
			'type'              => 'array',
			'sanitize_callback' => function ( $input ) use ( $page ) {
				return lg_tracking_sanitize( $page['option'], $page['fields'], $input );
			},
		)
	);
}

function lg_tracking_add_page( array $page ) {
	add_options_page(
		$page['title'],
		$page['title'],
		'manage_options',
		$page['slug'],
		function () use ( $page ) {
			lg_tracking_render_page( $page );
		}
	);
}

function lg_tracking_render_page( array $page ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$option = $page['option'];
	$saved  = get_option( $option, array() );
	$saved  = is_array( $saved ) ? $saved : array();
	echo '<div class="wrap"><h1>' . esc_html( $page['title'] ) . '</h1>';
	echo '<p>' . esc_html( $page['intro'] ) . '</p>';
	if ( ! empty( $page['warning'] ) ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html( $page['warning'] ) . '</p></div>';
	}
	echo '<p><strong>Status:</strong> ' . ( $page['ready'] ? 'On. Events are sent for new lead entries.' : 'Off. Nothing is sent until the secret below is saved.' ) . '</p>';
	echo '<form method="post" action="options.php">';
	settings_fields( $page['slug'] );
	echo '<table class="form-table" role="presentation">';
	foreach ( $page['fields'] as $key => $field ) {
		$id   = $option . '_' . $key;
		$name = $option . '[' . $key . ']';
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
		if ( defined( $field['const'] ) ) {
			echo '<p><em>Set in wp-config.php (' . esc_html( $field['const'] ) . '), which overrides this page.</em></p>';
		} elseif ( 'bool' === $field['type'] ) {
			echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . ( ! empty( $saved[ $key ] ) ? ' checked' : '' ) . '> On</label>';
		} elseif ( 'secret' === $field['type'] ) {
			// The saved secret is never printed back into the page.
			$has = isset( $saved[ $key ] ) && '' !== $saved[ $key ];
			echo '<input type="password" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="" autocomplete="new-password" placeholder="' . ( $has ? 'Saved. Leave blank to keep it.' : 'Not set' ) . '">';
			if ( $has ) {
				echo '<p><label><input type="checkbox" name="' . esc_attr( $option . '[' . $key . '_remove]' ) . '" value="1"> Remove the saved value</label></p>';
			}
		} else {
			$value = isset( $saved[ $key ] ) ? (string) $saved[ $key ] : '';
			echo '<input type="text" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( (string) $field['default'] ) . '">';
		}
		if ( ! empty( $field['help'] ) ) {
			echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	submit_button();
	echo '</form></div>';
}
