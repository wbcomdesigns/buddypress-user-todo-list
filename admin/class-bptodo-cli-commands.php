<?php
/**
 * WP-CLI commands for BuddyPress User Todo List
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate demo data for BuddyPress User Todo List plugin.
 */
class BPTODO_CLI_Commands {

	/**
	 * Generate demo member todos for testing.
	 *
	 * ## OPTIONS
	 *
	 * [--users=<ids>]
	 * : Comma-separated user IDs. If not provided, uses all members.
	 *
	 * [--count=<number>]
	 * : Number of todos per user. Default: 5
	 *
	 * [--clean]
	 * : Clean existing demo data before generating new.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bptodo generate member-todos
	 *     wp bptodo generate member-todos --users=2,24,21 --count=10
	 *     wp bptodo generate member-todos --clean --count=3
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function member_todos( $args, $assoc_args ) {
		$user_ids = ! empty( $assoc_args['users'] ) ? array_map( 'intval', explode( ',', $assoc_args['users'] ) ) : array();
		$count    = ! empty( $assoc_args['count'] ) ? absint( $assoc_args['count'] ) : 5;
		$clean    = isset( $assoc_args['clean'] );

		if ( $clean ) {
			WP_CLI::log( 'Cleaning existing demo data...' );
			$this->cleanup_demo_data();
		}

		// Get users
		if ( empty( $user_ids ) ) {
			$users    = get_users( array( 'fields' => 'ID' ) );
			$user_ids = array_slice( $users, 0, 3 ); // Limit to 3 users if not specified
		}

		if ( empty( $user_ids ) ) {
			WP_CLI::error( 'No users found.' );
			return;
		}

		$task_templates = $this->get_task_templates();
		$total_created  = 0;

		foreach ( $user_ids as $user_id ) {
			$user = get_user_by( 'ID', $user_id );
			if ( ! $user ) {
				WP_CLI::warning( "User ID {$user_id} not found. Skipping." );
				continue;
			}

			WP_CLI::log( "Generating {$count} todos for user: {$user->user_login} (ID: {$user_id})" );

			for ( $i = 0; $i < $count; $i++ ) {
				$template = $task_templates[ array_rand( $task_templates ) ];
				$todo_id  = $this->create_todo( $user_id, $template );

				if ( $todo_id ) {
					update_post_meta( $todo_id, '_bptodo_demo_data', '1' );
					$total_created++;
				}
			}
		}

		WP_CLI::success( "Created {$total_created} demo todos for " . count( $user_ids ) . ' users.' );
	}

	/**
	 * Generate demo group todos for testing.
	 *
	 * ## OPTIONS
	 *
	 * [--groups=<ids>]
	 * : Comma-separated group IDs. If not provided, uses all groups.
	 *
	 * [--count=<number>]
	 * : Number of todos per group. Default: 5
	 *
	 * [--clean]
	 * : Clean existing demo data before generating new.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bptodo generate group-todos
	 *     wp bptodo generate group-todos --groups=1,2,3 --count=10
	 *     wp bptodo generate group-todos --clean --count=3
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function group_todos( $args, $assoc_args ) {
		if ( ! function_exists( 'groups_get_groups' ) ) {
			WP_CLI::error( 'BuddyPress Groups component is not active.' );
			return;
		}

		$group_ids = ! empty( $assoc_args['groups'] ) ? array_map( 'intval', explode( ',', $assoc_args['groups'] ) ) : array();
		$count     = ! empty( $assoc_args['count'] ) ? absint( $assoc_args['count'] ) : 5;
		$clean     = isset( $assoc_args['clean'] );

		if ( $clean ) {
			WP_CLI::log( 'Cleaning existing demo data...' );
			$this->cleanup_demo_data();
		}

		// Get groups
		if ( empty( $group_ids ) ) {
			$groups_data = groups_get_groups(
				array(
					'per_page' => 3,
				)
			);
			if ( ! empty( $groups_data['groups'] ) ) {
				$group_ids = wp_list_pluck( $groups_data['groups'], 'id' );
			}
		}

		if ( empty( $group_ids ) ) {
			WP_CLI::error( 'No groups found.' );
			return;
		}

		$task_templates = $this->get_task_templates();
		$total_created  = 0;

		foreach ( $group_ids as $group_id ) {
			$group = groups_get_group( $group_id );
			if ( ! $group->id ) {
				WP_CLI::warning( "Group ID {$group_id} not found. Skipping." );
				continue;
			}

			// Get group creator or first admin
			$admins  = groups_get_group_admins( $group_id );
			$user_id = ! empty( $admins ) ? $admins[0]->user_id : get_current_user_id();

			WP_CLI::log( "Generating {$count} todos for group: {$group->name} (ID: {$group_id})" );

			for ( $i = 0; $i < $count; $i++ ) {
				$template            = $task_templates[ array_rand( $task_templates ) ];
				$template['groupid'] = $group_id;
				$todo_id             = $this->create_todo( $user_id, $template );

				if ( $todo_id ) {
					update_post_meta( $todo_id, '_bptodo_demo_data', '1' );
					update_post_meta( $todo_id, 'todo_group_id', $group_id );
					$total_created++;
				}
			}
		}

		WP_CLI::success( "Created {$total_created} demo todos for " . count( $group_ids ) . ' groups.' );
	}

	/**
	 * Clean up demo data.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Delete all bp-todo posts, not just demo data.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bptodo cleanup
	 *     wp bptodo cleanup --all
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function cleanup( $args, $assoc_args ) {
		$delete_all = isset( $assoc_args['all'] );

		// Use batch processing to avoid memory issues with large datasets
		$batch_size = 100;
		$paged = 1;
		$deleted = 0;
		$total_found = 0;

		// First, count total items
		$count_args = array(
			'post_type'      => 'bp-todo',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);

		if ( ! $delete_all ) {
			$count_args['meta_query'] = array(
				array(
					'key'   => '_bptodo_demo_data',
					'value' => '1',
				),
			);
		}

		$count_query = new WP_Query( $count_args );
		$total_found = $count_query->found_posts;

		if ( $total_found === 0 ) {
			WP_CLI::log( 'No demo data found to clean up.' );
			return;
		}

		$confirm_message = $delete_all ?
			"Are you sure you want to delete ALL {$total_found} todos?" :
			"Delete {$total_found} demo todos?";

		WP_CLI::confirm( $confirm_message );

		// Process in batches
		do {
			$query_args = array(
				'post_type'      => 'bp-todo',
				'posts_per_page' => $batch_size,
				'paged'          => $paged,
				'fields'         => 'ids',
			);

			if ( ! $delete_all ) {
				$query_args['meta_query'] = array(
					array(
						'key'   => '_bptodo_demo_data',
						'value' => '1',
					),
				);
			}

			$query = new WP_Query( $query_args );
			$todo_ids = $query->posts;

			foreach ( $todo_ids as $todo_id ) {
				if ( wp_delete_post( $todo_id, true ) ) {
					$deleted++;
					if ( $deleted % 50 === 0 ) {
						WP_CLI::log( "Deleted {$deleted}/{$total_found} todos..." );
					}
				}
			}

			$paged++;
		} while ( $paged <= $query->max_num_pages );

		WP_CLI::success( "Deleted {$deleted} todos." );
	}

	/**
	 * Helper: Clean up demo data without confirmation using batch processing.
	 */
	private function cleanup_demo_data() {
		$batch_size = 100;
		$paged = 1;
		$deleted = 0;

		do {
			$query_args = array(
				'post_type'      => 'bp-todo',
				'posts_per_page' => $batch_size,
				'paged'          => $paged,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => '_bptodo_demo_data',
						'value' => '1',
					),
				),
			);

			$query = new WP_Query( $query_args );
			$todo_ids = $query->posts;

			foreach ( $todo_ids as $todo_id ) {
				if ( wp_delete_post( $todo_id, true ) ) {
					$deleted++;
				}
			}

			$paged++;
		} while ( $paged <= $query->max_num_pages );

		if ( $deleted > 0 ) {
			WP_CLI::log( 'Cleaned up ' . $deleted . ' existing demo todos.' );
		}
	}

	/**
	 * Create a todo with given template.
	 *
	 * @param int   $user_id  User ID.
	 * @param array $template Task template data.
	 * @return int|false Post ID on success, false on failure.
	 */
	private function create_todo( $user_id, $template ) {
		$todo_id = wp_insert_post(
			array(
				'post_title'   => $template['title'],
				'post_content' => $template['description'],
				'post_type'    => 'bp-todo',
				'post_status'  => 'publish',
				'post_author'  => $user_id,
			)
		);

		if ( is_wp_error( $todo_id ) ) {
			return false;
		}

		// Add meta data
		update_post_meta( $todo_id, 'todo_status', $template['status'] );
		update_post_meta( $todo_id, 'todo_priority', $template['priority'] );
		update_post_meta( $todo_id, 'todo_due_date', $template['due_date'] );

		if ( ! empty( $template['groupid'] ) ) {
			update_post_meta( $todo_id, 'todo_group_id', $template['groupid'] );
		}

		return $todo_id;
	}

	/**
	 * Get task templates for demo data.
	 *
	 * @return array Array of task templates.
	 */
	private function get_task_templates() {
		$today    = gmdate( 'Y-m-d' );
		$tomorrow = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
		$week     = gmdate( 'Y-m-d', strtotime( '+7 days' ) );
		$past     = gmdate( 'Y-m-d', strtotime( '-3 days' ) );

		// Load from JSON file if it exists
		$json_file = BPTODO_PLUGIN_PATH . 'admin/demo-tasks.json';
		if ( file_exists( $json_file ) ) {
			$json_content = file_get_contents( $json_file );
			$json_tasks   = json_decode( $json_content, true );

			if ( ! empty( $json_tasks ) && is_array( $json_tasks ) ) {
				// Process date placeholders in JSON
				foreach ( $json_tasks as &$task ) {
					if ( isset( $task['due_date'] ) ) {
						switch ( $task['due_date'] ) {
							case '{{today}}':
								$task['due_date'] = $today;
								break;
							case '{{tomorrow}}':
								$task['due_date'] = $tomorrow;
								break;
							case '{{week}}':
								$task['due_date'] = $week;
								break;
							case '{{past}}':
								$task['due_date'] = $past;
								break;
							case '{{2weeks}}':
								$task['due_date'] = gmdate( 'Y-m-d', strtotime( '+14 days' ) );
								break;
							case '{{3weeks}}':
								$task['due_date'] = gmdate( 'Y-m-d', strtotime( '+21 days' ) );
								break;
						}
					}
				}
				return $json_tasks;
			}
		}

		// Fallback to hardcoded templates if JSON not found
		return array(
			// Critical/Urgent
			array(
				'title'       => 'Fix critical security vulnerability',
				'description' => 'Address the security issue reported in the latest scan',
				'priority'    => 'critical',
				'due_date'    => $today,
				'status'      => 'incomplete',
			),
			array(
				'title'       => 'Deploy hotfix to production',
				'description' => 'Critical bug fix needs to be deployed ASAP',
				'priority'    => 'critical',
				'due_date'    => $tomorrow,
				'status'      => 'incomplete',
			),
			array(
				'title'       => 'Review urgent client request',
				'description' => 'Client escalation needs immediate attention',
				'priority'    => 'critical',
				'due_date'    => $past,
				'status'      => 'incomplete',
			),

			// High Priority
			array(
				'title'       => 'Complete monthly report',
				'description' => 'Prepare and submit the monthly performance report',
				'priority'    => 'high',
				'due_date'    => $tomorrow,
				'status'      => 'incomplete',
			),
			array(
				'title'       => 'Update team documentation',
				'description' => 'Review and update the project documentation',
				'priority'    => 'high',
				'due_date'    => $week,
				'status'      => 'incomplete',
			),
			array(
				'title'       => 'Code review for PR #234',
				'description' => 'Review the new feature pull request',
				'priority'    => 'high',
				'due_date'    => $today,
				'status'      => 'incomplete',
			),

			// Normal Priority
			array(
				'title'       => 'Refactor user authentication module',
				'description' => 'Improve code quality and add tests',
				'priority'    => 'normal',
				'due_date'    => $week,
				'status'      => 'incomplete',
			),
			array(
				'title'       => 'Research new UI framework',
				'description' => 'Evaluate options for the next project',
				'priority'    => 'normal',
				'due_date'    => gmdate( 'Y-m-d', strtotime( '+14 days' ) ),
				'status'      => 'incomplete',
			),
			array(
				'title'       => 'Organize team meeting notes',
				'description' => 'Archive and organize past meeting notes',
				'priority'    => 'normal',
				'due_date'    => $week,
				'status'      => 'incomplete',
			),
			array(
				'title'       => 'Write blog post about project',
				'description' => 'Share insights from the recent project launch',
				'priority'    => 'normal',
				'due_date'    => gmdate( 'Y-m-d', strtotime( '+21 days' ) ),
				'status'      => 'incomplete',
			),

			// Completed tasks
			array(
				'title'       => 'Setup development environment',
				'description' => 'Configure local environment with required tools',
				'priority'    => 'high',
				'due_date'    => $past,
				'status'      => 'complete',
			),
			array(
				'title'       => 'Onboard new team member',
				'description' => 'Complete onboarding process for new developer',
				'priority'    => 'normal',
				'due_date'    => $past,
				'status'      => 'complete',
			),
		);
	}
}
