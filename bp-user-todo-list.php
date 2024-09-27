<?php
/**
 * Plugin Name: Wbcom Designs BP Todo List
 * Plugin URI: https://wbcomdesigns.com/contact/
 * Description: This plugin allows users to create to-do items in their profile section and a simple interface to schedule their tasks.
 * Version: 3.2.1
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
    define( 'BPTODO_VERSION', '3.2.1' );
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
function activate_bptodo_list() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-bptodo-activator.php';
    Bptodo_List_Activator::activate();
}

function deactivate_bptodo_list() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-bptodo-deactivator.php';
    Bptodo_List_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_bptodo_list' );
register_deactivation_hook( __FILE__, 'deactivate_bptodo_list' );

/**
 * Include needed files.
 */
add_action( 'bp_loaded', 'run_wp_bptodo_list' );
function run_wp_bptodo_list() {
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
function bptodo_media_button_subscriber() {
    $subscriber = get_role( 'subscriber' );
    if ( ! empty( $subscriber ) ) {
        $subscriber->add_cap( 'upload_files' );
    }
}
add_action( 'init', 'bptodo_media_button_subscriber' );

/**
 * Check if the To-Do List plugin should be active on the current subsite.
 */
function bptodo_check_buddypress_subsite_activation() {
    // If BuddyPress is not active or this is not a multisite, no further checks are needed.
    if ( ! is_multisite() || ! class_exists( 'BuddyPress' ) ) {
        return;
    }

    // Check if BuddyPress is network-active.
    if ( is_plugin_active_for_network( 'buddypress/bp-loader.php' ) ) {
        // Get the BuddyPress root blog. If not defined, default to the primary site.
        $bp_root_blog = defined( 'BP_ROOT_BLOG' ) ? BP_ROOT_BLOG : get_network()->blog_id;
        $current_blog_id = get_current_blog_id();

        // If the current blog is not the root blog, deactivate the To-Do List plugin.
        if ( $current_blog_id != $bp_root_blog ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            add_action( 'admin_notices', 'bptodo_show_buddypress_root_blog_notice' );
        }

    } else {
        // BuddyPress is not network-active, check if it's active on the current subsite.
        if ( ! is_plugin_active( 'buddypress/bp-loader.php' ) ) {
            // BuddyPress is not active on this subsite, deactivate the To-Do List plugin.
            deactivate_plugins( plugin_basename( __FILE__ ) );
            add_action( 'admin_notices', 'bptodo_show_buddypress_required_notice' );
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
add_action( 'admin_init', 'bptodo_check_buddypress_subsite_activation' );

/**
 * Screen function for todo list title.
 */
function list_todo_tab_function_to_show_title() {
    global $bptodo;
    $profile_menu_label = $bptodo->profile_menu_label;

    $args  = array(
        'post_type'      => 'bp-todo',
        'author'         => bp_displayed_user_id(),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    );
    $todos = get_posts( $args );
    if ( count( $todos ) > 0 ) {
        $todo_export_nonce = wp_create_nonce( 'bptodo-export-todo' );
        ?>
        <input type="hidden" id="bptodo-export-todo-nonce" value="<?php echo esc_html( $todo_export_nonce ); ?>">
        <a href="javascript:void(0);" id="export_my_tasks">
            <div class="export-download"></div> 
            <?php echo esc_html__( 'Export', 'wb-todo' ); ?>
        </a>
        <?php
    }
}

/**
 * Activate group member todo.
 */
function bptodo_activate_group_member_todo() {
    $user_todo_list_settings = get_option( 'user_todo_list_settings' );
    if ( isset( $user_todo_list_settings['enable_todo_member'] ) ) {
        $user_todo_list_settings['enable_todo_member'] = 'on';
    }
    update_option( 'user_todo_list_settings', $user_todo_list_settings );

    $group_todo_list_settings = get_option( 'group-todo-list-settings' );
    if ( isset( $group_todo_list_settings['enable_todo_tab_group'] ) ) {
        $group_todo_list_settings['enable_todo_tab_group'] = 'yes';
    }
    update_option( 'group-todo-list-settings', $group_todo_list_settings );
}
add_action( 'plugins_loaded', 'bptodo_activate_group_member_todo' );

/**
 * Custom CSS for frontend.
 */
function bptodo_css() {
    ?>
    <style>
        .bptodo-modal.modal {
            z-index: 99; 
        }
    </style>
    <?php
}
add_action( 'wp_footer', 'bptodo_css' );

/**
 * Redirect to settings page after activation.
 */
add_action( 'activated_plugin', 'bptodo_activation_redirect_settings' );
function bptodo_activation_redirect_settings( $plugin ) {
    if ( $plugin == plugin_basename( __FILE__ ) && class_exists( 'BuddyPress' ) ) {
        wp_redirect( admin_url( 'admin.php?page=user-todo-list-settings' ) );
        exit;
    }
}

/**
 * Run the main plugin class.
 */
function bptodo_run() {
    require plugin_dir_path( __FILE__ ) . 'includes/class-bptodo.php';
    $plugin = new Bptodo_List();
    $plugin->run();
}
bptodo_run();
