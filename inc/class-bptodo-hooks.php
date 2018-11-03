<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bptodo_Hooks' ) ) {

	/**
	 * Class to add custom hooks for this plugin
	 *
	 * @since    1.0.0
	 * @author   Wbcom Designs
	 */
	class Bptodo_Hooks {

		/**
		 * Constructor.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'bptodo_manage_todo_due_date' ) );
			add_action( 'bp_member_header_actions', array( $this, 'bptodo_add_review_button_on_member_header' ) );
			add_action( 'bp_setup_admin_bar', array( $this, 'bptodo_setup_admin_bar' ), 80 );
			add_filter( 'manage_bp-todo_posts_columns', array( $this, 'bptodo_due_date_column_heading' ), 10 );
			add_action( 'manage_bp-todo_posts_custom_column', array( $this, 'bptodo_due_date_column_content' ), 10, 2 );
			add_filter( 'bp_notifications_get_registered_components', array( $this, 'bptodo_due_date_notifications_component' ) );
			add_filter( 'bp_notifications_get_notifications_for_user', array( $this, 'bptodo_format_due_date_notifications' ), 10, 5 );
			add_shortcode( 'bptodo_by_category', array( $this, 'bptodo_by_categpry_template' ) );
		}

		/**
		 * Actions performed to send mails and notifications whose due date has arrived.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 */
		public function bptodo_manage_todo_due_date() {
			global $bptodo;
			$args       = array(
				'post_type'      => 'bp-todo',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'order_by'       => 'name',
				'order'          => 'ASC',
			);
			$todo_items = get_posts( $args );
			if ( ! empty( $todo_items ) ) {
				foreach ( $todo_items as $key => $todo ) {
					$todo_status = get_post_meta( $todo->ID, 'todo_status', true );
					if ( 'complete' != $todo_status ) {
						$author_id = $todo->post_author;
						$curr_date = date_create( date( 'Y-m-d' ) );
						$due_date  = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
						$diff      = date_diff( $curr_date, $due_date );
						$diff_days = $diff->format( '%R%a' );

						/** Check if mail sending is allowed. */
						if ( ! empty( $bptodo->send_mail ) && 'yes' == $bptodo->send_mail ) {
							/** If today is the due date. */
							if ( 0 == $diff_days ) {
								/** If the mail is not sent already. */
								$due_date_mail_sent = get_post_meta( $todo->ID, 'todo_last_day_mail_sent', true );
								if ( 'no' == $due_date_mail_sent ) {
									$author       = get_userdata( $author_id );
									$author_email = $author->data->user_email;
									$subject      = esc_html__( 'BP Task - WordPress', 'wb-todo' );
									$messsage     = sprintf( esc_html__( 'Your task: %1$s is going to exipre today. Kindly finish it up! Thanks!', 'wb-todo' ), esc_html( $todo->post_title ) );
									wp_mail( $author_email, $subject, $messsage );
									update_post_meta( $todo->ID, 'todo_last_day_mail_sent', 'yes' );
								}
							}
						}

						/** Check if notification sending is allowed. */
						if ( ! empty( $bptodo->send_notification ) && $bptodo->send_notification == 'yes' ) {
							/** If today is the due date. */
							if ( 0 == $diff_days ) {
								/** If the mail is not sent already. */
								$due_date_notification_sent = get_post_meta( $todo->ID, 'todo_last_day_notification_sent', true );
								if ( 'no' == $due_date_notification_sent ) {
									/** Send notification for appectance. */
									bp_notifications_add_notification(
										array(
											'user_id' => $author_id,
											'item_id' => $todo->ID,
											'secondary_item_id' => get_current_user_id(),
											'component_name' => 'bptodo_due_date',
											'component_action' => 'bptodo_due_date_action',
											'date_notified' => bp_core_current_time(),
											'is_new'  => 1,
										)
									);
									update_post_meta( $todo->ID, 'todo_last_day_notification_sent', 'yes' );
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Actions performed to add a review button on member header.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 */
		public function bptodo_add_review_button_on_member_header() {
			global $bptodo;
			$profile_menu_label = $bptodo->profile_menu_label;
			$profile_menu_slug  = $bptodo->profile_menu_slug;
			if ( bp_displayed_user_id() === bp_loggedin_user_id() ) {
				$todo_add_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add';
				?>
				<div id="bptodo-add-todo-btn" class="generic-button">
					<a href="<?php echo esc_attr( $todo_add_url ); ?>" class="add-todo"><?php echo sprintf( esc_html__( 'Add %1$s', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?></a>
				</div>
				<?php
			}
		}

		/**
		 * Contain admin nav item.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 * @param    array $wp_admin_nav contain admin nav item.
		 */
		public function bptodo_setup_admin_bar( $wp_admin_nav = array() ) {
			global $wp_admin_bar, $bptodo;
			$profile_menu_slug         = $bptodo->profile_menu_slug;
			$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
			$my_todo_items             = $bptodo->my_todo_items;

			$base_url      = bp_loggedin_user_domain() . $profile_menu_slug;
			$todo_add_url  = $base_url . '/add';
			$todo_list_url = $base_url . '/list';
			if ( is_user_logged_in() ) {
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'my-account-buddypress',
						'id'     => 'my-account-' . $profile_menu_slug,
						'title'  => $profile_menu_label_plural . ' <span class="count">' . $my_todo_items . '</span>',
						'href'   => trailingslashit( $todo_list_url ),
					)
				);

				/** Add add-new submenu. */
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'my-account-' . $profile_menu_slug,
						'id'     => 'my-account-' . $profile_menu_slug . '-list',
						'title'  => esc_html__( 'List', 'wb-todo' ),
						'href'   => trailingslashit( $todo_list_url ),
					)
				);

				/** Add add-new submenu. */
				$wp_admin_bar->add_menu(
					array(
						'parent' => 'my-account-' . $profile_menu_slug,
						'id'     => 'my-account-' . $profile_menu_slug . '-add',
						'title'  => esc_html__( 'Add', 'wb-todo' ),
						'href'   => trailingslashit( $todo_add_url ),
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
		 * Contain default settings.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @author   Wbcom Designs
		 * @param    array $column_name contain default settings.
		 * @param    int   $post_id contain post id.
		 */
		public function bptodo_due_date_column_content( $column_name, $post_id ) {
			$due_date_str      = '';
			$due_date_td_class = '';

			if ( 'due_date' == $column_name ) {
				$due_date = get_post_meta( $post_id, 'todo_due_date', true );
				echo esc_html( date( 'F jS, Y', strtotime( $due_date ) ) );
			}

			if ( 'status' == $column_name ) {
				$todo_status = get_post_meta( $post_id, 'todo_status', true );
				$curr_date   = date_create( date( 'Y-m-d' ) );
				$due_date    = date_create( get_post_meta( $post_id, 'todo_due_date', true ) );
				$diff        = date_diff( $curr_date, $due_date );
				$diff_days   = $diff->format( '%R%a' );
				if ( $diff_days < 0 ) {
					$due_date_str = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
				} elseif ( 0 == $diff_days ) {
					$due_date_str = esc_html__( 'Today is the last day to complete. Hurry Up!','wb-todo');
				} else {
					$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
				}

				if ( 'complete' == $todo_status ) {
					$due_date_str = esc_html__( 'Completed!', 'wb-todo' );
				}

				echo esc_html( $due_date_str );
			}

			if ( 'todo_id' == $column_name ) {
				echo esc_html( $post_id );
			}
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
		 * Actions performed for formatting the notifications of bptodo due date.
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
		public function bptodo_format_due_date_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

			global $bptodo;
			$profile_menu_label = $bptodo->profile_menu_label;
			if ( 'bptodo_due_date_action' === $action ) {
				$todo = get_post( $item_id );
				if ( ! empty( $todo ) ) {
					$todo_title = $todo->post_title;
					$todo_link  = get_permalink( $item_id );

					$custom_title = sprintf( esc_html__( '%1$s due date arrived for task: %2$s', 'wb-todo' ), esc_html( $profile_menu_label ), esc_html( $todo_title ) );
					$custom_link  = $todo_link;
					$custom_text  = sprintf( esc_html__( 'Your %1$s: %2$s is due today. Please complete it as soon as possible.', 'wb-todo' ), esc_html( $profile_menu_label ), esc_html( $todo_title ) );

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
			return $action;
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
			if ( is_user_logged_in() ) {
				$shortcode_template = BPTODO_PLUGIN_PATH . 'inc/todo/bptodo-by-category-template.php';
				if ( file_exists( $shortcode_template ) ) {
					include_once $shortcode_template;
				}
			} else {
				$shortcode_template_loggedout_user = BPTODO_PLUGIN_PATH . 'inc/todo/bptodo-by-category-template-loggedout-user.php';
				if ( file_exists( $shortcode_template_loggedout_user ) ) {
					include_once $shortcode_template_loggedout_user;
				}
			}
		}
	}
	new Bptodo_Hooks();
}
