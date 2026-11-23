<?php
/**
 * Group-task regression check (BuddyPress or BuddyBoss must be active).
 *
 * Run from the WordPress root:  wp eval-file wp-content/plugins/buddypress-user-todo-list/bin/regression-check.php
 *
 * Seeds its own users, group and tasks, prints PASS/FAIL per rule, removes what it
 * created and restores the settings it touched. Exits non-zero on any failure.
 * Not shipped (bin/ is in .distignore).
 *
 * @package bp-user-to-do-list
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'groups_create_group' ) ) {
	WP_CLI::error( 'BuddyPress/BuddyBoss with the Groups component is required.' );
}

// wp eval-file runs inside a function: declare the shared state global.
global $bptodo_rc_fails, $bptodo_rc_posts;
$bptodo_rc_fails = 0;
$bptodo_rc_posts = array();

/**
 * Print one result line.
 *
 * @param string $label Rule.
 * @param bool   $ok    Result.
 */
function bptodo_rc_check( $label, $ok ) {
	global $bptodo_rc_fails;
	WP_CLI::log( ( $ok ? 'PASS ' : 'FAIL ' ) . $label );
	if ( ! $ok ) {
		++$bptodo_rc_fails;
	}
}

/**
 * Get or create a test user.
 *
 * @param string $login Login.
 * @return int
 */
function bptodo_rc_user( $login ) {
	$user = get_user_by( 'login', $login );
	return $user ? (int) $user->ID : (int) wp_insert_user(
		array(
			'user_login' => $login,
			'user_pass'  => wp_generate_password(),
			'user_email' => $login . '@example.com',
			'role'       => 'subscriber',
		)
	);
}

/**
 * Does a post still exist outside the trash?
 *
 * @param int $id Post ID.
 * @return bool
 */
function bptodo_rc_alive( $id ) {
	clean_post_cache( $id );
	$post = get_post( $id );
	return $post && 'trash' !== $post->post_status;
}

/**
 * Create a group task as $author for $members; returns [primary, copies by member].
 *
 * @param int   $author   Creator.
 * @param int   $group_id Group.
 * @param int[] $members  Assignees.
 * @param array $data     Task data.
 * @return array
 */
function bptodo_rc_task( $author, $group_id, $members, $data = array() ) {
	global $bptodo_rc_posts;
	wp_set_current_user( $author );
	$primary = Bptodo_Todo_Repository::assign_to_group( $data + array( 'title' => 'RC task' ), $group_id, $members, $author );
	$copies  = array();
	foreach ( (array) get_post_meta( $primary->id(), Bptodo_Todo::META_ASSOCIATED, true ) as $copy_id ) {
		$copies[ (int) get_post_field( 'post_author', $copy_id ) ] = (int) $copy_id;
	}
	$bptodo_rc_posts = array_merge( $bptodo_rc_posts, array( $primary->id() ), array_values( $copies ) );
	return array( $primary->id(), $copies );
}

$bptodo_rc_admin = bptodo_rc_user( 'bptodo-rc-gadmin' );
$bptodo_rc_mod   = bptodo_rc_user( 'bptodo-rc-gmod' );
$bptodo_rc_m1    = bptodo_rc_user( 'bptodo-rc-member1' );
$bptodo_rc_m2    = bptodo_rc_user( 'bptodo-rc-member2' );

$bptodo_rc_group = (int) groups_create_group(
	array(
		'creator_id' => $bptodo_rc_admin,
		'name'       => 'Todo regression check',
		'status'     => 'public',
	)
);
foreach ( array( $bptodo_rc_mod, $bptodo_rc_m1, $bptodo_rc_m2 ) as $bptodo_rc_uid ) {
	groups_join_group( $bptodo_rc_group, $bptodo_rc_uid );
}
( new BP_Groups_Member( $bptodo_rc_mod, $bptodo_rc_group ) )->promote( 'mod' );

$bptodo_rc_group_settings     = get_option( 'group-todo-list-settings' );
$bptodo_rc_retention_settings = get_option( 'data-retention-settings' );

// 1. Deleting an assignee's copy deletes only that copy.
list( $p, $c ) = bptodo_rc_task( $bptodo_rc_admin, $bptodo_rc_group, array( $bptodo_rc_m1, $bptodo_rc_m2 ) );
wp_set_current_user( $bptodo_rc_m1 );
if ( Bptodo_Permissions::can_delete( $c[ $bptodo_rc_m1 ] ) ) {
	Bptodo_Todo_Repository::delete( $c[ $bptodo_rc_m1 ] );
}
bptodo_rc_check( 'delete copy: own copy removed', ! bptodo_rc_alive( $c[ $bptodo_rc_m1 ] ) );
bptodo_rc_check( 'delete copy: other member copy kept', bptodo_rc_alive( $c[ $bptodo_rc_m2 ] ) );
bptodo_rc_check( 'delete copy: primary kept', bptodo_rc_alive( $p ) );
wp_set_current_user( $bptodo_rc_admin );
Bptodo_Todo_Repository::delete( $p );
bptodo_rc_check( 'delete primary by manager: copies removed', ! bptodo_rc_alive( $c[ $bptodo_rc_m2 ] ) && ! bptodo_rc_alive( $p ) );

// 2. Retention "only completed" keeps a pending copy.
list( $p, $c ) = bptodo_rc_task( $bptodo_rc_admin, $bptodo_rc_group, array( $bptodo_rc_m1, $bptodo_rc_m2 ) );
Bptodo_Todo_Repository::set_status( $p, 'complete', $bptodo_rc_admin );
Bptodo_Todo_Repository::set_status( $c[ $bptodo_rc_m1 ], 'complete', $bptodo_rc_m1 );
$bptodo_rc_old = gmdate( 'Y-m-d H:i:s', time() - 200 * DAY_IN_SECONDS );
foreach ( array( $p, $c[ $bptodo_rc_m1 ], $c[ $bptodo_rc_m2 ] ) as $bptodo_rc_id ) {
	$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, array( 'post_date' => $bptodo_rc_old, 'post_date_gmt' => $bptodo_rc_old ), array( 'ID' => $bptodo_rc_id ) );
	clean_post_cache( $bptodo_rc_id );
}
update_option(
	'data-retention-settings',
	array(
		'enable_retention'       => 'yes',
		'cleanup_type'           => 'completed',
		'retention_days'         => 90,
		'enable_group_retention' => 'yes',
		'permanent_delete'       => 'no',
	)
);
// Keep the run to this check's own rows so the site's real tasks are untouched.
$bptodo_rc_scope = static function ( $query ) use ( $p, $c ) {
	if ( 'bp-todo' === $query->get( 'post_type' ) ) {
		$query->set( 'post__in', array_merge( array( $p ), array_values( $c ) ) );
	}
};
add_action( 'pre_get_posts', $bptodo_rc_scope );
do_action( 'bptodo_daily_cleanup' );
remove_action( 'pre_get_posts', $bptodo_rc_scope );
bptodo_rc_check( 'retention completed-only: completed primary trashed', ! bptodo_rc_alive( $p ) );
bptodo_rc_check( 'retention completed-only: completed copy trashed', ! bptodo_rc_alive( $c[ $bptodo_rc_m1 ] ) );
bptodo_rc_check( 'retention completed-only: pending copy kept', bptodo_rc_alive( $c[ $bptodo_rc_m2 ] ) );

// 3. Moderator settings are enforced server-side.
list( $p, $c ) = bptodo_rc_task( $bptodo_rc_admin, $bptodo_rc_group, array( $bptodo_rc_m1, $bptodo_rc_mod ) );
update_option( 'group-todo-list-settings', array( 'enable_todo_tab_group' => 'yes', 'mod_enable' => 'no', 'list_enable' => 'no', 'view_enable' => 'yes' ) );
bptodo_rc_check( 'moderators cannot add: create refused', ! Bptodo_Permissions::can_create_group_todo( $bptodo_rc_group, false, $bptodo_rc_mod ) );
bptodo_rc_check( 'moderators cannot add: group admin still can', Bptodo_Permissions::can_create_group_todo( $bptodo_rc_group, false, $bptodo_rc_admin ) );
update_option( 'group-todo-list-settings', array( 'enable_todo_tab_group' => 'yes', 'mod_enable' => 'yes', 'list_enable' => 'yes', 'view_enable' => 'yes' ) );
bptodo_rc_check( 'lock: moderator cannot edit primary', ! Bptodo_Permissions::can_edit( $p, $bptodo_rc_mod ) );
bptodo_rc_check( 'lock: moderator cannot complete primary', ! Bptodo_Permissions::can_complete( $p, $bptodo_rc_mod ) );
bptodo_rc_check( 'lock: moderator cannot delete primary', ! Bptodo_Permissions::can_delete( $p, $bptodo_rc_mod ) );
bptodo_rc_check( 'lock: moderator cannot add', ! Bptodo_Permissions::can_create_group_todo( $bptodo_rc_group, false, $bptodo_rc_mod ) );
bptodo_rc_check( 'lock: moderator can complete own assigned copy', Bptodo_Permissions::can_complete( $c[ $bptodo_rc_mod ], $bptodo_rc_mod ) );
bptodo_rc_check( 'lock: group admin can still edit', Bptodo_Permissions::can_edit( $p, $bptodo_rc_admin ) );
update_option( 'group-todo-list-settings', array( 'enable_todo_tab_group' => 'yes', 'mod_enable' => 'no', 'list_enable' => 'yes', 'view_enable' => 'yes' ) );
bptodo_rc_check( 'unlocked: moderator can edit primary', Bptodo_Permissions::can_edit( $p, $bptodo_rc_mod ) );
bptodo_rc_check( 'unlocked: moderator can add', Bptodo_Permissions::can_create_group_todo( $bptodo_rc_group, false, $bptodo_rc_mod ) );

// 4. Editing a primary syncs shared fields to copies and keeps their completion state.
$bptodo_rc_terms = get_terms( array( 'taxonomy' => 'todo_category', 'hide_empty' => false, 'number' => 2 ) );
list( $p, $c ) = bptodo_rc_task( $bptodo_rc_admin, $bptodo_rc_group, array( $bptodo_rc_m1, $bptodo_rc_m2 ), array( 'due_date' => '2030-01-01', 'priority' => 'normal' ) );
Bptodo_Todo_Repository::set_status( $c[ $bptodo_rc_m1 ], 'complete', $bptodo_rc_m1 );
wp_set_current_user( $bptodo_rc_admin );
$bptodo_rc_new = array(
	'title'       => 'RC task revised',
	'description' => 'Revised details',
	'due_date'    => '2030-02-02',
	'priority'    => 'critical',
);
if ( $bptodo_rc_terms ) {
	$bptodo_rc_new['category'] = (string) end( $bptodo_rc_terms )->term_id;
}
Bptodo_Todo_Repository::update( $p, $bptodo_rc_new );
foreach ( $c as $bptodo_rc_id ) {
	clean_post_cache( $bptodo_rc_id );
	$bptodo_rc_copy = Bptodo_Todo::from_id( $bptodo_rc_id );
	bptodo_rc_check(
		"sync: copy {$bptodo_rc_id} has title/description/due/priority",
		'RC task revised' === $bptodo_rc_copy->post()->post_title
			&& 'Revised details' === $bptodo_rc_copy->post()->post_content
			&& '2030-02-02' === $bptodo_rc_copy->due_date()
			&& 'critical' === $bptodo_rc_copy->priority()
	);
	if ( $bptodo_rc_terms ) {
		$bptodo_rc_got = wp_get_object_terms( $bptodo_rc_id, 'todo_category', array( 'fields' => 'ids' ) );
		bptodo_rc_check( "sync: copy {$bptodo_rc_id} has the category", array( (int) end( $bptodo_rc_terms )->term_id ) === array_map( 'intval', $bptodo_rc_got ) );
	}
}
bptodo_rc_check( 'sync: completed copy stays complete', Bptodo_Todo::from_id( $c[ $bptodo_rc_m1 ] )->is_complete() );
bptodo_rc_check( 'sync: pending copy stays pending', ! Bptodo_Todo::from_id( $c[ $bptodo_rc_m2 ] )->is_complete() );

// 5. Rescheduling resets the due reminder flags (task and copies).
foreach ( array( $p, $c[ $bptodo_rc_m2 ] ) as $bptodo_rc_id ) {
	update_post_meta( $bptodo_rc_id, Bptodo_Todo::META_MAIL_SENT, 'yes' );
	update_post_meta( $bptodo_rc_id, Bptodo_Todo::META_NOTIF_SENT, 'yes' );
}
Bptodo_Todo_Repository::update( $p, array( 'due_date' => '2030-02-02' ) );
bptodo_rc_check( 'reminder: same due date keeps flags', 'yes' === get_post_meta( $p, Bptodo_Todo::META_MAIL_SENT, true ) );
Bptodo_Todo_Repository::update( $p, array( 'due_date' => '2030-03-03' ) );
foreach ( array( $p, $c[ $bptodo_rc_m2 ] ) as $bptodo_rc_id ) {
	bptodo_rc_check(
		"reminder: new due date resets flags on {$bptodo_rc_id}",
		'no' === get_post_meta( $bptodo_rc_id, Bptodo_Todo::META_MAIL_SENT, true ) && 'no' === get_post_meta( $bptodo_rc_id, Bptodo_Todo::META_NOTIF_SENT, true )
	);
}

// 6. Batch cleanup reaches every matching row (CLI cleanup + retention share it).
for ( $bptodo_rc_i = 0; $bptodo_rc_i < 250; $bptodo_rc_i++ ) {
	$bptodo_rc_id = wp_insert_post( array( 'post_type' => 'bp-todo', 'post_status' => 'publish', 'post_title' => 'RC batch ' . $bptodo_rc_i, 'post_author' => $bptodo_rc_admin ) );
	update_post_meta( $bptodo_rc_id, '_bptodo_rc_batch', '1' );
}
$bptodo_rc_batch_args = array(
	'post_type'  => 'bp-todo',
	'meta_query' => array( array( 'key' => '_bptodo_rc_batch', 'value' => '1' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
);
bptodo_rc_check( 'batch cleanup: removed all 250', 250 === bptodo_delete_todos_in_batches( $bptodo_rc_batch_args, true, 100 ) );
bptodo_rc_check( 'batch cleanup: none left', 0 === count( get_posts( $bptodo_rc_batch_args + array( 'fields' => 'ids', 'posts_per_page' => 1 ) ) ) );

// Cleanup: restore settings, remove seeded rows and the group.
update_option( 'group-todo-list-settings', $bptodo_rc_group_settings );
if ( false === $bptodo_rc_retention_settings ) {
	delete_option( 'data-retention-settings' );
} else {
	update_option( 'data-retention-settings', $bptodo_rc_retention_settings );
}
foreach ( array_unique( $bptodo_rc_posts ) as $bptodo_rc_id ) {
	wp_delete_post( $bptodo_rc_id, true );
}
groups_delete_group( $bptodo_rc_group );

if ( $bptodo_rc_fails ) {
	WP_CLI::error( "{$bptodo_rc_fails} check(s) failed." );
}
WP_CLI::success( 'All group-task checks passed.' );
