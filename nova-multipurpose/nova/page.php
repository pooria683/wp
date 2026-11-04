<?php
/**
 * Default page template.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	// Elementor / builder canvas pages render themselves.
	$is_builder = did_action( 'elementor/loaded' ) && \Elementor\Plugin::$instance->db->is_built_with_elementor( get_the_ID() );
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( $is_builder ? 'nv-page-builder' : 'nv-container nv-page' ); ?>>
		<?php if ( ! $is_builder ) : ?>
			<header class="nv-page__head">
				<?php echo do_shortcode( '[nova_breadcrumb]' ); ?>
				<h1 class="nv-page__title"><?php the_title(); ?></h1>
			</header>
			<div class="nv-prose">
				<?php
				the_content();
				wp_link_pages();
				?>
			</div>
			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		<?php else : ?>
			<?php the_content(); ?>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
