<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cats = wp_get_object_terms( get_the_ID(), 'news_category', [ 'fields' => 'names' ] );
// One category per entry, matching w270_topic() and w270_subtype(): the design shows a single
// chip, and adjacent spans with no separator read as one run-on string.
$cat  = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0] : '';
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<section class="page-hero">
		<div class="page-hero-kicker"><span class="page-hero-kicker-rule"></span>News</div>
		<h1 class="page-hero-h1"><?php echo esc_html( w270_field( 'seo_h1' ) ?: get_the_title() ); ?></h1>
		<div class="news-entry-meta">
			<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
			<?php if ( $cat ) : ?><span class="news-cat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
		</div>
	</section>
	<section class="article-layout article-layout-narrow">
		<div class="article-body"><?php echo apply_filters( 'the_content', get_the_content() ); ?></div>
	</section>
</main>
