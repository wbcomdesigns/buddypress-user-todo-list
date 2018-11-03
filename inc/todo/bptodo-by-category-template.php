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
$profile_menu_label = $bptodo->profile_menu_label;
$profile_menu_slug  = $bptodo->profile_menu_slug;
$class              = 'todo-completed';
$bp_template_option = bp_get_option('_bp_theme_package_id');

if ( ! empty( $atts['category'] ) ) {
	$term = get_term_by( 'id', $atts['category'], 'todo_category' );
	if ( ! empty( $term ) ) {
		$args  = array(
			'post_type'   => 'bp-todo',
			'numberposts' => -1,
			'author'  => get_current_user_id(),
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
		if ( ! empty( $todos ) ) {
			$todo_list = array();
			foreach ( $todos as $todo ) {
				$curr_date = date_create( date( 'Y-m-d' ) );
				$due_date  = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
				$diff      = date_diff( $curr_date, $due_date );
				$diff_days = $diff->format( '%R%a' );

				if ( $diff_days < 0 ) {
					$todo_list['past'][] = $todo->ID;
				} elseif ( 0 == $diff_days ) {
					$todo_list['today'][] = $todo->ID;
				} elseif ( 1 == $diff_days ) {
					$todo_list['tomorrow'][] = $todo->ID;
				} else {
					$todo_list['future'][] = $todo->ID;
				}
			}
			?>
			<div class="bptodo-adming-setting">
				<div class="bptodo-admin-settings-block">
					<div id="bptodo-settings-tbl">

						<!-- PAST TASKS -->
						<?php if ( ! empty( $todo_list['past'] ) ) { ?>
							<div class="bptodo-admin-row">
								<div>
									<button class="bptodo-item"><?php esc_html_e( 'PAST', 'wb-todo' ); ?></button>
									<div class="panel">
										<div class="todo-detail">
											<table class="bp-todo-reminder">
												<thead>
													<tr>
														<th></th>
														<th><?php esc_html_e( 'Task', 'wb-todo' ); ?></th>
														<th><?php esc_html_e( 'Task Description', 'wb-todo' ); ?></th>
														<th><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></th>
														<th><?php esc_html_e( 'Mark Complete', 'wb-todo' ); ?></th>
													</tr>
												</thead>
												<tbody>
													<?php $count = 1; ?>
													<?php foreach ( $todo_list['past'] as $tid ) { ?>
														<?php
														$todo       = get_post( $tid );
														$todo_title = $todo->post_title;

														$todo_edit_url = bp_core_get_userlink( get_current_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status  = get_post_meta( $todo->ID, 'todo_status', true );
														$due_date_str = $due_date_td_class = '';
														$curr_date    = date_create( date( 'Y-m-d' ) );
														$due_date     = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff         = date_diff( $curr_date, $due_date );
														$diff_days    = $diff->format( '%R%a' );
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
															$due_date_str      = esc_html__( 'Completed!', 'wb-todo' );
															$due_date_td_class = '';
														}
														?>
														<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $count++ ); ?></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $todo_title ); ?></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $todo->post_content ); ?></td>
															<td class="
															<?php
															echo esc_attr( $due_date_td_class );
															if ( 'complete' == $todo_status ) {
																echo esc_html( $class );
															}
															?>
															"><?php echo esc_html( $due_date_str, 'wb-todo' ); ?></td>
															<td class="bp-to-do-actions">
																<ul>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-times"></i></a></li>
																	<?php if ( 'complete' != $todo_status ) { ?>
																		<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-edit"></i></a></li>
																		<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javacript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-check"></i></a></li>
																	<?php } else { ?>
																		<li><a href="javacript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-undo"></i></a></li>
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
						<?php } ?>

						<!-- TASKS FOR TODAY -->
						<?php if ( ! empty( $todo_list['today'] ) ) { ?>
							<div class="bptodo-admin-row">
								<div>
									<button class="bptodo-item"><?php echo esc_html( 'TODAY', 'wb-todo' ); ?></button>
									<div class="panel">
										<div class="todo-detail">
											<table class="bp-todo-reminder">
												<thead>
													<tr>
														<th></th>
														<th><?php esc_html_e( 'Task', 'wb-todo' ); ?></th>
														<th><?php esc_html_e( 'Task Description', 'wb-todo' ); ?></th>
														<th><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></th>
														<th><?php esc_html_e( 'Mark Complete', 'wb-todo' ); ?></th>
													</tr>
												</thead>
												<tbody>
													<?php $count = 1; ?>
													<?php foreach ( $todo_list['today'] as $tid ) { ?>
														<?php
														$todo       = get_post( $tid );
														$todo_title = $todo->post_title;

														$todo_edit_url = bp_core_get_userlink( get_current_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status  = get_post_meta( $todo->ID, 'todo_status', true );
														$due_date_str = $due_date_td_class = '';
														$curr_date    = date_create( date( 'Y-m-d' ) );
														$due_date     = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff         = date_diff( $curr_date, $due_date );
														$diff_days    = $diff->format( '%R%a' );
														if ( $diff_days < 0 ) {
															$due_date_str = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 == $diff_days ) {
															$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!','wb-todo');
															$due_date_td_class = 'bptodo-expires-today';
														} else {
															$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
														}
														if ( 'complete' == $todo_status ) {
															$due_date_str      = esc_html__('Completed!','wb-todo');
															$due_date_td_class = '';
														}
														?>
														<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $count++ ); ?></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $todo_title ); ?></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $todo->post_content ); ?></td>
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
																	<?php if ( $todo_status !== 'complete' ) { ?>
																		<li><a href="<?php echo esc_attr( $todo_edit_url ); ?>" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-edit"></i></a></li>
																		<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javacript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-check"></i></a></li>
																	<?php } else { ?>
																		<li><a href="javacript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-undo"></i></a></li>
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
						<?php } ?>

						<!-- TASKS FOR TOMORROW -->
						<?php if ( ! empty( $todo_list['tomorrow'] ) ) { ?>
							<div class="bptodo-admin-row">
								<div>
									<button class="bptodo-item"><?php esc_html_e( 'TOMORROW', 'wb-todo' ); ?></button>
									<div class="panel">
										<div class="todo-detail">
											<table class="bp-todo-reminder">
												<thead>
													<tr>
														<th></th>
														<th><?php esc_html_e( 'Task', 'wb-todo' ); ?></th>
														<th><?php esc_html_e( 'Task Description', 'wb-todo' ); ?></th>
														<th><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></th>
														<th><?php esc_html_e( 'Mark Complete', 'wb-todo' ); ?></th>
													</tr>
												</thead>
												<tbody>
													<?php $count = 1; ?>
													<?php foreach ( $todo_list['tomorrow'] as $tid ) { ?>
														<?php
														$todo       = get_post( $tid );
														$todo_title = $todo->post_title;

														$todo_edit_url = bp_core_get_userlink( get_current_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status  = get_post_meta( $todo->ID, 'todo_status', true );
														$due_date_str = $due_date_td_class    = '';
														$curr_date    = date_create( date( 'Y-m-d' ) );
														$due_date     = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff         = date_diff( $curr_date, $due_date );
														$diff_days    = $diff->format( '%R%a' );
														if ( $diff_days < 0 ) {
															$due_date_str = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 == $diff_days ) {
															$due_date_str      = esc_html__('Today is the last day to complete. Hurry Up!','wb-todo');
															$due_date_td_class = 'bptodo-expires-today';
														} else {
															$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
														}
														if ( 'complete' == $todo_status) {
															$due_date_str      = esc_html__('Completed!','wb-todo');
															$due_date_td_class = '';
														}
														?>
														<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $count++ ); ?></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $todo_title ); ?></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $todo->post_content ); ?></td>
															<td class="
															<?php
															echo esc_html( $due_date_td_class );
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
																		<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" ><a href="javacript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-check"></i></a></li>
																	<?php } else { ?>
																		<li><a href="javacript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-undo"></i></a></li>
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
						<?php } ?>

						<!-- TASKS FOR SOMEDAY -->
						<?php if ( ! empty( $todo_list['future'] ) ) { ?>
							<div class="bptodo-admin-row">
								<div>
									<button class="bptodo-item"><?php esc_html_e( 'SOMEDAY', 'wb-todo' ); ?></button>
									<div class="panel">
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
													<?php foreach ( $todo_list['future'] as $tid ) { ?>
														<?php
														$todo       = get_post( $tid );
														$todo_title = $todo->post_title;

														$todo_edit_url = bp_core_get_userlink( get_current_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status  = get_post_meta( $todo->ID, 'todo_status', true );
														$due_date_str = $due_date_td_class    = '';
														$curr_date    = date_create( date( 'Y-m-d' ) );
														$due_date     = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff         = date_diff( $curr_date, $due_date );
														$diff_days    = $diff->format( '%R%a' );
														if ( $diff_days < 0 ) {
															$due_date_str = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 == $diff_days ) {
															$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!','wb-todo');
															$due_date_td_class = 'bptodo-expires-today';
														} else {
															$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
														}
														if ( 'complete' == $todo_status ) {
															$due_date_str      = esc_html__('Completed!','wb-todo');
															$due_date_td_class = '';
														}
														?>
														<tr id="bptodo-row-<?php echo esc_attr( $tid ); ?>">
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $count++ ); ?></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $todo_title ); ?></td>
															<td class="
															<?php
															if ( 'complete' == $todo_status ) {
																echo esc_attr( $class );}
?>
"><?php echo esc_html( $todo->post_content ); ?></td>
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
																		<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>"><a href="javacript:void(0);" class="bptodo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-check"></i></a></li>
																	<?php } else { ?>
																		<li><a href="javacript:void(0);" class="bptodo-undo-complete-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Undo Complete: %s', 'wb-todo' ), $todo_title ); ?>"><i class="fa fa-undo"></i></a></li>
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
						<?php } ?>
					</div>
				</div>
			</div>
			<?php
		} else {
			if( 'nouveau' == $bp_template_option ) { ?>
				<div id="message" class="info bp-feedback bp-messages bp-template-notice">
				<span class="bp-icon" aria-hidden="true"></span>
			<?php } else { ?>	
				<div id="message" class="info">
			<?php } ?>
				<p><?php esc_html_e( 'There are no todos in this category.', 'wb-todo' ); ?></p>
			</div>
			<?php
		}
	} else {
			if( 'nouveau' == $bp_template_option ) { ?>
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
			if( 'nouveau' == $bp_template_option ) { ?>
				<div id="message" class="info bp-feedback bp-messages bp-template-notice">
				<span class="bp-icon" aria-hidden="true"></span>
		<?php } else { ?>
				<div id="message" class="info">
		<?php } ?>
		<p><?php esc_html_e( 'Please provide any category ID.', 'wb-todo' ); ?></p>
	</div>
	<?php
}