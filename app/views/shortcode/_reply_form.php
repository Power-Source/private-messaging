<?php
if (!isset($message)) {
    //get the current
    $messages = $this->messages;
    $message = array_shift($messages);
    if (!is_object($message)) {
        return;
    }
}

// Legacy model only for validation errors & hooks compatibility
$model = new MM_Message_Model();
?>
<div class="ig-container">
    <div class="mmessage-container">
        <div class="modal" id="reply-form-c">
            <div class="modal-dialog">
                <div class="modal-content" id="reply-compose">
                    <div class="modal-header">
                        <h4 class="modal-title"><?php _e("Reply", mmg()->domain) ?></h4>
                    </div>
                    <form method="post" class="form-horizontal compose-form" id="reply-form">
                    <div class="modal-body">
                        <?php do_action('mm_before_reply_form', $message, $model) ?>
                        <div class="form-group <?php echo $model->has_error("content") ? "has-error" : null ?>">
                            <div class="col-lg-12">
                                <textarea 
                                    name="MM_Message_Model[content]" 
                                    id="mm_reply_content"
                                    class="form-control mm_wsysiwyg"
                                    style="height:100px"
                                ><?php echo esc_textarea($model->content ?? ''); ?></textarea>
                                <span class="help-block m-b-none error-content"><?php echo esc_html($model->get_error("content")); ?></span>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <?php wp_nonce_field('compose_message') ?>
                        <input type="hidden" name="is_reply" value="1">
                        <input type="hidden" name="action" value="mm_send_message">
                        <input type="hidden" name="parent_id" value="<?php echo esc_attr(mmg()->encrypt($message->conversation_id)); ?>">
                        <input type="hidden" name="id" value="<?php echo esc_attr(mmg()->encrypt($message->id)); ?>">
                        <input type="hidden" name="MM_Message_Model[attachment]" id="mm_message_model-attachment" value="<?php echo esc_attr($model->attachment ?? ''); ?>">

                        <?php if (mmg()->can_upload() == true) {
                            ig_uploader()->show_upload_control($model, 'attachment', false, array(
                                'title' => __("Attach media or other files.", mmg()->domain),
                                'c_id' => 'mm_reply_compose_container'
                            ));
                        } ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-default compose-close"><?php _e("Close", mmg()->domain) ?></button>
                        <button type="submit"
                                class="btn btn-primary reply-submit"><?php _e("Send", mmg()->domain) ?></button>
                    </div>
                    </form>
                </div>
                <!-- /.modal-content -->
            </div>
        </div>
    </div>
</div>