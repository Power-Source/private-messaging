jQuery(function($) {
    'use strict';

    $(document).on('click', '.mm-community-gallery-toggle', function() {
        var $picker = $(this).siblings('.mm-community-gallery-picker');
        var willOpen = $picker.prop('hidden');
        $picker.prop('hidden', !willOpen);
        if (willOpen) {
            $picker.find('select').trigger('focus');
        }
    });

    $(document).on('click', '.mm-community-gallery-add', function() {
        var $button = $(this);
        var $actions = $button.closest('.mm-community-media-actions');
        var galleryId = parseInt($actions.find('.mm-community-gallery-select').val(), 10);
        var $status = $actions.find('.mm-community-gallery-status');

        if (!galleryId || $button.prop('disabled')) {
            return;
        }

        $button.prop('disabled', true);
        $status.removeClass('is-error').text('...');

        $.post(mm_community_media.ajaxurl, {
            action: 'mm_add_attachment_to_community_gallery',
            nonce: $button.data('nonce'),
            conversation_id: $button.data('conversation-id'),
            filename: $button.data('filename'),
            gallery_id: galleryId
        }).done(function(response) {
            var message = response && response.data && response.data.message
                ? response.data.message
                : '';
            $status.text(message).toggleClass('is-error', !response || !response.success);
            if (response && response.success) {
                $actions.find('.mm-community-gallery-picker').prop('hidden', true);
            }
        }).fail(function(xhr) {
            var response = xhr.responseJSON;
            var message = response && response.data && response.data.message
                ? response.data.message
                : mm_community_media.uploadError;
            $status.text(message).addClass('is-error');
        }).always(function() {
            $button.prop('disabled', false);
        });
    });
});
