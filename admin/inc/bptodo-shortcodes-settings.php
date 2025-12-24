<?php
/**
 * Shortcodes documentation page for the BP User Todo List plugin.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $bptodo;
?>
<div class="wbcom-tab-content">
	<div class="wbcom-welcome-main-wrapper form-table">
		<div class="wbcom-admin-title-section">
			<h3><?php esc_html_e( 'Shortcodes', 'wb-todo' ); ?></h3>
		</div>
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">

			<!-- Main Shortcode -->
			<div class="bptodo-shortcode-tab wbcom-settings-section-wrap">
				<div class="wbcom-settings-section-options-heading">
					<label><code>[bptodo_list]</code></label>
					<p class="description">
						<?php
						/* translators: %s: Plural label name (e.g., "Todos") */
						printf( esc_html__( 'Display a list of %s with full filtering and pagination support.', 'wb-todo' ), esc_html( $bptodo->profile_menu_label_plural ) );
						?>
					</p>
				</div>
				<div class="wbcom-settings-section-options">
					<strong class="description"><?php esc_html_e( 'Available Parameters:', 'wb-todo' ); ?></strong>
					<table class="widefat striped" style="margin-top: 10px;">
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
								<td><?php esc_html_e( 'User ID to show todos for. Use "0" for current user, or "all" for all users (admin only).', 'wb-todo' ); ?></td>
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
								<td><?php esc_html_e( 'Show "Add New" button: "yes" or "no".', 'wb-todo' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Example Usage -->
			<div class="bptodo-shortcode-tab wbcom-settings-section-wrap" style="margin-top: 20px;">
				<div class="wbcom-settings-section-options-heading">
					<label><?php esc_html_e( 'Example Usage', 'wb-todo' ); ?></label>
				</div>
				<div class="wbcom-settings-section-options">
					<p><code>[bptodo_list]</code> - <?php esc_html_e( 'Current user\'s todos', 'wb-todo' ); ?></p>
					<p><code>[bptodo_list user_id="all" status="incomplete"]</code> - <?php esc_html_e( 'All incomplete todos (admin only)', 'wb-todo' ); ?></p>
					<p><code>[bptodo_list category="work" priority="high" per_page="20"]</code> - <?php esc_html_e( 'High priority work todos, 20 per page', 'wb-todo' ); ?></p>
					<p><code>[bptodo_list show_add="yes"]</code> - <?php esc_html_e( 'With add button visible', 'wb-todo' ); ?></p>
				</div>
			</div>

			<!-- Legacy Shortcode -->
			<div class="bptodo-shortcode-tab wbcom-settings-section-wrap" style="margin-top: 20px;">
				<div class="wbcom-settings-section-options-heading">
					<label><code>[bptodo_by_category]</code></label>
					<p class="description"><?php esc_html_e( 'Legacy shortcode (still supported for backward compatibility).', 'wb-todo' ); ?></p>
				</div>
				<div class="wbcom-settings-section-options">
					<p><code>[bptodo_by_category category="<em>CATEGORY_ID</em>"]</code></p>
					<p class="description">
						<?php
						/* translators: %s: Plural label name (e.g., "Todos") */
						printf( esc_html__( 'Display %s filtered by a specific category ID.', 'wb-todo' ), esc_html( $bptodo->profile_menu_label_plural ) );
						?>
					</p>
				</div>
			</div>

		</div>
	</div>
</div>
