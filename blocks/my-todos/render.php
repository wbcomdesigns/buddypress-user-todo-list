<?php
/**
 * Server render for the bptodo/my-todos block.
 *
 * Delegates to Bptodo_Renderer (the shared SSR + Interactivity renderer). Because
 * the block declares supports.interactivity, WordPress processes the data-wp-*
 * directives in this output server-side.
 *
 * @package bp-user-todo-list
 * @since   4.0.0
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	printf(
		'<div class="bptodo-app bptodo-app--loggedout"><p>%s</p></div>',
		esc_html__( 'Please log in to view your todos.', 'bp-user-to-do-list' )
	);
	return;
}

if ( ! class_exists( 'Bptodo_Renderer' ) ) {
	return;
}

$bptodo_atts = is_array( $attributes ) ? $attributes : array();

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer returns escaped SSR markup with Interactivity directives.
echo Bptodo_Renderer::render(
	array(
		'scope'      => 'member',
		'user_id'    => 0,
		'per_page'   => isset( $bptodo_atts['perPage'] ) ? (int) $bptodo_atts['perPage'] : 10,
		'status'     => isset( $bptodo_atts['status'] ) ? $bptodo_atts['status'] : 'all',
		'priority'   => isset( $bptodo_atts['priority'] ) ? $bptodo_atts['priority'] : 'all',
		'show_add'   => isset( $bptodo_atts['showAdd'] ) ? (bool) $bptodo_atts['showAdd'] : true,
		'show_stats' => isset( $bptodo_atts['showStats'] ) ? (bool) $bptodo_atts['showStats'] : true,
	)
);
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
