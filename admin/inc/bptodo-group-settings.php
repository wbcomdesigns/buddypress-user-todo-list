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
if ( bp_is_active( 'groups' ) ) {
	?>
<div class="wbcom-tab-content">
	<div class="wbcom-welcome-main-wrapper">
		<div class="wbcom-admin-title-section">
			<h3><?php esc_html_e( 'Group To-do Settings', 'wb-todo' ); ?></h3>
		</div>
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
			<form method="post" action="options.php" id="bptodo-general-settings-form">
				<div class="form-table">
					<?php
					settings_fields( 'group-todo-list-settings' );
					do_settings_sections( 'group-todo-list-settings' );
					?>
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-enable-todo-tab-group">
								<?php esc_html_e( 'Enable To-do For Group', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Check this option if you want to allow enable todo tab in group.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="group-todo-list-settings[enable_todo_tab_group]" id="bptodo-enable-todo-tab-group" value="yes"
								<?php
								if ( isset( $group_todo_list_settings['enable_todo_tab_group'] ) ) {
									checked( 'yes', $group_todo_list_settings['enable_todo_tab_group'] ); }
								?>
								>
								<span class="wb-slider wb-round"></span>
							</label>
						</div>
					</div>
					<!-- SEND EMAIL AS DUE DATE REMINDER -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-on-moderator">
								<?php esc_html_e( 'Allow group moderators to modify To Do list', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Check this option if you want to permit group moderators to modify To Do list.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="group-todo-list-settings[mod_enable]" id="bptodo-on-moderator" value="yes"
								<?php
								if ( isset( $group_todo_list_settings['mod_enable'] ) ) {
									checked( 'yes', $group_todo_list_settings['mod_enable'] ); }
								?>
								>
								<span class="wb-slider wb-round"></span>
							</label>
						</div>
					</div>
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-list-moderator">
								<?php esc_html_e( 'Include Group Moderators in To do Average List Calculation', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Activate this option if you wish to include group moderators in the calculation of group todo completion.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="group-todo-list-settings[list_enable]" id="bptodo-list-moderator" value="yes"
								<?php
								if ( isset( $group_todo_list_settings['list_enable'] ) ) {
									checked( 'yes', $group_todo_list_settings['list_enable'] ); }
								?>
								>
								<span class="wb-slider wb-round"></span>
							</label>
						</div>
					</div>
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-view-moderator">
								<?php esc_html_e( 'Allow group moderators to view To Do list report', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Check this option if you want to permit group moderators to view report on single todo page.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="group-todo-list-settings[view_enable]" id="bptodo-view-moderator" value="yes"
								<?php
								if ( isset( $group_todo_list_settings['view_enable'] ) ) {
									checked( 'yes', $group_todo_list_settings['view_enable'] ); }
								?>
								>
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
	<?php
} else {
	?>
<div class="wbcom-tab-content">
	<div class="wbcom-welcome-main-wrapper">
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
			<div class="form-table">
				<div class="bptodo-groups">
					<?php
					$bp_groups_link = '<a href="' . admin_url( 'admin.php?page=bp-components' ) . '" target="blank">Click here</a>';
					?>
					<span>
						<?php echo sprintf( esc_html( 'Enable BuddyPress groups component to allow groups integration %1$s.' ), '<strong>' . wp_kses_post( $bp_groups_link ) . '</strong>' ); ?>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>
<?php }
?>
