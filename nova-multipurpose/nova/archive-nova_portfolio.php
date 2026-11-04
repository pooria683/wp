<?php
/**
 * Portfolio archive.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nv-container nv-archive nv-archive--portfolio">
	<div class="nv-content">
		<header class="nv-archive__head">
			<?php echo do_shortcode( '[nova_breadcrumb]' ); ?>
			<h1 class="nv-archive__title"><?php post_type_archive_title(); ?></h1>
			<?php the_archive_description( '<div class="nv-archive__desc nv-muted">', '</div>' ); ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="nv-portfolio" data-nv-portfolio style="--nv-cols:3">
				<?php $terms = Nova_Portfolio::terms(); ?>
				<?php if ( $terms ) : ?>
					<div class="nv-portfolio__filters" role="tablist">
						<button class="nv-filter is-active" type="button" data-filter="*"><?php esc_html_e( 'همه', 'nova' ); ?></button>
						<?php foreach ( $terms as $term ) : ?>
							<button class="nv-filter" type="button" data-filter="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="nv-portfolio__grid">
					<?php
					while ( have_posts() ) :
						the_post();
						$p_terms = get_the_terms( get_the_ID(), 'nova_portfolio_cat' );
						$slugs   = ( $p_terms && ! is_wp_error( $p_terms ) ) ? wp_list_pluck( $p_terms, 'slug' ) : array();
						?>
						<article class="nv-project" data-cats="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>">
							<a class="nv-project__link" href="<?php the_permalink(); ?>">
								<span class="nv-project__media"><?php has_post_thumbnail() && the_post_thumbnail( 'nova-card', array( 'loading' => 'lazy' ) ); ?></span>
								<span class="nv-project__body">
									<span class="nv-project__cat"><?php echo esc_html( $p_terms ? $p_terms[0]->name : __( 'نمونه‌کار', 'nova' ) ); ?></span>
									<h2 class="nv-project__title"><?php the_title(); ?></h2>
									<span class="nv-project__more" aria-hidden="true">↗</span>
								</span>
							</a>
						</article>
					<?php endwhile; ?>
				</div>
			</div>
			<?php nova_pagination(); ?>
		<?php else : ?>
			<p class="nv-muted"><?php esc_html_e( 'هنوز نمونه‌کاری ثبت نشده است.', 'nova' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
