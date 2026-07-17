<?php
/**
 * Permission authority.
 *
 * The single place every capability decision about a todo is made. Replaces the
 * "owner || edit_others_posts || group admin/mod" check that was copy-pasted across
 * the AJAX handlers. Standalone-safe: group checks are guarded so the plugin works
 * with no BuddyPress present.
 *
 * @package bp-user-todo-list
 * @since   4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bptodo_Permissions' ) ) {

	/**
	 * Class Bptodo_Permissions.
	 */
	class Bptodo_Permissions {

		/**
		 * Normalize a user id (0 => current user).
		 *
		 * @param int $user_id User ID.
		 * @return int
		 */
		protected static function uid( $user_id ) {
			return $user_id ? (int) $user_id : (int) get_current_user_id();
		}

		/**
		 * Is the plugin's group layer available (BuddyPress/BuddyBoss active)?
		 *
		 * @return bool
		 */
		public static function is_bp_active() {
			return function_exists( 'bptodo_is_bp_active' )
				? bptodo_is_bp_active()
				: function_exists( 'buddypress' );
		}

		/**
		 * Is the user an admin/mod of a group (site admins always qualify).
		 *
		 * @param int $user_id  User ID.
		 * @param int $group_id Group ID.
		 * @return bool
		 */
		public static function is_group_manager( $user_id, $group_id ) {
			$user_id  = self::uid( $user_id );
			$group_id = (int) $group_id;
			if ( ! $group_id || ! $user_id ) {
				return false;
			}
			if ( user_can( $user_id, 'manage_options' ) ) {
				return true;
			}
			if ( ! self::is_bp_active() ) {
				return false;
			}
			$is_admin = function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $user_id, $group_id );
			$is_mod   = function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $user_id, $group_id );
			return (bool) ( $is_admin || $is_mod );
		}

		/**
		 * Can the user modify (edit/complete) a todo?
		 *
		 * Mirrors the historic rule: owner, or `edit_others_posts`, or manager of the
		 * todo's group.
		 *
		 * @param Bptodo_Todo|int $todo    Todo or ID.
		 * @param int             $user_id User ID (0 = current).
		 * @return bool
		 */
		public static function can_edit( $todo, $user_id = 0 ) {
			$todo = $todo instanceof Bptodo_Todo ? $todo : Bptodo_Todo::from_id( $todo );
			if ( ! $todo ) {
				return false;
			}
			$user_id = self::uid( $user_id );
			if ( ! $user_id ) {
				return false;
			}
			if ( $todo->author_id() === $user_id ) {
				return true;
			}
			if ( user_can( $user_id, 'edit_others_posts' ) ) {
				return true;
			}
			return self::is_group_manager( $user_id, $todo->group_id() );
		}

		/**
		 * Can the user complete/undo a todo? (Same rule as edit.)
		 *
		 * @param Bptodo_Todo|int $todo    Todo or ID.
		 * @param int             $user_id User ID.
		 * @return bool
		 */
		public static function can_complete( $todo, $user_id = 0 ) {
			return self::can_edit( $todo, $user_id );
		}

		/**
		 * Can the user delete a todo? (Same rule as edit.)
		 *
		 * @param Bptodo_Todo|int $todo    Todo or ID.
		 * @param int             $user_id User ID.
		 * @return bool
		 */
		public static function can_delete( $todo, $user_id = 0 ) {
			return self::can_edit( $todo, $user_id );
		}

		/**
		 * Can the user view a single todo?
		 *
		 * Todos are visible to their owner, anyone who can edit them, and — matching
		 * the existing BuddyPress profile-tab behaviour — fellow group members for
		 * group todos.
		 *
		 * @param Bptodo_Todo|int $todo    Todo or ID.
		 * @param int             $user_id User ID.
		 * @return bool
		 */
		public static function can_view( $todo, $user_id = 0 ) {
			$todo = $todo instanceof Bptodo_Todo ? $todo : Bptodo_Todo::from_id( $todo );
			if ( ! $todo ) {
				return false;
			}
			if ( self::can_edit( $todo, $user_id ) ) {
				return true;
			}
			$group_id = $todo->group_id();
			if ( $group_id && self::is_bp_active() && function_exists( 'groups_is_user_member' ) ) {
				return (bool) groups_is_user_member( self::uid( $user_id ), $group_id );
			}
			return false;
		}

		/**
		 * Can the user create a personal todo?
		 *
		 * Honors the optional `bptodo_user_roles` restriction from settings.
		 *
		 * @param int $user_id User ID.
		 * @return bool
		 */
		public static function can_create( $user_id = 0 ) {
			$user_id = self::uid( $user_id );
			if ( ! $user_id ) {
				return false;
			}
			$settings = get_option( 'user_todo_list_settings' );
			if ( ! empty( $settings['bptodo_user_roles'] ) && is_array( $settings['bptodo_user_roles'] ) ) {
				$user = get_userdata( $user_id );
				if ( ! $user ) {
					return false;
				}
				if ( in_array( 'administrator', (array) $user->roles, true ) ) {
					return true;
				}
				return (bool) array_intersect( (array) $user->roles, $settings['bptodo_user_roles'] );
			}
			return true;
		}

		/**
		 * Can the user create a todo in a group (and, if assigning, manage it)?
		 *
		 * @param int  $group_id  Group ID.
		 * @param bool $assigning Whether members are being assigned (needs manager).
		 * @param int  $user_id   User ID.
		 * @return bool
		 */
		public static function can_create_group_todo( $group_id, $assigning = false, $user_id = 0 ) {
			$user_id  = self::uid( $user_id );
			$group_id = (int) $group_id;
			if ( ! $group_id || ! $user_id || ! self::is_bp_active() ) {
				return false;
			}
			// Creating a group todo fans a copy out to members (an empty assignee
			// list means everyone), so it always requires a group manager
			// (admin/mod) or a site admin - matching the legacy group Add flow and
			// the documented behaviour. The $assigning flag is kept for callers but
			// no longer relaxes the check.
			unset( $assigning );
			return self::is_group_manager( $user_id, $group_id );
		}

		/**
		 * Can the viewer read someone else's / everyone's todos in a list scope?
		 *
		 * @param mixed $scope   'all', a numeric user id, or 0/self.
		 * @param int   $viewer  Viewer user ID.
		 * @return bool
		 */
		public static function can_view_scope( $scope, $viewer = 0 ) {
			$viewer = self::uid( $viewer );
			if ( ! $viewer ) {
				return false;
			}
			if ( 'all' === $scope ) {
				return user_can( $viewer, 'manage_options' );
			}
			$scope = (int) $scope;
			if ( 0 === $scope || $scope === $viewer ) {
				return true;
			}
			return user_can( $viewer, 'manage_options' );
		}
	}
}
