<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bptodo_Admin' ) ) {

	/**
	 * Add admin page settings.
	 *
	 * @package bp-user-todo-list
	 * @author  wbcomdesigns
	 * @since   1.0.0
	 */
	class Bptodo_Admin {

		/**
		 * Plugin slug.
		 *
		 * @since 1.0.0
		 * @access private
		 * @var string $plugin_slug Contains plugin slug.
		 */
		private $plugin_slug = 'user-todo-list-settings';

		/**
		 * Setting tabs.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var array $plugin_settings_tabs Contains setting tabs.
		 */
		public $plugin_settings_tabs = array();

		/**
		 * Todo post type slug.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var string $post_type Contains plugin slug.
		 */
		public $post_type = 'bp-todo';

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// Actions and filters can be added here in future versions if needed.
		}

		/**
		 * Enqueue styles.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function enqueue_styles() {
			wp_enqueue_style( 'selectize', plugin_dir_url( __FILE__ ) . 'assets/css/selectize.css', array(), BPTODO_VERSION, 'all' );
		}

		/**
		 * Enqueue scripts.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function enqueue_scripts() {
			wp_enqueue_script( 'selectize', plugin_dir_url( __FILE__ ) . 'assets/js/selectize.min.js', array( 'jquery' ), BPTODO_VERSION, false );
			wp_enqueue_script( 'bp-todo-list-js', plugin_dir_url( __FILE__ ) . 'assets/js/bp-todo-list-admin.js', array( 'jquery' ), BPTODO_VERSION, false );
			
		
			
		}

		/**
		 * Enqueue frontend scripts and styles.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_variables() {
			global $bptodo, $post;

			$profile_menu_slug = $bptodo->profile_menu_slug;

			if (
				isset( $_SERVER['REQUEST_URI'] ) &&
				(
					strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $profile_menu_slug ) !== false ||
					isset( $post->post_type ) ||
					( isset( $post->post_content ) && ( has_shortcode( $post->post_content, 'bptodo_by_category' ) ||
					has_shortcode( $post->post_content, 'ld_dashboard' ) ) )
				)
			) {
				wp_enqueue_style( 'bptodo-css-ui', BPTODO_PLUGIN_URL . 'assets/css/jquery-ui.min.css', array(), BPTODO_VERSION );
				if ( ! wp_style_is( 'wb-font-awesome', 'enqueued' ) ) {
					wp_enqueue_style( 'wb-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css' );
				}

				if ( ! wp_script_is( 'jquery' ) ) {
					wp_enqueue_script( 'jquery' );
				}

				if ( ! wp_script_is( 'datepicker.min.js' ) ) {
					wp_enqueue_script( 'jquery-ui-datepicker' );
				}

				wp_enqueue_script( 'select2-js', BPTODO_PLUGIN_URL . 'assets/js/select2.full.min.js', array( 'jquery' ), BPTODO_VERSION, true );
				wp_enqueue_script( 'bptodo-js-front', BPTODO_PLUGIN_URL . 'assets/js/bptodo-front.js', array( 'jquery' ), BPTODO_VERSION, true );

				wp_localize_script(
					'bptodo-js-front',
					'todo_ajax_object',
					array(
						'ajax_url'            => admin_url( 'admin-ajax.php' ),
						'ajax_nonce'          => wp_create_nonce( 'bptodo-todo-nonce' ),
						/* translators: %1$s */
						'export_file_heading' => sprintf( esc_html__( 'My %1$s', 'wb-todo' ), esc_html( $bptodo->profile_menu_label_plural ) ),
						'required_cat_text'   => esc_html__( 'Category name is required.', 'wb-todo' ),
						'undo_todo_title'     => esc_html__( 'Undo Complete', 'wb-todo' ),
						'remove_todo_text'    => esc_html__( 'Are you sure?', 'wb-todo' ),
					)
				);
				wp_enqueue_style( 'select2-css', BPTODO_PLUGIN_URL . 'assets/css/select2.min.css', array(), BPTODO_VERSION );
				wp_enqueue_style( 'bptodo-front-css', BPTODO_PLUGIN_URL . 'assets/css/bptodo-front.css', array(), BPTODO_VERSION );
			}

			wp_enqueue_script( 'bptodo-js-tempust', BPTODO_PLUGIN_URL . 'assets/js/tempust.js', array( 'jquery' ), BPTODO_VERSION );
			wp_enqueue_script( 'bptodo-js-pdfobject', BPTODO_PLUGIN_URL . 'assets/js/pdfobject.min.js', array( 'jquery' ), BPTODO_VERSION );
		}

		/**
		 * Enqueue admin panel scripts and styles.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_admin_variables() {
			$screen = get_current_screen();
			if ( 'wb-plugins_page_user-todo-list-settings' === $screen->base ) {
				wp_enqueue_style( 'bptodo-css-admin', BPTODO_PLUGIN_URL . 'admin/assets/css/bptodo-admin.css', array() );
			}
		}

		/**
		 * Add menu pages for the plugin.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_add_menu_page() {
			if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) && class_exists( 'Buddypress' ) ) {
				add_menu_page( esc_html__( 'WB Plugins', 'wb-todo' ), esc_html__( 'WB Plugins', 'wb-todo' ), 'manage_options', 'wbcomplugins', array( $this, 'bptodo_admin_options_page' ), 'dashicons-lightbulb', 59 );
				add_submenu_page( 'wbcomplugins', esc_html__( 'General', 'wb-todo' ), esc_html__( 'General', 'wb-todo' ), 'manage_options', 'wbcomplugins' );
			}
			add_submenu_page( 'wbcomplugins', esc_html__( 'BP User To-Do List', 'wb-todo' ), esc_html__( 'BP User To-Do List', 'wb-todo' ), 'manage_options', 'user-todo-list-settings', array( $this, 'bptodo_admin_options_page' ) );
		}

		/**
		 * Display the plugin settings page content.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_admin_options_page() {
			$tab = filter_input(INPUT_GET, 'tab', FILTER_DEFAULT);

			if ($tab !== null) {
				// Sanitize the input by removing any HTML tags and trimming whitespace
				$tab = strip_tags(trim($tab));
			} else {
				// Default value if 'tab' parameter is not set
				$tab = 'user-todo-list-welcome';
			}

			?>
			<div class="wrap">
				<div class="wbcom-bb-plugins-offer-wrapper">
					<div id="wb_admin_logo">
						<a href="https://wbcomdesigns.com/downloads/buddypress-community-bundle/?utm_source=pluginoffernotice&utm_medium=community_banner" target="_blank">
							<img src="<?php echo esc_url( BPTODO_PLUGIN_URL ) . 'admin/wbcom/assets/imgs/wbcom-offer-notice.png'; ?>">
						</a>
					</div>
				</div>
				<div class="wbcom-wrap">
					<div class="blpro-header">
						<div class="wbcom_admin_header-wrapper">
							<div id="wb_admin_plugin_name">
								<?php esc_html_e( 'BuddyPress User To-Do List', 'wb-todo' ); ?>
								<?php	/* translators: %1$s */ ?>
								<span><?php printf( esc_html__( 'Version %s', 'wb-todo' ), esc_attr( BPTODO_VERSION ) ); ?></span>
							</div>
							<?php echo do_shortcode( '[wbcom_admin_setting_header]' ); ?>
						</div>
					</div>
					<?php $this->bptodo_show_notice(); ?>
					<div class="wbcom-admin-settings-page">
						<?php $this->bptodo_plugin_settings_tabs(); ?>
						<?php do_settings_sections( $tab ); ?>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Display plugin setting's tabs.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_plugin_settings_tabs() {
			$current_tab = filter_input(INPUT_GET, 'tab', FILTER_DEFAULT);

			if ($current_tab !== null) {
				// Sanitize the input by removing any HTML tags and trimming whitespace
				$current_tab = strip_tags(trim($current_tab));
			} else {
				// Default value if 'tab' parameter is not set
				$current_tab = 'user-todo-list-welcome';
			}

			// Now you can safely use $current_tab in your code

			echo '<div class="wbcom-tabs-section"><div class="nav-tab-wrapper"><div class="wb-responsive-menu"><span>' . esc_html( 'Menu' ) . '</span><input class="wb-toggle-btn" type="checkbox" id="wb-toggle-btn"><label class="wb-toggle-icon" for="wb-toggle-btn"><span class="wb-icon-bars"></span></label></div><ul>';
			foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
				$active = $current_tab === $tab_key ? 'nav-tab-active' : '';
				echo '<li class="' . esc_attr( $tab_caption ) . '"><a class="nav-tab ' . esc_attr( $active ) . '" id="' . esc_attr( $tab_key ) . '" href="?page=' . esc_attr( $this->plugin_slug ) . '&tab=' . esc_attr( $tab_key ) . '">' . esc_html( $tab_caption ) . '</a></li>';
			}
			echo '</div></ul></div>';
		}

		/**
		 * Register general settings tabs.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_register_general_settings() {
			$this->plugin_settings_tabs['user-todo-list-welcome']   = esc_html__( 'Welcome', 'wb-todo' );
			$this->plugin_settings_tabs['user-todo-list-settings']  = esc_html__( 'General', 'wb-todo' );
			$this->plugin_settings_tabs['group-todo-list-settings'] = esc_html__( 'Group To-Do', 'wb-todo' );

			register_setting( 'user-todo-list-settings', 'user-todo-list-settings' );
			add_settings_section( 'section_welcome', ' ', array( $this, 'bptodo_welcome_content' ), 'user-todo-list-welcome' );
			add_settings_section( 'section_general', ' ', array( $this, 'bptodo_general_settings_content' ), 'user-todo-list-settings' );
			register_setting( 'group-todo-list-settings', 'group-todo-list-settings' );
			add_settings_section( 'section_group', ' ', array( $this, 'bptodo_group_settings_content' ), 'group-todo-list-settings' );
		}

		/**
		 * Display welcome content.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_welcome_content() {
			if ( file_exists( dirname( __FILE__ ) . '/inc/bptodo-welcome-page.php' ) ) {
				require_once dirname( __FILE__ ) . '/inc/bptodo-welcome-page.php';
			}
		}

		/**
		 * Display general settings content.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_general_settings_content() {
			if ( file_exists( dirname( __FILE__ ) . '/inc/bptodo-general-settings.php' ) ) {
				require_once dirname( __FILE__ ) . '/inc/bptodo-general-settings.php';
			}
		}

		/**
		 * Display group settings content.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_group_settings_content() {
			if ( file_exists( dirname( __FILE__ ) . '/inc/bptodo-group-settings.php' ) ) {
				require_once dirname( __FILE__ ) . '/inc/bptodo-group-settings.php';
			}
		}

		/**
		 * Register shortcode settings tab.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_register_shortcode_settings() {
			$this->plugin_settings_tabs['user-todo-list-shortcodes'] = esc_html__( 'Shortcodes', 'wb-todo' );
			register_setting( 'user-todo-list-shortcodes', 'user-todo-list-shortcodes' );
			add_settings_section( 'section_shortcodes', ' ', array( $this, 'bptodo_general_shortcodes_content' ), 'user-todo-list-shortcodes' );
		}

		/**
		 * Display shortcode settings content.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_general_shortcodes_content() {
			if ( file_exists( dirname( __FILE__ ) . '/inc/bptodo-shortcodes-settings.php' ) ) {
				require_once dirname( __FILE__ ) . '/inc/bptodo-shortcodes-settings.php';
			}
		}

		/**
		 * Save general settings.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_save_general_settings() {
			if ( isset( $_POST['bptodo-save-settings'] ) && isset( $_POST['bptodo-general-settings-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bptodo-general-settings-nonce'] ) ), 'bptodo' ) ) {
				$settings = array();
				
				if ( isset( $_POST['bptodo_profile_menu_label'] ) ) {
					$settings['profile_menu_label'] = sanitize_text_field( wp_unslash( $_POST['bptodo_profile_menu_label'] ) );
				}
				if ( isset( $_POST['bptodo_hide_button'] ) ) {
					$settings['hide_button'] = sanitize_text_field( wp_unslash( $_POST['bptodo_hide_button'] ) );
				}
				if ( isset( $_POST['bptodo_profile_menu_label_plural'] ) ) {
					$settings['profile_menu_label_plural'] = sanitize_text_field( wp_unslash( $_POST['bptodo_profile_menu_label_plural'] ) );
				}
				if ( isset( $_POST['bptodo_enable_todo_member'] ) ) {
					$settings['enable_todo_member'] = sanitize_text_field( wp_unslash( $_POST['bptodo_enable_todo_member'] ) );
				}
				if ( isset( $_POST['bptodo_allow_user_add_category'] ) ) {
					$settings['allow_user_add_category'] = sanitize_text_field( wp_unslash( $_POST['bptodo_allow_user_add_category'] ) );
				}
				if ( isset( $_POST['bptodo_send_notification'] ) ) {
					$settings['send_notification'] = sanitize_text_field( wp_unslash( $_POST['bptodo_send_notification'] ) );
				}
				if ( isset( $_POST['bptodo_send_mail'] ) ) {
					$settings['send_mail'] = sanitize_text_field( wp_unslash( $_POST['bptodo_send_mail'] ) );
				}
				if ( isset( $_POST['bptodo_user_roles'] ) ) {
					$settings['bptodo_user_roles'] = map_deep( wp_unslash( $_POST['bptodo_user_roles'] ), 'sanitize_text_field' );
				}
				if ( isset( $_POST['bptodo_req_duedate'] ) ) {
					$settings['req_duedate'] = sanitize_text_field( wp_unslash( $_POST['bptodo_req_duedate'] ) );
				}

				update_option( 'user_todo_list_settings', $settings );
			}
		}

		/**
		 * Show success notice after saving settings.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_show_notice() {
			if ( isset( $_POST['bptodo-save-settings'] ) && isset( $_POST['bptodo-general-settings-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bptodo-general-settings-nonce'] ) ), 'bptodo' ) ) {
				echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html( 'Settings Saved.' ) . '</strong></p></div>';
			}
		}

		/**
		 * Hide all admin notices from the settings page.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_hide_all_admin_notices_from_setting_page() {
			$wbcom_pages_array  = array( 'wbcomplugins', 'wbcom-plugins-page', 'wbcom-support-page', 'user-todo-list-settings' );
			// Get the 'page' parameter from the GET request and sanitize it
			$wbcom_setting_page = filter_input(INPUT_GET, 'page', FILTER_DEFAULT);

			if ($wbcom_setting_page !== null) {
				// Sanitize the input by removing any HTML tags and trimming whitespace
				$wbcom_setting_page = strip_tags(trim($wbcom_setting_page));
			}

			if ( in_array( $wbcom_setting_page, $wbcom_pages_array, true ) ) {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
			}
		}
	}
}
