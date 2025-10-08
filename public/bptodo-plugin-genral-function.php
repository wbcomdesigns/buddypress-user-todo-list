<?php
/**
 * Locate template.
 *
 * Locate the called template. Search Order:
 *
 * @since 2.2.1
 * @package bp-user-todo-list
 * @param string $template_name    Template to load.
 * @param string $template_path    Path to templates.
 * @param string $default_path     Default path to template files.
 * @return string                  Path to the template file.
 */

if ( ! function_exists( 'bp_todo_locate_template' ) ) {

	function bp_todo_locate_template( $template_name, $template_path = '', $default_path = '' ) {

		// Set variable to search in todo folder of theme.
		$template_path = $template_path ?: 'todo/';
		
		// Set default plugin templates path.
		$default_path = $default_path ?: BPTODO_PLUGIN_PATH . '/inc/todo/';
		
		// Search template file in theme folder.
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name,
			)
		);

		// Get plugin's template file if not found in theme.
		if ( ! $template ) {
			$template = $default_path . $template_name;
		}

		return apply_filters( 'bp_todo_locate_template', $template, $template_name, $template_path, $default_path );
	}
}

/**
 * Get template.
 *
 * Search for the template and include the file.
 *
 * @since 2.2.1
 * @param string $template_name    Template to load.
 * @param array  $args             Args passed for the template file.
 * @param string $template_path    Path to templates.
 * @param string $default_path     Default path to template files.
 */
if ( ! function_exists( 'bp_todo_get_template' ) ) {

	function bp_todo_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {

		if ( is_array( $args ) ) {
			extract( $args ); // Extract variables for use in the template.
		}

		$template_file = bp_todo_locate_template( $template_name, $template_path, $default_path );

		if ( ! file_exists( $template_file ) ) {
			_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file /* phpcs:ignore*/ ), '1.0.0' );
			return;
		}

		include $template_file;
	}
}

/**
 * Modify the query for the 'bp-todo' post type archive.
 *
 * @since 2.2.1
 * @param WP_Query $query The WordPress Query object.
 */
if ( ! function_exists( 'bp_todo_post_type_archive' ) ) {

	function bp_todo_post_type_archive( $query ) {
		if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'bp-todo' ) ) {
			$user_id = get_current_user_id();
			$query->set( 'author', $user_id ); // Show only todos authored by the current user.
		}
	}
	add_action( 'pre_get_posts', 'bp_todo_post_type_archive' );
}

/**
 * Template loader for the plugin.
 *
 * @since 2.2.1
 * @param string $template The path to the template file.
 * @return string          Modified path to the template file.
 */
if ( ! function_exists( 'bptodo_template_loader' ) ) {

	add_filter( 'template_include', 'bptodo_template_loader' );

	function bptodo_template_loader( $template ) {
		$default_file = bptodo_get_template_loader_default_file();

		if ( $default_file ) {
			$search_files = bptodo_get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template ) {
				$template = BPTODO_TEMPLATE_PATH . $default_file;
			}
		}

		return $template;
	}
}

/**
 * Get the default template file for the plugin.
 *
 * @since 2.2.1
 * @return string Default template file.
 */
if ( ! function_exists( 'bptodo_get_template_loader_default_file' ) ) {

	function bptodo_get_template_loader_default_file() {
		if ( is_singular( 'bp-todo' ) ) {
			return 'single-bp-todo.php';
		} elseif ( is_tax( get_object_taxonomies( 'bp-todo' ) ) ) {
			return 'archive-bp-todo.php';
		} elseif ( is_post_type_archive( 'bp-todo' ) ) {
			return 'archive-bp-todo.php';
		} else {
			return '';
		}
	}
}

/**
 * Get the list of files to search for the template loader.
 *
 * @since 2.2.1
 * @param string $default_file Default file name.
 * @return array               List of template files to search for.
 */
if ( ! function_exists( 'bptodo_get_template_loader_files' ) ) {

	function bptodo_get_template_loader_files( $default_file ) {
		$templates = array();

		if ( is_page_template() ) {
			$templates[] = get_page_template_slug();
		}

		if ( is_singular( 'bp-todo' ) ) {
			$object       = get_queried_object();
			$name_decoded = urldecode( $object->post_name );

			if ( $name_decoded !== $object->post_name ) {
				$templates[] = "single-bp-todo-{$name_decoded}.php";
			}

			$templates[] = "single-bp-todo-{$object->post_name}.php";
		}

		if ( is_tax( get_object_taxonomies( 'bp-todo' ) ) ) {
			$object      = get_queried_object();
			$templates[] = "archive-{$object->taxonomy}-{$object->slug}.php";
			$templates[] = BPTODO_TEMPLATE_PATH . "archive-{$object->taxonomy}-{$object->slug}.php";
			$templates[] = "archive-{$object->taxonomy}.php";
			$templates[] = BPTODO_TEMPLATE_PATH . "archive-{$object->taxonomy}.php";
		}

		$templates[] = $default_file;
		$templates[] = BPTODO_TEMPLATE_PATH . $default_file;

		return array_unique( $templates );
	}
}

/**
 * Get the average completion percentage of todos for a user.
 *
 * @since 2.2.1
 * @param int $todoID The ID of the todo post.
 * @return float      Average completion percentage.
 */
if ( ! function_exists( 'bptodo_get_user_average_todos' ) ) {

	function bptodo_get_user_average_todos( $todoID ) {
		global $bp;

		$group_id        = $bp->groups->current_group->id ?? 0;
		$todo_primary_id = get_post_meta( $todoID, 'todo_primary_id', true );
		$gp_admin_id     = groups_get_group_admins( $group_id );

		// Use fields => 'ids' to reduce memory usage
		$total_args = array(
			'post_type'      => 'bp-todo',
			'posts_per_page' => apply_filters( 'bptodo_group_task_limit', 500 ),
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'todo_group_id',
					'value'   => $group_id,
					'compare' => '=',
				),
				array(
					'key'     => 'todo_primary_id',
					'value'   => $todo_primary_id,
					'compare' => '=',
				),
			),
		);

		$total_args['author__not_in'] = (float) $gp_admin_id;
		$todos = get_posts( $total_args );

		$total_count    = count( $todos );
		$completed_count = bpto_completed_todo_count( $group_id, $todo_primary_id );
		$avg_rating     = 0;

		if ( $total_count > 0 ) {
			$avg_rating = round( ( $completed_count * 100 ) / $total_count, 2 ) . '%';
		}

		wp_reset_postdata();

		return $avg_rating;
	}
}

/**
 * Get the count of completed todos.
 *
 * @since 2.2.1
 * @param int $group_id        Associated group ID.
 * @param int $todo_primary_id Primary todo ID.
 * @return int                 Count of completed todos.
 */
if ( ! function_exists( 'bpto_completed_todo_count' ) ) {

	function bpto_completed_todo_count( $group_id, $todo_primary_id ) {
		// Use fields => 'ids' to reduce memory usage - we only need the count
		$completed_args = array(
			'post_type'      => 'bp-todo',
			'posts_per_page' => apply_filters( 'bptodo_group_task_limit', 500 ),
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'todo_status',
					'value'   => 'complete',
					'compare' => '=',
				),
				array(
					'key'     => 'todo_group_id',
					'value'   => $group_id,
					'compare' => '=',
				),
				array(
					'key'     => 'todo_primary_id',
					'value'   => $todo_primary_id,
					'compare' => '=',
				),
			),
		);

		$gp_admin_id                  = groups_get_group_admins( $group_id );
		$completed_args['author__not_in'] = (float) $gp_admin_id;
		$completed_todos = get_posts( $completed_args );
		$completed_count = count( $completed_todos );

		wp_reset_postdata();

		return $completed_count;
	}
}

/**
 * Display a report table on the single todo page.
 *
 * @since 2.2.1
 */
if ( ! function_exists( 'bptodo_todo_user_report' ) ) {

	function bptodo_todo_user_report() {
		global $post;

		if ( ! bp_is_active( 'groups' ) ) {
			return;
		}

		$group_id     = get_post_meta( $post->ID, 'todo_group_id', true );
		$current_user = get_current_user_id();

		if ( empty( $group_id ) ) {
			return;
		}

		// Only show report on the PRIMARY todo (where primary_id equals post ID)
		$primary_todo_id = get_post_meta( $post->ID, 'todo_primary_id', true );
		if ( empty( $primary_todo_id ) || $primary_todo_id != $post->ID ) {
			return; // This is an associated todo, not the primary one
		}

		$can_view = bptodo_enable_report_view( $current_user, $group_id );

		if ( ! $can_view ) {
			return; // Bail if the user does not have permission to view the report.
		}

		$args = array(
			'group_id'            => $group_id,
			'exclude_admins_mods' => false, // Include ALL members including admins and mods
		);

		$group_members     = groups_get_group_members( $args );
		$group_members_ids = array_column( $group_members['members'], 'ID' );

		if ( ! empty( $group_members_ids ) && is_array( $group_members_ids ) ) {
			$parent_todo = get_post_meta( $post->ID, 'todo_primary_id', true );

			// Calculate completion statistics
			$total_members = count( $group_members_ids );
			$completed_count = 0;
			$member_data = array();

			if ( $parent_todo ) {
				// Add primary todo to statistics
				$primary_status = get_post_meta( $parent_todo, 'todo_status', true );
				$primary_author = get_post_field( 'post_author', $parent_todo );
				$primary_time = get_post_meta( $parent_todo, 'todo_complete_time', true );
				$primary_date = get_post_meta( $parent_todo, 'todo_completed_date', true );

				if ( in_array( $primary_author, $group_members_ids ) ) {
					$member_data[] = array(
						'user_id' => $primary_author,
						'name' => get_the_author_meta( 'display_name', $primary_author ),
						'status' => $primary_status,
						'time' => $primary_time,
						'completed_date' => $primary_date,
					);
					if ( $primary_status === 'complete' ) {
						$completed_count++;
					}
				}

				// Add associated todos to statistics
				$assoc_todos = get_post_meta( $parent_todo, 'botodo_associated_todo', true );
				if ( $assoc_todos ) {
					foreach ( $assoc_todos as $assoc_todo ) {
						$member_todo_status = get_post_meta( $assoc_todo, 'todo_status', true );
						$todo_author_id = get_post_field( 'post_author', $assoc_todo );
						$member_todo_time = get_post_meta( $assoc_todo, 'todo_complete_time', true );
						$member_todo_date = get_post_meta( $assoc_todo, 'todo_completed_date', true );

						if ( in_array( $todo_author_id, $group_members_ids ) ) {
							$member_data[] = array(
								'user_id' => $todo_author_id,
								'name' => get_the_author_meta( 'display_name', $todo_author_id ),
								'status' => $member_todo_status,
								'time' => $member_todo_time,
								'completed_date' => $member_todo_date,
							);
							if ( $member_todo_status === 'complete' ) {
								$completed_count++;
							}
						}
					}
				}
			}

			$completion_percentage = $total_members > 0 ? round( ( $completed_count / $total_members ) * 100 ) : 0;

			// Get group link for navigation
			$group = groups_get_group( $group_id );
			$group_link = bptodo_get_group_url( $group );
			$group_todo_link = trailingslashit( $group_link ) . 'todo/list/';
			?>
			<div class="bptodo-group-report-wrapper">
				<div class="bptodo-report-header">
					<h3 class="bptodo-report-title"><?php esc_html_e( 'Group Task Progress', 'wb-todo' ); ?></h3>
					<a href="<?php echo esc_url( $group_todo_link ); ?>" class="bptodo-back-to-group">
						<span class="dashicons dashicons-arrow-left-alt2"></span>
						<?php printf( esc_html__( 'Back to %s', 'wb-todo' ), esc_html( $group->name ) ); ?>
					</a>
				</div>

				<!-- Overall Completion Stats -->
				<div class="bptodo-report-overview">
					<div class="bptodo-completion-summary">
						<span class="bptodo-completion-percentage"><?php echo esc_html( $completion_percentage ); ?>%</span>
						<span class="bptodo-completion-details"><?php echo esc_html( $completed_count ); ?> <?php esc_html_e( 'of', 'wb-todo' ); ?> <?php echo esc_html( $total_members ); ?> <?php esc_html_e( 'members completed', 'wb-todo' ); ?></span>
					</div>

					<!-- Progress Bar -->
					<div class="bptodo-progress-bar">
						<div class="bptodo-progress-fill" style="width: <?php echo esc_attr( $completion_percentage ); ?>%;"></div>
					</div>
				</div>

				<!-- Member Progress List -->
				<div class="bptodo-member-progress-list">
					<div class="bptodo-member-list-header">
						<h4 class="bptodo-member-list-title"><?php esc_html_e( 'Member Status', 'wb-todo' ); ?></h4>
						<div class="bptodo-member-filters">
							<button class="bptodo-filter-btn active" data-filter="all">
								<?php esc_html_e( 'All', 'wb-todo' ); ?>
								<span class="bptodo-filter-count"><?php echo esc_html( $total_members ); ?></span>
							</button>
							<button class="bptodo-filter-btn" data-filter="completed">
								<?php esc_html_e( 'Completed', 'wb-todo' ); ?>
								<span class="bptodo-filter-count"><?php echo esc_html( $completed_count ); ?></span>
							</button>
							<button class="bptodo-filter-btn" data-filter="pending">
								<?php esc_html_e( 'Pending', 'wb-todo' ); ?>
								<span class="bptodo-filter-count"><?php echo esc_html( $total_members - $completed_count ); ?></span>
							</button>
						</div>
					</div>

					<!-- Member Grid -->
					<div class="bptodo-member-grid">
					<?php foreach ( $member_data as $member ) :
						$is_complete = $member['status'] === 'complete';
						$status_class = $is_complete ? 'status-complete' : 'status-incomplete';

						// Get completion date/time
						$time_text = '';
						if ( $is_complete ) {
							if ( !empty( $member['completed_date'] ) ) {
								// Use the new completed_date field (MySQL datetime)
								$time_text = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $member['completed_date'] ) );
							} elseif ( !empty( $member['time'] ) ) {
								// Fallback to old timestamp field
								$time_text = human_time_diff( $member['time'], current_time( 'timestamp' ) ) . ' ' . esc_html__( 'ago', 'wb-todo' );
							}
						}
					?>
						<div class="bptodo-member-item <?php echo esc_attr( $status_class ); ?>">
							<?php echo get_avatar( $member['user_id'], 40, '', '', array( 'class' => 'bptodo-member-avatar' ) ); ?>
							<div class="bptodo-member-info">
								<a href="<?php echo bptodo_get_member_url( $member['user_id'] ); ?>" class="bptodo-member-name">
									<?php echo esc_html( ucfirst( $member['name'] ) ); ?>
								</a>
								<span class="bptodo-member-status">
									<?php if ( $is_complete ) : ?>
										<span class="bptodo-status-badge bptodo-status-done">
											<span class="dashicons dashicons-yes"></span>
											<?php esc_html_e( 'Completed', 'wb-todo' ); ?>
										</span>
										<?php if ( !empty( $time_text ) ) : ?>
											<span class="bptodo-member-time"><?php echo esc_html( $time_text ); ?></span>
										<?php endif; ?>
									<?php else : ?>
										<span class="bptodo-status-badge bptodo-status-pending">
											<span class="dashicons dashicons-clock"></span>
											<?php esc_html_e( 'Pending', 'wb-todo' ); ?>
										</span>
									<?php endif; ?>
								</span>
							</div>
						</div>
					<?php endforeach; ?>
					</div><!-- .bptodo-member-grid -->
				</div>
			</div>
			<?php
			wp_reset_postdata();
		}
	}
}

/**
 * Check if the current user can view the report.
 *
 * @since 2.2.1
 * @param int $current_user The current user ID.
 * @param int $group_id     The group ID.
 * @return bool             True if the user can view, false otherwise.
 */
if ( ! function_exists( 'bptodo_enable_report_view' ) ) {

	function bptodo_enable_report_view( $current_user, $group_id ) {

		$group_todo_list_settings = get_option( 'group-todo-list-settings' );
		$can_view = false;

		// Site admins and group admins can always view the report
		if ( current_user_can( 'manage_options' ) || groups_is_user_admin( $current_user, $group_id ) ) {
			$can_view = true;
		}

		// Group moderators can view the report only if the setting is enabled
		elseif ( groups_is_user_mod( $current_user, $group_id ) ) {
			if ( ! empty( $group_todo_list_settings['view_enable'] ) && in_array( $group_todo_list_settings['view_enable'], array( 'yes', 'on' ), true ) ) {
				$can_view = true;
			}
		}

		// Allow filtering the result
		return apply_filters( 'bptodo_enable_report_view', $can_view, $current_user, $group_id );
	}
}


