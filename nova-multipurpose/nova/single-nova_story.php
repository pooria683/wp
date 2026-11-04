<?php
/**
 * Single story: shows the story rail with this item highlighted.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nv-container nv-page">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<h1 class="nv-page__title"><?php the_title(); ?></h1>
		<?php echo Nova_Story::render( Nova_Story::items( (string) get_the_ID(), 1 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="nv-prose"><?php the_content(); ?></div>
		<?php
	endwhile;
	?>
</div>
<?php
get_footer();
