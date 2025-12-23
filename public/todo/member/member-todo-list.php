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
		const tabs = document.querySelectorAll('.bptodo-tab');
		const panels = document.querySelectorAll('.bptodo-tab-panel');

		tabs.forEach(function(tab) {
			tab.addEventListener('click', function() {
				const targetId = 'bptodo-panel-' + this.dataset.tab;

				// Update tabs.
				tabs.forEach(function(t) {
					t.classList.remove('bptodo-tab--active');
					t.setAttribute('aria-selected', 'false');
				});
				this.classList.add('bptodo-tab--active');
				this.setAttribute('aria-selected', 'true');

				// Update panels.
				panels.forEach(function(p) {
					if (p.id === targetId) {
						p.hidden = false;
					} else {
						p.hidden = true;
					}
				});
			});
		});
	});
})();
</script>
