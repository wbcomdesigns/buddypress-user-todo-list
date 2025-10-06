<?php

if ( ! function_exists( 'bptodo_list_group_modrator' ) ) {
	/**
	 *  Add or exclude group modtaros in average percentage calculation.
	 *
	 * @return boolean
	 */
	add_filter( 'bptodo_exclude_modrator_view', 'bptodo_list_group_modrator' );
	function bptodo_list_group_modrator() {
		$group_todo_list_settings = get_option( 'group-todo-list-settings' );

		// list_enable = 'yes' means moderators CAN add todos (ALLOW setting)
		$can_list = ( ! empty( $group_todo_list_settings['list_enable'] ) && in_array( $group_todo_list_settings['list_enable'], array( 'yes', 'on' ), true ) ) ? true : false;

		return $can_list;
	}
}

/**
 * Create admin menu to plugin settings.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_if_moderator_modification_enabled( $group_id, $current_user ) {
	$group_todo_list_settings = get_option( 'group-todo-list-settings' );

	// mod_enable = 'yes' means PREVENT modification (inverted logic: PREVENT setting)
	// So if mod_enable is 'yes', return FALSE (cannot modify); otherwise return TRUE (can modify)
	$can_modify = ( ! empty( $group_todo_list_settings['mod_enable'] ) && in_array( $group_todo_list_settings['mod_enable'], array( 'yes', 'on' ), true ) ) ? false : true;

	return apply_filters( 'alter_bptodo_if_moderator_modification_enabled', $can_modify, $group_id, $current_user );
}

/**
 * Add or exclude group modrators to view todo report.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_report_view_enabled( $group_id, $current_user ) {
	$group_todo_list_settings = get_option( 'group-todo-list-settings' );

	// view_enable = 'yes' means moderators CAN view reports (ALLOW setting)
	$can_views = ( ! empty( $group_todo_list_settings['view_enable'] ) && in_array( $group_todo_list_settings['view_enable'], array( 'yes', 'on' ), true ) ) ? true : false;
	return $can_views;
}
add_filter( 'bptodo_exclude_modrator_edit', 'bptodo_report_view_enabled' );


/**
 * Hide the todo tab when group todo disable option is enabled.
 *
 * @return void
 */
function bptodo_disable_group_todo(){
	global $bp;
	if ( bp_is_group() ) {
		$group_id 			 = $bp->groups->current_group->id;
		$group_disable_todos = groups_get_groupmeta( $group_id, 'group-disable-todos', true );
		if ( 1 === (int) $group_disable_todos ) {
		?>
		<style>
			#nav-to-do{
				display:none!important;
			}
		</style>
		<?php
		}
	}
}
add_action( 'wp', 'bptodo_disable_group_todo' );