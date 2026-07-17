/**
 * Network-admin JS for Todo for BuddyPress & BuddyBoss.
 *
 * Loaded only on the network plugins screen (see
 * bptodo_enqueue_network_scripts in bp-user-todo-list.php) to drive the
 * "Network Deactivate" action. Uses the modern BPTodo.modal / BPTodo.toast
 * helpers (assets/js/bptodo-modern.js) instead of native confirm()/alert()
 * — skill Part 6 rules 10 + 12. Falls back gracefully if the helper is not
 * present on the screen.
 *
 * @since 3.6.0
 */
( function ( $ ) {
	'use strict';

	// Translated strings come from the `bptodo_network_ajax.i18n` array that
	// bptodo_enqueue_network_scripts() localizes onto this handle
	// (bp-user-todo-list.php). The literals below are English mirrors of those
	// __() values and are only used if the localize call is missing; every key
	// read here MUST exist in that PHP array or the fallback wins on all locales.
	function ni( key, fallback ) {
		var dict = window.bptodo_network_ajax && window.bptodo_network_ajax.i18n;
		return ( dict && dict[ key ] ) || fallback;
	}

	function notify( message, type ) {
		if ( window.BPTodo && window.BPTodo.toast && window.BPTodo.toast[ type ] ) {
			window.BPTodo.toast[ type ]( message );
		} else if ( window.bptodoToast ) {
			window.bptodoToast( message, type );
		} else {
			// Last-resort accessible fallback: a polite live region.
			var region = document.getElementById( 'bptodo-network-status' );
			if ( ! region ) {
				region = document.createElement( 'div' );
				region.id = 'bptodo-network-status';
				region.setAttribute( 'role', 'status' );
				region.setAttribute( 'aria-live', 'polite' );
				region.style.position = 'absolute';
				region.style.left = '-9999px';
				document.body.appendChild( region );
			}
			region.textContent = message;
		}
	}

	function runDeactivate() {
		$.ajax( {
			url: bptodo_network_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'bptodo_network_deactivate',
				nonce: bptodo_network_ajax.nonce,
			},
			success: function ( response ) {
				if ( response.success ) {
					notify( response.data.message, 'success' );
					window.location.reload();
				} else {
					notify( ( response.data && response.data.message ) || ni( 'genericError', 'Error' ), 'error' );
				}
			},
			error: function () {
				notify( ni( 'requestError', 'There was an error with the request.' ), 'error' );
			},
		} );
	}

	$( function () {
		$( '.bptodo-network-deactivate' ).on( 'click', function ( e ) {
			e.preventDefault();

			var title = ni( 'confirmTitle', 'Network Deactivate' );
			var message = ni( 'confirmMessage', 'Are you sure you want to deactivate this plugin across all sites?' );

			if ( window.BPTodo && window.BPTodo.modal && window.BPTodo.modal.confirm ) {
				window.BPTodo.modal.confirm( {
					title: title,
					message: message,
					type: 'danger',
				} ).then( function ( ok ) {
					if ( ok ) { runDeactivate(); }
				} );
			} else if ( window.bptodoConfirm ) {
				window.bptodoConfirm( { title: title, message: message, tone: 'danger' } ).then( function ( ok ) {
					if ( ok ) { runDeactivate(); }
				} );
			} else {
				// No modal helper on this screen — proceed (the action is
				// already gated server-side by nonce + manage_network_plugins).
				runDeactivate();
			}
		} );
	} );

} )( jQuery );
