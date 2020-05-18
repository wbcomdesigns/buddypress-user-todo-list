<?php
/**
 * Plugin Name: BuddyPress User To-Do List
 * Plugin URI: https://wbcomdesigns.com/contact/
 * Description: This plugin allows users to create to do items in their profile section and simple interface to schedule your task.
 * Version: 2.2.0
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
	define( 'BPTODO_VERSION', '2.2.0' );
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

global $bptodo;

/**
 * Include needed files.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
add_action( 'bp_loaded', 'run_wp_bptodo_list' );
function run_wp_bptodo_list() {
	$include_files = array(
		'inc/class-bptodo-profile-menu.php',
		'inc/class-bptodo-groups-extension-tab.php',
		'inc/class-bptodo-scripts.php',
		'inc/class-bptodo-ajax.php',
		'inc/class-bptodo-cpt.php',
		'inc/class-bptodo-globals.php',
		'inc/class-bptodo-hooks.php',
		'inc/bptodo-plugin-genral-function.php',
		'admin/wbcom/wbcom-admin-settings.php',
		'admin/class-bptodo-admin.php',
		'admin/class-bptodo-feedback.php',
		'inc/widget/class-user-todo.php',
	);
	foreach ( $include_files  as $include_file ) {
		include $include_file;
	}

	// Initialize admin class.
	new Bptodo_Admin();

	// Initialize globals class.
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
			// run_wp_bptodo_list();
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bptodo_admin_page_link' );
			// add_action( 'bp_include', 'bptodo_create_profile_menu' );
		}
	} else {
		// $bp_active = in_array( 'buddypress/bp-loader.php', get_option( 'active_plugins' ) );
		if ( ! class_exists( 'BuddyPress' ) ) {
			add_action( 'admin_notices', 'bptodo_plugin_admin_notice' );
		} else {
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bptodo_admin_page_link' );
			// add_action( 'bp_include', 'bptodo_create_profile_menu' );
			// run_wp_bptodo_list();
		}
	}
}

/**
 * Plugin notice - activate buddypress - single site.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_plugin_admin_notice() {
	$bptodo_plugin = esc_html__( 'BuddyPress Member To-Do List', 'wb-todo' );
	$bp_plugin     = esc_html__( 'BuddyPress', 'wb-todo' );

	echo '<div class="error"><p>' . sprintf( esc_html__( '%1$s is ineffective now as it requires %2$s to be installed and active.', 'wb-todo' ), '<strong>' . esc_html( $bptodo_plugin ) . '</strong>', '<strong>' . esc_html( $bp_plugin ) . '</strong>' ) . '</p></div>';
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}

/**
 * Plugin notice - activate buddypress - multisite.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_network_plugin_admin_notice() {
	$bptodo_plugin = esc_html__( 'BuddyPress Member To-Do List', 'wb-todo' );
	$bp_plugin     = esc_html__( 'BuddyPress', 'wb-todo' );

	echo '<div class="error"><p>' . sprintf( esc_html__( '%1$s is ineffective now as it requires %2$s to be installed and active.', 'wb-todo' ), '<strong>' . esc_html( $bptodo_plugin ) . '</strong>', '<strong>' . esc_html( $bp_plugin ) . '</strong>' ) . '</p></div>';
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
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

	$can_modify = ( isset( $group_todo_list_settings['mod_enable'] ) ) ? true : false;

	return apply_filters( 'alter_bptodo_if_moderator_modification_enabled', $can_modify, $group_id, $current_user );
}


if ( ! function_exists( 'bptodo_list_group_modrator' ) ) {
	/**
	 *  Add or exclude group modtaros in average percentage calculation.
	 *
	 * @return boolean
	 */
	add_filter( 'bptodo_exclude_modrator_view', 'bptodo_list_group_modrator' );
	function bptodo_list_group_modrator() {
		$group_todo_list_settings = get_option( 'group-todo-list-settings' );

		$can_list = ( isset( $group_todo_list_settings['list_enable'] ) ) ? true : false;
		return $can_list;
	}
}
