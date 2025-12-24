<?php
/**
 * Group Todo List Template
 *
 * Modern card-based layout for displaying group todos.
 * Uses the design system and reusable partials.
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Filter to get only primary tasks (where todo_primary_id equals the post's own ID).
 * This performs the filtering at database level instead of loading all tasks into memory.
 *
 * @param string   $where The WHERE clause of the query.
 * @param WP_Query $query The WP_Query instance.
 * @return string Modified WHERE clause.
 */
if ( ! function_exists( 'bptodo_filter_primary_tasks_only' ) ) {
	function bptodo_filter_primary_tasks_only( $where, $query ) {
		global $wpdb;

		if ( isset( $query->query_vars['post_type'] ) && 'bp-todo' === $query->query_vars['post_type'] ) {
			$where .= $wpdb->prepare(
				" AND {$wpdb->posts}.ID IN (
					SELECT pm.post_id FROM {$wpdb->postmeta} pm
					WHERE pm.meta_key = %s AND pm.meta_value = pm.post_id
				)",
				'todo_primary_id'
			);
		}

		return $where;
	}
}

global $bptodo, $bp;

// Get plugin settings.
$profile_menu_slug         = $bptodo->profile_menu_slug;
$profile_menu_label        = $bptodo->profile_menu_label;
$profile_menu_label_plural = $bptodo->profile_menu_label_plural;

// Group info.
$groups_link = bptodo_get_group_url( $bp->groups->current_group );
$admin_link  = trailingslashit( $groups_link . $profile_menu_slug );
$group_id    = $bp->groups->current_group->id ? $bp->groups->current_group->id : 0;

// Permission checks.
$current_user_id  = get_current_user_id();
$is_site_admin    = bptodo_is_site_admin();
$is_group_member  = groups_is_user_member( $current_user_id, $group_id );
$is_admin_non_member = $is_site_admin && ! $is_group_member;

// Determine modification and view permissions.
$can_modify = ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $current_user_id, $group_id ) ) || $is_admin_non_member;
$can_views  = $can_modify || ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $current_user_id, $group_id ) );

// Check moderator permissions.
if ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $current_user_id, $group_id ) ) {
	if ( bptodo_if_moderator_modification_enabled( $group_id, $current_user_id ) ) {
		$can_modify = true;
	}
	if ( bptodo_report_view_enabled( $group_id, $current_user_id ) ) {
		$can_views = true;
	}
}

// Build query arguments.
$args = array(
	'post_type'      => 'bp-todo',
	'post_status'    => 'publish',
	'posts_per_page' => 100,
	'paged'          => 1,
	'meta_query'     => array(
		array(
			'key'     => 'todo_group_id',
			'value'   => $group_id,
			'compare' => '=',
			'type'    => 'NUMERIC',
		),
	),
);

// Query logic based on user type.
if ( $is_admin_non_member ) {
	// Admin non-member: Get only PRIMARY tasks (filtered at database level).
	add_filter( 'posts_where', 'bptodo_filter_primary_tasks_only', 10, 2 );
} else {
	// Regular member or admin who is a group member: Get only their own tasks.
	$args['author'] = $current_user_id;
}

// Fetch all todos with pagination.
$all_todos = array();
do {
	$todos      = get_posts( $args );
	$all_todos  = array_merge( $all_todos, $todos );
	$args['paged']++;
} while ( count( $todos ) === $args['posts_per_page'] );

// Remove the filter after query completes.
if ( $is_admin_non_member ) {
	remove_filter( 'posts_where', 'bptodo_filter_primary_tasks_only', 10 );
}

// Sort todos: Incomplete first, then by priority, then by due date.
usort(
	$all_todos,
	function ( $a, $b ) {
		$a_status   = get_post_meta( $a->ID, 'todo_status', true );
		$b_status   = get_post_meta( $b->ID, 'todo_status', true );
		$a_priority = get_post_meta( $a->ID, 'todo_priority', true );
		$b_priority = get_post_meta( $b->ID, 'todo_priority', true );
		$a_due_date = get_post_meta( $a->ID, 'todo_due_date', true );
		$b_due_date = get_post_meta( $b->ID, 'todo_due_date', true );

		// Completed tasks go to bottom.
		if ( 'complete' === $a_status && 'complete' !== $b_status ) {
			return 1;
		}
		if ( 'complete' !== $a_status && 'complete' === $b_status ) {
			return -1;
		}

		// Sort by priority.
		$priority_map   = array(
			'critical' => 3,
			'high'     => 2,
			'normal'   => 1,
		);
		$a_priority_val = isset( $priority_map[ $a_priority ] ) ? $priority_map[ $a_priority ] : 0;
		$b_priority_val = isset( $priority_map[ $b_priority ] ) ? $priority_map[ $b_priority ] : 0;

		if ( $a_priority_val !== $b_priority_val ) {
			return $b_priority_val - $a_priority_val;
		}

		// Sort by due date.
		if ( empty( $a_due_date ) && ! empty( $b_due_date ) ) {
			return 1;
		}
		if ( ! empty( $a_due_date ) && empty( $b_due_date ) ) {
			return -1;
		}
		if ( ! empty( $a_due_date ) && ! empty( $b_due_date ) ) {
			$a_timestamp = strtotime( $a_due_date );
			$b_timestamp = strtotime( $b_due_date );
			if ( false !== $a_timestamp && false !== $b_timestamp ) {
				return $a_timestamp - $b_timestamp;
			}
		}

		return strtotime( $b->post_date ) - strtotime( $a->post_date );
	}
);

// Calculate statistics.
$all_todo_count     = count( $all_todos );
$all_completed_todo = 0;
$all_pending_todo   = 0;
$pending_todos      = array();
$completed_todos    = array();

foreach ( $all_todos as $todo ) {
	$todo_status = get_post_meta( $todo->ID, 'todo_status', true );
	if ( 'complete' === $todo_status ) {
		$all_completed_todo++;
		$completed_todos[] = $todo;
	} else {
		$all_pending_todo++;
		$pending_todos[] = $todo;
	}
}

$completion_percentage = $all_todo_count > 0 ? round( ( $all_completed_todo * 100 ) / $all_todo_count, 1 ) : 0;
?>

<div class="bptodo-wrapper" id="bptodo-group-list">
	<?php if ( empty( $all_todos ) ) : ?>
		<?php
		$context    = 'group';
		$filter     = '';
		$can_create = $can_modify;
		include BPTODO_PLUGIN_PATH . 'public/todo/partials/empty-state.php';
		?>
	<?php else : ?>
		<!-- Page Header -->
		<div class="bptodo-page-header">
			<h2 class="bptodo-page-title">
				<?php
				printf(
					/* translators: %s: Todo label */
					esc_html__( 'Group %s', 'wb-todo' ),
					esc_html( $profile_menu_label_plural )
				);
				?>
			</h2>
			<?php if ( $is_admin_non_member ) : ?>
				<span class="bptodo-badge bptodo-badge--category">
					<svg class="bptodo-badge__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
						<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
					</svg>
					<?php esc_html_e( 'Read-Only Mode', 'wb-todo' ); ?>
				</span>
			<?php endif; ?>
		</div>

		<!-- Progress Stats -->
		<div class="bptodo-stats">
			<div class="bptodo-stat-card">
				<div class="bptodo-stat-card__icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
						<circle cx="9" cy="7" r="4"></circle>
						<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
						<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
					</svg>
				</div>
				<div class="bptodo-stat-card__content">
					<p class="bptodo-stat-card__value"><?php echo esc_html( $all_todo_count ); ?></p>
					<p class="bptodo-stat-card__label">
						<?php echo esc_html( $all_todo_count === 1 ? $profile_menu_label : $profile_menu_label_plural ); ?>
					</p>
				</div>
			</div>

			<div class="bptodo-stat-card">
				<div class="bptodo-stat-card__icon" style="background: var(--bptodo-warning-light); color: var(--bptodo-warning);">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="12" cy="12" r="10"></circle>
						<polyline points="12 6 12 12 16 14"></polyline>
					</svg>
				</div>
				<div class="bptodo-stat-card__content">
					<p class="bptodo-stat-card__value"><?php echo esc_html( $all_pending_todo ); ?></p>
					<p class="bptodo-stat-card__label"><?php esc_html_e( 'Pending', 'wb-todo' ); ?></p>
				</div>
			</div>

			<div class="bptodo-stat-card">
				<div class="bptodo-stat-card__icon" style="background: var(--bptodo-success-light); color: var(--bptodo-success);">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
						<polyline points="22 4 12 14.01 9 11.01"></polyline>
					</svg>
				</div>
				<div class="bptodo-stat-card__content">
					<p class="bptodo-stat-card__value"><?php echo esc_html( $all_completed_todo ); ?></p>
					<p class="bptodo-stat-card__label"><?php esc_html_e( 'Completed', 'wb-todo' ); ?></p>
				</div>
			</div>

			<div class="bptodo-stat-card">
				<div class="bptodo-stat-card__icon" style="background: var(--bptodo-primary-light); color: var(--bptodo-primary);">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<line x1="12" y1="20" x2="12" y2="10"></line>
						<line x1="18" y1="20" x2="18" y2="4"></line>
						<line x1="6" y1="20" x2="6" y2="16"></line>
					</svg>
				</div>
				<div class="bptodo-stat-card__content">
					<p class="bptodo-stat-card__value"><?php echo esc_html( $completion_percentage ); ?>%</p>
					<p class="bptodo-stat-card__label"><?php esc_html_e( 'Progress', 'wb-todo' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Progress Bar -->
		<div class="bptodo-progress" style="margin-bottom: var(--bptodo-space-6);">
			<div class="bptodo-progress__bar" style="width: <?php echo esc_attr( $completion_percentage ); ?>%;"></div>
		</div>

		<?php do_action( 'bptodo_add_extra_tab_content_before_defaults', $profile_menu_label ); ?>

		<!-- Search & Filters Bar -->
		<?php
		// Get categories for filter dropdown.
		$todo_categories = get_terms(
			array(
				'taxonomy'   => 'bptodo_category',
				'hide_empty' => false,
			)
		);
		?>
		<div class="bptodo-search-filters">
			<div class="bptodo-search-filters__row">
				<!-- Search Input -->
				<div class="bptodo-search-filters__search">
					<input
						type="text"
						id="bptodo-search-input"
						class="bptodo-input bptodo-search-input"
						placeholder="<?php esc_attr_e( 'Search todos...', 'wb-todo' ); ?>"
						aria-label="<?php esc_attr_e( 'Search todos', 'wb-todo' ); ?>"
					>
					<svg class="bptodo-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="11" cy="11" r="8"></circle>
						<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
					</svg>
				</div>

				<!-- Filter Dropdowns -->
				<div class="bptodo-search-filters__filters">
					<!-- Status Filter -->
					<select id="bptodo-filter-status" class="bptodo-filter-select" aria-label="<?php esc_attr_e( 'Filter by status', 'wb-todo' ); ?>">
						<option value=""><?php esc_html_e( 'All Status', 'wb-todo' ); ?></option>
						<option value="incomplete"><?php esc_html_e( 'Pending', 'wb-todo' ); ?></option>
						<option value="complete"><?php esc_html_e( 'Completed', 'wb-todo' ); ?></option>
					</select>

					<!-- Priority Filter -->
					<select id="bptodo-filter-priority" class="bptodo-filter-select" aria-label="<?php esc_attr_e( 'Filter by priority', 'wb-todo' ); ?>">
						<option value=""><?php esc_html_e( 'All Priorities', 'wb-todo' ); ?></option>
						<option value="critical"><?php esc_html_e( 'Critical', 'wb-todo' ); ?></option>
						<option value="high"><?php esc_html_e( 'High', 'wb-todo' ); ?></option>
						<option value="normal"><?php esc_html_e( 'Normal', 'wb-todo' ); ?></option>
					</select>

					<!-- Category Filter -->
					<?php if ( ! empty( $todo_categories ) && ! is_wp_error( $todo_categories ) ) : ?>
					<select id="bptodo-filter-category" class="bptodo-filter-select" aria-label="<?php esc_attr_e( 'Filter by category', 'wb-todo' ); ?>">
						<option value=""><?php esc_html_e( 'All Categories', 'wb-todo' ); ?></option>
						<?php foreach ( $todo_categories as $category ) : ?>
							<option value="<?php echo esc_attr( $category->slug ); ?>"><?php echo esc_html( $category->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php endif; ?>

					<!-- Clear Filters Button -->
					<button type="button" id="bptodo-clear-filters" class="bptodo-clear-filters" aria-label="<?php esc_attr_e( 'Clear all filters', 'wb-todo' ); ?>">
						<?php esc_html_e( 'Clear', 'wb-todo' ); ?>
					</button>
				</div>
			</div>

			<!-- Results Info -->
			<div class="bptodo-search-filters__info">
				<span id="bptodo-results-count" class="bptodo-results-count">
					<?php
					printf(
						/* translators: %d: Number of todos */
						esc_html( _n( 'Showing %d todo', 'Showing %d todos', $all_todo_count, 'wb-todo' ) ),
						esc_html( $all_todo_count )
					);
					?>
				</span>
			</div>
		</div>

		<!-- No Results Message (hidden by default) -->
		<div class="bptodo-no-results" id="bptodo-no-results" style="display: none;">
			<svg class="bptodo-no-results__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="11" cy="11" r="8"></circle>
				<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
				<line x1="8" y1="8" x2="14" y2="14"></line>
				<line x1="14" y1="8" x2="8" y2="14"></line>
			</svg>
			<p class="bptodo-no-results__text"><?php esc_html_e( 'No todos match your search criteria.', 'wb-todo' ); ?></p>
		</div>

		<!-- Tab Navigation -->
		<div class="bptodo-tabs" role="tablist">
			<button
				type="button"
				class="bptodo-tab bptodo-tab--active"
				role="tab"
				aria-selected="true"
				aria-controls="bptodo-panel-all"
				id="bptodo-tab-all"
				data-tab="all"
			>
				<?php
				printf(
					/* translators: %s: Todo label */
					esc_html__( 'All %s', 'wb-todo' ),
					esc_html( $profile_menu_label_plural )
				);
				?>
				<span class="bptodo-badge bptodo-badge--category"><?php echo esc_html( $all_todo_count ); ?></span>
			</button>
			<button
				type="button"
				class="bptodo-tab"
				role="tab"
				aria-selected="false"
				aria-controls="bptodo-panel-completed"
				id="bptodo-tab-completed"
				data-tab="completed"
			>
				<?php esc_html_e( 'Completed', 'wb-todo' ); ?>
				<span class="bptodo-badge bptodo-badge--category"><?php echo esc_html( $all_completed_todo ); ?></span>
			</button>
		</div>

		<!-- All Todos Panel -->
		<div
			class="bptodo-tab-panel"
			role="tabpanel"
			id="bptodo-panel-all"
			aria-labelledby="bptodo-tab-all"
		>
			<div class="bptodo-list bptodo-list--grid" role="list">
				<?php
				foreach ( $all_todos as $todo ) :
					// Determine permissions for this todo.
					// Admin non-members have read-only access.
					// Regular members can only edit/delete if they own the todo and have modify permission.
					$is_readonly = $is_admin_non_member;
					$todo_status = get_post_meta( $todo->ID, 'todo_status', true );

					$can_edit   = $can_modify && ! $is_readonly && 'complete' !== $todo_status;
					$can_delete = $can_modify && ! $is_readonly;

					$context       = 'group';
					$show_checkbox = ! $is_readonly;
					include BPTODO_PLUGIN_PATH . 'public/todo/partials/todo-card.php';
				endforeach;
				?>
			</div>
		</div>

		<!-- Completed Todos Panel -->
		<div
			class="bptodo-tab-panel"
			role="tabpanel"
			id="bptodo-panel-completed"
			aria-labelledby="bptodo-tab-completed"
			hidden
		>
			<?php if ( empty( $completed_todos ) ) : ?>
				<?php
				$context    = 'group';
				$filter     = 'complete';
				$can_create = false;
				include BPTODO_PLUGIN_PATH . 'public/todo/partials/empty-state.php';
				?>
			<?php else : ?>
				<div class="bptodo-list bptodo-list--grid" role="list">
					<?php
					foreach ( $completed_todos as $todo ) :
						$is_readonly = $is_admin_non_member;

						$can_edit      = false; // Completed todos can't be edited.
						$can_delete    = $can_modify && ! $is_readonly;
						$context       = 'group';
						$show_checkbox = ! $is_readonly;
						include BPTODO_PLUGIN_PATH . 'public/todo/partials/todo-card.php';
					endforeach;
					?>
				</div>
			<?php endif; ?>
		</div>

		<?php do_action( 'bptodo_add_extra_tab_content_after_defaults', $profile_menu_label ); ?>
	<?php endif; ?>
</div>

<!-- Tab Switching JavaScript -->
<script>
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		const tabs = document.querySelectorAll('#bptodo-group-list .bptodo-tab');
		const panels = document.querySelectorAll('#bptodo-group-list .bptodo-tab-panel');

		tabs.forEach(function(tab) {
			tab.addEventListener('click', function() {
				const targetId = 'bptodo-panel-' + this.dataset.tab;

				tabs.forEach(function(t) {
					t.classList.remove('bptodo-tab--active');
					t.setAttribute('aria-selected', 'false');
				});
				this.classList.add('bptodo-tab--active');
				this.setAttribute('aria-selected', 'true');

				panels.forEach(function(p) {
					p.hidden = (p.id !== targetId);
				});
			});
		});
	});
})();
</script>
