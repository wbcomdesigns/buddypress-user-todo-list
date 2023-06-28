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

		$this->plugin_name = 'wb-todo';

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

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-bptodo-scripts.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-bptodo-ajax.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-bptodo-cpt.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'inc/class-bptodo-globals.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/bptodo-plugin-genral-function.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wbcom/wbcom-admin-settings.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-bptodo-admin.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-bptodo-feedback.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/widget/class-user-todo.php';

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

		$plugin_i18n = new Bptodo_List_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Bptodo_Admin( $this->get_plugin_name(), $this->get_plugin_prefix(), $this->get_version() );
		$plugin_cpt   = new Bptodo_Cpt( $this->get_plugin_name(), $this->get_plugin_prefix(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_cpt, 'bptodo_create_cpt' );
		$this->loader->add_action( 'init', $plugin_cpt, 'bptodo_create_cpt_category' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'bptodo_add_menu_page' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'bptodo_register_general_settings' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'bptodo_register_shortcode_settings' );
		
		$this->loader->add_action( 'plugins_loaded', $plugin_admin, 'bptodo_save_general_settings' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_admin, 'bptodo_custom_variables' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'bptodo_admin_variables' );
		$this->loader->add_action( 'in_admin_header', $plugin_admin, 'bptodo_hide_all_admin_notices_from_setting_page' );
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

		$this->loader->add_action( 'bp_init', $plugin_public, 'bptodo_register_templates_pack', 5 );
		$this->loader->add_action( 'bp_setup_nav', $plugin_public, 'bptodo_add_todo_tabs_in_groups', 10 );
		$this->loader->add_action( 'bp_setup_nav', $plugin_public, 'bptodo_member_profile_todo_tab' );

		$this->loader->add_action( 'bptodo_todo_notification', $plugin_public, 'bptodo_manage_todo_due_date' );
		// add_action( 'bp_member_header_actions', array( $this, 'bptodo_add_todo_button_on_member_header' ) );.
		$this->loader->add_action( 'bp_setup_admin_bar', $plugin_public, 'bptodo_setup_admin_bar', 80 );
		$this->loader->add_filter( 'manage_bp-todo_posts_columns', $plugin_public, 'bptodo_due_date_column_heading', 10 );
		$this->loader->add_action( 'manage_bp-todo_posts_custom_column', $plugin_public, 'bptodo_due_date_column_content', 10, 2 );
		$this->loader->add_filter( 'bp_notifications_get_registered_components', $plugin_public, 'bptodo_due_date_notifications_component' );
		$this->loader->add_filter( 'bp_notifications_get_notifications_for_user', $plugin_public, 'bptodo_format_due_date_notifications', 10, 8 );
		$this->loader->add_filter( 'cron_schedules', $plugin_public, 'bptodo_notification_cron_schedule' );

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

		// Shortcode name must be the same as in shortcode_atts() third parameter.
		$this->loader->add_shortcode( $this->get_plugin_prefix() . 'shortcode', $plugin_public, 'pfx_shortcode_func' );		
		$this->loader->add_shortcode( 'bptodo_by_category', $plugin_public, 'bptodo_by_categpry_template' );

		$this->loader->add_action( 'wp_ajax_bptodo_edit_form_popup', $plugin_public, 'bptodo_edit_form_popup' );

		$this->loader->add_action( 'wp_footer', $plugin_public, 'bptodo_add_div_edit_form' );

		$this->loader->add_action( 'wp_ajax_bptodo_update_form_popup', $plugin_public, 'bptodo_update_form_popup' );

		$this->loader->add_action( 'bp_members_notification_settings_before_submit', $plugin_public, 'bptodo_before_submit_in_mail' );

		$this->loader->add_action( 'bp_core_notification_settings_after_save', $plugin_public, 'bptodo_mail_settings_after_save' );
		
		
		$this->loader->add_action( 'groups_custom_group_fields_editable', $plugin_public, 'bp_nouveau_add_disable_group_todolists_checkbox', 999 );
		$this->loader->add_action( 'groups_group_details_edited', $plugin_public, 'bp_nouveau_add_disable_group_todolists_details_edited', 999 );
		$this->loader->add_filter( 'bp_notifications_get_registered_components', $plugin_public, 'bptodo_notifications_get_registered_components' );
		$this->loader->add_filter( 'bp_notifications_get_notifications_for_user', $plugin_public, 'bptodo_gp_todo_notification_format', 10, 8 );
		$this->loader->add_action( 'bptodo_group_todo_submit', $plugin_public, 'bptodo_group_member_notification', 10, 2 );

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
