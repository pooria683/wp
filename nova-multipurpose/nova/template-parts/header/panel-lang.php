<?php
/**
 * Language switcher (WPML / Polylang / TranslatePress).
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'pll_the_languages' ) ) {
	echo '<div class="nv-lang">';
	pll_the_languages( array( 'show_flags' => 0, 'show_names' => 1, 'display_names_as' => 'code' ) );
	echo '</div>';
} elseif ( function_exists( 'icl_get_languages' ) ) {
	$langs = icl_get_languages( 'skip_missing=0' );
	echo '<div class="nv-lang">';
	foreach ( (array) $langs as $lang ) {
		printf( '<a href="%s" class="%s">%s</a>', esc_url( $lang['url'] ), $lang['active'] ? 'is-active' : '', esc_html( strtoupper( $lang['language_code'] ) ) );
	}
	echo '</div>';
} elseif ( function_exists( 'trp_custom_language_switcher' ) ) {
	trp_custom_language_switcher();
}
