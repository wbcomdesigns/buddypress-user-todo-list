<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="bptodo-loggedout-user-panel">
	<p><?php esc_html_e( 'You need to be logged in to view the todo items.', 'bp-user-to-do-list' ); ?></p>
	<?php wp_login_form(); ?>
</div>
