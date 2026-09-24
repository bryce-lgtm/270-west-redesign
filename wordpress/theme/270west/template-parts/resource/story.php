<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$quote    = w270_field( 'pull_quote' );
$name     = w270_field( 'veteran_name' );
$role     = w270_field( 'veteran_role' ) ?: 'Canadian Armed Forces Veteran';
$video    = w270_field( 'video_url' );
$duration = w270_field( 'duration' );
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<section class="page-hero">
		<div class="page-hero-kicker"><span class="page-hero-kicker-rule"></span>Veteran story</div>
		<h1 class="page-hero-h1"><?php echo esc_html( get_the_title() ); ?></h1>
	</section>
	<section class="story-single">
		<?php if ( $video ) : ?>
			<div class="story-single-video">
				<iframe src="<?php echo esc_url( $video ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>"
					loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"
					allowfullscreen></iframe>
			</div>
		<?php elseif ( has_post_thumbnail() ) : ?>
			<div class="photo story-single-photo"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>
		<?php if ( $quote ) : ?>
			<blockquote class="story-single-quote"><?php echo esc_html( $quote ); ?></blockquote>
		<?php endif; ?>
		<?php if ( $name ) : ?>
			<div class="story-single-cite">
				<span class="story-card-name"><?php echo esc_html( $name ); ?></span>
				<span class="story-card-role"><?php echo esc_html( $role ); ?><?php echo $duration ? ' · ' . esc_html( $duration ) . ' min' : ''; ?></span>
			</div>
		<?php endif; ?>
		<div class="article-body"><?php echo apply_filters( 'the_content', get_the_content() ); ?></div>
	</section>
</main>
