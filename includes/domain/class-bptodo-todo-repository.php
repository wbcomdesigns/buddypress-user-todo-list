<?php
/**
 * Todo repository — the single source of truth for the todo store.
 *
 * Every read and write of the `bp-todo` CPT + its meta goes through here. REST,
 * the legacy AJAX handlers, the CLI seeder, blocks and templates all delegate to
 * this class, so business logic exists in exactly one place and the storage model
 * (CPT/postmeta today) can be swapped later without touching any caller.
 *
 * @package bp-user-todo-list
 * @since   4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bptodo_Todo_Repository' ) ) {

	/**
	 * Class Bptodo_Todo_Repository.
	 */
	class Bptodo_Todo_Repository {

		/**
		 * Fetch a single todo.
		 *
		 * @param int $id Todo ID.
		 * @return Bptodo_Todo|null
		 */
		public static function get( $id ) {
			return Bptodo_Todo::from_id( $id );
		}

		/**
		 * Query one page of todos with full server-side filtering + pagination.
		 *
		 * Absorbs and extends the former `bptodo_get_todos_page()` (which handled only
		 * author/group/status): adds priority, category, search and explicit ordering,
		 * so REST, SSR and the shortcode share one filtered query path.
		 *
		 * @param array $args {
		 *     Optional. Query arguments.
		 *
		 *     @type int    $author       Author ID. Default 0 (any).
		 *     @type int    $group_id     Group ID (todo_group_id). Default 0 (any).
		 *     @type string $status       'all'|'complete'|'incomplete'. Default 'all'.
		 *     @type string $priority     'all'|'normal'|'high'|'critical'. Default 'all'.
		 *     @type string $category     Category slug or term ID. Default '' (any).
		 *     @type string $search       Free-text search over title/content. Default ''.
		 *     @type bool   $primary_only Only primary todos. Default false.
		 *     @type bool   $exclude_group Exclude todos that belong to a group (todo_group_id set),
		 *                                 so group todos do not leak into a member's personal list.
		 *                                 Default false. Ignored when $group_id is set.
		 *     @type int    $page         Page (1-based). Default 1.
		 *     @type int    $per_page     Per page. Default 12.
		 *     @type string $orderby      'smart'|'due_date'|'date'|'title'|'priority'. Default 'smart'.
		 *     @type string $order        'ASC'|'DESC'. Default 'ASC'.
		 * }
		 * @return array { Bptodo_Todo[] $items, int $total, int $total_pages, int $page, int $per_page }
		 */
		public static function query( $args = array() ) {
			$a = wp_parse_args(
				$args,
				array(
					'author'        => 0,
					'group_id'      => 0,
					'status'        => 'all',
					'priority'      => 'all',
					'category'      => '',
					'search'        => '',
					'primary_only'  => false,
					'exclude_group' => false,
					'page'          => 1,
					'per_page'      => 12,
					'orderby'       => 'smart',
					'order'         => 'ASC',
				)
			);

			$query_args = array(
				'post_type'           => Bptodo_Todo::POST_TYPE,
				'post_status'         => 'publish',
				'posts_per_page'      => max( 1, (int) $a['per_page'] ),
				'paged'               => max( 1, (int) $a['page'] ),
				'ignore_sticky_posts' => true,
			);

			if ( ! empty( $a['author'] ) ) {
				$query_args['author'] = (int) $a['author'];
			}
			if ( '' !== (string) $a['search'] ) {
				$query_args['s'] = (string) $a['search'];
			}

			$meta_query = array();
			if ( ! empty( $a['group_id'] ) ) {
				$meta_query[] = array(
					'key'     => Bptodo_Todo::META_GROUP_ID,
					'value'   => (int) $a['group_id'],
					'compare' => '=',
					'type'    => 'NUMERIC',
				);
			} elseif ( ! empty( $a['exclude_group'] ) ) {
				// Personal/member scope: a group todo (primary owned by its creator, or a
				// per-assignee copy) must never appear in a member's own list - it lives on
				// the group Todos tab. Exclude anything carrying a todo_group_id.
				$meta_query[] = array(
					'key'     => Bptodo_Todo::META_GROUP_ID,
					'compare' => 'NOT EXISTS',
				);
			}
			if ( 'complete' === $a['status'] ) {
				$meta_query[] = array(
					'key'   => Bptodo_Todo::META_STATUS,
					'value' => 'complete',
				);
			} elseif ( 'incomplete' === $a['status'] ) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key'     => Bptodo_Todo::META_STATUS,
						'value'   => 'complete',
						'compare' => '!=',
					),
					array(
						'key'     => Bptodo_Todo::META_STATUS,
						'compare' => 'NOT EXISTS',
					),
				);
			}
			if ( 'all' !== $a['priority'] && '' !== (string) $a['priority'] ) {
				$meta_query[] = array(
					'key'   => Bptodo_Todo::META_PRIORITY,
					'value' => sanitize_text_field( (string) $a['priority'] ),
				);
			}
			if ( ! empty( $meta_query ) ) {
				$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- postmeta is the storage model; paginated.
			}

			if ( '' !== (string) $a['category'] ) {
				$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- single term, paginated.
					array(
						'taxonomy' => Bptodo_Todo::TAXONOMY,
						'field'    => is_numeric( $a['category'] ) ? 'term_id' : 'slug',
						'terms'    => $a['category'],
					),
				);
			}

			$smart       = ( 'smart' === $a['orderby'] );
			$use_primary = (bool) $a['primary_only'];
			$order       = ( 'DESC' === strtoupper( (string) $a['order'] ) ) ? 'DESC' : 'ASC';

			if ( $smart ) {
				// Preserve the historic "incomplete first, by priority, by due date" ordering
				// (and primary-only) via the shared clause filter.
				$query_args['bptodo_sorted']       = true;
				$query_args['bptodo_primary_only'] = $use_primary;
				add_filter( 'posts_clauses', 'bptodo_sorted_todos_clauses', 10, 2 );
			} else {
				switch ( $a['orderby'] ) {
					case 'title':
						$query_args['orderby'] = 'title';
						break;
					case 'date':
						$query_args['orderby'] = 'date';
						break;
					case 'priority':
						$query_args['meta_key'] = Bptodo_Todo::META_PRIORITY; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						$query_args['orderby']  = 'meta_value';
						break;
					case 'due_date':
					default:
						$query_args['meta_key'] = Bptodo_Todo::META_DUE_DATE; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						$query_args['orderby']  = 'meta_value';
						break;
				}
				$query_args['order'] = $order;

				if ( $use_primary ) {
					$query_args['bptodo_repo_primary_only'] = true;
					add_filter( 'posts_where', array( __CLASS__, 'filter_primary_only_where' ), 10, 2 );
				}
			}

			$query = new WP_Query( $query_args );

			if ( $smart ) {
				remove_filter( 'posts_clauses', 'bptodo_sorted_todos_clauses', 10 );
			} elseif ( $use_primary ) {
				remove_filter( 'posts_where', array( __CLASS__, 'filter_primary_only_where' ), 10 );
			}

			$items = array();
			foreach ( $query->posts as $post ) {
				$items[] = new Bptodo_Todo( $post );
			}

			return array(
				'items'       => $items,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
				'page'        => (int) $query_args['paged'],
				'per_page'    => (int) $query_args['posts_per_page'],
			);
		}

		/**
		 * WHERE filter: restrict to primary todos (used with explicit ordering).
		 *
		 * @param string   $where WHERE clause.
		 * @param WP_Query $query Query.
		 * @return string
		 */
		public static function filter_primary_only_where( $where, $query ) {
			global $wpdb;
			if ( $query->get( 'bptodo_repo_primary_only' ) ) {
				$where .= " AND {$wpdb->posts}.ID IN ( SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_key = '" . Bptodo_Todo::META_PRIMARY_ID . "' AND pm.meta_value = pm.post_id )";
			}
			return $where;
		}

		/**
		 * Which todos a viewer's group Todo list shows - the one rule for every
		 * group list, count and stat (REST, SSR, nav badge, legacy template).
		 *
		 * A group todo is a primary owned by the manager who created it plus one
		 * copy per assigned member. Whoever may manage the group's tasks (group
		 * admin, site admin, moderator while the list is not locked) sees the
		 * primaries; everyone else, a locked moderator included, sees the copies
		 * assigned to them, which they own and can complete. Listing primaries to
		 * them showed tasks they could not complete (403 on the checkbox).
		 *
		 * @param int $group_id  Group ID.
		 * @param int $viewer_id Viewer (0 = current user).
		 * @return array Query args for query()/stats()/bptodo_count_todos().
		 */
		public static function group_scope_args( $group_id, $viewer_id = 0 ) {
			$viewer_id = $viewer_id ? (int) $viewer_id : (int) get_current_user_id();
			$args      = array( 'group_id' => (int) $group_id );
			if ( Bptodo_Permissions::can_manage_group_tasks( $viewer_id, $group_id ) ) {
				$args['primary_only'] = true;
			} else {
				$args['author'] = $viewer_id;
			}
			return $args;
		}

		/**
		 * Aggregate counts for the stat cards.
		 *
		 * @param array $args { int $author, int $group_id, bool $primary_only }.
		 * @return array { int $total, int $completed, int $pending, float $progress }
		 */
		public static function stats( $args = array() ) {
			$counts = bptodo_count_todos( $args );
			$total  = (int) $counts['total'];
			$done   = (int) $counts['completed'];
			return array(
				'total'     => $total,
				'completed' => $done,
				'pending'   => (int) $counts['pending'],
				'progress'  => $total > 0 ? round( ( $done * 100 ) / $total, 1 ) : 0.0,
			);
		}

		/**
		 * Create a personal todo.
		 *
		 * @param array $data    { string title*, string description, string priority, string due_date, mixed category }.
		 * @param int   $author  Author ID (0 = current).
		 * @return Bptodo_Todo|WP_Error
		 */
		public static function create( $data, $author = 0 ) {
			$author = $author ? (int) $author : (int) get_current_user_id();
			$title  = isset( $data['title'] ) ? sanitize_text_field( wp_unslash( $data['title'] ) ) : '';
			if ( '' === $title ) {
				return new WP_Error( 'bptodo_empty_title', __( 'A title is required.', 'bp-user-to-do-list' ), array( 'status' => 400 ) );
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => Bptodo_Todo::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_content' => isset( $data['description'] ) ? wp_kses_post( wp_unslash( $data['description'] ) ) : '',
					'post_author'  => $author,
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			self::apply_core_meta( $post_id, $data );

			$todo = Bptodo_Todo::from_id( $post_id );
			/**
			 * Fires after a personal todo is created through the repository.
			 *
			 * @param int          $post_id Todo ID.
			 * @param array        $data    Submitted data.
			 * @param Bptodo_Todo  $todo    The todo object.
			 */
			do_action( 'bptodo_todo_created', $post_id, $data, $todo );
			return $todo;
		}

		/**
		 * Create a group todo and fan it out to the selected members.
		 *
		 * Faithful port of the historic group-create flow.
		 *
		 * @param array $data       { title*, description, priority, due_date, category }.
		 * @param int   $group_id   Group ID.
		 * @param array $member_ids Member IDs, or ['all'] / empty for everyone.
		 * @param int   $author     Creator ID (0 = current).
		 * @return Bptodo_Todo|WP_Error The primary todo.
		 */
		public static function assign_to_group( $data, $group_id, $member_ids = array(), $author = 0 ) {
			$author   = $author ? (int) $author : (int) get_current_user_id();
			$group_id = (int) $group_id;
			$title    = isset( $data['title'] ) ? sanitize_text_field( wp_unslash( $data['title'] ) ) : '';
			if ( '' === $title ) {
				return new WP_Error( 'bptodo_empty_title', __( 'A title is required.', 'bp-user-to-do-list' ), array( 'status' => 400 ) );
			}
			if ( ! $group_id ) {
				return new WP_Error( 'bptodo_no_group', __( 'A group is required.', 'bp-user-to-do-list' ), array( 'status' => 400 ) );
			}

			// Resolve the recipient list.
			$member_ids = (array) $member_ids;
			$assign_all = empty( $member_ids ) || in_array( 'all', $member_ids, true );
			if ( $assign_all ) {
				$member_ids = self::group_member_ids( $group_id );
			} else {
				$member_ids = function_exists( 'bptodo_filter_group_member_ids' )
					? bptodo_filter_group_member_ids( $group_id, $member_ids )
					: array_map( 'absint', $member_ids );
			}

			// Primary todo (owned by the creator).
			$primary_id = wp_insert_post(
				array(
					'post_type'    => Bptodo_Todo::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_content' => isset( $data['description'] ) ? wp_kses_post( wp_unslash( $data['description'] ) ) : '',
					'post_author'  => $author,
				),
				true
			);
			if ( is_wp_error( $primary_id ) ) {
				return $primary_id;
			}
			self::apply_core_meta( $primary_id, $data );
			update_post_meta( $primary_id, Bptodo_Todo::META_GROUP_ID, $group_id );
			update_post_meta( $primary_id, Bptodo_Todo::META_PRIMARY_ID, $primary_id );
			update_post_meta( $primary_id, Bptodo_Todo::META_CREATOR_ID, $author );

			// Per-member copies (everyone except the creator, who owns the primary).
			$associated = array();
			foreach ( $member_ids as $member_id ) {
				$member_id = (int) $member_id;
				if ( $member_id === $author ) {
					continue;
				}
				$copy_id = wp_insert_post(
					array(
						'post_type'    => Bptodo_Todo::POST_TYPE,
						'post_status'  => 'publish',
						'post_title'   => $title,
						'post_content' => isset( $data['description'] ) ? wp_kses_post( wp_unslash( $data['description'] ) ) : '',
						'post_author'  => $member_id,
					),
					true
				);
				if ( is_wp_error( $copy_id ) ) {
					continue;
				}
				self::apply_core_meta( $copy_id, $data );
				update_post_meta( $copy_id, Bptodo_Todo::META_GROUP_ID, $group_id );
				update_post_meta( $copy_id, Bptodo_Todo::META_CREATOR_ID, $author );
				update_post_meta( $copy_id, Bptodo_Todo::META_PRIMARY_ID, $primary_id );
				$associated[] = $copy_id;
			}
			update_post_meta( $primary_id, Bptodo_Todo::META_ASSOCIATED, $associated );

			// Preserve the historic notification/email hook contract.
			do_action( 'bptodo_group_todo_submit', $member_ids, $primary_id );

			return Bptodo_Todo::from_id( $primary_id );
		}

		/**
		 * Update editable fields of a todo (only those present in $data).
		 *
		 * @param int   $id   Todo ID.
		 * @param array $data { title, description, priority, due_date, category }.
		 * @return Bptodo_Todo|WP_Error
		 */
		public static function update( $id, $data ) {
			$todo = Bptodo_Todo::from_id( $id );
			if ( ! $todo ) {
				return new WP_Error( 'bptodo_not_found', __( 'Todo not found.', 'bp-user-to-do-list' ), array( 'status' => 404 ) );
			}

			$postarr = array( 'ID' => $todo->id() );
			if ( array_key_exists( 'title', $data ) ) {
				$postarr['post_title'] = sanitize_text_field( wp_unslash( $data['title'] ) );
			}
			if ( array_key_exists( 'description', $data ) ) {
				$postarr['post_content'] = wp_kses_post( wp_unslash( $data['description'] ) );
			}
			if ( count( $postarr ) > 1 ) {
				$updated = wp_update_post( $postarr, true );
				if ( is_wp_error( $updated ) ) {
					return $updated;
				}
			}

			$meta = array();
			if ( array_key_exists( 'priority', $data ) ) {
				$meta[ Bptodo_Todo::META_PRIORITY ] = self::sanitize_priority( $data['priority'] );
			}
			if ( array_key_exists( 'due_date', $data ) ) {
				$due = sanitize_text_field( wp_unslash( $data['due_date'] ) );
				if ( $due !== $todo->due_date() ) {
					// A new due date earns a new reminder: clear the "already sent" flags.
					$meta[ Bptodo_Todo::META_DUE_DATE ]   = $due;
					$meta[ Bptodo_Todo::META_MAIL_SENT ]  = 'no';
					$meta[ Bptodo_Todo::META_NOTIF_SENT ] = 'no';
				}
			}
			foreach ( $meta as $key => $value ) {
				update_post_meta( $todo->id(), $key, $value );
			}
			$term_ids = null;
			if ( array_key_exists( 'category', $data ) && '' !== (string) $data['category'] ) {
				$term_ids = self::resolve_category_terms( $data['category'] );
				$current  = wp_get_object_terms( $todo->id(), Bptodo_Todo::TAXONOMY, array( 'fields' => 'ids' ) );
				if ( ! is_wp_error( $current ) && array_map( 'intval', $current ) === $term_ids ) {
					$term_ids = null; // Unchanged: nothing to write here or on the copies.
				} else {
					wp_set_object_terms( $todo->id(), $term_ids, Bptodo_Todo::TAXONOMY );
				}
			}

			// A group primary carries the shared task: its members' copies follow it.
			if ( $todo->group_id() && (int) get_post_meta( $todo->id(), Bptodo_Todo::META_PRIMARY_ID, true ) === $todo->id() ) {
				self::sync_copies( $todo->id(), array_diff_key( $postarr, array( 'ID' => 0 ) ), $meta, $term_ids );
			}

			$fresh = Bptodo_Todo::from_id( $todo->id() );
			do_action( 'bptodo_todo_updated', $todo->id(), $data, $fresh );
			return $fresh;
		}

		/**
		 * Set a todo's completion status.
		 *
		 * @param int    $id       Todo ID.
		 * @param string $status   'complete' | 'incomplete'.
		 * @param int    $actor_id Acting user (recorded on completion).
		 * @return Bptodo_Todo|WP_Error
		 */
		public static function set_status( $id, $status, $actor_id = 0 ) {
			$todo = Bptodo_Todo::from_id( $id );
			if ( ! $todo ) {
				return new WP_Error( 'bptodo_not_found', __( 'Todo not found.', 'bp-user-to-do-list' ), array( 'status' => 404 ) );
			}
			$actor_id = $actor_id ? (int) $actor_id : (int) get_current_user_id();

			if ( 'complete' === $status ) {
				update_post_meta( $todo->id(), Bptodo_Todo::META_STATUS, 'complete' );
				update_post_meta( $todo->id(), Bptodo_Todo::META_USER_ID, $actor_id );
				update_post_meta( $todo->id(), Bptodo_Todo::META_COMPLETE_TIME, time() );
				update_post_meta( $todo->id(), Bptodo_Todo::META_COMPLETED_DATE, current_time( 'mysql', true ) );
				do_action( 'bptodo_todo_completed', $todo->id(), $actor_id );
			} else {
				update_post_meta( $todo->id(), Bptodo_Todo::META_STATUS, 'incomplete' );
				delete_post_meta( $todo->id(), Bptodo_Todo::META_COMPLETE_TIME );
				delete_post_meta( $todo->id(), Bptodo_Todo::META_COMPLETED_DATE );
				do_action( 'bptodo_todo_uncompleted', $todo->id(), $actor_id );
			}
			return Bptodo_Todo::from_id( $todo->id() );
		}

		/**
		 * Delete a todo.
		 *
		 * Deleting a group primary also deletes its members' copies (callers check
		 * can_delete() first, which only a group task manager passes for a primary).
		 * Deleting a member's copy deletes that copy only and drops it from the
		 * primary's list; the other members keep theirs.
		 *
		 * @param int $id Todo ID.
		 * @return true|WP_Error
		 */
		public static function delete( $id ) {
			$todo = Bptodo_Todo::from_id( $id );
			if ( ! $todo ) {
				return new WP_Error( 'bptodo_not_found', __( 'Todo not found.', 'bp-user-to-do-list' ), array( 'status' => 404 ) );
			}
			$primary_id = (int) get_post_meta( $todo->id(), Bptodo_Todo::META_PRIMARY_ID, true );
			$copy_ids   = array_map( 'intval', (array) get_post_meta( $primary_id ? $primary_id : $todo->id(), Bptodo_Todo::META_ASSOCIATED, true ) );
			if ( $primary_id === $todo->id() ) {
				// ponytail: one wp_delete_post per copy so delete hooks and caches stay right; a 2000-member fan-out is a slow request, batch in the background if owners hit it.
				foreach ( array_filter( $copy_ids ) as $copy_id ) {
					wp_delete_post( $copy_id, true );
				}
			} elseif ( $primary_id ) {
				update_post_meta( $primary_id, Bptodo_Todo::META_ASSOCIATED, array_values( array_diff( $copy_ids, array( $todo->id() ) ) ) );
			}
			wp_delete_post( $todo->id(), true );
			do_action( 'bptodo_todo_deleted', $todo->id() );
			return true;
		}

		// Internal helpers.

		/**
		 * Copy a primary's shared fields to its members' copies.
		 *
		 * Title, description, due date, priority and category follow the primary;
		 * each copy keeps its own completion state. Batched SQL so a task assigned
		 * to a whole 2000-member group is a handful of queries, not thousands.
		 *
		 * @param int        $primary_id Primary todo ID.
		 * @param array      $post_fields post_title / post_content values to copy.
		 * @param array      $meta        Meta key => value to copy.
		 * @param int[]|null $term_ids    Category term IDs to copy, null = unchanged.
		 * @return void
		 */
		protected static function sync_copies( $primary_id, $post_fields, $meta, $term_ids ) {
			global $wpdb;
			$copy_ids = array_values( array_filter( array_map( 'intval', (array) get_post_meta( $primary_id, Bptodo_Todo::META_ASSOCIATED, true ) ) ) );
			if ( ! $copy_ids || ( ! $post_fields && ! $meta && null === $term_ids ) ) {
				return;
			}
			$post_fields['post_modified']     = current_time( 'mysql' );
			$post_fields['post_modified_gmt'] = current_time( 'mysql', true );

			foreach ( array_chunk( $copy_ids, 500 ) as $chunk ) {
				$in = implode( ',', $chunk ); // Integers only (intval above).
				$set = array();
				foreach ( $post_fields as $column => $value ) {
					$set[] = $wpdb->prepare( "`{$column}` = %s", $value ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- column names are fixed keys from update().
				}
				$wpdb->query( "UPDATE {$wpdb->posts} SET " . implode( ', ', $set ) . " WHERE ID IN ({$in}) AND post_type = '" . Bptodo_Todo::POST_TYPE . "'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- values prepared above, ids are ints.
				foreach ( $meta as $key => $value ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND post_id IN ({$in})", $value, $key ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- ids are ints.
				}
				if ( null !== $term_ids ) {
					// ponytail: wp_set_object_terms per copy (a few queries each); only runs when the category changes.
					foreach ( $chunk as $copy_id ) {
						wp_set_object_terms( $copy_id, $term_ids, Bptodo_Todo::TAXONOMY );
					}
				}
				foreach ( $chunk as $copy_id ) {
					clean_post_cache( $copy_id );
				}
			}
		}

		/**
		 * Resolve a category value to EXISTING term IDs only.
		 *
		 * Writing a todo must never create new taxonomy terms - that would let any
		 * user who can edit a todo bypass the `allow_user_add_category` gate. New
		 * categories are created only through the dedicated /categories endpoint
		 * (Bptodo_Categories::create), which enforces the permission. A value that
		 * matches no existing term (by id, slug or name) resolves to an empty set,
		 * which clears the category rather than inventing one.
		 *
		 * @param mixed $value Category id, slug or name.
		 * @return int[] Existing term IDs (0 or 1 element).
		 */
		protected static function resolve_category_terms( $value ) {
			$value = is_scalar( $value ) ? trim( (string) $value ) : '';
			if ( '' === $value ) {
				return array();
			}
			$field = is_numeric( $value ) ? 'term_id' : 'slug';
			$term  = get_term_by( $field, $value, Bptodo_Todo::TAXONOMY );
			if ( ! $term ) {
				$term = get_term_by( 'name', $value, Bptodo_Todo::TAXONOMY );
			}
			return $term ? array( (int) $term->term_id ) : array();
		}

		/**
		 * Apply the shared "core" meta + category that every create path writes.
		 *
		 * @param int   $post_id Todo ID.
		 * @param array $data    Submitted data.
		 * @return void
		 */
		protected static function apply_core_meta( $post_id, $data ) {
			update_post_meta( $post_id, Bptodo_Todo::META_STATUS, 'incomplete' );
			update_post_meta( $post_id, Bptodo_Todo::META_DUE_DATE, isset( $data['due_date'] ) ? sanitize_text_field( wp_unslash( $data['due_date'] ) ) : '' );
			update_post_meta( $post_id, Bptodo_Todo::META_PRIORITY, self::sanitize_priority( isset( $data['priority'] ) ? $data['priority'] : 'normal' ) );
			update_post_meta( $post_id, Bptodo_Todo::META_MAIL_SENT, 'no' );
			update_post_meta( $post_id, Bptodo_Todo::META_NOTIF_SENT, 'no' );
			if ( isset( $data['category'] ) && '' !== (string) $data['category'] ) {
				wp_set_object_terms( $post_id, self::resolve_category_terms( $data['category'] ), Bptodo_Todo::TAXONOMY );
			}
		}

		/**
		 * Normalize a priority value.
		 *
		 * @param string $priority Raw priority.
		 * @return string
		 */
		protected static function sanitize_priority( $priority ) {
			$priority = sanitize_text_field( (string) $priority );
			return in_array( $priority, Bptodo_Todo::PRIORITIES, true ) ? $priority : 'normal';
		}

		/**
		 * All confirmed member IDs of a group (standalone-safe).
		 *
		 * @param int $group_id Group ID.
		 * @return int[]
		 */
		protected static function group_member_ids( $group_id ) {
			if ( ! function_exists( 'groups_get_group_members' ) ) {
				return array();
			}
			$members = groups_get_group_members(
				array(
					'group_id'            => (int) $group_id,
					'exclude_admins_mods' => false,
				)
			);
			$ids     = array();
			if ( ! empty( $members['members'] ) ) {
				foreach ( $members['members'] as $member ) {
					$ids[] = (int) $member->ID;
				}
			}
			return $ids;
		}
	}
}
