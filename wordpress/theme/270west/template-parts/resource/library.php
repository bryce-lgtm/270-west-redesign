<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$exclude = array_map( 'intval', $args['exclude'] ?? [] );
$types   = w270_resource_types();
$groups  = [];
$terms   = get_terms( [ 'taxonomy' => 'resource_topic', 'hide_empty' => true, 'orderby' => 'id', 'order' => 'ASC' ] );
foreach ( is_wp_error( $terms ) ? [] : $terms as $term ) {
	$posts = get_posts( [
		'post_type' => $types, 'numberposts' => -1, 'post__not_in' => $exclude,
		'tax_query' => [ [ 'taxonomy' => 'resource_topic', 'terms' => $term->term_id ] ],
		'orderby' => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
	] );
	if ( $posts ) { $groups[] = [ 'slug' => $term->slug, 'name' => $term->name, 'posts' => $posts ]; }
}
$orphans = get_posts( [
	'post_type' => $types, 'numberposts' => -1, 'post__not_in' => $exclude, 'orderby' => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
	'tax_query' => [ [ 'taxonomy' => 'resource_topic', 'operator' => 'NOT EXISTS' ] ],
] );
if ( $orphans ) { $groups[] = [ 'slug' => 'other', 'name' => 'Other', 'posts' => $orphans ]; }
if ( ! $groups ) { return; }
?>
<section class="section-pad res-library">
	<div class="res-library-head">
		<div class="mono-label">Library</div>
		<h2 class="res-library-h">Browse VAC resources by topic</h2>
	</div>
	<?php foreach ( $groups as $g ) : ?>
		<div class="library-topic" id="topic-<?php echo esc_attr( $g['slug'] ); ?>">
			<div class="library-topic-grid">
				<h3 class="library-topic-h"><?php echo esc_html( $g['name'] ); ?></h3>
				<div class="library-items">
					<?php foreach ( $g['posts'] as $p ) : ?>
						<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="library-item"><?php echo esc_html( get_the_title( $p ) ); ?><span class="library-item-arrow">→</span></a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</section>
