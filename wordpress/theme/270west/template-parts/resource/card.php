<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$p       = $args['post'];
$main    = ! empty( $args['main'] );
$sub     = w270_subtype_slug( $p->ID );
$minutes = w270_read_time( $p->ID );
$cls     = $main ? 'resource-card-main' : 'resource-card-sm';
$tag     = $main
	? w270_type_label( $p->ID, true ) . ( $minutes ? " · {$minutes} min read" : '' )
	: w270_type_label( $p->ID ) . ( $minutes ? " · {$minutes} min" : '' );
?>
<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="<?php echo esc_attr( $cls ); ?>">
	<?php if ( has_post_thumbnail( $p ) ) : ?>
		<div class="photo" style="aspect-ratio:16/10"><?php echo get_the_post_thumbnail( $p, 'large' ); ?></div>
	<?php endif; ?>
	<div class="<?php echo esc_attr( $cls ); ?>-body">
		<?php if ( $main ) : ?>
			<div class="resource-card-main-tag"><?php echo esc_html( $tag ); ?></div>
			<h3 class="resource-card-main-h"><?php echo esc_html( get_the_title( $p ) ); ?></h3>
			<div class="resource-card-main-link">Read<?php echo 'guides' === $sub ? ' guide' : ''; ?> →</div>
		<?php else : ?>
			<div>
				<div class="resource-card-sm-tag"><?php echo esc_html( $tag ); ?></div>
				<h3 class="resource-card-sm-h"><?php echo esc_html( get_the_title( $p ) ); ?></h3>
			</div>
			<div class="resource-card-sm-link">Read →</div>
		<?php endif; ?>
	</div>
</a>
