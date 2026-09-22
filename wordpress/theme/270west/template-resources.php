<?php
/**
 * Template Name: Resources landing
 * Lists Guides, Checklists and Explainers: "Start here" (featured) + Library grouped by Topic.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
the_post();
$hero_h1   = w270_field( 'hero_h1' ) ?: get_the_title();
$hero_lead = w270_field( 'hero_lead' ) ?: '';
$hero_sub  = w270_field( 'hero_sub' ) ?: get_the_excerpt();
$types     = w270_resource_types();
$featured  = get_posts( [ 'post_type' => $types, 'numberposts' => 3, 'meta_key' => 'featured', 'meta_value' => '1', 'orderby' => [ 'menu_order' => 'ASC', 'date' => 'DESC' ] ] );
if ( ! $featured ) { $featured = get_posts( [ 'post_type' => $types, 'numberposts' => 3, 'orderby' => 'date', 'order' => 'DESC' ] ); }
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<section class="page-hero">
		<div class="page-hero-kicker"><span class="page-hero-kicker-rule"></span>Resources</div>
		<h1 class="page-hero-h1"><?php echo esc_html( $hero_h1 ); ?></h1>
		<?php if ( $hero_lead ) : ?><p class="page-hero-lead"><?php echo esc_html( $hero_lead ); ?></p><?php endif; ?>
		<?php if ( $hero_sub ) : ?><p class="page-hero-sub"><?php echo esc_html( $hero_sub ); ?></p><?php endif; ?>
	</section>
	<?php if ( $featured ) : ?>
	<section class="section-pad">
		<h2 class="res-start-h">Start here</h2>
		<div class="resources-featured">
			<?php foreach ( $featured as $i => $p ) { get_template_part( 'template-parts/resource/card', null, [ 'post' => $p, 'main' => 0 === $i ] ); } ?>
		</div>
	</section>
	<?php endif; ?>
	<?php get_template_part( 'template-parts/resource/library', null, [ 'exclude' => wp_list_pluck( $featured, 'ID' ) ] ); ?>
</main>
<?php
get_footer();
