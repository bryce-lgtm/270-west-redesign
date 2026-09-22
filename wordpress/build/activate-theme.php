<?php
// Activates the 270west child theme. Run: "$PHP" -c "$INI" wordpress/build/activate-theme.php
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; } // never runnable over HTTP
$site = getenv( 'W270_SITE' ) ?: '/Users/Bryce/Local Sites/270-west/app/public';
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = getenv( 'W270_HOST' ) ?: '270-west.local';
require $site . '/wp-load.php';
if ( get_stylesheet() !== '270west' ) {
	switch_theme( '270west' );
	echo "switched to 270west\n";
} else {
	echo "270west already active\n";
}
