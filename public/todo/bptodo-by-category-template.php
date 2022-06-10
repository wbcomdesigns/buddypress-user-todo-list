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
		if ( ! empty( $todos ) ) {
			$todo_list = array();
			foreach ( $todos as $todo ) {
				$curr_date = date_create( date( 'Y-m-d' ) );
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
			?>
			<div class="bptodo-adming-setting bptod-category-shortcode-block">
				<div class="bptodo-admin-settings-block">
					<div id="bptodo-settings-tbl">

						<!-- PAST TASKS -->
						<?php if ( ! empty( $todo_list['past'] ) ) { ?>
							<div class="bptodo-admin-row">
								<div>
									<button class="bptodo-item"><?php esc_html_e( 'PAST', 'wb-todo' ); ?></button>
									<div class="bptodo-bptodo-panel">
										<div class="todo-detail">
											<div class="bp-todo-reminder">
												<div class="todo-header">
													<ul class="todo-header-head">
														<li class="bptodo-check"><?php esc_html_e( 'Mark', 'wb-todo' ); ?></li>
														<li class="bptodo-priority"><?php esc_html_e( 'Priority', 'wb-todo' ); ?></li>
														<li class="bptodo-date"><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></li>
														<li class="bptodo-task"><?php esc_html_e( 'Task', 'wb-todo' ); ?></li>
														<li class="bptodo-task bptodo-description"><?php esc_html_e( 'Task Description', 'wb-todo' ); ?></li>
														<li class="bptodo-actions bptodo-complete"><?php esc_html_e( 'Actions', 'wb-todo' ); ?></li>
													</ul>
												</div>
												<div class="todo-header-list">
													<?php foreach ( $todo_list['past'] as $tid ) { ?>
														<?php
														$todo       = get_post( $tid );
														$todo_title = $todo->post_title;
														$todo_view_url = get_permalink( $tid );
														$todo_edit_url = bp_core_get_userlink( get_current_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status  = get_post_meta( $todo->ID, 'todo_status', true );
														$due_date_str = $due_date_td_class = '';
														$curr_date    = date_create( date( 'Y-m-d' ) );
														$due_date     = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff         = date_diff( $curr_date, $due_date );
														$diff_days    = $diff->format( '%R%a' );
														if ( $diff_days < 0 ) {
															/* translators: Number of Expiry days */
															$due_date_str      = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 === $diff_days ) {
															$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
															$due_date_td_class = 'bptodo-expires-today';
														} else {
															/* translators: Number of left days */
															$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
														}
														if ( 'complete' === $todo_status ) {
															$due_date_str      = esc_html__( 'Completed!', 'wb-todo' );
															$due_date_td_class = '';
														}
														?>
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row">
														<?php
																$checked = '';
																$checked_class = 'todo-uncomplete';
																if ( 'complete' == $todo_status ) {
																	$checked = 'checked';
																	$checked_class = 'todo-complete';
																}
																?>
														<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
															<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>																
															</li>
															<li class="bptodo-priority"><?php esc_html_e( 'Priority', 'wb-todo' ); ?></li>
															<li class="bptodo-date  <?php echo esc_attr( $due_date_td_class ); if ( 'complete' === $todo_status ) {echo esc_html( $class );} ?>">
																<?php echo esc_html( $due_date_str, 'wb-todo' ); ?>
															</li>
															<li class="bptodo-task <?php if ( 'complete' === $todo_status ) { echo esc_attr( $class );} ?>">
																<?php echo esc_html( $todo_title ); ?>
															</li>
															<li class="bptodo-task <?php if ( 'complete' === $todo_status ) { echo esc_attr( $class );} ?>">
															<?php echo esc_html( wp_trim_words( $todo->post_content, 15 ) ); ?>
															<a href="<?php echo esc_attr( $todo_view_url ); ?>" target="_blank"><?php echo esc_html( 'Read More' ); ?></a>
															</li>
															
															<li class="bp-to-do-actions">
																<ul class="bp-to-do-actions-list">
																<?php /* translators: Display todo title name */ ?>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo BPTODO_PLUGIN_URL; ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) { ?>
																		<?php /* translators: Display todo title name */ ?>
																		<li><button data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger"  data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo BPTODO_PLUGIN_URL; ?>assets/css/images/edit.svg" /></button></li>
																	<?php } else { ?>
																	<?php } ?>
																</ul>
															</li>
														</ul>
													<?php } ?>
												</div>
											</div>
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
									<div class="bptodo-panel">
										<div class="todo-detail">
											<div class="bp-todo-reminder">
												<div class="todo-header-head">
													<ul  class="todo-header-head">
														<li class="bptodo-check"><?php esc_html_e( 'Mark', 'wb-todo' ); ?></li>
														<li class="bptodo-priority"><?php esc_html_e( 'Priority', 'wb-todo' ); ?></li>
														<li class="bptodo-date"><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></li>
														<li class="bptodo-task"><?php esc_html_e( 'Task', 'wb-todo' ); ?></li>
														<li class="bptodo-task bptodo-description"><?php esc_html_e( 'Task Description', 'wb-todo' ); ?></li>
														<li class="bptodo-actions bptodo-complete"><?php esc_html_e( 'Actions', 'wb-todo' ); ?></li>
													</ul>
												</div>
												<div class="todo-header-list">
													<?php foreach ( $todo_list['today'] as $tid ) { ?>
														<?php
														$todo       = get_post( $tid );
														$todo_title = $todo->post_title;
														$todo_view_url = get_permalink( $tid );
														$todo_edit_url = bp_core_get_userlink( get_current_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status  = get_post_meta( $todo->ID, 'todo_status', true );
														$due_date_str = $due_date_td_class = '';
														$curr_date    = date_create( date( 'Y-m-d' ) );
														$due_date     = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff         = date_diff( $curr_date, $due_date );
														$diff_days    = $diff->format( '%R%a' );
														if ( $diff_days < 0 ) {
															/* translators: Number of Expiry days */
															$due_date_str      = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 === $diff_days ) {
															$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
															$due_date_td_class = 'bptodo-expires-today';
														} else {
															/* translators: Number of left days */
															$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
														}
														if ( 'complete' === $todo_status ) {
															$due_date_str      = esc_html__( 'Completed!', 'wb-todo' );
															$due_date_td_class = '';
														}
														?>
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row">
														<?php
																$checked = '';
																$checked_class = 'todo-uncomplete';
																if ( 'complete' == $todo_status ) {
																	$checked = 'checked';
																	$checked_class = 'todo-complete';
																}
																?>
														<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
															<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>																
															</li>
															<li class="bptodo-priority"><?php esc_html_e( 'Priority', 'wb-todo' ); ?></li>
															<li class="bptodo-date <?php echo esc_attr( $due_date_td_class ); if ( 'complete' === $todo_status ) { echo esc_attr( $class ); }?>">
																<?php echo esc_html( $due_date_str ); ?>
															</li>
															<li class="bptodo-task <?php if ( 'complete' === $todo_status ) { echo esc_attr( $class );} ?>">
																<?php echo esc_html( $todo_title ); ?>
															</li>
															<li class="bptodo-task <?php if ( 'complete' === $todo_status ) { echo esc_attr( $class );} ?>">
																<?php echo esc_html( wp_trim_words( $todo->post_content, 15 ) ); ?>
																<a href="<?php echo esc_attr( $todo_view_url ); ?>" target="_blank"><?php echo esc_html( 'Read More' ); ?></a>
															</li>
															
															<li class="bp-to-do-actions">
																<ul class="bp-to-do-actions-list">
																<?php /* translators: Display todo title name */ ?>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo BPTODO_PLUGIN_URL; ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) { ?>
																		<?php /* translators: Display todo title name */ ?>
																		<li><button data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger"  data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo BPTODO_PLUGIN_URL; ?>assets/css/images/edit.svg" /></button></li>
																	<?php } else { ?>
																	<?php } ?>
																</ul>
															</li>
														</ul>
													<?php } ?>
												</div>
											</div>
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
									<div class="bptodo-panel">
										<div class="todo-detail">
											<div class="bp-todo-reminder">
												<div class="todo-header">
													<ul  class="todo-header-head">
														<li class="bptodo-check"><?php esc_html_e( 'Mark', 'wb-todo' ); ?></li>
														<li class="bptodo-priority"><?php esc_html_e( 'Priority', 'wb-todo' ); ?></li>
														<li class="bptodo-date"><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></li>
														<li class="bptodo-task"><?php esc_html_e( 'Task', 'wb-todo' ); ?></li>
														<li class="bptodo-task bptodo-description"><?php esc_html_e( 'Task Description', 'wb-todo' ); ?></li>
														<li class="bptodo-actions bptodo-complete"><?php esc_html_e( 'Actions', 'wb-todo' ); ?></li>
													</ul>
												</div>
												<div class="todo-header-list">
													<?php foreach ( $todo_list['tomorrow'] as $tid ) { ?>
														<?php
														$todo       = get_post( $tid );
														$todo_title = $todo->post_title;
														$todo_view_url = get_permalink( $tid );
														$todo_edit_url = bp_core_get_userlink( get_current_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status  = get_post_meta( $todo->ID, 'todo_status', true );
														$due_date_str = $due_date_td_class    = '';
														$curr_date    = date_create( date( 'Y-m-d' ) );
														$due_date     = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff         = date_diff( $curr_date, $due_date );
														$diff_days    = $diff->format( '%R%a' );
														if ( $diff_days < 0 ) {
															/* Translators: Number of expiry days */
															$due_date_str      = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 === $diff_days ) {
															$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
															$due_date_td_class = 'bptodo-expires-today';
														} else {
															/* Translators: Number of left days */
															$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
														}
														if ( 'complete' === $todo_status ) {
															$due_date_str      = esc_html__( 'Completed!', 'wb-todo' );
															$due_date_td_class = '';
														}
														?>
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row">
														<?php
																$checked = '';
																$checked_class = 'todo-uncomplete';
																if ( 'complete' == $todo_status ) {
																	$checked = 'checked';
																	$checked_class = 'todo-complete';
																}
																?>
														<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
															<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>																
															</li>
															<li class="bptodo-priority"><?php esc_html_e( 'Priority', 'wb-todo' ); ?></li>
															<li class="bptodo-date <?php echo esc_html( $due_date_td_class ); if ( 'complete' === $todo_status ) {echo esc_attr( $class );}?>">
																<?php echo esc_html( $due_date_str ); ?>
															</li>
															<li class="bptodo-task <?php if ( 'complete' === $todo_status ) { echo esc_attr( $class );} ?>">
																<?php echo esc_html( $todo_title ); ?>
															</li>
															<li class="bptodo-task <?php if ( 'complete' === $todo_status ) { echo esc_attr( $class );} ?>">
																<?php echo esc_html( wp_trim_words( $todo->post_content, 15 ) ); ?>
																<a href="<?php echo esc_attr( $todo_view_url ); ?>" target="_blank"><?php echo esc_html( 'Read More' ); ?></a>
															</li>													
															<li class="bp-to-do-actions">
																<ul class="bp-to-do-actions-list">
																<?php /* Translators: Display todo title name */ ?>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo BPTODO_PLUGIN_URL; ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) { ?>
																		<?php /* Translators: Display todo title name */ ?>
																		<li><button data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger"  data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo BPTODO_PLUGIN_URL; ?>assets/css/images/edit.svg" /></button></li>
																	<?php } else { ?>
																	<?php } ?>
																</ul>
															</li>
														</ul>
													<?php } ?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						<?php } ?>

						<!-- TASKS FOR SAMEDAY -->
						<?php if ( ! empty( $todo_list['future'] ) ) { ?>
							<div class="bptodo-admin-row">
								<div>
									<button class="bptodo-item"><?php esc_html_e( 'SAMEDAY', 'wb-todo' ); ?></button>
									<div class="bptodo-panel">
										<div class="todo-detail">
											<div class="bp-todo-reminder">
												<div class="todo-header">
													<ul class="todo-header-head">
														<li class="bptodo-check"><?php esc_html_e( 'Mark', 'wb-todo' ); ?></li>
														<li class="bptodo-priority"><?php esc_html_e( 'Priority', 'wb-todo' ); ?></li>
														<li class="bptodo-date"><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></li>
														<li class="bptodo-task"><?php esc_html_e( 'Task', 'wb-todo' ); ?></li>
														<li class="bptodo-task bptodo-description"><?php esc_html_e( 'Task Description', 'wb-todo' ); ?></li>
														<li class="bptodo-actions bptodo-complete"><?php esc_html_e( 'Actions', 'wb-todo' ); ?></li>
													</ul>
												</div>
												<div class="todo-header-list">
													<?php foreach ( $todo_list['future'] as $tid ) { ?>
														<?php
														$todo       = get_post( $tid );
														$todo_title = $todo->post_title;
														$todo_view_url = get_permalink( $tid );
														$todo_edit_url = bp_core_get_userlink( get_current_user_id(), false, true ) . $profile_menu_slug . '/add?args=' . $tid;

														$todo_status  = get_post_meta( $todo->ID, 'todo_status', true );
														$due_date_str = $due_date_td_class    = '';
														$curr_date    = date_create( date( 'Y-m-d' ) );
														$due_date     = date_create( get_post_meta( $todo->ID, 'todo_due_date', true ) );
														$diff         = date_diff( $curr_date, $due_date );
														$diff_days    = $diff->format( '%R%a' );
														if ( $diff_days < 0 ) {
															/* Translators: Number of expiry days */
															$due_date_str      = sprintf( esc_html__( 'Expired %d days ago!', 'wb-todo' ), abs( $diff_days ) );
															$due_date_td_class = 'bptodo-expired';
														} elseif ( 0 === $diff_days ) {
															$due_date_str      = esc_html__( 'Today is the last day to complete. Hurry Up!', 'wb-todo' );
															$due_date_td_class = 'bptodo-expires-today';
														} else {
															/* Translators: Number of left days */
															$due_date_str = sprintf( esc_html__( '%d days left to complete the task!', 'wb-todo' ), abs( $diff_days ) );
														}
														if ( 'complete' === $todo_status ) {
															$due_date_str      = esc_html__( 'Completed!', 'wb-todo' );
															$due_date_td_class = '';
														}
														?>
														<ul id="bptodo-row-<?php echo esc_attr( $tid ); ?>" class="todo-header-list-row">
														<?php
																$checked = '';
																$checked_class = 'todo-uncomplete';
																if ( 'complete' == $todo_status ) {
																	$checked = 'checked';
																	$checked_class = 'todo-complete';
																}
																?>
														<li id="bptodo-complete-li-<?php echo esc_attr( $tid ); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr( $checked_class ); ?>" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Complete: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>">
															<label><input type="checkbox" class="option-input checkbox" <?php echo esc_attr( $checked ); ?> /></label>																
															</li>
															<li class="bptodo-priority"><?php esc_html_e( 'Priority', 'wb-todo' ); ?></li>
															<li class="bptodo-date <?php echo esc_attr( $due_date_td_class ); if ( 'complete' === $todo_status ) { echo esc_attr( $class );	}?>">
																<?php echo esc_html( $due_date_str ); ?>
															</li>
															<li class="bptodo-task <?php if ( 'complete' === $todo_status ) { echo esc_attr( $class );} ?>">
																<?php echo esc_html( $todo_title ); ?>
															</li>
															<li class="bptodo-task <?php if ( 'complete' === $todo_status ) { echo esc_attr( $class );} ?>">
																<?php echo esc_html( wp_trim_words( $todo->post_content, 15 ) ); ?>
																<a href="<?php echo esc_attr( $todo_view_url ); ?>" target="_blank"><?php echo esc_html( 'Read More' ); ?></a>
															</li>
															<li class="bp-to-do-actions">
																<ul class="bp-to-do-actions-list">
																<?php /* Translators: Display todo title name */ ?>
																	<li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr( $tid ); ?>" title="<?php echo sprintf( esc_html__( 'Remove: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo BPTODO_PLUGIN_URL; ?>assets/css/images/trash-alt.svg" /></a></li>
																	<?php if ( 'complete' !== $todo_status ) { ?>
																		<?php /* Translators: Display todo title name */ ?>
																		<li><button data-tid="<?php echo esc_attr( $tid ); ?>" class="trigger"  data-modal-trigger="trigger" title="<?php echo sprintf( esc_html__( 'Edit: %s', 'wb-todo' ), esc_attr( $todo_title ) ); ?>"><img src="<?php echo BPTODO_PLUGIN_URL; ?>assets/css/images/edit.svg" /></button></li>
																	<?php } else { ?>
																	<?php } ?>
																</ul>
															</li>
														</ul>
													<?php } ?>
												</div>
											</div>
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
