<?php
/**
 * Slider markup (no library — core.js/nova-slider.js does the work).
 *
 * @package Nova
 *
 * @var array $args Passed from the shortcode: slides, height, autoplay, arrows, dots.
 */

defined( 'ABSPATH' ) || exit;

$slides   = $args['slides'] ?? array();
$height   = (int) ( $args['height'] ?? 520 );
$autoplay = (int) ( $args['autoplay'] ?? 6000 );
$arrows   = $args['arrows'] ?? true;
$dots     = $args['dots'] ?? true;

if ( ! $slides ) {
	return;
}
?>
<div class="nv-slider" data-nv-slider data-autoplay="<?php echo esc_attr( $autoplay ); ?>" style="--nv-slider-h:<?php echo esc_attr( $height ); ?>px">
	<div class="nv-slider__track">
		<?php foreach ( $slides as $i => $slide ) : ?>
			<div class="nv-slider__slide <?php echo 0 === $i ? 'is-active' : ''; ?>" data-slide="<?php echo (int) $i; ?>" aria-hidden="<?php echo 0 === $i ? 'false' : 'true'; ?>">
				<?php if ( ! empty( $slide['image'] ) ) : ?>
					<img class="nv-slider__img" src="<?php echo esc_url( $slide['image'] ); ?>" alt="<?php echo esc_attr( $slide['title'] ); ?>" <?php echo 0 === $i ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
				<?php endif; ?>
				<div class="nv-slider__overlay" aria-hidden="true"></div>
				<div class="nv-container nv-slider__content">
					<?php if ( ! empty( $slide['title'] ) ) : ?>
						<h2 class="nv-slider__title"><?php echo esc_html( $slide['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $slide['text'] ) ) : ?>
						<p class="nv-slider__text"><?php echo esc_html( $slide['text'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $slide['url'] ) ) : ?>
						<a class="nv-btn nv-btn--gradient" href="<?php echo esc_url( $slide['url'] ); ?>"><span><?php echo esc_html( $slide['btn'] ?? __( 'بیشتر', 'nova' ) ); ?></span></a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( $arrows && count( $slides ) > 1 ) : ?>
		<button class="nv-slider__arrow nv-slider__arrow--prev" type="button" data-dir="-1" aria-label="<?php esc_attr_e( 'قبلی', 'nova' ); ?>">‹</button>
		<button class="nv-slider__arrow nv-slider__arrow--next" type="button" data-dir="1" aria-label="<?php esc_attr_e( 'بعدی', 'nova' ); ?>">›</button>
	<?php endif; ?>

	<?php if ( $dots && count( $slides ) > 1 ) : ?>
		<div class="nv-slider__dots" role="tablist">
			<?php foreach ( $slides as $i => $slide ) : ?>
				<button class="nv-slider__dot <?php echo 0 === $i ? 'is-active' : ''; ?>" type="button" data-go="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'اسلاید %d', 'nova' ), $i + 1 ) ); ?>"></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
