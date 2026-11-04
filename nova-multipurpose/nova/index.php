<?php
/**
 * Fallback template / blog archive.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( Nova_Modes::has( 'ads' ) ) {
	Nova_Ads::zone( 'before-content' );
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
