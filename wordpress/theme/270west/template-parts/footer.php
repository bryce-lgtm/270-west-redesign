<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$img          = W270_ASSETS . '/img/';
$topo_opacity = is_front_page() ? '0.07' : '0.12';
$p            = fn( $slug ) => esc_url( home_url( '/' . $slug . '/' ) );
?>
<footer class="site-footer">
  <div id="footer-topo" data-decor="topoLinesSVG" data-decor-args='["#A1B6C2",<?php echo $topo_opacity; ?>]'></div>
  <div class="footer-tagline-band" style="position:relative">
    <div>
      <div class="footer-tagline">In service of <em>your story.</em></div>
      <div class="footer-pillars">
        <span>Privacy</span><span>Respect</span><span>Support</span><span class="footer-pillar-accent">No successful claim? No fee.</span>
      </div>
    </div>
    <div class="footer-compass" aria-hidden="true">
      <svg viewBox="0 0 352.72 291.31" width="116" height="96" fill="currentColor"><path fill-rule="evenodd" d="M176.13 0 143.57 116.33 78.72 78.77 116.27 143.59 0 176.13 116.47 208.72 79.77 272.1 171.75 225.33 167.85 291.31 184.41 291.31 180.51 225.37 272.49 272.08 234.94 207.27 352.72 176.26 235.61 143.48 273.09 78.77 208.62 116.11ZM146.16 139.62 171.75 48.19 171.75 165.47ZM214.57 137.35 211.06 124.82 249.01 102.84 226.98 140.89ZM102.8 102.84 140.84 124.88 137.29 137.34 124.83 140.89ZM212.64 146.16 304.07 171.75 186.79 171.75ZM48.23 180.52 171.5 180.52 169.66 214.5ZM188.38 219.53 226.16 209.59 250.06 250.87ZM125.19 211.16 160.71 221.1 102.2 250.85Z"/></svg>
    </div>
  </div>
  <div class="footer-grid-5">
    <div>
      <div class="footer-lockup"><img src="<?php echo esc_url( $img . 'compass-light.svg' ); ?>" alt="" width="34" height="29"/><div><div class="footer-brand">270 West</div><div class="footer-brand-tag">Veteran Services</div></div></div>
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
  <div class="footer-bottom"><span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> 270 West Consulting · <a href="<?php echo $p( 'privacy' ); ?>" style="color:inherit">Privacy</a> · <a href="<?php echo $p( 'terms' ); ?>" style="color:inherit">Terms</a> · <a href="<?php echo $p( 'accessibility' ); ?>" style="color:inherit">Accessibility</a></span><span>In service of your story.</span></div>
</footer>
