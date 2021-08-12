<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $bp;
$group_members = groups_get_group_members(
	array(
		'group_id'   => $bp->groups->current_group->id,
		//'group_role' => array( 'member', 'mod', 'administrator' ),
		'exclude_admins_mods' => false,
		// 'exclude_admins_mods' => apply_filters( 'bptodo_exclude_modrator_view', true ),
	)
);
$group_members_ids = array();
if ( isset( $group_members['members'] ) ) {
	foreach ( $group_members['members'] as $member ) {
		$group_members_ids[] = $member->ID;
	}
}

$group_id = ( $bp->groups->current_group->id ) ? $bp->groups->current_group->id : 0;

$todo_cats = get_terms( 'todo_category', 'orderby=name&hide_empty=0' );

global $bptodo;
$profile_menu_label = $bptodo->profile_menu_label;
$profile_menu_slug  = $bptodo->profile_menu_slug;
$name               = bp_get_displayed_user_username();


$displayed_uid  = get_current_user_id();
$form_post_link = bp_get_group_permalink() . strtolower( $profile_menu_label ) . '/list';

if ( ! empty( $group_members_ids ) ) {
	$current_user = get_current_user_id();
	$cu_index     = array_search( $current_user, $group_members_ids );
	unset( $group_members_ids[ $cu_index ] );

}

// Save todo items.
if ( isset( $_POST['group_todo_create'] ) && wp_verify_nonce( $_POST['group_save_new_todo_data_nonce'], 'wp-bp-todo' ) ) {

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
		$summary = wp_kses_post( wp_unslash( $_POST['bptodo-summary-input'] ) );
	}

	if ( isset( $_POST['todo_priority'] ) ) {
		$priority = sanitize_text_field( wp_unslash( $_POST['todo_priority'] ) );
	}

	$todo_group_id = '';
	if ( isset( $_POST['todo_group_id'] ) ) {
		$todo_group_id = sanitize_text_field( wp_unslash( $_POST['todo_group_id'] ) );
	}
	if ( empty( $todo_group_id ) ) {
		$todo_group_id = $group_id;
	}

	$taxonomy = 'todo_category';

	/*
	============================================
	=            Insert primary To-Do            =
	============================================*/

	$args            = array(
		'post_type'    => 'bp-todo',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_content' => $summary,
		'post_author'  => $displayed_uid,
	);
	$primary_todo_id = wp_insert_post( $args );
	update_post_meta( $primary_todo_id, 'todo_status', 'incomplete' );
	update_post_meta( $primary_todo_id, 'todo_due_date', $due_date );
	update_post_meta( $primary_todo_id, 'todo_priority', $priority );
	update_post_meta( $primary_todo_id, 'todo_last_day_mail_sent', 'no' );
	update_post_meta( $primary_todo_id, 'todo_last_day_notification_sent', 'no' );

	if ( ! empty( $todo_group_id ) ) {
		update_post_meta( $primary_todo_id, 'todo_group_id', $todo_group_id );
	}
	wp_set_object_terms( $primary_todo_id, $cat, $taxonomy );

	update_post_meta( $primary_todo_id, 'todo_primary_id', $primary_todo_id );

	// update post meta for to do creator.
	update_post_meta( $primary_todo_id, 'todo_creator_id', get_current_user_id() );

	/*=====  End of Insert primary To-Do  ======*/
	$associated_todos = array();

	foreach ( (array) $group_members_ids as $key => $member_id ) {
		$args    = array(
			'post_type'    => 'bp-todo',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $summary,
			'post_author'  => $member_id,
		);
		$post_id = wp_insert_post( $args );

		$associated_todos[] = $post_id;

		update_post_meta( $post_id, 'todo_status', 'incomplete' );
		update_post_meta( $post_id, 'todo_due_date', $due_date );
		update_post_meta( $post_id, 'todo_priority', $priority );
		update_post_meta( $post_id, 'todo_last_day_mail_sent', 'no' );
		update_post_meta( $post_id, 'todo_last_day_notification_sent', 'no' );

		if ( ! empty( $todo_group_id ) ) {
			update_post_meta( $post_id, 'todo_group_id', $todo_group_id );
		}

		wp_set_object_terms( $post_id, $cat, $taxonomy );

		// update post meta for to do creator.
		update_post_meta( $post_id, 'todo_creator_id', get_current_user_id() );

		update_post_meta( $post_id, 'todo_primary_id', $primary_todo_id );
	}

	update_post_meta( $primary_todo_id, 'botodo_associated_todo', $associated_todos );

	?>
		<script>
			window.location.replace('<?php echo esc_url( $form_post_link ); ?>');
		</script>
	<?php

}

?>
<form class="bptodo-form-add" action="" method="post" id="myForm">
	<table class="bptodo-add-todo-tbl">
		<?php do_action( 'bp_group_todo_add_field_before_default_listing', $displayed_uid, $form_post_link ); ?>
		<tr>
			<td width="20%">
				<?php esc_html_e( 'Category', 'wb-todo' ); ?>
			</td>
			<td width="80%">
				<div>
					<select name="todo_cat" id="bp_todo_categories" required>
						<option value=""><?php esc_html_e( '--Select--', 'wb-todo' ); ?></option>
						<?php if ( isset( $todo_cats ) ) { ?>
							<?php foreach ( $todo_cats as $todo_cat ) { ?>
						<option value="<?php echo esc_html( $todo_cat->name ); ?>"><?php echo esc_html( $todo_cat->name ); ?></option>
						<?php } ?>
						<?php } ?>
					</select>
					<?php if ( 'yes' === $bptodo->allow_user_add_category ) { ?>
					<a href="javascript:void(0);" class="add-todo-category"><i class="fa fa-plus" aria-hidden="true"></i></a>
					<?php } ?>
				</div>
				<?php if ( 'yes' === $bptodo->allow_user_add_category ) { ?>
				<div class="add-todo-cat-row">
					<?php /* Translators: Display the plural label name */ ?>
					<input type="text" id="todo-category-name" placeholder="<?php echo sprintf( esc_html__( '%1$s category', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?>">
					<?php $add_cat_nonce = wp_create_nonce( 'bptodo-add-todo-category' ); ?>
					<input type="hidden" id="bptodo-add-category-nonce" value="<?php echo esc_html( $add_cat_nonce ); ?>">
					<button type="button" id="add-todo-cat"><?php esc_html_e( 'Add', 'wb-todo' ); ?></button>
				</div>
				<?php } ?>
			</td>
		</tr>

		<tr>
			<td width="20%">
				<?php esc_html_e( 'Title', 'wb-todo' ); ?>
			</td>
			<td width="80%">
				<input type="text" placeholder="<?php esc_html_e( 'Title', 'wb-todo' ); ?>" name="todo_title" required class="bptodo-text-input">
			</td>
		</tr>

		<tr>
			<td width="20%">
				<?php esc_html_e( 'Summary', 'wb-todo' ); ?>
			</td>
			<td width="80%">
				<?php
				$settings = array(
					'media_buttons' => true,
					'editor_height' => 200,
				);
				wp_editor( '', 'bptodo-summary-input', $settings );
				?>
			</td>
		</tr>

		<tr>
			<td width="20%">
				<?php esc_html_e( 'Due Date', 'wb-todo' ); ?>
			</td>
			<td width="80%">
				<input type="text" placeholder="<?php esc_html_e( 'Due Date', 'wb-todo' ); ?>" name="todo_due_date" class="todo_due_date bptodo-text-input" required>
			</td>
		</tr>

		<tr>
			<td width="20%">
				<?php esc_html_e( 'Priority', 'wb-todo' ); ?>
			</td>
			<td width="80%">
				<select name="todo_priority" id="bp_todo_priority" required>
					<option value=""><?php esc_html_e( '--Select--', 'wb-todo' ); ?></option>
					<option value="critical"><?php esc_html_e( 'Critical', 'wb-todo' ); ?></option>
					<option value="high"><?php esc_html_e( 'High', 'wb-todo' ); ?></option>
					<option value="normal"><?php esc_html_e( 'Normal', 'wb-todo' ); ?></option>
				</select>
			</td>
		</tr>

		<tr>
			<td width="20%">
			</td>
			<td>
				<input type="hidden" name="todo_group_id" value="<?php echo esc_attr( $group_id ); ?>">
			</td>
		</tr>

		<?php $change_submit_btn = apply_filters( 'bp_group_todo_change_submit_button', $change = false ); ?>
		<?php if ( ! $change_submit_btn ) { ?>
			<tr>
				<td width="20%"></td>
				<td width="80%">
					<?php wp_nonce_field( 'wp-bp-todo', 'group_save_new_todo_data_nonce' ); ?>
					<?php /* Translators: Display the plural label name */ ?>
					<input id="bp-add-new-todo" type="submit" name="group_todo_create" value="<?php echo sprintf( esc_html__( 'Submit %s', 'wb-todo' ), esc_attr( $profile_menu_label ) ); ?>" >
				</td>
			</tr>
			<?php
		} else {
			do_action( 'bp_group_todo_change_submit_button_action', $profile_menu_label );
		}
		?>
		<?php do_action( 'bp_group_todo_add_field_after_default_listing', $displayed_uid, $form_post_link ); ?>
	</table>
</form>
