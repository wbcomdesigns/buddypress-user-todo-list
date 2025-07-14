<?php
/**
 * Plugin Name: Wbcom Designs BP Todo List
 * Plugin URI: https://wbcomdesigns.com/contact/
 * Description: This plugin allows users to create to-do items in their profile section and a simple interface to schedule their tasks.
 * Version: 3.3.0
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
    define( 'BPTODO_VERSION', '3.3.0' );
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
    // Ensure this is a multisite installation
        if ( is_multisite() ) {        
        $deactivate = false;
        // If we're in the network admin, check for network-wide BuddyPress activation
        if ( is_network_admin() ) {
            if ( ! is_plugin_active_for_network( 'buddypress/bp-loader.php' ) ) {
                // BuddyPress is not network-activated, deactivate the plugin
                $deactivate = true;
                add_action( 'network_admin_notices', 'bptodo_show_buddypress_root_blog_notice' );
            }
        } else {
            // Check if BP_ROOT_BLOG is defined
            if ( defined( 'BP_ROOT_BLOG' ) ) {
                if ( get_current_blog_id() != BP_ROOT_BLOG ) {
                    // This subsite is not the one defined by BP_ROOT_BLOG, deactivate plugin
                    $deactivate = true;
                    add_action( 'admin_notices', 'bptodo_show_bp_root_blog_notice' );
                }
            } else {
                // BP_ROOT_BLOG is not defined, so only activate on the main site
                if ( ! is_main_site() && ! is_plugin_active( 'buddypress/bp-loader.php' ) ) {
                    // BuddyPress is not active on this subsite, deactivate the plugin
                    $deactivate = true;
                    add_action( 'admin_notices', 'bptodo_show_buddypress_required_notice' );
                }
            }
        }

        // Deactivate the plugin if necessary
        if ( $deactivate ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            $activate = filter_input( INPUT_GET, 'activate', FILTER_SANITIZE_STRING );
            // Prevent the activation message from showing up after deactivation
            if ( isset( $activate ) ) { 
                unset( $activate );
            }
        }
    } else {
        if ( ! class_exists( 'BuddyPress' ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
			add_action( 'admin_notices', 'bptodo_plugin_admin_notice' );
		}
    }
}
add_action( 'admin_init', 'bptodo_check_buddypress_subsite_activation' );

/**
 * Admin notice when BP_ROOT_BLOG is defined and the plugin is activated on the wrong subsite.
 */
function bptodo_show_bp_root_blog_notice() {    
    echo '<div class="error"><p>';
	printf(
		// Translators: %s.
		esc_html__( '%1$s is only active on the subsite defined by BP_ROOT_BLOG.', 'wb-todo' ),
		'<strong>' . esc_html__( 'To-Do List', 'wb-todo' ) . '</strong>'
	);
	echo '</p></div>';
}

/**
 * Show admin notice to inform the user that To-Do List can only be activated on the BuddyPress root blog.
 */
function bptodo_show_buddypress_root_blog_notice() {   
    echo '<div class="error"><p>';
	printf(
		// Translators: %s.
		esc_html__( '%1$s can only be activated on the BuddyPress root blog.', 'wb-todo' ),
		'<strong>' . esc_html__( 'To-Do List', 'wb-todo' ) . '</strong>'
	);
	echo '</p></div>';
}

/**
 * Show admin notice to inform the user that BuddyPress is required for the To-Do List plugin.
 */
function bptodo_show_buddypress_required_notice() {    
    echo '<div class="error"><p>';
	printf(
		// Translators: %s.
		esc_html__( '%1$s requires BuddyPress to be active on this subsite. The plugin has been deactivated.', 'wb-todo' ),
		'<strong>' . esc_html__( 'To-Do List', 'wb-todo' ) . '</strong>'
	);
	echo '</p></div>';
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
	/* Translators: 1) BuddyPress Member To-Do List 2) BuddyPress  */
	echo '<div class="error"><p>' . sprintf( esc_html__( '%1$s is ineffective now as it requires %2$s to be installed and active.', 'wb-todo' ), '<strong>' . esc_html( $bptodo_plugin ) . '</strong>', '<strong>' . esc_html( $bp_plugin ) . '</strong>' ) . '</p></div>';
}

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
 * Redirect to settings page after activation.
 */
add_action( 'activated_plugin', 'bptodo_activation_redirect_settings' );
function bptodo_activation_redirect_settings( $plugin ) {
    if ( is_multisite() ) {
        return;
    } 
    if ( $plugin == plugin_basename( __FILE__ ) && class_exists( 'BuddyPress' ) ) {
        if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'activate' && isset( $_REQUEST['plugin'] ) && $_REQUEST['plugin'] === $plugin ) { //phpcs:ignore
            wp_redirect( admin_url( 'admin.php?page=user-todo-list-settings' ) );
            exit;
        }
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

/**
 * Advanced multisite management functionality.
 * Disabled by default for standard WordPress installations.
 */
if ( ! defined( 'BPTODO_ENABLE_ADVANCED_MULTISITE' ) ) {
    define( 'BPTODO_ENABLE_ADVANCED_MULTISITE', false );
}

if ( BPTODO_ENABLE_ADVANCED_MULTISITE ) {
    /**
     * Advanced multisite activation and management functions.
     * These provide enhanced multisite control for enterprise installations.
     */
    
    /**
     * Handle plugin activation across multisite network.
     *
     * @param string $plugin Plugin basename.
     * @param bool   $network_wide Whether plugin is network activated.
     */
    add_action('activated_plugin', 'bptodo_handle_multisite_activation', 10, 2);
    
    function bptodo_handle_multisite_activation($plugin, $network_wide) {
        if (is_multisite()) {
            bptodo_update_plugin_status('activate', false);
            bptodo_update_plugin_status('deactivate', true);
        }
    }
    
    /**
     * Update plugin activation status across multisite network.
     *
     * @param string $status Plugin status (activate/deactivate).
     * @param bool   $network_wide Whether to apply network-wide.
     */
    function bptodo_update_plugin_status($status, $network_wide = false){
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
    
    add_filter('network_admin_plugin_action_links_' . plugin_basename(__FILE__), 'bptodo_network_admin_links');
    
    /**
     * Customize network admin plugin action links.
     *
     * @param array $links Existing action links.
     * @return array Modified action links.
     */
    function bptodo_network_admin_links($links) {
        unset($links['activate']);
        $links['deactivate'] = '<a href="#" class="bptodo-network-deactivate" data-plugin="' . esc_attr(plugin_basename(__FILE__)) . '">' . esc_html__('Network Deactivate', 'wb-todo') . '</a>';
        return $links;
    }
    
    add_action('admin_enqueue_scripts', 'bptodo_enqueue_network_scripts');
    
    /**
     * Enqueue scripts for network administration.
     */
    function bptodo_enqueue_network_scripts() {
        if (is_network_admin()) {
            wp_enqueue_script('bptodo-network-admin', plugin_dir_url(__FILE__) . 'admin/assets/js/bp-todo-list-admin.js', ['jquery'], BPTODO_VERSION, true);
            wp_localize_script('bptodo-network-admin', 'bptodo_network_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bptodo_network_nonce'),
            ]);
        }
    }
    
    add_action('wp_ajax_bptodo_network_deactivate', 'bptodo_handle_network_deactivation');
    
    /**
     * Handle network-wide plugin deactivation.
     */
    function bptodo_handle_network_deactivation() {
        check_ajax_referer('bptodo_network_nonce', 'nonce');
        
        if (!current_user_can('manage_network_plugins')) {
            wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'wb-todo')]);
        }
        
        $sites = get_sites();
        foreach ($sites as $site) {
            bptodo_deactivate_for_site($site->blog_id);
        }
        wp_send_json_success(['message' => esc_html__('Plugin deactivated across all sites', 'wb-todo')]);
    }
    
    /**
     * Deactivate plugin for a specific site.
     *
     * @param int $site_id Site ID.
     */
    function bptodo_deactivate_for_site($site_id) {
        switch_to_blog($site_id);
        $active_plugins = get_option('active_plugins');
        $plugin_basename = plugin_basename(__FILE__);
        if (in_array($plugin_basename, $active_plugins)) {
            $active_plugins = array_diff($active_plugins, [$plugin_basename]);
            update_option('active_plugins', $active_plugins);
        }
        restore_current_blog();
    }
    
    add_filter('site_option_active_sitewide_plugins', 'bptodo_manage_network_activation', 99, 3);
    
    /**
     * Manage network activation display.
     *
     * @param mixed  $value Network option value.
     * @param string $option Option name.
     * @param int    $network_id Network ID.
     * @return mixed Modified option value.
     */
    function bptodo_manage_network_activation($value, $option, $network_id){
        if('active_sitewide_plugins' == $option && is_network_admin()) {
            $plugin_path = plugin_basename(__FILE__);
            $timestamp = current_time('timestamp');
            $value[$plugin_path] = $timestamp;
        }
        return $value;
    }
}