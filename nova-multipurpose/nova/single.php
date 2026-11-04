<?php
/**
 * Single post.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$layout = Nova_Modes::get_option( 'single_layout' );
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'nv-container nv-single' ); ?>>
		<?php echo do_shortcode( '[nova_breadcrumb]' ); ?>

		<header class="nv-single__head">
			<?php
			$cats = get_the_category();
			if ( $cats ) :
				?>
				<a class="nv-chip nv-chip--cat" href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a>
			<?php endif; ?>
			<h1 class="nv-single__title"><?php the_title(); ?></h1>
			<div class="nv-single__meta nv-muted">
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<span class="nv-dot" aria-hidden="true">·</span>
				<span><?php the_author(); ?></span>
				<span class="nv-dot" aria-hidden="true">·</span>
				<span><?php echo esc_html( sprintf( __( '%s دقیقه مطالعه', 'nova' ), max( 1, (int) ( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) ) ) ); ?></span>
			</div>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="nv-single__media">
				<?php the_post_thumbnail( 'nova-wide', array( 'fetchpriority' => 'high' ) ); ?>
				<?php if ( get_the_post_thumbnail_caption() ) : ?>
					<figcaption><?php the_post_thumbnail_caption(); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<div class="nv-single__layout nv-single__layout--<?php echo esc_attr( $layout ); ?>">
			<div class="nv-single__content nv-prose">
				<?php
				if ( Nova_Modes::has( 'ads' ) ) {
					Nova_Ads::zone( 'before-content' );
				}
				the_content();
				wp_link_pages( array( 'before' => '<nav class="nv-pagelinks">', 'after' => '</nav>' ) );
				if ( Nova_Modes::has( 'ads' ) ) {
					Nova_Ads::zone( 'after-content' );
				}
				?>
				<footer class="nv-single__foot">
					<?php the_tags( '<ul class="nv-tags"><li>', '</li><li>', '</li></ul>' ); ?>
					<div class="nv-share">
						<span><?php esc_html_e( 'اشتراک‌گذاری:', 'nova' ); ?></span>
						<?php
						$share_url = rawurlencode( get_permalink() );
						$share_txt = rawurlencode( get_the_title() );
						$links     = array(
							'telegram' => 'https://t.me/share/url?url=' . $share_url . '&text=' . $share_txt,
							'whatsapp' => 'https://wa.me/?text=' . $share_txt . '%20' . $share_url,
							'x'        => 'https://twitter.com/intent/tweet?url=' . $share_url . '&text=' . $share_txt,
							'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $share_url,
						);
						foreach ( $links as $net => $url ) {
							printf( '<a class="nv-share__%1$s" href="%2$s" target="_blank" rel="noopener nofollow" aria-label="%1$s">%3$s</a>', esc_attr( $net ), esc_url( $url ), nova_social_icon( $net ) );
						}
						?>
					</div>
				</footer>

				<?php
				the_post_navigation(
					array(
						'prev_text' => '<span class="nv-muted">' . esc_html__( 'قبلی', 'nova' ) . '</span><strong>%title</strong>',
						'next_text' => '<span class="nv-muted">' . esc_html__( 'بعدی', 'nova' ) . '</span><strong>%title</strong>',
						'class'     => 'nv-postnav',
					)
				);

				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</div>

			<?php if ( 'sidebar' === $layout ) : ?>
				<aside class="nv-single__aside">
					<?php get_sidebar(); ?>
				</aside>
			<?php endif; ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
