<?php
/**
 * Cart panel.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;
$count = nova_cart_count();
?>
<a class="nv-hbtn nv-hbtn--cart" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'سبد خرید', 'nova' ); ?>">
	<?php echo Nova_Menus::icon_svg( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<span class="nv-count" data-cart-count><?php echo esc_html( $count ); ?></span>
</a>
