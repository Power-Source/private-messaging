jQuery(function ($) {
    var file_port;
    var igu_uploader;

    function loadInlineForm(trigger, url, isEdit) {
        var wrapper = trigger.closest('.panel');
        var inline = wrapper.find('.igu-inline-form');
        inline.show().addClass('igu-inline-form--loading').html('<div class="igu-inline-loading">Loading...</div>');

        $.get(url, function (html) {
            inline.removeClass('igu-inline-form--loading').html(html);
            inline.find('.igu-close-uploader').off('click').on('click', function () {
                inline.slideUp().empty();
            });
            $('html, body').animate({ scrollTop: inline.offset().top - 60 }, 200);
        }).fail(function () {
            inline.removeClass('igu-inline-form--loading').html('<div class="igu-inline-error">Unable to load form.</div>');
        });

        igu_uploader.instance = trigger;
        file_port = wrapper.find('.file-view-port');
    }

    $('body').on('click', '.add-file', function () {
        var cId = $(this).closest('.panel').data('cid');
        var key = 'igu_uploader_' + cId;
        igu_uploader = window[key];
        loadInlineForm($(this), igu_uploader.add_url, false);
    });

    $('body').on('click', '.igu-file-update', function () {
        var cId = $(this).closest('.panel').data('cid');
        var key = 'igu_uploader_' + cId;
        igu_uploader = window[key];
        loadInlineForm($(this), igu_uploader.edit_url + $(this).data('id'), true);
    });
    var file_frame;
    $('body').on('click', '.upload_image_button', function () {
        var cId = $(this).closest('.panel').data('cid') || $(this).closest('.igu-inline-form').data('cid');
        if (cId) {
            var key = 'igu_uploader_' + cId;
            if (window[key]) {
                igu_uploader = window[key];
            }
        }
        if (!igu_uploader || !igu_uploader.file_frame_title) {
            return false;
        }
        if (file_frame) {
            file_frame.open();
            return false;
        }
        file_frame = wp.media.frames.file_frame = wp.media({
            title: igu_uploader.file_frame_title,
            multiple: false
        });
        file_frame.on('select', function () {
            attachment = file_frame.state().get('selection').first().toJSON();
            $('#attachment').first().val(attachment.id);
            $('.file-upload-name').first().text(attachment.filename);
        });
        file_frame.on('open', function () {
            file_frame.uploader.uploader.param("igu_uploading", "1");
        });
        file_frame.open();
        return false;
    });
    $('body').on('submit', '.igu-upload-form', function () {
        var that = $(this);
        $.ajax(igu_uploader.form_submit_url, {
            type: 'POST',
            data: $(this).find(':input').serialize(),
            processData: false,
            beforeSend: function () {
                that.find('button').attr('disabled', 'disabled');
            },
            success: function (data) {
                //data = $.parseJSON(data);
                that.find('button').removeAttr('disabled');
                if (data.status == 'success') {
                    //check case update or case insert
                    if (igu_uploader.instance.hasClass('add-file') == false) {
                        var html = $(data.html);
                        igu_uploader.instance.closest('.igu-inline-form').slideUp().empty();
                        $('#igu-media-file-' + data.id).html(html.html());
                    } else {
                        var file_view_port = file_port;
                        var att = $(data.html);
                        att.css('display', 'none');

                        file_view_port.find('.no-file').remove();
                        file_view_port.prepend(att);
                        att.css('display', 'none');

                        file_view_port.find('.no-file').remove();
                        file_view_port.prepend(att);
                        if (file_view_port.width() <= (180 * 3)) {
                            att.css('width', '49%');
                        }
                        if (file_view_port.width() >= (180 * 4)) {
                            att.css('width', '25%');
                        }
                        att.css('display', 'block');
                        var input = file_view_port.closest('form').find('#' + igu_uploader.target_id);
                        input.val(input.val() + ',' + data.id);
                        that.find(':input:not([type=hidden])').val('');
                        that.closest('.igu-inline-form').slideUp().empty();
                    }
                } else {
                    that.find('.form-group').removeClass('has-error has-success');
                    $.each(data.errors, function (i, v) {
                        var element = that.find('.error-' + i);
                        element.parent().addClass('has-error');
                        element.html(v);
                    });
                    that.find('.form-group').each(function () {
                        if (!$(this).hasClass('has-error')) {
                            $(this).find('.m-b-none').text('');
                            $(this).addClass('has-success');
                        }
                    });
                }
            }
        })
        return false;
    });
    $('body').on('click', '.igu-file-delete', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        var that = $(this);
        var parent = $('#igu-media-file-' + id);
        $.ajax({
            type: 'POST',
            url: igu_uploader.ajax_url,
            data: {
                action: 'igu_file_delete',
                id: id,
                _wpnonce: igu_uploader.delete_nonce
            },
            beforeSend: function () {
                /* that.parent().parent().find('button').attr('disabled', 'disabled');
                 that.parent().parent().css('opacity', 0.5);*/
                parent.find('button').attr('disabled', 'disabled');
                parent.css('opacity', 0.5);
            },
            success: function () {
                var element = $('#' + igu_uploader.target_id);
                element.val(element.val().replace(id, ''));
                parent.remove();
            }
        })
    });
    $('.file-view-port').each(function () {
        if ($(this).width() >= (180 * 4)) {
            $(this).find('.igu-media-file-land').css('width', '25%');
        }
        if ($(this).width() <= (180 * 3)) {
            $(this).find('.igu-media-file-land').css('width', '49%');
        }
    })
    $(window).scroll(function () {
        // no-op; left for future use if inline form needs reposition logic
    })
})