<?php
/**
 * Meta boxes: portfolio details, story fields, ad settings.
 * All saves go through a single nonce + capability check.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Meta {

	const NONCE = 'nova_meta_nonce';

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
	}

	public static function boxes() {
		if ( Nova_Modes::has( 'portfolio' ) ) {
			add_meta_box( 'nova_portfolio', __( 'جزئیات نمونه‌کار', 'nova' ), array( __CLASS__, 'portfolio_box' ), 'nova_portfolio', 'side', 'high' );
		}
		if ( Nova_Modes::has( 'story' ) ) {
			add_meta_box( 'nova_story', __( 'تنظیمات استوری', 'nova' ), array( __CLASS__, 'story_box' ), 'nova_story', 'normal', 'high' );
		}
		if ( Nova_Modes::has( 'ads' ) ) {
			add_meta_box( 'nova_ad', __( 'تنظیمات تبلیغ', 'nova' ), array( __CLASS__, 'ad_box' ), 'nova_ad', 'normal', 'high' );
		}
	}

	private static function field( $post_id, $key, $label, $type = 'text', $placeholder = '' ) {
		$val = get_post_meta( $post_id, $key, true );
		printf( '<p><label for="%1$s"><strong>%2$s</strong></label><br>', esc_attr( $key ), esc_html( $label ) );
		if ( 'textarea' === $type ) {
			printf( '<textarea id="%1$s" name="%1$s" rows="4" class="widefat" placeholder="%2$s">%3$s</textarea>', esc_attr( $key ), esc_attr( $placeholder ), esc_textarea( $val ) );
		} else {
			printf( '<input type="%4$s" id="%1$s" name="%1$s" value="%2$s" class="widefat" placeholder="%3$s">', esc_attr( $key ), esc_attr( $val ), esc_attr( $placeholder ), esc_attr( $type ) );
		}
		echo '</p>';
	}

	public static function portfolio_box( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE );
		self::field( $post->ID, 'nova_client', __( 'کارفرما', 'nova' ) );
		self::field( $post->ID, 'nova_date', __( 'تاریخ پروژه', 'nova' ) );
		self::field( $post->ID, 'nova_services', __( 'خدمات (با کاما)', 'nova' ) );
		self::field( $post->ID, 'nova_link', __( 'لینک پروژه', 'nova' ), 'url' );
		self::field( $post->ID, 'nova_gallery', __( 'گالری (شناسه تصاویر با کاما)', 'nova' ), 'text', '12,13,14' );
	}

	public static function story_box( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE );
		self::field( $post->ID, 'nova_story_video', __( 'ویدئو استوری (MP4 یا یوتیوب/آپارات)', 'nova' ), 'url' );
		self::field( $post->ID, 'nova_story_link', __( 'لینک «بیشتر»', 'nova' ), 'url' );
		echo '<p class="description">' . esc_html__( 'برای استوری از تصویر شاخص (مربع) استفاده کنید.', 'nova' ) . '</p>';
	}

	public static function ad_box( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE );
		self::field( $post->ID, '_nova_ad_link', __( 'لینک مقصد', 'nova' ), 'url' );
		self::field( $post->ID, '_nova_ad_start', __( 'شروع (timestamp) — خالی = فوری', 'nova' ), 'number' );
		self::field( $post->ID, '_nova_ad_end', __( 'پایان (timestamp) — خالی = همیشه', 'nova' ), 'number' );
		self::field( $post->ID, '_nova_ad_exclude', __( 'استثناها', 'nova' ), 'text', 'home,shop,product' );
		echo '<p class="description">' . esc_html__( 'محتوای ادیتور = کد HTML تبلیغ؛ اگر خالی باشد، تصویر شاخص + لینک نمایش داده می‌شود.', 'nova' ) . '</p>';

		$stats_key = 'nova_ad_stats_' . gmdate( 'Ymd' );
		$stats     = get_option( $stats_key, array() );
		$mine      = $stats[ $post->ID ] ?? array( 'imp' => 0, 'clk' => 0 );
		printf(
			'<p><strong>%s</strong> %s / %s</p>',
			esc_html__( 'امروز — نمایش / کلیک:', 'nova' ),
			(int) $mine['imp'],
			(int) $mine['clk']
		);
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$allowed = array(
			'nova_portfolio' => array( 'nova_client', 'nova_date', 'nova_services', 'nova_link', 'nova_gallery' ),
			'nova_story'     => array( 'nova_story_video', 'nova_story_link' ),
			'nova_ad'        => array( '_nova_ad_link', '_nova_ad_start', '_nova_ad_end', '_nova_ad_exclude' ),
		);
		$type = $post->post_type;
		if ( ! isset( $allowed[ $type ] ) ) {
			return;
		}

		foreach ( $allowed[ $type ] as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw = wp_unslash( $_POST[ $key ] );
			if ( '_nova_ad_start' === $key || '_nova_ad_end' === $key ) {
				$val = (int) $raw;
			} elseif ( 'nova_link' === $key || '_nova_ad_link' === $key || 'nova_story_video' === $key || 'nova_story_link' === $key ) {
				$val = esc_url_raw( $raw );
			} else {
				$val = sanitize_text_field( $raw );
			}
			update_post_meta( $post_id, $key, $val );
		}
	}
}
