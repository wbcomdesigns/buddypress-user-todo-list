<?php
/**
 * Handles the Group Todo Settings page for the BP User Todo List plugin.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$group_todo_list_settings = get_option( 'group-todo-list-settings' );

if ( bp_is_active( 'groups' ) ) : ?>
	<div class="wbcom-tab-content">
		<div class="wbcom-welcome-main-wrapper">
			<div class="wbcom-admin-title-section">
				<h3><?php esc_html_e( 'Group Todo Settings', 'wb-todo' ); ?></h3>
			</div>
			<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
				<form method="post" action="options.php" id="bptodo-general-settings-form">
					<div class="form-table">
						<?php
						settings_fields( 'group-todo-list-settings' );
						do_settings_sections( 'group-todo-list-settings' );
						?>

						<!-- Enable Todo For Group -->
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label for="bptodo-enable-todo-tab-group">
									<?php esc_html_e( 'Enable Todo For Group', 'wb-todo' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enable this option to add a Todo tab for groups.', 'wb-todo' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label class="wb-switch">
									<input type="checkbox" name="group-todo-list-settings[enable_todo_tab_group]" id="bptodo-enable-todo-tab-group" value="yes"
									<?php checked( isset( $group_todo_list_settings['enable_todo_tab_group'] ) ? $group_todo_list_settings['enable_todo_tab_group'] : 'no', 'yes' ); ?>>
									<span class="wb-slider wb-round"></span>
								</label>
							</div>
						</div>

						<!-- Allow Group Moderators to Modify Todo List -->
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label for="bptodo-on-moderator">
									<?php esc_html_e( 'Prevent Group Moderators from Modifying Todo List', 'wb-todo' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enable this option to prevent group moderators from modifying the Todo list.', 'wb-todo' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label class="wb-switch">
									<input type="checkbox" name="group-todo-list-settings[mod_enable]" id="bptodo-on-moderator" value="yes"
									<?php checked( isset( $group_todo_list_settings['mod_enable'] ) ? $group_todo_list_settings['mod_enable'] : 'no', 'yes' ); ?>>
									<span class="wb-slider wb-round"></span>
								</label>
							</div>
						</div>

						<!-- Include Group Moderators in Todo Average List Calculation -->
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label for="bptodo-list-moderator">
									<?php esc_html_e( 'Allow Group Moderators to add Todos in group', 'wb-todo' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enable this option to allow group moderators to add Todos to the group.', 'wb-todo' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label class="wb-switch">
									<input type="checkbox" name="group-todo-list-settings[list_enable]" id="bptodo-list-moderator" value="yes"
									<?php checked( isset( $group_todo_list_settings['list_enable'] ) ? $group_todo_list_settings['list_enable'] : 'no', 'yes' ); ?>>
									<span class="wb-slider wb-round"></span>
								</label>
							</div>
						</div>

						<!-- Allow Group Moderators to View Todo List Report -->
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label for="bptodo-view-moderator">
									<?php esc_html_e( 'Allow Group Moderators to View Todo List Report', 'wb-todo' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enable this option to allow group moderators to view reports on the Todo list.', 'wb-todo' ); ?></p>
							</div>
							<div class="wbcom-settings-section-options">
								<label class="wb-switch">
									<input type="checkbox" name="group-todo-list-settings[view_enable]" id="bptodo-view-moderator" value="yes"
									<?php checked( isset( $group_todo_list_settings['view_enable'] ) ? $group_todo_list_settings['view_enable'] : 'no', 'yes' ); ?>>
									<span class="wb-slider wb-round"></span>
								</label>
							</div>
						</div>
					</div>
					<?php submit_button(); ?>
				</form>
			</div>
		</div>
	</div>
<?php else : ?>
	<div class="wbcom-tab-content">
		<div class="wbcom-welcome-main-wrapper">
			<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
				<div class="form-table">
					<div class="bptodo-groups">
						<?php
						/* translators: %s */
						$bp_groups_link = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( admin_url( 'admin.php?page=bp-components' ) ), esc_html__( 'Click here', 'wb-todo' ) );
						 /* translators: %s */
						printf( '<span>%s</span>', wp_kses_post( sprintf( __( 'Enable the BuddyPress groups component to allow group integration %s.', 'wb-todo' ), '<strong>' . $bp_groups_link . '</strong>' ) ) );
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>
