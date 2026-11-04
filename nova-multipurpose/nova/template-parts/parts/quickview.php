<?php
/**
 * Quick view content (loaded by Ajax).
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

// The product is resolved by the Ajax controller before the template runs.
$product_id = (int) ( $GLOBALS['nova_quickview_id'] ?? 0 );
$product    = $product_id ? wc_get_product( $product_id ) : false;
if ( ! $product ) {
	return;
}
?>
<div class="nv-quickview">
	<div class="nv-quickview__media">
		<?php echo $product->get_image( 'woocommerce_single' ); // phpcs:ignore ?>
	</div>
	<div class="nv-quickview__info">
		<h2 class="nv-quickview__title"><?php echo esc_html( $product->get_name() ); ?></h2>
		<div class="nv-quickview__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
		<div class="nv-quickview__excerpt nv-prose"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>

		<?php if ( $product->is_in_stock() ) : ?>
			<form class="nv-quickview__form" method="post" action="<?php echo esc_url( $product->add_to_cart_url() ); ?>">
				<?php if ( $product->is_type( 'simple' ) ) : ?>
					<div class="nv-qty">
						<button type="button" data-qty="-1" aria-label="<?php esc_attr_e( 'کاهش', 'nova' ); ?>">−</button>
						<input type="number" name="quantity" value="1" min="1" step="1" inputmode="numeric">
						<button type="button" data-qty="1" aria-label="<?php esc_attr_e( 'افزایش', 'nova' ); ?>">+</button>
					</div>
					<button class="nv-btn nv-btn--primary" type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>">
						<span><?php esc_html_e( 'افزودن به سبد خرید', 'nova' ); ?></span>
					</button>
				<?php else : ?>
					<a class="nv-btn nv-btn--primary" href="<?php echo esc_url( $product->get_permalink() ); ?>"><span><?php esc_html_e( 'انتخاب گزینه‌ها', 'nova' ); ?></span></a>
				<?php endif; ?>
			</form>
		<?php else : ?>
			<p class="nv-out"><?php esc_html_e( 'ناموجود', 'nova' ); ?></p>
		<?php endif; ?>

		<a class="nv-quickview__full" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php esc_html_e( 'مشاهده صفحه کامل محصول', 'nova' ); ?></a>
	</div>
</div>
