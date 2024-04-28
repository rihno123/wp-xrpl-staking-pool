jQuery(document).ready(function($) {
    $('#customButton').click(function() {
        $.ajax({
            url: ajax_params.ajax_url,
            type: 'POST',
            data: {
                action: 'send_tokens'
            },
            success: function(response) {
                alert('Success: ' + response);
            },
            error: function() {
                alert('Error sending tokens.');
            }
        });
    });
});