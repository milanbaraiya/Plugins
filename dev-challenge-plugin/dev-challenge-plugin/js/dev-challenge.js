jQuery(document).ready(function($) {
    $.ajax({
        url: devChallenge.ajax_url,
        method: 'POST',
        data: {
            action: 'dev_challenge_get_table',
            nonce: devChallenge.nonce,
        },
        success: function(response) {
            if (response.success) {
                $('#dev-challenge-table').html(response.data);
            } else {
                $('#dev-challenge-table').html('<p>Error loading data.</p>');
            }
        },
        error: function() {
            $('#dev-challenge-table').html('<p>Error loading data.</p>');
        }
    });
});