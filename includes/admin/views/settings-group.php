<?php
/**
 * Settings tab: Group Todo.
 *
 * Preserves the Settings API save path EXACTLY:
 *   - form posts to options.php
 *   - settings_fields( 'group-todo-list-settings' )
 *   - option key `group-todo-list-settings`
 *   - field names group-todo-list-settings[enable_todo_tab_group|mod_enable|list_enable|view_enable]
 * Sanitized by Bptodo_Admin::bptodo_sanitize_group_settings().
 *
 * @package bp-user-todo-list
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

$bptodo_group_settings = get_option( 'group-todo-list-settings', array() );
?>
<?php if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) : ?>
	<form method="post" action="options.php" id="bptodo-group-settings-form">
		<?php settings_fields( 'group-todo-list-settings' ); ?>

		<div class="bptodo-card">
			<div class="bptodo-card__head">
				<p class="bptodo-card__title"><?php esc_html_e( 'Group Todo', 'wb-todo' ); ?></p>
				<p class="bptodo-card__desc"><?php esc_html_e( 'Add a shared to-do list to BuddyPress groups and decide what group moderators can do with it.', 'wb-todo' ); ?></p>
			</div>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable for groups', 'wb-todo' ); ?></th>
					<td>
						<label class="wb-switch">
							<input type="checkbox" name="group-todo-list-settings[enable_todo_tab_group]" id="bptodo-enable-todo-tab-group" value="yes" aria-label="<?php esc_attr_e( 'Enable for groups', 'wb-todo' ); ?>"
								<?php checked( isset( $bptodo_group_settings['enable_todo_tab_group'] ) ? $bptodo_group_settings['enable_todo_tab_group'] : 'no', 'yes' ); ?>>
							<span class="wb-slider wb-round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Adds a To-do tab to every group.', 'wb-todo' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="bptodo-card">
			<div class="bptodo-card__head">
				<p class="bptodo-card__title"><?php esc_html_e( 'Moderator Permissions', 'wb-todo' ); ?></p>
				<p class="bptodo-card__desc"><?php esc_html_e( 'Control what group moderators can do with the group to-do list.', 'wb-todo' ); ?></p>
			</div>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Lock list for moderators', 'wb-todo' ); ?></th>
					<td>
						<label class="wb-switch">
							<input type="checkbox" name="group-todo-list-settings[mod_enable]" id="bptodo-on-moderator" value="yes" aria-label="<?php esc_attr_e( 'Lock list for moderators', 'wb-todo' ); ?>"
								<?php checked( isset( $bptodo_group_settings['mod_enable'] ) ? $bptodo_group_settings['mod_enable'] : 'no', 'yes' ); ?>>
							<span class="wb-slider wb-round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Prevents group moderators from modifying the group to-do list.', 'wb-todo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Moderators can add tasks', 'wb-todo' ); ?></th>
					<td>
						<label class="wb-switch">
							<input type="checkbox" name="group-todo-list-settings[list_enable]" id="bptodo-list-moderator" value="yes" aria-label="<?php esc_attr_e( 'Moderators can add tasks', 'wb-todo' ); ?>"
								<?php checked( isset( $bptodo_group_settings['list_enable'] ) ? $bptodo_group_settings['list_enable'] : 'no', 'yes' ); ?>>
							<span class="wb-slider wb-round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Lets group moderators add tasks to the group to-do list.', 'wb-todo' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Moderators can view report', 'wb-todo' ); ?></th>
					<td>
						<label class="wb-switch">
							<input type="checkbox" name="group-todo-list-settings[view_enable]" id="bptodo-view-moderator" value="yes" aria-label="<?php esc_attr_e( 'Moderators can view report', 'wb-todo' ); ?>"
								<?php checked( isset( $bptodo_group_settings['view_enable'] ) ? $bptodo_group_settings['view_enable'] : 'no', 'yes' ); ?>>
							<span class="wb-slider wb-round"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Lets group moderators view the group to-do list report.', 'wb-todo' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="bptodo-save-bar">
			<?php submit_button( __( 'Save Settings', 'wb-todo' ), 'primary bptodo-btn bptodo-btn-primary', 'submit', false ); ?>
		</div>
	</form>
<?php else : ?>
	<div class="bptodo-card bptodo-card--empty">
		<div class="bptodo-empty">
			<span class="bptodo-empty__icon" aria-hidden="true">
				<span class="dashicons dashicons-groups"></span>
			</span>
			<?php if ( function_exists( 'bp_is_active' ) ) : ?>
				<?php
				/*
				 * BuddyPress is running, only its Groups component is off, so the
				 * Components screen exists. Build the URL the way BuddyPress builds
				 * it itself (bp-core-admin-functions.php) so a network-activated
				 * install resolves to network admin instead of a 404 subsite URL.
				 */
				$bptodo_components_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), 'admin.php' ) );
				?>
				<p class="bptodo-empty__title"><?php esc_html_e( 'The Groups component is not active', 'wb-todo' ); ?></p>
				<p class="bptodo-empty__desc">
					<?php esc_html_e( 'Enable the BuddyPress Groups component to add to-do lists to your groups.', 'wb-todo' ); ?>
				</p>
				<a href="<?php echo esc_url( $bptodo_components_url ); ?>" class="bptodo-btn bptodo-btn-primary">
					<?php esc_html_e( 'Enable Groups component', 'wb-todo' ); ?>
				</a>
			<?php else : ?>
				<?php /* No BuddyPress at all - bp-components is not a registered screen, so never link to it. */ ?>
				<p class="bptodo-empty__title"><?php esc_html_e( 'BuddyPress or BuddyBoss is not active.', 'wb-todo' ); ?></p>
				<p class="bptodo-empty__desc">
					<?php esc_html_e( 'This plugin needs BuddyPress or BuddyBoss for member and group to-do lists. Activate one to use all features.', 'wb-todo' ); ?>
				</p>
				<?php if ( current_user_can( 'activate_plugins' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="bptodo-btn bptodo-btn-primary">
						<?php esc_html_e( 'Go to Plugins', 'wb-todo' ); ?>
					</a>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>
