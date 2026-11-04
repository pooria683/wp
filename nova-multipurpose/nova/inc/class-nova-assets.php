<?php
/**
 * Asset loader — "technology of separate loading".
 *
 * Rules:
 *  1. One tiny core CSS + one tiny core JS are always loaded (~4KB).
 *  2. Every other module (shop, single product, portfolio, player, story, ads,
 *     mega menu…) is registered but enqueued only when the current request
 *     actually needs it.
 *  3. Anything the admin disabled in the panel is never registered at all.
 *  4. Dynamic (customizer) values are printed as CSS custom properties in one
 *     inline block instead of generating a separate stylesheet file.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Assets {

	/** @var array|null Lazy detected context. */
	private static $ctx = null;

	/** @var array Handles that must be printed in the footer. */
	private static $footer = array();

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'dynamic_css' ), 30 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'script_attrs' ), 10, 3 );
		add_filter( 'style_loader_tag', array( __CLASS__, 'style_attrs' ), 10, 2 );
		add_action( 'wp_head', array( __CLASS__, 'preconnect' ), 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'dequeue_bloat' ), 100 );
	}

	/**
	 * Register (not enqueue) everything. Registration costs nothing.
	 */
	public static function register() {
		$v = NOVA_VERSION;
		$u = NOVA_URI . '/assets';

		wp_register_style( 'nova-base', "$u/css/base.css", array(), $v );

		// Module styles — enqueued on demand only.
		$styles = array(
			'slider'    => 'slider.css',
			'header'    => 'header.css',
			'footer'    => 'footer.css',
			'mega'      => 'megamenu.css',
			'blog'      => 'blog.css',
			'shop'      => 'shop.css',
			'product'   => 'product.css',
			'portfolio' => 'portfolio.css',
			'player'    => 'player.css',
			'story'     => 'story.css',
			'ads'       => 'ads.css',
			'rtl'       => 'rtl.css',
		);
		foreach ( $styles as $handle => $file ) {
			wp_register_style( "nova-$handle", "$u/css/$file", array( 'nova-base' ), $v );
		}

		// Scripts.
		wp_register_script( 'nova-core', "$u/js/core.js", array(), $v, true );
		$scripts = array(
			'player'    => 'player.js',
			'shop'      => 'shop.js',
			'portfolio' => 'portfolio.js',
			'story'     => 'story.js',
			'slider'    => 'slider.js',
			'mega'      => 'megamenu.js',
		);
		foreach ( $scripts as $handle => $file ) {
			wp_register_script( "nova-$handle", "$u/js/$file", array( 'nova-core' ), $v, true );
		}

		wp_localize_script(
			'nova-core',
			'NOVA',
			array(
				'ajax'   => admin_url( 'admin-ajax.php' ),
				'nonce'  => wp_create_nonce( 'nova_ajax' ),
				'rtl'    => is_rtl(),
				'i18n'   => array(
					'added'    => __( 'به سبد خرید اضافه شد', 'nova' ),
					'error'    => __( 'خطایی رخ داد، دوباره تلاش کنید.', 'nova' ),
					'loading'  => __( 'در حال بارگذاری…', 'nova' ),
					'close'    => __( 'بستن', 'nova' ),
					'play'     => __( 'پخش', 'nova' ),
					'pause'    => __( 'توقف', 'nova' ),
					'mute'     => __( 'بی‌صدا', 'nova' ),
					'full'     => __( 'تمام‌صفحه', 'nova' ),
					'next'     => __( 'بعدی', 'nova' ),
					'prev'     => __( 'قبلی', 'nova' ),
				),
			)
		);
	}

	/**
	 * Detect what the current request needs. Cached in a static + a request
	 * transient-free object cache friendly way.
	 *
	 * @return array
	 */
	public static function context() {
		if ( null !== self::$ctx ) {
			return self::$ctx;
		}

		$ctx = array(
			'player'    => false,
			'slider'    => false,
			'story'     => false,
			'gallery'   => false,
			'portfolio' => false,
			'shop'      => false,
			'product'   => false,
		);

		// Whole-request conditions (cheap, no query content).
		if ( function_exists( 'is_woocommerce' ) && Nova_Modes::has( 'shop' ) ) {
			$ctx['shop'] = is_shop() || is_product_taxonomy() || is_cart() || is_checkout() || is_account_page();
			$ctx['shop'] = $ctx['shop'] || is_product();
			$ctx['product'] = is_product();
		}
		if ( is_singular( 'nova_portfolio' ) || is_post_type_archive( 'nova_portfolio' ) || is_tax( 'nova_portfolio_cat' ) ) {
			$ctx['portfolio'] = true;
		}

		// Content based detection (only for real singular views).
		$content = '';
		if ( is_singular() && ! is_404() && ! is_search() ) {
			$post = get_post();
			if ( $post ) {
				$content = $post->post_content;
			}
		}

		if ( $content ) {
			if ( has_shortcode( $content, 'nova_player' ) || has_block( 'nova/player', $content ) ) {
				$ctx['player'] = true;
			}
			if ( has_shortcode( $content, 'nova_slider' ) || has_block( 'nova/slider', $content ) ) {
				$ctx['slider'] = true;
			}
			if ( has_shortcode( $content, 'nova_story' ) || has_block( 'nova/story', $content ) ) {
				$ctx['story'] = true;
			}
			if ( has_shortcode( $content, 'nova_portfolio' ) || has_block( 'nova/portfolio' ) ) {
				$ctx['portfolio'] = true;
			}
			if ( has_shortcode( $content, 'gallery' ) ) {
				$ctx['gallery'] = true;
			}
		}

		// Widgets & builders.
		if ( self::widget_contains( 'nova_player' ) || self::widget_contains( 'nova_slider' ) ) {
			$ctx['player']    = true;
			$ctx['slider']    = true;
		}

		// Single post/page always needs typography-ready base only; blog part
		// only on archive-ish views.
		self::$ctx = $ctx;

		/**
		 * Filter the runtime asset context (allows child themes / plugins to
		 * force-load a module on a specific template).
		 *
		 * @param array $ctx Context flags.
		 */
		return self::$ctx = apply_filters( 'nova/assets/context', $ctx );
	}

	/** Cheap check: does any active widget contain a shortcode/block? */
	private static function widget_contains( $shortcode ) {
		static $map = null;
		if ( null === $map ) {
			$map = array();
			$data = wp_get_sidebars_widgets();
			$opts = get_option( 'widget_block', array() );
			foreach ( (array) $data as $widgets ) {
				foreach ( (array) $widgets as $id ) {
					if ( 0 === strpos( (string) $id, 'block-' ) && is_array( $opts ) ) {
						if ( isset( $opts[ substr( $id, 6 ) ]['content'] ) ) {
							$map[] = $opts[ substr( $id, 6 ) ]['content'];
						}
					}
				}
			}
			$map = implode( ' ', $map );
		}
		return $map && false !== strpos( $map, $shortcode );
	}

	/** Actually enqueue what the context says. */
	public static function enqueue() {
		$ctx = self::context();

		wp_enqueue_style( 'nova-base' );
		wp_enqueue_script( 'nova-core' );

		if ( is_rtl() ) {
			wp_enqueue_style( 'nova-rtl' );
		}

		// Header/footer builders always matter, but each has its own small file.
		wp_enqueue_style( 'nova-header' );
		wp_enqueue_style( 'nova-footer' );

		if ( Nova_Header::has_mega() ) {
			wp_enqueue_style( 'nova-mega' );
			wp_enqueue_script( 'nova-mega' );
		}

		if ( ( is_home() || is_archive() || is_search() || is_author() || is_singular( 'post' ) ) && Nova_Modes::has( 'blog' ) ) {
			wp_enqueue_style( 'nova-blog' );
		}

		if ( $ctx['shop'] ) {
			wp_enqueue_style( 'nova-shop' );
			wp_enqueue_script( 'nova-shop' );
		}
		if ( $ctx['product'] ) {
			wp_enqueue_style( 'nova-product' );
		}
		if ( $ctx['portfolio'] ) {
			wp_enqueue_style( 'nova-portfolio' );
			wp_enqueue_script( 'nova-portfolio' );
		}
		if ( $ctx['player'] ) {
			wp_enqueue_style( 'nova-player' );
			wp_enqueue_script( 'nova-player' );
		}
		if ( $ctx['slider'] ) {
			wp_enqueue_style( 'nova-slider' );
			wp_enqueue_script( 'nova-slider' );
		}
		if ( $ctx['story'] ) {
			wp_enqueue_style( 'nova-story' );
			wp_enqueue_script( 'nova-story' );
		}
		if ( Nova_Modes::has( 'ads' ) && Nova_Ads::needs_css() ) {
			wp_enqueue_style( 'nova-ads' );
		}

		// Elementor: let the base file be a dependency so its editor preview
		// always has theme variables.
		if ( did_action( 'elementor/loaded' ) ) {
			wp_enqueue_style( 'nova-base' );
		}

		/**
		 * Allow plugins/child theme to enqueue on demand.
		 *
		 * @param array $ctx Asset context.
		 */
		do_action( 'nova/assets/enqueue', $ctx );
	}

	/**
	 * Request-scoped enqueue helpers used by shortcodes/blocks. Also survives
	 * the case where a shortcode is rendered inside a widget that is printed
	 * after wp_enqueue_scripts has already run.
	 *
	 * @param string $handle Short module handle, e.g. 'player'.
	 */
	public static function ensure( $handle ) {
		$style  = "nova-$handle";
		$script = "nova-$handle";
		if ( wp_style_is( $style, 'registered' ) && ! wp_style_is( $style, 'enqueued' ) ) {
			wp_enqueue_style( $style );
		}
		if ( wp_script_is( $script, 'registered' ) && ! wp_script_is( $script, 'enqueued' ) ) {
			wp_enqueue_script( $script );
		}
	}

	/**
	 * Build the CSS custom properties block.
	 *
	 * Everything visual (colours, gradients, radii, fonts, container width,
	 * typography scale) is a variable, so a single inline block themes the
	 * whole site — no duplicated stylesheets per page.
	 */
	public static function dynamic_css() {
		$primary   = Nova_Modes::get_option( 'color_primary', '#2563eb' );
		$secondary = Nova_Modes::get_option( 'color_secondary', '#7c3aed' );
		$text      = Nova_Modes::get_option( 'color_text', '#18181b' );
		$muted     = Nova_Modes::get_option( 'color_muted', '#71717a' );
		$bg        = Nova_Modes::get_option( 'color_bg', '#ffffff' );
		$surface   = Nova_Modes::get_option( 'color_surface', '#f6f7f9' );
		$border    = Nova_Modes::get_option( 'color_border', '#e7e8ec' );

		$grad_enabled = Nova_Modes::feature( 'gradients', true );
		$grad_angle   = (int) Nova_Modes::get_option( 'gradient_angle', 135 );
		$grad_from    = Nova_Modes::get_option( 'gradient_from', $primary );
		$grad_to      = Nova_Modes::get_option( 'gradient_to', $secondary );

		$radius  = (int) Nova_Modes::get_option( 'radius', 14 );
		$width   = (int) Nova_Modes::get_option( 'container', 1200 );
		$font    = Nova_Modes::get_option( 'font_body', '' );
		$head    = Nova_Modes::get_option( 'font_heading', '' );
		$base_fs = (int) Nova_Modes::get_option( 'font_size', 16 );

		$vars = array(
			'--nv-primary'     => $primary,
			'--nv-primary-rgb' => self::rgb( $primary ),
			'--nv-secondary'   => $secondary,
			'--nv-text'        => $text,
			'--nv-muted'       => $muted,
			'--nv-bg'          => $bg,
			'--nv-surface'     => $surface,
			'--nv-border'      => $border,
			'--nv-radius'      => $radius . 'px',
			'--nv-container'   => $width . 'px',
			'--nv-font-size'   => $base_fs . 'px',
			'--nv-gradient'    => $grad_enabled ? sprintf( 'linear-gradient(%1$ddeg, %2$s, %3$s)', $grad_angle, $grad_from, $grad_to ) : 'none',
		);
		if ( $font ) {
			$vars['--nv-font'] = "'" . sanitize_text_field( $font ) . "', system-ui, -apple-system, 'Segoe UI', Tahoma, sans-serif";
		}
		if ( $head ) {
			$vars['--nv-font-heading'] = "'" . sanitize_text_field( $head ) . "', system-ui, -apple-system, 'Segoe UI', Tahoma, sans-serif";
		}

		/**
		 * Filter the generated CSS variables.
		 *
		 * @param array $vars Variable => value.
		 */
		$vars = apply_filters( 'nova/css/variables', $vars );

		$css = ':root{';
		foreach ( $vars as $k => $v ) {
			if ( '' === $v || null === $v ) {
				continue;
			}
			$css .= $k . ':' . $v . ';';
		}
		$css .= '}';

		// Font faces for uploaded / referenced custom fonts.
		$faces = get_option( 'nova_font_faces', array() );
		if ( is_array( $faces ) ) {
			foreach ( $faces as $face ) {
				if ( empty( $face['src'] ) ) {
					continue;
				}
				$css .= sprintf(
					"@font-face{font-family:'%s';src:url('%s') format('%s');font-weight:%s;font-display:swap;}",
					esc_attr( $face['family'] ?? 'NovaFont' ),
					esc_url_raw( $face['src'] ),
					esc_attr( $face['format'] ?? 'woff2' ),
					esc_attr( $face['weight'] ?? '400' )
				);
			}
		}

		// User custom CSS (panel → Custom CSS), sanitised on save.
		$custom = (string) Nova_Modes::get_option( 'custom_css', '' );
		if ( $custom ) {
			$css .= "\n" . $custom;
		}

		wp_add_inline_style( 'nova-base', $css );
	}

	/** Hex → "r,g,b" for rgb() usage. */
	private static function rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return '37,99,235';
		}
		return implode(
			',',
			array(
				hexdec( substr( $hex, 0, 2 ) ),
				hexdec( substr( $hex, 2, 2 ) ),
				hexdec( substr( $hex, 4, 2 ) ),
			)
		);
	}

	/** Add defer to our scripts (WP < 6.3 fallback) and module preload hints. */
	public static function script_attrs( $tag, $handle, $src ) {
		if ( 'nova-' === substr( $handle, 0, 5 ) && false === strpos( $tag, 'defer' ) ) {
			$tag = str_replace( ' src=', ' defer src=', $tag );
		}
		return $tag;
	}

	/** Skip the media="all" onload trick; nothing to change but keeps filter slot. */
	public static function style_attrs( $tag, $handle ) {
		return $tag;
	}

	/** Preconnect only to hosts we actually use. */
	public static function preconnect() {
		$hosts = array();
		$ctx   = self::context();
		if ( $ctx['player'] ) {
			$hosts[] = 'https://www.youtube.com';
			$hosts[] = 'https://www.aparat.com';
			$hosts[] = 'https://player.vimeo.com';
		}
		$font_url = Nova_Modes::get_option( 'font_cdn', '' );
		if ( $font_url ) {
			$hosts[] = $font_url;
		}
		foreach ( array_unique( $hosts ) as $host ) {
			printf( '<link rel="preconnect" href="%s" crossorigin>' . "\n", esc_url( $host ) );
		}
	}

	/** Remove bloat that plugins add globally (emoji, etc.) when enabled. */
	public static function dequeue_bloat() {
		if ( ! Nova_Modes::feature( 'trim_bloat', true ) ) {
			return;
		}
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
		// Classic block library CSS is not needed on non-block themes.
		if ( Nova_Modes::feature( 'trim_block_css', false ) ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'global-styles' );
		}
	}
}
