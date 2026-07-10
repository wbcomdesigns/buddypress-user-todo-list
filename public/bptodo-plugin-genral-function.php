<?php
/**
 * Locate template.
 *
 * Locate the called template. Search Order:
 *
 * @since 2.2.1
 * @package bp-user-todo-list
 * @param string $template_name    Template to load.
 * @param string $template_path    Path to templates.
 * @param string $default_path     Default path to template files.
 * @return string                  Path to the template file.
 */

if ( ! function_exists( 'bp_todo_locate_template' ) ) {

	function bp_todo_locate_template( $template_name, $template_path = '', $default_path = '' ) {

		// Set variable to search in todo folder of theme.
		$template_path = $template_path ?: 'todo/';
		
		// Set default plugin templates path.
		$default_path = $default_path ?: BPTODO_PLUGIN_PATH . '/inc/todo/';
		
		// Search template file in theme folder.
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name,
			)
		);

		// Get plugin's template file if not found in theme.
		if ( ! $template ) {
			$template = $default_path . $template_name;
		}

		return apply_filters( 'bp_todo_locate_template', $template, $template_name, $template_path, $default_path );
	}
}

/**
 * Get template.
 *
 * Search for the template and include the file.
 *
 * @since 2.2.1
 * @param string $template_name    Template to load.
 * @param array  $args             Args passed for the template file.
 * @param string $template_path    Path to templates.
 * @param string $default_path     Default path to template files.
 */
if ( ! function_exists( 'bp_todo_get_template' ) ) {

	function bp_todo_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		// Note: $args is available directly in the included template.
		// Templates should access variables via $args['key'] instead of extracted variables.
		// This avoids security risks associated with extract().

		$template_file = bp_todo_locate_template( $template_name, $template_path, $default_path );

		if ( ! file_exists( $template_file ) ) {
			/* translators: %s: Template file path */
			_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', esc_html( $template_file ) ), '1.0.0' );
			return;
		}

		include $template_file;
	}
}


