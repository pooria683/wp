<?php
/**
 * Logo panel.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="nv-logo">
	<?php if ( has_custom_logo() ) : ?>
		<?php the_custom_logo(); ?>
	<?php else : ?>
		<a class="nv-logo__text" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<span class="nv-logo__mark" aria-hidden="true"><?php echo esc_html( mb_substr( get_bloginfo( 'name' ), 0, 1 ) ); ?></span>
			<span class="nv-logo__name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
		</a>
	<?php endif; ?>
</div>
