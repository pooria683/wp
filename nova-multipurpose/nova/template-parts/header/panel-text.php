<?php
/**
 * Text panel.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;
$text = Nova_Modes::get_option( 'header_text', __( 'ارسال رایگان برای خریدهای بالای ۵۰۰ هزار تومان', 'nova' ) );
?>
<p class="nv-topbar__text"><?php echo esc_html( $text ); ?></p>
