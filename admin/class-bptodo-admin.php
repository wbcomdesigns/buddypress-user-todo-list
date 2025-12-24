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
		public function enqueue_styles( $hook_suffix ) {
			if ( $hook_suffix === 'wb-plugins_page_user-todo-list-settings' ) {
				wp_enqueue_style( 'selectize', plugin_dir_url( __FILE__ ) . 'assets/css/selectize.css', array(), BPTODO_VERSION, 'all' );
			}
		}

		/**
		 * Enqueue scripts.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function enqueue_scripts( $hook_suffix ) {	
			if ( $hook_suffix === 'wb-plugins_page_user-todo-list-settings' ) {
				wp_enqueue_script( 'selectize', plugin_dir_url( __FILE__ ) . 'assets/js/selectize.min.js', array( 'jquery' ), BPTODO_VERSION, false );
				wp_enqueue_script( 'bp-todo-list-js', plugin_dir_url( __FILE__ ) . 'assets/js/bp-todo-list-admin.js', array( 'jquery' ), BPTODO_VERSION, false );			
			}
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

			// Use minified versions in production, non-minified in development.
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			if (
				isset( $_SERVER['REQUEST_URI'] ) &&
				(
					strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $profile_menu_slug ) !== false ||
					isset( $post->post_type ) ||
					( isset( $post->post_content ) && ( has_shortcode( $post->post_content, 'bptodo_by_category' ) ||
					has_shortcode( $post->post_content, 'ld_dashboard' ) ) )
				)
			) {
				// Legacy jQuery UI for datepicker (still needed for date inputs).
				wp_enqueue_style( 'bptodo-css-ui', BPTODO_PLUGIN_URL . 'assets/css/jquery-ui.min.css', array(), BPTODO_VERSION );

				// Font Awesome for legacy icons.
				if ( ! wp_style_is( 'wb-font-awesome', 'enqueued' ) ) {
					wp_enqueue_style( 'wb-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css' );
				}

				// Modern Design System CSS (loaded first as foundation).
				wp_enqueue_style( 'bptodo-design-system', BPTODO_PLUGIN_URL . 'assets/css/bptodo-design-system' . $suffix . '.css', array(), BPTODO_VERSION );

				// Modern Component CSS.
				wp_enqueue_style( 'bptodo-components', BPTODO_PLUGIN_URL . 'assets/css/bptodo-components' . $suffix . '.css', array( 'bptodo-design-system' ), BPTODO_VERSION );

				// Legacy frontend CSS (for backward compatibility with existing customizations).
				wp_enqueue_style( 'bptodo-front-css', BPTODO_PLUGIN_URL . 'assets/css/bptodo-front' . $suffix . '.css', array( 'bptodo-components' ), BPTODO_VERSION );

				// Select2 for multi-select dropdowns.
				wp_enqueue_style( 'select2-css', BPTODO_PLUGIN_URL . 'assets/css/select2.min.css', array(), BPTODO_VERSION );

				// jQuery and datepicker.
				if ( ! wp_script_is( 'jquery' ) ) {
					wp_enqueue_script( 'jquery' );
				}

				if ( ! wp_script_is( 'datepicker.min.js' ) ) {
					wp_enqueue_script( 'jquery-ui-datepicker' );
				}

				// Select2 JS.
				wp_enqueue_script( 'select2-js', BPTODO_PLUGIN_URL . 'assets/js/select2.full.min.js', array( 'jquery' ), BPTODO_VERSION, true );

				// Modern UX Module (toast, loading, modal).
				wp_enqueue_script( 'bptodo-modern-js', BPTODO_PLUGIN_URL . 'assets/js/bptodo-modern' . $suffix . '.js', array( 'jquery' ), BPTODO_VERSION, true );

				// Main frontend JavaScript.
				wp_enqueue_script( 'bptodo-js-front', BPTODO_PLUGIN_URL . 'assets/js/bptodo-front' . $suffix . '.js', array( 'jquery', 'bptodo-modern-js' ), BPTODO_VERSION, true );

				$localized_strings = $this->bp_todo_get_localized_strings();

				wp_localize_script(
					'bptodo-js-front',
					'todo_ajax_object',
					array(
						'ajax_url'            => admin_url( 'admin-ajax.php' ),
						'ajax_nonce'          => wp_create_nonce( 'bptodo-todo-nonce' ),
						'current_user_id'     => get_current_user_id(),
						'bptodo_complete_url' => esc_url( home_url( $_SERVER['REQUEST_URI'] ?? '' ) ),
						/* translators: %1$s */
						'export_file_heading' => sprintf( esc_html__( 'My %1$s', 'wb-todo' ), esc_html( $bptodo->profile_menu_label_plural ) ),
						'required_cat_text'   => esc_html__( 'Category name is required.', 'wb-todo' ),
						'undo_todo_title'     => esc_html__( 'Undo Complete', 'wb-todo' ),
						'remove_todo_text'    => esc_html__( 'Are you sure you want to delete this task?', 'wb-todo' ),
						// Toast notification messages.
						'saving_text'         => esc_html__( 'Saving...', 'wb-todo' ),
						'todo_updated_text'   => esc_html__( 'Task updated successfully!', 'wb-todo' ),
						'todo_completed_text' => esc_html__( 'Task completed!', 'wb-todo' ),
						'todo_removed_text'   => esc_html__( 'Task deleted successfully.', 'wb-todo' ),
						'error_text'          => esc_html__( 'Something went wrong. Please try again.', 'wb-todo' ),
						'localized'           => $localized_strings,
					)
				);
			}

			wp_enqueue_script( 'bptodo-js-tempust', BPTODO_PLUGIN_URL . 'assets/js/tempust.js', array( 'jquery' ), BPTODO_VERSION );
			wp_enqueue_script( 'bptodo-js-pdfobject', BPTODO_PLUGIN_URL . 'assets/js/pdfobject.min.js', array( 'jquery' ), BPTODO_VERSION );

		}

		/**
		 * Function to create Data Table related strings translatable.
		 * 
		 * @since 3.3.1
		 */
		private function bp_todo_get_localized_strings() {
			
			$localized_strings = array();

			$localized_strings['empty_table']         = __( 'No data available in table', 'wb-todo' );
			$localized_strings['paginate_first']      = __( 'First', 'wb-todo' );
			$localized_strings['paginate_last']       = __( 'Last', 'wb-todo' );
			$localized_strings['paginate_next']       = __( 'Next', 'wb-todo' );
			$localized_strings['paginate_previous']   = __( 'Previous', 'wb-todo' );
			$localized_strings['search']              = __( 'Search', 'wb-todo' );
			/* translators: %s: Number of entries selector */
			$localized_strings['show_entries']        = sprintf( __( 'Show %s entries', 'wb-todo' ), '_MENU_' );
			/* translators: 1: Start number, 2: End number, 3: Total entries */
			$localized_strings['pagination_info']     = sprintf( __( 'Showing %1$d to %2$d of %3$d entries', 'wb-todo' ), '_START_', '_END_', '_TOTAL_' );
			$localized_strings['pagination_empty_info']   =  __( "Showing 0 to 0 of 0 entries", 'wb-todo' );
			

			return $localized_strings;
		}

		/**
		 * Enqueue admin panel scripts and styles.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_admin_variables() {
			$screen = get_current_screen();

			if ( 'wb-plugins_page_user-todo-list-settings' === $screen->base || ( isset($_GET['page']) && $_GET['page'] === 'wbcomplugins' ) ) {
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
			if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) && class_exists( 'BuddyPress' ) ) {
				add_menu_page( esc_html__( 'WB Plugins', 'wb-todo' ), esc_html__( 'WB Plugins', 'wb-todo' ), 'manage_options', 'wbcomplugins', array( $this, 'bptodo_admin_options_page' ), 'dashicons-lightbulb', 59 );
				add_submenu_page( 'wbcomplugins', esc_html__( 'General', 'wb-todo' ), esc_html__( 'General', 'wb-todo' ), 'manage_options', 'wbcomplugins' );
			}
			add_submenu_page( 'wbcomplugins', esc_html__( 'Todo for BuddyPress & BuddyBoss', 'wb-todo' ), esc_html__( 'Todo Lists', 'wb-todo' ), 'manage_options', 'user-todo-list-settings', array( $this, 'bptodo_admin_options_page' ) );
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
				$tab = wp_strip_all_tags( trim( $tab ) );
			} else {
				// Default value if 'tab' parameter is not set
				$tab = 'user-todo-list-welcome';
			}

			?>
			<div class="wrap">
				<div class="wbcom-bb-plugins-offer-wrapper">
					<div id="wb_admin_logo">
					</div>
				</div>
				<div class="wbcom-wrap">
					<div class="blpro-header">
						<div class="wbcom_admin_header-wrapper">
							<div id="wb_admin_plugin_name">
								<?php esc_html_e( 'Todo for BuddyPress & BuddyBoss', 'wb-todo' ); ?>
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
				$current_tab = wp_strip_all_tags( trim( $current_tab ) );
			} else {
				// Default value if 'tab' parameter is not set
				$current_tab = 'user-todo-list-welcome';
			}

			// Now you can safely use $current_tab in your code

			echo '<div class="wbcom-tabs-section"><div class="nav-tab-wrapper"><div class="wb-responsive-menu"><span>' . esc_html__( 'Menu', 'wb-todo' ) . '</span><input class="wb-toggle-btn" type="checkbox" id="wb-toggle-btn"><label class="wb-toggle-icon" for="wb-toggle-btn"><span class="wb-icon-bars"></span></label></div><ul>';
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
			$this->plugin_settings_tabs['group-todo-list-settings'] = esc_html__( 'Group Todo', 'wb-todo' );
		$this->plugin_settings_tabs['data-retention-settings']  = esc_html__( 'Data Retention', 'wb-todo' );

			register_setting( 'user-todo-list-settings', 'user-todo-list-settings' );
			add_settings_section( 'section_welcome', ' ', array( $this, 'bptodo_welcome_content' ), 'user-todo-list-welcome' );
			add_settings_section( 'section_general', ' ', array( $this, 'bptodo_general_settings_content' ), 'user-todo-list-settings' );
			register_setting( 'group-todo-list-settings', 'group-todo-list-settings', array( $this, 'bptodo_sanitize_group_settings' ) );
			add_settings_section( 'section_group', ' ', array( $this, 'bptodo_group_settings_content' ), 'group-todo-list-settings' );
		register_setting( 'data-retention-settings', 'data-retention-settings', array( $this, 'bptodo_sanitize_retention_settings' ) );
		add_settings_section( 'section_retention', ' ', array( $this, 'bptodo_retention_settings_content' ), 'data-retention-settings' );
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
	 * Display data retention settings content.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function bptodo_retention_settings_content() {
		if ( file_exists( dirname( __FILE__ ) . '/inc/bptodo-retention-settings.php' ) ) {
			require_once dirname( __FILE__ ) . '/inc/bptodo-retention-settings.php';
		}
	}

	/**
	 * Sanitize data retention settings.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $input The settings input array.
	 * @return array Sanitized settings array.
	 */
	public function bptodo_sanitize_retention_settings( $input ) {
		$sanitized = array();

		// Handle checkboxes
		$sanitized['enable_retention'] = isset( $input['enable_retention'] ) && $input['enable_retention'] === 'yes' ? 'yes' : 'no';
		$sanitized['enable_group_retention'] = isset( $input['enable_group_retention'] ) && $input['enable_group_retention'] === 'yes' ? 'yes' : 'no';
		$sanitized['enable_personal_retention'] = isset( $input['enable_personal_retention'] ) && $input['enable_personal_retention'] === 'yes' ? 'yes' : 'no';
		$sanitized['permanent_delete'] = isset( $input['permanent_delete'] ) && $input['permanent_delete'] === 'yes' ? 'yes' : 'no';
		$sanitized['email_notification'] = isset( $input['email_notification'] ) && $input['email_notification'] === 'yes' ? 'yes' : 'no';

		// Handle select fields
		$sanitized['cleanup_type'] = isset( $input['cleanup_type'] ) ? sanitize_text_field( $input['cleanup_type'] ) : 'completed';

		// Handle numeric fields
		$sanitized['retention_days'] = isset( $input['retention_days'] ) ? absint( $input['retention_days'] ) : 90;
		
		// Ensure minimum 7 days
		if ( $sanitized['retention_days'] < 7 ) {
			$sanitized['retention_days'] = 7;
		}

		// Handle email field
		if ( isset( $input['notification_email'] ) ) {
			$emails = array_map( 'trim', explode( ',', $input['notification_email'] ) );
			$valid_emails = array_filter( $emails, 'is_email' );
			$sanitized['notification_email'] = implode( ', ', $valid_emails );
		} else {
			$sanitized['notification_email'] = get_option( 'admin_email' );
		}

		return $sanitized;
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

				// Text fields
				if ( isset( $_POST['bptodo_profile_menu_label'] ) ) {
					$settings['profile_menu_label'] = sanitize_text_field( wp_unslash( $_POST['bptodo_profile_menu_label'] ) );
				}
				if ( isset( $_POST['bptodo_hide_button'] ) ) {
					$settings['hide_button'] = sanitize_text_field( wp_unslash( $_POST['bptodo_hide_button'] ) );
				}
				if ( isset( $_POST['bptodo_profile_menu_label_plural'] ) ) {
					$settings['profile_menu_label_plural'] = sanitize_text_field( wp_unslash( $_POST['bptodo_profile_menu_label_plural'] ) );
				}

				// Checkboxes - handle both checked and unchecked states
				$settings['enable_todo_member'] = isset( $_POST['bptodo_enable_todo_member'] ) ? 'yes' : 'no';
				$settings['allow_user_add_category'] = isset( $_POST['bptodo_allow_user_add_category'] ) ? 'yes' : 'no';
				$settings['send_notification'] = isset( $_POST['bptodo_send_notification'] ) ? 'yes' : 'no';
				$settings['send_mail'] = isset( $_POST['bptodo_send_mail'] ) ? 'yes' : 'no';
				$settings['req_duedate'] = isset( $_POST['bptodo_req_duedate'] ) ? 'yes' : 'no';

				// Multi-select
				if ( isset( $_POST['bptodo_user_roles'] ) ) {
					$settings['bptodo_user_roles'] = map_deep( wp_unslash( $_POST['bptodo_user_roles'] ), 'sanitize_text_field' );
				} else {
					$settings['bptodo_user_roles'] = array();
				}

				update_option( 'user_todo_list_settings', $settings );
				// Refresh global settings object to reflect changes immediately
				global $bptodo;
				$bptodo = new Bptodo_Globals();
			}
		}

		/**
	 * Sanitize group todo settings.
	 * Handles checkbox values properly - unchecked boxes won't be in POST, so we set them to 'no'.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $input The settings input array.
	 * @return array Sanitized settings array.
	 */
	public function bptodo_sanitize_group_settings( $input ) {
		$sanitized = array();

		// Handle checkboxes - they won't be in $input if unchecked
		$checkbox_fields = array( 'enable_todo_tab_group', 'mod_enable', 'list_enable', 'view_enable' );

		foreach ( $checkbox_fields as $field ) {
			// If checkbox is checked, it will have value 'yes', otherwise set to 'no'
			$sanitized[ $field ] = isset( $input[ $field ] ) && $input[ $field ] === 'yes' ? 'yes' : 'no';
		}

		return $sanitized;
	}

	/**
	 * Show success notice after saving settings.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_show_notice() {
			if ( isset( $_POST['bptodo-save-settings'] ) && isset( $_POST['bptodo-general-settings-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bptodo-general-settings-nonce'] ) ), 'bptodo' ) ) {
				echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Settings Saved.', 'wb-todo' ) . '</strong></p></div>';
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
				$wbcom_setting_page = wp_strip_all_tags( trim( $wbcom_setting_page ) );
			}

			if ( in_array( $wbcom_setting_page, $wbcom_pages_array, true ) ) {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
			}
		}
	}
}
