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
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="bptodo-shortcode-1">[bptodo_by_category category="<i>CATEGORY_ID</i>"]</label></th>
				<td>
					<p>
						<?php
							echo sprintf( esc_html__( 'This shortcode will list all the %1$s category wise.', 'wb-todo' ), esc_html( $bptodo->profile_menu_label_plural ) );
						?>
					</p>
					<p class="description"><?php esc_html_e( 'Arguments accepted:', 'wb-todo' ); ?></p>
					<ol type="1">
						<li>
							<?php
								echo esc_html( 'category : ' );
								echo sprintf( esc_html__( 'You need to provide the category id of which the %1$s you want to show.', 'wb-todo' ), esc_html( $bptodo->profile_menu_label_plural ) );
							?>
						</li>
					</ol>
				</td>
			</tr>
		</tbody>
	</table>
</div>