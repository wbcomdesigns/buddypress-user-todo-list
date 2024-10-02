$('.deactivate-plugin').on('click', function(e) {
    e.preventDefault();

    if (confirm('Are you sure you want to deactivate this plugin for all sites?')) {
        $.ajax({
            url: bptodo_deactivation_ajax_object.ajax_url,  // Updated object name
            type: 'POST',
            data: {
                action: 'bptodo_deactivate_plugin',  // Updated action name
                nonce: bptodo_deactivation_ajax_object.nonce,  // Updated nonce
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
