<?php $mes = new MM_Message_Model(); ?>
<?php
$messages = $model->get_messages();
?>
<div class="wrap">
    <div class="ig-container">
        <div class="mmessage-container">
            <div class="page-header">
                <h2><?php _e("Message #" . $model->id, mmg()->domain) ?></h2>
            </div>
            <div class="row">
                <div class="clearfix"></div>
                <div class="col-md-12">
                    <a class="button button-default inject-message"
                       href="#inject-message"><?php _e("Send a message to this conversation", mmg()->domain) ?></a>

                    <div class="clearfix"></div>
                    <br/>

                    <div class="panel panel-default">
                        <div class="panel-body">
                            <table class="table table-striped table-condensed" style="margin: 0;">
                                <thead style="display:none;">
                                <tr>
                                    <th><?php _e("Message", mmg()->domain) ?></th>
                                    <th style="width: 10%"><?php _e("", mmg()->domain) ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($messages as $message): ?>
                                    <tr style="border-bottom: 2px solid #e5e7eb;">
                                        <td style="vertical-align: top; padding: 12px;">
                                            <!-- Subject + Metadata Header -->
                                            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #f3f4f6;">
                                                <div>
                                                    <strong style="font-size: 14px; color: #111827;"><?php echo esc_html($message->subject ?: '(No subject)'); ?></strong>
                                                </div>
                                                <div style="text-align: right; flex-shrink: 0;">
                                                    <div style="color: #6b7280; font-size: 10px; line-height: 1.3; white-space: nowrap; margin-left: 16px;">
                                                        <div style="font-weight: 500;"><?php echo esc_html($message->get_name($message->send_from)); ?></div>
                                                        <div><?php echo date('j.m.Y H:i', strtotime($message->date)); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Message Content -->
                                            <div style="color: #374151; font-size: 13px; line-height: 1.6;">
                                                <?php echo wpautop($message->content); ?>
                                            </div>
                                        </td>
                                        <td style="vertical-align: top; padding: 12px; width: 80px; text-align: center;">
                                            <a id="target-message-<?php echo $message->id ?>"
                                               href="#message-<?php echo $message->id ?>"
                                               class="button button-small leanmodal-trigger" title="<?php _e('Edit', mmg()->domain); ?>" style="display: block; margin-bottom: 4px;">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form method="post" style="display: inline" class="delete-message-frm">
                                                <input type="hidden" name="id" value="<?php echo $message->id ?>">
                                                <input type="hidden" name="action" value="mm_delete_user_message">
                                                <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('mm_delete_user_message'); ?>">
                                                <button type="submit" class="button button-small" title="<?php _e('Delete', mmg()->domain); ?>" style="display: block;">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
            <?php foreach ($messages as $message): ?>
                <div class="modal" data-id="<?php echo $message->id ?>" id="message-<?php echo $message->id ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" class="message-save-form" data-id="<?php echo $message->id ?>">
                                <div class="modal-header">
                                    <h4 class="modal-title"><?php _e("Edit Message", mmg()->domain) ?></h4>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-danger hide"></div>
                                    <input type="hidden" name="id" value="<?php echo $message->id ?>">

                                    <div class="form-group">
                                        <label class="label-control">
                                            <?php _e("Subject", mmg()->domain) ?>
                                        </label>
                                        <input type="text" name="subject" class="form-control"
                                               value="<?php echo $message->subject ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="label-control">
                                            <?php _e("Content", mmg()->domain) ?>
                                        </label>
                                        <?php wp_editor(stripslashes($message->content), 'message-content-' . $message->id, array(
                                            'textarea_name' => 'content'
                                        )) ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default compose-close"
                                                data-dismiss="modal"><?php _e("Close", mmg()->domain) ?></button>
                                        <button type="submit"
                                                class="btn btn-primary"><?php _e("Save Changes", mmg()->domain) ?></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php $this->render_partial('backend/message/modal', array(
    'conversation_id' => $model->id
)) ?>
<!-- /.modal -->
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $(".inject-message").leanModal({
            closeButton: ".compose-close",
            top: '5%',
            width: '90%',
            maxWidth: 659
        });
        $('body').on('submit', '#inject-message-form', function () {
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
    })
</script>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $(".leanmodal-trigger").leanModal({
            closeButton: ".compose-close",
            top: '5%',
            width: '90%',
            maxWidth: 659
        });
        $('.message-save-form').submit(function () {
            var that = $(this);
            var send = that.serializeAssoc();
            var editor_id = 'message-content-' + send['id'];
            var editor = tinymce.editors[editor_id];
            if (editor) {
                send['content'] = editor.getContent();
            }
            $.ajax({
                type: 'POST',
                data: {
                    action: 'mmg_message_edit',
                    data: send,
                    _wpnonce: '<?php echo wp_create_nonce('mmg_message_edit'); ?>'
                },
                url: ajaxurl,
                beforeSend: function () {
                    that.find('button').attr('disabled');
                },
                success: function (data) {
                    that.find('button').removeAttr('disabled');
                    if (data.status == 0) {
                        that.parent().find('.alert').html(data.errors).removeClass('hide');
                    } else {
                        that.parent().find('.alert').html('').addClass('hide');
                        that.find('.compose-close').trigger('click');
                        var tr = $('#target-message-' + send['id']).closest('tr');
                        tr.find('td:eq(2)').html(data.model['content']);
                        tr.addClass('animated flash');
                        tr.one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
                            tr.removeClass('animated flash');
                        });
                    }
                }
            })
            return false;
        });
        $('.delete-message-frm').submit(function () {
            if (confirm('<?php __("Are you sure",mmg()->domain) ?>')) {
                var that = $(this);
                $.ajax({
                    type: 'POST',
                    data: $(this).serializeAssoc(),
                    url: ajaxurl,
                    beforeSend: function () {
                        that.find('button').attr('disabled');
                    },
                    success: function () {
                        that.closest('tr').remove();
                    }
                })
            }
            return false;
        })
    })
</script>