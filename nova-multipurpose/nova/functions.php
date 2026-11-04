<?php
/**
 * Nova Multipurpose — bootstrap.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

define( 'NOVA_VERSION', '1.0.0' );
define( 'NOVA_DIR', get_template_directory() );
define( 'NOVA_URI', get_template_directory_uri() );

/**
 * Autoload core classes from /inc.
 *
 * @param string $class Class name.
 * @return void
 */
function nova_autoload( $class ) {
	if ( 0 !== strpos( $class, 'Nova_' ) ) {
		return;
	}
	$map = array(
		'Nova_Modes'     => 'class-nova-modes.php',
		'Nova_Assets'    => 'class-nova-assets.php',
		'Nova_Setup'     => 'class-nova-setup.php',
		'Nova_Admin'     => 'class-nova-admin.php',
		'Nova_Header'    => 'class-nova-header.php',
		'Nova_Footer'    => 'class-nova-footer.php',
		'Nova_Post_Types'=> 'class-nova-post-types.php',
		'Nova_Meta'      => 'class-nova-meta.php',
		'Nova_Shortcodes'=> 'class-nova-shortcodes.php',
		'Nova_Ads'       => 'class-nova-ads.php',
		'Nova_Story'     => 'class-nova-story.php',
		'Nova_Portfolio' => 'class-nova-portfolio.php',
		'Nova_PWA'       => 'class-nova-pwa.php',
		'Nova_Security'  => 'class-nova-security.php',
		'Nova_Ajax'      => 'class-nova-ajax.php',
		'Nova_Menus'     => 'class-nova-menus.php',
	);
	if ( isset( $map[ $class ] ) ) {
		require_once NOVA_DIR . '/inc/' . $map[ $class ];
	}
}
spl_autoload_register( 'nova_autoload' );

/** Template tags / global helpers. */
require_once NOVA_DIR . '/inc/nova-helpers.php';

/** Load modules that only exist in some modes. */
require_once NOVA_DIR . '/inc/class-nova-modes.php';

Nova_Modes::boot();

// Always-on core.
Nova_Setup::init();
Nova_Assets::init();
Nova_Security::init();
Nova_Menus::init();
Nova_Shortcodes::init();
Nova_Ajax::init();

if ( is_admin() ) {
	Nova_Admin::init();
	Nova_Meta::init();
}

// Mode dependent modules.
if ( Nova_Modes::has( 'portfolio' ) && Nova_Modes::feature( 'mod_portfolio', true ) ) {
	Nova_Portfolio::init();
}

if ( Nova_Modes::has( 'ads' ) && Nova_Modes::feature( 'mod_ads', true ) ) {
	Nova_Ads::init();
}

if ( Nova_Modes::has( 'story' ) && Nova_Modes::feature( 'mod_story', true ) ) {
	Nova_Story::init();
}

require_once NOVA_DIR . '/inc/class-nova-post-types.php';

if ( Nova_Modes::has( 'shop' ) && class_exists( 'WooCommerce' ) ) {
	require_once NOVA_DIR . '/inc/woocommerce/class-nova-woo.php';
	Nova_Woo::init();
}

if ( Nova_Modes::has( 'elementor' ) && did_action( 'elementor/loaded' ) ) {
	require_once NOVA_DIR . '/inc/elementor/class-nova-elementor.php';
	Nova_Elementor::init();
}

require_once NOVA_DIR . '/inc/class-nova-header.php';
require_once NOVA_DIR . '/inc/class-nova-footer.php';
Nova_Header::init();
Nova_Footer::init();

// PWA is opt-in from the admin (panel → PWA / Mobile App).
if ( Nova_Modes::feature( 'pwa_enabled' ) ) {
	Nova_PWA::init();
}
