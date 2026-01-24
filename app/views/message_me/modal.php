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
                        ?>
                        <form method="post" id="message-me-form">
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
                                    style="height:100px"
                                ><?php echo esc_textarea($model->content ?? ''); ?></textarea>
                                <span class="help-block m-b-none error-content"><?php echo esc_html($model->get_error("content")); ?></span>

                                <div class="clearfix"></div>
                            </div>
                            <?php wp_nonce_field('compose_message') ?>
                            <input type="hidden" name="action" value="mm_send_message">
                            <input type="hidden" name="MM_Message_Model[attachment]" id="mm_message_model-attachment" value="<?php echo esc_attr($model->attachment ?? ''); ?>">
                            <?php
                            if (mmg()->can_upload()) {
                                ig_uploader()->show_upload_control($model, 'attachment', false, array(
                                    'title' => __("Füge Medien oder andere Dateien hinzu.", mmg()->domain),
                                    'c_id' => 'message_me_modal_container'
                                ));
                            } ?>
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
                ;
                $('.message-me-has-subject').removeClass('hide').find('input').val(subject);
            } else {
                $('.message-me-has-subject').addClass('hide');
                $('.message-me-no-subject').removeClass('hide').find('input').removeAttr('disabled');
            }
            $('.message-me-send-to').val(send_to);
        });
        $('body').on('submit', '#message-me-form', function () {
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