<?php

/**
 * Locate template.
 *
 * Locate the called template.
 * Search Order:
 *
 * @since 2.2.1
 *
 * @param   string $template_name          Template to load.
 * @param   string $string $template_path  Path to templates.
 * @param   string $default_path           Default path to template files.
 * @return  string                          Path to the template file.
 */
function bp_todo_locate_template( $template_name, $template_path = '', $default_path = '' ) {

	// Set variable to search in woocommerce-plugin-templates folder of theme.
	if ( ! $template_path ) :
		 $template_path = 'todo/';
  endif;
	// Set default plugin templates path.
	if ( ! $default_path ) :
		 $default_path = BPTODO_PLUGIN_PATH . '/inc/todo/';
		// Path to the template folder
  endif;
	// Search template file in theme folder.
	$template = locate_template(
		array(
			$template_path . $template_name,
			$template_name,
		)
	);
	// Get plugins template file.
	if ( ! $template ) :
		 $template = $default_path . $template_name;
	endif;

	return apply_filters( 'bp_todo_locate_template', $template, $template_name, $template_path, $default_path );
}

/**
 * Get template.
 *
 * Search for the template and include the file.
 *
 * @since 2.2.1
 *
 * @see bp_todo_get_template()
 *
 * @param string $template_name          Template to load.
 * @param array  $args                   Args passed for the template file.
 * @param string $string $template_path  Path to templates.
 * @param string $default_path           Default path to template files.
 */
function bp_todo_get_template( $template_name, $args = array(), $tempate_path = '', $default_path = '' ) {

	if ( is_array( $args ) && isset( $args ) ) :
		extract( $args );
  endif;
	$template_file = bp_todo_locate_template( $template_name, $tempate_path, $default_path );
	if ( ! file_exists( $template_file ) ) :
		 _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
		return;
	endif;

	include $template_file;
}


if ( ! function_exists( 'bp_todo_post_type_archive' ) ) {
	function wpc_order_post_type_archive( $query ) {
		if ( $query->is_main_query() && is_post_type_archive( 'bp-todo' ) ) {
			$user_id = get_current_user_id();
			// order post_type and remove pagination
			$query->set( 'author', $user_id );
		}
	}
	add_action( 'pre_get_posts', 'wpc_order_post_type_archive' );
}

/* Filter the single_template with our custom function*/
add_filter( 'single_template', 'my_custom_template' );

function my_custom_template( $single ) {

	global $post;

	/* Checks for single template by post type */
	if ( $post->post_type == 'bp-todo' ) {
		if ( file_exists( BPTODO_PLUGIN_PATH . '/inc/todo/single-bp-todo.php' ) ) {
			return BPTODO_PLUGIN_PATH . '/inc/todo/single-bp-todo.php';
		}
	}

	return $single;

}
