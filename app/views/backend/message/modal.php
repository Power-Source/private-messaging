<?php
// Legacy model for validation errors
$model = new MM_Message_Model();
$form_id = 'inject-message-form';
?>
<div class="ig-container">
    <div class="mmessage-container">
        <div>
            <div class="modal" id="inject-message">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title"><?php _e("Nachricht verfassen", mmg()->domain) ?></h4>
                        </div>
                        <form method="post" class="form-horizontal" id="<?php echo $form_id ?>">
                        <div class="modal-body">
                            <div style="margin-bottom: 0"
                                 class="form-group">
                                <label for="mm_message_model-subject" class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Betreff", mmg()->domain); ?></label>
                                <div class="col-md-10 col-sm-12 col-xs-12">
                                    <input type="text" 
                                           name="MM_Message_Model[subject]" 
                                           id="mm_message_model-subject" 
                                           class="form-control" 
                                           placeholder="<?php echo esc_attr__('Betreff', mmg()->domain); ?>"
                                           value="<?php echo esc_attr($model->subject ?? ''); ?>">
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <div style="margin-bottom: 0"
                                 class="form-group">
                                <label for="mm_compose_content" class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Inhalt", mmg()->domain); ?></label>
                                <div class="col-md-10 col-sm-12 col-xs-12">
                                    <textarea 
                                        name="MM_Message_Model[content]" 
                                        id="mm_compose_content" 
                                        class="form-control mm_wsysiwyg"
                                        style="min-height:160px"
                                        rows="8"
                                        placeholder="<?php echo esc_attr__('Inhalt', mmg()->domain); ?>"
                                    ><?php echo esc_textarea($model->content ?? ''); ?></textarea>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <input type="hidden" name="action" value="mm_inject_message">
                            <input type="hidden" name="conversation_id" value="<?php echo esc_attr($conversation_id); ?>">
                            <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('mm_inject_message'); ?>">

                            <?php if (mmg()->can_upload()): ?>
                            <div class="form-group">
                                <label class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Anhänge", mmg()->domain); ?></label>
                                <div class="col-md-10 col-sm-12 col-xs-12">
                                    <div class="mm-attachments-control">
                                        <input type="file" id="mm-attachment-input-<?php echo $form_id ?>" class="mm-attachment-input" multiple style="display:none;">
                                        <button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('mm-attachment-input-<?php echo $form_id ?>').click(); return false;"><?php _e("Dateien auswählen", mmg()->domain) ?></button>
                                        <span class="mm-attachment-status-<?php echo $form_id ?>" style="margin-left:10px;color:#666;font-size:12px;"></span>
                                        <div id="mm-attachments-list-<?php echo $form_id ?>" class="mm-attachments-list" style="margin-top:8px;"></div>
                                        <input type="hidden" name="MM_Message_Model[attachment]" id="mm-message-model-attachment-<?php echo $form_id ?>" value="">
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default compose-close"
                                    data-dismiss="modal"><?php _e("Schließen", mmg()->domain) ?></button>
                            <button type="submit"
                                    class="btn btn-primary compose-submit"><?php _e("Senden", mmg()->domain) ?></button>
                        </div>
                        </form>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        var formId = '<?php echo $form_id ?>';
        var attachmentIds = [];

        $(".inject-message").leanModal({
            closeButton: ".compose-close",
            top: '5%',
            width: '90%',
            maxWidth: 659
        });

        <?php if (mmg()->can_upload()): ?>
        $(document).on('change', '#mm-attachment-input-' + formId, function() {
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
            formData.append('conversation_id', <?php echo intval($conversation_id); ?>);
            formData.append('_wpnonce', '<?php echo wp_create_nonce('mm_upload_attachment') ?>');

            var statusEl = $('.mm-attachment-status-' + formId);
            statusEl.text('Hochladen ' + file.name + '...').css('color', '#333');

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
                        statusEl.text('Fehler: ' + (data.data || 'Unbekannter Fehler')).css('color', '#d9534f');
                    }
                },
                error: function() {
                    statusEl.text('Upload fehlgeschlagen').css('color', '#d9534f');
                }
            });
        }

        function updateAttachmentList(fileData) {
            var listEl = $('#mm-attachments-list-' + formId);
            var fileSizeKB = (fileData.size / 1024).toFixed(1);

            var itemHtml = '<div class="mm-attachment-item" data-filename="' + fileData.filename + '" style="padding:8px;background:#f5f5f5;border-radius:4px;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;">' +
                '<span style="font-size:12px;">' + fileData.display_name + ' (' + fileSizeKB + ' KB)</span>' +
                '<button type="button" class="btn btn-xs btn-danger mm-remove-attachment" data-filename="' + fileData.filename + '">×</button>' +
                '</div>';

            listEl.append(itemHtml);
        }

        function updateAttachmentField() {
            $('#mm-message-model-attachment-' + formId).val(attachmentIds.join(','));
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
                conversation_id: <?php echo intval($conversation_id); ?>,
                filename: filename,
                _wpnonce: '<?php echo wp_create_nonce('mm_delete_attachment') ?>'
            });
        });
        <?php endif; ?>

        $('body').on('submit', '#'+formId, function () {
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
                        location.reload();
                    } else {
                        $.each(data.errors, function (i, v) {
                            var element = that.find('.error-' + i);
                            element.parent().parent().addClass('has-error');
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
    });
</script>
