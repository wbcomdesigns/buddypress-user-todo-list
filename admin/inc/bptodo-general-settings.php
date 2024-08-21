<?php
/**
 * Handles the General Settings page for the BP User Todo List plugin.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $bptodo, $wp_roles;
$all_roles = $wp_roles->get_names();
unset( $all_roles['administrator'] );
?>
<div class="wbcom-tab-content">
	<div class="wbcom-welcome-main-wrapper">
		<div class="wbcom-admin-title-section">
			<h3><?php esc_html_e( 'General Settings', 'wb-todo' ); ?></h3>
		</div>
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
			<form action="" method="POST" id="bptodo-general-settings-form">
				<div class="form-table">
					<!-- Profile Menu Label (Singular) -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-profile-menu-label">
								<?php esc_html_e( 'To-do Label (Singular)', 'wb-todo' ); ?>
							</label>
						</div>
						<div class="wbcom-settings-section-options">
							<input type="text" placeholder="<?php esc_attr_e( 'Label', 'wb-todo' ); ?>" name="bptodo_profile_menu_label" value="<?php echo esc_attr( $bptodo->profile_menu_label ); ?>" class="regular-text" required>
						</div>
					</div>

					<!-- Profile Menu Label (Plural) -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-profile-menu-label-plural">
								<?php esc_html_e( 'To-dos Label (Plural)', 'wb-todo' ); ?>
							</label>
						</div>
						<div class="wbcom-settings-section-options">
							<input type="text" placeholder="<?php esc_attr_e( 'To-dos', 'wb-todo' ); ?>" name="bptodo_profile_menu_label_plural" value="<?php echo esc_attr( $bptodo->profile_menu_label_plural ); ?>" class="regular-text" required>
						</div>
					</div>

					<!-- Enable To-do for Member -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-enable-todo-member">
								<?php esc_html_e( 'Enable To-do For Member', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enable this option to add a To-do tab for members.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="bptodo_enable_todo_member" id="bptodo-enable-todo-tab-member" <?php checked( 'yes', $bptodo->enable_todo_member ); ?>>
								<span class="wb-slider wb-round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Enable To-do tab for members.', 'wb-todo' ); ?></p>
						</div>
					</div>

					<!-- Allow User to Add To-do Category -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-allow-user-add-category">
								<?php esc_html_e( 'Allow User to Add Category', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enable this option to allow users to create To-do categories.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="bptodo_allow_user_add_category" id="bptodo-allow-user-add-todo-category" <?php checked( 'yes', $bptodo->allow_user_add_category ); ?>>
								<span class="wb-slider wb-round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Allow users to create To-do categories.', 'wb-todo' ); ?></p>
						</div>
					</div>

					<!-- Send Notification as Due Date Reminder -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-send-notification">
								<?php esc_html_e( 'Send Notification', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enable this option to send a notification to the member as a reminder for their task due date.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="bptodo_send_notification" id="bptodo-send-todo-due-date-bp-notification" <?php checked( 'yes', $bptodo->send_notification ); ?>>
								<span class="wb-slider wb-round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Send a BuddyPress notification to the user when their To-do due date arrives.', 'wb-todo' ); ?></p>
						</div>
					</div>

					<!-- Send Email as Due Date Reminder -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-send-mail">
								<?php esc_html_e( 'Send Email', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enable this option to send an email reminder to the member for their task due date.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="bptodo_send_mail" id="bptodo-send-todo-due-date-mail" <?php checked( 'yes', $bptodo->send_mail ); ?>>
								<span class="wb-slider wb-round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Send an email to the user when their To-do due date arrives.', 'wb-todo' ); ?></p>
						</div>
					</div>

					<!-- Select User Roles to Create To-dos -->
					<div class="wbcom-settings-section-wrap bptodo-user-roles-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo_user_roles">
								<?php esc_html_e( 'Select User Roles to Create To-dos', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Select the user roles that are allowed to create To-dos from their profile page.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<select name="bptodo_user_roles[]" id="bptodo_user_roles" multiple="multiple" data-placeholder="<?php esc_html_e( 'Select user roles', 'wb-todo' ); ?>">
								<?php foreach ( $all_roles as $role_id => $role_name ) : ?>
									<option value="<?php echo esc_attr( $role_id ); ?>" <?php selected( ! empty( $bptodo->bptodo_user_roles ) && in_array( $role_id, $bptodo->bptodo_user_roles, true ) ); ?>>
										<?php echo esc_html( translate_user_role( $role_name ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<!-- Require Due Date -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo_req_duedate">
								<?php esc_html_e( 'Require Due Date?', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enable this option to make the due date required when creating To-dos.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="bptodo_req_duedate" id="bptodo-req-todo-due-date" <?php checked( 'yes', $bptodo->req_duedate ); ?>>
								<span class="wb-slider wb-round"></span>
							</label>
						</div>
					</div>
				</div>
				<p class="submit">
					<?php wp_nonce_field( 'bptodo', 'bptodo-general-settings-nonce' ); ?>
					<input type="submit" name="bptodo-save-settings" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'wb-todo' ); ?>">
				</p>
			</form>
		</div>
	</div>
</div>
