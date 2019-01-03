<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $bptodo;
?>
<div class="wbcom-tab-content">
	<form action="" method="POST" id="bptodo-general-settings-form">
		<table class="form-table">
			<tbody>
				<!-- PROFILE MENU LABEL -->
				<tr>
					<th scope="row"><label for="bptodo-profile-menu-label"><?php esc_html_e( 'To-do Label (Singular)', 'wb-todo' ); ?></label></th>
					<td>
						<input type="text" placeholder="<?php esc_html_e( 'Label', 'wb-todo' ); ?>" name="bptodo_profile_menu_label" value="<?php echo esc_html( $bptodo->profile_menu_label, 'wb-todo' ); ?>" class="regular-text" required>
					</td>
				</tr>

				<!-- TODO MENU LABEL -->
				<tr>
					<th scope="row"><label for="bptodo-profile-menu-label"><?php esc_html_e( 'To-dos Label (Plural)', 'wb-todo' ); ?></label></th>
					<td>
						<input type="text" placeholder="<?php esc_html_e( 'To-dos', 'wb-todo' ); ?>" name="bptodo_profile_menu_label_plural" value="<?php echo esc_html( $bptodo->profile_menu_label_plural, 'wb-todo' ); ?>" class="regular-text" required>
					</td>
				</tr>

				<!-- ALLOW USER TO ADD CATEGORY OF TODO -->
				<tr>
					<th scope="row"><label for="bptodo-allow-user-add-category"><?php esc_html_e( 'Allow User To Add Category', 'wb-todo' ); ?></label></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="bptodo_allow_user_add_category" id="bptodo-allow-user-add-todo-category" <?php echo ( 'yes' == $bptodo->allow_user_add_category ) ? 'checked' : 'unchecked'; ?>>
							<span class="slider round"></span>
						</label>
						<label for="bptodo-allow-user-add-todo-category"><?php esc_html_e( 'Allow the loggedin user to create To-Do category.', 'wb-todo' ); ?></label>
						<p class="description"><?php esc_html_e( 'Check this option if you want to allow normal users of the site to have the ability to create the To-Do category.', 'wb-todo' ); ?></p>
					</td>
				</tr>

				<!-- SEND NOTIFICATION AS DUE DATE REMINDER -->
				<tr>
					<th scope="row"><label for="bptodo-send-notification"><?php esc_html_e( 'Send Notification', 'wb-todo' ); ?></label></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="bptodo_send_notification" id="bptodo-send-todo-due-date-bp-notification" <?php echo ( 'yes' == $bptodo->send_notification ) ? 'checked' : 'unchecked'; ?>>
							<span class="slider round"></span>
						</label>
						<label for="bptodo-send-todo-due-date-bp-notification"><?php esc_html_e( 'Send a BP notification to the user whose To-Do due date has arrived.', 'wb-todo' ); ?></label>
						<p class="description"><?php esc_html_e( 'Check this option if you want to send notification to the member as a reminder for his/her task due date.', 'wb-todo' ); ?></p>
					</td>
				</tr>

				<!-- SEND EMAIL AS DUE DATE REMINDER -->
				<tr>
					<th scope="row"><label for="bptodo-send-mail"><?php esc_html_e( 'Send Mail', 'wb-todo' ); ?></label></th>
					<td>
						<label class="switch">
							<input type="checkbox" name="bptodo_send_mail" id="bptodo-send-todo-due-date-mail" <?php echo ( 'yes' == $bptodo->send_mail ) ? 'checked' : 'unchecked'; ?>>
							<span class="slider round"></span>
						</label>
						<label for="bptodo-send-todo-due-date-mail"><?php esc_html_e( 'Send a mail to the user whose To-Do due date has arrived.', 'wb-todo' ); ?></label>
						<p class="description"><?php esc_html_e( 'Check this option if you want to send mail to the member as a reminder for his/her task due date.', 'wb-todo' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<?php wp_nonce_field( 'bptodo', 'bptodo-general-settings-nonce' ); ?>
			<input type="submit" name="bptodo-save-settings" class="button button-primary" value="Save Changes">
		</p>
	</form>
</div>
