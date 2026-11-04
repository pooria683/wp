<?php
/**
 * Account panel.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
?>
<a class="nv-hbtn nv-hbtn--account" href="<?php echo esc_url( $url ); ?>" aria-label="<?php esc_attr_e( 'حساب کاربری', 'nova' ); ?>">
	<?php echo Nova_Menus::icon_svg( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</a>
