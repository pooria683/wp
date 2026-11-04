<?php
/**
 * Template Name: بوم تمام‌عرض (بدون هدر/فوتر — مناسب المنتور)
 * Template Post Type: page
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'nv-canvas' ); ?>>
<?php wp_body_open(); ?>
<main id="nv-main" class="nv-canvas__main">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>
<?php wp_footer(); ?>
</body>
</html>
