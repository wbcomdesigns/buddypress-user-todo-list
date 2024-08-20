<?php
/**
 * Locate template.
 *
 * Locate the called template.
 * Search Order:
 *
 * @since 2.2.1
 * @package bp-user-todo-list
 * @param   string $template_name   Template to load.
 * @param   string $template_path   Path to templates.
 * @param   string $default_path    Default path to template files.
 * @return  string                  Path to the template file.
 */

if ( ! function_exists( 'bp_todo_locate_template' ) ) {

	function bp_todo_locate_template( $template_name, $template_path = '', $default_path = '' ) {

		// Set variable to search in the todo folder of theme.
		if ( ! $template_path ) {
			$template_path = 'todo/';
		}
		// Set default plugin templates path.
		if ( ! $default_path ) {
			$default_path = BPTODO_PLUGIN_PATH . '/inc/todo/';
		}
		// Search template file in theme folder.
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name,
			)
		);
		// Get plugin's template file.
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
 *
 * @param string $template_name   Template to load.
 * @param array  $args            Args passed for the template file.
 * @param string $template_path   Path to templates.
 * @param string $default_path    Default path to template files.
 */
if ( ! function_exists( 'bp_todo_get_template' ) ) {

	function bp_todo_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {

		if ( is_array( $args ) && ! empty( $args ) ) {
			// Consider avoiding 'extract' for better readability and safety.
			extract( $args );
		}
		$template_file = bp_todo_locate_template( $template_name, $template_path, $default_path );
		if ( ! file_exists( $template_file ) ) {
			_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', esc_html( $template_file ) ), '1.0.0' );
			return;
		}

		include $template_file;
	}
}


if ( ! function_exists( 'bp_todo_post_type_archive' ) ) {

	/**
	 * Get an archive of todo posts.
	 */
	function bp_todo_post_type_archive( $query ) {
		if ( is_admin() ) {
			return;
		}
		if ( $query->is_main_query() && is_post_type_archive( 'bp-todo' ) ) {
			$user_id = get_current_user_id();
			$query->set( 'author', $user_id );
		}
	}
	add_action( 'pre_get_posts', 'bp_todo_post_type_archive' );
}


if ( ! function_exists( 'bptodo_template_loader' ) ) {
	add_filter( 'template_include', 'bptodo_template_loader' );
	/**
	 * Function for searching the default template file.
	 *
	 * @param string $template Location of the template.
	 */
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

if ( ! function_exists( 'bptodo_get_template_loader_default_file' ) ) {
	/**
	 * Function for loading the default template file.
	 */
	function bptodo_get_template_loader_default_file() {
		if ( is_singular( 'bp-todo' ) ) {
			$default_file = 'single-bp-todo.php';
		} elseif ( is_tax( get_object_taxonomies( 'bp-todo' ) ) ) {
			$default_file = 'archive-bp-todo.php';
		} elseif ( is_post_type_archive( 'bp-todo' ) ) {
			$default_file = 'archive-bp-todo.php';
		} else {
			$default_file = '';
		}
		return $default_file;
	}
}

if ( ! function_exists( 'bptodo_get_template_loader_files' ) ) {
	/**
	 * Function for loading template files.
	 *
	 * @param string $default_file Default file to load.
	 * @return array Unique list of templates.
	 */
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
			$templates[] = 'archive-' . $object->taxonomy . '-' . $object->slug . '.php';
			$templates[] = BPTODO_TEMPLATE_PATH . 'archive-' . $object->taxonomy . '-' . $object->slug . '.php';
			$templates[] = 'archive-' . $object->taxonomy . '.php';
			$templates[] = BPTODO_TEMPLATE_PATH . 'archive-' . $object->taxonomy . '.php';
		}

		$templates[] = $default_file;
		$templates[] = BPTODO_TEMPLATE_PATH . $default_file;

		return array_unique( $templates );
	}
}


if ( ! function_exists( 'bptodo_get_user_average_todos' ) ) {
	/**
	 * Display average todo percentage of each member.
	 *
	 * @param  int $todoID The ID of the post (TO DO).
	 * @return float       Average percentage of todo.
	 */
	function bptodo_get_user_average_todos( $todoID ) {
		$group_id        = bp_groups_get_current_group_id();
		$todo_primary_id = get_post_meta( $todoID, 'todo_primary_id', true );
		$gp_admin_id     = groups_get_group_admins( $group_id );

		$total_args = array(
			'post_type'      => 'bp-todo',
			'posts_per_page' => -1,
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
		$total_count = 0;
		if ( ! empty( $todos ) ) {
			$total_count = count( $todos );
		}

		$completed_count = bpto_completed_todo_count( $group_id, $todo_primary_id );
		$avg_rating      = 0;
		if ( ! empty( $total_count ) ) {
			$avg_rating = ( $completed_count * 100 ) / $total_count;
			$avg_rating = round( $avg_rating, 2 ) . '% ';
		}
		
		wp_reset_postdata();
		
		return $avg_rating;
	}
}

if ( ! function_exists( 'bpto_completed_todo_count' ) ) {
	/**
	 * Get the completed todo count.
	 *
	 * @param  int $group_id        Associated group ID.
	 * @param  int $todo_primary_id Primary todo ID.
	 * @return int                  Count of completed todos.
	 */
	function bpto_completed_todo_count( $group_id, $todo_primary_id ) {
		$gp_admin_id    = groups_get_group_admins( $group_id );
		$completed_args = array(
			'post_type'      => 'bp-todo',
			'posts_per_page' => -1,
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
					'key'     => 'todo_primary_id',
					'value'   => $todo_primary_id,
					'compare' => '=',
				),
			),
		);
		$completed_args['author__not_in'] = (float) $gp_admin_id;

		$completed_todos = get_posts( $completed_args );
		$completed_count = 0;
		if ( ! empty( $completed_todos ) ) {
			$completed_count = count( $completed_todos );
		}

		wp_reset_postdata();

		return $completed_count;
	}
}

if ( ! function_exists( 'bptodo_todo_user_report' ) ) {
	/**
	 * Create a report table on the single todo page.
	 */
	function bptodo_todo_user_report() {
		global $post;

		if ( bp_is_active( 'groups' ) ) {
			$group_id     = get_post_meta( $post->ID, 'todo_group_id', true );
			$current_user = get_current_user_id();

			if ( ! empty( $group_id ) ) {

				$can_view = bptodo_enable_report_view( $current_user, $group_id );

				if ( true !== $can_view ) {
					return; // Bail out if moderator view is not enabled.
				}

				$args = array(
					'group_id'            => $group_id,
					'exclude_admins_mods' => apply_filters( 'bptodo_exclude_moderator_view', true ),
				);

				$group_members     = groups_get_group_members( $args );
				$group_members_ids = array();

				foreach ( $group_members['members'] as $member ) {
					$group_members_ids[] = $member->ID;
				}
			}

			if ( ! empty( $group_members_ids ) && is_array( $group_members_ids ) ) {
				?>
				<table class="bp-todo-report">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Members', 'wb-todo' ); ?></th>
							<th><?php esc_html_e( 'Status', 'wb-todo' ); ?></th>
							<th><?php esc_html_e( 'Time', 'wb-todo' ); ?></th>
						</tr>
					</thead>
					<tbody>

						<?php
						$parent_todo = get_post_meta( $post->ID, 'todo_primary_id', true );
						if ( ! empty( $parent_todo ) ) {
							$assoc_todos = get_post_meta( $parent_todo, 'botodo_associated_todo', true );

							if ( ! empty( $assoc_todos ) ) {

								foreach ( $assoc_todos as $assoc_todo ) {

									$member_todo_status         = get_post_meta( $assoc_todo, 'todo_status', true );
									$get_member_todo_completion = get_post_meta( $assoc_todo, 'todo_complete_time', true );
									$todo_author_id             = get_post_field( 'post_author', $assoc_todo );

									if ( ! empty( $get_member_todo_completion ) ) {
										$member_todo_completion = human_time_diff( current_time( 'timestamp' ), $get_member_todo_completion ) . ' ago';

									}

									if ( in_array( $todo_author_id, $group_members_ids ) ) {
										$author_name = get_the_author_meta( 'display_name', $todo_author_id );

										?>
										<tr>
											<td><?php echo esc_html( ucfirst( $author_name ) ); ?></td>
											<td><?php echo esc_html( $member_todo_status ); ?></td>
											<td><?php echo ( 'incomplete' === $member_todo_status ) ? '-' : esc_html( $member_todo_completion ); ?></td>
										</tr>
										<?php
									}
								}
							}
						}
				}
				?>

					</tbody>
				</table>
				<?php
				wp_reset_postdata();
			}

		}
	}
}


if ( ! function_exists( 'bptodo_enable_report_view' ) ) {
	/**
	 * Function performed for view action.
	 *
	 * @param  mixed $current_user Get current user.
	 * @param  mixed $group_id Get a group ID.
	 */
	function bptodo_enable_report_view( $current_user, $group_id ) {

		$group_todo_list_settings = get_option( 'group-todo-list-settings' );
		$can_view                 = false;

		if ( (bool) groups_is_user_mod( $current_user, $group_id ) || (bool) groups_is_user_admin( $current_user, $group_id ) ) {

			$mod_can_view = ( isset( $group_todo_list_settings['view_enable'] ) ) ? true : false;

			if ( $mod_can_view ) {

				$can_view = true;
			}
		}

		return apply_filters( 'bptodo_enable_report_view', $can_view, $current_user, $group_id );
	}
}
