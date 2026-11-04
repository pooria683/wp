<?php
/**
 * Shared theme helpers (template tags).
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

/**
 * Social profiles registered in the panel.
 *
 * @return array
 */
function nova_social_networks() {
	return array(
		'instagram' => __( 'اینستاگرام', 'nova' ),
		'telegram'  => __( 'تلگرام', 'nova' ),
		'whatsapp'  => __( 'واتس‌اپ', 'nova' ),
		'x'         => __( 'ایکس (توییتر)', 'nova' ),
		'facebook'  => __( 'فیسبوک', 'nova' ),
		'youtube'   => __( 'یوتیوب', 'nova' ),
		'aparat'    => __( 'آپارات', 'nova' ),
		'linkedin'  => __( 'لینکدین', 'nova' ),
		'pinterest' => __( 'پینترست', 'nova' ),
		'tiktok'    => __( 'تیک‌تاک', 'nova' ),
		'github'    => __( 'گیت‌هاب', 'nova' ),
		'rss'       => __( 'فید RSS', 'nova' ),
		'phone'     => __( 'تلفن', 'nova' ),
		'email'     => __( 'ایمیل', 'nova' ),
	);
}

/** Print social links (only the ones with a value). */
function nova_social_links( $class = '' ) {
	$links = get_option( 'nova_social', array() );
	if ( ! is_array( $links ) || ! array_filter( $links ) ) {
		return;
	}
	echo '<ul class="nv-social ' . esc_attr( $class ) . '">';
	foreach ( $links as $key => $url ) {
		if ( ! $url ) {
			continue;
		}
		$href = $url;
		if ( 'phone' === $key ) {
			$href = 'tel:' . preg_replace( '/[^0-9+]/', '', $url );
		} elseif ( 'email' === $key ) {
			$href = 'mailto:' . sanitize_email( $url );
		} elseif ( 'rss' === $key ) {
			$href = $url ?: get_feed_link();
		}
		printf(
			'<li><a href="%1$s" class="nv-social__%2$s" target="_blank" rel="noopener nofollow" aria-label="%3$s" title="%3$s">%4$s</a></li>',
			esc_url( $href ),
			esc_attr( $key ),
			esc_attr( nova_social_networks()[ $key ] ?? $key ),
			nova_social_icon( $key )
		);
	}
	echo '</ul>';
}

/** Inline brand glyphs (no external icon font). */
function nova_social_icon( $key ) {
	$icons = array(
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1.2" fill="currentColor" stroke="none"/>',
		'telegram'  => '<path d="M21 4 3 11l5 2 2 6 3-4 5 3z"/>',
		'whatsapp'  => '<path d="M20 12a8 8 0 0 1-11.7 7.1L4 20l1-4.2A8 8 0 1 1 20 12Z"/><path d="M9 9c0 3 3 6 6 6"/>',
		'x'         => '<path d="M4 4l16 16M20 4L4 20"/>',
		'facebook'  => '<path d="M15 3h-3a4 4 0 0 0-4 4v3H5v4h3v7h4v-7h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'youtube'   => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9l5 3-5 3z" fill="currentColor" stroke="none"/>',
		'aparat'    => '<circle cx="12" cy="12" r="9"/><path d="M10 8l6 4-6 4z" fill="currentColor" stroke="none"/>',
		'linkedin'  => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10v7M8 7v.5M12 17v-4a2 2 0 0 1 4 0v4"/>',
		'pinterest' => '<circle cx="12" cy="12" r="9"/><path d="M10 20l2-7M9 10a3 3 0 1 1 6 1c0 2-1 3-2 3"/>',
		'tiktok'    => '<path d="M14 4v9a4 4 0 1 1-4-4"/><path d="M14 4c0 3 2 5 5 5"/>',
		'github'    => '<path d="M9 20c-4 0-4-2-6-2m12 4v-3.5a3 3 0 0 0-.8-2.3c2.8-.3 4.8-1.4 4.8-5a4 4 0 0 0-1-2.7 3.7 3.7 0 0 0-.1-2.7s-1.2-.4-3.9 1.5a9 9 0 0 0-5 0C6.3 4.4 5.1 4.8 5.1 4.8a3.7 3.7 0 0 0-.1 2.7A4 4 0 0 0 4 10c0 3.6 2 4.7 4.8 5a3 3 0 0 0-.8 2.3V22"/>',
		'rss'       => '<circle cx="6" cy="18" r="2" fill="currentColor" stroke="none"/><path d="M4 11a9 9 0 0 1 9 9M4 5a15 15 0 0 1 15 15"/>',
		'phone'     => '<path d="M6 3h4l2 5-3 2a11 11 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A18 18 0 0 1 4 5a2 2 0 0 1 2-2Z"/>',
		'email'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
	);
	$path = $icons[ $key ] ?? '<circle cx="12" cy="12" r="8"/>';
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

/**
 * Breadcrumb trail (theme level, no plugin needed).
 *
 * @return array[] Each item: ['name' => string, 'url' => string].
 */
function nova_breadcrumb_items() {
	$items = array(
		array(
			'name' => __( 'خانه', 'nova' ),
			'url'  => home_url( '/' ),
		),
	);

	if ( is_singular() ) {
		$post = get_post();
		$type = $post->post_type;

		if ( 'post' === $type ) {
			$cats = get_the_category( $post->ID );
			if ( $cats ) {
				$parents = array_reverse( get_ancestors( $cats[0]->term_id, 'category' ) );
				foreach ( $parents as $pid ) {
					$term    = get_term( $pid, 'category' );
					$items[] = array( 'name' => $term->name, 'url' => get_term_link( $term ) );
				}
				$items[] = array( 'name' => $cats[0]->name, 'url' => get_term_link( $cats[0] ) );
			}
		} elseif ( 'product' === $type && function_exists( 'wc_get_product_category_list' ) ) {
			$terms = get_the_terms( $post->ID, 'product_cat' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$items[] = array( 'name' => $terms[0]->name, 'url' => get_term_link( $terms[0] ) );
			}
		} elseif ( 'nova_portfolio' === $type ) {
			$terms = get_the_terms( $post->ID, 'nova_portfolio_cat' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$items[] = array(
					'name' => __( 'نمونه‌کارها', 'nova' ),
					'url'  => get_post_type_archive_link( 'nova_portfolio' ),
				);
				$items[] = array( 'name' => $terms[0]->name, 'url' => get_term_link( $terms[0] ) );
			}
		}

		$items[] = array( 'name' => get_the_title( $post ), 'url' => '' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy ) ) as $pid ) {
			$parent  = get_term( $pid, $term->taxonomy );
			$items[] = array( 'name' => $parent->name, 'url' => get_term_link( $parent ) );
		}
		$items[] = array( 'name' => single_term_title( '', false ), 'url' => '' );
	} elseif ( is_search() ) {
		$items[] = array( 'name' => sprintf( __( 'نتایج «%s»', 'nova' ), get_search_query() ), 'url' => '' );
	} elseif ( is_404() ) {
		$items[] = array( 'name' => __( 'صفحه یافت نشد', 'nova' ), 'url' => '' );
	} elseif ( is_home() && ! is_front_page() ) {
		$items[] = array( 'name' => single_post_title( '', false ), 'url' => '' );
	} elseif ( is_archive() ) {
		if ( function_exists( 'is_shop' ) && is_shop() && Nova_Modes::has( 'shop' ) ) {
			$items[] = array( 'name' => get_the_title( wc_get_page_id( 'shop' ) ), 'url' => '' );
		} elseif ( is_post_type_archive() ) {
			$items[] = array( 'name' => post_type_archive_title( '', false ), 'url' => '' );
		} else {
			$items[] = array( 'name' => wp_strip_all_tags( get_the_archive_title() ), 'url' => '' );
		}
	}

	return $items;
}

/**
 * Shorthand for reading a layout option.
 *
 * @param string $key      Option name.
 * @param mixed  $fallback Default.
 * @return mixed
 */
function nova_opt( $key, $fallback = '' ) {
	return Nova_Modes::get_option( $key, $fallback );
}

/**
 * Cart / account counters used by the header panels.
 */
function nova_cart_count() {
	if ( ! Nova_Modes::has( 'shop' ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}
	return (int) WC()->cart->get_cart_contents_count();
}

/**
 * Print a "card" style post tile — used by blog & portfolio grids.
 *
 * @param int    $post_id  Post id.
 * @param string $style    'vertical' | 'horizontal' | 'overlay'.
 * @param string $size     Image size.
 */
function nova_post_card( $post_id = null, $style = 'vertical', $size = 'nova-card' ) {
	$post_id = $post_id ?: get_the_ID();
	$url     = get_permalink( $post_id );
	$thumb   = get_the_post_thumbnail( $post_id, $size, array( 'loading' => 'lazy', 'decoding' => 'async' ) );
	?>
	<article class="nv-card nv-card--<?php echo esc_attr( $style ); ?>">
		<a class="nv-card__media" href="<?php echo esc_url( $url ); ?>" aria-hidden="true" tabindex="-1">
			<?php
			if ( $thumb ) {
				echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo '<span class="nv-card__ph"></span>';
			}
			if ( 'post' === get_post_type( $post_id ) ) {
				$cat = get_the_category( $post_id );
				if ( $cat ) {
					echo '<span class="nv-card__cat">' . esc_html( $cat[0]->name ) . '</span>';
				}
			}
			?>
		</a>
		<div class="nv-card__body">
			<h3 class="nv-card__title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
			<?php if ( 'overlay' !== $style ) : ?>
				<p class="nv-card__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), 18 ) ); ?></p>
			<?php endif; ?>
			<div class="nv-card__meta">
				<time datetime="<?php echo esc_attr( get_the_date( 'c', $post_id ) ); ?>"><?php echo esc_html( get_the_date( '', $post_id ) ); ?></time>
				<span class="nv-dot" aria-hidden="true">·</span>
				<span><?php echo esc_html( get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ) ); ?></span>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Pagination wrapper.
 */
function nova_pagination() {
	the_posts_pagination(
		array(
			'mid_size'           => 1,
			'prev_text'          => '<span aria-hidden="true">‹</span><span class="screen-reader-text">' . esc_html__( 'قبلی', 'nova' ) . '</span>',
			'next_text'          => '<span aria-hidden="true">›</span><span class="screen-reader-text">' . esc_html__( 'بعدی', 'nova' ) . '</span>',
			'screen_reader_text' => __( 'صفحه‌بندی', 'nova' ),
			'class'              => 'nv-pagination',
		)
	);
}

/**
 * Menu fallback: page list, so a fresh install still has navigation.
 */
function nova_menu_fallback() {
	echo '<ul class="nv-menu">';
	wp_list_pages(
		array(
			'title_li'  => '',
			'depth'     => 1,
			'number'    => 6,
			'link_before' => '<span class="nv-menu-label">',
			'link_after'  => '</span>',
		)
	);
	echo '</ul>';
}

/**
 * Payment / trust badges in the footer (SVG, no external images).
 */
function nova_payment_badges() {
	if ( ! Nova_Modes::feature( 'payment_icons', true ) ) {
		return;
	}
	$badges = array( 'secure' => __( 'پرداخت امن', 'nova' ), 'shipping' => __( 'ارسال سریع', 'nova' ), 'return' => __( '۷ روز ضمانت بازگشت', 'nova' ), 'support' => __( 'پشتیبانی ۲۴/۷', 'nova' ) );
	echo '<ul class="nv-badges">';
	foreach ( $badges as $key => $label ) {
		printf( '<li class="nv-badges__item nv-badges__item--%s">%s<span>%s</span></li>', esc_attr( $key ), Nova_Menus::icon_svg( 'star', 'nv-ico nv-badges__ico' ), esc_html( $label ) );
	}
	echo '</ul>';
}
