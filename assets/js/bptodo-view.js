/**
 * Todo list — WP Interactivity API view module.
 *
 * Hydrates the server-rendered todo list and drives complete/undo/delete/filter/
 * paginate/quick-add against the bptodo/v1 REST API. No page reloads, no admin-ajax.
 * Buildless ES module: @wordpress/interactivity is provided by WordPress via import map.
 *
 * @package bp-user-todo-list
 * @since   4.0.0
 */

import { store, getContext, withScope } from '@wordpress/interactivity';

const { state, actions } = store( 'bptodo', {
	state: {
		// Progress bar width (root context).
		get progressWidth() {
			const stats = getContext().stats;
			return ( stats && stats.progress ? stats.progress : 0 ) + '%';
		},
		// Per-card derived visibility (evaluated in each item's context).
		get showEditBtn() {
			const todo = getContext().todo;
			return todo.can_edit && ! todo.confirming && ! todo.editing;
		},
		get showDeleteBtn() {
			const todo = getContext().todo;
			return todo.can_delete && ! todo.confirming && ! todo.editing;
		},
		get showConfirm() {
			return getContext().todo.confirming === true;
		},
		get isEditing() {
			return getContext().todo.editing === true;
		},
	},
	actions: {
		setDraft( event ) {
			getContext().draftTitle = event.target.value;
		},

		setSearch( event ) {
			const ctx = getContext();
			ctx.search = event.target.value;
			ctx.page = 1;
			clearTimeout( ctx._searchTimer );
			// withScope() preserves the Interactivity context across the async
			// setTimeout boundary; without it getContext() inside reload() is
			// undefined and the debounced search throws + never filters.
			ctx._searchTimer = setTimeout(
				withScope( () => {
					actions.reload();
				} ),
				350
			);
		},

		setStatus( event ) {
			const ctx = getContext();
			ctx.status = event.target.value;
			ctx.page = 1;
			actions.reload();
		},

		setPriority( event ) {
			const ctx = getContext();
			ctx.priority = event.target.value;
			ctx.page = 1;
			actions.reload();
		},

		setCategory( event ) {
			const ctx = getContext();
			ctx.category = event.target.value;
			ctx.page = 1;
			actions.reload();
		},

		prevPage() {
			const ctx = getContext();
			if ( ctx.page > 1 ) {
				ctx.page -= 1;
				actions.reload();
			}
		},

		nextPage() {
			const ctx = getContext();
			if ( ctx.page < ctx.totalPages ) {
				ctx.page += 1;
				actions.reload();
			}
		},

		*toggleComplete( event ) {
			const ctx = getContext();
			const todo = ctx.todo;
			const makeComplete = event.target.checked;
			// Optimistic UI.
			todo.is_complete = makeComplete;
			todo.status = makeComplete ? 'complete' : 'incomplete';
			try {
				yield bptodoApi( `/todos/${ todo.id }/complete`, makeComplete ? 'POST' : 'DELETE' );
				yield actions.refreshStats();
			} catch ( err ) {
				// Roll back on failure.
				todo.is_complete = ! makeComplete;
				todo.status = makeComplete ? 'incomplete' : 'complete';
			}
		},

		// Accessible inline delete confirmation (no window.confirm).
		askDelete() {
			getContext().todo.confirming = true;
		},

		cancelDelete() {
			getContext().todo.confirming = false;
		},

		// Expand/collapse a todo's details (description) inline in the list.
		toggleDetails() {
			const todo = getContext().todo;
			todo.detailsOpen = ! todo.detailsOpen;
		},

		*confirmDelete() {
			const ctx = getContext();
			const todo = ctx.todo;
			todo.confirming = false;
			try {
				yield bptodoApi( `/todos/${ todo.id }`, 'DELETE' );
				ctx.todos = ctx.todos.filter( ( item ) => item.id !== todo.id );
				ctx.total = Math.max( 0, ctx.total - 1 );
				yield actions.refreshStats();
				// If the page emptied out, step back a page.
				if ( ctx.todos.length === 0 && ctx.page > 1 ) {
					ctx.page -= 1;
					yield actions.reload();
				}
			} catch ( err ) {}
		},

		// Inline edit (title + details + priority + due) via PATCH.
		startEdit() {
			const todo = getContext().todo;
			todo.editTitle = todo.title;
			todo.editDesc = todo.description || '';
			todo.editPriority = todo.priority;
			todo.editDue = todo.due_date || '';
			todo.editing = true;
		},

		cancelEdit() {
			getContext().todo.editing = false;
		},

		setEditTitle( event ) {
			getContext().todo.editTitle = event.target.value;
		},

		setEditDesc( event ) {
			getContext().todo.editDesc = event.target.value;
		},

		setEditPriority( event ) {
			getContext().todo.editPriority = event.target.value;
		},

		setEditDue( event ) {
			getContext().todo.editDue = event.target.value;
		},

		*saveEdit( event ) {
			event.preventDefault();
			const todo = getContext().todo;
			const title = ( todo.editTitle || '' ).trim();
			if ( ! title ) {
				return;
			}
			try {
				const updated = yield bptodoApi( `/todos/${ todo.id }`, 'PATCH', {
					title,
					description: todo.editDesc || '',
					priority: todo.editPriority,
					due_date: todo.editDue || '',
				} );
				todo.title = updated.title;
				todo.description = updated.description;
				todo.priority = updated.priority;
				todo.priority_label = updated.priority_label;
				todo.due_date = updated.due_date;
				todo.due_human = updated.due_human;
				todo.editing = false;
			} catch ( err ) {}
		},

		*quickAdd( event ) {
			event.preventDefault();
			const ctx = getContext();
			const title = ( ctx.draftTitle || '' ).trim();
			if ( ! title ) {
				return;
			}
			const base = ctx.scope === 'group' && ctx.groupId ? `/groups/${ ctx.groupId }/todos` : '/todos';
			try {
				yield bptodoApi( base, 'POST', { title } );
				ctx.draftTitle = '';
				ctx.page = 1;
				yield actions.reload();
			} catch ( err ) {}
		},

		// Full add form (the Add page).
		setFTitle( event ) {
			getContext().fTitle = event.target.value;
		},
		setFDesc( event ) {
			getContext().fDesc = event.target.value;
		},
		setFCat( event ) {
			getContext().fCat = event.target.value;
		},
		setFPriority( event ) {
			getContext().fPriority = event.target.value;
		},
		setFDue( event ) {
			getContext().fDue = event.target.value;
		},

		// Inline "+ New" category creator (honors allow_user_add_category).
		setNewCat( event ) {
			getContext().fNewCat = event.target.value;
		},

		toggleAddCat() {
			const ctx = getContext();
			ctx.addingCat = ! ctx.addingCat;
			ctx.fNewCat = '';
		},

		newCatKeydown( event ) {
			if ( 'Enter' === event.key ) {
				// Stop the keystroke from submitting the whole add form.
				event.preventDefault();
				actions.addCategory();
			}
		},

		*addCategory() {
			const ctx = getContext();
			const name = ( ctx.fNewCat || '' ).trim();
			if ( ! name ) {
				return;
			}
			try {
				const term = yield bptodoApi( '/categories', 'POST', { name } );
				if ( term && term.slug ) {
					// Append to the client-side option list.
					const exists = ( ctx.newCats || [] ).some( ( c ) => c.slug === term.slug );
					if ( ! exists ) {
						ctx.newCats = [ ...( ctx.newCats || [] ), { slug: term.slug, name: term.name } ];
					}
					// Let the new <option> render before selecting it, otherwise the
					// controlled <select> value is set before the node exists.
					yield new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
					ctx.fCat = term.slug;
				}
				ctx.fNewCat = '';
				ctx.addingCat = false;
				ctx.formError = '';
			} catch ( err ) {
				ctx.formError = state.i18n.error;
			}
		},

		// Toggle a group member in the assignee list (group add form).
		toggleAssignee( event ) {
			const ctx = getContext();
			const id = event.target.value;
			const list = Array.isArray( ctx.fAssignees ) ? ctx.fAssignees.slice() : [];
			const idx = list.indexOf( id );
			if ( event.target.checked && idx === -1 ) {
				list.push( id );
			} else if ( ! event.target.checked && idx !== -1 ) {
				list.splice( idx, 1 );
			}
			ctx.fAssignees = list;
		},

		*submitFull( event ) {
			event.preventDefault();
			const ctx = getContext();
			const title = ( ctx.fTitle || '' ).trim();
			if ( ! title ) {
				ctx.formError = state.i18n.titleRequired;
				return;
			}
			// Honor the "Require due date" setting (req_duedate).
			if ( ctx.requireDue && ! ( ctx.fDue || '' ).trim() ) {
				ctx.formError = state.i18n.dueRequired;
				return;
			}
			ctx.submitting = true;
			ctx.formError = '';
			const isGroup = ctx.scope === 'group' && ctx.groupId;
			const base = isGroup ? `/groups/${ ctx.groupId }/todos` : '/todos';
			const payload = {
				title,
				description: ctx.fDesc || '',
				category: ctx.fCat || '',
				priority: ctx.fPriority || 'normal',
				due_date: ctx.fDue || '',
			};
			if ( isGroup ) {
				// Empty selection means "everyone" (server treats [] / ['all'] alike).
				payload.assignees = ctx.fAssignees && ctx.fAssignees.length ? ctx.fAssignees : [ 'all' ];
			}
			try {
				yield bptodoApi( base, 'POST', payload );
				window.location.href = ctx.listUrl || window.location.href;
			} catch ( err ) {
				ctx.submitting = false;
				ctx.formError = state.i18n.error;
			}
		},

		*reload() {
			const ctx = getContext();
			ctx.isBusy = true;
			try {
				const params = new URLSearchParams( {
					status: ctx.status,
					priority: ctx.priority,
					category: ctx.category,
					search: ctx.search,
					orderby: ctx.orderby,
					order: ctx.order,
					per_page: ctx.perPage,
					page: ctx.page,
				} );
				let base = '/todos';
				if ( ctx.scope === 'group' && ctx.groupId ) {
					base = `/groups/${ ctx.groupId }/todos`;
				} else if ( ctx.userId && ctx.userId !== '0' ) {
					params.set( 'user_id', ctx.userId );
				}
				const data = yield bptodoApi( `${ base }?${ params.toString() }` );
				ctx.todos = data.items || [];
				ctx.total = data.total || 0;
				ctx.totalPages = data.total_pages || 1;
				ctx.page = data.page || 1;
				ctx.isFirstPage = ctx.page <= 1;
				ctx.isLastPage = ctx.page >= ctx.totalPages;
				ctx.singlePage = ctx.totalPages <= 1;
				yield actions.refreshStats();
			} finally {
				ctx.isBusy = false;
			}
		},

		*refreshStats() {
			const ctx = getContext();
			const params = new URLSearchParams();
			if ( ctx.scope === 'group' && ctx.groupId ) {
				params.set( 'group_id', ctx.groupId );
			} else if ( ctx.userId && ctx.userId !== '0' ) {
				params.set( 'user_id', ctx.userId );
			}
			const stats = yield bptodoApi( `/todos/stats?${ params.toString() }` );
			ctx.stats = stats;
		},
	},
} );

/**
 * Minimal REST helper bound to the plugin namespace + REST nonce.
 *
 * @param {string} path   Path under bptodo/v1 (leading slash).
 * @param {string} method HTTP method.
 * @param {Object} body   Optional JSON body.
 * @return {Promise<Object>} Parsed JSON.
 */
function bptodoApi( path, method = 'GET', body = null ) {
	return fetch( state.restRoot + path, {
		method,
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': state.nonce,
		},
		body: body ? JSON.stringify( body ) : undefined,
	} ).then( ( response ) => {
		if ( ! response.ok ) {
			throw new Error( 'bptodo request failed: ' + response.status );
		}
		return response.status === 204 ? {} : response.json();
	} );
}
