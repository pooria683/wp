<?php
/**
 * Shared archive loop.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;
?>
<header class="nv-archive__head">
	<?php echo do_shortcode( '[nova_breadcrumb]' ); ?>
	<h1 class="nv-archive__title">
		<?php
		if ( is_search() ) {
			printf( esc_html__( 'نتایج جستجو برای «%s»', 'nova' ), esc_html( get_search_query() ) );
		} elseif ( is_home() && ! is_front_page() ) {
			single_post_title();
		} elseif ( function_exists( 'is_shop' ) && is_shop() && Nova_Modes::has( 'shop' ) ) {
			echo esc_html( get_the_title( wc_get_page_id( 'shop' ) ) );
		} else {
			echo wp_kses_post( get_the_archive_title() );
		}
		?>
	</h1>
	<?php the_archive_description( '<div class="nv-archive__desc nv-muted">', '</div>' ); ?>
</header>

<?php if ( have_posts() ) : ?>
	<div class="nv-grid nv-grid--posts">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/parts/card' );
		endwhile;
		?>
	</div>
	<?php nova_pagination(); ?>
<?php else : ?>
	<div class="nv-empty">
		<h2><?php esc_html_e( 'چیزی پیدا نشد', 'nova' ); ?></h2>
		<p class="nv-muted"><?php esc_html_e( 'عبارت دیگری را جستجو کنید.', 'nova' ); ?></p>
		<?php get_search_form(); ?>
	</div>
<?php endif; ?>
