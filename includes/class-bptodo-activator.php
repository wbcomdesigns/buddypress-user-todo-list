<?php
/**
 * Fired during plugin activation
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @todo This should probably be in one class together with Deactivator Class.
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */
class Bptodo_List_Activator {

	/**
	 * The $_REQUEST during plugin activation.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $request    The $_REQUEST array during plugin activation.
	 */
	private static $request = array();

	/**
	 * The $_REQUEST['plugin'] during plugin activation.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin    The $_REQUEST['plugin'] value during plugin activation.
	 */
	private static $plugin = 'wb-todo';

	/**
	 * Activate the plugin.
	 *
	 * Checks if the plugin was (safely) activated.
	 * Place to add any custom action during plugin activation.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wp_roles;

		if ( false === self::get_request()
			|| false === self::validate_request( self::$plugin )
			|| false === self::check_caps()
		) {
			if ( isset( $_REQUEST['wb-todo'] ) ) {
				if ( ! check_admin_referer( 'activate-plugin_' . self::$request['wb-todo'] ) ) {
					exit;
				}
			} elseif ( isset( $_REQUEST['checked'] ) ) {
				if ( ! check_admin_referer( 'bulk-plugins' ) ) {
					exit;
				}
			}
		}

		$todo_mail_setting = get_user_meta( get_current_user_id(), 'todo_mail_setting' );
		if ( false == $todo_mail_setting || '' == $todo_mail_setting ) {
			$offset = 0;
			$batch_size = 100;
			do {
				// Fetch users in batches to avoid memory issues with large datasets.
				$users = get_users( array(
					'number' => $batch_size,
					'offset' => $offset,
				));

				foreach( $users as $user ) {
					$user_id = $user->ID;
					
					// Check the current 'todo_mail_setting' value.
					$todo_mail_option = get_user_meta( $user_id, 'todo_mail_setting', true );
					
					// Update only if 'todo_mail_setting' is not already 'yes'.
					if ( $todo_mail_option !== 'yes' ) {
						update_user_meta( $user_id, 'todo_mail_setting', 'yes' );
					}
				}

				$offset += $batch_size;
			} while ( count( $users ) === $batch_size );
		}

		$user_todo_list_settings = get_option( 'user_todo_list_settings' );
		
		$all_roles = $wp_roles->get_names();
		unset( $all_roles['administrator'] );
		foreach ( $all_roles as $role_id => $role_name ) {
			$user_array[] = $role_id;
		}

		if ( false === $user_todo_list_settings ) {
			$user_todo_list_settings = array(
				'profile_menu_label' 		=> 'To-do',
				'profile_menu_label_plural' => 'To-dos',
				'enable_todo_member' 		=> 'on',
				'allow_user_add_category' 	=> 'on',
				'send_notification' 		=> 'on',
				'send_mail' 				=> 'on',
				'bptodo_user_roles' 		=> $user_array,				
			);
			update_option( 'user_todo_list_settings', $user_todo_list_settings );
		}

		$group_todo_list_settings = get_option( 'group-todo-list-settings' );
		if ( false === $group_todo_list_settings ) {
			$group_todo_list_settings = array(
				'mod_enable'  			=> 'yes',
				'list_enable' 			=> 'yes',
				'view_enable' 			=> 'yes',
				'enable_todo_tab_group' => 'yes',
			);
			update_option( 'group-todo-list-settings', $group_todo_list_settings );
		}

		/**
		 * The plugin is now safely activated.
		 * Perform your activation actions here.
		 */

	}

	/**
	 * Get the request.
	 *
	 * Gets the $_REQUEST array and checks if necessary keys are set.
	 * Populates self::request with necessary and sanitized values.
	 *
	 * @since    1.0.0
	 * @return bool|array false or self::$request array.
	 */
	private static function get_request() {

		if ( ! empty( $_REQUEST )
			&& isset( $_REQUEST['_wpnonce'] )
			&& isset( $_REQUEST['action'] )
		) {
			if ( isset( $_REQUEST['plugin'] ) ) {
				if ( false !== wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'activate-plugin_' . sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) ) ) {

					self::$request['plugin'] = sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) );
					self::$request['action'] = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );

					return self::$request;

				}
			} elseif ( isset( $_REQUEST['checked'] ) ) {
				if ( false !== wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-plugins' ) ) {

					self::$request['action'] = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
					self::$request['plugins'] = array_map( 'sanitize_text_field', wp_unslash( $_REQUEST['checked'] ) );

					return self::$request;

				}
			}
		} else {

			return false;
		}

	}

	/**
	 * Validate the Request data.
	 *
	 * Validates the $_REQUESTed data is matching this plugin and action.
	 *
	 * @since    1.0.0
	 * @param string $plugin The Plugin folder/name.php.
	 * @return bool false if either plugin or action does not match, else true.
	 */
	private static function validate_request( $plugin ) {

		if ( isset( self::$request['plugin'] )
			&& $plugin === self::$request['plugin']
			&& 'activate' === self::$request['action']
		) {

			return true;

		} elseif ( isset( self::$request['plugins'] )
			&& 'activate-selected' === self::$request['action']
			&& in_array( $plugin, self::$request['plugins'] )
		) {
			return true;
		}

		return false;

	}

	/**
	 * Check Capabilities.
	 *
	 * We want no one else but users with activate_plugins or above to be able to active this plugin.
	 *
	 * @since    1.0.0
	 * @return bool false if no caps, else true.
	 */
	private static function check_caps() {

		if ( current_user_can( 'activate_plugins' ) ) {
			return true;
		}

		return false;

	}

}
