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
	bp_core_add_message( __( 'You must be logged in to add todos.', 'wb-todo' ), 'error' );
	bp_core_redirect( bptodo_get_group_url() );
	exit;
}

// Check if user is a group admin or moderator (or site admin).
$current_user_id = get_current_user_id();
$is_site_admin   = bptodo_is_site_admin();
$is_group_admin  = groups_is_user_admin( $current_user_id, $group_id );
$is_group_mod    = groups_is_user_mod( $current_user_id, $group_id );

if ( ! $is_group_admin && ! $is_group_mod && ! $is_site_admin ) {
	bp_core_add_message( __( 'You must be a group administrator or moderator to add todos.', 'wb-todo' ), 'error' );
	bp_core_redirect( bptodo_get_group_url() );
	exit;
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
$form_post_link     = bptodo_get_group_url() . strtolower( $profile_menu_label ) . '/list';
$allow_add_category = 'yes' === $bptodo->allow_user_add_category;

// Process form submission.
if ( isset( $_POST['group_todo_create'], $_POST['group_save_new_todo_data_nonce'] )
	&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['group_save_new_todo_data_nonce'] ) ), 'wp-bp-todo' ) ) {

	$todo_group_members = isset( $_POST['todo_group_members'] ) ? (array) wp_unslash( $_POST['todo_group_members'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( ! empty( $todo_group_members ) && ! in_array( 'all', $todo_group_members, true ) ) {
		$group_members_ids = array_map( 'absint', $todo_group_members );
	}

	$todo_group_id = isset( $_POST['todo_group_id'] ) ? absint( wp_unslash( $_POST['todo_group_id'] ) ) : $group_id;
	$title         = isset( $_POST['todo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['todo_title'] ) ) : '';
	$summary       = isset( $_POST['bptodo-summary-input'] ) ? wp_kses_post( wp_unslash( $_POST['bptodo-summary-input'] ) ) : '';
	$due_date      = isset( $_POST['todo_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['todo_due_date'] ) ) : '';
	$priority      = isset( $_POST['todo_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['todo_priority'] ) ) : '';
	$cat           = isset( $_POST['todo_cat'] ) ? sanitize_text_field( wp_unslash( $_POST['todo_cat'] ) ) : '';

	// Create primary todo.
	$args            = array(
		'post_type'    => 'bp-todo',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_content' => $summary,
		'post_author'  => $displayed_uid,
	);
	$primary_todo_id = wp_insert_post( $args );

	if ( $primary_todo_id ) {
		update_post_meta( $primary_todo_id, 'todo_status', 'incomplete' );
		update_post_meta( $primary_todo_id, 'todo_due_date', $due_date );
		update_post_meta( $primary_todo_id, 'todo_priority', $priority );
		update_post_meta( $primary_todo_id, 'todo_last_day_mail_sent', 'no' );
		update_post_meta( $primary_todo_id, 'todo_last_day_notification_sent', 'no' );
		update_post_meta( $primary_todo_id, 'todo_group_id', $todo_group_id );
		wp_set_object_terms( $primary_todo_id, $cat, 'todo_category' );
		update_post_meta( $primary_todo_id, 'todo_primary_id', $primary_todo_id );
		update_post_meta( $primary_todo_id, 'todo_creator_id', get_current_user_id() );

		$associated_todos = array();
		$current_user_id  = get_current_user_id();

		foreach ( $group_members_ids as $member_id ) {
			// Skip the current user as they already have the primary todo assigned.
			if ( $member_id == $current_user_id ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				continue;
			}

			// Create tasks for ALL other members including admins and mods.
			$args    = array(
				'post_type'    => 'bp-todo',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $summary,
				'post_author'  => $member_id,
			);
			$post_id = wp_insert_post( $args );

			if ( $post_id ) {
				$associated_todos[] = $post_id;
				update_post_meta( $post_id, 'todo_status', 'incomplete' );
				update_post_meta( $post_id, 'todo_due_date', $due_date );
				update_post_meta( $post_id, 'todo_priority', $priority );
				update_post_meta( $post_id, 'todo_last_day_mail_sent', 'no' );
				update_post_meta( $post_id, 'todo_last_day_notification_sent', 'no' );
				update_post_meta( $post_id, 'todo_group_id', $todo_group_id );
				wp_set_object_terms( $post_id, $cat, 'todo_category' );
				update_post_meta( $post_id, 'todo_creator_id', get_current_user_id() );
				update_post_meta( $post_id, 'todo_primary_id', $primary_todo_id );
			}
		}

		update_post_meta( $primary_todo_id, 'botodo_associated_todo', $associated_todos );

		// Trigger notifications and emails AFTER all todos are created.
		do_action( 'bptodo_group_todo_submit', $group_members_ids, $primary_todo_id );

		bp_core_redirect( $form_post_link );
	}
}
?>

<div class="bptodo-wrapper" id="bptodo-group-add">
	<!-- Page Header -->
	<div class="bptodo-page-header">
		<h2 class="bptodo-page-title">
			<?php
			printf(
				/* translators: %s: Todo label */
				esc_html__( 'Add Group %s', 'wb-todo' ),
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
				<?php esc_html_e( 'Back to List', 'wb-todo' ); ?>
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
					<button type="button" class="bptodo-btn bptodo-btn--secondary js-bptodo-cancel">
						<?php esc_html_e( 'Cancel', 'wb-todo' ); ?>
					</button>
					<button type="submit" name="group_todo_create" class="bptodo-btn bptodo-btn--primary" id="bp-add-new-todo">
						<svg class="bptodo-btn__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="20 6 9 17 4 12"></polyline>
						</svg>
						<?php
						printf(
							/* translators: %s: Todo label */
							esc_html__( 'Assign %s', 'wb-todo' ),
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

<script>
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		// Cancel button handler
		var cancelBtn = document.querySelector('.js-bptodo-cancel');
		if (cancelBtn) {
			cancelBtn.addEventListener('click', function() {
				window.history.back();
			});
		}

		// Form submission with loading state
		var form = document.getElementById('bptodo-group-add-form');
		if (form) {
			form.addEventListener('submit', function() {
				var submitBtn = form.querySelector('[type="submit"]');
				if (submitBtn && window.BPTodo && window.BPTodo.loading) {
					window.BPTodo.loading.button(submitBtn, '<?php echo esc_js( __( 'Assigning...', 'wb-todo' ) ); ?>');
				}
			});
		}
	});
})();
</script>
