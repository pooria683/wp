<?php
/**
 * Portfolio grid with category filter (JS filter, no reload).
 *
 * @package Nova
 *
 * @var array $args count, filter, cols.
 */

defined( 'ABSPATH' ) || exit;

$count  = (int) ( $args['count'] ?? 9 );
$filter = $args['filter'] ?? true;
$cols   = (int) ( $args['cols'] ?? 3 );

$q = Nova_Portfolio::query( array( 'posts_per_page' => $count ) );
if ( ! $q->have_posts() ) {
	return;
}
$terms = $filter ? Nova_Portfolio::terms() : array();
?>
<div class="nv-portfolio" data-nv-portfolio style="--nv-cols:<?php echo esc_attr( $cols ); ?>">
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
		while ( $q->have_posts() ) :
			$q->the_post();
			$p_terms = get_the_terms( get_the_ID(), 'nova_portfolio_cat' );
			$slugs   = ( $p_terms && ! is_wp_error( $p_terms ) ) ? wp_list_pluck( $p_terms, 'slug' ) : array();
			?>
			<article class="nv-project" data-cats="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>">
				<a class="nv-project__link" href="<?php the_permalink(); ?>">
					<span class="nv-project__media">
						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail( 'nova-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) );
						}
						?>
					</span>
					<span class="nv-project__body">
						<span class="nv-project__cat"><?php echo esc_html( $p_terms ? $p_terms[0]->name : __( 'نمونه‌کار', 'nova' ) ); ?></span>
						<h3 class="nv-project__title"><?php the_title(); ?></h3>
						<span class="nv-project__more" aria-hidden="true">↗</span>
					</span>
				</a>
			</article>
		<?php endwhile; ?>
	</div>
</div>
<?php
wp_reset_postdata();
