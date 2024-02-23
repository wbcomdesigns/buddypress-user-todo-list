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
	 * Class to define global variable for this plugin
	 *
	 * @since    1.0.0
	 * @author   Wbcom Designs
	 */
	class Bptodo_Globals {

		/**
		 * Variable contain all label text.
		 *
		 * @since    1.0.0
		 * @author   Wbcom Designs
		 * @access   public
		 * @var      string $profile_menu_label contain all label text.
		 */
		public $profile_menu_label;

		/**
		 * Variable contains label plural text.
		 *
		 * @since    1.0.0
		 * @author   Wbcom Designs
		 * @access   public
		 * @var      string $profile_menu_label_plural contains label plural text.
		 */
		public $profile_menu_label_plural;

		/**
		 * Variable contains profile slug text.
		 *
		 * @since    1.0.0
		 * @author   Wbcom Designs
		 * @access   public
		 * @var      string $profile_menu_slug contains profile slug text.
		 */
		public $profile_menu_slug;


		/**
		 * Variable contains enable member setting.
		 *
		 * @since    1.0.0
		 * @author   Wbcom Designs
		 * @access   public
		 * @var      string $enable_todo_member contains group setting.
		 */
		public $enable_todo_member;


		/**
		 * Variable contains enable group setting.
		 *
		 * @since    1.0.0
		 * @author   Wbcom Designs
		 * @access   public
		 * @var      string $enable_todo_group conatins group setting.
		 */
		// public $enable_todo_group;

		/**
		 * Variable contains email notification setting.
		 *
		 * @since    1.0.0
		 * @author   Wbcom Designs
		 * @access   public
		 * @var      string $send_mail contains email notification setting.
		 */
		public $send_mail;


		public $req_duedate;


		/**
		 * Variable contains email notification setting.
		 *
		 * @since    1.0.0
		 * @author   Wbcom Designs
		 * @access   public
		 * @var      string $send_mail contains email notification setting.
		 */
		public $send_notification;

		/**
		 * Variable contains contains category setting.
		 *
		 * @since    1.0.0
		 * @author   Wbcom Designs
		 * @access   public
		 * @var      string $allow_user_add_category contains category setting.
		 */
		public $allow_user_add_category;

		/**
		 * Variable contains todo list item.
		 *
		 * @since    1.0.0
		 * @author   Wbcom Designs
		 * @access   public
		 * @var      string $my_todo_items contains todo list item.
		 */
		public $my_todo_items;

		/**
		 * For user roles
		 *
		 * @var array
		 */
		public $bptodo_user_roles;

		/**
		 * Variable contains hide btton settings.
		 *
		 * @since    2.2.1
		 * @author   Wbcom Designs
		 * @access   public
		 * @var      string $hide_button contains values.
		 */
		// public $hide_button;

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
			$this->profile_menu_label        = esc_html__( 'To-Do', 'wb-todo' );
			$this->profile_menu_label_plural = esc_html__( 'To-Dos', 'wb-todo' );
			if ( isset( $settings['profile_menu_label'] ) ) {
				$this->profile_menu_label = $settings['profile_menu_label'];
			}

			if ( isset( $settings['profile_menu_label_plural'] ) ) {
				$this->profile_menu_label_plural = $settings['profile_menu_label_plural'];
			}

			$this->profile_menu_slug = apply_filters( 'wbbptodo_slug', strtolower( $this->profile_menu_label ) );

			// $this->profile_menu_slug         = str_replace( ' ', '-', strtolower( $this->profile_menu_label ) );

			/** Allow User To Add Todo Tab in Member. */
			$this->enable_todo_member = 'no';
			if ( ! empty( $settings['enable_todo_member'] ) ) {
				$this->enable_todo_member = 'yes';
			}

			/** Allow User To Add Todo Category. */
			$this->allow_user_add_category = 'no';
			if ( ! empty( $settings['allow_user_add_category'] ) ) {
				$this->allow_user_add_category = 'yes';
			}

			/** Send Notification. */
			$this->send_notification = 'no';
			if ( ! empty( $settings['send_notification'] ) ) {
				$this->send_notification = 'yes';
			}

			/** Send Mail. */
			$this->send_mail = 'no';
			if ( ! empty( $settings['send_mail'] ) ) {
				$this->send_mail = 'yes';
			}

			/** Due Date Require or not. */
			$this->req_duedate = 'no';
			if ( ! empty( $settings['req_duedate'] ) ) {
				$this->req_duedate = 'yes';
			}
			$this->bptodo_user_roles = [];
			if ( ! empty( $settings['bptodo_user_roles'] ) ) {
				$this->bptodo_user_roles = $settings['bptodo_user_roles'];
			}			

			/** Send Mail. */
			// $this->hide_button = 'no';
			// if ( ! empty( $settings['hide_button'] ) ) {
			// $this->hide_button = 'yes';
			// }

			/** Count my todo items. */
			$this->my_todo_items = $this->bptodo_count_my_todo_items();
		}

		/**
		 * Count current member todo items.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @author   Wbcom Designs
		 */
		public function bptodo_count_my_todo_items() {
			global $bp;
			$args  = array(
				'post_type'      => 'bp-todo',
				'author'         => get_current_user_id(),
				'post_staus'     => 'publish',
				'posts_per_page' => -1,
			);
			if ( bp_is_group() && function_exists( 'bp_get_current_group_id' ) ) {
				// Ensure $group_id is a non-negative integer
				$group_id = absint(bp_get_current_group_id());		
				$args['meta_query'] = [
										array(
											'key'   => 'todo_group_id',
											'value' => $group_id,
											'compare'	=> '='
										)
							];
			}
			
			$todos = get_posts( $args );
			return count( $todos );
		}
	}
}
