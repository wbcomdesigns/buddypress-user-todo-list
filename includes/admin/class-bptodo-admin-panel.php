<?php
/**
 * Bptodo card-panel admin: menu, enqueue, page rendering.
 *
 * Replaces the legacy Wbcom wrapper chrome (admin/wbcom/). Follows the
 * wp-plugin-development skill Part 6 card-panel pattern and the
 * references/wbcom-wrapper-migration.md playbook (Parts 5, 6, 16).
 *
 * This controller owns ONLY the new admin surface (menu, enqueue,
 * notice suppression, page render). The legacy Bptodo_Admin class is
 * retained for its non-UI logic (settings registration, sanitizers,
 * the custom General-tab save handler, frontend asset enqueue).
 *
 * @package bp-user-todo-list
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bptodo_Admin_Panel' ) ) {

	/**
	 * Class Bptodo_Admin_Panel
	 *
	 * @since 3.6.0
	 */
	class Bptodo_Admin_Panel {

		/**
		 * Menu slug (kept identical to legacy so bookmarks still work).
		 */
		const MENU_SLUG = 'user-todo-list-settings';

		/**
		 * Sidebar tabs rendered inside the one admin page.
		 *
		 * Single source of truth for the sidebar nav. Grouped into
		 * main + settings + resources by the shell.
		 *
		 * @return array<string, array{label:string, icon:string, group:string}>
		 */
		public static function get_tabs() {
			$tabs = array(
				'overview'   => array(
					'label' => __( 'Overview', 'wb-todo' ),
					'icon'  => 'dashicons-chart-bar',
					'group' => 'main',
				),
				'general'    => array(
					'label' => __( 'General', 'wb-todo' ),
					'icon'  => 'dashicons-admin-generic',
					'group' => 'settings',
				),
				'group'      => array(
					'label' => __( 'Group Todo', 'wb-todo' ),
					'icon'  => 'dashicons-groups',
					'group' => 'settings',
				),
				'retention'  => array(
					'label' => __( 'Data Retention', 'wb-todo' ),
					'icon'  => 'dashicons-database',
					'group' => 'settings',
				),
				'shortcodes' => array(
					'label' => __( 'Shortcodes', 'wb-todo' ),
					'icon'  => 'dashicons-shortcode',
					'group' => 'settings',
				),
			);
			return apply_filters( 'bptodo_admin_tabs', $tabs );
		}

		/**
		 * Bootstrap hooks.
		 */
		public function register(): void {
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			// Priority 999 runs after every other plugin has registered its
			// admin_menu entries. Used to reclaim the hub landing render when
			// a legacy wbcom-wrapper plugin was first to register the
			// wbcomplugins top-level menu. See migration playbook Part 16.
			add_action( 'admin_menu', array( $this, 'takeover_hub_landing' ), 999 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'in_admin_header', array( $this, 'suppress_foreign_notices' ), 1 );
		}

		/**
		 * Attach as a single submenu under the shared WB Plugins hub.
		 */
		public function add_menu(): void {
			$cap = apply_filters( 'bptodo_admin_capability', 'manage_options' );

			// First Wbcom plugin to load creates the shared WB Plugins hub.
			// Whichever plugin wins the race provides the hub's landing
			// dashboard (a card grid of every Wbcom plugin attached to the
			// hub). Peer plugins are auto-discovered via
			// $GLOBALS['submenu']['wbcomplugins'].
			if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) ) {
				add_menu_page(
					esc_html__( 'WB Plugins', 'wb-todo' ),
					esc_html__( 'WB Plugins', 'wb-todo' ),
					$cap,
					'wbcomplugins',
					array( $this, 'render_hub' ),
					'dashicons-lightbulb',
					59
				);
			}

			add_submenu_page(
				'wbcomplugins',
				esc_html__( 'Todo for BuddyPress & BuddyBoss', 'wb-todo' ),
				esc_html__( 'Todo Lists', 'wb-todo' ),
				$cap,
				self::MENU_SLUG,
				array( $this, 'render_page' )
			);
		}

		/**
		 * Force the shared hub's landing page to render our clean card-panel
		 * dashboard, overriding whatever a legacy wbcom-wrapper plugin may
		 * have registered. See migration playbook Part 16.
		 *
		 * Mixed installs are the common case: a client site may have a couple
		 * of migrated plugins and many still on the old wrapper. Whoever wins
		 * the admin_menu race creates the wbcomplugins top-level and points
		 * its landing at their own dashboard. A legacy wrapper's dashboard
		 * shows generic Wbcom brand chrome, not the card grid clients expect.
		 *
		 * This runs at admin_menu priority 999 so every other registration is
		 * complete; we strip any existing callback on the hub's page action
		 * and install our render_hub. Migrated peer plugins run the same
		 * routine and overwrite each other harmlessly because the hub views
		 * are visually equivalent across plugins.
		 */
		public function takeover_hub_landing(): void {
			global $admin_page_hooks;
			if ( empty( $admin_page_hooks['wbcomplugins'] ) ) {
				return;
			}
			remove_all_actions( 'toplevel_page_wbcomplugins' );
			add_action( 'toplevel_page_wbcomplugins', array( $this, 'render_hub' ) );
		}

		/**
		 * Enqueue admin assets only on our screen + the shared hub landing.
		 *
		 * @param string $hook_suffix Current admin page hook.
		 */
		public function enqueue_assets( string $hook_suffix ): void {
			unset( $hook_suffix ); // We inspect the screen object instead.
			$screen = get_current_screen();
			if ( ! $screen || ! $this->is_our_screen( $screen ) ) {
				return;
			}

			$version = defined( 'BPTODO_VERSION' ) ? BPTODO_VERSION : '1.0.0';

			wp_enqueue_style(
				'bptodo-admin-panel',
				BPTODO_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				$version
			);

			wp_enqueue_script(
				'bptodo-admin-panel',
				BPTODO_PLUGIN_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				$version,
				true
			);

			// Multi-select on the General tab. Select2 already ships with the
			// plugin for the frontend; reuse it for the role picker instead of
			// the retired selectize dependency.
			wp_enqueue_style( 'select2-css', BPTODO_PLUGIN_URL . 'assets/css/select2.min.css', array(), $version );
			wp_enqueue_script( 'select2-js', BPTODO_PLUGIN_URL . 'assets/js/select2.full.min.js', array( 'jquery' ), $version, true );

			wp_localize_script(
				'bptodo-admin-panel',
				'bptodoAdmin',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'i18n'    => array(
						'saved'           => __( 'Settings saved.', 'wb-todo' ),
						'saveFailed'      => __( 'Could not save. Please try again.', 'wb-todo' ),
						'confirmDanger'   => __( 'Are you sure? This cannot be undone.', 'wb-todo' ),
						'confirmContinue' => __( 'Continue', 'wb-todo' ),
						'confirmCancel'   => __( 'Cancel', 'wb-todo' ),
						'rolePlaceholder' => __( 'Select user roles', 'wb-todo' ),
					),
				)
			);
		}

		/**
		 * Suppress 3rd-party admin notices on our screen.
		 */
		public function suppress_foreign_notices(): void {
			$screen = get_current_screen();
			if ( ! $screen || ! $this->is_our_screen( $screen ) ) {
				return;
			}
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}

		/**
		 * True if the current screen is the plugin's admin page OR the shared
		 * hub landing (our callback owns the hub render too, so our CSS/JS
		 * must load there).
		 *
		 * @param WP_Screen $screen Current screen.
		 */
		private function is_our_screen( $screen ): bool {
			if ( empty( $screen->id ) ) {
				return false;
			}
			return (bool) preg_match( '/_page_' . preg_quote( self::MENU_SLUG, '/' ) . '$/', $screen->id )
				|| 'toplevel_page_wbcomplugins' === $screen->id;
		}

		/**
		 * Render the shared WB Plugins hub landing page.
		 */
		public function render_hub(): void {
			$view = BPTODO_PLUGIN_PATH . 'includes/admin/views/hub.php';
			if ( file_exists( $view ) ) {
				include $view;
			}
		}

		/**
		 * Render the single admin page. Routes to the active tab.
		 *
		 * Each tab view is self-contained: the Overview is read-only, and the
		 * three settings tabs each own their existing save mechanism (General
		 * = custom self-POST handled by Bptodo_Admin::bptodo_save_general_settings;
		 * Group + Data Retention = Settings API via options.php). We preserve
		 * those forms 1:1 rather than collapsing them into one option group,
		 * so the option-save contract is byte-identical to the pre-3.6.0 UI.
		 */
		public function render_page(): void {
			$bptodo_tabs = self::get_tabs();
			$tab_slugs   = array_keys( $bptodo_tabs );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab routing.
			$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : $tab_slugs[0];

			// Back-compat: legacy tab slugs (?tab=user-todo-list-settings,
			// group-todo-list-settings, data-retention-settings, etc.) so old
			// bookmarks resolve to the right card-panel tab.
			$legacy_map = array(
				'user-todo-list-welcome'    => 'overview',
				'user-todo-list-settings'   => 'general',
				'group-todo-list-settings'  => 'group',
				'data-retention-settings'   => 'retention',
				'user-todo-list-shortcodes' => 'shortcodes',
			);
			if ( isset( $legacy_map[ $active ] ) ) {
				$active = $legacy_map[ $active ];
			}
			if ( ! isset( $bptodo_tabs[ $active ] ) ) {
				$active = $tab_slugs[0];
			}

			$page_url = admin_url( 'admin.php?page=' . self::MENU_SLUG );

			$view_map = array(
				'overview'   => 'overview',
				'general'    => 'settings-general',
				'group'      => 'settings-group',
				'retention'  => 'settings-retention',
				'shortcodes' => 'settings-shortcodes',
			);
			$view     = isset( $view_map[ $active ] ) ? $view_map[ $active ] : 'overview';

			// Surface the General-tab custom-save success banner in-panel.
			// That handler redirects with settings-updated=true and sets a
			// backup transient; register it as a settings error here (before
			// the shell calls settings_errors()) so it renders like the
			// options.php tabs' "Settings saved" notice.
			if ( 'general' === $active ) {
				$bptodo_saved = get_transient( 'bptodo_settings_saved' );
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only post-redirect flag.
				$bptodo_updated = isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) );
				if ( $bptodo_saved || $bptodo_updated ) {
					add_settings_error( 'general', 'bptodo_settings_saved', __( 'Settings saved.', 'wb-todo' ), 'updated' );
					if ( $bptodo_saved ) {
						delete_transient( 'bptodo_settings_saved' );
					}
				}
			}

			$view_path = BPTODO_PLUGIN_PATH . 'includes/admin/views/' . $view . '.php';
			$shell     = BPTODO_PLUGIN_PATH . 'includes/admin/views/shell.php';

			include $shell;
		}
	}
}
