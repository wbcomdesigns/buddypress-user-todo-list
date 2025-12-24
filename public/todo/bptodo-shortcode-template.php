<?php
/**
 * BuddyPress Todo List Shortcode Template
 *
 * Unified template for [bptodo_list] shortcode with full parameter support.
 * Supports user_id, category, status, priority, per_page, orderby, order.
 *
 * @package bp-user-todo-list
 * @since 3.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $bptodo;

$bptodo_menu_label        = isset( $bptodo->profile_menu_label ) ? $bptodo->profile_menu_label : 'Todo';
$bptodo_menu_slug         = isset( $bptodo->profile_menu_slug ) ? $bptodo->profile_menu_slug : 'todo';
$bptodo_menu_label_plural = isset( $bptodo->profile_menu_label_plural ) ? $bptodo->profile_menu_label_plural : 'Todos';

// Get shortcode attributes (passed from the shortcode handler).
$bptodo_user_id   = isset( $atts['user_id'] ) ? $atts['user_id'] : 0;
$bptodo_category  = isset( $atts['category'] ) ? $atts['category'] : '';
$bptodo_status    = isset( $atts['status'] ) ? $atts['status'] : 'all';
$bptodo_priority  = isset( $atts['priority'] ) ? $atts['priority'] : 'all';
$bptodo_per_page  = isset( $atts['per_page'] ) ? absint( $atts['per_page'] ) : 10;
$bptodo_orderby   = isset( $atts['orderby'] ) ? $atts['orderby'] : 'due_date';
$bptodo_order     = isset( $atts['order'] ) ? strtoupper( $atts['order'] ) : 'ASC';
$bptodo_show_add  = isset( $atts['show_add'] ) && 'yes' === $atts['show_add'];
$bptodo_paged     = isset( $_GET['todo_page'] ) ? absint( $_GET['todo_page'] ) : 1;

// Determine which user's todos to show.
$bptodo_current_user_id = get_current_user_id();
$bptodo_is_admin        = current_user_can( 'manage_options' );

if ( 'all' === $bptodo_user_id ) {
	// Show all users' todos (admin only).
	if ( ! $bptodo_is_admin ) {
		echo '<div class="bptodo-notice bptodo-notice--error">';
		esc_html_e( 'You do not have permission to view all todos.', 'wb-todo' );
		echo '</div>';
		return;
	}
	$bptodo_author_query = '';
} elseif ( ! empty( $bptodo_user_id ) && is_numeric( $bptodo_user_id ) ) {
	// Show specific user's todos.
	$bptodo_author_query = absint( $bptodo_user_id );
	// Check permission - only admin or the user themselves can view.
	if ( ! $bptodo_is_admin && $bptodo_author_query !== $bptodo_current_user_id ) {
		echo '<div class="bptodo-notice bptodo-notice--error">';
		esc_html_e( 'You do not have permission to view this user\'s todos.', 'wb-todo' );
		echo '</div>';
		return;
	}
} else {
	// Default: show current user's todos.
	$bptodo_author_query = $bptodo_current_user_id;
}

// Build the query args.
$bptodo_args = array(
	'post_type'      => 'bp-todo',
	'post_status'    => 'publish',
	'posts_per_page' => $bptodo_per_page,
	'paged'          => $bptodo_paged,
);

// Author filter.
if ( ! empty( $bptodo_author_query ) ) {
	$bptodo_args['author'] = $bptodo_author_query;
}

// Category filter.
if ( ! empty( $bptodo_category ) ) {
	// Support both ID and slug.
	$bptodo_tax_field = is_numeric( $bptodo_category ) ? 'term_id' : 'slug';
	$bptodo_args['tax_query'] = array(
		array(
			'taxonomy' => 'todo_category',
			'field'    => $bptodo_tax_field,
			'terms'    => $bptodo_category,
		),
	);
}

// Status filter (complete/incomplete).
if ( 'complete' === $bptodo_status ) {
	$bptodo_args['meta_query'][] = array(
		'key'   => 'todo_status',
		'value' => 'complete',
	);
} elseif ( 'incomplete' === $bptodo_status ) {
	$bptodo_args['meta_query'][] = array(
		'relation' => 'OR',
		array(
			'key'   => 'todo_status',
			'value' => 'incomplete',
		),
		array(
			'key'     => 'todo_status',
			'compare' => 'NOT EXISTS',
		),
	);
}

// Priority filter.
if ( 'all' !== $bptodo_priority && ! empty( $bptodo_priority ) ) {
	$bptodo_args['meta_query'][] = array(
		'key'   => 'todo_priority',
		'value' => sanitize_text_field( $bptodo_priority ),
	);
}

// Orderby.
switch ( $bptodo_orderby ) {
	case 'title':
		$bptodo_args['orderby'] = 'title';
		$bptodo_args['order']   = $bptodo_order;
		break;
	case 'priority':
		$bptodo_args['meta_key'] = 'todo_priority';
		$bptodo_args['orderby']  = 'meta_value';
		$bptodo_args['order']    = $bptodo_order;
		break;
	case 'date':
		$bptodo_args['orderby'] = 'date';
		$bptodo_args['order']   = $bptodo_order;
		break;
	case 'due_date':
	default:
		$bptodo_args['meta_key'] = 'todo_due_date';
		$bptodo_args['orderby']  = 'meta_value';
		$bptodo_args['order']    = $bptodo_order;
		break;
}

// Run the query.
$bptodo_todos_query = new WP_Query( $bptodo_args );
$bptodo_todos       = $bptodo_todos_query->posts;
$bptodo_total_pages = $bptodo_todos_query->max_num_pages;
$bptodo_total_count = $bptodo_todos_query->found_posts;

// Get unique ID for this shortcode instance (for multiple on same page).
$bptodo_instance_id = 'bptodo-shortcode-' . wp_rand( 1000, 9999 );
?>

<div class="bptodo-wrapper bptodo-shortcode-wrapper"
	 id="<?php echo esc_attr( $bptodo_instance_id ); ?>"
	 role="region"
	 aria-label="<?php esc_attr_e( 'Todo List', 'wb-todo' ); ?>">
	<?php if ( $bptodo_show_add && is_user_logged_in() ) : ?>
		<!-- Quick Add Form -->
		<div class="bptodo-shortcode-header">
			<h3 class="bptodo-shortcode-title">
				<?php echo esc_html( $bptodo_menu_label_plural ); ?>
				<span class="bptodo-count">(<?php echo esc_html( $bptodo_total_count ); ?>)</span>
			</h3>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $bptodo_todos ) ) : ?>
		<!-- Filters Bar -->
		<div class="bptodo-filters">
			<div class="bptodo-filters__info">
				<?php
				printf(
					/* translators: 1: current page, 2: total pages, 3: total items */
					esc_html__( 'Page %1$d of %2$d (%3$d items)', 'wb-todo' ),
					absint( $bptodo_paged ),
					absint( $bptodo_total_pages ),
					absint( $bptodo_total_count )
				);
				?>
			</div>
		</div>

		<!-- Todo Cards Grid -->
		<div class="bptodo-cards bptodo-cards--shortcode"
			 role="list"
			 aria-label="<?php esc_attr_e( 'Todo items', 'wb-todo' ); ?>">
			<?php foreach ( $bptodo_todos as $bptodo_item ) : ?>
				<?php
				$bptodo_todo_id       = $bptodo_item->ID;
				$bptodo_todo_title    = $bptodo_item->post_title;
				$bptodo_todo_content  = $bptodo_item->post_content;
				$bptodo_todo_status   = get_post_meta( $bptodo_todo_id, 'todo_status', true );
				$bptodo_todo_priority = get_post_meta( $bptodo_todo_id, 'todo_priority', true );
				$bptodo_due_date = get_post_meta( $bptodo_todo_id, 'todo_due_date', true );
				$bptodo_todo_author   = get_the_author_meta( 'display_name', $bptodo_item->post_author );
				$bptodo_is_complete   = 'complete' === $bptodo_todo_status;

				// Calculate due date status.
				$bptodo_due_class = '';
				$bptodo_due_text  = '';
				if ( ! empty( $bptodo_due_date ) ) {
					$bptodo_today     = new DateTime( 'today' );
					$bptodo_due       = new DateTime( $bptodo_due_date );
					$bptodo_diff      = $bptodo_today->diff( $bptodo_due );
					$bptodo_days_diff = (int) $bptodo_diff->format( '%r%a' );

					if ( $bptodo_is_complete ) {
						$bptodo_due_class = 'bptodo-due--completed';
						$bptodo_due_text  = __( 'Completed', 'wb-todo' );
					} elseif ( $bptodo_days_diff < 0 ) {
						$bptodo_due_class = 'bptodo-due--overdue';
						/* translators: %d: number of days */
						$bptodo_due_text = sprintf( __( '%d days overdue', 'wb-todo' ), abs( $bptodo_days_diff ) );
					} elseif ( 0 === $bptodo_days_diff ) {
						$bptodo_due_class = 'bptodo-due--today';
						$bptodo_due_text  = __( 'Due today', 'wb-todo' );
					} elseif ( 1 === $bptodo_days_diff ) {
						$bptodo_due_class = 'bptodo-due--tomorrow';
						$bptodo_due_text  = __( 'Due tomorrow', 'wb-todo' );
					} else {
						$bptodo_due_class = 'bptodo-due--future';
						/* translators: %d: number of days */
						$bptodo_due_text = sprintf( __( 'Due in %d days', 'wb-todo' ), $bptodo_days_diff );
					}
				}

				// Priority class.
				$bptodo_priority_class = ! empty( $bptodo_todo_priority ) ? 'bptodo-priority--' . esc_attr( $bptodo_todo_priority ) : '';

				// Get category.
				$bptodo_categories = wp_get_post_terms( $bptodo_todo_id, 'todo_category' );
				$bptodo_cat_name   = ! empty( $bptodo_categories ) && ! is_wp_error( $bptodo_categories ) ? $bptodo_categories[0]->name : '';
				?>
				<article class="bptodo-card <?php echo $bptodo_is_complete ? 'bptodo-card--completed' : ''; ?> <?php echo esc_attr( $bptodo_priority_class ); ?>"
					 role="listitem"
					 data-todo-id="<?php echo esc_attr( $bptodo_todo_id ); ?>"
					 id="bptodo-card-<?php echo esc_attr( $bptodo_todo_id ); ?>"
					 aria-labelledby="todo-title-<?php echo esc_attr( $bptodo_todo_id ); ?>">

					<!-- Card Header -->
					<div class="bptodo-card__header">
						<div class="bptodo-card__checkbox">
							<input type="checkbox"
								   id="todo-check-<?php echo esc_attr( $bptodo_todo_id ); ?>"
								   class="bptodo-checkbox js-bptodo-toggle-complete"
								   data-todo-id="<?php echo esc_attr( $bptodo_todo_id ); ?>"
								   <?php checked( $bptodo_is_complete ); ?>>
							<label for="todo-check-<?php echo esc_attr( $bptodo_todo_id ); ?>" class="bptodo-checkbox-label">
								<span class="screen-reader-text"><?php esc_html_e( 'Mark as complete', 'wb-todo' ); ?></span>
							</label>
						</div>
						<div class="bptodo-card__title-wrap">
							<h4 class="bptodo-card__title <?php echo $bptodo_is_complete ? 'bptodo-card__title--completed' : ''; ?>"
							id="todo-title-<?php echo esc_attr( $bptodo_todo_id ); ?>">
								<?php echo esc_html( $bptodo_todo_title ); ?>
							</h4>
							<?php if ( ! empty( $bptodo_cat_name ) ) : ?>
								<span class="bptodo-card__category"><?php echo esc_html( $bptodo_cat_name ); ?></span>
							<?php endif; ?>
						</div>
						<?php if ( ! empty( $bptodo_todo_priority ) ) : ?>
							<span class="bptodo-badge bptodo-badge--<?php echo esc_attr( $bptodo_todo_priority ); ?>">
								<?php echo esc_html( ucfirst( $bptodo_todo_priority ) ); ?>
							</span>
						<?php endif; ?>
					</div>

					<!-- Card Body -->
					<?php if ( ! empty( $bptodo_todo_content ) ) : ?>
						<div class="bptodo-card__body">
							<p class="bptodo-card__description"><?php echo esc_html( wp_trim_words( $bptodo_todo_content, 20 ) ); ?></p>
						</div>
					<?php endif; ?>

					<!-- Card Footer -->
					<div class="bptodo-card__footer">
						<div class="bptodo-card__meta">
							<?php if ( ! empty( $bptodo_due_date ) ) : ?>
								<span class="bptodo-card__due <?php echo esc_attr( $bptodo_due_class ); ?>">
									<svg class="bptodo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
										<line x1="16" y1="2" x2="16" y2="6"></line>
										<line x1="8" y1="2" x2="8" y2="6"></line>
										<line x1="3" y1="10" x2="21" y2="10"></line>
									</svg>
									<?php echo esc_html( $bptodo_due_text ); ?>
								</span>
							<?php endif; ?>
							<?php if ( 'all' === $bptodo_user_id && $bptodo_is_admin ) : ?>
								<span class="bptodo-card__author">
									<svg class="bptodo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
										<circle cx="12" cy="7" r="4"></circle>
									</svg>
									<?php echo esc_html( $bptodo_todo_author ); ?>
								</span>
							<?php endif; ?>
						</div>
						<?php if ( ! $bptodo_is_complete && ( $bptodo_is_admin || $bptodo_item->post_author === $bptodo_current_user_id ) ) : ?>
							<div class="bptodo-card__actions">
								<button type="button"
										class="bptodo-btn bptodo-btn--icon js-bptodo-edit"
										data-todo-id="<?php echo esc_attr( $bptodo_todo_id ); ?>"
										aria-label="<?php esc_attr_e( 'Edit todo', 'wb-todo' ); ?>">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
										<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
									</svg>
								</button>
								<button type="button"
										class="bptodo-btn bptodo-btn--icon bptodo-btn--danger js-bptodo-delete"
										data-todo-id="<?php echo esc_attr( $bptodo_todo_id ); ?>"
										aria-label="<?php esc_attr_e( 'Delete todo', 'wb-todo' ); ?>">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<polyline points="3 6 5 6 21 6"></polyline>
										<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
									</svg>
								</button>
							</div>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>

		<?php if ( $bptodo_total_pages > 1 ) : ?>
			<!-- Pagination -->
			<nav class="bptodo-pagination"
				 role="navigation"
				 aria-label="<?php esc_attr_e( 'Todo list pagination', 'wb-todo' ); ?>">
				<?php
				$bptodo_base_url = remove_query_arg( 'todo_page' );

				// Previous button.
				if ( $bptodo_paged > 1 ) :
					$bptodo_prev_url = add_query_arg( 'todo_page', $bptodo_paged - 1, $bptodo_base_url );
					?>
					<a href="<?php echo esc_url( $bptodo_prev_url ); ?>" class="bptodo-pagination__btn bptodo-pagination__btn--prev">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<polyline points="15 18 9 12 15 6"></polyline>
						</svg>
						<?php esc_html_e( 'Previous', 'wb-todo' ); ?>
					</a>
				<?php else : ?>
					<span class="bptodo-pagination__btn bptodo-pagination__btn--prev bptodo-pagination__btn--disabled">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<polyline points="15 18 9 12 15 6"></polyline>
						</svg>
						<?php esc_html_e( 'Previous', 'wb-todo' ); ?>
					</span>
				<?php endif; ?>

				<!-- Page numbers -->
				<div class="bptodo-pagination__numbers">
					<?php
					$bptodo_range = 2; // Pages to show on each side of current.
					for ( $bptodo_i = 1; $bptodo_i <= $bptodo_total_pages; $bptodo_i++ ) :
						if ( $bptodo_i === 1 || $bptodo_i === $bptodo_total_pages || ( $bptodo_i >= $bptodo_paged - $bptodo_range && $bptodo_i <= $bptodo_paged + $bptodo_range ) ) :
							$bptodo_page_url = add_query_arg( 'todo_page', $bptodo_i, $bptodo_base_url );
							if ( $bptodo_i === $bptodo_paged ) :
								?>
								<span class="bptodo-pagination__num bptodo-pagination__num--current"
									  aria-current="page"><?php echo esc_html( $bptodo_i ); ?></span>
							<?php else : ?>
								<a href="<?php echo esc_url( $bptodo_page_url ); ?>" class="bptodo-pagination__num"><?php echo esc_html( $bptodo_i ); ?></a>
								<?php
							endif;
						elseif ( $bptodo_i === $bptodo_paged - $bptodo_range - 1 || $bptodo_i === $bptodo_paged + $bptodo_range + 1 ) :
							?>
							<span class="bptodo-pagination__ellipsis">...</span>
							<?php
						endif;
					endfor;
					?>
				</div>

				<!-- Next button -->
				<?php if ( $bptodo_paged < $bptodo_total_pages ) :
					$bptodo_next_url = add_query_arg( 'todo_page', $bptodo_paged + 1, $bptodo_base_url );
					?>
					<a href="<?php echo esc_url( $bptodo_next_url ); ?>" class="bptodo-pagination__btn bptodo-pagination__btn--next">
						<?php esc_html_e( 'Next', 'wb-todo' ); ?>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<polyline points="9 18 15 12 9 6"></polyline>
						</svg>
					</a>
				<?php else : ?>
					<span class="bptodo-pagination__btn bptodo-pagination__btn--next bptodo-pagination__btn--disabled">
						<?php esc_html_e( 'Next', 'wb-todo' ); ?>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<polyline points="9 18 15 12 9 6"></polyline>
						</svg>
					</span>
				<?php endif; ?>
			</nav>
		<?php endif; ?>

	<?php else : ?>
		<!-- Empty State -->
		<div class="bptodo-empty-state">
			<div class="bptodo-empty-state__icon">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
					<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
				</svg>
			</div>
			<h3 class="bptodo-empty-state__title">
				<?php
				printf(
					/* translators: %s: todo label plural */
					esc_html__( 'No %s Found', 'wb-todo' ),
					esc_html( $bptodo_menu_label_plural )
				);
				?>
			</h3>
			<p class="bptodo-empty-state__text">
				<?php
				if ( ! empty( $bptodo_category ) || 'all' !== $bptodo_status || 'all' !== $bptodo_priority ) {
					esc_html_e( 'Try adjusting your filters to see more results.', 'wb-todo' );
				} else {
					printf(
						/* translators: %s: todo label plural */
						esc_html__( 'There are no %s to display.', 'wb-todo' ),
						esc_html( strtolower( $bptodo_menu_label_plural ) )
					);
				}
				?>
			</p>
		</div>
	<?php endif; ?>
</div>
