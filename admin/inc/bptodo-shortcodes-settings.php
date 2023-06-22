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
?>
<div class="wbcom-tab-content">
<div class="wbcom-welcome-main-wrapper form-table">
	<div class="wbcom-admin-title-section">
		<h3>Shortcode</h3>
	</div>
	<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
		<div class="bptodo-shortcode-tab wbcom-settings-section-wrap">
			<label for="bptodo-shortcode-1">[bptodo_by_category category="<i>CATEGORY_ID</i>"]</label>
			<p class="description">
				<?php
				/* Translators: Get a Plural Label Name */
					echo sprintf( esc_html__( 'This shortcode will list all the %1$s category wise.', 'wb-todo' ), esc_html( $bptodo->profile_menu_label_plural ) );
				?>
			</p>
			<strong class="description"><?php esc_html_e( 'Arguments accepted:', 'wb-todo' ); ?></strong>
			<ol type="1">
				<li>
					<?php
						echo esc_html( 'category : ' );
						/* Translators: Get a Plural Label Name */
						echo sprintf( esc_html__( 'You need to provide the category id of which the %1$s you want to show.', 'wb-todo' ), esc_html( $bptodo->profile_menu_label_plural ) );
					?>
				</li>
			</ol>
			</div>
		</div>
	</div>
</div>
