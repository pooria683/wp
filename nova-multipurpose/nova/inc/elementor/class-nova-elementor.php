<?php
/**
 * Elementor integration.
 *
 * - registers theme locations (header, footer, single, archive) so the site can
 *   be built with Elementor's Theme Builder while keeping Nova's markup;
 * - adds a "Nova" widget category with the theme's own dynamic widgets
 *   (player, slider, stories, portfolio, posts grid). Registering the widget
 *   classes also triggers the theme's conditional asset loader, so nothing extra
 *   is loaded on pages that do not use them;
 * - keeps the editor light: theme front-end assets are only enqueued on demand.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Elementor {

	public static function init() {
		add_action( 'elementor/theme/register_locations', array( __CLASS__, 'locations' ) );
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'widgets' ) );
	}

	public static function locations( $manager ) {
		$manager->register_all_core_location();
	}

	public static function category( $elements_manager ) {
		$elements_manager->add_category(
			'nova',
			array(
				'title' => __( 'قالب Nova', 'nova' ),
				'icon'  => 'eicon-nerd',
			)
		);
	}

	/** Register the theme widgets if the Elementor base class is available. */
	public static function widgets( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		require_once NOVA_DIR . '/inc/elementor/nova-widgets.php';

		$widgets_manager->register( new Nova_El_Player() );
		$widgets_manager->register( new Nova_El_Slider() );
		$widgets_manager->register( new Nova_El_Stories() );
		$widgets_manager->register( new Nova_El_Portfolio() );
		$widgets_manager->register( new Nova_El_Posts() );
	}
}
