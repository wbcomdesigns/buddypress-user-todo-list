<?php
/**
 * Category authority.
 *
 * Single place for reading the todo category list and creating categories, honoring
 * the `allow_user_add_category` setting. Used by REST, the legacy AJAX handler, and
 * the render/editor layers.
 *
 * @package bp-user-todo-list
 * @since   4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bptodo_Categories' ) ) {

	/**
	 * Class Bptodo_Categories.
	 */
	class Bptodo_Categories {

		/**
		 * All todo categories as flat data.
		 *
		 * @return array[] List of { id, slug, name, count }.
		 */
		public static function all() {
			$terms = get_terms(
				array(
					'taxonomy'   => Bptodo_Todo::TAXONOMY,
					'orderby'    => 'name',
					'hide_empty' => false,
				)
			);
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return array();
			}
			return array_map(
				static function ( $term ) {
					return array(
						'id'    => (int) $term->term_id,
						'slug'  => $term->slug,
						'name'  => $term->name,
						'count' => (int) $term->count,
					);
				},
				$terms
			);
		}

		/**
		 * Whether a user may create categories from the frontend.
		 *
		 * @param int $user_id User ID (0 = current).
		 * @return bool
		 */
		public static function user_can_add( $user_id = 0 ) {
			$user_id = $user_id ? (int) $user_id : (int) get_current_user_id();
			if ( ! $user_id ) {
				return false;
			}
			if ( user_can( $user_id, 'manage_categories' ) ) {
				return true;
			}
			$settings = get_option( 'user_todo_list_settings' );
			return ! empty( $settings['bptodo_allow_user_add_category'] )
				&& 'yes' === $settings['bptodo_allow_user_add_category'];
		}

		/**
		 * Create a category (idempotent on name).
		 *
		 * @param string $name    Category name.
		 * @param int    $user_id Acting user (0 = current).
		 * @return array|WP_Error { id, slug, name } or error.
		 */
		public static function create( $name, $user_id = 0 ) {
			$name = trim( wp_strip_all_tags( (string) $name ) );
			if ( '' === $name ) {
				return new WP_Error( 'bptodo_empty_category', __( 'Category name is required.', 'wb-todo' ), array( 'status' => 400 ) );
			}
			if ( ! self::user_can_add( $user_id ) ) {
				return new WP_Error( 'bptodo_cannot_add_category', __( 'You are not allowed to add categories.', 'wb-todo' ), array( 'status' => 403 ) );
			}

			$existing = term_exists( $name, Bptodo_Todo::TAXONOMY );
			if ( $existing ) {
				$term_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
			} else {
				$inserted = wp_insert_term( $name, Bptodo_Todo::TAXONOMY );
				if ( is_wp_error( $inserted ) ) {
					return $inserted;
				}
				$term_id = (int) $inserted['term_id'];
			}

			$term = get_term( $term_id, Bptodo_Todo::TAXONOMY );
			if ( ! $term || is_wp_error( $term ) ) {
				return new WP_Error( 'bptodo_category_lookup', __( 'Category could not be created.', 'wb-todo' ), array( 'status' => 500 ) );
			}
			return array(
				'id'   => (int) $term->term_id,
				'slug' => $term->slug,
				'name' => $term->name,
			);
		}
	}
}
