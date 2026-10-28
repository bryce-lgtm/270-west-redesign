<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
// An Elementor Pro single template whose conditions match job postings renders instead; this is the
// layout until (or unless) one is built.
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'single' ) ) {
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/job/single' );
	}
}
get_footer();
