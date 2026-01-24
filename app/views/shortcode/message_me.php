<?php
$cid = uniqid();
$bid = 'btn_' . uniqid();
$mid = 'modal_' . uniqid();
$fid = 'form_' . uniqid();
$r_id = 'reply_' . uniqid();

?>
<div class="ig-container">
    <div class="mmessage-container">
        <div id="<?php echo $cid ?>">
            <?php
            $disabled = null;
            if (!is_user_logged_in()) {
                $disabled = null;
            } elseif (get_current_user_id() == $user->ID) {
                $disabled = 'disabled';
            } ?>
            <button type="button" id="<?php echo $bid ?>"
                <?php echo $disabled ?> class="<?php echo $a['class'] ?>"><?php echo $a['text'] ?></button>
            <div class="modal fade" id="<?php echo $mid ?>">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <?php if (!is_user_logged_in()) {
                            ?>
                            <div class="modal-body text-left">
                                <?php $this->render_partial('shortcode/login') ?>
                            </div>
                            <script type="text/javascript">
                                jQuery(function ($) {
                                    $('body').on('click', '#<?php echo $bid ?>', function () {
                                        $('#<?php echo $mid ?>').modal({
                                            keyboard: false
                                        })
                                    });
                                })
                            </script>
                        <?php
                        } else {
                        ?>
                        <?php
                        $model = new MM_Message_Model();
                        $model->send_to = $user->user_login;
                        $model->subject = $a['subject'];
                        $model = apply_filters('mm_message_me_before_init', $model);
                        ?>
                        <form method="post" id="<?php echo esc_attr($fid); ?>">
                            <div class="modal-header">
                                <h4 class="modal-title text-left"><?php _e("Nachricht verfassen", mmg()->domain) ?></h4>
                            </div>
                            <div class="modal-body text-left">
                                <div class="alert alert-success hide mm-notice">
                                    <?php _e("Deine Nachricht wurde gesendet", mmg()->domain) ?>
                                </div>
                                <?php if ($a['subject']): ?>
                                    <input type="hidden" name="MM_Message_Model[subject]" id="mm_message_model-subject" value="<?php echo esc_attr($model->subject); ?>">
                                <?php else: ?>
                                    <div
                                        class="form-group <?php echo $model->has_error("subject") ? "has-error" : null ?>">
                                        <label for="mm_message_model-subject" class="col-lg-2 control-label">Betreff</label>
                                        <div class="col-lg-10">
                                            <input type="text" 
                                                   name="MM_Message_Model[subject]" 
                                                   id="mm_message_model-subject" 
                                                   class="form-control mm-wysiwyg"
                                                   value="<?php echo esc_attr($model->subject); ?>">
                                            <span
                                                class="help-block m-b-none error-subject"><?php echo esc_html($model->get_error("subject")); ?></span>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                                <?php endif; ?>
                                <input type="hidden" name="MM_Message_Model[send_to]" id="mm_message_model-send_to" value="<?php echo esc_attr($model->send_to); ?>">
                                <div style="margin-bottom: 0"
                                     class="form-group <?php echo $model->has_error("content") ? "has-error" : null ?>">
                                    <textarea 
                                        name="MM_Message_Model[content]" 
                                        id="mm_compose_content" 
                                        class="form-control mm_wsysiwyg"
                                        style="height:100px"
                                    ><?php echo esc_textarea($model->content ?? ''); ?></textarea>
                                    <span
                                        class="help-block m-b-none error-content"><?php echo esc_html($model->get_error("content")); ?></span>

                                    <div class="clearfix"></div>
                                </div>
                                <?php wp_nonce_field('compose_message') ?>
                                <input type="hidden" name="action" value="mm_send_message">

                                <div class="mm-attachments-control" style="margin-top:10px;">
                                    <input type="file" id="mm-attachment-input-<?php echo $fid ?>" class="mm-attachment-input" multiple style="display:none;">
                                    <button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('mm-attachment-input-<?php echo $fid ?>').click(); return false;\"><?php _e("Dateien auswählen", mmg()->domain) ?></button>
                                    <span class="mm-attachment-status-<?php echo $fid ?>" style="margin-left:10px;color:#666;font-size:12px;"></span>
                                    <div id="mm-attachments-list-<?php echo $fid ?>" class="mm-attachments-list" style="margin-top:8px;"></div>
                                    <input type="hidden" name="MM_Message_Model[attachment]" id="mm-message-model-attachment-<?php echo $fid ?>" value="">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default"
                                        data-dismiss="modal"><?php _e("Close", mmg()->domain) ?></button>
                                <button type="button"
                                        class="btn btn-primary reply-submit"><?php _e("Senden", mmg()->domain) ?></button>
                            </div>
                        </form>
                            <script type="text/javascript">
                                jQuery(document).ready(function ($) {
                                    var attachmentIds = [];

                                    <?php if (mmg()->can_upload()): ?>
                                    $(document).on('change', '#mm-attachment-input-<?php echo $fid ?>', function() {
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

                                        var statusEl = $('.mm-attachment-status-<?php echo $fid ?>');
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
                                        var listEl = $('#mm-attachments-list-<?php echo $fid ?>');
                                        var fileSizeKB = (fileData.size / 1024).toFixed(1);

                                        var itemHtml = '<div class="mm-attachment-item" data-filename="' + fileData.filename + '" style="padding:8px;background:#f5f5f5;border-radius:4px;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;">' +
                                            '<span style="font-size:12px;">' + fileData.display_name + ' (' + fileSizeKB + ' KB)</span>' +
                                            '<button type="button" class="btn btn-xs btn-danger mm-remove-attachment" data-filename="' + fileData.filename + '">×</button>' +
                                            '</div>';

                                        listEl.append(itemHtml);
                                    }

                                    function updateAttachmentField() {
                                        $('#mm-message-model-attachment-<?php echo $fid ?>').val(attachmentIds.join(','));
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

                                    $('#<?php echo $cid ?>').on('click', '.reply-submit', function () {
                                        var top_parent = $('#<?php echo $cid ?>');
                                        var form = top_parent.find('#<?php echo $fid ?>');
                                        var btn = $('<button type="submit" style="width: 0!important;height:0;display: inline - block;background: none;border: none;padding: 0;margin: 0;position: absolute;"></button>');
                                        form.append(btn);
                                        btn.click();
                                    });
                                    $('body').on('click', '#<?php echo $bid ?>', function () {
                                        $('#<?php echo $mid ?>').modal({
                                            keyboard: false
                                        })
                                    });
                                    $('body').on('submit', '#<?php echo $fid ?>', function () {
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
                                                    $('#<?php echo $cid ?>').find('.mm-notice').removeClass('hide');
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
                        <?php } ?>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
        </div>
    </div>
</div>