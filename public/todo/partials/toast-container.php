<?php
/**
 * Toast Container Partial
 *
 * Outputs the container element for toast notifications.
 * The JavaScript will inject toasts into this container.
 *
 * Note: The modern JS module (bptodo-modern.js) creates this container
 * automatically if it doesn't exist. This partial is provided for cases
 * where you want explicit control over placement or need the container
 * to exist before JavaScript initializes.
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<!-- Toast Notification Container -->
<div
	class="bptodo-toast-container"
	role="alert"
	aria-live="polite"
	aria-atomic="true"
	id="bptodo-toast-container"
></div>
