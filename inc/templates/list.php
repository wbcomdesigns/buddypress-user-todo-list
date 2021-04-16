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

$groups_link = bp_get_group_permalink( $bp->groups->current_group );
$admin_link  = trailingslashit( $groups_link . $profile_menu_slug );

$group_members = groups_get_group_members(
	array(
		'group_id'            => $bp->groups->current_group->id,
		'exclude_admins_mods' => false,
	)
);

if ( isset( $group_members['members'] ) ) {
	foreach ( $group_members['members'] as $member ) {
		$group_members_ids[] = $member->ID;
	}
}
$current_user = get_current_user_id();
$cu_index     = array_search( $current_user, $group_members_ids );
unset( $group_members_ids[ $cu_index ] );

$class      = 'todo-completed';
$group_id   = ( $bp->groups->current_group->id ) ? $bp->groups->current_group->id : 0;
$can_modify = false;
$can_views  = false;
if ( bp_group_is_admin() ) {
	$can_modify = true;
	$can_views  = true;
}


if ( bp_group_is_mod() ) {
	$mod_can_modify = bptodo_if_moderator_modification_enabled( $group_id, $current_user );
	if ( false === $mod_can_modify ) {
		$can_modify = true;
	}
	$mod_can_views = bptodo_report_view_enabled( $group_id, $current_user );
	if ( false === $mod_can_views ) {
		$can_views = true;
	}
}

// List of Todo Items.
$args               = array(
	'post_type'      => 'bp-todo',
	'post_status'    => 'publish',
	'author'         => get_current_user_id(),
	'posts_per_page' => -1,
	'meta_key'       => 'todo_group_id',
	'meta_value'     => $group_id,
);
$todos              = get_posts( $args );
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
	} elseif ( 0 === $diff_days ) {
		$todo_list['today'][] = $todo->ID;
		if ( 'complete' !== $todo_status ) {
			$all_remaining_todo++;
		}
	} elseif ( 1 === $diff_days ) {
		$todo_list['tomorrow'][] = $todo->ID;
		if ( 'complete' !== $todo_status ) {
			$all_remaining_todo++;
		}
	} else {
		$todo_list['future'][] = $todo->ID;
		if ( 'complete' !== $todo_status ) {
			$all_remaining_todo++;
		}
	}

	if ( 'complete' === $todo_status ) {
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
$bp_template_option  = bp_get_option( '_bp_theme_package_id' );
if ( empty( $todos ) ) {
	if ( 'nouveau' === $bp_template_option ) { ?>
				<div id="message" class="bptodo-notice info bp-feedback bp-messages bp-template-notice">
				<span class="bp-icon" aria-hidden="true"></span>
		<?php } else { ?>
				<div id="message" class="info">
		<?php } ?>
		<p>
		<?php /* Translators: Display the plural label name */ ?>
			<?php echo sprintf( esc_html__( 'Sorry, no %1$s found.', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?>
		</p>
	</div>
	<?php } else { ?>

	<!-- Show the successful message when todo is added -->
	<?php
	if ( isset( $_POST['todo_create'] ) ) {
		if ( 'nouveau' === $bp_template_option ) {
			?>
				<div id="message" class="bptodo-notice info bp-feedback bp-messages bp-template-notice">
				<span class="bp-icon" aria-hidden="true"></span>
		<?php } else { ?>
				<div id="message" class="info">
		<?php } ?>
		<p>
		<?php /* Translators: Display the plural label name */ ?>
			<?php echo sprintf( esc_html__( '%1$s added successfully !', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?>
		</p>
	</div>
	<?php } ?>

	<!-- Show the successful message when todo is updated -->
	<?php
	if ( isset( $_POST['todo_update'] ) ) {
		if ( 'nouveau' === $bp_template_option ) {
			?>
				<div id="message" class="bptodo-notice info bp-feedback bp-messages bp-template-notice">
				<span class="bp-icon" aria-hidden="true"></span>
		<?php } else { ?>
				<div id="message" class="info">
		<?php } ?>
					<p>
					<?php /* Translators: Display the plural label name */ ?>
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
						<span class="task_breaker-total-tasks"><?php echo esc_html( $all_todo_count ); ?></span>
							<?php
							if ( $all_todo_count <= 1 ) {
								echo esc_html( $profile_menu_label );
							} else {
								echo esc_html( $profile_menu_label_plural );
							}
							?>
					</div>
					</div>
					<div class="bptodo-light-grey">
						<span><b><?php echo esc_html( round( $avg_rating, 2 ) ) . '% '; ?></b><?php esc_html_e( 'Completed', 'wb-todo' ); ?></span>
						<div class="bptodo-color" style="height:24px;width:<?php echo esc_attr( $avg_rating ); ?>%">
						</div>
					</div>
				</div>
				<div id="bptodo-tabs">
					<ul>
						<?php do_action( 'bptodo_add_etxra_tab_before_defaults', $profile_menu_label_plural ); ?>
						<!-- <li><a href="#bptodo-dashboard"><?php // esc_html_e( 'Dashboard', 'wb-todo' ); ?></a></li> -->
						<li><a href="#bptodo-todos"><?php echo esc_html( $profile_menu_label_plural ); ?></a></li>
						<?php do_action( 'bptodo_add_etxra_tab_after_defaults', $profile_menu_label_plural ); ?>
					</ul>
					<?php do_action( 'bptodo_add_extra_tab_content_before_defaults', $profile_menu_label ); ?>
					<?php
					/*
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
									<?php
									if ( $all_remaining_todo <= 1 ) {
										echo sprintf( esc_html__( '%1$s Remaining', 'wb-todo' ), esc_html( $profile_menu_label ) );
									} else {
										echo sprintf( esc_html__( '%1$s Remaining', 'wb-todo' ), esc_html( $profile_menu_label_plural ) );
									}
									?>
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
					*/
					?>
					<div id="bptodo-todos">
						<div id="bptodo-task-tabs">
							<ul>
								<li><a href="#bptodo-all"><i class="fa fa-list" aria-hidden="true"></i>
									<?php /* Translators: Display the plural label name */ ?>
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
										<?php
										if ( ! empty( $todo_list['past'] ) ) {
											$count = 1;
											foreach ( $todo_list['past'] as $tid ) {
												?>
												<?php
												$todo          = get_post( $tid );
												$todo_title    = $todo->post_title;
												$todo_edit_url = $admin_link . '/add?args=' . $tid;
												$todo_view_url = get_permalink( $tid );

												$todo_status    = get_post_meta( $todo->ID, 'todo_status', true );
												$todo_priority  = get_post_meta( $todo->ID, 'todo_priority', true );
												$due_date_str   = $due_date_td_class = '';
												$curr_date      = date_create( date( 'Y-m-d' ) );
												$due_date       = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
												$diff           = date_diff( $curr_date, $due_date );
												$diff_days      = $diff->format( '%R%a' );
												$priority_class = '';
												if ( $diff_days < 0 ) {
													/* Translators: Number of expiry days */
													$due_date_str      = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
													$due_date_td_class = 'bptodo-expired';
												} elseif ( 0 === $diff_days ) {
													$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
													$due_date_td_class = 'bptodo-expires-today';
												} else {
													if ( 1 === $diff_days ) {
														$day_string = __( 'day', 'wb-todo' );
													} else {
														$day_string = __( 'days', 'wb-todo' );
													}
													/* Translators: Number of left days */
													$due_date_str = sprintf( esc_html__( '%1$d %2$s left to complete the task!', 'wb-todo' ), abs( $diff_days ), $day_string );
													// $all_remaining_todo++;
												}
												if ( 'complete' === $todo_status ) {
													$due_date_str      = esc_html__( 'Completed!', 'wb-todo' );
													$due_date_td_class = '';
													$all_completed_todo++;
												}
												if ( ! empty( $todo_priority ) ) {
													if ( 'critical' === $todo_priority ) {
														$priority_class = 'bptodo-priority-critical';
														$priority_text  = esc_html__( 'Critical', 'wb-todo' );
													} elseif ( 'high' === $todo_priority ) {
														$priority_class = 'bptodo-priority-high';
														$priority_text  = esc_html__( 'High', 'wb-todo' );
													} else {
														$priority_class = 'bptodo-priority-normal';
														$priority_text  = esc_html__( 'Normal', 'wb-todo' );
													}
												}

												?>
										<tr id="as bptodo-row-<?php echo esc_attr( $tid ); ?>">
											<td class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></td>
											<td class="<?php echo ( 'complete' === $todo_status ) ? esc_attr( $class ) : ''; ?>"><a href="<?php echo esc_url( $todo_view_url ); ?>" target = "_blank"><?php echo esc_html( $todo_title ); ?></a></td>
											<td class="<?php echo ( 'complete' === $todo_status ) ? esc_attr( $class ) : esc_attr( $due_date_td_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php echo esc_html( $due_date_str ); ?></td>
											<td class="bp-to-do-actions">
												<ul>
													<?php if ( $can_modify ) { ?>
														<?php /* Translators: Display the todo title name */ ?>
													<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>"    title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"
														><i class="fa fa-times"></i></a></li>
													<?php } ?>
													<?php if ( 'complete' != $todo_status ) { ?>
														<?php if ( $can_modify ) { ?>
															<?php /* Translators: Display the todo title name */ ?>
													<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-edit"></i></a></li>
													<?php } ?>
														<?php /* Translators: Display the todo title name */ ?>
														<?php if ( $can_views ) { ?>
													<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><i class="fa fa-eye"></i></a></li>
														<?php } ?>
														<?php	if ( ! bp_group_is_admin() ) { ?>
															<?php /* Translators: Display the todo title name */ ?>
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" data-uid="<?php echo esc_attr( $current_user ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-check"></i></a></li>
														<?php } ?>
													<?php } else { ?>
														<?php /* Translators: Display the todo title name */ ?>
													<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-undo"></i></a></li>
														<?php
													}
													if ( $can_views ) {
														?>
														<li id="bptodo-progress-li-<?php echo esc_attr( $tid ); ?>"><?php echo esc_html( bptodo_get_user_average_todos( $tid ) ); ?></li>
														<?php
													}
													?>
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
												$todo_edit_url = $admin_link . '/add?args=' . $tid;
												$todo_view_url = get_permalink( $tid );

												$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
												$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
												$due_date_str  = $due_date_td_class  = '';
												$curr_date     = date_create( date( 'Y-m-d' ) );
												$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
												$diff          = date_diff( $curr_date, $due_date );
												$diff_days     = $diff->format( '%R%a' );
												if ( $diff_days < 0 ) {
													/* Translators: Number of expiry days */
													$due_date_str      = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
													$due_date_td_class = 'bptodo-expired';
												} elseif ( 0 === $diff_days ) {
													$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
													$due_date_td_class = 'bptodo-expires-today';
													$all_remaining_todo++;
												} else {
													if ( 1 === $diff_days ) {
														$day_string = __( 'day', 'wb-todo' );
													} else {
														$day_string = __( 'days', 'wb-todo' );
													}
													/* Translators: Number of left days */
													$due_date_str = sprintf( esc_html__( '%1$d %2$s left to complete the task!', 'wb-todo' ), abs( $diff_days ), $day_string );
													$all_remaining_todo++;
												}
												if ( 'complete' === $todo_status ) {
													$due_date_str      = esc_html__( 'Completed!', 'wb-todo' );
													$due_date_td_class = '';
													$all_completed_todo++;
												}
												if ( ! empty( $todo_priority ) ) {
													if ( 'critical' === $todo_priority ) {
														$priority_class = 'bptodo-priority-critical';
														$priority_text  = esc_html__( 'Critical', 'wb-todo' );
													} elseif ( 'high' === $todo_priority ) {
														$priority_class = 'bptodo-priority-high';
														$priority_text  = esc_html__( 'High', 'wb-todo' );
													} else {
														$priority_class = 'bptodo-priority-normal';
														$priority_text  = esc_html__( 'Normal', 'wb-todo' );
													}
												}
												?>
										<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
											<td class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></td>
											<td class="<?php echo ( 'complete' === $todo_status ) ? esc_attr( $class ) : ''; ?>"><a href="<?php echo esc_url( $todo_view_url ); ?>" target = "_blank"><?php echo esc_html( $todo_title ); ?></a></td>
											<td class="<?php echo ( 'complete' === $todo_status ) ? esc_attr( $class ) : esc_attr( $due_date_td_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php echo esc_html( $due_date_str ); ?></td>

											<td class="bp-to-do-actions">
												<ul>
													<?php if ( $can_modify ) { ?>
														<?php /* Translators: Display todo title name  */ ?>
													<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-times"></i></a></li>
													<?php } ?>
													<?php if ( 'complete' !== $todo_status ) { ?>
														<?php if ( $can_modify ) { ?>
															<?php /* Translators: Display todo title name  */ ?>
													<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-edit"></i></a></li>
													<?php } ?>
														<?php /* Translators: Display todo title name  */ ?>
													<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><i class="fa fa-eye"></i></a></li>
														<?php	if ( ! bp_group_is_admin() ) { ?>
															<?php /* Translators: Display todo title name  */ ?>
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" data-uid="<?php echo esc_attr( $current_user ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-check"></i></a></li>
														<?php } ?>

													<?php } else { ?>
														<?php /* Translators: Display todo title name  */ ?>
													<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-undo"></i></a></li>
													<?php } ?>
													<?php
													if ( $can_views ) {
														?>
														<li id="bptodo-progress-li-<?php echo esc_attr( $tid ); ?>"><?php echo esc_html( bptodo_get_user_average_todos( $tid ) ); ?></li>
														<?php
													}
													?>
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
												$todo_edit_url = $admin_link . '/add?args=' . $tid;
												$todo_view_url = get_permalink( $tid );

												$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
												$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
												$due_date_str  = $due_date_td_class = '';
												$curr_date     = date_create( date( 'Y-m-d' ) );
												$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
												$diff          = date_diff( $curr_date, $due_date );
												$diff_days     = $diff->format( '%R%a' );
												if ( $diff_days < 0 ) {
													/* Translators: Number of expiry days */
													$due_date_str      = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
													$due_date_td_class = 'bptodo-expired';
												} elseif ( 0 === $diff_days ) {
													$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
													$due_date_td_class = 'bptodo-expires-today';
													$all_remaining_todo++;
												} else {
													if ( 1 === $diff_days ) {
														$day_string = __( 'day', 'wb-todo' );
													} else {
														$day_string = __( 'days', 'wb-todo' );
													}
													/* Translators: Number of left days  */
													$due_date_str = sprintf( esc_html__( '%1$d %2$s left to complete the task!', 'wb-todo' ), abs( $diff_days ), $day_string );
													$all_remaining_todo++;
												}
												if ( 'complete' === $todo_status ) {
													$due_date_str      = esc_html__( 'Completed!', 'wb-todo' );
													$due_date_td_class = '';
													$all_completed_todo++;
												}
												if ( ! empty( $todo_priority ) ) {
													if ( 'critical' === $todo_priority ) {
														$priority_class = 'bptodo-priority-critical';
														$priority_text  = esc_html__( 'Critical', 'wb-todo' );
													} elseif ( 'high' === $todo_priority ) {
														$priority_class = 'bptodo-priority-high';
														$priority_text  = esc_html__( 'High', 'wb-todo' );
													} else {
														$priority_class = 'bptodo-priority-normal';
														$priority_text  = esc_html__( 'Normal', 'wb-todo' );
													}
												}
												?>
										<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
											<td class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></td>
											<td class="
												<?php
												if ( 'complete' === $todo_status ) {
													echo esc_attr( $class );
												}
												?>
												"><a href="<?php echo esc_url( $todo_view_url ); ?>" target = "_blank"><?php echo esc_html( $todo_title ); ?></a>
											</td>
											<td class="
												<?php
												echo esc_attr( $due_date_td_class );
												if ( 'complete' === $todo_status ) {
													echo esc_attr( $class );
												}
												?>
											"><?php echo esc_html( $due_date_str ); ?>
										</td>
											<td class="bp-to-do-actions">
												<ul>
													<?php if ( $can_modify ) { ?>
														<?php /* Translators: Display todo title name  */ ?>
													<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s ', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-times"></i></a></li>
													<?php } ?>
													<?php if ( 'complete' !== $todo_status ) { ?>
														<?php if ( $can_modify ) { ?>
															<?php /* Translators: Display todo title name  */ ?>
													<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-edit"></i></a></li>
													<?php } ?>
														<?php /* Translators: Display todo title name  */ ?>
													<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><i class="fa fa-eye"></i></a></li>
														<?php	if ( ! bp_group_is_admin() ) { ?>
															<?php /* Translators: Display todo title name  */ ?>
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" data-uid="<?php echo esc_attr( $current_user ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-check"></i></a></li>
														<?php } ?>
													<?php } else { ?>
														<?php /* Translators: Display todo title name  */ ?>
													<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-undo"></i></a></li>
														<?php
													}
													if ( $can_views ) {
														?>
														<li id="bptodo-progress-li-<?php echo esc_attr( $tid ); ?>"><?php echo esc_html( bptodo_get_user_average_todos( $tid ) ); ?></li>
														<?php
													}
													?>
												</ul>
											</td>
										</tr>
										<?php } ?>

										<?php } ?>

										<!-- TASKS FOR SAMEDAY. -->
										<?php if ( ! empty( $todo_list['future'] ) ) { ?>
											<?php $count = 1; ?>
											<?php foreach ( $todo_list['future'] as $tid ) { ?>
												<?php
												$todo          = get_post( $tid );
												$todo_title    = $todo->post_title;
												$todo_edit_url = $admin_link . '/add?args=' . $tid;
												$todo_view_url = get_permalink( $tid );

												$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
												$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
												$due_date_str  = $due_date_td_class    = '';
												$curr_date     = date_create( date( 'Y-m-d' ) );
												$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
												$diff          = date_diff( $curr_date, $due_date );
												$diff_days     = $diff->format( '%R%a' );
												if ( $diff_days < 0 ) {
													/* Translators: Number of expiry date */
													$due_date_str      = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
													$due_date_td_class = 'bptodo-expired';
												} elseif ( 0 === $diff_days ) {
													$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
													$due_date_td_class = 'bptodo-expires-today';
													$all_remaining_todo++;
												} else {
													if ( 1 === $diff_days ) {
														$day_string = __( 'day', 'wb-todo' );
													} else {
														$day_string = __( 'days', 'wb-todo' );
													}
													/* Translators: Number of left days */
													$due_date_str = sprintf( esc_html__( '%1$d %2$s left to complete the task!', 'wb-todo' ), abs( $diff_days ), $day_string );
													$all_remaining_todo++;
												}
												if ( 'complete' === $todo_status ) {
													$due_date_str      = esc_html__( 'Completed!', 'wb-todo' );
													$due_date_td_class = '';
													$all_completed_todo++;
												}
												if ( ! empty( $todo_priority ) ) {
													if ( 'critical' === $todo_priority ) {
														$priority_class = 'bptodo-priority-critical';
														$priority_text  = esc_html__( 'Critical', 'wb-todo' );
													} elseif ( 'high' === $todo_priority ) {
														$priority_class = 'bptodo-priority-high';
														$priority_text  = esc_html__( 'High', 'wb-todo' );
													} else {
														$priority_class = 'bptodo-priority-normal';
														$priority_text  = esc_html__( 'Normal', 'wb-todo' );
													}
												}
												?>
										<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
											<td class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></td>
											<td class="
												<?php
												if ( 'complete' === $todo_status ) {
													echo esc_attr( $class );
												}
												?>
												"><a href="<?php echo esc_url( $todo_view_url ); ?>" target = "_blank"><?php echo esc_html( $todo_title ); ?></a></td>
											<td class="
												<?php
												echo esc_attr( $due_date_td_class );
												if ( 'complete' === $todo_status ) {
													echo esc_attr( $class );
												}
												?>
											"><?php echo esc_html( $due_date_str ); ?></td>
											<td class="bp-to-do-actions">
												<ul>
												<?php if ( $can_modify ) { ?>
													<?php /* Translators: Display todo title name  */ ?>
													<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-times"></i></a></li>
													<?php } ?>
													<?php if ( 'complete' !== $todo_status ) { ?>
														<?php if ( $can_modify ) { ?>
															<?php /* Translators: Display todo title name  */ ?>
													<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-edit"></i></a></li>
													<?php } ?>
														<?php /* Translators: Display todo title name  */ ?>
														<?php if ( $can_views ) { ?>
													<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><i class="fa fa-eye"></i></a></li>
														<?php } ?>
														<?php	if ( ! bp_group_is_admin() ) { ?>
															<?php /* Translators: Display todo title name  */ ?>
														<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" data-uid="<?php echo esc_attr( $current_user ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-check"></i></a></li>
													<?php } ?>
													<?php } else { ?>
														<?php /* Translators: Display todo title name  */ ?>
													<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-undo"></i></a></li>
														<?php
													}
													if ( $can_views ) {
														?>
														<li id="bptodo-progress-li-<?php echo esc_attr( $tid ); ?>"><?php echo esc_html( bptodo_get_user_average_todos( $tid ) ); ?></li>
														<?php
													}
													?>
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
																	$todo_edit_url = $admin_link . '/add?args=' . $tid;
																	$todo_view_url = get_permalink( $tid );

																	$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
																	$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
																	$due_date_str  = $due_date_td_class = '';
																	$curr_date     = date_create( date( 'Y-m-d' ) );
																	$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
																	$diff          = date_diff( $curr_date, $due_date );
																	$diff_days     = $diff->format( '%R%a' );
																	if ( $diff_days < 0 ) {
																		/* Translators: Number of expiry days  */
																		$due_date_str      = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
																		$due_date_td_class = 'bptodo-expired';
																	} elseif ( 0 === $diff_days ) {
																		$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
																		$due_date_td_class = 'bptodo-expires-today';
																		$all_remaining_todo++;
																	} else {
																		if ( 1 === $diff_days ) {
																			$day_string = __( 'day', 'wb-todo' );
																		} else {
																			$day_string = __( 'days', 'wb-todo' );
																		}
																		/* Translators: Number of left days */
																		$due_date_str = sprintf( esc_html__( '%1$d %2$s left to complete the task!', 'wb-todo' ), abs( $diff_days ), $day_string );
																		$all_remaining_todo++;
																	}
																	if ( 'complete' === $todo_status ) {
																		$due_date_str      = esc_html__( 'Completed!', 'wb-todo' );
																		$due_date_td_class = '';
																		$all_completed_todo++;
																	}
																	if ( ! empty( $todo_priority ) ) {
																		if ( 'critical' === $todo_priority ) {
																			$priority_class = 'bptodo-priority-critical';
																			$priority_text  = esc_html__( 'Critical', 'wb-todo' );
																		} elseif ( 'high' === $todo_priority ) {
																			$priority_class = 'bptodo-priority-high';
																			$priority_text  = esc_html__( 'High', 'wb-todo' );
																		} else {
																			$priority_class = 'bptodo-priority-normal';
																			$priority_text  = esc_html__( 'Normal', 'wb-todo' );
																		}
																	}

																	?>
																<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
																	<td class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></td>
																	<td class="
																	<?php
																	if ( 'complete' === $todo_status ) {
																		echo esc_attr( $class );}
																	?>
																		"><a href="<?php echo esc_url( $todo_view_url ); ?>" target = "_blank"><?php echo esc_html( $todo_title ); ?></a></td>
																	<td class="bp-to-do-actions">
																		<ul>
																				<?php if ( $can_modify ) { ?>
																					<?php /* Translators: Display todo title name  */ ?>
																			<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-times"></i></a>
																			</li>
																			<?php } ?>
																				<?php if ( 'complete' !== $todo_status ) { ?>
																					<?php if ( $can_modify ) { ?>
																						<?php /* Translators: Display todo title name  */ ?>
																			<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-edit"></i></a></li>
																						<?php /* Translators: Display todo title name  */ ?>
																			<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><i class="fa fa-eye"></i></a></li>
																			<?php } ?>.<?php /* Translators: Display todo title name  */ ?>
																			<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javascript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-check"></i></a></li>
																			<?php } else { ?>
																					<?php /* Translators: Display todo title name  */ ?>
																			<li><a href="javascript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><i class="fa fa-undo"></i></a></li>
																			<?php	} ?>
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
						<?php do_action( 'bptodo_add_extra_tab_content_after_defaults', $profile_menu_label ); ?>
				</div>
		</section>
				<?php
	} ?>
