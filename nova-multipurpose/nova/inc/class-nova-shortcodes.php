<?php
/**
 * Shortcodes + blocks (registered as dynamic blocks so nothing extra is stored
 * in the content and the asset loader can see them via has_block).
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Shortcodes {

	public static function init() {
		add_shortcode( 'nova_player', array( __CLASS__, 'player' ) );
		add_shortcode( 'nova_slider', array( __CLASS__, 'slider' ) );
		add_shortcode( 'nova_story', array( __CLASS__, 'story' ) );
		add_shortcode( 'nova_portfolio', array( __CLASS__, 'portfolio' ) );
		add_shortcode( 'nova_ads', array( __CLASS__, 'ads' ) );
		add_shortcode( 'nova_social', array( __CLASS__, 'social' ) );
		add_shortcode( 'nova_breadcrumb', array( __CLASS__, 'breadcrumb' ) );
		add_shortcode( 'nova_button', array( __CLASS__, 'button' ) );
		add_shortcode( 'nova_wishlist_count', array( __CLASS__, 'wishlist_count' ) );
		add_filter( 'widget_text', 'do_shortcode' );
		add_action( 'init', array( __CLASS__, 'blocks' ) );
	}

	/** Register lightweight dynamic blocks (server rendered = no build step). */
	public static function blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		$common = array(
			'api_version'     => 2,
			'render_callback' => null,
			'supports'        => array( 'html' => false ),
		);
		$blocks = array(
			'nova/player'    => array( 'nova_player', array( 'src' => 'url', 'type' => 'text', 'poster' => 'url', 'title' => 'text', 'ratio' => 'text' ) ),
			'nova/slider'    => array( 'nova_slider', array( 'ids' => 'text', 'height' => 'text', 'autoplay' => 'text' ) ),
			'nova/story'     => array( 'nova_story', array( 'ids' => 'text' ) ),
			'nova/portfolio' => array( 'nova_portfolio', array( 'count' => 'text', 'filter' => 'text' ) ),
			'nova/ads'       => array( 'nova_ads', array( 'id' => 'text' ) ),
		);
		foreach ( $blocks as $name => $data ) {
			register_block_type(
				$name,
				array_merge(
					$common,
					array(
						'attributes'      => array_map(
							static function ( $type ) {
								return array( 'type' => $type );
							},
							$data[1]
						),
						'render_callback' => static function ( $attrs ) use ( $data ) {
							return call_user_func( array( __CLASS__, $data[0] ), $attrs );
						},
					)
				)
			);
		}
	}

	/* --------------------------------------------------------------------- */

	/**
	 * [nova_player src="…" type="auto" poster="…" title="…" ratio="16/9"]
	 * type: auto | local | youtube | aparat | vimeo | dailymotion | audio
	 */
	public static function player( $atts ) {
		Nova_Assets::ensure( 'player' );
		$atts = shortcode_atts(
			array(
				'src'      => '',
				'type'     => 'auto',
				'poster'   => '',
				'title'    => '',
				'ratio'    => '',
				'autoplay' => '0',
				'loop'     => '0',
				'muted'    => '0',
				'subtitle' => '',
				'id'       => '',
			),
			$atts,
			'nova_player'
		);
		if ( ! $atts['src'] ) {
			return '';
		}
		return self::render_player( $atts );
	}

	public static function render_player( $atts ) {
		$atts   = (array) $atts;
		$src    = trim( $atts['src'] ?? '' );
		$type   = self::detect_type( $src, $atts['type'] ?? 'auto' );
		$ratio  = $atts['ratio'] ? $atts['ratio'] : ( 'audio' === $type ? '' : '16/9' );
		$poster = $atts['poster'] ? $atts['poster'] : '';
		$poster = $poster ?: self::auto_poster( $src, $type );
		$uid    = 'nvp-' . wp_unique_id();
		$title  = $atts['title'] ?? '';
		$media  = self::embed_url( $src, $type );

		ob_start();
		?>
		<div class="nv-player nv-player--<?php echo esc_attr( $type ); ?>"
			data-nvp="<?php echo esc_attr( $uid ); ?>"
			data-type="<?php echo esc_attr( $type ); ?>"
			<?php if ( $ratio ) : ?>style="--nv-ratio:<?php echo esc_attr( $ratio ); ?>"<?php endif; ?>
			data-autoplay="<?php echo esc_attr( $atts['autoplay'] ?? 0 ); ?>"
			data-muted="<?php echo esc_attr( $atts['muted'] ?? 0 ); ?>"
			data-loop="<?php echo esc_attr( $atts['loop'] ?? 0 ); ?>">
			<?php if ( $title ) : ?>
				<span class="nv-player__title"><?php echo esc_html( $title ); ?></span>
			<?php endif; ?>

			<div class="nv-player__stage">
				<?php if ( 'audio' === $type ) : ?>
					<div class="nv-player__cover" <?php echo $poster ? 'style="background-image:url(' . esc_url( $poster ) . ')"' : ''; ?>>
						<button class="nv-player__big" type="button" aria-label="<?php esc_attr_e( 'پخش', 'nova' ); ?>"><?php echo Nova_Menus::icon_svg( 'chevron' ); ?></button>
					</div>
					<audio preload="metadata" <?php echo $poster ? 'data-poster="' . esc_url( $poster ) . '"' : ''; ?>>
						<source src="<?php echo esc_url( $media ); ?>">
					</audio>
				<?php elseif ( 'local' === $type ) : ?>
					<video playsinline preload="metadata" <?php echo $poster ? 'poster="' . esc_url( $poster ) . '"' : ''; ?>>
						<source src="<?php echo esc_url( $media ); ?>">
						<?php if ( ! empty( $atts['subtitle'] ) ) : ?>
							<track kind="subtitles" src="<?php echo esc_url( $atts['subtitle'] ); ?>" srclang="fa" label="فارسی" default>
						<?php endif; ?>
					</video>
				<?php else : ?>
					<div class="nv-player__facade" data-embed="<?php echo esc_url( $media ); ?>">
						<?php if ( $poster ) : ?>
							<img src="<?php echo esc_url( $poster ); ?>" alt="" decoding="async" loading="lazy" width="1280" height="720">
						<?php endif; ?>
						<button class="nv-player__big" type="button" aria-label="<?php esc_attr_e( 'پخش', 'nova' ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z" fill="currentColor"/></svg>
						</button>
						<span class="nv-player__src"><?php echo esc_html( self::provider_label( $type ) ); ?></span>
					</div>
				<?php endif; ?>
			</div>

			<div class="nv-player__bar" <?php echo 'audio' === $type ? 'hidden' : ''; ?>>
				<button class="nv-player__btn" data-act="play" type="button" aria-label="<?php esc_attr_e( 'پخش/توقف', 'nova' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z" fill="currentColor"/></svg>
				</button>
				<input class="nv-player__seek" type="range" min="0" max="100" value="0" step="0.1" aria-label="<?php esc_attr_e( 'جای پخش', 'nova' ); ?>">
				<span class="nv-player__time" data-time>0:00</span>
				<button class="nv-player__btn" data-act="mute" type="button" aria-label="<?php esc_attr_e( 'صدا', 'nova' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9h4l5-4v14l-5-4H4z" fill="currentColor"/></svg>
				</button>
				<input class="nv-player__vol" type="range" min="0" max="1" step="0.05" value="1" aria-label="<?php esc_attr_e( 'بلندی صدا', 'nova' ); ?>">
				<button class="nv-player__btn" data-act="full" type="button" aria-label="<?php esc_attr_e( 'تمام‌صفحه', 'nova' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9V4h5M20 15v5h-5M15 4h5v5M9 20H4v-5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
				</button>
				<?php if ( Nova_Modes::feature( 'player_speed', true ) ) : ?>
				<select class="nv-player__speed" data-act="speed" aria-label="<?php esc_attr_e( 'سرعت', 'nova' ); ?>">
					<option value="0.75">0.75×</option>
					<option value="1" selected>1×</option>
					<option value="1.25">1.25×</option>
					<option value="1.5">1.5×</option>
					<option value="2">2×</option>
				</select>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Guess a provider from the URL. */
	public static function detect_type( $src, $type = 'auto' ) {
		if ( 'auto' !== $type ) {
			return $type;
		}
		$host = wp_parse_url( $src, PHP_URL_HOST );
		$host = $host ? strtolower( $host ) : '';
		if ( false !== strpos( $host, 'youtu' ) ) {
			return 'youtube';
		}
		if ( false !== strpos( $host, 'aparat' ) ) {
			return 'aparat';
		}
		if ( false !== strpos( $host, 'vimeo' ) ) {
			return 'vimeo';
		}
		if ( false !== strpos( $host, 'dailymotion' ) || false !== strpos( $host, 'dai.ly' ) ) {
			return 'dailymotion';
		}
		if ( preg_match( '/\.(mp3|ogg|wav|m4a|flac)$/i', $src ) ) {
			return 'audio';
		}
		if ( preg_match( '/\.(mp4|webm|ogv|m4v|mov)$/i', $src ) ) {
			return 'local';
		}
		return 'embed';
	}

	public static function provider_label( $type ) {
		$map = array(
			'youtube'     => 'YouTube',
			'aparat'      => 'آپارات',
			'vimeo'       => 'Vimeo',
			'dailymotion' => 'Dailymotion',
			'embed'       => __( 'ویدئو', 'nova' ),
		);
		return $map[ $type ] ?? '';
	}

	/** Convert a watch URL into an embeddable URL (privacy friendly). */
	public static function embed_url( $src, $type ) {
		switch ( $type ) {
			case 'youtube':
				$id = '';
				if ( preg_match( '/(?:v=|youtu\.be\/|embed\/|shorts\/)([A-Za-z0-9_-]{6,})/', $src, $m ) ) {
					$id = $m[1];
				}
				return $id ? 'https://www.youtube-nocookie.com/embed/' . $id . '?autoplay=1&rel=0' : $src;
			case 'aparat':
				if ( preg_match( '/aparat\.com\/v\/([A-Za-z0-9]+)/', $src, $m ) ) {
					return 'https://www.aparat.com/video/video/embed/videohash/' . $m[1] . '/vt/frame';
				}
				return $src;
			case 'vimeo':
				if ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $src, $m ) ) {
					return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
				}
				return $src;
			case 'dailymotion':
				if ( false !== strpos( $src, 'dai.ly' ) ) {
					$src = 'https://www.dailymotion.com/video/' . basename( $src );
				}
				return str_replace( '/video/', '/embed/video/', $src ) . '?autoplay=1';
		}
		return $src;
	}

	public static function auto_poster( $src, $type ) {
		if ( 'youtube' === $type && preg_match( '/(?:v=|youtu\.be\/|embed\/|shorts\/)([A-Za-z0-9_-]{6,})/', $src, $m ) ) {
			return 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg';
		}
		return '';
	}

	/* --------------------------------------------------------------------- */

	/** [nova_slider ids="1,2,3" height="520" autoplay="6000"] */
	public static function slider( $atts ) {
		Nova_Assets::ensure( 'slider' );
		$atts = shortcode_atts(
			array(
				'ids'      => '',
				'height'   => '520',
				'autoplay' => '6000',
				'type'     => 'post',
				'count'    => '5',
				'arrows'   => '1',
				'dots'     => '1',
			),
			$atts,
			'nova_slider'
		);
		$slides = array();

		if ( $atts['ids'] ) {
			foreach ( array_map( 'absint', explode( ',', $atts['ids'] ) ) as $id ) {
				$post = get_post( $id );
				if ( ! $post ) {
					continue;
				}
				$slides[] = array(
					'title' => get_the_title( $id ),
					'text'  => wp_trim_words( get_the_excerpt( $id ), 22 ),
					'url'   => get_permalink( $id ),
					'image' => get_the_post_thumbnail_url( $id, 'nova-wide' ),
					'btn'   => __( 'بیشتر بخوانید', 'nova' ),
				);
			}
		} else {
			$q = new WP_Query(
				array(
					'post_type'      => sanitize_key( $atts['type'] ),
					'posts_per_page' => absint( $atts['count'] ),
					'post_status'    => 'publish',
					'ignore_sticky_posts' => true,
				)
			);
			foreach ( $q->posts as $post ) {
				$slides[] = array(
					'title' => get_the_title( $post ),
					'text'  => wp_trim_words( get_the_excerpt( $post ), 22 ),
					'url'   => get_permalink( $post ),
					'image' => get_the_post_thumbnail_url( $post, 'nova-wide' ),
					'btn'   => __( 'مشاهده', 'nova' ),
				);
			}
			wp_reset_postdata();
		}

		ob_start();
		get_template_part(
			'template-parts/parts/slider',
			null,
			array(
				'slides'   => $slides,
				'height'   => (int) $atts['height'],
				'autoplay' => (int) $atts['autoplay'],
				'arrows'   => (bool) $atts['arrows'],
				'dots'     => (bool) $atts['dots'],
			)
		);
		return ob_get_clean();
	}

	/* --------------------------------------------------------------------- */

	/** [nova_story ids="1,2" title="…"] or pulls recent stories in story mode. */
	public static function story( $atts ) {
		if ( ! Nova_Modes::has( 'story' ) ) {
			return '';
		}
		Nova_Assets::ensure( 'story' );
		$atts = shortcode_atts(
			array(
				'ids'   => '',
				'count' => '8',
				'title' => '',
			),
			$atts,
			'nova_story'
		);
		ob_start();
		get_template_part(
			'template-parts/parts/story',
			null,
			array(
				'ids'   => $atts['ids'],
				'count' => (int) $atts['count'],
				'title' => $atts['title'],
			)
		);
		return ob_get_clean();
	}

	/* --------------------------------------------------------------------- */

	/** [nova_portfolio count="9" filter="1"] */
	public static function portfolio( $atts ) {
		if ( ! Nova_Modes::has( 'portfolio' ) ) {
			return '';
		}
		Nova_Assets::ensure( 'portfolio' );
		$atts = shortcode_atts(
			array(
				'count'  => '9',
				'filter' => '1',
				'cols'   => '3',
			),
			$atts,
			'nova_portfolio'
		);
		ob_start();
		get_template_part(
			'template-parts/parts/portfolio-grid',
			null,
			array(
				'count'  => (int) $atts['count'],
				'filter' => (bool) $atts['filter'],
				'cols'   => (int) $atts['cols'],
			)
		);
		return ob_get_clean();
	}

	/* --------------------------------------------------------------------- */

	/** [nova_ads id="12"] — an ad post type entry, or a zone slug. */
	public static function ads( $atts ) {
		if ( ! Nova_Modes::has( 'ads' ) ) {
			return '';
		}
		$atts = shortcode_atts( array( 'id' => '', 'zone' => '' ), $atts, 'nova_ads' );
		Nova_Assets::ensure( 'ads' );
		return Nova_Ads::render( $atts['id'], $atts['zone'] );
	}

	/** [nova_social size="md" style="round"] */
	public static function social( $atts ) {
		$atts = shortcode_atts( array( 'class' => '' ), $atts, 'nova_social' );
		ob_start();
		nova_social_links( $atts['class'] );
		return ob_get_clean();
	}

	/** [nova_breadcrumb] — SEO friendly, schema.org markup. */
	public static function breadcrumb( $atts = array() ) {
		$items = nova_breadcrumb_items();
		if ( ! $items ) {
			return '';
		}
		$out  = '<nav class="nv-crumbs" aria-label="' . esc_attr__( 'مسیر صفحه', 'nova' ) . '"><ol>';
		$last = count( $items ) - 1;
		foreach ( $items as $i => $item ) {
			if ( $i === $last || empty( $item['url'] ) ) {
				$out .= '<li aria-current="page">' . esc_html( $item['name'] ) . '</li>';
			} else {
				$out .= '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['name'] ) . '</a></li>';
			}
		}
		$out .= '</ol></nav>';
		return $out;
	}

	/** [nova_button text="…" url="…" style="gradient" icon="cart"] */
	public static function button( $atts ) {
		$atts = shortcode_atts(
			array(
				'text'  => '',
				'url'   => '#',
				'style' => 'primary',
				'icon'  => '',
				'size'  => '',
			),
			$atts,
			'nova_button'
		);
		return sprintf(
			'<a class="nv-btn nv-btn--%1$s %2$s" href="%3$s">%4$s<span>%5$s</span></a>',
			esc_attr( $atts['style'] ),
			esc_attr( $atts['size'] ? 'nv-btn--' . $atts['size'] : '' ),
			esc_url( $atts['url'] ),
			$atts['icon'] ? Nova_Menus::icon_svg( $atts['icon'] ) : '',
			esc_html( $atts['text'] )
		);
	}

	/** [nova_wishlist_count] */
	public static function wishlist_count() {
		$count = count( Nova_Ajax::wishlist_ids() );
		return '<span class="nv-count" data-wishlist-count>' . esc_html( $count ) . '</span>';
	}
}
