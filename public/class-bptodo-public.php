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
			if ( ! wp_next_scheduled( 'bptodo_todo_notification' ) ) {
				wp_schedule_event( time(), 'every_six_hours', 'bptodo_todo_notification' );
			}
		}

		/**
		 * Enqueue styles.
		 */
		public function enqueue_styles() {
			// Enqueue styles here if needed.
		}

		/**
		 * Enqueue scripts.
		 */
		public function enqueue_scripts() {
			// Enqueue scripts here if needed.
		}

		/**
		 * Manage due date notifications.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_manage_todo_due_date() {
			global $bptodo;
			$args = array(
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
					$diff_days   = '';
					if ( 'complete' !== $todo_status ) {
						$author_id     = $todo->post_author;
						$curr_date     = date_create( gmdate( 'Y-m-d' ) );
						$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
						$diff          = date_diff( $curr_date, $due_date );
						$mail_setting  = get_user_meta( get_current_user_id(), 'todo_mail_setting' );
						$mail_settings = isset( $mail_setting[0] ) ? $mail_setting[0] : '';
						if ( is_object( $diff ) ) {
							$diff_days = $diff->format( '%R%a' );
						}
						/** Check if mail sending is allowed. */
						if ( 'yes' === $mail_settings ) {
							if ( ! empty( $bptodo->send_mail ) && 'yes' === $bptodo->send_mail ) {

								/** If today is the due date. */
								if ( 0 === $diff_days ) {
									/** If the mail is not sent already. */
									$due_date_mail_sent = get_post_meta( $todo->ID, 'todo_last_day_mail_sent', true );
									if ( 'no' === $due_date_mail_sent ) {
										$author       = get_userdata( $author_id );
										$author_email = $author->data->user_email;
										$subject      = esc_html__( 'BPTODO Task - WordPress', 'wb-todo' );
										/* Translators: Get a To do title name */
										$messsage = sprintf( esc_html__( 'Your task: %1$s is going to expire today. Kindly finish it up! Thanks!', 'wb-todo' ), esc_html( $todo->post_title ) );
										$headers  = array( 'Content-Type: text/html; charset=UTF-8' );
										wp_mail( $author_email, $subject, $messsage, $headers );
										update_post_meta( $todo->ID, 'todo_last_day_mail_sent', 'yes' );
									}
								}
							}
						}

						/** Check if notification sending is allowed. */
						if ( ! empty( $bptodo->send_notification ) && 'yes' === $bptodo->send_notification ) {
							/** If today is the due date. */
							if ( 0 === $diff_days ) {
								/** If the mail is not sent already. */
								$due_date_notification_sent = get_post_meta( $todo->ID, 'todo_last_day_notification_sent', true );
								if ( 'no' === $due_date_notification_sent ) {
									/** Send notification for acceptance. */
									bp_notifications_add_notification(
										array(
											'user_id'           => $author_id,
											'item_id'           => $todo->ID,
											'secondary_item_id' => get_current_user_id(),
											'component_name'    => 'bptodo_due_date',
											'component_action'  => 'bptodo_due_date_action',
											'date_notified'     => bp_core_current_time(),
											'is_new'            => 1,
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
		 * Setup admin bar menu items.
		 *
		 * @since 1.0.0
		 * @access public
		 * @param array $wp_admin_nav Admin bar menu items.
		 */
		public function bptodo_setup_admin_bar( $wp_admin_nav = array() ) {
			global $wp_admin_bar, $bptodo;
			$profile_menu_slug         = $bptodo->profile_menu_slug;
			$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
			$my_todo_items             = $bptodo->my_todo_items;
			$todo_list_settings        = get_option( 'user_todo_list_settings' );

			if ( isset( $todo_list_settings['enable_todo_member'] ) ) {
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
		}

		/**
		 * Manage todo due date column heading in the admin.
		 *
		 * @since 1.0.0
		 * @access public
		 * @param array $defaults Column defaults.
		 * @return array
		 */
		public function bptodo_due_date_column_heading( $defaults ) {
			$defaults['due_date'] = esc_html__( 'Due Date', 'wb-todo' );
			$defaults['status']   = esc_html__( 'Status', 'wb-todo' );
			$defaults['todo_id']  = esc_html__( 'ID', 'wb-todo' );
			return $defaults;
		}

		/**
		 * Manage todo due date column content in the admin.
		 *
		 * @since 1.0.0
		 * @access public
		 * @param string $column_name Column name.
		 * @param int    $post_id Post ID.
		 */
		public function bptodo_due_date_column_content( $column_name, $post_id ) {
			$due_date_str      = '';
			$due_date_td_class = '';

			if ( 'due_date' === $column_name ) {
				$due_date = get_post_meta( $post_id, 'todo_due_date', true );
				echo esc_html( gmdate( 'F jS, Y', strtotime( $due_date ) ) );
			}

			if ( 'status' === $column_name ) {
				$todo_status = get_post_meta( $post_id, 'todo_status', true );
				$curr_date   = date_create( gmdate( 'Y-m-d' ) );
				$due_date    = date_create( get_post_meta( $post_id, 'todo_due_date', true ) );
				$diff        = date_diff( $curr_date, $due_date );
				$diff_days   = $diff->format( '%R%a' );
				if ( $diff_days < 0 ) {
					/* Translators: Display Expiry Days */
					$due_date_str = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
				} elseif ( 0 === $diff_days ) {
					$due_date_str = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
				} else {
					/* Translators: Display the left days */
					$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
				}

				if ( 'complete' === $todo_status ) {
					$due_date_str = esc_html__( 'Completed!', 'wb-todo' );
				}

				echo esc_html( $due_date_str );
			}

			if ( 'todo_id' === $column_name ) {
				echo esc_html( $post_id );
			}
		}

		/**
		 * Add todo category via AJAX.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_add_todo_category_front() {
			check_ajax_referer( 'bptodo-add-todo-category', 'security_nonce' );
			if ( isset( $_POST['action'] ) && 'bptodo_add_todo_category_front' === $_POST['action'] ) {
				$term = sanitize_text_field( wp_unslash( $_POST['name'] ) );
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
		 * Export tasks via AJAX.
		 *
		 * @since 1.0.0
		 * @access public
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
		 * Complete a todo via AJAX.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_complete_todo() {
			global $bptodo;
			check_ajax_referer( 'bptodo-todo-nonce', 'ajax_nonce' );
			if ( isset( $_POST['action'] ) && 'bptodo_complete_todo' === $_POST['action'] ) {
				$due_date_str      = '';
				$due_date_td_class = '';
				$tid               = sanitize_text_field( wp_unslash( $_POST['tid'] ) );
				$user_id           = sanitize_text_field( wp_unslash( $_POST['uid'] ) );
				update_post_meta( $tid, 'todo_status', 'complete' );
				update_post_meta( $tid, 'todo_user_id', $user_id );
				update_post_meta( $tid, 'todo_complete_time', time() );
				$completed_todos = sanitize_text_field( wp_unslash( $_POST['completed'] ) );
				$all_todo        = sanitize_text_field( wp_unslash( $_POST['all_todo'] ) );
				$completed_todos++;
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
				$curr_date          = date_create( gmdate( 'Y-m-d' ) );
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
				<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="' . esc_attr( $tid ) . '" title="' . sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), $todo_title ) . '"><i class="fa fa-times"></i></a></li>';
				if ( 'complete' !== $todo_status ) {

					$completed_html .= '<li><a href="' . esc_attr( $todo_edit_url ) . '" title="' . sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), $todo_title ) . '"><i class="fa fa-edit"></i></a></li>
					<li id="bptodo-complete-li-' . esc_attr( $tid ) . '"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="' . esc_attr( $tid ) . '" title="' . sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), $todo_title ) . '"><i class="fa fa-check"></i></a></li>';
				} else {
					$completed_html .= '<li><a href="" class="bptodo-undo-complete-todo" data-tid="' . $tid . '" title="' . sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), $todo_title ) . '"><i class="fa fa-undo"></i></a></li>';
				}
				$completed_html .= '<li><a href="' . esc_attr( $todo_view_url ) . '" title="' . sprintf( esc_html__( 'View: %s', 'wb-todo' ), $todo_title ) . '" target="_blank"><i class="fa fa-eye"></i></a></li>';
				$completed_html .= '</ul></td></tr>';
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
		 * Undo complete todo via AJAX.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_undo_complete_todo() {
			check_ajax_referer( 'bptodo-todo-nonce', 'ajax_nonce' );
			if ( isset( $_POST['action'] ) && 'bptodo_undo_complete_todo' === $_POST['action'] ) {
				$tid = sanitize_text_field( wp_unslash( $_POST['tid'] ) );
				update_post_meta( $tid, 'todo_status', 'incomplete' );
				echo 'todo-undo-completed';
				die;
			}
		}

		/**
		 * Remove todo via AJAX.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_remove_todo() {
			check_ajax_referer( 'bptodo-todo-nonce', 'ajax_nonce' );
			if ( isset( $_POST['action'] ) && 'bptodo_remove_todo' === $_POST['action'] ) {
				$tid = sanitize_text_field( wp_unslash( $_POST['tid'] ) );
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
		 * Add todo button on member header.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_add_todo_button_on_member_header() {
			global $bptodo;

			if ( 'yes' !== $bptodo->hide_button ) {
				return;
			}

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
		 * Display notification settings for todo mail.
		 *
		 * @since 1.0.0
		 * @access public
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
					<tr id="activity-notification-settings-mentions">
						<td>&nbsp;</td>
						<td>
							<?php esc_html_e( 'Enable/disable mail option', 'wb-todo' ); ?>
						</td>
						<td class="yes"><input type="radio" name="notifications[todo_mail_setting]" id="todo-mail-settings-yes" value="yes"<?php checked( isset( $mail[0] ) ? $mail[0] : '', 'yes', true ); ?>/><label for="todo-mail-settings-yes" class="bp-screen-reader-text">
						<?php esc_html_e( 'Yes, send email', 'wb-todo' ); ?>
						</label></td>
						<td class="no"><input type="radio" name="notifications[todo_mail_setting]" id="todo-mail-settings-no" value="no"<?php checked( isset( $mail[0] ) ? $mail[0] : '', 'no', true ); ?>/><label for="todo-mail-settings-no" class="bp-screen-reader-text">
						<?php esc_html_e( 'No, do not send email', 'wb-todo' ); ?>
						</label></td>
					</tr>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Save notification settings for todo mail.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_mail_settings_after_save() {
			if ( isset( $_POST['notifications'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'bp_settings_notifications' ) ) {
				$post_data = sanitize_text_field( wp_unslash( $_POST['notifications']['todo_mail_setting'] ) );
				update_user_meta( bp_loggedin_user_id(), 'todo_mail_setting', $post_data );
			}
		}

		/**
		 * Add disable todo list checkbox in group settings.
		 *
		 * @since 1.0.0
		 * @access public
		 */
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

		/**
		 * Save group settings for disabling todos tab.
		 *
		 * @since 1.0.0
		 * @access public
		 * @param int $group_id Group ID.
		 */
		public function bp_nouveau_add_disable_group_todolists_details_edited( $group_id ) {
			if ( isset( $_POST['group-disable-todos'] ) ) { // phpcs:ignore
				groups_update_groupmeta( $group_id, 'group-disable-todos', $_POST['group-disable-todos'] ); // phpcs:ignore
			} else {
				groups_update_groupmeta( $group_id, 'group-disable-todos', '' );
			}
		}

		/**
		 * Register the shortcode for listing todos by category.
		 *
		 * @since 1.0.0
		 * @access public
		 * @param string $atts Shortcode attributes.
		 */
		public function bptodo_by_category_template( $atts ) {
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
		 * Register notification components.
		 *
		 * @since 1.0.0
		 * @access public
		 * @param array $component_names Notification components.
		 * @return array
		 */
		public function bptodo_notifications_get_registered_components( $component_names = array() ) {
			// Force $component_names to be an array.
			if ( ! is_array( $component_names ) ) {
				$component_names = array();
			}
			// Add 'bptodo_group_todo_notifications' component to registered components array.
			array_push( $component_names, 'bptodo_group_todo_notifications' );
			// Return component's with 'bptodo_group_todo_notifications' appended.
			return $component_names;
		}

		/**
		 * Format todo notification content.
		 *
		 * @since 1.0.0
		 * @access public
		 * @param string $content Notification content.
		 * @param int    $item_id Item ID.
		 * @param int    $secondary_item_id Secondary item ID.
		 * @param int    $total_items Total items.
		 * @param string $format Format.
		 * @param string $component_action_name Component action name.
		 * @param string $component_name Component name.
		 * @param string $id ID.
		 */
		public function bptodo_gp_todo_notification_format( $content, $item_id, $secondary_item_id, $total_items, $format = 'string', $component_action_name = '', $component_name = '', $id = '' ) {
			$get_gp_id = get_post_meta( $item_id, 'todo_group_id', true );
			$group = groups_get_group( array( 'group_id' => $get_gp_id ) );
			$bptodo_post = get_post( $item_id );
			$author_name = bp_core_get_user_displayname( $secondary_item_id );
			$user_link = bp_get_notifications_permalink( get_current_user_id() );
			$notification_title = isset( $bptodo_post->post_title ) ? $bptodo_post->post_title : '';
			$notification_guid = isset( $bptodo_post->guid ) ? $bptodo_post->guid : '';

			if ( 'bptodo_group_todo_notifications' === $component_name ) {
				/* translators: 1: todo title, 2: group name, 3: author name */
				$notification_string = sprintf( __( '%1$s todo added in group %2$s by %3$s.', 'bp-profile-views' ), $notification_title, $group->name, $author_name );
				if ( 'string' === $format ) {
					$return = "<a href='" . esc_url( $notification_guid ) . "'>" . $notification_string . '</a>';
				} else {
					$return = array(
						'text' => $notification_string,
						'link' => $user_link,
					);
				}
				return $return;
			}
			return $content;
		}

		/**
		 * Notify group members of todo updates.
		 *
		 * @since 1.0.0
		 * @access public
		 * @param array $group_members_ids Group member IDs.
		 * @param int   $post_id Post ID.
		 */
		public function bptodo_group_member_notification( $group_members_ids, $post_id ) {
			if ( ! array_key_exists( 'todo_group_members', $_POST ) ) { // phpcs:ignore
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
					bp_notifications_add_notification( $args );
				}
			}
		}

		/**
		 * Update todo form via AJAX.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_update_form_popup() {
			if ( isset( $_POST['ajax_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ajax_nonce'] ) ), 'bptodo-todo-nonce' ) ) {
				$update_form_data = isset( $_POST['update_form_data'] ) ? $_POST['update_form_data']  : ''; // phpcs:ignore
				foreach ( $update_form_data as $key => $value ) {
					$todo_cat             = $update_form_data[0]['value'];
					$todo_title           = $update_form_data[1]['value'];
					$todo_summary         = $update_form_data[2]['value'];
					$todo_due_date        = $update_form_data[3]['value'];
					$todo_priority        = $update_form_data[4]['value'];
					$todo_todo_id         = $update_form_data[8]['value'] ?? false;
					$todo_primary_todo_id = $update_form_data[9]['value'] ?? false;
					$todo_group_id        = $update_form_data[10]['value'] ?? false;

					$cat             = sanitize_text_field( $todo_cat );
					$title           = sanitize_text_field( wp_unslash( $todo_title ) );
					$summary         = wp_kses_post( wp_unslash( $todo_summary ) );
					$due_date        = sanitize_text_field( wp_unslash( $todo_due_date ) );
					$priority        = sanitize_text_field( wp_unslash( $todo_priority ) );
					$todo_id         = sanitize_text_field( wp_unslash( $todo_todo_id ) );
					$primary_todo_id = sanitize_text_field( wp_unslash( $todo_primary_todo_id ) );

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
					$args             = array(
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
		 * Display edit form popup via AJAX.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_edit_form_popup() {
			if ( isset( $_POST['ajax_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ajax_nonce'] ) ), 'bptodo-todo-nonce' ) ) {
				$todo_id   = sanitize_text_field( wp_unslash( $_POST['t_id'] ) );
				$todo_guid = sanitize_text_field( wp_unslash( $_POST['gu_id'] ) );
				$component_type = sanitize_text_field( wp_unslash( $_POST['component'] ) );

				ob_start();
				if ( 'member' === $component_type ) {
					include BPTODO_PLUGIN_PATH . 'public/todo/member/member-todo-edit.php';
				} elseif ( 'group' === $component_type ) {
					include BPTODO_PLUGIN_PATH . 'public/todo/group/edit.php';
				}
				$todo_edit = ob_get_clean();
				echo $todo_edit; // phpcs:ignore
				_WP_Editors::enqueue_scripts();
				print_footer_scripts();
				_WP_Editors::editor_js();
				exit();
			}
		}

		/**
		 * Add edit form div for popup.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function bptodo_add_div_edit_form() {
			echo '<div class="bptodo-modal modal" data-modal="trigger">
				<div class="content-wrapper bptodo_edit_form_popup">
					<button class="close"></button>
					<div class="edit_form_popup"></div>
				</div>
			</div>';
		}
	}
	new Bptodo_Public();
}
