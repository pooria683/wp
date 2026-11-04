<?php
/**
 * Elementor widgets sold with the theme.
 *
 * Every widget is a thin wrapper around the theme's own render methods, which
 * means Elementor pages use exactly the same markup, CSS and JS as Shortcode /
 * Block pages — one source of truth, one optimisation path.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

if ( ! class_exists( 'Nova_El_Base' ) ) {

	/**
	 * Shared base class.
	 */
	abstract class Nova_El_Base extends \Elementor\Widget_Base {

		/** @return array */
		public function get_categories() {
			return array( 'nova' );
		}

		/** @return array */
		public function get_keywords() {
			return array( 'nova', 'قالب نووا' );
		}

		/** Common style controls: radius, spacing, colours. */
		protected function add_box_styles( $name = 'box' ) {
			$this->start_controls_section(
				$name . '_style',
				array(
					'label' => __( 'استایل جعبه', 'nova' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);
			$this->add_responsive_control(
				$name . '_radius',
				array(
					'label'      => __( 'شعاع گوشه', 'nova' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
					'selectors'  => array(
						'{{WRAPPER}} .nv-el' => 'border-radius: {{SIZE}}{{UNIT}};',
					),
				)
			);
			$this->add_responsive_control(
				$name . '_gap',
				array(
					'label'      => __( 'فاصله', 'nova' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
					'selectors'  => array(
						'{{WRAPPER}} .nv-el' => '--nv-gap: {{SIZE}}{{UNIT}};',
					),
				)
			);
			$this->end_controls_section();
		}
	}
}

/**
 * Video / audio player.
 */
class Nova_El_Player extends Nova_El_Base {

	public function get_name() {
		return 'nova-player';
	}

	public function get_title() {
		return __( 'Nova — پلیر ویدئو/صوت', 'nova' );
	}

	public function get_icon() {
		return 'eicon-video-playlist';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'محتوا', 'nova' ) ) );

		$this->add_control(
			'src',
			array(
				'label'       => __( 'آدرس ویدئو / صوت', 'nova' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'https://www.aparat.com/v/xxxxx',
				'label_block' => true,
			)
		);
		$this->add_control(
			'source',
			array(
				'label'   => __( 'منبع', 'nova' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'auto'    => __( 'تشخیص خودکار', 'nova' ),
					'local'   => __( 'فایل وردپرس (MP4)', 'nova' ),
					'youtube' => 'YouTube',
					'aparat'  => __( 'آپارات', 'nova' ),
					'vimeo'   => 'Vimeo',
					'audio'   => __( 'صوت', 'nova' ),
				),
				'default' => 'auto',
			)
		);
		$this->add_control(
			'poster',
			array(
				'label' => __( 'تصویر کاور', 'nova' ),
				'type'  => \Elementor\Controls_Manager::MEDIA,
			)
		);
		$this->add_control(
			'ratio',
			array(
				'label'   => __( 'نسبت ابعاد', 'nova' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array( '16/9' => '16:9', '4/3' => '4:3', '1/1' => '1:1', '21/9' => '21:9' ),
				'default' => '16/9',
			)
		);
		$this->add_control(
			'autoplay',
			array(
				'label'   => __( 'پخش خودکار', 'nova' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => '',
			)
		);
		$this->add_control(
			'title',
			array(
				'label'       => __( 'عنوان', 'nova' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
			)
		);
		$this->end_controls_section();
		$this->add_box_styles();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		if ( empty( $s['src'] ) ) {
			return;
		}
		echo '<div class="nv-el">';
		echo Nova_Shortcodes::render_player( // phpcs:ignore WordPress.Security.EscapeOutput
			array(
				'src'      => $s['src'],
				'type'     => $s['source'] ?? 'auto',
				'poster'   => $s['poster']['url'] ?? '',
				'ratio'    => $s['ratio'] ?? '16/9',
				'autoplay' => 'yes' === ( $s['autoplay'] ?? '' ) ? 1 : 0,
				'title'    => $s['title'] ?? '',
			)
		);
		echo '</div>';
	}
}

/**
 * Slider.
 */
class Nova_El_Slider extends Nova_El_Base {

	public function get_name() {
		return 'nova-slider';
	}

	public function get_title() {
		return __( 'Nova — اسلایدر', 'nova' );
	}

	public function get_icon() {
		return 'eicon-slider-3d';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'محتوا', 'nova' ) ) );

		$this->add_control(
			'source',
			array(
				'label'   => __( 'منبع اسلایدها', 'nova' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'post'          => __( 'نوشته‌ها', 'nova' ),
					'product'       => __( 'محصولات', 'nova' ),
					'nova_portfolio' => __( 'نمونه‌کارها', 'nova' ),
				),
				'default' => 'post',
			)
		);
		$this->add_control(
			'ids',
			array(
				'label'       => __( 'شناسه‌های دلخواه (اختیاری)', 'nova' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => '12,15,20',
				'label_block' => true,
			)
		);
		$this->add_control(
			'count',
			array(
				'label'   => __( 'تعداد اسلاید', 'nova' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 5,
			)
		);
		$this->add_control(
			'height',
			array(
				'label'   => __( 'ارتفاع (px)', 'nova' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 520,
			)
		);
		$this->add_control(
			'autoplay',
			array(
				'label'   => __( 'زمان پخش خودکار (ms — صفر = خاموش)', 'nova' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 6000,
			)
		);
		$this->end_controls_section();
		$this->add_box_styles();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		Nova_Assets::ensure( 'slider' );
		echo do_shortcode(
			sprintf(
				'[nova_slider ids="%1$s" type="%2$s" count="%3$d" height="%4$d" autoplay="%5$d"]',
				esc_attr( $s['ids'] ?? '' ),
				esc_attr( $s['source'] ?? 'post' ),
				(int) ( $s['count'] ?? 5 ),
				(int) ( $s['height'] ?? 520 ),
				(int) ( $s['autoplay'] ?? 6000 )
			)
		);
	}
}

/**
 * Stories.
 */
class Nova_El_Stories extends Nova_El_Base {

	public function get_name() {
		return 'nova-stories';
	}

	public function get_title() {
		return __( 'Nova — استوری‌ها', 'nova' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'محتوا', 'nova' ) ) );
		$this->add_control( 'title', array( 'label' => __( 'عنوان', 'nova' ), 'type' => \Elementor\Controls_Manager::TEXT ) );
		$this->add_control( 'count', array( 'label' => __( 'تعداد', 'nova' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 8 ) );
		$this->add_control( 'ids', array( 'label' => __( 'شناسه‌های دلخواه', 'nova' ), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		if ( ! Nova_Modes::has( 'story' ) ) {
			echo '<p class="nv-muted">' . esc_html__( 'ماژول استوری غیرفعال است.', 'nova' ) . '</p>';
			return;
		}
		echo do_shortcode(
			sprintf(
				'[nova_story ids="%1$s" count="%2$d" title="%3$s"]',
				esc_attr( $s['ids'] ?? '' ),
				(int) ( $s['count'] ?? 8 ),
				esc_attr( $s['title'] ?? '' )
			)
		);
	}
}

/**
 * Portfolio grid.
 */
class Nova_El_Portfolio extends Nova_El_Base {

	public function get_name() {
		return 'nova-portfolio';
	}

	public function get_title() {
		return __( 'Nova — نمونه‌کارها', 'nova' );
	}

	public function get_icon() {
		return 'eicon-gallery-masonry';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'محتوا', 'nova' ) ) );
		$this->add_control( 'count', array( 'label' => __( 'تعداد', 'nova' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 9 ) );
		$this->add_responsive_control(
			'cols',
			array(
				'label'   => __( 'ستون‌ها', 'nova' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 3,
				'min'     => 1,
				'max'     => 6,
			)
		);
		$this->add_control( 'filter', array( 'label' => __( 'نمایش فیلتر دسته‌بندی', 'nova' ), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo do_shortcode(
			sprintf(
				'[nova_portfolio count="%1$d" cols="%2$d" filter="%3$s"]',
				(int) ( $s['count'] ?? 9 ),
				(int) ( $s['cols'] ?? 3 ),
				'yes' === ( $s['filter'] ?? 'yes' ) ? 1 : 0
			)
		);
	}
}

/**
 * Posts grid.
 */
class Nova_El_Posts extends Nova_El_Base {

	public function get_name() {
		return 'nova-posts';
	}

	public function get_title() {
		return __( 'Nova — شبکه نوشته‌ها', 'nova' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'محتوا', 'nova' ) ) );
		$this->add_control(
			'type',
			array(
				'label'   => __( 'نوع محتوا', 'nova' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'post'           => __( 'نوشته', 'nova' ),
					'nova_portfolio' => __( 'نمونه‌کار', 'nova' ),
					'product'        => __( 'محصول', 'nova' ),
				),
				'default' => 'post',
			)
		);
		$this->add_control( 'count', array( 'label' => __( 'تعداد', 'nova' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 6 ) );
		$this->add_responsive_control( 'cols', array( 'label' => __( 'ستون‌ها', 'nova' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 3, 'min' => 1, 'max' => 6 ) );
		$this->end_controls_section();
		$this->add_box_styles();
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$q   = new WP_Query(
			array(
				'post_type'      => $s['type'] ?? 'post',
				'posts_per_page' => (int) ( $s['count'] ?? 6 ),
				'post_status'    => 'publish',
			)
		);
		$cls = 'nv-grid nv-grid--cols-' . max( 1, (int) ( $s['cols'] ?? 3 ) );
		echo '<div class="nv-el ' . esc_attr( $cls ) . '">';
		if ( $q->have_posts() ) {
			while ( $q->have_posts() ) {
				$q->the_post();
				if ( 'product' === get_post_type() && function_exists( 'wc_get_template_part' ) ) {
					wc_get_template_part( 'content', 'product' );
				} else {
					nova_post_card();
				}
			}
		} else {
			echo '<p class="nv-muted">' . esc_html__( 'محتوایی یافت نشد.', 'nova' ) . '</p>';
		}
		echo '</div>';
		wp_reset_postdata();
	}
}
