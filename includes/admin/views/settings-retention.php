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
			<p class="bptodo-card__title"><?php esc_html_e( 'Auto-Cleanup', 'wb-todo' ); ?></p>
			<p class="bptodo-card__desc"><?php esc_html_e( 'Automatically remove old tasks to keep your database lean. Cleanup runs once a day.', 'wb-todo' ); ?></p>
		</div>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable auto-cleanup', 'wb-todo' ); ?></th>
				<td>
					<label class="wb-switch">
						<input type="checkbox" name="data-retention-settings[enable_retention]" id="bptodo-enable-retention" value="yes"
							<?php checked( $bptodo_retention_settings['enable_retention'], 'yes' ); ?>>
						<span class="wb-slider wb-round"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Turn on scheduled cleanup of old tasks based on the rules below.', 'wb-todo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Task scope', 'wb-todo' ); ?></th>
				<td>
					<label class="bptodo-checkline">
						<input type="checkbox" name="data-retention-settings[enable_group_retention]" value="yes"
							<?php checked( $bptodo_retention_settings['enable_group_retention'], 'yes' ); ?>>
						<?php esc_html_e( 'Group tasks', 'wb-todo' ); ?>
					</label>
					<label class="bptodo-checkline">
						<input type="checkbox" name="data-retention-settings[enable_personal_retention]" value="yes"
							<?php checked( $bptodo_retention_settings['enable_personal_retention'], 'yes' ); ?>>
						<?php esc_html_e( 'Personal tasks', 'wb-todo' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Which kinds of tasks the cleanup applies to.', 'wb-todo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bptodo-cleanup-type"><?php esc_html_e( 'Tasks to clean up', 'wb-todo' ); ?></label>
				</th>
				<td>
					<select name="data-retention-settings[cleanup_type]" id="bptodo-cleanup-type">
						<option value="completed" <?php selected( $bptodo_retention_settings['cleanup_type'], 'completed' ); ?>><?php esc_html_e( 'Only completed tasks', 'wb-todo' ); ?></option>
						<option value="all" <?php selected( $bptodo_retention_settings['cleanup_type'], 'all' ); ?>><?php esc_html_e( 'All tasks (completed and pending)', 'wb-todo' ); ?></option>
						<option value="pending" <?php selected( $bptodo_retention_settings['cleanup_type'], 'pending' ); ?>><?php esc_html_e( 'Only pending tasks', 'wb-todo' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bptodo-retention-days"><?php esc_html_e( 'Retention period', 'wb-todo' ); ?></label>
				</th>
				<td>
					<input type="number" name="data-retention-settings[retention_days]" id="bptodo-retention-days"
						value="<?php echo esc_attr( $bptodo_retention_settings['retention_days'] ); ?>"
						min="7" max="3650" step="1" class="bptodo-input-narrow">
					<span class="bptodo-input-suffix"><?php esc_html_e( 'days', 'wb-todo' ); ?></span>
					<p class="description"><?php esc_html_e( 'Tasks older than this are cleaned up. Minimum 7 days.', 'wb-todo' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<div class="bptodo-card">
		<div class="bptodo-card__head">
			<p class="bptodo-card__title"><?php esc_html_e( 'Cleanup Behavior', 'wb-todo' ); ?></p>
		</div>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Permanent delete', 'wb-todo' ); ?></th>
				<td>
					<label class="wb-switch">
						<input type="checkbox" name="data-retention-settings[permanent_delete]" id="bptodo-permanent-delete" value="yes"
							<?php checked( $bptodo_retention_settings['permanent_delete'], 'yes' ); ?>>
						<span class="wb-slider wb-round"></span>
					</label>
					<p class="description"><?php esc_html_e( 'On: tasks are deleted permanently. Off: tasks go to trash (recoverable for 30 days).', 'wb-todo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Email notifications', 'wb-todo' ); ?></th>
				<td>
					<label class="wb-switch">
						<input type="checkbox" name="data-retention-settings[email_notification]" id="bptodo-email-notification" value="yes"
							<?php checked( $bptodo_retention_settings['email_notification'], 'yes' ); ?>>
						<span class="wb-slider wb-round"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Send a summary email after each cleanup run.', 'wb-todo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bptodo-notification-email"><?php esc_html_e( 'Notification email(s)', 'wb-todo' ); ?></label>
				</th>
				<td>
					<input type="text" name="data-retention-settings[notification_email]" id="bptodo-notification-email"
						value="<?php echo esc_attr( $bptodo_retention_settings['notification_email'] ); ?>"
						placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
						class="regular-text bptodo-input-wide">
					<p class="description"><?php esc_html_e( 'Where cleanup summaries go. Separate multiple addresses with commas.', 'wb-todo' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<div class="bptodo-notice bptodo-notice--info">
		<span class="dashicons dashicons-info" aria-hidden="true"></span>
		<div>
			<strong><?php esc_html_e( 'Cleanup schedule', 'wb-todo' ); ?></strong>
			<?php
			if ( $bptodo_next_run ) {
				echo ' ';
				echo esc_html(
					sprintf(
						/* translators: %s: next scheduled cleanup date and time */
						__( 'The cleanup runs once a day. Next run: %s.', 'wb-todo' ),
						date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $bptodo_next_run )
					)
				);
			} else {
				echo ' ';
				esc_html_e( 'The cleanup runs once a day. It is not scheduled yet — it schedules automatically when auto-cleanup is enabled.', 'wb-todo' );
			}
			?>
		</div>
	</div>

	<div class="bptodo-save-bar">
		<?php submit_button( __( 'Save Settings', 'wb-todo' ), 'primary bptodo-btn bptodo-btn-primary', 'submit', false ); ?>
	</div>
</form>
