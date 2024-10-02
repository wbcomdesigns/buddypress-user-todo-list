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
    define( 'BP_ROOT_BLOG', 1);
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
    if ( ! is_multisite() ) {
        if(! is_plugin_active( 'buddypress/bp-loader.php' )) {
            add_action( 'admin_notices', 'bptodo_show_buddypress_required_notice' );
            deactivate_plugins( plugin_basename( __FILE__ ) );
            if( isset($_GET['activate']) ) {
                unset($_GET['activate']);
            }
         }
        return;
    }
    if( is_network_admin() ) {
        if(!is_plugin_active_for_network('buddypress/bp-loader.php')) {
            add_action( 'network_admin_notices', 'bptodo_show_buddypress_root_blog_notice' );
            $sites = get_sites();
            foreach ($sites as $site) {
                deactivate_plugin_for_site($site->blog_id);
            }
            change_plugin_status('deactivate', true);
            // $deactivate_nonce = wp_create_nonce('deactivate-plugin_buddypress/bp-loader.php');
            if( isset($_GET['activate']) ) {
                unset($_GET['activate']);
            }
            // wp_redirect( admin_url( 'plugins.php?action=deactivate&plugin=buddypress/bp-loader.php&plugin_status=all&paged=1&s&_wpnonce=' . $deactivate_nonce ) );
        }
    }else{
        if ( ! is_plugin_active( 'buddypress/bp-loader.php' ) ) {
            // BuddyPress is not active on this subsite, deactivate the To-Do List plugin.
            add_action( 'admin_notices', 'bptodo_show_buddypress_required_notice' );
            deactivate_plugins( plugin_basename( __FILE__ ) );
            if( isset($_GET['activate']) ) {
                unset($_GET['activate']);
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
// add_action( 'activated_plugin', 'bptodo_activation_redirect_settings' );
// function bptodo_activation_redirect_settings( $plugin ) {
    // if ( $plugin == plugin_basename( __FILE__ ) && class_exists( 'BuddyPress' ) ) {
    //     wp_redirect( admin_url( 'admin.php?page=user-todo-list-settings' ) );
    //     exit;
    // }
// }

/**
 * Run the main plugin class.
 */
function bptodo_run() {
    require plugin_dir_path( __FILE__ ) . 'includes/class-bptodo.php';
    $plugin = new Bptodo_List();
    $plugin->run();
}
bptodo_run();


add_action('activated_plugin', 'my_plugin_post_activation', 10, 2);


function my_plugin_post_activation($plugin, $network_wide) {
	if (is_multisite()) {
		change_plugin_status('activate', false);
		change_plugin_status('deactivate', true);
	}

    if(!class_exists( 'BuddyPress' ) ) {
        if ( $plugin == plugin_basename( __FILE__ ) && class_exists( 'BuddyPress' ) ) {
            if(!is_multisite()) {
                wp_redirect( admin_url( 'admin.php?page=user-todo-list-settings' ) );
                exit;
            }
           
        }
     } 
}


function change_plugin_status($status, $network_wide = false){
	$plugin_path = plugin_basename(__FILE__);
	if ($network_wide) {
		$active_plugins = get_site_option('active_sitewide_plugins');
	} else {
		$active_plugins = get_option('active_plugins');
	}
	if( $status == 'deactivate' ) {
		if (isset($active_plugins[$plugin_path]) && !empty($active_plugins[$plugin_path]) ) {
			unset($active_plugins[$plugin_path]);
			if ($network_wide) {
				update_site_option('active_sitewide_plugins', $active_plugins);
			}else{
				update_option('active_plugins', $active_plugins);
			}
		}
	}else{
		if (!isset($active_plugins[$plugin_path])) {
			$current_timestamp = current_time('timestamp');
			$active_plugins[] = $plugin_path;
			if ($network_wide) {
				update_site_option('active_sitewide_plugins', $active_plugins);
			}else{
				update_option('active_plugins', $active_plugins);
			}
		}
	}
}


add_filter('network_admin_plugin_action_links_' . plugin_basename(__FILE__), 'custom_network_plugin_action_links');

function custom_network_plugin_action_links($links) {
    // Create a new activation link for network activation
	unset($links['activate']);

	// Create a new deactivation link
    $links['deactivate'] = '<a href="#" class="custom-deactivate-plugin" data-plugin="' . esc_attr(plugin_basename(__FILE__)) . '">Network Deactivate</a>';

    return $links;
}


// Enqueue JavaScript for confirmation and AJAX handling
// add_action('admin_enqueue_scripts', 'enqueue_custom_deactivation_script');

// function enqueue_custom_deactivation_script() {
//     // Only enqueue on the network admin plugin page
//     if (is_network_admin()) {
//         wp_enqueue_script('custom-deactivation-script', plugin_dir_url(__FILE__) . 'deactivation-script.js', ['jquery'], null, true);
//         wp_localize_script('custom-deactivation-script', 'deactivation_ajax_object', [
//             'ajax_url' => admin_url('admin-ajax.php'),
//             'nonce' => wp_create_nonce('custom_deactivation_nonce'),
//         ]);
//     }
// }



// Handle the AJAX request for deactivation
add_action('wp_ajax_custom_deactivate_plugin', 'custom_deactivate_plugin');

function custom_deactivate_plugin() {
    check_ajax_referer('custom_deactivation_nonce', 'nonce');

	$sites = get_sites();
	foreach ($sites as $site) {
			deactivate_plugin_for_site($site->blog_id);
	}
	wp_send_json_success(['message' => 'Plugin deactivated successfully']);
}

// Function to deactivate the plugin for a specific site
function deactivate_plugin_for_site($site_id) {
    // Switch to the specified site
    switch_to_blog($site_id);

    // Get the current active plugins for the site
    $active_plugins = get_option('active_plugins');

    // Check if the plugin is active and remove it
    $plugin_basename = plugin_basename(__FILE__);
    if (in_array($plugin_basename, $active_plugins)) {
        $active_plugins = array_diff($active_plugins, [$plugin_basename]);
        update_option('active_plugins', $active_plugins);
    }

    // Restore the original blog
    restore_current_blog();
}

add_filter('site_option_active_sitewide_plugins', 'set_plugin_active_for_network', 99, 3);
function set_plugin_active_for_network($value, $option, $network_id){
	if('active_sitewide_plugins' == $option && is_network_admin()) {
		$plugin_path = plugin_basename(__FILE__);
		$timestamp = current_time('timestamp');
		$value[$plugin_path] = $timestamp;
	}
	return $value;
}