<?php
/**
 * REST controller for the bptodo/v1 API.
 *
 * A thin adapter over the domain layer: routes validate + authorize, then delegate
 * to Bptodo_Todo_Repository / Bptodo_Permissions / Bptodo_Categories. No business
 * logic lives here. Backs the modern blocks + Interactivity, and any future client
 * (mobile app, headless).
 *
 * @package bp-user-todo-list
 * @since   4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bptodo_REST_Controller' ) ) {

	/**
	 * Class Bptodo_REST_Controller.
	 */
	class Bptodo_REST_Controller {

		const NAMESPACE_V1 = 'bptodo/v1';

		/**
		 * Register all routes.
		 *
		 * @return void
		 */
		public function register_routes() {
			$ns = self::NAMESPACE_V1;

			register_rest_route(
				$ns,
				'/todos',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_todos' ),
						'permission_callback' => array( $this, 'auth_logged_in' ),
						'args'                => $this->collection_args(),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_todo' ),
						'permission_callback' => array( $this, 'auth_can_create' ),
						'args'                => $this->writable_args( true ),
					),
				)
			);

			register_rest_route(
				$ns,
				'/todos/(?P<id>\d+)',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_todo' ),
						'permission_callback' => array( $this, 'auth_can_view' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_todo' ),
						'permission_callback' => array( $this, 'auth_can_edit' ),
						'args'                => $this->writable_args( false ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_todo' ),
						'permission_callback' => array( $this, 'auth_can_delete' ),
					),
				)
			);

			register_rest_route(
				$ns,
				'/todos/(?P<id>\d+)/complete',
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'complete_todo' ),
						'permission_callback' => array( $this, 'auth_can_complete' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'uncomplete_todo' ),
						'permission_callback' => array( $this, 'auth_can_complete' ),
					),
				)
			);

			register_rest_route(
				$ns,
				'/todos/stats',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_stats' ),
					'permission_callback' => array( $this, 'auth_logged_in' ),
					'args'                => array(
						'user_id'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
						'group_id' => array( 'sanitize_callback' => 'absint' ),
					),
				)
			);

			register_rest_route(
				$ns,
				'/categories',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_categories' ),
						'permission_callback' => array( $this, 'auth_logged_in' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_category' ),
						'permission_callback' => array( $this, 'auth_can_add_category' ),
						'args'                => array(
							'name' => array(
								'required'          => true,
								'sanitize_callback' => 'sanitize_text_field',
							),
						),
					),
				)
			);

			register_rest_route(
				$ns,
				'/groups/(?P<group_id>\d+)/todos',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_group_todos' ),
						'permission_callback' => array( $this, 'auth_group_member' ),
						'args'                => $this->collection_args(),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_group_todo' ),
						'permission_callback' => array( $this, 'auth_can_create_group_todo' ),
						'args'                => $this->writable_args( true ) + array(
							'assignees' => array(
								'sanitize_callback' => array( $this, 'sanitize_assignees' ),
							),
						),
					),
				)
			);
		}

		/* --------------------------------------------------------------------- */
		/* Argument schemas                                                       */
		/* --------------------------------------------------------------------- */

		/**
		 * Args for list/collection routes.
		 *
		 * @return array
		 */
		protected function collection_args() {
			return array(
				'status'   => array(
					'default'           => 'all',
					'enum'              => array( 'all', 'complete', 'incomplete' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
				'priority' => array(
					'default'           => 'all',
					'enum'              => array( 'all', 'normal', 'high', 'critical' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
				'category' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				'search'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
				'orderby'  => array(
					'default'           => 'smart',
					'enum'              => array( 'smart', 'due_date', 'date', 'title', 'priority' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
				'order'    => array(
					'default'           => 'ASC',
					'enum'              => array( 'ASC', 'DESC' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
				'per_page' => array(
					'default'           => 10,
					'sanitize_callback' => 'absint',
					'validate_callback' => static function ( $v ) {
						return $v >= 1 && $v <= 100;
					},
				),
				'page'     => array(
					'default'           => 1,
					'sanitize_callback' => 'absint',
				),
				'user_id'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
			);
		}

		/**
		 * Args for create/update routes.
		 *
		 * @param bool $creating Whether this is a create (title required).
		 * @return array
		 */
		protected function writable_args( $creating ) {
			return array(
				'title'       => array(
					'required'          => (bool) $creating,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'description' => array( 'sanitize_callback' => 'wp_kses_post' ),
				'priority'    => array(
					'enum'              => array( 'normal', 'high', 'critical' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
				'due_date'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
				'category'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
			);
		}

		/**
		 * Sanitize an assignees list ('all' or int[]).
		 *
		 * @param mixed $value Raw value.
		 * @return array
		 */
		public function sanitize_assignees( $value ) {
			$value = (array) $value;
			if ( in_array( 'all', $value, true ) ) {
				return array( 'all' );
			}
			return array_values( array_filter( array_map( 'absint', $value ) ) );
		}

		/* --------------------------------------------------------------------- */
		/* Permission callbacks                                                   */
		/* --------------------------------------------------------------------- */

		/** @return bool|WP_Error */
		public function auth_logged_in() {
			return is_user_logged_in() ? true : $this->err_auth();
		}

		/** @param WP_REST_Request $request Request. @return bool|WP_Error */
		public function auth_can_create( $request ) {
			if ( ! is_user_logged_in() ) {
				return $this->err_auth();
			}
			return Bptodo_Permissions::can_create() ? true : $this->err_forbidden();
		}

		/** @param WP_REST_Request $request Request. @return bool|WP_Error */
		public function auth_can_view( $request ) {
			if ( ! is_user_logged_in() ) {
				return $this->err_auth();
			}
			return Bptodo_Permissions::can_view( (int) $request['id'] ) ? true : $this->err_forbidden();
		}

		/** @param WP_REST_Request $request Request. @return bool|WP_Error */
		public function auth_can_edit( $request ) {
			if ( ! is_user_logged_in() ) {
				return $this->err_auth();
			}
			return Bptodo_Permissions::can_edit( (int) $request['id'] ) ? true : $this->err_forbidden();
		}

		/** @param WP_REST_Request $request Request. @return bool|WP_Error */
		public function auth_can_delete( $request ) {
			if ( ! is_user_logged_in() ) {
				return $this->err_auth();
			}
			return Bptodo_Permissions::can_delete( (int) $request['id'] ) ? true : $this->err_forbidden();
		}

		/** @param WP_REST_Request $request Request. @return bool|WP_Error */
		public function auth_can_complete( $request ) {
			if ( ! is_user_logged_in() ) {
				return $this->err_auth();
			}
			return Bptodo_Permissions::can_complete( (int) $request['id'] ) ? true : $this->err_forbidden();
		}

		/** @return bool|WP_Error */
		public function auth_can_add_category() {
			if ( ! is_user_logged_in() ) {
				return $this->err_auth();
			}
			return Bptodo_Categories::user_can_add() ? true : $this->err_forbidden();
		}

		/** @param WP_REST_Request $request Request. @return bool|WP_Error */
		public function auth_group_member( $request ) {
			if ( ! is_user_logged_in() ) {
				return $this->err_auth();
			}
			$group_id = (int) $request['group_id'];
			if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( get_current_user_id(), $group_id ) ) {
				return true;
			}
			return Bptodo_Permissions::is_group_manager( get_current_user_id(), $group_id ) ? true : $this->err_forbidden();
		}

		/** @param WP_REST_Request $request Request. @return bool|WP_Error */
		public function auth_can_create_group_todo( $request ) {
			if ( ! is_user_logged_in() ) {
				return $this->err_auth();
			}
			$group_id  = (int) $request['group_id'];
			$assignees = $request->get_param( 'assignees' );
			$assigning = ! empty( $assignees );
			return Bptodo_Permissions::can_create_group_todo( $group_id, $assigning ) ? true : $this->err_forbidden();
		}

		/* --------------------------------------------------------------------- */
		/* Route callbacks                                                        */
		/* --------------------------------------------------------------------- */

		/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
		public function get_todos( $request ) {
			$viewer  = get_current_user_id();
			$user_id = $request->get_param( 'user_id' );

			$author = $viewer;
			if ( null !== $user_id && '' !== $user_id ) {
				if ( ! Bptodo_Permissions::can_view_scope( $user_id, $viewer ) ) {
					return $this->err_forbidden( __( 'You do not have permission to view these todos.', 'bp-user-to-do-list' ) );
				}
				$author = ( 'all' === $user_id ) ? 0 : (int) $user_id;
			}

			$result = Bptodo_Todo_Repository::query(
				array(
					'author'        => $author,
					'exclude_group' => true,
					'status'        => $request->get_param( 'status' ),
					'priority'      => $request->get_param( 'priority' ),
					'category'      => (string) $request->get_param( 'category' ),
					'search'        => (string) $request->get_param( 'search' ),
					'orderby'       => $request->get_param( 'orderby' ),
					'order'         => $request->get_param( 'order' ),
					'per_page'      => (int) $request->get_param( 'per_page' ),
					'page'          => (int) $request->get_param( 'page' ),
				)
			);

			return $this->collection_response( $result, $viewer );
		}

		/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
		public function get_group_todos( $request ) {
			$viewer = get_current_user_id();
			$result = Bptodo_Todo_Repository::query(
				Bptodo_Todo_Repository::group_scope_args( (int) $request['group_id'], $viewer ) + array(
					'status'   => $request->get_param( 'status' ),
					'priority' => $request->get_param( 'priority' ),
					'category' => (string) $request->get_param( 'category' ),
					'search'   => (string) $request->get_param( 'search' ),
					'orderby'  => $request->get_param( 'orderby' ),
					'order'    => $request->get_param( 'order' ),
					'per_page' => (int) $request->get_param( 'per_page' ),
					'page'     => (int) $request->get_param( 'page' ),
				)
			);
			return $this->collection_response( $result, $viewer );
		}

		/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
		public function get_todo( $request ) {
			$todo = Bptodo_Todo_Repository::get( (int) $request['id'] );
			if ( ! $todo ) {
				return $this->err_not_found();
			}
			return rest_ensure_response( $todo->prepare_for_response( get_current_user_id() ) );
		}

		/**
		 * Enforce the `req_duedate` setting on create (server-side authority for
		 * the modern form's client-side check).
		 *
		 * @param WP_REST_Request $request Request.
		 * @return WP_Error|null Error when a required due date is missing.
		 */
		protected function require_due_error( $request ) {
			$settings = get_option( 'user_todo_list_settings' );
			if ( empty( $settings['req_duedate'] ) || 'yes' !== $settings['req_duedate'] ) {
				return null;
			}
			$due = trim( (string) $request->get_param( 'due_date' ) );
			if ( '' === $due ) {
				return new WP_Error( 'bptodo_due_required', __( 'A due date is required.', 'bp-user-to-do-list' ), array( 'status' => 400 ) );
			}
			return null;
		}

		/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
		public function create_todo( $request ) {
			$due_error = $this->require_due_error( $request );
			if ( $due_error ) {
				return $due_error;
			}
			$todo = Bptodo_Todo_Repository::create( $this->extract_writable( $request ), get_current_user_id() );
			if ( is_wp_error( $todo ) ) {
				return $todo;
			}
			return rest_ensure_response( $todo->prepare_for_response( get_current_user_id() ) );
		}

		/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
		public function create_group_todo( $request ) {
			$due_error = $this->require_due_error( $request );
			if ( $due_error ) {
				return $due_error;
			}
			$assignees = $request->get_param( 'assignees' );
			$todo      = Bptodo_Todo_Repository::assign_to_group(
				$this->extract_writable( $request ),
				(int) $request['group_id'],
				null === $assignees ? array() : (array) $assignees,
				get_current_user_id()
			);
			if ( is_wp_error( $todo ) ) {
				return $todo;
			}
			return rest_ensure_response( $todo->prepare_for_response( get_current_user_id() ) );
		}

		/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
		public function update_todo( $request ) {
			$todo = Bptodo_Todo_Repository::update( (int) $request['id'], $this->extract_writable( $request, true ) );
			if ( is_wp_error( $todo ) ) {
				return $todo;
			}
			return rest_ensure_response( $todo->prepare_for_response( get_current_user_id() ) );
		}

		/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
		public function delete_todo( $request ) {
			$result = Bptodo_Todo_Repository::delete( (int) $request['id'] );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			return rest_ensure_response(
				array(
					'deleted' => true,
					'id'      => (int) $request['id'],
				)
			);
		}

		/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
		public function complete_todo( $request ) {
			$todo = Bptodo_Todo_Repository::set_status( (int) $request['id'], 'complete' );
			if ( is_wp_error( $todo ) ) {
				return $todo;
			}
			return rest_ensure_response( $todo->prepare_for_response( get_current_user_id() ) );
		}

		/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
		public function uncomplete_todo( $request ) {
			$todo = Bptodo_Todo_Repository::set_status( (int) $request['id'], 'incomplete' );
			if ( is_wp_error( $todo ) ) {
				return $todo;
			}
			return rest_ensure_response( $todo->prepare_for_response( get_current_user_id() ) );
		}

		/** @param WP_REST_Request $request Request. @return WP_REST_Response */
		public function get_stats( $request ) {
			$args    = array();
			$user_id = $request->get_param( 'user_id' );
			// Same scope rule as the lists: another user's (or everyone's) counts only
			// for viewers allowed to see that scope; otherwise the viewer's own.
			if ( null !== $user_id && '' !== $user_id && ! Bptodo_Permissions::can_view_scope( $user_id, get_current_user_id() ) ) {
				$user_id = null;
			}
			if ( null !== $user_id && '' !== $user_id && 'all' !== $user_id ) {
				$args['author'] = (int) $user_id;
			} elseif ( 'all' !== $user_id ) {
				$args['author'] = get_current_user_id();
			}
			$group_id = (int) $request->get_param( 'group_id' );
			if ( $group_id ) {
				$args = Bptodo_Todo_Repository::group_scope_args( $group_id );
			} else {
				// Personal stats mirror the personal list: exclude group todos.
				$args['exclude_group'] = true;
			}
			return rest_ensure_response( Bptodo_Todo_Repository::stats( $args ) );
		}

		/** @return WP_REST_Response */
		public function get_categories() {
			return rest_ensure_response( Bptodo_Categories::all() );
		}

		/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
		public function create_category( $request ) {
			$term = Bptodo_Categories::create( $request->get_param( 'name' ) );
			if ( is_wp_error( $term ) ) {
				return $term;
			}
			return rest_ensure_response( $term );
		}

		/* --------------------------------------------------------------------- */
		/* Helpers                                                                */
		/* --------------------------------------------------------------------- */

		/**
		 * Extract the writable fields present in the request.
		 *
		 * @param WP_REST_Request $request  Request.
		 * @param bool            $for_update Only include explicitly-sent keys.
		 * @return array
		 */
		protected function extract_writable( $request, $for_update = false ) {
			$data = array();
			foreach ( array( 'title', 'description', 'priority', 'due_date', 'category' ) as $key ) {
				$val = $request->get_param( $key );
				if ( null !== $val ) {
					$data[ $key ] = $val;
				}
			}
			return $data;
		}

		/**
		 * Build a paginated collection response with WP-style headers.
		 *
		 * @param array $result Repository query result.
		 * @param int   $viewer Viewer user ID.
		 * @return WP_REST_Response
		 */
		protected function collection_response( $result, $viewer ) {
			$items = array();
			foreach ( $result['items'] as $todo ) {
				$items[] = $todo->prepare_for_response( $viewer );
			}
			$response = rest_ensure_response(
				array(
					'items'       => $items,
					'total'       => (int) $result['total'],
					'total_pages' => (int) $result['total_pages'],
					'page'        => (int) $result['page'],
					'per_page'    => (int) $result['per_page'],
				)
			);
			$response->header( 'X-WP-Total', (int) $result['total'] );
			$response->header( 'X-WP-TotalPages', (int) $result['total_pages'] );
			return $response;
		}

		/** @param string $msg Message. @return WP_Error */
		protected function err_auth( $msg = '' ) {
			return new WP_Error( 'bptodo_rest_unauthorized', $msg ? $msg : __( 'You must be logged in.', 'bp-user-to-do-list' ), array( 'status' => rest_authorization_required_code() ) );
		}

		/** @param string $msg Message. @return WP_Error */
		protected function err_forbidden( $msg = '' ) {
			return new WP_Error( 'bptodo_rest_forbidden', $msg ? $msg : __( 'You are not allowed to do that.', 'bp-user-to-do-list' ), array( 'status' => 403 ) );
		}

		/** @param string $msg Message. @return WP_Error */
		protected function err_not_found( $msg = '' ) {
			return new WP_Error( 'bptodo_rest_not_found', $msg ? $msg : __( 'Todo not found.', 'bp-user-to-do-list' ), array( 'status' => 404 ) );
		}
	}
}
