<?php
/**
 * Settings tab: General.
 *
 * Preserves the legacy custom self-POST save path EXACTLY:
 *   - form posts to the same page (action="")
 *   - nonce field name `bptodo-general-settings-nonce`, action `bptodo`
 *   - submit name `bptodo-save-settings`
 *   - field names `bptodo_profile_menu_label`, `bptodo_profile_menu_label_plural`,
 *     `bptodo_enable_todo_member`, `bptodo_allow_user_add_category`,
 *     `bptodo_send_notification`, `bptodo_send_mail`, `bptodo_user_roles[]`,
 *     `bptodo_req_duedate`.
 * Handled by Bptodo_Admin::bptodo_save_general_settings(), which writes the
 * `user_todo_list_settings` option. Do NOT convert this to the Settings API —
 * the save contract must stay byte-identical to pre-3.6.0.
 *
 * @package bp-user-todo-list
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $bptodo, $wp_roles;
$bptodo_all_roles = $wp_roles->get_names();
unset( $bptodo_all_roles['administrator'] );
?>
<form action="" method="POST" id="bptodo-general-settings-form">
	<div class="bptodo-card">
		<div class="bptodo-card__head">
			<p class="bptodo-card__title"><?php esc_html_e( 'Labels', 'wb-todo' ); ?></p>
			<p class="bptodo-card__desc"><?php esc_html_e( 'What members see this feature called on their profile.', 'wb-todo' ); ?></p>
		</div>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="bptodo-profile-menu-label"><?php esc_html_e( 'Todo Label (Singular)', 'wb-todo' ); ?></label>
				</th>
				<td>
					<input type="text" id="bptodo-profile-menu-label" placeholder="<?php esc_attr_e( 'Todo', 'wb-todo' ); ?>" name="bptodo_profile_menu_label" value="<?php echo esc_attr( $bptodo->profile_menu_label ); ?>" class="regular-text" required>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bptodo-profile-menu-label-plural"><?php esc_html_e( 'Todos Label (Plural)', 'wb-todo' ); ?></label>
				</th>
				<td>
					<input type="text" id="bptodo-profile-menu-label-plural" placeholder="<?php esc_attr_e( 'Todos', 'wb-todo' ); ?>" name="bptodo_profile_menu_label_plural" value="<?php echo esc_attr( $bptodo->profile_menu_label_plural ); ?>" class="regular-text" required>
				</td>
			</tr>
		</table>
	</div>

	<div class="bptodo-card">
		<div class="bptodo-card__head">
			<p class="bptodo-card__title"><?php esc_html_e( 'Member Access', 'wb-todo' ); ?></p>
			<p class="bptodo-card__desc"><?php esc_html_e( 'Who can use to-do lists, and what they are allowed to do.', 'wb-todo' ); ?></p>
		</div>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable for members', 'wb-todo' ); ?></th>
				<td>
					<label class="wb-switch">
						<input type="checkbox" name="bptodo_enable_todo_member" id="bptodo-enable-todo-tab-member" aria-label="<?php esc_attr_e( 'Enable for members', 'wb-todo' ); ?>" <?php checked( 'yes', $bptodo->enable_todo_member ); ?>>
						<span class="wb-slider wb-round"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Adds a To-do tab to every member profile.', 'wb-todo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Member-created categories', 'wb-todo' ); ?></th>
				<td>
					<label class="wb-switch">
						<input type="checkbox" name="bptodo_allow_user_add_category" id="bptodo-allow-user-add-todo-category" aria-label="<?php esc_attr_e( 'Member-created categories', 'wb-todo' ); ?>" <?php checked( 'yes', $bptodo->allow_user_add_category ); ?>>
						<span class="wb-slider wb-round"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Lets members create their own to-do categories.', 'wb-todo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bptodo_user_roles"><?php esc_html_e( 'Roles allowed to create tasks', 'wb-todo' ); ?></label>
				</th>
				<td>
					<select name="bptodo_user_roles[]" id="bptodo_user_roles" class="bptodo-role-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select user roles', 'wb-todo' ); ?>">
						<?php foreach ( $bptodo_all_roles as $bptodo_role_id => $bptodo_role_name ) : ?>
							<option value="<?php echo esc_attr( $bptodo_role_id ); ?>" <?php selected( ! empty( $bptodo->bptodo_user_roles ) && in_array( $bptodo_role_id, $bptodo->bptodo_user_roles, true ) ); ?>>
								<?php echo esc_html( translate_user_role( $bptodo_role_name ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Only members in these roles can create tasks from their profile. Leave empty to allow every member.', 'wb-todo' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<div class="bptodo-card">
		<div class="bptodo-card__head">
			<p class="bptodo-card__title"><?php esc_html_e( 'Reminders', 'wb-todo' ); ?></p>
			<p class="bptodo-card__desc"><?php esc_html_e( 'Tell members when a task is coming due.', 'wb-todo' ); ?></p>
		</div>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Send notification', 'wb-todo' ); ?></th>
				<td>
					<label class="wb-switch">
						<input type="checkbox" name="bptodo_send_notification" id="bptodo-send-todo-due-date-bp-notification" aria-label="<?php esc_attr_e( 'Send notification', 'wb-todo' ); ?>" <?php checked( 'yes', $bptodo->send_notification ); ?>>
						<span class="wb-slider wb-round"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Sends a BuddyPress notification when a task due date arrives.', 'wb-todo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Send email', 'wb-todo' ); ?></th>
				<td>
					<label class="wb-switch">
						<input type="checkbox" name="bptodo_send_mail" id="bptodo-send-todo-due-date-mail" aria-label="<?php esc_attr_e( 'Send email', 'wb-todo' ); ?>" <?php checked( 'yes', $bptodo->send_mail ); ?>>
						<span class="wb-slider wb-round"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Sends an email reminder when a task due date arrives.', 'wb-todo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Require due date', 'wb-todo' ); ?></th>
				<td>
					<label class="wb-switch">
						<input type="checkbox" name="bptodo_req_duedate" id="bptodo-req-todo-due-date" aria-label="<?php esc_attr_e( 'Require due date', 'wb-todo' ); ?>" <?php checked( 'yes', $bptodo->req_duedate ); ?>>
						<span class="wb-slider wb-round"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Makes the due date mandatory when creating a task.', 'wb-todo' ); ?></p>
				</td>
			</tr>
				<tr>
					<th scope="row"><label for="bptodo-layout-mode"><?php esc_html_e( 'Frontend layout', 'wb-todo' ); ?></label></th>
					<td>
						<?php $bptodo_mode = function_exists( 'bptodo_layout_mode' ) ? bptodo_layout_mode() : 'modern'; ?>
						<select name="bptodo_layout_mode" id="bptodo-layout-mode" class="regular-text">
							<option value="modern" <?php selected( $bptodo_mode, 'modern' ); ?>><?php esc_html_e( 'Modern (blocks + REST, faster)', 'wb-todo' ); ?></option>
							<option value="legacy" <?php selected( $bptodo_mode, 'legacy' ); ?>><?php esc_html_e( 'Classic (legacy templates)', 'wb-todo' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Modern uses blocks and the REST API for a faster, no-reload list. Switch to Classic if a theme or plugin conflict appears.', 'wb-todo' ); ?></p>
					</td>
				</tr>
		</table>
	</div>

	<div class="bptodo-save-bar">
		<?php wp_nonce_field( 'bptodo', 'bptodo-general-settings-nonce' ); ?>
		<input type="submit" name="bptodo-save-settings" class="button button-primary bptodo-btn bptodo-btn-primary" value="<?php esc_attr_e( 'Save Settings', 'wb-todo' ); ?>">
	</div>
</form>
