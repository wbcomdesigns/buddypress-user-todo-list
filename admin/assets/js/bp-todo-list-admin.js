(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	 
	 $(document).ready(function($) {
		 $('#bptodo_user_roles').selectize({
	 		placeholder		: $( '#bptodo_user_roles').data( 'placeholder' ),
	 		plugins			: ['remove_button']
	 	});

		 $('.custom-deactivate-plugin').on('click', function(e) {
			e.preventDefault();
   
			if (confirm('Are you sure you want to deactivate this plugin for all site?')) {
				$.ajax({
					url: deactivation_ajax_object.ajax_url,
					type: 'POST',
					data: {
						action: 'custom_deactivate_plugin',
						nonce: deactivation_ajax_object.nonce,
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							location.reload(); // Reload the page to see the changes
						} else {
							alert('Error: ' + response.data.message);
						}
					},
					error: function() {
						alert('There was an error with the AJAX request.');
					}
				});
			}
		});
	 });

})( jQuery );
