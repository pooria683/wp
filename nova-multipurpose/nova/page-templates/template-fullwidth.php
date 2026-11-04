<?php
/**
 * Template Name: تمام‌عرض (با هدر و فوتر)
 * Template Post Type: page
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
<div class="nv-container nv-page-full">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</div>
<?php
get_footer();
