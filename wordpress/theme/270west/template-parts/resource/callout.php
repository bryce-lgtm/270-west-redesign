<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$c = w270_field( 'callout' );
if ( ! $c || empty( $c['text'] ) ) { return; }
?>
<div class="article-callout">
	<div class="article-callout-label"><?php echo esc_html( $c['label'] ?: 'Helpful tip' ); ?></div>
	<p class="article-callout-p"><?php echo esc_html( $c['text'] ); ?></p>
</div>
