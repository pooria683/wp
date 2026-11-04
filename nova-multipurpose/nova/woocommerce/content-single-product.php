<?php
/**
 * Product content — overrides Woo's template to control the grid order.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

global $product;

$pos   = Nova_Modes::get_option( 'product_gallery_pos', 'left' );
$show  = (array) Nova_Modes::get_option( 'product_show' );
$sticky = 'sticky' === $pos ? ' nv-product--sticky' : '';
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'nv-single-product nv-single-product--' . sanitize_html_class( $pos ) . $sticky, $product ); ?>>

	<?php
	/**
	 * Before single product summary (sale flash, notices).
	 */
	do_action( 'woocommerce_before_single_product' );
	?>

	<div class="nv-container">
		<?php echo do_shortcode( '[nova_breadcrumb]' ); ?>

		<?php if ( 'top' === $pos ) : ?>
			<div class="nv-sp__gallery-top">
				<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
			</div>
			<div class="nv-sp__summary nv-sp__summary--top">
				<?php do_action( 'woocommerce_single_product_summary' ); ?>
			</div>
		<?php else : ?>
			<div class="nv-sp__grid">
				<div class="nv-sp__media">
					<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
				</div>
				<div class="nv-sp__summary">
					<?php do_action( 'woocommerce_single_product_summary' ); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
	</div>

	<?php if ( in_array( 'stickybar', $show, true ) ) : ?>
		<div class="nv-sp__stickybar">
			<span class="nv-sp__sticky-title"><?php the_title(); ?></span>
			<span class="nv-sp__sticky-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
			<a class="nv-btn nv-btn--primary" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"><span><?php esc_html_e( 'افزودن به سبد', 'nova' ); ?></span></a>
		</div>
	<?php endif; ?>
</div>

<div class="nv-360" data-nv-360-modal hidden>
	<button class="nv-360__close" type="button" aria-label="<?php esc_attr_e( 'بستن', 'nova' ); ?>">&times;</button>
	<div class="nv-360__stage" tabindex="0"></div>
	<p class="nv-360__hint"><?php esc_html_e( 'برای چرخش، بکشید یا با کلیدهای جهت‌دار جابه‌جا شوید.', 'nova' ); ?></p>
</div>
