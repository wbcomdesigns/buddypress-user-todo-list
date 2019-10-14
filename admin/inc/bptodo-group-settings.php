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
						<label class="switch">
							<input type="checkbox" name="group-todo-list-settings[mod_enable]" id="bptodo-on-moderator" value="yes" <?php if(isset($group_todo_list_settings['mod_enable'] )) {checked( 'yes', $group_todo_list_settings['mod_enable'] ); }?>>
							<span class="slider round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Check this option if you want to permit group moderators to modify To Do list.', 'wb-todo' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
