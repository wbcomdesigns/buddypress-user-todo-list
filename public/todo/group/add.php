<?php
/**
 * Group Todo Add Form
 *
 * Modern form layout for adding new group todos.
 * Uses flexbox-based form groups with proper accessibility.
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Ensure BuddyPress and Groups component are active.
if ( ! function_exists( 'groups_is_user_admin' ) || ! function_exists( 'bp_core_add_message' ) ) {
	return;
}

global $bp, $bptodo;

$group_id = isset( $bp->groups->current_group->id ) ? $bp->groups->current_group->id : 0;

// Check if user is logged in.
if ( ! is_user_logged_in() ) {
	bp_core_add_message( __( 'You must be logged in to add todos.', 'bp-user-to-do-list' ), 'error' );
	bp_core_redirect( bptodo_get_group_url() );
	exit;
}

// Check if user is a group admin or moderator (or site admin).
$current_user_id = get_current_user_id();
$is_site_admin   = bptodo_is_site_admin();
$is_group_admin  = groups_is_user_admin( $current_user_id, $group_id );
$is_group_mod    = groups_is_user_mod( $current_user_id, $group_id );

if ( ! $is_group_admin && ! $is_group_mod && ! $is_site_admin ) {
	bp_core_add_message( __( 'You must be a group administrator or moderator to add todos.', 'bp-user-to-do-list' ), 'error' );
	bp_core_redirect( bptodo_get_group_url() );
	exit;
}

// Modern layout (4.0): render the Interactivity add form (with member
// assignment) and stop. The classic form below runs only in legacy mode.
if ( function_exists( 'bptodo_render_todos' ) && 'modern' === bptodo_layout_mode( 'group' ) ) {
	/*
	 * The assignee picker is bounded: big groups would otherwise render one checkbox
	 * per member. Members past the limit are still reached by leaving every box
	 * unticked (assign to everyone). Filter the limit per site if needed.
	 * ponytail: no member search in the picker; add a REST member search if owners
	 * need to pick individuals beyond the first page in very large groups.
	 */
	$bptodo_picker_limit = (int) apply_filters( 'bptodo_assignee_picker_limit', 100, $group_id );
	$bptodo_page         = groups_get_group_members(
		array(
			'group_id'            => $group_id,
			'exclude_admins_mods' => false,
			'per_page'            => max( 1, $bptodo_picker_limit ),
			'page'                => 1,
			'type'                => 'alphabetical',
		)
	);
	$bptodo_members = array();
	if ( ! empty( $bptodo_page['members'] ) ) {
		foreach ( $bptodo_page['members'] as $bptodo_member ) {
			$bptodo_members[] = array(
				'id'   => (int) $bptodo_member->ID,
				'name' => $bptodo_member->display_name,
			);
		}
	}
	$bptodo_list_url = trailingslashit( bptodo_get_group_url( $bp->groups->current_group ) . $bptodo->profile_menu_slug ) . 'list';
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer returns escaped Interactivity markup.
	echo Bptodo_Renderer::render_add_form(
		array(
			'scope'         => 'group',
			'group_id'      => (int) $group_id,
			'list_url'      => $bptodo_list_url,
			'members'       => $bptodo_members,
			'members_total' => isset( $bptodo_page['count'] ) ? (int) $bptodo_page['count'] : count( $bptodo_members ),
		)
	);
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

// Get group members.
$group_members = groups_get_group_members(
	array(
		'group_id'            => $group_id,
		'exclude_admins_mods' => false,
	)
);

$group_members_ids = array();
$members_ids       = array();
if ( ! empty( $group_members['members'] ) ) {
	foreach ( $group_members['members'] as $member ) {
		$group_members_ids[]            = $member->ID;
		$members_ids[ $member->ID ] = $member->display_name;
	}
}

// Get categories.
$todo_cats = get_terms(
	array(
		'taxonomy'   => 'todo_category',
		'orderby'    => 'name',
		'hide_empty' => false,
	)
);

// Get settings.
$profile_menu_label = $bptodo->profile_menu_label;
$profile_menu_slug  = $bptodo->profile_menu_slug;
$bptodo_req         = 'yes' === $bptodo->req_duedate ? 'required' : '';
$displayed_uid      = get_current_user_id();
$form_post_link     = bptodo_get_group_url() . $profile_menu_slug . '/list';
$allow_add_category = 'yes' === $bptodo->allow_user_add_category;

// Form processing is now handled earlier by bptodo_process_form_submissions()
// on the template_redirect hook to avoid "headers already sent" errors.
// This template now only handles display.
?>

<div class="bptodo-wrapper" id="bptodo-group-add">
	<!-- Page Header -->
	<div class="bptodo-page-header">
		<h2 class="bptodo-page-title">
			<?php
			printf(
				/* translators: %s: Todo label */
				esc_html__( 'Add Group %s', 'bp-user-to-do-list' ),
				esc_html( $profile_menu_label )
			);
			?>
		</h2>
		<div class="bptodo-page-actions">
			<a href="<?php echo esc_url( $form_post_link ); ?>" class="bptodo-btn bptodo-btn--secondary">
				<svg class="bptodo-btn__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<line x1="19" y1="12" x2="5" y2="12"></line>
					<polyline points="12 19 5 12 12 5"></polyline>
				</svg>
				<?php esc_html_e( 'Back to List', 'bp-user-to-do-list' ); ?>
			</a>
		</div>
	</div>

	<!-- Add Form Card -->
	<div class="bptodo-card bptodo-card--form">
		<form class="bptodo-form" action="" method="post" id="bptodo-group-add-form">
			<?php
			/**
			 * Hook: Before default form fields.
			 *
			 * @since 1.0.0
			 * @param int    $displayed_uid   Displayed user ID.
			 * @param string $form_post_link  Form action URL.
			 */
			do_action( 'bp_group_todo_add_field_before_default_listing', $displayed_uid, $form_post_link );
			?>

			<?php
			// Include form fields partial.
			$context = 'group';
			include BPTODO_PLUGIN_PATH . 'public/todo/partials/todo-form-fields.php';
			?>

			<?php
			/**
			 * Hook: After default form fields.
			 *
			 * @since 1.0.0
			 * @param int    $displayed_uid   Displayed user ID.
			 * @param string $form_post_link  Form action URL.
			 */
			do_action( 'bp_group_todo_add_field_after_default_listing', $displayed_uid, $form_post_link );
			?>

			<?php
			/**
			 * Filter: Allow customizing the submit button.
			 *
			 * @since 1.0.0
			 * @param bool $change_button Whether to replace the default button.
			 */
			if ( ! apply_filters( 'bp_group_todo_change_submit_button', false ) ) :
				?>
				<!-- Form Actions -->
				<div class="bptodo-form-actions">
					<?php wp_nonce_field( 'wp-bp-todo', 'group_save_new_todo_data_nonce' ); ?>
					<!-- Hidden input ensures group_todo_create is submitted even when button is disabled by loading state -->
					<input type="hidden" name="group_todo_create" value="1">
					<input type="hidden" name="todo_group_id" value="<?php echo esc_attr( $group_id ); ?>">
					<button type="button" class="bptodo-btn bptodo-btn--secondary js-bptodo-cancel">
						<?php esc_html_e( 'Cancel', 'bp-user-to-do-list' ); ?>
					</button>
					<button type="submit" class="bptodo-btn bptodo-btn--primary" id="bp-add-new-todo">
						<svg class="bptodo-btn__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="20 6 9 17 4 12"></polyline>
						</svg>
						<?php
						printf(
							/* translators: %s: Todo label */
							esc_html__( 'Assign %s', 'bp-user-to-do-list' ),
							esc_html( $profile_menu_label )
						);
						?>
					</button>
				</div>
			<?php else : ?>
				<?php
				/**
				 * Hook: Custom submit button action.
				 *
				 * @since 1.0.0
				 * @param string $profile_menu_label Todo label.
				 */
				do_action( 'bp_group_todo_change_submit_button_action', $profile_menu_label );
				?>
			<?php endif; ?>
		</form>
	</div>
</div>

