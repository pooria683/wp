<?php
/**
 * Custom post types & taxonomies created by the theme itself.
 *
 * The theme ships: portfolio projects, stories, ads and a generic "custom post
 * type builder" driven by the `nova_custom_types` option so the site owner can
 * add post types (with their own taxonomies, icon and slug) without a plugin.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Post_Types {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_custom' ), 6 );
		add_filter( 'post_updated_messages', array( __CLASS__, 'messages' ) );
	}

	public static function register() {
		// Portfolio.
		if ( Nova_Modes::has( 'portfolio' ) ) {
			register_post_type(
				'nova_portfolio',
				array(
					'labels'       => self::labels( __( 'نمونه‌کار', 'nova' ), __( 'نمونه‌کارها', 'nova' ) ),
					'public'       => true,
					'has_archive'  => (bool) Nova_Modes::get_option( 'portfolio_archive', true ),
					'rewrite'      => array( 'slug' => Nova_Modes::get_option( 'portfolio_slug', 'portfolio' ), 'with_front' => false ),
					'menu_icon'    => 'dashicons-portfolio',
					'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'elementor' ),
					'show_in_rest' => true,
					'taxonomies'   => array( 'nova_portfolio_cat', 'nova_portfolio_tag' ),
				)
			);
			register_taxonomy(
				'nova_portfolio_cat',
				'nova_portfolio',
				array(
					'labels'            => self::tax_labels( __( 'دسته نمونه‌کار', 'nova' ), __( 'دسته‌های نمونه‌کار', 'nova' ) ),
					'hierarchical'      => true,
					'public'            => true,
					'show_admin_column' => true,
					'show_in_rest'      => true,
					'rewrite'           => array( 'slug' => 'portfolio-category' ),
				)
			);
			register_taxonomy(
				'nova_portfolio_tag',
				'nova_portfolio',
				array(
					'labels'            => self::tax_labels( __( 'برچسب نمونه‌کار', 'nova' ), __( 'برچسب‌های نمونه‌کار', 'nova' ) ),
					'hierarchical'      => false,
					'public'            => true,
					'show_admin_column' => true,
					'show_in_rest'      => true,
				)
			);
		}

		// Stories.
		if ( Nova_Modes::has( 'story' ) ) {
			register_post_type(
				'nova_story',
				array(
					'labels'       => self::labels( __( 'استوری', 'nova' ), __( 'استوری‌ها', 'nova' ) ),
					'public'       => true,
					'has_archive'  => false,
					'publicly_queryable' => true,
					'rewrite'      => array( 'slug' => 'story' ),
					'menu_icon'    => 'dashicons-format-image',
					'supports'     => array( 'title', 'thumbnail', 'editor' ),
					'show_in_rest' => true,
				)
			);
		}

		// Ads.
		if ( Nova_Modes::has( 'ads' ) ) {
			register_post_type(
				'nova_ad',
				array(
					'labels'       => self::labels( __( 'تبلیغ', 'nova' ), __( 'تبلیغات', 'nova' ) ),
					'public'       => false,
					'show_ui'      => true,
					'menu_icon'    => 'dashicons-megaphone',
					'supports'     => array( 'title', 'editor' ),
					'show_in_rest' => true,
				)
			);
			register_taxonomy(
				'nova_ad_zone',
				'nova_ad',
				array(
					'labels'            => self::tax_labels( __( 'زون تبلیغ', 'nova' ), __( 'زون‌های تبلیغ', 'nova' ) ),
					'hierarchical'      => false,
					'public'            => false,
					'show_ui'           => true,
					'show_admin_column' => true,
					'show_in_rest'      => true,
				)
			);
		}
	}

	/** Post types defined by the site owner through the panel. */
	public static function register_custom() {
		$types = get_option( 'nova_custom_types', array() );
		if ( ! is_array( $types ) ) {
			return;
		}
		foreach ( $types as $type ) {
			$key = isset( $type['key'] ) ? sanitize_key( $type['key'] ) : '';
			if ( ! $key || post_type_exists( $key ) ) {
				continue;
			}
			$plural = $type['plural'] ?? $key;
			$single = $type['single'] ?? $key;
			register_post_type(
				$key,
				array(
					'labels'        => self::labels( $single, $plural ),
					'public'        => ! empty( $type['public'] ),
					'has_archive'   => ! empty( $type['archive'] ),
					'rewrite'       => array( 'slug' => sanitize_title( $type['slug'] ?? $key ) ),
					'menu_icon'     => sanitize_text_field( $type['icon'] ?? 'dashicons-admin-post' ),
					'supports'      => array_filter( array_map( 'sanitize_key', (array) ( $type['supports'] ?? array( 'title', 'editor', 'thumbnail' ) ) ) ),
					'show_in_rest'  => true,
					'taxonomies'    => array_filter( array_map( 'sanitize_key', (array) ( $type['taxonomies'] ?? array() ) ) ),
					'capability_type' => 'post',
					'map_meta_cap'  => true,
				)
			);

			foreach ( (array) ( $type['taxonomies'] ?? array() ) as $tax ) {
				$tax = sanitize_key( $tax );
				if ( ! $tax || taxonomy_exists( $tax ) ) {
					continue;
				}
				register_taxonomy(
					$tax,
					$key,
					array(
						'labels'            => self::tax_labels( $tax, $tax ),
						'hierarchical'      => true,
						'public'            => true,
						'show_admin_column' => true,
						'show_in_rest'      => true,
					)
				);
			}
		}
	}

	private static function labels( $single, $plural ) {
		return array(
			'name'          => $plural,
			'singular_name' => $single,
			'add_new'       => __( 'افزودن', 'nova' ),
			'add_new_item'  => sprintf( __( 'افزودن %s', 'nova' ), $single ),
			'edit_item'     => sprintf( __( 'ویرایش %s', 'nova' ), $single ),
			'view_item'     => sprintf( __( 'مشاهده %s', 'nova' ), $single ),
			'search_items'  => sprintf( __( 'جستجوی %s', 'nova' ), $plural ),
			'not_found'     => __( 'موردی یافت نشد.', 'nova' ),
			'menu_name'     => $plural,
		);
	}

	private static function tax_labels( $single, $plural ) {
		return array(
			'name'          => $plural,
			'singular_name' => $single,
			'add_new_item'  => sprintf( __( 'افزودن %s', 'nova' ), $single ),
			'edit_item'     => sprintf( __( 'ویرایش %s', 'nova' ), $single ),
			'search_items'  => sprintf( __( 'جستجوی %s', 'nova' ), $plural ),
			'menu_name'     => $plural,
		);
	}

	public static function messages( $messages ) {
		return $messages;
	}
}

Nova_Post_Types::init();
