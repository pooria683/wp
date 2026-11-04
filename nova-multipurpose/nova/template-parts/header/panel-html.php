<?php
/**
 * Custom HTML panel.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

$html = Nova_Modes::get_option( 'header_html', '' );
if ( $html ) {
	echo '<div class="nv-topbar__html">' . wp_kses_post( $html ) . '</div>';
}
