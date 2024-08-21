<?php
/**
 * The bp_is_active( 'groups' ) check is recommended to prevent problems
 * during upgrades or when the Groups component is disabled.
 *
 * @package bp-user-todo-list
 */

if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) :

	class Group_Extension_Todo extends BP_Group_Extension {

		/**
		 * Constructor to initialize the group extension.
		 */
		public function __construct() {
			global $bp, $bptodo;

			// Check if we are in a group and if todos are disabled for this group.
			if ( bp_is_group() ) {
				$group_id = $bp->groups->current_group->id;
				$group_disable_todos = groups_get_groupmeta( $group_id, 'group-disable-todos', true );

				if ( 1 === (int) $group_disable_todos ) {
					return;
				}
			}

			// Set up the extension settings.
			$profile_menu_slug = sanitize_title( strtolower( $bptodo->profile_menu_slug ) );
			$args = array(
				'slug'  => $profile_menu_slug,
				'name'  => esc_html( $bptodo->profile_menu_label_plural ) . ' <span>' . $bptodo->my_todo_items . '</span>',
				'count' => $bptodo->my_todo_items,
			);

			// Initialize the extension if the group todo tab is enabled.
			$enable_todo_group = get_option( 'group-todo-list-settings' );
			if ( is_user_logged_in() && isset( $enable_todo_group['enable_todo_tab_group'] ) ) {
				parent::init( $args );
			}

			// Add filters to modify the nav count display.
			add_filter( 'bp_nouveau_nav_has_count', array( $this, 'bptodo_group_nav_has_count' ), 10, 3 );
			add_filter( 'bp_nouveau_get_nav_count', array( $this, 'bptodo_group_nav_has_count' ), 10, 3 );
		}

		/**
		 * Modify the group navigation item count.
		 *
		 * @param int $count The current count.
		 * @param object $nav_item The navigation item object.
		 * @param string $displayed_nav The displayed navigation context.
		 * @return int Modified count.
		 */
		public function bptodo_group_nav_has_count( $count, $nav_item, $displayed_nav ) {
			if ( 'groups' === $displayed_nav && 'to-do' === $nav_item->slug ) {
				global $bptodo;
				$count = $bptodo->my_todo_items;
			}
			return $count;
		}

		/**
		 * Display the content for the group extension.
		 *
		 * @param int|null $group_id The group ID.
		 */
		public function display( $group_id = null ) {
			global $bptodo;

			?>
			<nav class="bp-navs bp-subnavs no-ajax group-subnav" id="subnav" role="navigation" aria-label="Group administration menu">
				<ul class="subnav">
					<?php
					global $groups_template;
					$group = $groups_template->group ?? groups_get_current_group();
					$css_id = $bptodo->profile_menu_slug;

					// Add backcompat filter for options nav.
					add_filter( "bp_get_options_nav_{$css_id}", 'bp_group_admin_tabs_backcompat', 10, 3 );
					bp_get_options_nav( $group->slug . '_' . $css_id );
					remove_filter( "bp_get_options_nav_{$css_id}", 'bp_group_admin_tabs_backcompat', 10 );
					?>
				</ul>
			</nav>
			<?php

			// Display the appropriate template part based on the action variable.
			if ( ! bp_action_variable() ) {
				bp_get_template_part( 'list' );
			} elseif ( 'add' === bp_action_variable() ) {
				if ( isset( $_GET['args'] ) ) { //phpcs:ignore
					bp_get_template_part( 'edit' );
				} else {
					bp_get_template_part( 'add' );
				}
			} else {
				bp_get_template_part( bp_action_variable() );
			}
		}
	}

	// Register the group extension.
	bp_register_group_extension( 'Group_Extension_Todo' );

endif; // End if ( bp_is_active( 'groups' ) ).
