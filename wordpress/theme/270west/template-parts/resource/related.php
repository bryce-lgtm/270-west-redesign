<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$related = w270_related( get_the_ID() );
if ( ! $related ) { return; }
?>
<div>
	<div class="article-related-label">Related guides</div>
	<div class="article-related-links">
		<?php foreach ( $related as $p ) : ?>
			<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?> →</a>
		<?php endforeach; ?>
	</div>
</div>
