<?php
/**
 * Admin panel.
 *
 * One class, a declarative field schema per page and a single save handler.
 * Adding a new setting = adding one line to the schema, nothing else.
 *
 * Pages:
 *   nova            dashboard + design tokens + custom CSS
 *   nova-wizard     first-run mode chooser (multi-select)
 *   nova-header     header builder
 *   nova-footer     footer builder
 *   nova-woo        shop settings + product card widget manager
 *   nova-modules    enable/disable theme modules (like Elementor's widget manager)
 *   nova-cpt        custom post types + PWA / mobile app export
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Admin {

	const CAP = 'manage_options';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_nova_save', array( __CLASS__, 'save' ) );
	}

	/* ---------------------------------------------------------------- menu */

	public static function menu() {
		add_menu_page(
			__( 'Nova', 'nova' ),
			__( 'قالب Nova', 'nova' ),
			self::CAP,
			'nova',
			array( __CLASS__, 'render' ),
			'dashicons-superhero',
			3
		);
		add_submenu_page( 'nova', __( 'راه‌اندازی', 'nova' ), __( 'راه‌اندازی', 'nova' ), self::CAP, 'nova-wizard', array( __CLASS__, 'render' ) );
		add_submenu_page( 'nova', __( 'هدرساز', 'nova' ), __( 'هدرساز', 'nova' ), self::CAP, 'nova-header', array( __CLASS__, 'render' ) );
		add_submenu_page( 'nova', __( 'فوترساز', 'nova' ), __( 'فوترساز', 'nova' ), self::CAP, 'nova-footer', array( __CLASS__, 'render' ) );
		if ( Nova_Modes::has( 'shop' ) ) {
			add_submenu_page( 'nova', __( 'فروشگاه', 'nova' ), __( 'فروشگاه', 'nova' ), self::CAP, 'nova-woo', array( __CLASS__, 'render' ) );
		}
		add_submenu_page( 'nova', __( 'مدیریت ماژول‌ها', 'nova' ), __( 'مدیریت ماژول‌ها', 'nova' ), self::CAP, 'nova-modules', array( __CLASS__, 'render' ) );
		add_submenu_page( 'nova', __( 'پست تایپ و اپ', 'nova' ), __( 'پست تایپ و اپ', 'nova' ), self::CAP, 'nova-cpt', array( __CLASS__, 'render' ) );
	}

	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'nova' ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_style( 'nova-admin', NOVA_URI . '/assets/css/admin.css', array( 'wp-color-picker' ), NOVA_VERSION );
		wp_enqueue_script( 'nova-admin', NOVA_URI . '/assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), NOVA_VERSION, true );
		wp_localize_script(
			'nova-admin',
			'NOVA_ADMIN',
			array(
				'i18n' => array(
					'selectImage' => __( 'انتخاب تصویر', 'nova' ),
					'useImage'    => __( 'استفاده از این تصویر', 'nova' ),
					'remove'      => __( 'حذف', 'nova' ),
					'confirm'     => __( 'مطمئن هستید؟', 'nova' ),
				),
			)
		);
	}

	/* -------------------------------------------------------------- schema */

	public static function schema( $page ) {
		$header_panels = Nova_Header::panels();

		$design = array(
			'section:colors' => array( 'type' => 'heading', 'label' => __( 'رنگ‌ها و گرادیانت', 'nova' ) ),
			'color_primary'   => array( 'type' => 'color', 'label' => __( 'رنگ اصلی', 'nova' ), 'default' => '#2563eb' ),
			'color_secondary' => array( 'type' => 'color', 'label' => __( 'رنگ مکمل', 'nova' ), 'default' => '#7c3aed' ),
			'color_text'      => array( 'type' => 'color', 'label' => __( 'رنگ متن', 'nova' ), 'default' => '#18181b' ),
			'color_muted'     => array( 'type' => 'color', 'label' => __( 'متن کم‌رنگ', 'nova' ), 'default' => '#71717a' ),
			'color_bg'        => array( 'type' => 'color', 'label' => __( 'پس‌زمینه', 'nova' ), 'default' => '#ffffff' ),
			'color_surface'   => array( 'type' => 'color', 'label' => __( 'پس‌زمینه ثانویه', 'nova' ), 'default' => '#f6f7f9' ),
			'color_border'    => array( 'type' => 'color', 'label' => __( 'خطوط و حاشیه', 'nova' ), 'default' => '#e7e8ec' ),
			'gradients'       => array( 'type' => 'toggle', 'label' => __( 'فعال‌سازی گرادیانت', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'gradient_from'   => array( 'type' => 'color', 'label' => __( 'گرادیانت — از رنگ', 'nova' ), 'default' => '#2563eb' ),
			'gradient_to'     => array( 'type' => 'color', 'label' => __( 'گرادیانت — تا رنگ', 'nova' ), 'default' => '#7c3aed' ),
			'gradient_angle'  => array( 'type' => 'number', 'label' => __( 'زاویه گرادیانت (deg)', 'nova' ), 'default' => 135, 'min' => 0, 'max' => 360 ),
			'gradient_targets'=> array(
				'type'    => 'checkboxes',
				'label'   => __( 'اجزایی که گرادیانت بگیرند', 'nova' ),
				'options' => array(
					'header'   => __( 'هدر', 'nova' ),
					'footer'   => __( 'فوتر', 'nova' ),
					'buttons'  => __( 'دکمه‌ها', 'nova' ),
					'widgets'  => __( 'ابزارک‌ها', 'nova' ),
					'cards'    => __( 'کارت‌ها', 'nova' ),
					'titles'   => __( 'تیترها', 'nova' ),
					'story'    => __( 'استوری', 'nova' ),
					'badges'   => __( 'برچسب‌ها', 'nova' ),
				),
				'default' => array( 'buttons' ),
			),

			'section:type' => array( 'type' => 'heading', 'label' => __( 'تایپوگرافی و چیدمان', 'nova' ) ),
			'font_body'    => array( 'type' => 'font', 'label' => __( 'فونت متن', 'nova' ) ),
			'font_heading' => array( 'type' => 'font', 'label' => __( 'فونت تیترها', 'nova' ) ),
			'font_size'    => array( 'type' => 'number', 'label' => __( 'اندازه پایه فونت (px)', 'nova' ), 'default' => 16, 'min' => 12, 'max' => 22 ),
			'font_cdn'     => array( 'type' => 'text', 'label' => __( 'آدرس CDN فونت (اختیاری)', 'nova' ), 'placeholder' => 'https://cdn.example.com' ),
			'container'    => array( 'type' => 'number', 'label' => __( 'عرض محتوا (px)', 'nova' ), 'default' => 1200, 'min' => 960, 'max' => 1600 ),
			'layout'       => array( 'type' => 'select', 'label' => __( 'نوع چیدمان', 'nova' ), 'options' => array( 'boxed' => __( 'محصور (Boxed)', 'nova' ), 'wide' => __( 'تمام‌عرض', 'nova' ) ), 'default' => 'boxed' ),
			'radius'       => array( 'type' => 'number', 'label' => __( 'شعاع گوشه‌ها (px)', 'nova' ), 'default' => 14, 'min' => 0, 'max' => 40 ),

			'section:fonts' => array( 'type' => 'heading', 'label' => __( 'فونت سفارشی', 'nova' ) ),
			'font_faces'   => array( 'type' => 'font_faces', 'label' => __( 'بارگذاری فونت دلخواه', 'nova' ), 'group' => 'global' ),
			'custom_css'   => array( 'type' => 'css', 'label' => __( 'CSS سفارشی', 'nova' ) ),

			'section:seo' => array( 'type' => 'heading', 'label' => __( 'سئو و بهینه‌سازی', 'nova' ) ),
			'seo_meta'        => array( 'type' => 'toggle', 'label' => __( 'متا تگ‌های سئو (description / OpenGraph)', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'schema'          => array( 'type' => 'toggle', 'label' => __( 'داده ساختاریافته JSON-LD', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'trim_bloat'      => array( 'type' => 'toggle', 'label' => __( 'حذف کدهای اضافه وردپرس (emoji، generator)', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'trim_block_css'  => array( 'type' => 'toggle', 'label' => __( 'حذف CSS بلاک‌ها در صفحاتی که استفاده نشده', 'nova' ), 'default' => 0, 'group' => 'feat' ),
			'editor_style'    => array( 'type' => 'toggle', 'label' => __( 'استایل ادیتور مطابق سایت', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'dark_mode'       => array( 'type' => 'toggle', 'label' => __( 'حالت شب (Dark Mode)', 'nova' ), 'default' => 0, 'group' => 'feat' ),

			'section:social' => array( 'type' => 'heading', 'label' => __( 'شبکه‌های اجتماعی', 'nova' ) ),
			'social'         => array( 'type' => 'social', 'label' => __( 'لینک شبکه‌های اجتماعی', 'nova' ), 'group' => 'global' ),
		);

		$header = array(
			'section:struct' => array( 'type' => 'heading', 'label' => __( 'ساختار هدر', 'nova' ) ),
			'header_layout'  => array(
				'type'  => 'header_builder',
				'label' => __( 'چیدمان هدر', 'nova' ),
				'group' => 'layout',
				'panels'=> $header_panels,
			),
			'section:menu' => array( 'type' => 'heading', 'label' => __( 'منو', 'nova' ) ),
			'menumode'     => array( 'type' => 'select', 'label' => __( 'رفتار منوی موبایل', 'nova' ), 'options' => array( 'drawer' => __( 'کشویی', 'nova' ), 'fullscreen' => __( 'تمام‌صفحه', 'nova' ) ), 'default' => 'drawer' ),
			'search_mode'  => array( 'type' => 'select', 'label' => __( 'نوع جستجو', 'nova' ), 'options' => array( 'popup' => __( 'پاپ‌آپ با پیشنهاد زنده', 'nova' ), 'inline' => __( 'بازشوی درجا', 'nova' ) ), 'default' => 'popup' ),
			'sticky_logo'  => array( 'type' => 'media', 'label' => __( 'لوگوی حالت چسبان (اختیاری)', 'nova' ) ),
		);

		$footer = array(
			'section:struct' => array( 'type' => 'heading', 'label' => __( 'ساختار فوتر', 'nova' ) ),
			'footer_layout'  => array( 'type' => 'footer_builder', 'label' => __( 'ستون‌های فوتر', 'nova' ), 'group' => 'layout' ),
			'section:extra'  => array( 'type' => 'heading', 'label' => __( 'افزوده‌ها', 'nova' ) ),
			'back_to_top'    => array( 'type' => 'toggle', 'label' => __( 'دکمه بازگشت به بالا', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'payment_icons'  => array( 'type' => 'toggle', 'label' => __( 'نمایش نمادهای پرداخت و اعتماد', 'nova' ), 'default' => 1, 'group' => 'feat' ),
		);

		$woo = array(
			'section:product'    => array( 'type' => 'heading', 'label' => __( 'صفحه محصول', 'nova' ) ),
			'product_gallery_pos' => array( 'type' => 'select', 'label' => __( 'جایگاه گالری', 'nova' ), 'options' => array( 'right' => __( 'راست (تصویر) + چپ (توضیحات)', 'nova' ), 'left' => __( 'چپ (تصویر) + راست (توضیحات)', 'nova' ), 'top' => __( 'بالا — تمام‌عرض', 'nova' ), 'sticky' => __( 'چسبان هنگام اسکرول', 'nova' ) ), 'default' => 'left' ),
			'product_tabs'        => array( 'type' => 'select', 'label' => __( 'نمایش توضیحات', 'nova' ), 'options' => array( 'tabs' => __( 'تب‌ها', 'nova' ), 'sections' => __( 'بخش‌های باز', 'nova' ), 'accordion' => __( 'آکاردئون', 'nova' ) ), 'default' => 'tabs' ),
			'product_show'        => array(
				'type'    => 'checkboxes',
				'label'   => __( 'اجزای صفحه تک محصول', 'nova' ),
				'options' => array(
					'attrs'    => __( 'نمایش ویژگی‌ها', 'nova' ),
					'reviews'  => __( 'نمایش نظرات', 'nova' ),
					'related'  => __( 'محصولات مرتبط', 'nova' ),
					'upsell'   => __( 'محصولات پیشنهادی', 'nova' ),
					'360'      => __( 'تصاویر ۳۶۰ درجه / سه‌بعدی', 'nova' ),
					'notify'   => __( 'فرم «در صورت موجودی مرا مطلع کن»', 'nova' ),
					'stickybar'=> __( 'نوار خرید چسبان موبایل', 'nova' ),
					'video'    => __( 'تب ویدئو محصول', 'nova' ),
					'customtab'=> __( 'تب محتوای اختصاصی (از ویرایشگر)', 'nova' ),
				),
				'default' => array( 'attrs', 'reviews', 'related', '360', 'notify' ),
			),
			'product_editor'      => array( 'type' => 'toggle', 'label' => __( 'ویرایشگر حرفه‌ای توضیحات (جدول و ویژگی)', 'nova' ), 'default' => 1, 'group' => 'feat' ),

			'section:shop'       => array( 'type' => 'heading', 'label' => __( 'صفحه فروشگاه', 'nova' ) ),
			'shop_layout'        => array( 'type' => 'select', 'label' => __( 'چیدمان کالاها', 'nova' ), 'options' => array( 'equal' => __( 'کارت‌های هم‌اندازه (Equal height)', 'nova' ), 'masonry' => __( 'Masonry', 'nova' ) ), 'default' => 'equal' ),
			'shop_columns'       => array( 'type' => 'number', 'label' => __( 'تعداد ستون دسکتاپ', 'nova' ), 'default' => 4, 'min' => 2, 'max' => 6 ),
			'shop_card_widgets'  => array(
				'type'    => 'checkboxes',
				'label'   => __( 'اجزای کارت محصول (مدیریت ویجت‌ها)', 'nova' ),
				'options' => array(
					'badge'      => __( 'برچسب تخفیف/جدید', 'nova' ),
					'rating'     => __( 'امتیاز ستاره', 'nova' ),
					'price'      => __( 'قیمت', 'nova' ),
					'cart'       => __( 'آیکون افزودن به سبد', 'nova' ),
					'wishlist'   => __( 'آیکون علاقه‌مندی', 'nova' ),
					'compare'    => __( 'آیکون مقایسه', 'nova' ),
					'quickview'  => __( 'نمایش سریع', 'nova' ),
					'stock'      => __( 'وضعیت موجودی', 'nova' ),
					'brand'      => __( 'برند', 'nova' ),
				),
				'default' => array( 'badge', 'rating', 'price', 'cart', 'wishlist', 'compare', 'quickview' ),
			),
			'shop_filters'       => array(
				'type'    => 'checkboxes',
				'label'   => __( 'فیلترهای فروشگاه', 'nova' ),
				'options' => array(
					'cat'     => __( 'دسته‌بندی', 'nova' ),
					'price'   => __( 'بازه قیمت', 'nova' ),
					'attr'    => __( 'ویژگی‌ها', 'nova' ),
					'stock'   => __( 'موجودی', 'nova' ),
					'rating'  => __( 'امتیاز', 'nova' ),
					'brand'   => __( 'برند', 'nova' ),
				),
				'default' => array( 'cat', 'price', 'attr', 'stock' ),
			),
			'shop_ajax'          => array( 'type' => 'toggle', 'label' => __( 'فیلتر و صفحه‌بندی Ajax', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'shop_sidebar'       => array( 'type' => 'select', 'label' => __( 'ستون کنار فروشگاه', 'nova' ), 'options' => array( 'right' => __( 'راست', 'nova' ), 'left' => __( 'چپ', 'nova' ), 'none' => __( 'بدون ستون', 'nova' ) ), 'default' => 'right' ),

			'section:extras'     => array( 'type' => 'heading', 'label' => __( 'امکانات تکمیلی', 'nova' ) ),
			'wishlist'           => array( 'type' => 'toggle', 'label' => __( 'لیست علاقه‌مندی‌ها', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'compare'            => array( 'type' => 'toggle', 'label' => __( 'مقایسه محصولات', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'quickview'          => array( 'type' => 'toggle', 'label' => __( 'نمایش سریع', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'notify_stock'       => array( 'type' => 'toggle', 'label' => __( '«مرا مطلع کن»', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'wishlist_page'      => array( 'type' => 'page', 'label' => __( 'برگه علاقه‌مندی‌ها', 'nova' ) ),
			'compare_page'       => array( 'type' => 'page', 'label' => __( 'برگه مقایسه', 'nova' ) ),
		);

		$modules = array(
			'section:modules' => array( 'type' => 'heading', 'label' => __( 'ماژول‌های قالب', 'nova' ) ),
			'mod_portfolio'  => array( 'type' => 'toggle', 'label' => __( 'نمونه‌کارها (Portfolio)', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'mod_story'      => array( 'type' => 'toggle', 'label' => __( 'استوری', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'mod_ads'        => array( 'type' => 'toggle', 'label' => __( 'تبلیغات', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'mod_slider'     => array( 'type' => 'toggle', 'label' => __( 'اسلایدر', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'mod_player'     => array( 'type' => 'toggle', 'label' => __( 'پلیر ویدئو/صوت', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'mod_ajax'       => array( 'type' => 'toggle', 'label' => __( 'درخواست‌های Ajax', 'nova' ), 'default' => 1, 'group' => 'feat' ),
			'player_speed'   => array( 'type' => 'toggle', 'label' => __( 'کنترل سرعت پخش', 'nova' ), 'default' => 1, 'group' => 'feat' ),

			'section:pwa'    => array( 'type' => 'heading', 'label' => __( 'اپلیکیشن موبایل (PWA)', 'nova' ) ),
			'pwa_enabled'    => array( 'type' => 'toggle', 'label' => __( 'فعال‌سازی PWA و نصب روی موبایل', 'nova' ), 'default' => 0, 'group' => 'feat' ),
		);

		$map = array(
			'nova'        => $design,
			'nova-wizard' => array(),
			'nova-header' => $header,
			'nova-footer' => $footer,
			'nova-woo'    => $woo,
			'nova-modules' => $modules,
			'nova-cpt'    => array(),
		);

		return $map[ $page ] ?? array();
	}

	/* -------------------------------------------------------------- render */

	public static function tabs() {
		$tabs = array(
			'nova'         => __( 'داشبورد و طراحی', 'nova' ),
			'nova-wizard'  => __( 'راه‌اندازی', 'nova' ),
			'nova-header'  => __( 'هدرساز', 'nova' ),
			'nova-footer'  => __( 'فوترساز', 'nova' ),
			'nova-modules' => __( 'ماژول‌ها', 'nova' ),
			'nova-cpt'     => __( 'پست تایپ و اپ', 'nova' ),
		);
		if ( Nova_Modes::has( 'shop' ) ) {
			$tabs['nova-woo'] = __( 'فروشگاه', 'nova' );
		}
		return $tabs;
	}

	public static function render() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'nova' ) );
		}
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'nova'; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<div class="wrap nv-admin">
			<h1 class="nv-admin__title">
				<span class="nv-admin__logo">N</span>
				<?php esc_html_e( 'پنل قالب Nova', 'nova' ); ?>
				<span class="nv-admin__ver">v<?php echo esc_html( NOVA_VERSION ); ?></span>
			</h1>

			<nav class="nv-tabs">
				<?php foreach ( self::tabs() as $slug => $label ) : ?>
					<a class="nv-tab <?php echo $slug === $page ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'تنظیمات ذخیره شد.', 'nova' ); ?></p></div>
			<?php endif; ?>

			<?php if ( 'nova' === $page ) { self::overview(); } ?>

			<div class="nv-panel">
				<?php
				if ( 'nova-wizard' === $page ) {
					self::view_wizard();
				} elseif ( 'nova-cpt' === $page ) {
					self::view_cpt();
				} else {
					self::view_form( $page, self::schema( $page ) );
				}
				?>
			</div>
		</div>
		<?php
	}

	/** Small dashboard so the first screen is not a wall of fields. */
	private static function overview() {
		$modes  = Nova_Modes::keys();
		$reg    = Nova_Modes::registry();
		$labels = array();
		foreach ( $modes as $m ) {
			$labels[] = $reg[ $m ]['label'] ?? $m;
		}
		?>
		<div class="nv-overview">
			<div class="nv-overview__card">
				<h3><?php esc_html_e( 'حالت فعال سایت', 'nova' ); ?></h3>
				<p><?php echo $labels ? esc_html( implode( ' + ', $labels ) ) : esc_html__( 'هنوز انتخاب نشده', 'nova' ); ?></p>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=nova-wizard' ) ); ?>"><?php esc_html_e( 'تغییر حالت‌ها', 'nova' ); ?></a>
			</div>
			<div class="nv-overview__card">
				<h3><?php esc_html_e( 'وضعیت سیستم', 'nova' ); ?></h3>
				<ul class="nv-overview__list">
					<li><?php echo esc_html( sprintf( __( 'WooCommerce: %s', 'nova' ), class_exists( 'WooCommerce' ) ? __( 'نصب شده', 'nova' ) : __( 'نصب نیست', 'nova' ) ) ); ?></li>
					<li><?php echo esc_html( sprintf( __( 'Elementor: %s', 'nova' ), did_action( 'elementor/loaded' ) ? __( 'نصب شده', 'nova' ) : __( 'نصب نیست', 'nova' ) ) ); ?></li>
					<li><?php echo esc_html( sprintf( __( 'زبان سایت: %s', 'nova' ), is_rtl() ? __( 'راست‌به‌چپ', 'nova' ) : __( 'چپ‌به‌راست', 'nova' ) ) ); ?></li>
					<li><?php echo esc_html( sprintf( __( 'PHP: %s', 'nova' ), PHP_VERSION ) ); ?></li>
				</ul>
			</div>
			<div class="nv-overview__card">
				<h3><?php esc_html_e( 'میانبرهای سریع', 'nova' ); ?></h3>
				<p>
					<a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php esc_html_e( 'منوها و مگامنو', 'nova' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>"><?php esc_html_e( 'ابزارک‌ها', 'nova' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"><?php esc_html_e( 'سفارشی‌سازی', 'nova' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/** Generic form renderer driven by the schema. */
	private static function view_form( $page, $schema ) {
		$layout = get_option( Nova_Modes::OPTION_LAYOUT, array() );
		$feat   = get_option( Nova_Modes::OPTION_OPT, array() );
		$social = get_option( 'nova_social', array() );
		$faces  = get_option( 'nova_font_faces', array() );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="nova_save">
			<input type="hidden" name="nova_page" value="<?php echo esc_attr( $page ); ?>">
			<?php wp_nonce_field( 'nova_save_' . $page ); ?>

			<div class="nv-grid">
			<?php
			foreach ( $schema as $key => $field ) {
				if ( 0 === strpos( $key, 'section:' ) ) {
					if ( 'section:colors' !== $key ) {
						echo '</div><div class="nv-grid">';
					}
					printf( '<h2 class="nv-section">%s</h2>', esc_html( $field['label'] ) );
					continue;
				}

				$group = $field['group'] ?? 'layout';
				$store = 'feat' === $group ? $feat : ( 'global' === $group ? null : $layout );
				$value = null;
				if ( 'global' === $group ) {
					$value = 'social' === $key ? $social : ( 'font_faces' === $key ? $faces : '' );
				} else {
					$value = isset( $store[ $key ] ) ? $store[ $key ] : ( $field['default'] ?? '' );
				}
				echo '<div class="nv-field nv-field--' . esc_attr( $field['type'] ) . '">';
				printf( '<label class="nv-label">%s</label>', esc_html( $field['label'] ) );
				self::control( $key, $field, $value );
				echo '</div>';
			}
			?>
			</div>

			<p class="nv-submit">
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'ذخیره تنظیمات', 'nova' ); ?></button>
			</p>
		</form>
		<?php
	}

	/** One field control. */
	private static function control( $key, $field, $value ) {
		$name = $key;
		switch ( $field['type'] ) {
			case 'color':
				printf( '<input type="text" class="nv-color" name="%1$s" value="%2$s" data-default-color="%3$s">', esc_attr( $name ), esc_attr( $value ), esc_attr( $field['default'] ?? '' ) );
				break;

			case 'number':
				printf(
					'<input type="number" name="%1$s" value="%2$s" min="%3$s" max="%4$s" class="small-text">',
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $field['min'] ?? 0 ),
					esc_attr( $field['max'] ?? 9999 )
				);
				break;

			case 'text':
				printf( '<input type="text" name="%1$s" value="%2$s" class="regular-text" placeholder="%3$s">', esc_attr( $name ), esc_attr( $value ), esc_attr( $field['placeholder'] ?? '' ) );
				break;

			case 'css':
				printf( '<textarea name="%1$s" rows="12" class="large-text code" spellcheck="false" dir="ltr">%2$s</textarea>', esc_attr( $name ), esc_textarea( $value ) );
				break;

			case 'toggle':
				printf(
					'<label class="nv-switch"><input type="checkbox" name="%1$s" value="1" %2$s><span class="nv-switch__ui"></span><span class="nv-switch__txt">%3$s</span></label>',
					esc_attr( $name ),
					checked( (int) $value, 1, false ),
					esc_html__( 'فعال', 'nova' )
				);
				break;

			case 'select':
				printf( '<select name="%s">', esc_attr( $name ) );
				foreach ( $field['options'] as $opt => $label ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $opt ), selected( $value, $opt, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;

			case 'checkboxes':
				$value = is_array( $value ) ? $value : array();
				echo '<div class="nv-chips">';
				foreach ( $field['options'] as $opt => $label ) {
					printf(
						'<label class="nv-chip"><input type="checkbox" name="%1$s[]" value="%2$s" %3$s><span>%4$s</span></label>',
						esc_attr( $name ),
						esc_attr( $opt ),
						checked( in_array( $opt, $value, true ), true, false ),
						esc_html( $label )
					);
				}
				echo '</div>';
				break;

			case 'media':
				printf(
					'<span class="nv-media"><input type="text" name="%1$s" value="%2$s" class="regular-text nv-media__url" dir="ltr"><button type="button" class="button nv-media__pick">%3$s</button><button type="button" class="button-link nv-media__clear">%4$s</button></span>',
					esc_attr( $name ),
					esc_attr( $value ),
					esc_html__( 'انتخاب', 'nova' ),
					esc_html__( 'حذف', 'nova' )
				);
				break;

			case 'page':
				wp_dropdown_pages(
					array(
						'name'             => $name,
						'selected'         => (int) $value,
						'show_option_none' => __( '— انتخاب کنید —', 'nova' ),
						'option_none_value' => 0,
					)
				);
				break;

			case 'social':
				$value = is_array( $value ) ? $value : array();
				echo '<div class="nv-social-grid">';
				foreach ( nova_social_networks() as $net => $label ) {
					printf(
						'<label class="nv-social-row"><span class="nv-social-ico">%1$s</span><span class="nv-social-name">%2$s</span><input type="text" dir="ltr" name="social[%3$s]" value="%4$s" placeholder="https://"></label>',
						nova_social_icon( $net ), // phpcs:ignore WordPress.Security.EscapeOutput
						esc_html( $label ),
						esc_attr( $net ),
						esc_attr( $value[ $net ] ?? '' )
					);
				}
				echo '</div>';
				break;

			case 'font':
				$faces = get_option( 'nova_font_faces', array() );
				$opts  = array( '' => __( '— پیش‌فرض قالب —', 'nova' ) );
				foreach ( (array) $faces as $face ) {
					if ( ! empty( $face['family'] ) ) {
						$opts[ $face['family'] ] = $face['family'];
					}
				}
				echo '<select name="' . esc_attr( $name ) . '">';
				foreach ( $opts as $opt => $label ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $opt ), selected( $value, $opt, false ), esc_html( $label ) );
				}
				echo '</select>';
				echo '<p class="description">' . esc_html__( 'فونت را در بخش «فونت سفارشی» اضافه کنید.', 'nova' ) . '</p>';
				break;

			case 'font_faces':
				$value = is_array( $value ) ? $value : array();
				echo '<div class="nv-repeater nv-field--repeater" data-repeater="font">';
				echo '<div class="nv-repeater__rows">';
				foreach ( $value as $i => $face ) {
					self::font_face_row( $i, $face );
				}
				echo '</div>';
				echo '<button type="button" class="button nv-repeater__add">' . esc_html__( '+ افزودن فونت', 'nova' ) . '</button>';
				echo '<template class="nv-repeater__tpl">';
				self::font_face_row( '__i__', array() );
				echo '</template></div>';
				break;

			case 'header_builder':
				$value = get_option( Nova_Modes::OPTION_LAYOUT, array() );
				$value = is_array( $value ) && ! empty( $value['left'] ) ? $value : array(
					'sticky' => 1, 'transparent' => 0,
					'topbar' => array( 'text', 'social' ),
					'left' => array( 'logo' ), 'center' => array( 'menu' ), 'right' => array( 'search', 'account', 'cart' ),
				);
				$rows = array(
					'topbar' => __( 'نوار بالایی', 'nova' ),
					'left'   => __( 'سمت چپ', 'nova' ),
					'center' => __( 'وسط', 'nova' ),
					'right'  => __( 'سمت راست', 'nova' ),
				);
				echo '<div class="nv-field nv-field--builder"><div class="nv-builder">';
				foreach ( $rows as $row => $row_label ) {
					$current = (array) ( $value[ $row ] ?? array() );
					printf( '<div class="nv-builder__row"><div class="nv-builder__head">%s</div><div class="nv-builder__body">', esc_html( $row_label ) );
					foreach ( $field['panels'] as $panel => $panel_label ) {
						printf(
							'<label class="nv-piece"><input type="checkbox" name="header_layout[%1$s][]" value="%2$s" %3$s><span>%4$s</span></label>',
							esc_attr( $row ),
							esc_attr( $panel ),
							checked( in_array( $panel, $current, true ), true, false ),
							esc_html( $panel_label )
						);
					}
					echo '</div></div>';
				}
				echo '</div>';
				printf(
					'<div class="nv-inline"><label class="nv-switch"><input type="checkbox" name="header_layout[sticky]" value="1" %1$s><span class="nv-switch__ui"></span><span class="nv-switch__txt">%2$s</span></label>',
					checked( (int) ( $value['sticky'] ?? 1 ), 1, false ),
					esc_html__( 'هدر چسبان', 'nova' )
				);
				printf(
					'<label class="nv-switch"><input type="checkbox" name="header_layout[transparent]" value="1" %1$s><span class="nv-switch__ui"></span><span class="nv-switch__txt">%2$s</span></label></div>',
					checked( (int) ( $value['transparent'] ?? 0 ), 1, false ),
					esc_html__( 'هدر شفاف روی اسلایدر', 'nova' )
				);
				echo '</div>';
				break;

			case 'footer_builder':
				$layout  = Nova_Footer::layout();
				$columns = (array) $layout['columns'];
				$types   = array(
					'logo'       => __( 'لوگو + توضیح سایت', 'nova' ),
					'widgets'    => __( 'ناحیه ابزارک', 'nova' ),
					'menu'       => __( 'منو', 'nova' ),
					'social'     => __( 'شبکه‌های اجتماعی', 'nova' ),
					'html'       => __( 'HTML سفارشی', 'nova' ),
					'newsletter' => __( 'فرم/شورتکد', 'nova' ),
				);
				echo '<div class="nv-repeater nv-field--repeater" data-repeater="footer"><div class="nv-repeater__rows">';
				foreach ( $columns as $i => $col ) {
					self::footer_row( $i, $col, $types );
				}
				echo '</div><button type="button" class="button nv-repeater__add">' . esc_html__( '+ افزودن ستون', 'nova' ) . '</button>';
				echo '<template class="nv-repeater__tpl">';
				self::footer_row( '__i__', array(), $types );
				echo '</template></div>';
				echo '<p class="nv-field"><label class="nv-label">' . esc_html__( 'استایل فوتر', 'nova' ) . '</label><select name="footer_layout[style]">';
				foreach ( array( 'dark' => __( 'تیره', 'nova' ), 'light' => __( 'روشن', 'nova' ), 'gradient' => __( 'گرادیانت', 'nova' ) ) as $k => $v ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $k ), selected( $layout['style'], $k, false ), esc_html( $v ) );
				}
				echo '</select></p>';
				printf(
					'<p class="nv-field"><label class="nv-label">%1$s</label><input class="large-text" type="text" name="footer_layout[bottom]" value="%2$s" placeholder="%3$s"></p>',
					esc_html__( 'متن کپی‌رایت پایین فوتر', 'nova' ),
					esc_attr( $layout['bottom'] ),
					esc_attr__( '© 2025 — تمام حقوق محفوظ است.', 'nova' )
				);
				break;
		}
	}

	private static function footer_row( $i, $col, $types ) {
		$col = wp_parse_args(
			(array) $col,
			array( 'width' => 3, 'type' => 'widgets', 'title' => '', 'area' => 'footer-1', 'value' => '' )
		);
		?>
		<div class="nv-rep-row">
			<span class="nv-rep-handle">⋮⋮</span>
			<label><?php esc_html_e( 'عرض', 'nova' ); ?> <input type="number" min="1" max="12" name="footer_layout[columns][<?php echo esc_attr( $i ); ?>][width]" value="<?php echo esc_attr( $col['width'] ); ?>"></label>
			<label><?php esc_html_e( 'نوع', 'nova' ); ?>
				<select name="footer_layout[columns][<?php echo esc_attr( $i ); ?>][type]">
					<?php foreach ( $types as $k => $v ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $col['type'], $k ); ?>><?php echo esc_html( $v ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label><?php esc_html_e( 'عنوان', 'nova' ); ?> <input type="text" name="footer_layout[columns][<?php echo esc_attr( $i ); ?>][title]" value="<?php echo esc_attr( $col['title'] ); ?>"></label>
			<label><?php esc_html_e( 'ناحیه/شناسه', 'nova' ); ?> <input type="text" dir="ltr" name="footer_layout[columns][<?php echo esc_attr( $i ); ?>][area]" value="<?php echo esc_attr( $col['area'] ); ?>"></label>
			<label class="nv-rep-wide"><?php esc_html_e( 'محتوا (HTML/شورتکد)', 'nova' ); ?> <textarea rows="2" name="footer_layout[columns][<?php echo esc_attr( $i ); ?>][value]"><?php echo esc_textarea( $col['value'] ); ?></textarea></label>
			<button type="button" class="button-link nv-rep-del" aria-label="<?php esc_attr_e( 'حذف', 'nova' ); ?>">✕</button>
		</div>
		<?php
	}

	private static function font_face_row( $i, $face ) {
		$face = wp_parse_args( (array) $face, array( 'family' => '', 'src' => '', 'weight' => '400', 'format' => 'woff2' ) );
		?>
		<div class="nv-rep-row">
			<label><?php esc_html_e( 'نام فونت', 'nova' ); ?> <input type="text" dir="ltr" name="font_faces[<?php echo esc_attr( $i ); ?>][family]" value="<?php echo esc_attr( $face['family'] ); ?>" placeholder="Vazirmatn"></label>
			<label><?php esc_html_e( 'وزن', 'nova' ); ?> <input type="text" dir="ltr" name="font_faces[<?php echo esc_attr( $i ); ?>][weight]" value="<?php echo esc_attr( $face['weight'] ); ?>" placeholder="400"></label>
			<label><?php esc_html_e( 'فرمت', 'nova' ); ?>
				<select name="font_faces[<?php echo esc_attr( $i ); ?>][format]">
					<?php foreach ( array( 'woff2', 'woff', 'truetype', 'opentype' ) as $f ) : ?>
						<option value="<?php echo esc_attr( $f ); ?>" <?php selected( $face['format'], $f ); ?>><?php echo esc_html( $f ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="nv-rep-wide"><?php esc_html_e( 'آدرس فایل', 'nova' ); ?>
				<span class="nv-media"><input type="text" dir="ltr" class="nv-media__url" name="font_faces[<?php echo esc_attr( $i ); ?>][src]" value="<?php echo esc_attr( $face['src'] ); ?>"><button type="button" class="button nv-media__pick"><?php esc_html_e( 'انتخاب', 'nova' ); ?></button></span>
			</label>
			<button type="button" class="button-link nv-rep-del" aria-label="<?php esc_attr_e( 'حذف', 'nova' ); ?>">✕</button>
		</div>
		<?php
	}

	/* -------------------------------------------------------------- wizard */

	private static function view_wizard() {
		$active = Nova_Modes::keys();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="nova_save">
			<input type="hidden" name="nova_page" value="nova-wizard">
			<?php wp_nonce_field( 'nova_save_nova-wizard' ); ?>

			<h2 class="nv-section"><?php esc_html_e( 'قالب را برای چه نوع سایتی می‌خواهید؟', 'nova' ); ?></h2>
			<p class="nv-hint"><?php esc_html_e( 'می‌توانید چند گزینه را همزمان انتخاب کنید. فقط کدها و امکانات همان بخش‌ها بارگذاری می‌شود؛ بقیه غیرفعال می‌ماند.', 'nova' ); ?></p>

			<div class="nv-modes">
				<?php foreach ( Nova_Modes::registry() as $key => $mode ) : ?>
					<label class="nv-mode <?php echo in_array( $key, $active, true ) ? 'is-on' : ''; ?>">
						<input type="checkbox" name="nova_modes[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $active, true ) ); ?>>
						<span class="nv-mode__box">
							<span class="nv-mode__ico"><?php echo Nova_Menus::icon_svg( $mode['icon'] ?? 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<strong><?php echo esc_html( $mode['label'] ); ?></strong>
							<em><?php echo esc_html( $mode['desc'] ); ?></em>
						</span>
					</label>
				<?php endforeach; ?>
			</div>

			<h2 class="nv-section"><?php esc_html_e( 'تنظیمات اولیه', 'nova' ); ?></h2>
			<div class="nv-grid">
				<div class="nv-field">
					<label class="nv-label"><?php esc_html_e( 'رنگ اصلی', 'nova' ); ?></label>
					<input type="text" class="nv-color" name="color_primary" data-default-color="#2563eb" value="<?php echo esc_attr( Nova_Modes::get_option( 'color_primary', '#2563eb' ) ); ?>">
				</div>
				<div class="nv-field">
					<label class="nv-label"><?php esc_html_e( 'رنگ مکمل', 'nova' ); ?></label>
					<input type="text" class="nv-color" name="color_secondary" data-default-color="#7c3aed" value="<?php echo esc_attr( Nova_Modes::get_option( 'color_secondary', '#7c3aed' ) ); ?>">
				</div>
			</div>

			<p class="nv-submit">
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'فعال‌سازی حالت‌ها', 'nova' ); ?></button>
			</p>
		</form>
		<?php
	}

	/* ----------------------------------------------------------- CPT + app */

	private static function view_cpt() {
		$types = get_option( 'nova_custom_types', array() );
		$types = is_array( $types ) ? $types : array();
		$pwa   = (array) get_option( 'nova_pwa', array() );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="nova_save">
			<input type="hidden" name="nova_page" value="nova-cpt">
			<?php wp_nonce_field( 'nova_save_nova-cpt' ); ?>

			<h2 class="nv-section"><?php esc_html_e( 'ساخت پست تایپ سفارشی', 'nova' ); ?></h2>
			<p class="nv-hint"><?php esc_html_e( 'بدون نیاز به افزونه، پست تایپ و تاکسونومی بسازید. بعد از ذخیره، منوی آن در پیشخوان ظاهر می‌شود؛ برای اعمال بازنویسی پیوندها یک‌بار «تنظیمات ← پیوندهای یکتا ← ذخیره» را بزنید.', 'nova' ); ?></p>

			<div class="nv-repeater nv-field--repeater" data-repeater="cpt">
				<div class="nv-repeater__rows">
					<?php foreach ( $types as $i => $type ) : ?>
						<?php self::cpt_row( $i, $type ); ?>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button nv-repeater__add"><?php esc_html_e( '+ افزودن پست تایپ', 'nova' ); ?></button>
				<template class="nv-repeater__tpl"><?php self::cpt_row( '__i__', array() ); ?></template>
			</div>

			<h2 class="nv-section"><?php esc_html_e( 'اپلیکیشن موبایل (PWA)', 'nova' ); ?></h2>
			<div class="nv-grid">
				<div class="nv-field">
					<label class="nv-label"><?php esc_html_e( 'نام اپ', 'nova' ); ?></label>
					<input type="text" name="pwa[name]" value="<?php echo esc_attr( $pwa['name'] ?? get_bloginfo( 'name' ) ); ?>">
				</div>
				<div class="nv-field">
					<label class="nv-label"><?php esc_html_e( 'نام کوتاه (زیر آیکون)', 'nova' ); ?></label>
					<input type="text" name="pwa[short_name]" value="<?php echo esc_attr( $pwa['short_name'] ?? mb_substr( get_bloginfo( 'name' ), 0, 12 ) ); ?>">
				</div>
				<div class="nv-field">
					<label class="nv-label"><?php esc_html_e( 'رنگ تم', 'nova' ); ?></label>
					<input type="text" class="nv-color" name="pwa[theme_color]" data-default-color="#2563eb" value="<?php echo esc_attr( $pwa['theme_color'] ?? Nova_Modes::get_option( 'color_primary', '#2563eb' ) ); ?>">
				</div>
				<div class="nv-field">
					<label class="nv-label"><?php esc_html_e( 'آیکون ۵۱۲×۵۱۲', 'nova' ); ?></label>
					<span class="nv-media"><input type="text" class="nv-media__url" name="pwa[icon512]" value="<?php echo esc_attr( $pwa['icon512'] ?? '' ); ?>"><button type="button" class="button nv-media__pick"><?php esc_html_e( 'انتخاب', 'nova' ); ?></button></span>
				</div>
			</div>

			<p class="nv-submit">
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'ذخیره', 'nova' ); ?></button>
			</p>
		</form>

		<div class="nv-card-app">
			<h3><?php esc_html_e( 'ساخت نسخه اندروید', 'nova' ); ?></h3>
			<p><?php esc_html_e( 'با یک کلیک، بسته‌ی پروژه اپلیکیشن (Capacitor) ساخته و دانلود می‌شود؛ آماده‌ی ساخت فایل APK/AAB.', 'nova' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="nova_app_export">
				<?php wp_nonce_field( 'nova_app_export' ); ?>
				<button class="button button-primary"><?php esc_html_e( 'دانلود بسته اپلیکیشن', 'nova' ); ?></button>
			</form>
		</div>
		<?php
	}

	private static function cpt_row( $i, $type ) {
		$type = wp_parse_args(
			(array) $type,
			array( 'key' => '', 'single' => '', 'plural' => '', 'slug' => '', 'icon' => 'dashicons-admin-post', 'archive' => 1, 'public' => 1, 'taxonomies' => array(), 'supports' => array( 'title', 'editor', 'thumbnail' ) )
		);
		$supports = array(
			'title'       => __( 'عنوان', 'nova' ),
			'editor'      => __( 'ویرایشگر', 'nova' ),
			'thumbnail'   => __( 'تصویر شاخص', 'nova' ),
			'excerpt'     => __( 'خلاصه', 'nova' ),
			'comments'    => __( 'نظرات', 'nova' ),
			'custom-fields' => __( 'فیلد سفارشی', 'nova' ),
			'elementor'   => __( 'المنتور', 'nova' ),
		);
		?>
		<div class="nv-rep-row nv-rep-row--cpt">
			<label><?php esc_html_e( 'کلید (en)', 'nova' ); ?> <input dir="ltr" type="text" name="cpt[<?php echo esc_attr( $i ); ?>][key]" value="<?php echo esc_attr( $type['key'] ); ?>" placeholder="book"></label>
			<label><?php esc_html_e( 'نام مفرد', 'nova' ); ?> <input type="text" name="cpt[<?php echo esc_attr( $i ); ?>][single]" value="<?php echo esc_attr( $type['single'] ); ?>" placeholder="کتاب"></label>
			<label><?php esc_html_e( 'نام جمع', 'nova' ); ?> <input type="text" name="cpt[<?php echo esc_attr( $i ); ?>][plural]" value="<?php echo esc_attr( $type['plural'] ); ?>" placeholder="کتاب‌ها"></label>
			<label><?php esc_html_e( 'اسلاگ', 'nova' ); ?> <input dir="ltr" type="text" name="cpt[<?php echo esc_attr( $i ); ?>][slug]" value="<?php echo esc_attr( $type['slug'] ); ?>" placeholder="books"></label>
			<label><?php esc_html_e( 'آیکون', 'nova' ); ?> <input dir="ltr" type="text" name="cpt[<?php echo esc_attr( $i ); ?>][icon]" value="<?php echo esc_attr( $type['icon'] ); ?>"></label>
			<label><?php esc_html_e( 'تاکسونومی‌ها (با کاما)', 'nova' ); ?> <input dir="ltr" type="text" name="cpt[<?php echo esc_attr( $i ); ?>][taxonomies_csv]" value="<?php echo esc_attr( implode( ',', (array) $type['taxonomies'] ) ); ?>" placeholder="book_cat,book_tag"></label>
			<div class="nv-rep-checks">
				<?php foreach ( $supports as $k => $label ) : ?>
					<label class="nv-chip nv-chip--sm"><input type="checkbox" name="cpt[<?php echo esc_attr( $i ); ?>][supports][]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, (array) $type['supports'], true ) ); ?>><span><?php echo esc_html( $label ); ?></span></label>
				<?php endforeach; ?>
				<label class="nv-chip nv-chip--sm"><input type="checkbox" name="cpt[<?php echo esc_attr( $i ); ?>][archive]" value="1" <?php checked( (int) $type['archive'], 1 ); ?>><span><?php esc_html_e( 'آرشیو', 'nova' ); ?></span></label>
				<label class="nv-chip nv-chip--sm"><input type="checkbox" name="cpt[<?php echo esc_attr( $i ); ?>][public]" value="1" <?php checked( (int) $type['public'], 1 ); ?>><span><?php esc_html_e( 'عمومی', 'nova' ); ?></span></label>
			</div>
			<button type="button" class="button-link nv-rep-del" aria-label="<?php esc_attr_e( 'حذف', 'nova' ); ?>">✕</button>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- save */

	public static function save() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'nova' ), 403 );
		}
		$page = isset( $_POST['nova_page'] ) ? sanitize_key( $_POST['nova_page'] ) : '';
		check_admin_referer( 'nova_save_' . $page );

		$layout = get_option( Nova_Modes::OPTION_LAYOUT, array() );
		$feat   = get_option( Nova_Modes::OPTION_OPT, array() );
		$layout = is_array( $layout ) ? $layout : array();
		$feat   = is_array( $feat ) ? $feat : array();

		// Wizard: modes + first colours.
		if ( 'nova-wizard' === $page ) {
			$modes = isset( $_POST['nova_modes'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['nova_modes'] ) ) : array();
			$valid = array_keys( Nova_Modes::registry() );
			update_option( Nova_Modes::OPTION_KEYS, array_values( array_intersect( $modes, $valid ) ) );
			update_option( Nova_Modes::OPTION_WIZ, 1 );
			foreach ( array( 'color_primary', 'color_secondary' ) as $c ) {
				if ( ! empty( $_POST[ $c ] ) ) {
					$layout[ $c ] = sanitize_hex_color( wp_unslash( $_POST[ $c ] ) );
				}
			}
			update_option( Nova_Modes::OPTION_LAYOUT, $layout );
			flush_rewrite_rules();

			// Create the pages the shop mode needs.
			if ( in_array( 'shop', $modes, true ) && class_exists( 'WooCommerce' ) ) {
				self::create_page( 'wishlist', __( 'علاقه‌مندی‌ها', 'nova' ), '[nova_wishlist]' );
				self::create_page( 'compare', __( 'مقایسه محصولات', 'nova' ), '[nova_compare]' );
			}
			wp_safe_redirect( add_query_arg( array( 'page' => 'nova', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// CPT / PWA page.
		if ( 'nova-cpt' === $page ) {
			$rows  = isset( $_POST['cpt'] ) ? (array) wp_unslash( $_POST['cpt'] ) : array();
			$types = array();
			foreach ( $rows as $row ) {
				$key = isset( $row['key'] ) ? sanitize_key( $row['key'] ) : '';
				if ( ! $key || in_array( $key, array( 'post', 'page', 'attachment', 'nova_ad', 'nova_story', 'nova_portfolio' ), true ) ) {
					continue;
				}
				$types[] = array(
					'key'        => $key,
					'single'     => sanitize_text_field( $row['single'] ?? $key ),
					'plural'     => sanitize_text_field( $row['plural'] ?? $key ),
					'slug'       => sanitize_title( $row['slug'] ?? $key ),
					'icon'       => sanitize_text_field( $row['icon'] ?? 'dashicons-admin-post' ),
					'public'     => empty( $row['public'] ) ? 0 : 1,
					'archive'    => empty( $row['archive'] ) ? 0 : 1,
					'supports'   => array_map( 'sanitize_key', (array) ( $row['supports'] ?? array() ) ),
					'taxonomies' => array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', (string) ( $row['taxonomies_csv'] ?? '' ) ) ) ) ),
				);
			}
			update_option( 'nova_custom_types', $types );

			if ( isset( $_POST['pwa'] ) ) {
				$pwa = array();
				foreach ( (array) wp_unslash( $_POST['pwa'] ) as $k => $v ) {
					$pwa[ sanitize_key( $k ) ] = 'theme_color' === $k ? sanitize_hex_color( $v ) : sanitize_text_field( $v );
				}
				update_option( 'nova_pwa', $pwa );
				if ( ! empty( $pwa['icon512'] ) && is_numeric( $pwa['icon512'] ) ) {
					update_option( 'nova_pwa_icon_512', (int) $pwa['icon512'] );
				}
			}
			update_option( 'nova_sw_version', (string) time() );
			flush_rewrite_rules();
			self::redirect( $page );
		}

		// Generic schema pages.
		$schema = self::schema( $page );
		foreach ( $schema as $key => $field ) {
			if ( 0 === strpos( $key, 'section:' ) ) {
				continue;
			}
			$group = $field['group'] ?? 'layout';
			$type  = $field['type'];

			// Repeaters and builders write their own structure.
			if ( in_array( $type, array( 'header_builder', 'footer_builder' ), true ) ) {
				continue;
			}

			if ( 'global' === $group ) {
				if ( 'social' === $type ) {
					$social = array();
					foreach ( (array) ( $_POST['social'] ?? array() ) as $net => $url ) {
						$social[ sanitize_key( $net ) ] = esc_url_raw( wp_unslash( $url ) );
					}
					update_option( 'nova_social', $social );
				} elseif ( 'font_faces' === $type ) {
					$faces = array();
					foreach ( (array) ( $_POST['font_faces'] ?? array() ) as $face ) {
						if ( empty( $face['family'] ) || empty( $face['src'] ) ) {
							continue;
						}
						$faces[] = array(
							'family' => sanitize_text_field( $face['family'] ),
							'src'    => esc_url_raw( $face['src'] ),
							'weight' => sanitize_text_field( $face['weight'] ?? '400' ),
							'format' => sanitize_text_field( $face['format'] ?? 'woff2' ),
						);
					}
					update_option( 'nova_font_faces', $faces );
				}
				continue;
			}

			$raw = $_POST[ $key ] ?? null;
			$value = self::sanitize( $type, $raw, $field );

			if ( 'feat' === $group ) {
				$feat[ $key ] = $value;
			} else {
				$layout[ $key ] = $value;
			}
		}

		// Header builder.
		if ( 'nova-header' === $page && isset( $_POST['header_layout'] ) ) {
			$in     = (array) wp_unslash( $_POST['header_layout'] );
			$panels = array_keys( Nova_Header::panels() );
			$clean  = array(
				'sticky'      => empty( $in['sticky'] ) ? 0 : 1,
				'transparent' => empty( $in['transparent'] ) ? 0 : 1,
			);
			foreach ( array( 'topbar', 'left', 'center', 'right' ) as $row ) {
				$clean[ $row ] = array_values( array_intersect( array_map( 'sanitize_key', (array) ( $in[ $row ] ?? array() ) ), $panels ) );
			}
			$layout['header_layout'] = $clean;
		}

		// Footer builder.
		if ( 'nova-footer' === $page && isset( $_POST['footer_layout'] ) ) {
			$in   = (array) wp_unslash( $_POST['footer_layout'] );
			$cols = array();
			foreach ( (array) ( $in['columns'] ?? array() ) as $col ) {
				$cols[] = array(
					'width' => max( 1, min( 12, (int) ( $col['width'] ?? 3 ) ) ),
					'type'  => sanitize_key( $col['type'] ?? 'widgets' ),
					'title' => sanitize_text_field( $col['title'] ?? '' ),
					'area'  => sanitize_key( $col['area'] ?? 'footer-1' ),
					'value' => wp_kses_post( $col['value'] ?? '' ),
				);
			}
			$layout['footer_layout'] = array(
				'style'   => sanitize_key( $in['style'] ?? 'dark' ),
				'columns' => $cols,
				'bottom'  => sanitize_text_field( $in['bottom'] ?? '' ),
				'payment' => 1,
			);
		}

		update_option( Nova_Modes::OPTION_LAYOUT, $layout );
		update_option( Nova_Modes::OPTION_OPT, $feat );
		self::redirect( $page );
	}

	private static function redirect( $page ) {
		wp_safe_redirect( add_query_arg( array( 'page' => $page, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function create_page( $slug, $title, $content ) {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			return $existing->ID;
		}
		$id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);
		update_option( 'nova_' . $slug . '_page', $id );
		return $id;
	}

	/** Sanitise a single value by field type. */
	private static function sanitize( $type, $raw, $field ) {
		switch ( $type ) {
			case 'color':
				return sanitize_hex_color( (string) $raw ) ?: ( $field['default'] ?? '' );
			case 'number':
				return (int) $raw;
			case 'toggle':
				return empty( $raw ) ? 0 : 1;
			case 'css':
				// Strip anything that could execute; CSS only.
				$css = wp_strip_all_tags( (string) $raw );
				return preg_replace( '#</?\w+#', '', $css );
			case 'checkboxes':
				$valid = array_keys( $field['options'] );
				return array_values( array_intersect( array_map( 'sanitize_key', (array) $raw ), $valid ) );
			case 'select':
				return isset( $field['options'][ $raw ] ) ? sanitize_key( $raw ) : ( $field['default'] ?? '' );
			case 'media':
				return esc_url_raw( (string) $raw );
			case 'page':
				return absint( $raw );
			case 'font':
				return sanitize_text_field( (string) $raw );
			default:
				return sanitize_text_field( (string) $raw );
		}
	}
}
