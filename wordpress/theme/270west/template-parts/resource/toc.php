<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$toc = $args['toc'] ?? [];
if ( ! $toc ) { return; } // no aside at all: .article-layout then drops the column
?>
<aside class="article-toc">
	<div class="article-toc-label">Contents</div>
	<div class="article-toc-links">
		<?php foreach ( $toc as $i => $h ) : ?>
			<a href="#<?php echo esc_attr( $h['id'] ); ?>"<?php echo 0 === $i ? ' class="active"' : ''; ?>><?php echo esc_html( $h['text'] ); ?></a>
		<?php endforeach; ?>
	</div>
</aside>
