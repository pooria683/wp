<?php
/**
 * Mega menu walker + menu icons + search/account panels support.
 *
 * Mega menu is driven by nav-menu-item meta set in the admin (Appearance → Menus):
 *   _nova_mega      = 1 to enable a mega panel on a top-level item
 *   _nova_icon      = Dashicon class or an SVG/icon name from the theme icon set
 *   _nova_badge     = badge text
 *   _nova_badge_clr = badge colour
 *   _nova_mega_cols = number of columns (2..5)
 * The panel content itself is a widget area: "Mega — {menu item title}", created
 * on demand so the site owner just drops widgets inside it.
 *
 * @package Nova
 */

defined( 'ABSPATH' ) || exit;

class Nova_Menus {

	public static function init() {
		add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'decorate' ), 10, 2 );
		add_filter( 'nav_menu_item_title', array( __CLASS__, 'title' ), 10, 4 );
		add_filter( 'walker_nav_menu_start_el', array( __CLASS__, 'after_item' ), 10, 4 );
		add_action( 'wp_update_nav_menu_item', array( __CLASS__, 'save_meta' ), 10, 2 );
		add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, 'fields' ), 10, 4 );
		add_action( 'init', array( __CLASS__, 'register_mega_sidebars' ), 20 );
	}

	/** Icon set (inline SVG path data keeps things dependency free). */
	public static function icons() {
		return array(
			'home'    => 'M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z',
			'cart'    => 'M4 5h2l2.2 9.2A2 2 0 0 0 10.1 16h7.5a2 2 0 0 0 2-1.6L21 8H7',
			'user'    => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0',
			'star'    => 'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z',
			'heart'   => 'M12 20s-7-4.5-7-9.2A4 4 0 0 1 12 7a4 4 0 0 1 7 3.8C19 15.5 12 20 12 20Z',
			'grid'    => 'M4 4h7v7H4zm9 0h7v7h-7zM4 13h7v7H4zm9 0h7v7h-7z',
			'tag'     => 'M3 12 12 3h9v9l-9 9z',
			'phone'   => 'M6 3h4l2 5-3 2a11 11 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A18 18 0 0 1 4 5a2 2 0 0 1 2-2Z',
			'mail'    => 'M3 6h18v12H3zM3 6l9 7 9-7',
			'search'  => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm10 2-4.5-4.5',
			'chevron' => 'M9 6l6 6-6 6',
		);
	}

	public static function icon_svg( $name, $class = 'nv-ico' ) {
		$icons = self::icons();
		if ( ! isset( $icons[ $name ] ) ) {
			// Allow a dashicon class directly.
			if ( 0 === strpos( $name, 'dashicons-' ) ) {
				return '<span class="dashicons ' . esc_attr( $name ) . '" aria-hidden="true"></span>';
			}
			return '';
		}
		return sprintf(
			'<svg class="%1$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="%2$s"/></svg>',
			esc_attr( $class ),
			esc_attr( $icons[ $name ] )
		);
	}

	/** Attach meta to menu items once. */
	public static function decorate( $items, $args ) {
		foreach ( $items as $item ) {
			$item->nova_mega     = (bool) get_post_meta( $item->ID, '_nova_mega', true );
			$item->nova_icon     = (string) get_post_meta( $item->ID, '_nova_icon', true );
			$item->nova_badge    = (string) get_post_meta( $item->ID, '_nova_badge', true );
			$item->nova_badge_cl = (string) get_post_meta( $item->ID, '_nova_badge_clr', true );
			$item->nova_sidebar  = self::sidebar_id( $item );
		}
		return $items;
	}

	/** Prefix icons/badges to titles. */
	public static function title( $title, $item, $args, $depth ) {
		$out = '';
		if ( ! empty( $item->nova_icon ) ) {
			$out .= self::icon_svg( $item->nova_icon, 'nv-ico nv-menu-ico' );
		}
		$out .= '<span class="nv-menu-label">' . $title . '</span>';
		if ( ! empty( $item->nova_badge ) ) {
			$style = $item->nova_badge_cl ? ' style="background:' . esc_attr( $item->nova_badge_cl ) . '"' : '';
			$out  .= '<span class="nv-menu-badge"' . $style . '>' . esc_html( $item->nova_badge ) . '</span>';
		}
		return $out;
	}

	/** Inject the mega panel markup after the `<a>` of a top-level item. */
	public static function after_item( $item_output, $item, $depth, $args ) {
		if ( 0 !== $depth || empty( $item->nova_mega ) ) {
			return $item_output;
		}
		$id = self::sidebar_id( $item );
		if ( ! is_active_sidebar( $id ) ) {
			return $item_output;
		}
		$item_output .= '<span class="nv-mega-toggle" aria-hidden="true">' . self::icon_svg( 'chevron' ) . '</span>';
		$item_output .= '<div class="nv-mega" role="group" aria-label="' . esc_attr( wp_strip_all_tags( $item->title ) ) . '"><div class="nv-container"><div class="nv-mega__grid">';
		ob_start();
		dynamic_sidebar( $id );
		$item_output .= ob_get_clean();
		$item_output .= '</div></div></div>';
		return $item_output;
	}

	/** Deterministic widget-area id for a menu item. */
	public static function sidebar_id( $item ) {
		return 'nova-mega-' . (int) $item->ID;
	}

	/** Register a widget area for every mega-enabled item. */
	public static function register_mega_sidebars() {
		$locations = get_nav_menu_locations();
		if ( ! $locations ) {
			return;
		}
		$seen = array();
		foreach ( $locations as $loc ) {
			$items = wp_get_nav_menu_items( $loc );
			foreach ( (array) $items as $item ) {
				if ( empty( $item->ID ) || isset( $seen[ $item->ID ] ) ) {
					continue;
				}
				$seen[ $item->ID ] = true;
				if ( ! get_post_meta( $item->ID, '_nova_mega', true ) ) {
					continue;
				}
				register_sidebar(
					array(
						'id'            => self::sidebar_id( $item ),
						/* translators: %s: menu item title */
						'name'          => sprintf( __( 'مگامنو — %s', 'nova' ), wp_strip_all_tags( $item->title ) ),
						'description'   => __( 'ابزارک‌های این ناحیه داخل مگامنوی این آیتم نمایش داده می‌شوند.', 'nova' ),
						'before_widget' => '<div id="%1$s" class="nv-mega__col %2$s">',
						'after_widget'  => '</div>',
						'before_title'  => '<h4 class="nv-mega__title">',
						'after_title'   => '</h4>',
					)
				);
			}
		}
	}

	/** Admin fields inside Appearance → Menus. */
	public static function fields( $item_id, $item, $depth, $args ) {
		$mega  = get_post_meta( $item_id, '_nova_mega', true );
		$icon  = get_post_meta( $item_id, '_nova_icon', true );
		$badge = get_post_meta( $item_id, '_nova_badge', true );
		$clr   = get_post_meta( $item_id, '_nova_badge_clr', true );
		$nonce = wp_create_nonce( 'nova_menu_meta' );
		?>
		<input type="hidden" name="nova_menu_nonce" value="<?php echo esc_attr( $nonce ); ?>">
		<p class="nv-field">
			<label for="nova-icon-<?php echo esc_attr( $item_id ); ?>"><strong><?php esc_html_e( 'آیکون منو', 'nova' ); ?></strong></label>
			<select id="nova-icon-<?php echo esc_attr( $item_id ); ?>" name="nova_icon[<?php echo esc_attr( $item_id ); ?>]">
				<option value=""><?php esc_html_e( '— بدون آیکون —', 'nova' ); ?></option>
				<?php
				foreach ( self::icons() as $key => $path ) {
					printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $key ), selected( $icon, $key, false ) );
				}
				?>
			</select>
		</p>
		<p class="nv-field">
			<label for="nova-badge-<?php echo esc_attr( $item_id ); ?>"><strong><?php esc_html_e( 'برچسب (Badge)', 'nova' ); ?></strong></label>
			<input type="text" id="nova-badge-<?php echo esc_attr( $item_id ); ?>" name="nova_badge[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $badge ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'مثلاً: جدید', 'nova' ); ?>">
			<input type="text" name="nova_badge_clr[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $clr ); ?>" class="widefat" placeholder="#e11d48" style="margin-top:4px">
		</p>
		<?php if ( 0 === (int) $depth ) : ?>
		<p class="nv-field">
			<label>
				<input type="checkbox" name="nova_mega[<?php echo esc_attr( $item_id ); ?>]" value="1" <?php checked( $mega, '1' ); ?>>
				<strong><?php esc_html_e( 'فعال‌سازی مگامنو', 'nova' ); ?></strong>
			</label>
			<br><small><?php esc_html_e( 'بعد از ذخیره، یک ناحیه ابزارک با نام «مگامنو — عنوان آیتم» در ابتداکننده‌ها ساخته می‌شود.', 'nova' ); ?></small>
		</p>
		<?php endif; ?>
		<?php
	}

	/** Persist meta. */
	public static function save_meta( $menu_id, $menu_item_db_id ) {
		if ( ! isset( $_POST['nova_menu_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nova_menu_nonce'] ), 'nova_menu_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		$id = (int) $menu_item_db_id;

		update_post_meta( $id, '_nova_mega', isset( $_POST['nova_mega'][ $id ] ) ? '1' : '' );

		$icon = isset( $_POST['nova_icon'][ $id ] ) ? sanitize_text_field( wp_unslash( $_POST['nova_icon'][ $id ] ) ) : '';
		$icon = isset( self::icons()[ $icon ] ) ? $icon : '';
		update_post_meta( $id, '_nova_icon', $icon );

		update_post_meta( $id, '_nova_badge', isset( $_POST['nova_badge'][ $id ] ) ? sanitize_text_field( wp_unslash( $_POST['nova_badge'][ $id ] ) ) : '' );
		$clr = isset( $_POST['nova_badge_clr'][ $id ] ) ? sanitize_hex_color( wp_unslash( $_POST['nova_badge_clr'][ $id ] ) ) : '';
		update_post_meta( $id, '_nova_badge_clr', $clr );
	}
}
