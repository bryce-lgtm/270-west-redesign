<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$posts = $args['posts'];
?>
<section class="archive archive-stories">
	<div class="story-grid">
		<?php foreach ( $posts as $p ) :
			$num   = (int) w270_field( 'story_number', $p->ID );
			$mins  = (int) w270_field( 'duration', $p->ID );
			$quote = w270_field( 'pull_quote', $p->ID );
			$name  = w270_field( 'veteran_name', $p->ID );
			$role  = w270_field( 'veteran_role', $p->ID ) ?: 'Canadian Armed Forces Veteran';
			$first = $name ? preg_replace( '/\s*\(.*$/', '', trim( explode( ' ', $name )[ count( explode( ' ', $name ) ) - 2 ] ?? $name ) ) : ''; ?>
			<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="story-card">
				<div class="story-card-media">
					<?php echo get_the_post_thumbnail( $p, 'large', [ 'loading' => 'lazy', 'decoding' => 'async' ] ); ?>
					<span class="story-card-play" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
				</div>
				<div class="story-card-body">
					<div class="story-card-meta">Story <?php echo esc_html( str_pad( (string) $num, 2, '0', STR_PAD_LEFT ) ); ?> · Video<?php echo $mins ? ' · ' . $mins . ' min' : ''; ?></div>
					<?php if ( $quote ) : ?><blockquote class="story-card-quote"><?php echo esc_html( $quote ); ?></blockquote><?php endif; ?>
					<div class="story-card-cite">
						<span class="story-card-name"><?php echo esc_html( $name ); ?></span>
						<span class="story-card-role"><?php echo esc_html( $role ); ?></span>
					</div>
					<span class="story-card-link">Watch<?php echo $first ? ' ' . esc_html( $first ) . "'s" : ''; ?> story →</span>
				</div>
			</a>
		<?php endforeach; ?>
	</div>
</section>
