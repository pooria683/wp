<?php
/**
 * Footer builder.
 *
 * Option shape: [
 *   'style'   => 'dark'|'light'|'gradient',
 *   'columns' => [ ['width'=>3,'type'=>'widgets','area'=>'footer-1','title'=>'…'], ... ],
 *   'bottom'  => 'text',
 *   'payment' => 1,
 * ]
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Footer {

	public static function init() {
		add_filter( 'body_class', array( __CLASS__, 'classes' ) );
	}

	public static function layout() {
		$default = array(
			'style'   => 'dark',
			'columns' => array(
				array( 'width' => 4, 'type' => 'logo', 'title' => '', 'value' => '' ),
				array( 'width' => 2, 'type' => 'menu', 'title' => __( 'دسترسی سریع', 'nova' ), 'value' => 'footer' ),
				array( 'width' => 3, 'type' => 'widgets', 'area' => 'footer-1', 'title' => __( 'درباره ما', 'nova' ), 'value' => '' ),
				array( 'width' => 3, 'type' => 'html', 'title' => __( 'ارتباط با ما', 'nova' ), 'value' => '' ),
			),
			'bottom'  => '',
			'payment' => 1,
		);
		$saved = Nova_Modes::get_option( 'footer_layout', array() );
		if ( ! is_array( $saved ) || empty( $saved['columns'] ) ) {
			$saved = $default;
		}
		return wp_parse_args( $saved, $default );
	}

	public static function classes( $classes ) {
		$classes[] = 'nv-footer-' . sanitize_html_class( self::layout()['style'] );
		return $classes;
	}

	/** Render a single column. */
	public static function column( $col ) {
		$width = max( 1, min( 12, (int) ( $col['width'] ?? 3 ) ) );
		$type  = $col['type'] ?? 'widgets';
		$title = $col['title'] ?? '';

		echo '<div class="nv-fcol nv-fcol--' . esc_attr( $type ) . '" style="--nv-fcol:' . $width . '">';

		if ( $title && 'logo' !== $type ) {
			echo '<h4 class="nv-fcol__title">' . esc_html( $title ) . '</h4>';
		}

		switch ( $type ) {
			case 'logo':
				if ( has_custom_logo() ) {
					the_custom_logo();
				} else {
					printf( '<a class="nv-fcol__brand" href="%s">%s</a>', esc_url( home_url( '/' ) ), esc_html( get_bloginfo( 'name' ) ) );
				}
				if ( get_bloginfo( 'description' ) ) {
					echo '<p class="nv-muted">' . esc_html( get_bloginfo( 'description' ) ) . '</p>';
				}
				break;

			case 'widgets':
				$area = sanitize_key( $col['area'] ?? 'footer-1' );
				if ( is_active_sidebar( $area ) ) {
					dynamic_sidebar( $area );
				}
				break;

			case 'menu':
				$loc = sanitize_key( $col['value'] ?? 'footer' );
				if ( has_nav_menu( $loc ) ) {
					wp_nav_menu(
						array(
							'theme_location' => $loc,
							'container'      => false,
							'menu_class'     => 'nv-flist',
							'depth'          => 1,
							'fallback_cb'    => false,
						)
					);
				}
				break;

			case 'social':
				nova_social_links( 'nv-fcol__social' );
				break;

			case 'html':
				echo '<div class="nv-fcol__html">' . wp_kses_post( wpautop( $col['value'] ?? '' ) ) . '</div>';
				break;

			case 'newsletter':
				echo do_shortcode( (string) ( $col['value'] ?? '' ) );
				break;
		}

		echo '</div>';
	}

	public static function render() {
		if ( function_exists( 'elementor_theme_do_location' ) ) {
			ob_start();
			$rendered = elementor_theme_do_location( 'footer' );
			$html     = trim( (string) ob_get_clean() );
			if ( $rendered && '' !== $html ) {
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
				return;
			}
		}
		get_template_part( 'template-parts/footer/footer' );
	}
}
