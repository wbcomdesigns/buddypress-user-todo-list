<?php
/**
 * Empty State Partial
 *
 * Displayed when no todos are found. Provides a friendly message
 * and call-to-action for creating new todos.
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 *
 * Expected variables:
 * @var string $context     'member' or 'group' - determines the message
 * @var string $filter      Current filter applied (e.g., 'complete', 'pending')
 * @var bool   $can_create  Whether current user can create todos
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set defaults.
$context    = isset( $context ) ? $context : 'member';
$filter     = isset( $filter ) ? $filter : '';
$can_create = isset( $can_create ) ? $can_create : true;

// Determine message based on context and filter.
$title   = '';
$message = '';

if ( 'group' === $context ) {
	if ( 'complete' === $filter ) {
		$title   = __( 'No completed todos', 'bp-user-to-do-list' );
		$message = __( 'Tasks will appear here once group members mark them as complete.', 'bp-user-to-do-list' );
	} elseif ( 'pending' === $filter ) {
		$title   = __( 'All caught up!', 'bp-user-to-do-list' );
		$message = __( 'There are no pending todos for this group right now.', 'bp-user-to-do-list' );
	} else {
		$title   = __( 'No group todos yet', 'bp-user-to-do-list' );
		$message = __( 'Create the first todo to start collaborating with your group members.', 'bp-user-to-do-list' );
	}
} else {
	if ( 'complete' === $filter ) {
		$title   = __( 'No completed todos', 'bp-user-to-do-list' );
		$message = __( 'Completed todos will appear here. Keep up the great work!', 'bp-user-to-do-list' );
	} elseif ( 'pending' === $filter ) {
		$title   = __( 'All caught up!', 'bp-user-to-do-list' );
		$message = __( 'You have no pending todos. Enjoy your free time!', 'bp-user-to-do-list' );
	} else {
		$title   = __( 'No todos yet', 'bp-user-to-do-list' );
		$message = __( 'Create your first todo to start tracking your tasks.', 'bp-user-to-do-list' );
	}
}
?>

<div class="bptodo-empty" role="status" aria-live="polite">
	<!-- Empty State Icon -->
	<svg class="bptodo-empty__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
		<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
		<polyline points="14 2 14 8 20 8"></polyline>
		<line x1="12" y1="18" x2="12" y2="12"></line>
		<line x1="9" y1="15" x2="15" y2="15"></line>
	</svg>

	<!-- Title -->
	<h3 class="bptodo-empty__title">
		<?php echo esc_html( $title ); ?>
	</h3>

	<!-- Message -->
	<p class="bptodo-empty__message">
		<?php echo esc_html( $message ); ?>
	</p>

	<!-- Call to Action (if can create and not filtering) -->
	<?php if ( $can_create && empty( $filter ) ) : ?>
		<button type="button" class="bptodo-btn bptodo-btn--primary js-bptodo-add-new">
			<svg class="bptodo-btn__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<line x1="12" y1="5" x2="12" y2="19"></line>
				<line x1="5" y1="12" x2="19" y2="12"></line>
			</svg>
			<?php esc_html_e( 'Create Todo', 'bp-user-to-do-list' ); ?>
		</button>
	<?php endif; ?>
</div>
