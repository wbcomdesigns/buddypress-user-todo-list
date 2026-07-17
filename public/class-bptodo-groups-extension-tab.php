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
			global $bptodo;

			// Set up the extension settings.
			$profile_menu_slug = sanitize_title( strtolower( $bptodo->profile_menu_slug ?? '' ) );
			// The group todo count is resolved at render time by
			// bptodo_group_nav_has_count() (the group is not yet in context here).
			// Do NOT bake the viewer's personal count into the label/count.
			$args = array(
				'slug'              => $profile_menu_slug,
				'name'              => esc_html( $bptodo->profile_menu_label_plural ),
				'count'             => 0,
				'enable_nav_item'   => true,
			);

			// Always initialize the extension - visibility is controlled by user_can_see_nav_item() method
			parent::init( $args );

			// Add filters to modify the nav count display.
			add_filter( 'bp_nouveau_nav_has_count', array( $this, 'bptodo_group_nav_has_count' ), 10, 3 );
			add_filter( 'bp_nouveau_get_nav_count', array( $this, 'bptodo_group_nav_has_count' ), 10, 3 );
		}

		/**
		 * Override to control navigation item visibility.
		 * Compatible with both BuddyPress and BuddyBoss.
		 *
		 * @param bool $user_can_see_nav_item Default visibility.
		 * @return bool True if tab should be displayed, false otherwise.
		 */
		public function user_can_see_nav_item( $user_can_see_nav_item = false ) {
			// Check if user is logged in
			if ( ! is_user_logged_in() ) {
				return false;
			}

			// Check global settings
			$enable_todo_group = get_option( 'group-todo-list-settings' );
			if ( empty( $enable_todo_group['enable_todo_tab_group'] ) || ! in_array( $enable_todo_group['enable_todo_tab_group'], array( 'yes', 'on' ), true ) ) {
				return false;
			}

			// Check if we are in a group context
			global $bp;
			if ( ! bp_is_group() || empty( $bp->groups->current_group->id ) ) {
				return false;
			}

			$group_id = $bp->groups->current_group->id;
			$group_disable_todos = groups_get_groupmeta( $group_id, 'group-disable-todos', true );

			// If todos are disabled for this group, don't show the tab
			if ( 1 === (int) $group_disable_todos ) {
				return false;
			}

			return true;
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
			global $bptodo;
			$todo_slug = sanitize_title( $bptodo->profile_menu_slug ?? 'todo' );
			if ( 'groups' === $displayed_nav && $todo_slug === $nav_item->slug ) {
				// Count DISTINCT group todos (primaries) scoped to THIS group, not the
				// viewer's personal count and not the per-assignee fan-out copies. The
				// group Todos tab lists primary_only rows, so the badge must match.
				$group_id = bp_get_current_group_id();
				if ( $group_id && function_exists( 'bptodo_count_todos' ) ) {
					$stats = bptodo_count_todos(
						array(
							'group_id'     => $group_id,
							'primary_only' => true,
						)
					);
					$count = isset( $stats['total'] ) ? (int) $stats['total'] : 0;
				}
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
			<nav class="bp-navs bp-subnavs no-ajax group-subnav" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group administration menu', 'wb-todo' ); ?>">
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
