<?php
/**
 * Template Name: لندینگ (تک‌صفحه‌ای سئو محور)
 * Template Post Type: page
 *
 * Keeps a slim header (logo + CTA + menu) and footer, but removes sidebars
 * and forces a full-width container: the classic campaign layout.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', static function ( $c ) {
	$c[] = 'nv-wide';
	return $c;
} );

get_header();
?>
<div class="nv-landing">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<div class="nv-prose nv-landing__content"><?php the_content(); ?></div>
		<?php
	endwhile;
	?>
</div>
<?php
get_footer();
