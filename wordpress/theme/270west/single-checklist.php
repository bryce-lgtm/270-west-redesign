<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) {
	the_post();
	get_template_part( 'template-parts/resource/single' );
}
get_footer();
