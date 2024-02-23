<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $bp,$bptodo;
$profile_menu_slug         = $bptodo->profile_menu_slug;
$profile_menu_label        = $bptodo->profile_menu_label;
$profile_menu_label_plural = $bptodo->profile_menu_label_plural;
$class                     = 'todo-completed';

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
	$curr_date = date_create(gmdate('Y-m-d'));
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

$completed_todo_args    = array(
	'post_type'      => 'bp-todo',
	'post_status'    => 'publish',
	'author'         => get_current_user_id(),
	'include'        => $completed_todo_ids,
	'posts_per_page' => -1,
);
	$completed_todos    = get_posts( $completed_todo_args );
	$bp_template_option = bp_get_option( '_bp_theme_package_id' );
if ( empty( $todos ) ) {
	if ( 'nouveau' === $bp_template_option ) { ?>
		<div id="message" class="info bp-feedback bp-messages bp-template-notice">
			<span class="bp-icon" aria-hidden="true"></span>
						<div id="message" class="info">
			<?php } ?>
				<p>
					<?php /* Translators: Display plural label name  */ ?>
						<?php echo sprintf( esc_html__( 'Sorry, no %1$s found.', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?>
				</p>
			</div>
		</div>
		<?php } else { ?>
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
					
					<?php do_action( 'bptodo_add_extra_tab_content_before_defaults', $profile_menu_label ); ?>

					<div id="bptodo-todos">
						<div id="bptodo-task-tabs">
							<div class="bptodo-task-tabs-head">
								<ul class="bptodo-task-tabs-btn">
									<li class="all-bp-todo-list-tab">
										<a href="#bptodo-all">
											<span class="bptodo-btn-icon"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/all-list.svg" /></span>  
											<span><?php /* translators: %s: */ echo sprintf( esc_html__( 'All %1$s', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?></span>
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
														<?php
														if ( ! empty( $todo_list['past'] ) ) {
															$count = 1;
															foreach ( $todo_list['past'] as $tid ) {
																?>
																<?php
																$todo          = get_post( $tid );
																$todo_title    = $todo->post_title;
																$todo_edit_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;
																$todo_view_url = get_permalink( $tid );

																$todo_status    = get_post_meta( $todo->ID, 'todo_status', true );
																$todo_priority  = get_post_meta( $todo->ID, 'todo_priority', true );
																$due_date_str   = $due_date_td_class = '';
																$curr_date      = date_create( gmdate('Y-m-d') );
																$due_date       = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
																$diff           = date_diff( $curr_date, $due_date );
																$diff_days      = $diff->format( '%R%a' );
																$priority_class = '';
																$todo_cats = get_terms(array(
																	'taxonomy'   => 'todo_category',
																	'orderby'    => 'name',
																	'hide_empty' => false,
																));

																$todo           = get_post( $tid );
																$todo_cat       = wp_get_object_terms( $tid, 'todo_category' );
																$todo_cat_id    = 0;
																$disabled       = '';
																if ( ! empty( $todo_cat ) && is_array( $todo_cat ) ) {
																	$todo_cat_id = $todo_cat[0]->term_id;
																}
																if ( $diff_days < 0 ) {
																	/* Translators: Number of expiry days */
																	$due_date_str      = sprintf( esc_html__( 'Expired!', 'wb-todo' ), abs( $diff_days ) );
																	$due_date_td_class = 'bptodo-expired';
																	$disabled          = 'disabled';
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
																	$due_date_str = sprintf( esc_html__( '%1$d %2$s left', 'wb-todo' ), abs( $diff_days ), $day_string );
																	// $all_remaining_todo++;
																}
																$bptodo_row_complete = '';
																if ( 'complete' === $todo_status ) {
																	$due_date_str      = esc_html__( 'Completed', 'wb-todo' );
																	$due_date_td_class = '';
																	$all_completed_todo++;
																	$bptodo_row_complete = 'completed';
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
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row <?php echo esc_attr( $bptodo_row_complete . $due_date_td_class ); ?>">
																<?php
																$checked       = '';
																$checked_class = 'todo-uncomplete';
																if ( 'complete' == $todo_status ) {
																	$checked       = 'checked';
																	$checked_class = 'todo-complete';
																}
																?>
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php /* translators: %s: */ echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
															<label><input type="checkbox" class="option-input checkbox 
																<?php
																if ( $due_date_td_class ) {
																	echo esc_attr( $disabled ); }
																?>
															" 
																<?php
																echo esc_attr( $checked );
																if ( $due_date_td_class ) {
																	echo esc_attr( $disabled );
																}
																?>
															 />
															</label>																
															</li>
															<li class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></li>
															<li class="bptodo-date <?php echo ( 'complete' === $due_date_td_class ) ? esc_attr( $class ) : ''; ?>"><span>Due Date</span><?php echo esc_html( $due_date_str ); ?></li>
															<li class="bptodo-cat">
																<span class="bptodo-cat-mobile-title">Category</span>
																<span class="bptodo-cat-text">
																<?php
																foreach ( $todo_cats as $todo_cat ) {
																	if ( $todo_cat_id == $todo_cat->term_id ) {
																		echo esc_html( $todo_cat->name );
																	}
																}
																?>
																</span>
															</li>
															<li class="bptodo-task <?php echo ( 'complete' === $todo_status ) ? esc_attr( $class ) : ''; ?>"><?php echo esc_html( $todo_title ); ?></li>
															<li class="bp-to-do-actions">
																<ul class="bp-to-do-actions-list">
																<?php /* Translators: Display todo title name  */ ?>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>"    title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"
																		><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) { ?>
																		<?php /* Translators: Display todo title name  */ ?>
																	<li><button data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger"  data-modal-trigger="trigger" data-type="member" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/edit.svg" /></button></li>
																		<?php /* Translators: Display todo title name  */ ?>
																	<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/eye.svg" /></a></li>																		
																	<?php } ?>
																		
																</ul>
															</li>
														</ul>
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
																$todo_view_url = get_permalink( $tid );
																$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
																$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
																$due_date_str  = $due_date_td_class  = '';
																$curr_date     = date_create( gmdate('Y-m-d') );
																$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
																$diff          = date_diff( $curr_date, $due_date );
																$diff_days     = $diff->format( '%R%a' );
																$todo_cats = get_terms(array(
																	'taxonomy'   => 'todo_category',
																	'orderby'    => 'name',
																	'hide_empty' => false,
																));
																$todo          = get_post( $tid );
																$todo_cat      = wp_get_object_terms( $tid, 'todo_category' );
																$todo_cat_id   = 0;
																if ( ! empty( $todo_cat ) && is_array( $todo_cat ) ) {
																	$todo_cat_id = $todo_cat[0]->term_id;
																}
																if ( $diff_days < 0 ) {
																	/* Translators: Number of expiry days */
																	$due_date_str      = sprintf( esc_html__( 'Expired!', 'wb-todo' ), abs( $diff_days ) );
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
																	$due_date_str = sprintf( esc_html__( '%1$d %2$s left', 'wb-todo' ), abs( $diff_days ), $day_string );
																	$all_remaining_todo++;
																}
																$bptodo_row_complete = '';
																if ( 'complete' === $todo_status ) {
																	$due_date_str      = esc_html__( 'Completed', 'wb-todo' );
																	$due_date_td_class = '';
																	$all_completed_todo++;
																	$bptodo_row_complete = 'completed';
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
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row <?php echo esc_attr( $bptodo_row_complete ); ?>">
																<?php
																$checked       = '';
																$checked_class = 'todo-uncomplete';
																if ( 'complete' == $todo_status ) {
																	$checked       = 'checked';
																	$checked_class = 'todo-complete';
																}
																?>
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php /* translators: %s: */ echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
															<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>																
															</li>
															<li class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></li>
															<li class="bptodo-date <?php echo ( 'complete' === $due_date_td_class ) ? esc_attr( $class ) : ''; ?>"><span>Due Date</span><?php echo esc_html( $due_date_str ); ?></li>
															<li class="bptodo-cat">
																<span class="bptodo-cat-mobile-title">Category</span>
																<span class="bptodo-cat-text">
																<?php
																foreach ( $todo_cats as $todo_cat ) {
																	if ( $todo_cat_id == $todo_cat->term_id ) {
																		echo esc_html( $todo_cat->name );
																	}
																}
																?>
																</span>
															</li>
															<li class="bptodo-task <?php echo ( 'complete' === $todo_status ) ? esc_attr( $class ) : ''; ?>"><?php echo esc_html( $todo_title ); ?></li>
															<li class="bp-to-do-actions" >
																<ul class="bp-to-do-actions-list">
																	<?php /* Translators: Display todo title name  */ ?>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) { ?>
																		<?php /* Translators: Display todo title name  */ ?>
																	<li><button data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger"  data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/edit.svg" /></button></li>
																		<?php /* Translators: Display todo title name  */ ?>
																	<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/eye.svg" /></a></li>																		
																	<?php } ?>
																</ul>
															</li>
														</ul>
														<?php } ?>

														<?php } ?>
														<!-- TASKS FOR TOMORROW -->
														<?php
														if ( ! empty( $todo_list['tomorrow'] ) ) {
															$count = 1;

															foreach ( $todo_list['tomorrow'] as $tid ) {

																$todo          = get_post( $tid );
																$todo_title    = $todo->post_title;
																$todo_edit_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;
																$todo_view_url = get_permalink( $tid );

																$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
																$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
																$due_date_str  = $due_date_td_class = '';
																$curr_date     = date_create( gmdate('Y-m-d') );
																$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
																$diff          = date_diff( $curr_date, $due_date );
																$diff_days     = $diff->format( '%R%a' );
																$todo_cats = get_terms(array(
																	'taxonomy'   => 'todo_category',
																	'orderby'    => 'name',
																	'hide_empty' => false,
																));
																$todo          = get_post( $tid );
																$todo_cat      = wp_get_object_terms( $tid, 'todo_category' );
																$todo_cat_id   = 0;
																if ( ! empty( $todo_cat ) && is_array( $todo_cat ) ) {
																	$todo_cat_id = $todo_cat[0]->term_id;
																}
																if ( $diff_days < 0 ) {
																	/* Translators: Number of expiry days */
																	$due_date_str      = sprintf( esc_html__( 'Expired!', 'wb-todo' ), abs( $diff_days ) );
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
																	$due_date_str = sprintf( esc_html__( '%1$d %2$s left', 'wb-todo' ), abs( $diff_days ), $day_string );
																	$all_remaining_todo++;
																}
																$bptodo_row_complete = '';
																if ( 'complete' === $todo_status ) {
																	$due_date_str      = esc_html__( 'Completed', 'wb-todo' );
																	$due_date_td_class = '';
																	$all_completed_todo++;
																	$bptodo_row_complete = 'completed';
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
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row <?php echo esc_attr( $bptodo_row_complete ); ?>">
																<?php
																$checked       = '';
																$checked_class = 'todo-uncomplete';
																if ( 'complete' == $todo_status ) {
																	$checked       = 'checked';
																	$checked_class = 'todo-complete';
																}
																?>
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php /* translators: %s: */ echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
															<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>																
															</li>	
															<li class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></li>
															<li  class="bptodo-date <?php echo ( 'complete' === $todo_status ) ? esc_attr( $due_date_td_class ) : ''; ?>"><span>Due Date</span><?php echo esc_html( $todo_title ); ?></li>
															<li class="bptodo-cat">
																<span class="bptodo-cat-mobile-title">Category</span>
																<span class="bptodo-cat-text">
																<?php
																foreach ( $todo_cats as $todo_cat ) {
																	if ( $todo_cat_id == $todo_cat->term_id ) {
																		echo esc_html( $todo_cat->name );
																	}
																}
																?>
																</span>
															</li>
															<li class="bptodo-task <?php echo ( 'complete' === $todo_status ) ? esc_attr( $class ) : ''; ?>"><?php echo esc_html( $todo_title ); ?></li>
															<li class="bp-to-do-actions">
																<ul class="bp-to-do-actions-list">
																<?php /* Translators: Display todo title name  */ ?>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s ', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) { ?>
																		<?php /* Translators: Display todo title name  */ ?>
																	<li><button  data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger"  data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/edit.svg" /></button></li>
																		<?php /* Translators: Display todo title name  */ ?>
																	<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/eye.svg" /></a></li>																		
																	<?php } ?>																		
																</ul>
															</li>
														</ul>
														<?php } ?>

														<?php } ?>

														<!-- TASKS FOR SAMEDAY. -->
														<?php if ( ! empty( $todo_list['future'] ) ) { ?>
															<?php $count = 1; ?>
															<?php foreach ( $todo_list['future'] as $tid ) { ?>
																<?php
																$todo          = get_post( $tid );
																$todo_title    = $todo->post_title;
																$todo_edit_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;
																$todo_view_url = get_permalink( $tid );
																$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
																$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
																$due_date_str  = $due_date_td_class    = '';
																$date_value    = get_post_meta( $todo->ID, 'todo_due_date', true );
																$curr_date     = date_create( gmdate('Y-m-d') );
																$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
																$diff          = date_diff( $curr_date, $due_date );
																$diff_days     = $diff->format( '%R%a' );
																$todo_cats = get_terms(array(
																	'taxonomy'   => 'todo_category',
																	'orderby'    => 'name',
																	'hide_empty' => false,
																));
																$todo          = get_post( $tid );
																$todo_cat      = wp_get_object_terms( $tid, 'todo_category' );
																$todo_cat_id   = 0;
																if ( ! empty( $todo_cat ) && is_array( $todo_cat ) ) {
																	$todo_cat_id = $todo_cat[0]->term_id;
																}
																if ( $diff_days < 0 ) {
																	/* Translators: Number of expiry days */
																	$due_date_str      = sprintf( esc_html__( 'Expired!', 'wb-todo' ), abs( $diff_days ) );
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
																	$due_date_str = sprintf( esc_html__( '%1$d %2$s left', 'wb-todo' ), abs( $diff_days ), $day_string );
																	$all_remaining_todo++;
																}
																$bptodo_row_complete = '';
																if ( 'complete' === $todo_status ) {
																	$due_date_str      = esc_html__( 'Completed', 'wb-todo' );
																	$due_date_td_class = '';
																	$all_completed_todo++;
																	$bptodo_row_complete = 'completed';
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
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row <?php echo esc_attr( $bptodo_row_complete ); ?>">													
																<?php
																$checked       = '';
																$checked_class = 'todo-uncomplete';
																if ( 'complete' == $todo_status ) {
																	$checked       = 'checked';
																	$checked_class = 'todo-complete';
																}
																?>
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php /* translators: %s: */ echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
															<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>																
															</li>
															<li class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></li>
															<li  class="bptodo-date <?php echo ( 'complete' === $due_date_td_class ) ? esc_attr( $class ) : ''; ?>"><span>Due Date</span>
																							   <?php
																								if ( '' == $date_value || empty( $date_value ) ) {
																									echo esc_html( '-', 'wb-todo' );
																								} else {
																									echo esc_html( $due_date_str );
																								}
																								?>
															</li>
															<li class="bptodo-cat">
																<span class="bptodo-cat-mobile-title">Category</span>
																<span class="bptodo-cat-text">
																<?php
																foreach ( $todo_cats as $todo_cat ) {
																	if ( $todo_cat_id == $todo_cat->term_id ) {
																		echo esc_html( $todo_cat->name );
																	}
																}
																?>
																</span>
															</li>
															<li class="bptodo-task <?php echo ( 'complete' === $todo_status ) ? esc_attr( $class ) : ''; ?>"><?php echo esc_html( $todo_title ); ?></li>
															<li class="bp-to-do-actions">
																<ul class="bp-to-do-actions-list">
																<?php /* Translators: Display todo title name  */ ?>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) { ?>
																		<?php /* Translators: Display todo title name  */ ?>
																	<li><button  data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger"  data-modal-trigger="trigger" data-type = 'member' title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/edit.svg" /></button></li>
																	<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php /* translators: %s: */ echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/eye.svg" /></a></li>																		
																	<?php } ?>																		
																</ul>
															</li>
																	</ul>
														<?php } ?>

														<?php } ?>
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
														<?php if ( ! empty( $completed_todo_ids ) ) { ?>
															<?php
															foreach ( $completed_todo_ids as $tid ) {
																$todo          = get_post( $tid );
																$todo_title    = $todo->post_title;
																$todo_edit_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;
																$todo_view_url = get_permalink( $tid );
																$todo_status   = get_post_meta( $todo->ID, 'todo_status', true );
																$todo_priority = get_post_meta( $todo->ID, 'todo_priority', true );
																$due_date_str  = $due_date_td_class = '';
																$curr_date     = date_create( gmdate('Y-m-d') );
																$due_date      = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
																$diff          = date_diff( $curr_date, $due_date );
																$diff_days     = $diff->format( '%R%a' );
																if ( $diff_days < 0 ) {
																	/* Translators: Number of expiry days */
																	$due_date_str      = sprintf( esc_html__( 'Expired!', 'wb-todo' ), abs( $diff_days ) );
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
																	$due_date_str = sprintf( esc_html__( '%1$d %2$s left', 'wb-todo' ), abs( $diff_days ), $day_string );
																	$all_remaining_todo++;
																}
																$bptodo_row_complete = '';
																if ( 'complete' === $todo_status ) {
																	$due_date_str      = esc_html__( 'Completed', 'wb-todo' );
																	$due_date_td_class = '';
																	$all_completed_todo++;
																	$bptodo_row_complete = 'completed';
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
															<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row <?php echo esc_attr( $bptodo_row_complete ); ?>">
																<?php
																$checked       = '';
																$checked_class = 'todo-uncomplete';
																if ( 'complete' == $todo_status ) {
																	$checked       = 'checked';
																	$checked_class = 'todo-complete';
																}
																?>
															<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php /* translators: %s: */ echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
															<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>																
															</li>	
																<li class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></li>
																<li class="bptodo-date <?php echo ( 'complete' === $due_date_td_class ) ? esc_attr( $class ) : ''; ?><?php echo ( 'complete' === $todo_status ) ? esc_attr( $class ) : ''; ?>"><span>Due Date</span><?php echo esc_html( $due_date_str ); ?></li>
																<li class="bptodo-cat <?php echo ( 'complete' === $todo_status ) ? esc_attr( $class ) : ''; ?>">
																	<span class="bptodo-cat-mobile-title">Category</span>
																	<span class="bptodo-cat-text">
																		<?php
																		foreach ( $todo_cats as $todo_cat ) {
																			if ( $todo_cat_id == $todo_cat->term_id ) {
																				echo esc_html( $todo_cat->name );
																			}
																		}
																		?>
																	</span>
																<li class="bptodo-task <?php echo ( 'complete' === $todo_status ) ? esc_attr( $class ) : ''; ?>"><?php echo esc_html( $todo_title ); ?></li>
																<li class="bp-to-do-actions">
																	<ul class="bp-to-do-actions-list">
																	<?php /* Translators: Display todo title name  */ ?>
																		<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/trash-alt.svg" /></a></li>
																		<?php if ( 'complete' !== $todo_status ) { ?>
																			<?php /* Translators: Display todo title name  */ ?>
																		<li><button  data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger"  data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/edit.svg" /></button></li>
																			<?php /* Translators: Display todo title name  */ ?>
																		<li><a href="<?php echo esc_attr( $todo_view_url ); ?>" title="<?php echo sprintf( esc_html__( 'View: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>" target="_blank"><img src="<?php echo esc_attr( BPTODO_PLUGIN_URL ); ?>assets/css/images/eye.svg" /></a></li>																			
																		<?php } ?>																		
																	</ul>
																</li>
															</ul>
															<?php } ?>
															<?php } else { ?>
															<div class="info bp-feedback">
																<span class="bp-icon" aria-hidden="true"></span>
																<p class="text"><?php echo esc_html( 'Completed Todo Not found' ); ?></p>
															</div>
																<?php } ?>
														</div>
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
		} ?>
