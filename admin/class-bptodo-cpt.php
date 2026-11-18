<?php
/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bptodo_Cpt' ) ) {

	/**
	 * Class to add admin menu to manage general settings.
	 *
	 * @package bp-user-todo-list
	 * @author  wbcomdesigns
	 * @since   1.0.0
	 */
	class Bptodo_Cpt {

		/**
		 * Define hook.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function __construct() {
		}

		/**
		 * Actions performed on loading init: creating cpt.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_create_cpt() {
			// The todo CPT is the storage for BOTH standalone (personal, no
			// BuddyPress) and BuddyPress/BuddyBoss group todos. It must register
			// regardless of BuddyPress so the plugin works stand-alone via the
			// "My Todos" block or the [bptodo_list] shortcode on any page.
			$labels = array(
				'name'               => esc_html__( 'Todo Items', 'bp-user-to-do-list' ),
				'singular_name'      => esc_html__( 'Todo Item', 'bp-user-to-do-list' ),
				'menu_name'          => esc_html__( 'Todo Items', 'bp-user-to-do-list' ),
				'name_admin_bar'     => esc_html__( 'Todo Items', 'bp-user-to-do-list' ),
				'view_item'          => esc_html__( 'View Todo Item', 'bp-user-to-do-list' ),
				'all_items'          => esc_html__( 'All Todo Items', 'bp-user-to-do-list' ),
				'search_items'       => esc_html__( 'Search Todo Item', 'bp-user-to-do-list' ),
				'parent_item_colon'  => esc_html__( 'Parent Todo Item:', 'bp-user-to-do-list' ),
				'not_found'          => esc_html__( 'No Todo Item Found', 'bp-user-to-do-list' ),
				'not_found_in_trash' => esc_html__( 'No Todo Item Found In Trash', 'bp-user-to-do-list' ),
			);

			/*
			 * Todos are PRIVATE, permission-scoped data (personal + group tasks).
			 * They must NOT be public WordPress pages: no single permalink, no
			 * archive, not in site search, not publicly queryable. All legitimate
			 * viewing happens through the To-do tab / block / shortcode / REST,
			 * each of which enforces per-user/per-group permissions. `show_ui`
			 * stays on so site admins can still manage items in wp-admin.
			 */
			$args = array(
				'labels'              => $labels,
				'public'              => false,
				'menu_icon'           => 'dashicons-edit',
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'query_var'           => false,
				'rewrite'             => false,
				'capability_type'     => 'post',
				'capabilities'        => array(
					'create_posts' => false,
				),
				'map_meta_cap'        => true,
				'has_archive'         => false,
				'hierarchical'        => false,
				'menu_position'       => null,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail' ),
			);
			register_post_type( 'bp-todo', $args );
		}

		/**
		 * Actions performed on loading init: creating cpt category.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_create_cpt_category() {
			$tax_labels = array(
				'name'              => esc_html__( 'Todo Category', 'bp-user-to-do-list' ),
				'singular_name'     => esc_html__( 'Todo Category', 'bp-user-to-do-list' ),
				'search_items'      => esc_html__( 'Search Todo Items Categories', 'bp-user-to-do-list' ),
				'all_items'         => esc_html__( 'All Todo Items Categories', 'bp-user-to-do-list' ),
				'parent_item'       => esc_html__( 'Parent Todo Item Category', 'bp-user-to-do-list' ),
				'parent_item_colon' => esc_html__( 'Parent Todo Item Category:', 'bp-user-to-do-list' ),
				'edit_item'         => esc_html__( 'Edit Category', 'bp-user-to-do-list' ),
				'update_item'       => esc_html__( 'Update Category', 'bp-user-to-do-list' ),
				'add_new_item'      => esc_html__( 'Add Todo Item Category', 'bp-user-to-do-list' ),
				'not_found'         => esc_html__( 'No Todo Item Categories Found', 'bp-user-to-do-list' ),
				'menu_name'         => esc_html__( 'Categories', 'bp-user-to-do-list' ),
			);
			// Private taxonomy - admin management only, no public term archives.
			$tax_args = array(
				'hierarchical'       => true,
				'labels'             => $tax_labels,
				'show_ui'            => true,
				'show_admin_column'  => true,
				'public'             => false,
				'publicly_queryable' => false,
				'query_var'          => false,
				'rewrite'            => false,
			);
			register_taxonomy( 'todo_category', array( 'bp-todo' ), $tax_args );

			$num_terms = wp_count_terms(
				array(
					'taxonomy'   => 'todo_category',
					'hide_empty' => false,
					'parent'     => 0,
				)
			);

			$term = term_exists( 'Uncategorized', 'todo_category' );
			if ( empty( $term ) && '0' === $num_terms ) {
				wp_insert_term(
					'Uncategorized',
					'todo_category'
				);
			}
		}
	}
	new Bptodo_Cpt();
}
