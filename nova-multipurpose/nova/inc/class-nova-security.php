<?php
/**
 * Security hardening.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Security {

	public static function init() {
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'xmlrpc_enabled', array( __CLASS__, 'xmlrpc' ) );
		add_filter( 'wp_headers', array( __CLASS__, 'headers' ) );
		add_filter( 'login_errors', array( __CLASS__, 'login_errors' ) );
		add_filter( 'comment_form_defaults', array( __CLASS__, 'comment_form' ) );
		add_action( 'init', array( __CLASS__, 'disable_author_scan' ) );
	}

	public static function xmlrpc( $enabled ) {
		return Nova_Modes::feature( 'xmlrpc', false ) ? $enabled : false;
	}

	public static function headers( $headers ) {
		if ( is_admin() ) {
			return $headers;
		}
		$headers['X-Content-Type-Options'] = 'nosniff';
		$headers['X-Frame-Options']        = 'SAMEORIGIN';
		$headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
		$headers['Permissions-Policy']     = 'geolocation=(), microphone=(), camera=()';
		if ( is_ssl() ) {
			$headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
		}
		return $headers;
	}

	public static function login_errors() {
		return __( 'نام کاربری یا گذرواژه نادرست است.', 'nova' );
	}

	/** Honeypot field — blocks most bots without a CAPTCHA service. */
	public static function comment_form( $defaults ) {
		$defaults['fields']['nova_hp'] = '<p class="nv-hp" aria-hidden="true"><label>' . esc_html__( 'این فیلد را خالی بگذارید', 'nova' ) . '<input type="text" name="nova_hp" value="" tabindex="-1" autocomplete="off"></label></p>';
		return $defaults;
	}

	/** Block ?author=N enumeration when the theme option is on. */
	public static function disable_author_scan() {
		if ( ! Nova_Modes::feature( 'block_author_scan', true ) || is_admin() ) {
			return;
		}
		if ( isset( $_GET['author'] ) && ! is_user_logged_in() ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( home_url( '/' ), 302 );
			exit;
		}
	}
}

/** Honeypot validation. */
add_filter(
	'pre_comment_approved',
	static function ( $approved ) {
		if ( ! empty( $_POST['nova_hp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 'spam';
		}
		return $approved;
	},
	10,
	1
);
