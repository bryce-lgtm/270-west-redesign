<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
// The main sections, each linked only while its page exists (a code deploy can reach a site before
// the pages it names have been created there).
$w270_links = array_filter( [
	[ 'services', 'Our services', 'Help with VAC claims, reviews and appeals, reassessments and ongoing support.' ],
	[ 'how-it-works', 'How it works', 'What happens when you work with 270 West, from the first conversation on.' ],
	[ 'vac-benefits-support-options', 'Your options', 'Compare managing it yourself, free support and dedicated navigation.' ],
	[ 'resources/stories', 'Veteran stories', 'Veterans describe their experience in their own words.' ],
	[ 'faq', 'Questions and answers', 'Common questions about VAC claims and working with us.' ],
	[ 'contact', 'Contact us', 'Reach our team by phone, email or mail.' ],
], fn( $l ) => (bool) get_page_by_path( $l[0] ) );
?>
<main id="content" class="site-main">
	<section class="page-hero">
		<h1 class="page-hero-h1">Page not found</h1>
		<p class="page-hero-sub">The page you are looking for may have moved, or the link may be out of date. These are good places to start.</p>
		<div class="page-hero-ctas">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn-accent">Go to the homepage →</a>
			<?php if ( get_page_by_path( 'book-a-consult' ) ) : ?>
				<a href="<?php echo esc_url( home_url( '/book-a-consult/' ) ); ?>" class="btn-ink">Book a Free Call</a>
			<?php endif; ?>
		</div>
	</section>
	<?php if ( $w270_links ) : ?>
		<section class="section-pad">
			<ul class="commitments-grid commitments-grid-3 notfound-links">
				<?php foreach ( $w270_links as [ $w270_path, $w270_label, $w270_text ] ) : ?>
					<li class="commitment notfound-link">
						<a href="<?php echo esc_url( home_url( "/{$w270_path}/" ) ); ?>">
							<h2 class="commitment-h"><?php echo esc_html( $w270_label ); ?></h2>
							<p class="commitment-p"><?php echo esc_html( $w270_text ); ?></p>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</main>
<script>
// GoDaddy caches every response for a month, 404s included, and replaces WordPress's no-cache headers
// for visitors, so a browser that saw this URL before its page was published keeps the 404. Ask the
// server once, past the browser cache: if the page exists now, that refreshes the cached copy and a
// reload shows it. Once per URL per session, so a real 404 never loops.
( function () {
	var key = 'w270-404-' + location.pathname;
	try { if ( sessionStorage.getItem( key ) ) { return; } sessionStorage.setItem( key, '1' ); } catch ( e ) { return; }
	fetch( location.href, { cache: 'reload', credentials: 'same-origin' } ).then( function ( r ) {
		if ( r.ok ) { location.reload(); }
	} ).catch( function () {} );
} )();
</script>
<?php
get_footer();
