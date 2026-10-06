<?php
/**
 * Creatio webhook enrichment.
 *
 * The Gravity Forms Webhooks feeds send each form's fields under the keys the client's original feed
 * used (UsrSource, UsrMedium, UsrTerm, UsrCampaign, Commentary…). Creatio's webhook service only
 * fills a column when the key equals the column code, and lookups only accept the record Id, so the
 * campaign values never reached the lead's Source and Channel. This filter adds, from the entry's
 * utm fields:
 *
 *   LeadSourceId  – Creatio "Lead source" lookup (Google AdWords, Facebook, Google, …)
 *   LeadMediumId  – Creatio "Lead channel" lookup (Web: paid search, Web: social, Email, …)
 *   BpmHref       – the page the form was submitted from (Creatio's landing-page tracking column)
 *
 * plus any extra text columns listed in W270_CREATIO_UTM_COLUMNS (the client's own utm_* columns).
 * Lookup Ids come from the client's Creatio instance (List setup of both lookups, 2026-10-06).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Lead source lookup Ids (Creatio "Lead sources"). */
function w270_creatio_sources() {
	return [
		'google-ads' => '6177c15b-5439-4c60-ba46-4d2eb201270e', // Google AdWords
		'google'     => 'a417d1e3-2029-4c17-8e15-c6a586d1a9b7', // Google (organic)
		'facebook'   => '532429b9-5324-407a-9c17-d1fdf4c3abc9',
		'linkedin'   => '6c7e2194-0b60-4b1c-a084-20a73d8cb06f',
		'twitter'    => '2c97826d-18ab-4e12-9e91-60a709315444',
		'mailchimp'  => '7ea0f0f3-cc41-4516-8ac8-d25f65b18a03',
		'other'      => 'f5e73b24-bd68-45ba-9ec6-dee40a35c615', // Other source
	];
}

/** Lead channel lookup Ids (Creatio "Lead channels"). Empty entries are not sent until the Id is known. */
function w270_creatio_channels() {
	return [
		'paid-search'      => '', // Web: paid search  (Id still to come from the client)
		'organic-search'   => '', // Web: organic search (Id still to come from the client)
		'social'           => 'fa3f5ad8-56da-4fcf-aa79-7033bdf62178', // Web: social
		'referral'         => 'cd64d8c3-746a-4c73-93ad-09a75ae71501', // Web: referrers
		'direct'           => 'e896a7ac-a6fe-43aa-a2cd-161b0faf65bb', // Web: direct traffic
		'display'          => '7e9f5358-e4ff-4139-a23a-0bfc4a1f1bb5', // Web: other online advertising
		'email'            => 'e95c0d56-e773-4a7c-81d8-148619beebb0', // Email
		'other'            => '22bcd15d-99ac-4ed1-bda9-2cdf7ca566ef', // Other channels
	];
}

/**
 * Text columns that carry the raw utm values, keyed by utm parameter. UtmSourceStr and
 * UtmCampaignStr are the codes behind the lead page's "utm_source" / "utm_campaign" fields
 * (client, 2026-10-06); the medium and term codes follow the same pattern and are harmless if absent.
 */
if ( ! defined( 'W270_CREATIO_UTM_COLUMNS' ) ) {
	define( 'W270_CREATIO_UTM_COLUMNS', [
		'utm_source'   => 'UtmSourceStr',
		'utm_campaign' => 'UtmCampaignStr',
		'utm_medium'   => 'UtmMediumStr',
		'utm_term'     => 'UtmTermStr',
	] );
}

/**
 * "Preferred Method of Communication" on the lead is column Column124 (client, 2026-10-06). Left
 * empty: sending the form's method Id under Column124Id made Creatio reject the whole lead with a
 * foreign-key error, so that column points at a different lookup than the one the form stores.
 * Set this once the lookup behind Column124 and its Ids are known.
 */
if ( ! defined( 'W270_CREATIO_COMMS_COLUMN' ) ) { define( 'W270_CREATIO_COMMS_COLUMN', '' ); }

/** Classify utm_source (+ medium) into a Lead source key. */
function w270_creatio_source_key( $source, $medium ) {
	$s = strtolower( trim( (string) $source ) );
	$m = strtolower( trim( (string) $medium ) );
	if ( '' === $s ) { return ''; }
	$paid = (bool) preg_match( '/cpc|ppc|paid|sem|ads?$/', $m );
	if ( preg_match( '/google|adwords|gads/', $s ) ) { return ( $paid || str_contains( $s, 'ads' ) || str_contains( $s, 'adwords' ) ) ? 'google-ads' : 'google'; }
	if ( preg_match( '/facebook|^fb$|meta|instagram|^ig$/', $s ) ) { return 'facebook'; }
	if ( str_contains( $s, 'linkedin' ) ) { return 'linkedin'; }
	if ( preg_match( '/twitter|^x$|x\.com/', $s ) ) { return 'twitter'; }
	if ( str_contains( $s, 'mailchimp' ) ) { return 'mailchimp'; }
	return 'other';
}

/** Classify utm_medium (+ source) into a Lead channel key. */
function w270_creatio_channel_key( $source, $medium ) {
	$s = strtolower( trim( (string) $source ) );
	$m = strtolower( trim( (string) $medium ) );
	if ( '' === $m && '' === $s ) { return ''; }
	if ( preg_match( '/social|facebook|instagram|linkedin|twitter|meta/', $m . ' ' . $s ) ) { return 'social'; }
	if ( preg_match( '/cpc|ppc|paid|sem/', $m ) ) { return 'paid-search'; }
	if ( preg_match( '/organic/', $m ) ) { return 'organic-search'; }
	if ( preg_match( '/email|newsletter|mail/', $m ) ) { return 'email'; }
	if ( preg_match( '/display|banner|video|audio|spotify|programmatic/', $m . ' ' . $s ) ) { return 'display'; }
	if ( preg_match( '/referral|referrer/', $m ) ) { return 'referral'; }
	if ( preg_match( '/direct|none/', $m ) ) { return 'direct'; }
	return 'other';
}

/** The entry's utm values, found through each field's dynamic-population parameter name. */
function w270_creatio_entry_utm( $entry, $form ) {
	$utm = [];
	foreach ( (array) $form['fields'] as $field ) {
		if ( ! empty( $field->inputName ) && preg_match( '/^utm_|^gclid$|^msclkid$/', $field->inputName ) ) {
			$utm[ $field->inputName ] = (string) rgar( $entry, (string) $field->id );
		}
	}
	return $utm;
}

add_filter( 'gform_webhooks_request_data', function ( $data, $feed, $entry, $form ) {
	if ( ! is_array( $data ) || empty( $feed['meta']['requestURL'] ) || ! str_contains( $feed['meta']['requestURL'], 'creatio.com' ) ) { return $data; }
	$utm    = w270_creatio_entry_utm( $entry, $form );
	$source = $utm['utm_source'] ?? '';
	$medium = $utm['utm_medium'] ?? '';

	$sk = w270_creatio_source_key( $source, $medium );
	$ck = w270_creatio_channel_key( $source, $medium );
	$sources  = w270_creatio_sources();
	$channels = w270_creatio_channels();
	if ( $sk && ! empty( $sources[ $sk ] ) )   { $data['LeadSourceId'] = $sources[ $sk ]; }
	if ( $ck && ! empty( $channels[ $ck ] ) )  { $data['LeadMediumId'] = $channels[ $ck ]; }

	// Creatio's own source tracking reads the utm marks out of BpmHref (a lead whose BpmHref has
	// none is filed under "Web: direct traffic", whatever LeadMediumId says), so the landing URL
	// always carries the campaign parameters the entry holds.
	$page = (string) rgar( $entry, 'source_url' );
	if ( $page && empty( $data['BpmHref'] ) ) {
		$missing = [];
		foreach ( $utm as $k => $v ) {
			if ( '' !== $v && ! preg_match( '/[?&]' . preg_quote( $k, '/' ) . '=/', $page ) ) { $missing[ $k ] = $v; }
		}
		$data['BpmHref'] = $missing ? add_query_arg( array_map( 'rawurlencode', $missing ), $page ) : $page;
	}

	foreach ( (array) W270_CREATIO_UTM_COLUMNS as $param => $column ) {
		if ( $column && isset( $utm[ $param ] ) && '' !== $utm[ $param ] ) { $data[ $column ] = $utm[ $param ]; }
	}

	// The feed already sends the preferred-method lookup Id as UsrCommsMethod; repeat it under the
	// lead's real column code (lookups take the Id suffix, so both spellings go out).
	if ( ! empty( $data['UsrCommsMethod'] ) && W270_CREATIO_COMMS_COLUMN ) {
		$data[ W270_CREATIO_COMMS_COLUMN . 'Id' ] = $data['UsrCommsMethod'];
		$data[ W270_CREATIO_COMMS_COLUMN ]        = $data['UsrCommsMethod'];
	}
	return $data;
}, 10, 4 );
