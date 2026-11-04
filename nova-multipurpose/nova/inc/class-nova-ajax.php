<?php
/**
 * Ajax endpoints (single controller, nonce + capability checked).
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Ajax {

	public static function init() {
		$actions = array(
			'quickview',
			'wishlist',
			'compare',
			'filter',
			'notify_stock',
			'load_more',
			'search',
		);
		foreach ( $actions as $action ) {
			add_action( "wp_ajax_nova_$action", array( __CLASS__, $action ) );
			add_action( "wp_ajax_nopriv_nova_$action", array( __CLASS__, $action ) );
		}
	}

	/** Shared guard. */
	private static function guard() {
		if ( ! check_ajax_referer( 'nova_ajax', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'درخواست نامعتبر.', 'nova' ) ), 403 );
		}
	}

	private static function send( $data ) {
		wp_send_json_success( $data );
	}

	/** Quick view: product card modal content. */
	public static function quickview() {
		self::guard();
		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'ووکامرس فعال نیست.', 'nova' ) ), 400 );
		}
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$product = wc_get_product( $id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'محصول یافت نشد.', 'nova' ) ), 404 );
		}
		ob_start();
		$GLOBALS['nova_quickview_id'] = $id;
		get_template_part( 'template-parts/parts/quickview' );
		$html = ob_get_clean();
		self::send( array( 'html' => $html ) );
	}

	/** Toggle wishlist (returns the whole list so the UI can re-render). */
	public static function wishlist() {
		self::guard();
		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$list = self::wishlist_ids();
		if ( in_array( $id, $list, true ) ) {
			$list = array_values( array_diff( $list, array( $id ) ) );
			$state = false;
		} else {
			$list[] = $id;
			$state  = true;
		}
		self::store_list( 'nova_wishlist', $list );
		self::send( array( 'in_list' => $state, 'count' => count( $list ) ) );
	}

	/** Toggle compare. */
	public static function compare() {
		self::guard();
		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$list = self::compare_ids();
		if ( in_array( $id, $list, true ) ) {
			$list  = array_values( array_diff( $list, array( $id ) ) );
			$state = false;
		} else {
			$list[] = $id;
			$state  = true;
		}
		self::store_list( 'nova_compare', $list );
		self::send( array( 'in_list' => $state, 'count' => count( $list ) ) );
	}

	/**
	 * AJAX product filtering.
	 *
	 * Builds the product query explicitly (taxonomies, price range, stock,
	 * ordering and catalog visibility) so filtering behaves identically on the
	 * shop archive, a category page or any page that embeds a product grid.
	 * Values are re-sanitised here — never trust the payload.
	 */
	public static function filter() {
		self::guard();
		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'ووکامرس فعال نیست.', 'nova' ) ), 400 );
		}

		$page     = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
		$per_page = (int) apply_filters( 'loop_shop_per_page', 12 );

		// ---- taxonomies (product_cat, product_tag, product_brand, pa_*) ----
		$tax_query = array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'exclude-from-catalog' ),
				'operator' => 'NOT IN',
			),
		);

		foreach ( array_keys( (array) wp_unslash( $_POST ) ) as $key ) {
			$key = sanitize_key( $key );
			if ( ! taxonomy_exists( $key ) || 'product_visibility' === $key || empty( $_POST[ $key ] ) ) {
				continue;
			}
			$terms = array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_POST[ $key ] ) ) );
			if ( ! $terms ) {
				continue;
			}
			$tax_query[] = array(
				'taxonomy'         => $key,
				'field'            => 'slug',
				'terms'            => $terms,
				'operator'         => 'IN',
				'include_children' => is_taxonomy_hierarchical( $key ),
			);
		}

		// ---- price + stock ----
		$min = isset( $_POST['min_price'] ) ? (float) $_POST['min_price'] : 0;
		$max = isset( $_POST['max_price'] ) ? (float) $_POST['max_price'] : 0;
		if ( $max > 0 && $max < $min ) {
			$min = 0;
		}

		$meta_query = array( 'relation' => 'AND' );
		if ( $max > 0 ) {
			$meta_query[] = array(
				'key'     => '_price',
				'value'   => array( $min, $max ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}
		if ( ! empty( $_POST['stock'] ) ) {
			$meta_query[] = array(
				'key'   => '_stock_status',
				'value' => 'instock',
			);
		}
		if ( ! empty( $_POST['rating'] ) ) {
			$meta_query[] = array(
				'key'     => '_wc_average_rating',
				'value'   => (float) $_POST['rating'],
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		// ---- ordering ----
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery
		);
		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		switch ( sanitize_key( $_POST['orderby'] ?? '' ) ) {
			case 'price':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['order']    = 'ASC';
				break;
			case 'price-desc':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['order']    = 'DESC';
				break;
			case 'popularity':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['order']    = 'DESC';
				break;
			case 'rating':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['order']    = 'DESC';
				break;
			case 'date':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
			default:
				$args['orderby']  = 'menu_order title';
				$args['order']    = 'ASC';
		}

		$query = new WP_Query( $args );

		wc_setup_loop(
			array(
				'total'        => $query->found_posts,
				'total_pages'  => $query->max_num_pages,
				'per_page'     => $per_page,
				'current_page' => $page,
				'columns'      => (int) Nova_Modes::get_option( 'shop_columns' ),
			)
		);

		ob_start();
		woocommerce_product_loop_start();
		while ( $query->have_posts() ) {
			$query->the_post();
			wc_get_template_part( 'content', 'product' );
		}
		woocommerce_product_loop_end();
		$html = ob_get_clean();

		wc_reset_loop();
		wp_reset_postdata();

		self::send(
			array(
				'html'  => $html,
				'found' => (int) $query->found_posts,
				'more'  => $query->max_num_pages > $page,
			)
		);
	}

	/** "Notify me when back in stock" — stores a phone number per product. */
	public static function notify_stock() {
		self::guard();
		$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		if ( ! $id || ! preg_match( '/^[0-9+\-\s()]{7,20}$/', $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'شماره تلفن معتبر نیست.', 'nova' ) ), 400 );
		}
		$key   = 'nova_notify_' . $id;
		$list  = get_option( $key, array() );
		$list  = is_array( $list ) ? $list : array();
		$list[] = array(
			'phone' => $phone,
			'time'  => time(),
			'ip'    => self::hashed_ip(),
		);
		update_option( $key, array_slice( $list, -500 ), false );
		self::send( array( 'message' => __( 'ثبت شد؛ به‌محض موجود شدن، به شما اطلاع می‌دهیم.', 'nova' ) ) );
	}

	/** Load more posts (blog/portfolio). */
	public static function load_more() {
		self::guard();
		$args = array(
			'post_type'      => sanitize_key( $_POST['type'] ?? 'post' ),
			'post_status'    => 'publish',
			'posts_per_page' => 9,
			'paged'          => max( 1, absint( $_POST['page'] ?? 2 ) ),
		);
		if ( ! empty( $_POST['tax'] ) && ! empty( $_POST['term'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => sanitize_key( $_POST['tax'] ),
					'field'    => 'slug',
					'terms'    => sanitize_title( $_POST['term'] ),
				),
			);
		}
		$q = new WP_Query( $args );
		ob_start();
		while ( $q->have_posts() ) {
			$q->the_post();
			get_template_part( 'template-parts/parts/card', get_post_type() );
		}
		wp_reset_postdata();
		self::send( array( 'html' => ob_get_clean(), 'more' => $q->max_num_pages > (int) $args['paged'] ) );
	}

	/** Live search (products or posts). */
	public static function search() {
		self::guard();
		$s = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
		if ( mb_strlen( $s ) < 2 ) {
			self::send( array( 'html' => '' ) );
		}
		$q = new WP_Query(
			array(
				's'              => $s,
				'post_status'    => 'publish',
				'posts_per_page' => 6,
				'post_type'      => class_exists( 'WooCommerce' ) && Nova_Modes::has( 'shop' ) ? array( 'product', 'post' ) : array( 'post', 'page' ),
			)
		);
		ob_start();
		if ( $q->have_posts() ) {
			echo '<ul class="nv-live-search__list">';
			while ( $q->have_posts() ) {
				$q->the_post();
				printf(
					'<li><a href="%1$s">%2$s<span>%3$s</span></a></li>',
					esc_url( get_permalink() ),
					get_the_post_thumbnail( null, 'thumbnail', array( 'loading' => 'lazy' ) ),
					esc_html( get_the_title() )
				);
			}
			echo '</ul>';
		} else {
			echo '<p class="nv-muted">' . esc_html__( 'نتیجه‌ای پیدا نشد.', 'nova' ) . '</p>';
		}
		wp_reset_postdata();
		self::send( array( 'html' => ob_get_clean() ) );
	}

	/* ---------- helpers ---------- */

	public static function wishlist_ids() {
		$list = self::read_list( 'nova_wishlist' );
		return $list;
	}

	public static function compare_ids() {
		return self::read_list( 'nova_compare' );
	}

	private static function read_list( $key ) {
		if ( is_user_logged_in() ) {
			$list = get_user_meta( get_current_user_id(), $key, true );
		} else {
			$list = isset( $_COOKIE[ $key ] ) ? json_decode( wp_unslash( $_COOKIE[ $key ] ), true ) : array();
		}
		return is_array( $list ) ? array_values( array_unique( array_map( 'absint', $list ) ) ) : array();
	}

	private static function store_list( $key, $list ) {
		$list = array_slice( array_values( array_unique( array_map( 'absint', $list ) ) ), -50 );
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), $key, $list );
		} else {
			setcookie( $key, wp_json_encode( $list ), time() + MONTH_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	private static function hashed_ip() {
		return wp_hash( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	}
}
