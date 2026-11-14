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
		 * Asset version string.
		 *
		 * In local/debug environments this returns the file's modification time so
		 * every CSS/JS edit busts the browser cache during development. In
		 * production it returns BPTODO_VERSION (bumped per release).
		 *
		 * @param string $rel_path Path relative to the plugin root (e.g. 'assets/css/bptodo-blocks.css').
		 * @return string
		 */
		protected static function asset_ver( $rel_path ) {
			if ( function_exists( 'bptodo_asset_ver' ) ) {
				return bptodo_asset_ver( $rel_path );
			}
			return BPTODO_VERSION;
		}

		/**
		 * The configured entity labels, so the modern layout honors the site
		 * owner's "Todo label" setting (e.g. Task, Goal) instead of hardcoding.
		 *
		 * @return array { @type string $singular, @type string $plural }.
		 */
		protected static function labels() {
			global $bptodo;
			$singular = ( is_object( $bptodo ) && ! empty( $bptodo->profile_menu_label ) )
				? $bptodo->profile_menu_label
				: __( 'Todo', 'wb-todo' );
			$plural   = ( is_object( $bptodo ) && ! empty( $bptodo->profile_menu_label_plural ) )
				? $bptodo->profile_menu_label_plural
				: __( 'Todos', 'wb-todo' );
			return array(
				'singular' => $singular,
				'plural'   => $plural,
			);
		}

		/**
		 * Whether a due date is mandatory (the `req_duedate` setting).
		 *
		 * @return bool
		 */
		protected static function due_required() {
			global $bptodo;
			return ( is_object( $bptodo ) && isset( $bptodo->req_duedate ) && 'yes' === $bptodo->req_duedate );
		}

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
				self::asset_ver( 'assets/js/bptodo-view.js' )
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
				wp_register_style( 'bptodo-design-system', BPTODO_PLUGIN_URL . 'assets/css/bptodo-design-system' . $suffix . '.css', array(), self::asset_ver( 'assets/css/bptodo-design-system' . $suffix . '.css' ) );
			}
			if ( ! wp_style_is( 'bptodo-components', 'registered' ) ) {
				wp_register_style( 'bptodo-components', BPTODO_PLUGIN_URL . 'assets/css/bptodo-components' . $suffix . '.css', array( 'bptodo-design-system' ), self::asset_ver( 'assets/css/bptodo-components' . $suffix . '.css' ) );
			}
			if ( ! wp_style_is( 'bptodo-blocks', 'registered' ) ) {
				wp_register_style( 'bptodo-blocks', BPTODO_PLUGIN_URL . 'assets/css/bptodo-blocks.css', array( 'bptodo-design-system' ), self::asset_ver( 'assets/css/bptodo-blocks.css' ) );
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
						'reopened'      => __( 'Task marked incomplete.', 'wb-todo' ),
						'deleted'       => __( 'Task deleted.', 'wb-todo' ),
						'added'         => __( 'Task added.', 'wb-todo' ),
						'updated'       => __( 'Task updated.', 'wb-todo' ),
						'categoryAdded' => __( 'Category added.', 'wb-todo' ),
						'titleRequired' => __( 'Please enter a title.', 'wb-todo' ),
						'dueRequired'   => __( 'Please set a due date.', 'wb-todo' ),
					),
				)
			);
		}

		/**
		 * Render the todo list.
		 *
		 * @param array $args {
		 *     Optional. Render options.
		 *
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

			// Group scope: SSR must match the REST permission model (auth_group_member).
			// Only group members can see a group's todos; site admins get read-only
			// oversight. Without this a group-todos block/shortcode placed on any page
			// would leak the first page of a group's tasks to logged-in non-members.
			if ( $is_group ) {
				$bptodo_gid         = (int) $args['group_id'];
				$bptodo_is_member   = function_exists( 'groups_is_user_member' ) && groups_is_user_member( $viewer, $bptodo_gid );
				$bptodo_can_oversee = current_user_can( 'manage_options' );
				if ( ! $bptodo_is_member && ! $bptodo_can_oversee ) {
					return '<div class="bptodo-app bptodo-app--denied"><p>' . esc_html__( 'You do not have access to this group\'s todos.', 'wb-todo' ) . '</p></div>';
				}
			}

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

			// Who may add: group scope requires a group manager (matches the legacy
			// group Add flow); member/all scope uses the personal create permission.
			$bptodo_can_add = $is_group
				? Bptodo_Permissions::can_create_group_todo( (int) $args['group_id'], false, $viewer )
				: Bptodo_Permissions::can_create( $viewer );

			// The inline quick-add only submits a title. When a due date is mandatory
			// (`req_duedate`) it can never satisfy the rule and every submit 400s, so
			// hide it and steer users to the full Add form (which has the due field).
			$bptodo_due_required = self::due_required();
			$bptodo_show_quick   = (bool) $args['show_add'] && $bptodo_can_add && ! $bptodo_due_required;

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
				$bptodo_scope = Bptodo_Todo_Repository::group_scope_args( (int) $args['group_id'], $viewer );
				$query_args   = $bptodo_scope + $query_args;
				$stats        = Bptodo_Todo_Repository::stats( $bptodo_scope );
			} else {
				$query_args['author']        = $author;
				$query_args['exclude_group'] = true;
				$stats                       = Bptodo_Todo_Repository::stats(
					$author
						? array(
							'author'        => $author,
							'exclude_group' => true,
						)
						: array( 'exclude_group' => true )
				);
			}

			$result = Bptodo_Todo_Repository::query( $query_args );
			$todos  = array();
			foreach ( $result['items'] as $todo ) {
				$todos[] = $todo->prepare_for_response( $viewer );
			}

			$context = array(
				'scope'       => $args['scope'],
				'userId'      => (string) $args['user_id'],
				'groupId'     => (int) $args['group_id'],
				'status'      => $args['status'],
				'priority'    => $args['priority'],
				'category'    => (string) $args['category'],
				'search'      => '',
				'orderby'     => $args['orderby'],
				'order'       => $args['order'],
				'page'        => (int) $result['page'],
				'perPage'     => (int) $result['per_page'],
				'totalPages'  => (int) $result['total_pages'],
				'total'       => (int) $result['total'],
				'todos'       => $todos,
				'stats'       => $stats,
				'isBusy'      => false,
				'showAdd'     => $bptodo_show_quick,
				'draftTitle'  => '',
				'canCreate'   => $bptodo_can_add,
				'isFirstPage' => 1 >= (int) $result['page'],
				'isLastPage'  => (int) $result['page'] >= (int) $result['total_pages'],
				'singlePage'  => (int) $result['total_pages'] <= 1,
			);

			$categories     = Bptodo_Categories::all();
			$bptodo_labels  = self::labels();
			$label_singular = $bptodo_labels['singular'];
			$label_plural   = $bptodo_labels['plural'];

			ob_start();
			?>
			<div
				class="bptodo-app bptodo-wrapper"
				data-wp-interactive="bptodo"
				data-wp-context='<?php echo esc_attr( wp_json_encode( $context ) ); ?>'
			>
				<?php if ( $args['show_stats'] ) : ?>
					<div class="bptodo-summary">
						<div class="bptodo-summary__nums">
							<?php
							// The count must stay its own node for the data-wp-text binding, so
							// the label is necessarily a separate fragment here. Give each one a
							// gettext context - "total"/"pending"/"done" are ambiguous alone and
							// several languages need different words than the adjectives used
							// elsewhere in the UI.
							?>
							<span class="bptodo-summary__stat"><b data-wp-text="context.stats.total"><?php echo esc_html( $stats['total'] ); ?></b> <?php echo esc_html( _x( 'total', 'todo count stat label', 'wb-todo' ) ); ?></span>
							<span class="bptodo-summary__stat bptodo-summary__stat--pending"><b data-wp-text="context.stats.pending"><?php echo esc_html( $stats['pending'] ); ?></b> <?php echo esc_html( _x( 'pending', 'todo count stat label', 'wb-todo' ) ); ?></span>
							<span class="bptodo-summary__stat bptodo-summary__stat--done"><b data-wp-text="context.stats.completed"><?php echo esc_html( $stats['completed'] ); ?></b> <?php echo esc_html( _x( 'done', 'todo count stat label', 'wb-todo' ) ); ?></span>
							<span class="bptodo-summary__pct"><b data-wp-text="context.stats.progress"><?php echo esc_html( $stats['progress'] ); ?></b>%</span>
						</div>
						<div class="bptodo-summary__track">
							<span class="bptodo-summary__fill" data-wp-style--width="state.progressWidth" style="width: <?php echo esc_attr( $stats['progress'] ); ?>%;"></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $context['showAdd'] ) : ?>
					<form class="bptodo-add" data-wp-on--submit="actions.quickAdd">
						<span class="bptodo-add__plus" aria-hidden="true">+</span>
						<input
							type="text"
							class="bptodo-add__input"
							placeholder="<?php echo esc_attr( sprintf( /* translators: %s: singular todo label, lowercased. */ __( 'Add a %s and press Enter…', 'wb-todo' ), strtolower( $label_singular ) ) ); ?>"
							data-wp-bind--value="context.draftTitle"
							data-wp-on--input="actions.setDraft"
							aria-label="<?php esc_attr_e( 'New task title', 'wb-todo' ); ?>"
						/>
						<button type="submit" class="bptodo-btn bptodo-btn--primary bptodo-btn--sm"><?php esc_html_e( 'Add', 'wb-todo' ); ?></button>
					</form>
				<?php elseif ( $bptodo_can_add && $bptodo_due_required && $args['show_add'] ) : ?>
					<p class="bptodo-add-hint"><?php echo esc_html( sprintf( /* translators: %s: singular todo label, lowercased. */ __( 'A due date is required. Use the Add %s form to set one.', 'wb-todo' ), strtolower( $label_singular ) ) ); ?></p>
				<?php endif; ?>

				<div class="bptodo-filters">
					<input type="search" class="bptodo-filter bptodo-filter--search" placeholder="<?php esc_attr_e( 'Search todos…', 'wb-todo' ); ?>" data-wp-on--input="actions.setSearch" aria-label="<?php esc_attr_e( 'Search todos', 'wb-todo' ); ?>" />
					<select class="bptodo-filter" data-wp-on--change="actions.setStatus" aria-label="<?php esc_attr_e( 'Filter by status', 'wb-todo' ); ?>">
						<option value="all"><?php esc_html_e( 'All status', 'wb-todo' ); ?></option>
						<option value="incomplete"><?php esc_html_e( 'Pending', 'wb-todo' ); ?></option>
						<option value="complete"><?php esc_html_e( 'Completed', 'wb-todo' ); ?></option>
					</select>
					<select class="bptodo-filter" data-wp-on--change="actions.setPriority" aria-label="<?php esc_attr_e( 'Filter by priority', 'wb-todo' ); ?>">
						<option value="all"><?php esc_html_e( 'All priorities', 'wb-todo' ); ?></option>
						<option value="critical"><?php esc_html_e( 'Critical', 'wb-todo' ); ?></option>
						<option value="high"><?php esc_html_e( 'High', 'wb-todo' ); ?></option>
						<option value="normal"><?php esc_html_e( 'Normal', 'wb-todo' ); ?></option>
					</select>
					<?php if ( ! empty( $categories ) ) : ?>
						<select class="bptodo-filter" data-wp-on--change="actions.setCategory" aria-label="<?php esc_attr_e( 'Filter by category', 'wb-todo' ); ?>">
							<option value=""><?php esc_html_e( 'All categories', 'wb-todo' ); ?></option>
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat['slug'] ); ?>"><?php echo esc_html( $cat['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
				</div>

				<p class="bptodo-count"><span data-wp-text="context.total"><?php echo esc_html( $result['total'] ); ?></span> <?php echo esc_html( strtolower( $label_plural ) ); ?></p>

				<ul class="bptodo-list" role="list" data-wp-bind--aria-busy="context.isBusy">
					<template data-wp-each--todo="context.todos" data-wp-each-key="context.todo.id">
						<li class="bptodo-item" data-wp-class--is-done="context.todo.is_complete" data-wp-class--is-editing="state.isEditing" data-wp-bind--data-priority="context.todo.priority">
							<div class="bptodo-item__row" data-wp-bind--hidden="state.isEditing">
								<label class="bptodo-check">
									<input type="checkbox" data-wp-bind--checked="context.todo.is_complete" data-wp-bind--disabled="!context.todo.can_edit" data-wp-on--change="actions.toggleComplete" aria-label="<?php esc_attr_e( 'Toggle complete', 'wb-todo' ); ?>" />
									<span class="bptodo-check__box" aria-hidden="true"></span>
								</label>
								<span class="bptodo-item__main">
									<span class="bptodo-item__title" data-wp-text="context.todo.title"></span>
									<span class="bptodo-item__sub">
										<span class="bptodo-item__prio" data-wp-text="context.todo.priority_label"></span>
										<span class="bptodo-item__due" data-wp-bind--data-due="context.todo.due_human.state" data-wp-text="context.todo.due_human.text"></span>
										<button type="button" class="bptodo-item__more" data-wp-bind--hidden="!context.todo.description" data-wp-on--click="actions.toggleDetails" data-wp-bind--aria-expanded="context.todo.detailsOpen" data-wp-class--is-open="context.todo.detailsOpen"><?php esc_html_e( 'Details', 'wb-todo' ); ?></button>
									</span>
									<p class="bptodo-item__note" data-wp-bind--hidden="!context.todo.detailsOpen" data-wp-text="context.todo.description"></p>
								</span>
								<span class="bptodo-item__act">
									<button type="button" class="bptodo-iconbtn" data-wp-on--click="actions.startEdit" data-wp-bind--hidden="!state.showEditBtn"><?php esc_html_e( 'Edit', 'wb-todo' ); ?></button>
									<button type="button" class="bptodo-iconbtn bptodo-iconbtn--danger" data-wp-on--click="actions.askDelete" data-wp-bind--hidden="!state.showDeleteBtn"><?php esc_html_e( 'Delete', 'wb-todo' ); ?></button>
									<span class="bptodo-confirm" role="group" aria-label="<?php esc_attr_e( 'Confirm delete', 'wb-todo' ); ?>" data-wp-bind--hidden="!state.showConfirm">
										<span class="bptodo-confirm__text"><?php esc_html_e( 'Delete?', 'wb-todo' ); ?></span>
										<button type="button" class="bptodo-iconbtn bptodo-iconbtn--danger" data-wp-on--click="actions.confirmDelete"><?php esc_html_e( 'Yes', 'wb-todo' ); ?></button>
										<button type="button" class="bptodo-iconbtn" data-wp-on--click="actions.cancelDelete"><?php esc_html_e( 'No', 'wb-todo' ); ?></button>
									</span>
								</span>
							</div>
							<form class="bptodo-item__edit" data-wp-bind--hidden="!state.isEditing" data-wp-on--submit="actions.saveEdit">
								<input type="text" class="bptodo-input bptodo-item__editfull" data-wp-bind--value="context.todo.editTitle" data-wp-on--input="actions.setEditTitle" aria-label="<?php esc_attr_e( 'Edit title', 'wb-todo' ); ?>" />
								<textarea class="bptodo-input bptodo-textarea bptodo-item__editfull" rows="2" placeholder="<?php esc_attr_e( 'Details (optional)', 'wb-todo' ); ?>" data-wp-bind--value="context.todo.editDesc" data-wp-on--input="actions.setEditDesc" aria-label="<?php esc_attr_e( 'Edit details', 'wb-todo' ); ?>"></textarea>
								<select class="bptodo-select" data-wp-bind--value="context.todo.editPriority" data-wp-on--change="actions.setEditPriority" aria-label="<?php esc_attr_e( 'Edit priority', 'wb-todo' ); ?>">
									<option value="critical"><?php esc_html_e( 'Critical', 'wb-todo' ); ?></option>
									<option value="high"><?php esc_html_e( 'High', 'wb-todo' ); ?></option>
									<option value="normal"><?php esc_html_e( 'Normal', 'wb-todo' ); ?></option>
								</select>
								<input type="date" class="bptodo-input" data-wp-bind--value="context.todo.editDue" data-wp-on--change="actions.setEditDue" aria-label="<?php esc_attr_e( 'Edit due date', 'wb-todo' ); ?>" />
								<button type="submit" class="bptodo-btn bptodo-btn--primary bptodo-btn--sm"><?php esc_html_e( 'Save', 'wb-todo' ); ?></button>
								<button type="button" class="bptodo-btn bptodo-btn--ghost bptodo-btn--sm" data-wp-on--click="actions.cancelEdit"><?php esc_html_e( 'Cancel', 'wb-todo' ); ?></button>
							</form>
						</li>
					</template>
				</ul>

				<div class="bptodo-empty" data-wp-bind--hidden="context.total">
					<p><?php echo esc_html( sprintf( /* translators: %s: singular todo label, lowercased. */ __( 'Nothing here yet. Add your first %s above.', 'wb-todo' ), strtolower( $label_singular ) ) ); ?></p>
				</div>

				<nav class="bptodo-pager" data-wp-bind--hidden="context.singlePage" aria-label="<?php esc_attr_e( 'Todo pagination', 'wb-todo' ); ?>">
					<button type="button" class="bptodo-btn bptodo-btn--ghost bptodo-btn--sm" data-wp-on--click="actions.prevPage" data-wp-bind--disabled="context.isFirstPage"><?php esc_html_e( 'Previous', 'wb-todo' ); ?></button>
					<span class="bptodo-pager__info"><span data-wp-text="context.page"><?php echo esc_html( $result['page'] ); ?></span> / <span data-wp-text="context.totalPages"><?php echo esc_html( $result['total_pages'] ); ?></span></span>
					<button type="button" class="bptodo-btn bptodo-btn--ghost bptodo-btn--sm" data-wp-on--click="actions.nextPage" data-wp-bind--disabled="context.isLastPage"><?php esc_html_e( 'Next', 'wb-todo' ); ?></button>
				</nav>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Render the modern full add form (the Add page).
		 *
		 * @param array $args { string $scope, int $group_id, string $list_url }.
		 * @return string
		 */
		public static function render_add_form( $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'scope'    => 'member',
					'group_id' => 0,
					'list_url' => '',
					'members'  => array(),
				)
			);

			if ( ! is_user_logged_in() ) {
				return '<div class="bptodo-app bptodo-app--loggedout"><p>' . esc_html__( 'Please log in to add a task.', 'wb-todo' ) . '</p></div>';
			}

			self::register_module();
			self::boot_state();
			self::enqueue_styles();
			if ( function_exists( 'wp_enqueue_script_module' ) ) {
				wp_enqueue_script_module( self::MODULE_ID );
			}

			$categories     = Bptodo_Categories::all();
			$is_group       = ( 'group' === $args['scope'] && ! empty( $args['members'] ) );
			$bptodo_labels  = self::labels();
			$label_singular = $bptodo_labels['singular'];
			$require_due    = self::due_required();
			$can_add_cat    = Bptodo_Categories::user_can_add();

			$context = array(
				'scope'      => $args['scope'],
				'groupId'    => (int) $args['group_id'],
				'fTitle'     => '',
				'fDesc'      => '',
				'fCat'       => '',
				'fPriority'  => 'normal',
				'fDue'       => '',
				'fAssignees' => array(),
				'fNewCat'    => '',
				'addingCat'  => false,
				'newCats'    => array(),
				'requireDue' => (bool) $require_due,
				'listUrl'    => $args['list_url'],
				'submitting' => false,
				'formError'  => '',
			);

			ob_start();
			?>
			<div class="bptodo-app bptodo-wrapper" data-wp-interactive="bptodo" data-wp-context='<?php echo esc_attr( wp_json_encode( $context ) ); ?>'>
				<form class="bptodo-addform" data-wp-on--submit="actions.submitFull">
					<div class="bptodo-field">
						<label class="bptodo-field__label" for="bptodo-add-title"><?php esc_html_e( 'Title', 'wb-todo' ); ?> <span class="bptodo-req" aria-hidden="true">*</span></label>
						<input type="text" id="bptodo-add-title" class="bptodo-input" required placeholder="<?php esc_attr_e( 'What needs to be done?', 'wb-todo' ); ?>" data-wp-bind--value="context.fTitle" data-wp-on--input="actions.setFTitle" />
					</div>

					<div class="bptodo-field">
						<label class="bptodo-field__label" for="bptodo-add-desc"><?php esc_html_e( 'Description', 'wb-todo' ); ?></label>
						<textarea id="bptodo-add-desc" class="bptodo-input bptodo-textarea" rows="4" placeholder="<?php esc_attr_e( 'Add details (optional)', 'wb-todo' ); ?>" data-wp-bind--value="context.fDesc" data-wp-on--input="actions.setFDesc"></textarea>
					</div>

					<div class="bptodo-field-grid">
						<?php if ( ! empty( $categories ) || $can_add_cat ) : ?>
							<div class="bptodo-field">
								<span class="bptodo-field__labelrow">
									<label class="bptodo-field__label" for="bptodo-add-cat"><?php esc_html_e( 'Category', 'wb-todo' ); ?></label>
									<?php if ( $can_add_cat ) : ?>
										<button type="button" class="bptodo-linkbtn" data-wp-on--click="actions.toggleAddCat" data-wp-bind--hidden="context.addingCat"><?php esc_html_e( '+ New', 'wb-todo' ); ?></button>
									<?php endif; ?>
								</span>
								<select id="bptodo-add-cat" class="bptodo-select" data-wp-bind--value="context.fCat" data-wp-on--change="actions.setFCat" data-wp-bind--hidden="context.addingCat">
									<option value=""><?php esc_html_e( 'None', 'wb-todo' ); ?></option>
									<?php foreach ( $categories as $bptodo_cat ) : ?>
										<option value="<?php echo esc_attr( $bptodo_cat['slug'] ); ?>"><?php echo esc_html( $bptodo_cat['name'] ); ?></option>
									<?php endforeach; ?>
									<?php // Client-side: categories the member creates via "+ New" are appended here. ?>
									<template data-wp-each--cat="context.newCats" data-wp-each-key="context.cat.slug">
										<option data-wp-bind--value="context.cat.slug" data-wp-text="context.cat.name"></option>
									</template>
								</select>
								<?php if ( $can_add_cat ) : ?>
									<span class="bptodo-newcat" data-wp-bind--hidden="!context.addingCat">
										<input type="text" class="bptodo-input" placeholder="<?php esc_attr_e( 'New category name', 'wb-todo' ); ?>" data-wp-bind--value="context.fNewCat" data-wp-on--input="actions.setNewCat" data-wp-on--keydown="actions.newCatKeydown" aria-label="<?php esc_attr_e( 'New category name', 'wb-todo' ); ?>" />
										<button type="button" class="bptodo-btn bptodo-btn--primary bptodo-btn--sm" data-wp-on--click="actions.addCategory"><?php esc_html_e( 'Add', 'wb-todo' ); ?></button>
										<button type="button" class="bptodo-btn bptodo-btn--ghost bptodo-btn--sm" data-wp-on--click="actions.toggleAddCat"><?php esc_html_e( 'Cancel', 'wb-todo' ); ?></button>
									</span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<div class="bptodo-field">
							<label class="bptodo-field__label" for="bptodo-add-priority"><?php esc_html_e( 'Priority', 'wb-todo' ); ?></label>
							<select id="bptodo-add-priority" class="bptodo-select" data-wp-on--change="actions.setFPriority">
								<option value="normal"><?php esc_html_e( 'Normal', 'wb-todo' ); ?></option>
								<option value="high"><?php esc_html_e( 'High', 'wb-todo' ); ?></option>
								<option value="critical"><?php esc_html_e( 'Critical', 'wb-todo' ); ?></option>
							</select>
						</div>

						<div class="bptodo-field">
							<label class="bptodo-field__label" for="bptodo-add-due">
								<?php esc_html_e( 'Due date', 'wb-todo' ); ?>
								<?php
								if ( $require_due ) :
									?>
									<span class="bptodo-req" aria-hidden="true">*</span><?php endif; ?>
							</label>
							<input type="date" id="bptodo-add-due" class="bptodo-input" <?php echo $require_due ? 'required' : ''; ?> data-wp-on--change="actions.setFDue" />
						</div>
					</div>

					<?php if ( $is_group ) : ?>
						<div class="bptodo-field">
							<span class="bptodo-field__label"><?php esc_html_e( 'Assign to members', 'wb-todo' ); ?></span>
							<div class="bptodo-assignees" role="group" aria-label="<?php esc_attr_e( 'Assign to members', 'wb-todo' ); ?>">
								<?php foreach ( $args['members'] as $bptodo_member ) : ?>
									<label class="bptodo-assignee">
										<input type="checkbox" class="bptodo-assignee__cb" value="<?php echo esc_attr( $bptodo_member['id'] ); ?>" data-wp-on--change="actions.toggleAssignee" />
										<?php echo get_avatar( (int) $bptodo_member['id'], 28, '', $bptodo_member['name'], array( 'class' => 'bptodo-assignee__avatar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar returns a safe core-built <img>. ?>
										<span class="bptodo-assignee__name"><?php echo esc_html( $bptodo_member['name'] ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="bptodo-field__hint"><?php esc_html_e( 'Leave all unchecked to assign to everyone in the group.', 'wb-todo' ); ?></p>
						</div>
					<?php endif; ?>

					<p class="bptodo-addform__error" role="alert" data-wp-bind--hidden="!context.formError" data-wp-text="context.formError"></p>

					<div class="bptodo-addform__actions">
						<a class="bptodo-btn bptodo-btn--ghost" data-wp-bind--href="context.listUrl"><?php esc_html_e( 'Cancel', 'wb-todo' ); ?></a>
						<button type="submit" class="bptodo-btn bptodo-btn--primary" data-wp-bind--disabled="context.submitting"><?php echo esc_html( sprintf( /* translators: %s: the configured todo label (singular), e.g. "Task". */ __( 'Create %s', 'wb-todo' ), $label_singular ) ); ?></button>
					</div>
				</form>
			</div>
			<?php
			return ob_get_clean();
		}
	}
}
