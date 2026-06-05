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
	 * Legacy admin service class — settings registration, sanitizers,
	 * the custom General-tab save handler, and the frontend asset loader.
	 *
	 * As of 3.6.0 this class NO LONGER renders any admin UI. The menu,
	 * enqueue, settings-page render, tab nav, and notice-suppression
	 * methods were removed when the card-panel admin (Bptodo_Admin_Panel,
	 * includes/admin/) took over. What remains:
	 *   - bptodo_register_general_settings()  (option-group registration)
	 *   - bptodo_register_shortcode_settings()
	 *   - bptodo_sanitize_group_settings() / bptodo_sanitize_retention_settings()
	 *   - bptodo_save_general_settings()      (custom self-POST handler)
	 *   - bptodo_variables()                  (frontend asset enqueue)
	 *
	 * @package bp-user-todo-list
	 * @author  wbcomdesigns
	 * @since   1.0.0
	 */
	class Bptodo_Admin {

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

			$localized_strings['empty_table']       = __( 'No data available in table', 'wb-todo' );
			$localized_strings['paginate_first']    = __( 'First', 'wb-todo' );
			$localized_strings['paginate_last']     = __( 'Last', 'wb-todo' );
			$localized_strings['paginate_next']     = __( 'Next', 'wb-todo' );
			$localized_strings['paginate_previous'] = __( 'Previous', 'wb-todo' );
			$localized_strings['search']            = __( 'Search', 'wb-todo' );
			/* translators: %s: Number of entries selector */
			$localized_strings['show_entries'] = sprintf( __( 'Show %s entries', 'wb-todo' ), '_MENU_' );
			/* translators: 1: Start number, 2: End number, 3: Total entries */
			$localized_strings['pagination_info']       = sprintf( __( 'Showing %1$d to %2$d of %3$d entries', 'wb-todo' ), '_START_', '_END_', '_TOTAL_' );
			$localized_strings['pagination_empty_info'] = __( 'Showing 0 to 0 of 0 entries', 'wb-todo' );

			return $localized_strings;
		}

		/**
		 * Register the Group Todo / Data Retention option groups.
		 *
		 * The card-panel views (includes/admin/views/) render the fields
		 * directly, so no add_settings_section() callbacks are needed. The
		 * register_setting() calls below are still required so options.php
		 * accepts the Group Todo + Data Retention saves with the SAME option
		 * keys and sanitizers as before — preserving the save contract.
		 *
		 * Note: the General tab does NOT use the Settings API. It saves the
		 * underscore-keyed `user_todo_list_settings` option via the custom
		 * self-POST handler bptodo_save_general_settings(), so there is no
		 * register_setting() for it here.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_register_general_settings() {
			register_setting( 'group-todo-list-settings', 'group-todo-list-settings', array( $this, 'bptodo_sanitize_group_settings' ) );
			register_setting( 'data-retention-settings', 'data-retention-settings', array( $this, 'bptodo_sanitize_retention_settings' ) );
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
			$sanitized['enable_retention']          = isset( $input['enable_retention'] ) && $input['enable_retention'] === 'yes' ? 'yes' : 'no';
			$sanitized['enable_group_retention']    = isset( $input['enable_group_retention'] ) && $input['enable_group_retention'] === 'yes' ? 'yes' : 'no';
			$sanitized['enable_personal_retention'] = isset( $input['enable_personal_retention'] ) && $input['enable_personal_retention'] === 'yes' ? 'yes' : 'no';
			$sanitized['permanent_delete']          = isset( $input['permanent_delete'] ) && $input['permanent_delete'] === 'yes' ? 'yes' : 'no';
			$sanitized['email_notification']        = isset( $input['email_notification'] ) && $input['email_notification'] === 'yes' ? 'yes' : 'no';

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
				$emails                          = array_map( 'trim', explode( ',', $input['notification_email'] ) );
				$valid_emails                    = array_filter( $emails, 'is_email' );
				$sanitized['notification_email'] = implode( ', ', $valid_emails );
			} else {
				$sanitized['notification_email'] = get_option( 'admin_email' );
			}

			return $sanitized;
		}

		/**
		 * Register the (informational) shortcodes option group.
		 *
		 * The Shortcodes tab is documentation-only; this keeps the
		 * `user-todo-list-shortcodes` option key registered for back-compat.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_register_shortcode_settings() {
			register_setting( 'user-todo-list-shortcodes', 'user-todo-list-shortcodes' );
		}

		/**
		 * Save general settings.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_save_general_settings() {
			if ( isset( $_POST['bptodo-save-settings'] ) && isset( $_POST['bptodo-general-settings-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bptodo-general-settings-nonce'] ) ), 'bptodo' ) ) {
				// Defense in depth: this handler runs on plugins_loaded for any
				// request carrying the nonce, so gate it on the capability the
				// settings page itself requires before writing the option.
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				$settings = array();

				// Text fields
				if ( isset( $_POST['bptodo_profile_menu_label'] ) ) {
					$settings['profile_menu_label'] = sanitize_text_field( wp_unslash( $_POST['bptodo_profile_menu_label'] ) );
				}
				if ( isset( $_POST['bptodo_profile_menu_label_plural'] ) ) {
					$settings['profile_menu_label_plural'] = sanitize_text_field( wp_unslash( $_POST['bptodo_profile_menu_label_plural'] ) );
				}

				// Checkboxes - handle both checked and unchecked states
				$settings['enable_todo_member']      = isset( $_POST['bptodo_enable_todo_member'] ) ? 'yes' : 'no';
				$settings['allow_user_add_category'] = isset( $_POST['bptodo_allow_user_add_category'] ) ? 'yes' : 'no';
				$settings['send_notification']       = isset( $_POST['bptodo_send_notification'] ) ? 'yes' : 'no';
				$settings['send_mail']               = isset( $_POST['bptodo_send_mail'] ) ? 'yes' : 'no';
				$settings['req_duedate']             = isset( $_POST['bptodo_req_duedate'] ) ? 'yes' : 'no';

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

				// Set transient for success notice
				set_transient( 'bptodo_settings_saved', true, 30 );

				// Redirect to prevent form resubmission
				wp_safe_redirect( admin_url( 'admin.php?page=user-todo-list-settings&tab=user-todo-list-settings&settings-updated=true' ) );
				exit;
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
	}
}
