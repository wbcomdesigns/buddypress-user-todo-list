<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $bp, $bptodo;
$profile_menu_slug  = $bptodo->profile_menu_slug;
$profile_menu_label = $bptodo->profile_menu_label;

// Initialize $all_todo_count to avoid warnings
$all_todo_count = 0;
$all_completed_todo = 0;
$avg_rating = 0;

// Function to generate task list
function bptodo_generate_task_list( $tasks, $due_date_text, $is_completed = false ) {
	global $profile_menu_slug;

	foreach ( $tasks as $tid ) {
		$todo          = get_post( $tid );
		$todo_title    = $todo->post_title;
		$todo_edit_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;
		$todo_view_url = get_permalink( $tid );
		$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
		$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
		$priority_class = '';
		$todo_cat       = wp_get_object_terms( $tid, 'todo_category' )[0] ?? null;
		$todo_cat_name  = $todo_cat ? $todo_cat->name : '';
		$checked        = 'complete' === $todo_status ? 'checked' : '';
		$checked_class  = 'complete' === $todo_status ? 'todo-complete' : 'todo-uncomplete';

		if ( $todo_priority ) {
			switch ( $todo_priority ) {
				case 'critical':
					$priority_class = 'bptodo-priority-critical';
					$priority_text  = esc_html__( 'Critical', 'wb-todo' );
					break;
				case 'high':
					$priority_class = 'bptodo-priority-high';
					$priority_text  = esc_html__( 'High', 'wb-todo' );
					break;
				default:
					$priority_class = 'bptodo-priority-normal';
					$priority_text  = esc_html__( 'Normal', 'wb-todo' );
					break;
			}
		}

		echo '<ul id="bptodo-row-' . esc_attr( $tid ) . '" class="todo-header-list-row ' . esc_attr( $checked_class ) . '">';
		echo '<li id="bptodo-complete-li-' . esc_attr( $tid ) . '" class="bptodo-complete-todo bptodo-check ' . esc_attr( $checked_class ) . '" data-tid="' . esc_attr( $tid ) . '" title="' . sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ) . '">';
		echo '<label><input type="checkbox" class="option-input checkbox" ' . esc_attr( $checked ) . ' /></label>';
		echo '</li>';
		echo '<li class="bptodo-priority"><span class="' . esc_attr( $priority_class ) . '">' . esc_html( $priority_text ) . '</span></li>';
		echo '<li class="bptodo-date"><span>' . esc_html__( 'Due Date', 'wb-todo' ) . '</span>' . esc_html( $due_date_text ) . '</li>';
		echo '<li class="bptodo-cat"><span class="bptodo-cat-mobile-title">' . esc_html__( 'Category', 'wb-todo' ) . '</span><span class="bptodo-cat-text">' . esc_html( $todo_cat_name ) . '</span></li>';
		echo '<li class="bptodo-task ' . esc_attr( $checked_class ) . '">' . esc_html( $todo_title ) . '</li>';
		echo '<li class="bp-to-do-actions">';
		echo '<ul class="bp-to-do-actions-list">';
		echo '<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="' . esc_attr( $tid ) . '" title="' . sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ) . '"><img src="' . esc_attr( BPTODO_PLUGIN_URL ) . 'assets/css/images/trash-alt.svg" /></a></li>';
		if ( 'complete' !== $todo_status ) {
			echo '<li><button data-tid="' . esc_attr( $tid ) . '" class="trigger" data-modal-trigger="trigger" title="' . sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ) . '"><img src="' . esc_attr( BPTODO_PLUGIN_URL ) . 'assets/css/images/edit.svg" /></button></li>';
			echo '<li><a href="' . esc_attr( $todo_view_url ) . '" title="' . sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ) . '" target="_blank"><img src="' . esc_attr( BPTODO_PLUGIN_URL ) . 'assets/css/images/eye.svg" /></a></li>';
		}
		echo '</ul></li></ul>';
	}
}

// Display Task Lists
?>

<section class="bptodo-adming-setting">
	<div class="bptodo-admin-settings-block">
		<div class="bptodo-progress-section">
			<h5><?php esc_html_e( 'Task Progress', 'wb-todo' ); ?></h5>
			<div class="task-progress-task-count-wrap">
				<div class="task-progress-task-count">
					<span class="task_breaker-total-tasks"><?php echo esc_html( $all_todo_count ); ?></span>
					<?php echo esc_html( $all_todo_count <= 1 ? $profile_menu_label : $profile_menu_label_plural ); ?>
				</div>
			</div>
			<div class="bptodo-light-grey">
				<span><b><?php echo esc_html( round( $avg_rating, 2 ) ) . '% '; ?></b><?php esc_html_e( 'Completed', 'wb-todo' ); ?></span>
				<div class="bptodo-color" style="height:24px;width:<?php echo esc_attr( $avg_rating ); ?>%"></div>
			</div>
		</div>
		<div id="bptodo-tabs">
			<div id="bptodo-todos">
				<div id="bptodo-task-tabs">
					<div class="bptodo-task-tabs-head">
						<ul class="bptodo-task-tabs-btn">
							<li class="all-bp-todo-list-tab">
								<a href="#bptodo-all">
									<span class="bptodo-btn-icon"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/all-list.svg" /></span>
									<span><?php echo sprintf( esc_html__( 'All %1$s', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?></span>
									<span class="bpdoto-count-tab bp_all_todo_count"><?php echo esc_html( $all_todo_count ); ?></span>
								</a>
							</li>
							<li class="all-bp-todo-list-tab-completed">
								<a href="#bptodo-completed">
									<span class="bptodo-btn-icon"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/check-complete.svg" /></span>
									<span><?php esc_html_e( 'Completed', 'wb-todo' ); ?></span>
									<span class="bpdoto-count-tab bp_completed_todo_count"><?php echo esc_html( $all_completed_todo ); ?></span>
								</a>
							</li>
						</ul>
					</div>
					<div id="bptodo-all">
						<div class="bptodo-admin-row">
							<div class="todo-panel">
								<div class="todo-detail">
									<div class="bp-todo-reminder">
										<div class="todo-header">
											<ul class="todo-header-head">
												<li class="bptodo-check"></li>
												<li class="bptodo-priority"><?php esc_html_e( 'Priority', 'wb-todo' ); ?></li>
												<li class="bptodo-date"><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></li>
												<li class="bptodo-cat"><?php esc_html_e( 'Category', 'wb-todo' ); ?></li>
												<li class="bptodo-task"><?php esc_html_e( 'Task', 'wb-todo' ); ?></li>
												<li class="bptodo-actions"><?php esc_html_e( 'Actions', 'wb-todo' ); ?></li>
											</ul>
											<div class="todo-header-list">
												<?php
													// Generate task lists for different timeframes
													bptodo_generate_task_list( $todo_list['past'] ?? [], esc_html__( 'Expired!', 'wb-todo' ) );
													bptodo_generate_task_list( $todo_list['today'] ?? [], esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' ) );
													bptodo_generate_task_list( $todo_list['tomorrow'] ?? [], esc_html__( '1 day left', 'wb-todo' ) );
													bptodo_generate_task_list( $todo_list['future'] ?? [], esc_html__( 'Future Task', 'wb-todo' ) );
												?>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div id="bptodo-completed">
						<div class="bptodo-admin-row">
							<div class="todo-panel">
								<div class="todo-detail">
									<div class="bp-todo-reminder">
										<div class="todo-header">
											<ul class="todo-header-head">
												<li class="bptodo-check"></li>
												<li class="bptodo-priority"><?php esc_html_e( 'Priority', 'wb-todo' ); ?></li>
												<li class="bptodo-date"><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></li>
												<li class="bptodo-cat"><?php esc_html_e( 'Category', 'wb-todo' ); ?></li>
												<li class="bptodo-task"><?php esc_html_e( 'Task', 'wb-todo' ); ?></li>
												<li class="bptodo-actions"><?php esc_html_e( 'Actions', 'wb-todo' ); ?></li>
											</ul>
											<div class="todo-header-list">
												<?php
													// Generate the completed task list
													bptodo_generate_task_list( $completed_todo_ids ?? [], esc_html__( 'Completed', 'wb-todo' ), true );
												?>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php do_action( 'bptodo_add_extra_tab_content_after_defaults', $profile_menu_label ); ?>
			</div>
		</div>
	</div>
</section>