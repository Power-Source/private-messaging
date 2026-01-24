<div class="ig-container">
    <div class="mmessage-container">
        <div class="modal" id="message-me-modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <?php if (!is_user_logged_in()) {
                        ?>
                        <button type="button" class="compose-close btn btn-xs"
                                style="position: absolute;top:5px;right:5px;z-index:999">x
                        </button>
                        <div class="modal-body text-left">
                            <?php $this->render_partial('shortcode/login') ?>
                        </div>
                    <?php
                    } else {
                        $model = new MM_Message_Model();
                        $model = apply_filters('mm_message_me_before_init', $model);
                        $form_id = 'message-me-form';
                        ?>
                        <form method="post" id="<?php echo $form_id ?>">
                        <div class="modal-header">
                            <h4 class="modal-title text-left"><?php _e("Nachricht verfassen", mmg()->domain) ?></h4>
                        </div>
                        <div class="modal-body text-left">
                            <div class="alert alert-success hide mm-notice">
                                <?php _e("Deine Nachricht wurde gesendet", mmg()->domain) ?>
                            </div>
                            <div class="message-me-has-subject hide">
                                <input type="hidden" name="MM_Message_Model[subject]" id="mm_message_model-subject" value="<?php echo esc_attr($model->subject); ?>">
                            </div>
                            <div class="message-me-no-subject hide">
                                <div class="form-group <?php echo $model->has_error("subject") ? "has-error" : null ?>">
                                    <label for="mm_message_model-subject" class="col-lg-2 control-label"><?php _e("Betreff", mmg()->domain); ?></label>
                                    <div class="col-lg-10">
                                        <input type="text" 
                                               name="MM_Message_Model[subject]" 
                                               id="mm_message_model-subject" 
                                               class="form-control" 
                                               disabled="disabled"
                                               value="<?php echo esc_attr($model->subject); ?>">
                                        <span
                                            class="help-block m-b-none error-subject"><?php echo esc_html($model->get_error("subject")); ?></span>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                            <input type="hidden" name="MM_Message_Model[send_to]" id="mm_message_model-send_to" class="message-me-send-to" value="<?php echo esc_attr($model->send_to); ?>">
                            <div style="margin-bottom: 0"
                                 class="form-group <?php echo $model->has_error("content") ? "has-error" : null ?>">
                                <textarea 
                                    name="MM_Message_Model[content]" 
                                    id="mm_compose_content" 
                                    class="form-control mm_wsysiwyg"
                                    style="min-height:160px"
                                    rows="8"
                                ><?php echo esc_textarea($model->content ?? ''); ?></textarea>
                                <span class="help-block m-b-none error-content"><?php echo esc_html($model->get_error("content")); ?></span>

                                <div class="clearfix"></div>
                            </div>
                            <?php wp_nonce_field('compose_message') ?>
                            <input type="hidden" name="action" value="mm_send_message">

                            <?php if (mmg()->can_upload()): ?>
                            <div class="mm-attachments-control" style="margin-top:10px;">
                                <input type="file" id="mm-attachment-input-<?php echo $form_id ?>" class="mm-attachment-input" multiple style="display:none;">
                                <button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('mm-attachment-input-<?php echo $form_id ?>').click(); return false;\"><?php _e("Dateien auswählen", mmg()->domain) ?></button>
                                <span class="mm-attachment-status-<?php echo $form_id ?>" style="margin-left:10px;color:#666;font-size:12px;"></span>
                                <div id="mm-attachments-list-<?php echo $form_id ?>" class="mm-attachments-list" style="margin-top:8px;"></div>
                                <input type="hidden" name="MM_Message_Model[attachment]" id="mm-message-model-attachment-<?php echo $form_id ?>" value="">
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button"
                                    class="btn btn-default compose-close"><?php _e("Schließen", mmg()->domain) ?></button>
                            <button type="submit"
                                    class="btn btn-primary reply-submit"><?php _e("Senden", mmg()->domain) ?></button>
                        </div>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    jQuery(function ($) {
        <?php if (is_user_logged_in()) { $form_id = 'message-me-form'; ?>
        var attachmentIds = [];

        <?php if (mmg()->can_upload()): ?>
        $(document).on('change', '#mm-attachment-input-<?php echo $form_id ?>', function() {
            var files = this.files;
            for (var i = 0; i < files.length; i++) {
                uploadAttachment(files[i]);
            }
            this.value = '';
        });

        function uploadAttachment(file) {
            var formData = new FormData();
            formData.append('action', 'mm_upload_attachment');
            formData.append('file', file);
            formData.append('conversation_id', 0);
            formData.append('_wpnonce', '<?php echo wp_create_nonce('mm_upload_attachment') ?>');

            var statusEl = $('.mm-attachment-status-<?php echo $form_id ?>');
            statusEl.text('<?php _e("Uploading", mmg()->domain) ?> ' + file.name + '...').css('color', '#333');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.success) {
                        var fileData = data.data;
                        attachmentIds.push(fileData.filename);
                        updateAttachmentList(fileData);
                        updateAttachmentField();
                        statusEl.text('');
                    } else {
                        statusEl.text('<?php _e("Error", mmg()->domain) ?>: ' + (data.data || '<?php _e("Unknown error", mmg()->domain) ?>')).css('color', '#d9534f');
                    }
                },
                error: function() {
                    statusEl.text('<?php _e("Upload failed", mmg()->domain) ?>').css('color', '#d9534f');
                }
            });
        }

        function updateAttachmentList(fileData) {
            var listEl = $('#mm-attachments-list-<?php echo $form_id ?>');
            var fileSizeKB = (fileData.size / 1024).toFixed(1);

            var itemHtml = '<div class="mm-attachment-item" data-filename="' + fileData.filename + '" style="padding:8px;background:#f5f5f5;border-radius:4px;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;">' +
                '<span style="font-size:12px;">' + fileData.display_name + ' (' + fileSizeKB + ' KB)</span>' +
                '<button type="button" class="btn btn-xs btn-danger mm-remove-attachment" data-filename="' + fileData.filename + '">×</button>' +
                '</div>';

            listEl.append(itemHtml);
        }

        function updateAttachmentField() {
            $('#mm-message-model-attachment-<?php echo $form_id ?>').val(attachmentIds.join(','));
        }

        $(document).on('click', '.mm-remove-attachment', function() {
            var filename = $(this).data('filename');
            var index = attachmentIds.indexOf(filename);
            if (index > -1) {
                attachmentIds.splice(index, 1);
            }
            $(this).closest('.mm-attachment-item').remove();
            updateAttachmentField();

            $.post('<?php echo admin_url('admin-ajax.php') ?>', {
                action: 'mm_delete_attachment',
                conversation_id: 0,
                filename: filename,
                _wpnonce: '<?php echo wp_create_nonce('mm_delete_attachment') ?>'
            });
        });
        <?php endif; ?>
        <?php } ?>

        $('.message-me-btn').leanModal({
            closeButton: ".compose-close",
            top: '5%',
            width: '90%',
            maxWidth: 659
        });
        $('body').on('click', '.message-me-btn', function () {
            var data = $($(this).data('target'));
            var subject = data.find('.subject').first().text();
            var send_to = data.find('.send_to').first().text();
            if ($.trim(subject).length != 0) {
                $('.message-me-no-subject').addClass('hide').find('input').attr('disabled', 'disabled');
                $('.message-me-has-subject').removeClass('hide').find('input').val(subject);
            } else {
                $('.message-me-has-subject').addClass('hide');
                $('.message-me-no-subject').removeClass('hide').find('input').removeAttr('disabled');
            }
            $('.message-me-send-to').val(send_to);
        });
        $('body').on('submit', '#<?php echo isset($form_id) ? $form_id : 'message-me-form' ?>', function () {
            var that = $(this);
            $.ajax({
                type: 'POST',
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                data: $(that).find(":input").serialize(),
                beforeSend: function () {
                    that.parent().parent().find('button').attr('disabled', 'disabled');
                },
                success: function (data) {
                    that.find('.form-group').removeClass('has-error has-success');
                    that.parent().parent().find('button').removeAttr('disabled');
                    if (data.status == 'success') {
                        that.find('.form-control').val('');
                        $('#message-me-modal').find('.mm-notice').removeClass('hide');
                        location.reload();
                    } else {
                        $.each(data.errors, function (i, v) {
                            var element = that.find('.error-' + i);
                            element.parent().parent().addClass('has-error');
                            element.html(v);
                        });
                        that.find('.form-group').each(function () {
                            if (!$(this).hasClass('has-error')) {
                                $(this).addClass('has-success');
                            }
                        })
                    }
                }
            })
            return false;
        });
    })
</script>