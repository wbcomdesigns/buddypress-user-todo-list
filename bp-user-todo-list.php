<?php
/**
 * Plugin Name: Wbcom Designs BP Todo List
 * Plugin URI: https://wbcomdesigns.com/contact/
 * Description: This plugin allows users to create to-do items in their profile section and a simple interface to schedule their tasks.
 * Version: 3.2.0
 * Author: Wbcom Designs
 * Author URI: http://wbcomdesigns.com
 * License: GPLv2+
 * Text Domain: wb-todo
 *
 * @link              www.wbcomdesigns.com
 * @since             1.0.0
 * @package           bp-user-todo-list
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

add_action( 'bp_loaded', 'bptodo_load_textdomain' );

/**
 * Load plugin textdomain.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_load_textdomain() {
	$domain = 'wb-todo';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	load_textdomain( $domain, 'languages/' . $domain . '-' . $locale . '.mo' );
	$var = load_plugin_textdomain( $domain, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}

// Constants used in the plugin.
if ( ! defined( 'BPTODO_PLUGIN_PATH' ) ) {
	define( 'BPTODO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BPTODO_PLUGIN_URL' ) ) {
	define( 'BPTODO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'BPTODO_VERSION' ) ) {
	define( 'BPTODO_VERSION', '3.2.0' );
}

if ( ! defined( 'BP_ENABLE_MULTIBLOG' ) ) {
	define( 'BP_ENABLE_MULTIBLOG', false );
}

if ( ! defined( 'BP_ROOT_BLOG' ) ) {
	define( 'BP_ROOT_BLOG', 1 );
}

if ( ! defined( 'BPTODO_TEMPLATE_PATH' ) ) {
	define( 'BPTODO_TEMPLATE_PATH', BPTODO_PLUGIN_PATH . '/inc/templates/' );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-bptodo-activator.php
 */
function activate_bptodo_list() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bptodo-activator.php';
	Bptodo_List_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-bptodo-deactivator.php
 */
function deactivate_bptodo_list() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bptodo-deactivator.php';
	Bptodo_List_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_bptodo_list' );
register_deactivation_hook( __FILE__, 'deactivate_bptodo_list' );

require_once __DIR__ . '/vendor/autoload.php';
HardG\BuddyPress120URLPolyfills\Loader::init();

global $bptodo;

/**
 * Include needed files.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
add_action( 'bp_loaded', 'run_wp_bptodo_list' );
/**
 * Function to include files.
 */
function run_wp_bptodo_list() {
	$include_files = array(
		'public/class-bptodo-groups-extension-tab.php',
	);
	foreach ( $include_files  as $include_file ) {
		include $include_file;
	}

	global $bptodo;
	$bptodo = new Bptodo_Globals();
}

/**
 * Settings link for this plugin.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 * @param   string $links contains plugin's setting links.
 */
function bptodo_admin_page_link( $links ) {
	$bptodo_links = array(
		'<a href="' . admin_url( 'admin.php?page=user-todo-list-settings' ) . '">' . esc_html__( 'Settings', 'wb-todo' ) . '</a>',
		'<a href="https://wbcomdesigns.com/contact/" target="_blank">' . esc_html__( 'Support', 'wb-todo' ) . '</a>',
	);
	return array_merge( $links, $bptodo_links );
}

add_action( 'plugins_loaded', 'bptodo_plugin_init' );

/**
 * Add media button for subscriber.
 *
 * @return void
 */
function bptodo_media_button_subscriber() {
	$subscriber = get_role( 'subscriber' );
	if ( ! empty( $subscriber ) ) {
			$subscriber->add_cap( 'upload_files' );
	}
}

add_action( 'init', 'bptodo_media_button_subscriber' );

/**
 * Check plugin requirement on plugins loaded.
 * this plugin requires buddypress to be installed and active.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_plugin_init() {
	if ( is_multisite() ) {
		// Makes sure the plugin is defined before trying to use it.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active_for_network( 'buddypress/bp-loader.php' ) === false ) {
			add_action( 'network_admin_notices', 'bptodo_network_plugin_admin_notice' );
		} else {
			// run_wp_bptodo_list();.
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bptodo_admin_page_link' );
			// add_action( 'bp_include', 'bptodo_create_profile_menu' );.
		}
	} else {
		// $bp_active = in_array( 'buddypress/bp-loader.php', get_option( 'active_plugins' ) );
		if ( ! class_exists( 'BuddyPress' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
			deactivate_plugins( plugin_basename( __FILE__ ) );
			add_action( 'admin_init', 'bptodo_existing_bptodo_plugin' );
			add_action( 'admin_notices', 'bptodo_plugin_admin_notice' );
		} else {
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bptodo_admin_page_link' );
			// add_action( 'bp_include', 'bptodo_create_profile_menu' );
			// run_wp_bptodo_list();.
		}
	}
}

/**
 * Screen function for todo list title.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 * @access  public
 */
function list_todo_tab_function_to_show_title() {
	global $bptodo;
	$profile_menu_label = $bptodo->profile_menu_label;
	// echo '<div class="export-download"></div>';
	/* Translators: Display plural label name */
	$args  = array(
		'post_type'      => 'bp-todo',
		'author'         => bp_displayed_user_id(),
		'post_staus'     => 'publish',
		'posts_per_page' => -1,
	);
	$todos = get_posts( $args );
	if ( 0 !== count( $todos ) ) {
		?>
			<?php $todo_export_nonce = wp_create_nonce( 'bptodo-export-todo' ); ?>
			<input type="hidden" id="bptodo-export-todo-nonce" value="<?php echo esc_html( $todo_export_nonce ); ?>">
			<a href="javascript:void(0);" id="export_my_tasks"><div class="export-download"></div> <?php echo esc_html__( 'Export', 'wb-todo' ); ?></a>
		<?php
	}
	echo '';
}


/**
 * Function to remove bptodo plugin if already exist.
 *
 * @since 1.0.0
 */
function bptodo_existing_bptodo_plugin() {
	$bptodo_plugin = plugin_dir_path( __DIR__ ) . 'buddypress-user-todo-list/bp-user-todo-list';
	// Check to see if plugin is already active.
	if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
}

/**
 * Plugin notice - activate buddypress - single site.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_plugin_admin_notice() {
	$bptodo_plugin       = esc_html__( 'BuddyPress Member To-Do List', 'wb-todo' );
	$bp_plugin           = esc_html__( 'BuddyPress', 'wb-todo' );
	$action              = 'install-plugin';
	$slug                = 'buddypress';
	$plugin_install_link = '<a href="' . wp_nonce_url(
		add_query_arg(
			array(
				'action' => $action,
				'plugin' => $slug,
			),
			admin_url( 'update.php' )
		),
		$action . '_' . $slug
	) . '">' . $bp_plugin . '</a>';
	/* Translators: 1) BuddyPress Member To-Do List 2) BuddyPress  */
	echo '<div class="error"><p>' . sprintf( esc_html__( '%1$s is ineffective now as it requires %2$s to be installed and active.', 'wb-todo' ), '<strong>' . esc_html( $bptodo_plugin ) . '</strong>', '<strong>' . wp_kses_post( $plugin_install_link ) . '</strong>' ) . '</p></div>';
	if ( null !== filter_input( INPUT_GET, 'activate' ) ) {
		$activate = filter_input( INPUT_GET, 'activate' );
		unset( $activate );
	}
}

/**
 * Plugin notice - activate buddypress - multisite.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_network_plugin_admin_notice() {
	$bptodo_plugin       = esc_html__( 'BuddyPress Member To-Do List', 'wb-todo' );
	$bp_plugin           = esc_html__( 'BuddyPress', 'wb-todo' );
	$action              = 'install-plugin';
	$slug                = 'buddypress';
	$plugin_install_link = '<a href="' . wp_nonce_url(
		add_query_arg(
			array(
				'action' => $action,
				'plugin' => $slug,
			),
			admin_url( 'update.php' )
		),
		$action . '_' . $slug
	) . '">' . $bp_plugin . '</a>';
	/* Translators: 1) BuddyPress Member To-Do List 2) BuddyPress  */
	echo '<div class="error"><p>' . sprintf( esc_html__( '%1$s is ineffective now as it requires %2$s to be installed and active.', 'wb-todo' ), '<strong>' . esc_html( $bptodo_plugin ) . '</strong>', '<strong>' . wp_kses_post( $plugin_install_link ) . '</strong>' ) . '</p></div>';
	if ( null !== filter_input( INPUT_GET, 'activate' ) ) {
		$activate = filter_input( INPUT_GET, 'activate' );
		unset( $activate );
	}
}

/**
 * Create admin menu to plugin settings.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_if_moderator_modification_enabled( $group_id, $current_user ) {
	$group_todo_list_settings = get_option( 'group-todo-list-settings' );

	$can_modify = ( ! isset( $group_todo_list_settings['mod_enable'] ) ) ? true : false;

	return apply_filters( 'alter_bptodo_if_moderator_modification_enabled', $can_modify, $group_id, $current_user );
}
/**
 * Add or exclude group modrators to view todo report.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_report_view_enabled( $group_id, $current_user ) {
	$group_todo_list_settings = get_option( 'group-todo-list-settings' );

	$can_views = ( ! isset( $group_todo_list_settings['view_enable'] ) ) ? true : false;
	return $can_views;
}
add_filter( 'bptodo_exclude_modrator_edit', 'bptodo_report_view_enabled' );

if ( ! function_exists( 'bptodo_list_group_modrator' ) ) {
	/**
	 *  Add or exclude group modtaros in average percentage calculation.
	 *
	 * @return boolean
	 */
	add_filter( 'bptodo_exclude_modrator_view', 'bptodo_list_group_modrator' );
	function bptodo_list_group_modrator() {
		$group_todo_list_settings = get_option( 'group-todo-list-settings' );

		$can_list = ( ! isset( $group_todo_list_settings['list_enable'] ) ) ? true : false;

		return $can_list;
	}
}


/**
 * redirect to plugin settings page after activated
 */

add_action( 'activated_plugin', 'bptodo_activation_redirect_settings' );
function bptodo_activation_redirect_settings( $plugin ) {

	if ( $plugin == plugin_basename( __FILE__ ) && class_exists( 'Buddypress' ) ) {
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action']  == 'activate' && isset( $_REQUEST['plugin'] ) && $_REQUEST['plugin'] == $plugin) { //phpcs:ignore
			wp_redirect( admin_url( 'admin.php?page=user-todo-list-settings' ) );
			exit;
		}
	}
}
add_action( 'plugins_loaded', 'bptodo_activate_group_member_todo' );
/**
 * Bptodo_activate_group_member_todo
 *
 * @return void
 */
function bptodo_activate_group_member_todo() {
	$user_todo_list_settings = get_option( 'user_todo_list_settings' );
	if (  isset( $user_todo_list_settings['enable_todo_member'] ) ) {
		$user_todo_list_settings['enable_todo_member'] = 'on';
	}
	update_option( 'user_todo_list_settings', $user_todo_list_settings );

	$group_todo_list_settings = get_option( 'group-todo-list-settings' );

	if ( isset( $group_todo_list_settings['enable_todo_tab_group'] ) ) {
		$group_todo_list_settings['enable_todo_tab_group'] = 'yes';
	}
	update_option( 'group-todo-list-settings', $group_todo_list_settings );

}

$active_plugins = get_option( 'active_plugins' );
if ( in_array( 'buddypress-member-review/buddypress-member-review.php', $active_plugins ) ) {
	add_action( 'wp_footer', 'bptodo_css' );
}
function bptodo_css() {
	?>
	<style>
		.bptodo-modal.modal {
			  z-index: 99; 
		}
	</style>
	<?php
}

function bptodo_run() {
	require plugin_dir_path( __FILE__ ) . 'includes/class-bptodo.php';
	$plugin = new Bptodo_List();
	$plugin->run();

}
bptodo_run();
