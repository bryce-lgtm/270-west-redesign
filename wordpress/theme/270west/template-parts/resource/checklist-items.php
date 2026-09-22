<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$items = w270_field( 'items' );
$pdf   = w270_field( 'download_pdf' );
if ( $items ) : ?>
	<ol class="res-checklist">
		<?php foreach ( $items as $row ) : ?>
			<li><span class="res-check" aria-hidden="true"></span><span><?php echo esc_html( $row['item'] ); ?><?php if ( ! empty( $row['note'] ) ) : ?><span class="res-checklist-note"><?php echo esc_html( $row['note'] ); ?></span><?php endif; ?></span></li>
		<?php endforeach; ?>
	</ol>
<?php endif;
if ( $pdf && ( $url = wp_get_attachment_url( (int) $pdf ) ) ) : ?>
	<a href="<?php echo esc_url( $url ); ?>" class="btn-ink res-pdf" download>Download the PDF checklist</a>
<?php endif; ?>
