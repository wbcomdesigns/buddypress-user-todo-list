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
			$this->setup_globals();
			add_action( 'wp', [$this, 'setup_globals']);
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

			$this->profile_menu_label        = isset( $settings['profile_menu_label'] ) ? $settings['profile_menu_label'] : esc_html__( 'Todo', 'wb-todo' );
			$this->profile_menu_label_plural = isset( $settings['profile_menu_label_plural'] ) ? $settings['profile_menu_label_plural'] : esc_html__( 'Todos', 'wb-todo' );
			$this->profile_menu_slug         = apply_filters( 'wbbptodo_slug', strtolower( $this->profile_menu_label ) );
			$this->enable_todo_member        = ! empty( $settings['enable_todo_member'] ) ? 'yes' : 'no';
			$this->allow_user_add_category   = ! empty( $settings['allow_user_add_category'] ) ? 'yes' : 'no';
			$this->send_notification         = ! empty( $settings['send_notification'] ) ? 'yes' : 'no';
			$this->send_mail                 = ! empty( $settings['send_mail'] ) ? 'yes' : 'no';
			$this->req_duedate               = ! empty( $settings['req_duedate'] ) ? 'yes' : 'no';
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

			// Base SQL query for counting posts
			$sql          = "SELECT COUNT(p.ID) FROM {$wpdb->posts} p WHERE p.post_type = 'bp-todo' AND p.post_status = 'publish' AND p.post_author = %d";
			$prepare_args = [ $user_id ];

			// Add meta query for group filtering if needed
			if ( bp_is_group() && function_exists( 'bp_get_current_group_id' ) ) {
				$group_id       = absint( bp_get_current_group_id() );
				$sql           .= " AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = 'todo_group_id' AND pm.meta_value = %d)";
				$prepare_args[] = $group_id;
			}

			$count = $wpdb->get_var( $wpdb->prepare( $sql, $prepare_args ) );

			return (int) $count;
		}
	}
}
