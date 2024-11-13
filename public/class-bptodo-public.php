<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bptodo_Public' ) ) {

	/**
	 * Class to add custom hooks for this plugin
	 *
	 * @since    1.0.0
	 * @author   Wbcom Designs
	 */
	class Bptodo_Public {

		/**
		 * Constructor.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 */
		public function __construct() {
			
			add_action('init', [$this, 'setup_cron_event']);

		}

		/**
		 * Enqueue styles.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function enqueue_styles() {
			wp_enqueue_style( 'datatables-css', BPTODO_PLUGIN_URL . 'assets/css/datatables.min.css', array(), BPTODO_VERSION, 'all' );
		}

		/**
		 * Enqueue scripts.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function enqueue_scripts() {
			wp_enqueue_script( 'datatables-js', BPTODO_PLUGIN_URL . 'assets/js/datatables.min.js', array( 'jquery' ), BPTODO_VERSION, false );			
		}
		
		public function setup_cron_event() {
			if ( ! wp_next_scheduled( 'bptodo_todo_notification' ) ) {
				wp_schedule_event( time(), 'every_six_hours', 'bptodo_todo_notification' );
			}
		}


		/**
		 * Handles the management of todo items' due dates.
		 * This function sends email and BuddyPress notifications for tasks that are due today or have already passed.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_manage_todo_due_date() {
			global $bptodo;

			// Query to get all incomplete todos that are due today or have passed
			$args = array(
				'post_type'      => 'bp-todo',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => 'todo_status',
						'value'   => 'complete',
						'compare' => '!=',
					),
					array(
						'key'     => 'todo_due_date',
						'value'   => gmdate('Y-m-d'),
						'compare' => '<=',
						'type'    => 'DATE',
					),
				),
			);

			// Execute the query
			$todo_query = new WP_Query($args);

			// Loop through each todo item and process it
			if ($todo_query->have_posts()) {
				while ($todo_query->have_posts()) {
					$todo_query->the_post();
					$todo_id = get_the_ID();
					$author_id = get_post_field('post_author', $todo_id);

					// Retrieve the user's mail settings
					$mail_setting = get_user_meta($author_id, 'todo_mail_setting', true);

					// Send email notification if configured
					$this->send_due_date_email($todo_id, $author_id, $mail_setting);

					// Send BuddyPress notification
					$this->send_due_date_notification($todo_id, $author_id);
				}
			}

			// Reset post data to avoid conflicts
			wp_reset_postdata();
		}

			/**
			 * Sends an email notification to the task owner if the task's due date is today.
			 *
			 * @since 1.0.0
			 * @access private
			 * @param int $todo_id The ID of the todo item.
			 * @param int $author_id The ID of the task owner.
			 * @param string $mail_settings The user's mail settings.
			 */
			private function send_due_date_email($todo_id, $author_id, $mail_settings) {
				// Check if the user has opted to receive email notifications
				if ('yes' === $mail_settings) {
					$email_sent = get_post_meta($todo_id, 'todo_last_day_mail_sent', true);
					if ('no' === $email_sent) {
						// Prepare email components
						$author_email = get_the_author_meta('user_email', $author_id);
						$site_name = get_bloginfo('name');
						$task_name = get_the_title($todo_id);
						/* translators: %2$s */
						$subject = sprintf(esc_html__('%1$s: Task "%2$s" Due Today', 'wb-todo'), $site_name, $task_name);
						/* translators: %1$s */
						$message = sprintf(esc_html__('Your task: %1$s is due today. Kindly finish it up! Thanks!', 'wb-todo'), $task_name);
						$headers = ['Content-Type: text/html; charset=UTF-8'];

						// Send the email
						wp_mail($author_email, $subject, $message, $headers);

						// Mark the email as sent to avoid duplicates
						update_post_meta($todo_id, 'todo_last_day_mail_sent', 'yes');
					}
				}
			}

			/**
			 * Sends a BuddyPress notification to the task owner if the task's due date is today.
			 *
			 * @since 1.0.0
			 * @access private
			 * @param int $todo_id The ID of the todo item.
			 * @param int $author_id The ID of the task owner.
			 */
			private function send_due_date_notification($todo_id, $author_id) {
				$notification_sent = get_post_meta($todo_id, 'todo_last_day_notification_sent', true);
				if ('no' === $notification_sent) {
					// Send the BuddyPress notification
					bp_notifications_add_notification(array(
						'user_id'           => $author_id,
						'item_id'           => $todo_id,
						'secondary_item_id' => get_current_user_id(),
						'component_name'    => 'bptodo_due_date',
						'component_action'  => 'bptodo_due_date_action',
						'date_notified'     => bp_core_current_time(),
						'is_new'            => 1,
					));

					// Mark the notification as sent to avoid duplicates
					update_post_meta($todo_id, 'todo_last_day_notification_sent', 'yes');
				}
			}



		/**
		 * Add To-Do items to the BuddyPress admin bar.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 * @param    array $wp_admin_nav Array of admin nav items.
		 */
		public function bptodo_setup_admin_bar( $wp_admin_nav = array() ) {
			// Fetch the global $wp_admin_bar and $bptodo objects.
			global $wp_admin_bar, $bptodo;

			// Get profile menu settings from the global $bptodo object.
			$profile_menu_slug         = $bptodo->profile_menu_slug;
			$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
			$my_todo_items             = $bptodo->my_todo_items;

			// Fetch the To-Do list settings.
			$todo_list_settings = get_option( 'user_todo_list_settings' );

			// Return early if To-Do lists are not enabled for members.
			if ( ! isset( $todo_list_settings['enable_todo_member'] ) ) {
				return;
			}

			// Proceed if the user is logged in.
			if ( is_user_logged_in() ) {
				// Generate base URLs.
				$base_url      = bp_loggedin_user_domain() . $profile_menu_slug;
				$todo_add_url  = trailingslashit( $base_url . '/add' );
				$todo_list_url = trailingslashit( $base_url . '/list' );

				// Add the main To-Do menu item to the BuddyPress admin bar.
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'my-account-buddypress',
						'id'     => 'my-account-' . esc_attr( $profile_menu_slug ),
						'title'  => esc_html( $profile_menu_label_plural ) . ' <span class="count">' . esc_html( $my_todo_items ) . '</span>',
						'href'   => esc_url( $todo_list_url ),
					)
				);

				// Add the "List" submenu.
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'my-account-' . esc_attr( $profile_menu_slug ),
						'id'     => 'my-account-' . esc_attr( $profile_menu_slug ) . '-list',
						'title'  => esc_html__( 'List', 'wb-todo' ),
						'href'   => esc_url( $todo_list_url ),
					)
				);

				// Add the "Add" submenu.
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'my-account-' . esc_attr( $profile_menu_slug ),
						'id'     => 'my-account-' . esc_attr( $profile_menu_slug ) . '-add',
						'title'  => esc_html__( 'Add', 'wb-todo' ),
						'href'   => esc_url( $todo_add_url ),
					)
				);
			}
		}


		/**
		 * Contain default settings.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 * @param    array $defaults contain default settings.
		 */
		public function bptodo_due_date_column_heading( $defaults ) {
			$defaults['due_date'] = esc_html__( 'Due Date', 'wb-todo' );
			$defaults['status']   = esc_html__( 'Status', 'wb-todo' );
			$defaults['todo_id']  = esc_html__( 'ID', 'wb-todo' );
			return $defaults;
		}

		/**
		 * Display custom column content for due date, status, and ID.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 * @param    string $column_name The name of the column being processed.
		 * @param    int    $post_id     The ID of the post.
		 */
		public function bptodo_due_date_column_content( $column_name, $post_id ) {
			// Initialize variables.
			$output = '';
			$meta_keys = array('todo_due_date', 'todo_status');
			$meta_values = get_post_meta($post_id, '', true);

			// Get the due date and status in a single query.
			$due_date = isset($meta_values['todo_due_date'][0]) ? $meta_values['todo_due_date'][0] : '';
			$todo_status = isset($meta_values['todo_status'][0]) ? $meta_values['todo_status'][0] : '';

			// Process columns.
			if ( 'due_date' === $column_name && $due_date ) {
				$output = esc_html(gmdate('F jS, Y', strtotime($due_date)));
			}

			if ( 'status' === $column_name && $due_date ) {
				$curr_date = date_create(gmdate('Y-m-d'));
				$due_date_obj = date_create($due_date);
				$diff = date_diff($curr_date, $due_date_obj);
				$diff_days = (int) $diff->format('%R%a');

				// Check and display the appropriate message based on due date and status.
				if ( 'complete' === $todo_status ) {
					$output = esc_html_e('Completed!', 'wb-todo');
				} elseif ( $diff_days < 0 ) {
					/* translators: %d */
					$output = sprintf(esc_html__('Expired %d days ago!', 'wb-todo'), abs($diff_days));
				} elseif ( 0 === $diff_days ) {
					$output = esc_html_e('Today is the last day to complete. Hurry Up!', 'wb-todo');
				} else {
					/* translators: %d */
					$output = sprintf(esc_html__('%d days left to complete the task!', 'wb-todo'), abs($diff_days));
				}
			}

			if ( 'todo_id' === $column_name ) {
				$output = esc_html($post_id);
			}

			echo esc_html( $output ) ;
		}


		/**
		 * Actions performed for adding component for due date of todo list.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 * @param    array $component_names contain default settings.
		 */
		public function bptodo_due_date_notifications_component( $component_names = array() ) {
			if ( ! is_array( $component_names ) ) {
				$component_names = array();
			}
			array_push( $component_names, 'bptodo_due_date' );
			return $component_names;
		}
		/**
		 * Function for check todo notification after every 6 hours.
		 *
		 * @param  mixed $schedules
		 * @return void
		 */
		public function bptodo_notification_cron_schedule( $schedules ) {
			$schedules['every_six_hours'] = array(
				'interval' => apply_filters( 'bptodo_notification_cron_schedule_interval', 21600 ), // Every 6 hours
				'display'  => __( 'Every 6 hours', 'wb-todo' ),
			);
			return $schedules;
		}

		/**
		 * Actions performed for formatting the notifications of bptodo due date
		 * and also for todo added in the group.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 * @param    string $action contain todo action.
		 * @param    int    $item_id contain item id.
		 * @param    int    $secondary_item_id contain secondory id.
		 * @param    string $total_items total items.
		 * @param    string $format contain format.
		 */
		public function bptodo_format_due_date_notifications( $content, $item_id, $secondary_item_id, $total_items, $format = 'string', $component_action_name = '', $component_name = '', $id = '' ) {
			global $bptodo;
			$action             = array();
			$profile_menu_label = $bptodo->profile_menu_label;
			if ( 'bptodo_due_date_action' === $component_action_name ) {
				$todo = get_post( $item_id );
				if ( ! empty( $todo ) ) {
					$todo_title = $todo->post_title;
					$todo_link  = get_permalink( $item_id );
					/* Translators: 1) Get a Plural Label Name 2) Display the To do title name*/
					$custom_title = sprintf( esc_html__( '%1$s due date arrived for task: %2$s', 'wb-todo' ), esc_html( $profile_menu_label ), esc_html( $todo_title ) );
					$custom_link  = $todo_link;
					/* Translators: 1) Get a Plural Label Name 2) Display the To do title name*/
					$custom_text = sprintf( esc_html__( 'Your %1$s: %2$s is due today. Please complete it as soon as possible.', 'wb-todo' ), esc_html( $profile_menu_label ), esc_html( $todo_title ) );

					/** WP Toolbar. */
					if ( 'string' === $format ) {
						$action = '<a href="' . esc_url( $custom_link ) . '" title="' . esc_attr( $custom_title ) . '">' . esc_html( $custom_text ) . '</a>';
					} else {
						/** Deprecated BuddyBar. */
						$action = array(
							'text' => $custom_text,
							'link' => $custom_link,
						);
					}
				}
			}

			//Notification send when todo added in the group
			if ( 'bptodo_group_todo_notifications' === $component_name ) {
				$get_gp_id   		= get_post_meta( $item_id, 'todo_group_id', true );
				$group       		= groups_get_group( array( 'group_id' => $get_gp_id ) );
				$bptodo_post 		= get_post( $item_id );
				$author_name        = bp_core_get_user_displayname( $secondary_item_id );
				$user_link          = bp_get_notifications_permalink( get_current_user_id() );
				$notification_title = isset( $bptodo_post->post_title ) ? $bptodo_post->post_title : '';
				$notification_guid  = isset( $bptodo_post->guid ) ? $bptodo_post->guid : '';
				/* translators: 1: todo title, 2: group name, 3: author name */
				$notification_string = sprintf( __( '%1$s todo added in group %2$s by %3$s.', 'wb-todo' ), $notification_title, $group->name, $author_name );
		
				if ( 'string' === $format ) {
					$action = "<a href='" . esc_url( $notification_guid ) . "'>" . esc_html( $notification_string ) . '</a>';
				} else {
					$action = array(
						'text' => $notification_string,
						'link' => $user_link,
					);
				}
			}
			
			return $action;
		}

		/**
		 * Function to register template location.
		 */
		public function bptodo_register_templates_pack() {
			if ( function_exists( 'bp_register_template_stack' ) ) {
				bp_register_template_stack( array( $this, 'bptodo_register_template_location' ) );
			}
		}

		/**
		 * Action performed for add todo tabs in groups menu.
		 */
		public function bptodo_add_todo_tabs_in_groups() {

			global $bp, $current_user, $bptodo;

			if ( ! bp_is_group() ) {
				return;
			}

			if ( ! function_exists( 'groups_get_groups' ) ) {
				return false;
			}
			if ( ! $bp->is_single_item ) {
				return false;
			}

			$profile_menu_label        = $bptodo->profile_menu_label;
			$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
			$profile_menu_slug         = sanitize_title( $bptodo->profile_menu_slug );
			$my_todo_items             = $bptodo->my_todo_items;
			$name                      = $profile_menu_slug;
			if ( function_exists( 'buddypress' ) && version_compare( buddypress()->version, '12.0', '>=' ) ) {
                $groups_link           = bp_get_group_url( $bp->groups->current_group );
            } else {
                $groups_link           = bp_get_group_permalink( $bp->groups->current_group );
            }
			$admin_link                = trailingslashit( $groups_link . $profile_menu_slug );

			// Common params to all nav items.
			$default_params = array(
				'parent_url'        => $admin_link,
				'parent_slug'       => $bp->groups->current_group->slug . '_' . $profile_menu_slug,
				'show_in_admin_bar' => true,
			);

			$sub_nav[]    = array_merge(
				array(
					'name'            => $profile_menu_label_plural,
					'slug'            => 'list',
					'screen_function' => array( $this, 'bind_bp_groups_page' ),
					'position'        => 0,
				),
				$default_params
			);
			$is_admin   = bp_group_is_admin();
			$can_list 	= false;
			$can_views  = bp_group_is_mod();
			
			if ( bp_group_is_mod() ) {
				$group_id    = $bp->groups->current_group->id;
				$mod_can_add = bptodo_list_group_modrator( $group_id, get_current_user_id() );
				$can_list    = true;
				if ( $mod_can_add ) {
					$can_list = false;
				}

				$mod_can_views = bptodo_report_view_enabled($group_id, get_current_user_id());
				if ($mod_can_views) {
					$can_views = false;
				}
			}

			if ( $is_admin || $can_list ) {
				$sub_nav[] = array_merge(
					array(
						'name'            => esc_html__( 'Add', 'wb-todo' ),
						'slug'            => 'add',
						'screen_function' => array( $this, 'bind_bp_groups_page' ),
						'position'        => 0,
					),
					$default_params
				);				
			}

			if( $is_admin || $can_views ){
				$sub_nav[] = array_merge(
					array(
						'name'            => esc_html__( 'Reports', 'wb-todo' ),
						'slug'            => 'reports',
						'screen_function' => array( $this, 'bind_bp_groups_page' ),
						'position'        => 0,
					),
					$default_params
				);
			}

			foreach ( $sub_nav as $nav ) {
				bp_core_new_subnav_item( $nav, 'groups' );
			}

		}

		/**
		 * Action performed for load group page template.
		 */
		public function bind_bp_groups_page() {
			add_action( 'bp_template_content', array( $this, 'show_group_events_profile_body' ) );
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/plugins' ) );
		}

		/**
		 * Display group events.
		 */
		public function show_group_events_profile_body() {
			do_action( 'eab-buddypress-group_events-before_events' );

			echo '<h3>Group Events</h3>';

			if ( function_exists( 'buddypress' ) && version_compare( buddypress()->version, '12.0', '>=' ) ) {
                $groups_link       = bp_get_group_url( $bp->groups->current_group ) . 'add-events/';
            } else {
                $groups_link       = bp_get_group_permalink( $bp->groups->current_group ) . 'add-events/';
            }
			$create_event_text = __( 'Create Event', 'wb-todo' );
			echo '<div class="wb-group-archive-add-event"><a href="' . esc_url( $groups_link ) . '">' . esc_html( $create_event_text ) . '</a></div>';

			$group_id = absint(bp_get_current_group_id()); // Sanitize the group ID
			echo do_shortcode('[eab_group_archives groups="' . $group_id . '" lookahead="yes"]');
			do_action( 'bptodo-buddypress-group_events-after_events' );
		}

		/**
		 * Action performed for regiter templates location.
		 */
		public function bptodo_register_template_location() {
			return BPTODO_PLUGIN_PATH . 'public/todo/group';
		}

		/**
		 * Actions performed on loading init: creating profile menu tab.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_member_profile_todo_tab() {
			if ( bp_is_my_profile() ) {
				global $bp, $bptodo, $current_user;

				$settings                  = get_option( 'user_todo_list_settings' );
				$profile_menu_label        = $bptodo->profile_menu_label;
				$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
				$bptodo_user_roles         = $bptodo->bptodo_user_roles;
				$user_roles                = $current_user->roles;

				if ( ( ! empty( $bptodo_user_roles ) || empty( $bptodo_user_roles ) ) && ! in_array( 'administrator', $user_roles ) ) {
					$common_roles = array_intersect( $user_roles, $bptodo_user_roles );
					if ( empty( $common_roles ) ) {
						return;
					}
				}
				$profile_menu_slug = sanitize_title( strtolower( $bptodo->profile_menu_label ) );
				$my_todo_items     = $bptodo->my_todo_items;
				$displayed_uid     = bp_displayed_user_id();
				$parent_slug       = $profile_menu_slug;
				$todo_menu_link    = bp_core_get_userlink( $displayed_uid, false, true ) . $parent_slug;

				$name               = bp_get_displayed_user_username();
				$tab_args           = array(
					'name'                    => esc_html( $profile_menu_label_plural ) . ' <span class="count">' . $my_todo_items . '</span>',
					'slug'                    => $profile_menu_slug,
					'screen_function'         => array( $this, 'todo_tab_function_to_show_screen' ),
					'position'                => 75,
					'default_subnav_slug'     => 'list',
					'show_for_displayed_user' => true,
				);
				$enable_todo_member = get_option( 'user_todo_list_settings' );
				if ( isset( $enable_todo_member['enable_todo_member'] ) ) {
					bp_core_new_nav_item( $tab_args );
				}

				/** Add subnav add new todo item. */
				bp_core_new_subnav_item(
					array(
						'name'            => esc_html__( 'Add', 'wb-todo' ),
						'slug'            => 'add',
						'parent_url'      => $todo_menu_link . '/',
						'parent_slug'     => $parent_slug,
						'screen_function' => array( $this, 'bptodo_add_todo_show_screen' ),
						'position'        => 200,
						'link'            => $todo_menu_link . '/add',
					)
				);

				/** Add subnav todo list items. */
				bp_core_new_subnav_item(
					array(
						'name'            => $profile_menu_label_plural,
						'slug'            => 'list',
						'parent_url'      => $todo_menu_link . '/',
						'parent_slug'     => $parent_slug,
						'screen_function' => array( $this, 'wbbp_todo_list_show_screen' ),
						'position'        => 100,
						'link'            => $todo_menu_link . '/list',
					)
				);
			}
		}

		/**
		 * Screen function for add todo menu item.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_add_todo_show_screen() {
			add_action( 'bp_template_title', array( $this, 'add_todo_tab_function_to_show_title' ) );
			add_action( 'bp_template_content', array( $this, 'add_todo_tab_function_to_show_content' ) );
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
		}

		/**
		 * Screen function for add todo menu item.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function add_todo_tab_function_to_show_title() {
			global $bptodo;

			$profile_menu_slug = $bptodo->profile_menu_slug;
			if ( isset( $_GET['args'] ) ) { //phpcs:ignore
				$todo_id = sanitize_text_field( wp_unslash( $_GET['args'] ) ); //phpcs:ignore
				$todo    = get_post( $todo_id );
				/* Translators: 1) Get a Plural Label Name 2) Get a to to title */
				echo sprintf( esc_html__( 'Edit %1$s : %2$s', 'wb-todo' ), esc_html( $profile_menu_slug ), esc_html( $todo->post_title ) );
			} else {
				/* Translators: Get a Singular Label Name */
				echo sprintf( esc_html__( 'Add a new %1$s in your list', 'wb-todo' ), esc_html( $profile_menu_slug ) );
			}
		}

		/**
		 * Screen function for add todo menu item.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function add_todo_tab_function_to_show_content() {
			global $bptodo;
			$profile_menu_label = $bptodo->profile_menu_label;
			$profile_menu_slug  = $bptodo->profile_menu_slug;
			if ( isset( $_GET['args'] ) ) {

				// Update todo items.
				if ( isset( $_POST['todo_update'] ) && isset( $_POST['save_update_todo_data_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['save_update_todo_data_nonce'] ) ), 'wp-bp-todo' ) ) {

					$cat      = isset( $_POST['todo_cat'] ) ? sanitize_text_field( wp_unslash( $_POST['todo_cat'] ) ) : '';
					$title    = isset( $_POST['todo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['todo_title'] ) ) : '';
					$summary  = isset( $_POST['bptodo-summary-input'] ) ? wp_kses_post( sanitize_text_field( wp_unslash( $_POST['bptodo-summary-input'] ) ) ) : '';
					$due_date = isset( $_POST['todo_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['todo_due_date'] ) ) : '';
					$priority = isset( $_POST['todo_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['todo_priority'] ) ) : '';
					$todo_id  = isset( $_POST['hidden_todo_id'] ) ? sanitize_text_field( wp_unslash( $_POST['hidden_todo_id'] ) ) : '';

					$taxonomy     = 'todo_category';
					$args         = array(
						'ID'           => $todo_id,
						'post_type'    => 'bp-todo',
						'post_status'  => 'publish',
						'post_title'   => $title,
						'post_content' => $summary,
						'post_author'  => get_current_user_id(),
					);
					$todo_post_id = wp_update_post( $args );

					update_post_meta( $todo_post_id, 'todo_status', 'incomplete' );
					update_post_meta( $todo_post_id, 'todo_due_date', $due_date );
					update_post_meta( $todo_post_id, 'todo_priority', $priority );

					wp_set_object_terms( $todo_post_id, $cat, $taxonomy );

					$todo_edit_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/list';

					if ( ! is_wp_error( $todo_post_id ) ) {
						bp_core_add_message(
							sprintf(
								/* translators: %s: */
								esc_html__( '%1$s added successfully !', 'wb-todo' ),
								esc_html( $profile_menu_label )
							)
						);
					} else {
						bp_core_add_message(
							__( 'There was a problem updating some of your profile information. Please try again.', 'wb-todo' ),
							'error'
						);
					}

					?>
						<script>
							window.location.replace('<?php echo esc_url( $todo_edit_url ); ?>');
						</script>
					<?php
				}

				include 'todo/member/member-todo-edit.php';
			} else {
				// Save todo items.
				if ( isset( $_POST['todo_create'] ) && isset( $_POST['save_new_todo_data_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['save_new_todo_data_nonce'] ) ), 'wp-bp-todo' ) ) {

					if ( isset( $_POST['todo_cat'] ) ) {
						$cat = sanitize_text_field( wp_unslash( $_POST['todo_cat'] ) );
					}

					if ( isset( $_POST['todo_title'] ) ) {
						$title = sanitize_text_field( wp_unslash( $_POST['todo_title'] ) );
					}

					if ( isset( $_POST['todo_due_date'] ) ) {
						$due_date = sanitize_text_field( wp_unslash( $_POST['todo_due_date'] ) );
					}

					if ( isset( $_POST['bptodo-summary-input'] ) ) {
						$summary = wp_kses_post( wp_unslash( $_POST['bptodo-summary-input'] ) );
					}

					if ( isset( $_POST['todo_priority'] ) ) {
						$priority = sanitize_text_field( wp_unslash( $_POST['todo_priority'] ) );
					}

					$taxonomy = 'todo_category';
					$args     = array(
						'post_type'    => 'bp-todo',
						'post_status'  => 'publish',
						'post_title'   => $title,
						'post_content' => $summary,
						'post_author'  => get_current_user_id(),
					);

					$to_do_id = wp_insert_post( $args );

					update_post_meta( $to_do_id, 'todo_status', 'incomplete' );
					update_post_meta( $to_do_id, 'todo_due_date', $due_date );
					update_post_meta( $to_do_id, 'todo_priority', $priority );
					update_post_meta( $to_do_id, 'todo_last_day_mail_sent', 'no' );
					update_post_meta( $to_do_id, 'todo_last_day_notification_sent', 'no' );

					wp_set_object_terms( $to_do_id, $cat, $taxonomy );
					$url = trailingslashit( bp_displayed_user_domain() . strtolower( $profile_menu_label ) . '/list' );

					if ( ! is_wp_error( $to_do_id ) ) {
						bp_core_add_message(
							sprintf(
								/* Translators: Display plural label name */
								esc_html__( '%1$s added successfully !', 'wb-todo' ),
								esc_html( $profile_menu_label )
							)
						);
					} else {
						bp_core_add_message( __( 'There was a problem updating some of your profile information. Please try again.', 'wb-todo' ), 'error' );
					}

					?>
						<script>
							window.location.replace('<?php echo esc_url( $url ); ?>');							
						</script>
					<?php
				}
				require 'todo/member/member-todo-add.php';
			}
		}

		/**
		 * Screen function for todo list menu item.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function wbbp_todo_list_show_screen() {
			add_action( 'bp_template_content', array( $this, 'list_todo_tab_function_to_show_content' ) );
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
		}

		/**
		 * Screen function for todo list content.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function list_todo_tab_function_to_show_content() {
			include 'todo/member/member-todo-list.php';
		}
	

		/**
		 * Actions performed to complete a todo.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_complete_todo() {
			global $bptodo;
			check_ajax_referer( 'bptodo-todo-nonce', 'ajax_nonce' );
			if ( isset( $_POST['action'] ) && 'bptodo_complete_todo' === $_POST['action'] ) {
				$due_date_str      = '';
				$due_date_td_class = '';
				$tid               = isset( $_POST['tid'] ) ? sanitize_text_field( wp_unslash( $_POST['tid'] ) ) : '';
				$user_id           = isset( $_POST['uid'] ) ? sanitize_text_field( wp_unslash( $_POST['uid'] ) ) : '';
				update_post_meta( $tid, 'todo_status', 'complete' );
				update_post_meta( $tid, 'todo_user_id', $user_id );
				update_post_meta( $tid, 'todo_complete_time', time() );
				$completed_todos = isset( $_POST['completed'] ) ? sanitize_text_field( wp_unslash( $_POST['completed'] ) ) : '';
				$all_todo        = isset( $_POST['all_todo'] ) ? sanitize_text_field( wp_unslash( $_POST['all_todo'] ) ) : '';
				(int) $completed_todos++;
				$avg_percentage = '';
				if ( $all_todo > 0 ) {
					$avg_percentage = ( $completed_todos * 100 ) / $all_todo;
				}

				$profile_menu_slug = $bptodo->profile_menu_slug;
				/** Add html of completed todo. */
				$todo               = get_post( $tid );
				$todo_title         = $todo->post_title;
				$todo_edit_url      = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;
				$todo_view_url      = get_permalink( $tid );
				$todo_status        = get_post_meta( $todo->ID, 'todo_status', true );
				$todo_priority      = get_post_meta( $todo->ID, 'todo_priority', true );
				$curr_date          = date_create( gmdate('Y-m-d') );
				$due_date           = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
				$diff               = date_diff( $curr_date, $due_date );
				$diff_days          = $diff->format( '%R%a' );
				$all_remaining_todo = 0;

				if ( $diff_days < 0 ) {
					/* translators: Number of expiry days */
					$due_date_str      = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
					$due_date_td_class = 'bptodo-expired';
				} elseif ( 0 === $diff_days ) {
					$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
					$due_date_td_class = 'bptodo-expires-today';
					$all_remaining_todo++;
				} else {
					/* translators: Number of left days */
					$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
					$all_remaining_todo++;
				}

				if ( 'complete' === $todo_status ) {
					$due_date_str      = esc_html__( 'Completed!', 'wb-todo' );
					$due_date_td_class = '';
				}

				if ( ! empty( $todo_priority ) ) {
					if ( 'critical' === $todo_priority ) {
						$priority_class = 'bptodo-priority-critical';
						$priority_text  = esc_html__( 'Critical', 'wb-todo' );
					} elseif ( 'high' === $todo_priority ) {
						$priority_class = 'bptodo-priority-high';
						$priority_text  = esc_html__( 'High', 'wb-todo' );
					} else {
						$priority_class = 'bptodo-priority-normal';
						$priority_text  = esc_html__( 'Normal', 'wb-todo' );
					}
				}

				$completed_html  = '';
				$completed_html .= '<tr id="bptodo-row-' . $tid . '">
				<td class="bptodo-priority"><span class="' . $priority_class . '">' . $priority_text . '</span></td>
				<td class="todo-completed">' . $todo_title . '</td>
				<td class="bp-to-do-actions">
				<ul>
				<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="' . esc_attr( $tid ) . '" title="' . /* translators: %s: */ sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), $todo_title ) . '"><i class="fa fa-times"></i></a></li>';
				if ( 'complete' !== $todo_status ) {

					$completed_html .= '<li><a href="' . esc_attr( $todo_edit_url ) . '" title="' . /* translators: %s: */ sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), $todo_title ) . '"><i class="fa fa-edit"></i></a></li>
					<li id="bptodo-complete-li-' . esc_attr( $tid ) . '"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="' . esc_attr( $tid ) . '" title="' . sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), $todo_title ) . '"><i class="fa fa-check"></i></a></li>';
				} else {
					$completed_html .= '<li><a href="" class="bptodo-undo-complete-todo" data-tid="' . $tid . '" title="' . /* translators: %s: */ sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), $todo_title ) . '"><i class="fa fa-undo"></i></a></li>';
				}
					$completed_html .= '<li><a href="' . esc_attr( $todo_view_url ) . '" title="' . /* translators: %s: */ sprintf( esc_html__( 'View: %s', 'wb-todo' ), $todo_title ) . '" target="_blank"><i class="fa fa-eye"></i></a></li>';
				$completed_html     .= '</ul></td></tr>';
				/** End of html of completed todo. */
				$response = array(
					'result'         => 'todo-completed',
					'completed_todo' => $completed_todos,
					'completed_html' => $completed_html,
					'avg_percentage' => round( $avg_percentage, 2 ),
					'due_date_str'   => $due_date_str,
				);
				echo wp_json_encode( $response );
				die;
			}
		}



		/**
		 * Actions performed to undo complete a todo.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_undo_complete_todo() {
			check_ajax_referer( 'bptodo-todo-nonce', 'ajax_nonce' );
			if ( isset( $_POST['action'] ) && 'bptodo_undo_complete_todo' === $_POST['action'] ) {
				if ( isset( $_POST['tid'] ) ) {
					$tid = sanitize_text_field( wp_unslash( $_POST['tid'] ) );
				}
				update_post_meta( $tid, 'todo_status', 'incomplete' );
				echo 'todo-undo-completed';
				die;
			}
		}

		/**
		 * Actions Performed To Add BP Todo Category.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_add_todo_category_front() {
			check_ajax_referer( 'bptodo-add-todo-category', 'security_nonce' );
			if ( isset( $_POST['action'] ) && 'bptodo_add_todo_category_front' === $_POST['action'] ) {
				if ( isset( $_POST['name'] ) ) {
					$term = sanitize_text_field( wp_unslash( $_POST['name'] ) );
				}
				$taxonomy    = 'todo_category';
				$term_exists = term_exists( $term, $taxonomy );
				if ( 0 === $term_exists || null === $term_exists ) {
					wp_insert_term( $term, $taxonomy );
				}
				echo 'todo-category-added';
				die;
			}
		}

		/**
		 * Actions performed to delete a todo.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_remove_todo() {
			check_ajax_referer( 'bptodo-todo-nonce', 'ajax_nonce' );
			if ( isset( $_POST['action'] ) && 'bptodo_remove_todo' === $_POST['action'] ) {
				if ( isset( $_POST['tid'] ) ) {
					$tid = sanitize_text_field( wp_unslash( $_POST['tid'] ) );
				}
				$primary_todo_id = get_post_meta( $tid, 'todo_primary_id', true );
				if ( $primary_todo_id ) {
					$associated_todo = get_post_meta( $primary_todo_id, 'botodo_associated_todo', true );
					foreach ( (array) $associated_todo as $key => $_todo_id ) {
						wp_delete_post( $_todo_id, true );
					}
				}
				wp_delete_post( $tid, true );
				echo 'todo-removed';
				die;
			}
		}

		/**
		 * Actions Performed To Export My Tasks.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_export_my_tasks() {
			check_ajax_referer( 'bptodo-export-todo', 'security_nonce' );
			if ( isset( $_POST['action'] ) && 'bptodo_export_my_tasks' === $_POST['action'] ) {
				$args   = array(
					'post_type'      => 'bp-todo',
					'post_status'    => 'publish',
					'author'         => get_current_user_id(),
					'posts_per_page' => -1,
				);
				$result = new WP_Query( $args );
				$todos  = $result->posts;
				$tasks  = array();
				if ( ! empty( $todos ) ) {
					foreach ( $todos as $key => $todo ) {
						$temp                  = array();
						$temp['Task ID']       = $todo->ID;
						$temp['Task Title']    = $todo->post_title;
						$temp['Task Summary']  = $todo->post_content;
						$temp['Task Due Date'] = get_post_meta( $todo->ID, 'todo_due_date', true );
						$temp['Task Status']   = get_post_meta( $todo->ID, 'todo_status', true );
						$tasks[ $key ]         = $temp;
					}
				}
				echo wp_json_encode( $tasks );
				die;
			}
		}

		/**
		 * Bptodo_edit_form_popup
		 *
		 * @return void
		 */
		public function bptodo_edit_form_popup() {
			if ( isset( $_POST['ajax_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ajax_nonce'] ) ), 'bptodo-todo-nonce' ) ) {
				$todo_id        = isset( $_POST['t_id'] ) ? sanitize_text_field( wp_unslash( $_POST['t_id'] ) ) : '';
				$todo_guid        = isset( $_POST['gu_id'] ) ? sanitize_text_field( wp_unslash( $_POST['gu_id'] ) ) : '';

				$component_type = isset( $_POST['component'] ) ? sanitize_text_field( wp_unslash( $_POST['component'] ) ) : '';
					ob_start();
				if ( 'member' == $component_type ) {
					include BPTODO_PLUGIN_PATH . 'public/todo/member/member-todo-edit.php';
				} elseif ( 'group' == $component_type ) {
					include BPTODO_PLUGIN_PATH . 'public/todo/group/edit.php';
				}
					$todo_edit = ob_get_clean();					
					echo $todo_edit; //phpcs:ignore
					\_WP_Editors::enqueue_scripts();
					print_footer_scripts();
					\_WP_Editors::editor_js();
					exit();
			}
		}
		

		/**
		 * Bptodo_add_div_edit_form
		 *
		 * @return void
		 */
		public function bptodo_add_div_edit_form() {

			echo '<div class="bptodo-modal modal" data-modal="trigger">
                    <div class="content-wrapper bptodo_edit_form_popup">
                    <button class="close"></button>
					<div class="edit_form_popup">
					</div>
						</div>
                    </div>';

		}

		public function bptodo_update_form_popup() {
			if ( isset( $_POST['ajax_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ajax_nonce'] ) ), 'bptodo-todo-nonce' ) ) {
				$update_form_data = isset( $_POST['update_form_data'] ) ? $_POST['update_form_data']  : ''; //phpcs:ignore				
				foreach ( $update_form_data as $key => $value ) {
					$todo_cat             = $update_form_data[0]['value'];
					$todo_title           = $update_form_data[1]['value'];
					$todo_summary         = $update_form_data[2]['value'];
					$todo_due_date        = $update_form_data[3]['value'];
					$todo_priority        = $update_form_data[4]['value'];
					$todo_todo_id         = $update_form_data[8]['value'] ?? false;
					$todo_primary_todo_id = $update_form_data[9]['value'] ?? false;
					$todo_group_id        = $update_form_data[10]['value'] ?? false;

					$cat             = isset( $todo_cat ) ? sanitize_text_field( $todo_cat ) : '';
					$title           = isset( $todo_title ) ? sanitize_text_field( wp_unslash( $todo_title ) ) : '';
					$summary         = isset( $todo_summary ) ? wp_kses_post( wp_unslash( $todo_summary ) ) : '';
					$due_date        = isset( $todo_due_date ) ? sanitize_text_field( wp_unslash( $todo_due_date ) ) : '';
					$priority        = isset( $todo_priority ) ? sanitize_text_field( wp_unslash( $todo_priority ) ) : '';
					$todo_id         = isset( $todo_todo_id ) ? sanitize_text_field( wp_unslash( $todo_todo_id ) ) : '';
					$primary_todo_id = isset( $todo_primary_todo_id ) ? sanitize_text_field( wp_unslash( $todo_primary_todo_id ) ) : '';

					$taxonomy = 'todo_category';
					$args     = array(
						'ID'           => $todo_id,
						'post_type'    => 'bp-todo',
						'post_status'  => 'publish',
						'post_title'   => $title,
						'post_content' => $summary,
						'post_author'  => get_current_user_id(),
					);
					$post_id  = wp_update_post( $args );
					update_post_meta( $post_id, 'todo_status', 'incomplete' );
					update_post_meta( $post_id, 'todo_due_date', $due_date );
					update_post_meta( $post_id, 'todo_priority', $priority );

					if ( ! empty( $todo_group_id ) ) {
						update_post_meta( $post_id, 'todo_group_id', $todo_group_id );
					}

					wp_set_object_terms( $post_id, $cat, $taxonomy );

					$associated_todo = array();

					$associated_todo = get_post_meta( $primary_todo_id, 'botodo_associated_todo', true );
					if ( is_array( $associated_todo ) ) {
						array_push( $associated_todo, $primary_todo_id );
					}
					foreach ( (array) $associated_todo as $key => $_todo_id ) {
						$args    = array(
							'ID'           => $_todo_id,
							'post_type'    => 'bp-todo',
							'post_status'  => 'publish',
							'post_title'   => $title,
							'post_content' => $summary,
						);
						$post_id = wp_update_post( $args );						

						update_post_meta( $post_id, 'todo_status', 'incomplete' );
						update_post_meta( $post_id, 'todo_due_date', $due_date );
						update_post_meta( $post_id, 'todo_priority', $priority );

						if ( ! empty( $todo_group_id ) ) {
							update_post_meta( $post_id, 'todo_group_id', $todo_group_id );
						}

						wp_set_object_terms( $post_id, $cat, $taxonomy );
					}
				}

				if ( is_array( $update_form_data ) ) {
					$update_data = array();
					foreach ( $update_form_data as $key => $value ) {
						$update_data[ $value['name'] ] = $value['value'];
					}
					$cat              = sanitize_text_field( $update_data['todo_cat'] );
					$title            = sanitize_text_field( $update_data['todo_title'] );
					$summary          = wp_kses_post( $update_data['bptodo-summary-input'] );
					$due_date         = sanitize_text_field( $update_data['todo_due_date'] );
					$priority         = sanitize_text_field( $update_data['todo_priority'] );
					$todo_id          = sanitize_text_field( $update_data['hidden_todo_id'] );
					$taxonomy         = 'todo_category';
						$args         = array(
							'ID'           => $todo_id,
							'post_type'    => 'bp-todo',
							'post_status'  => 'publish',
							'post_title'   => $title,
							'post_content' => $summary,
							'post_author'  => get_current_user_id(),
						);
						$todo_post_id = wp_update_post( $args );
						update_post_meta( $todo_post_id, 'todo_status', 'incomplete' );
						update_post_meta( $todo_post_id, 'todo_due_date', $due_date );
						update_post_meta( $todo_post_id, 'todo_priority', $priority );
						wp_set_object_terms( $todo_post_id, $cat, $taxonomy );
				}
			}
			exit();
		}

		/**
		 * Bptodo_before_submit_in_mail
		 *
		 * @return void
		 */
		public function bptodo_before_submit_in_mail() {
				$mail = get_user_meta( get_current_user_id(), 'todo_mail_setting' );
			?>
			<table class="notification-settings" id="activity-notification-settings">
		<thead>
			<tr>
				<th class="icon">&nbsp;</th>				
				<th class="title"><?php esc_html_e( 'To Do Mail', 'wb-todo' ); ?></th>
				<th class="yes"><?php esc_html_e( 'Yes', 'wb-todo' ); ?></th>
				<th class="no"><?php esc_html_e( 'No', 'wb-todo' ); ?></th>			
			</tr>
		</thead>

		<tbody>
			<?php if ( bp_activity_do_mentions() ) : ?>
					<tr id="activity-notification-settings-mentions">
					<td>&nbsp;</td>
					<td>
						<?php
						esc_html_e( 'Enable/disable mail option', 'wb-todo' );
						?>
					</td>
					<td class="yes"><input type="radio" name="notifications[todo_mail_setting]" id="todo-mail-settings-yes" value="yes"<?php checked( isset( $mail[0] ) ? $mail[0] : '', 'yes', true ); ?>/><label for="todo-mail-settings-yes" class="bp-screen-reader-text">
					<?php
						/* translators: accessibility text */
						esc_html_e( 'Yes, send email', 'wb-todo' );
					?>
					</label></td>
					<td class="no"><input type="radio" name="notifications[todo_mail_setting]" id="todo-mail-settings-no" value="no"<?php checked( isset( $mail[0] ) ? $mail[0] : '', 'no', true ); ?>/><label for="todo-mail-settings-no" class="bp-screen-reader-text">
					<?php
						/* translators: accessibility text */
						esc_html_e( 'No, do not send email', 'wb-todo' );
					?>
					</label></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
						<?php
		}

		/**
		 * Bptodo_mail_settings_after_save
		 *
		 * @return void
		 */
		public function bptodo_mail_settings_after_save() {
			if ( isset( $_POST['notifications'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'bp_settings_notifications' ) ) {
				$post_data = isset( $_POST['notifications']['todo_mail_setting'] ) ? sanitize_text_field( wp_unslash( $_POST['notifications']['todo_mail_setting'] ) ) : '';
				update_user_meta( bp_loggedin_user_id(), 'todo_mail_setting', $post_data );
			}

		}

		public function bp_nouveau_add_disable_group_todolists_checkbox() {
			global $bp;
			$group_id = $bp->groups->current_group->id;
			$group_disable_todos = groups_get_groupmeta( $group_id, 'group-disable-todos', true );
			printf(
				'<p class="bp-controls-wrap">
				<label for="group-disable-todos" class="bp-label-text">
					<input type="checkbox" name="group-disable-todos" id="group-disable-todos" value="1" %s/> %s
				</label>
			</p>',
				checked( $group_disable_todos, 1, false ),
				esc_html__( 'Disable todos tab?', 'wb-todo' )
			);
		}

		public function bp_nouveau_add_disable_group_todolists_details_edited( $group_id ) {
			if ( isset( $_POST['group-disable-todos'] ) ) { //phpcs:ignore
				groups_update_groupmeta( $group_id, 'group-disable-todos', $_POST['group-disable-todos'] ); //phpcs:ignore
			} else {
				groups_update_groupmeta( $group_id, 'group-disable-todos', '' );
			}
		}
		/**
		 * Register the shortcode - bptodo_by_categpry that will list all the todo items according to the category.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 * @param    string $atts contain attribute.
		 */
		public function bptodo_by_categpry_template( $atts ) {
			ob_start();
			if ( is_user_logged_in() ) {

				$shortcode_template = BPTODO_PLUGIN_PATH . 'public/todo/bptodo-by-category-template.php';
				if ( file_exists( $shortcode_template ) ) {
					include_once $shortcode_template;
				}
			} else {
				$shortcode_template_loggedout_user = BPTODO_PLUGIN_PATH . 'public/todo/bptodo-by-category-template-loggedout-user.php';
				if ( file_exists( $shortcode_template_loggedout_user ) ) {
					include_once $shortcode_template_loggedout_user;
				}
			}
			return ob_get_clean();
		}

		/**
		 * Function will trigger to register notification component
		 */
		public function bptodo_notifications_get_registered_components( $component_names = array() ) {
			// Force $component_names to be an array.
			if ( ! is_array( $component_names ) ) {
				$component_names = array();
			}
			// Add 'buddypress_member_review' component to registered components array.
			array_push( $component_names, 'bptodo_group_todo_notifications' );
			// Return component's with 'buddypress_member_review' appended.
			return $component_names;
		}
		

		/**
		 * Function will trigger notifications to member users
		 */
		public function bptodo_group_member_notification( $group_members_ids, $post_id ) {
			if ( ! array_key_exists( 'todo_group_members', $_POST ) ) { //phpcs:ignore
				return;
			}
			foreach ( $group_members_ids as $group_members_id ) {
				if ( bp_is_active( 'notifications' ) ) {
					$args = array(
						'user_id'           => $group_members_id,
						'item_id'           => $post_id,
						'secondary_item_id' => get_current_user_id(),
						'component_name'    => 'bptodo_group_todo_notifications',
						'component_action'  => 'bptodo_group_todo_notifications_action',
						'date_notified'     => bp_core_current_time(),
						'is_new'            => 1,
						'allow_duplicate'   => true,
					);
				}
				bp_notifications_add_notification( $args );
			}
		}
		
		/**
		 * Display group todo member specific report.
		 *
		 * @return void
		 */
		public function bptodo_display_group_todo_report(){
			if ( isset( $_GET['action'] ) && 'get_group_todo_report' === $_GET['action'] ) {
				check_ajax_referer( 'bptodo-todo-nonce', 'ajax_nonce' );
				
				$group_id         = isset( $_GET['group'] ) ? sanitize_text_field( wp_unslash( $_GET['group'] ) ) : '';
				$selected_todo_id = isset( $_GET['todo_id'] ) ? intval( $_GET['todo_id'] ) : null; // Get the selected todo ID if available
				
				$todos_table_data = array();
				$args = array(
					'post_type'      => 'bp-todo',
					'post_status'    => 'publish',
					'author'         => get_current_user_id(),
					'meta_key'       => 'todo_group_id',
					'meta_value'     => $group_id,
					'paged'          => 1,
					'orderby'        => 'date',
					'order'          => 'DESC',
				);
				$todo_query = get_posts($args);			
				$args = apply_filters( 'bptodo_admin_mod_gorup_report_view', 
					array(
						'group_id'            => $group_id,
						'exclude_admins_mods' => apply_filters( 'bptodo_exclude_moderator_view', false ),
					)
				);
				$group_members = groups_get_group_members( $args );
				$group_members_ids = array_column( $group_members['members'], 'ID' );

				if ( ! empty( $group_members_ids ) && is_array( $group_members_ids ) ) {
					foreach( $todo_query as $todos ) {
						$todo_id = $todos->ID;					
						$parent_todo = get_post_meta( $todo_id, 'todo_primary_id', true );					
						// Check if selected todo ID is set, and skip non-matching todos
						if ( $selected_todo_id && $selected_todo_id != $todo_id ) {
							continue;
						}

						if ( $parent_todo ) {
							$assoc_todos = get_post_meta( $parent_todo, 'botodo_associated_todo', true );						
							if ( $assoc_todos ) {
								foreach ( $assoc_todos as $assoc_todo ) {
									$member_todo_status  = get_post_meta( $assoc_todo, 'todo_status', true );
									$todo_author_id      = get_post_field( 'post_author', $assoc_todo );
									$member_todo_time    = get_post_meta( $assoc_todo, 'todo_complete_time', true );
									$member_todo_time    = $member_todo_time ? human_time_diff( current_time( 'timestamp' ), $member_todo_time ) . ' ago' : '-';									
									if ( in_array( $todo_author_id, $group_members_ids ) ) {
										$author_name = get_the_author_meta( 'display_name', $todo_author_id );
										$row = array(
											'Members'      => ucfirst( $author_name ),
											'Status'       => $member_todo_status,
											'Time'         => $member_todo_time,
										);
										$todos_table_data[] = $row;
									}
								}
							}
						}
					}
				}
				
				echo wp_json_encode(
					array(
						'data' => $todos_table_data,
					)
				);
				die;
			}
		}
	}
	new Bptodo_Public();
}