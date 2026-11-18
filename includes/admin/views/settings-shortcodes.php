<?php
/**
 * Settings tab: Shortcodes (informational reference).
 *
 * No form - this tab only documents the available shortcodes. The
 * `user-todo-list-shortcodes` option group stays registered in
 * Bptodo_Admin::bptodo_register_shortcode_settings() so the option key
 * is preserved, but nothing on this page writes it.
 *
 * @package bp-user-todo-list
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $bptodo;
$bptodo_plural = isset( $bptodo->profile_menu_label_plural ) ? $bptodo->profile_menu_label_plural : __( 'Todos', 'bp-user-to-do-list' );
?>
<div class="bptodo-card">
	<div class="bptodo-card__head">
		<p class="bptodo-card__title"><?php esc_html_e( 'Place a to-do list on any page', 'bp-user-to-do-list' ); ?></p>
		<p class="bptodo-card__desc">
			<?php esc_html_e( 'This plugin works on any WordPress site, with or without BuddyPress. To show a personal to-do list on a page, add the "My Todos" block in the block editor, or use the [bptodo_list] shortcode below. When BuddyPress or BuddyBoss is active it also adds a To-do tab on member profiles and group to-dos - no block or shortcode needed for those.', 'bp-user-to-do-list' ); ?>
		</p>
	</div>
</div>

<div class="bptodo-card">
	<div class="bptodo-card__head">
		<p class="bptodo-card__title"><code>[bptodo_list]</code></p>
		<p class="bptodo-card__desc">
			<?php
			/* translators: %s: plural label (e.g. "Todos") */
			printf( esc_html__( 'Display a list of %s with filtering and pagination.', 'bp-user-to-do-list' ), esc_html( $bptodo_plural ) );
			?>
		</p>
	</div>
	<div class="bptodo-card__body">
		<table class="widefat striped bptodo-shortcode-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Parameter', 'bp-user-to-do-list' ); ?></th>
					<th><?php esc_html_e( 'Default', 'bp-user-to-do-list' ); ?></th>
					<th><?php esc_html_e( 'Description', 'bp-user-to-do-list' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>user_id</code></td>
					<td><code>0</code></td>
					<td><?php esc_html_e( 'User ID to show tasks for. Use "0" for the current user, or "all" for everyone (admin only).', 'bp-user-to-do-list' ); ?></td>
				</tr>
				<tr>
					<td><code>category</code></td>
					<td><code>""</code></td>
					<td><?php esc_html_e( 'Filter by category slug or ID.', 'bp-user-to-do-list' ); ?></td>
				</tr>
				<tr>
					<td><code>status</code></td>
					<td><code>all</code></td>
					<td><?php esc_html_e( 'Filter by status: "all", "complete", or "incomplete".', 'bp-user-to-do-list' ); ?></td>
				</tr>
				<tr>
					<td><code>priority</code></td>
					<td><code>all</code></td>
					<td><?php esc_html_e( 'Filter by priority: "all", "critical", "high", or "normal".', 'bp-user-to-do-list' ); ?></td>
				</tr>
				<tr>
					<td><code>per_page</code></td>
					<td><code>10</code></td>
					<td><?php esc_html_e( 'Number of items per page.', 'bp-user-to-do-list' ); ?></td>
				</tr>
				<tr>
					<td><code>orderby</code></td>
					<td><code>due_date</code></td>
					<td><?php esc_html_e( 'Sort by: "due_date", "date", "title", or "priority".', 'bp-user-to-do-list' ); ?></td>
				</tr>
				<tr>
					<td><code>order</code></td>
					<td><code>ASC</code></td>
					<td><?php esc_html_e( 'Sort order: "ASC" or "DESC".', 'bp-user-to-do-list' ); ?></td>
				</tr>
				<tr>
					<td><code>show_add</code></td>
					<td><code>no</code></td>
					<td><?php esc_html_e( 'Show the inline quick-add field: "yes" or "no". Set to "yes" on a standalone page so visitors can add tasks.', 'bp-user-to-do-list' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<div class="bptodo-card">
	<div class="bptodo-card__head">
		<p class="bptodo-card__title"><?php esc_html_e( 'Example Usage', 'bp-user-to-do-list' ); ?></p>
	</div>
	<div class="bptodo-card__body">
		<ul class="bptodo-shortcode-examples">
			<li><code>[bptodo_list]</code> - <?php esc_html_e( "the current user's tasks", 'bp-user-to-do-list' ); ?></li>
			<li><code>[bptodo_list user_id="all" status="incomplete"]</code> - <?php esc_html_e( 'all incomplete tasks (admin only)', 'bp-user-to-do-list' ); ?></li>
			<li><code>[bptodo_list category="work" priority="high" per_page="20"]</code> - <?php esc_html_e( 'high-priority work tasks, 20 per page', 'bp-user-to-do-list' ); ?></li>
			<li><code>[bptodo_list show_add="yes"]</code> - <?php esc_html_e( 'with the add button visible', 'bp-user-to-do-list' ); ?></li>
		</ul>
	</div>
</div>

<div class="bptodo-card">
	<div class="bptodo-card__head">
		<p class="bptodo-card__title"><code>[bptodo_by_category]</code></p>
		<p class="bptodo-card__desc"><?php esc_html_e( 'Shorthand for [bptodo_list category="..."]. Renders the same modern list, filtered to one category.', 'bp-user-to-do-list' ); ?></p>
	</div>
	<div class="bptodo-card__body">
		<p><code>[bptodo_by_category category="CATEGORY_ID"]</code></p>
		<p class="description">
			<?php
			/* translators: %s: plural label (e.g. "Todos") */
			printf( esc_html__( 'Display %s filtered by a specific category ID.', 'bp-user-to-do-list' ), esc_html( $bptodo_plural ) );
			?>
		</p>
	</div>
</div>
