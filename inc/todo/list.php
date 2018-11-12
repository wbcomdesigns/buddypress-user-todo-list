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
global $bp;
$profile_menu_slug         = $bptodo->profile_menu_slug;
$profile_menu_label        = $bptodo->profile_menu_label;
$profile_menu_label_plural = $bptodo->profile_menu_label_plural;

// Save todo items.
if ( isset( $_POST['todo_create'] ) && wp_verify_nonce( $_POST['save_new_todo_data_nonce'], 'wp-bp-todo' ) ) {

	if ( isset( $_POST['todo_cat'] ) ) {
		$cat = sanitize_text_field( wp_unslash( $_POST['todo_cat'] ) );
	}

	if ( isset( $_POST['todo_title'] ) ) {
		$title = sanitize_text_field( wp_unslash( $_POST['todo_title'] ) );
	}

	if ( isset( $_POST['todo_due_date'] ) ) {
		$due_date = sanitize_text_field( wp_unslash( $_POST['todo_due_date'] ) );
	}

	if ( isset( $_POST['bptodo-summary-input'] ) ) {
		$summary = sanitize_text_field( wp_unslash( $_POST['bptodo-summary-input'] ) );
	}

	if ( isset( $_POST['todo_priority'] ) ) {
		$priority = sanitize_text_field( wp_unslash( $_POST['todo_priority'] ) );
	}

	$taxonomy = 'todo_category';
	$args     = array(
		'post_type'    => 'bp-todo',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_content' => $summary,
		'post_author'  => get_current_user_id(),
	);
	$post_id  = wp_insert_post( $args );

	update_post_meta( $post_id, 'todo_status', 'incomplete' );
	update_post_meta( $post_id, 'todo_due_date', $due_date );
	update_post_meta( $post_id, 'todo_priority', $priority );
	update_post_meta( $post_id, 'todo_last_day_mail_sent', 'no' );
	update_post_meta( $post_id, 'todo_last_day_notification_sent', 'no' );

	wp_set_object_terms( $post_id, $cat, $taxonomy );
}
// Update todo items.
if ( isset( $_POST['todo_update'] ) && wp_verify_nonce( $_POST['save_update_todo_data_nonce'], 'wp-bp-todo' ) ) {
	$cat = sanitize_text_field( $_POST['todo_cat'] );

	$title    = sanitize_text_field( $_POST['todo_title'] );
	$summary  = wp_kses_post( $_POST['bptodo-summary-input'] );
	$due_date = sanitize_text_field( $_POST['todo_due_date'] );
	$priority = sanitize_text_field( $_POST['todo_priority'] );
	$todo_id  = sanitize_text_field( $_POST['hidden_todo_id'] );

	$taxonomy = 'todo_category';
	$args     = array(
		'ID'           => $todo_id,
		'post_type'    => 'bp-todo',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_content' => $summary,
		'post_author'  => get_current_user_id(),
	);
	$post_id  = wp_update_post( $args );

	update_post_meta( $post_id, 'todo_status', 'incomplete' );
	update_post_meta( $post_id, 'todo_due_date', $due_date );
	update_post_meta( $post_id, 'todo_priority', $priority );

	wp_set_object_terms( $post_id, $cat, $taxonomy );
}
$class = 'todo-completed';

// List of Todo Items.
$args  = array(
	'post_type'      => 'bp-todo',
	'post_status'    => 'publish',
	'author'         => get_current_user_id(),
	'posts_per_page' => -1,
);
$todos = get_posts( $args );

$todo_list          = array();
$all_todo_count     = 0;
$all_completed_todo = 0;
$all_remaining_todo = 0;
$completed_todo_ids = array();
foreach ( $todos as $todo ) {
	$curr_date   = date_create( date( 'Y-m-d' ) );
	$due_date    = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
	$todo_status = get_post_meta( $todo->ID, 'todo_status', true );
	$diff        = date_diff( $curr_date, $due_date );
	$diff_days   = $diff->format( '%R%a' );

	if ( $diff_days < 0 ) {
		$todo_list['past'][] = $todo->ID;
	} elseif ( 0 == $diff_days ) {
		$todo_list['today'][] = $todo->ID;
		if ( 'complete' != $todo_status ) {
			$all_remaining_todo++;
		}
	} elseif ( 1 == $diff_days ) {
		$todo_list['tomorrow'][] = $todo->ID;
		if ( 'complete' != $todo_status ) {
			$all_remaining_todo++;
		}
	} else {
		$todo_list['future'][] = $todo->ID;
		if ( 'complete' != $todo_status ) {
			$all_remaining_todo++;
		}
	}

	if ( 'complete' == $todo_status ) {
		$all_completed_todo++;
		array_push( $completed_todo_ids, $todo->ID );
	}

	$all_todo_count++;
}
if ( $all_todo_count > 0 ) {
	$avg_rating = ( $all_completed_todo * 100 ) / $all_todo_count;
}

$completed_todo_args = array(
	'post_type'      => 'bp-todo',
	'post_status'    => 'publish',
	'author'         => get_current_user_id(),
	'include'        => $completed_todo_ids,
	'posts_per_page' => -1,
);
$completed_todos     = get_posts( $completed_todo_args );
$bp_template_option  = bp_get_option('_bp_theme_package_id');
if ( empty( $todos ) ) {
		if( 'nouveau' == $bp_template_option ) { ?>
				<div id="message" class="info bp-feedback bp-messages bp-template-notice">
				<span class="bp-icon" aria-hidden="true"></span>
		<?php } else { ?>
				<div id="message" class="info">
		<?php } ?>
		<p>
			<?php echo sprintf( esc_html__( 'Sorry, no %1$s found.', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?>
		</p>
	</div>
	<?php } else { ?>

	<!-- Show the successful message when todo is added -->
	<?php if ( isset( $_POST['todo_create'] ) ) {
		if( 'nouveau' == $bp_template_option ) { ?>
				<div id="message" class="info bp-feedback bp-messages bp-template-notice">
				<span class="bp-icon" aria-hidden="true"></span>
		<?php } else { ?>
				<div id="message" class="info">
		<?php } ?>
		<p>
			<?php echo sprintf( esc_html__( '%1$s added successfully !', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?>
		</p>
	</div>
	<?php } ?>

	<!-- Show the successful message when todo is updated -->
	<?php if ( isset( $_POST['todo_update'] ) ) {
			if( 'nouveau' == $bp_template_option ) { ?>
				<div id="message" class="info bp-feedback bp-messages bp-template-notice">
				<span class="bp-icon" aria-hidden="true"></span>
		<?php } else { ?>
				<div id="message" class="info">
		<?php } ?>
					<p>
						<?php echo sprintf( esc_html__( '%1$s updated successfully !', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?>
					</p>
				</div>
	<?php } ?>

	<section class="bptodo-adming-setting">
		<div class="bptodo-admin-settings-block">

			<div class="bptodo-progress-section">
				<h5><?php esc_html_e( 'Task Progress', 'wb-todo' ); ?></h5>
				<div class="task-progress-task-count-wrap">
					<div class="task-progress-task-count">
						<span class="task_breaker-total-tasks"><?php echo esc_html( $all_todo_count ); ?></span><?php echo ' ' . esc_html( $profile_menu_label ); ?></div>
					</div>
					<div class="bptodo-light-grey">
						<span><b><?php echo esc_html( round( $avg_rating, 2 ) ).'% '; ?></b><?php esc_html_e( 'Completed', 'wb-todo' ); ?></span>
						<div class="bptodo-color" style="height:24px;width:<?php echo esc_attr( $avg_rating ); ?>%">
						</div>
					</div>
				</div>
				<div id="bptodo-tabs">
					<ul>
						<li><a href="#bptodo-dashboard"><?php esc_html_e( 'Dashboard', 'wb-todo' ); ?></a></li>
						<li><a href="#bptodo-todos"><?php echo esc_html( $profile_menu_label ); ?></a></li>
					</ul>
					<div id="bptodo-dashboard">
						<h2><?php esc_html_e( 'At a Glance', 'wb-todo' ); ?></h2>
						<ul id="bptodo-dashboard-box">
							<li>
								<div class="bp-todo-dashboard-at-a-glance-box">
									<h4>
										<span id="bp-todo-total-tasks-count" class="bp-todo-total-tasks">
											<?php echo esc_html( $all_todo_count ); ?>
										</span>
									</h4>
									<p>
										<?php echo sprintf( esc_html__( 'Total %1$s', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?>					
									</p>
								</div>
							</li>

							<li class="bp-todo-dashboard-at-a-glance-box" id="bptodo-remaining-task-count">
								<h4>
									<span id="bp-todo-remaining-tasks-count" class="bp-todo-remaining-tasks-count">
										<?php echo esc_html( $all_remaining_todo ); ?>
									</span>
								</h4>
								<p>
									<?php echo sprintf( esc_html__( '%1$s Remaining', 'wb-todo' ), esc_html( $profile_menu_label_plural ) ); ?>
								</p>
							</li>

							<li class="bp-todo-dashboard-at-a-glance-box" id="bptodo-remaining-task-count">
								<h4>
									<span id="task-progress-completed-count" class="task-progress-completed">
										<?php echo esc_html( $all_completed_todo ); ?>
									</span>
								</h4>
								<p>
								<?php echo sprintf( esc_html__( '%1$s Completed', 'wb-todo' ), esc_html( $profile_menu_label_plural ) ); ?>
								</p>
							</li>

						</ul>
					</div>
					<div id="bptodo-todos">
						<div id="bptodo-task-tabs">
							<ul>
								<li><a href="#bptodo-all"><i class="fa fa-list" aria-hidden="true"></i>
									<?php echo sprintf( esc_html__( 'All %1$s', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?>
									<span class="bp_all_todo_count"><?php echo esc_html( $all_todo_count ); ?></span></a></li>
								<li><a href="#bptodo-completed"><i class="fa fa-check"></i><?php esc_html_e( 'Completed', 'wb-todo' ); ?><span class="bp_completed_todo_count"><?php echo esc_html( $all_completed_todo ); ?></span></a></li>
							</ul>
							<div id="bptodo-all">
								<div class="bptodo-admin-row">
									<div>
										<div class="todo-panel">
											<div class="todo-detail">
												<table class="bp-todo-reminder">
													<thead>
														<tr>
															<th><?php esc_html_e( 'Priority', 'wb-todo' ); ?></th>
															<th><?php esc_html_e( 'Task', 'wb-todo' ); ?></th>
															<th><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></th>
															<th><?php esc_html_e( 'Actions', 'wb-todo' ); ?></th>
														</tr>
													</thead>
													<tbody>
														<!-- PAST TASKS -->
														<?php if ( ! empty( $todo_list['past'] ) ) { 
														 $count = 1;
															foreach ( $todo_list['past'] as $tid ) { ?>
														<?php
														$todo          = get_post( $tid );
														$todo_title    = $todo->post_title;
														$todo_edit_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status    = get_post_meta( $todo->ID, 'todo_status', true );
														$todo_priority  = get_post_meta( $todo->ID, 'todo_priority', true );
														$due_date_str   = $due_date_td_class = '';
														$curr_date      = date_create( date( 'Y-m-d' ) );
														$due_date       = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff           = date_diff( $curr_date, $due_date );
														$diff_days      = $diff->format( '%R%a' );
														$priority_class = '';
														if ( $diff_days < 0 ) {
															$due_date_str = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 == $diff_days ) {
															$due_date_str      = esc_html__('Today is the last day to complete. Hurry Up!','wb-todo');
															$due_date_td_class = 'bptodo-expires-today';
														} else {
															$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
														}
														if ( 'complete' == $todo_status ) {
															$due_date_str      = esc_html__('Completed!','wb-todo');
															$due_date_td_class = '';
															$all_completed_todo++;
														}
														if ( ! empty( $todo_priority ) ) {
															if ( 'critical' == $todo_priority ) {
																$priority_class = 'bptodo-priority-critical';
																$priority_text = esc_html__('Critical','wb-todo');
															} elseif ( 'high' == $todo_priority ) {
																$priority_class = 'bptodo-priority-high';
																$priority_text = esc_html__('High','wb-todo');
															} else {
																$priority_class = 'bptodo-priority-normal';
																$priority_text = esc_html__('Normal','wb-todo');
															}
														}
														?>
														<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
															<td class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo $priority_text; ?></span></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );
															}
?>
"><?php echo esc_html( $todo_title ); ?></td>
															<td class="
															<?php
															echo esc_attr( $due_date_td_class );
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );
															}
															?>
															"><?php echo $due_date_str; ?></td>
															<td class="bp-to-do-actions">
																<ul>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>"    title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), $todo_title ); ?>"
																		><i class="fa fa-times"></i></a></li>
																	<?php if ( 'complete' !== $todo_status ) { ?>
																	<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-edit"></i></a></li>
																	<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-check"></i></a></li>
																	<?php } else { ?>
																	<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-undo"></i></a></li>
																	<?php } ?>
																</ul>
															</td>
														</tr>
														<?php } ?>

														<?php } ?>
														<!-- TASKS FOR TODAY -->
														<?php if ( ! empty( $todo_list['today'] ) ) { ?>
														<?php $count = 1; ?>
														<?php foreach ( $todo_list['today'] as $tid ) { ?>
														<?php
														$todo          = get_post( $tid );
														$todo_title    = $todo->post_title;
														$todo_edit_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
														$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
														$due_date_str  = $due_date_td_class  = '';
														$curr_date     = date_create( date( 'Y-m-d' ) );
														$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff          = date_diff( $curr_date, $due_date );
														$diff_days     = $diff->format( '%R%a' );
														if ( $diff_days < 0 ) {
															$due_date_str = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 == $diff_days ) {
															$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!','wb-todo' );
															$due_date_td_class = 'bptodo-expires-today';
															$all_remaining_todo++;
														} else {
															$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
															$all_remaining_todo++;
														}
														if ( 'complete' == $todo_status ) {
															$due_date_str      = esc_html__( 'Completed!','wb-todo');
															$due_date_td_class = '';
															$all_completed_todo++;
														}
														if ( ! empty( $todo_priority ) ) {
															if ( 'critical' == $todo_priority ) {
																$priority_class = 'bptodo-priority-critical';
																$priority_text = esc_html__('Critical','wb-todo');
															} elseif ( 'high' == $todo_priority ) {
																$priority_class = 'bptodo-priority-high';
																$priority_text = esc_html__('High','wb-todo');
															} else {
																$priority_class = 'bptodo-priority-normal';
																$priority_text = esc_html__('Normal','wb-todo');
															}
														}
														?>
														<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
															<td class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo $priority_text; ?></span></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );
															}
?>
"><?php echo esc_html( $todo_title ); ?></td>
															<td class="
															<?php
															echo esc_attr( $due_date_td_class );
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );
															}
															?>
															"><?php echo $due_date_str; ?></td>
															<td class="bp-to-do-actions">
																<ul>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-times"></i></a></li>
																	<?php if ( 'complete' !== $todo_status ) { ?>
																	<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-edit"></i></a></li>
																	<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-check"></i></a></li>
																	<?php } else { ?>
																	<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-undo"></i></a></li>
																	<?php } ?>
																</ul>
															</td>
														</tr>
														<?php } ?>

														<?php } ?>
														<!-- TASKS FOR TOMORROW -->
														<?php if ( ! empty( $todo_list['tomorrow'] ) ) { ?>

														<?php $count = 1; ?>
														<?php foreach ( $todo_list['tomorrow'] as $tid ) { ?>
														<?php
														$todo          = get_post( $tid );
														$todo_title    = $todo->post_title;
														$todo_edit_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
														$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
														$due_date_str  = $due_date_td_class = '';
														$curr_date     = date_create( date( 'Y-m-d' ) );
														$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff          = date_diff( $curr_date, $due_date );
														$diff_days     = $diff->format( '%R%a' );
														if ( $diff_days < 0 ) {
															$due_date_str = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 == $diff_days ) {
															$due_date_str      = esc_html__('Today is the last day to complete. Hurry Up!','wb-todo');
															$due_date_td_class = 'bptodo-expires-today';
															$all_remaining_todo++;
														} else {
															$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
															$all_remaining_todo++;
														}
														if ( 'complete' == $todo_status ) {
															$due_date_str      = esc_html__('Completed!','wb-todo');
															$due_date_td_class = '';
															$all_completed_todo++;
														}
														if ( ! empty( $todo_priority ) ) {
															if ( 'critical' == $todo_priority ) {
																$priority_class = 'bptodo-priority-critical';
																$priority_text = esc_html__('Critical','wb-todo');
															} elseif ( 'high' == $todo_priority ) {
																$priority_class = 'bptodo-priority-high';
																$priority_text = esc_html__('High','wb-todo');
															} else {
																$priority_class = 'bptodo-priority-normal';
																$priority_text = esc_html__('Normal','wb-todo');
															}
														}
														?>
														<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
															<td class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo $priority_text; ?></span></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );
															}
?>
"><?php echo esc_html( $todo_title ); ?></td>
															<td class="
															<?php
															echo esc_attr( $due_date_td_class );
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );
															}
															?>
															"><?php echo $due_date_str; ?></td>
															<td class="bp-to-do-actions">
																<ul>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s ', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-times"></i></a></li>
																	<?php if ( 'complete' !== $todo_status ) { ?>
																	<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-edit"></i></a></li>
																	<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-check"></i></a></li>
																	<?php } else { ?>
																	<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-undo"></i></a></li>
																	<?php } ?>
																</ul>
															</td>
														</tr>
														<?php } ?>

														<?php } ?>

														<!-- TASKS FOR SOMEDAY. -->
														<?php if ( ! empty( $todo_list['future'] ) ) { ?>
														<?php $count = 1; ?>
														<?php foreach ( $todo_list['future'] as $tid ) { ?>
														<?php
														$todo          = get_post( $tid );
														$todo_title    = $todo->post_title;
														$todo_edit_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
														$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
														$due_date_str  = $due_date_td_class    = '';
														$curr_date     = date_create( date( 'Y-m-d' ) );
														$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff          = date_diff( $curr_date, $due_date );
														$diff_days     = $diff->format( '%R%a' );
														if ( $diff_days < 0 ) {
															$due_date_str = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 == $diff_days ) {
															$due_date_str      = esc_html__('Today is the last day to complete. Hurry Up!','wb-todo');
															$due_date_td_class = 'bptodo-expires-today';
															$all_remaining_todo++;
														} else {
															$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
															$all_remaining_todo++;
														}
														if ( 'complete' == $todo_status ) {
															$due_date_str      = esc_html__('Completed!','wb-todo');
															$due_date_td_class = '';
															$all_completed_todo++;
														}
														if ( ! empty( $todo_priority ) ) {
															if ( 'critical' == $todo_priority ) {
																$priority_class = 'bptodo-priority-critical';
																$priority_text = esc_html__( 'Critical','wb-todo');
															} elseif ( 'high' == $todo_priority ) {
																$priority_class = 'bptodo-priority-high';
																$priority_text = esc_html__('High','wb-todo');
															} else {
																$priority_class = 'bptodo-priority-normal';
																$priority_text = esc_html__('Normal','wb-todo');
															}
														}
														?>
														<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
															<td class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo $priority_text; ?></span></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );
															}
?>
"><?php echo esc_html( $todo_title ); ?></td>
															<td class="
															<?php
															echo esc_attr( $due_date_td_class );
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );
															}
															?>
															"><?php echo $due_date_str; ?></td>
															<td class="bp-to-do-actions">
																<ul>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-times"></i></a></li>
																	<?php if ( 'complete' != $todo_status ) { ?>
																	<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-edit"></i></a></li>
																	<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-check"></i></a></li>
																	<?php } else { ?>
																	<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-undo"></i></a></li>
																	<?php } ?>
																</ul>
															</td>
														</tr>
														<?php } ?>

														<?php } ?>
													</tbody>
												</table>
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
												<table class="bp-todo-reminder">
													<thead>
														<tr>
															<th><?php esc_html_e( 'Priority', 'wb-todo' ); ?></th>
															<th><?php esc_html_e( 'Task', 'wb-todo' ); ?></th>
															<th><?php esc_html_e( 'Actions', 'wb-todo' ); ?></th>
														</tr>
													</thead>
													<tbody>
														<?php if ( ! empty( $completed_todo_ids ) ) { ?>
														<?php
														foreach ( $completed_todo_ids as $tid ) {
															$todo          = get_post( $tid );
															$todo_title    = $todo->post_title;
															$todo_edit_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

															$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
															$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
															$due_date_str  = $due_date_td_class = '';
															$curr_date     = date_create( date( 'Y-m-d' ) );
															$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
															$diff          = date_diff( $curr_date, $due_date );
															$diff_days     = $diff->format( '%R%a' );
															if ( $diff_days < 0 ) {
																$due_date_str = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
																$due_date_td_class = 'bptodo-expired';
															} elseif ( 0 == $diff_days ) {
																$due_date_str      = esc_html__('Today is the last day to complete. Hurry Up!','wb-todo');
																$due_date_td_class = 'bptodo-expires-today';
																$all_remaining_todo++;
															} else {
																$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
																$all_remaining_todo++;
															}
															if ( 'complete' == $todo_status ) {
																$due_date_str      = esc_html__('Completed!','wb-todo');
																$due_date_td_class = '';
																$all_completed_todo++;
															}
															if ( ! empty( $todo_priority ) ) {
																if ( 'critical' == $todo_priority ) {
																	$priority_class = 'bptodo-priority-critical';
																	$priority_text = esc_html__('Critical','wb-todo');
																} elseif ( 'high' == $todo_priority ) {
																	$priority_class = 'bptodo-priority-high';
																	$priority_text = esc_html__('High','wb-todo');
																} else {
																	$priority_class = 'bptodo-priority-normal';
																	$priority_text = esc_html__('Normal','wb-todo');
																}
															}
															?>
															<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
																<td class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo $priority_text; ?></span></td>
																<td class="
																<?php
																if ( 'complete' == $todo_status ) {
																	echo esc_attr( $class );}
?>
"><?php echo esc_html( $todo_title ); ?></td>
																<td class="bp-to-do-actions">
																	<ul>
																		<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-times"></i></a></li>
																		<?php if ( 'complete' != $todo_status ) { ?>
																		<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-edit"></i></a></li>
																		<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-check"></i></a></li>
																		<?php } else { ?>
																		<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-undo"></i></a></li>
																		<?php } ?>
																	</ul>
																</td>
															</tr>
															<?php } ?>
															<?php } ?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</section>
				<?php
} ?>
