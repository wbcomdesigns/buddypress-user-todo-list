<?php
/**
 * Todo Card Partial
 *
 * Reusable template for displaying a single todo item as a card.
 * This partial replaces the duplicated todo row markup across member and group lists.
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 *
 * Expected variables:
 * @var WP_Post|object $todo           The todo post object
 * @var string         $context        'member' or 'group' - determines available actions
 * @var bool           $can_edit       Whether current user can edit this todo
 * @var bool           $can_delete     Whether current user can delete this todo
 * @var bool           $show_checkbox  Whether to show completion checkbox
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Ensure we have required data.
if ( empty( $todo ) ) {
	return;
}

// Get todo data.
$todo_id       = isset( $todo->ID ) ? $todo->ID : $todo->todo_id;
$todo_title    = isset( $todo->post_title ) ? $todo->post_title : ( isset( $todo->title ) ? $todo->title : '' );
$todo_content  = isset( $todo->post_content ) ? $todo->post_content : ( isset( $todo->content ) ? $todo->content : '' );

// Get meta values.
$todo_priority    = get_post_meta( $todo_id, 'todo_priority', true );
$todo_due_date    = get_post_meta( $todo_id, 'todo_due_date', true );
$todo_status      = get_post_meta( $todo_id, 'todo_status', true );
$todo_category_id = get_post_meta( $todo_id, 'todo_category', true );

// Set defaults.
$context       = isset( $context ) ? $context : 'member';
$can_edit      = isset( $can_edit ) ? $can_edit : false;
$can_delete    = isset( $can_delete ) ? $can_delete : false;
$show_checkbox = isset( $show_checkbox ) ? $show_checkbox : true;

// Determine if completed.
$is_completed = ( 'complete' === $todo_status );

// Build priority data.
$priority_map = array(
	'critical' => array(
		'class' => 'bptodo-badge--critical',
		'label' => esc_html__( 'Critical', 'wb-todo' ),
	),
	'high'     => array(
		'class' => 'bptodo-badge--high',
		'label' => esc_html__( 'High', 'wb-todo' ),
	),
	'normal'   => array(
		'class' => 'bptodo-badge--normal',
		'label' => esc_html__( 'Normal', 'wb-todo' ),
	),
);

$priority_data = isset( $priority_map[ $todo_priority ] ) ? $priority_map[ $todo_priority ] : $priority_map['normal'];

// Get category name if exists.
$category_name = '';
if ( ! empty( $todo_category_id ) ) {
	$category = get_term( $todo_category_id, 'bp_todo_category' );
	if ( $category && ! is_wp_error( $category ) ) {
		$category_name = $category->name;
	}
}

// Calculate due date status.
$due_badge_class = 'bptodo-badge--due';
$due_text        = '';
if ( ! empty( $todo_due_date ) ) {
	$due_timestamp = strtotime( $todo_due_date );
	$today         = strtotime( 'today' );
	$tomorrow      = strtotime( 'tomorrow' );
	$next_week     = strtotime( '+7 days' );

	if ( $due_timestamp < $today && ! $is_completed ) {
		$due_badge_class = 'bptodo-badge--overdue';
		$due_text        = esc_html__( 'Overdue', 'wb-todo' );
	} elseif ( $due_timestamp === $today ) {
		$due_badge_class = 'bptodo-badge--due-soon';
		$due_text        = esc_html__( 'Due today', 'wb-todo' );
	} elseif ( $due_timestamp === $tomorrow ) {
		$due_badge_class = 'bptodo-badge--due-soon';
		$due_text        = esc_html__( 'Due tomorrow', 'wb-todo' );
	} else {
		$due_text = esc_html( wp_date( get_option( 'date_format' ), $due_timestamp ) );
	}
}

// Build card classes.
$card_classes = array( 'bptodo-card' );
if ( $is_completed ) {
	$card_classes[] = 'bptodo-card--completed';
}
if ( ! empty( $todo_priority ) ) {
	$card_classes[] = 'bptodo-card--priority-' . sanitize_html_class( $todo_priority );
}

// Generate nonce for actions.
$nonce = wp_create_nonce( 'bptodo_nonce' );
?>

<article
	class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>"
	data-todo-id="<?php echo esc_attr( $todo_id ); ?>"
	data-context="<?php echo esc_attr( $context ); ?>"
	role="listitem"
>
	<!-- Card Header: Checkbox + Title -->
	<header class="bptodo-card__header">
		<?php if ( $show_checkbox ) : ?>
			<label class="bptodo-checkbox">
				<input
					type="checkbox"
					class="bptodo-checkbox__input js-bptodo-toggle-complete"
					<?php checked( $is_completed ); ?>
					data-todo-id="<?php echo esc_attr( $todo_id ); ?>"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					aria-label="<?php echo $is_completed ? esc_attr__( 'Mark as incomplete', 'wb-todo' ) : esc_attr__( 'Mark as complete', 'wb-todo' ); ?>"
				>
				<span class="bptodo-checkbox__box">
					<svg class="bptodo-checkbox__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="20 6 9 17 4 12"></polyline>
					</svg>
				</span>
			</label>
		<?php endif; ?>

		<h3 class="bptodo-card__title">
			<?php if ( $can_edit ) : ?>
				<a href="<?php echo esc_url( bptodo_get_todo_view_url( $todo_id ) ); ?>">
					<?php echo esc_html( $todo_title ); ?>
				</a>
			<?php else : ?>
				<?php echo esc_html( $todo_title ); ?>
			<?php endif; ?>
		</h3>
	</header>

	<!-- Card Content: Description -->
	<?php if ( ! empty( $todo_content ) ) : ?>
		<div class="bptodo-card__content">
			<p class="bptodo-card__description">
				<?php echo esc_html( wp_trim_words( wp_strip_all_tags( $todo_content ), 20 ) ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Card Meta: Priority, Due Date, Category -->
	<div class="bptodo-card__meta">
		<!-- Priority Badge -->
		<span class="bptodo-badge <?php echo esc_attr( $priority_data['class'] ); ?>">
			<?php echo esc_html( $priority_data['label'] ); ?>
		</span>

		<!-- Due Date Badge -->
		<?php if ( ! empty( $due_text ) ) : ?>
			<span class="bptodo-badge <?php echo esc_attr( $due_badge_class ); ?>">
				<svg class="bptodo-badge__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
					<line x1="16" y1="2" x2="16" y2="6"></line>
					<line x1="8" y1="2" x2="8" y2="6"></line>
					<line x1="3" y1="10" x2="21" y2="10"></line>
				</svg>
				<?php echo esc_html( $due_text ); ?>
			</span>
		<?php endif; ?>

		<!-- Category Badge -->
		<?php if ( ! empty( $category_name ) ) : ?>
			<span class="bptodo-badge bptodo-badge--category">
				<?php echo esc_html( $category_name ); ?>
			</span>
		<?php endif; ?>

		<!-- Status Badge (if completed) -->
		<?php if ( $is_completed ) : ?>
			<span class="bptodo-badge bptodo-badge--completed">
				<?php esc_html_e( 'Completed', 'wb-todo' ); ?>
			</span>
		<?php endif; ?>
	</div>

	<!-- Card Footer: Action Buttons -->
	<footer class="bptodo-card__footer">
		<?php if ( $can_edit ) : ?>
			<button
				type="button"
				class="bptodo-btn bptodo-btn--ghost bptodo-btn--sm js-bptodo-edit"
				data-todo-id="<?php echo esc_attr( $todo_id ); ?>"
				data-nonce="<?php echo esc_attr( $nonce ); ?>"
				aria-label="<?php esc_attr_e( 'Edit todo', 'wb-todo' ); ?>"
			>
				<svg class="bptodo-btn__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
					<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
				</svg>
				<span class="bptodo-hide-mobile"><?php esc_html_e( 'Edit', 'wb-todo' ); ?></span>
			</button>
		<?php endif; ?>

		<?php if ( $can_delete ) : ?>
			<button
				type="button"
				class="bptodo-btn bptodo-btn--ghost bptodo-btn--sm js-bptodo-delete"
				data-todo-id="<?php echo esc_attr( $todo_id ); ?>"
				data-nonce="<?php echo esc_attr( $nonce ); ?>"
				aria-label="<?php esc_attr_e( 'Delete todo', 'wb-todo' ); ?>"
			>
				<svg class="bptodo-btn__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="3 6 5 6 21 6"></polyline>
					<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
				</svg>
				<span class="bptodo-hide-mobile"><?php esc_html_e( 'Delete', 'wb-todo' ); ?></span>
			</button>
		<?php endif; ?>
	</footer>
</article>
