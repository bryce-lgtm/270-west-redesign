<?php
/**
 * Template Name: Resource archive
 *
 * One template behind /resources/guides/, /resources/stories/ and /resources/news/. Which
 * sub-types a page lists, which vocabulary its filter chips use and which card layout it draws
 * are per-page settings, so a new archive needs no new code.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
the_post();

// ACF's taxonomy field returns term objects; the importer's plain meta stores slugs. Normalise
// to slugs so the tax_query below works whichever supplied the value.
$types = array_filter( array_map(
	fn( $t ) => is_object( $t ) ? $t->slug : (string) $t,
	(array) ( w270_field( 'archive_types' ) ?: [] )
) );
$layout = w270_field( 'archive_layout' ) ?: 'library';
$filter = w270_field( 'archive_filter' ) ?: 'none';
$posts  = $types ? get_posts( [
	'post_type'   => 'resource',
	'numberposts' => -1,
	'post_status' => 'publish',
	'orderby'     => 'news' === $layout ? 'date' : [ 'menu_order' => 'ASC', 'date' => 'DESC' ],
	'order'       => 'DESC',
	'tax_query'   => [ [ 'taxonomy' => 'resource_type', 'field' => 'slug', 'terms' => $types ] ],
] ) : [];
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<section class="page-hero">
		<div class="page-hero-kicker"><span class="page-hero-kicker-rule"></span>Resources</div>
		<h1 class="page-hero-h1"><?php echo esc_html( w270_field( 'hero_h1' ) ?: get_the_title() ); ?></h1>
		<?php if ( $lead = w270_field( 'hero_lead' ) ) : ?><p class="page-hero-lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>
		<?php if ( $sub = w270_field( 'hero_sub' ) ) : ?><p class="page-hero-sub"><?php echo esc_html( $sub ); ?></p><?php endif; ?>
	</section>
	<?php get_template_part( 'template-parts/archive/' . $layout, null, [ 'posts' => $posts, 'filter' => $filter ] ); ?>
</main>
<?php
get_footer();
