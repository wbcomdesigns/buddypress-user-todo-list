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
			add_action( 'admin_menu', array( $this, 'bptodo_add_menu_page' ) );
			add_action( 'admin_init', array( $this, 'bptodo_register_general_settings' ) );
			add_action( 'admin_init', array( $this, 'bptodo_register_shortcode_settings' ) );
			add_action( 'admin_init', array( $this, 'bptodo_register_support_settings' ) );
			$this->bptodo_save_general_settings();
		}

		/**
		 * Actions performed on loading admin_menu.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_add_menu_page() {
			add_submenu_page( 'edit.php?post_type=bp-todo', esc_html__( 'BuddyPress Member To-Do List Settings', 'wb-todo' ), esc_html__( 'Settings', 'wb-todo' ), 'manage_options', 'user-todo-list-settings', array( $this, 'bptodo_admin_options_page' ) );
		}

		/**
		 * Display plugin setting page content.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_admin_options_page() {
			$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'user-todo-list-settings';
			?>
			<div class="wrap">
				<div class="bptodo-header">
					<h1 class="bptodo-plugin-heading"><?php esc_html_e( 'BuddyPress Member To-Do List Settings', 'wb-todo' ); ?></h1>
					<?php $this->bptodo_show_notice(); ?>
					<div class="bptodo-extra-actions">
						<button type="button" class="button button-secondary" onclick="window.open('https://wbcomdesigns.com/contact/', '_blank');"><i class="fa fa-envelope" aria-hidden="true"></i> <?php esc_html_e( 'Email Support', 'wb-todo' ); ?></button>
						<button type="button" class="button button-secondary" onclick="window.open('https://wbcomdesigns.com/helpdesk/article-categories/bp-user-to-do-list/', '_blank');"><i class="fa fa-file" aria-hidden="true"></i> <?php esc_html_e( 'User Manual', 'wb-todo' ); ?></button>
						<button type="button" class="button button-secondary" onclick="window.open('https://wordpress.org/support/plugin/bp-user-to-do-list/reviews/', '_blank');"><i class="fa fa-star" aria-hidden="true"></i> <?php esc_html_e( 'Rate Us on WordPress.org', 'wb-todo' ); ?></button>
					</div>
				</div>
				<?php $this->bptodo_plugin_settings_tabs(); ?>
				<?php do_settings_sections( $tab ); ?>
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
			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'user-todo-list-settings';
			echo '<h2 class="nav-tab-wrapper">';
			foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
				$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
				echo '<a class="nav-tab ' . esc_attr( $active ) . '" href="?post_type=' . esc_attr( $this->post_type ) . '&page=' . esc_attr( $this->plugin_slug ) . '&tab=' . esc_attr( $tab_key ) . '">' . esc_html( $tab_caption, 'wb-todo' ) . '</a>';
			}
			echo '</h2>';
		}

		/**
		 * Display plugin general setting's tab.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_register_general_settings() {
			$this->plugin_settings_tabs['user-todo-list-settings'] = esc_html__( 'General', 'wb-todo' );
			register_setting( 'user-todo-list-settings', 'user-todo-list-settings' );
			add_settings_section( 'section_general', ' ', array( &$this, 'bptodo_general_settings_content' ), 'user-todo-list-settings' );
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
		 * Display plugin general setting's tab.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_register_support_settings() {
			$this->plugin_settings_tabs['bptodo-support'] = esc_html__( 'Support', 'wb-todo' );
			register_setting( 'bptodo-support', 'bptodo-support' );
			add_settings_section( 'section_support', ' ', array( &$this, 'bptodo_support_settings_content' ), 'bptodo-support' );
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
			if ( isset( $_POST['bptodo-save-settings'] ) && wp_verify_nonce( $_POST['bptodo-general-settings-nonce'], 'bptodo' ) ) {
				if ( isset( $_POST['bptodo_profile_menu_label'] ) ) {
					$settings['profile_menu_label'] = sanitize_text_field( wp_unslash( $_POST['bptodo_profile_menu_label'] ) );
				}
				if ( isset( $_POST['bptodo_profile_menu_label_plural'] ) ) {
					$settings['profile_menu_label_plural'] = sanitize_text_field( wp_unslash( $_POST['bptodo_profile_menu_label_plural'] ) );
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
			if ( isset( $_POST['bptodo-save-settings'] ) && wp_verify_nonce( $_POST['bptodo-general-settings-nonce'], 'bptodo' ) ) {
				echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html( 'Settings Saved.', 'wb-todo' ) . '</strong></p></div>';
			}
		}
	}
}
