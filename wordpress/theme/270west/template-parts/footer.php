<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$p = fn( $slug ) => esc_url( home_url( '/' . $slug . '/' ) );
?>
<footer class="site-footer">
  <div id="footer-topo"></div>
  <div class="footer-grid-5">
    <div>
      <div class="footer-lockup"><img src="<?php echo esc_url( W270_ASSETS . '/img/brand/270west-horizontal-light.svg' ); ?>" alt="270 West Consulting" width="186" height="50"/></div>
      <div style="line-height:1.6">Helping Canadian veterans secure the benefits they’ve earned.<br>Made in Canada · BBB Accredited</div>
    </div>
    <div>
      <div class="footer-col-label">Explore</div>
      <div class="footer-col-items"><?php echo w270_menu( 'footer-explore', 'flat' ); // phpcs:ignore ?></div>
    </div>
    <div>
      <div class="footer-col-label">Services</div>
      <div class="footer-col-items"><?php echo w270_menu( 'footer-services', 'flat' ); // phpcs:ignore ?></div>
    </div>
    <div>
      <div class="footer-col-label">Get in touch</div>
      <div class="footer-col-items"><a href="mailto:info@270westconsulting.ca">info@270westconsulting.ca</a></div>
    </div>
    <div>
      <div class="footer-col-label">Languages</div>
      <div class="footer-col-items"><span>English</span><span>Français (Canada)</span></div>
    </div>
  </div>
  <div class="footer-bottom"><span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> 270 West Consulting · <a href="<?php echo $p( 'privacy' ); ?>" style="color:inherit">Privacy</a> · <a href="<?php echo $p( 'terms' ); ?>" style="color:inherit">Terms</a> · <a href="<?php echo $p( 'accessibility' ); ?>" style="color:inherit">Accessibility</a></span><span>Veteran-founded · Canadian-owned</span></div>
</footer>
