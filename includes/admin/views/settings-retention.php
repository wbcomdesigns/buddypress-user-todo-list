<?php
/**
 * Settings tab: Data Retention.
 *
 * Preserves the Settings API save path EXACTLY:
 *   - form posts to options.php
 *   - settings_fields( 'data-retention-settings' )
 *   - option key `data-retention-settings`
 *   - field names data-retention-settings[enable_retention|enable_group_retention|
 *     enable_personal_retention|cleanup_type|retention_days|permanent_delete|
 *     email_notification|notification_email]
 * Sanitized by Bptodo_Admin::bptodo_sanitize_retention_settings().
 *
 * @package bp-user-todo-list
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

$bptodo_retention_settings = get_option( 'data-retention-settings', array() );

$bptodo_defaults = array(
	'enable_retention'          => 'no',
	'enable_group_retention'    => 'yes',
	'enable_personal_retention' => 'yes',
	'cleanup_type'              => 'completed',
	'retention_days'            => 90,
	'permanent_delete'          => 'no',
	'email_notification'        => 'yes',
	'notification_email'        => get_option( 'admin_email' ),
);

$bptodo_retention_settings = wp_parse_args( $bptodo_retention_settings, $bptodo_defaults );

$bptodo_next_run = wp_next_scheduled( 'bptodo_daily_cleanup' );
?>
<form method="post" action="options.php" id="bptodo-retention-settings-form">
	<?php settings_fields( 'data-retention-settings' ); ?>

	<div class="bptodo-card">
		<div class="bptodo-card__head">
			<p class="bptodo-card__title"><?php esc_html_e( 'Auto-Cleanup', 'bp-user-to-do-list' ); ?></p>
			<p class="bptodo-card__desc"><?php esc_html_e( 'Automatically remove old tasks to keep your database lean. Cleanup runs once a day.', 'bp-user-to-do-list' ); ?></p>
		</div>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable auto-cleanup', 'bp-user-to-do-list' ); ?></th>
				<td>
					<label class="wb-switch">
						<input type="checkbox" name="data-retention-settings[enable_retention]" id="bptodo-enable-retention" value="yes" aria-label="<?php esc_attr_e( 'Enable auto-cleanup', 'bp-user-to-do-list' ); ?>"
							<?php checked( $bptodo_retention_settings['enable_retention'], 'yes' ); ?>>
						<span class="wb-slider wb-round"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Turn on scheduled cleanup of old tasks based on the rules below.', 'bp-user-to-do-list' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Task scope', 'bp-user-to-do-list' ); ?></th>
				<td>
					<label class="bptodo-checkline">
						<input type="checkbox" name="data-retention-settings[enable_group_retention]" value="yes"
							<?php checked( $bptodo_retention_settings['enable_group_retention'], 'yes' ); ?>>
						<?php esc_html_e( 'Group tasks', 'bp-user-to-do-list' ); ?>
					</label>
					<label class="bptodo-checkline">
						<input type="checkbox" name="data-retention-settings[enable_personal_retention]" value="yes"
							<?php checked( $bptodo_retention_settings['enable_personal_retention'], 'yes' ); ?>>
						<?php esc_html_e( 'Personal tasks', 'bp-user-to-do-list' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Which kinds of tasks the cleanup applies to.', 'bp-user-to-do-list' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bptodo-cleanup-type"><?php esc_html_e( 'Tasks to clean up', 'bp-user-to-do-list' ); ?></label>
				</th>
				<td>
					<select name="data-retention-settings[cleanup_type]" id="bptodo-cleanup-type">
						<option value="completed" <?php selected( $bptodo_retention_settings['cleanup_type'], 'completed' ); ?>><?php esc_html_e( 'Only completed tasks', 'bp-user-to-do-list' ); ?></option>
						<option value="all" <?php selected( $bptodo_retention_settings['cleanup_type'], 'all' ); ?>><?php esc_html_e( 'All tasks (completed and pending)', 'bp-user-to-do-list' ); ?></option>
						<option value="pending" <?php selected( $bptodo_retention_settings['cleanup_type'], 'pending' ); ?>><?php esc_html_e( 'Only pending tasks', 'bp-user-to-do-list' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bptodo-retention-days"><?php esc_html_e( 'Retention period', 'bp-user-to-do-list' ); ?></label>
				</th>
				<td>
					<input type="number" name="data-retention-settings[retention_days]" id="bptodo-retention-days"
						value="<?php echo esc_attr( $bptodo_retention_settings['retention_days'] ); ?>"
						min="7" max="3650" step="1" class="bptodo-input-narrow">
					<span class="bptodo-input-suffix"><?php esc_html_e( 'days', 'bp-user-to-do-list' ); ?></span>
					<p class="description"><?php esc_html_e( 'Tasks older than this are cleaned up. Minimum 7 days.', 'bp-user-to-do-list' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<div class="bptodo-card">
		<div class="bptodo-card__head">
			<p class="bptodo-card__title"><?php esc_html_e( 'Cleanup Behavior', 'bp-user-to-do-list' ); ?></p>
		</div>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Permanent delete', 'bp-user-to-do-list' ); ?></th>
				<td>
					<label class="wb-switch">
						<input type="checkbox" name="data-retention-settings[permanent_delete]" id="bptodo-permanent-delete" value="yes" aria-label="<?php esc_attr_e( 'Permanent delete', 'bp-user-to-do-list' ); ?>"
							<?php checked( $bptodo_retention_settings['permanent_delete'], 'yes' ); ?>>
						<span class="wb-slider wb-round"></span>
					</label>
					<p class="description"><?php esc_html_e( 'On: tasks are deleted permanently. Off: tasks go to trash (recoverable for 30 days).', 'bp-user-to-do-list' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Email notifications', 'bp-user-to-do-list' ); ?></th>
				<td>
					<label class="wb-switch">
						<input type="checkbox" name="data-retention-settings[email_notification]" id="bptodo-email-notification" value="yes" aria-label="<?php esc_attr_e( 'Email notifications', 'bp-user-to-do-list' ); ?>"
							<?php checked( $bptodo_retention_settings['email_notification'], 'yes' ); ?>>
						<span class="wb-slider wb-round"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Send a summary email after each cleanup run.', 'bp-user-to-do-list' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bptodo-notification-email"><?php esc_html_e( 'Notification email(s)', 'bp-user-to-do-list' ); ?></label>
				</th>
				<td>
					<input type="text" name="data-retention-settings[notification_email]" id="bptodo-notification-email"
						value="<?php echo esc_attr( $bptodo_retention_settings['notification_email'] ); ?>"
						placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
						class="regular-text bptodo-input-wide">
					<p class="description"><?php esc_html_e( 'Where cleanup summaries go. Separate multiple addresses with commas.', 'bp-user-to-do-list' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<div class="bptodo-notice bptodo-notice--info">
		<span class="dashicons dashicons-info" aria-hidden="true"></span>
		<div>
			<strong><?php esc_html_e( 'Cleanup schedule', 'bp-user-to-do-list' ); ?></strong>
			<?php
			if ( $bptodo_next_run ) {
				echo ' ';
				echo esc_html(
					sprintf(
						/* translators: %s: next scheduled cleanup date and time */
						__( 'The cleanup runs once a day. Next run: %s.', 'bp-user-to-do-list' ),
						bptodo_date_label( $bptodo_next_run )
					)
				);
			} else {
				echo ' ';
				esc_html_e( 'The cleanup runs once a day. It is not scheduled yet — it schedules automatically when auto-cleanup is enabled.', 'bp-user-to-do-list' );
			}
			?>
		</div>
	</div>

	<div class="bptodo-save-bar">
		<?php submit_button( __( 'Save Settings', 'bp-user-to-do-list' ), 'primary bptodo-btn bptodo-btn-primary', 'submit', false ); ?>
	</div>
</form>
