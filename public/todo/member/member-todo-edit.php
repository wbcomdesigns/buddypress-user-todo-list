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
$todo_id            = isset( $_GET['args'] ) ? sanitize_text_field( wp_unslash( $_GET['args'] ) ) : 0;
$todo_cats          = get_terms( array(
	'taxonomy'   => 'todo_category',
	'orderby'    => 'name',
	'hide_empty' => false,
));

$todo           = get_post( $todo_id );
$todo_cat       = wp_get_object_terms( $todo_id, 'todo_category' );
$todo_cat_id    = ! empty( $todo_cat ) && is_array( $todo_cat ) ? $todo_cat[0]->term_id : 0;
$todo_due_date  = get_post_meta( $todo_id, 'todo_due_date', true );
$todo_priority  = get_post_meta( $todo_id, 'todo_priority', true ) ?: 'normal';
$bptodo_req     = $bptodo->req_duedate === 'yes' ? 'required' : '';

?>

<form action="" method="post" id="bptodo_edit_form_popup">
	<table class="bptodo-add-todo-tbl">
		<?php do_action( 'bptodo_add_field_before_edit_default_listing', $displayed_uid, $form_post_link ); ?>

		<tr>
			<td><?php esc_html_e( 'Category', 'wb-todo' ); ?></td>
			<td>
				<div>
					<select name="todo_cat" id="bp_todo_categories" required>
						<option value=""><?php esc_html_e( '--Select--', 'wb-todo' ); ?></option>
						<?php foreach ( $todo_cats as $todo_cat ) : ?>
							<option value="<?php echo esc_attr( $todo_cat->name ); ?>" <?php selected( $todo_cat_id, $todo_cat->term_id ); ?>>
								<?php echo esc_html( $todo_cat->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php if ( $bptodo->allow_user_add_category === 'yes' ) : ?>
						<a href="javascript:void(0);" class="add-todo-category"><span class="add-cat-icon"></span></a>
					<?php endif; ?>
				</div>
				<?php if ( $bptodo->allow_user_add_category === 'yes' ) : ?>
					<div class="add-todo-cat-row">
						<input type="text" id="todo-category-name" placeholder="<?php echo esc_attr( sprintf( esc_html__( '%1$s category', 'wb-todo' ), $profile_menu_label ) ); ?>">
						<input type="hidden" id="bptodo-add-category-nonce" value="<?php echo esc_attr( wp_create_nonce( 'bptodo-add-todo-category' ) ); ?>">
						<button type="button" id="add-todo-cat" class="btn button"><?php esc_html_e( 'Add', 'wb-todo' ); ?></button>
					</div>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<td><?php esc_html_e( 'Title', 'wb-todo' ); ?></td>
			<td>
				<input type="text" name="todo_title" value="<?php echo esc_attr( $todo->post_title ); ?>" placeholder="<?php esc_html_e( 'Title', 'wb-todo' ); ?>" required class="bptodo-text-input">
			</td>
		</tr>

		<tr>
			<td><?php esc_html_e( 'Summary', 'wb-todo' ); ?></td>
			<td>
				<?php
				$settings = array(
					'media_buttons' => false,
					'editor_height' => 200,
					'quicktags'     => false,
				);
				wp_editor( $todo->post_content, 'bptodo-summary-input', $settings );
				?>
			</td>
		</tr>

		<tr>
			<td><?php esc_html_e( 'Due Date', 'wb-todo' ); ?></td>
			<td>
				<input type="date" name="todo_due_date" value="<?php echo esc_attr( $todo_due_date ); ?>" placeholder="<?php esc_html_e( 'Due Date', 'wb-todo' ); ?>" class="todo_due_date bptodo-text-input" <?php echo esc_attr( $bptodo_req ); ?>>
			</td>
		</tr>

		<tr>
			<td><?php esc_html_e( 'Priority', 'wb-todo' ); ?></td>
			<td>
				<select name="todo_priority" id="bp_todo_priority">
					<option value=""><?php esc_html_e( '--Select--', 'wb-todo' ); ?></option>
					<option value="critical" <?php selected( $todo_priority, 'critical' ); ?>><?php esc_html_e( 'Critical', 'wb-todo' ); ?></option>
					<option value="high" <?php selected( $todo_priority, 'high' ); ?>><?php esc_html_e( 'High', 'wb-todo' ); ?></option>
					<option value="normal" <?php selected( $todo_priority, 'normal' ); ?>><?php esc_html_e( 'Normal', 'wb-todo' ); ?></option>
				</select>
			</td>
		</tr>

		<?php if ( ! apply_filters( 'bptodo_change_todo_edit_button', false ) ) : ?>
			<tr>
				<td></td>
				<td>
					<?php wp_nonce_field( 'wp-bp-todo', 'save_update_todo_data_nonce' ); ?>
					<input type="hidden" name="hidden_todo_id" value="<?php echo esc_attr( $todo_id ); ?>">
					<input type="submit" id="todo_update" name="todo_update" value="<?php echo esc_attr( sprintf( esc_html__( 'Update %s', 'wb-todo' ), $profile_menu_label ) ); ?>">
				</td>
			</tr>
		<?php else : ?>
			<?php do_action( 'bptodo_change_todo_edit_button_action', $profile_menu_label ); ?>
		<?php endif; ?>

		<?php do_action( 'bptodo_add_field_after_edit_default_listing', $displayed_uid, $form_post_link ); ?>
	</table>
</form>
