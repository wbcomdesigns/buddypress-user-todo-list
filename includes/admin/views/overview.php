<?php
/**
 * Overview dashboard partial: rendered inside page-layout.php.
 *
 * Read-only snapshot of the current configuration plus aggregate task
 * counts. Counts use WP_Query found_posts (COUNT(*) under the hood) with
 * fields => ids and posts_per_page => 1 so nothing unbounded is loaded.
 *
 * @package bp-user-todo-list
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $bptodo;

$bptodo_general   = get_option( 'user_todo_list_settings', array() );
$bptodo_group_opt = get_option( 'group-todo-list-settings', array() );
$bptodo_retention = get_option( 'data-retention-settings', array() );

// Aggregate task counts (COUNT(*) via found_posts; no rows hydrated).
$bptodo_count_query = static function ( $extra = array() ) {
	$args  = array_merge(
		array(
			'post_type'              => 'bp-todo',
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'posts_per_page'         => 1,
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		),
		$extra
	);
	$query = new WP_Query( $args );
	return (int) $query->found_posts;
};

$bptodo_total_tasks  = $bptodo_count_query();
$bptodo_completed    = $bptodo_count_query(
	array(
		'meta_key'   => 'todo_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value' => 'complete', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	)
);
$bptodo_group_tasks  = $bptodo_count_query(
	array(
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => 'todo_group_id',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
		),
	)
);
$bptodo_active_tasks = max( 0, $bptodo_total_tasks - $bptodo_completed );

// Feature status flags.
$bptodo_member_on    = ! empty( $bptodo_general['enable_todo_member'] ) && 'yes' === $bptodo_general['enable_todo_member'];
$bptodo_group_on     = ! empty( $bptodo_group_opt['enable_todo_tab_group'] ) && 'yes' === $bptodo_group_opt['enable_todo_tab_group'];
$bptodo_notify_on    = ! empty( $bptodo_general['send_notification'] ) && 'yes' === $bptodo_general['send_notification'];
$bptodo_email_on     = ! empty( $bptodo_general['send_mail'] ) && 'yes' === $bptodo_general['send_mail'];
$bptodo_addcat_on    = ! empty( $bptodo_general['allow_user_add_category'] ) && 'yes' === $bptodo_general['allow_user_add_category'];
$bptodo_retention_on = ! empty( $bptodo_retention['enable_retention'] ) && 'yes' === $bptodo_retention['enable_retention'];

$bptodo_general_url   = admin_url( 'admin.php?page=user-todo-list-settings&tab=general' );
$bptodo_group_url     = admin_url( 'admin.php?page=user-todo-list-settings&tab=group' );
$bptodo_retention_url = admin_url( 'admin.php?page=user-todo-list-settings&tab=retention' );
?>

<?php if ( ! function_exists( 'bp_is_active' ) ) : ?>
	<div class="bptodo-notice bptodo-notice--warning">
		<span class="dashicons dashicons-warning" aria-hidden="true"></span>
		<div>
			<strong><?php esc_html_e( 'BuddyPress or BuddyBoss is not active.', 'bp-user-to-do-list' ); ?></strong>
			<?php esc_html_e( 'This plugin needs BuddyPress or BuddyBoss for member and group to-do lists. Activate one to use all features.', 'bp-user-to-do-list' ); ?>
		</div>
	</div>
<?php endif; ?>

<div class="bptodo-stats-grid">
	<div class="bptodo-stat">
		<p class="bptodo-stat__label"><?php esc_html_e( 'Total Tasks', 'bp-user-to-do-list' ); ?></p>
		<p class="bptodo-stat__value"><?php echo esc_html( number_format_i18n( $bptodo_total_tasks ) ); ?></p>
		<p class="bptodo-stat__trend"><?php esc_html_e( 'Tasks created across the site', 'bp-user-to-do-list' ); ?></p>
	</div>
	<div class="bptodo-stat">
		<p class="bptodo-stat__label"><?php esc_html_e( 'Active Tasks', 'bp-user-to-do-list' ); ?></p>
		<p class="bptodo-stat__value"><?php echo esc_html( number_format_i18n( $bptodo_active_tasks ) ); ?></p>
		<p class="bptodo-stat__trend"><?php esc_html_e( 'Tasks not yet completed', 'bp-user-to-do-list' ); ?></p>
	</div>
	<div class="bptodo-stat">
		<p class="bptodo-stat__label"><?php esc_html_e( 'Completed Tasks', 'bp-user-to-do-list' ); ?></p>
		<p class="bptodo-stat__value"><?php echo esc_html( number_format_i18n( $bptodo_completed ) ); ?></p>
		<p class="bptodo-stat__trend"><?php esc_html_e( 'Tasks marked complete', 'bp-user-to-do-list' ); ?></p>
	</div>
	<div class="bptodo-stat">
		<p class="bptodo-stat__label"><?php esc_html_e( 'Group Tasks', 'bp-user-to-do-list' ); ?></p>
		<p class="bptodo-stat__value"><?php echo esc_html( number_format_i18n( $bptodo_group_tasks ) ); ?></p>
		<p class="bptodo-stat__trend"><?php esc_html_e( 'Tasks attached to a group', 'bp-user-to-do-list' ); ?></p>
	</div>
</div>

<div class="bptodo-card">
	<div class="bptodo-card__head">
		<p class="bptodo-card__title"><?php esc_html_e( 'Current Configuration', 'bp-user-to-do-list' ); ?></p>
		<p class="bptodo-card__desc"><?php esc_html_e( 'What members can do with to-do lists right now.', 'bp-user-to-do-list' ); ?></p>
	</div>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Member to-do lists', 'bp-user-to-do-list' ); ?></th>
			<td>
				<?php if ( $bptodo_member_on ) : ?>
					<span class="bptodo-status bptodo-status--on"><?php esc_html_e( 'On: members get a To-do tab on their profile', 'bp-user-to-do-list' ); ?></span>
				<?php else : ?>
					<span class="bptodo-status bptodo-status--off"><?php esc_html_e( 'Off', 'bp-user-to-do-list' ); ?></span>
					<a href="<?php echo esc_url( $bptodo_general_url ); ?>" class="bptodo-inline-hint"><?php esc_html_e( 'Turn on', 'bp-user-to-do-list' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Group to-do lists', 'bp-user-to-do-list' ); ?></th>
			<td>
				<?php if ( $bptodo_group_on ) : ?>
					<span class="bptodo-status bptodo-status--on"><?php esc_html_e( 'On: groups get a To-do tab', 'bp-user-to-do-list' ); ?></span>
				<?php else : ?>
					<span class="bptodo-status bptodo-status--off"><?php esc_html_e( 'Off', 'bp-user-to-do-list' ); ?></span>
					<a href="<?php echo esc_url( $bptodo_group_url ); ?>" class="bptodo-inline-hint"><?php esc_html_e( 'Turn on', 'bp-user-to-do-list' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Due-date notifications', 'bp-user-to-do-list' ); ?></th>
			<td>
				<?php if ( $bptodo_notify_on ) : ?>
					<span class="bptodo-status bptodo-status--on"><?php esc_html_e( 'On: members get a reminder when a task is due', 'bp-user-to-do-list' ); ?></span>
				<?php else : ?>
					<span class="bptodo-status bptodo-status--off"><?php esc_html_e( 'Off', 'bp-user-to-do-list' ); ?></span>
					<a href="<?php echo esc_url( $bptodo_general_url ); ?>" class="bptodo-inline-hint"><?php esc_html_e( 'Turn on', 'bp-user-to-do-list' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Due-date emails', 'bp-user-to-do-list' ); ?></th>
			<td>
				<?php if ( $bptodo_email_on ) : ?>
					<span class="bptodo-status bptodo-status--on"><?php esc_html_e( 'On: members get an email reminder when a task is due', 'bp-user-to-do-list' ); ?></span>
				<?php else : ?>
					<span class="bptodo-status bptodo-status--off"><?php esc_html_e( 'Off', 'bp-user-to-do-list' ); ?></span>
					<a href="<?php echo esc_url( $bptodo_general_url ); ?>" class="bptodo-inline-hint"><?php esc_html_e( 'Turn on', 'bp-user-to-do-list' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Member-created categories', 'bp-user-to-do-list' ); ?></th>
			<td>
				<?php if ( $bptodo_addcat_on ) : ?>
					<span class="bptodo-status bptodo-status--on"><?php esc_html_e( 'On: members can create their own categories', 'bp-user-to-do-list' ); ?></span>
				<?php else : ?>
					<span class="bptodo-status bptodo-status--off"><?php esc_html_e( 'Off', 'bp-user-to-do-list' ); ?></span>
					<a href="<?php echo esc_url( $bptodo_general_url ); ?>" class="bptodo-inline-hint"><?php esc_html_e( 'Turn on', 'bp-user-to-do-list' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Auto-cleanup', 'bp-user-to-do-list' ); ?></th>
			<td>
				<?php if ( $bptodo_retention_on ) : ?>
					<span class="bptodo-status bptodo-status--on"><?php esc_html_e( 'On: old tasks are cleaned up automatically', 'bp-user-to-do-list' ); ?></span>
				<?php else : ?>
					<span class="bptodo-status bptodo-status--off"><?php esc_html_e( 'Off', 'bp-user-to-do-list' ); ?></span>
					<a href="<?php echo esc_url( $bptodo_retention_url ); ?>" class="bptodo-inline-hint"><?php esc_html_e( 'Configure', 'bp-user-to-do-list' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
	</table>
</div>

<div class="bptodo-card">
	<div class="bptodo-card__head">
		<p class="bptodo-card__title"><?php esc_html_e( 'Quick Actions', 'bp-user-to-do-list' ); ?></p>
	</div>
	<div class="bptodo-card__body">
		<div class="bptodo-action-row">
			<a href="<?php echo esc_url( $bptodo_general_url ); ?>" class="bptodo-btn bptodo-btn-secondary">
				<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
				<?php esc_html_e( 'General Settings', 'bp-user-to-do-list' ); ?>
			</a>
			<a href="<?php echo esc_url( $bptodo_group_url ); ?>" class="bptodo-btn bptodo-btn-secondary">
				<span class="dashicons dashicons-groups" aria-hidden="true"></span>
				<?php esc_html_e( 'Group Todo', 'bp-user-to-do-list' ); ?>
			</a>
			<a href="<?php echo esc_url( $bptodo_retention_url ); ?>" class="bptodo-btn bptodo-btn-secondary">
				<span class="dashicons dashicons-database" aria-hidden="true"></span>
				<?php esc_html_e( 'Data Retention', 'bp-user-to-do-list' ); ?>
			</a>
		</div>
	</div>
</div>
