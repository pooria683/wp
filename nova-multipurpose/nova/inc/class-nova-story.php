<?php
/**
 * Instagram-style stories (post type nova_story).
 *
 * Each story item = an image or video + optional link + optional caption.
 * The front-end viewer is vanilla JS (no library) with progress bars,
 * keyboard/touch navigation and lazy media loading.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Story {

	public static function init() {
		add_filter( 'body_class', array( __CLASS__, 'classes' ) );
	}

	public static function classes( $classes ) {
		if ( is_singular( 'nova_story' ) ) {
			$classes[] = 'nv-story-view';
		}
		return $classes;
	}

	/**
	 * Collect story items.
	 *
	 * @param string $ids   Comma separated ids (optional).
	 * @param int    $count How many recent stories to show.
	 * @return array[]
	 */
	public static function items( $ids = '', $count = 8 ) {
		$posts = array();

		if ( $ids ) {
			$posts = get_posts(
				array(
					'post_type'      => 'nova_story',
					'post__in'       => array_map( 'absint', explode( ',', $ids ) ),
					'orderby'        => 'post__in',
					'posts_per_page' => count( explode( ',', $ids ) ),
					'post_status'    => 'publish',
				)
			);
		} else {
			$posts = get_posts(
				array(
					'post_type'      => 'nova_story',
					'posts_per_page' => absint( $count ),
					'post_status'    => 'publish',
					'orderby'        => 'date',
				)
			);
		}

		$items = array();
		foreach ( $posts as $post ) {
			$video = get_post_meta( $post->ID, '_nova_story_video', true );
			$items[] = array(
				'id'      => $post->ID,
				'title'   => get_the_title( $post ),
				'caption' => wp_strip_all_tags( $post->post_excerpt ? $post->post_excerpt : wp_trim_words( $post->post_content, 20 ) ),
				'image'   => get_the_post_thumbnail_url( $post->ID, 'nova-square' ),
				'video'   => $video,
				'link'    => get_post_meta( $post->ID, '_nova_story_link', true ) ?: get_permalink( $post ),
				'url'     => get_permalink( $post ),
			);
		}
		return array_values( array_filter( $items, static function ( $i ) {
			return $i['image'] || $i['video'];
		} ) );
	}

	/** Render the viewer markup (markup only, JS handles the interaction). */
	public static function render( $items, $title = '' ) {
		if ( ! $items ) {
			return '';
		}
		ob_start();
		?>
		<div class="nv-story" data-nv-story>
			<?php if ( $title ) : ?>
				<h2 class="nv-story__heading"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>
			<ul class="nv-story__rail">
				<?php foreach ( $items as $item ) : ?>
					<li class="nv-story__item">
						<button type="button" class="nv-story__thumb" aria-label="<?php echo esc_attr( $item['title'] ); ?>">
							<?php if ( $item['image'] ) : ?>
								<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" loading="lazy" decoding="async" width="160" height="160">
							<?php endif; ?>
							<span class="nv-story__ring" aria-hidden="true"></span>
						</button>
						<span class="nv-story__label"><?php echo esc_html( wp_trim_words( $item['title'], 3 ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="nv-story__viewer" hidden>
				<div class="nv-story__bars" aria-hidden="true"></div>
				<button class="nv-story__close" type="button" aria-label="<?php esc_attr_e( 'بستن', 'nova' ); ?>">&times;</button>
				<button class="nv-story__nav" data-dir="-1" type="button" aria-label="<?php esc_attr_e( 'قبلی', 'nova' ); ?>">‹</button>
				<button class="nv-story__nav nv-story__nav--next" data-dir="1" type="button" aria-label="<?php esc_attr_e( 'بعدی', 'nova' ); ?>">›</button>
				<div class="nv-story__stage"></div>
				<p class="nv-story__caption"></p>
			</div>

			<script type="application/json" class="nv-story__data"><?php echo wp_json_encode( $items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
		</div>
		<?php
		return ob_get_clean();
	}
}
