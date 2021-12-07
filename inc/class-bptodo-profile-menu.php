<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bptodo_Profile_Menu' ) ) {
	/**
	 * Class to add admin menu to manage general settings.
	 *
	 * @package bp-user-todo-list
	 * @author  wbcomdesigns
	 * @since   1.0.0
	 */
	class Bptodo_Profile_Menu {

		/**
		 * Define hook.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function __construct() {
			add_action( 'bp_init', array( $this, 'bptodo_register_templates_pack' ), 5 );
			add_action( 'bp_setup_nav', array( $this, 'bptodo_add_todo_tabs_in_groups' ), 5 );
			add_action( 'bp_setup_nav', array( $this, 'bptodo_member_profile_todo_tab' ) );
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
			global $bp, $current_user;

			if ( ! bp_is_group() ) {
				return;
			}

			if ( ! function_exists( 'groups_get_groups' ) ) {
				return false;
			}
			if ( ! $bp->is_single_item ) {
				return false;
			}

			global $bptodo;

			$profile_menu_label        = $bptodo->profile_menu_label;
			$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
			$profile_menu_slug         = $bptodo->profile_menu_slug;
			$my_todo_items             = $bptodo->my_todo_items;

			$name        = $profile_menu_slug;
			$groups_link = bp_get_group_permalink( $bp->groups->current_group );
			$admin_link  = trailingslashit( $groups_link . $profile_menu_slug );

			// Common params to all nav items.
			$default_params = array(
				'parent_url'        => $admin_link,
				'parent_slug'       => $bp->groups->current_group->slug . '_' . $profile_menu_slug,
				'show_in_admin_bar' => true,
			);

			$sub_nav[] = array_merge(
				array(
					'name'            => $profile_menu_label_plural,
					'slug'            => 'list',
					'screen_function' => array( $this, 'bind_bp_groups_page' ),
					'position'        => 0,
				),
				$default_params
			);

			$is_admin     = bp_group_is_admin();
				$can_list = false;
			if ( bp_group_is_mod() ) {
				$group_id    = $bp->groups->current_group->id;
				$mod_can_add = bptodo_list_group_modrator( $group_id, get_current_user_id() );
				$can_list    = true;
				if ( $mod_can_add ) {
					$can_list = false;
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

			$groups_link       = bp_get_group_permalink( $bp->groups->current_group ) . 'add-events/';
			$create_event_text = __( 'Create Event', 'wb-todo' );
			echo '<div class="wb-group-archive-add-event"><a href="' . esc_url( $groups_link ) . '">' . esc_html( $create_event_text ) . '</a></div>';

			echo do_shortcode( '[eab_group_archives groups="' . bp_get_current_group_id() . '" lookahead="yes"]' );

			do_action( 'eab-buddypress-group_events-after_head' );
			// echo $this->_get_navigation($timestamp);.
			// echo $renderer->get_month_calendar($timestamp);.
			// echo $this->_get_navigation($timestamp);.
			do_action( 'eab-buddypress-group_events-after_events' );
		}

		/**
		 * Action performed for regiter templates location.
		 */
		public function bptodo_register_template_location() {
			return BPTODO_PLUGIN_PATH . '/inc/templates/';
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
				global $bp, $bptodo;
				$settings                  = get_option( 'user_todo_list_settings' );
				$profile_menu_label        = $bptodo->profile_menu_label;
				$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
				// $profile_menu_label_plural = $settings['profile_menu_label_plural'];
				$profile_menu_slug = strtolower( $bptodo->profile_menu_label );
				$my_todo_items     = $bptodo->my_todo_items;

				$displayed_uid  = bp_displayed_user_id();
				$parent_slug    = $profile_menu_slug;
				$todo_menu_link = bp_core_get_userlink( $displayed_uid, false, true ) . $parent_slug;

				$name     = bp_get_displayed_user_username();
				$tab_args = array(
					'name'                    => esc_html( $profile_menu_label_plural ) . ' <span class="count">' . $my_todo_items . '</span>',
					'slug'                    => $profile_menu_slug,
					'screen_function'         => array( $this, 'todo_tab_function_to_show_screen' ),
					'position'                => 75,
					'default_subnav_slug'     => 'list',
					'show_for_displayed_user' => true,
				);
				bp_core_new_nav_item( $tab_args );

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
			if ( isset( $_GET['args'] ) ) {
				$todo_id = sanitize_text_field( wp_unslash( $_GET['args'] ) );
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
				if ( isset( $_POST['todo_update'] ) && wp_verify_nonce( $_POST['save_update_todo_data_nonce'], 'wp-bp-todo' ) ) {

					$cat      = sanitize_text_field( $_POST['todo_cat'] );
					$title    = sanitize_text_field( $_POST['todo_title'] );
					$summary  = wp_kses_post( $_POST['bptodo-summary-input'] );
					$due_date = sanitize_text_field( $_POST['todo_due_date'] );
					$priority = sanitize_text_field( $_POST['todo_priority'] );
					$todo_id  = sanitize_text_field( $_POST['hidden_todo_id'] );

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

				include 'todo/edit.php';
			} else {
				// Save todo items.
				if ( isset( $_POST['todo_create'] ) && wp_verify_nonce( $_POST['save_new_todo_data_nonce'], 'wp-bp-todo' ) ) {

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
				require 'todo/add.php';
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
			add_action( 'bp_template_title', array( $this, 'list_todo_tab_function_to_show_title' ) );
			add_action( 'bp_template_content', array( $this, 'list_todo_tab_function_to_show_content' ) );
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
		}

		/**
		 * Screen function for todo list title.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function list_todo_tab_function_to_show_title() {
			global $bptodo;
			$profile_menu_label = $bptodo->profile_menu_label;
			echo '<h4>';
			/* Translators: Display plural label name */
			echo sprintf( esc_html__( '%1$s List', 'wb-todo' ), esc_html( $profile_menu_label ) );
			$args  = array(
				'post_type'      => 'bp-todo',
				'author'         => bp_displayed_user_id(),
				'post_staus'     => 'publish',
				'posts_per_page' => -1,
			);
			$todos = get_posts( $args );
			if ( 0 !== count( $todos ) ) {
				?>
					<?php $todo_export_nonce = wp_create_nonce( 'bptodo-export-todo' ); ?>
					<input type="hidden" id="bptodo-export-todo-nonce" value="<?php echo esc_html( $todo_export_nonce ); ?>">
					<a href="javascript:void(0);" id="export_my_tasks"><i class="fa fa-download" aria-hidden="true"></i></a>
				<?php
			}
			echo '</h4>';
		}

		/**
		 * Screen function for todo list content.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function list_todo_tab_function_to_show_content() {
			include 'todo/list.php';
		}
	}
	new Bptodo_Profile_Menu();
}
