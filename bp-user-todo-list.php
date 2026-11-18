<?php
/**
 * Plugin Name: Todo for BuddyPress & BuddyBoss
 * Plugin URI: https://wbcomdesigns.com/downloads/buddypress-user-to-do-list/
 * Description: Personal and team todo lists for WordPress. Works standalone, and adds a member profile tab plus group todos when BuddyPress or BuddyBoss is active.
 * Version: 4.0.1
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * License: GPLv2+
 * Text Domain: wb-todo
 * Domain Path: /languages
 *
 * @link              www.wbcomdesigns.com
 * @since             1.0.0
 * @package           bp-user-todo-list
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Load plugin textdomain.
 */
// init, not plugins_loaded: loading a text domain before init triggers
// _load_textdomain_just_in_time on WordPress 6.7+.
add_action( 'init', 'bptodo_load_textdomain' );
function bptodo_load_textdomain() {
    // load_plugin_textdomain() already checks wp-content/languages/plugins first
    // (the WordPress.org language pack) and falls back to the bundled file, so
    // both paths work from this one call.
    load_plugin_textdomain( 'wb-todo', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}

// Constants used in the plugin.
if ( ! defined( 'BPTODO_PLUGIN_PATH' ) ) {
    define( 'BPTODO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BPTODO_PLUGIN_URL' ) ) {
    define( 'BPTODO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'BPTODO_VERSION' ) ) {
    define( 'BPTODO_VERSION', '4.0.1' );
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
 * Check for plugin updates and run upgrade routines.
 */
add_action( 'plugins_loaded', 'bptodo_check_plugin_update' );
function bptodo_check_plugin_update() {
    $installed_version = get_option( 'bptodo_version', '0.0.0' );

    if ( version_compare( $installed_version, BPTODO_VERSION, '<' ) ) {
        // Persist the new version FIRST so that if a one-off upgrade task fails, this
        // routine (and its side effects) can never re-run on every request and wedge
        // the site. Then defer the actual upgrade work to 'init', when BuddyPress email
        // post types + taxonomies are registered (plugins_loaded is too early - creating
        // the email posts there previously failed term assignment and triggered a delete
        // that could fatal via other plugins hooking wp_delete_post).
        update_option( 'bptodo_version', BPTODO_VERSION );
        update_option( 'bptodo_pending_upgrade_from', $installed_version );
    }

    if ( false !== get_option( 'bptodo_pending_upgrade_from', false ) ) {
        add_action( 'init', 'bptodo_run_pending_upgrade', 20 );
    }
}

/**
 * Run the deferred one-off upgrade tasks exactly once, on init.
 */
function bptodo_run_pending_upgrade() {
    $from = get_option( 'bptodo_pending_upgrade_from', false );
    if ( false === $from ) {
        return;
    }
    delete_option( 'bptodo_pending_upgrade_from' );
    bptodo_upgrade_routine( $from );

    // The bp-todo CPT/taxonomy became private in 4.0.0 (no public single pages,
    // archive or term URLs). Flush once so the stale /todo/ and /todo_category/
    // rewrite rules are dropped and those URLs stop resolving to todo data.
    flush_rewrite_rules();
}

/**
 * Plugin upgrade routine - runs when plugin is updated.
 *
 * @param string $from_version The version being upgraded from.
 */
function bptodo_upgrade_routine( $from_version ) {
    // Check if BuddyPress is active
    if ( ! function_exists( 'bp_is_active' ) ) {
        return;
    }

    // Load dependencies
    require_once plugin_dir_path( __FILE__ ) . 'inc/class-bptodo-globals.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-bptodo-emails.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-bptodo-data-retention.php';

    // Install/update email templates
    // These functions check if emails exist before creating them
    Bptodo_Emails::install_emails(); // Due date reminder email
    Bptodo_Emails::install_assigned_email(); // Assignment notification email

    // Add any future upgrade routines here based on version
    // Example:
    // if ( version_compare( $from_version, '3.5.0', '<' ) ) {
    //     // Upgrade routine for versions before 3.5.0
    // }
}

/**
 * Include needed files.
 */
/**
 * Instantiate the settings globals on every request (standalone-safe).
 *
 * $bptodo must exist even without BuddyPress so the frontend blocks/renderer and admin
 * can read the plugin's labels and settings. setup_globals() is BuddyPress-guarded; when
 * BuddyPress is present it re-runs on bp_init for full group context.
 */
add_action( 'init', 'bptodo_init_globals', 1 );
function bptodo_init_globals() {
    global $bptodo;
    if ( ! ( $bptodo instanceof Bptodo_Globals ) ) {
        $bptodo = new Bptodo_Globals();
        $bptodo->setup_globals();
    }
}

/**
 * Load the BuddyPress group extension - only when BuddyPress/BuddyBoss is active.
 */
add_action( 'bp_loaded', 'run_wp_bptodo_list' );
function run_wp_bptodo_list() {
    global $bptodo;

    // With BP_ENABLE_MULTIBLOG off, bp_loaded still fires on every subsite of a network
    // where BuddyPress is network-active, but groups only have screens on BP_ROOT_BLOG.
    // Keep the group layer off elsewhere (this used to be enforced by deactivating the
    // whole plugin on non-root subsites - see bptodo_check_buddypress_subsite_activation).
    if ( ! bptodo_is_bp_root_blog() ) {
        return;
    }

    if ( ! ( $bptodo instanceof Bptodo_Globals ) ) {
        $bptodo = new Bptodo_Globals();
        $bptodo->setup_globals();
    }

    // Group extension depends on $bptodo and BuddyPress groups.
	if ( ! bp_is_active( 'groups' ) ) {
		return;
	}
    $include_files = array(
        'public/class-bptodo-groups-extension-tab.php',
    );
    foreach ( $include_files as $include_file ) {
        include $include_file;
    }
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
 * Allow subscribers to upload media files only on BuddyPress todo pages.
 *
 * Uses user_has_cap filter to dynamically grant upload capability
 * instead of permanently modifying the subscriber role.
 *
 * @param array $allcaps All capabilities of the user.
 * @param array $caps    Required capabilities.
 * @param array $args    Arguments (capability being checked, user ID, etc).
 * @return array Modified capabilities.
 */
function bptodo_grant_upload_cap_on_todo_pages( $allcaps, $caps, $args ) {
	// Only process upload_files capability checks.
	if ( ! in_array( 'upload_files', $caps, true ) ) {
		return $allcaps;
	}

	// User already has the capability.
	if ( ! empty( $allcaps['upload_files'] ) ) {
		return $allcaps;
	}

	// Check if we're on a BuddyPress todo page.
	$is_todo_page = false;

	if ( function_exists( 'bp_is_user' ) && bp_is_user() ) {
		// Check if on member todo tab.
		$current_component = function_exists( 'bp_current_component' ) ? bp_current_component() : '';
		$todo_slug         = 'todo'; // Default slug.

		// Get custom slug from settings if available.
		$settings = get_option( 'user_todo_list_settings' );
		if ( ! empty( $settings['profile_menu_label'] ) ) {
			$todo_slug = sanitize_title( $settings['profile_menu_label'] );
		}

		if ( $current_component === $todo_slug ) {
			$is_todo_page = true;
		}
	}

	// Grant capability only on todo pages.
	if ( $is_todo_page ) {
		$allcaps['upload_files'] = true;
	}

	return $allcaps;
}
add_filter( 'user_has_cap', 'bptodo_grant_upload_cap_on_todo_pages', 10, 3 );

/**
 * Whether BuddyPress or BuddyBoss Platform is active.
 *
 * Since 4.0 the plugin works standalone (personal todos for any WordPress user);
 * BuddyPress/BuddyBoss is an optional enhancement (profile tab + group todos).
 *
 * @return bool
 */
function bptodo_is_bp_active() {
	return function_exists( 'buddypress' ) || class_exists( 'BuddyPress' );
}

/**
 * Whether the current site is a blog where BuddyPress itself runs.
 *
 * BuddyPress serves its member profiles and groups from a single blog - BP_ROOT_BLOG -
 * unless BP_ENABLE_MULTIBLOG is on, in which case it runs everywhere. On any other
 * subsite there are no BP profile/group URLs to hang our tabs off, so the group and
 * profile layers must stay out of the way there. Personal todos are unaffected: they
 * are plain CPT posts and work on every site.
 *
 * @since 4.0.0
 * @return bool
 */
function bptodo_is_bp_root_blog() {
	if ( ! is_multisite() ) {
		return true;
	}

	if ( defined( 'BP_ENABLE_MULTIBLOG' ) && BP_ENABLE_MULTIBLOG ) {
		return true;
	}

	// BP_ROOT_BLOG is always defined by this point (BuddyPress defines it, and this
	// plugin falls back to 1 above), so there is no is_main_site() branch to take.
	return defined( 'BP_ROOT_BLOG' )
		? (int) get_current_blog_id() === (int) BP_ROOT_BLOG
		: is_main_site();
}

/**
 * Whether the BuddyPress-dependent layer (profile tab + group todos) can run here.
 *
 * Needs BuddyPress/BuddyBoss active AND a blog where BuddyPress serves its screens.
 *
 * @since 4.0.0
 * @return bool
 */
function bptodo_bp_features_available() {
	return bptodo_is_bp_active() && bptodo_is_bp_root_blog();
}

/**
 * Register the modern Gutenberg blocks (Interactivity-powered, REST-backed).
 *
 * The personal 'My Todos' block works standalone; the group block loads only where the
 * BuddyPress layer is available (BP active, on the BP root blog).
 */
add_action( 'init', 'bptodo_register_blocks' );
function bptodo_register_blocks() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	register_block_type( BPTODO_PLUGIN_PATH . 'blocks/my-todos' );

	if ( bptodo_bp_features_available() && file_exists( BPTODO_PLUGIN_PATH . 'blocks/group-todos/block.json' ) ) {
		register_block_type( BPTODO_PLUGIN_PATH . 'blocks/group-todos' );
	}
}

/**
 * Load the modern list styles inside the block editor.
 *
 * The blocks are server-rendered via ServerSideRender, whose REST render pass
 * enqueues the design-system/components/blocks styles on the REST request - not
 * on the editor page - so the editor preview shows unstyled markup. `enqueue_block_assets`
 * runs inside the editor iframe (and the front end); we gate to the editor because
 * the front end already enqueues these on demand from Bptodo_Renderer::render().
 */
add_action( 'enqueue_block_assets', 'bptodo_enqueue_block_editor_styles' );
function bptodo_enqueue_block_editor_styles() {
	if ( ! is_admin() || ! class_exists( 'Bptodo_Renderer' ) ) {
		return;
	}
	Bptodo_Renderer::enqueue_styles();
}

/**
 * Surface the availability of the BuddyPress layer on the current site.
 *
 * Since 4.0 BuddyPress is OPTIONAL: personal todos are plain CPT posts and work on any
 * WordPress site, single or multisite. This check therefore never deactivates the plugin
 * - it only explains, via a dismissible notice, when the group/profile layer is not
 * available here so the site owner is not left wondering where those tabs went.
 *
 * Before 4.0.0 the multisite branch self-deactivated the plugin whenever BuddyPress was
 * not network-active, or the current blog was not BP_ROOT_BLOG. That killed personal
 * todos on every subsite of every multisite network, which contradicts standalone mode.
 */
function bptodo_check_buddypress_subsite_activation() {
	if ( is_multisite() && is_network_admin() ) {
		// Nothing site-specific to report from the network screens.
		return;
	}

	if ( ! bptodo_is_bp_active() ) {
		add_action( 'admin_notices', 'bptodo_plugin_admin_notice' );
		return;
	}

	// BuddyPress is active, but this subsite is not where it serves its screens, so the
	// group/profile layer stays off here (bptodo_bp_features_available()). Personal
	// todos keep working.
	if ( ! bptodo_is_bp_root_blog() ) {
		add_action( 'admin_notices', 'bptodo_show_bp_root_blog_notice' );
	}
}
add_action( 'admin_init', 'bptodo_check_buddypress_subsite_activation' );

/**
 * Notice: BuddyPress is active on the network but not on this subsite's blog.
 *
 * Informational and dismissible - the plugin stays active and personal todos still work.
 */
function bptodo_show_bp_root_blog_notice() {
	echo '<div class="notice notice-info is-dismissible"><p>';
	printf(
		/* Translators: %s: plugin name. */
		esc_html__( '%s: personal todo lists are active on this site. Group todos and the member profile tab are only available on the BuddyPress root blog.', 'wb-todo' ),
		'<strong>' . esc_html__( 'Todo for BuddyPress & BuddyBoss', 'wb-todo' ) . '</strong>'
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
	$bptodo_plugin = esc_html__( 'Todo for BuddyPress & BuddyBoss', 'wb-todo' );
	/* Translators: %s: plugin name. */
	echo '<div class="notice notice-info is-dismissible"><p>' . sprintf( esc_html__( '%s: personal todo lists are active. Install and activate BuddyPress or BuddyBoss Platform to also enable group todos and the member profile tab.', 'wb-todo' ), '<strong>' . esc_html( $bptodo_plugin ) . '</strong>' ) . '</p></div>';
}

/**
 * Screen function for todo list title.
 */
function list_todo_tab_function_to_show_title() {
    global $bptodo;
    $profile_menu_label = $bptodo->profile_menu_label;

    // Get displayed user ID with fallback
    $displayed_user_id = function_exists( 'bp_displayed_user_id' ) ? bp_displayed_user_id() : get_current_user_id();

    // Only check if user has any todos - no need to load all posts
    $args  = array(
        'post_type'      => 'bp-todo',
        'author'         => $displayed_user_id,
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    );
    $todos = get_posts( $args );
    if ( ! empty( $todos ) ) {
        $todo_export_nonce = wp_create_nonce( 'bptodo-export-todo' );
        ?>
        <input type="hidden" id="bptodo-export-todo-nonce" value="<?php echo esc_html( $todo_export_nonce ); ?>">
        <button type="button" id="export_my_tasks" class="bptodo-btn bptodo-btn--secondary">
            <svg class="bptodo-btn__icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span><?php echo esc_html__( 'Export', 'wb-todo' ); ?></span>
        </button>
        <?php
    }
}

/**
 * Activate group member todo.
 * NOTE: This function was causing bugs by resetting settings on every page load.
 * Settings are now properly initialized in the activator class.
 */
// Removed problematic function that was forcing incorrect settings values

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
 * Load the data retention / cleanup layer.
 *
 * This is NOT a BuddyPress feature: it is a scheduled cleanup of old `bp-todo` posts
 * driven by the `data-retention-settings` option, and it contains no BuddyPress calls.
 * Until 4.0.0 it was loaded behind the same bp_is_active() gate as the email
 * integration, so standalone sites got no cleanup cron at all even though the setting
 * was visible and saveable in the admin. It must load on every request so the cron job
 * stays scheduled (the class self-registers on include).
 */
add_action( 'plugins_loaded', 'bptodo_load_data_retention', 20 );
function bptodo_load_data_retention() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bptodo-data-retention.php';
}

/**
 * Load the BuddyPress email integration.
 *
 * This one genuinely needs BuddyPress: it registers BP email types and sends through
 * bp_send_email(). The class self-instantiates on bp_loaded.
 */
add_action( 'plugins_loaded', 'bptodo_load_bp_integrations', 20 );
function bptodo_load_bp_integrations() {
	if ( ! function_exists( 'bp_is_active' ) ) {
		return;
	}

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bptodo-emails.php';
}

/**
 * Register WP-CLI commands if WP-CLI is available.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-bptodo-cli-commands.php';
	WP_CLI::add_command( 'bptodo generate member-todos', array( 'BPTODO_CLI_Commands', 'member_todos' ) );
	WP_CLI::add_command( 'bptodo generate group-todos', array( 'BPTODO_CLI_Commands', 'group_todos' ) );
	WP_CLI::add_command( 'bptodo cleanup', array( 'BPTODO_CLI_Commands', 'cleanup' ) );
}

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
            // Modern toast/modal helper so the deactivate action can use it
            // instead of native confirm()/alert(). Loaded as a dependency.
            wp_enqueue_style('bptodo-modern-css', BPTODO_PLUGIN_URL . 'assets/css/bptodo-design-system.min.css', [], BPTODO_VERSION);
            wp_enqueue_script('bptodo-modern-js', BPTODO_PLUGIN_URL . 'assets/js/bptodo-modern.min.js', ['jquery'], BPTODO_VERSION, true);
            bptodo_localize_modern_i18n();
            wp_enqueue_script('bptodo-network-admin', plugin_dir_url(__FILE__) . 'admin/assets/js/bp-todo-list-admin.js', ['jquery', 'bptodo-modern-js'], BPTODO_VERSION, true);
            wp_localize_script('bptodo-network-admin', 'bptodo_network_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bptodo_network_nonce'),
                'i18n' => [
                    'confirmTitle'   => __('Network Deactivate', 'wb-todo'),
                    'confirmMessage' => __('Are you sure you want to deactivate this plugin across all sites?', 'wb-todo'),
                    'requestError'   => __('There was an error with the request.', 'wb-todo'),
                    'genericError'   => __('Error', 'wb-todo'),
                ],
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
