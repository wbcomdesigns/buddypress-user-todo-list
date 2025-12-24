<?php
/**
 * Member Todo Add Form
 *
 * Modern form layout for adding new todos.
 * Uses flexbox-based form groups with proper accessibility.
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $bptodo;

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
$displayed_uid      = bp_displayed_user_id();
$form_post_link     = bp_core_get_userlink( $displayed_uid, false, true ) . $profile_menu_slug . '/add/';
$bptodo_req         = 'yes' === $bptodo->req_duedate ? 'required' : '';
$allow_add_category = in_array( $bptodo->allow_user_add_category, array( 'yes', 'on' ), true );
?>

<div class="bptodo-wrapper" id="bptodo-member-add">
	<!-- Page Header -->
	<div class="bptodo-page-header">
		<h2 class="bptodo-page-title">
			<?php
			printf(
				/* translators: %s: Todo label */
				esc_html__( 'Add New %s', 'wb-todo' ),
				esc_html( $profile_menu_label )
			);
			?>
		</h2>
		<div class="bptodo-page-actions">
			<a href="<?php echo esc_url( bp_core_get_userlink( $displayed_uid, false, true ) . $profile_menu_slug ); ?>" class="bptodo-btn bptodo-btn--secondary">
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
		<form class="bptodo-form" action="" method="post" id="bptodo-add-form">
			<?php
			/**
			 * Hook: Before default form fields.
			 *
			 * @since 1.0.0
			 * @param int    $displayed_uid   Displayed user ID.
			 * @param string $form_post_link  Form action URL.
			 */
			do_action( 'bptodo_add_field_before_default_listing', $displayed_uid, $form_post_link );
			?>

			<?php
			// Include form fields partial.
			$context = 'member';
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
			do_action( 'bptodo_add_field_after_default_listing', $displayed_uid, $form_post_link );
			?>

			<?php
			/**
			 * Filter: Allow customizing the submit button.
			 *
			 * @since 1.0.0
			 * @param bool $change_button Whether to replace the default button.
			 */
			if ( ! apply_filters( 'bptodo_change_submit_button', false ) ) :
				?>
				<!-- Form Actions -->
				<div class="bptodo-form-actions">
					<?php wp_nonce_field( 'wp-bp-todo', 'save_new_todo_data_nonce' ); ?>
					<!-- Hidden input ensures todo_create is submitted even when button is disabled by loading state -->
					<input type="hidden" name="todo_create" value="1">
					<button type="button" class="bptodo-btn bptodo-btn--secondary js-bptodo-cancel">
						<?php esc_html_e( 'Cancel', 'wb-todo' ); ?>
					</button>
					<button type="submit" class="bptodo-btn bptodo-btn--primary" id="bp-add-new-todo">
						<svg class="bptodo-btn__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="20 6 9 17 4 12"></polyline>
						</svg>
						<?php
						printf(
							/* translators: %s: Todo label */
							esc_html__( 'Create %s', 'wb-todo' ),
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
				do_action( 'bptodo_change_submit_button_action', $profile_menu_label );
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
		var form = document.getElementById('bptodo-add-form');
		if (form) {
			form.addEventListener('submit', function(e) {
				// Check for required fields
				var category = form.querySelector('[name="todo_cat"]');
				var title = form.querySelector('[name="todo_title"]');

				// If category is empty, show error
				if (category && !category.value) {
					if (window.BPTodo && window.BPTodo.toast) {
						window.BPTodo.toast.error('<?php echo esc_js( __( 'Please select a category', 'wb-todo' ) ); ?>');
					}
					e.preventDefault();
					return false;
				}

				// If title is empty, show error
				if (title && !title.value.trim()) {
					if (window.BPTodo && window.BPTodo.toast) {
						window.BPTodo.toast.error('<?php echo esc_js( __( 'Please enter a title', 'wb-todo' ) ); ?>');
					}
					e.preventDefault();
					return false;
				}

				// Show loading state on button
				var submitBtn = form.querySelector('[type="submit"]');
				if (submitBtn && window.BPTodo && window.BPTodo.loading) {
					window.BPTodo.loading.button(submitBtn, '<?php echo esc_js( __( 'Creating...', 'wb-todo' ) ); ?>');
				}

				// Form will submit naturally (no preventDefault)
			});
		}
	});
})();
</script>
