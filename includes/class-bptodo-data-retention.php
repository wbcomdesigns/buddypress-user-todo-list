<?php
/**
 * Data Retention and Cleanup functionality
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle data retention and automatic cleanup of old tasks
 */
class Bptodo_Data_Retention {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'schedule_cleanup' ) );
		add_action( 'bptodo_daily_cleanup', array( $this, 'run_cleanup' ) );

		// Add action to reschedule when settings change (note: WordPress uses underscores in option names)
		add_action( 'update_option_data-retention-settings', array( $this, 'reschedule_cleanup' ), 10, 2 );

		// Also add hook for deactivation
		register_deactivation_hook( BPTODO_PLUGIN_PATH . 'bp-user-todo-list.php', array( $this, 'clear_scheduled_cleanup' ) );
	}

	/**
	 * Schedule the daily cleanup cron job
	 * Only schedules if retention is enabled in settings
	 */
	public function schedule_cleanup() {
		$settings = get_option( 'data-retention-settings', array() );

		// Check if retention is enabled
		$is_enabled = isset( $settings['enable_retention'] ) && $settings['enable_retention'] === 'yes';

		$is_scheduled = wp_next_scheduled( 'bptodo_daily_cleanup' );

		if ( $is_enabled && ! $is_scheduled ) {
			// Schedule cron if enabled but not scheduled
			wp_schedule_event( time(), 'daily', 'bptodo_daily_cleanup' );
		} elseif ( ! $is_enabled && $is_scheduled ) {
			// Unschedule cron if disabled but still scheduled
			wp_unschedule_event( $is_scheduled, 'bptodo_daily_cleanup' );
		}
	}

	/**
	 * Reschedule cleanup when settings change
	 *
	 * @param array $old_value Old settings value.
	 * @param array $new_value New settings value.
	 */
	public function reschedule_cleanup( $old_value, $new_value ) {
		// Clear existing schedule
		$timestamp = wp_next_scheduled( 'bptodo_daily_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'bptodo_daily_cleanup' );
		}

		// Reschedule if enabled
		if ( isset( $new_value['enable_retention'] ) && $new_value['enable_retention'] === 'yes' ) {
			wp_schedule_event( time(), 'daily', 'bptodo_daily_cleanup' );
		}
	}

	/**
	 * Run the cleanup process
	 */
	public function run_cleanup() {
		$settings = get_option( 'data-retention-settings', array() );

		// Check if cleanup is enabled
		if ( ! isset( $settings['enable_retention'] ) || $settings['enable_retention'] !== 'yes' ) {
			return;
		}

		// Get settings with defaults
		$cleanup_type              = isset( $settings['cleanup_type'] ) ? $settings['cleanup_type'] : 'completed';
		$retention_days            = isset( $settings['retention_days'] ) ? absint( $settings['retention_days'] ) : 90;
		$enable_group_retention    = isset( $settings['enable_group_retention'] ) && $settings['enable_group_retention'] === 'yes';
		$enable_personal_retention = isset( $settings['enable_personal_retention'] ) && $settings['enable_personal_retention'] === 'yes';
		$permanent_delete          = isset( $settings['permanent_delete'] ) && $settings['permanent_delete'] === 'yes';

		// Ensure minimum 7 days
		if ( $retention_days < 7 ) {
			$retention_days = 7;
		}

		// Calculate cutoff date
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		// Initialize counters
		$deleted_tasks = array(
			'group'    => 0,
			'personal' => 0,
			'total'    => 0,
		);

		// Build query args
		$base_args = array(
			'post_type'      => 'bp-todo',
			'post_status'    => 'any', // Include all post statuses
			'posts_per_page' => -1,
			'date_query'     => array(
				array(
					'before'    => $cutoff_date,
					'inclusive' => true,
					'column'    => 'post_date', // Explicitly set column
				),
			),
		);

		// Add status filter based on cleanup type
		if ( $cleanup_type === 'completed' ) {
			$base_args['meta_query'] = array(
				array(
					'key'     => 'todo_status',
					'value'   => 'complete',
					'compare' => '=',
				),
			);
		} elseif ( $cleanup_type === 'pending' ) {
			$base_args['meta_query'] = array(
				array(
					'key'     => 'todo_status',
					'value'   => 'incomplete',
					'compare' => '=',
				),
			);
		}
		// If 'all', no status filter needed

		// Clean up group tasks
		if ( $enable_group_retention ) {
			$group_args = $base_args;

			// Properly structure meta_query
			if ( isset( $group_args['meta_query'] ) ) {
				// If there's already a status filter, add relation and group condition
				$group_args['meta_query']['relation'] = 'AND';
				$group_args['meta_query'][] = array(
					'key'     => 'todo_group_id',
					'value'   => 0,
					'compare' => '>',
				);
			} else {
				// No status filter, just add group condition
				$group_args['meta_query'] = array(
					array(
						'key'     => 'todo_group_id',
						'value'   => 0,
						'compare' => '>',
					),
				);
			}

			$deleted_tasks['group'] = $this->delete_tasks( $group_args, $permanent_delete );
		}

		// Clean up personal tasks
		if ( $enable_personal_retention ) {
			$personal_args = $base_args;

			// Build personal task condition (group_id = 0 OR doesn't exist)
			$personal_condition = array(
				'relation' => 'OR',
				array(
					'key'     => 'todo_group_id',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'todo_group_id',
					'value'   => 0,
					'compare' => '=',
				),
			);

			// Properly structure meta_query
			if ( isset( $personal_args['meta_query'] ) ) {
				// If there's already a status filter, combine with personal condition
				$personal_args['meta_query'] = array(
					'relation' => 'AND',
					$personal_args['meta_query'][0], // Status condition
					$personal_condition, // Personal task condition
				);
			} else {
				// No status filter, just use personal condition
				$personal_args['meta_query'] = $personal_condition;
			}

			$deleted_tasks['personal'] = $this->delete_tasks( $personal_args, $permanent_delete );
		}

		// Calculate total
		$deleted_tasks['total'] = $deleted_tasks['group'] + $deleted_tasks['personal'];

		// Send email notification if enabled and tasks were deleted
		if ( $deleted_tasks['total'] > 0 && isset( $settings['email_notification'] ) && $settings['email_notification'] === 'yes' ) {
			$this->send_cleanup_notification( $deleted_tasks, $settings );
		}

		// Log the cleanup
		$this->log_cleanup( $deleted_tasks );
	}

	/**
	 * Delete tasks based on query args
	 *
	 * @param array $args Query arguments.
	 * @param bool  $permanent Whether to permanently delete or move to trash.
	 * @return int Number of tasks deleted.
	 */
	private function delete_tasks( $args, $permanent = false ) {
		$query = new WP_Query( $args );
		$count = 0;

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				// Delete associated todos first
				$this->delete_associated_todos( $post_id, $permanent );

				// Delete the main todo
				if ( $permanent ) {
					wp_delete_post( $post_id, true );
				} else {
					wp_trash_post( $post_id );
				}

				$count++;
			}
		}

		wp_reset_postdata();

		return $count;
	}

	/**
	 * Delete associated todos for a primary todo
	 *
	 * @param int  $primary_todo_id Primary todo ID.
	 * @param bool $permanent Whether to permanently delete.
	 */
	private function delete_associated_todos( $primary_todo_id, $permanent = false ) {
		$associated_args = array(
			'post_type'      => 'bp-todo',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => 'todo_primary_id',
					'value' => $primary_todo_id,
				),
			),
		);

		$associated_query = new WP_Query( $associated_args );

		if ( $associated_query->have_posts() ) {
			while ( $associated_query->have_posts() ) {
				$associated_query->the_post();
				$associated_id = get_the_ID();

				if ( $permanent ) {
					wp_delete_post( $associated_id, true );
				} else {
					wp_trash_post( $associated_id );
				}
			}
		}

		wp_reset_postdata();
	}

	/**
	 * Send email notification about cleanup
	 *
	 * @param array $deleted_tasks Deleted task counts.
	 * @param array $settings Retention settings.
	 */
	private function send_cleanup_notification( $deleted_tasks, $settings ) {
		$to = isset( $settings['notification_email'] ) ? $settings['notification_email'] : get_option( 'admin_email' );

		// Handle multiple emails
		$to = array_map( 'trim', explode( ',', $to ) );

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Todo List Cleanup Summary', 'wb-todo' ),
			get_bloginfo( 'name' )
		);

		$cleanup_type = isset( $settings['cleanup_type'] ) ? $settings['cleanup_type'] : 'completed';
		$retention_days = isset( $settings['retention_days'] ) ? absint( $settings['retention_days'] ) : 90;
		$permanent_delete = isset( $settings['permanent_delete'] ) && $settings['permanent_delete'] === 'yes';

		$cleanup_type_label = array(
			'completed' => __( 'Completed Tasks', 'wb-todo' ),
			'pending'   => __( 'Pending Tasks', 'wb-todo' ),
			'all'       => __( 'All Tasks', 'wb-todo' ),
		);

		$message = sprintf(
			/* translators: %1$s: site name, %2$s: date and time */
			__( 'Automatic cleanup ran on %1$s at %2$s', 'wb-todo' ),
			get_bloginfo( 'name' ),
			current_time( 'mysql' )
		) . "\n\n";

		$message .= __( 'Cleanup Summary:', 'wb-todo' ) . "\n";
		$message .= str_repeat( '-', 50 ) . "\n";
		/* translators: %s: cleanup type (all, group, personal) */
		$message .= sprintf( __( 'Cleanup Type: %s', 'wb-todo' ), $cleanup_type_label[ $cleanup_type ] ) . "\n";
		/* translators: %d: number of days */
		$message .= sprintf( __( 'Retention Period: %d days', 'wb-todo' ), $retention_days ) . "\n";
		/* translators: %s: delete method (Permanent or Moved to Trash) */
		$message .= sprintf( __( 'Delete Method: %s', 'wb-todo' ), $permanent_delete ? __( 'Permanent', 'wb-todo' ) : __( 'Moved to Trash', 'wb-todo' ) ) . "\n\n";

		$message .= __( 'Tasks Deleted:', 'wb-todo' ) . "\n";
		/* translators: %d: number of group tasks deleted */
		$message .= sprintf( __( '- Group Tasks: %d', 'wb-todo' ), $deleted_tasks['group'] ) . "\n";
		/* translators: %d: number of personal tasks deleted */
		$message .= sprintf( __( '- Personal Tasks: %d', 'wb-todo' ), $deleted_tasks['personal'] ) . "\n";
		/* translators: %d: total number of tasks deleted */
		$message .= sprintf( __( '- Total: %d', 'wb-todo' ), $deleted_tasks['total'] ) . "\n\n";

		if ( ! $permanent_delete ) {
			$message .= __( 'Note: Tasks have been moved to trash and can be recovered within 30 days.', 'wb-todo' ) . "\n";
		}

		$message .= "\n" . sprintf(
			/* translators: %s: settings page URL */
			__( 'Manage retention settings: %s', 'wb-todo' ),
			admin_url( 'admin.php?page=user-todo-list-settings&tab=data-retention-settings' )
		);

		wp_mail( $to, $subject, $message );
	}

	/**
	 * Log cleanup activity
	 *
	 * @param array $deleted_tasks Deleted task counts.
	 */
	private function log_cleanup( $deleted_tasks ) {
		$log_entry = array(
			'timestamp'     => current_time( 'mysql' ),
			'group_tasks'   => $deleted_tasks['group'],
			'personal_tasks' => $deleted_tasks['personal'],
			'total_tasks'   => $deleted_tasks['total'],
		);

		// Get existing log
		$log = get_option( 'bptodo_cleanup_log', array() );

		// Add new entry
		array_unshift( $log, $log_entry );

		// Keep only last 30 entries
		$log = array_slice( $log, 0, 30 );

		// Save log
		update_option( 'bptodo_cleanup_log', $log );
	}

	/**
	 * Clear scheduled cleanup on plugin deactivation
	 */
	public function clear_scheduled_cleanup() {
		$timestamp = wp_next_scheduled( 'bptodo_daily_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'bptodo_daily_cleanup' );
		}
	}
}

// Initialize the class
new Bptodo_Data_Retention();
