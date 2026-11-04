<?php
/**
 * Blog posts page — optional hero slider + archive loop.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! is_paged() && Nova_Modes::feature( 'mod_slider', true ) ) {
	echo '<section class="nv-home-slider">' . do_shortcode( '[nova_slider count="5" height="520"]' ) . '</section>';
}
?>
<div class="nv-container nv-archive">
	<div class="nv-content">
		<?php get_template_part( 'template-parts/parts/loop-archive' ); ?>
	</div>
	<?php get_sidebar(); ?>
</div>
<?php
get_footer();
