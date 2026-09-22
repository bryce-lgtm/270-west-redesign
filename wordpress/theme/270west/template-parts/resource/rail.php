<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<aside class="article-rail">
	<div class="article-rail-cta">
		<div class="article-rail-label">Need help?</div>
		<h3 class="article-rail-h">Talk to an advisor — free.</h3>
		<p class="article-rail-p">Two-minute eligibility check. No pressure, no obligation.</p>
		<a href="<?php echo esc_url( home_url( '/eligibility/' ) ); ?>" class="article-rail-btn">Check eligibility →</a>
	</div>
	<?php get_template_part( 'template-parts/resource/related' ); ?>
</aside>
