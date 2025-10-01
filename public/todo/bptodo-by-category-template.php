<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $bptodo;
$profile_menu_label        = $bptodo->profile_menu_label;
$profile_menu_slug         = $bptodo->profile_menu_slug;
$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
$class                     = 'todo-completed';
$bp_template_option        = bp_get_option( '_bp_theme_package_id' );

/**
 * Function to display todo tasks in a table format based on the category (e.g., past, today, tomorrow, future)
 *
 * @param array  $todo_list List of todo task IDs.
 * @param string $category  The category of tasks being displayed.
 * @param string $label     The label to display for this category.
 */
function bptodo_display_todo_tasks( $todo_list, $category, $label, $profile_menu_slug, $class ) {
	if ( ! empty( $todo_list[ $category ] ) ) { ?>
		<div class="bptodo-admin-row">
			<div>
				<button class="bptodo-item"><?php echo esc_html( strtoupper( $label ), 'wb-todo' ); ?></button>
				<div class="bptodo-panel">
					<div class="todo-detail">
						<table class="bp-todo-reminder">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Sr. No.', 'wb-todo' ); ?></th>
									<th><?php esc_html_e( 'Task', 'wb-todo' ); ?></th>
									<th><?php esc_html_e( 'Task Description', 'wb-todo' ); ?></th>
									<th><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></th>
									<th><?php esc_html_e( 'Mark Complete', 'wb-todo' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php $count = 1; ?>
								<?php foreach ( $todo_list[ $category ] as $tid ) { ?>
									<?php
									$todo         = get_post( $tid );
									$todo_title   = $todo->post_title;
									$todo_content = $todo->post_content;
									$todo_status  = get_post_meta( $todo->ID, 'todo_status', true );

									$todo_edit_url = bp_core_get_userlink( get_current_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

									$due_date_str     = '';
									$due_date_td_class = '';
									$curr_date        = date_create( gmdate( 'Y-m-d' ) );
									$due_date         = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
									$diff             = date_diff( $curr_date, $due_date );
									$diff_days        = $diff->format( '%R%a' );

									if ( $diff_days < 0 ) {
										 /* translators: %s */
										$due_date_str      = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
										$due_date_td_class = 'bptodo-expired';
									} elseif ( 0 === $diff_days ) {
										$due_date_str      = esc_html_e( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
										$due_date_td_class = 'bptodo-expires-today';
									} else {
										 /* translators: %s */
										$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
									}

									if ( 'complete' === $todo_status ) {
										$due_date_str      = esc_html_e( 'Completed!', 'wb-todo' );
										$due_date_td_class = '';
									}
									?>
									<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
										<td class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $count++ ); ?></td>
										<td class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $todo_title ); ?></td>
										<td class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $todo_content ); ?></td>
										<td class="<?php echo esc_attr( $due_date_td_class . ' ' . $class ); ?>"><?php echo esc_html( $due_date_str ); ?></td>
										<td class="bp-to-do-actions">
											<ul>
													<?php /* translators: %s */ ?>
													<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-times"></i></a></li>
													<?php if ( 'complete' !== $todo_status ) { ?>
													<?php /* translators: %s */ ?>
													<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-edit"></i></a></li>
													<?php /* translators: %s */ ?>
													<li><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-check"></i></a></li>
												<?php } else { ?>
													<?php /* translators: %s */ ?>
													<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-undo"></i></a></li>
												<?php } ?>
											</ul>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	<?php }
}

// Example usage of the bptodo_display_todo_tasks function:
if ( ! empty( $atts['category'] ) ) {
	$term = get_term_by( 'id', $atts['category'], 'todo_category' );
	if ( ! empty( $term ) ) {
		$args  = array(
			'post_type'   => 'bp-todo',
			'numberposts' => 10,
			'author'      => get_current_user_id(),
			'tax_query'   => array(
				array(
					'taxonomy'         => 'todo_category',
					'field'            => 'id',
					'terms'            => $atts['category'],
					'include_children' => true,
				),
			),
		);
		$todos = get_posts( $args );

		// Intelligent sorting: Priority (Critical > High > Normal) + Due Date (earliest first) + Status (incomplete first)
		usort( $todos, function( $a, $b ) {
			$a_status   = get_post_meta( $a->ID, 'todo_status', true );
			$b_status   = get_post_meta( $b->ID, 'todo_status', true );
			$a_priority = get_post_meta( $a->ID, 'todo_priority', true );
			$b_priority = get_post_meta( $b->ID, 'todo_priority', true );
			$a_due_date = get_post_meta( $a->ID, 'todo_due_date', true );
			$b_due_date = get_post_meta( $b->ID, 'todo_due_date', true );

			// 1. Completed tasks always go to bottom
			if ( $a_status === 'complete' && $b_status !== 'complete' ) {
				return 1;
			}
			if ( $a_status !== 'complete' && $b_status === 'complete' ) {
				return -1;
			}

			// 2. Sort by priority (Critical=3, High=2, Normal=1, Empty=0)
			$priority_map = array( 'critical' => 3, 'high' => 2, 'normal' => 1 );
			$a_priority_val = isset( $priority_map[ $a_priority ] ) ? $priority_map[ $a_priority ] : 0;
			$b_priority_val = isset( $priority_map[ $b_priority ] ) ? $priority_map[ $b_priority ] : 0;

			if ( $a_priority_val !== $b_priority_val ) {
				return $b_priority_val - $a_priority_val; // Higher priority first
			}

			// 3. Sort by due date (earliest first, empty dates last)
			if ( empty( $a_due_date ) && ! empty( $b_due_date ) ) {
				return 1;
			}
			if ( ! empty( $a_due_date ) && empty( $b_due_date ) ) {
				return -1;
			}
			if ( ! empty( $a_due_date ) && ! empty( $b_due_date ) ) {
				$a_timestamp = strtotime( $a_due_date );
				$b_timestamp = strtotime( $b_due_date );
				// Handle invalid dates gracefully
				if ( false === $a_timestamp || false === $b_timestamp ) {
					return 0;
				}
				return $a_timestamp - $b_timestamp;
			}

			// 4. If all else equal, sort by post date (newest first)
			return strtotime( $b->post_date ) - strtotime( $a->post_date );
		} );

		if ( ! empty( $todos ) ) {
			$todo_list = array();
			foreach ( $todos as $todo ) {
				$curr_date = date_create( gmdate( 'Y-m-d' ) );
				$due_date  = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
				$diff      = date_diff( $curr_date, $due_date );
				$diff_days = $diff->format( '%R%a' );

				if ( $diff_days < 0 ) {
					$todo_list['past'][] = $todo->ID;
				} elseif ( 0 === $diff_days ) {
					$todo_list['today'][] = $todo->ID;
				} elseif ( 1 === $diff_days ) {
					$todo_list['tomorrow'][] = $todo->ID;
				} else {
					$todo_list['future'][] = $todo->ID;
				}
			}

			// Display the tasks based on their due dates using the function
			?>
			<div class="bptodo-adming-setting">
				<div class="bptodo-admin-settings-block">
					<div id="bptodo-settings-tbl">
						<?php
						bptodo_display_todo_tasks( $todo_list, 'past', 'PAST', $profile_menu_slug, $class );
						bptodo_display_todo_tasks( $todo_list, 'today', 'TODAY', $profile_menu_slug, $class );
						bptodo_display_todo_tasks( $todo_list, 'tomorrow', 'TOMORROW', $profile_menu_slug, $class );
						bptodo_display_todo_tasks( $todo_list, 'future', 'FUTURE', $profile_menu_slug, $class );
						?>
					</div>
				</div>
			</div>
			<?php
		} else {
			if ( 'nouveau' === $bp_template_option ) {
				?>
				<div id="message" class="info bp-feedback bp-messages bp-template-notice">
					<span class="bp-icon" aria-hidden="true"></span>
				<?php } else { ?>
				<div id="message" class="info">
				<?php } ?>
					<p>
					<?php
					/* Translators: 1) Profile Menu Plural Label */
					echo sprintf( esc_html__( 'There are no %s in this category.', 'wb-todo' ), esc_html( $profile_menu_label_plural ) );
					?>
					</p>
				</div>
				<?php
		}
	} else {
		if ( 'nouveau' === $bp_template_option ) {
			?>
			<div id="message" class="info bp-feedback bp-messages bp-template-notice">
				<span class="bp-icon" aria-hidden="true"></span>
		<?php } else { ?>
			<div id="message" class="info">
		<?php } ?>
			<p><?php esc_html_e( 'Please provide a valid category ID.', 'wb-todo' ); ?></p>
		</div>
		<?php
	}
} else {
	if ( 'nouveau' === $bp_template_option ) {
		?>
			<div id="message" class="info bp-feedback bp-messages bp-template-notice">
				<span class="bp-icon" aria-hidden="true"></span>
		<?php } else { ?>
			<div id="message" class="info">
		<?php } ?>
		<p><?php esc_html_e( 'Please provide any category ID.', 'wb-todo' ); ?></p>
	</div>
	<?php
}
?>
