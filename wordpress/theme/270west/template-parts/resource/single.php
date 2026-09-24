<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$sub = w270_subtype_slug();

// Stories and News have their own layouts; guides, checklists and explainers share the
// article layout they have always used.
if ( 'stories' === $sub ) { get_template_part( 'template-parts/resource/story' ); return; }
if ( 'news' === $sub )    { get_template_part( 'template-parts/resource/news' );  return; }

$content  = apply_filters( 'the_content', get_the_content() );
$show_toc = w270_field( 'show_toc' );
$show_toc = null === $show_toc ? true : (bool) $show_toc;
$toc      = ( 'guides' === $sub && $show_toc ) ? w270_toc( $content ) : [];
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<?php get_template_part( 'template-parts/resource/hero' ); ?>
	<section class="article-layout">
		<?php get_template_part( 'template-parts/resource/toc', null, [ 'toc' => $toc ] ); ?>
		<?php get_template_part( 'template-parts/resource/body', null, [ 'content' => $content ] ); ?>
		<?php get_template_part( 'template-parts/resource/rail' ); ?>
	</section>
</main>
