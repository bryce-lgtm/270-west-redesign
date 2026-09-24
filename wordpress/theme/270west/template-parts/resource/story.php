<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'w270_embed_url' ) ) {
	/**
	 * Normalises the pasted URL to an embeddable one. Editors paste watch/share links far more
	 * often than embed links, and a raw watch link renders as a silent blank frame because the
	 * provider refuses to be framed.
	 */
	function w270_embed_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) { return ''; }
		if ( preg_match( '~youtube\.com/watch\?(?:.*&)?v=([\w-]+)~i', $url, $m )
			|| preg_match( '~youtu\.be/([\w-]+)~i', $url, $m ) ) {
			return 'https://www.youtube.com/embed/' . $m[1];
		}
		if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~i', $url, $m ) ) {
			return 'https://player.vimeo.com/video/' . $m[1];
		}
		// Already an embed URL, or a provider we do not know: pass it through if it is https.
		return preg_match( '~^https://~i', $url ) ? $url : '';
	}
}

$quote    = w270_field( 'pull_quote' );
$name     = w270_field( 'veteran_name' );
$role     = w270_field( 'veteran_role' ) ?: 'Canadian Armed Forces Veteran';
$video    = w270_embed_url( w270_field( 'video_url' ) );
$duration = w270_field( 'duration' );
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<section class="page-hero">
		<div class="page-hero-kicker"><span class="page-hero-kicker-rule"></span>Veteran story</div>
		<h1 class="page-hero-h1"><?php echo esc_html( w270_field( 'seo_h1' ) ?: get_the_title() ); ?></h1>
	</section>
	<section class="story-single">
		<?php if ( $video ) : ?>
			<div class="story-single-video">
				<iframe src="<?php echo esc_url( $video, [ 'https' ] ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>"
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
