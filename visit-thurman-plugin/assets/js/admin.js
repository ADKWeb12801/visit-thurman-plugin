jQuery(document).ready(function($) {
    'use strict';

    // Initialize WordPress color picker
    $('.color-picker').wpColorPicker();

    // Admin Tabs
    $('.vt-admin-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        const tab_id = $(this).attr('href');

        // Deactivate all tabs and content
        $('.vt-admin-tabs .nav-tab').removeClass('nav-tab-active');
        $('.vt-tab-content').hide();

        // Activate the clicked tab and its content
        $(this).addClass('nav-tab-active');
        $(tab_id).show();
    });

    // On page load, if a tab is specified in hash, show it.
    if (window.location.hash) {
        const hash = window.location.hash;
        if ($(hash).length) {
            $('.vt-admin-tabs a[href="' + hash + '"]').trigger('click');
        }
    }
});
