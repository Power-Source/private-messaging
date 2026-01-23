<?php
// Legacy model for validation errors
$model = new MM_Message_Model();
?>
<div class="ig-container">
    <div class="mmessage-container">
        <div>
            <div class="modal" id="compose-form-container-admin-bar">
                <div class="modal-dialog">
                    <div class="modal-content" id="compose-modal-admin-bar">
                        <div class="modal-header">
                            <h4 class="modal-title"><?php _e("Compose Message", mmg()->domain) ?></h4>
                        </div>
                        <form method="post" class="form-horizontal" id="compose-form-admin-bar">
                        <div class="modal-body">

                            <div style="margin-bottom: 0"
                                 class="form-group <?php echo $model->has_error("send_to") ? "has-error" : null ?>">
                                <label for="admin-bar-mm-send-to" class="control-label col-sm-2 hidden-xs hidden-sm"><?php _e("Send To", mmg()->domain); ?></label>
                                <div class="col-md-10 col-sm-12 col-xs-12">
                                    <input type="text" 
                                           name="MM_Message_Model[send_to]" 
                                           id="admin-bar-mm-send-to" 
                                           class="form-control" 
                                           placeholder="<?php echo esc_attr__('Send to', mmg()->domain); ?>"
                                           value="<?php echo esc_attr($model->send_to); ?>">
                                    <!--<span
                                class="help-block m-b-none"><?php /*_e("Please enter the username, separate by commas", mmg()->domain) */ ?></span>-->
                                    <span
                                        class="help-block m-b-none error-send_to"><?php echo esc_html($model->get_error("send_to")); ?></span>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <?php do_action('mm_before_subject_field', $model, 'admin-bar') ?>
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
        if($(".mm-compose-admin-bar a").size() > 0) {
            $(".mm-compose-admin-bar a").leanModal({
                closeButton: ".compose-close",
                top: '5%',
                width: '90%',
                maxWidth: 659
            });
            $('body').on('submit', '#compose-form-admin-bar', function () {
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
                            $('.compose-admin-bar-alert').removeClass('hide');
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

            var admin_bar_seletize = $('#admin-bar-mm-send-to').selectize({
                valueField: 'id',
                labelField: 'name',
                searchField: 'name',
                options: [],
                create: false,
                load: function (query, callback) {
                    if (!query.length) return callback();
                    var instance = admin_bar_seletize[0].selectize;
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
        }
    })
</script>

