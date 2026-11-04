<?php
/**
 * Portfolio module.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Portfolio {

	public static function init() {
		add_filter( 'body_class', array( __CLASS__, 'classes' ) );
		add_action( 'init', array( __CLASS__, 'disable_archive_redirect' ) );
	}

	public static function classes( $classes ) {
		if ( is_singular( 'nova_portfolio' ) || is_post_type_archive( 'nova_portfolio' ) || is_tax( 'nova_portfolio_cat' ) ) {
			$classes[] = 'nv-portfolio-view';
		}
		return $classes;
	}

	public static function disable_archive_redirect() {}

	/**
	 * Query for the portfolio grid.
	 *
	 * @param array $args Args.
	 * @return WP_Query
	 */
	public static function query( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'post_type'      => 'nova_portfolio',
				'posts_per_page' => 9,
				'post_status'    => 'publish',
			)
		);
		return new WP_Query( $args );
	}

	/** Terms for the filter bar. */
	public static function terms() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'nova_portfolio_cat',
				'hide_empty' => true,
			)
		);
		return is_wp_error( $terms ) ? array() : $terms;
	}

	/** Services / features printed on the single project. */
	public static function meta_rows( $post_id ) {
		$keys = array(
			'nova_client'  => __( 'کارفرما', 'nova' ),
			'nova_date'    => __( 'تاریخ', 'nova' ),
			'nova_services'=> __( 'خدمات', 'nova' ),
			'nova_link'    => __( 'لینک', 'nova' ),
		);
		$out = array();
		foreach ( $keys as $key => $label ) {
			$val = get_post_meta( $post_id, $key, true );
			if ( $val ) {
				$out[ $label ] = $val;
			}
		}
		return $out;
	}
}
