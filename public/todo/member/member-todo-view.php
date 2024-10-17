<?php
$group_id     = get_post_meta( $todo_id, 'todo_group_id', true );
$current_user = get_current_user_id();

if ( empty( $group_id ) ) {
	return;
}

$can_view = bptodo_enable_report_view( $current_user, $group_id );

if ( ! $can_view ) {
	return; // Bail if the user does not have permission to view the report.
}

$args = array(
	'group_id'            => $group_id,
	'exclude_admins_mods' => apply_filters( 'bptodo_exclude_moderator_view', true ),
);

$group_members     = groups_get_group_members( $args );
$group_members_ids = array_column( $group_members['members'], 'ID' );
if ( ! empty( $group_members_ids ) && is_array( $group_members_ids ) ) {
?>

<table class="bp-todo-report">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Members', 'wb-todo' ); ?></th>
						<th><?php esc_html_e( 'Status', 'wb-todo' ); ?></th>
						<th><?php esc_html_e( 'Time', 'wb-todo' ); ?></th>
					</tr>
				</thead>
				<tbody>
                   <?php
					$parent_todo = get_post_meta( $todo_id, 'todo_primary_id', true );
					
					if ( $parent_todo ) {
						$assoc_todos = get_post_meta( $parent_todo, 'botodo_associated_todo', true );						
						if ( $assoc_todos ) {
							foreach ( $assoc_todos as $assoc_todo ) {
								$member_todo_status  = get_post_meta( $assoc_todo, 'todo_status', true );
								$todo_author_id      = get_post_field( 'post_author', $assoc_todo );
								$member_todo_time    = get_post_meta( $assoc_todo, 'todo_complete_time', true );
								$member_todo_time    = $member_todo_time ? human_time_diff( current_time( 'timestamp' ), $member_todo_time ) . ' ago' : '-';

								if ( in_array( $todo_author_id, $group_members_ids ) ) {
									$author_name = get_the_author_meta( 'display_name', $todo_author_id );
					 				?>
					 				<tr>
					 					<td><?php echo esc_html( ucfirst( $author_name ) ); ?></td>
					 					<td><?php echo esc_html( $member_todo_status ); ?></td>
										<td><?php echo esc_html( $member_todo_time ); ?></td>
					 				</tr>
					 				<?php
								}
							}
						}
					}
					?>
									
				</tbody>
			</table>
			<?php
}