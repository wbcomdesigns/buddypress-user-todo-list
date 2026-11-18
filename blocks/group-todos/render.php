<?php
/**
 * Server render for the bptodo/group-todos block.
 *
 * @package bp-user-todo-list
 * @since   4.0.0
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bptodo_Renderer' ) ) {
	return;
}

$bptodo_atts = is_array( $attributes ) ? $attributes : array();
$bptodo_gid  = ! empty( $bptodo_atts['groupId'] ) ? (int) $bptodo_atts['groupId'] : 0;
if ( ! $bptodo_gid && function_exists( 'bp_get_current_group_id' ) ) {
	$bptodo_gid = (int) bp_get_current_group_id();
}

if ( ! $bptodo_gid ) {
	printf( '<div class="bptodo-app bptodo-app--loggedout"><p>%s</p></div>', esc_html__( 'Select a group for this block, or place it on a group page.', 'bp-user-to-do-list' ) );
	return;
}

if ( ! is_user_logged_in() ) {
	printf( '<div class="bptodo-app bptodo-app--loggedout"><p>%s</p></div>', esc_html__( 'Please log in to view group todos.', 'bp-user-to-do-list' ) );
	return;
}

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer returns escaped SSR markup.
echo Bptodo_Renderer::render(
	array(
		'scope'      => 'group',
		'group_id'   => $bptodo_gid,
		'per_page'   => isset( $bptodo_atts['perPage'] ) ? (int) $bptodo_atts['perPage'] : 10,
		'show_add'   => isset( $bptodo_atts['showAdd'] ) ? (bool) $bptodo_atts['showAdd'] : true,
		'show_stats' => isset( $bptodo_atts['showStats'] ) ? (bool) $bptodo_atts['showStats'] : true,
	)
);
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
