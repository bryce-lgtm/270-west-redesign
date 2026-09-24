<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$posts  = $args['posts'];
$filter = $args['filter'];
$terms  = 'topic' === $filter ? get_terms( [ 'taxonomy' => 'resource_topic', 'hide_empty' => true ] ) : [];
?>
<section class="archive archive-library">
	<?php if ( $terms ) : ?>
		<div class="archive-toolbar">
			<div class="archive-count"><?php echo count( $posts ); ?> resources</div>
			<div class="archive-filters" role="group" aria-label="Filter resources by topic">
				<button type="button" class="filter-chip" data-filter="all" aria-pressed="true">All</button>
				<?php foreach ( $terms as $t ) : ?>
					<button type="button" class="filter-chip" data-filter="<?php echo esc_attr( $t->slug ); ?>" aria-pressed="false"><?php echo esc_html( $t->name ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
	<div class="archive-grid">
		<?php foreach ( $posts as $p ) :
			$topic = w270_topic( $p->ID );
			$mins  = w270_read_time( $p->ID ); ?>
			<a class="guide-card" href="<?php echo esc_url( get_permalink( $p ) ); ?>"
				data-topic="<?php echo esc_attr( $topic ? $topic->slug : '' ); ?>">
				<div class="guide-card-tag"><?php echo esc_html( w270_type_label( $p->ID, (bool) w270_field( 'featured', $p->ID ) ) ); ?><?php echo $mins ? ' · ' . (int) $mins . ' min read' : ''; ?></div>
				<h3 class="guide-card-h"><?php echo esc_html( get_the_title( $p ) ); ?></h3>
				<p class="guide-card-p"><?php echo esc_html( w270_field( 'summary', $p->ID ) ?: get_the_excerpt( $p ) ); ?></p>
				<span class="guide-card-link">Read →</span>
			</a>
		<?php endforeach; ?>
	</div>
	<p class="archive-empty" hidden>Nothing in that topic yet. <button type="button" class="link-button" data-filter="all">Show all</button></p>
</section>
