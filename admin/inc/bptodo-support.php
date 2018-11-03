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
<div class="bptodo-adming-setting">
	<div class="bptodo-tab-header">
		<h3><?php esc_html_e( 'FAQ(s)', 'wb-todo' ); ?></h3>
	</div>

	<div class="bptodo-admin-settings-block">
		<div id="bptodo-settings-tbl">
			<div class="bptodo-admin-row">
				<div>
					<button class="bptodo-accordion"><?php esc_html_e( 'Does this plugin require any other plugin to work?', 'wb-todo' ); ?></button>
					<div class="panel">
						<p><?php esc_html_e( 'As the name of the plugin justifies, this plugin helps manage To-Do List for BuddyPress Members, this plugin requires BuddyPress plugin to be installed and active.', 'wb-todo' ); ?></p>
						<p><?php esc_html_e( 'You\'ll also get an admin notice and the plugin will become ineffective if the required plugin is not there.', 'wb-todo' ); ?></p>
					</div>
				</div>
			</div>
			<div class="bptodo-admin-row">
				<div>
					<button class="bptodo-accordion"><?php esc_html_e( 'Plugin Working?', 'wb-todo' ); ?></button>
					<div class="panel">
						<p><?php esc_html_e( 'This plugin creates a menu To-Do in the user profile. User can add a set of tasks and can manage them accordingly.', 'wb-todo' ); ?></p>
						<p><?php esc_html_e( 'A user can even Export his/her list.', 'wb-todo' ); ?></p>
					</div>
				</div>
			</div>
			<div class="bptodo-admin-row">
				<div>
					<button class="bptodo-accordion"><?php esc_html_e( 'How to go for any custom development?', 'wb-todo' ); ?></button>
					<div class="panel">
						<p><?php
						echo sprintf( esc_html__( 'If you need additional help you can contact us for %1$s.', 'wb-todo' ), '<a href="https://wbcomdesigns.com/contact/" target="_blank" title="Custom Development by Wbcom Designs">'. esc_html__( 'Custom Development', 'wb-todo' ) .'</a>' );
						?>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
