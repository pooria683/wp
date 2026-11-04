<?php
/**
 * Compare panel.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

$page = (int) Nova_Modes::get_option( 'compare_page' ) ?: get_option( 'nova_compare_page', 0 );
$url  = $page ? get_permalink( $page ) : home_url( '/' );
?>
<a class="nv-hbtn nv-hbtn--compare" href="<?php echo esc_url( $url ); ?>" aria-label="<?php esc_attr_e( 'مقایسه محصولات', 'nova' ); ?>">
	<?php echo Nova_Menus::icon_svg( 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<span class="nv-count" data-compare-count><?php echo esc_html( count( Nova_Ajax::compare_ids() ) ); ?></span>
</a>
