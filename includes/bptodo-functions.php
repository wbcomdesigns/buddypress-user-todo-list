<?php

/**
 * BuddyPress/BuddyBoss compatibility helper for getting member URL.
 * Supports BuddyPress 12.0+, older BuddyPress versions, and BuddyBoss.
 *
 * @param int $user_id The user ID.
 * @return string The member profile URL.
 * @since 1.0.0
 */
if ( ! function_exists( 'bptodo_get_member_url' ) ) {
	function bptodo_get_member_url( $user_id ) {
		if ( function_exists( 'bp_members_get_user_url' ) ) {
			// BuddyPress 12.0+
			return bp_members_get_user_url( $user_id );
		} elseif ( function_exists( 'bp_core_get_user_domain' ) ) {
			// Older BuddyPress/BuddyBoss
			return bp_core_get_user_domain( $user_id );
		} else {
			// Fallback to WordPress author URL
			return get_author_posts_url( $user_id );
		}
	}
}

/**
 * BuddyPress/BuddyBoss compatibility helper for getting group URL.
 * Supports BuddyPress 12.0+, older BuddyPress versions, and BuddyBoss.
 *
 * @param int|object $group_id_or_obj The group ID or group object.
 * @return string The group URL.
 * @since 1.0.0
 */
if ( ! function_exists( 'bptodo_get_group_url' ) ) {
	function bptodo_get_group_url( $group_id_or_obj = 0 ) {
		if ( function_exists( 'bp_get_group_url' ) ) {
			// BuddyPress 12.0+
			return bp_get_group_url( $group_id_or_obj );
		} elseif ( function_exists( 'bp_get_group_permalink' ) ) {
			// Older BuddyPress/BuddyBoss
			return bp_get_group_permalink( $group_id_or_obj );
		} else {
			// Fallback - try to get group object
			if ( is_numeric( $group_id_or_obj ) ) {
				$group = groups_get_group( $group_id_or_obj );
			} else {
				$group = $group_id_or_obj;
			}

			if ( ! empty( $group->slug ) ) {
				return home_url( '/groups/' . $group->slug . '/' );
			}

			return home_url( '/groups/' );
		}
	}
}

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
	// Check if groups component is active
	if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'groups' ) ) {
		return;
	}

	global $bp;
	if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
		$group_id 			 = $bp->groups->current_group->id;
		$group_disable_todos = function_exists( 'groups_get_groupmeta' ) ? groups_get_groupmeta( $group_id, 'group-disable-todos', true ) : false;
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

/**
 * Check if current user is a site admin.
 *
 * @return bool True if user can manage options (is admin).
 * @since 1.0.0
 */
if ( ! function_exists( 'bptodo_is_site_admin' ) ) {
	function bptodo_is_site_admin() {
		return current_user_can( 'manage_options' );
	}
}

/**
 * Check if a user is assigned to a specific task.
 *
 * @param int $task_id The task/todo post ID.
 * @param int $user_id The user ID to check.
 * @return bool True if user is assigned to the task.
 * @since 1.0.0
 */
if ( ! function_exists( 'bptodo_is_user_assigned_to_task' ) ) {
	function bptodo_is_user_assigned_to_task( $task_id, $user_id ) {
		$task = get_post( $task_id );

		if ( ! $task ) {
			return false;
		}

		// Check if user is the primary task creator
		if ( $task->post_author == $user_id ) {
			return true;
		}

		// Check if user has an associated todo for this task
		$associated_todos = get_post_meta( $task_id, 'botodo_associated_todo', true );
		if ( ! empty( $associated_todos ) && is_array( $associated_todos ) ) {
			foreach ( $associated_todos as $assoc_id ) {
				$assoc_post = get_post( $assoc_id );
				if ( $assoc_post && $assoc_post->post_author == $user_id ) {
					return true;
				}
			}
		}

		return false;
	}
}