<?php
/**
 * Ad slots.
 *
 * A "zone" is a named position (header, before-content, sidebar, footer,
 * in-feed…). Each zone can hold one or more `nova_ad` posts; the module picks
 * one at random, counts impressions/clicks (privacy friendly, no cookies) and
 * hides itself on the pages listed in the ad's own "exclude" meta.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Ads {

	const META_FIELDS = 3;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_zones' ) );
		add_shortcode( 'nova_ad_zone', array( __CLASS__, 'zone_shortcode' ) );
		add_action( 'wp_ajax_nova_ad_click', array( __CLASS__, 'click' ) );
		add_action( 'wp_ajax_nopriv_nova_ad_click', array( __CLASS__, 'click' ) );
	}

	/** Built-in zones. */
	public static function zones() {
		return array(
			'header'        => __( 'زیر هدر', 'nova' ),
			'home-top'      => __( 'بالای صفحه اصلی', 'nova' ),
			'before-content'=> __( 'بالای محتوا', 'nova' ),
			'after-content' => __( 'پایین محتوا', 'nova' ),
			'sidebar'       => __( 'ستون کنار', 'nova' ),
			'footer'        => __( 'بالای فوتر', 'nova' ),
			'shop-top'      => __( 'بالای فروشگاه', 'nova' ),
			'in-feed'       => __( 'داخل لیست پست‌ها', 'nova' ),
			'product'       => __( 'صفحه محصول', 'nova' ),
		);
	}

	/** Auto-create a term inside nova_ad_zone for each zone. */
	public static function register_zones() {
		foreach ( self::zones() as $slug => $label ) {
			if ( ! term_exists( $slug, 'nova_ad_zone' ) ) {
				wp_insert_term( $label, 'nova_ad_zone', array( 'slug' => $slug ) );
			}
		}
	}

	/** Does this request need the ads stylesheet? */
	public static function needs_css() {
		return (bool) self::active_slot_guess();
	}

	private static function active_slot_guess() {
		// Cheap check (cached counts, no query): are there any published ads?
		$counts = wp_count_posts( 'nova_ad' );
		return ! empty( $counts->publish );
	}

	/**
	 * Render a slot.
	 *
	 * @param int|string $id   Specific ad post id.
	 * @param string     $zone Zone slug.
	 * @return string
	 */
	public static function render( $id = 0, $zone = '' ) {
		$ad = null;

		if ( $id ) {
			$ad = get_post( absint( $id ) );
		} elseif ( $zone ) {
			$q = new WP_Query(
				array(
					'post_type'      => 'nova_ad',
					'post_status'    => 'publish',
					'posts_per_page' => 5,
					'orderby'        => 'rand',
					'no_found_rows'  => true,
					'tax_query'      => array(
						array(
							'taxonomy' => 'nova_ad_zone',
							'field'    => 'slug',
							'terms'    => sanitize_title( $zone ),
						),
					),
					'meta_query'     => array(
						'relation' => 'OR',
						array(
							'key'     => '_nova_ad_start',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_nova_ad_start',
							'value'   => time(),
							'compare' => '<=',
							'type'    => 'NUMERIC',
						),
					),
				)
			);
			$ad = $q->posts ? $q->posts[0] : null;
			wp_reset_postdata();
		}

		if ( ! $ad || 'publish' !== $ad->post_status ) {
			return '';
		}

		// Scheduled expiry.
		$end = (int) get_post_meta( $ad->ID, '_nova_ad_end', true );
		if ( $end && $end < time() ) {
			return '';
		}

		// Per-page exclusion rules.
		if ( self::is_excluded( $ad->ID ) ) {
			return '';
		}

		self::count( $ad->ID, 'imp' );

		$img  = get_the_post_thumbnail_url( $ad->ID, 'nova-wide' );
		$link = get_post_meta( $ad->ID, '_nova_ad_link', true );
		$html = $ad->post_content;

		ob_start();
		echo '<div class="nv-ad nv-ad--' . esc_attr( $zone ? $zone : 'inline' ) . '" data-ad="' . (int) $ad->ID . '">';
		echo '<span class="nv-ad__label">' . esc_html__( 'تبلیغات', 'nova' ) . '</span>';

		if ( $html ) {
			echo '<div class="nv-ad__html">' . do_shortcode( wp_kses_post( $html ) ) . '</div>';
		} elseif ( $img && $link ) {
			printf(
				'<a class="nv-ad__link" href="%1$s" target="_blank" rel="nofollow sponsored noopener" data-nv-ad-click="%2$d"><img src="%3$s" alt="%4$s" loading="lazy" decoding="async"></a>',
				esc_url( $link ),
				(int) $ad->ID,
				esc_url( $img ),
				esc_attr( get_the_title( $ad ) )
			);
		} elseif ( $img ) {
			printf( '<img src="%1$s" alt="%2$s" loading="lazy">', esc_url( $img ), esc_attr( get_the_title( $ad ) ) );
		}
		echo '</div>';
		return ob_get_clean();
	}

	private static function is_excluded( $post_id ) {
		$rules = get_post_meta( $post_id, '_nova_ad_exclude', true );
		if ( ! $rules ) {
			return false;
		}
		$rules = array_map( 'trim', explode( ',', $rules ) );
		foreach ( $rules as $rule ) {
			if ( 'home' === $rule && is_front_page() ) {
				return true;
			}
			if ( 'shop' === $rule && function_exists( 'is_shop' ) && is_shop() ) {
				return true;
			}
			if ( 'product' === $rule && function_exists( 'is_product' ) && is_product() ) {
				return true;
			}
			if ( 'mobile' === $rule ) {
				return false;
			}
			if ( is_singular() && (int) $rule === get_queried_object_id() ) {
				return true;
			}
		}
		return false;
	}

	/** Daily impression / click counters stored in a single option array. */
	private static function count( $post_id, $type ) {
		$key  = 'nova_ad_stats_' . gmdate( 'Ymd' );
		$data = get_option( $key, array() );
		$data = is_array( $data ) ? $data : array();
		if ( ! isset( $data[ $post_id ] ) ) {
			$data[ $post_id ] = array( 'imp' => 0, 'clk' => 0 );
		}
		$data[ $post_id ][ $type ] = (int) $data[ $post_id ][ $type ] + 1;
		update_option( $key, $data, false );
	}

	/** Click tracking (nonce-less by design: fire-and-forget pixel). */
	public static function click() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error();
		}
		self::count( $id, 'clk' );
		wp_send_json_success();
	}

	/** [nova_ad_zone zone="header"] */
	public static function zone_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'zone' => 'before-content', 'id' => '' ), $atts, 'nova_ad_zone' );
		return self::render( $atts['id'], $atts['zone'] );
	}

	/** Helper for templates: echo a zone. */
	public static function zone( $zone ) {
		$out = self::render( 0, $zone );
		if ( $out ) {
			echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
}
