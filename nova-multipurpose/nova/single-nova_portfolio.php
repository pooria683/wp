<?php
/**
 * Single project.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$meta    = Nova_Portfolio::meta_rows( get_the_ID() );
	$link    = get_post_meta( get_the_ID(), 'nova_link', true );
	$gallery = get_post_meta( get_the_ID(), 'nova_gallery', true );
	$ids     = $gallery ? array_filter( array_map( 'absint', explode( ',', $gallery ) ) ) : array();
	?>
	<article class="nv-project-single">
		<div class="nv-container">
			<?php echo do_shortcode( '[nova_breadcrumb]' ); ?>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="nv-project-single__hero">
					<?php the_post_thumbnail( 'nova-wide', array( 'fetchpriority' => 'high' ) ); ?>
				</figure>
			<?php endif; ?>

			<div class="nv-project-single__grid">
				<div class="nv-prose">
					<h1 class="nv-single__title"><?php the_title(); ?></h1>
					<?php the_content(); ?>

					<?php if ( $ids ) : ?>
						<div class="nv-project-gallery">
							<?php foreach ( $ids as $id ) : ?>
								<?php echo wp_get_attachment_image( $id, 'nova-card', false, array( 'loading' => 'lazy' ) ); ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $meta ) : ?>
					<aside class="nv-project-meta">
						<dl>
							<?php foreach ( $meta as $label => $value ) : ?>
								<div>
									<dt><?php echo esc_html( $label ); ?></dt>
									<dd>
										<?php
										if ( $link && $value === $link ) {
											printf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $value ), esc_html__( 'مشاهده پروژه', 'nova' ) );
										} else {
											echo esc_html( $value );
										}
										?>
									</dd>
								</div>
							<?php endforeach; ?>
						</dl>
					</aside>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();
