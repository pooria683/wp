<?php
/**
 * Footer builder output.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

$layout = Nova_Footer::layout();
?>
<footer class="nv-footer" role="contentinfo">
	<?php if ( Nova_Modes::has( 'ads' ) ) { Nova_Ads::zone( 'footer' ); } ?>

	<div class="nv-container">
		<div class="nv-footer__grid">
			<?php foreach ( (array) $layout['columns'] as $col ) { Nova_Footer::column( $col ); } ?>
		</div>

		<?php nova_payment_badges(); ?>

		<div class="nv-footer__bottom">
			<p class="nv-footer__copy">
				<?php
				$bottom = trim( (string) $layout['bottom'] );
				echo esc_html( $bottom ? $bottom : sprintf( '© %s — %s', gmdate( 'Y' ), get_bloginfo( 'name' ) ) );
				?>
			</p>
			<?php
			if ( has_nav_menu( 'footer' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'nv-footer__links',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
			}
			?>
		</div>
	</div>

	<?php if ( Nova_Modes::feature( 'back_to_top', true ) ) : ?>
		<button class="nv-totop" type="button" aria-label="<?php esc_attr_e( 'بازگشت به بالا', 'nova' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
		</button>
	<?php endif; ?>
</footer>
