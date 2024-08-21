<?php
/**
 * Class to add top header pages for Wbcom plugins and additional features.
 *
 * @author   Wbcom Designs
 * @package  Bp_Add_Group_Types
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wbcom_Admin_Settings' ) ) {

	/**
	 * Class to add Wbcom plugin's admin settings.
	 *
	 * @since 2.0.0
	 */
	class Wbcom_Admin_Settings {

		/**
		 * Wbcom_Admin_Settings Constructor.
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			add_shortcode( 'wbcom_admin_setting_header', array( $this, 'wbcom_admin_setting_header_html' ) );
			add_action( 'admin_menu', array( $this, 'wbcom_admin_additional_pages' ), 999 );
			add_action( 'admin_enqueue_scripts', array( $this, 'wbcom_enqueue_admin_scripts' ) );
			add_action( 'wp_ajax_wbcom_addons_cards', array( $this, 'wbcom_addons_cards_links' ) );
		}

		/**
		 * Callback function for handling extension cards via AJAX.
		 *
		 * @since 2.0.0
		 */
		public function wbcom_addons_cards_links() {
			check_ajax_referer( 'wbcom_admin_setting_nonce', 'nonce' );

			$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
			if ( 'wbcom_addons_cards' === $action ) {
				$display_extension = isset( $_POST['display_extension'] ) ? sanitize_text_field( wp_unslash( $_POST['display_extension'] ) ) : '';
				echo esc_html( $display_extension );
				wp_die();
			}
		}

		/**
		 * Upgrade the specified plugin.
		 *
		 * @since 2.0.0
		 * @param string $plugin_slug The plugin slug.
		 * @return mixed The result of the upgrade process.
		 */
		public function upgrade_plugin( $plugin_slug ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			wp_cache_flush();

			$upgrader = new Plugin_Upgrader();
			return $upgrader->upgrade( $plugin_slug );
		}

		/**
		 * Get the WordPress repository download URL for a plugin.
		 *
		 * @since 2.0.0
		 * @param string $slug The plugin slug.
		 * @return string The download URL.
		 */
		public function get_download_url( $slug ) {
			return $this->get_wp_repo_download_url( $slug );
		}

		/**
		 * Fetch the WordPress repository download URL for a plugin.
		 *
		 * @since 2.0.0
		 * @param string $slug The plugin slug.
		 * @return string The download URL.
		 */
		public function get_wp_repo_download_url( $slug ) {
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array( 'sections' => false ),
				)
			);

			if ( is_wp_error( $api ) ) {
				wp_send_json_error( array( 'error' => $api->get_error_message() ) );
			}

			return $api->download_link;
		}

		/**
		 * Check if a plugin is installed.
		 *
		 * @since 2.0.0
		 * @param string $slug The plugin slug.
		 * @return bool True if the plugin is installed, false otherwise.
		 */
		public function wbcom_is_plugin_installed( $slug ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$all_plugins = get_plugins();
			foreach ( array_keys( $all_plugins ) as $key ) {
				if ( preg_match( '|^' . $slug . '/|', $key ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Get the status of a plugin (activated, installed, not installed).
		 *
		 * @since 2.0.0
		 * @param string $slug The plugin slug.
		 * @return string The plugin status.
		 */
		public function wbcom_plugin_status( $slug ) {
			if ( $this->wbcom_is_plugin_installed( $slug ) ) {
				return $this->wbcom_is_plugin_active( $slug ) ? 'activated' : 'installed';
			}
			return 'not_installed';
		}

		/**
		 * Check if a plugin is activated.
		 *
		 * @since 2.0.0
		 * @param string $slug The plugin slug.
		 * @return bool True if the plugin is activated, false otherwise.
		 */
		public function wbcom_is_plugin_active( $slug ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$all_plugins = get_plugins();
			foreach ( array_keys( $all_plugins ) as $key ) {
				if ( preg_match( '|^' . $slug . '/|', $key ) ) {
					if ( is_plugin_active( $key ) ) {
						return true;
					}
				}
			}
			return false;
		}

		/**
		 * Enqueue JS & CSS related to Wbcom plugins.
		 *
		 * @since 2.0.0
		 */
		public function wbcom_enqueue_admin_scripts() {
			if ( ! wp_style_is( 'font-awesome', 'enqueued' ) ) {
				wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css' );
			}

			if ( ! wp_script_is( 'wbcom_admin_setting_js', 'enqueued' ) ) {
				wp_register_script(
					'wbcom_admin_setting_js',
					BPTODO_PLUGIN_URL . 'admin/wbcom/assets/js/wbcom-admin-setting.js',
					array( 'jquery' ),
					time(),
					false
				);
				wp_localize_script(
					'wbcom_admin_setting_js',
					'wbcom_plugin_installer_params',
					array(
						'ajax_url'        => admin_url( 'admin-ajax.php' ),
						'activate_text'   => esc_html__( 'Activate', 'wb-todo' ),
						'deactivate_text' => esc_html__( 'Deactivate', 'wb-todo' ),
						'nonce'           => wp_create_nonce( 'wbcom_admin_setting_nonce' ),
					)
				);
				wp_enqueue_script( 'wbcom_admin_setting_js' );
			}

			if ( ! wp_style_is( 'wbcom-admin-setting-css', 'enqueued' ) ) {
				wp_enqueue_style( 'wbcom-admin-setting-css', BPTODO_PLUGIN_URL . 'admin/wbcom/assets/css/wbcom-admin-setting.css' );
			}
		}

		/**
		 * Add additional pages to the plugin's admin panel.
		 *
		 * @since 2.0.0
		 */
		public function wbcom_admin_additional_pages() {
			add_submenu_page(
				'wbcomplugins',
				esc_html__( 'Our Plugins', 'wb-todo' ),
				esc_html__( 'Our Plugins', 'wb-todo' ),
				'manage_options',
				'wbcom-plugins-page',
				array( $this, 'wbcom_plugins_submenu_page_callback' )
			);
			add_submenu_page(
				'wbcomplugins',
				esc_html__( 'Our Themes', 'wb-todo' ),
				esc_html__( 'Our Themes', 'wb-todo' ),
				'manage_options',
				'wbcom-themes-page',
				array( $this, 'wbcom_themes_submenu_page_callback' )
			);
			add_submenu_page(
				'wbcomplugins',
				esc_html__( 'Support', 'wb-todo' ),
				esc_html__( 'Support', 'wb-todo' ),
				'manage_options',
				'wbcom-support-page',
				array( $this, 'wbcom_support_submenu_page_callback' )
			);
		}

		/**
		 * Include the Wbcom plugins list page.
		 *
		 * @since 2.0.0
		 */
		public function wbcom_plugins_submenu_page_callback() {
			include 'templates/wbcom-plugins-page.php';
		}

		/**
		 * Include the themes list page.
		 *
		 * @since 2.0.0
		 */
		public function wbcom_themes_submenu_page_callback() {
			include 'templates/wbcom-themes-page.php';
		}

		/**
		 * Include the support page.
		 *
		 * @since 2.0.0
		 */
		public function wbcom_support_submenu_page_callback() {
			include 'templates/wbcom-support-page.php';
		}

		/**
		 * Shortcode to display the admin panel header.
		 *
		 * @since 2.0.0
		 */
		public function wbcom_admin_setting_header_html() {
			$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

			if ( ! $page ) {
				// Fallback to the default settings page for the User To-Do List plugin
				$page = 'user-todo-list-settings';
			}

			$plugin_active = $theme_active = $support_active = $settings_active = '';

			switch ( $page ) {
				case 'wbcom-plugins-page':
					$plugin_active = 'is_active';
					break;
				case 'wbcom-support-page':
					$support_active = 'is_active';
					break;
				case 'wbcom-license-page':
					$license_active = 'is_active';
					break;
				default:
					$settings_active = 'is_active';
			}
			?>
			<div id="wb_admin_header" class="wp-clearfix">
				<nav id="wb_admin_nav">
					<ul>
						<li class="wb_admin_nav_item <?php echo esc_attr( $settings_active ); ?>">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcomplugins' ) ); ?>" id="wb_admin_nav_trigger_settings">
								<i class="fa fa-sliders" aria-hidden="true"></i>
								<h4><?php esc_html_e( 'Settings', 'wb-todo' ); ?></h4>
							</a>
						</li>
						<li class="wb_admin_nav_item <?php echo esc_attr( $plugin_active ); ?>">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-plugins-page' ) ); ?>" id="wb_admin_nav_trigger_extensions">
								<i class="fa fa-th" aria-hidden="true"></i>
								<h4><?php esc_html_e( 'Themes & Extensions', 'wb-todo' ); ?></h4>
							</a>
						</li>
						<li class="wb_admin_nav_item <?php echo esc_attr( $support_active ); ?>">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-support-page' ) ); ?>" id="wb_admin_nav_trigger_support">
								<i class="fa fa-question-circle" aria-hidden="true"></i>
								<h4><?php esc_html_e( 'Help & Support', 'wb-todo' ); ?></h4>
							</a>
						</li>
						<?php do_action( 'wbcom_add_header_menu' ); ?>
					</ul>
				</nav>
			</div>
			<?php
		}
	}

	/**
	 * Instantiate the Wbcom_Admin_Settings class.
	 *
	 * @return void
	 */
	function instantiate_wbcom_plugin_manager() {
		new Wbcom_Admin_Settings();
	}

	instantiate_wbcom_plugin_manager();
}
