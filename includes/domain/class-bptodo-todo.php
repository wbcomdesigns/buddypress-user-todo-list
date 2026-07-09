<?php
/**
 * Todo domain object (DTO).
 *
 * A thin, read-oriented value object around a single `bp-todo` post + its meta.
 * It centralizes the storage contract (meta keys) and produces the ONE canonical
 * array shape consumed by both the REST API and the SSR render parts, so the two
 * can never drift.
 *
 * @package bp-user-todo-list
 * @since   4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bptodo_Todo' ) ) {

	/**
	 * Class Bptodo_Todo.
	 */
	class Bptodo_Todo {

		/** Post type + taxonomy. */
		const POST_TYPE = 'bp-todo';
		const TAXONOMY  = 'todo_category';

		/** Meta keys — the storage contract. Nothing else in the plugin should hard-code these. */
		const META_STATUS          = 'todo_status';           // 'complete' | 'incomplete'.
		const META_PRIORITY        = 'todo_priority';         // 'critical' | 'high' | 'normal'.
		const META_DUE_DATE        = 'todo_due_date';         // 'Y-m-d'.
		const META_GROUP_ID        = 'todo_group_id';         // int, group todos only.
		const META_PRIMARY_ID      = 'todo_primary_id';       // int, primary of a group fan-out (self for primary).
		const META_CREATOR_ID      = 'todo_creator_id';       // int, group todos.
		const META_ASSOCIATED      = 'botodo_associated_todo'; // int[], primary -> member copies (legacy key spelling).
		const META_USER_ID         = 'todo_user_id';          // int, who completed.
		const META_COMPLETE_TIME   = 'todo_complete_time';    // unix ts.
		const META_COMPLETED_DATE  = 'todo_completed_date';   // mysql datetime.
		const META_MAIL_SENT       = 'todo_last_day_mail_sent';         // 'no' on create.
		const META_NOTIF_SENT      = 'todo_last_day_notification_sent'; // 'no' on create.

		/** Valid enum values. */
		const STATUSES   = array( 'incomplete', 'complete' );
		const PRIORITIES = array( 'normal', 'high', 'critical' );

		/**
		 * The underlying post.
		 *
		 * @var WP_Post
		 */
		protected $post;

		/**
		 * Constructor.
		 *
		 * @param WP_Post $post The bp-todo post.
		 */
		public function __construct( WP_Post $post ) {
			$this->post = $post;
		}

		/**
		 * Build from an ID, returning null if it is not a todo.
		 *
		 * @param int $id Post ID.
		 * @return Bptodo_Todo|null
		 */
		public static function from_id( $id ) {
			$post = get_post( (int) $id );
			if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
				return null;
			}
			return new self( $post );
		}

		/** @return int */
		public function id() {
			return (int) $this->post->ID;
		}

		/** @return WP_Post */
		public function post() {
			return $this->post;
		}

		/** @return int */
		public function author_id() {
			return (int) $this->post->post_author;
		}

		/** @return string 'complete'|'incomplete' */
		public function status() {
			return 'complete' === get_post_meta( $this->id(), self::META_STATUS, true ) ? 'complete' : 'incomplete';
		}

		/** @return bool */
		public function is_complete() {
			return 'complete' === $this->status();
		}

		/** @return string */
		public function priority() {
			$p = (string) get_post_meta( $this->id(), self::META_PRIORITY, true );
			return in_array( $p, self::PRIORITIES, true ) ? $p : 'normal';
		}

		/** @return string 'Y-m-d' or '' */
		public function due_date() {
			return (string) get_post_meta( $this->id(), self::META_DUE_DATE, true );
		}

		/** @return int 0 when not a group todo */
		public function group_id() {
			return (int) get_post_meta( $this->id(), self::META_GROUP_ID, true );
		}

		/**
		 * The primary category term for this todo, or null.
		 *
		 * @return array|null { id, slug, name }
		 */
		public function category() {
			$terms = wp_get_object_terms( $this->id(), self::TAXONOMY );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				return null;
			}
			$term = $terms[0];
			return array(
				'id'   => (int) $term->term_id,
				'slug' => $term->slug,
				'name' => $term->name,
			);
		}

		/**
		 * Human-readable due-date string + a state class, matching the legacy UI copy.
		 *
		 * @return array { string $text, string $state } state: '', 'overdue', 'today', 'upcoming', 'done'.
		 */
		public function due_human() {
			if ( $this->is_complete() ) {
				return array(
					'text'  => __( 'Completed!', 'wb-todo' ),
					'state' => 'done',
				);
			}
			$due = $this->due_date();
			if ( '' === $due ) {
				return array(
					'text'  => '',
					'state' => '',
				);
			}
			$today    = date_create( gmdate( 'Y-m-d' ) );
			$due_obj  = date_create( $due );
			if ( ! $today || ! $due_obj ) {
				return array(
					'text'  => '',
					'state' => '',
				);
			}
			$diff_days = (int) date_diff( $today, $due_obj )->format( '%R%a' );

			if ( $diff_days < 0 ) {
				/* translators: %d: number of days a task is overdue. */
				return array(
					'text'  => sprintf( _n( 'Overdue by %d day', 'Overdue by %d days', abs( $diff_days ), 'wb-todo' ), abs( $diff_days ) ),
					'state' => 'overdue',
				);
			}
			if ( 0 === $diff_days ) {
				return array(
					'text'  => __( 'Due today', 'wb-todo' ),
					'state' => 'today',
				);
			}
			/* translators: %d: number of days left to complete a task. */
			return array(
				'text'  => sprintf( _n( '%d day left', '%d days left', $diff_days, 'wb-todo' ), $diff_days ),
				'state' => 'upcoming',
			);
		}

		/**
		 * Author summary (name + avatar) — plain WP, no BuddyPress dependency.
		 *
		 * @return array { id, name, avatar }
		 */
		public function author() {
			$author_id = $this->author_id();
			$user      = get_userdata( $author_id );
			return array(
				'id'     => $author_id,
				'name'   => $user ? $user->display_name : '',
				'avatar' => get_avatar_url( $author_id, array( 'size' => 48 ) ),
			);
		}

		/**
		 * The canonical response shape — used by REST JSON AND the SSR render parts.
		 *
		 * @param int $viewer_id Current user, for permission flags. 0 = current.
		 * @return array
		 */
		public function prepare_for_response( $viewer_id = 0 ) {
			$viewer_id = $viewer_id ? (int) $viewer_id : get_current_user_id();

			$priority = $this->priority();

			return array(
				'id'             => $this->id(),
				'title'          => $this->post->post_title,
				'description'    => $this->post->post_content,
				'status'         => $this->status(),
				'is_complete'    => $this->is_complete(),
				'priority'       => $priority,
				'priority_label' => self::priority_label( $priority ),
				'due_date'       => $this->due_date(),
				'due_human'      => $this->due_human(),
				'category'       => $this->category(),
				'group_id'       => $this->group_id(),
				'author'         => $this->author(),
				'is_owner'       => $this->author_id() === $viewer_id,
				'can_edit'       => Bptodo_Permissions::can_edit( $this, $viewer_id ),
				'can_delete'     => Bptodo_Permissions::can_delete( $this, $viewer_id ),
				'permalink'      => get_permalink( $this->id() ),
			);
		}

		/**
		 * Translated label for a priority value.
		 *
		 * @param string $priority Priority.
		 * @return string
		 */
		public static function priority_label( $priority ) {
			switch ( $priority ) {
				case 'critical':
					return __( 'Critical', 'wb-todo' );
				case 'high':
					return __( 'High', 'wb-todo' );
				default:
					return __( 'Normal', 'wb-todo' );
			}
		}
	}
}
