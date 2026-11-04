<?php
/**
 * Top bar menu panel.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

if ( has_nav_menu( 'top' ) ) {
	wp_nav_menu(
		array(
			'theme_location' => 'top',
			'container'      => false,
			'menu_class'     => 'nv-topmenu',
			'depth'          => 1,
			'fallback_cb'    => false,
		)
	);
}
