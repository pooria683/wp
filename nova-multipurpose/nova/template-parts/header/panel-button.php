<?php
/**
 * CTA button panel.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;
$label = Nova_Modes::get_option( 'header_btn_label', __( 'تماس با ما', 'nova' ) );
$url   = Nova_Modes::get_option( 'header_btn_url', '#contact' );
?>
<a class="nv-btn nv-btn--primary nv-btn--sm" href="<?php echo esc_url( $url ); ?>"><span><?php echo esc_html( $label ); ?></span></a>
