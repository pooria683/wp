<?php
/**
 * Header builder: renders header from a saved layout, loads panel parts.
 *
 * Layout option shape:
 * [
 *   'sticky'      => 1,
 *   'transparent' => 0,
 *   'topbar'      => ['text', 'social', 'top-menu'],
 *   'left'        => ['logo'],
 *   'center'      => ['menu'],
 *   'right'       => ['search', 'account', 'wishlist', 'cart'],
 * ]
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Header {

	/** Panels a user is allowed to place. */
	public static function panels() {
		return array(
			'logo'      => __( 'لوگو', 'nova' ),
			'menu'      => __( 'منوی اصلی (با مگامنو)', 'nova' ),
			'top-menu'  => __( 'منوی نوار بالا', 'nova' ),
			'search'    => __( 'جستجو', 'nova' ),
			'cart'      => __( 'سبد خرید', 'nova' ),
			'account'   => __( 'حساب کاربری', 'nova' ),
			'wishlist'  => __( 'علاقه‌مندی‌ها', 'nova' ),
			'compare'   => __( 'مقایسه', 'nova' ),
			'social'    => __( 'شبکه‌های اجتماعی', 'nova' ),
			'text'      => __( 'متن / توضیح کوتاه', 'nova' ),
			'button'    => __( 'دکمه (CTA)', 'nova' ),
			'html'      => __( 'HTML سفارشی', 'nova' ),
			'darkmode'  => __( 'کلید حالت شب', 'nova' ),
			'lang'      => __( 'تغییر زبان (WPML/Polylang)', 'nova' ),
		);
	}

	public static function init() {
		add_filter( 'body_class', array( __CLASS__, 'classes' ) );
		add_action( 'wp_body_open', array( __CLASS__, 'skip_link' ) );
	}

	public static function layout() {
		$default = array(
			'sticky'      => 1,
			'transparent' => 0,
			'topbar'      => array( 'text', 'social' ),
			'left'        => array( 'logo' ),
			'center'      => array( 'menu' ),
			'right'       => array( 'search', 'account', 'cart' ),
		);
		$saved = Nova_Modes::get_option( 'header_layout', array() );
		if ( ! is_array( $saved ) || empty( $saved['left'] ) ) {
			$saved = $default;
		}
		return wp_parse_args( $saved, $default );
	}

	/** Does any menu item expose a mega panel or an icon? Used for conditional CSS. */
	public static function has_mega() {
		static $has = null;
		if ( null !== $has ) {
			return $has;
		}
		$has     = false;
		$locations = get_nav_menu_locations();
		foreach ( array( 'primary', 'mobile', 'top' ) as $loc ) {
			if ( empty( $locations[ $loc ] ) ) {
				continue;
			}
			$items = wp_get_nav_menu_items( $locations[ $loc ] );
			foreach ( (array) $items as $item ) {
				if ( get_post_meta( $item->ID, '_nova_mega', true ) || get_post_meta( $item->ID, '_nova_icon', true ) ) {
					$has = true;
					break 2;
				}
			}
		}
		return $has;
	}

	public static function classes( $classes ) {
		$layout = self::layout();
		$classes[] = 'nv-header-sticky-' . ( $layout['sticky'] ? 'on' : 'off' );
		if ( $layout['transparent'] ) {
			$classes[] = 'nv-header-transparent';
		}
		return $classes;
	}

	public static function skip_link() {
		echo '<a class="nv-skip" href="#nv-main">' . esc_html__( 'رفتن به محتوا', 'nova' ) . '</a>';
	}

	/** Render one panel. Unknown panels are silently ignored. */
	public static function panel( $panel, $area = 'main' ) {
		$panel = sanitize_key( $panel );
		if ( ! isset( self::panels()[ $panel ] ) ) {
			return;
		}
		$file = NOVA_DIR . '/template-parts/header/panel-' . $panel . '.php';
		if ( ! file_exists( $file ) ) {
			return;
		}
		echo '<div class="nv-hpanel nv-hpanel--' . esc_attr( $panel ) . '">';
		load_template( $file, false, array( 'area' => $area ) );
		echo '</div>';
	}

	public static function render() {
		// Elementor Theme Builder takes over when a header template exists.
		if ( function_exists( 'elementor_theme_do_location' ) ) {
			ob_start();
			$rendered = elementor_theme_do_location( 'header' );
			$html     = trim( (string) ob_get_clean() );
			if ( $rendered && '' !== $html ) {
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
				return;
			}
		}
		get_template_part( 'template-parts/header/header' );
	}
}
