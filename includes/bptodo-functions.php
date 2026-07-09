<?php

/**
 * BuddyPress/BuddyBoss compatibility helper for getting member URL.
 * Supports BuddyPress 12.0+, older BuddyPress versions, and BuddyBoss.
 *
 * @param int $user_id The user ID.
 * @return string The member profile URL.
 * @since 1.0.0
 */
if ( ! function_exists( 'bptodo_get_member_url' ) ) {
	function bptodo_get_member_url( $user_id ) {
		if ( function_exists( 'bp_members_get_user_url' ) ) {
			// BuddyPress 12.0+
			return bp_members_get_user_url( $user_id );
		} elseif ( function_exists( 'bp_core_get_user_domain' ) ) {
			// Older BuddyPress/BuddyBoss
			return bp_core_get_user_domain( $user_id );
		} else {
			// Fallback to WordPress author URL
			return get_author_posts_url( $user_id );
		}
	}
}

/**
 * BuddyPress/BuddyBoss compatibility helper for getting group URL.
 * Supports BuddyPress 12.0+, older BuddyPress versions, and BuddyBoss.
 *
 * @param int|object $group_id_or_obj The group ID or group object.
 * @return string The group URL.
 * @since 1.0.0
 */
if ( ! function_exists( 'bptodo_get_group_url' ) ) {
	function bptodo_get_group_url( $group_id_or_obj = 0 ) {
		if ( function_exists( 'bp_get_group_url' ) ) {
			// BuddyPress 12.0+
			return bp_get_group_url( $group_id_or_obj );
		} elseif ( function_exists( 'bp_get_group_permalink' ) ) {
			// Older BuddyPress/BuddyBoss
			return bp_get_group_permalink( $group_id_or_obj );
		} else {
			// Fallback - try to get group object
			if ( is_numeric( $group_id_or_obj ) ) {
				$group = groups_get_group( $group_id_or_obj );
			} else {
				$group = $group_id_or_obj;
			}

			if ( ! empty( $group->slug ) ) {
				return home_url( '/groups/' . $group->slug . '/' );
			}

			return home_url( '/groups/' );
		}
	}
}

if ( ! function_exists( 'bptodo_list_group_modrator' ) ) {
	/**
	 *  Add or exclude group modtaros in average percentage calculation.
	 *
	 * @return boolean
	 */
	function bptodo_list_group_modrator() {
		$group_todo_list_settings = get_option( 'group-todo-list-settings' );

		// list_enable = 'yes' means moderators CAN add todos (ALLOW setting)
		$can_list = ( ! empty( $group_todo_list_settings['list_enable'] ) && in_array( $group_todo_list_settings['list_enable'], array( 'yes', 'on' ), true ) ) ? true : false;

		/**
		 * Filter whether group moderators can add/list group todos.
		 *
		 * Fired where the setting is evaluated so third-party overrides take
		 * effect. Previously this hook name was only registered via
		 * add_filter() and never applied, so overrides were silently ignored.
		 *
		 * @since 3.6.0
		 *
		 * @param bool $can_list Whether moderators can add/list group todos.
		 */
		return apply_filters( 'bptodo_exclude_modrator_view', $can_list );
	}
}

/**
 * Create admin menu to plugin settings.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_if_moderator_modification_enabled( $group_id, $current_user ) {
	$group_todo_list_settings = get_option( 'group-todo-list-settings' );

	// mod_enable = 'yes' means PREVENT modification (inverted logic: PREVENT setting)
	// So if mod_enable is 'yes', return FALSE (cannot modify); otherwise return TRUE (can modify)
	$can_modify = ( ! empty( $group_todo_list_settings['mod_enable'] ) && in_array( $group_todo_list_settings['mod_enable'], array( 'yes', 'on' ), true ) ) ? false : true;

	return apply_filters( 'alter_bptodo_if_moderator_modification_enabled', $can_modify, $group_id, $current_user );
}

/**
 * Add or exclude group modrators to view todo report.
 *
 * @author  wbcomdesigns
 * @since   1.0.0
 */
function bptodo_report_view_enabled( $group_id, $current_user ) {
	$group_todo_list_settings = get_option( 'group-todo-list-settings' );

	// view_enable = 'yes' means moderators CAN view reports (ALLOW setting)
	$can_views = ( ! empty( $group_todo_list_settings['view_enable'] ) && in_array( $group_todo_list_settings['view_enable'], array( 'yes', 'on' ), true ) ) ? true : false;

	/**
	 * Filter whether group moderators can view the group todo report.
	 *
	 * Fired where the setting is evaluated so third-party overrides take
	 * effect. Previously this hook name was only registered via add_filter()
	 * and never applied, so overrides were silently ignored.
	 *
	 * @since 3.6.0
	 *
	 * @param bool $can_views    Whether moderators can view the report.
	 * @param int  $group_id     The group ID.
	 * @param int  $current_user The user being checked.
	 */
	return apply_filters( 'bptodo_exclude_modrator_edit', $can_views, $group_id, $current_user );
}


/**
 * Hide the todo tab when group todo disable option is enabled.
 *
 * @return void
 */
function bptodo_disable_group_todo(){
	// Check if groups component is active
	if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'groups' ) ) {
		return;
	}

	global $bp;
	if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
		$group_id 			 = $bp->groups->current_group->id;
		$group_disable_todos = function_exists( 'groups_get_groupmeta' ) ? groups_get_groupmeta( $group_id, 'group-disable-todos', true ) : false;
		if ( 1 === (int) $group_disable_todos ) {
		?>
		<style>
			#nav-to-do{
				display:none!important;
			}
		</style>
		<?php
		}
	}
}
add_action( 'wp', 'bptodo_disable_group_todo' );

/**
 * Check if current user is a site admin.
 *
 * @return bool True if user can manage options (is admin).
 * @since 1.0.0
 */
if ( ! function_exists( 'bptodo_is_site_admin' ) ) {
	function bptodo_is_site_admin() {
		return current_user_can( 'manage_options' );
	}
}

if ( ! function_exists( 'bptodo_sorted_todos_clauses' ) ) {
	/**
	 * Apply the shared front-end todo ordering at the database level.
	 *
	 * Order: incomplete first, then priority (critical > high > normal),
	 * then due date (earliest first, empty last), then newest first.
	 * Only runs for queries flagged with the `bptodo_sorted` query var
	 * (set by bptodo_get_todos_page()).
	 *
	 * @param array    $clauses The query clauses.
	 * @param WP_Query $query   The current query.
	 * @return array Modified clauses.
	 * @since 3.6.0
	 */
	function bptodo_sorted_todos_clauses( $clauses, $query ) {
		global $wpdb;

		if ( ! $query->get( 'bptodo_sorted' ) ) {
			return $clauses;
		}

		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} bptodo_status_mt ON ( bptodo_status_mt.post_id = {$wpdb->posts}.ID AND bptodo_status_mt.meta_key = 'todo_status' )";
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} bptodo_priority_mt ON ( bptodo_priority_mt.post_id = {$wpdb->posts}.ID AND bptodo_priority_mt.meta_key = 'todo_priority' )";
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} bptodo_due_mt ON ( bptodo_due_mt.post_id = {$wpdb->posts}.ID AND bptodo_due_mt.meta_key = 'todo_due_date' )";

		if ( $query->get( 'bptodo_primary_only' ) ) {
			$clauses['where'] .= " AND {$wpdb->posts}.ID IN ( SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_key = 'todo_primary_id' AND pm.meta_value = pm.post_id )";
		}

		$clauses['orderby'] = "( bptodo_status_mt.meta_value = 'complete' ) ASC, "
			. "CASE bptodo_priority_mt.meta_value WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END ASC, "
			. "( bptodo_due_mt.meta_value IS NULL OR bptodo_due_mt.meta_value = '' ) ASC, "
			. 'bptodo_due_mt.meta_value ASC, '
			. "{$wpdb->posts}.post_date DESC";

		return $clauses;
	}
}

if ( ! function_exists( 'bptodo_get_todos_page' ) ) {
	/**
	 * Fetch ONE page of todos with server-side LIMIT/OFFSET and total count.
	 *
	 * Replaces the old fetch-all patterns (posts_per_page => -1 / do-while
	 * loops / hard caps) in the member and group list templates. Post meta
	 * is batch-primed by WP_Query, so per-card get_post_meta() calls hit
	 * the object cache instead of the database.
	 *
	 * @param array $args {
	 *     Query arguments.
	 *
	 *     @type int    $author       Restrict to this author. Default 0 (any).
	 *     @type int    $group_id     Restrict to this group (todo_group_id meta). Default 0 (any).
	 *     @type string $status       '' (all), 'complete' or 'incomplete'. Default ''.
	 *     @type bool   $primary_only Only primary todos (todo_primary_id = own ID). Default false.
	 *     @type int    $page         Page number (1-based). Default 1.
	 *     @type int    $per_page     Items per page. Default 12.
	 * }
	 * @return array {
	 *     @type WP_Post[] $todos       The current page of todos.
	 *     @type int       $total       Total matching todos.
	 *     @type int       $total_pages Total pages.
	 *     @type int       $page        Current page (1-based).
	 *     @type int       $per_page    Items per page.
	 * }
	 * @since 3.6.0
	 */
	function bptodo_get_todos_page( $args = array() ) {
		// Backward-compatible wrapper over the domain repository (the single query
		// authority). The historic signature used status '' for "all"; map it, then
		// delegate. Return shape ($todos as WP_Post[]) is preserved for existing callers.
		$args = wp_parse_args(
			$args,
			array(
				'status' => '',
			)
		);
		if ( '' === $args['status'] ) {
			$args['status'] = 'all';
		}

		$result = Bptodo_Todo_Repository::query( $args );

		$posts = array();
		foreach ( $result['items'] as $todo ) {
			$posts[] = $todo->post();
		}

		return array(
			'todos'       => $posts,
			'total'       => (int) $result['total'],
			'total_pages' => (int) $result['total_pages'],
			'page'        => (int) $result['page'],
			'per_page'    => (int) $result['per_page'],
		);
	}
}

if ( ! function_exists( 'bptodo_count_todos' ) ) {
	/**
	 * Count todos with a single aggregate query (no row fetch).
	 *
	 * Intentionally uncached: todo_status flips via AJAX meta updates that
	 * do not fire save_post, so a TTL cache would show stale counts right
	 * after a member completes a task. The query is a single indexed
	 * aggregate and is run once per list view.
	 *
	 * @param array $args {
	 *     Count arguments.
	 *
	 *     @type int  $author       Restrict to this author. Default 0 (any).
	 *     @type int  $group_id     Restrict to this group. Default 0 (any).
	 *     @type bool $primary_only Only primary todos. Default false.
	 * }
	 * @return array { @type int $total @type int $completed @type int $pending }
	 * @since 3.6.0
	 */
	function bptodo_count_todos( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'author'       => 0,
			'group_id'     => 0,
			'primary_only' => false,
		);
		$args     = wp_parse_args( $args, $defaults );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names are safe, values are prepared below.
		$sql = "SELECT COUNT(p.ID) AS total, COALESCE( SUM( status_mt.meta_value = 'complete' ), 0 ) AS completed
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} status_mt ON ( status_mt.post_id = p.ID AND status_mt.meta_key = 'todo_status' )
			WHERE p.post_type = 'bp-todo' AND p.post_status = 'publish'";

		$prepare_args = array();

		if ( ! empty( $args['author'] ) ) {
			$sql           .= ' AND p.post_author = %d';
			$prepare_args[] = (int) $args['author'];
		}
		if ( ! empty( $args['group_id'] ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names are safe, values are prepared below.
			$sql           .= " AND EXISTS ( SELECT 1 FROM {$wpdb->postmeta} gm WHERE gm.post_id = p.ID AND gm.meta_key = 'todo_group_id' AND gm.meta_value = %d )";
			$prepare_args[] = (int) $args['group_id'];
		}
		if ( ! empty( $args['primary_only'] ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names are safe, no user input in this clause.
			$sql .= " AND p.ID IN ( SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_key = 'todo_primary_id' AND pm.meta_value = pm.post_id )";
		}

		if ( ! empty( $prepare_args ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is built above with placeholders only.
			$sql = $wpdb->prepare( $sql, $prepare_args );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Single aggregate count, prepared above; see docblock for why it is uncached.
		$row = $wpdb->get_row( $sql, ARRAY_A );

		$total     = isset( $row['total'] ) ? (int) $row['total'] : 0;
		$completed = isset( $row['completed'] ) ? (int) $row['completed'] : 0;

		return array(
			'total'     => $total,
			'completed' => $completed,
			'pending'   => max( 0, $total - $completed ),
		);
	}
}

if ( ! function_exists( 'bptodo_filter_group_member_ids' ) ) {
	/**
	 * Reduce a list of user IDs to the ones who are confirmed, unbanned
	 * members of a group — in a single query.
	 *
	 * @param int   $group_id Group ID.
	 * @param int[] $user_ids User IDs to check.
	 * @return int[] Subset of $user_ids that are current group members.
	 * @since 3.6.0
	 */
	function bptodo_filter_group_member_ids( $group_id, $user_ids ) {
		global $wpdb, $bp;

		$group_id = absint( $group_id );
		$user_ids = array_filter( array_map( 'absint', (array) $user_ids ) );

		if ( ! $group_id || empty( $user_ids ) ) {
			return array();
		}

		$members_table = isset( $bp->groups->table_name_members ) ? $bp->groups->table_name_members : $wpdb->prefix . 'bp_groups_members';
		$placeholders  = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe, placeholders built above, values prepared here.
		$sql = $wpdb->prepare(
			"SELECT user_id FROM {$members_table} WHERE group_id = %d AND is_confirmed = 1 AND is_banned = 0 AND user_id IN ( {$placeholders} )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array_merge( array( $group_id ), $user_ids )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Single membership lookup, prepared above.
		return array_map( 'intval', (array) $wpdb->get_col( $sql ) );
	}
}

if ( ! function_exists( 'bptodo_get_group_member_task_stats' ) ) {
	/**
	 * Per-member task stats for a group, aggregated and paginated in SQL.
	 *
	 * Replaces the old pattern of loading every group todo plus every group
	 * member and cross-checking them with per-row get_post_meta() calls.
	 * Members are ranked by completion percentage (lowest first) so the
	 * members needing attention surface on page 1.
	 *
	 * @param int $group_id Group ID.
	 * @param int $per_page Members per page.
	 * @param int $page     Page number (1-based).
	 * @return array {
	 *     @type array[] $members       Page of rows: user_id, total_tasks, completed_tasks,
	 *                                  pending_tasks, completion_percentage, status.
	 *     @type int     $total_members Total members with at least one task.
	 *     @type int     $completed     Members who completed all their tasks.
	 * }
	 * @since 3.6.0
	 */
	function bptodo_get_group_member_task_stats( $group_id, $per_page = 15, $page = 1 ) {
		global $wpdb, $bp;

		$group_id = absint( $group_id );
		$per_page = max( 1, (int) $per_page );
		$page     = max( 1, (int) $page );
		$offset   = ( $page - 1 ) * $per_page;

		$members_table = isset( $bp->groups->table_name_members ) ? $bp->groups->table_name_members : $wpdb->prefix . 'bp_groups_members';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names are safe, values are prepared below.
		$base_sql = "FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} gm ON ( gm.post_id = p.ID AND gm.meta_key = 'todo_group_id' AND gm.meta_value = %d )
			INNER JOIN {$members_table} m ON ( m.user_id = p.post_author AND m.group_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0 )
			LEFT JOIN {$wpdb->postmeta} st ON ( st.post_id = p.ID AND st.meta_key = 'todo_status' )
			WHERE p.post_type = 'bp-todo' AND p.post_status = 'publish'
			GROUP BY p.post_author";

		// Summary: how many members have tasks, and how many completed everything.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built from the safe base SQL above.
		$summary_sql = 'SELECT COUNT(*) AS total_members, COALESCE( SUM( t.is_complete ), 0 ) AS completed_members FROM (
			SELECT p.post_author, ( COALESCE( SUM( st.meta_value = \'complete\' ), 0 ) = COUNT(*) ) AS is_complete ' . $base_sql . ' ) t';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Aggregate stats, prepared here; uncached because todo_status flips via meta updates.
		$summary = $wpdb->get_row( $wpdb->prepare( $summary_sql, $group_id, $group_id ), ARRAY_A );

		// Page of member rows, worst completion percentage first.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built from the safe base SQL above.
		$page_sql = 'SELECT p.post_author AS user_id, COUNT(*) AS total_tasks,
			COALESCE( SUM( st.meta_value = \'complete\' ), 0 ) AS completed_tasks ' . $base_sql
			. ' ORDER BY ( COALESCE( SUM( st.meta_value = \'complete\' ), 0 ) / COUNT(*) ) ASC, p.post_author ASC LIMIT %d OFFSET %d';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Paginated aggregate, prepared here.
		$rows = $wpdb->get_results( $wpdb->prepare( $page_sql, $group_id, $group_id, $per_page, $offset ), ARRAY_A );

		$members = array();
		if ( ! empty( $rows ) ) {
			// Batch-prime user objects for the page (display names, avatars).
			cache_users( wp_list_pluck( $rows, 'user_id' ) );

			foreach ( $rows as $row ) {
				$total_tasks     = (int) $row['total_tasks'];
				$completed_tasks = (int) $row['completed_tasks'];
				$user            = get_userdata( (int) $row['user_id'] );

				$members[] = array(
					'user_id'               => (int) $row['user_id'],
					'name'                  => $user ? $user->display_name : '',
					'total_tasks'           => $total_tasks,
					'completed_tasks'       => $completed_tasks,
					'pending_tasks'         => $total_tasks - $completed_tasks,
					'completion_percentage' => $total_tasks > 0 ? (int) round( ( $completed_tasks / $total_tasks ) * 100 ) : 0,
					'status'                => ( $completed_tasks === $total_tasks ) ? 'complete' : 'incomplete',
					'completed_date'        => '',
				);
			}
		}

		return array(
			'members'       => $members,
			'total_members' => isset( $summary['total_members'] ) ? (int) $summary['total_members'] : 0,
			'completed'     => isset( $summary['completed_members'] ) ? (int) $summary['completed_members'] : 0,
		);
	}
}

/**
 * Check if a user is assigned to a specific task.
 *
 * @param int $task_id The task/todo post ID.
 * @param int $user_id The user ID to check.
 * @return bool True if user is assigned to the task.
 * @since 1.0.0
 */
if ( ! function_exists( 'bptodo_is_user_assigned_to_task' ) ) {
	function bptodo_is_user_assigned_to_task( $task_id, $user_id ) {
		$task = get_post( $task_id );

		if ( ! $task ) {
			return false;
		}

		// Check if user is the primary task creator
		if ( $task->post_author == $user_id ) {
			return true;
		}

		// Check if user has an associated todo for this task
		$associated_todos = get_post_meta( $task_id, 'botodo_associated_todo', true );
		if ( ! empty( $associated_todos ) && is_array( $associated_todos ) ) {
			foreach ( $associated_todos as $assoc_id ) {
				$assoc_post = get_post( $assoc_id );
				if ( $assoc_post && $assoc_post->post_author == $user_id ) {
					return true;
				}
			}
		}

		return false;
	}
}
if ( ! function_exists( 'bptodo_asset_ver' ) ) {
	/**
	 * Version string for a plugin-owned asset.
	 *
	 * On local/debug environments this returns the file's modification time so
	 * every CSS/JS edit busts the browser cache during development. In production
	 * it returns BPTODO_VERSION (bumped per release). Shared by the modern
	 * renderer and the legacy admin/front enqueue so cache-busting behaves the
	 * same everywhere.
	 *
	 * @since 4.0.0
	 * @param string $rel_path Path relative to the plugin root (e.g. 'assets/css/bptodo-front.min.css').
	 * @return string
	 */
	function bptodo_asset_ver( $rel_path ) {
		$is_dev = ( defined( 'WP_DEBUG' ) && WP_DEBUG )
			|| ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() );

		if ( $is_dev && defined( 'BPTODO_PLUGIN_PATH' ) ) {
			$abs = BPTODO_PLUGIN_PATH . ltrim( $rel_path, '/' );
			if ( is_readable( $abs ) ) {
				return (string) filemtime( $abs );
			}
		}

		return defined( 'BPTODO_VERSION' ) ? BPTODO_VERSION : '4.0.0';
	}
}

if ( ! function_exists( 'bptodo_layout_mode' ) ) {
	/**
	 * The effective frontend layout mode: 'modern' (default) or 'legacy'.
	 *
	 * Modern = block/REST/Interactivity. Legacy = the classic templates + jQuery.
	 * Auto-falls back to legacy when the Interactivity/block runtime is unavailable
	 * (e.g. WordPress < 6.5), so the modern default never hard-breaks an older site.
	 *
	 * @since 4.0.0
	 * @param string $context Optional caller context ('member', 'group', 'shortcode').
	 * @return string 'modern'|'legacy'.
	 */
	function bptodo_layout_mode( $context = '' ) {
		$modern_supported = function_exists( 'wp_interactivity_state' )
			&& function_exists( 'wp_interactivity_process_directives' )
			&& function_exists( 'register_block_type' );

		if ( ! $modern_supported ) {
			return 'legacy';
		}

		$settings = get_option( 'user_todo_list_settings' );
		$mode     = ! empty( $settings['bptodo_layout_mode'] ) ? $settings['bptodo_layout_mode'] : 'modern';
		$mode     = ( 'legacy' === $mode ) ? 'legacy' : 'modern';

		/**
		 * Filter the effective todo layout mode.
		 *
		 * @param string $mode    'modern'|'legacy'.
		 * @param string $context Caller context.
		 */
		return apply_filters( 'bptodo_layout_mode', $mode, $context );
	}
}

if ( ! function_exists( 'bptodo_render_todos' ) ) {
	/**
	 * Render the modern todo list for a non-block context (shortcode / BP tab).
	 *
	 * Returns processed Interactivity markup, or '' in legacy mode (the caller then
	 * renders its classic template).
	 *
	 * @since 4.0.0
	 * @param array  $args    Renderer args (see Bptodo_Renderer::render()).
	 * @param string $context Caller context.
	 * @return string
	 */
	function bptodo_render_todos( $args = array(), $context = '' ) {
		if ( 'legacy' === bptodo_layout_mode( $context ) || ! class_exists( 'Bptodo_Renderer' ) ) {
			return '';
		}
		$html = Bptodo_Renderer::render( $args );
		if ( function_exists( 'wp_interactivity_process_directives' ) ) {
			$html = wp_interactivity_process_directives( $html );
		}
		return $html;
	}
}
