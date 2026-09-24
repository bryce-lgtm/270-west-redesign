<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$home = esc_url( home_url( '/' ) );
?>
<header class="site-header">
  <a href="<?php echo $home; ?>" class="logo" aria-label="270 West Consulting home"><img src="<?php echo esc_url( W270_ASSETS . '/img/brand/270west-horizontal-light.svg' ); ?>" alt="" class="logo-full" width="168" height="45"/></a>
  <nav>
    <?php echo w270_menu( 'primary', 'primary' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
  </nav>
  <button class="mobile-menu-btn" aria-label="Open menu">Menu</button>
</header>
<nav class="mobile-nav">
  <?php echo w270_menu( 'mobile', 'mobile' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</nav>
