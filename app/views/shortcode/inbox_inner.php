<?php 
// Only include compose form on initial page load, not on AJAX reloads
// Check if this is an AJAX request
$is_ajax_reload = (defined('DOING_AJAX') && DOING_AJAX) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
if (!$is_ajax_reload && isset($compose_html)) { 
    echo $compose_html; 
} 
?>
<?php if (count($models)): ?>
    <br/>
    <div class="row">
        <div class="col-md-5 col-sm-3 col-xs-3 no-padding">
            <div class="message-list">
                <form class="mm-search-form" method="get"
                      action="<?php echo parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control"
                               value="<?php echo mmg()->get('query', '') ?>" name="query"
                               placeholder="<?php _e("Search", mmg()->domain) ?>">
                        <button class="btn btn-link" type="submit">
                            <i class="fa fa-search"></i>
                        </button>

                        <div class="clearfix"></div>
                    </div>
                </form>
                <div class="ps-container ps-active-x ps-active-y" id="mmessage-list">
                    <ul class="list-group no-margin">
                        <?php foreach ($models as $key => $model): ?>
                            <?php $active_conversation = $key == 0 ? true : false;
                            if (mmg()->get('message_id', -1) != -1) {
                                $checked_message = MM_Message_Model::model()->find(mmg()->get('message_id'));
                                if (is_object($checked_message)) {
                                    $active_conversation = $checked_message->conversation_id == $model->id ? true : false;
                                }
                            }
                            ?>
                            <?php $message = $model->get_last_message(); ?>
                            <li data-id="<?php echo mmg()->encrypt($model->id) ?>"
                                class="load-conv <?php echo $model->has_unread() == false ? 'read' : null ?> list-group-item <?php echo $active_conversation == true ? 'active' : null ?>">
                                <div class="row">
                                    <div class="col-md-3 no-padding">
                                        <img style="width: 90%" class="img-responsive img-circle center-block"
                                             src="<?php echo mmg()->get_avatar_url(get_avatar($message->send_from)) ?>">
                                    </div>
                                    <div class="col-md-9">
                                        <div>
                                            <strong class="small">
                                                <?php echo $message->get_name($message->send_from) ?>
                                            </strong>
                                            <label
                                                class="pull-right label label-primary"><?php echo date('j M', strtotime($message->date)) ?></label>
                                        </div>
                                        <div>
                                            <strong><?php
                                                $fmessage = $model->get_first_message();
                                                $subject = trim(strip_tags(apply_filters('mm_message_subject', $fmessage->subject)), "\n");

                                                echo mmg()->mb_word_wrap($subject, 50) ?></strong>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-md-12">
                                        <p class="text-muted"><?php
                                            $content = trim(strip_tags(apply_filters('mm_message_content', $message->content)), "\n");
                                            echo mmg()->mb_word_wrap($content, 150) ?></p>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="clearfix"></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-7 col-xs-9 col-sm-9 no-padding">
            <div id="mmessage-content" class="ps-container ps-active-x ps-active-y">
                <?php echo $this->render_inbox_message(reset($models)) ?>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
    <?php if ($total_pages > 1): ?>
        <div class="row mm-paging">
            <div class="col-md-12 no-padding">
                <?php if ($paged <= 1): ?>
                    <a disabled href="#"
                       class="btn btn-default btn-sm pull-left"><?php _e("Vorherige", mmg()->domain) ?></a>
                <?php else: ?>
                    <a href="<?php echo esc_url(add_query_arg('mpaged', $paged - 1)) ?>"
                       class="btn btn-default btn-sm pull-left"><?php _e("Vorherige", mmg()->domain) ?></a>
                <?php endif; ?>
                <?php if ($paged >= $total_pages): ?>
                    <a disabled href="#"
                       class="btn btn-default btn-sm pull-right"><?php _e("Nächste", mmg()->domain) ?></a>
                <?php else: ?>
                    <a href="<?php echo esc_url(add_query_arg('mpaged', $paged + 1)) ?>"
                       class="btn btn-default btn-sm pull-right"><?php _e("Nächste", mmg()->domain) ?></a>
                <?php endif; ?>
                <div class="clearfix"></div>
            </div>
            <div class="clearfix"></div>
        </div>
    <?php endif; ?>
    <script type="text/javascript">
        jQuery(function ($) {
            function setInlineStatus(message, type) {
                var box = $('#mmessage-content .mm-inline-status');
                if (!box.length) return;
                var color = '#1f2937';
                if (type === 'success') color = '#15803d';
                if (type === 'error') color = '#b91c1c';
                if (type === 'info') color = '#1d4ed8';
                box.stop(true, true).css({ color: color }).text(message).fadeIn(120).delay(2000).fadeOut(200);
            }

            // Delegated bind so it works after AJAX replacement
            $('body').on('click', '.load-conv', function () {
                var that = $(this);
                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php?box='.mmg()->get('box')) ?>',
                    data: {
                        action: 'mm_load_conversation',
                        id: $(this).data('id'),
                        _wpnonce: '<?php echo wp_create_nonce('mm_load_conversation') ?>'
                    },
                    beforeSend: function () { that.css('cursor', 'wait'); },
                    success: function (data) {
                        that.css('cursor', 'pointer');
                        $('.load-conv').removeClass('active');
                        that.addClass('active read');
                        $('.mm-admin-bar span').text(data.count_unread);
                        $('.unread-count').attr('title', data.count_unread + ' ' + $('.unread-count').data('text'));
                        $('.read-count').attr('title', data.count_read + ' ' + $('.unread-count').data('text'));
                        $('#mmessage-content').html(data.html);
                        $('#mmessage-content').perfectScrollbar('destroy');
                        $('#mmessage-content').perfectScrollbar({ suppressScrollX: true });
                        var reply_form = $(data.reply_form);
                        $('#reply-form-c').html(reply_form.find('#reply-form-c').html());
                        $('body').trigger('abc');
                    }
                })
            });
            $('body').on('click', '.mm-status', function (e) {
                e.preventDefault();
                var that = $(this);
                var status = $(this).data('type');
                if (status == '<?php echo MM_Message_Status_Model::STATUS_DELETE ?>') {
                    if (confirm('<?php echo esc_js(__("Are you sure?", mmg()->domain)) ?>')) {
                        $.ajax({
                            type: 'POST',
                            url: '<?php echo admin_url('admin-ajax.php') ?>',
                            data: { action: 'mm_status', id: $(this).data('id'), _wpnonce: '<?php echo wp_create_nonce('mm_status') ?>', type: status },
                            beforeSend: function () { that.attr('disabled', 'disabled'); },
                            success: function () { $('.load-conv.active').remove(); $('.load-conv').first().trigger('click'); }
                        })
                    }
                } else {
                    $.ajax({
                        type: 'POST',
                        url: '<?php echo admin_url('admin-ajax.php') ?>',
                        data: { action: 'mm_status', id: $(this).data('id'), _wpnonce: '<?php echo wp_create_nonce('mm_status') ?>', type: status },
                        beforeSend: function () { that.attr('disabled', 'disabled'); },
                        success: function () { $('.load-conv.active').remove(); $('.load-conv').first().trigger('click'); }
                    })
                }
            });

            // Delete conversation with attachment cleanup
            $('body').on('click', '.mm-delete-conv', function (e) {
                e.preventDefault();
                var btn = $(this);
                if (!confirm('<?php echo esc_js(__('Diese Unterhaltung löschen?', mmg()->domain)) ?>')) return;

                var convId = btn.data('id');
                var nonce = btn.data('nonce');
                setInlineStatus('<?php echo esc_js(__('Löschen…', mmg()->domain)) ?>', 'info');
                btn.prop('disabled', true);

                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php') ?>',
                    data: { action: 'mm_delete_conversation', id: convId, _wpnonce: nonce },
                    success: function (res) {
                        if (res.status === 'success') {
                            setInlineStatus('<?php echo esc_js(__('Unterhaltung gelöscht', mmg()->domain)) ?>', 'success');
                            var active = $('.load-conv.active');
                            active.remove();
                            var first = $('.load-conv').first();
                            if (first.length) {
                                first.trigger('click');
                            } else {
                                $('#mmessage-content').html('<div class="well well-sm no-margin"><?php echo esc_js(__('Keine Nachricht gefunden!', mmg()->domain)) ?></div>');
                            }
                        } else {
                            setInlineStatus(res.message || '<?php echo esc_js(__('Löschen fehlgeschlagen', mmg()->domain)) ?>', 'error');
                            btn.prop('disabled', false);
                        }
                    },
                    error: function () {
                        setInlineStatus('<?php echo esc_js(__('Löschen fehlgeschlagen', mmg()->domain)) ?>', 'error');
                        btn.prop('disabled', false);
                    }
                });
            });
            $('#mmessage-list').perfectScrollbar({ suppressScrollX: true });
            $('#mmessage-content').perfectScrollbar({ suppressScrollX: true });
            if ($('.load-conv.active').length > 0) { $('.load-conv.active').first().trigger('click'); }
            
            // Reply button handler - opens compose form in reply mode
            $('body').on('click', '.mm-reply-inline', function(e) {
                e.preventDefault();
                var conversationId = $(this).data('conversation-id');
                var container = $('#compose-form-container');
                
                // Set reply mode
                container.attr('data-reply-mode', '1');
                container.find('.panel-heading strong').text('<?php echo esc_js(__('Antworten', mmg()->domain)); ?>');
                container.find('.compose-field-sendto, .compose-field-subject').hide();
                
                // Show attachment section
                container.find('.mm-attachments-control').parent().show();
                
                // Remove old conversation_id and add new one
                container.find('input[name="MM_Message_Model[conversation_id]"]').remove();
                container.find('form').prepend('<input type="hidden" name="MM_Message_Model[conversation_id]" value="' + conversationId + '">');
                
                // Clear content and attachments
                container.find('#mm_compose_content').val('');
                if (typeof window.mmAttachmentIds !== 'undefined') {
                    window.mmAttachmentIds = [];
                    window.mmAttachmentNames = {};
                }
                $('#mm-attachments-list').html('');
                $('#mm-message-model-attachment').val('');
                
                // Show form and scroll to it
                container.show();
                $('html, body').animate({
                    scrollTop: container.offset().top - 20
                }, 300);
            });
        });
    </script>
<?php else: ?>
    <br/>
    <div class="row">
        <div class="col-md-12 no-padding">
            <div class="well well-sm">
                <?php _e("Keine Nachricht gefunden!", mmg()->domain) ?>
            </div>
        </div>
    </div>
<?php endif; ?>
