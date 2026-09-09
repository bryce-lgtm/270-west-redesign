<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$home = esc_url( home_url( '/' ) );
?>
<header class="site-header">
  <a href="<?php echo $home; ?>" class="logo" aria-label="270 West Veteran Services — home"><img src="<?php echo esc_url( W270_ASSETS . '/img/compass-light.svg' ); ?>" alt="" class="logo-mark" width="42" height="35"/><span class="logo-lockup"><span class="logo-name">270<span class="logo-name-west">West</span></span><span class="logo-rule" aria-hidden="true"></span><span class="logo-tag">Veteran Services</span></span></a>
  <nav>
    <?php echo w270_menu( 'primary', 'primary' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
  </nav>
  <button class="mobile-menu-btn" aria-label="Open menu">Menu</button>
</header>
<nav class="mobile-nav">
  <?php echo w270_menu( 'mobile', 'mobile' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</nav>
