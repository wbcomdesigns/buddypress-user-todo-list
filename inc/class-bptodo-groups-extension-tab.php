<?php
/**
 * The bp_is_active( 'groups' ) check is recommended, to prevent problems
 * during upgrade or when the Groups component is disabled
 *
 * @package bp-user-todo-list
 */

if ( bp_is_active( 'groups' ) ) :
	class Group_Extension_Todo extends BP_Group_Extension {
		/**
		 * Your __construct() method will contain configuration options for
		 * your extension, and will pass them to parent::init()
		 */
		public function __construct() {

			global $bp, $bptodo;

			$profile_menu_label        = $bptodo->profile_menu_label;
			$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
			$profile_menu_slug         = $bptodo->profile_menu_slug;
			$my_todo_items             = $bptodo->my_todo_items;

			$args = array(
				'slug' => $profile_menu_slug,
				'name' => esc_html( $profile_menu_label_plural ) . ' <span class="count">' . $my_todo_items . '</span>',
			);
			if ( is_user_logged_in() ) {
				parent::init( $args );
			}
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
				if ( isset( $_GET['args'] ) ) {
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
