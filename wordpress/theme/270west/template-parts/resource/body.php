<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$summary = w270_field( 'summary' ) ?: get_the_excerpt();
?>
<article class="article-body">
	<?php if ( $summary ) : ?><p class="article-intro"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
	<?php if ( 'checklist' === get_post_type() ) { get_template_part( 'template-parts/resource/checklist-items' ); } ?>
	<?php echo $args['content']; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered post content ?>
	<?php if ( 'guide' === get_post_type() ) { get_template_part( 'template-parts/resource/callout' ); } ?>
</article>
