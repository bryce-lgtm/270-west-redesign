<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$content  = apply_filters( 'the_content', get_the_content() );
$show_toc = w270_field( 'show_toc' );
$show_toc = null === $show_toc ? true : (bool) $show_toc;
$toc      = ( 'guide' === get_post_type() && $show_toc ) ? w270_toc( $content ) : [];
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<?php get_template_part( 'template-parts/resource/hero' ); ?>
	<section class="article-layout">
		<?php get_template_part( 'template-parts/resource/toc', null, [ 'toc' => $toc ] ); ?>
		<?php get_template_part( 'template-parts/resource/body', null, [ 'content' => $content ] ); ?>
		<?php get_template_part( 'template-parts/resource/rail' ); ?>
	</section>
</main>
