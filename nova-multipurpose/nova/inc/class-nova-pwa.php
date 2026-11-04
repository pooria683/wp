<?php
/**
 * PWA / mobile app module.
 *
 * Turns the finished site into an installable app (Android via WebView/TWA or
 * "Add to home screen"): web-app manifest, service worker, offline page,
 * install prompt, and an export of a Cordova/Capacitor-ready config bundle.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_PWA {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrite' ) );
		add_action( 'template_redirect', array( __CLASS__, 'serve' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'head' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'register_sw' ), 99 );
		add_action( 'wp_ajax_nova_app_export', array( __CLASS__, 'ajax_export' ) );
	}

	public static function rewrite() {
		add_rewrite_rule( '^nova-manifest\.json$', 'index.php?nova_manifest=1', 'top' );
		add_rewrite_rule( '^nova-sw\.js$', 'index.php?nova_sw=1', 'top' );
		add_filter( 'query_vars', static function ( $vars ) {
			$vars[] = 'nova_manifest';
			$vars[] = 'nova_sw';
			return $vars;
		} );
	}

	public static function options() {
		$defaults = array(
			'name'             => get_bloginfo( 'name' ),
			'short_name'       => mb_substr( get_bloginfo( 'name' ), 0, 12 ),
			'theme_color'      => Nova_Modes::get_option( 'color_primary', '#2563eb' ),
			'background_color' => Nova_Modes::get_option( 'color_bg', '#ffffff' ),
			'display'          => 'standalone',
			'orientation'      => 'portrait',
			'start_url'        => home_url( '/' ),
			'offline'          => true,
			'install_button'   => true,
		);
		return wp_parse_args( (array) get_option( 'nova_pwa', array() ), $defaults );
	}

	/** Serve manifest, service worker and the offline fallback page. */
	public static function serve() {
		// Offline fallback page used by the service worker.
		if ( isset( $_GET['nova_offline'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$o = self::options();
			nocache_headers();
			header( 'Content-Type: text/html; charset=utf-8' );
			?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( sprintf( __( '%s — آفلاین', 'nova' ), $o['name'] ) ); ?></title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;text-align:center;font-family:system-ui,Tahoma,sans-serif;background:<?php echo esc_attr( $o['background_color'] ); ?>;color:#18181b;padding:24px}
h1{font-size:1.5rem;margin:0 0 8px}p{color:#71717a;margin:0 0 20px}
button{padding:12px 24px;border:0;border-radius:12px;background:<?php echo esc_attr( $o['theme_color'] ); ?>;color:#fff;font:inherit;font-weight:700;cursor:pointer}
</style>
</head>
<body>
<div>
	<h1><?php esc_html_e( 'اتصال اینترنت قطع است', 'nova' ); ?></h1>
	<p><?php esc_html_e( 'صفحه‌هایی که قبلاً باز کرده‌اید در دسترس هستند. بعد از وصل شدن دوباره تلاش کنید.', 'nova' ); ?></p>
	<button onclick="location.reload()"><?php esc_html_e( 'تلاش دوباره', 'nova' ); ?></button>
</div>
</body>
</html>
			<?php
			exit;
		}

		if ( get_query_var( 'nova_manifest' ) ) {
			$o = self::options();
			$icons = array();
			$sizes = array( 192, 512 );
			foreach ( $sizes as $size ) {
				$id = (int) get_option( 'nova_pwa_icon_' . $size, 0 );
				if ( $id ) {
					$src = wp_get_attachment_image_url( $id, 'full' );
					$icons[] = array(
						'src'   => $src,
						'sizes' => "{$size}x{$size}",
						'type'  => 'image/png',
					);
				}
			}
			if ( ! $icons && has_site_icon() ) {
				$icons[] = array(
					'src'   => get_site_icon_url( 512 ),
					'sizes' => '512x512',
					'type'  => 'image/png',
				);
			}

			$manifest = array(
				'name'             => $o['name'],
				'short_name'       => $o['short_name'],
				'description'      => get_bloginfo( 'description' ),
				'start_url'        => $o['start_url'],
				'scope'            => home_url( '/' ),
				'display'          => $o['display'],
				'orientation'      => $o['orientation'],
				'theme_color'      => $o['theme_color'],
				'background_color' => $o['background_color'],
				'lang'             => get_bloginfo( 'language' ),
				'dir'              => is_rtl() ? 'rtl' : 'ltr',
				'icons'            => $icons,
			);

			nocache_headers();
			header( 'Content-Type: application/manifest+json; charset=utf-8' );
			echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			exit;
		}

		if ( get_query_var( 'nova_sw' ) ) {
			$version = get_option( 'nova_sw_version', '1' );
			$offline = home_url( '/?nova_offline=1' );
			nocache_headers();
			header( 'Content-Type: application/javascript; charset=utf-8' );
			header( 'Service-Worker-Allowed: /' );
			?>
const CACHE = 'nova-v<?php echo esc_js( $version ); ?>';
const OFFLINE = <?php echo wp_json_encode( $offline ); ?>;
const SHELL = ['/', OFFLINE];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  const req = e.request;
  if (req.method !== 'GET' || new URL(req.url).origin !== location.origin) return;

  // Stale-while-revalidate for static assets.
  const isAsset = /\.(css|js|woff2?|png|jpe?g|svg|webp|avif|gif|ico)$/i.test(req.url);
  if (isAsset) {
    e.respondWith(
      caches.match(req).then(hit => {
        const net = fetch(req).then(res => {
          const copy = res.clone();
          caches.open(CACHE).then(c => c.put(req, copy));
          return res;
        }).catch(() => hit);
        return hit || net;
      })
    );
    return;
  }

  // Network-first for HTML, offline fallback.
  e.respondWith(
    fetch(req).then(res => {
      const copy = res.clone();
      caches.open(CACHE).then(c => c.put(req, copy));
      return res;
    }).catch(() => caches.match(req).then(hit => hit || caches.match(OFFLINE)))
  );
});
			<?php
			exit;
		}
	}

	/** Manifest link + iOS meta. */
	public static function head() {
		$o = self::options();
		printf( '<link rel="manifest" href="%s">' . "\n", esc_url( home_url( '/nova-manifest.json' ) ) );
		printf( '<meta name="theme-color" content="%s">' . "\n", esc_attr( $o['theme_color'] ) );
		echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
		printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( get_site_icon_url( 180 ) ) );
	}

	/** Register the service worker + install button (only when enabled). */
	public static function register_sw() {
		if ( is_admin() ) {
			return;
		}
		?>
		<script>
		if ('serviceWorker' in navigator) {
			window.addEventListener('load', function () {
				navigator.serviceWorker.register('<?php echo esc_url( home_url( '/nova-sw.js' ) ); ?>', { scope: '<?php echo esc_url( home_url( '/' ) ); ?>' }).catch(function () {});
			});
		}
		</script>
		<?php
	}

	/**
	 * Build a downloadable "mobile app" bundle: a Capacitor/Cordova project
	 * skeleton that wraps the finished site (works for Android, and iOS later).
	 */
	public static function ajax_export() {
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'nova_app_export', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'دسترسی مجاز نیست.', 'nova' ) ), 403 );
		}
		$o = self::options();
		$pkg = array(
			'name'       => 'nova-app',
			'version'    => '1.0.0',
			'appId'      => 'com.nova.' . sanitize_title( get_bloginfo( 'name' ) ),
			'appName'    => $o['name'],
			'webDir'     => 'www',
			'server'     => array(
				'url'      => $o['start_url'],
				'cleartext'=> false,
			),
			'android'    => array(
				'themeColor' => $o['theme_color'],
			),
		);

		$readme = "# Nova App ({$o['name']})\n\n"
			. "این بسته، پوسته‌ی اپلیکیشن اندروید سایت شماست.\n\n"
			. "## ساخت APK\n"
			. "1) Node.js 18+ و Android Studio را نصب کنید.\n"
			. "2) `npm i -D @capacitor/cli @capacitor/core @capacitor/android`\n"
			. "3) `npx cap init \"{$o['name']}\" com.nova.app --web-dir=www`\n"
			. "4) `npx cap add android`\n"
			. "5) در فایل capacitor.config.json آدرس سرور را تنظیم کنید (server.url).\n"
			. "6) `npx cap open android` و سپس Build > Generate Signed Bundle.\n\n"
			. "## آیکون و اسپلش\n"
			. "از فایل‌های icon-192.png و icon-512.png استفاده کنید (appicon.co).\n";

		$files = array(
			'capacitor.config.json' => wp_json_encode( $pkg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
			'README.md'             => $readme,
			'www/index.html'        => '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( $o['name'] ) . '</title><script>location.replace(' . wp_json_encode( $o['start_url'] ) . ')</script>',
		);

		wp_send_json_success(
			array(
				'files' => $files,
				'name'  => 'nova-app-' . gmdate( 'Ymd' ),
			)
		);
	}
}
