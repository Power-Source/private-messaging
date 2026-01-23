<?php
// Legacy model for validation errors & hooks compatibility
$model = new MM_Message_Model();
?>
    <div class="ig-container">
        <div class="mmessage-container">
            <div>
                <div class="modal" id="compose-form-container">
                    <div class="modal-dialog">
                        <div class="modal-content" id="compose-modal">
                            <div class="modal-header">
                                <h4 class="modal-title"><?php _e("Compose Message", mmg()->domain) ?></h4>
                            </div>
                            <form method="post" class="compose-form form-horizontal" id="compose-form">
                            <div class="modal-body">
                                <div style="margin-bottom: 0"
                                     class="form-group <?php echo $model->has_error("send_to") ? "has-error" : null ?>">
                                    <label for="mm_message_model-send_to" class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Send To", mmg()->domain); ?></label>
                                    <div class="col-md-10 col-sm-12 col-xs-12">
                                        <input type="text" 
                                               name="MM_Message_Model[send_to]" 
                                               id="mm_message_model-send_to" 
                                               class="form-control" 
                                               placeholder="<?php echo esc_attr__('Send to', mmg()->domain); ?>"
                                               value="<?php echo esc_attr($model->send_to); ?>">
                                        <?php do_action('mm_compose_form_after_send_to', $model) ?>
                                        <span class="help-block m-b-none error-send_to">
                                        <?php echo esc_html($model->get_error("send_to")); ?>
                                    </span>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                                <?php do_action('mm_before_subject_field', $model, 'compose_form') ?>
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
                                        <?php do_action('mm_compose_form_after_subject', $model) ?>
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
                                        <?php do_action('mm_compose_form_after_content', $model) ?>
                                        <span
                                            class="help-block m-b-none error-content"><?php echo esc_html($model->get_error("content")); ?></span>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                                <?php wp_nonce_field('compose_message'); ?>
                                <input type="hidden" name="MM_Message_Model[attachment]" id="mm_message_model-attachment" value="<?php echo esc_attr($model->attachment ?? ''); ?>">
                                <input type="hidden" name="action" value="mm_send_message">
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
    <!-- /.modal -->
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            window.mm_compose_select = $('#mm_message_model-send_to').selectize({
                valueField: 'id',
                labelField: 'name',
                searchField: 'name',
                options: [],
                create: false,
                load: function (query, callback) {
                    if (!query.length) return callback();
                    var instance = window.mm_compose_select[0].selectize;
                    $.ajax({
                        type: 'POST',
                        url: '<?php echo admin_url('admin-ajax.php?action=mm_suggest_users&_wpnonce='.wp_create_nonce('mm_suggest_users')) ?>',
                        data: {
                            'query': query
                        },
                        beforeSend: function () {
                            instance.$control.append('<i style="position: absolute;right: 10px;" class="fa fa-circle-o-notch fa-spin"></i>');
                        },
                        success: function (data) {
                            instance.$control.find('i').remove();
                            callback(data);
                        }
                    });
                }
            });
        })
    </script>
<?php do_action('mm_compose_form_end') ?>