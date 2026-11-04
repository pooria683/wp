<?php
/**
 * Theme setup: supports, menus, image sizes, editor, translation.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Setup {

	public static function init() {
		add_action( 'after_setup_theme', array( __CLASS__, 'setup' ) );
		add_action( 'widgets_init', array( __CLASS__, 'widgets' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'excerpt_length', array( __CLASS__, 'excerpt_length' ) );
		add_filter( 'excerpt_more', array( __CLASS__, 'excerpt_more' ) );
		add_action( 'wp_head', array( __CLASS__, 'head_meta' ), 1 );
		add_filter( 'language_attributes', array( __CLASS__, 'language_attributes' ) );
		add_action( 'customize_register', array( __CLASS__, 'customizer' ) );
	}

	public static function setup() {
		load_theme_textdomain( 'nova', NOVA_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'customize-selective-refresh-widgets' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 64,
				'width'       => 240,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);

		// WooCommerce: opt out of its global CSS so our conditional file is the
		// single source of truth.
		add_theme_support( 'woocommerce', array( 'thumbnail_image_width' => 600, 'single_image_width' => 900 ) );
		if ( Nova_Modes::has( 'shop' ) ) {
			add_theme_support( 'wc-product-gallery-zoom' );
			add_theme_support( 'wc-product-gallery-lightbox' );
			add_theme_support( 'wc-product-gallery-slider' );
		}

		add_image_size( 'nova-card', 640, 420, true );
		add_image_size( 'nova-wide', 1280, 640, true );
		add_image_size( 'nova-square', 480, 480, true );

		register_nav_menus(
			array(
				'primary' => __( 'منوی اصلی', 'nova' ),
				'mobile'  => __( 'منوی موبایل', 'nova' ),
				'footer'  => __( 'منوی فوتر', 'nova' ),
				'top'     => __( 'نوار بالایی', 'nova' ),
			)
		);

		if ( Nova_Modes::feature( 'editor_style', true ) ) {
			add_editor_style( 'assets/css/editor.css' );
		}
	}

	public static function widgets() {
		register_sidebar(
			array(
				'name'          => __( 'ستون کنار (وبلاگ)', 'nova' ),
				'id'            => 'sidebar-main',
				'before_widget' => '<section id="%1$s" class="nv-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="nv-widget__title">',
				'after_title'   => '</h3>',
			)
		);
		register_sidebar(
			array(
				'name'          => __( 'فوتر — ستون ۱', 'nova' ),
				'id'            => 'footer-1',
				'before_widget' => '<section id="%1$s" class="nv-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4 class="nv-widget__title">',
				'after_title'   => '</h4>',
			)
		);
		register_sidebar(
			array(
				'name'          => __( 'فوتر — ستون ۲', 'nova' ),
				'id'            => 'footer-2',
				'before_widget' => '<section id="%1$s" class="nv-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4 class="nv-widget__title">',
				'after_title'   => '</h4>',
			)
		);
		register_sidebar(
			array(
				'name'          => __( 'فروشگاه — فیلتر', 'nova' ),
				'id'            => 'shop-sidebar',
				'description'   => __( 'فقط در حالت فروشگاهی ثبت می‌شود.', 'nova' ),
				'before_widget' => '<section id="%1$s" class="nv-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4 class="nv-widget__title">',
				'after_title'   => '</h4>',
			)
		);
	}

	public static function body_class( $classes ) {
		foreach ( Nova_Modes::keys() as $mode ) {
			$classes[] = 'nv-mode-' . sanitize_html_class( $mode );
		}
		$classes[] = 'nv-' . ( Nova_Modes::get_option( 'layout', 'boxed' ) === 'wide' ? 'wide' : 'boxed' );
		$classes[] = is_rtl() ? 'nv-rtl' : 'nv-ltr';
		if ( Nova_Modes::feature( 'dark_mode', false ) ) {
			$classes[] = 'nv-dark-ready';
		}
		return $classes;
	}

	public static function excerpt_length() {
		return 24;
	}

	public static function excerpt_more() {
		return '…';
	}

	public static function language_attributes( $output ) {
		return $output;
	}

	/** SEO basics that a theme can safely own (title-tag handles the rest). */
	public static function head_meta() {
		if ( ! Nova_Modes::feature( 'seo_meta', true ) ) {
			return;
		}
		echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";

		$desc = '';
		if ( is_singular() ) {
			$desc = get_the_excerpt( get_queried_object_id() );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$desc = term_description();
		} elseif ( is_home() ) {
			$desc = get_bloginfo( 'description' );
		}
		$desc = wp_strip_all_tags( (string) $desc );
		if ( $desc ) {
			$desc = mb_substr( $desc, 0, 160 );
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
		}

		// Open Graph (only if no SEO plugin took over).
		if ( ! defined( 'WPSEO_VERSION' ) && ! class_exists( 'RankMath' ) ) {
			$img = '';
			if ( is_singular() && has_post_thumbnail() ) {
				$img = get_the_post_thumbnail_url( get_queried_object_id(), 'nova-wide' );
			}
			printf( '<meta property="og:type" content="%s">' . "\n", is_singular() ? 'article' : 'website' );
			printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( wp_get_document_title() ) );
			printf( '<meta property="og:url" content="%s">' . "\n", esc_url( home_url( add_query_arg( array() ) ) ) );
			printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
			if ( $desc ) {
				printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
			}
			if ( $img ) {
				printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $img ) );
			}
			printf( '<meta name="twitter:card" content="%s">' . "\n", $img ? 'summary_large_image' : 'summary' );
		}

		// Structured data: Article / Product-ready breadcrumb JSON-LD.
		if ( is_singular() && Nova_Modes::feature( 'schema', true ) ) {
			$schema = array(
				'@context' => 'https://schema.org',
				'@type'    => is_singular( 'post' ) ? 'BlogPosting' : 'WebPage',
				'headline' => wp_strip_all_tags( get_the_title() ),
				'url'      => get_permalink(),
				'datePublished' => get_the_date( 'c' ),
				'dateModified'  => get_the_modified_date( 'c' ),
				'author'   => array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', get_post_field( 'post_author', get_queried_object_id() ) ),
				),
			);
			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
		}
	}

	/** A small customizer section so the theme is usable without the panel. */
	public static function customizer( $wp_customize ) {
		$wp_customize->add_section(
			'nova_identity',
			array(
				'title'    => __( 'Nova — رنگ و لوگو', 'nova' ),
				'priority' => 25,
			)
		);

		$fields = array(
			'color_primary'   => array( __( 'رنگ اصلی', 'nova' ), '#2563eb' ),
			'color_secondary' => array( __( 'رنگ مکمل', 'nova' ), '#7c3aed' ),
		);
		foreach ( $fields as $key => $data ) {
			$wp_customize->add_setting( 'nova_' . $key, array( 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ) );
			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					'nova_' . $key,
					array(
						'label'   => $data[0],
						'section' => 'nova_identity',
					)
				)
			);
		}

		$wp_customize->add_setting( 'nova_custom_css', array( 'sanitize_callback' => 'wp_strip_all_tags', 'transport' => 'refresh' ) );
		$wp_customize->add_control(
			'nova_custom_css',
			array(
				'type'        => 'textarea',
				'label'       => __( 'CSS سفارشی', 'nova' ),
				'section'     => 'nova_identity',
			)
		);
	}
}
