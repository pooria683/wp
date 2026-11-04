<?php
/**
 * Mode manager: decides which parts of the theme are active.
 *
 * The site owner picks one or more "modes" (shop, blog, portfolio, corporate,
 * news, landing…) in the first-run wizard. Everything that is not needed by the
 * selected mode is never booted, never enqueued and never registered, so a shop
 * site does not pay the cost of the portfolio module and vice versa.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Modes {

	const OPTION_KEYS   = 'nova_mode_keys';
	const OPTION_LAYOUT = 'nova_layout_options';
	const OPTION_OPT    = 'nova_feature_options';
	const OPTION_WIZ    = 'nova_wizard_done';

	/**
	 * Available modes => module dependencies + admin label.
	 *
	 * @return array
	 */
	public static function registry() {
		return array(
			'shop'      => array(
				'label'  => __( 'فروشگاه / کسب‌وکار اینترنتی (WooCommerce)', 'nova' ),
				'desc'   => __( 'فروشگاه، فیلترها، صفحه محصول حرفه‌ای، مقایسه، علاقه‌مندی‌ها.', 'nova' ),
				'icon'   => 'cart',
				'modules'=> array( 'blog' ),
			),
			'blog'      => array(
				'label'  => __( 'وبلاگ / مجله', 'nova' ),
				'desc'   => __( 'آرشیو پست، دسته‌بندی، نویسنده، نظرات.', 'nova' ),
				'icon'   => 'tag',
				'modules'=> array(),
			),
			'portfolio' => array(
				'label'  => __( 'نمونه‌کار (Portfolio)', 'nova' ),
				'desc'   => __( 'پست تایپ پروژه، آرشیو گالری‌محور، فیلتر دسته‌بندی.', 'nova' ),
				'icon'   => 'grid',
				'modules'=> array(),
			),
			'corporate' => array(
				'label'  => __( 'شرکتی / آژانس', 'nova' ),
				'desc'   => __( 'صفحات خدمات، تیم، لندینگ.', 'nova' ),
				'icon'   => 'home',
				'modules'=> array( 'blog' ),
			),
			'news'      => array(
				'label'  => __( 'خبری / تخصصی', 'nova' ),
				'desc'   => __( 'چیدمان خبری، تیکر، تیتر ویژه.', 'nova' ),
				'icon'   => 'star',
				'modules'=> array( 'blog' ),
			),
			'landing'   => array(
				'label'  => __( 'لندینگ / تک‌صفحه‌ای', 'nova' ),
				'desc'   => __( 'بدون هدر/فوتر سنگین، مناسب کمپین.', 'nova' ),
				'icon'   => 'chevron',
				'modules'=> array(),
			),
		);
	}

	/** Boot: nothing heavy, only filters + module bootstrapping is done in functions.php. */
	public static function boot() {
		add_action( 'after_switch_theme', array( __CLASS__, 'mark_switch' ) );
		add_action( 'admin_notices', array( __CLASS__, 'wizard_notice' ) );
	}

	public static function mark_switch() {
		delete_option( self::OPTION_WIZ );
		delete_transient( 'nova_active_modules' );
	}

	public static function wizard_notice() {
		if ( self::get_option( self::OPTION_WIZ ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-info is-dismissible"><p><strong>%1$s</strong> %2$s <a href="%3$s" class="button button-primary" style="margin-inline-start:8px">%4$s</a></p></div>',
			esc_html__( 'Nova', 'nova' ),
			esc_html__( 'برای فعال‌سازی بخش‌های مورد نیاز سایت، ابتدا نوع استفاده از قالب را انتخاب کنید.', 'nova' ),
			esc_url( admin_url( 'admin.php?page=nova-wizard' ) ),
			esc_html__( 'شروع راه‌اندازی', 'nova' )
		);
	}

	/**
	 * Selected mode keys (filterable by a theme option).
	 *
	 * @return array
	 */
	public static function keys() {
		$keys = get_option( self::OPTION_KEYS, array() );
		if ( ! is_array( $keys ) ) {
			$keys = array();
		}
		$valid = array_keys( self::registry() );
		$keys  = array_values( array_intersect( $keys, $valid ) );

		// First run: fall back to a blog-ish default so the site is never blank.
		if ( empty( $keys ) && ! self::get_option( self::OPTION_WIZ ) ) {
			$keys = array( 'blog' );
		}

		/**
		 * Filter active mode keys.
		 *
		 * @param array $keys Mode keys.
		 */
		return (array) apply_filters( 'nova/modes', $keys );
	}

	/**
	 * Feature keys that can be switched on from the Modules panel even when the
	 * related mode is not selected (mixing two modes on one site).
	 *
	 * @return array
	 */
	public static function feature_modes() {
		return array(
			'mod_portfolio' => 'portfolio',
			'mod_story'     => 'story',
			'mod_ads'       => 'ads',
		);
	}

	/**
	 * Has a mode (with dependency resolution)?
	 *
	 * A module that is explicitly enabled in the Modules panel counts as active
	 * too, so a shop site can still run the portfolio or stories module alone
	 * without switching the whole site to that mode.
	 *
	 * @param string $mode Mode key.
	 * @return bool
	 */
	public static function has( $mode ) {
		foreach ( self::feature_modes() as $feature => $mapped ) {
			if ( $mapped === $mode && self::feature( $feature ) ) {
				return true;
			}
		}

		$keys = self::keys();
		if ( in_array( $mode, $keys, true ) ) {
			return true;
		}
		foreach ( $keys as $key ) {
			$reg = self::registry();
			if ( isset( $reg[ $key ]['modules'] ) && in_array( $mode, $reg[ $key ]['modules'], true ) ) {
				return true;
			}
		}
		return false;
	}

	/** True when only one mode is selected (used to shrink CSS). */
	public static function is_single() {
		return 1 === count( self::keys() );
	}

	/**
	 * Runtime defaults. Kept here (not only in the admin schema) so templates and
	 * the panel always agree on the same starting point.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// Design.
			'color_primary'      => '#2563eb',
			'color_secondary'    => '#7c3aed',
			'color_text'         => '#18181b',
			'color_muted'        => '#71717a',
			'color_bg'           => '#ffffff',
			'color_surface'      => '#f6f7f9',
			'color_border'       => '#e7e8ec',
			'gradient_from'      => '#2563eb',
			'gradient_to'        => '#7c3aed',
			'gradient_angle'     => 135,
			'gradient_targets'   => array( 'buttons' ),
			'font_body'          => '',
			'font_heading'       => '',
			'font_size'          => 16,
			'font_cdn'           => '',
			'container'          => 1200,
			'layout'             => 'boxed',
			'radius'             => 14,
			'custom_css'         => '',
			// Header / footer.
			'menumode'           => 'drawer',
			'search_mode'        => 'popup',
			'sticky_logo'        => '',
			'header_text'        => __( 'ارسال رایگان برای خریدهای بالای ۵۰۰ هزار تومان', 'nova' ),
			'header_btn_label'   => __( 'تماس با ما', 'nova' ),
			'header_btn_url'     => '#contact',
			'header_html'        => '',
			// Shop.
			'shop_layout'        => 'equal',
			'shop_columns'       => 4,
			'shop_sidebar'       => 'right',
			'shop_filters'       => array( 'cat', 'price', 'attr', 'stock' ),
			'shop_card_widgets'  => array( 'badge', 'rating', 'price', 'cart', 'wishlist', 'compare', 'quickview' ),
			'product_gallery_pos'=> 'left',
			'product_tabs'       => 'tabs',
			'product_show'       => array( 'attrs', 'reviews', 'related', '360', 'notify', 'video', 'customtab' ),
			'wishlist_page'      => 0,
			'compare_page'       => 0,
			// Portfolio.
			'portfolio_slug'     => 'portfolio',
			'portfolio_archive'  => 1,
			// Blog.
			'single_layout'      => 'sidebar',
		);
	}

	/**
	 * Feature switch defaults (panel → modules / seo pages).
	 *
	 * @return array
	 */
	public static function feature_defaults() {
		return array(
			'gradients'        => 1,
			'seo_meta'         => 1,
			'schema'           => 1,
			'trim_bloat'       => 1,
			'trim_block_css'   => 0,
			'editor_style'     => 1,
			'dark_mode'        => 0,
			'back_to_top'      => 1,
			'payment_icons'    => 1,
			'shop_ajax'        => 1,
			'wishlist'         => 1,
			'compare'          => 1,
			'quickview'        => 1,
			'notify_stock'     => 1,
			'product_editor'   => 1,
			'player_speed'     => 1,
			'mod_portfolio'    => 0,
			'mod_story'        => 0,
			'mod_ads'          => 0,
			'mod_slider'       => 1,
			'mod_player'       => 1,
			'mod_ajax'         => 1,
			'pwa_enabled'      => 0,
			'xmlrpc'           => 0,
			'block_author_scan'=> 1,
		);
	}

	/**
	 * Read a theme option with default.
	 *
	 * @param string $key      Option key inside nova_layout_options.
	 * @param mixed  $fallback Default value (null = use the runtime default).
	 * @return mixed
	 */
	public static function get_option( $key, $fallback = null ) {
		$opts = get_option( self::OPTION_LAYOUT, array() );
		if ( is_array( $opts ) && array_key_exists( $key, $opts ) ) {
			return $opts[ $key ];
		}
		if ( null !== $fallback ) {
			return $fallback;
		}
		$defaults = self::defaults();
		return array_key_exists( $key, $defaults ) ? $defaults[ $key ] : '';
	}

	/**
	 * Feature toggle (on/off switches in the panel).
	 *
	 * @param string $key      Feature key.
	 * @param bool   $fallback Default.
	 * @return bool
	 */
	public static function feature( $key, $fallback = null ) {
		$opts = get_option( self::OPTION_OPT, array() );
		if ( is_array( $opts ) && array_key_exists( $key, $opts ) ) {
			return (bool) $opts[ $key ];
		}
		if ( null !== $fallback ) {
			return (bool) $fallback;
		}
		$defaults = self::feature_defaults();
		return array_key_exists( $key, $defaults ) ? (bool) $defaults[ $key ] : false;
	}
}
