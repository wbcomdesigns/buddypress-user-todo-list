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
$displayed_uid      = bp_displayed_user_id();
$form_post_link     = bp_core_get_userlink( $displayed_uid, false, true ) . $profile_menu_slug;
if ( isset( $_GET['args'] ) ) {
	$todo_id = sanitize_text_field( wp_unslash( $_GET['args'] ) );
}


$todo_cats = get_terms( 'todo_category', 'orderby=name&hide_empty=0' );
$todo      = get_post( $todo_id );

$todo_cat    = wp_get_object_terms( $todo_id, 'todo_category' );
$todo_cat_id = 0;
if ( ! empty( $todo_cat ) && is_array( $todo_cat ) ) {
	$todo_cat_id = $todo_cat[0]->term_id;
}
$todo_due_date = get_post_meta( $todo_id, 'todo_due_date', true );
$todo_priority = get_post_meta( $todo_id, 'todo_priority', true );
?>
<form action="<?php echo esc_attr( $form_post_link ); ?>" method="post">
	<table class="bptodo-add-todo-tbl">
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
						<option
						<?php
						if ( $todo_cat_id == $todo_cat->term_id ) {
							echo 'selected="selected"';}
?>
 value="<?php echo esc_html( $todo_cat->name ); ?>">
							<?php echo esc_html( $todo_cat->name ); ?>
						</option>
						<?php } ?>
						<?php } ?>
					</select>
					<?php if ( 'yes' == $bptodo->allow_user_add_category ) { ?>
					<a href="javascript:void(0);" class="add-todo-category"><i class="fas fa-plus" aria-hidden="true"></i></a>
					<?php } ?>
				</div>
				<?php if ( 'yes' == $bptodo->allow_user_add_category ) { ?>
				<div class="add-todo-cat-row">
					<input type="text" id="todo-category-name" placeholder="<?php echo sprintf( esc_html__( '%1$s category', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?>">
					<?php $add_cat_nonce = wp_create_nonce( 'bptodo-add-todo-category' ); ?>
					<input type="hidden" id="bptodo-add-category-nonce" value="<?php echo esc_attr( $add_cat_nonce ); ?>">
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
				<input value="<?php echo esc_html( $todo->post_title ); ?>" type="text" placeholder="<?php esc_html_e( 'Title', 'wb-todo' ); ?>" name="todo_title" required class="bptodo-text-input">
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
				wp_editor( $todo->post_content, 'bptodo-summary-input', $settings );
				?>
			</td>
		</tr>

		<tr>
			<td width="20%">
				<?php esc_html_e( 'Due Date', 'wb-todo' ); ?>
			</td>
			<td width="80%">
				<input type="text" placeholder="<?php esc_html_e( 'Due Date', 'wb-todo' ); ?>" class="todo_due_date bptodo-text-input" name="todo_due_date" value="<?php echo esc_html( $todo_due_date ); ?>" required>
			</td>
		</tr>

		<tr>
			<td width="20%">
				<?php esc_html_e( 'Priority', 'wb-todo' ); ?>
			</td>
			<td width="80%">
				<select name="todo_priority" id="bp_todo_priority" required>
					<option value=""><?php esc_html_e( '--Select--', 'wb-todo' ); ?></option>
					<option value="critical" <?php selected( $todo_priority, 'critical' ); ?>><?php esc_html_e( 'Critical', 'wb-todo' ); ?></option>
					<option value="high" <?php selected( $todo_priority, 'high' ); ?>><?php esc_html_e( 'High', 'wb-todo' ); ?></option>
					<option value="normal" <?php selected( $todo_priority, 'normal' ); ?>><?php esc_html_e( 'Normal', 'wb-todo' ); ?></option>
				</select>
			</td>
		</tr>

		<tr>
			<td width="20%"></td>
			<td width="80%">
				<?php wp_nonce_field( 'wp-bp-todo', 'save_update_todo_data_nonce' ); ?>
				<input type="hidden" name="hidden_todo_id" value="<?php echo esc_attr( $todo_id ); ?>">
				<input type="submit" id="todo_update" name="todo_update" value="<?php echo sprintf( esc_html__( 'Update %s', 'wb-todo' ), $profile_menu_label ); ?>">
			</td>
		</tr>
	</table>
</form>
