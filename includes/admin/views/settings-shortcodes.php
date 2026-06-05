<?php
/**
 * Settings tab: Shortcodes (informational reference).
 *
 * No form — this tab only documents the available shortcodes. The
 * `user-todo-list-shortcodes` option group stays registered in
 * Bptodo_Admin::bptodo_register_shortcode_settings() so the option key
 * is preserved, but nothing on this page writes it.
 *
 * @package bp-user-todo-list
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $bptodo;
$bptodo_plural = isset( $bptodo->profile_menu_label_plural ) ? $bptodo->profile_menu_label_plural : __( 'Todos', 'wb-todo' );
?>
<div class="bptodo-card">
	<div class="bptodo-card__head">
		<p class="bptodo-card__title"><code>[bptodo_list]</code></p>
		<p class="bptodo-card__desc">
			<?php
			/* translators: %s: plural label (e.g. "Todos") */
			printf( esc_html__( 'Display a list of %s with filtering and pagination.', 'wb-todo' ), esc_html( $bptodo_plural ) );
			?>
		</p>
	</div>
	<div class="bptodo-card__body">
		<table class="widefat striped bptodo-shortcode-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Parameter', 'wb-todo' ); ?></th>
					<th><?php esc_html_e( 'Default', 'wb-todo' ); ?></th>
					<th><?php esc_html_e( 'Description', 'wb-todo' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>user_id</code></td>
					<td><code>0</code></td>
					<td><?php esc_html_e( 'User ID to show tasks for. Use "0" for the current user, or "all" for everyone (admin only).', 'wb-todo' ); ?></td>
				</tr>
				<tr>
					<td><code>category</code></td>
					<td><code>""</code></td>
					<td><?php esc_html_e( 'Filter by category slug or ID.', 'wb-todo' ); ?></td>
				</tr>
				<tr>
					<td><code>status</code></td>
					<td><code>all</code></td>
					<td><?php esc_html_e( 'Filter by status: "all", "complete", or "incomplete".', 'wb-todo' ); ?></td>
				</tr>
				<tr>
					<td><code>priority</code></td>
					<td><code>all</code></td>
					<td><?php esc_html_e( 'Filter by priority: "all", "critical", "high", or "normal".', 'wb-todo' ); ?></td>
				</tr>
				<tr>
					<td><code>per_page</code></td>
					<td><code>10</code></td>
					<td><?php esc_html_e( 'Number of items per page.', 'wb-todo' ); ?></td>
				</tr>
				<tr>
					<td><code>orderby</code></td>
					<td><code>due_date</code></td>
					<td><?php esc_html_e( 'Sort by: "due_date", "date", "title", or "priority".', 'wb-todo' ); ?></td>
				</tr>
				<tr>
					<td><code>order</code></td>
					<td><code>ASC</code></td>
					<td><?php esc_html_e( 'Sort order: "ASC" or "DESC".', 'wb-todo' ); ?></td>
				</tr>
				<tr>
					<td><code>show_add</code></td>
					<td><code>no</code></td>
					<td><?php esc_html_e( 'Show the "Add New" button: "yes" or "no".', 'wb-todo' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<div class="bptodo-card">
	<div class="bptodo-card__head">
		<p class="bptodo-card__title"><?php esc_html_e( 'Example Usage', 'wb-todo' ); ?></p>
	</div>
	<div class="bptodo-card__body">
		<ul class="bptodo-shortcode-examples">
			<li><code>[bptodo_list]</code> — <?php esc_html_e( "the current user's tasks", 'wb-todo' ); ?></li>
			<li><code>[bptodo_list user_id="all" status="incomplete"]</code> — <?php esc_html_e( 'all incomplete tasks (admin only)', 'wb-todo' ); ?></li>
			<li><code>[bptodo_list category="work" priority="high" per_page="20"]</code> — <?php esc_html_e( 'high-priority work tasks, 20 per page', 'wb-todo' ); ?></li>
			<li><code>[bptodo_list show_add="yes"]</code> — <?php esc_html_e( 'with the add button visible', 'wb-todo' ); ?></li>
		</ul>
	</div>
</div>

<div class="bptodo-card">
	<div class="bptodo-card__head">
		<p class="bptodo-card__title"><code>[bptodo_by_category]</code></p>
		<p class="bptodo-card__desc"><?php esc_html_e( 'Legacy shortcode, still supported for backward compatibility.', 'wb-todo' ); ?></p>
	</div>
	<div class="bptodo-card__body">
		<p><code>[bptodo_by_category category="CATEGORY_ID"]</code></p>
		<p class="description">
			<?php
			/* translators: %s: plural label (e.g. "Todos") */
			printf( esc_html__( 'Display %s filtered by a specific category ID.', 'wb-todo' ), esc_html( $bptodo_plural ) );
			?>
		</p>
	</div>
</div>
