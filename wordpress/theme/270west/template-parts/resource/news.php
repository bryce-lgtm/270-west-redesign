<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$cats = wp_get_object_terms( get_the_ID(), 'news_category', [ 'fields' => 'names' ] );
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<section class="page-hero">
		<div class="page-hero-kicker"><span class="page-hero-kicker-rule"></span>News</div>
		<h1 class="page-hero-h1"><?php echo esc_html( get_the_title() ); ?></h1>
		<div class="news-entry-meta">
			<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
			<?php foreach ( $cats as $c ) : ?><span class="news-cat"><?php echo esc_html( $c ); ?></span><?php endforeach; ?>
		</div>
	</section>
	<section class="article-layout article-layout-narrow">
		<div class="article-body"><?php echo apply_filters( 'the_content', get_the_content() ); ?></div>
	</section>
</main>
