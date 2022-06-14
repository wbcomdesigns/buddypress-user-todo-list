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
		 * Define Plugin slug.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  private
		 * @var     $plugin_slug contains plugin slug.
		 */
		private $plugin_slug = 'user-todo-list-settings';

		/**
		 * Define setting tab.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 * @var     $plugin_settings_tabs contains setting tab.
		 */
		public $plugin_settings_tabs = array();

		/**
		 * Define todo post type slug.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 * @var     $post_type contains plugin slug.
		 */
		public $post_type = 'bp-todo';

		/**
		 * Define hook.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function __construct() {
		}

		public function enqueue_styles() {

		}

		public function enqueue_scripts() {

		}

			/**
			 * Actions performed for enqueuing scripts and styles for front end.
			 *
			 * @author  wbcomdesigns
			 * @since   1.0.0
			 * @access  public
			 */
		public function bptodo_custom_variables() {
			global $bptodo, $post;
			$profile_menu_slug = $bptodo->profile_menu_slug;

			if ( isset( $_SERVER['REQUEST_URI'] ) && ( strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $profile_menu_slug ) !== false ) || isset( ( $post->post_type ) ) || isset( $post->post_content ) && has_shortcode( $post->post_content, 'bptodo_by_category' ) || has_shortcode( $post->post_content, 'ld_dashboard' ) ) {
				/** JQuery UI Datepicker CSS. */
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

				wp_enqueue_script( 'bptodo-js-front', BPTODO_PLUGIN_URL . 'assets/js/bptodo-front.js', array( 'jquery' ), BPTODO_VERSION, true );

				$todo_ajax_object = array(
					'ajax_url'            => admin_url( 'admin-ajax.php' ),
					'ajax_nonce'          => wp_create_nonce( 'bptodo-todo-nonce' ),
					/* Translators: Display plural label name */
					'export_file_heading' => sprintf( esc_html__( 'My %1$s', 'wb-todo' ), esc_html( $bptodo->profile_menu_label_plural ) ),
					'required_cat_text'   => esc_html__( 'Category name is required.', 'wb-todo' ),
					'undo_todo_title'     => esc_html__( 'Undo Complete', 'wb-todo' ),
					'remove_todo_text'    => esc_html__( 'Are you sure?', 'wb-todo' ),
					'bptodo_complete_url' => get_permalink( $post->ID ),
				);
				if ( bp_is_active( 'members' ) || bp_is_active( 'groups' ) ) {
					$todo_ajax_object['bptodo_complete_url'] = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $bptodo->profile_menu_slug . '/list';
				}
				if ( shortcode_exists( 'bptodo_by_category' ) ) {
					$todo_ajax_object['bptodo_complete_url'] = get_permalink( $post->ID );
				}
				wp_localize_script(
					'bptodo-js-front',
					'todo_ajax_object',
					$todo_ajax_object,
				);
				wp_enqueue_style( 'bptodo-front-css', BPTODO_PLUGIN_URL . 'assets/css/bptodo-front.css', array(), BPTODO_VERSION );
			}
			wp_enqueue_script( 'bptodo-js-tempust', BPTODO_PLUGIN_URL . 'assets/js/tempust.js', array( 'jquery' ), BPTODO_VERSION );
			wp_enqueue_script( 'bptodo-js-pdfobject', BPTODO_PLUGIN_URL . 'assets/js/pdfobject.min.js', array( 'jquery' ), BPTODO_VERSION );
		}

		/**
		 * Actions performed for enqueuing scripts and styles for admin panel.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_admin_variables() {
			$screen = get_current_screen();
			if ( 'wb-plugins_page_user-todo-list-settings' === $screen->base ) {
				wp_enqueue_style( 'bptodo-css-admin', BPTODO_PLUGIN_URL . 'admin/assets/css/bptodo-admin.css', array() );
			}
		}

		/**
		 * Actions performed on loading admin_menu.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_add_menu_page() {
			if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) && class_exists( 'Buddypress' ) ) {
				add_menu_page( esc_html__( 'WB Plugins', 'wb-todo' ), esc_html__( 'WB Plugins', 'wb-todo' ), 'manage_options', 'wbcomplugins', array( $this, 'bptodo_admin_options_page' ), 'dashicons-lightbulb', 59 );
				add_submenu_page( 'wbcomplugins', esc_html__( 'General', 'wb-todo' ), esc_html__( 'General', 'wb-todo' ), 'manage_options', 'wbcomplugins' );
			}
			add_submenu_page( 'wbcomplugins', esc_html__( 'BP User To-Do List', 'wb-todo' ), esc_html__( 'BP User To-Do List', 'wb-todo' ), 'manage_options', 'user-todo-list-settings', array( $this, 'bptodo_admin_options_page' ) );
		}

		/**
		 * Display plugin setting page content.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_admin_options_page() {
			$tab = filter_input( INPUT_GET, 'tab' ) ? filter_input( INPUT_GET, 'tab' ) : 'user-todo-list-welcome';
			?>
			<div class="wrap">
				<hr class="wp-header-end">
				<div class="wbcom-wrap">
				<div class="bptodo-header">
					<?php echo do_shortcode( '[wbcom_admin_setting_header]' ); ?>
					<h1 class="wbcom-plugin-heading">
						<?php esc_html_e( 'BuddyPress User To-Do List', 'wb-todo' ); ?>
					</h1>
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
		 * Display plugin setting's tab.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_plugin_settings_tabs() {
			$current_tab = filter_input( INPUT_GET, 'tab' ) ? filter_input( INPUT_GET, 'tab' ) : 'user-todo-list-welcome';
			echo '<div class="wbcom-tabs-section"><div class="nav-tab-wrapper"><div class="wb-responsive-menu"><span>' . esc_html( 'Menu' ) . '</span><input class="wb-toggle-btn" type="checkbox" id="wb-toggle-btn"><label class="wb-toggle-icon" for="wb-toggle-btn"><span class="wb-icon-bars"></span></label></div><ul>';
			foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
				$active = $current_tab === $tab_key ? 'nav-tab-active' : '';
				echo '<li><a class="nav-tab ' . esc_attr( $active ) . '" href="?page=' . esc_attr( $this->plugin_slug ) . '&tab=' . esc_attr( $tab_key ) . '">' . esc_html( $tab_caption, 'wb-todo' ) . '</a></li>';
			}
			echo '</div></ul></div>';
		}

		/**
		 * Display plugin general setting's tab.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_register_general_settings() {
			$this->plugin_settings_tabs['user-todo-list-welcome']  = esc_html__( 'Welcome', 'wb-todo' );
			$this->plugin_settings_tabs['user-todo-list-settings']  = esc_html__( 'General', 'wb-todo' );
			$this->plugin_settings_tabs['group-todo-list-settings'] = esc_html__( 'Group To-Do', 'wb-todo' );
			register_setting( 'user-todo-list-settings', 'user-todo-list-settings' );
			add_settings_section( 'section_welcome', ' ', array( &$this, 'bptodo_welcome_content' ), 'user-todo-list-welcome' );

			add_settings_section( 'section_general', ' ', array( &$this, 'bptodo_general_settings_content' ), 'user-todo-list-settings' );

			register_setting( 'group-todo-list-settings', 'group-todo-list-settings' );
			add_settings_section( 'section_group', ' ', array( &$this, 'bptodo_group_settings_content' ), 'group-todo-list-settings' );
		}

		public function bptodo_welcome_content() {
			if ( file_exists( dirname( __FILE__ ) . '/inc/bptodo-welcome-page.php' ) ) {
				require_once dirname( __FILE__ ) . '/inc/bptodo-welcome-page.php';
			}
		}

		/**
		 * Display plugin general setting's tab content.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_general_settings_content() {
			if ( file_exists( dirname( __FILE__ ) . '/inc/bptodo-general-settings.php' ) ) {
				require_once dirname( __FILE__ ) . '/inc/bptodo-general-settings.php';
			}
		}

		/**
		 * Display plugin group settings tab content.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_group_settings_content() {
			// if ( file_exists( dirname( __FILE__ ) . '/inc/bptodo-group-settings.php' ) ) {.
				require_once dirname( __FILE__ ) . '/inc/bptodo-group-settings.php';
			// }
		}

		/**
		 * Display plugin support setting's tab content.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_support_settings_content() {
			if ( file_exists( dirname( __FILE__ ) . '/inc/bptodo-support.php' ) ) {
				require_once dirname( __FILE__ ) . '/inc/bptodo-support.php';
			}
		}

		/**
		 * Display plugin shortcode setting's tab.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_register_shortcode_settings() {
			$this->plugin_settings_tabs['user-todo-list-shortcodes'] = esc_html__( 'Shortcodes', 'wb-todo' );
			register_setting( 'user-todo-list-shortcodes', 'user-todo-list-shortcodes' );
			add_settings_section( 'section_shortcodes', ' ', array( &$this, 'bptodo_general_shortcodes_content' ), 'user-todo-list-shortcodes' );
		}

		/**
		 * Display plugin shortcode setting's tab content.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_general_shortcodes_content() {
			if ( file_exists( dirname( __FILE__ ) . '/inc/bptodo-shortcodes-settings.php' ) ) {
				require_once dirname( __FILE__ ) . '/inc/bptodo-shortcodes-settings.php';
			}
		}

		/**
		 * Save general setting.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_save_general_settings() {
			if ( isset( $_POST['bptodo-save-settings'] ) && isset( $_POST['bptodo-general-settings-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bptodo-general-settings-nonce'] ) ), 'bptodo' ) ) {
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
				update_option( 'user_todo_list_settings', $settings );
			}
		}

		/**
		 * Admin notice on setting save.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_show_notice() {
			if ( isset( $_POST['bptodo-save-settings'] ) && isset( $_POST['bptodo-general-settings-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bptodo-general-settings-nonce'] ) ), 'bptodo' ) ) {
				echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html( 'Settings Saved.', 'wb-todo' ) . '</strong></p></div>';
			}
		}
	}
}
