<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $bp, $bptodo;

// Attempt to get group ID from the BuddyPress group context
$group_id = isset( $bp->groups->current_group->id ) ? $bp->groups->current_group->id : '';

// Check if user is logged in
if ( ! is_user_logged_in() ) {
	bp_core_add_message( __( 'You must be logged in to edit todos.', 'wb-todo' ), 'error' );
	bp_core_redirect( bptodo_get_group_url() );
	exit;
}

// Get todo_id from URL parameter
$todo_id = isset( $_GET['args'] ) ? absint( $_GET['args'] ) : 0;

// Validate todo_id exists
if ( empty( $todo_id ) ) {
	bp_core_add_message( __( 'Invalid todo ID.', 'wb-todo' ), 'error' );
	bp_core_redirect( bptodo_get_group_url() );
	exit;
}

// If the group ID is not available, attempt to use the group ID stored in the post meta
if ( empty( $group_id ) && ! empty( $todo_id ) ) {
	$group_id = get_post_meta( $todo_id, 'todo_group_id', true );
}

if ( empty( $group_id ) ) {
    // If no group ID is available even after the fallback, handle the situation (e.g., exit or set a default value)
    return; // or set $group_id to a default valid value if applicable
}

// Check if user is a group member (or site admin)
$current_user_id = get_current_user_id();
$is_site_admin   = bptodo_is_site_admin();
$is_group_member = groups_is_user_member( $current_user_id, $group_id );

if ( ! $is_group_member && ! $is_site_admin ) {
	bp_core_add_message( __( 'You must be a group member to edit todos.', 'wb-todo' ), 'error' );
	bp_core_redirect( bptodo_get_group_url() );
	exit;
}

$profile_menu_label = $bptodo->profile_menu_label;
$profile_menu_slug  = $bptodo->profile_menu_slug;
$groups_link = bptodo_get_group_url( $bp->groups->current_group );
$admin_link  = trailingslashit( $groups_link . $profile_menu_slug );

$bptodo_req = $bptodo->req_duedate === 'yes' ? 'required' : '';

$displayed_uid = bp_displayed_user_id();

// List of Todo Items for group edit form.
// Limit to prevent memory issues - group todos are typically smaller datasets
$args  = array(
	'post_type'      => 'bp-todo',
	'post_status'    => 'publish',
	'author'         => get_current_user_id(),
	'posts_per_page' => apply_filters( 'bptodo_group_edit_limit', 200 ),
	'meta_query'     => array(
		array(
			'key'     => 'todo_group_id',
			'value'   => $group_id,
			'compare' => '=',
			'type'    => 'NUMERIC',
		),
	),
);
$todos = get_posts( $args );

$form_post_link = add_query_arg(
	'args',
	$todo_id,
	$admin_link . '/list'
);

$group_members = groups_get_group_members(
	array(
		'group_id'            => $group_id,
		'exclude_admins_mods' => false,
	)
);
if ( isset( $group_members['members'] ) ) {
	foreach ( $group_members['members'] as $member ) {
		$group_members_ids[] = $member->ID;
	}
}

$todo_cats = get_terms(
	array(
		'taxonomy'   => 'todo_category',
		'orderby'    => 'name',
		'hide_empty' => false,
	)
);

$todo = get_post( $todo_id );

$todo_cat    = wp_get_object_terms( $todo_id, 'todo_category' );
$todo_cat_id = 0;
if ( ! empty( $todo_cat ) && is_array( $todo_cat ) ) {
	$todo_cat_id = $todo_cat[0]->term_id;
}

$todo_due_date = get_post_meta( $todo_id, 'todo_due_date', true );
$todo_priority = get_post_meta( $todo_id, 'todo_priority', true );
if ( empty( $todo_priority ) ) {
	$todo_priority = 'normal';
}

$todo_group_id  = get_post_meta( $todo_id, 'todo_group_id', true );
$primary_todo_id = get_post_meta( $todo_id, 'todo_primary_id', true );

?>

<form action="" method="post" id="bptodo_edit_form_popup">
	<table class="bptodo-add-todo-tbl">
		<?php do_action( 'bp_group_todo_add_field_before_edit_default_listing', $displayed_uid, $form_post_link ); ?>
		<tr>
			<td><?php esc_html_e( 'Category', 'wb-todo' ); ?></td>
			<td>
				<div class="bp_todo_categories">
					<select name="todo_cat" id="bp_todo_categories" aria-label="<?php esc_attr_e( 'Category', 'wb-todo' ); ?>" required>
						<option value=""><?php esc_html_e( '--Select--', 'wb-todo' ); ?></option>
						<?php if ( isset( $todo_cats ) ) { ?>
							<?php foreach ( $todo_cats as $todo_cat ) { ?>
								<option value="<?php echo esc_attr( $todo_cat->name ); ?>" <?php selected( $todo_cat_id, $todo_cat->term_id ); ?>>
									<?php echo esc_html( $todo_cat->name ); ?>
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
						<input type="text" id="todo-category-name" aria-label="<?php echo esc_attr( sprintf( esc_html__( 'New %1$s category name', 'wb-todo' ), esc_html( $profile_menu_label ) ) ); ?>" placeholder="<?php echo esc_attr( sprintf( esc_html__( '%1$s category', 'wb-todo' ), esc_html( $profile_menu_label ) ) ); ?>">
						<?php $add_cat_nonce = wp_create_nonce( 'bptodo-add-todo-category' ); ?>
						<input type="hidden" id="bptodo-add-category-nonce" value="<?php echo esc_attr( $add_cat_nonce ); ?>">
						<button type="button" id="add-todo-cat" class="btn button"><?php esc_html_e( 'Add', 'wb-todo' ); ?></button>
					</div>
				<?php } ?>
			</td>
		</tr>

		<tr>
			<td><?php esc_html_e( 'Title', 'wb-todo' ); ?></td>
			<td>
				<input type="text" name="todo_title" value="<?php echo esc_html( $todo->post_title ); ?>" aria-label="<?php esc_attr_e( 'Title', 'wb-todo' ); ?>" placeholder="<?php esc_html_e( 'Title', 'wb-todo' ); ?>" required class="bptodo-text-input">
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
				<input type="date" id="datepick" name="todo_due_date" value="<?php echo esc_html( $todo_due_date ); ?>" aria-label="<?php esc_attr_e( 'Due Date', 'wb-todo' ); ?>" placeholder="<?php esc_html_e( 'Due Date', 'wb-todo' ); ?>" class="todo_due_date bptodo-text-input" <?php echo esc_attr( $bptodo_req ); ?>>
			</td>
		</tr>

		<tr>
			<td><?php esc_html_e( 'Priority', 'wb-todo' ); ?></td>
			<td>
				<select name="todo_priority" id="bp_todo_priority" aria-label="<?php esc_attr_e( 'Priority', 'wb-todo' ); ?>">
					<option value=""><?php esc_html_e( '--Select--', 'wb-todo' ); ?></option>
					<option value="critical" <?php selected( $todo_priority, 'critical' ); ?>><?php esc_html_e( 'Critical', 'wb-todo' ); ?></option>
					<option value="high" <?php selected( $todo_priority, 'high' ); ?>><?php esc_html_e( 'High', 'wb-todo' ); ?></option>
					<option value="normal" <?php selected( $todo_priority, 'normal' ); ?>><?php esc_html_e( 'Normal', 'wb-todo' ); ?></option>
				</select>
			</td>
		</tr>

		<tr>
			<td></td>
			<td>
				<input type="hidden" name="todo_group_id" value="<?php echo esc_attr( $todo_group_id ); ?>">
			</td>
		</tr>

		<?php $change_edit_btn = apply_filters( 'bp_group_todo_change_todo_edit_button', false ); ?>
		<?php if ( ! $change_edit_btn ) { ?>
			<tr>
				<td></td>
				<td>
					<?php wp_nonce_field( 'wp-bp-todo', 'save_update_group_todo_data_nonce' ); ?>
					<input type="hidden" name="hidden_todo_id" value="<?php echo esc_attr( $todo_id ); ?>">
					<input type="hidden" name="primary_todo_id" value="<?php echo esc_attr( $primary_todo_id ); ?>">
					<input type="hidden" name="group_id" value="<?php echo esc_attr( $group_id ); ?>">
					<?php /* translators: %s: the configured todo label (singular), e.g. "Task". */ ?>
					<input type="submit" id="group_todo_update" name="group_todo_update" value="<?php echo esc_html( sprintf( esc_html__( 'Update %s', 'wb-todo' ), esc_attr( $profile_menu_label ) ) ); ?>">
				</td>
			</tr>
		<?php } else { ?>
			<?php do_action( 'bp_group_todo_change_todo_edit_button_action', $profile_menu_label ); ?>
		<?php } ?>

		<?php do_action( 'bp_group_todo_add_field_after_edit_default_listing', $displayed_uid, $form_post_link ); ?>
	</table>
</form>
