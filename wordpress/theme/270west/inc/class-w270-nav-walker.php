<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Renders menu items as bare <a> links matching the prototype markup.
 * Modes: primary (Services dropdown + nav-cta), mobile (mobile-subnav + mobile-nav-cta), flat.
 */
class W270_Nav_Walker extends Walker_Nav_Menu {
	private $mode;
	public function __construct( $mode = 'flat' ) { $this->mode = $mode; }

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( 'primary' === $this->mode ) { $output .= '<div class="nav-dropdown-menu">'; }
	}
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		if ( 'primary' === $this->mode ) { $output .= '</div></div>'; }
	}
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes  = (array) $item->classes;
		$is_cta   = in_array( 'nav-cta', $classes, true );
		$has_kids = in_array( 'menu-item-has-children', $classes, true );
		$url      = esc_url( $item->url );
		$title    = esc_html( $item->title );
		$current  = ( $item->current || $item->current_item_ancestor ) ? ' active' : '';
		if ( 'primary' === $this->mode ) {
			if ( $is_cta ) { $output .= '<a href="' . $url . '" class="nav-cta' . $current . '">' . $title . ' →</a>'; return; }
			if ( $has_kids && 0 === $depth ) {
				$output .= '<div class="nav-dropdown"><a href="' . $url . '" class="nav-dropdown-parent' . $current . '" aria-haspopup="true">' . $title
					. ' <svg class="nav-dropdown-chevron" viewBox="0 0 10 6" width="9" height="6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 1l4 4 4-4"/></svg></a>';
				return;
			}
			$output .= '<a href="' . $url . '"' . ( $current ? ' class="active"' : '' ) . '>' . $title . '</a>';
			return;
		}
		if ( 'mobile' === $this->mode ) {
			if ( $is_cta ) { $output .= '<a href="' . $url . '" class="mobile-nav-cta">' . $title . ' →</a>'; return; }
			$output .= '<a href="' . $url . '"' . ( $depth > 0 ? ' class="mobile-subnav"' : '' ) . '>' . $title . '</a>';
			return;
		}
		$output .= '<a href="' . $url . '">' . $title . '</a>';
	}
	public function end_el( &$output, $item, $depth = 0, $args = null ) {}
}

function w270_menu( $location, $mode ) {
	return wp_nav_menu( [
		'theme_location' => $location,
		'container'      => false,
		'items_wrap'     => '%3$s',
		'fallback_cb'    => false,
		'echo'           => false,
		'walker'         => new W270_Nav_Walker( $mode ),
	] );
}
