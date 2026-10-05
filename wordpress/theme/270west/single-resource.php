<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
// An Elementor Pro single template whose conditions match this resource renders instead (Hello's
// own templates do the same); the PHP layouts below are the fallback without Pro.
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'single' ) ) {
	while ( have_posts() ) {
		the_post();
		// Stories and News have their own layouts; guides, checklists and explainers share the
		// article layout in template-parts/resource/single.php.
		$w270_sub  = w270_subtype_slug();
		$w270_part = in_array( $w270_sub, [ 'stories', 'news' ], true ) ? $w270_sub : 'single';
		get_template_part( 'template-parts/resource/' . ( 'stories' === $w270_part ? 'story' : $w270_part ) );
	}
}
get_footer();
