<?php
/**
 * Product card (used by every Woo loop).
 *
 * Renders an equal-height card; all optional parts are controlled from the
 * panel's "product card widgets" manager.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
$widgets = (array) Nova_Modes::get_option( 'shop_card_widgets' );
?>
<li <?php wc_product_class( 'nv-product', $product ); ?>>
	<?php
	do_action( 'woocommerce_before_shop_loop_item' );
	do_action( 'woocommerce_before_shop_loop_item_title' ); // media + badges + quick actions.
	?>
	<div class="nv-product__body">
		<?php if ( in_array( 'brand', $widgets, true ) ) : ?>
			<?php
			$brands = get_the_terms( $product->get_id(), 'product_brand' );
			if ( $brands && ! is_wp_error( $brands ) ) :
				?>
				<span class="nv-product__brand"><?php echo esc_html( $brands[0]->name ); ?></span>
			<?php endif; ?>
		<?php endif; ?>

		<h2 class="nv-product__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

		<?php if ( in_array( 'rating', $widgets, true ) && wc_review_ratings_enabled() ) : ?>
			<div class="nv-product__rating">
				<?php if ( $product->get_average_rating() > 0 ) : ?>
					<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
						<span class="nv-star <?php echo $i <= round( (float) $product->get_average_rating() ) ? 'is-on' : ''; ?>" aria-hidden="true">★</span>
					<?php endfor; ?>
					<em>(<?php echo (int) $product->get_review_count(); ?>)</em>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( in_array( 'stock', $widgets, true ) ) : ?>
			<span class="nv-product__stock <?php echo $product->is_in_stock() ? 'is-in' : 'is-out'; ?>">
				<?php echo $product->is_in_stock() ? esc_html__( 'موجود', 'nova' ) : esc_html__( 'ناموجود', 'nova' ); ?>
			</span>
		<?php endif; ?>
	</div>

	<?php
	do_action( 'woocommerce_after_shop_loop_item_title' );
	do_action( 'woocommerce_after_shop_loop_item' ); // price + cart.
	?>
</li>
