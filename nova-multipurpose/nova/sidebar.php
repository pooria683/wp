<?php
/**
 * Sidebar.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

$id = ( Nova_Modes::has( 'shop' ) && ( is_shop() || is_product_taxonomy() ) ) ? 'shop-sidebar' : 'sidebar-main';

if ( ! is_active_sidebar( $id ) ) {
	return;
}
?>
<aside class="nv-sidebar" aria-label="<?php esc_attr_e( 'ستون کنار', 'nova' ); ?>">
	<?php dynamic_sidebar( $id ); ?>
</aside>
