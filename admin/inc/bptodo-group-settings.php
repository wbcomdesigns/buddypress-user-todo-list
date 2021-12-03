<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$group_todo_list_settings = get_option( 'group-todo-list-settings' );
?>
<div class="wbcom-tab-content">
	<form method="post" action="options.php">
		<table class="form-table">
			<?php
			settings_fields( 'group-todo-list-settings' );
			do_settings_sections( 'group-todo-list-settings' );
			?>
			<tbody>
				<!-- SEND EMAIL AS DUE DATE REMINDER -->
				<tr>
					<th scope="row"><label for="bptodo-on-moderator"><?php esc_html_e( 'Allow group moderators to modify To Do list', 'wb-todo' ); ?></label></th>
					<td>
						<label class="wb-switch">
							<input type="checkbox" name="group-todo-list-settings[mod_enable]" id="bptodo-on-moderator" value="yes"
							<?php
							if ( isset( $group_todo_list_settings['mod_enable'] ) ) {
								checked( 'yes', $group_todo_list_settings['mod_enable'] ); }
							?>
							>
							<span class="wb-slider wb-round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Check this option if you want to permit group moderators to modify To Do list.', 'wb-todo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bptodo-list-moderator"><?php esc_html_e( 'Allow group moderators to add in To Do list', 'wb-todo' ); ?></label></th>
					<td>
						<label class="wb-switch">
							<input type="checkbox" name="group-todo-list-settings[list_enable]" id="bptodo-list-moderator" value="yes"
							<?php
							if ( isset( $group_todo_list_settings['list_enable'] ) ) {
								checked( 'yes', $group_todo_list_settings['list_enable'] ); }
							?>
							>
							<span class="wb-slider wb-round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Check this option if you want to permit group moderators to add in To Do average list.', 'wb-todo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bptodo-view-moderator"><?php esc_html_e( 'Allow group moderators to view To Do list report', 'wb-todo' ); ?></label></th>
					<td>
						<label class="wb-switch">
							<input type="checkbox" name="group-todo-list-settings[view_enable]" id="bptodo-view-moderator" value="yes"
							<?php
							if ( isset( $group_todo_list_settings['view_enable'] ) ) {
								checked( 'yes', $group_todo_list_settings['view_enable'] ); }
							?>
							>
							<span class="wb-slider wb-round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Check this option if you want to permit group moderators to view report on single todo page.', 'wb-todo' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
