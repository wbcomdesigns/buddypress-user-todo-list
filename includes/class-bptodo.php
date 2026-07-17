<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */
class Bptodo_List {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Bptodo_List_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The unique prefix of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_prefix    The string used to uniquely prefix technical functions of this plugin.
	 */
	protected $plugin_prefix;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		if ( defined( 'BPTODO_VERSION' ) ) {
			$this->version = BPTODO_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		$this->plugin_name   = 'wb-todo';
		$this->plugin_prefix = 'bptodo_';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
	 * - Plugin_Name_i18n. Defines internationalization functionality.
	 * - Plugin_Name_Admin. Defines all hooks for the admin area.
	 * - Plugin_Name_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bptodo-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bptodo-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-bptodo-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-bptodo-public.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-bptodo-cpt.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'inc/class-bptodo-globals.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/bptodo-plugin-genral-function.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-bptodo-admin.php';

		// Card-panel admin controller (replaces the legacy admin/wbcom/ wrapper).
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/admin/class-bptodo-admin-panel.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-bptodo-feedback.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/widget/class-user-todo.php';
		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/bptodo-functions.php';

		// Domain layer — the single source of truth for the todo store, shared by REST,
		// the legacy AJAX handlers, blocks, CLI and templates. Loaded before anything
		// that queries or writes todos.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/domain/class-bptodo-todo.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/domain/class-bptodo-permissions.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/domain/class-bptodo-categories.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/domain/class-bptodo-todo-repository.php';

		// REST API — thin adapter over the domain layer (bptodo/v1).
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/rest/class-bptodo-rest-controller.php';

		// Modern SSR renderer (Interactivity API) — shared by blocks, shortcode and tabs.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/render/class-bptodo-renderer.php';

		$this->loader = new Bptodo_List_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		// The text domain is loaded by bptodo_load_textdomain() on init in the main
		// plugin file. Bptodo_List_I18n was unedited boilerplate that loaded the
		// domain "plugin-name", which no string in this plugin uses, and it ran on
		// plugins_loaded (too early for WordPress 6.7+). Nothing to wire here.
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		// Legacy admin service class. As of 3.6.0 its UI methods (menu,
		// enqueue, settings-page render, notice suppression) are NO LONGER
		// hooked — the new Bptodo_Admin_Panel owns the card-panel admin.
		// We retain this class only for its non-UI logic: settings option
		// registration + sanitizers (Group Todo, Data Retention, Shortcodes)
		// and the custom General-tab save handler, which the new General view
		// still posts to. The frontend asset loader (bptodo_variables) also
		// lives here and stays wired.
		// Bptodo_Admin's constructor takes no parameters and the class uses
		// neither plugin_name/prefix nor version (it reads BPTODO_VERSION /
		// BPTODO_PLUGIN_URL directly), so instantiate it with no arguments.
		$plugin_admin = new Bptodo_Admin();
		$plugin_cpt   = new Bptodo_Cpt( $this->get_plugin_name(), $this->get_plugin_prefix(), $this->get_version() );
		$this->loader->add_action( 'init', $plugin_cpt, 'bptodo_create_cpt' );
		$this->loader->add_action( 'init', $plugin_cpt, 'bptodo_create_cpt_category' );

		// Settings registration (option groups + sanitizers) stays on the
		// legacy class — preserves the exact option-save contract.
		$this->loader->add_action( 'admin_init', $plugin_admin, 'bptodo_register_general_settings' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'bptodo_register_shortcode_settings' );

		// Custom General-tab save path (self-POST, nonce `bptodo`).
		$this->loader->add_action( 'plugins_loaded', $plugin_admin, 'bptodo_save_general_settings' );

		// Frontend asset loader (still owned by the legacy class).
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_admin, 'bptodo_variables' );

		// New card-panel admin controller (menu + enqueue + render + hub).
		$bptodo_panel = new Bptodo_Admin_Panel();
		$bptodo_panel->register();
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Bptodo_Public( $this->get_plugin_name(), $this->get_plugin_prefix(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Register the bptodo/v1 REST API (thin adapter over the domain layer).
		$bptodo_rest = new Bptodo_REST_Controller();
		$this->loader->add_action( 'rest_api_init', $bptodo_rest, 'register_routes' );

		$this->loader->add_action( 'bp_init', $plugin_public, 'bptodo_register_templates_pack', 5 );
		$this->loader->add_action( 'bp_setup_nav', $plugin_public, 'bptodo_add_todo_tabs_in_groups', 10 );
		$this->loader->add_action( 'bp_setup_nav', $plugin_public, 'bptodo_member_profile_todo_tab' );

		$this->loader->add_action( 'bptodo_todo_notification', $plugin_public, 'bptodo_manage_todo_due_date' );
		
		$this->loader->add_action( 'bp_setup_admin_bar', $plugin_public, 'bptodo_setup_admin_bar', 80 );
		$this->loader->add_filter( 'manage_bp-todo_posts_columns', $plugin_public, 'bptodo_due_date_column_heading', 10 );
		$this->loader->add_action( 'manage_bp-todo_posts_custom_column', $plugin_public, 'bptodo_due_date_column_content', 10, 2 );
		$this->loader->add_filter( 'bp_notifications_get_registered_components', $plugin_public, 'bptodo_due_date_notifications_component' );
		$this->loader->add_filter( 'bp_notifications_get_notifications_for_user', $plugin_public, 'bptodo_format_due_date_notifications', 10, 8 );

		/** Export My Tasks. */
		$this->loader->add_action( 'wp_ajax_bptodo_export_my_tasks', $plugin_public, 'bptodo_export_my_tasks' );

		/** Remove a task. */
		$this->loader->add_action( 'wp_ajax_bptodo_remove_todo', $plugin_public, 'bptodo_remove_todo' );

		/** Complete a task. */
		$this->loader->add_action( 'wp_ajax_bptodo_complete_todo', $plugin_public, 'bptodo_complete_todo' );

		/** Undo complete a task. */
		$this->loader->add_action( 'wp_ajax_bptodo_undo_complete_todo', $plugin_public, 'bptodo_undo_complete_todo' );

		/** Add BP Todo Category. */
		$this->loader->add_action( 'wp_ajax_bptodo_add_todo_category_front', $plugin_public, 'bptodo_add_todo_category_front' );

		// Register shortcode for displaying todos by category (legacy, kept for backward compatibility).
		$this->loader->add_shortcode( 'bptodo_by_category', $plugin_public, 'bptodo_by_category_template' );

		// Register unified shortcode with full parameter support.
		// Usage: [bptodo_list user_id="all" category="work" status="incomplete" per_page="10"]
		$this->loader->add_shortcode( 'bptodo_list', $plugin_public, 'bptodo_list_shortcode' );

		$this->loader->add_action( 'wp_ajax_bptodo_update_form_popup', $plugin_public, 'bptodo_update_form_popup' );

		// AJAX handler for getting individual todo card HTML (for smooth card updates)
		$this->loader->add_action( 'wp_ajax_bptodo_get_todo_card_html', $plugin_public, 'bptodo_get_todo_card_html' );

		$this->loader->add_action( 'bp_members_notification_settings_before_submit', $plugin_public, 'bptodo_before_submit_in_mail' );

		$this->loader->add_action( 'bp_core_notification_settings_after_save', $plugin_public, 'bptodo_mail_settings_after_save' );
		
		
		$this->loader->add_action( 'groups_custom_group_fields_editable', $plugin_public, 'bp_nouveau_add_disable_group_todolists_checkbox', 999 );
		$this->loader->add_action( 'groups_group_details_edited', $plugin_public, 'bp_nouveau_add_disable_group_todolists_details_edited', 999 );
		$this->loader->add_filter( 'bp_notifications_get_registered_components', $plugin_public, 'bptodo_notifications_get_registered_components' );
		$this->loader->add_action( 'bptodo_group_todo_submit', $plugin_public, 'bptodo_group_member_notification', 10, 2 );
		$this->loader->add_action( 'bptodo_group_todo_submit', $plugin_public, 'bptodo_group_member_assignment_email', 10, 2 );
		$this->loader->add_action( 'wp_ajax_get_group_todo_report', $plugin_public, 'bptodo_display_group_todo_report' );

		// Process todo form submissions early (before output starts) to allow redirects
		$this->loader->add_action( 'template_redirect', $plugin_public, 'bptodo_process_form_submissions', 5 );

		// Clear cache when todos are created, updated, or deleted
		$this->loader->add_action( 'save_post_bp-todo', $plugin_public, 'bptodo_clear_cache_on_save', 10, 3 );
		$this->loader->add_action( 'before_delete_post', $plugin_public, 'bptodo_clear_cache_on_delete', 10, 2 );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The unique prefix of the plugin used to uniquely prefix technical functions.
	 *
	 * @since     1.0.0
	 * @return    string    The prefix of the plugin.
	 */
	public function get_plugin_prefix() {
		return $this->plugin_prefix;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Bptodo_List_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
