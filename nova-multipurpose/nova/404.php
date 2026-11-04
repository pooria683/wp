<?php
/**
 * 404.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nv-container nv-404">
	<p class="nv-404__code">404</p>
	<h1 class="nv-404__title"><?php esc_html_e( 'این صفحه پیدا نشد', 'nova' ); ?></h1>
	<p class="nv-muted"><?php esc_html_e( 'ممکن است آدرس عوض شده باشد. جستجو کنید یا به خانه برگردید.', 'nova' ); ?></p>
	<?php get_search_form(); ?>
	<a class="nv-btn nv-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span><?php esc_html_e( 'بازگشت به خانه', 'nova' ); ?></span></a>

	<?php
	$recent = new WP_Query( array( 'posts_per_page' => 3, 'post_status' => 'publish' ) );
	if ( $recent->have_posts() ) :
		?>
		<div class="nv-grid nv-grid--posts nv-404__grid">
			<?php
			while ( $recent->have_posts() ) :
				$recent->the_post();
				get_template_part( 'template-parts/parts/card' );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
