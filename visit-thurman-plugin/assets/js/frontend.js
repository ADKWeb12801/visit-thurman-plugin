jQuery(document).ready(function($) {
    'use strict';

    /**
     * Handles toggling a bookmark via AJAX.
     */
    function handleBookmarkToggle() {
        $(document).on('click', '.vt-bookmark-btn', function(e) {
            e.preventDefault();
            const button = $(this);
            const postId = button.data('post-id');
            
            if (button.prop('disabled')) {
                return;
            }

            $.ajax({
                url: vt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vt_toggle_bookmark',
                    post_id: postId,
                    nonce: vt_ajax.nonce
                },
                beforeSend: function() {
                    button.prop('disabled', true);
                    button.css('opacity', '0.5');
                },
                success: function(response) {
                    if (response.success) {
                        button.toggleClass('is-bookmarked');
                        const newTitle = response.data.status === 'added' ? 'Remove bookmark' : 'Add bookmark';
                        button.attr('title', newTitle);
                    } else {
                        alert(response.data.message || vt_ajax.i18n.error);
                    }
                },
                error: function() {
                    alert(vt_ajax.i18n.error);
                },
                complete: function() {
                    button.prop('disabled', false);
                    button.css('opacity', '1');
                }
            });
        });
    }

    /**
     * Handles the tab navigation on the user profile page.
     */
    function handleProfileTabs() {
        const profileTabs = $('.vt-profile-tabs a');
        if (!profileTabs.length) return;

        profileTabs.on('click', function(e) {
            e.preventDefault();
            const tabId = $(this).attr('href').substring(1); // remove '#'
            
            // Update active class on tabs
            profileTabs.removeClass('is-active');
            $(this).addClass('is-active');

            // Show/hide content panels
            $('.vt-profile-tab-content').removeClass('is-active');
            $('#' + tabId).addClass('is-active');
            
            // Update URL with the new tab for bookmarking/sharing
            if (history.pushState) {
                const newUrl = window.location.pathname + '?tab=' + tabId;
                window.history.pushState({path: newUrl}, '', newUrl);
            }
        });

        // On page load, check for a tab in the URL and activate it.
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        if (activeTab) {
            profileTabs.filter('[href="#' + activeTab + '"]').trigger('click');
        } else {
            // Activate the first tab by default
            profileTabs.first().trigger('click');
        }
    }

    // Initialize all frontend scripts
    handleBookmarkToggle();
    handleProfileTabs();

    /**
     * AJAX listing filters
     */
    $(document).on('submit', '.vt-listing-filters', function(e){
        e.preventDefault();
        const form = $(this);
        const target = $(form.data('target'));
        const data = form.serializeArray();
        data.push({name:'action', value:'vt_fetch_listings'});
        data.push({name:'nonce', value:vt_ajax.nonce});
        $.post(vt_ajax.ajax_url, data, function(response){
            if(response.success){
                target.html(response.data.html);
            }
        });
    });

});
