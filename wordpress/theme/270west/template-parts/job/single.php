<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$w270_id    = get_the_ID();
$w270_open  = w270_job_is_open( $w270_id );
$w270_pay   = w270_job_pay( $w270_id );
$w270_close = w270_job_closing( $w270_id );
$w270_back  = home_url( '/about/careers/#opportunities' );
?>
<main id="content" <?php post_class( 'site-main' ); ?>>
	<section class="page-hero job-hero">
		<a class="job-back" href="<?php echo esc_url( $w270_back ); ?>">← All open roles</a>
		<h1 class="page-hero-h1"><?php the_title(); ?></h1>
		<p class="job-meta"><?php echo esc_html( implode( ' · ', w270_job_meta( $w270_id ) ) ); ?></p>
		<?php if ( $w270_pay || ( $w270_open && $w270_close ) ) : ?>
			<p class="job-meta job-meta-sub">
				<?php echo esc_html( implode( ' · ', array_filter( [ $w270_pay, $w270_open && $w270_close ? 'Apply by ' . wp_date( 'F j, Y', strtotime( $w270_close ) ) : '' ] ) ) ); ?>
			</p>
		<?php endif; ?>
		<?php if ( $w270_open ) : ?>
			<div class="page-hero-ctas"><a href="#apply" class="btn-accent">Apply for this role →</a></div>
		<?php endif; ?>
	</section>

	<section class="section-pad">
		<div class="section-body-narrow prose-list job-body"><?php the_content(); ?></div>
	</section>

	<section class="section-pad section-tint" id="apply" style="background:var(--bg2)">
		<?php if ( $w270_open && w270_job_form_id() && function_exists( 'gravity_form' ) ) : ?>
			<div class="section-head">
				<h2 class="sec-h2">Apply for this role</h2>
				<p class="sec-p">Send us your resume and, if you like, a short note. We read every application and will be in touch if your experience is a good fit.</p>
			</div>
			<div class="section-body-narrow job-apply">
				<div class="w270-gf"><?php gravity_form( w270_job_form_id(), false, false, false, null, true ); ?></div>
			</div>
		<?php else : ?>
			<div class="section-head">
				<h2 class="sec-h2">This role is no longer accepting applications</h2>
				<p class="sec-p">Take a look at our other opportunities, or get in touch if you would like to work with us.</p>
				<div class="section-ctas"><a href="<?php echo esc_url( $w270_back ); ?>" class="btn-accent">View current opportunities →</a></div>
			</div>
		<?php endif; ?>
	</section>
</main>
