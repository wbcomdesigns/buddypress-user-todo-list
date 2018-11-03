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
			add_action( 'init', array( $this, 'bptodo_create_cpt' ) );
			add_action( 'init', array( $this, 'bptodo_create_cpt_category' ) );
		}

		/**
		 * Actions performed on loading init: creating cpt.
		 *
		 * @author  wbcomdesigns
		 * @since   1.0.0
		 * @access  public
		 */
		public function bptodo_create_cpt() {
			$labels = array(
				'name'               => esc_html__( 'To-Do Items', 'wb-todo' ),
				'singular_name'      => esc_html__( 'To-Do Item', 'wb-todo' ),
				'menu_name'          => esc_html__( 'To-Do Items', 'wb-todo' ),
				'name_admin_bar'     => esc_html__( 'To-Do Items', 'wb-todo' ),
				'view_item'          => esc_html__( 'View To-Do Item', 'wb-todo' ),
				'all_items'          => esc_html__( 'All To-Do Items', 'wb-todo' ),
				'search_items'       => esc_html__( 'Search To-Do Item', 'wb-todo' ),
				'parent_item_colon'  => esc_html__( 'Parent To-Do Item:', 'wb-todo' ),
				'not_found'          => esc_html__( 'No To-Do Item Found', 'wb-todo' ),
				'not_found_in_trash' => esc_html__( 'No To-Do Item Found In Trash', 'wb-todo' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'menu_icon'          => 'dashicons-edit',
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array(
					'slug'       => 'todo',
					'with_front' => false,
				),
				'capability_type'    => 'post',
				'capabilities'       => array(
					'create_posts' => false,
					'delete_posts' => false,
					'edit_post'    => false,
				),
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title', 'editor', 'author', 'thumbnail' ),
			);
			register_post_type( 'bp-todo', $args );
			flush_rewrite_rules( false );
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
				'name'              => esc_html__( 'To-Do Category', 'wb-todo' ),
				'singular_name'     => esc_html__( 'To-Do Category', 'wb-todo' ),
				'search_items'      => esc_html__( 'Search To-Do Items Categories', 'wb-todo' ),
				'all_items'         => esc_html__( 'All To-Do Items Categories', 'wb-todo' ),
				'parent_item'       => esc_html__( 'Parent To-Do Item Category', 'wb-todo' ),
				'parent_item_colon' => esc_html__( 'Parent To-Do Item Category:', 'wb-todo' ),
				'edit_item'         => esc_html__( 'Edit Category', 'wb-todo' ),
				'update_item'       => esc_html__( 'Update Category', 'wb-todo' ),
				'add_new_item'      => esc_html__( 'Add To-Do Item Category', 'wb-todo' ),
				'not_found'         => esc_html__( 'No To-Do Item Categories Found', 'wb-todo' ),
				'menu_name'         => esc_html__( 'Categories', 'wb-todo' ),
			);
			$tax_args   = array(
				'hierarchical'      => true,
				'labels'            => $tax_labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'public'            => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'todo_category' ),
			);
			register_taxonomy( 'todo_category', array( 'bp-todo' ), $tax_args );

			$term = term_exists( 'Uncategorized', 'todo_category' );
			if ( empty( $term ) ) {
				wp_insert_term(
					'Uncategorized',
					'todo_category'
				);
			}
		}
	}
	new Bptodo_Cpt();
}
