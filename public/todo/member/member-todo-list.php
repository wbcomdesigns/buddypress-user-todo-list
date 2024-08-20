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

$profile_menu_slug         = $bptodo->profile_menu_slug;
$profile_menu_label        = $bptodo->profile_menu_label;
$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
$class                     = 'todo-completed';

// Fetch todo items for the current user
$args  = array(
	'post_type'      => 'bp-todo',
	'post_status'    => 'publish',
	'author'         => get_current_user_id(),
	'posts_per_page' => -1,
);
$todos = get_posts( $args );

$todo_list          = array();
$all_todo_count     = count( $todos );
$all_completed_todo = 0;
$all_remaining_todo = 0;
$completed_todo_ids = array();

foreach ( $todos as $todo ) {
	$curr_date  = date_create( gmdate( 'Y-m-d' ) );
	$due_date   = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
	$todo_status = get_post_meta( $todo->ID, 'todo_status', true );
	$diff        = date_diff( $curr_date, $due_date );
	$diff_days   = (int) $diff->format( '%R%a' );

	// Organize tasks based on due dates
	if ( $diff_days < 0 ) {
		$todo_list['past'][] = $todo->ID;
	} elseif ( 0 === $diff_days ) {
		$todo_list['today'][] = $todo->ID;
	} elseif ( 1 === $diff_days ) {
		$todo_list['tomorrow'][] = $todo->ID;
	} else {
		$todo_list['future'][] = $todo->ID;
	}

	if ( 'complete' === $todo_status ) {
		$all_completed_todo++;
		$completed_todo_ids[] = $todo->ID;
	} else {
		$all_remaining_todo++;
	}
}

// Calculate completion percentage
$avg_rating = $all_todo_count > 0 ? ( $all_completed_todo * 100 ) / $all_todo_count : 0;

// Fetch completed todos
$completed_todos_args = array(
	'post_type'      => 'bp-todo',
	'post_status'    => 'publish',
	'author'         => get_current_user_id(),
	'include'        => $completed_todo_ids,
	'posts_per_page' => -1,
);
$completed_todos = get_posts( $completed_todos_args );

$bp_template_option = bp_get_option( '_bp_theme_package_id' );

if ( empty( $todos ) ) {
	// No todos found
	if ( 'nouveau' === $bp_template_option ) {
		?>
		<div id="message" class="info bp-feedback bp-messages bp-template-notice">
			<span class="bp-icon" aria-hidden="true"></span>
			<div id="message" class="info">
				<p><?php echo sprintf( esc_html__( 'Sorry, no %1$s found.', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?></p>
			</div>
		</div>
		<?php
	} else {
		?>
		<section class="bptodo-admin-setting">
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
					<?php do_action( 'bptodo_add_extra_tab_content_before_defaults', $profile_menu_label ); ?>

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
								<div class="all-bp-todo-list-export"><?php echo esc_html( list_todo_tab_function_to_show_title() ); ?></div>
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
												</div>
												<div class="todo-header-list">
													<!-- PAST TASKS -->
													<?php foreach ( $todo_list['past'] ?? [] as $tid ) : ?>
														<?php
														$todo             = get_post( $tid );
														$todo_title       = $todo->post_title;
														$todo_edit_url    = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;
														$todo_view_url    = get_permalink( $tid );
														$todo_status      = get_post_meta( $todo->ID, 'todo_status', true );
														$todo_priority    = get_post_meta( $todo->ID, 'todo_priority', true );
														$due_date_str     = '';
														$due_date_td_class = '';
														$curr_date        = date_create( gmdate( 'Y-m-d' ) );
														$due_date         = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff             = date_diff( $curr_date, $due_date );
														$diff_days        = (int) $diff->format( '%R%a' );
														$priority_class   = '';
														$todo_cat         = wp_get_object_terms( $tid, 'todo_category' )[0] ?? null;
														$todo_cat_name    = $todo_cat ? $todo_cat->name : '';
														$disabled         = $diff_days < 0 ? 'disabled' : '';

														if ( $diff_days < 0 ) {
															$due_date_str      = esc_html__( 'Expired!', 'wb-todo' );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 === $diff_days ) {
															$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
															$due_date_td_class = 'bptodo-expires-today';
															$all_remaining_todo++;
														} elseif ( 1 === $diff_days ) {
															$due_date_str = esc_html__( '1 day left', 'wb-todo' );
														} elseif ( $diff_days > 1 ) {
															$due_date_str = sprintf( esc_html__( '%1$d days left', 'wb-todo' ), abs( $diff_days ) );
														}

														if ( 'complete' === $todo_status ) {
															$due_date_str      = esc_html__( 'Completed', 'wb-todo' );
															$due_date_td_class = '';
															$bptodo_row_complete = 'completed';
														}

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

														$checked       = 'complete' === $todo_status ? 'checked' : '';
														$checked_class = 'complete' === $todo_status ? 'todo-complete' : 'todo-uncomplete';
														?>
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row <?php echo esc_attr( $bptodo_row_complete . $due_date_td_class ); ?>">
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
																<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> <?php echo esc_attr( $disabled ); ?> /></label>
															</li>
															<li class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></li>
															<li class="bptodo-date <?php echo esc_attr( $due_date_td_class ); ?>"><span><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></span><?php echo esc_html( $due_date_str ); ?></li>
															<li class="bptodo-cat"><span class="bptodo-cat-mobile-title"><?php esc_html_e( 'Category', 'wb-todo' ); ?></span><span class="bptodo-cat-text"><?php echo esc_html( $todo_cat_name ); ?></span></li>
															<li class="bptodo-task <?php echo esc_attr( $checked_class ); ?>"><?php echo esc_html( $todo_title ); ?></li>
															<li class="bp-to-do-actions">
																<ul class="bp-to-do-actions-list">
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) : ?>
																		<li><button data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger" data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/edit.svg" /></button></li>
																		<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/eye.svg" /></a></li>
																	<?php endif; ?>
																</ul>
															</li>
														</ul>
													<?php endforeach; ?>
													<!-- TASKS FOR TODAY -->
													<?php foreach ( $todo_list['today'] ?? [] as $tid ) : ?>
														<?php
														$todo             = get_post( $tid );
														$todo_title       = $todo->post_title;
														$todo_edit_url    = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;
														$todo_view_url    = get_permalink( $tid );
														$todo_status      = get_post_meta( $todo->ID, 'todo_status', true );
														$todo_priority    = get_post_meta( $todo->ID, 'todo_priority', true );
														$due_date_str     = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
														$due_date_td_class = 'bptodo-expires-today';
														$priority_class   = '';
														$todo_cat         = wp_get_object_terms( $tid, 'todo_category' )[0] ?? null;
														$todo_cat_name    = $todo_cat ? $todo_cat->name : '';
														$checked          = 'complete' === $todo_status ? 'checked' : '';
														$checked_class    = 'complete' === $todo_status ? 'todo-complete' : 'todo-uncomplete';

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
														?>
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row <?php echo esc_attr( $checked_class ); ?>">
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
																<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>
															</li>
															<li class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></li>
															<li class="bptodo-date <?php echo esc_attr( $due_date_td_class ); ?>"><span><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></span><?php echo esc_html( $due_date_str ); ?></li>
															<li class="bptodo-cat"><span class="bptodo-cat-mobile-title"><?php esc_html_e( 'Category', 'wb-todo' ); ?></span><span class="bptodo-cat-text"><?php echo esc_html( $todo_cat_name ); ?></span></li>
															<li class="bptodo-task <?php echo esc_attr( $checked_class ); ?>"><?php echo esc_html( $todo_title ); ?></li>
															<li class="bp-to-do-actions">
																<ul class="bp-to-do-actions-list">
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) : ?>
																		<li><button data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger" data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/edit.svg" /></button></li>
																		<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/eye.svg" /></a></li>
																	<?php endif; ?>
																</ul>
															</li>
														</ul>
													<?php endforeach; ?>
													<!-- TASKS FOR TOMORROW -->
													<?php foreach ( $todo_list['tomorrow'] ?? [] as $tid ) : ?>
														<?php
														$todo             = get_post( $tid );
														$todo_title       = $todo->post_title;
														$todo_edit_url    = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;
														$todo_view_url    = get_permalink( $tid );
														$todo_status      = get_post_meta( $todo->ID, 'todo_status', true );
														$todo_priority    = get_post_meta( $todo->ID, 'todo_priority', true );
														$due_date_str     = esc_html__( '1 day left', 'wb-todo' );
														$priority_class   = '';
														$todo_cat         = wp_get_object_terms( $tid, 'todo_category' )[0] ?? null;
														$todo_cat_name    = $todo_cat ? $todo_cat->name : '';
														$checked          = 'complete' === $todo_status ? 'checked' : '';
														$checked_class    = 'complete' === $todo_status ? 'todo-complete' : 'todo-uncomplete';

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
														?>
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row <?php echo esc_attr( $checked_class ); ?>">
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
																<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>
															</li>
															<li class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></li>
															<li class="bptodo-date"><span><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></span><?php echo esc_html( $due_date_str ); ?></li>
															<li class="bptodo-cat"><span class="bptodo-cat-mobile-title"><?php esc_html_e( 'Category', 'wb-todo' ); ?></span><span class="bptodo-cat-text"><?php echo esc_html( $todo_cat_name ); ?></span></li>
															<li class="bptodo-task <?php echo esc_attr( $checked_class ); ?>"><?php echo esc_html( $todo_title ); ?></li>
															<li class="bp-to-do-actions">
																<ul class="bp-to-do-actions-list">
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) : ?>
																		<li><button data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger" data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/edit.svg" /></button></li>
																		<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/eye.svg" /></a></li>
																	<?php endif; ?>
																</ul>
															</li>
														</ul>
													<?php endforeach; ?>
													<!-- FUTURE TASKS -->
													<?php foreach ( $todo_list['future'] ?? [] as $tid ) : ?>
														<?php
														$todo             = get_post( $tid );
														$todo_title       = $todo->post_title;
														$todo_edit_url    = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;
														$todo_view_url    = get_permalink( $tid );
														$todo_status      = get_post_meta( $todo->ID, 'todo_status', true );
														$todo_priority    = get_post_meta( $todo->ID, 'todo_priority', true );
														$due_date_str     = esc_html__( 'Due in a few days', 'wb-todo' );
														$priority_class   = '';
														$todo_cat         = wp_get_object_terms( $tid, 'todo_category' )[0] ?? null;
														$todo_cat_name    = $todo_cat ? $todo_cat->name : '';
														$checked          = 'complete' === $todo_status ? 'checked' : '';
														$checked_class    = 'complete' === $todo_status ? 'todo-complete' : 'todo-uncomplete';

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
														?>
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row <?php echo esc_attr( $checked_class ); ?>">
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
																<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>
															</li>
															<li class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></li>
															<li class="bptodo-date"><span><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></span><?php echo esc_html( $due_date_str ); ?></li>
															<li class="bptodo-cat"><span class="bptodo-cat-mobile-title"><?php esc_html_e( 'Category', 'wb-todo' ); ?></span><span class="bptodo-cat-text"><?php echo esc_html( $todo_cat_name ); ?></span></li>
															<li class="bptodo-task <?php echo esc_attr( $checked_class ); ?>"><?php echo esc_html( $todo_title ); ?></li>
															<li class="bp-to-do-actions">
																<ul class="bp-to-do-actions-list">
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) : ?>
																		<li><button data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger" data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/edit.svg" /></button></li>
																		<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/eye.svg" /></a></li>
																	<?php endif; ?>
																</ul>
															</li>
														</ul>
													<?php endforeach; ?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div id="bptodo-completed">
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
													</div>
													<div class="todo-header-list">
														<?php foreach ( $completed_todo_ids as $tid ) : ?>
															<?php
															$todo             = get_post( $tid );
															$todo_title       = $todo->post_title;
															$todo_edit_url    = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;
															$todo_view_url    = get_permalink( $tid );
															$todo_status      = get_post_meta( $todo->ID, 'todo_status', true );
															$todo_priority    = get_post_meta( $todo->ID, 'todo_priority', true );
															$due_date_str     = esc_html__( 'Completed', 'wb-todo' );
															$due_date_td_class = 'bptodo-expires-today';
															$priority_class   = '';
															$todo_cat         = wp_get_object_terms( $tid, 'todo_category' )[0] ?? null;
															$todo_cat_name    = $todo_cat ? $todo_cat->name : '';
															$checked          = 'complete' === $todo_status ? 'checked' : '';
															$checked_class    = 'complete' === $todo_status ? 'todo-complete' : 'todo-uncomplete';

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
															?>
															<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row <?php echo esc_attr( $checked_class ); ?>">
																<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
																	<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>
																</li>
																<li class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></li>
																<li class="bptodo-date"><span><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></span><?php echo esc_html( $due_date_str ); ?></li>
																<li class="bptodo-cat"><span class="bptodo-cat-mobile-title"><?php esc_html_e( 'Category', 'wb-todo' ); ?></span><span class="bptodo-cat-text"><?php echo esc_html( $todo_cat_name ); ?></span></li>
																<li class="bptodo-task <?php echo esc_attr( $checked_class ); ?>"><?php echo esc_html( $todo_title ); ?></li>
																<li class="bp-to-do-actions">
																	<ul class="bp-to-do-actions-list">
																		<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/trash-alt.svg" /></a></li>
																		<?php if ( 'complete' !== $todo_status ) : ?>
																			<li><button data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger" data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/edit.svg" /></button></li>
																			<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/eye.svg" /></a></li>
																		<?php endif; ?>
																	</ul>
																</li>
															</ul>
														<?php endforeach; ?>
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
				</section>
				<?php
	}
}
?>
