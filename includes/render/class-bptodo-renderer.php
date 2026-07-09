<?php
/**
 * Modern SSR renderer for the todo list.
 *
 * Produces the interactive todo-list markup used by the blocks, and (via the
 * mode-aware wrapper) by the shortcode and BuddyPress tabs. Server-renders the
 * first page with the WP Interactivity API (data-wp-* directives) so first paint
 * is complete; the view module then hydrates and talks to the bptodo/v1 REST API
 * for complete/undo/delete/filter/paginate/quick-add — no reloads.
 *
 * @package bp-user-todo-list
 * @since   4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bptodo_Renderer' ) ) {

	/**
	 * Class Bptodo_Renderer.
	 */
	class Bptodo_Renderer {

		const MODULE_ID = 'bptodo-view';

		/**
		 * Whether the shared config state has been registered this request.
		 *
		 * @var bool
		 */
		protected static $state_booted = false;

		/**
		 * Register the interactivity view script module (once).
		 *
		 * @return void
		 */
		public static function register_module() {
			if ( ! function_exists( 'wp_register_script_module' ) ) {
				return;
			}
			wp_register_script_module(
				self::MODULE_ID,
				BPTODO_PLUGIN_URL . 'assets/js/bptodo-view.js',
				array( '@wordpress/interactivity' ),
				BPTODO_VERSION
			);
		}

		/**
		 * Register + enqueue the modern list styles (design tokens + block styles).
		 *
		 * Registered here (not only in the legacy todo-page enqueue) so the blocks are
		 * styled wherever they are placed.
		 *
		 * @return void
		 */
		public static function enqueue_styles() {
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			if ( ! wp_style_is( 'bptodo-design-system', 'registered' ) ) {
				wp_register_style( 'bptodo-design-system', BPTODO_PLUGIN_URL . 'assets/css/bptodo-design-system' . $suffix . '.css', array(), BPTODO_VERSION );
			}
			if ( ! wp_style_is( 'bptodo-components', 'registered' ) ) {
				wp_register_style( 'bptodo-components', BPTODO_PLUGIN_URL . 'assets/css/bptodo-components' . $suffix . '.css', array( 'bptodo-design-system' ), BPTODO_VERSION );
			}
			if ( ! wp_style_is( 'bptodo-blocks', 'registered' ) ) {
				wp_register_style( 'bptodo-blocks', BPTODO_PLUGIN_URL . 'assets/css/bptodo-blocks.css', array( 'bptodo-design-system' ), BPTODO_VERSION );
			}

			wp_enqueue_style( 'bptodo-design-system' );
			wp_enqueue_style( 'bptodo-components' );
			wp_enqueue_style( 'bptodo-blocks' );
		}

		/**
		 * Register the shared (global) interactivity config state, once per request.
		 *
		 * @return void
		 */
		protected static function boot_state() {
			if ( self::$state_booted || ! function_exists( 'wp_interactivity_state' ) ) {
				return;
			}
			self::$state_booted = true;
			wp_interactivity_state(
				'bptodo',
				array(
					'restRoot' => esc_url_raw( rest_url( 'bptodo/v1' ) ),
					'nonce'    => wp_create_nonce( 'wp_rest' ),
					'i18n'     => array(
						'confirmDelete' => __( 'Are you sure you want to delete this task?', 'wb-todo' ),
						'saving'        => __( 'Saving…', 'wb-todo' ),
						'error'         => __( 'Something went wrong. Please try again.', 'wb-todo' ),
						'completed'     => __( 'Task completed.', 'wb-todo' ),
						'deleted'       => __( 'Task deleted.', 'wb-todo' ),
						'added'         => __( 'Task added.', 'wb-todo' ),
					),
				)
			);
		}

		/**
		 * Render the todo list.
		 *
		 * @param array $args {
		 *     @type string $scope    'member'|'group'|'all'. Default 'member'.
		 *     @type int    $user_id  0 = current user; numeric; 'all' for admin.
		 *     @type int    $group_id Group ID for group scope.
		 *     @type string $status   Default 'all'.
		 *     @type string $priority Default 'all'.
		 *     @type string $category Default ''.
		 *     @type string $orderby  Default 'smart'.
		 *     @type string $order    Default 'ASC'.
		 *     @type int    $per_page Default 10.
		 *     @type bool   $show_add Show quick-add. Default true.
		 *     @type bool   $show_stats Show stat cards. Default true.
		 * }
		 * @return string Raw directive markup (caller processes directives).
		 */
		public static function render( $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'scope'      => 'member',
					'user_id'    => 0,
					'group_id'   => 0,
					'status'     => 'all',
					'priority'   => 'all',
					'category'   => '',
					'orderby'    => 'smart',
					'order'      => 'ASC',
					'per_page'   => 10,
					'show_add'   => true,
					'show_stats' => true,
				)
			);

			self::register_module();
			self::boot_state();
			self::enqueue_styles();
			if ( function_exists( 'wp_enqueue_script_module' ) ) {
				wp_enqueue_script_module( self::MODULE_ID );
			}

			$viewer   = get_current_user_id();
			$is_group = ( 'group' === $args['scope'] && $args['group_id'] );

			// Scope enforcement (SSR must match the REST permission model): a viewer may
			// only widen to 'all' or another user's todos if allowed; otherwise fall back
			// to their own list.
			if ( 'all' === $args['user_id'] || ( ! empty( $args['user_id'] ) && is_numeric( $args['user_id'] ) && (int) $args['user_id'] !== $viewer ) ) {
				if ( ! Bptodo_Permissions::can_view_scope( $args['user_id'], $viewer ) ) {
					$args['user_id'] = 0;
				}
			}

			// Resolve the query author for member/all scopes.
			$author = $viewer;
			if ( 'all' === $args['user_id'] ) {
				$author = 0;
			} elseif ( ! empty( $args['user_id'] ) && is_numeric( $args['user_id'] ) ) {
				$author = (int) $args['user_id'];
			}

			$query_args = array(
				'status'   => $args['status'],
				'priority' => $args['priority'],
				'category' => $args['category'],
				'orderby'  => $args['orderby'],
				'order'    => $args['order'],
				'per_page' => (int) $args['per_page'],
				'page'     => 1,
			);
			if ( $is_group ) {
				$query_args['group_id']     = (int) $args['group_id'];
				$query_args['primary_only'] = true;
				$stats                      = Bptodo_Todo_Repository::stats(
					array(
						'group_id'     => (int) $args['group_id'],
						'primary_only' => true,
					)
				);
			} else {
				$query_args['author'] = $author;
				$stats                = Bptodo_Todo_Repository::stats( $author ? array( 'author' => $author ) : array() );
			}

			$result = Bptodo_Todo_Repository::query( $query_args );
			$todos  = array();
			foreach ( $result['items'] as $todo ) {
				$todos[] = $todo->prepare_for_response( $viewer );
			}

			$context = array(
				'scope'      => $args['scope'],
				'userId'     => (string) $args['user_id'],
				'groupId'    => (int) $args['group_id'],
				'status'     => $args['status'],
				'priority'   => $args['priority'],
				'category'   => (string) $args['category'],
				'search'     => '',
				'orderby'    => $args['orderby'],
				'order'      => $args['order'],
				'page'       => (int) $result['page'],
				'perPage'    => (int) $result['per_page'],
				'totalPages' => (int) $result['total_pages'],
				'total'      => (int) $result['total'],
				'todos'      => $todos,
				'stats'      => $stats,
				'isBusy'      => false,
				'showAdd'     => (bool) $args['show_add'] && Bptodo_Permissions::can_create( $viewer ),
				'draftTitle'  => '',
				'canCreate'   => Bptodo_Permissions::can_create( $viewer ),
				'isFirstPage' => 1 >= (int) $result['page'],
				'isLastPage'  => (int) $result['page'] >= (int) $result['total_pages'],
				'singlePage'  => (int) $result['total_pages'] <= 1,
			);

			$categories = Bptodo_Categories::all();

			ob_start();
			?>
			<div
				class="bptodo-app bptodo-wrapper"
				data-wp-interactive="bptodo"
				data-wp-context='<?php echo esc_attr( wp_json_encode( $context ) ); ?>'
			>
				<?php if ( $args['show_stats'] ) : ?>
					<div class="bptodo-stats" data-wp-class--is-busy="context.isBusy">
						<div class="bptodo-stat">
							<span class="bptodo-stat__num" data-wp-text="context.stats.total"><?php echo esc_html( $stats['total'] ); ?></span>
							<span class="bptodo-stat__label"><?php esc_html_e( 'Todos', 'wb-todo' ); ?></span>
						</div>
						<div class="bptodo-stat">
							<span class="bptodo-stat__num" data-wp-text="context.stats.pending"><?php echo esc_html( $stats['pending'] ); ?></span>
							<span class="bptodo-stat__label"><?php esc_html_e( 'Pending', 'wb-todo' ); ?></span>
						</div>
						<div class="bptodo-stat">
							<span class="bptodo-stat__num" data-wp-text="context.stats.completed"><?php echo esc_html( $stats['completed'] ); ?></span>
							<span class="bptodo-stat__label"><?php esc_html_e( 'Completed', 'wb-todo' ); ?></span>
						</div>
						<div class="bptodo-stat">
							<span class="bptodo-stat__num"><span data-wp-text="context.stats.progress"><?php echo esc_html( $stats['progress'] ); ?></span>%</span>
							<span class="bptodo-stat__label"><?php esc_html_e( 'Progress', 'wb-todo' ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $context['showAdd'] ) : ?>
					<form class="bptodo-quickadd" data-wp-on--submit="actions.quickAdd">
						<input
							type="text"
							class="bptodo-input bptodo-quickadd__input"
							placeholder="<?php esc_attr_e( 'Add a task and press Enter…', 'wb-todo' ); ?>"
							data-wp-bind--value="context.draftTitle"
							data-wp-on--input="actions.setDraft"
							aria-label="<?php esc_attr_e( 'New task title', 'wb-todo' ); ?>"
						/>
						<button type="submit" class="bptodo-btn bptodo-btn--primary"><?php esc_html_e( 'Add', 'wb-todo' ); ?></button>
					</form>
				<?php endif; ?>

				<div class="bptodo-toolbar">
					<input
						type="search"
						class="bptodo-input bptodo-toolbar__search"
						placeholder="<?php esc_attr_e( 'Search todos…', 'wb-todo' ); ?>"
						data-wp-on--input="actions.setSearch"
						aria-label="<?php esc_attr_e( 'Search todos', 'wb-todo' ); ?>"
					/>
					<select class="bptodo-select" data-wp-on--change="actions.setStatus" aria-label="<?php esc_attr_e( 'Filter by status', 'wb-todo' ); ?>">
						<option value="all"><?php esc_html_e( 'All Status', 'wb-todo' ); ?></option>
						<option value="incomplete"><?php esc_html_e( 'Pending', 'wb-todo' ); ?></option>
						<option value="complete"><?php esc_html_e( 'Completed', 'wb-todo' ); ?></option>
					</select>
					<select class="bptodo-select" data-wp-on--change="actions.setPriority" aria-label="<?php esc_attr_e( 'Filter by priority', 'wb-todo' ); ?>">
						<option value="all"><?php esc_html_e( 'All Priorities', 'wb-todo' ); ?></option>
						<option value="critical"><?php esc_html_e( 'Critical', 'wb-todo' ); ?></option>
						<option value="high"><?php esc_html_e( 'High', 'wb-todo' ); ?></option>
						<option value="normal"><?php esc_html_e( 'Normal', 'wb-todo' ); ?></option>
					</select>
					<?php if ( ! empty( $categories ) ) : ?>
						<select class="bptodo-select" data-wp-on--change="actions.setCategory" aria-label="<?php esc_attr_e( 'Filter by category', 'wb-todo' ); ?>">
							<option value=""><?php esc_html_e( 'All Categories', 'wb-todo' ); ?></option>
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat['slug'] ); ?>"><?php echo esc_html( $cat['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
				</div>

				<p class="bptodo-count">
					<span data-wp-text="context.total"><?php echo esc_html( $result['total'] ); ?></span>
					<?php esc_html_e( 'todos', 'wb-todo' ); ?>
				</p>

				<div class="bptodo-list" role="list" data-wp-bind--aria-busy="context.isBusy">
					<template
						data-wp-each--todo="context.todos"
						data-wp-each-key="context.todo.id"
					>
						<div class="bptodo-card" role="listitem" data-wp-class--bptodo-card--completed="context.todo.is_complete">
							<label class="bptodo-card__check">
								<input
									type="checkbox"
									class="bptodo-checkbox__input"
									data-wp-bind--checked="context.todo.is_complete"
									data-wp-on--change="actions.toggleComplete"
								/>
							</label>
							<div class="bptodo-card__body">
								<h3 class="bptodo-card__title" data-wp-text="context.todo.title"></h3>
								<div class="bptodo-card__meta">
									<span class="bptodo-badge" data-wp-text="context.todo.priority_label"></span>
									<span class="bptodo-card__due" data-wp-text="context.todo.due_human.text"></span>
								</div>
							</div>
							<div class="bptodo-card__actions">
								<button type="button" class="bptodo-btn bptodo-btn--ghost bptodo-btn--sm bptodo-delete" data-wp-on--click="actions.deleteTodo" data-wp-bind--hidden="!context.todo.can_delete"><?php esc_html_e( 'Delete', 'wb-todo' ); ?></button>
							</div>
						</div>
					</template>
				</div>

				<div class="bptodo-empty" data-wp-bind--hidden="context.total">
					<p><?php esc_html_e( 'No todos yet.', 'wb-todo' ); ?></p>
				</div>

				<nav class="bptodo-pager" data-wp-bind--hidden="context.singlePage" aria-label="<?php esc_attr_e( 'Todo pagination', 'wb-todo' ); ?>">
					<button type="button" class="bptodo-btn bptodo-btn--ghost" data-wp-on--click="actions.prevPage" data-wp-bind--disabled="context.isFirstPage"><?php esc_html_e( 'Previous', 'wb-todo' ); ?></button>
					<span class="bptodo-pager__info">
						<span data-wp-text="context.page"><?php echo esc_html( $result['page'] ); ?></span>
						/
						<span data-wp-text="context.totalPages"><?php echo esc_html( $result['total_pages'] ); ?></span>
					</span>
					<button type="button" class="bptodo-btn bptodo-btn--ghost" data-wp-on--click="actions.nextPage" data-wp-bind--disabled="context.isLastPage"><?php esc_html_e( 'Next', 'wb-todo' ); ?></button>
				</nav>
			</div>
			<?php
			return ob_get_clean();
		}
	}
}
