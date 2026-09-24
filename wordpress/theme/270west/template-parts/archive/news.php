<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$posts = $args['posts'];
$cats  = get_terms( [ 'taxonomy' => 'news_category', 'hide_empty' => true ] );
$byMonth = [];
foreach ( $posts as $p ) { $byMonth[ get_the_date( 'F Y', $p ) ][] = $p; }
?>
<section class="archive archive-news">
	<div class="archive-toolbar">
		<div class="archive-count">Latest updates from the team</div>
		<div class="news-cats" role="group" aria-label="Filter updates by category">
			<button type="button" class="filter-chip" data-filter="all" aria-pressed="true">All updates</button>
			<?php foreach ( $cats as $c ) : ?>
				<button type="button" class="filter-chip" data-filter="<?php echo esc_attr( $c->name ); ?>" aria-pressed="false"><?php echo esc_html( $c->name ); ?></button>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="news-months">
		<?php foreach ( $byMonth as $month => $group ) :
			$id = 'm-' . sanitize_title( $month ); ?>
			<section class="news-month" aria-labelledby="<?php echo esc_attr( $id ); ?>">
				<h2 class="news-month-h" id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $month ); ?></h2>
				<ol class="news-entries">
					<?php foreach ( $group as $p ) :
						$names = wp_get_object_terms( $p->ID, 'news_category', [ 'fields' => 'names' ] );
						$href  = w270_field( 'external_link', $p->ID ) ?: get_permalink( $p ); ?>
						<?php // initArchives() reads the category from the .news-cat text below, so no data attribute is needed. ?>
						<li class="news-entry">
							<article>
								<div class="news-entry-meta">
									<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $p ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y', $p ) ); ?></time>
									<?php foreach ( $names as $n ) : ?><span class="news-cat"><?php echo esc_html( $n ); ?></span><?php endforeach; ?>
								</div>
								<h3 class="news-entry-h"><a href="<?php echo esc_url( $href ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></h3>
								<p class="news-entry-p"><?php echo esc_html( w270_field( 'summary', $p->ID ) ?: get_the_excerpt( $p ) ); ?></p>
								<a class="news-entry-more" href="<?php echo esc_url( $href ); ?>">Read update →</a>
							</article>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endforeach; ?>
	</div>
	<p class="archive-empty" hidden>No updates in that category yet. <button type="button" class="link-button" data-filter="all">Show all updates</button></p>
</section>
