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

if ( isset( $_GET['args'] ) ) { // phpcs:ignore
	$todo_id = sanitize_text_field( wp_unslash( $_GET['args'] ) ); // phpcs:ignore
}

$todo_cats = get_terms(array(
	'taxonomy'   => 'todo_category',
	'orderby'    => 'name',
	'hide_empty' => false,
));

$todo = get_post( $todo_id );

$todo_cat = wp_get_object_terms( $todo_id, 'todo_category' );

$todo_cat_id = 0;
if ( ! empty( $todo_cat ) && is_array( $todo_cat ) ) {
	$todo_cat_id = $todo_cat[0]->term_id;
}

$todo_due_date = get_post_meta( $todo_id, 'todo_due_date', true );
$todo_priority = get_post_meta( $todo_id, 'todo_priority', true );
if ( empty( $todo_priority ) ) {
	$todo_priority = 'normal';
}

$bptodo_req = '';
if ( 'yes' === $bptodo->req_duedate ) {
	$bptodo_req = 'required';
}
?>

<form action="" method="post" id="bptodo_edit_form_popup">
	<table class="bptodo-add-todo-tbl">
		<?php do_action( 'bptodo_add_field_before_edit_default_listing', $displayed_uid, $form_post_link ); ?>
		<tr>
			<td><?php esc_html_e( 'Category', 'bp-user-to-do-list' ); ?></td>
			<td>
				<div class="bp_todo_categories">
					<select name="todo_cat" id="bp_todo_categories" aria-label="<?php esc_attr_e( 'Category', 'bp-user-to-do-list' ); ?>" required>
						<option value=""><?php esc_html_e( '--Select--', 'bp-user-to-do-list' ); ?></option>
						<?php if ( isset( $todo_cats ) && ! empty( $todo_cats ) ) { ?>
							<?php foreach ( $todo_cats as $cat ) { ?>
								<option value="<?php echo esc_html( $cat->name ); ?>" <?php selected( $todo_cat_id, $cat->term_id ); ?>>
									<?php echo esc_html( $cat->name ); ?>
								</option>
							<?php } ?>
						<?php } ?>
					</select>
					<?php if ( 'yes' === $bptodo->allow_user_add_category ) { ?>
						<a href="javascript:void(0);" class="add-todo-category"><span class="add-cat-icon"></span></a>
					<?php } ?>
				</div>
				<?php if ( 'yes' === $bptodo->allow_user_add_category ) { ?>
					<div class="add-todo-cat-row">
						<?php /* translators: %1$s: the configured todo label (singular), e.g. "Task". */ ?>
						<input type="text" id="todo-category-name" aria-label="<?php echo esc_attr( sprintf( esc_html__( 'New %1$s category name', 'bp-user-to-do-list' ), esc_html( $profile_menu_label ) ) ); ?>" placeholder="<?php echo esc_attr( sprintf( esc_html__( '%1$s category', 'bp-user-to-do-list' ), esc_html( $profile_menu_label ) ) ); ?>">
						<input type="hidden" id="bptodo-add-category-nonce" value="<?php echo esc_attr( wp_create_nonce( 'bptodo-add-todo-category' ) ); ?>">
						<button type="button" id="add-todo-cat" class="btn button"><?php esc_html_e( 'Add', 'bp-user-to-do-list' ); ?></button>
					</div>
				<?php } ?>
			</td>
		</tr>

		<tr>
			<td><?php esc_html_e( 'Title', 'bp-user-to-do-list' ); ?></td>
			<td>
				<input value="<?php echo esc_html( $todo->post_title ); ?>" type="text" aria-label="<?php esc_attr_e( 'Title', 'bp-user-to-do-list' ); ?>" placeholder="<?php esc_html_e( 'Title', 'bp-user-to-do-list' ); ?>" name="todo_title" required class="bptodo-text-input">
			</td>
		</tr>

		<tr>
			<td><?php esc_html_e( 'Summary', 'bp-user-to-do-list' ); ?></td>
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
			<td><?php esc_html_e( 'Due Date', 'bp-user-to-do-list' ); ?></td>
			<td>
				<input type="date" aria-label="<?php esc_attr_e( 'Due Date', 'bp-user-to-do-list' ); ?>" placeholder="<?php esc_html_e( 'Due Date', 'bp-user-to-do-list' ); ?>" class="todo_due_date bptodo-text-input" name="todo_due_date" value="<?php echo esc_html( $todo_due_date ); ?>" <?php echo esc_attr( $bptodo_req ); ?>>
			</td>
		</tr>

		<tr>
			<td><?php esc_html_e( 'Priority', 'bp-user-to-do-list' ); ?></td>
			<td>
				<select name="todo_priority" id="bp_todo_priority" aria-label="<?php esc_attr_e( 'Priority', 'bp-user-to-do-list' ); ?>">
					<option value=""><?php esc_html_e( '--Select--', 'bp-user-to-do-list' ); ?></option>
					<option value="critical" <?php selected( $todo_priority, 'critical' ); ?>><?php esc_html_e( 'Critical', 'bp-user-to-do-list' ); ?></option>
					<option value="high" <?php selected( $todo_priority, 'high' ); ?>><?php esc_html_e( 'High', 'bp-user-to-do-list' ); ?></option>
					<option value="normal" <?php selected( $todo_priority, 'normal' ); ?>><?php esc_html_e( 'Normal', 'bp-user-to-do-list' ); ?></option>
				</select>
			</td>
		</tr>

		<?php $change_edit_btn = apply_filters( 'bptodo_change_todo_edit_button', false ); ?>
		<?php if ( ! $change_edit_btn ) { ?>
			<tr>
				<td></td>
				<td>
					<?php wp_nonce_field( 'wp-bp-todo', 'save_update_todo_data_nonce' ); ?>
					<input type="hidden" name="hidden_todo_id" value="<?php echo esc_attr( $todo_id ); ?>">
					<!-- Hidden input ensures todo_update is submitted even when button is disabled by loading state -->
					<input type="hidden" name="todo_update" value="1">
					<?php /* translators: %s: the configured todo label (singular), e.g. "Task". */ ?>
					<input type="submit" data-tid="<?php echo esc_attr( $todo_id ); ?>" id="todo_update" value="<?php echo esc_html( sprintf( esc_html__( 'Update %s', 'bp-user-to-do-list' ), esc_attr( $profile_menu_label ) ) ); ?>">
				</td>
			</tr>
		<?php } else { ?>
			<?php do_action( 'bptodo_change_todo_edit_button_action', $profile_menu_label ); ?>
		<?php } ?>
		<?php do_action( 'bptodo_add_field_after_edit_default_listing', $displayed_uid, $form_post_link ); ?>
	</table>
</form>

<?php
/*
	// Show the successful message when todo is updated
	if ( isset( $_POST['todo_update'] ) ) {
		if ( 'nouveau' == $bp_template_option ) {
			?>
				<div id="message" class="info bp-feedback bp-messages bp-template-notice">
				<span class="bp-icon" aria-hidden="true"></span>
		<?php } else { ?>
				<div id="message" class="info">
		<?php } ?>
					<p>
						<?php echo sprintf( esc_html__( '%1$s updated successfully!', 'bp-user-to-do-list' ), esc_html( $profile_menu_label ) ); ?>
					</p>
				</div>
	<?php } ?>
*/ 
?>
