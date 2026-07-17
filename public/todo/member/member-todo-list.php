<?php
/**
 * Member Todo List Template
 *
 * Modern card-based layout for displaying member's todos.
 * Uses the design system and reusable partials.
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $bp, $bptodo;

// Get plugin settings.
$profile_menu_slug         = $bptodo->profile_menu_slug;
$profile_menu_label        = $bptodo->profile_menu_label;
$profile_menu_label_plural = $bptodo->profile_menu_label_plural;

// Determine current user permissions.
$current_user_id = get_current_user_id();

// Modern layout (4.0): render the Interactivity/REST list and stop. The classic
// template below runs only in legacy mode (or when modern is unsupported).
if ( function_exists( 'bptodo_render_todos' ) && 'modern' === bptodo_layout_mode( 'member' ) ) {
	$bptodo_modern_list = bptodo_render_todos(
		array(
			'scope'      => 'member',
			'user_id'    => 0,
			'per_page'   => (int) apply_filters( 'bptodo_member_list_per_page', 12 ),
			'show_add'   => true,
			'show_stats' => true,
		),
		'member'
	);
	if ( '' !== $bptodo_modern_list ) {
		echo $bptodo_modern_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer returns escaped Interactivity markup.
		return;
	}
}

/*
 * Server-side pagination (big-site readiness). The old code fetched up to
 * a silent 500-task cap (apply_filters bptodo_member_list_limit) and
 * paginated client-side, so anything past the cap was invisible and
 * unmanageable. Each tab panel now runs one LIMIT/OFFSET query with the
 * ordering pushed to SQL and meta batch-primed; counts come from a single
 * aggregate COUNT query, so there is no cap at all.
 */

/**
 * Filters the number of todos per page on the member list.
 *
 * @since 3.6.0
 *
 * @param int $per_page Todos per page.
 */
$bptodo_per_page = apply_filters( 'bptodo_member_list_per_page', 12 );

$todo_page = isset( $_GET['todo_page'] ) ? max( 1, absint( $_GET['todo_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination arg.
$done_page = isset( $_GET['done_page'] ) ? max( 1, absint( $_GET['done_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination arg.

// Aggregate counts (stats cards, tab badges, progress bar).
$todo_counts        = bptodo_count_todos( array( 'author' => $current_user_id ) );
$all_todo_count     = $todo_counts['total'];
$all_completed_todo = $todo_counts['completed'];
$all_pending_todo   = $todo_counts['pending'];

// Current page of todos for each tab panel (meta batch-primed by WP_Query,
// so the per-card todo_group_id / status reads below hit the cache).
$all_result = bptodo_get_todos_page(
	array(
		'author'   => $current_user_id,
		'page'     => $todo_page,
		'per_page' => $bptodo_per_page,
	)
);

$completed_result = bptodo_get_todos_page(
	array(
		'author'   => $current_user_id,
		'status'   => 'complete',
		'page'     => $done_page,
		'per_page' => $bptodo_per_page,
	)
);

$todos           = $all_result['todos'];
$completed_todos = $completed_result['todos'];

$completion_percentage = $all_todo_count > 0 ? round( ( $all_completed_todo * 100 ) / $all_todo_count, 1 ) : 0;
?>

<div class="bptodo-wrapper" id="bptodo-member-list">
	<?php if ( 0 === $all_todo_count ) : ?>
		<?php
		// Show empty state.
		$context    = 'member';
		$filter     = '';
		$can_create = true;
		include BPTODO_PLUGIN_PATH . 'public/todo/partials/empty-state.php';
		?>
	<?php else : ?>
		<!-- Page Header with Stats -->
		<div class="bptodo-page-header">
			<h2 class="bptodo-page-title">
				<?php
				printf(
					/* translators: %s: Todo label */
					esc_html__( 'My %s', 'wb-todo' ),
					esc_html( $profile_menu_label_plural )
				);
				?>
			</h2>
			<div class="bptodo-page-actions">
				<?php echo esc_html( list_todo_tab_function_to_show_title() ); ?>
			</div>
		</div>

		<!-- Progress Stats -->
		<div class="bptodo-stats">
			<div class="bptodo-stat-card">
				<div class="bptodo-stat-card__icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
						<polyline points="14 2 14 8 20 8"></polyline>
						<line x1="16" y1="13" x2="8" y2="13"></line>
						<line x1="16" y1="17" x2="8" y2="17"></line>
						<polyline points="10 9 9 9 8 9"></polyline>
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
					<button type="button" id="bptodo-clear-filters" class="bptodo-btn bptodo-btn--ghost bptodo-btn--sm" aria-label="<?php esc_attr_e( 'Clear all filters', 'wb-todo' ); ?>">
						<?php esc_html_e( 'Clear', 'wb-todo' ); ?>
					</button>
				</div>
			</div>

			<!-- Results Info -->
			<div class="bptodo-search-filters__info">
				<span id="bptodo-results-count" class="bptodo-results-count">
					<?php
					$showing_start = min( $all_todo_count, ( ( $all_result['page'] - 1 ) * $all_result['per_page'] ) + 1 );
					$showing_end   = min( $all_todo_count, $all_result['page'] * $all_result['per_page'] );
					printf(
						/* translators: 1: First item on page, 2: Last item on page, 3: Total todos */
						esc_html__( 'Showing %1$d-%2$d of %3$d todos', 'wb-todo' ),
						esc_html( $showing_start ),
						esc_html( $showing_end ),
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
			<?php if ( empty( $todos ) ) : ?>
				<?php
				$context    = 'member';
				$filter     = '';
				$can_create = true;
				include BPTODO_PLUGIN_PATH . 'public/todo/partials/empty-state.php';
				?>
			<?php else : ?>
				<div class="bptodo-list bptodo-list--grid" role="list">
					<?php
					foreach ( $todos as $todo ) :
						// Determine if this is a group todo.
						$todo_gp_id   = get_post_meta( $todo->ID, 'todo_group_id', true );
						$is_group_todo = ! empty( $todo_gp_id );

						// Set permissions based on todo ownership.
						$can_edit   = ! $is_group_todo; // Can only edit personal todos.
						$can_delete = ! $is_group_todo; // Can only delete personal todos.

						// Include the card partial.
						$context       = 'member';
						$show_checkbox = true;
						include BPTODO_PLUGIN_PATH . 'public/todo/partials/todo-card.php';
					endforeach;
					?>
				</div>

				<?php
				// Server-side pager for the All panel.
				$pager = array(
					'param'       => 'todo_page',
					'current'     => $all_result['page'],
					'total_pages' => $all_result['total_pages'],
					'label'       => __( 'All todos pagination', 'wb-todo' ),
				);
				include BPTODO_PLUGIN_PATH . 'public/todo/partials/pagination.php';
				?>
			<?php endif; ?>
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
				$context    = 'member';
				$filter     = 'complete';
				$can_create = false;
				include BPTODO_PLUGIN_PATH . 'public/todo/partials/empty-state.php';
				?>
			<?php else : ?>
				<div class="bptodo-list bptodo-list--grid" role="list">
					<?php
					foreach ( $completed_todos as $todo ) :
						$todo_gp_id    = get_post_meta( $todo->ID, 'todo_group_id', true );
						$is_group_todo = ! empty( $todo_gp_id );

						$can_edit      = false; // Completed todos can't be edited.
						$can_delete    = ! $is_group_todo;
						$context       = 'member';
						$show_checkbox = true;
						include BPTODO_PLUGIN_PATH . 'public/todo/partials/todo-card.php';
					endforeach;
					?>
				</div>

				<?php
				// Server-side pager for the Completed panel.
				$pager = array(
					'param'       => 'done_page',
					'current'     => $completed_result['page'],
					'total_pages' => $completed_result['total_pages'],
					'label'       => __( 'Completed todos pagination', 'wb-todo' ),
				);
				include BPTODO_PLUGIN_PATH . 'public/todo/partials/pagination.php';
				?>
			<?php endif; ?>
		</div>

		<?php do_action( 'bptodo_add_extra_tab_content_after_defaults', $profile_menu_label ); ?>
	<?php endif; ?>
</div>

<!-- Tab Switching and Pagination JavaScript -->
<script>
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		const tabs = document.querySelectorAll('.bptodo-tab');
		const panels = document.querySelectorAll('.bptodo-tab-panel');

		// Tab switching with keyboard support.
		tabs.forEach(function(tab) {
			tab.addEventListener('click', function() {
				switchTab(this);
			});

			// Keyboard navigation for tabs.
			tab.addEventListener('keydown', function(e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					switchTab(this);
				}
				if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
					e.preventDefault();
					const tabsArray = Array.from(tabs);
					const currentIndex = tabsArray.indexOf(this);
					let newIndex;
					if (e.key === 'ArrowRight') {
						newIndex = (currentIndex + 1) % tabsArray.length;
					} else {
						newIndex = (currentIndex - 1 + tabsArray.length) % tabsArray.length;
					}
					tabsArray[newIndex].focus();
					switchTab(tabsArray[newIndex]);
				}
			});
		});

		function switchTab(tab) {
			const targetId = 'bptodo-panel-' + tab.dataset.tab;

			// Update tabs.
			tabs.forEach(function(t) {
				t.classList.remove('bptodo-tab--active');
				t.setAttribute('aria-selected', 'false');
				t.setAttribute('tabindex', '-1');
			});
			tab.classList.add('bptodo-tab--active');
			tab.setAttribute('aria-selected', 'true');
			tab.setAttribute('tabindex', '0');

			// Update panels.
			panels.forEach(function(p) {
				if (p.id === targetId) {
					p.hidden = false;
				} else {
					p.hidden = true;
				}
			});
		}

		// Pagination is server-side (see the pagination partial); re-open
		// the Completed tab after following a done_page pagination link.
		if (new URLSearchParams(window.location.search).has('done_page')) {
			const completedTab = document.getElementById('bptodo-tab-completed');
			if (completedTab) {
				switchTab(completedTab);
			}
		}
	});
})();
</script>
