<?php
/**
 * Primary menu panel.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;
?>
<nav class="nv-nav" aria-label="<?php esc_attr_e( 'منوی اصلی', 'nova' ); ?>">
	<?php
	wp_nav_menu(
		array(
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'nv-menu',
			'fallback_cb'    => 'nova_menu_fallback',
			'depth'          => 3,
		)
	);
	?>
</nav>
