jQuery(document).on('click', '.wwn-remove-waitlist', function (e) {
    e.preventDefault();
    var row = jQuery(this).closest('tr');
    var id = jQuery(this).data('id');

    if (!confirm('Remove this item from your waitlist?')) return;

    jQuery.post(wwn_ajax.ajax_url, {
        action: 'wwn_remove_waitlist',
        id: id
    }, function (response) {
        if (response.success) {
            row.fadeOut(300, function () {
                jQuery(this).remove();
            });
        } else {
            alert('Failed to remove item.');
        }
    });
});
