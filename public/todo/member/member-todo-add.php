<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

		$todo_cats = get_terms( array(
    'taxonomy'   => 'todo_category',
    'orderby'    => 'name',
    'hide_empty' => false,
) );

global $bptodo;
$profile_menu_label = $bptodo->profile_menu_label;
$profile_menu_slug  = $bptodo->profile_menu_slug;
$name               = bp_get_displayed_user_username();

$displayed_uid  = bp_displayed_user_id();
$form_post_link = bp_core_get_userlink( $displayed_uid, false, true ) . $profile_menu_slug . '/add/';
// bp_nouveau_template_notices();
$bptodo_req = '';
if ( 'yes' == $bptodo->req_duedate ) {
	$bptodo_req = 'required';
}

?>
<form class="bptodo-form-add" action="" method="post" id="myForm">
	<table class="bptodo-add-todo-tbl">
		<?php do_action( 'bptodo_add_field_before_default_listing', $displayed_uid, $form_post_link ); ?>
		<tr>
			<td>
				<?php esc_html_e( 'Category', 'wb-todo' ); ?>
			</td>
			<td>
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
					<?php /* translators: Display plural label name */ ?>
					<input type="text" id="todo-category-name" placeholder="<?php echo sprintf( esc_html__( '%1$s category', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?>">
					<?php $add_cat_nonce = wp_create_nonce( 'bptodo-add-todo-category' ); ?>
					<input type="hidden" id="bptodo-add-category-nonce" value="<?php echo esc_html( $add_cat_nonce ); ?>">
					<button type="button" id="add-todo-cat" class="btn button"><?php esc_html_e( 'Add', 'wb-todo' ); ?></button>
				</div>
				<?php } ?>
			</td>
		</tr>

		<tr>
			<td>
				<?php esc_html_e( 'Title', 'wb-todo' ); ?>
			</td>
			<td>
				<input type="text" placeholder="<?php esc_html_e( 'Title', 'wb-todo' ); ?>" name="todo_title" required class="bptodo-text-input">
			</td>
		</tr>

		<tr>
			<td>
				<?php esc_html_e( 'Summary', 'wb-todo' ); ?>
			</td>
			<td>
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
			<td>
				<?php esc_html_e( 'Due Date', 'wb-todo' ); ?>
			</td>
			<td>
				<input type="text" placeholder="<?php esc_html_e( 'Due Date', 'wb-todo' ); ?>" name="todo_due_date" class="todo_due_date bptodo-text-input"<?php echo esc_attr( $bptodo_req ); ?>>
			</td>
		</tr>

		<tr>
			<td>
				<?php esc_html_e( 'Priority', 'wb-todo' ); ?>
			</td>
			<td>
				<select name="todo_priority" id="bp_todo_priority" >
					<option value=""><?php esc_html_e( '--Select--', 'wb-todo' ); ?></option>
					<option value="critical"><?php esc_html_e( 'Critical', 'wb-todo' ); ?></option>
					<option value="high"><?php esc_html_e( 'High', 'wb-todo' ); ?></option>
					<option value="normal" selected><?php esc_html_e( 'Normal', 'wb-todo' ); ?></option>
				</select>
			</td>
		</tr>

		<?php $change_submit_btn = apply_filters( 'bptodo_change_submit_button', $change = false ); ?>
		<?php if ( ! $change_submit_btn ) { ?>
			<tr>
				<td></td>
				<td>
					<?php wp_nonce_field( 'wp-bp-todo', 'save_new_todo_data_nonce' ); ?>
					<?php /* translators: Display plural label name */ ?>
					<input id="bp-add-new-todo" type="submit" name="todo_create" value="<?php echo sprintf( esc_html__( 'Submit %s', 'wb-todo' ), esc_attr( $profile_menu_label ) ); ?>" >
				</td>
			</tr>
			<?php
		} else {
			do_action( 'bptodo_change_submit_button_action', $profile_menu_label );
		}
		?>
		<?php do_action( 'bptodo_add_field_after_default_listing', $displayed_uid, $form_post_link ); ?>
	</table>
</form>

