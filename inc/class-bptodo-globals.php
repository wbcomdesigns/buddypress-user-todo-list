<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bptodo_Globals' ) ) {

	/**
	 * Class to define global variables for this plugin.
	 *
	 * @since    1.0.0
	 * @author   Wbcom Designs
	 */
	class Bptodo_Globals {

		public $profile_menu_label;
		public $profile_menu_label_plural;
		public $profile_menu_slug;
		public $enable_todo_member;
		public $send_mail;
		public $req_duedate;
		public $send_notification;
		public $allow_user_add_category;
		public $my_todo_items;
		public $bptodo_user_roles;

		/**
		 * Constructor.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 */
		public function __construct() {
			// Setup globals on 'bp_init' so they're available for bp_setup_nav.
			// Priority 5 ensures this runs before nav setup (default priority 10).
			add_action( 'bp_init', [ $this, 'setup_globals' ], 5 );
		}

		/**
		 * Define all the global variable values.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 */
		public function setup_globals() {
			global $bptodo;
			$settings = get_option( 'user_todo_list_settings' );

			$this->profile_menu_label        = isset( $settings['profile_menu_label'] ) ? $settings['profile_menu_label'] : esc_html__( 'Todo', 'bp-user-to-do-list' );
			$this->profile_menu_label_plural = isset( $settings['profile_menu_label_plural'] ) ? $settings['profile_menu_label_plural'] : esc_html__( 'Todos', 'bp-user-to-do-list' );
			/*
			 * The slug must be a valid URL/nav slug. Labels like "Student's Todo"
			 * or "Todo for Student" previously produced slugs with spaces and
			 * apostrophes, which broke BP nav registration, subnav rendering and
			 * asset loading. sanitize_title() converts them to hyphenated slugs.
			 */
			$this->profile_menu_slug         = apply_filters( 'bptodo_profile_menu_slug', sanitize_title( $this->profile_menu_label ) );
			$this->enable_todo_member        = ( isset( $settings['enable_todo_member'] ) && $settings['enable_todo_member'] === 'yes' ) ? 'yes' : 'no';
			$this->allow_user_add_category   = ( isset( $settings['allow_user_add_category'] ) && $settings['allow_user_add_category'] === 'yes' ) ? 'yes' : 'no';
			$this->send_notification         = ( isset( $settings['send_notification'] ) && $settings['send_notification'] === 'yes' ) ? 'yes' : 'no';
			$this->send_mail                 = ( isset( $settings['send_mail'] ) && $settings['send_mail'] === 'yes' ) ? 'yes' : 'no';
			$this->req_duedate               = ( isset( $settings['req_duedate'] ) && $settings['req_duedate'] === 'yes' ) ? 'yes' : 'no';
			$this->bptodo_user_roles         = ! empty( $settings['bptodo_user_roles'] ) ? $settings['bptodo_user_roles'] : [];
			$this->my_todo_items             = $this->bptodo_count_my_todo_items();
		}

		/**
		 * Count current member todo items efficiently using direct database query.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @author   Wbcom Designs
		 * @return   int The count of todo items.
		 */
		private function bptodo_count_my_todo_items() {
			global $wpdb;

			$user_id = get_current_user_id();

			// Generate cache key based on user and group context
			$cache_key = 'bptodo_count_' . $user_id;
			if ( function_exists( 'bp_is_group' ) && bp_is_group() && function_exists( 'bp_get_current_group_id' ) ) {
				$cache_key .= '_group_' . absint( bp_get_current_group_id() );
			}

			// Try to get cached count
			$count = wp_cache_get( $cache_key, 'bptodo' );
			if ( false !== $count ) {
				return (int) $count;
			}

			// Base SQL query for counting posts
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names are safe, values are prepared below
			$sql          = "SELECT COUNT(p.ID) FROM {$wpdb->posts} p WHERE p.post_type = 'bp-todo' AND p.post_status = 'publish' AND p.post_author = %d";
			$prepare_args = [ $user_id ];

			// Add meta query for group filtering if needed
			if ( function_exists( 'bp_is_group' ) && bp_is_group() && function_exists( 'bp_get_current_group_id' ) ) {
				$group_id       = absint( bp_get_current_group_id() );
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names are safe, values are prepared below
				$sql           .= " AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = 'todo_group_id' AND pm.meta_value = %d)";
				$prepare_args[] = $group_id;
			} else {
				// Personal ("My Todos") badge must match the personal list, which excludes
				// group todos - otherwise the count (e.g. 26) disagrees with the rows (20).
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names are safe, no user input in this clause.
				$sql .= " AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} gx WHERE gx.post_id = p.ID AND gx.meta_key = 'todo_group_id')";
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Caching is handled above, SQL is built dynamically with prepare()
			$count = $wpdb->get_var( $wpdb->prepare( $sql, $prepare_args ) );

			// Cache for 5 minutes
			wp_cache_set( $cache_key, (int) $count, 'bptodo', 300 );

			return (int) $count;
		}

		/**
		 * Clear todo count cache for a specific user.
		 *
		 * @since 1.0.0
		 * @param int $user_id User ID to clear cache for.
		 * @param int $group_id Optional group ID if clearing group-specific cache.
		 */
		public static function clear_todo_count_cache( $user_id, $group_id = 0 ) {
			// Clear user's general todo count
			wp_cache_delete( 'bptodo_count_' . $user_id, 'bptodo' );

			// Clear group-specific count if group ID provided
			if ( $group_id ) {
				wp_cache_delete( 'bptodo_count_' . $user_id . '_group_' . $group_id, 'bptodo' );
			}
		}
	}
}
