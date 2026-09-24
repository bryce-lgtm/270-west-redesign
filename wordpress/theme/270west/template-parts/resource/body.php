<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$summary = w270_field( 'summary' ) ?: get_the_excerpt();
?>
<article class="article-body">
	<?php if ( $summary ) : ?><p class="article-intro"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
	<?php if ( 'checklists' === w270_subtype_slug() ) { get_template_part( 'template-parts/resource/checklist-items' ); } ?>
	<?php echo $args['content']; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered post content ?>
	<?php if ( 'guides' === w270_subtype_slug() ) { get_template_part( 'template-parts/resource/callout' ); } ?>
</article>
