<?php
/**
 * Plugin Name: Wbcom Designs BP Todo List
 * Plugin URI: https://wbcomdesigns.com/contact/
 * Description: This plugin allows users to create to-do items in their profile section and a simple interface to schedule their tasks.
 * Version: 3.2.2
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

/**
 * Load plugin textdomain.
 */
add_action( 'bp_loaded', 'bptodo_load_textdomain' );
function bptodo_load_textdomain() {
    $domain = 'wb-todo';
    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

    load_textdomain( $domain, 'languages/' . $domain . '-' . $locale . '.mo' );
    load_plugin_textdomain( $domain, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}

// Constants used in the plugin.
if ( ! defined( 'BPTODO_PLUGIN_PATH' ) ) {
    define( 'BPTODO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BPTODO_PLUGIN_URL' ) ) {
    define( 'BPTODO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'BPTODO_VERSION' ) ) {
    define( 'BPTODO_VERSION', '3.2.2' );
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
 * Activation and deactivation hooks.
 */
function bptodo_activate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-bptodo-activator.php';
    Bptodo_List_Activator::activate();
}

function bptodo_deactivate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-bptodo-deactivator.php';
    Bptodo_List_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'bptodo_activate' );
register_deactivation_hook( __FILE__, 'bptodo_deactivate' );

/**
 * Include needed files.
 */
add_action( 'bp_loaded', 'bptodo_run' );
function bptodo_run() {
    $include_files = array(
        'public/class-bptodo-groups-extension-tab.php',
    );
    foreach ( $include_files as $include_file ) {
        include $include_file;
    }

    global $bptodo;
    $bptodo = new Bptodo_Globals();
}

/**
 * Admin page links.
 */
function bptodo_admin_page_link( $links ) {
    $bptodo_links = array(
        '<a href="' . admin_url( 'admin.php?page=user-todo-list-settings' ) . '">' . esc_html__( 'Settings', 'wb-todo' ) . '</a>',
        '<a href="https://wbcomdesigns.com/contact/" target="_blank">' . esc_html__( 'Support', 'wb-todo' ) . '</a>',
    );
    return array_merge( $links, $bptodo_links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bptodo_admin_page_link' );

/**
 * Allow subscribers to upload media files.
 */
function bptodo_allow_subscribers_upload() {
    $subscriber = get_role( 'subscriber' );
    if ( ! empty( $subscriber ) ) {
        $subscriber->add_cap( 'upload_files' );
    }
}
add_action( 'init', 'bptodo_allow_subscribers_upload' );

/**
 * Check if the To-Do List plugin should be active on the current subsite.
 */
function bptodo_check_subsite_activation() {
    // If BuddyPress is not active or this is not a multisite, no further checks are needed.
    if ( ! is_multisite() ) {
        if ( ! is_plugin_active( 'buddypress/bp-loader.php' ) ) {
            add_action( 'admin_notices', 'bptodo_show_buddypress_required_notice' );
            deactivate_plugins( plugin_basename( __FILE__ ) );
            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }
        }
        return;
    }

    if ( is_network_admin() ) {
        if ( ! is_plugin_active_for_network( 'buddypress/bp-loader.php' ) ) {
            add_action( 'network_admin_notices', 'bptodo_show_buddypress_root_blog_notice' );
            $sites = get_sites();
            foreach ( $sites as $site ) {
                bptodo_deactivate_plugin_for_site( $site->blog_id );
            }
            bptodo_change_plugin_status( 'deactivate', true );
            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }
        }
    } else {
        if ( ! is_plugin_active( 'buddypress/bp-loader.php' ) ) {
            add_action( 'admin_notices', 'bptodo_show_buddypress_required_notice' );
            deactivate_plugins( plugin_basename( __FILE__ ) );
            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }
        }
    }
}

/**
 * Show admin notice to inform the user that To-Do List can only be activated on the BuddyPress root blog.
 */
function bptodo_show_buddypress_root_blog_notice() {
    echo '<div class="error"><p>' . esc_html__( 'Wbcom Designs BP To-Do List can only be activated on the BuddyPress root blog.', 'wb-todo' ) . '</p></div>';
}

/**
 * Show admin notice to inform the user that BuddyPress is required for the To-Do List plugin.
 */
function bptodo_show_buddypress_required_notice() {
    echo '<div class="error"><p>' . esc_html__( 'Wbcom Designs BP To-Do List requires BuddyPress to be active on this subsite. The plugin has been deactivated.', 'wb-todo' ) . '</p></div>';
}

// Hook the function to check BuddyPress activation on the correct blog or subsite.
add_action( 'admin_init', 'bptodo_check_subsite_activation' );

/**
 * Handle AJAX request for deactivation
 */
add_action( 'wp_ajax_bptodo_deactivate_plugin', 'bptodo_deactivate_plugin' ); // Removed custom from function name

function bptodo_deactivate_plugin() {
    check_ajax_referer( 'bptodo_deactivation_nonce', 'nonce' );  // Updated nonce name

    $sites = get_sites();
    foreach ( $sites as $site ) {
        bptodo_deactivate_plugin_for_site( $site->blog_id );  // Ensure function name consistency
    }
    wp_send_json_success( array( 'message' => 'Plugin deactivated successfully' ) );
}

/**
 * Deactivate the plugin for a specific site.
 */
function bptodo_deactivate_plugin_for_site( $site_id ) {
    switch_to_blog( $site_id );
    $active_plugins = get_option( 'active_plugins' );
    $plugin_basename = plugin_basename( __FILE__ );
    if ( in_array( $plugin_basename, $active_plugins ) ) {
        $active_plugins = array_diff( $active_plugins, array( $plugin_basename ) );
        update_option( 'active_plugins', $active_plugins );
    }
    restore_current_blog();
}