<?php
/**
 * Calendly bookings → Gravity Forms → Creatio.
 *
 * Calendly posts an "invitee.created" webhook to /wp-json/w270/v1/calendly for every booking. This
 * endpoint checks Calendly's signature, turns the booking into a "Calendly Booking" form entry with
 * GFAPI::submit_form(), and the Webhooks add-on feed "Creatio Webhook - Calendly Booking" sends it on
 * to Creatio, the same path the status checker uses (inc/checker.php). The importer creates the form
 * and feed (gravity-forms*.json).
 *
 * The webhook subscription itself lives in Calendly. Settings → Calendly creates it from a personal
 * access token the admin pastes in; the token is used for that one request and never stored.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function w270_calendly_form_id() {
	$ids = get_option( 'w270_form_ids', [] );
	return (int) ( $ids['booking'] ?? 0 );
}

function w270_calendly_webhook_url() {
	return rest_url( 'w270/v1/calendly' );
}

/** The secret Calendly signs each webhook with. Created on first use, kept in the database. */
function w270_calendly_signing_key() {
	$key = (string) get_option( 'w270_calendly_signing_key', '' );
	if ( '' === $key ) {
		$key = wp_generate_password( 48, false, false );
		update_option( 'w270_calendly_signing_key', $key, false );
	}
	return $key;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'w270/v1', '/calendly', [
		'methods'             => 'POST',
		'permission_callback' => '__return_true', // authenticated by the signature below
		'callback'            => 'w270_calendly_receive',
	] );
} );

/**
 * Calendly-Webhook-Signature: "t=<unix time>,v1=<hex HMAC-SHA256 of "<t>.<raw body>">".
 * Rejects stale timestamps (over 3 minutes) so a captured request cannot be replayed later.
 */
function w270_calendly_signature_ok( $header, $body, $key, $now = null ) {
	$parts = [];
	foreach ( explode( ',', (string) $header ) as $kv ) {
		[ $k, $v ] = array_pad( explode( '=', trim( $kv ), 2 ), 2, '' );
		$parts[ $k ] = $v;
	}
	if ( empty( $parts['t'] ) || empty( $parts['v1'] ) ) { return false; }
	if ( abs( ( $now ?? time() ) - (int) $parts['t'] ) > 180 ) { return false; }
	return hash_equals( hash_hmac( 'sha256', $parts['t'] . '.' . $body, $key ), $parts['v1'] );
}

function w270_calendly_receive( WP_REST_Request $req ) {
	if ( ! w270_calendly_signature_ok( $req->get_header( 'calendly_webhook_signature' ), $req->get_body(), w270_calendly_signing_key() ) ) {
		return new WP_Error( 'w270_calendly_signature', 'Invalid signature.', [ 'status' => 401 ] );
	}
	$data = json_decode( $req->get_body(), true );
	// Only new bookings become leads. Cancellations (and the cancel half of a reschedule) are acknowledged
	// so Calendly does not retry them.
	if ( 'invitee.created' !== ( $data['event'] ?? '' ) ) {
		return [ 'ok' => true, 'ignored' => $data['event'] ?? '' ];
	}
	$payload = (array) ( $data['payload'] ?? [] );

	// Calendly retries anything that is not a 2xx; never create the same booking twice.
	$uri  = (string) ( $payload['uri'] ?? '' );
	$seen = 'w270_cal_' . md5( $uri );
	if ( '' !== $uri && get_transient( $seen ) ) { return [ 'ok' => true, 'duplicate' => true ]; }

	$form_id = w270_calendly_form_id();
	if ( ! class_exists( 'GFAPI' ) || ! $form_id ) {
		return new WP_Error( 'w270_calendly_unavailable', 'Booking form not available.', [ 'status' => 503 ] );
	}
	$values = w270_calendly_values( $payload );
	$form   = GFAPI::get_form( $form_id );
	foreach ( $form['fields'] ?? [] as $field ) {
		$k = 'input_' . $field->id;
		if ( 'hidden' === $field->type && '' !== (string) $field->defaultValue && ! isset( $values[ $k ] ) ) {
			$values[ $k ] = $field->defaultValue;
		}
	}
	$result = GFAPI::submit_form( $form_id, $values );
	if ( is_wp_error( $result ) || empty( $result['is_valid'] ) ) {
		error_log( 'w270 calendly: submit_form failed: ' . ( is_wp_error( $result ) ? $result->get_error_message() : wp_json_encode( $result['validation_messages'] ?? [] ) ) );
		return new WP_Error( 'w270_calendly_failed', 'Could not record the booking.', [ 'status' => 500 ] );
	}
	if ( '' !== $uri ) { set_transient( $seen, 1, 14 * DAY_IN_SECONDS ); }
	return [ 'ok' => true ];
}

/** Map a Calendly invitee payload onto the "Calendly Booking" form's inputs. */
function w270_calendly_values( array $p ) {
	$clean = fn( $v, $len = 200 ) => mb_substr( sanitize_text_field( (string) $v ), 0, $len );
	$name  = trim( (string) ( $p['name'] ?? '' ) );
	$first = trim( (string) ( $p['first_name'] ?? '' ) );
	$last  = trim( (string) ( $p['last_name'] ?? '' ) );
	if ( '' === $first ) {
		$parts = preg_split( '/\s+/', $name, 2 );
		$first = $parts[0] ?? '';
		$last  = $parts[1] ?? '';
	}
	$event    = (array) ( $p['scheduled_event'] ?? [] );
	$location = (array) ( $event['location'] ?? [] );
	$answers  = (array) ( $p['questions_and_answers'] ?? [] );

	// Phone: the number Calendly calls (outbound call), else the SMS reminder number, else a "phone" question.
	$phone = 'outbound_call' === ( $location['type'] ?? '' ) ? ( $location['location'] ?? '' ) : '';
	$phone = $phone ?: ( $p['text_reminder_number'] ?? '' );
	foreach ( $answers as $qa ) {
		if ( ! $phone && preg_match( '/phone/i', (string) ( $qa['question'] ?? '' ) ) ) { $phone = $qa['answer'] ?? ''; }
	}

	$start = '';
	if ( ! empty( $event['start_time'] ) ) {
		try {
			// The team works in Atlantic time; use it unless the site has its own timezone set.
			$tz    = 'UTC' === wp_timezone_string() || '+00:00' === wp_timezone_string() ? new DateTimeZone( 'America/Halifax' ) : wp_timezone();
			$start = ( new DateTimeImmutable( $event['start_time'] ) )->setTimezone( $tz )->format( 'D, M j, Y, g:i A T' );
		} catch ( Exception $e ) {
			$start = (string) $event['start_time'];
		}
	}
	$where = [ 'outbound_call' => 'Phone call (we call)', 'inbound_call' => 'Phone call (they call)', 'physical' => 'In person', 'zoom' => 'Zoom', 'google_conference' => 'Google Meet', 'microsoft_teams_conference' => 'Microsoft Teams' ][ $location['type'] ?? '' ] ?? ( $location['type'] ?? '' );

	// Creatio's Commentary gets one line, like the checker's.
	$line = [ 'Calendly booking' . ( ! empty( $p['old_invitee'] ) ? ' (rescheduled)' : '' ) ];
	if ( $start ) { $line[] = 'When: ' . $start; }
	if ( $where ) { $line[] = 'How: ' . $where; }
	foreach ( $answers as $qa ) {
		if ( '' !== trim( (string) ( $qa['answer'] ?? '' ) ) ) { $line[] = rtrim( $clean( $qa['question'] ?? '', 80 ), ' .:?' ) . ': ' . rtrim( $clean( $qa['answer'], 400 ), ' .;' ); }
	}

	$values = [
		'input_1_3' => $clean( $first, 80 ),
		'input_1_6' => $clean( $last, 80 ),
		'input_2'   => sanitize_email( $p['email'] ?? '' ),
		'input_5'   => $clean( $phone, 40 ),
		'input_20'  => implode( '; ', $line ) . '.',
		'input_40'  => $start,
		'input_41'  => $clean( $event['name'] ?? '', 120 ),
		'input_42'  => esc_url_raw( $p['uri'] ?? '' ),
	];
	// Campaign codes: main.js forwards the visitor's utm_* to Calendly, which returns them under "tracking".
	$tracking = (array) ( $p['tracking'] ?? [] );
	foreach ( [ 12 => 'utm_source', 13 => 'utm_medium', 14 => 'utm_term', 17 => 'utm_campaign' ] as $fid => $k ) {
		$values[ "input_{$fid}" ] = $clean( $tracking[ $k ] ?? '' );
	}
	return $values;
}

// ── Settings → Calendly: create the webhook subscription ──
add_action( 'admin_menu', function () {
	add_options_page( 'Calendly', 'Calendly', 'manage_options', 'w270-calendly', 'w270_calendly_settings_page' );
} );

/** One Calendly API call with the pasted token. Returns [status, decoded body]. */
function w270_calendly_api( $method, $path, $token, $body = null ) {
	$r = wp_remote_request( 'https://api.calendly.com' . $path, [
		'method'  => $method,
		'timeout' => 20,
		'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
		'body'    => null === $body ? null : wp_json_encode( $body ),
	] );
	if ( is_wp_error( $r ) ) { return [ 0, [ 'message' => $r->get_error_message() ] ]; }
	return [ (int) wp_remote_retrieve_response_code( $r ), (array) json_decode( wp_remote_retrieve_body( $r ), true ) ];
}

/** Calendly answers a token without the right permissions with 403 and a required_scopes list: name them. */
function w270_calendly_scopes_hint( array $res ) {
	$scopes = (array) ( $res['required_scopes'] ?? $res['details']['required_scopes'] ?? [] );
	return $scopes ? ' Create a token with these scopes: ' . implode( ', ', array_map( 'sanitize_text_field', $scopes ) ) . '.' : '';
}

function w270_calendly_subscribe( $token ) {
	[ $code, $me ] = w270_calendly_api( 'GET', '/users/me', $token );
	if ( 200 !== $code ) { return 'Calendly did not accept the token (' . $code . ': ' . ( $me['message'] ?? 'unknown error' ) . ').' . w270_calendly_scopes_hint( $me ); }
	$user = $me['resource']['uri'] ?? '';
	$org  = $me['resource']['current_organization'] ?? '';
	$base = [ 'url' => w270_calendly_webhook_url(), 'events' => [ 'invitee.created', 'invitee.canceled' ], 'organization' => $org, 'signing_key' => w270_calendly_signing_key() ];
	// Organization scope covers every team member's bookings; it needs an owner/admin token, so fall back to the token's own user.
	[ $code, $res ] = w270_calendly_api( 'POST', '/webhook_subscriptions', $token, $base + [ 'scope' => 'organization' ] );
	if ( 403 === $code ) {
		[ $code, $res ] = w270_calendly_api( 'POST', '/webhook_subscriptions', $token, $base + [ 'scope' => 'user', 'user' => $user ] );
	}
	if ( 201 === $code ) {
		update_option( 'w270_calendly_subscription', [ 'uri' => $res['resource']['uri'] ?? '', 'scope' => $res['resource']['scope'] ?? '', 'created' => current_time( 'mysql' ) ], false );
		return true;
	}
	if ( 409 === $code ) {
		return 'Calendly already has a webhook for this site. Delete it in Calendly first if it was created with a different signing key.';
	}
	$detail = $res['message'] ?? '';
	if ( ! empty( $res['details'] ) ) { $detail .= ' ' . wp_json_encode( $res['details'] ); }
	return 'Calendly refused the webhook (' . $code . ': ' . trim( $detail ) . ').' . ( 403 === $code ? w270_calendly_scopes_hint( $res ) : ' Webhooks need a paid Calendly plan (Standard or above).' );
}

function w270_calendly_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$notice = '';
	if ( isset( $_POST['w270_calendly_token'] ) && check_admin_referer( 'w270_calendly' ) ) {
		$token = trim( sanitize_text_field( wp_unslash( $_POST['w270_calendly_token'] ) ) );
		$r     = '' === $token ? 'Paste a personal access token first.' : w270_calendly_subscribe( $token );
		$notice = true === $r
			? '<div class="notice notice-success"><p>Webhook created. New Calendly bookings will now reach Creatio.</p></div>'
			: '<div class="notice notice-error"><p>' . esc_html( $r ) . '</p></div>';
	}
	$sub = get_option( 'w270_calendly_subscription', [] );
	?>
	<div class="wrap">
		<h1>Calendly</h1>
		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput -- built above with esc_html ?>
		<p>Each Calendly booking is sent to this site, saved as a <strong>Calendly Booking</strong> form entry and passed to Creatio by that form's webhook feed.</p>
		<table class="form-table" role="presentation">
			<tr><th scope="row">Webhook URL</th><td><code><?php echo esc_html( w270_calendly_webhook_url() ); ?></code></td></tr>
			<tr><th scope="row">Status</th><td><?php echo $sub ? esc_html( 'Connected (' . ( $sub['scope'] ?? '' ) . ' scope) on ' . ( $sub['created'] ?? '' ) ) : 'Not connected yet'; ?></td></tr>
			<tr><th scope="row">Booking form</th><td><?php echo w270_calendly_form_id() ? '<a href="' . esc_url( admin_url( 'admin.php?page=gf_entries&id=' . w270_calendly_form_id() ) ) . '">View entries</a>' : 'Not imported'; ?></td></tr>
		</table>
		<h2>Connect</h2>
		<p>In Calendly, go to <strong>Integrations &amp; apps → API and webhooks</strong>, generate a personal access token with the <strong>users:read</strong> and <strong>webhooks:write</strong> scopes, and paste it below. It is used once to create the webhook and is not saved.</p>
		<form method="post">
			<?php wp_nonce_field( 'w270_calendly' ); ?>
			<input type="password" name="w270_calendly_token" class="regular-text" autocomplete="off" placeholder="Personal access token" />
			<?php submit_button( $sub ? 'Create the webhook again' : 'Create webhook', 'primary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}
