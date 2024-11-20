jQuery(document).ready(function($) {
    function updateSyncStatus(status) {
        localStorage.setItem('syncStatus', status);
        $('#sync-status').text(' Sync status: ' + status);
    }

    $('#manual-sync').one('click', function(event) {
        event.preventDefault(); // Prevent default action
        updateSyncStatus('Syncing...');
        $.post(ajax_object.ajax_url, {action: 'manual_sync_action'}, function(response) {
            if (response.success) {
                updateSyncStatus('Sync complete!');
            } else {
                updateSyncStatus('Error - ' + response.data);
            }
        }).fail(function() {
            updateSyncStatus('Error - Request failed');
        });
    });

    // Periodically check the sync status
    setInterval(function() {
        const status = localStorage.getItem('syncStatus');
        if (status) {
            $('#sync-status').text(' Sync status: ' + status);
        }
    }, 5000); // Check every 5 seconds
});