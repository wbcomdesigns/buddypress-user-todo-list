/**
 * Todo for BuddyPress & BuddyBoss: card-panel admin JS.
 *
 * Skill Part 6 rules 10 + 12: no browser alert()/confirm() anywhere.
 * Exposes window.bptodoToast() and window.bptodoConfirm() so this file
 * and any future extension can use the same feedback surface.
 *
 * @since 3.6.0
 */
( function ( $ ) {
	'use strict';

	var i18n = ( window.bptodoAdmin && window.bptodoAdmin.i18n ) || {};

	/* ─── Toast ─────────────────────────────────────────────── */

	function getToastHost() {
		var host = document.querySelector( '.bptodo-toast-host' );
		if ( ! host ) {
			host = document.createElement( 'div' );
			host.className = 'bptodo-toast-host';
			document.body.appendChild( host );
		}
		return host;
	}

	function toast( message, tone ) {
		tone = tone || 'info';
		var host = getToastHost();
		var el   = document.createElement( 'div' );
		el.className   = 'bptodo-toast bptodo-toast--' + tone;
		el.setAttribute( 'role', 'status' );
		el.textContent = String( message );
		host.appendChild( el );

		requestAnimationFrame( function () {
			el.classList.add( 'bptodo-toast--visible' );
		} );

		window.setTimeout( function () {
			el.classList.remove( 'bptodo-toast--visible' );
			window.setTimeout( function () {
				if ( el.parentNode ) {
					el.parentNode.removeChild( el );
				}
			}, 250 );
		}, 3600 );
	}

	window.bptodoToast = toast;

	/* ─── Confirm modal (returns a Promise) ──────────────────── */

	function confirmModal( opts ) {
		opts = opts || {};
		return new Promise( function ( resolve ) {
			var backdrop = document.createElement( 'div' );
			backdrop.className = 'bptodo-confirm-backdrop';

			var card = document.createElement( 'div' );
			card.className = 'bptodo-confirm';
			card.setAttribute( 'role', 'dialog' );
			card.setAttribute( 'aria-modal', 'true' );

			var title = document.createElement( 'h2' );
			title.className = 'bptodo-confirm__title';
			title.textContent = opts.title || '';
			if ( opts.title ) { card.appendChild( title ); }

			var desc = document.createElement( 'p' );
			desc.className = 'bptodo-confirm__desc';
			desc.textContent = opts.message || i18n.confirmDanger || '';
			if ( opts.message || i18n.confirmDanger ) { card.appendChild( desc ); }

			var actions = document.createElement( 'div' );
			actions.className = 'bptodo-confirm__actions';

			var cancelBtn = document.createElement( 'button' );
			cancelBtn.type = 'button';
			cancelBtn.className = 'bptodo-btn bptodo-btn-secondary';
			cancelBtn.textContent = opts.cancelLabel || i18n.confirmCancel || 'Cancel';

			var confirmBtn = document.createElement( 'button' );
			confirmBtn.type = 'button';
			confirmBtn.className = 'bptodo-btn ' + ( 'danger' === opts.tone ? 'bptodo-btn-danger' : 'bptodo-btn-primary' );
			confirmBtn.textContent = opts.confirmLabel || i18n.confirmContinue || 'Continue';

			actions.appendChild( cancelBtn );
			actions.appendChild( confirmBtn );
			card.appendChild( actions );
			backdrop.appendChild( card );
			document.body.appendChild( backdrop );

			function cleanup( result ) {
				document.removeEventListener( 'keydown', onKey );
				if ( backdrop.parentNode ) {
					backdrop.parentNode.removeChild( backdrop );
				}
				resolve( result );
			}

			function onKey( e ) {
				if ( 'Escape' === e.key ) { cleanup( false ); }
				if ( 'Enter' === e.key ) { cleanup( true ); }
			}

			cancelBtn.addEventListener( 'click', function () { cleanup( false ); } );
			confirmBtn.addEventListener( 'click', function () { cleanup( true ); } );
			backdrop.addEventListener( 'click', function ( e ) {
				if ( e.target === backdrop ) { cleanup( false ); }
			} );
			document.addEventListener( 'keydown', onKey );
			confirmBtn.focus();
		} );
	}

	window.bptodoConfirm = confirmModal;

	/* ─── Role multiselect (General tab) ─────────────────────── */

	$( function () {
		var $roles = $( '#bptodo_user_roles' );
		if ( $roles.length && $.fn.select2 ) {
			$roles.select2( {
				placeholder: ( i18n.rolePlaceholder || $roles.data( 'placeholder' ) || '' ),
				width: 'resolve',
				allowClear: true,
			} );
		}

		// Intercept destructive controls that opt in via data-bptodo-confirm.
		$( document ).on( 'click', '[data-bptodo-confirm]', function ( e ) {
			var $el = $( this );
			if ( $el.attr( 'data-action' ) ) { return; } // owned by a specific handler
			if ( $el.data( 'bptodo-confirm-ok' ) ) { return; }
			e.preventDefault();
			var message = $el.data( 'bptodo-confirm' ) || i18n.confirmDanger;
			var tone    = $el.data( 'bptodo-confirm-tone' ) || 'danger';
			confirmModal( { message: message, tone: tone } ).then( function ( ok ) {
				if ( ! ok ) { return; }
				$el.data( 'bptodo-confirm-ok', true );
				if ( $el.is( 'a' ) ) {
					window.location.href = $el.attr( 'href' );
				} else if ( $el.is( 'button' ) || $el.is( 'input' ) ) {
					var form = $el.closest( 'form' ).get( 0 );
					if ( ! form ) { return; }
					var btnName  = $el.attr( 'name' ) || '';
					var btnValue = $el.attr( 'value' ) || '1';
					if ( btnName ) {
						var hidden   = document.createElement( 'input' );
						hidden.type  = 'hidden';
						hidden.name  = btnName;
						hidden.value = btnValue;
						form.appendChild( hidden );
					}
					form.submit();
				}
			} );
		} );
	} );

} )( jQuery );
