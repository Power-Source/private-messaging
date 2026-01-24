<?php
$message = array_shift($messages);
if (!isset($render_reply)) {
    $render_reply = true;
}
$conversation = MM_Conversation_Model::model()->find($message->conversation_id);

$mm_render_message_body = function ($msg) {
    $raw = apply_filters('mm_message_content', $msg->content);
    if (isset($GLOBALS['wp_embed']) && $GLOBALS['wp_embed'] instanceof \WP_Embed) {
        $raw = $GLOBALS['wp_embed']->autoembed($raw);
        $raw = $GLOBALS['wp_embed']->run_shortcode($raw);
    }
    $pretty = wpautop(make_clickable($raw));

    $allowed = wp_kses_allowed_html('post');
    $allowed['iframe'] = array(
        'src'             => true,
        'width'           => true,
        'height'          => true,
        'frameborder'     => true,
        'allow'           => true,
        'allowfullscreen' => true,
        'title'           => true,
    );

    return wp_kses($pretty, $allowed);
};
$history_messages = array();
$seen_fingerprints = array();
$primary_fp = ($message->id && is_scalar($message->id)) ? ('id:' . $message->id) : md5(($message->content ?? '') . ($message->date ?? ''));
$seen_fingerprints[$primary_fp] = true;

foreach ($messages as $m) {
    if (!is_object($m)) { continue; }
    $fp = (isset($m->id) && $m->id) ? ('id:' . $m->id) : md5(($m->content ?? '') . ($m->date ?? ''));
    if (isset($seen_fingerprints[$fp])) { continue; }
    $seen_fingerprints[$fp] = true;
    $history_messages[] = $m;
}
$has_history = count($history_messages) > 0;
?>
<div class="ig-container">
    <section class="message-content">
        <div class="message-content-meta pull-left" style="margin-bottom:10px;">
            <?php do_action('message_content_meta', $message) ?>
            <?php if ($conversation->is_lock()): ?>
                <div class="clearfix"></div>
                <span style="color:#f0ad4e;font-weight:600;"><i class="fa fa-lock"></i> <?php _e("This conversation has been locked", mmg()->domain) ?></span>
            <?php endif; ?>
        </div>
        <div class="message-content-actions" style="margin-bottom:10px;display:flex;justify-content:flex-end;gap:8px;align-items:center;">
            <?php if ($render_reply == true): ?>
                <?php $delete_nonce = wp_create_nonce('mm_delete_conv'); ?>
                <div class="mm-toolbar" style="display:flex;gap:8px;align-items:center;">
                    <?php if ($conversation->is_lock()): ?>
                        <button type="button" class="btn btn-info btn-sm" disabled style="border-radius:8px;opacity:0.6;">
                            <i class="fa fa-lock"></i> <?php _e("Gesperrt", mmg()->domain) ?>
                        </button>
                    <?php else: ?>
                        <button type="button"
                            data-conversation-id="<?php echo esc_attr($message->conversation_id) ?>"
                            class="btn btn-primary btn-sm mm-reply-inline" style="border-radius:8px;">
                            <i class="fa fa-reply"></i> <?php _e("Antworten", mmg()->domain) ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($conversation->is_archive()): ?>
                        <button type="button"
                            data-id="<?php echo esc_attr(mmg()->encrypt($message->conversation_id)) ?>"
                            data-type="<?php echo MM_Message_Status_Model::STATUS_READ ?>"
                            class="btn btn-default btn-sm mm-status" style="border-radius:8px;">
                            <i class="fa fa-undo"></i> <?php _e("Archivierung aufheben", mmg()->domain) ?>
                        </button>
                    <?php else: ?>
                        <button type="button"
                            data-id="<?php echo esc_attr(mmg()->encrypt($message->conversation_id)) ?>"
                            data-type="<?php echo MM_Message_Status_Model::STATUS_ARCHIVE ?>"
                            class="btn btn-default btn-sm mm-status" style="border-radius:8px;">
                            <i class="fa fa-archive"></i> <?php _e("Archivieren", mmg()->domain) ?>
                        </button>
                    <?php endif; ?>

                    <button type="button"
                        class="btn btn-danger btn-sm mm-delete-conv"
                        data-id="<?php echo esc_attr(mmg()->encrypt($message->conversation_id)) ?>"
                        data-nonce="<?php echo esc_attr($delete_nonce); ?>"
                        style="border-radius:8px;">
                        <i class="fa fa-trash"></i> <?php _e("Löschen", mmg()->domain) ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <div class="mm-inline-status" style="display:none;font-size:12px;margin:0 0 8px;text-align:right;"></div>
        <?php /*$this->render_partial('shortcode/_reply_form', array(
            'message' => $message
        )); */ ?>
        <div class="clearfix"></div>
        <div class="page-header">
            <!-- Avatar + Subject + Metadata in one line -->
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;">
                <!-- Avatar left -->
                <div style="flex-shrink:0;">
                    <?php echo PM_Avatar_Handler::get_avatar_html($message->send_from, 55, 'mm-detail-avatar'); ?>
                </div>
                
                <!-- Subject middle -->
                <h3 class="mm-message-subject" style="margin:0;flex:1;font-size:18px;"><?php echo apply_filters('mm_message_subject', $message->subject) ?></h3>
                
                <!-- Metadata right -->
                <div style="flex-shrink:0;text-align:right;">
                    <strong style="display:block;font-size:13px;line-height:1.3;margin-bottom:2px;"><?php
                        if ($message->send_from == get_current_user_id()) {
                            echo __("Ich", mmg()->domain) . ' (' . $message->get_name($message->send_from) . ')';
                        } else {
                            echo $message->get_name($message->send_from);
                        } ?></strong>
                    <span style="display:block;font-size:12px;color:#6b7280;line-height:1.3;margin-bottom:1px;"><?php echo date('j. F Y, G:i', strtotime($message->date)) ?></span>
                    <?php if (mmg()->get('box') == 'sent'): ?>
                        <small style="display:block;font-size:11px;color:#9ca3af;line-height:1.3;"><?php _e("An:", mmg()->domain) ?> <?php echo $message->get_name($message->send_to); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="message-body" style="font-size:15px;line-height:1.7;color:#1f2937;">
            <?php echo $mm_render_message_body($message); ?>
        </div>
        <?php 
        // PM Attachments (new system) - Modern 2026 display
        $attachment_files = explode(',', $message->attachment);
        $attachment_files = array_filter($attachment_files);
        
        // DEBUG
        /*if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('=== ATTACHMENT DEBUG ===');
            error_log('Message ID: ' . $message->id);
            error_log('Conversation ID: ' . $message->conversation_id);
            error_log('Attachment raw: ' . $message->attachment);
            error_log('Attachment files count: ' . count($attachment_files));
            error_log('Attachment files: ' . print_r($attachment_files, true));
        }
        */ 
        if (count($attachment_files) > 0):
        ?>
            <div class="message-footer" style="margin-top:20px;">
                <div class="mm-attachments-modern">
                    <?php foreach ($attachment_files as $filename): 
                        $filename = trim($filename);
                        $file_info = PM_Attachment_Handler::get_file_info($message->conversation_id, $filename);
                        
                        if (!$file_info) continue;
                        
                        $is_image = in_array(strtolower($file_info['extension']), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        $is_video = in_array(strtolower($file_info['extension']), ['mp4', 'webm', 'ogg', 'mov']);
                        $file_url = $file_info['url'];
                    ?>
                        <?php if ($is_image): ?>
                            <!-- Image Preview (Inline) -->
                            <div class="mm-attachment-image" style="margin-bottom:16px;">
                                <div style="position:relative;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <img src="<?php echo esc_url($file_url); ?>" 
                                         alt="<?php echo esc_attr($file_info['display_name']); ?>"
                                         class="mm-image-preview"
                                         style="width:100%;height:auto;max-width:600px;display:block;cursor:zoom-in;"
                                         onclick="window.open('<?php echo esc_url($file_url); ?>', '_blank')">
                                    <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(to top, rgba(0,0,0,0.7), transparent);padding:12px 16px;">
                                        <div style="color:#fff;font-size:13px;font-weight:500;text-shadow:0 1px 3px rgba(0,0,0,0.3);">
                                            <i class="fa fa-image" style="margin-right:6px;"></i><?php echo esc_html($file_info['display_name']); ?>
                                        </div>
                                        <div style="color:rgba(255,255,255,0.9);font-size:11px;margin-top:2px;">
                                            <?php echo esc_html($file_info['size_formatted']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($is_video): ?>
                            <!-- Video Preview (Inline Player) -->
                            <div class="mm-attachment-video" style="margin-bottom:16px;">
                                <div style="position:relative;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);background:#000;">
                                    <video controls style="width:100%;max-width:600px;display:block;">
                                        <source src="<?php echo esc_url($file_url); ?>" type="video/<?php echo esc_attr($file_info['extension']); ?>">
                                        Your browser doesn't support video.
                                    </video>
                                    <div style="padding:12px 16px;background:rgba(0,0,0,0.9);">
                                        <div style="color:#fff;font-size:13px;font-weight:500;">
                                            <i class="fa fa-play-circle" style="margin-right:6px;color:#ef4444;"></i><?php echo esc_html($file_info['display_name']); ?>
                                        </div>
                                        <div style="color:rgba(255,255,255,0.7);font-size:11px;margin-top:2px;">
                                            <?php echo esc_html($file_info['size_formatted']); ?> • <?php echo strtoupper($file_info['extension']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- File Download Card (Modern Design) -->
                            <div class="mm-attachment-file" style="margin-bottom:12px;">
                                <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:10px;background:linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);box-shadow:0 2px 12px rgba(0,0,0,0.06);transition:all 0.3s ease;" 
                                     onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'"
                                     onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.06)'">
                                    <div style="flex-shrink:0;width:48px;height:48px;border-radius:10px;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(102,126,234,0.4);">
                                        <i class="fa fa-<?php echo $file_info['extension'] === 'zip' ? 'file-archive-o' : ($file_info['extension'] === 'pdf' ? 'file-pdf-o' : 'file-o'); ?>" 
                                           style="color:#fff;font-size:22px;"></i>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-weight:600;font-size:14px;color:#1e293b;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            <?php echo esc_html($file_info['display_name']); ?>
                                        </div>
                                        <div style="font-size:12px;color:#64748b;font-weight:500;">
                                            <?php echo esc_html($file_info['size_formatted']); ?> • <?php echo strtoupper($file_info['extension']); ?> File
                                        </div>
                                    </div>
                                    <a href="<?php echo esc_url($file_url); ?>" 
                                       class="btn btn-sm"
                                       download
                                       style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:600;font-size:13px;box-shadow:0 4px 12px rgba(102,126,234,0.3);transition:all 0.3s ease;white-space:nowrap;"
                                       onmouseover="this.style.transform='scale(1.05)';this.style.boxShadow='0 6px 16px rgba(102,126,234,0.4)'"
                                       onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 4px 12px rgba(102,126,234,0.3)'">
                                        <i class="fa fa-download" style="margin-right:6px;"></i>Download
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
    <!--render history-->
    <?php if ($has_history): ?>
        <div class="well well-sm no-margin">
            <?php foreach ($history_messages as $key => $message): ?>
                <section class="message-content">

                    <div class="page-header">
                        <h3 class="mm-message-subject"><?php echo apply_filters('mm_message_subject', $message->subject) ?></h3>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div style="text-align:center;display:flex;justify-content:center;align-items:center;">
                                            <?php echo PM_Avatar_Handler::get_avatar_html($message->send_from, 100, 'mm-detail-avatar'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <strong><?php echo $message->get_name($message->send_from) ?></strong>

                                        <div class="clearfix"></div>
                                        <span><?php echo date('j. F Y, G:i', strtotime($message->date)) ?></span>

                                        <div class="clearfix"></div>
                                        <?php if (mmg()->get('box') == 'sent'): ?>
                                            <small><?php _e("An:", mmg()->domain) ?> <?php echo $message->get_name($message->send_to) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                    <div class="message-body" style="font-size:15px;line-height:1.7;color:#1f2937;">
                        <?php echo $mm_render_message_body($message); ?>
                    </div>
                    <?php 
                    // PM Attachments (new system) - history messages with modern design
                    $attachment_files = explode(',', $message->attachment);
                    $attachment_files = array_filter($attachment_files);
                    
                    // DEBUG
                    /*if (defined('WP_DEBUG') && WP_DEBUG && count($attachment_files) > 0) {
                        error_log('=== HISTORY ATTACHMENT DEBUG ===');
                        error_log('History Message ID: ' . $message->id);
                        error_log('History Conversation ID: ' . $message->conversation_id);
                        error_log('History Attachment raw: ' . $message->attachment);
                        error_log('History Attachment files count: ' . count($attachment_files));
                    }*/
                    
                    if (count($attachment_files) > 0):
                    ?>
                        <div class="message-footer" style="margin-top:20px;">
                            <div class="mm-attachments-modern">
                                <?php foreach ($attachment_files as $filename): 
                                    $filename = trim($filename);
                                    $file_info = PM_Attachment_Handler::get_file_info($message->conversation_id, $filename);
                                    
                                    // DEBUG
                                    if (defined('WP_DEBUG') && WP_DEBUG) {
                                        error_log('  History File: ' . $filename);
                                        error_log('  History get_file_info result: ' . var_export($file_info, true));
                                    }
                                    
                                    if (!$file_info) continue;
                                    
                                    $is_image = in_array(strtolower($file_info['extension']), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    $is_video = in_array(strtolower($file_info['extension']), ['mp4', 'webm', 'ogg', 'mov']);
                                    $download_url = admin_url('admin-ajax.php?action=mm_download_attachment&conversation_id=' . $message->conversation_id . '&filename=' . urlencode($filename) . '&_wpnonce=' . wp_create_nonce('mm_download_attachment'));
                                ?>
                                    <?php if ($is_image): ?>
                                        <!-- Image Preview -->
                                        <div class="mm-attachment-image" style="margin-bottom:16px;">
                                            <div style="position:relative;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                <img src="<?php echo esc_url($download_url); ?>" 
                                                     alt="<?php echo esc_attr($file_info['display_name']); ?>"
                                                     style="width:100%;height:auto;max-width:600px;display:block;cursor:zoom-in;"
                                                     onclick="window.open('<?php echo esc_url($download_url); ?>', '_blank')">
                                                <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(to top, rgba(0,0,0,0.7), transparent);padding:12px 16px;">
                                                    <div style="color:#fff;font-size:13px;font-weight:500;text-shadow:0 1px 3px rgba(0,0,0,0.3);">
                                                        <i class="fa fa-image" style="margin-right:6px;"></i><?php echo esc_html($file_info['display_name']); ?>
                                                    </div>
                                                    <div style="color:rgba(255,255,255,0.9);font-size:11px;margin-top:2px;">
                                                        <?php echo esc_html($file_info['size_formatted']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php elseif ($is_video): ?>
                                        <!-- Video Player -->
                                        <div class="mm-attachment-video" style="margin-bottom:16px;">
                                            <div style="position:relative;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);background:#000;">
                                                <video controls style="width:100%;max-width:600px;display:block;">
                                                    <source src="<?php echo esc_url($download_url); ?>" type="video/<?php echo esc_attr($file_info['extension']); ?>">
                                                    Your browser doesn't support video.
                                                </video>
                                                <div style="padding:12px 16px;background:rgba(0,0,0,0.9);">
                                                    <div style="color:#fff;font-size:13px;font-weight:500;">
                                                        <i class="fa fa-play-circle" style="margin-right:6px;color:#ef4444;"></i><?php echo esc_html($file_info['display_name']); ?>
                                                    </div>
                                                    <div style="color:rgba(255,255,255,0.7);font-size:11px;margin-top:2px;">
                                                        <?php echo esc_html($file_info['size_formatted']); ?> • <?php echo strtoupper($file_info['extension']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Download Card -->
                                        <div class="mm-attachment-file" style="margin-bottom:12px;">
                                            <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:10px;background:linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);box-shadow:0 2px 12px rgba(0,0,0,0.06);transition:all 0.3s ease;" 
                                                 onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'"
                                                 onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.06)'">
                                                <div style="flex-shrink:0;width:48px;height:48px;border-radius:10px;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(102,126,234,0.4);">
                                                    <i class="fa fa-<?php echo $file_info['extension'] === 'zip' ? 'file-archive-o' : ($file_info['extension'] === 'pdf' ? 'file-pdf-o' : 'file-o'); ?>" 
                                                       style="color:#fff;font-size:22px;"></i>
                                                </div>
                                                <div style="flex:1;min-width:0;">
                                                    <div style="font-weight:600;font-size:14px;color:#1e293b;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                        <?php echo esc_html($file_info['display_name']); ?>
                                                    </div>
                                                    <div style="font-size:12px;color:#64748b;font-weight:500;">
                                                        <?php echo esc_html($file_info['size_formatted']); ?> • <?php echo strtoupper($file_info['extension']); ?> File
                                                    </div>
                                                </div>
                                                <a href="<?php echo esc_url($download_url); ?>" 
                                                   class="btn btn-sm"
                                                   download
                                                   style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:600;font-size:13px;box-shadow:0 4px 12px rgba(102,126,234,0.3);transition:all 0.3s ease;white-space:nowrap;"
                                                   onmouseover="this.style.transform='scale(1.05)';this.style.boxShadow='0 6px 16px rgba(102,126,234,0.4)'"
                                                   onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 4px 12px rgba(102,126,234,0.3)'">
                                                    <i class="fa fa-download" style="margin-right:6px;"></i>Download
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
