<?php
/**
 * Search panel (button that opens the popup).
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;
?>
<button class="nv-hbtn nv-hbtn--search" type="button" data-nv-open="search" aria-label="<?php esc_attr_e( 'جستجو', 'nova' ); ?>">
	<?php echo Nova_Menus::icon_svg( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</button>
