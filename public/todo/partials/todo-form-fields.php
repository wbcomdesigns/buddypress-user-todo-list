<?php
/**
 * Todo Form Fields Partial
 *
 * Reusable form fields for add/edit todo forms.
 * Uses modern flexbox layout with proper accessibility.
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 *
 * Expected variables:
 * @var string $context             'member' or 'group' - determines which fields to show
 * @var array  $todo_cats           Array of todo category terms
 * @var string $profile_menu_label  Singular label for todo (e.g., "Task")
 * @var bool   $allow_add_category  Whether to show add category option
 * @var string $bptodo_req          'required' if due date is required, empty otherwise
 * @var array  $members_ids         Group members (for group context only)
 * @var int    $group_id            Group ID (for group context only)
 * @var object $todo                Todo post object (for edit forms, optional)
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set defaults.
$context            = isset( $context ) ? $context : 'member';
$todo_cats          = isset( $todo_cats ) ? $todo_cats : array();
$profile_menu_label = isset( $profile_menu_label ) ? $profile_menu_label : __( 'Todo', 'wb-todo' );
$allow_add_category = isset( $allow_add_category ) ? $allow_add_category : false;
$bptodo_req         = isset( $bptodo_req ) ? $bptodo_req : '';
$members_ids        = isset( $members_ids ) ? $members_ids : array();
$group_id           = isset( $group_id ) ? $group_id : 0;

// Get existing values for edit form.
$existing_title    = isset( $todo ) ? $todo->post_title : '';
$existing_content  = isset( $todo ) ? $todo->post_content : '';
$existing_due_date = isset( $todo ) ? get_post_meta( $todo->ID, 'todo_due_date', true ) : '';
$existing_priority = isset( $todo ) ? get_post_meta( $todo->ID, 'todo_priority', true ) : 'normal';
$existing_category = '';
if ( isset( $todo ) ) {
	$terms = get_the_terms( $todo->ID, 'todo_category' );
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$existing_category = $terms[0]->name;
	}
}

// Unique ID prefix for accessibility.
$id_prefix = 'bptodo-' . $context . '-';
?>

<!-- Category Field -->
<div class="bptodo-form-group">
	<label class="bptodo-form-group__label" for="<?php echo esc_attr( $id_prefix ); ?>category">
		<?php esc_html_e( 'Category', 'wb-todo' ); ?>
		<span class="bptodo-form-group__required" aria-hidden="true">*</span>
	</label>
	<div class="bptodo-form-group__input-wrapper">
		<div class="bptodo-select-wrapper">
			<select
				name="todo_cat"
				id="<?php echo esc_attr( $id_prefix ); ?>category"
				class="bptodo-input bptodo-input--select"
				required
				aria-required="true"
			>
				<option value=""><?php esc_html_e( '-- Select Category --', 'wb-todo' ); ?></option>
				<?php foreach ( $todo_cats as $todo_cat ) : ?>
					<option
						value="<?php echo esc_attr( $todo_cat->name ); ?>"
						<?php selected( $existing_category, $todo_cat->name ); ?>
					>
						<?php echo esc_html( $todo_cat->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php if ( $allow_add_category ) : ?>
			<button
				type="button"
				class="bptodo-btn bptodo-btn--secondary bptodo-btn--icon add-todo-category"
				aria-label="<?php esc_attr_e( 'Add new category', 'wb-todo' ); ?>"
			>
				<svg class="bptodo-btn__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<line x1="12" y1="5" x2="12" y2="19"></line>
					<line x1="5" y1="12" x2="19" y2="12"></line>
				</svg>
			</button>
		<?php endif; ?>
	</div>

	<?php if ( $allow_add_category ) : ?>
		<div class="bptodo-form-group__addon add-todo-cat-row" style="display: none;">
			<div class="bptodo-input-group">
				<input
					type="text"
					id="todo-category-name"
					class="bptodo-input"
					placeholder="<?php echo esc_attr( sprintf( __( 'New %s category', 'wb-todo' ), $profile_menu_label ) ); ?>"
				>
				<input type="hidden" id="bptodo-add-category-nonce" value="<?php echo esc_attr( wp_create_nonce( 'bptodo-add-todo-category' ) ); ?>">
				<button type="button" id="add-todo-cat" class="bptodo-btn bptodo-btn--secondary">
					<?php esc_html_e( 'Add', 'wb-todo' ); ?>
				</button>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- Title Field -->
<div class="bptodo-form-group">
	<label class="bptodo-form-group__label" for="<?php echo esc_attr( $id_prefix ); ?>title">
		<?php esc_html_e( 'Title', 'wb-todo' ); ?>
		<span class="bptodo-form-group__required" aria-hidden="true">*</span>
	</label>
	<input
		type="text"
		name="todo_title"
		id="<?php echo esc_attr( $id_prefix ); ?>title"
		class="bptodo-input"
		placeholder="<?php esc_attr_e( 'Enter a descriptive title', 'wb-todo' ); ?>"
		value="<?php echo esc_attr( $existing_title ); ?>"
		required
		aria-required="true"
	>
</div>

<!-- Summary/Description Field -->
<div class="bptodo-form-group bptodo-form-group--full">
	<label class="bptodo-form-group__label" for="<?php echo esc_attr( $id_prefix ); ?>summary">
		<?php esc_html_e( 'Description', 'wb-todo' ); ?>
	</label>
	<?php
	$editor_settings = array(
		'media_buttons' => true,
		'editor_height' => 200,
		'textarea_name' => 'bptodo-summary-input',
		'textarea_rows' => 8,
		'teeny'         => false,
		'quicktags'     => true,
	);
	wp_editor( $existing_content, 'bptodo-summary-input', $editor_settings );
	?>
</div>

<!-- Due Date & Priority Row -->
<div class="bptodo-form-row">
	<!-- Due Date Field -->
	<div class="bptodo-form-group">
		<label class="bptodo-form-group__label" for="<?php echo esc_attr( $id_prefix ); ?>due-date">
			<?php esc_html_e( 'Due Date', 'wb-todo' ); ?>
			<?php if ( ! empty( $bptodo_req ) ) : ?>
				<span class="bptodo-form-group__required" aria-hidden="true">*</span>
			<?php endif; ?>
		</label>
		<input
			type="date"
			name="todo_due_date"
			id="<?php echo esc_attr( $id_prefix ); ?>due-date"
			class="bptodo-input todo_due_date"
			value="<?php echo esc_attr( $existing_due_date ); ?>"
			min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"
			<?php echo esc_attr( $bptodo_req ); ?>
			<?php echo ! empty( $bptodo_req ) ? 'aria-required="true"' : ''; ?>
		>
	</div>

	<!-- Priority Field -->
	<div class="bptodo-form-group">
		<label class="bptodo-form-group__label" for="<?php echo esc_attr( $id_prefix ); ?>priority">
			<?php esc_html_e( 'Priority', 'wb-todo' ); ?>
		</label>
		<select
			name="todo_priority"
			id="<?php echo esc_attr( $id_prefix ); ?>priority"
			class="bptodo-input bptodo-input--select"
		>
			<option value="normal" <?php selected( $existing_priority, 'normal' ); ?>>
				<?php esc_html_e( 'Normal', 'wb-todo' ); ?>
			</option>
			<option value="high" <?php selected( $existing_priority, 'high' ); ?>>
				<?php esc_html_e( 'High', 'wb-todo' ); ?>
			</option>
			<option value="critical" <?php selected( $existing_priority, 'critical' ); ?>>
				<?php esc_html_e( 'Critical', 'wb-todo' ); ?>
			</option>
		</select>
	</div>
</div>

<?php if ( 'group' === $context && ! empty( $members_ids ) ) : ?>
	<!-- Group Members Field -->
	<div class="bptodo-form-group bptodo-form-group--full">
		<label class="bptodo-form-group__label" for="<?php echo esc_attr( $id_prefix ); ?>members">
			<?php esc_html_e( 'Assign to Members', 'wb-todo' ); ?>
		</label>
		<select
			name="todo_group_members[]"
			id="todo_group_members"
			class="bptodo-input bptodo-input--select"
			multiple
			data-placeholder="<?php esc_attr_e( 'Select members to assign this task', 'wb-todo' ); ?>"
		>
			<option value="all"><?php esc_html_e( 'All Members', 'wb-todo' ); ?></option>
			<?php foreach ( $members_ids as $member_id => $member_name ) : ?>
				<option value="<?php echo esc_attr( $member_id ); ?>">
					<?php echo esc_html( $member_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="bptodo-form-group__help">
			<?php esc_html_e( 'Leave empty or select "All Members" to assign to everyone in the group.', 'wb-todo' ); ?>
		</p>
	</div>

	<!-- Hidden Group ID -->
	<input type="hidden" name="todo_group_id" value="<?php echo esc_attr( $group_id ); ?>">
<?php endif; ?>
