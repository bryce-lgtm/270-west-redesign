<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$topic   = w270_topic( get_the_ID() );
$seo_h1  = w270_field( 'seo_h1' );
$minutes = w270_read_time( get_the_ID() );
?>
<section class="res-header">
	<div class="res-crumb">
		<a href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">Resources</a>
		<?php if ( $topic ) : ?>
			<span>/</span>
			<span class="res-crumb-current"><a href="<?php echo esc_url( home_url( '/resources/#topic-' . $topic->slug ) ); ?>"><?php echo esc_html( $topic->name ); ?></a></span>
		<?php endif; ?>
	</div>
	<?php if ( $seo_h1 && 0 !== strcasecmp( trim( $seo_h1 ), get_the_title() ) ) : ?>
		<h1 class="res-title"><?php echo esc_html( $seo_h1 ); ?></h1>
		<h2 class="page-hero-lead"><?php the_title(); ?></h2>
	<?php else : ?>
		<h1 class="res-title"><?php the_title(); ?></h1>
	<?php endif; ?>
	<div class="res-meta">
		<?php if ( $minutes ) : ?><span>● <?php echo (int) $minutes; ?> min read</span><?php endif; ?>
		<span>Updated <?php echo esc_html( get_the_modified_date( 'F Y' ) ); ?></span>
		<span>By 270 West Consulting</span>
	</div>
</section>
