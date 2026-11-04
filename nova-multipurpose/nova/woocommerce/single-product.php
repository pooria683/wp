<?php
/**
 * Single product layout with a configurable gallery position.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$pos  = Nova_Modes::get_option( 'product_gallery_pos', 'left' );
$show = (array) Nova_Modes::get_option( 'product_show' );

while ( have_posts() ) :
	the_post();
	wc_get_template_part( 'content', 'single-product' );
endwhile;

get_footer( 'shop' );
