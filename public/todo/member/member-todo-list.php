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

// Fetch todos with intelligent sorting.
$args = array(
	'post_type'      => 'bp-todo',
	'post_status'    => 'publish',
	'author'         => get_current_user_id(),
	'posts_per_page' => apply_filters( 'bptodo_member_list_limit', 500 ),
);
$todos = get_posts( $args );

// Sort todos: Incomplete first, then by priority (Critical > High > Normal), then by due date.
usort(
	$todos,
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

		// Sort by due date (earliest first).
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

		// Fall back to post date (newest first).
		return strtotime( $b->post_date ) - strtotime( $a->post_date );
	}
);

// Calculate statistics.
$all_todo_count     = count( $todos );
$all_completed_todo = 0;
$all_pending_todo   = 0;
$pending_todos      = array();
$completed_todos    = array();

foreach ( $todos as $todo ) {
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

// Determine current user permissions.
$current_user_id = get_current_user_id();
?>

<div class="bptodo-wrapper" id="bptodo-member-list">
	<?php if ( empty( $todos ) ) : ?>
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

		<?php
		// Pagination settings.
		$per_page = apply_filters( 'bptodo_member_list_per_page', 12 );
		?>

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
				<div class="bptodo-list bptodo-list--grid js-bptodo-paginated-list" role="list" data-per-page="<?php echo esc_attr( $per_page ); ?>">
					<?php
					foreach ( $todos as $index => $todo ) :
						// Determine if this is a group todo.
						$todo_gp_id   = get_post_meta( $todo->ID, 'todo_group_id', true );
						$is_group_todo = ! empty( $todo_gp_id );

						// Set permissions based on todo ownership.
						$can_edit   = ! $is_group_todo; // Can only edit personal todos.
						$can_delete = ! $is_group_todo; // Can only delete personal todos.

						// Include the card partial.
						$context       = 'member';
						$show_checkbox = true;
						// Add data attribute for pagination.
						$item_index = $index;
						include BPTODO_PLUGIN_PATH . 'public/todo/partials/todo-card.php';
					endforeach;
					?>
				</div>

				<?php if ( count( $todos ) > $per_page ) : ?>
					<!-- Pagination for All Todos -->
					<div class="bptodo-pagination js-bptodo-pagination" data-target="bptodo-panel-all" data-total="<?php echo esc_attr( count( $todos ) ); ?>" data-per-page="<?php echo esc_attr( $per_page ); ?>">
						<button type="button" class="bptodo-pagination__btn bptodo-pagination__btn--prev js-bptodo-page-prev" disabled>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<polyline points="15 18 9 12 15 6"></polyline>
							</svg>
							<?php esc_html_e( 'Previous', 'wb-todo' ); ?>
						</button>
						<div class="bptodo-pagination__info">
							<span class="js-bptodo-page-current">1</span> / <span class="js-bptodo-page-total"><?php echo esc_html( ceil( count( $todos ) / $per_page ) ); ?></span>
						</div>
						<button type="button" class="bptodo-pagination__btn bptodo-pagination__btn--next js-bptodo-page-next">
							<?php esc_html_e( 'Next', 'wb-todo' ); ?>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<polyline points="9 18 15 12 9 6"></polyline>
							</svg>
						</button>
					</div>
				<?php endif; ?>
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
				<div class="bptodo-list bptodo-list--grid js-bptodo-paginated-list" role="list" data-per-page="<?php echo esc_attr( $per_page ); ?>">
					<?php
					foreach ( $completed_todos as $index => $todo ) :
						$todo_gp_id    = get_post_meta( $todo->ID, 'todo_group_id', true );
						$is_group_todo = ! empty( $todo_gp_id );

						$can_edit      = false; // Completed todos can't be edited.
						$can_delete    = ! $is_group_todo;
						$context       = 'member';
						$show_checkbox = true;
						$item_index    = $index;
						include BPTODO_PLUGIN_PATH . 'public/todo/partials/todo-card.php';
					endforeach;
					?>
				</div>

				<?php if ( count( $completed_todos ) > $per_page ) : ?>
					<!-- Pagination for Completed Todos -->
					<div class="bptodo-pagination js-bptodo-pagination" data-target="bptodo-panel-completed" data-total="<?php echo esc_attr( count( $completed_todos ) ); ?>" data-per-page="<?php echo esc_attr( $per_page ); ?>">
						<button type="button" class="bptodo-pagination__btn bptodo-pagination__btn--prev js-bptodo-page-prev" disabled>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<polyline points="15 18 9 12 15 6"></polyline>
							</svg>
							<?php esc_html_e( 'Previous', 'wb-todo' ); ?>
						</button>
						<div class="bptodo-pagination__info">
							<span class="js-bptodo-page-current">1</span> / <span class="js-bptodo-page-total"><?php echo esc_html( ceil( count( $completed_todos ) / $per_page ) ); ?></span>
						</div>
						<button type="button" class="bptodo-pagination__btn bptodo-pagination__btn--next js-bptodo-page-next">
							<?php esc_html_e( 'Next', 'wb-todo' ); ?>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<polyline points="9 18 15 12 9 6"></polyline>
							</svg>
						</button>
					</div>
				<?php endif; ?>
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

		// Pagination functionality.
		initPagination();

		function initPagination() {
			const paginationContainers = document.querySelectorAll('.js-bptodo-pagination');

			paginationContainers.forEach(function(pagination) {
				const targetId = pagination.dataset.target;
				const panel = document.getElementById(targetId);
				if (!panel) return;

				const list = panel.querySelector('.js-bptodo-paginated-list');
				if (!list) return;

				const items = list.querySelectorAll('.bptodo-card');
				const perPage = parseInt(pagination.dataset.perPage, 10) || 12;
				const totalItems = items.length;
				const totalPages = Math.ceil(totalItems / perPage);
				let currentPage = 1;

				const prevBtn = pagination.querySelector('.js-bptodo-page-prev');
				const nextBtn = pagination.querySelector('.js-bptodo-page-next');
				const currentSpan = pagination.querySelector('.js-bptodo-page-current');
				const totalSpan = pagination.querySelector('.js-bptodo-page-total');

				// Initial render.
				showPage(1);

				// Event listeners.
				if (prevBtn) {
					prevBtn.addEventListener('click', function() {
						if (currentPage > 1) {
							showPage(currentPage - 1);
						}
					});
				}

				if (nextBtn) {
					nextBtn.addEventListener('click', function() {
						if (currentPage < totalPages) {
							showPage(currentPage + 1);
						}
					});
				}

				function showPage(page) {
					currentPage = page;
					const start = (page - 1) * perPage;
					const end = start + perPage;

					items.forEach(function(item, index) {
						if (index >= start && index < end) {
							item.style.display = '';
							item.removeAttribute('hidden');
						} else {
							item.style.display = 'none';
							item.setAttribute('hidden', '');
						}
					});

					// Update UI.
					if (currentSpan) currentSpan.textContent = page;
					if (totalSpan) totalSpan.textContent = totalPages;

					if (prevBtn) {
						prevBtn.disabled = page <= 1;
						prevBtn.classList.toggle('bptodo-pagination__btn--disabled', page <= 1);
					}

					if (nextBtn) {
						nextBtn.disabled = page >= totalPages;
						nextBtn.classList.toggle('bptodo-pagination__btn--disabled', page >= totalPages);
					}

					// Scroll to top of list.
					if (page !== 1 || totalItems > perPage) {
						list.scrollIntoView({ behavior: 'smooth', block: 'start' });
					}
				}
			});
		}
	});
})();
</script>
