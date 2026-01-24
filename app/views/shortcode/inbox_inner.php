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
                <div class="mm-search-form" style="position:relative;margin-bottom:10px;">
                    <div class="input-group input-group-sm" style="position:relative;">
                        <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;z-index:5;">
                            <i class="fa fa-search"></i>
                        </span>
                        <input type="text" class="form-control mm-search-input"
                               id="mm-search-input"
                               value="<?php echo esc_attr(mmg()->get('query', '')) ?>"
                               placeholder="<?php _e("Suchen...", mmg()->domain) ?>"
                               style="border-radius:6px;padding-left:35px;padding-right:35px;">
                        <button class="btn btn-link" type="button" id="mm-search-clear"
                               style="position:absolute;right:8px;top:50%;transform:translateY(-50%);z-index:10;border:none;background:none;color:#dc2626;display:none;padding:0;font-size:16px;cursor:pointer;">
                            <i class="fa fa-times"></i>
                        </button>
                        <div class="clearfix"></div>
                    </div>
                    <div id="mm-search-dropdown" class="mm-search-dropdown" 
                         style="position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 6px 6px;max-height:400px;overflow-y:auto;z-index:100;display:none;box-shadow:0 4px 16px rgba(0,0,0,0.12);margin-top:-1px;"></div>
                </div>
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
                            <?php $message = $model->get_last_message(); $is_unread = $model->has_unread(); ?>
                            <li data-id="<?php echo mmg()->encrypt($model->id) ?>"
                                class="load-conv list-group-item <?php echo $is_unread ? 'unread' : 'read' ?> <?php echo $active_conversation == true ? 'active' : null ?>"
                                style="display:flex;align-items:flex-start;gap:12px;border:none;border-bottom:1px solid #e5e7eb;padding:10px 12px;cursor:pointer;margin:0;">
                                <div style="flex-shrink:0;width:36px;height:36px;">
                                    <?php echo PM_Avatar_Handler::get_avatar_html($message->send_from, 36, 'mm-list-avatar'); ?>
                                </div>
                                <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:4px;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:nowrap;">
                                        <strong style="<?php echo $is_unread ? 'font-weight:600;color:#111827;' : 'color:#374151;'; ?>;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            <?php echo $message->get_name($message->send_from) ?>
                                        </strong>
                                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                            <?php if (!empty($message->attachment)) : ?>
                                                <i class="fa fa-paperclip" title="<?php esc_attr_e('Anhang', mmg()->domain) ?>" style="color:#6b7280;font-size:11px;"></i>
                                            <?php endif; ?>
                                            <span style="background:#eef2ff;color:#374151;border-radius:12px;padding:2px 8px;font-size:11px;white-space:nowrap;">
                                                <?php echo date('j M', strtotime($message->date)) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:4px;align-items:center;">
                                        <span style="<?php echo $is_unread ? 'font-weight:600;color:#111827;' : 'color:#111827;'; ?>;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            <?php
                                                $fmessage = $model->get_first_message();
                                                $subject = trim(strip_tags(apply_filters('mm_message_subject', $fmessage->subject)), "\n");
                                                echo mmg()->mb_word_wrap($subject, 40);
                                            ?>
                                        </span>
                                    </div>
                                    <div style="margin:0;">
                                        <span style="color:#6b7280;font-size:12px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;">
                                            <?php
                                                $content = trim(strip_tags(apply_filters('mm_message_content', $message->content)), "\n");
                                                echo mmg()->mb_word_wrap($content, 80);
                                            ?>
                                        </span>
                                    </div>
                                </div>
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

            // Keyboard shortcuts: j (next), k (prev), r (reply), a (archive/unarchive), Del (delete)
            $(document).on('keydown', function (e) {
                // Avoid interfering when typing in inputs/textareas or using modifier keys
                var tag = e.target.tagName.toLowerCase();
                if (tag === 'input' || tag === 'textarea' || e.ctrlKey || e.metaKey || e.altKey) return;

                var items = $('.load-conv');
                if (!items.length) return;
                var active = $('.load-conv.active');
                var idx = active.length ? items.index(active) : 0;

                // j = next
                if (e.key === 'j' || e.keyCode === 74) {
                    e.preventDefault();
                    var nextIdx = Math.min(idx + 1, items.length - 1);
                    $(items.get(nextIdx)).trigger('click');
                    return;
                }
                // k = prev
                if (e.key === 'k' || e.keyCode === 75) {
                    e.preventDefault();
                    var prevIdx = Math.max(idx - 1, 0);
                    $(items.get(prevIdx)).trigger('click');
                    return;
                }
                // r = reply
                if (e.key === 'r' || e.keyCode === 82) {
                    e.preventDefault();
                    var replyBtn = $('#mmessage-content .mm-reply-inline').first();
                    if (replyBtn.length) replyBtn.trigger('click');
                    return;
                }
                // a = archive / unarchive (toggle via existing button)
                if (e.key === 'a' || e.keyCode === 65) {
                    e.preventDefault();
                    var statusBtn = $('#mmessage-content .mm-status').first();
                    if (statusBtn.length) statusBtn.trigger('click');
                    return;
                }
                // Delete key = delete conversation
                if (e.key === 'Delete' || e.keyCode === 46) {
                    e.preventDefault();
                    var delBtn = $('#mmessage-content .mm-delete-conv').first();
                    if (delBtn.length) delBtn.trigger('click');
                    return;
                }
            });

            // Helper function to escape HTML
            function esc(text) {
                if (!text) return '';
                return $('<div/>').text(text).html();
            }

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

            // Live search: As user types, fetch results via AJAX
            var searchTimeout;
            $('#mm-search-input').on('keyup', function() {
                var query = $(this).val().trim();
                var dropdown = $('#mm-search-dropdown');
                var clearBtn = $('#mm-search-clear');

                // Show/hide clear button
                if (query.length > 0) {
                    clearBtn.show();
                } else {
                    clearBtn.hide();
                    dropdown.hide();
                    return;
                }

                // Debounce search
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    if (query.length < 2) {
                        return;
                    }

                    $.ajax({
                        type: 'GET',
                        url: '<?php echo admin_url('admin-ajax.php') ?>',
                        data: {
                            action: 'mm_search_conversations',
                            query: query
                        },
                        success: function(response) {
                            if (response.success && response.data.results && response.data.results.length > 0) {
                                var html = '';
                                $.each(response.data.results, function(i, item) {
                                    html += '<div class="mm-search-item" data-id="' + item.id + '" style="padding:8px 12px;border-bottom:1px solid #e5e7eb;cursor:pointer;transition:all 0.15s ease;display:flex;align-items:center;gap:10px;" onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'transparent\'">';
                                    
                                    // Avatar
                                    html += '<div style="flex-shrink:0;">' + item.avatar + '</div>';
                                    
                                    // Content
                                    html += '<div style="flex:1;min-width:0;">';
                                    html += '<div style="font-weight:600;color:#111827;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + esc(item.subject) + '</div>';
                                    html += '<div style="color:#9ca3af;font-size:11px;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + esc(item.snippet) + '</div>';
                                    html += '</div>';
                                    
                                    // Date
                                    html += '<div style="flex-shrink:0;color:#9ca3af;font-size:10px;white-space:nowrap;">' + esc(item.date) + '</div>';
                                    
                                    html += '</div>';
                                });
                                dropdown.html(html).show();
                                
                                // Bind click to search results
                                $('.mm-search-item').off('click').on('click', function() {
                                    var convId = $(this).data('id');
                                    $('.load-conv[data-id="' + convId + '"]').trigger('click');
                                    dropdown.hide();
                                    $('#mm-search-input').val('');
                                    clearBtn.hide();
                                });
                            } else {
                                dropdown.html('<div style="padding:16px;text-align:center;color:#9ca3af;font-size:12px;font-style:italic;"><?php echo esc_js(__('Keine Ergebnisse gefunden', mmg()->domain)); ?></div>').show();
                            }
                        },
                        error: function(xhr, status, error) {
                            dropdown.html('<div style="padding:12px;text-align:center;color:#dc2626;font-size:12px;">Fehler beim Suchen</div>').show();
                        }
                    });
                }, 300);
            });

            // Clear search
            $('#mm-search-clear').on('click', function() {
                $('#mm-search-input').val('').focus();
                $(this).hide();
                $('#mm-search-dropdown').hide();
            });

            // Close dropdown on escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('#mm-search-dropdown').hide();
                }
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
