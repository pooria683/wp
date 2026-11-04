<?php
/**
 * WooCommerce integration (only loaded when the shop mode is active).
 *
 * Covers:
 *  - equal-height product cards (default) or masonry, switchable
 *  - product card "widget manager" (badges, rating, price, cart, wishlist,
 *    compare, quick view, stock, brand) — each can be turned off
 *  - AJAX filters + pagination
 *  - quick view, wishlist, compare
 *  - single product customisation: gallery position, tabs style, attributes,
 *    reviews, related, 360° images, "notify me when in stock"
 *  - a small rich editor for the product attributes table
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Woo {

	public static function init() {
		// Layout / wrappers.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		add_action( 'woocommerce_before_main_content', array( __CLASS__, 'open_wrapper' ), 10 );
		add_action( 'woocommerce_after_main_content', array( __CLASS__, 'close_wrapper' ), 10 );
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

		// Shop page. Our toolbar replaces Woo's result count + ordering, and the
		// theme's breadcrumb replaces Woo's (avoids double breadcrumbs).
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );

		add_filter( 'loop_shop_columns', array( __CLASS__, 'columns' ) );
		add_filter( 'loop_shop_per_page', array( __CLASS__, 'per_page' ), 20 );
		add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'shop_toolbar' ), 15 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'woocommerce_product_loop_start', array( __CLASS__, 'loop_class' ) );

		// Product card: the theme renders image, badges, price and CTA itself.
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'card_media' ), 10 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		add_action( 'woocommerce_after_shop_loop_item', array( __CLASS__, 'card_actions' ), 10 );

		// Single product.
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
		add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'product_badges' ), 5 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'product_360_button' ), 25 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'notify_form' ), 35 );
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'product_tabs' ), 20 );
		add_filter( 'woocommerce_product_get_short_description', array( __CLASS__, 'short_description' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'image_sizes' ) );

		// Cart / fragments.
		add_filter( 'woocommerce_add_to_cart_fragments', array( __CLASS__, 'fragments' ) );

		// Compare & wishlist pages.
		add_shortcode( 'nova_wishlist', array( __CLASS__, 'wishlist_page' ) );
		add_shortcode( 'nova_compare', array( __CLASS__, 'compare_page' ) );

		// Admin: rich attribute editor + product extra content.
		add_action( 'add_meta_boxes', array( __CLASS__, 'product_metabox' ) );
		add_action( 'save_post_product', array( __CLASS__, 'save_product_meta' ) );

		// Order reminder for "notify me" subscribers (simple cron-free list,
		// shown in the product edit screen).
		add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'stock_changed' ), 10, 3 );
	}

	public static function image_sizes() {
		add_image_size( 'nova-product-card', 560, 560, true );
	}

	/* ---------------------------------------------------------- wrappers */

	public static function open_wrapper() {
		$sidebar = Nova_Modes::get_option( 'shop_sidebar', 'right' );
		$classes = 'nv-container nv-shop nv-shop--sidebar-' . sanitize_html_class( $sidebar );
		if ( Nova_Modes::has( 'ads' ) ) {
			Nova_Ads::zone( 'shop-top' );
		}
		echo '<div class="' . esc_attr( $classes ) . '"><div class="nv-shop__layout">';
		echo '<div class="nv-shop__content">';
		if ( is_shop() || is_product_taxonomy() ) {
			echo do_shortcode( '[nova_breadcrumb]' );
		}
	}

	public static function close_wrapper() {
		echo '</div>'; // .nv-shop__content
		$sidebar = Nova_Modes::get_option( 'shop_sidebar', 'right' );
		if ( 'none' !== $sidebar ) {
			echo '<aside class="nv-shop__aside">';
			self::filters();
			if ( is_active_sidebar( 'shop-sidebar' ) ) {
				dynamic_sidebar( 'shop-sidebar' );
			}
			echo '</aside>';
		}
		echo '</div></div>'; // .nv-shop__layout / .nv-container
	}

	public static function body_class( $classes ) {
		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
			$classes[] = 'nv-shop-layout-' . ( 'masonry' === Nova_Modes::get_option( 'shop_layout', 'equal' ) ? 'masonry' : 'equal' );
		}
		return $classes;
	}

	public static function columns() {
		return (int) ( Nova_Modes::get_option( 'shop_columns', 4 ) ?: 4 );
	}

	public static function per_page() {
		return 12;
	}

	/**
	 * Equal-height switch. The CSS grid already equalises rows; the class tells
	 * the stylesheet which strategy to use.
	 */
	public static function loop_class( $html ) {
		$mode = 'masonry' === Nova_Modes::get_option( 'shop_layout', 'equal' ) ? 'nv-products--masonry' : 'nv-products--equal';
		return str_replace( 'class="products', 'class="' . $mode . ' products', $html );
	}

	/* ------------------------------------------------------ shop toolbar */

	public static function shop_toolbar() {
		$active = self::active_filters_count();
		?>
		<div class="nv-shop__bar">
			<button class="nv-btn nv-btn--ghost nv-filters-toggle" type="button" data-nv-filters-toggle aria-expanded="false">
				<?php echo Nova_Menus::icon_svg( 'grid' ); // phpcs:ignore ?>
				<span><?php esc_html_e( 'فیلترها', 'nova' ); ?></span>
				<?php if ( $active ) : ?>
					<em class="nv-badge"><?php echo (int) $active; ?></em>
				<?php endif; ?>
			</button>

			<div class="nv-shop__count nv-muted">
				<?php
				global $wp_query;
				printf(
					esc_html__( '%s محصول', 'nova' ),
					esc_html( number_format_i18n( (int) $wp_query->found_posts ) )
				);
				?>
			</div>

			<div class="nv-shop__tools">
				<label class="nv-sort">
					<span class="screen-reader-text"><?php esc_html_e( 'ترتیب', 'nova' ); ?></span>
					<select data-nv-orderby>
						<?php
						$current = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'menu_order'; // phpcs:ignore WordPress.Security.NonceVerification
						$options = array(
							'menu_order' => __( 'پیش‌فرض', 'nova' ),
							'date'       => __( 'جدیدترین', 'nova' ),
							'price'      => __( 'ارزان‌ترین', 'nova' ),
							'popularity' => __( 'محبوب‌ترین', 'nova' ),
						);
						foreach ( $options as $key => $label ) {
							printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $key ), selected( $current, $key, false ), esc_html( $label ) );
						}
						?>
					</select>
				</label>

				<div class="nv-viewswitch" role="group" aria-label="<?php esc_attr_e( 'نمایش', 'nova' ); ?>">
					<button type="button" class="is-active" data-view="grid" aria-label="<?php esc_attr_e( 'شبکه', 'nova' ); ?>"><?php echo Nova_Menus::icon_svg( 'grid' ); // phpcs:ignore ?></button>
					<button type="button" data-view="list" aria-label="<?php esc_attr_e( 'لیست', 'nova' ); ?>"><?php echo Nova_Menus::icon_svg( 'tag' ); // phpcs:ignore ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	private static function active_filters_count() {
		$count = 0;
		foreach ( array( 'product_cat', 'product_tag', 'pa_brand', 'min_price', 'max_price', 'stock', 'rating' ) as $key ) {
			if ( ! empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$count++;
			}
		}
		return $count;
	}

	/** Filter sidebar (AJAX driven). */
	public static function filters() {
		if ( 'none' === Nova_Modes::get_option( 'shop_sidebar', 'right' ) ) {
			return;
		}
		$enabled = (array) Nova_Modes::get_option( 'shop_filters' );
		$current_cat = isset( $_GET['product_cat'] ) ? sanitize_title( $_GET['product_cat'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<aside class="nv-filters" data-nv-filters aria-label="<?php esc_attr_e( 'فیلترها', 'nova' ); ?>">
			<div class="nv-filters__head">
				<strong><?php esc_html_e( 'فیلترها', 'nova' ); ?></strong>
				<button type="button" class="nv-filters__close" data-nv-filters-close aria-label="<?php esc_attr_e( 'بستن', 'nova' ); ?>">&times;</button>
			</div>

			<?php if ( in_array( 'cat', $enabled, true ) ) : ?>
				<div class="nv-filter-group">
					<h4 class="nv-filter-group__title"><?php esc_html_e( 'دسته‌بندی', 'nova' ); ?></h4>
					<?php
					$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0 ) );
					if ( $cats && ! is_wp_error( $cats ) ) :
						?>
						<ul class="nv-filter-list">
							<?php foreach ( $cats as $cat ) : ?>
								<li>
									<label>
										<input type="checkbox" name="product_cat[]" value="<?php echo esc_attr( $cat->slug ); ?>" <?php checked( in_array( $cat->slug, (array) $current_cat, true ) ); ?>>
										<span><?php echo esc_html( $cat->name ); ?></span>
										<em><?php echo (int) $cat->count; ?></em>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( in_array( 'price', $enabled, true ) ) : ?>
				<div class="nv-filter-group">
					<h4 class="nv-filter-group__title"><?php esc_html_e( 'بازه قیمت', 'nova' ); ?></h4>
					<?php
					$max = (int) ceil( (float) ( self::price_range()['max'] ?? 1000000 ) );
					?>
					<div class="nv-range" data-max="<?php echo esc_attr( $max ); ?>">
						<input type="range" name="max_price" min="0" max="<?php echo esc_attr( $max ); ?>" step="1000" value="<?php echo esc_attr( $max ); ?>">
						<output><?php echo esc_html( number_format_i18n( $max ) ); ?></output>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( in_array( 'attr', $enabled, true ) ) : ?>
				<?php
				$attrs = wc_get_attribute_taxonomies();
				foreach ( (array) $attrs as $attr ) :
					$taxonomy = wc_attribute_taxonomy_name( $attr->attribute_name );
					$terms    = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );
					if ( ! $terms || is_wp_error( $terms ) ) {
						continue;
					}
					?>
					<div class="nv-filter-group">
						<h4 class="nv-filter-group__title"><?php echo esc_html( $attr->attribute_label ); ?></h4>
						<ul class="nv-filter-list">
							<?php foreach ( $terms as $term ) : ?>
								<li>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( $taxonomy ); ?>[]" value="<?php echo esc_attr( $term->slug ); ?>">
										<span><?php echo esc_html( $term->name ); ?></span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>

			<?php if ( in_array( 'stock', $enabled, true ) ) : ?>
				<div class="nv-filter-group">
					<h4 class="nv-filter-group__title"><?php esc_html_e( 'موجودی', 'nova' ); ?></h4>
					<label class="nv-check"><input type="checkbox" name="stock" value="1"><span><?php esc_html_e( 'فقط کالاهای موجود', 'nova' ); ?></span></label>
				</div>
			<?php endif; ?>

			<?php if ( in_array( 'brand', $enabled, true ) ) : ?>
				<div class="nv-filter-group">
					<h4 class="nv-filter-group__title"><?php esc_html_e( 'برند', 'nova' ); ?></h4>
					<?php
					$brands = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => true ) );
					if ( $brands && ! is_wp_error( $brands ) ) :
						?>
						<ul class="nv-filter-list">
							<?php foreach ( $brands as $brand ) : ?>
								<li><label><input type="checkbox" name="product_brand[]" value="<?php echo esc_attr( $brand->slug ); ?>"><span><?php echo esc_html( $brand->name ); ?></span></label></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="nv-filters__actions">
				<button class="nv-btn nv-btn--primary" type="button" data-nv-apply-filters><span><?php esc_html_e( 'اعمال فیلتر', 'nova' ); ?></span></button>
				<a class="nv-link" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'حذف فیلترها', 'nova' ); ?></a>
			</div>
		</aside>
		<?php
	}

	public static function price_range() {
		global $wpdb;
		$row = $wpdb->get_row(
			"SELECT MIN(CAST(pm.meta_value AS DECIMAL(18,2))) AS min_price, MAX(CAST(pm.meta_value AS DECIMAL(18,2))) AS max_price
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_price' AND p.post_status = 'publish' AND p.post_type = 'product'"
		);
		return array(
			'min' => (float) ( $row->min_price ?? 0 ),
			'max' => (float) ( $row->max_price ?? 0 ),
		);
	}

	/* -------------------------------------------------------- product card */

	/** Media block: image, second-image hover, badge, quick actions. */
	public static function card_media() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$widgets = (array) Nova_Modes::get_option( 'shop_card_widgets' );
		$second  = $product->get_gallery_image_ids();
		$wishlist = in_array( $product->get_id(), Nova_Ajax::wishlist_ids(), true );
		$compare  = in_array( $product->get_id(), Nova_Ajax::compare_ids(), true );
		?>
		<div class="nv-product__media">
			<a href="<?php the_permalink(); ?>" class="nv-product__img" aria-hidden="true" tabindex="-1">
				<?php echo $product->get_image( 'nova-product-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore ?>
				<?php if ( $second ) : ?>
					<img class="nv-product__img-hover" src="<?php echo esc_url( wp_get_attachment_image_url( $second[0], 'nova-product-card' ) ); ?>" alt="" loading="lazy" decoding="async" width="560" height="560">
				<?php endif; ?>
			</a>

			<?php if ( in_array( 'badge', $widgets, true ) ) : ?>
				<div class="nv-product__badges">
					<?php if ( $product->is_on_sale() ) : ?>
						<?php
						$regular = (float) $product->get_regular_price();
						$sale    = (float) $product->get_sale_price();
						$pct     = ( $regular > 0 && $sale > 0 ) ? round( ( ( $regular - $sale ) / $regular ) * 100 ) : 0;
						?>
						<span class="nv-badge nv-badge--sale"><?php echo $pct ? esc_html( '-' . $pct . '%' ) : esc_html__( 'فروش ویژه', 'nova' ); ?></span>
					<?php endif; ?>
					<?php if ( $product->is_featured() ) : ?>
						<span class="nv-badge nv-badge--new"><?php esc_html_e( 'ویژه', 'nova' ); ?></span>
					<?php endif; ?>
					<?php if ( ! $product->is_in_stock() ) : ?>
						<span class="nv-badge nv-badge--out"><?php esc_html_e( 'ناموجود', 'nova' ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="nv-product__quick">
				<?php if ( in_array( 'wishlist', $widgets, true ) && Nova_Modes::feature( 'wishlist', true ) ) : ?>
					<button class="nv-icon-btn <?php echo $wishlist ? 'is-on' : ''; ?>" type="button" data-nv-wishlist="<?php echo esc_attr( $product->get_id() ); ?>" aria-label="<?php esc_attr_e( 'افزودن به علاقه‌مندی', 'nova' ); ?>"><?php echo Nova_Menus::icon_svg( 'heart' ); // phpcs:ignore ?></button>
				<?php endif; ?>
				<?php if ( in_array( 'compare', $widgets, true ) && Nova_Modes::feature( 'compare', true ) ) : ?>
					<button class="nv-icon-btn <?php echo $compare ? 'is-on' : ''; ?>" type="button" data-nv-compare="<?php echo esc_attr( $product->get_id() ); ?>" aria-label="<?php esc_attr_e( 'مقایسه', 'nova' ); ?>"><?php echo Nova_Menus::icon_svg( 'grid' ); // phpcs:ignore ?></button>
				<?php endif; ?>
				<?php if ( in_array( 'quickview', $widgets, true ) && Nova_Modes::feature( 'quickview', true ) ) : ?>
					<button class="nv-icon-btn" type="button" data-nv-quickview="<?php echo esc_attr( $product->get_id() ); ?>" aria-label="<?php esc_attr_e( 'نمایش سریع', 'nova' ); ?>"><?php echo Nova_Menus::icon_svg( 'search' ); // phpcs:ignore ?></button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/** Price / rating / cart button under the card image. */
	public static function card_actions() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$widgets = (array) Nova_Modes::get_option( 'shop_card_widgets' );
		echo '<div class="nv-product__foot">';
		if ( in_array( 'price', $widgets, true ) ) {
			echo '<div class="nv-product__price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		}
		if ( in_array( 'cart', $widgets, true ) ) {
			echo sprintf(
				'<a href="%s" data-quantity="1" class="nv-product__cart %s" %s>%s<span>%s</span></a>',
				esc_url( $product->add_to_cart_url() ),
				esc_attr( implode( ' ', array_filter( array( 'product_type_' . $product->get_type(), $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '', $product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : '' ) ) ) ),
				'data-product_id="' . esc_attr( $product->get_id() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" aria-label="' . esc_attr( $product->add_to_cart_description() ) . '" rel="nofollow"',
				Nova_Menus::icon_svg( 'cart' ), // phpcs:ignore
				esc_html( $product->add_to_cart_text() )
			);
		}
		echo '</div>';
	}

	/* ------------------------------------------------------- single product */

	public static function product_badges() {
		global $product;
		if ( $product instanceof WC_Product && ! $product->is_in_stock() ) {
			echo '<p class="nv-stock-out">' . esc_html__( 'این محصول فعلاً ناموجود است', 'nova' ) . '</p>';
		}
	}

	/** 360° / 3D viewer trigger (images come from the product gallery meta). */
	public static function product_360_button() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$show = (array) Nova_Modes::get_option( 'product_show' );
		if ( ! in_array( '360', $show, true ) ) {
			return;
		}
		$frames = get_post_meta( $product->get_id(), '_nova_360', true );
		if ( ! $frames ) {
			return;
		}
		$ids = array_filter( array_map( 'absint', explode( ',', $frames ) ) );
		if ( count( $ids ) < 2 ) {
			return;
		}
		$srcs = array_map( static function ( $id ) {
			return wp_get_attachment_image_url( $id, 'large' );
		}, $ids );
		printf(
			'<button class="nv-btn nv-btn--ghost nv-360-open" type="button" data-nv-360="%s"><span>%s</span></button>',
			esc_attr( wp_json_encode( array_values( $srcs ) ) ),
			esc_html__( 'نمای ۳۶۰ درجه', 'nova' )
		);
	}

	/** "Notify me when back in stock" form for out-of-stock products. */
	public static function notify_form() {
		global $product;
		if ( ! $product instanceof WC_Product || $product->is_in_stock() ) {
			return;
		}
		$show = (array) Nova_Modes::get_option( 'product_show' );
		if ( ! in_array( 'notify', $show, true ) || ! Nova_Modes::feature( 'notify_stock', true ) ) {
			return;
		}
		?>
		<form class="nv-notify" data-nv-notify="<?php echo esc_attr( $product->get_id() ); ?>">
			<label for="nv-notify-phone"><?php esc_html_e( 'در صورت موجود شدن، مرا مطلع کن', 'nova' ); ?></label>
			<div class="nv-notify__row">
				<input id="nv-notify-phone" type="tel" name="phone" inputmode="tel" autocomplete="tel" placeholder="<?php esc_attr_e( 'شماره تلفن شما', 'nova' ); ?>" required>
				<button class="nv-btn nv-btn--primary" type="submit"><span><?php esc_html_e( 'اطلاع بده', 'nova' ); ?></span></button>
			</div>
			<p class="nv-notify__msg" role="status"></p>
		</form>
		<?php
	}

	/** Tabs: description / attributes table / reviews / video / custom. */
	public static function product_tabs( $tabs ) {
		$show  = (array) Nova_Modes::get_option( 'product_show' );
		$style = Nova_Modes::get_option( 'product_tabs', 'tabs' );

		if ( ! in_array( 'reviews', $show, true ) && isset( $tabs['reviews'] ) ) {
			unset( $tabs['reviews'] );
		}

		global $product;
		if ( $product instanceof WC_Product ) {
			// Attributes table as its own tab (richer than Woo's list).
			if ( in_array( 'attrs', $show, true ) ) {
				$tabs['nova_attrs'] = array(
					'title'    => __( 'مشخصات فنی', 'nova' ),
					'priority' => 15,
					'callback' => array( __CLASS__, 'attributes_table' ),
				);
			}
			if ( in_array( 'video', $show, true ) ) {
				$video = get_post_meta( $product->get_id(), '_nova_video', true );
				if ( $video ) {
					$tabs['nova_video'] = array(
						'title'    => __( 'ویدئوی محصول', 'nova' ),
						'priority' => 16,
						'callback' => static function () use ( $video ) {
							echo do_shortcode( '[nova_player src="' . esc_url( $video ) . '"]' );
						},
					);
				}
			}
			if ( in_array( 'customtab', $show, true ) ) {
				$custom = get_post_meta( $product->get_id(), '_nova_extra_tab', true );
				if ( $custom ) {
					$tabs['nova_custom'] = array(
						'title'    => get_post_meta( $product->get_id(), '_nova_extra_tab_title', true ) ?: __( 'اطلاعات بیشتر', 'nova' ),
						'priority' => 18,
						'callback' => static function () use ( $custom ) {
							echo '<div class="nv-prose">' . wp_kses_post( wpautop( $custom ) ) . '</div>';
						},
					);
				}
			}
		}

		if ( 'accordion' === $style ) {
			// Handled in CSS by rendering as accordion via a body class.
			add_filter( 'body_class', static function ( $c ) {
				$c[] = 'nv-tabs-accordion';
				return $c;
			} );
		} elseif ( 'sections' === $style ) {
			add_filter( 'body_class', static function ( $c ) {
				$c[] = 'nv-tabs-sections';
				return $c;
			} );
		}

		return $tabs;
	}

	/** Attributes table, supports the rich HTML editor stored per product. */
	public static function attributes_table() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$rich = get_post_meta( $product->get_id(), '_nova_attrs_html', true );
		if ( $rich ) {
			echo '<div class="nv-prose nv-attrs-rich">' . wp_kses_post( $rich ) . '</div>';
		}

		$rows = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_visible() ) {
				continue;
			}
			if ( $attribute->is_taxonomy() ) {
				$values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
				$label  = wc_attribute_label( $attribute->get_name() );
			} else {
				$values = $attribute->get_options();
				$label  = $attribute->get_name();
			}
			if ( $values ) {
				$rows[ $label ] = implode( '، ', $values );
			}
		}

		$extra = get_post_meta( $product->get_id(), '_nova_attrs', true );
		if ( $extra && is_array( $extra ) ) {
			foreach ( $extra as $pair ) {
				if ( ! empty( $pair['k'] ) ) {
					$rows[ $pair['k'] ] = $pair['v'] ?? '';
				}
			}
		}

		if ( ! $rows ) {
			echo '<p class="nv-muted">' . esc_html__( 'مشخصاتی برای این محصول ثبت نشده است.', 'nova' ) . '</p>';
			return;
		}
		echo '<table class="nv-attrs"><tbody>';
		foreach ( $rows as $k => $v ) {
			printf( '<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html( $k ), esc_html( $v ) );
		}
		echo '</tbody></table>';
	}

	/** Replace the plain short description with the rich one when present. */
	public static function short_description( $excerpt ) {
		$id = get_the_ID();
		if ( ! $id ) {
			return $excerpt;
		}
		$rich = get_post_meta( $id, '_nova_short_rich', true );
		return $rich ? $rich : $excerpt;
	}

	/* ----------------------------------------------------- cart fragments */

	public static function fragments( $fragments ) {
		$fragments['[data-cart-count]'] = '<span class="nv-count" data-cart-count>' . esc_html( nova_cart_count() ) . '</span>';
		return $fragments;
	}

	/* ------------------------------------------------- wishlist / compare */

	public static function wishlist_page() {
		$ids = Nova_Ajax::wishlist_ids();
		if ( ! $ids ) {
			return '<p class="nv-muted">' . esc_html__( 'لیست علاقه‌مندی‌ها خالی است.', 'nova' ) . '</p>';
		}
		return self::product_grid_from_ids( $ids );
	}

	public static function compare_page() {
		$ids = Nova_Ajax::compare_ids();
		if ( ! $ids ) {
			return '<p class="nv-muted">' . esc_html__( 'محصولی برای مقایسه انتخاب نشده است.', 'nova' ) . '</p>';
		}
		$products = array_filter( array_map( 'wc_get_product', $ids ) );
		if ( ! $products ) {
			return '';
		}

		// Collect every attribute used by these products.
		$keys = array();
		foreach ( $products as $product ) {
			foreach ( $product->get_attributes() as $attribute ) {
				$keys[ wc_attribute_label( $attribute->get_name() ) ] = true;
			}
		}

		ob_start();
		echo '<div class="nv-compare"><table class="nv-compare__table"><thead><tr><th></th>';
		foreach ( $products as $product ) {
			printf(
				'<th><a href="%s">%s</a><span class="nv-compare__price">%s</span></th>',
				esc_url( $product->get_permalink() ),
				$product->get_image( 'thumbnail' ),
				wp_kses_post( $product->get_price_html() )
			);
		}
		echo '</tr></thead><tbody>';

		foreach ( array_keys( $keys ) as $label ) {
			echo '<tr><th scope="row">' . esc_html( $label ) . '</th>';
			foreach ( $products as $product ) {
				$value = $product->get_attribute( $label );
				echo '<td>' . ( $value ? esc_html( $value ) : '—' ) . '</td>';
			}
			echo '</tr>';
		}

		echo '<tr><th scope="row">' . esc_html__( 'افزودن به سبد', 'nova' ) . '</th>';
		foreach ( $products as $product ) {
			echo '<td><a class="nv-btn nv-btn--sm nv-btn--primary" href="' . esc_url( $product->add_to_cart_url() ) . '"><span>' . esc_html__( 'خرید', 'nova' ) . '</span></a></td>';
		}
		echo '</tr></tbody></table></div>';
		return ob_get_clean();
	}

	private static function product_grid_from_ids( $ids ) {
		$q = new WP_Query(
			array(
				'post_type'      => 'product',
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => count( $ids ),
			)
		);
		ob_start();
		if ( $q->have_posts() ) {
			echo '<div class="nv-products--equal"><ul class="products columns-4">';
			while ( $q->have_posts() ) {
				$q->the_post();
				wc_get_template_part( 'content', 'product' );
			}
			echo '</ul></div>';
		}
		wp_reset_postdata();
		return ob_get_clean();
	}

	/* ------------------------------------------------------------ admin */

	public static function product_metabox() {
		add_meta_box(
			'nova_product_editor',
			__( 'Nova — ویرایشگر حرفه‌ای محصول', 'nova' ),
			array( __CLASS__, 'product_metabox_html' ),
			'product',
			'normal',
			'high'
		);
	}

	public static function product_metabox_html( $post ) {
		wp_nonce_field( 'nova_product', 'nova_product_nonce' );
		$short = get_post_meta( $post->ID, '_nova_short_rich', true );
		$attrs = get_post_meta( $post->ID, '_nova_attrs_html', true );
		$rows  = get_post_meta( $post->ID, '_nova_attrs', true );
		$rows  = is_array( $rows ) ? $rows : array();
		$video = get_post_meta( $post->ID, '_nova_video', true );
		$g360  = get_post_meta( $post->ID, '_nova_360', true );
		$tab   = get_post_meta( $post->ID, '_nova_extra_tab', true );
		$tabt  = get_post_meta( $post->ID, '_nova_extra_tab_title', true );
		$subs  = get_option( 'nova_notify_' . $post->ID, array() );
		?>
		<div class="nv-admin nv-admin--metabox">
			<p><strong><?php esc_html_e( 'توضیح کوتاه غنی (با جدول و قالب‌بندی)', 'nova' ); ?></strong></p>
			<?php
			wp_editor(
				$short,
				'nova_short_rich',
				array(
					'textarea_name' => 'nova_short_rich',
					'textarea_rows' => 6,
					'media_buttons' => true,
					'teeny'         => false,
				)
			);
			?>

			<p><strong><?php esc_html_e( 'جدول مشخصات فنی (HTML)', 'nova' ); ?></strong></p>
			<textarea name="nova_attrs_html" rows="6" class="large-text code" dir="ltr"><?php echo esc_textarea( $attrs ); ?></textarea>

			<p><strong><?php esc_html_e( 'ویژگی‌های اضافی (کلید/مقدار)', 'nova' ); ?></strong></p>
			<div id="nova-attrs-rows">
				<?php foreach ( $rows as $i => $pair ) : ?>
					<p class="nv-attr-pair">
						<input type="text" name="nova_attrs[<?php echo (int) $i; ?>][k]" value="<?php echo esc_attr( $pair['k'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'مثلاً: جنس', 'nova' ); ?>">
						<input type="text" name="nova_attrs[<?php echo (int) $i; ?>][v]" value="<?php echo esc_attr( $pair['v'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'مثلاً: چرم طبیعی', 'nova' ); ?>">
					</p>
				<?php endforeach; ?>
				<p class="nv-attr-pair">
					<input type="text" name="nova_attrs[999][k]" value="" placeholder="<?php esc_attr_e( 'کلید جدید', 'nova' ); ?>">
					<input type="text" name="nova_attrs[999][v]" value="" placeholder="<?php esc_attr_e( 'مقدار', 'nova' ); ?>">
				</p>
			</div>

			<hr>
			<p><label><strong><?php esc_html_e( 'ویدئوی محصول (MP4 / یوتیوب / آپارات)', 'nova' ); ?></strong><br>
				<input type="url" class="large-text" dir="ltr" name="nova_video" value="<?php echo esc_attr( $video ); ?>"></label></p>

			<p><label><strong><?php esc_html_e( 'تصاویر ۳۶۰ درجه (شناسه تصاویر با کاما)', 'nova' ); ?></strong><br>
				<input type="text" class="large-text" dir="ltr" name="nova_360" value="<?php echo esc_attr( $g360 ); ?>" placeholder="101,102,103"></label></p>

			<p><label><strong><?php esc_html_e( 'عنوان تب اختصاصی', 'nova' ); ?></strong><br>
				<input type="text" class="large-text" name="nova_extra_tab_title" value="<?php echo esc_attr( $tabt ); ?>"></label></p>
			<p><label><strong><?php esc_html_e( 'محتوای تب اختصاصی (HTML)', 'nova' ); ?></strong><br>
				<textarea name="nova_extra_tab" rows="5" class="large-text code" dir="ltr"><?php echo esc_textarea( $tab ); ?></textarea></label></p>

			<hr>
			<p><strong><?php esc_html_e( 'درخواست‌های «مرا مطلع کن»', 'nova' ); ?></strong>
				<?php if ( $subs ) : ?>
					<span class="nv-badge"><?php echo count( $subs ); ?></span>
					<button type="button" class="button" onclick="var t=document.getElementById('nova-subs');t.hidden=!t.hidden"><?php esc_html_e( 'نمایش', 'nova' ); ?></button>
					<textarea id="nova-subs" hidden rows="4" class="large-text" dir="ltr" readonly><?php
					foreach ( (array) $subs as $sub ) {
						echo esc_html( ( $sub['phone'] ?? '' ) . ' — ' . gmdate( 'Y-m-d H:i', (int) ( $sub['time'] ?? 0 ) ) . "\n" );
					}
					?></textarea>
				<?php else : ?>
					<em><?php esc_html_e( 'ثبتی وجود ندارد.', 'nova' ); ?></em>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	public static function save_product_meta( $post_id ) {
		if ( ! isset( $_POST['nova_product_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nova_product_nonce'] ), 'nova_product' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		$rich = wp_kses_post( wp_unslash( $_POST['nova_short_rich'] ?? '' ) );
		update_post_meta( $post_id, '_nova_short_rich', $rich );

		$attrs_html = wp_kses_post( wp_unslash( $_POST['nova_attrs_html'] ?? '' ) );
		update_post_meta( $post_id, '_nova_attrs_html', $attrs_html );

		$pairs = array();
		foreach ( (array) ( $_POST['nova_attrs'] ?? array() ) as $pair ) {
			if ( empty( $pair['k'] ) ) {
				continue;
			}
			$pairs[] = array(
				'k' => sanitize_text_field( wp_unslash( $pair['k'] ) ),
				'v' => sanitize_text_field( wp_unslash( $pair['v'] ?? '' ) ),
			);
		}
		update_post_meta( $post_id, '_nova_attrs', array_slice( $pairs, 0, 60 ) );

		update_post_meta( $post_id, '_nova_video', esc_url_raw( wp_unslash( $_POST['nova_video'] ?? '' ) ) );
		update_post_meta( $post_id, '_nova_360', sanitize_text_field( wp_unslash( $_POST['nova_360'] ?? '' ) ) );
		update_post_meta( $post_id, '_nova_extra_tab_title', sanitize_text_field( wp_unslash( $_POST['nova_extra_tab_title'] ?? '' ) ) );
		update_post_meta( $post_id, '_nova_extra_tab', wp_kses_post( wp_unslash( $_POST['nova_extra_tab'] ?? '' ) ) );
	}

	/** When stock returns, email the shop admin with the pending list. */
	public static function stock_changed( $product_id, $status, $product ) {
		if ( 'instock' !== $status ) {
			return;
		}
		$subs = get_option( 'nova_notify_' . $product_id, array() );
		if ( ! $subs ) {
			return;
		}
		$phones = array_map( static function ( $s ) {
			return $s['phone'] ?? '';
		}, (array) $subs );
		wp_mail(
			get_option( 'admin_email' ),
			sprintf( /* translators: %s: product name */ __( 'محصول «%s» موجود شد — لیست اطلاع‌رسانی', 'nova' ), $product->get_name() ),
			implode( "\n", array_filter( $phones ) )
		);
	}
}
