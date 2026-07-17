/**
 * BuddyPress Todo - Modern UX Module
 *
 * Provides toast notifications, loading states, modal management,
 * and AJAX helpers for a modern, no-reload user experience.
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 */

(function() {
	'use strict';

	// Ensure we have a global namespace
	window.BPTodo = window.BPTodo || {};

	/* ==========================================================================
	   I18N
	   ========================================================================== */

	/**
	 * Read a translated string from the dictionary PHP localizes onto the
	 * `bptodo-modern-js` handle (bptodo_localize_modern_i18n() in
	 * includes/bptodo-functions.php).
	 *
	 * The literal passed as `fallback` is a mirror of the PHP __() value and is
	 * only used when this file is loaded without its localize call. Every key
	 * read here MUST exist in that PHP array, or the English fallback silently
	 * wins on every locale.
	 *
	 * @param {string} key - Dictionary key.
	 * @param {string} fallback - English mirror of the PHP __() value.
	 * @returns {string}
	 */
	function mi(key, fallback) {
		var dict = window.bptodoModernI18n;
		return (dict && dict[key]) || fallback;
	}

	/* ==========================================================================
	   SVG ICONS (Hardcoded, safe for innerHTML)
	   ========================================================================== */

	const ICONS = {
		success: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
		error: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
		warning: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
		info: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
		close: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
		check: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
		trash: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
		edit: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
		calendar: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
		empty: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>'
	};

	BPTodo.icons = ICONS;

	/**
	 * Safely insert SVG icon into an element
	 * SVG icons are hardcoded constants, not user input
	 *
	 * @param {HTMLElement} element - Target element
	 * @param {string} iconName - Icon name from ICONS object
	 */
	function insertIcon(element, iconName) {
		if (ICONS[iconName]) {
			// Icons are hardcoded SVG strings, safe to use
			element.innerHTML = ICONS[iconName]; // eslint-disable-line no-unsanitized/property
		}
	}

	/* ==========================================================================
	   TOAST NOTIFICATION SYSTEM
	   ========================================================================== */

	const Toast = {
		container: null,
		queue: [],
		maxVisible: 5,
		defaultDuration: 5000,

		/**
		 * Initialize toast container
		 */
		init: function() {
			if (this.container) return;

			this.container = document.createElement('div');
			this.container.className = 'bptodo-toast-container';
			this.container.setAttribute('role', 'alert');
			this.container.setAttribute('aria-live', 'polite');
			this.container.setAttribute('aria-atomic', 'true');
			document.body.appendChild(this.container);
		},

		/**
		 * Show a toast notification
		 *
		 * @param {Object} options - Toast options
		 * @param {string} options.type - 'success', 'error', 'warning', 'info'
		 * @param {string} options.title - Toast title (optional)
		 * @param {string} options.message - Toast message
		 * @param {number} options.duration - Duration in ms (0 for no auto-dismiss)
		 * @returns {HTMLElement} The toast element
		 */
		show: function(options) {
			this.init();

			const defaults = {
				type: 'info',
				title: '',
				message: '',
				duration: this.defaultDuration
			};

			const settings = Object.assign({}, defaults, options);

			// Create toast element
			const toast = document.createElement('div');
			toast.className = 'bptodo-toast bptodo-toast--' + settings.type;

			// Icon - using safe insertIcon function
			const icon = document.createElement('span');
			icon.className = 'bptodo-toast__icon';
			insertIcon(icon, settings.type);
			toast.appendChild(icon);

			// Content
			const content = document.createElement('div');
			content.className = 'bptodo-toast__content';

			if (settings.title) {
				const title = document.createElement('p');
				title.className = 'bptodo-toast__title';
				title.textContent = settings.title; // Safe: uses textContent
				content.appendChild(title);
			}

			const message = document.createElement('p');
			message.className = 'bptodo-toast__message';
			message.textContent = settings.message; // Safe: uses textContent
			content.appendChild(message);

			toast.appendChild(content);

			// Close button
			const closeBtn = document.createElement('button');
			closeBtn.className = 'bptodo-toast__close';
			closeBtn.setAttribute('type', 'button');
			closeBtn.setAttribute('aria-label', mi('closeNotification', 'Close notification'));
			insertIcon(closeBtn, 'close');
			closeBtn.addEventListener('click', () => this.dismiss(toast));
			toast.appendChild(closeBtn);

			// Add to container
			this.container.appendChild(toast);

			// Auto-dismiss
			if (settings.duration > 0) {
				toast.dismissTimeout = setTimeout(() => {
					this.dismiss(toast);
				}, settings.duration);
			}

			// Manage queue
			this.queue.push(toast);
			if (this.queue.length > this.maxVisible) {
				this.dismiss(this.queue[0]);
			}

			return toast;
		},

		/**
		 * Dismiss a toast
		 *
		 * @param {HTMLElement} toast - The toast element to dismiss
		 */
		dismiss: function(toast) {
			if (!toast || !toast.parentNode) return;

			// Clear timeout
			if (toast.dismissTimeout) {
				clearTimeout(toast.dismissTimeout);
			}

			// Remove from queue
			const index = this.queue.indexOf(toast);
			if (index > -1) {
				this.queue.splice(index, 1);
			}

			// Animate out
			toast.classList.add('bptodo-toast--exiting');
			setTimeout(() => {
				if (toast.parentNode) {
					toast.parentNode.removeChild(toast);
				}
			}, 200);
		},

		/**
		 * Convenience methods
		 */
		success: function(message, title) {
			return this.show({ type: 'success', message: message, title: title });
		},

		error: function(message, title) {
			return this.show({ type: 'error', message: message, title: title, duration: 8000 });
		},

		warning: function(message, title) {
			return this.show({ type: 'warning', message: message, title: title });
		},

		info: function(message, title) {
			return this.show({ type: 'info', message: message, title: title });
		}
	};

	BPTodo.toast = Toast;

	/* ==========================================================================
	   LOADING STATE HELPERS
	   ========================================================================== */

	const Loading = {
		/**
		 * Show loading state on an element
		 *
		 * @param {HTMLElement|string} element - Element or selector
		 * @param {Object} options - Options
		 */
		show: function(element, options) {
			const el = typeof element === 'string' ? document.querySelector(element) : element;
			if (!el) return;

			const defaults = {
				overlay: true,
				spinner: true,
				text: ''
			};

			const settings = Object.assign({}, defaults, options);

			// Store original state
			el.dataset.bptodoLoading = 'true';

			if (settings.overlay) {
				// Create overlay
				const overlay = document.createElement('div');
				overlay.className = 'bptodo-loading-overlay';
				overlay.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.8);z-index:10;';

				if (settings.spinner) {
					const spinner = document.createElement('div');
					spinner.className = 'bptodo-spinner';
					overlay.appendChild(spinner);
				}

				if (settings.text) {
					const text = document.createElement('span');
					text.style.marginLeft = '8px';
					text.textContent = settings.text; // Safe: uses textContent
					overlay.appendChild(text);
				}

				// Ensure element has position
				const position = window.getComputedStyle(el).position;
				if (position === 'static') {
					el.style.position = 'relative';
					el.dataset.bptodoAddedPosition = 'true';
				}

				el.appendChild(overlay);
				el.dataset.bptodoOverlay = 'true';
			}
		},

		/**
		 * Hide loading state on an element
		 *
		 * @param {HTMLElement|string} element - Element or selector
		 */
		hide: function(element) {
			const el = typeof element === 'string' ? document.querySelector(element) : element;
			if (!el) return;

			delete el.dataset.bptodoLoading;

			// Remove overlay
			if (el.dataset.bptodoOverlay) {
				const overlay = el.querySelector('.bptodo-loading-overlay');
				if (overlay) {
					overlay.remove();
				}
				delete el.dataset.bptodoOverlay;
			}

			// Remove added position
			if (el.dataset.bptodoAddedPosition) {
				el.style.position = '';
				delete el.dataset.bptodoAddedPosition;
			}
		},

		/**
		 * Show loading state on a button
		 *
		 * @param {HTMLElement|string} button - Button element or selector
		 */
		button: function(button) {
			const btn = typeof button === 'string' ? document.querySelector(button) : button;
			if (!btn) return;

			btn.dataset.bptodoOriginalText = btn.textContent;
			btn.classList.add('bptodo-btn--loading');
			btn.disabled = true;
		},

		/**
		 * Remove loading state from a button
		 *
		 * @param {HTMLElement|string} button - Button element or selector
		 */
		buttonDone: function(button) {
			const btn = typeof button === 'string' ? document.querySelector(button) : button;
			if (!btn) return;

			btn.classList.remove('bptodo-btn--loading');
			btn.disabled = false;

			if (btn.dataset.bptodoOriginalText) {
				btn.textContent = btn.dataset.bptodoOriginalText; // Safe: uses textContent
				delete btn.dataset.bptodoOriginalText;
			}
		}
	};

	BPTodo.loading = Loading;

	/* ==========================================================================
	   AJAX HELPER
	   ========================================================================== */

	const Ajax = {
		/**
		 * Get the AJAX URL
		 */
		getUrl: function() {
			// Try to get from WordPress
			if (typeof ajaxurl !== 'undefined') {
				return ajaxurl;
			}
			if (typeof todo_ajax_object !== 'undefined' && todo_ajax_object.ajax_url) {
				return todo_ajax_object.ajax_url;
			}
			// Fallback
			return '/wp-admin/admin-ajax.php';
		},

		/**
		 * Get the nonce
		 */
		getNonce: function() {
			if (typeof todo_ajax_object !== 'undefined' && todo_ajax_object.ajax_nonce) {
				return todo_ajax_object.ajax_nonce;
			}
			return '';
		},

		/**
		 * Make an AJAX POST request
		 *
		 * @param {string} action - WordPress AJAX action name
		 * @param {Object} data - Additional data to send
		 * @param {Object} options - Options
		 * @returns {Promise}
		 */
		post: function(action, data, options) {
			const defaults = {
				showLoading: null,  // Element to show loading on
				loadingText: '',
				successMessage: '', // Toast message on success
				errorMessage: mi('ajaxError', 'An error occurred. Please try again.')
			};

			const settings = Object.assign({}, defaults, options);

			// Show loading if specified
			if (settings.showLoading) {
				if (settings.showLoading.tagName === 'BUTTON') {
					Loading.button(settings.showLoading);
				} else {
					Loading.show(settings.showLoading, { text: settings.loadingText });
				}
			}

			// Prepare form data
			const formData = new FormData();
			formData.append('action', action);
			formData.append('ajax_nonce', this.getNonce());

			// Add additional data
			if (data) {
				if (data instanceof FormData) {
					for (const [key, value] of data.entries()) {
						formData.append(key, value);
					}
				} else {
					for (const key in data) {
						if (Object.prototype.hasOwnProperty.call(data, key)) {
							formData.append(key, data[key]);
						}
					}
				}
			}

			return fetch(this.getUrl(), {
				method: 'POST',
				credentials: 'same-origin',
				body: formData
			})
			.then(function(response) {
				if (!response.ok) {
					throw new Error('Network response was not ok');
				}
				return response.text();
			})
			.then(function(text) {
				// Try to parse as JSON
				try {
					return JSON.parse(text);
				} catch (e) {
					// Return as text if not JSON
					return { success: true, data: text };
				}
			})
			.then(function(response) {
				// Hide loading
				if (settings.showLoading) {
					if (settings.showLoading.tagName === 'BUTTON') {
						Loading.buttonDone(settings.showLoading);
					} else {
						Loading.hide(settings.showLoading);
					}
				}

				// Show success toast
				if (settings.successMessage && response.success !== false) {
					Toast.success(settings.successMessage);
				}

				return response;
			})
			.catch(function(error) {
				// Hide loading
				if (settings.showLoading) {
					if (settings.showLoading.tagName === 'BUTTON') {
						Loading.buttonDone(settings.showLoading);
					} else {
						Loading.hide(settings.showLoading);
					}
				}

				// Show error toast
				Toast.error(settings.errorMessage);

				throw error;
			});
		}
	};

	BPTodo.ajax = Ajax;

	/* ==========================================================================
	   MODAL SYSTEM
	   ========================================================================== */

	const Modal = {
		activeModal: null,
		backdrop: null,

		/**
		 * Initialize modal backdrop
		 */
		init: function() {
			if (this.backdrop) return;

			this.backdrop = document.createElement('div');
			this.backdrop.className = 'bptodo-modal-backdrop';
			this.backdrop.addEventListener('click', () => this.close());
			document.body.appendChild(this.backdrop);

			// Close on escape
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape' && this.activeModal) {
					this.close();
				}
			});
		},

		/**
		 * Open a modal
		 *
		 * @param {Object} options - Modal options
		 * @param {string} options.title - Modal title
		 * @param {string|HTMLElement} options.content - Modal content (HTMLElement for safety)
		 * @param {Array} options.buttons - Footer buttons
		 * @returns {HTMLElement} The modal element
		 */
		open: function(options) {
			this.init();

			// Close any existing modal
			if (this.activeModal) {
				this.close(true);
			}

			const defaults = {
				title: '',
				content: '',
				buttons: [],
				width: '500px',
				onClose: null
			};

			const settings = Object.assign({}, defaults, options);

			// Create modal.
			// The modal is appended to <body>, outside any .bptodo-wrapper on the
			// page, so it must carry the wrapper class itself - otherwise the
			// .bptodo-wrapper-scoped button/confirm styles never apply and the
			// footer buttons (e.g. Cancel/Delete) render unstyled.
			const modal = document.createElement('div');
			modal.className = 'bptodo-wrapper bptodo-modal';
			modal.style.setProperty('--bptodo-modal-width', settings.width);
			modal.setAttribute('role', 'dialog');
			modal.setAttribute('aria-modal', 'true');
			if (settings.title) {
				modal.setAttribute('aria-labelledby', 'bptodo-modal-title');
			}

			// Header
			const header = document.createElement('div');
			header.className = 'bptodo-modal__header';

			const title = document.createElement('h3');
			title.className = 'bptodo-modal__title';
			title.id = 'bptodo-modal-title';
			title.textContent = settings.title; // Safe: uses textContent
			header.appendChild(title);

			const closeBtn = document.createElement('button');
			closeBtn.className = 'bptodo-modal__close';
			closeBtn.setAttribute('type', 'button');
			closeBtn.setAttribute('aria-label', mi('closeModal', 'Close modal'));
			insertIcon(closeBtn, 'close');
			closeBtn.addEventListener('click', () => this.close());
			header.appendChild(closeBtn);

			modal.appendChild(header);

			// Body
			const body = document.createElement('div');
			body.className = 'bptodo-modal__body';

			// Content handling: prefer HTMLElement for safety
			if (typeof settings.content === 'string') {
				// For string content, treat as plain text
				body.textContent = settings.content;
			} else if (settings.content instanceof HTMLElement) {
				// HTMLElement is safe - created by our code
				body.appendChild(settings.content);
			}

			modal.appendChild(body);

			// Footer (if buttons provided)
			if (settings.buttons.length > 0) {
				const footer = document.createElement('div');
				footer.className = 'bptodo-modal__footer';

				settings.buttons.forEach(function(btnConfig) {
					const btn = document.createElement('button');
					btn.className = 'bptodo-btn bptodo-btn--' + (btnConfig.type || 'secondary');
					btn.textContent = btnConfig.text; // Safe: uses textContent

					if (btnConfig.onClick) {
						btn.addEventListener('click', btnConfig.onClick);
					}

					footer.appendChild(btn);
				});

				modal.appendChild(footer);
			}

			// Store settings for later
			modal._bptodoSettings = settings;

			// Add to DOM
			document.body.appendChild(modal);
			this.activeModal = modal;

			// Show with animation
			requestAnimationFrame(() => {
				this.backdrop.classList.add('bptodo-modal-backdrop--visible');
				modal.classList.add('bptodo-modal--visible');
			});

			// Prevent body scroll
			document.body.style.overflow = 'hidden';

			// Focus first focusable element
			const focusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
			if (focusable) {
				focusable.focus();
			}

			return modal;
		},

		/**
		 * Close the active modal
		 *
		 * @param {boolean} immediate - Skip animation
		 */
		close: function(immediate) {
			if (!this.activeModal) return;

			const modal = this.activeModal;
			const settings = modal._bptodoSettings || {};

			// Call onClose callback
			if (settings.onClose) {
				settings.onClose();
			}

			if (immediate) {
				modal.remove();
				this.backdrop.classList.remove('bptodo-modal-backdrop--visible');
				document.body.style.overflow = '';
				this.activeModal = null;
				return;
			}

			// Animate out
			modal.classList.remove('bptodo-modal--visible');
			this.backdrop.classList.remove('bptodo-modal-backdrop--visible');

			setTimeout(() => {
				modal.remove();
				document.body.style.overflow = '';
				this.activeModal = null;
			}, 200);
		},

		/**
		 * Show a confirmation dialog
		 *
		 * @param {Object} options - Confirm options
		 * @returns {Promise} Resolves with true if confirmed, false if cancelled
		 */
		confirm: function(options) {
			const defaults = {
				title: mi('confirmTitle', 'Confirm'),
				message: mi('confirmMessage', 'Are you sure?'),
				confirmText: mi('confirmOk', 'Confirm'),
				cancelText: mi('confirmCancel', 'Cancel'),
				type: 'danger' // 'danger' or 'primary'
			};

			const settings = Object.assign({}, defaults, options);

			return new Promise((resolve) => {
				// Guard: the Confirm/Cancel buttons close the modal, which fires
				// onClose. Without this flag, onClose's resolve(false) settles the
				// promise before the button's own resolve() runs, so Confirm always
				// returned false (dead delete/destructive actions).
				let settled = false;
				const settle = function(result) {
					if (settled) return;
					settled = true;
					resolve(result);
				};

				// Build content using safe DOM methods
				const content = document.createElement('div');
				content.className = 'bptodo-confirm';

				const icon = document.createElement('div');
				icon.className = 'bptodo-confirm__icon';
				insertIcon(icon, settings.type === 'danger' ? 'warning' : 'info');
				content.appendChild(icon);

				const title = document.createElement('h4');
				title.className = 'bptodo-confirm__title';
				title.textContent = settings.title; // Safe: uses textContent
				content.appendChild(title);

				const message = document.createElement('p');
				message.className = 'bptodo-confirm__message';
				message.textContent = settings.message; // Safe: uses textContent
				content.appendChild(message);

				this.open({
					title: '',
					content: content,
					width: '400px',
					buttons: [
						{
							text: settings.cancelText,
							type: 'secondary',
							onClick: () => {
								settle(false);
								this.close();
							}
						},
						{
							text: settings.confirmText,
							type: settings.type,
							onClick: () => {
								settle(true);
								this.close();
							}
						}
					],
					onClose: () => settle(false)
				});
			});
		}
	};

	BPTodo.modal = Modal;

	/* ==========================================================================
	   DOM HELPERS
	   ========================================================================== */

	const DOM = {
		/**
		 * Find closest ancestor matching selector
		 *
		 * @param {HTMLElement} element - Starting element
		 * @param {string} selector - CSS selector
		 * @returns {HTMLElement|null}
		 */
		closest: function(element, selector) {
			return element.closest ? element.closest(selector) : null;
		},

		/**
		 * Create an element with attributes and content
		 *
		 * @param {string} tag - Tag name
		 * @param {Object} attrs - Attributes
		 * @param {string|HTMLElement|Array} content - Content (string = textContent)
		 * @returns {HTMLElement}
		 */
		create: function(tag, attrs, content) {
			const el = document.createElement(tag);

			if (attrs) {
				for (const key in attrs) {
					if (key === 'className') {
						el.className = attrs[key];
					} else if (key === 'dataset') {
						for (const dataKey in attrs[key]) {
							el.dataset[dataKey] = attrs[key][dataKey];
						}
					} else if (Object.prototype.hasOwnProperty.call(attrs, key)) {
						el.setAttribute(key, attrs[key]);
					}
				}
			}

			if (content) {
				if (typeof content === 'string') {
					el.textContent = content; // Safe: uses textContent
				} else if (Array.isArray(content)) {
					content.forEach(function(child) {
						if (typeof child === 'string') {
							el.appendChild(document.createTextNode(child));
						} else if (child instanceof HTMLElement) {
							el.appendChild(child);
						}
					});
				} else if (content instanceof HTMLElement) {
					el.appendChild(content);
				}
			}

			return el;
		},

		/**
		 * Remove an element with animation
		 *
		 * @param {HTMLElement} element - Element to remove
		 */
		removeWithAnimation: function(element) {
			element.style.animation = 'bptodo-fade-out 0.2s ease forwards';
			setTimeout(function() {
				if (element.parentNode) {
					element.parentNode.removeChild(element);
				}
			}, 200);
		},

		/**
		 * Update a todo card in the DOM by replacing with new element
		 *
		 * @param {string|number} todoId - Todo ID
		 * @param {HTMLElement} newCard - New card element
		 */
		updateTodoCard: function(todoId, newCard) {
			const card = document.querySelector('[data-todo-id="' + todoId + '"]');
			if (!card || !(newCard instanceof HTMLElement)) return;

			const parent = card.parentNode;

			// Add animation
			newCard.style.animation = 'bptodo-scale-in 0.2s ease';
			parent.replaceChild(newCard, card);
		},

		/**
		 * Remove a todo card from the DOM
		 *
		 * @param {string|number} todoId - Todo ID
		 */
		removeTodoCard: function(todoId) {
			const card = document.querySelector('[data-todo-id="' + todoId + '"]');
			if (!card) return;

			this.removeWithAnimation(card);
		}
	};

	BPTodo.dom = DOM;

	/* ==========================================================================
	   UTILITY FUNCTIONS
	   ========================================================================== */

	// NOTE: Utils.formatDate() / Utils.relativeTime() were removed in 4.0.0. They
	// were dead code (no caller in this plugin, never documented) whose output was
	// built by concatenating English fragments ('2 days ago', 'just now'), which is
	// untranslatable — the pluralisation cannot be expressed through a localize
	// dictionary. Relative due dates that reach the UI are resolved server-side
	// instead (Bptodo_Todo::due_human), which uses _n() + __() correctly.
	const Utils = {
		/**
		 * Debounce a function
		 *
		 * @param {Function} func - Function to debounce
		 * @param {number} wait - Wait time in ms
		 * @returns {Function}
		 */
		debounce: function(func, wait) {
			let timeout;
			return function() {
				const context = this;
				const args = arguments;
				clearTimeout(timeout);
				timeout = setTimeout(function() {
					func.apply(context, args);
				}, wait);
			};
		},

		/**
		 * Serialize form data to object
		 *
		 * @param {HTMLFormElement} form - Form element
		 * @returns {Object}
		 */
		serializeForm: function(form) {
			const data = {};
			const formData = new FormData(form);

			for (const [key, value] of formData.entries()) {
				if (Object.prototype.hasOwnProperty.call(data, key)) {
					if (!Array.isArray(data[key])) {
						data[key] = [data[key]];
					}
					data[key].push(value);
				} else {
					data[key] = value;
				}
			}

			return data;
		}
	};

	BPTodo.utils = Utils;

	/* ==========================================================================
	   INITIALIZATION
	   ========================================================================== */

	/**
	 * Initialize when DOM is ready
	 */
	document.addEventListener('DOMContentLoaded', function() {
		// Pre-create toast container
		Toast.init();

		// Initialize modal backdrop
		Modal.init();

		// Expose to jQuery if available (for backwards compatibility)
		if (typeof jQuery !== 'undefined') {
			jQuery.fn.bptodoLoading = function(show) {
				return this.each(function() {
					if (show === false) {
						Loading.hide(this);
					} else {
						Loading.show(this, typeof show === 'object' ? show : {});
					}
				});
			};
		}
	});

})();
