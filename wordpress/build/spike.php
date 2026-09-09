<?php
// Data-format spike: creates a throwaway Elementor page to inspect rendered markup. Delete the page afterwards.
$site = getenv( 'W270_SITE' ) ?: '/Users/Bryce/Local Sites/270-west/app/public';
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = '270-west.local';
require $site . '/wp-load.php';
wp_set_current_user( 1 );

$id = fn() => substr( md5( uniqid( '', true ) ), 0, 7 );
$elements = [ [
	'id' => $id(), 'elType' => 'container', 'isInner' => false,
	'settings' => [ 'content_width' => 'full', 'html_tag' => 'section', 'css_classes' => 'w-con section-pad journey-section' ],
	'elements' => [
		[ 'id' => $id(), 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => [ 'title' => 'Four steps,<br>at your pace.', 'header_size' => 'h2', '_css_classes' => 'w-heading display-lg' ], 'elements' => [] ],
		[ 'id' => $id(), 'elType' => 'widget', 'widgetType' => 'text-editor', 'settings' => [ 'editor' => '<p>A clear, unhurried process.</p>', '_css_classes' => 'w-text journey-intro' ], 'elements' => [] ],
		[ 'id' => $id(), 'elType' => 'container', 'isInner' => true,
		  'settings' => [ 'content_width' => 'full', 'html_tag' => 'a', 'link' => [ 'url' => home_url( '/eligibility/' ), 'is_external' => '', 'nofollow' => '' ], 'css_classes' => 'w-con lm-card lm-card-dark' ],
		  'elements' => [
			[ 'id' => $id(), 'elType' => 'widget', 'widgetType' => 'button', 'settings' => [ 'text' => 'Book a free consult →', 'link' => [ 'url' => home_url( '/book-a-consult/' ) ], '_css_classes' => 'w-btn btn-accent' ], 'elements' => [] ],
			[ 'id' => $id(), 'elType' => 'widget', 'widgetType' => 'html', 'settings' => [ 'html' => '<div class="hero-quote-scrim"></div>', '_css_classes' => 'w-html' ], 'elements' => [] ],
			[ 'id' => $id(), 'elType' => 'widget', 'widgetType' => 'image', 'settings' => [ 'image' => [ 'url' => get_stylesheet_directory_uri() . '/assets/img/veteran-sarah.jpg', 'id' => '' ], 'image_size' => 'full', '_css_classes' => 'w-image' ], 'elements' => [] ],
		  ] ],
	],
] ];

$page = get_page_by_path( 'spike', OBJECT, 'page' );
$pid  = $page ? $page->ID : wp_insert_post( [ 'post_title' => 'Spike', 'post_name' => 'spike', 'post_type' => 'page', 'post_status' => 'publish' ] );
update_post_meta( $pid, '_elementor_edit_mode', 'builder' );
update_post_meta( $pid, '_elementor_template_type', 'wp-page' );
update_post_meta( $pid, '_elementor_version', ELEMENTOR_VERSION );
update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
update_post_meta( $pid, '_elementor_page_settings', [ 'hide_title' => 'yes' ] );
delete_post_meta( $pid, '_elementor_css' );
\Elementor\Plugin::$instance->files_manager->clear_cache();
echo "spike page {$pid}: " . get_permalink( $pid ) . "\n";
