<?php
/**
 * Wishlist panel.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

$page = (int) Nova_Modes::get_option( 'wishlist_page' ) ?: get_option( 'nova_wishlist_page', 0 );
$url  = $page ? get_permalink( $page ) : home_url( '/' );
$n    = count( Nova_Ajax::wishlist_ids() );
?>
<a class="nv-hbtn nv-hbtn--wishlist" href="<?php echo esc_url( $url ); ?>" aria-label="<?php esc_attr_e( 'علاقه‌مندی‌ها', 'nova' ); ?>">
	<?php echo Nova_Menus::icon_svg( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<span class="nv-count" data-wishlist-count><?php echo esc_html( $n ); ?></span>
</a>
