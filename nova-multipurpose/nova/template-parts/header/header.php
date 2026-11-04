<?php
/**
 * Header builder output.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

$layout = Nova_Header::layout();
$social = get_option( 'nova_social', array() );
?>
<header class="nv-header" role="banner">
	<?php if ( ! empty( $layout['topbar'] ) ) : ?>
		<div class="nv-topbar">
			<div class="nv-container nv-topbar__in">
				<div class="nv-topbar__left">
					<?php foreach ( $layout['topbar'] as $panel ) : ?>
						<?php Nova_Header::panel( $panel, 'topbar' ); ?>
					<?php endforeach; ?>
				</div>
				<div class="nv-topbar__right">
					<?php
					if ( has_nav_menu( 'top' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'top',
								'container'      => false,
								'menu_class'     => 'nv-topmenu',
								'depth'          => 1,
								'fallback_cb'    => false,
							)
						);
					}
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="nv-header__main">
		<div class="nv-container nv-header__grid">
			<div class="nv-header__left">
				<?php foreach ( (array) $layout['left'] as $panel ) : ?>
					<?php Nova_Header::panel( $panel, 'left' ); ?>
				<?php endforeach; ?>
			</div>

			<div class="nv-header__center">
				<?php foreach ( (array) $layout['center'] as $panel ) : ?>
					<?php Nova_Header::panel( $panel, 'center' ); ?>
				<?php endforeach; ?>
			</div>

			<div class="nv-header__right">
				<?php foreach ( (array) $layout['right'] as $panel ) : ?>
					<?php Nova_Header::panel( $panel, 'right' ); ?>
				<?php endforeach; ?>
				<button class="nv-burger" type="button" aria-expanded="false" aria-controls="nv-mobilenav" aria-label="<?php esc_attr_e( 'منو', 'nova' ); ?>">
					<span></span><span></span><span></span>
				</button>
			</div>
		</div>
	</div>

	<div id="nv-mobilenav" class="nv-mobilenav" data-mode="<?php echo esc_attr( Nova_Modes::get_option( 'menumode', 'drawer' ) ); ?>" hidden>
		<div class="nv-mobilenav__inner">
			<button class="nv-mobilenav__close" type="button" aria-label="<?php esc_attr_e( 'بستن', 'nova' ); ?>">&times;</button>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => has_nav_menu( 'mobile' ) ? 'mobile' : 'primary',
					'container'      => false,
					'menu_class'     => 'nv-mobilenav__menu',
					'fallback_cb'    => false,
				)
			);
			?>
			<?php nova_social_links( 'nv-mobilenav__social' ); ?>
		</div>
	</div>

	<div class="nv-searchbox" hidden>
		<div class="nv-container">
			<form role="search" method="get" class="nv-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="nv-s"><?php esc_html_e( 'جستجو', 'nova' ); ?></label>
				<input id="nv-s" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'جستجو…', 'nova' ); ?>" autocomplete="off" data-nv-live-search>
				<?php if ( Nova_Modes::has( 'shop' ) ) : ?>
					<input type="hidden" name="post_type" value="product">
				<?php endif; ?>
				<button type="submit" class="nv-btn nv-btn--primary"><?php echo Nova_Menus::icon_svg( 'search' ); // phpcs:ignore ?><span><?php esc_html_e( 'بگرد', 'nova' ); ?></span></button>
			</form>
			<div class="nv-live-search" aria-live="polite"></div>
		</div>
	</div>
</header>
