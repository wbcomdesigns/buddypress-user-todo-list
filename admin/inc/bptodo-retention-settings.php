<?php
/**
 * Handles the Data Retention Settings page for the BP User Todo List plugin.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$retention_settings = get_option( 'data-retention-settings', array() );

// Set defaults
$defaults = array(
	'enable_retention'          => 'no',
	'enable_group_retention'    => 'yes',
	'enable_personal_retention' => 'yes',
	'cleanup_type'              => 'completed',
	'retention_days'            => 90,
	'permanent_delete'          => 'no',
	'email_notification'        => 'yes',
	'notification_email'        => get_option( 'admin_email' ),
);

$retention_settings = wp_parse_args( $retention_settings, $defaults );
?>

<div class="wbcom-tab-content">
	<div class="wbcom-welcome-main-wrapper">
		<div class="wbcom-admin-title-section">
			<h3><?php esc_html_e( 'Data Retention Policy', 'wb-todo' ); ?></h3>
			<p><?php esc_html_e( 'Configure automatic cleanup of old tasks to manage database size and maintain performance.', 'wb-todo' ); ?></p>
		</div>
		<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
			<form method="post" action="options.php" id="bptodo-retention-settings-form">
				<div class="form-table">
					<?php
					settings_fields( 'data-retention-settings' );
					do_settings_sections( 'data-retention-settings' );
					?>

					<!-- Enable Data Retention -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-enable-retention">
								<?php esc_html_e( 'Enable Auto-Cleanup', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enable automatic cleanup of old tasks based on the settings below.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="data-retention-settings[enable_retention]" id="bptodo-enable-retention" value="yes"
								<?php checked( $retention_settings['enable_retention'], 'yes' ); ?>>
								<span class="wb-slider wb-round"></span>
							</label>
						</div>
					</div>

					<!-- Task Scope -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label><?php esc_html_e( 'Task Scope', 'wb-todo' ); ?></label>
							<p class="description"><?php esc_html_e( 'Select which types of tasks to include in auto-cleanup.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<div style="display: flex; flex-direction: column; gap: 10px;">
								<label>
									<input type="checkbox" name="data-retention-settings[enable_group_retention]" value="yes"
									<?php checked( $retention_settings['enable_group_retention'], 'yes' ); ?>>
									<?php esc_html_e( 'Group Tasks', 'wb-todo' ); ?>
								</label>
								<label>
									<input type="checkbox" name="data-retention-settings[enable_personal_retention]" value="yes"
									<?php checked( $retention_settings['enable_personal_retention'], 'yes' ); ?>>
									<?php esc_html_e( 'Personal Tasks', 'wb-todo' ); ?>
								</label>
							</div>
						</div>
					</div>

					<!-- Cleanup Type -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-cleanup-type">
								<?php esc_html_e( 'Tasks to Clean Up', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Choose which tasks to automatically clean up.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<select name="data-retention-settings[cleanup_type]" id="bptodo-cleanup-type">
								<option value="completed" <?php selected( $retention_settings['cleanup_type'], 'completed' ); ?>>
									<?php esc_html_e( 'Only Completed Tasks', 'wb-todo' ); ?>
								</option>
								<option value="all" <?php selected( $retention_settings['cleanup_type'], 'all' ); ?>>
									<?php esc_html_e( 'All Tasks (Completed & Pending)', 'wb-todo' ); ?>
								</option>
								<option value="pending" <?php selected( $retention_settings['cleanup_type'], 'pending' ); ?>>
									<?php esc_html_e( 'Only Pending Tasks', 'wb-todo' ); ?>
								</option>
							</select>
						</div>
					</div>

					<!-- Retention Days -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-retention-days">
								<?php esc_html_e( 'Retention Period (Days)', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Tasks older than this many days will be cleaned up. Minimum: 7 days.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<input type="number" name="data-retention-settings[retention_days]" id="bptodo-retention-days"
								value="<?php echo esc_attr( $retention_settings['retention_days'] ); ?>"
								min="7" max="3650" step="1" style="width: 100px;">
							<span style="margin-left: 10px;"><?php esc_html_e( 'days', 'wb-todo' ); ?></span>
						</div>
					</div>

					<!-- Delete Method -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-permanent-delete">
								<?php esc_html_e( 'Permanent Delete', 'wb-todo' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'If enabled, tasks will be permanently deleted. If disabled, tasks will be moved to trash (recoverable for 30 days).', 'wb-todo' ); ?>
							</p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="data-retention-settings[permanent_delete]" id="bptodo-permanent-delete" value="yes"
								<?php checked( $retention_settings['permanent_delete'], 'yes' ); ?>>
								<span class="wb-slider wb-round"></span>
							</label>
						</div>
					</div>

					<!-- Email Notification -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-email-notification">
								<?php esc_html_e( 'Email Notifications', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Send email notification after each cleanup with a summary of deleted tasks.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<label class="wb-switch">
								<input type="checkbox" name="data-retention-settings[email_notification]" id="bptodo-email-notification" value="yes"
								<?php checked( $retention_settings['email_notification'], 'yes' ); ?>>
								<span class="wb-slider wb-round"></span>
							</label>
						</div>
					</div>

					<!-- Notification Email -->
					<div class="wbcom-settings-section-wrap">
						<div class="wbcom-settings-section-options-heading">
							<label for="bptodo-notification-email">
								<?php esc_html_e( 'Notification Email(s)', 'wb-todo' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Email address(es) to receive cleanup notifications. Separate multiple emails with commas.', 'wb-todo' ); ?></p>
						</div>
						<div class="wbcom-settings-section-options">
							<input type="text" name="data-retention-settings[notification_email]" id="bptodo-notification-email"
								value="<?php echo esc_attr( $retention_settings['notification_email'] ); ?>"
								placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
								style="width: 100%; max-width: 500px;">
						</div>
					</div>

					<!-- Cleanup Schedule Info -->
					<div class="wbcom-settings-section-wrap" style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 15px;">
						<div style="display: flex; align-items: start; gap: 10px;">
							<span class="dashicons dashicons-info" style="color: #0073aa; margin-top: 2px;"></span>
							<div>
								<strong><?php esc_html_e( 'Cleanup Schedule', 'wb-todo' ); ?></strong>
								<p style="margin: 5px 0 0 0;"><?php esc_html_e( 'The cleanup process runs automatically once daily. Next scheduled run:', 'wb-todo' ); ?>
									<strong>
										<?php
										$next_run = wp_next_scheduled( 'bptodo_daily_cleanup' );
										echo $next_run ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_run ) ) : esc_html__( 'Not scheduled', 'wb-todo' );
										?>
									</strong>
								</p>
							</div>
						</div>
					</div>

				</div>
				<?php submit_button(); ?>
			</form>
		</div>
	</div>
</div>
