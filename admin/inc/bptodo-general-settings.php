<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $bptodo, $wp_roles;
$all_roles          = $wp_roles->get_names();
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
					<!-- PROFILE MENU LABEL -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-profile-menu-label">
								<?php esc_html_e( 'To-do Label (Singular)', 'wb-todo' ); ?>
							</label>
						</div>
						<div class="wbcom-settings-section-options">
							<input type="text" placeholder="<?php esc_html_e( 'Label', 'wb-todo' ); ?>" name="bptodo_profile_menu_label" value="<?php echo esc_html( $bptodo->profile_menu_label, 'wb-todo' ); ?>" class="regular-text" required>
						</div>
					</div>

					<!-- TODO MENU LABEL -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-profile-menu-label">
								<?php esc_html_e( 'To-dos Label (Plural)', 'wb-todo' ); ?>
							</label>
						</div>
						<div class="wbcom-settings-section-options">
							<input type="text" placeholder="<?php esc_html_e( 'To-dos', 'wb-todo' ); ?>" name="bptodo_profile_menu_label_plural" value="<?php echo esc_html( $bptodo->profile_menu_label_plural, 'wb-todo' ); ?>" class="regular-text" required>
						</div>
					</div>

					<!-- ALLOW USER TO HIDE BUTTON ON PROFILE HEADR -->
					<!-- <div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-hide-button">
								<?php // esc_html_e( 'Hide Button', 'wb-todo' );. ?>
							</label>
							<p class="description"><?php // esc_html_e( 'Check this option if you want to hide button on member profile.', 'wb-todo' );. ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="bptodo_hide_button" id="bptodo-hide-button" <?php // echo ( 'yes' == $bptodo->hide_button ) ? 'checked' : 'unchecked';. ?>>
								<span class="wb-slider wb-round"></span>
							</label>
							<label for="bptodo-hide-button"><?php // esc_html_e( 'Allow user to hide button on member profile header.', 'wb-todo' );. ?></label>
						</div>
					</div> -->

					<!-- Enable todo for member -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-enable-todo-member">
								<?php esc_html_e( 'Enable To-do For Member', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Check this option if you want to allow enable todo tab in member.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="bptodo_enable_todo_member" id="bptodo-enable-todo-tab-member" <?php echo ( 'yes' === $bptodo->enable_todo_member ) ? 'checked' : 'unchecked'; ?>>
								<span class="wb-slider wb-round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Enable todo in member', 'wb-todo' ); ?></p>
						</div>
					</div>

					<!-- ALLOW USER TO ADD CATEGORY OF TODO -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-allow-user-add-category">
								<?php esc_html_e( 'Allow User To Add Category', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Check this option if you want to allow normal users of the site to have the ability to create the To-Do category.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="bptodo_allow_user_add_category" id="bptodo-allow-user-add-todo-category" <?php echo ( 'yes' === $bptodo->allow_user_add_category ) ? 'checked' : 'unchecked'; ?>>
								<span class="wb-slider wb-round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Allow the loggedin user to create To-Do category.', 'wb-todo' ); ?></p>
						</div>
					</div>

					<!-- SEND NOTIFICATION AS DUE DATE REMINDER -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-send-notification">
								<?php esc_html_e( 'Send Notification', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Check this option if you want to send notification to the member as a reminder for his/her task due date.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="bptodo_send_notification" id="bptodo-send-todo-due-date-bp-notification" <?php echo ( 'yes' === $bptodo->send_notification ) ? 'checked' : 'unchecked'; ?>>
								<span class="wb-slider wb-round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Send a BP notification to the user whose To-Do due date has arrived.', 'wb-todo' ); ?></p>
						</div>
					</div>

					<!-- SEND EMAIL AS DUE DATE REMINDER -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-send-mail">
								<?php esc_html_e( 'Send Mail', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Check this option if you want to send mail to the member as a reminder for his/her task due date.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="bptodo_send_mail" id="bptodo-send-todo-due-date-mail" <?php echo ( 'yes' === $bptodo->send_mail ) ? 'checked' : 'unchecked'; ?>>
								<span class="wb-slider wb-round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Send a mail to the user whose To-Do due date has arrived.', 'wb-todo' ); ?></p>
						</div>
					</div>
					
					<div class="wbcom-settings-section-wrap bptodo-user-roles-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-send-mail">
								<?php esc_html_e( 'Select user role to create todos', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Select the user roles who can create todos from them profile page.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<select name="bptodo_user_roles[]" id="bptodo_user_roles" multiple="true" data-placeholder="<?php esc_html_e( 'Select user roles', 'wb-todo' ); ?>">
							<?php foreach ( $all_roles as $role_id => $role_name ):
								$role_name       = translate_user_role( $role_name );
							?>
								<option value="<?php echo esc_attr($role_id);?>" <?php if( !empty($bptodo->bptodo_user_roles) && in_array($role_id, $bptodo->bptodo_user_roles)):?> selected <?php endif;?>><?php echo esc_html($role_name);?></option>
							<?php endforeach;?>
							</select>
						</div>
					</div>

					<div class="wbcom-settings-section-wrap bptodo-user-roles-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-send-mail">
								<?php esc_html_e( 'Due date is required?', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Make the required due date, enable the option', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="bptodo_req_duedate" id="bptodo-req-todo-due-date" <?php echo ( 'yes' === $bptodo->req_duedate ) ? 'checked' : 'unchecked'; ?>>
								<span class="wb-slider wb-round"></span>
							</label>
						</div>
					</div>

				</div>
				<p class="submit">
					<?php wp_nonce_field( 'bptodo', 'bptodo-general-settings-nonce' ); ?>
					<input type="submit" name="bptodo-save-settings" class="button button-primary" value="Save Changes">
				</p>
			</form>
		</div>
	</div>
</div>
