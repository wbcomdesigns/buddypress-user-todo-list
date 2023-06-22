<?php
/**
 * The bp_is_active( 'groups' ) check is recommended, to prevent problems
 * during upgrade or when the Groups component is disabled
 *
 * @package bp-user-todo-list
 */

if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) :
	class Group_Extension_Todo extends BP_Group_Extension {
		/**
		 * Your __construct() method will contain configuration options for
		 * your extension, and will pass them to parent::init()
		 */
		public function __construct() {

			global $bp, $bptodo;
			if ( bp_is_group() ) {
				$group_id = $bp->groups->current_group->id;
				$group_disable_todos = groups_get_groupmeta( $group_id, 'group-disable-todos', true);
				if ( $group_disable_todos == 1) {
					return;
				}
			}
			$profile_menu_label        = $bptodo->profile_menu_label;
			$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
			$profile_menu_slug         = $bptodo->profile_menu_slug;
			$my_todo_items             = $bptodo->my_todo_items;
			$enable_todo_group = get_option( 'group-todo-list-settings' );
			$args = array(
				'slug' => sanitize_title( strtolower( $profile_menu_slug ) ),
				'name' => esc_html( $profile_menu_label_plural ) . ' <span>' . $my_todo_items . '</span>',
				'count' => $my_todo_items,
			);			
			if ( is_user_logged_in() && isset( $enable_todo_group['enable_todo_tab_group'] ) ) {
				parent::init( $args );
			}
			add_filter( 'bp_nouveau_nav_has_count', [$this, 'bptodo_group_nav_has_count'], 10, 3 );
			add_filter( 'bp_nouveau_get_nav_count', [$this, 'bptodo_group_nav_has_count'], 10, 3 );
		}
		
		public function bptodo_group_nav_has_count( $count, $nav_item, $displayed_nav) {
			if ( 'groups' === $displayed_nav && 'to-do' === $nav_item->slug ) {
				global $bp, $bptodo;
				$count = $bptodo->my_todo_items;				
			}
			return $count;
		}

		/**
		 * Display() contains the markup that will be displayed on the main plugin tab.
		 */
		public function display( $group_id = null ) {
			global $bptodo;
			?>
		<nav class="bp-navs bp-subnavs no-ajax group-subnav" id="subnav" role="navigation" aria-label="Group administration menu">
			<ul class="subnav">
				<?php
				global $groups_template;

				if ( empty( $group ) ) {
					$group = ( $groups_template->group ) ? $groups_template->group : groups_get_current_group();
				}

					$css_id = $bptodo->profile_menu_slug;

					add_filter( "bp_get_options_nav_{$css_id}", 'bp_group_admin_tabs_backcompat', 10, 3 );

					bp_get_options_nav( $group->slug . '_' . $css_id );

					remove_filter( "bp_get_options_nav_{$css_id}", 'bp_group_admin_tabs_backcompat', 10 );
				?>
			</ul>
		</nav>
			<?php

			if ( ! bp_action_variable() ) {
				bp_get_template_part( 'list' );
			}

			if ( bp_action_variable() === 'add' ) {
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
	bp_register_group_extension( 'Group_Extension_Todo' );

endif; // if ( bp_is_active( 'groups' ) ).
