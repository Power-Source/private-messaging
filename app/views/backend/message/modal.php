<?php
// Legacy model for validation errors
$model = new MM_Message_Model();
?>
<div class="ig-container">
    <div class="mmessage-container">
        <div>
            <div class="modal" id="inject-message">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title"><?php _e("Compose Message", mmg()->domain) ?></h4>
                        </div>
                        <form method="post" class="form-horizontal" id="inject-message-form">
                        <div class="modal-body">
                            <div style="margin-bottom: 0"
                                 class="form-group <?php echo $model->has_error("subject") ? "has-error" : null ?>">
                                <label for="mm_message_model-subject" class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Subject", mmg()->domain); ?></label>
                                <div class="col-md-10 col-sm-12 col-xs-12">
                                    <input type="text" 
                                           name="MM_Message_Model[subject]" 
                                           id="mm_message_model-subject" 
                                           class="form-control" 
                                           placeholder="<?php echo esc_attr__('Subject', mmg()->domain); ?>"
                                           value="<?php echo esc_attr($model->subject); ?>">
                                    <span
                                        class="help-block m-b-none error-subject"><?php echo esc_html($model->get_error("subject")); ?></span>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <div style="margin-bottom: 0"
                                 class="form-group <?php echo $model->has_error("content") ? "has-error" : null ?>">
                                <label for="mm_compose_content" class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Content", mmg()->domain); ?></label>
                                <div class="col-md-10 col-sm-12 col-xs-12">
                                    <textarea 
                                        name="MM_Message_Model[content]" 
                                        id="mm_compose_content" 
                                        class="form-control mm_wsysiwyg"
                                        style="height:100px"
                                        placeholder="<?php echo esc_attr__('Content', mmg()->domain); ?>"
                                    ><?php echo esc_textarea($model->content ?? ''); ?></textarea>
                                    <span
                                        class="help-block m-b-none error-content"><?php echo esc_html($model->get_error("content")); ?></span>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <input type="hidden" name="MM_Message_Model[attachment]" id="mm_message_model-attachment" value="<?php echo esc_attr($model->attachment ?? ''); ?>">
                            <input type="hidden" name="action" value="mm_inject_message">
                            <input type="hidden" name="conversation_id" value="<?php echo esc_attr($conversation_id); ?>">
                            <?php if (mmg()->can_upload() == true) {
                                ig_uploader()->show_upload_control($model, 'attachment', false, array(
                                    'title' => __("Attach media or other files.", mmg()->domain)
                                ));
                            } ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default compose-close"
                                    data-dismiss="modal"><?php _e("Close", mmg()->domain) ?></button>
                            <button type="submit"
                                    class="btn btn-primary compose-submit"><?php _e("Send", mmg()->domain) ?></button>
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
