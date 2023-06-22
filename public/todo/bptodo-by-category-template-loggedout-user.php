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
	<p><?php esc_html_e( 'You need to be loggedin to view the todo items.', 'wb-todo' ); ?></p>
	<?php wp_login_form(); ?>
</div>
